<?php

declare(strict_types=1);

$filters = is_array($homeFilters ?? null)
    ? $homeFilters
    : [
        'name' => '',
        'min_ram' => 0,
        'min_storage' => 0,
        'min_processor' => 0,
        'max_price' => 0,
    ];

$results = is_array($homeResults ?? null) ? $homeResults : [];
$hasRecommendation = (bool)($hasRecommendation ?? false);
$options = is_array($options ?? null)
    ? $options
    : [
        'names' => [],
        'rams' => [],
        'storages' => [],
        'processors' => [],
        'prices' => [],
    ];

$laptops = is_array($laptops ?? null) ? $laptops : [];
$catalogCount = count($laptops);
$topResults = array_slice($results, 0, 5);
$bestResult = $topResults[0] ?? null;
?>

<section class="market-shell">
    <div class="market-topbar">
        <p>Hotline Konsultasi: 0812-3456-7890</p>
        <div class="market-topbar-meta">
            <span>Skema: Weighted Product</span>
            <span>Platform: PHP + MySQL</span>
            <span>Akses: Admin, Kasir &amp; User</span>
        </div>
    </div>

    <div class="market-searchbar">
        <div class="market-logo">
            <span class="market-logo-mark"></span>
            <div>
                <p class="market-logo-name">SPEKLAP</p>
                <p class="market-logo-sub">Pemilihan Spek Laptop</p>
            </div>
        </div>

        <form class="market-search-form" method="get" action="index.php">
            <input type="hidden" name="page" value="home">
            <input
                type="search"
                name="q"
                value="<?= e($query ?? '') ?>"
                placeholder="Cari laptop berdasarkan nama..."
            >
            <button type="submit">Cari</button>
        </form>

        <div class="market-badges">
            <span class="market-badge">Katalog: <?= e((string)$catalogCount) ?></span>
            <span class="market-badge">Ranking: <?= e((string)count($results)) ?></span>
        </div>
    </div>

    <nav class="market-nav">
        <a class="active" href="<?= e(url('home')) ?>">Landing</a>
        <a href="<?= e(url('katalog')) ?>">Katalog Laptop</a>
        <a href="<?= e(url('form-rekomendasi')) ?>">Form Rekomendasi</a>
        <a href="<?= e(url('diagram')) ?>">Diagram Mermaid</a>
        <a href="<?= e(url('konsultasi')) ?>">Konsultasi AI</a>
        <a href="<?= e(url('cashier')) ?>">Panel Kasir</a>
        <a href="<?= e(url('admin')) ?>">Panel Admin</a>
    </nav>

    <div class="market-hero-grid">
        <article class="market-hero-main">
            <p class="market-kicker">Sistem Pendukung Keputusan</p>
            <h1>Pemilihan Spek Laptop yang Tepat Sesuai Budget dan Kebutuhan</h1>
            <p>
                Bandingkan RAM, storage, prosesor, dan harga dalam satu dashboard.
                Gunakan filter rekomendasi untuk mendapatkan ranking laptop paling sesuai.
            </p>

            <div class="button-row">
                <a class="btn btn-primary" href="<?= e(url('form-rekomendasi')) ?>">Mulai Hitung Rekomendasi</a>
                <a class="btn btn-outline" href="<?= e(url('katalog')) ?>">Lihat Katalog</a>
            </div>

            <?php if (is_array($bestResult)): ?>
                <div class="market-highlight">
                    <p class="market-highlight-label">Top Rekomendasi Saat Ini</p>
                    <p class="market-highlight-title"><?= e($bestResult['name']) ?></p>
                    <p class="market-highlight-text">
                        Harga <?= e(format_rupiah((int)$bestResult['price'])) ?>
                        dengan skor WP <?= e(number_format((float)$bestResult['skor'], 4)) ?>.
                    </p>
                </div>
            <?php endif; ?>
        </article>

        <div class="market-hero-side-col">
            <article class="market-side-card">
                <p class="market-side-label">Kriteria Utama</p>
                <h3>Komponen Penilaian</h3>
                <p>RAM, Storage, dan Prosesor sebagai benefit, Harga sebagai cost.</p>
            </article>

            <article class="market-side-card">
                <p class="market-side-label">Akurasi Keputusan</p>
                <h3>Skor Objektif</h3>
                <p>Perankingan dilakukan otomatis menggunakan metode Weighted Product.</p>
            </article>
        </div>
    </div>

    <div class="market-main-grid">
        <section class="market-panel" id="katalog-laptop">
            <div class="market-panel-head">
                <div>
                    <h2>Katalog Laptop</h2>
                    <p>Menampilkan <?= e((string)$catalogCount) ?> laptop dari database MySQL.</p>
                </div>
                <a class="market-link" href="<?= e(url('rekomendasi')) ?>">Buka Halaman Rekomendasi Penuh</a>
            </div>

            <?php if (empty($laptops)): ?>
                <p class="muted">Tidak ada data laptop.</p>
            <?php else: ?>
                <div class="market-product-grid">
                    <?php foreach ($laptops as $item): ?>
                        <article class="market-product-card">
                            <p class="market-product-name"><?= e($item['name']) ?></p>
                            <p class="market-product-spec">RAM <?= e($item['ram']) ?> GB</p>
                            <p class="market-product-spec">Storage <?= e($item['storage']) ?> GB</p>
                            <p class="market-product-spec">Prosesor <?= e($item['processor']) ?></p>
                            <p class="market-product-price"><?= e(format_rupiah((int)$item['price'])) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="market-panel market-panel-rekomendasi" id="form-rekomendasi">
            <div class="market-panel-head">
                <div>
                    <h2>Form Rekomendasi</h2>
                    <p>Bobot default: RAM 30%, Storage 20%, Prosesor 30%, Harga 20%.</p>
                </div>
            </div>

            <form class="form-grid" method="post" action="<?= e(url('home', ['q' => $query ?? ''])) ?>">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="recommend_home">

                <label>
                    Nama Laptop
                    <select name="name">
                        <option value="">Semua laptop</option>
                        <?php foreach (($options['names'] ?? []) as $name): ?>
                            <option value="<?= e((string)$name) ?>" <?= ($filters['name'] ?? '') === (string)$name ? 'selected' : '' ?>>
                                <?= e((string)$name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    RAM Minimal (GB)
                    <select name="min_ram">
                        <option value="0">Semua RAM</option>
                        <?php foreach (($options['rams'] ?? []) as $value): ?>
                            <option value="<?= e((string)$value) ?>" <?= (int)($filters['min_ram'] ?? 0) === (int)$value ? 'selected' : '' ?>>
                                <?= e((string)$value) ?> GB
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Storage Minimal (GB)
                    <select name="min_storage">
                        <option value="0">Semua Storage</option>
                        <?php foreach (($options['storages'] ?? []) as $value): ?>
                            <option value="<?= e((string)$value) ?>" <?= (int)($filters['min_storage'] ?? 0) === (int)$value ? 'selected' : '' ?>>
                                <?= e((string)$value) ?> GB
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Prosesor Minimal (skor)
                    <select name="min_processor">
                        <option value="0">Semua Prosesor</option>
                        <?php foreach (($options['processors'] ?? []) as $value): ?>
                            <option value="<?= e((string)$value) ?>" <?= (int)($filters['min_processor'] ?? 0) === (int)$value ? 'selected' : '' ?>>
                                <?= e((string)$value) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Budget Maksimal (Rp)
                    <select name="max_price">
                        <option value="0">Semua Harga</option>
                        <?php foreach (($options['prices'] ?? []) as $value): ?>
                            <option value="<?= e((string)$value) ?>" <?= (int)($filters['max_price'] ?? 0) === (int)$value ? 'selected' : '' ?>>
                                <?= e(format_rupiah((int)$value)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div class="button-row">
                    <button class="btn btn-primary" type="submit">Cari Rekomendasi</button>
                    <button class="btn btn-outline" type="submit" name="action" value="reset_home">Reset</button>
                </div>
            </form>

            <div class="market-ranking-box">
                <h3>Top 5 Ranking</h3>
                <?php if (!$hasRecommendation): ?>
                    <p class="muted">Isi filter untuk menampilkan ranking rekomendasi.</p>
                <?php elseif (empty($results)): ?>
                    <p class="muted">Tidak ada laptop yang cocok dengan filter saat ini.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="compact-table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Nama</th>
                                    <th>Harga</th>
                                    <th>Skor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topResults as $index => $item): ?>
                                    <tr>
                                        <td><?= e((string)($index + 1)) ?></td>
                                        <td><?= e($item['name']) ?></td>
                                        <td><?= e(format_rupiah((int)$item['price'])) ?></td>
                                        <td><?= e(number_format((float)$item['skor'], 4)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</section>

