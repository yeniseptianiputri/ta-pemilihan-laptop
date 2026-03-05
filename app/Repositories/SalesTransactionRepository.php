<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class SalesTransactionRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function ensureTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS sales_transactions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                invoice_code VARCHAR(40) NOT NULL UNIQUE,
                laptop_id INT UNSIGNED NOT NULL,
                cashier_id INT UNSIGNED NOT NULL,
                customer_name VARCHAR(120) NULL,
                quantity SMALLINT UNSIGNED NOT NULL,
                unit_price INT UNSIGNED NOT NULL,
                total_price BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_sales_laptop (laptop_id),
                INDEX idx_sales_cashier (cashier_id),
                INDEX idx_sales_created_at (created_at)
            ) ENGINE=InnoDB'
        );
    }

    public function create(
        int $laptopId,
        int $cashierId,
        int $quantity,
        int $unitPrice,
        ?string $customerName = null
    ): string {
        $invoiceCode = $this->generateInvoiceCode();
        $totalPrice = $unitPrice * $quantity;

        $statement = $this->pdo->prepare(
            'INSERT INTO sales_transactions
            (invoice_code, laptop_id, cashier_id, customer_name, quantity, unit_price, total_price)
            VALUES
            (:invoice_code, :laptop_id, :cashier_id, :customer_name, :quantity, :unit_price, :total_price)'
        );
        $statement->execute([
            'invoice_code' => $invoiceCode,
            'laptop_id' => $laptopId,
            'cashier_id' => $cashierId,
            'customer_name' => $customerName,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
        ]);

        return $invoiceCode;
    }

    public function all(): array
    {
        $statement = $this->pdo->query(
            "SELECT
                t.id,
                t.invoice_code,
                t.customer_name,
                t.quantity,
                t.unit_price,
                t.total_price,
                t.created_at,
                COALESCE(l.name, '[Laptop terhapus]') AS laptop_name,
                COALESCE(NULLIF(TRIM(u.name), ''), u.email, '[Kasir]') AS cashier_name
            FROM sales_transactions t
            LEFT JOIN laptops l ON l.id = t.laptop_id
            LEFT JOIN users u ON u.id = t.cashier_id
            ORDER BY t.id DESC"
        );

        return $statement->fetchAll();
    }

    public function allByCashier(int $cashierId): array
    {
        $statement = $this->pdo->prepare(
            "SELECT
                t.id,
                t.invoice_code,
                t.customer_name,
                t.quantity,
                t.unit_price,
                t.total_price,
                t.created_at,
                COALESCE(l.name, '[Laptop terhapus]') AS laptop_name
            FROM sales_transactions t
            LEFT JOIN laptops l ON l.id = t.laptop_id
            WHERE t.cashier_id = :cashier_id
            ORDER BY t.id DESC"
        );
        $statement->execute(['cashier_id' => $cashierId]);

        return $statement->fetchAll();
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM sales_transactions WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function deleteByCashier(int $id, int $cashierId): bool
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM sales_transactions
            WHERE id = :id
            AND cashier_id = :cashier_id'
        );
        $statement->execute([
            'id' => $id,
            'cashier_id' => $cashierId,
        ]);

        return $statement->rowCount() > 0;
    }

    public function countAll(): int
    {
        return (int)$this->pdo->query('SELECT COUNT(*) FROM sales_transactions')->fetchColumn();
    }

    public function countByCashier(int $cashierId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM sales_transactions WHERE cashier_id = :cashier_id'
        );
        $statement->execute(['cashier_id' => $cashierId]);

        return (int)$statement->fetchColumn();
    }

    public function revenueAll(): int
    {
        return (int)$this->pdo->query(
            'SELECT COALESCE(SUM(total_price), 0) FROM sales_transactions'
        )->fetchColumn();
    }

    public function revenueByCashier(int $cashierId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COALESCE(SUM(total_price), 0)
            FROM sales_transactions
            WHERE cashier_id = :cashier_id'
        );
        $statement->execute(['cashier_id' => $cashierId]);

        return (int)$statement->fetchColumn();
    }

    private function generateInvoiceCode(): string
    {
        do {
            $invoiceCode = 'INV-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
        } while ($this->invoiceExists($invoiceCode));

        return $invoiceCode;
    }

    private function invoiceExists(string $invoiceCode): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT id
            FROM sales_transactions
            WHERE invoice_code = :invoice_code
            LIMIT 1'
        );
        $statement->execute(['invoice_code' => $invoiceCode]);

        return $statement->fetch() !== false;
    }
}

