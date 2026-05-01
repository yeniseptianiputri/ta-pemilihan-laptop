<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Throwable;

final class SalesTransactionRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function ensureSchema(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS customers (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(120) NOT NULL UNIQUE,
                phone VARCHAR(40) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB'
        );

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS sales_orders (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                order_code VARCHAR(40) NOT NULL UNIQUE,
                cashier_id INT UNSIGNED NOT NULL,
                customer_id INT UNSIGNED NULL,
                customer_note VARCHAR(120) NULL,
                order_status ENUM("paid", "unpaid", "cancelled", "refunded") NOT NULL DEFAULT "paid",
                grand_total BIGINT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_sales_orders_cashier_id (cashier_id),
                INDEX idx_sales_orders_customer_id (customer_id),
                INDEX idx_sales_orders_created_at (created_at),
                CONSTRAINT fk_sales_orders_cashier_runtime
                    FOREIGN KEY (cashier_id) REFERENCES users(id)
                    ON UPDATE CASCADE
                    ON DELETE RESTRICT,
                CONSTRAINT fk_sales_orders_customer_runtime
                    FOREIGN KEY (customer_id) REFERENCES customers(id)
                    ON UPDATE CASCADE
                    ON DELETE SET NULL
            ) ENGINE=InnoDB'
        );

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS sales_order_items (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                order_id BIGINT UNSIGNED NOT NULL,
                laptop_id INT UNSIGNED NULL,
                quantity SMALLINT UNSIGNED NOT NULL,
                unit_price INT UNSIGNED NOT NULL,
                line_total BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_sales_items_order_id (order_id),
                INDEX idx_sales_items_laptop_id (laptop_id),
                CONSTRAINT fk_sales_items_order_runtime
                    FOREIGN KEY (order_id) REFERENCES sales_orders(id)
                    ON UPDATE CASCADE
                    ON DELETE CASCADE,
                CONSTRAINT fk_sales_items_laptop_runtime
                    FOREIGN KEY (laptop_id) REFERENCES laptops(id)
                    ON UPDATE CASCADE
                    ON DELETE SET NULL
            ) ENGINE=InnoDB'
        );

        $this->migrateLegacySalesTransactions();
    }

    public function create(
        int $laptopId,
        int $cashierId,
        int $quantity,
        int $unitPrice,
        ?string $customerName = null
    ): string {
        $orderCode = $this->generateOrderCode();
        $lineTotal = $unitPrice * $quantity;
        $customerId = $this->findOrCreateCustomer($customerName);

        $this->pdo->beginTransaction();
        try {
            $orderStatement = $this->pdo->prepare(
                'INSERT INTO sales_orders
                (order_code, cashier_id, customer_id, customer_note, order_status, grand_total)
                VALUES
                (:order_code, :cashier_id, :customer_id, :customer_note, :order_status, :grand_total)'
            );
            $orderStatement->execute([
                'order_code' => $orderCode,
                'cashier_id' => $cashierId,
                'customer_id' => $customerId,
                'customer_note' => $customerName,
                'order_status' => 'paid',
                'grand_total' => $lineTotal,
            ]);

            $orderId = (int)$this->pdo->lastInsertId();

            $itemStatement = $this->pdo->prepare(
                'INSERT INTO sales_order_items
                (order_id, laptop_id, quantity, unit_price, line_total)
                VALUES
                (:order_id, :laptop_id, :quantity, :unit_price, :line_total)'
            );
            $itemStatement->execute([
                'order_id' => $orderId,
                'laptop_id' => $laptopId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ]);

            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }

        return $orderCode;
    }

    public function all(): array
    {
        $statement = $this->pdo->query(
            "SELECT
                oi.id,
                o.order_code AS invoice_code,
                COALESCE(NULLIF(TRIM(c.full_name), ''), NULLIF(TRIM(o.customer_note), ''), '-') AS customer_name,
                oi.quantity,
                oi.unit_price,
                oi.line_total AS total_price,
                o.created_at,
                COALESCE(l.name, '[Laptop terhapus]') AS laptop_name,
                COALESCE(NULLIF(TRIM(u.name), ''), u.email, '[Kasir]') AS cashier_name
            FROM sales_order_items oi
            INNER JOIN sales_orders o ON o.id = oi.order_id
            LEFT JOIN laptops l ON l.id = oi.laptop_id
            LEFT JOIN users u ON u.id = o.cashier_id
            LEFT JOIN customers c ON c.id = o.customer_id
            ORDER BY oi.id DESC"
        );

        return $statement->fetchAll();
    }

    public function allByCashier(int $cashierId): array
    {
        $statement = $this->pdo->prepare(
            "SELECT
                oi.id,
                o.order_code AS invoice_code,
                COALESCE(NULLIF(TRIM(c.full_name), ''), NULLIF(TRIM(o.customer_note), ''), '-') AS customer_name,
                oi.quantity,
                oi.unit_price,
                oi.line_total AS total_price,
                o.created_at,
                COALESCE(l.name, '[Laptop terhapus]') AS laptop_name
            FROM sales_order_items oi
            INNER JOIN sales_orders o ON o.id = oi.order_id
            LEFT JOIN laptops l ON l.id = oi.laptop_id
            LEFT JOIN customers c ON c.id = o.customer_id
            WHERE o.cashier_id = :cashier_id
            ORDER BY oi.id DESC"
        );
        $statement->execute(['cashier_id' => $cashierId]);

        return $statement->fetchAll();
    }

    public function delete(int $id): void
    {
        $this->pdo->beginTransaction();
        try {
            $orderId = $this->orderIdByItemId($id);
            if ($orderId <= 0) {
                $this->pdo->rollBack();
                return;
            }

            $statement = $this->pdo->prepare('DELETE FROM sales_order_items WHERE id = :id');
            $statement->execute(['id' => $id]);

            $this->cleanupOrderIfEmpty($orderId);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function deleteByCashier(int $id, int $cashierId): bool
    {
        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare(
                'SELECT oi.order_id
                FROM sales_order_items oi
                INNER JOIN sales_orders o ON o.id = oi.order_id
                WHERE oi.id = :id
                AND o.cashier_id = :cashier_id
                LIMIT 1'
            );
            $statement->execute([
                'id' => $id,
                'cashier_id' => $cashierId,
            ]);

            $orderId = (int)$statement->fetchColumn();
            if ($orderId <= 0) {
                $this->pdo->rollBack();
                return false;
            }

            $deleteStatement = $this->pdo->prepare('DELETE FROM sales_order_items WHERE id = :id');
            $deleteStatement->execute(['id' => $id]);

            $this->cleanupOrderIfEmpty($orderId);
            $this->pdo->commit();

            return true;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function countAll(): int
    {
        return (int)$this->pdo->query('SELECT COUNT(*) FROM sales_order_items')->fetchColumn();
    }

    public function countByCashier(int $cashierId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*)
            FROM sales_order_items oi
            INNER JOIN sales_orders o ON o.id = oi.order_id
            WHERE o.cashier_id = :cashier_id'
        );
        $statement->execute(['cashier_id' => $cashierId]);

        return (int)$statement->fetchColumn();
    }

    public function revenueAll(): int
    {
        return (int)$this->pdo->query(
            'SELECT COALESCE(SUM(line_total), 0) FROM sales_order_items'
        )->fetchColumn();
    }

    public function revenueByCashier(int $cashierId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COALESCE(SUM(oi.line_total), 0)
            FROM sales_order_items oi
            INNER JOIN sales_orders o ON o.id = oi.order_id
            WHERE o.cashier_id = :cashier_id'
        );
        $statement->execute(['cashier_id' => $cashierId]);

        return (int)$statement->fetchColumn();
    }

    private function generateOrderCode(): string
    {
        do {
            $orderCode = 'ORD-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
        } while ($this->orderCodeExists($orderCode));

        return $orderCode;
    }

    private function orderCodeExists(string $orderCode): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT id
            FROM sales_orders
            WHERE order_code = :order_code
            LIMIT 1'
        );
        $statement->execute(['order_code' => $orderCode]);

        return $statement->fetch() !== false;
    }

    private function findOrCreateCustomer(?string $customerName): ?int
    {
        $name = trim((string)$customerName);
        if ($name === '') {
            return null;
        }

        $select = $this->pdo->prepare('SELECT id FROM customers WHERE full_name = :name LIMIT 1');
        $select->execute(['name' => $name]);
        $existing = (int)$select->fetchColumn();
        if ($existing > 0) {
            return $existing;
        }

        $insert = $this->pdo->prepare('INSERT INTO customers (full_name) VALUES (:name)');
        $insert->execute(['name' => $name]);

        return (int)$this->pdo->lastInsertId();
    }

    private function orderIdByItemId(int $itemId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT order_id
            FROM sales_order_items
            WHERE id = :id
            LIMIT 1'
        );
        $statement->execute(['id' => $itemId]);

        return (int)$statement->fetchColumn();
    }

    private function cleanupOrderIfEmpty(int $orderId): void
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*)
            FROM sales_order_items
            WHERE order_id = :order_id'
        );
        $statement->execute(['order_id' => $orderId]);

        if ((int)$statement->fetchColumn() > 0) {
            return;
        }

        $delete = $this->pdo->prepare('DELETE FROM sales_orders WHERE id = :id');
        $delete->execute(['id' => $orderId]);
    }

    private function migrateLegacySalesTransactions(): void
    {
        if (!$this->tableExists('sales_transactions')) {
            return;
        }

        $legacyRows = $this->pdo->query(
            'SELECT
                invoice_code,
                laptop_id,
                cashier_id,
                customer_name,
                quantity,
                unit_price,
                total_price,
                created_at
            FROM sales_transactions
            ORDER BY id ASC'
        )->fetchAll();

        if ($legacyRows === []) {
            return;
        }

        $this->pdo->beginTransaction();
        try {
            $orderInsert = $this->pdo->prepare(
                'INSERT INTO sales_orders
                (order_code, cashier_id, customer_id, customer_note, order_status, grand_total, created_at, updated_at)
                VALUES
                (:order_code, :cashier_id, :customer_id, :customer_note, :order_status, :grand_total, :created_at, :updated_at)'
            );

            $itemInsert = $this->pdo->prepare(
                'INSERT INTO sales_order_items
                (order_id, laptop_id, quantity, unit_price, line_total, created_at)
                VALUES
                (:order_id, :laptop_id, :quantity, :unit_price, :line_total, :created_at)'
            );

            foreach ($legacyRows as $row) {
                $orderCode = (string)($row['invoice_code'] ?? '');
                $cashierId = (int)($row['cashier_id'] ?? 0);
                if ($orderCode === '' || $cashierId <= 0 || $this->orderCodeExists($orderCode)) {
                    continue;
                }

                if (!$this->userExists($cashierId)) {
                    continue;
                }

                $customerName = trim((string)($row['customer_name'] ?? ''));
                $customerId = $this->findOrCreateCustomer($customerName);

                $createdAt = (string)($row['created_at'] ?? date('Y-m-d H:i:s'));
                $orderInsert->execute([
                    'order_code' => $orderCode,
                    'cashier_id' => $cashierId,
                    'customer_id' => $customerId,
                    'customer_note' => $customerName !== '' ? $customerName : null,
                    'order_status' => 'paid',
                    'grand_total' => (int)($row['total_price'] ?? 0),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $orderId = (int)$this->pdo->lastInsertId();
                $laptopId = (int)($row['laptop_id'] ?? 0);
                if (!$this->laptopExists($laptopId)) {
                    $laptopId = 0;
                }

                $itemInsert->execute([
                    'order_id' => $orderId,
                    'laptop_id' => $laptopId > 0 ? $laptopId : null,
                    'quantity' => (int)($row['quantity'] ?? 1),
                    'unit_price' => (int)($row['unit_price'] ?? 0),
                    'line_total' => (int)($row['total_price'] ?? 0),
                    'created_at' => $createdAt,
                ]);
            }

            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    private function tableExists(string $table): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*)
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = :table'
        );
        $statement->execute(['table' => $table]);

        return (int)$statement->fetchColumn() > 0;
    }

    private function userExists(int $userId): bool
    {
        $statement = $this->pdo->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $userId]);

        return $statement->fetch() !== false;
    }

    private function laptopExists(int $laptopId): bool
    {
        if ($laptopId <= 0) {
            return false;
        }

        $statement = $this->pdo->prepare('SELECT id FROM laptops WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $laptopId]);

        return $statement->fetch() !== false;
    }
}
