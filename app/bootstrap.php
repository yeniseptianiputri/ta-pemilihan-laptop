<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Env;
use App\Core\Session;
use App\Repositories\LaptopRepository;
use App\Repositories\RecommendationRepository;
use App\Repositories\SalesTransactionRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\RecommendationService;

require_once __DIR__ . '/autoload.php';

Env::load(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env');
require_once __DIR__ . '/Helpers/functions.php';

$config = require __DIR__ . '/Config/config.php';

Session::start();
Database::init($config['db']);

$connection = Database::connection();
$userRepository = new UserRepository($connection);
$laptopRepository = new LaptopRepository($connection);
$salesRepository = new SalesTransactionRepository($connection);
$recommendationRepository = new RecommendationRepository($connection);
$authService = new AuthService($userRepository, $config['auth']);
$recommendationService = new RecommendationService($recommendationRepository);

$userRepository->ensureRoleSchema();
$laptopRepository->ensureSchema();
$salesRepository->ensureSchema();
$recommendationRepository->ensureSchema();
$authService->ensureDefaultAccounts();
$laptopRepository->seedDefaultsIfEmpty();

return [
    'config' => $config,
    'userRepository' => $userRepository,
    'laptopRepository' => $laptopRepository,
    'salesRepository' => $salesRepository,
    'recommendationRepository' => $recommendationRepository,
    'recommendationService' => $recommendationService,
    'authService' => $authService,
];
