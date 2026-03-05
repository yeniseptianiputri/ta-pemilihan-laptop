<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\LaptopRepository;
use App\Services\AuthService;

final class AdminController
{
    public function __construct(
        private LaptopRepository $laptops,
        private AuthService $auth
    ) {
    }

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
        }

        $isAuthed = $this->auth->isAdminLoggedIn();
        $items = $isAuthed ? $this->laptops->all() : [];
        $editing = null;

        $editId = (int)($_GET['edit'] ?? 0);
        if ($isAuthed && $editId > 0) {
            $editing = $this->laptops->find($editId);
        }

        render('admin', [
            'title' => 'Admin',
            'isAuthed' => $isAuthed,
            'laptops' => $items,
            'editing' => $editing,
        ]);
    }

    private function handlePost(): void
    {
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            flash_set('error', 'Token CSRF tidak valid. Silakan ulangi.');
            redirect(url('admin'));
        }

        $action = (string)($_POST['action'] ?? '');
        match ($action) {
            'admin_login' => $this->loginAdmin(),
            'admin_logout' => $this->logoutAdmin(),
            'create_laptop' => $this->createLaptop(),
            'update_laptop' => $this->updateLaptop(),
            'delete_laptop' => $this->deleteLaptop(),
            'restore_laptops' => $this->restoreLaptops(),
            default => redirect(url('admin')),
        };
    }

    private function loginAdmin(): never
    {
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $result = $this->auth->loginAdmin($email, $password);
        if (!($result['ok'] ?? false)) {
            flash_set('error', (string)($result['error'] ?? 'Login admin gagal.'));
            redirect(url('admin'));
        }

        flash_set('success', 'Login admin berhasil.');
        redirect(url('admin'));
    }

    private function logoutAdmin(): never
    {
        $this->auth->logoutAdmin();
        flash_set('success', 'Anda berhasil logout dari area admin.');
        redirect(url('admin'));
    }

    private function createLaptop(): never
    {
        if (!$this->auth->isAdminLoggedIn()) {
            flash_set('error', 'Silakan login sebagai admin.');
            redirect(url('admin'));
        }

        [$valid, $payload, $error] = $this->validateLaptopPayload($_POST);
        if (!$valid) {
            flash_set('error', $error);
            redirect(url('admin'));
        }

        $this->laptops->create($payload);
        flash_set('success', 'Laptop berhasil ditambahkan.');
        redirect(url('admin'));
    }

    private function updateLaptop(): never
    {
        if (!$this->auth->isAdminLoggedIn()) {
            flash_set('error', 'Silakan login sebagai admin.');
            redirect(url('admin'));
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash_set('error', 'Data laptop tidak valid.');
            redirect(url('admin'));
        }

        [$valid, $payload, $error] = $this->validateLaptopPayload($_POST);
        if (!$valid) {
            flash_set('error', $error);
            redirect(url('admin', ['edit' => $id]));
        }

        $this->laptops->update($id, $payload);
        flash_set('success', 'Data laptop berhasil diperbarui.');
        redirect(url('admin'));
    }

    private function deleteLaptop(): never
    {
        if (!$this->auth->isAdminLoggedIn()) {
            flash_set('error', 'Silakan login sebagai admin.');
            redirect(url('admin'));
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $this->laptops->delete($id);
            flash_set('success', 'Laptop berhasil dihapus.');
        }

        redirect(url('admin'));
    }

    private function restoreLaptops(): never
    {
        if (!$this->auth->isAdminLoggedIn()) {
            flash_set('error', 'Silakan login sebagai admin.');
            redirect(url('admin'));
        }

        $this->laptops->restoreDefaults();
        flash_set('success', 'Data laptop sudah di-reset ke default.');
        redirect(url('admin'));
    }

    private function validateLaptopPayload(array $input): array
    {
        $name = trim((string)($input['name'] ?? ''));
        $ram = (int)($input['ram'] ?? 0);
        $storage = (int)($input['storage'] ?? 0);
        $processor = (int)($input['processor'] ?? 0);
        $price = (int)($input['price'] ?? 0);

        if ($name === '') {
            return [false, [], 'Nama laptop wajib diisi.'];
        }

        if ($ram <= 0 || $storage <= 0 || $processor <= 0 || $price <= 0) {
            return [false, [], 'Semua angka wajib lebih dari 0.'];
        }

        return [
            true,
            [
                'name' => $name,
                'ram' => $ram,
                'storage' => $storage,
                'processor' => $processor,
                'price' => $price,
            ],
            null,
        ];
    }
}

