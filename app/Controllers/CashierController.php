<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\LaptopRepository;
use App\Repositories\SalesTransactionRepository;
use App\Services\AuthService;

final class CashierController
{
    public function __construct(
        private LaptopRepository $laptops,
        private SalesTransactionRepository $sales,
        private AuthService $auth
    ) {
    }

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
        }

        $isAuthed = $this->auth->isCashierLoggedIn();
        $currentCashier = $this->auth->currentCashier();
        $cashierId = (int)($currentCashier['id'] ?? 0);

        render('cashier', [
            'title' => 'Kasir',
            'isAuthed' => $isAuthed,
            'currentCashier' => $currentCashier,
            'laptops' => $isAuthed ? $this->laptops->allForRanking() : [],
            'transactions' => ($isAuthed && $cashierId > 0) ? $this->sales->allByCashier($cashierId) : [],
            'summary' => [
                'count' => ($isAuthed && $cashierId > 0) ? $this->sales->countByCashier($cashierId) : 0,
                'revenue' => ($isAuthed && $cashierId > 0) ? $this->sales->revenueByCashier($cashierId) : 0,
            ],
        ]);
    }

    private function handlePost(): void
    {
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            flash_set('error', 'Token CSRF tidak valid. Silakan ulangi.');
            redirect(url('cashier'));
        }

        $action = (string)($_POST['action'] ?? '');
        match ($action) {
            'cashier_login' => $this->loginCashier(),
            'cashier_logout' => $this->logoutCashier(),
            'create_sale' => $this->createSale(),
            'delete_sale' => $this->deleteSale(),
            default => redirect(url('cashier')),
        };
    }

    private function loginCashier(): never
    {
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $result = $this->auth->loginCashier($email, $password);
        if (!($result['ok'] ?? false)) {
            flash_set('error', (string)($result['error'] ?? 'Login kasir gagal.'));
            redirect(url('cashier'));
        }

        flash_set('success', 'Login kasir berhasil.');
        redirect(url('cashier'));
    }

    private function logoutCashier(): never
    {
        $this->auth->logoutCashier();
        flash_set('success', 'Anda berhasil logout dari area kasir.');
        redirect(url('cashier'));
    }

    private function createSale(): never
    {
        if (!$this->auth->isCashierLoggedIn()) {
            flash_set('error', 'Silakan login sebagai kasir.');
            redirect(url('cashier'));
        }

        $currentCashier = $this->auth->currentCashier();
        $cashierId = (int)($currentCashier['id'] ?? 0);
        if ($cashierId <= 0) {
            flash_set('error', 'Sesi kasir tidak valid.');
            redirect(url('cashier'));
        }

        $laptopId = (int)($_POST['laptop_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        $customerName = trim((string)($_POST['customer_name'] ?? ''));

        if ($laptopId <= 0 || $quantity <= 0) {
            flash_set('error', 'Laptop dan quantity wajib diisi dengan benar.');
            redirect(url('cashier'));
        }

        $laptop = $this->laptops->find($laptopId);
        if ($laptop === null) {
            flash_set('error', 'Laptop tidak ditemukan.');
            redirect(url('cashier'));
        }

        $invoiceCode = $this->sales->create(
            $laptopId,
            $cashierId,
            $quantity,
            (int)$laptop['price'],
            $customerName !== '' ? $customerName : null
        );

        flash_set('success', 'Transaksi berhasil dibuat dengan kode ' . $invoiceCode . '.');
        redirect(url('cashier'));
    }

    private function deleteSale(): never
    {
        if (!$this->auth->isCashierLoggedIn()) {
            flash_set('error', 'Silakan login sebagai kasir.');
            redirect(url('cashier'));
        }

        $currentCashier = $this->auth->currentCashier();
        $cashierId = (int)($currentCashier['id'] ?? 0);
        $saleId = (int)($_POST['id'] ?? 0);
        if ($cashierId <= 0 || $saleId <= 0) {
            flash_set('error', 'Transaksi tidak valid.');
            redirect(url('cashier'));
        }

        $deleted = $this->sales->deleteByCashier($saleId, $cashierId);
        if (!$deleted) {
            flash_set('error', 'Transaksi tidak ditemukan atau bukan milik Anda.');
            redirect(url('cashier'));
        }

        flash_set('success', 'Transaksi berhasil dihapus.');
        redirect(url('cashier'));
    }
}

