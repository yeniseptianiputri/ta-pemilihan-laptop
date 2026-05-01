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
        $this->ensureRolesTable();
        $this->ensureDefaultRoles();

        if (!$this->hasColumn('users', 'role_id')) {
            $this->pdo->exec('ALTER TABLE users ADD COLUMN role_id TINYINT UNSIGNED NULL AFTER password_hash');
        }

        if ($this->hasColumn('users', 'role')) {
            $this->pdo->exec(
                "UPDATE users u
                JOIN roles r ON r.code = u.role
                SET u.role_id = r.id
                WHERE u.role_id IS NULL"
            );
        }

        $defaultRoleId = $this->resolveRoleId('user');
        $statement = $this->pdo->prepare(
            'UPDATE users
            SET role_id = :role_id
            WHERE role_id IS NULL OR role_id = 0'
        );
        $statement->execute(['role_id' => $defaultRoleId]);

        if (!$this->hasIndex('users', 'idx_users_role_id')) {
            $this->pdo->exec('ALTER TABLE users ADD INDEX idx_users_role_id (role_id)');
        }

        if (!$this->hasRoleForeignKey()) {
            $this->pdo->exec(
                'ALTER TABLE users
                ADD CONSTRAINT fk_users_role_runtime
                FOREIGN KEY (role_id) REFERENCES roles(id)
                ON UPDATE CASCADE
                ON DELETE RESTRICT'
            );
        }
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            "SELECT u.id, u.name, u.email, u.password_hash, r.code AS role, u.created_at
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE u.id = :id
            LIMIT 1"
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row !== false ? $row : null;
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            "SELECT u.id, u.name, u.email, u.password_hash, r.code AS role, u.created_at
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE u.email = :email
            LIMIT 1"
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
            "SELECT u.id, u.name, u.email, r.code AS role, u.created_at
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            ORDER BY FIELD(r.code, 'admin', 'cashier', 'user'), u.id ASC"
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
            'INSERT INTO users (name, email, password_hash, role_id)
            VALUES (:name, :email, :password_hash, :role_id)'
        );
        $statement->execute([
            'name' => $name,
            'email' => strtolower(trim($email)),
            'password_hash' => $passwordHash,
            'role_id' => $this->resolveRoleId($role),
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
                role_id = :role_id
            WHERE id = :id'
        );
        $statement->execute([
            'id' => $current['id'],
            'name' => $needsNameUpdate ? $name : $current['name'],
            'password_hash' => $needsPasswordUpdate
                ? password_hash($plainPassword, PASSWORD_DEFAULT)
                : $current['password_hash'],
            'role_id' => $needsRoleUpdate ? $this->resolveRoleId($role) : $this->resolveRoleId((string)$current['role']),
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
            'role_id = :role_id',
        ];
        $params = [
            'id' => $id,
            'name' => $name,
            'email' => strtolower(trim($email)),
            'role_id' => $this->resolveRoleId($role),
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
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE role_id = :role_id');
        $statement->execute(['role_id' => $this->resolveRoleId($role)]);

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

    private function ensureRolesTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS roles (
                id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(20) NOT NULL UNIQUE,
                label VARCHAR(60) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB'
        );
    }

    private function ensureDefaultRoles(): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO roles (code, label)
            VALUES (:code, :label)
            ON DUPLICATE KEY UPDATE label = VALUES(label)'
        );

        foreach ([
            'admin' => 'Administrator',
            'cashier' => 'Kasir',
            'user' => 'Pengguna',
        ] as $code => $label) {
            $statement->execute([
                'code' => $code,
                'label' => $label,
            ]);
        }
    }

    private function resolveRoleId(string $role): int
    {
        $statement = $this->pdo->prepare('SELECT id FROM roles WHERE code = :code LIMIT 1');
        $statement->execute(['code' => $role]);
        $id = (int)$statement->fetchColumn();

        if ($id > 0) {
            return $id;
        }

        $fallback = $this->pdo->prepare('SELECT id FROM roles WHERE code = :code LIMIT 1');
        $fallback->execute(['code' => 'user']);

        return (int)$fallback->fetchColumn();
    }

    private function hasColumn(string $table, string $column): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = :table
            AND COLUMN_NAME = :column'
        );
        $statement->execute([
            'table' => $table,
            'column' => $column,
        ]);

        return (int)$statement->fetchColumn() > 0;
    }

    private function hasIndex(string $table, string $index): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*)
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = :table
            AND INDEX_NAME = :index'
        );
        $statement->execute([
            'table' => $table,
            'index' => $index,
        ]);

        return (int)$statement->fetchColumn() > 0;
    }

    private function hasRoleForeignKey(): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*)
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = "users"
            AND COLUMN_NAME = "role_id"
            AND REFERENCED_TABLE_NAME = "roles"'
        );
        $statement->execute();

        return (int)$statement->fetchColumn() > 0;
    }
}
