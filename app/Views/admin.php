<?php

declare(strict_types=1);

$editing = $editing ?? null;
$isEditing = is_array($editing);
?>
<?php if (!($isAuthed ?? false)): ?>
    <section class="card narrow">
        <p class="eyebrow">Autentikasi</p>
        <h2>Login Administrator</h2>
        <p class="muted">Masuk untuk mengelola data spesifikasi laptop.</p>

        <form class="form-grid" method="post" action="<?= e(url('admin')) ?>">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="admin_login">

            <label>
                Email Admin
                <input type="email" name="email" required placeholder="admin@laptop.local">
            </label>
            <label>
                Password Admin
                <input type="password" name="password" required placeholder="password">
            </label>
            <div class="button-row">
                <button class="btn btn-primary" type="submit">Masuk Admin</button>
                <a class="btn btn-outline" href="<?= e(url('home')) ?>">Kembali</a>
            </div>
        </form>
    </section>
<?php else: ?>
    <section class="card">
        <div class="card-head">
            <div>
                <p class="eyebrow">Kontrol</p>
                <h2>Kontrol Data Laptop</h2>
                <p class="muted">Kelola data spesifikasi laptop di katalog MySQL.</p>
            </div>
            <div class="button-row">
                <a class="btn btn-outline" href="<?= e(url('home')) ?>">Landing Page</a>
                <form method="post" action="<?= e(url('admin')) ?>">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="admin_logout">
                    <button class="btn btn-primary" type="submit">Logout</button>
                </form>
            </div>
        </div>
    </section>

    <section class="card">
        <p class="eyebrow">Form</p>
        <h2><?= $isEditing ? 'Edit Laptop' : 'Tambah Laptop' ?></h2>
        <p class="muted">Isi seluruh spesifikasi agar data konsisten.</p>

        <form class="form-grid two-columns" method="post" action="<?= e(url('admin')) ?>">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="<?= $isEditing ? 'update_laptop' : 'create_laptop' ?>">
            <?php if ($isEditing): ?>
                <input type="hidden" name="id" value="<?= e($editing['id']) ?>">
            <?php endif; ?>

            <label class="span-2">
                Nama Laptop
                <input
                    type="text"
                    name="name"
                    required
                    value="<?= e($isEditing ? $editing['name'] : '') ?>"
                    placeholder="cth. Laptop X"
                >
            </label>
            <label>
                RAM (GB)
                <input type="number" min="1" name="ram" required value="<?= e($isEditing ? $editing['ram'] : '') ?>">
            </label>
            <label>
                Storage (GB)
                <input type="number" min="1" name="storage" required value="<?= e($isEditing ? $editing['storage'] : '') ?>">
            </label>
            <label>
                Prosesor (skor)
                <input type="number" min="1" name="processor" required value="<?= e($isEditing ? $editing['processor'] : '') ?>">
            </label>
            <label>
                Harga (Rp)
                <input type="number" min="1" step="500000" name="price" required value="<?= e($isEditing ? $editing['price'] : '') ?>">
            </label>

            <div class="button-row span-2">
                <button class="btn btn-primary" type="submit">
                    <?= $isEditing ? 'Simpan Perubahan' : 'Tambah Laptop' ?>
                </button>
                <?php if ($isEditing): ?>
                    <a class="btn btn-outline" href="<?= e(url('admin')) ?>">Batal Edit</a>
                <?php endif; ?>
            </div>
        </form>

        <form class="button-row" method="post" action="<?= e(url('admin')) ?>">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="restore_laptops">
            <button class="btn btn-secondary" type="submit">Reset ke Default</button>
        </form>
    </section>

    <section class="card">
        <h2>Daftar Laptop</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>RAM</th>
                        <th>Storage</th>
                        <th>Prosesor</th>
                        <th>Harga</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($laptops)): ?>
                        <tr>
                            <td colspan="6" class="text-center muted">Belum ada data laptop.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($laptops as $item): ?>
                            <tr>
                                <td><?= e($item['name']) ?></td>
                                <td><?= e($item['ram']) ?> GB</td>
                                <td><?= e($item['storage']) ?> GB</td>
                                <td><?= e($item['processor']) ?></td>
                                <td><?= e(format_rupiah((int)$item['price'])) ?></td>
                                <td>
                                    <div class="inline-actions">
                                        <a class="btn btn-outline btn-small" href="<?= e(url('admin', ['edit' => $item['id']])) ?>">Edit</a>
                                        <form method="post" action="<?= e(url('admin')) ?>" onsubmit="return confirm('Hapus laptop ini?');">
                                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="delete_laptop">
                                            <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                                            <button class="btn btn-danger btn-small" type="submit">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
