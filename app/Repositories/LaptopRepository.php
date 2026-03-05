<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class LaptopRepository
{
    private array $defaultLaptops = [
        ['name' => 'Acer Aspire 5', 'ram' => 8, 'storage' => 512, 'processor' => 7, 'price' => 7500000],
        ['name' => 'ASUS VivoBook 14', 'ram' => 16, 'storage' => 512, 'processor' => 8, 'price' => 9500000],
        ['name' => 'Lenovo ThinkPad E14', 'ram' => 16, 'storage' => 1024, 'processor' => 9, 'price' => 12500000],
        ['name' => 'HP Pavilion 15', 'ram' => 8, 'storage' => 256, 'processor' => 6, 'price' => 6500000],
    ];

    public function __construct(private PDO $pdo)
    {
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
        $statement = $this->pdo->query(
            'SELECT id, name, ram, storage, processor_score AS processor, price
            FROM laptops
            ORDER BY id DESC'
        );

        return $statement->fetchAll();
    }

    public function allForRanking(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, name, ram, storage, processor_score AS processor, price
            FROM laptops
            ORDER BY name ASC'
        );

        return $statement->fetchAll();
    }

    public function searchByName(string $query): array
    {
        if ($query === '') {
            return $this->all();
        }

        $statement = $this->pdo->prepare(
            'SELECT id, name, ram, storage, processor_score AS processor, price
            FROM laptops
            WHERE name LIKE :query
            ORDER BY id DESC'
        );
        $statement->execute([
            'query' => '%' . $query . '%',
        ]);

        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, ram, storage, processor_score AS processor, price
            FROM laptops
            WHERE id = :id
            LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row !== false ? $row : null;
    }

    public function create(array $payload): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO laptops (name, ram, storage, processor_score, price)
            VALUES (:name, :ram, :storage, :processor, :price)'
        );
        $statement->execute([
            'name' => $payload['name'],
            'ram' => $payload['ram'],
            'storage' => $payload['storage'],
            'processor' => $payload['processor'],
            'price' => $payload['price'],
        ]);
    }

    public function update(int $id, array $payload): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE laptops
            SET name = :name,
                ram = :ram,
                storage = :storage,
                processor_score = :processor,
                price = :price
            WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'name' => $payload['name'],
            'ram' => $payload['ram'],
            'storage' => $payload['storage'],
            'processor' => $payload['processor'],
            'price' => $payload['price'],
        ]);
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
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function distinctOptions(): array
    {
        return [
            'names' => $this->distinctValues('name'),
            'rams' => array_map('intval', $this->distinctValues('ram')),
            'storages' => array_map('intval', $this->distinctValues('storage')),
            'processors' => array_map('intval', $this->distinctValues('processor_score')),
            'prices' => array_map('intval', $this->distinctValues('price')),
        ];
    }

    private function distinctValues(string $column): array
    {
        $statement = $this->pdo->query(
            sprintf('SELECT DISTINCT %s AS value FROM laptops ORDER BY %s ASC', $column, $column)
        );
        $rows = $statement->fetchAll();

        return array_map(static fn (array $row): mixed => $row['value'], $rows);
    }

    private function insertDefaults(): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO laptops (name, ram, storage, processor_score, price)
            VALUES (:name, :ram, :storage, :processor, :price)'
        );

        foreach ($this->defaultLaptops as $item) {
            $statement->execute([
                'name' => $item['name'],
                'ram' => $item['ram'],
                'storage' => $item['storage'],
                'processor' => $item['processor'],
                'price' => $item['price'],
            ]);
        }
    }
}

