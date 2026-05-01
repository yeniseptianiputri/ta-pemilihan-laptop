<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Throwable;

final class LaptopRepository
{
    private array $defaultLaptops = [
        ['brand' => 'Acer', 'name' => 'Acer Aspire 5', 'ram' => 8, 'storage' => 512, 'processor' => 7, 'price' => 7500000],
        ['brand' => 'ASUS', 'name' => 'ASUS VivoBook 14', 'ram' => 16, 'storage' => 512, 'processor' => 8, 'price' => 9500000],
        ['brand' => 'Lenovo', 'name' => 'Lenovo ThinkPad E14', 'ram' => 16, 'storage' => 1024, 'processor' => 9, 'price' => 12500000],
        ['brand' => 'HP', 'name' => 'HP Pavilion 15', 'ram' => 8, 'storage' => 256, 'processor' => 6, 'price' => 6500000],
    ];

    private array $criterionCache = [];

    public function __construct(private PDO $pdo)
    {
    }

    public function ensureSchema(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS brands (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB'
        );

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS criteria (
                id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(40) NOT NULL UNIQUE,
                name VARCHAR(120) NOT NULL,
                attribute_type ENUM("benefit", "cost") NOT NULL,
                weight DECIMAL(6,4) NOT NULL,
                unit VARCHAR(20) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB'
        );

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS laptops (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                brand_id INT UNSIGNED NOT NULL,
                name VARCHAR(160) NOT NULL,
                price INT UNSIGNED NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_laptops_brand_id (brand_id),
                INDEX idx_laptops_name (name),
                INDEX idx_laptops_price (price),
                CONSTRAINT fk_laptops_brand_runtime
                    FOREIGN KEY (brand_id) REFERENCES brands(id)
                    ON UPDATE CASCADE
                    ON DELETE RESTRICT
            ) ENGINE=InnoDB'
        );

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS laptop_criteria_values (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                laptop_id INT UNSIGNED NOT NULL,
                criterion_id TINYINT UNSIGNED NOT NULL,
                numeric_value DECIMAL(14,4) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_laptop_criterion (laptop_id, criterion_id),
                INDEX idx_laptop_criteria_criterion_id (criterion_id),
                CONSTRAINT fk_laptop_criteria_laptop_runtime
                    FOREIGN KEY (laptop_id) REFERENCES laptops(id)
                    ON UPDATE CASCADE
                    ON DELETE CASCADE,
                CONSTRAINT fk_laptop_criteria_criterion_runtime
                    FOREIGN KEY (criterion_id) REFERENCES criteria(id)
                    ON UPDATE CASCADE
                    ON DELETE RESTRICT
            ) ENGINE=InnoDB'
        );

        if (!$this->columnExists('laptops', 'brand_id')) {
            $this->pdo->exec('ALTER TABLE laptops ADD COLUMN brand_id INT UNSIGNED NULL AFTER id');
        }

        $unknownBrandId = $this->ensureBrand('Unknown');
        $statement = $this->pdo->prepare(
            'UPDATE laptops
            SET brand_id = :brand_id
            WHERE brand_id IS NULL OR brand_id = 0'
        );
        $statement->execute(['brand_id' => $unknownBrandId]);

        $this->ensureDefaultCriteria();
        $this->migrateLegacyCriteriaValues();
    }

    public function seedDefaultsIfEmpty(): void
    {
        $count = (int)$this->pdo->query('SELECT COUNT(*) FROM laptops')->fetchColumn();
        if ($count === 0) {
            $this->insertDefaults();
        }
    }

    public function all(): array
    {
        $statement = $this->pdo->query($this->selectLaptopSql('', 'ORDER BY l.id DESC'));

        return $statement->fetchAll();
    }

    public function allForRanking(): array
    {
        $statement = $this->pdo->query($this->selectLaptopSql('', 'ORDER BY l.name ASC'));

        return $statement->fetchAll();
    }

    public function searchByName(string $query): array
    {
        if ($query === '') {
            return $this->all();
        }

        $statement = $this->pdo->prepare(
            $this->selectLaptopSql('WHERE l.name LIKE :query', 'ORDER BY l.id DESC')
        );
        $statement->execute([
            'query' => '%' . $query . '%',
        ]);

        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            $this->selectLaptopSql('WHERE l.id = :id', 'ORDER BY l.id DESC', 'LIMIT 1')
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row !== false ? $row : null;
    }

    public function create(array $payload): void
    {
        $brandName = trim((string)($payload['brand'] ?? 'Unknown'));
        $brandId = $this->ensureBrand($brandName !== '' ? $brandName : 'Unknown');

        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO laptops (brand_id, name, price)
                VALUES (:brand_id, :name, :price)'
            );
            $statement->execute([
                'brand_id' => $brandId,
                'name' => $payload['name'],
                'price' => $payload['price'],
            ]);

            $laptopId = (int)$this->pdo->lastInsertId();
            $this->upsertCriteriaValues($laptopId, $payload);

            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function update(int $id, array $payload): void
    {
        $brandName = trim((string)($payload['brand'] ?? 'Unknown'));
        $brandId = $this->ensureBrand($brandName !== '' ? $brandName : 'Unknown');

        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare(
                'UPDATE laptops
                SET brand_id = :brand_id,
                    name = :name,
                    price = :price
                WHERE id = :id'
            );
            $statement->execute([
                'id' => $id,
                'brand_id' => $brandId,
                'name' => $payload['name'],
                'price' => $payload['price'],
            ]);

            $this->upsertCriteriaValues($id, $payload);

            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM laptops WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function restoreDefaults(): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec('DELETE FROM laptops');
            $this->pdo->exec('ALTER TABLE laptops AUTO_INCREMENT = 1');
            $this->insertDefaults();
            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function distinctOptions(): array
    {
        $catalog = $this->allForRanking();

        return [
            'names' => $this->distinctFromCatalog($catalog, 'name'),
            'rams' => $this->distinctIntFromCatalog($catalog, 'ram'),
            'storages' => $this->distinctIntFromCatalog($catalog, 'storage'),
            'processors' => $this->distinctIntFromCatalog($catalog, 'processor'),
            'prices' => $this->distinctIntFromCatalog($catalog, 'price'),
            'brands' => $this->distinctFromCatalog($catalog, 'brand'),
        ];
    }

    public function allBrands(): array
    {
        $statement = $this->pdo->query('SELECT id, name FROM brands ORDER BY name ASC');

        return $statement->fetchAll();
    }

    private function selectLaptopSql(
        string $whereClause = '',
        string $orderClause = '',
        string $tailClause = ''
    ): string {
        return "SELECT
            l.id,
            COALESCE(NULLIF(TRIM(b.name), ''), 'Unknown') AS brand,
            l.name,
            CAST(COALESCE(MAX(CASE WHEN c.code = 'ram' THEN lcv.numeric_value END), 0) AS UNSIGNED) AS ram,
            CAST(COALESCE(MAX(CASE WHEN c.code = 'storage' THEN lcv.numeric_value END), 0) AS UNSIGNED) AS storage,
            CAST(COALESCE(MAX(CASE WHEN c.code = 'processor' THEN lcv.numeric_value END), 0) AS UNSIGNED) AS processor,
            CAST(COALESCE(MAX(CASE WHEN c.code = 'price' THEN lcv.numeric_value END), l.price, 0) AS UNSIGNED) AS price
        FROM laptops l
        LEFT JOIN brands b ON b.id = l.brand_id
        LEFT JOIN laptop_criteria_values lcv ON lcv.laptop_id = l.id
        LEFT JOIN criteria c ON c.id = lcv.criterion_id
        {$whereClause}
        GROUP BY l.id, l.name, b.name, l.price
        {$orderClause}
        {$tailClause}";
    }

    private function ensureDefaultCriteria(): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO criteria (code, name, attribute_type, weight, unit)
            VALUES (:code, :name, :attribute_type, :weight, :unit)
            ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            attribute_type = VALUES(attribute_type),
            weight = VALUES(weight),
            unit = VALUES(unit)'
        );

        $items = [
            ['code' => 'ram', 'name' => 'RAM', 'attribute_type' => 'benefit', 'weight' => 0.3, 'unit' => 'GB'],
            ['code' => 'storage', 'name' => 'Storage', 'attribute_type' => 'benefit', 'weight' => 0.2, 'unit' => 'GB'],
            ['code' => 'processor', 'name' => 'Processor Score', 'attribute_type' => 'benefit', 'weight' => 0.3, 'unit' => 'score'],
            ['code' => 'price', 'name' => 'Harga', 'attribute_type' => 'cost', 'weight' => 0.2, 'unit' => 'IDR'],
        ];

        foreach ($items as $item) {
            $statement->execute($item);
        }

        $this->criterionCache = [];
    }

    private function migrateLegacyCriteriaValues(): void
    {
        $hasRam = $this->columnExists('laptops', 'ram');
        $hasStorage = $this->columnExists('laptops', 'storage');
        $hasProcessor = $this->columnExists('laptops', 'processor_score');
        $hasPrice = $this->columnExists('laptops', 'price');

        if (!$hasRam && !$hasStorage && !$hasProcessor && !$hasPrice) {
            return;
        }

        $columns = ['id'];
        if ($hasRam) {
            $columns[] = 'ram';
        }
        if ($hasStorage) {
            $columns[] = 'storage';
        }
        if ($hasProcessor) {
            $columns[] = 'processor_score';
        }
        if ($hasPrice) {
            $columns[] = 'price';
        }

        $statement = $this->pdo->query('SELECT ' . implode(', ', $columns) . ' FROM laptops');
        $rows = $statement->fetchAll();

        foreach ($rows as $row) {
            $laptopId = (int)$row['id'];
            if ($laptopId <= 0) {
                continue;
            }

            if ($hasRam && (int)($row['ram'] ?? 0) > 0) {
                $this->upsertCriterionValue($laptopId, 'ram', (float)$row['ram']);
            }
            if ($hasStorage && (int)($row['storage'] ?? 0) > 0) {
                $this->upsertCriterionValue($laptopId, 'storage', (float)$row['storage']);
            }
            if ($hasProcessor && (int)($row['processor_score'] ?? 0) > 0) {
                $this->upsertCriterionValue($laptopId, 'processor', (float)$row['processor_score']);
            }
            if ($hasPrice && (int)($row['price'] ?? 0) > 0) {
                $this->upsertCriterionValue($laptopId, 'price', (float)$row['price']);
            }
        }
    }

    private function upsertCriteriaValues(int $laptopId, array $payload): void
    {
        $this->upsertCriterionValue($laptopId, 'ram', (float)$payload['ram']);
        $this->upsertCriterionValue($laptopId, 'storage', (float)$payload['storage']);
        $this->upsertCriterionValue($laptopId, 'processor', (float)$payload['processor']);
        $this->upsertCriterionValue($laptopId, 'price', (float)$payload['price']);
    }

    private function upsertCriterionValue(int $laptopId, string $criterionCode, float $value): void
    {
        $criterionId = $this->criterionId($criterionCode);
        if ($criterionId <= 0) {
            return;
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO laptop_criteria_values (laptop_id, criterion_id, numeric_value)
            VALUES (:laptop_id, :criterion_id, :numeric_value)
            ON DUPLICATE KEY UPDATE numeric_value = VALUES(numeric_value)'
        );
        $statement->execute([
            'laptop_id' => $laptopId,
            'criterion_id' => $criterionId,
            'numeric_value' => $value,
        ]);
    }

    private function criterionId(string $code): int
    {
        if (isset($this->criterionCache[$code])) {
            return $this->criterionCache[$code];
        }

        $statement = $this->pdo->prepare('SELECT id FROM criteria WHERE code = :code LIMIT 1');
        $statement->execute(['code' => $code]);
        $id = (int)$statement->fetchColumn();
        $this->criterionCache[$code] = $id;

        return $id;
    }

    private function ensureBrand(string $brandName): int
    {
        $name = trim($brandName);
        if ($name === '') {
            $name = 'Unknown';
        }

        $select = $this->pdo->prepare('SELECT id FROM brands WHERE name = :name LIMIT 1');
        $select->execute(['name' => $name]);
        $existing = (int)$select->fetchColumn();
        if ($existing > 0) {
            return $existing;
        }

        $insert = $this->pdo->prepare('INSERT INTO brands (name) VALUES (:name)');
        $insert->execute(['name' => $name]);

        return (int)$this->pdo->lastInsertId();
    }

    private function insertDefaults(): void
    {
        foreach ($this->defaultLaptops as $item) {
            $this->create($item);
        }
    }

    private function columnExists(string $table, string $column): bool
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

    private function distinctFromCatalog(array $catalog, string $key): array
    {
        $values = [];
        foreach ($catalog as $item) {
            $value = trim((string)($item[$key] ?? ''));
            if ($value === '') {
                continue;
            }

            $values[$value] = true;
        }

        $result = array_keys($values);
        sort($result);

        return $result;
    }

    private function distinctIntFromCatalog(array $catalog, string $key): array
    {
        $values = [];
        foreach ($catalog as $item) {
            $value = (int)($item[$key] ?? 0);
            if ($value <= 0) {
                continue;
            }

            $values[$value] = true;
        }

        $result = array_map('intval', array_keys($values));
        sort($result);

        return $result;
    }
}
