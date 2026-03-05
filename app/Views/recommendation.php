<?php

declare(strict_types=1);

$filters = $filters ?? [
    'name' => '',
    'min_ram' => 0,
    'min_storage' => 0,
    'min_processor' => 0,
    'max_price' => 0,
];
$results = $results ?? [];
$options = $options ?? [
    'names' => [],
    'rams' => [],
    'storages' => [],
    'processors' => [],
    'prices' => [],
];
$weights = $weights ?? ['ram' => 0.3, 'storage' => 0.2, 'processor' => 0.3, 'price' => 0.2];
$pageRoute = (string)($pageRoute ?? 'rekomendasi');
?>
<section class="card">
    <p class="eyebrow">Konfigurasi Bobot</p>
    <h2>Atur Kriteria Penilaian</h2>
    <p class="lead">
        Proses perhitungan menggunakan metode Weighted Product dengan bobot default:
        RAM <?= e((string)($weights['ram'] * 100)) ?>%,
        Storage <?= e((string)($weights['storage'] * 100)) ?>%,
        Prosesor <?= e((string)($weights['processor'] * 100)) ?>%,
        Harga <?= e((string)($weights['price'] * 100)) ?>%.
    </p>
</section>

<section class="card">
    <form class="form-grid two-columns" method="post" action="<?= e(url($pageRoute)) ?>">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="recommend">

        <label class="span-2">
            Nama Laptop
            <select name="name">
                <option value="">Semua laptop</option>
                <?php foreach ($options['names'] as $name): ?>
                    <option value="<?= e($name) ?>" <?= ($filters['name'] ?? '') === $name ? 'selected' : '' ?>>
                        <?= e($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            RAM Minimal (GB)
            <select name="min_ram">
                <option value="0">Semua RAM</option>
                <?php foreach ($options['rams'] as $value): ?>
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
                <?php foreach ($options['storages'] as $value): ?>
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
                <?php foreach ($options['processors'] as $value): ?>
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
                <?php foreach ($options['prices'] as $value): ?>
                    <option value="<?= e((string)$value) ?>" <?= (int)($filters['max_price'] ?? 0) === (int)$value ? 'selected' : '' ?>>
                        <?= e(format_rupiah((int)$value)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="button-row span-2">
            <button class="btn btn-primary" type="submit">Cari Rekomendasi</button>
            <button class="btn btn-outline" type="submit" name="action" value="reset">Reset Kriteria</button>
        </div>
    </form>
</section>

<section class="card">
    <div class="card-head">
        <div>
            <p class="eyebrow">Hasil</p>
            <h2>Ranking Laptop</h2>
        </div>
        <p class="muted">Total <?= count($results) ?> laptop dinilai</p>
    </div>

    <?php if (empty($results)): ?>
        <p class="muted">Belum ada hasil. Isi filter lalu tekan tombol cari.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Nama</th>
                        <th>RAM</th>
                        <th>Storage</th>
                        <th>Prosesor</th>
                        <th>Harga</th>
                        <th>Skor WP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $index => $item): ?>
                        <tr>
                            <td><?= e((string)($index + 1)) ?></td>
                            <td><?= e($item['name']) ?></td>
                            <td><?= e($item['ram']) ?> GB</td>
                            <td><?= e($item['storage']) ?> GB</td>
                            <td><?= e($item['processor']) ?></td>
                            <td><?= e(format_rupiah((int)$item['price'])) ?></td>
                            <td><?= e(number_format((float)$item['skor'], 4)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="explanation-list">
            <p class="muted">
                <strong>Penjelasan singkat:</strong>
                Semakin besar RAM/storage/prosesor dan semakin rendah harga, skor cenderung lebih tinggi.
            </p>
            <?php foreach ($results as $item): ?>
                <p>
                    Laptop <strong><?= e($item['name']) ?></strong> direkomendasikan karena RAM
                    <?= e($item['ram']) ?> GB, storage <?= e($item['storage']) ?> GB,
                    prosesor <?= e($item['processor']) ?>, dan harga
                    <?= e(format_rupiah((int)$item['price'])) ?>.
                </p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
