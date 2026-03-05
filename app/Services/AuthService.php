<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Repositories\UserRepository;

final class AuthService
{
    private const ADMIN_SESSION_KEY = 'admin_user_id';
    private const USER_SESSION_KEY = 'frontend_user_id';

    public function __construct(
        private UserRepository $users,
        private array $authConfig
    ) {
    }

    public function ensureDefaultAccounts(): void
    {
        $adminEmail = trim((string)($this->authConfig['admin_email'] ?? ''));
        $adminPassword = trim((string)($this->authConfig['admin_password'] ?? ''));
        if ($adminEmail !== '' && $adminPassword !== '') {
            $this->users->ensureUser($adminEmail, $adminPassword, 'admin', 'Administrator');
        }

        $userEmail = trim((string)($this->authConfig['default_user_email'] ?? ''));
        $userPassword = trim((string)($this->authConfig['default_user_password'] ?? ''));
        if ($userEmail !== '' && $userPassword !== '') {
            $this->users->ensureUser($userEmail, $userPassword, 'user', 'User Default');
        }
    }

    public function loginAdmin(string $email, string $password): array
    {
        $user = $this->users->validateCredentials($email, $password, 'admin');
        if ($user === null) {
            return ['ok' => false, 'error' => 'Email atau password admin salah.'];
        }

        Session::regenerate();
        Session::set(self::ADMIN_SESSION_KEY, (int)$user['id']);

        return ['ok' => true];
    }

    public function logoutAdmin(): void
    {
        Session::forget(self::ADMIN_SESSION_KEY);
    }

    public function isAdminLoggedIn(): bool
    {
        return Session::has(self::ADMIN_SESSION_KEY);
    }

    public function currentAdmin(): ?array
    {
        $userId = (int)Session::get(self::ADMIN_SESSION_KEY, 0);
        if ($userId <= 0) {
            return null;
        }

        $user = $this->users->findById($userId);
        if ($user === null || $user['role'] !== 'admin') {
            return null;
        }

        return $user;
    }

    public function loginUser(string $email, string $password): array
    {
        $user = $this->users->validateCredentials($email, $password);
        if ($user === null) {
            return ['ok' => false, 'error' => 'Email atau password user salah.'];
        }

        Session::regenerate();
        Session::set(self::USER_SESSION_KEY, (int)$user['id']);

        return ['ok' => true];
    }

    public function registerUser(
        string $name,
        string $email,
        string $password,
        string $confirmPassword
    ): array {
        $name = trim($name);
        $email = strtolower(trim($email));

        if ($email === '' || $password === '') {
            return ['ok' => false, 'error' => 'Email dan password wajib diisi.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Format email tidak valid.'];
        }

        if (strlen($password) < 6) {
            return ['ok' => false, 'error' => 'Password minimal 6 karakter.'];
        }

        if ($password !== $confirmPassword) {
            return ['ok' => false, 'error' => 'Konfirmasi password tidak sama.'];
        }

        if ($this->users->emailExists($email)) {
            return ['ok' => false, 'error' => 'Email sudah terdaftar.'];
        }

        $id = $this->users->create(
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            'user',
            $name !== '' ? $name : null
        );

        Session::regenerate();
        Session::set(self::USER_SESSION_KEY, $id);

        return ['ok' => true];
    }

    public function logoutUser(): void
    {
        Session::forget(self::USER_SESSION_KEY);
    }

    public function isUserLoggedIn(): bool
    {
        return Session::has(self::USER_SESSION_KEY);
    }

    public function currentUser(): ?array
    {
        $userId = (int)Session::get(self::USER_SESSION_KEY, 0);
        if ($userId <= 0) {
            return null;
        }

        return $this->users->findById($userId);
    }
}

