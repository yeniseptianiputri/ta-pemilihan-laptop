<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\CatalogController;
use App\Controllers\ConsultationController;
use App\Controllers\HomeController;
use App\Controllers\RecommendationController;
use App\Services\ChatService;
use App\Services\RecommendationService;

$container = require dirname(__DIR__) . '/app/bootstrap.php';

$config = $container['config'];
$laptopRepository = $container['laptopRepository'];
$authService = $container['authService'];
$recommendationService = new RecommendationService();

$page = (string)($_GET['page'] ?? 'home');

try {
    switch ($page) {
        case 'home':
            (new HomeController($laptopRepository, $recommendationService))->index();
            break;

        case 'katalog':
            (new CatalogController($laptopRepository))->index();
            break;

        case 'form-rekomendasi':
            (new RecommendationController(
                $laptopRepository,
                $recommendationService,
                'form-rekomendasi',
                'Form Rekomendasi'
            ))->index();
            break;

        case 'rekomendasi':
            (new RecommendationController(
                $laptopRepository,
                $recommendationService,
                'rekomendasi',
                'Rekomendasi Laptop'
            ))->index();
            break;

        case 'admin':
            (new AdminController($laptopRepository, $authService))->index();
            break;

        case 'konsultasi':
            (new ConsultationController(
                $authService,
                new ChatService(
                    (string)($config['openai']['api_key'] ?? ''),
                    (string)($config['openai']['model'] ?? 'gpt-4.1-mini')
                ),
                $laptopRepository
            ))->index();
            break;

        default:
            http_response_code(404);
            render('home', [
                'title' => '404',
                'query' => '',
                'laptops' => [],
                'homeFilters' => [
                    'name' => '',
                    'min_ram' => 0,
                    'min_storage' => 0,
                    'min_processor' => 0,
                    'max_price' => 0,
                ],
                'homeResults' => [],
                'hasRecommendation' => false,
                'options' => [
                    'names' => [],
                    'rams' => [],
                    'storages' => [],
                    'processors' => [],
                    'prices' => [],
                ],
            ]);
            break;
    }
} catch (Throwable $exception) {
    http_response_code(500);
    echo '<h1>500 - Internal Server Error</h1>';
    echo '<p>' . e($exception->getMessage()) . '</p>';
}
