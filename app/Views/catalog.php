<?php

declare(strict_types=1);

$query = (string)($query ?? '');
$laptops = is_array($laptops ?? null) ? $laptops : [];
?>
<section class="card">
    <div class="card-head">
        <div>
            <p class="eyebrow">Katalog Data</p>
            <h2>Daftar Laptop</h2>
            <p class="muted">Daftar laptop dari database untuk bahan pembanding sebelum proses rekomendasi.</p>
        </div>
        <form class="search-form" method="get" action="index.php">
            <input type="hidden" name="page" value="katalog">
            <label for="q">Cari laptop</label>
            <input id="q" name="q" type="search" value="<?= e($query) ?>" placeholder="cth. Aspire">
        </form>
    </div>

    <p class="muted">Menampilkan <?= e((string)count($laptops)) ?> laptop</p>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>RAM</th>
                    <th>Storage</th>
                    <th>Prosesor</th>
                    <th>Harga</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($laptops)): ?>
                    <tr>
                        <td colspan="5" class="text-center muted">Tidak ada data laptop.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($laptops as $item): ?>
                        <tr>
                            <td><?= e($item['name']) ?></td>
                            <td><?= e($item['ram']) ?> GB</td>
                            <td><?= e($item['storage']) ?> GB</td>
                            <td><?= e($item['processor']) ?></td>
                            <td><?= e(format_rupiah((int)$item['price'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
