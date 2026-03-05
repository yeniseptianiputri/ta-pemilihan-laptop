<?php

declare(strict_types=1);

$messages = is_array($messages ?? null) ? $messages : [];
$currentUser = is_array($currentUser ?? null) ? $currentUser : null;
?>
<?php if (!($isAuthed ?? false)): ?>
    <section class="card">
        <p class="eyebrow">Akses</p>
        <h2>Akses Pengguna</h2>
        <p class="lead">Login atau daftar dulu untuk menggunakan bot rekomendasi laptop.</p>
    </section>

    <section class="two-pane">
        <article class="card">
            <h2>Login User</h2>
            <form class="form-grid" method="post" action="<?= e(url('konsultasi')) ?>">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="user_login">
                <label>
                    Email User
                    <input type="email" name="email" required placeholder="user@laptop.local">
                </label>
                <label>
                    Password User
                    <input type="password" name="password" required placeholder="password">
                </label>
                <button class="btn btn-primary" type="submit">Masuk</button>
            </form>
        </article>

        <article class="card">
            <h2>Register User</h2>
            <form class="form-grid" method="post" action="<?= e(url('konsultasi')) ?>">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="user_register">
                <label>
                    Nama (opsional)
                    <input type="text" name="name" placeholder="cth. Budi">
                </label>
                <label>
                    Email User
                    <input type="email" name="email" required placeholder="nama@email.com">
                </label>
                <label>
                    Password
                    <input type="password" name="password" required placeholder="minimal 6 karakter">
                </label>
                <label>
                    Konfirmasi Password
                    <input type="password" name="confirm_password" required placeholder="ulang password">
                </label>
                <button class="btn btn-primary" type="submit">Daftar</button>
            </form>
        </article>
    </section>
<?php else: ?>
    <section class="card">
        <div class="card-head">
            <div>
                <p class="eyebrow">Sesi Aktif</p>
                <h2>Ruang Konsultasi</h2>
                <p class="muted">
                    Login sebagai: <?= e($currentUser['email'] ?? '-') ?> ·
                    Data katalog terpakai: <?= e((string)($catalogCount ?? 0)) ?> laptop
                </p>
            </div>
            <form method="post" action="<?= e(url('konsultasi')) ?>">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="user_logout">
                <button class="btn btn-outline" type="submit">Logout</button>
            </form>
        </div>
    </section>

    <section class="card">
        <form class="form-grid" method="post" action="<?= e(url('konsultasi')) ?>">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="send_chat">

            <label>
                Budget Maksimal (Rp)
                <input type="number" min="1" step="500000" name="budget" required placeholder="cth. 9000000">
            </label>
            <label>
                Kebutuhan / Catatan
                <textarea name="needs" rows="5" placeholder="cth. untuk kuliah, desain ringan, baterai awet"></textarea>
            </label>
            <div class="button-row">
                <button class="btn btn-primary" type="submit">Minta Saran</button>
                <button class="btn btn-outline" type="submit" name="action" value="reset_chat">Reset Riwayat</button>
            </div>
        </form>
    </section>

    <section class="card">
        <h2>Riwayat Chat</h2>
        <?php if (empty($messages)): ?>
            <p class="muted">Belum ada percakapan. Isi form di atas untuk mulai bertanya.</p>
        <?php else: ?>
            <div class="chat-list">
                <?php foreach ($messages as $message): ?>
                    <?php $role = ($message['role'] ?? 'assistant') === 'user' ? 'user' : 'assistant'; ?>
                    <article class="chat-bubble <?= e($role) ?>">
                        <p class="chat-role"><?= $role === 'user' ? 'User' : 'Bot' ?></p>
                        <p><?= nl2br(e($message['text'] ?? '')) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
