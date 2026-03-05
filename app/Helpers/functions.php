<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\View;

if (!function_exists('env')) {
    function env(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return (string)$value;
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('render')) {
    function render(string $view, array $data = []): void
    {
        View::render($view, $data);
    }
}

if (!function_exists('url')) {
    function url(string $page = 'home', array $params = []): string
    {
        $query = ['page' => $page];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $query[$key] = $value;
        }

        return 'index.php?' . http_build_query($query);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $to): never
    {
        header('Location: ' . $to);
        exit;
    }
}

if (!function_exists('current_page')) {
    function current_page(): string
    {
        return (string)($_GET['page'] ?? 'home');
    }
}

if (!function_exists('is_active_page')) {
    function is_active_page(string $page): bool
    {
        return current_page() === $page;
    }
}

if (!function_exists('is_admin_logged_in')) {
    function is_admin_logged_in(): bool
    {
        return isset($_SESSION['admin_user_id']) && (int)$_SESSION['admin_user_id'] > 0;
    }
}

if (!function_exists('format_rupiah')) {
    function format_rupiah(float|int $value): string
    {
        return 'Rp ' . number_format((float)$value, 0, ',', '.');
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf(?string $token): bool
    {
        return Csrf::verify($token);
    }
}

if (!function_exists('flash_set')) {
    function flash_set(string $type, string $message): void
    {
        if (!isset($_SESSION['_flash']) || !is_array($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [];
        }
        if (!isset($_SESSION['_flash'][$type]) || !is_array($_SESSION['_flash'][$type])) {
            $_SESSION['_flash'][$type] = [];
        }

        $_SESSION['_flash'][$type][] = $message;
    }
}

if (!function_exists('flash_pull')) {
    function flash_pull(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);

        return is_array($messages) ? $messages : [];
    }
}
