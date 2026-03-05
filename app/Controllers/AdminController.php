<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\LaptopRepository;
use App\Repositories\SalesTransactionRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;

final class AdminController
{
    public function __construct(
        private LaptopRepository $laptops,
        private AuthService $auth,
        private UserRepository $users,
        private SalesTransactionRepository $sales
    ) {
    }

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
        }

        $isAuthed = $this->auth->isAdminLoggedIn();
        $items = $isAuthed ? $this->laptops->all() : [];
        $users = $isAuthed ? $this->users->allManaged() : [];
        $sales = $isAuthed ? $this->sales->all() : [];
        $editing = null;
        $editingUser = null;
        $currentAdmin = $isAuthed ? $this->auth->currentAdmin() : null;

        $editId = (int)($_GET['edit'] ?? 0);
        if ($isAuthed && $editId > 0) {
            $editing = $this->laptops->find($editId);
        }

        $editUserId = (int)($_GET['edit_user'] ?? 0);
        if ($isAuthed && $editUserId > 0) {
            $editingUser = $this->users->findById($editUserId);
        }

        render('admin', [
            'title' => 'Admin',
            'isAuthed' => $isAuthed,
            'laptops' => $items,
            'editing' => $editing,
            'users' => $users,
            'editingUser' => $editingUser,
            'currentAdmin' => $currentAdmin,
            'sales' => $sales,
            'salesSummary' => [
                'count' => $isAuthed ? $this->sales->countAll() : 0,
                'revenue' => $isAuthed ? $this->sales->revenueAll() : 0,
            ],
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
            'create_user' => $this->createUser(),
            'update_user' => $this->updateUser(),
            'delete_user' => $this->deleteUser(),
            'delete_sale' => $this->deleteSale(),
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

    private function createUser(): never
    {
        if (!$this->auth->isAdminLoggedIn()) {
            flash_set('error', 'Silakan login sebagai admin.');
            redirect(url('admin'));
        }

        [$valid, $payload, $error] = $this->validateUserPayload($_POST, true);
        if (!$valid) {
            flash_set('error', $error);
            redirect(url('admin'));
        }

        if ($this->users->emailExists($payload['email'])) {
            flash_set('error', 'Email sudah digunakan oleh akun lain.');
            redirect(url('admin'));
        }

        $this->users->create(
            $payload['email'],
            password_hash($payload['password'], PASSWORD_DEFAULT),
            $payload['role'],
            $payload['name']
        );

        flash_set('success', 'Akun baru berhasil ditambahkan.');
        redirect(url('admin'));
    }

    private function updateUser(): never
    {
        if (!$this->auth->isAdminLoggedIn()) {
            flash_set('error', 'Silakan login sebagai admin.');
            redirect(url('admin'));
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash_set('error', 'Data user tidak valid.');
            redirect(url('admin'));
        }

        $target = $this->users->findById($id);
        if ($target === null) {
            flash_set('error', 'User tidak ditemukan.');
            redirect(url('admin'));
        }

        [$valid, $payload, $error] = $this->validateUserPayload($_POST, false);
        if (!$valid) {
            flash_set('error', $error);
            redirect(url('admin', ['edit_user' => $id]));
        }

        if ($this->users->emailExistsForOtherUser($payload['email'], $id)) {
            flash_set('error', 'Email sudah digunakan oleh akun lain.');
            redirect(url('admin', ['edit_user' => $id]));
        }

        $currentAdmin = $this->auth->currentAdmin();
        $currentAdminId = (int)($currentAdmin['id'] ?? 0);
        if ($currentAdminId === $id && $payload['role'] !== 'admin') {
            flash_set('error', 'Role akun Anda sendiri harus tetap admin.');
            redirect(url('admin', ['edit_user' => $id]));
        }

        $isDemotingLastAdmin = $target['role'] === 'admin'
            && $payload['role'] !== 'admin'
            && $this->users->countByRole('admin') <= 1;

        if ($isDemotingLastAdmin) {
            flash_set('error', 'Setidaknya harus ada 1 akun admin aktif.');
            redirect(url('admin', ['edit_user' => $id]));
        }

        $passwordHash = null;
        if ($payload['password'] !== '') {
            $passwordHash = password_hash($payload['password'], PASSWORD_DEFAULT);
        }

        $this->users->updateManagedUser(
            $id,
            $payload['email'],
            $payload['role'],
            $payload['name'],
            $passwordHash
        );

        flash_set('success', 'Data user berhasil diperbarui.');
        redirect(url('admin'));
    }

    private function deleteUser(): never
    {
        if (!$this->auth->isAdminLoggedIn()) {
            flash_set('error', 'Silakan login sebagai admin.');
            redirect(url('admin'));
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash_set('error', 'Data user tidak valid.');
            redirect(url('admin'));
        }

        $target = $this->users->findById($id);
        if ($target === null) {
            flash_set('error', 'User tidak ditemukan.');
            redirect(url('admin'));
        }

        $currentAdmin = $this->auth->currentAdmin();
        $currentAdminId = (int)($currentAdmin['id'] ?? 0);
        if ($currentAdminId === $id) {
            flash_set('error', 'Akun admin yang sedang dipakai tidak bisa dihapus.');
            redirect(url('admin'));
        }

        if ($target['role'] === 'admin' && $this->users->countByRole('admin') <= 1) {
            flash_set('error', 'Tidak bisa menghapus admin terakhir.');
            redirect(url('admin'));
        }

        $this->users->deleteById($id);
        flash_set('success', 'User berhasil dihapus.');
        redirect(url('admin'));
    }

    private function deleteSale(): never
    {
        if (!$this->auth->isAdminLoggedIn()) {
            flash_set('error', 'Silakan login sebagai admin.');
            redirect(url('admin'));
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash_set('error', 'Transaksi tidak valid.');
            redirect(url('admin'));
        }

        $this->sales->delete($id);
        flash_set('success', 'Transaksi kasir berhasil dihapus.');
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

    private function validateUserPayload(array $input, bool $isCreate): array
    {
        $name = trim((string)($input['name'] ?? ''));
        $email = strtolower(trim((string)($input['email'] ?? '')));
        $role = trim((string)($input['role'] ?? 'user'));
        $password = (string)($input['password'] ?? '');

        if ($email === '') {
            return [false, [], 'Email wajib diisi.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [false, [], 'Format email tidak valid.'];
        }

        if (!in_array($role, ['admin', 'cashier', 'user'], true)) {
            return [false, [], 'Role user tidak valid.'];
        }

        if ($isCreate && strlen($password) < 6) {
            return [false, [], 'Password minimal 6 karakter.'];
        }

        if (!$isCreate && $password !== '' && strlen($password) < 6) {
            return [false, [], 'Password baru minimal 6 karakter.'];
        }

        return [
            true,
            [
                'name' => $name !== '' ? $name : null,
                'email' => $email,
                'role' => $role,
                'password' => $password,
            ],
            null,
        ];
    }
}
