<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function ensureRoleSchema(): void
    {
        $column = $this->pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch();
        if ($column === false) {
            return;
        }

        $type = strtolower((string)($column['Type'] ?? ''));
        if (str_contains($type, "'cashier'")) {
            return;
        }

        $this->pdo->exec(
            "ALTER TABLE users
            MODIFY role ENUM('admin', 'cashier', 'user') NOT NULL DEFAULT 'user'"
        );
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, email, password_hash, role, created_at
            FROM users
            WHERE id = :id
            LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row !== false ? $row : null;
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, email, password_hash, role, created_at
            FROM users
            WHERE email = :email
            LIMIT 1'
        );
        $statement->execute(['email' => strtolower(trim($email))]);
        $row = $statement->fetch();

        return $row !== false ? $row : null;
    }

    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    public function emailExistsForOtherUser(string $email, int $excludeId): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT id
            FROM users
            WHERE email = :email
            AND id <> :exclude_id
            LIMIT 1'
        );
        $statement->execute([
            'email' => strtolower(trim($email)),
            'exclude_id' => $excludeId,
        ]);

        return $statement->fetch() !== false;
    }

    public function allManaged(): array
    {
        $statement = $this->pdo->query(
            "SELECT id, name, email, role, created_at
            FROM users
            ORDER BY FIELD(role, 'admin', 'cashier', 'user'), id ASC"
        );

        return $statement->fetchAll();
    }

    public function create(
        string $email,
        string $passwordHash,
        string $role = 'user',
        ?string $name = null
    ): int {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role)
            VALUES (:name, :email, :password_hash, :role)'
        );
        $statement->execute([
            'name' => $name,
            'email' => strtolower(trim($email)),
            'password_hash' => $passwordHash,
            'role' => $role,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function ensureUser(
        string $email,
        string $plainPassword,
        string $role,
        ?string $name = null
    ): void {
        $current = $this->findByEmail($email);
        $email = strtolower(trim($email));

        if ($current === null) {
            $this->create($email, password_hash($plainPassword, PASSWORD_DEFAULT), $role, $name);
            return;
        }

        $needsPasswordUpdate = !password_verify($plainPassword, (string)$current['password_hash']);
        $needsRoleUpdate = $current['role'] !== $role;
        $needsNameUpdate = $name !== null && trim($name) !== '' && (string)($current['name'] ?? '') !== $name;

        if (!$needsPasswordUpdate && !$needsRoleUpdate && !$needsNameUpdate) {
            return;
        }

        $statement = $this->pdo->prepare(
            'UPDATE users
            SET name = :name,
                password_hash = :password_hash,
                role = :role
            WHERE id = :id'
        );
        $statement->execute([
            'id' => $current['id'],
            'name' => $needsNameUpdate ? $name : $current['name'],
            'password_hash' => $needsPasswordUpdate
                ? password_hash($plainPassword, PASSWORD_DEFAULT)
                : $current['password_hash'],
            'role' => $needsRoleUpdate ? $role : $current['role'],
        ]);
    }

    public function updateManagedUser(
        int $id,
        string $email,
        string $role,
        ?string $name,
        ?string $passwordHash = null
    ): void {
        $fields = [
            'name = :name',
            'email = :email',
            'role = :role',
        ];
        $params = [
            'id' => $id,
            'name' => $name,
            'email' => strtolower(trim($email)),
            'role' => $role,
        ];

        if ($passwordHash !== null && $passwordHash !== '') {
            $fields[] = 'password_hash = :password_hash';
            $params['password_hash'] = $passwordHash;
        }

        $statement = $this->pdo->prepare(
            'UPDATE users
            SET ' . implode(",\n                ", $fields) . '
            WHERE id = :id'
        );
        $statement->execute($params);
    }

    public function deleteById(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function countByRole(string $role): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE role = :role');
        $statement->execute(['role' => $role]);

        return (int)$statement->fetchColumn();
    }

    public function validateCredentials(string $email, string $password, ?string $role = null): ?array
    {
        $user = $this->findByEmail($email);
        if ($user === null) {
            return null;
        }

        if ($role !== null && $user['role'] !== $role) {
            return null;
        }

        if (!password_verify($password, (string)$user['password_hash'])) {
            return null;
        }

        return $user;
    }
}
