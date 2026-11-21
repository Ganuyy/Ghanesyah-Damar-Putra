<?php
/**
 * register.php - Pendaftaran user baru
 *
 * Setelah berhasil daftar, user otomatis login dan langsung
 * diarahkan ke halaman pilih level belajar (level.php).
 */
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/chord_svg.php';
require_once __DIR__ . '/inc/layout.php';

// Sudah login? Jangan daftar lagi
if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];
$old    = ['username' => '', 'email' => '', 'fullname' => ''];

// ---------- Proses saat form dikirim ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $old['username'] = p('username');
    $old['email']    = p('email');
    $old['fullname'] = p('fullname');
    $password        = (string) ($_POST['password'] ?? '');
    $passwordUlang   = (string) ($_POST['password_ulang'] ?? '');

    // --- Validasi username ---
    if ($old['username'] === '') {
        $errors['username'] = 'Username wajib diisi.';
    } elseif (mb_strlen($old['username']) < 3) {
        $errors['username'] = 'Username minimal 3 karakter.';
    } elseif (!preg_match('/^[a-zA-Z0-9_.-]+$/', $old['username'])) {
        $errors['username'] = 'Username hanya boleh huruf, angka, titik, garis, dan underscore.';
    } else {
        $cek = db()->prepare('SELECT id FROM users WHERE username = ?');
        $cek->execute([$old['username']]);
        if ($cek->fetch()) {
            $errors['username'] = 'Username ini sudah dipakai. Coba yang lain.';
        }
    }

    // --- Validasi email ---
    if ($old['email'] === '') {
        $errors['email'] = 'Email wajib diisi.';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format email tidak valid.';
    } else {
        $cek = db()->prepare('SELECT id FROM users WHERE email = ?');
        $cek->execute([$old['email']]);
        if ($cek->fetch()) {
            $errors['email'] = 'Email ini sudah terdaftar. Silakan login.';
        }
    }

    // --- Validasi password ---
    if ($password === '') {
        $errors['password'] = 'Password wajib diisi.';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password minimal 6 karakter.';
    } elseif ($password !== $passwordUlang) {
        $errors['password'] = 'Konfirmasi password tidak sama.';
    }

    // --- Simpan kalau tidak ada error ---
    if (!$errors) {
        db()->prepare(
            'INSERT INTO users (username, email, password, fullname, level, role)
             VALUES (?, ?, ?, ?, NULL, \'user\')'
        )->execute([
            $old['username'],
            $old['email'],
            password_hash($password, PASSWORD_DEFAULT),
            $old['fullname'] !== '' ? $old['fullname'] : null,
        ]);

        // Langsung login agar tidak perlu mengetik ulang
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) db()->lastInsertId();

        log_activity('register', 'Akun baru: ' . $old['username']);

        flash_set('success', 'Registrasi berhasil. Sekarang pilih level belajarmu.');
        redirect('level.php');
    }
}

layout_head('Daftar', '', []);
?>

<div class="row justify-content-center">
  <div class="col-md-8 col-lg-6">
    <div class="text-center mb-4">
      <h1 class="h3 mb-1">Buat Akun Baru</h1>
      <p class="text-dim">Gratis. Tinggal pilih level belajar setelah daftar.</p>
    </div>

    <div class="auth-card">
      <form method="post" action="<?= e(url('register.php')) ?>" novalidate>
        <?= csrf_field() ?>

        <div class="mb-3">
          <label class="form-label" for="username">Username <span class="text-danger">*</span></label>
          <input type="text" id="username" name="username" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                 value="<?= e($old['username']) ?>" placeholder="contoh: ghanes" required autofocus>
          <?php if (isset($errors['username'])): ?>
            <div class="invalid-feedback"><?= e($errors['username']) ?></div>
          <?php endif; ?>
        </div>

        <div class="mb-3">
          <label class="form-label" for="fullname">Nama Lengkap</label>
          <input type="text" id="fullname" name="fullname" class="form-control"
                 value="<?= e($old['fullname']) ?>" placeholder="boleh dikosongkan">
        </div>

        <div class="mb-3">
          <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
          <input type="email" id="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                 value="<?= e($old['email']) ?>" placeholder="nama@email.com" required>
          <?php if (isset($errors['email'])): ?>
            <div class="invalid-feedback"><?= e($errors['email']) ?></div>
          <?php endif; ?>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-sm-6">
            <label class="form-label" for="password">Password <span class="text-danger">*</span></label>
            <input type="password" id="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                   placeholder="minimal 6 karakter" required>
          </div>
          <div class="col-sm-6">
            <label class="form-label" for="password_ulang">Ulangi Password <span class="text-danger">*</span></label>
            <input type="password" id="password_ulang" name="password_ulang" class="form-control" placeholder="ketik ulang" required>
          </div>
        </div>
        <?php if (isset($errors['password'])): ?>
          <div class="text-danger small mb-3"><?= e($errors['password']) ?></div>
        <?php endif; ?>

        <button type="submit" class="btn btn-accent w-100 mb-3">
          <i class="bi bi-person-plus me-1"></i>Daftar Sekarang
        </button>

        <p class="text-center text-dim small mb-0">
          Sudah punya akun? <a href="<?= e(url('login.php')) ?>">Login di sini</a>
        </p>
      </form>
    </div>
  </div>
</div>

<?php layout_foot(); ?>