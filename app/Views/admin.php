<?php

declare(strict_types=1);

$editing = $editing ?? null;
$isEditing = is_array($editing);
$users = is_array($users ?? null) ? $users : [];
$editingUser = is_array($editingUser ?? null) ? $editingUser : null;
$isEditingUser = is_array($editingUser);
$currentAdmin = is_array($currentAdmin ?? null) ? $currentAdmin : null;
$sales = is_array($sales ?? null) ? $sales : [];
$salesSummary = is_array($salesSummary ?? null) ? $salesSummary : ['count' => 0, 'revenue' => 0];
?>
<?php if (!($isAuthed ?? false)): ?>
    <section class="card narrow">
        <p class="eyebrow">Autentikasi</p>
        <h2>Login Administrator</h2>
        <p class="muted">Masuk untuk mengelola laptop, user, dan transaksi kasir.</p>

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
                <p class="eyebrow">Kontrol Penuh</p>
                <h2>Dashboard Admin</h2>
                <p class="muted">
                    Kelola data laptop, akun role (`admin`, `cashier`, `user`), dan histori transaksi kasir.
                </p>
            </div>
            <div class="button-row">
                <a class="btn btn-outline" href="<?= e(url('home')) ?>">Landing Page</a>
                <a class="btn btn-outline" href="<?= e(url('cashier')) ?>">Buka Panel Kasir</a>
                <form method="post" action="<?= e(url('admin')) ?>">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="admin_logout">
                    <button class="btn btn-primary" type="submit">Logout</button>
                </form>
            </div>
        </div>
    </section>

    <section class="card">
        <p class="eyebrow">Master Data</p>
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

    <section class="card">
        <p class="eyebrow">Role Management</p>
        <h2><?= $isEditingUser ? 'Edit User' : 'Tambah User' ?></h2>
        <p class="muted">Admin dapat membuat akun `admin`, `cashier`, atau `user`.</p>

        <form class="form-grid two-columns" method="post" action="<?= e(url('admin')) ?>">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="<?= $isEditingUser ? 'update_user' : 'create_user' ?>">
            <?php if ($isEditingUser): ?>
                <input type="hidden" name="id" value="<?= e((string)$editingUser['id']) ?>">
            <?php endif; ?>

            <label>
                Nama (opsional)
                <input type="text" name="name" value="<?= e((string)($isEditingUser ? ($editingUser['name'] ?? '') : '')) ?>" placeholder="cth. Budi">
            </label>

            <label>
                Email
                <input type="email" name="email" required value="<?= e((string)($isEditingUser ? ($editingUser['email'] ?? '') : '')) ?>" placeholder="nama@email.com">
            </label>

            <?php $selectedRole = $isEditingUser ? (string)$editingUser['role'] : 'user'; ?>
            <label>
                Role
                <select name="role" required>
                    <option value="admin" <?= $selectedRole === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="cashier" <?= $selectedRole === 'cashier' ? 'selected' : '' ?>>Kasir</option>
                    <option value="user" <?= $selectedRole === 'user' ? 'selected' : '' ?>>User</option>
                </select>
            </label>

            <label>
                <?= $isEditingUser ? 'Password Baru (opsional)' : 'Password' ?>
                <input
                    type="password"
                    name="password"
                    <?= $isEditingUser ? '' : 'required' ?>
                    placeholder="<?= $isEditingUser ? 'Isi jika ingin ganti password' : 'minimal 6 karakter' ?>"
                >
            </label>

            <div class="button-row span-2">
                <button class="btn btn-primary" type="submit">
                    <?= $isEditingUser ? 'Simpan User' : 'Tambah User' ?>
                </button>
                <?php if ($isEditingUser): ?>
                    <a class="btn btn-outline" href="<?= e(url('admin')) ?>">Batal Edit</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="card">
        <h2>Daftar User</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="5" class="text-center muted">Belum ada user.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $item): ?>
                            <?php $isCurrentAdmin = (int)($currentAdmin['id'] ?? 0) === (int)$item['id']; ?>
                            <tr>
                                <td><?= e((string)($item['name'] ?: '-')) ?></td>
                                <td><?= e($item['email']) ?></td>
                                <td><?= e($item['role']) ?></td>
                                <td><?= e((string)$item['created_at']) ?></td>
                                <td>
                                    <div class="inline-actions">
                                        <a class="btn btn-outline btn-small" href="<?= e(url('admin', ['edit_user' => $item['id']])) ?>">Edit</a>
                                        <?php if ($isCurrentAdmin): ?>
                                            <span class="muted">Akun aktif</span>
                                        <?php else: ?>
                                            <form method="post" action="<?= e(url('admin')) ?>" onsubmit="return confirm('Hapus user ini?');">
                                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="id" value="<?= e((string)$item['id']) ?>">
                                                <button class="btn btn-danger btn-small" type="submit">Hapus</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="card-head">
            <div>
                <p class="eyebrow">Monitoring</p>
                <h2>Transaksi Kasir</h2>
                <p class="muted">
                    Total transaksi: <?= e((string)($salesSummary['count'] ?? 0)) ?>.
                    Total pendapatan: <?= e(format_rupiah((int)($salesSummary['revenue'] ?? 0))) ?>.
                </p>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Laptop</th>
                        <th>Kasir</th>
                        <th>Pembeli</th>
                        <th>Qty</th>
                        <th>Harga Satuan</th>
                        <th>Total</th>
                        <th>Waktu</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales)): ?>
                        <tr>
                            <td colspan="9" class="text-center muted">Belum ada transaksi kasir.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sales as $item): ?>
                            <tr>
                                <td><?= e($item['invoice_code']) ?></td>
                                <td><?= e($item['laptop_name']) ?></td>
                                <td><?= e($item['cashier_name']) ?></td>
                                <td><?= e((string)($item['customer_name'] ?: '-')) ?></td>
                                <td><?= e((string)$item['quantity']) ?></td>
                                <td><?= e(format_rupiah((int)$item['unit_price'])) ?></td>
                                <td><?= e(format_rupiah((int)$item['total_price'])) ?></td>
                                <td><?= e((string)$item['created_at']) ?></td>
                                <td>
                                    <form method="post" action="<?= e(url('admin')) ?>" onsubmit="return confirm('Hapus transaksi ini?');">
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

