<?php

declare(strict_types=1);

$laptops = is_array($laptops ?? null) ? $laptops : [];
$transactions = is_array($transactions ?? null) ? $transactions : [];
$summary = is_array($summary ?? null) ? $summary : ['count' => 0, 'revenue' => 0];
$currentCashier = is_array($currentCashier ?? null) ? $currentCashier : null;
?>
<?php if (!($isAuthed ?? false)): ?>
    <section class="card narrow">
        <p class="eyebrow">Autentikasi</p>
        <h2>Login Kasir</h2>
        <p class="muted">Masuk sebagai kasir untuk mencatat transaksi penjualan laptop.</p>

        <form class="form-grid" method="post" action="<?= e(url('cashier')) ?>">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="cashier_login">

            <label>
                Email Kasir
                <input type="email" name="email" required placeholder="cashier@laptop.local">
            </label>
            <label>
                Password Kasir
                <input type="password" name="password" required placeholder="password">
            </label>
            <div class="button-row">
                <button class="btn btn-primary" type="submit">Masuk Kasir</button>
                <a class="btn btn-outline" href="<?= e(url('home')) ?>">Kembali</a>
            </div>
        </form>
    </section>
<?php else: ?>
    <section class="card">
        <div class="card-head">
            <div>
                <p class="eyebrow">Sesi Kasir</p>
                <h2>Dashboard Kasir</h2>
                <p class="muted">
                    Login sebagai: <?= e($currentCashier['email'] ?? '-') ?>.
                    Total transaksi: <?= e((string)($summary['count'] ?? 0)) ?>.
                    Total pendapatan: <?= e(format_rupiah((int)($summary['revenue'] ?? 0))) ?>.
                </p>
            </div>
            <form method="post" action="<?= e(url('cashier')) ?>">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="cashier_logout">
                <button class="btn btn-outline" type="submit">Logout</button>
            </form>
        </div>
    </section>

    <section class="card">
        <p class="eyebrow">Input</p>
        <h2>Transaksi Penjualan</h2>
        <p class="muted">Pilih laptop, isi quantity, lalu simpan transaksi.</p>

        <form class="form-grid two-columns" method="post" action="<?= e(url('cashier')) ?>">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="create_sale">

            <label class="span-2">
                Pilih Laptop
                <select name="laptop_id" required>
                    <option value="">Pilih laptop</option>
                    <?php foreach ($laptops as $item): ?>
                        <option value="<?= e((string)$item['id']) ?>">
                            <?= e($item['name']) ?> - <?= e(format_rupiah((int)$item['price'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Nama Pembeli (opsional)
                <input type="text" name="customer_name" placeholder="cth. Andi">
            </label>

            <label>
                Quantity
                <input type="number" min="1" name="quantity" required value="1">
            </label>

            <div class="button-row span-2">
                <button class="btn btn-primary" type="submit">Simpan Transaksi</button>
            </div>
        </form>
    </section>

    <section class="card">
        <h2>Riwayat Transaksi Kasir</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Laptop</th>
                        <th>Pembeli</th>
                        <th>Qty</th>
                        <th>Harga Satuan</th>
                        <th>Total</th>
                        <th>Waktu</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="8" class="text-center muted">Belum ada transaksi.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $item): ?>
                            <tr>
                                <td><?= e($item['invoice_code']) ?></td>
                                <td><?= e($item['laptop_name']) ?></td>
                                <td><?= e($item['customer_name'] ?: '-') ?></td>
                                <td><?= e((string)$item['quantity']) ?></td>
                                <td><?= e(format_rupiah((int)$item['unit_price'])) ?></td>
                                <td><?= e(format_rupiah((int)$item['total_price'])) ?></td>
                                <td><?= e((string)$item['created_at']) ?></td>
                                <td>
                                    <form method="post" action="<?= e(url('cashier')) ?>" onsubmit="return confirm('Hapus transaksi ini?');">
                                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="delete_sale">
                                        <input type="hidden" name="id" value="<?= e((string)$item['id']) ?>">
                                        <button class="btn btn-danger btn-small" type="submit">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

