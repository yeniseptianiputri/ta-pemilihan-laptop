<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\LaptopRepository;

final class CatalogController
{
    public function __construct(private LaptopRepository $laptops)
    {
    }

    public function index(): void
    {
        $query = trim((string)($_GET['q'] ?? ''));
        $items = $query === ''
            ? $this->laptops->all()
            : $this->laptops->searchByName($query);

        render('catalog', [
            'title' => 'Katalog Laptop',
            'query' => $query,
            'laptops' => $items,
        ]);
    }
}
