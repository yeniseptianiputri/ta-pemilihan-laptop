<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\LaptopRepository;
use App\Services\RecommendationService;

final class RecommendationController
{
    public function __construct(
        private LaptopRepository $laptops,
        private RecommendationService $recommendationService,
        private string $pageKey = 'rekomendasi',
        private string $pageTitle = 'Rekomendasi Laptop'
    ) {
    }

    public function index(): void
    {
        $filters = [
            'name' => '',
            'min_ram' => 0,
            'min_storage' => 0,
            'min_processor' => 0,
            'max_price' => 0,
        ];
        $results = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['_csrf'] ?? null)) {
                flash_set('error', 'Token CSRF tidak valid. Silakan ulangi.');
                redirect(url($this->pageKey));
            }

            $action = (string)($_POST['action'] ?? 'recommend');
            if ($action === 'reset') {
                redirect(url($this->pageKey));
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
        }

        render('recommendation', [
            'title' => $this->pageTitle,
            'pageRoute' => $this->pageKey,
            'pageHeading' => $this->pageTitle,
            'filters' => $filters,
            'results' => $results,
            'options' => $this->laptops->distinctOptions(),
            'weights' => $this->recommendationService->defaultWeights(),
        ]);
    }
}
