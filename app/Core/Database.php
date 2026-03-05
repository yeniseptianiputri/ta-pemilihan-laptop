<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function init(array $config): void
    {
        if (self::$connection instanceof PDO) {
            return;
        }

        $host = (string)($config['host'] ?? '127.0.0.1');
        $port = (int)($config['port'] ?? 3306);
        $name = (string)($config['name'] ?? '');
        $user = (string)($config['user'] ?? 'root');
        $password = (string)($config['password'] ?? '');
        $charset = (string)($config['charset'] ?? 'utf8mb4');

        if ($name === '') {
            throw new RuntimeException('Konfigurasi DB_NAME belum diisi.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $host,
            $port,
            $name,
            $charset
        );

        try {
            self::$connection = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'Koneksi MySQL gagal. Pastikan service Laragon aktif dan konfigurasi .env benar.',
                previous: $exception
            );
        }
    }

    public static function connection(): PDO
    {
        if (!self::$connection instanceof PDO) {
            throw new RuntimeException('Database belum diinisialisasi.');
        }

        return self::$connection;
    }
}

