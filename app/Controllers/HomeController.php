<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\LaptopRepository;
use App\Services\RecommendationService;

final class HomeController
{
    public function __construct(
        private LaptopRepository $laptops,
        private RecommendationService $recommendationService
    ) {
    }

    public function index(): void
    {
        $query = trim((string)($_GET['q'] ?? ''));
        $catalog = $query === ''
            ? $this->laptops->all()
            : $this->laptops->searchByName($query);

        $filters = [
            'name' => '',
            'min_ram' => 0,
            'min_storage' => 0,
            'min_processor' => 0,
            'max_price' => 0,
        ];
        $results = [];
        $hasRecommendation = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['_csrf'] ?? null)) {
                flash_set('error', 'Token CSRF tidak valid. Silakan ulangi.');
                redirect(url('home', ['q' => $query]));
            }

            $action = (string)($_POST['action'] ?? 'recommend_home');
            if ($action === 'reset_home') {
                redirect(url('home', ['q' => $query]));
            }

            $filters = [
                'name' => trim((string)($_POST['name'] ?? '')),
                'min_ram' => max(0, (int)($_POST['min_ram'] ?? 0)),
                'min_storage' => max(0, (int)($_POST['min_storage'] ?? 0)),
                'min_processor' => max(0, (int)($_POST['min_processor'] ?? 0)),
                'max_price' => max(0, (int)($_POST['max_price'] ?? 0)),
            ];

            $results = $this->recommendationService->recommend(
                $this->laptops->allForRanking(),
                $filters
            );
            $hasRecommendation = true;
        }

        render('home', [
            'title' => 'Beranda',
            'query' => $query,
            'laptops' => $catalog,
            'homeFilters' => $filters,
            'homeResults' => $results,
            'hasRecommendation' => $hasRecommendation,
            'options' => $this->laptops->distinctOptions(),
        ]);
    }
}