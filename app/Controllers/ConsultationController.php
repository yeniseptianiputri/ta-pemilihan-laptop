<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Repositories\LaptopRepository;
use App\Services\AuthService;
use App\Services\ChatService;
use RuntimeException;

final class ConsultationController
{
    public function __construct(
        private AuthService $auth,
        private ChatService $chat,
        private LaptopRepository $laptops
    ) {
    }

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
        }

        render('consultation', [
            'title' => 'Konsultasi',
            'isAuthed' => $this->auth->isUserLoggedIn(),
            'messages' => Session::get('chat_messages', []),
            'catalogCount' => count($this->laptops->all()),
            'currentUser' => $this->auth->currentUser(),
        ]);
    }

    private function handlePost(): void
    {
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            flash_set('error', 'Token CSRF tidak valid. Silakan ulangi.');
            redirect(url('konsultasi'));
        }

        $action = (string)($_POST['action'] ?? '');

        match ($action) {
            'user_login' => $this->handleLogin(),
            'user_register' => $this->handleRegister(),
            'user_logout' => $this->handleLogout(),
            'send_chat' => $this->handleSendChat(),
            'reset_chat' => $this->handleResetChat(),
            default => redirect(url('konsultasi')),
        };
    }

    private function handleLogin(): never
    {
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $result = $this->auth->loginUser($email, $password);
        if (!($result['ok'] ?? false)) {
            flash_set('error', (string)($result['error'] ?? 'Login gagal.'));
            redirect(url('konsultasi'));
        }

        flash_set('success', 'Login berhasil. Selamat datang.');
        redirect(url('konsultasi'));
    }

    private function handleRegister(): never
    {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        $result = $this->auth->registerUser($name, $email, $password, $confirmPassword);
        if (!($result['ok'] ?? false)) {
            flash_set('error', (string)($result['error'] ?? 'Registrasi gagal.'));
            redirect(url('konsultasi'));
        }

        flash_set('success', 'Registrasi berhasil. Anda sudah login.');
        redirect(url('konsultasi'));
    }

    private function handleLogout(): never
    {
        $this->auth->logoutUser();
        Session::forget('chat_messages');
        flash_set('success', 'Anda berhasil logout.');
        redirect(url('konsultasi'));
    }

    private function handleSendChat(): never
    {
        if (!$this->auth->isUserLoggedIn()) {
            flash_set('error', 'Silakan login dulu untuk berkonsultasi.');
            redirect(url('konsultasi'));
        }

        $budget = (int)($_POST['budget'] ?? 0);
        $needs = trim((string)($_POST['needs'] ?? ''));

        if ($budget <= 0) {
            flash_set('error', 'Budget wajib diisi dan lebih dari 0.');
            redirect(url('konsultasi'));
        }

        $question = $needs !== '' ? $needs : 'Tolong rekomendasikan laptop yang cocok dengan budget saya.';
        $messages = Session::get('chat_messages', []);
        if (!is_array($messages)) {
            $messages = [];
        }

        $messages[] = [
            'role' => 'user',
            'text' => "Budget: " . format_rupiah($budget) . "\nKebutuhan: " . $question,
        ];

        try {
            $reply = $this->chat->ask(
                $question,
                (float)$budget,
                $needs,
                $this->laptops->allForRanking()
            );
            $messages[] = [
                'role' => 'assistant',
                'text' => $reply,
            ];
            Session::set('chat_messages', $messages);
        } catch (RuntimeException $exception) {
            flash_set('error', $exception->getMessage());
        }

        redirect(url('konsultasi'));
    }

    private function handleResetChat(): never
    {
        Session::forget('chat_messages');
        flash_set('success', 'Riwayat chat berhasil direset.');
        redirect(url('konsultasi'));
    }
}

