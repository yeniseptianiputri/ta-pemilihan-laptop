<?php

declare(strict_types=1);

$appName = env('APP_NAME', 'SPK Pemilihan Laptop');
$pageTitle = isset($title) && $title !== '' ? $title . ' | ' . $appName : $appName;
$flashes = flash_pull();
$currentPage = current_page();
$isAdminNavbar = is_admin_logged_in();
$isCashierNavbar = is_cashier_logged_in();
$pageHeading = isset($title) && $title !== '' ? (string)$title : $appName;
$pageTaglines = [
    'katalog' => 'Lihat daftar spek laptop sebagai bahan pembanding sebelum menentukan pilihan akhir.',
    'form-rekomendasi' => 'Atur kriteria kebutuhan dan dapatkan ranking laptop dengan metode Weighted Product.',
    'rekomendasi' => 'Analisis hasil rekomendasi berbasis bobot RAM, storage, prosesor, dan harga.',
    'konsultasi' => 'Diskusikan kebutuhan perangkat Anda untuk mendapatkan arahan spek yang lebih tepat.',
    'admin' => 'Kelola data laptop agar perhitungan rekomendasi selalu akurat dan relevan.',
    'cashier' => 'Catat transaksi penjualan laptop dan monitor pendapatan kasir secara terstruktur.',
];
$pageTagline = $pageTaglines[$currentPage] ?? 'Sistem pendukung keputusan untuk pemilihan spek laptop yang terstruktur.';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php if ($currentPage !== 'home'): ?>
<header class="site-header">
    <div class="container header-inner">
        <div>
            <p class="eyebrow">Sistem Pendukung Keputusan</p>
            <a class="brand" href="<?= e(url('home')) ?>"><?= e($appName) ?></a>
        </div>
        <nav class="nav-links">
            <?php if ($isAdminNavbar): ?>
                <a class="<?= is_active_page('admin') ? 'active' : '' ?>" href="<?= e(url('admin')) ?>">Dashboard Admin</a>
                <a class="<?= is_active_page('cashier') ? 'active' : '' ?>" href="<?= e(url('cashier')) ?>">Kasir</a>
                <a class="<?= is_active_page('katalog') ? 'active' : '' ?>" href="<?= e(url('katalog')) ?>">Katalog Laptop</a>
                <a class="<?= is_active_page('form-rekomendasi') ? 'active' : '' ?>" href="<?= e(url('form-rekomendasi')) ?>">Form Rekomendasi</a>
                <a class="<?= is_active_page('home') ? 'active' : '' ?>" href="<?= e(url('home')) ?>">Landing</a>
                <form class="nav-inline-form" method="post" action="<?= e(url('admin')) ?>">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="admin_logout">
                    <button class="nav-inline-button" type="submit">Logout Admin</button>
                </form>
            <?php elseif ($isCashierNavbar): ?>
                <a class="<?= is_active_page('cashier') ? 'active' : '' ?>" href="<?= e(url('cashier')) ?>">Dashboard Kasir</a>
                <a class="<?= is_active_page('katalog') ? 'active' : '' ?>" href="<?= e(url('katalog')) ?>">Katalog Laptop</a>
                <a class="<?= is_active_page('form-rekomendasi') ? 'active' : '' ?>" href="<?= e(url('form-rekomendasi')) ?>">Form Rekomendasi</a>
                <a class="<?= is_active_page('home') ? 'active' : '' ?>" href="<?= e(url('home')) ?>">Landing</a>
                <form class="nav-inline-form" method="post" action="<?= e(url('cashier')) ?>">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="cashier_logout">
                    <button class="nav-inline-button" type="submit">Logout Kasir</button>
                </form>
            <?php else: ?>
                <a class="<?= is_active_page('home') ? 'active' : '' ?>" href="<?= e(url('home')) ?>">Beranda</a>
                <a class="<?= is_active_page('katalog') ? 'active' : '' ?>" href="<?= e(url('katalog')) ?>">Katalog</a>
                <a class="<?= is_active_page('rekomendasi') ? 'active' : '' ?>" href="<?= e(url('rekomendasi')) ?>">Rekomendasi</a>
                <a class="<?= is_active_page('konsultasi') ? 'active' : '' ?>" href="<?= e(url('konsultasi')) ?>">Konsultasi</a>
                <a class="<?= is_active_page('cashier') ? 'active' : '' ?>" href="<?= e(url('cashier')) ?>">Kasir</a>
                <a class="<?= is_active_page('admin') ? 'active' : '' ?>" href="<?= e(url('admin')) ?>">Admin</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<?php endif; ?>

<main class="container page-content<?= $currentPage === 'home' ? ' page-home' : '' ?>">
    <?php if ($currentPage !== 'home'): ?>
        <section class="page-title-hero">
            <p class="page-title-kicker">SPEKLAP</p>
            <h1><?= e($pageHeading) ?></h1>
            <p><?= e($pageTagline) ?></p>
        </section>
    <?php endif; ?>

    <?php foreach ($flashes as $type => $messages): ?>
        <?php foreach ((array)$messages as $message): ?>
            <div class="alert alert-<?= e($type) ?>"><?= e($message) ?></div>
        <?php endforeach; ?>
    <?php endforeach; ?>

    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <section class="footer-brand">
            <p class="footer-title"><?= e($appName) ?></p>
            <p class="footer-text">
                Sistem pendukung keputusan pemilihan laptop berbasis metode Weighted Product
                untuk membantu pengguna menentukan pilihan secara terstruktur.
            </p>
        </section>

        <nav class="footer-meta" aria-label="Navigasi Footer">
            <a href="<?= e(url('home')) ?>">Beranda</a>
            <a href="<?= e(url('rekomendasi')) ?>">Rekomendasi</a>
            <a href="<?= e(url('konsultasi')) ?>">Konsultasi</a>
            <a href="<?= e(url('cashier')) ?>">Kasir</a>
            <a href="<?= e(url('admin')) ?>">Admin</a>
        </nav>
    </div>

    <div class="container footer-bottom">
        <p>&copy; <?= e((string)date('Y')) ?> <?= e($appName) ?>. All rights reserved.</p>
        <p>Powered by PHP 8 and MySQL.</p>
    </div>
</footer>
</body>
</html>
