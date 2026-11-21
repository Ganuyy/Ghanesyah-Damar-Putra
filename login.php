<?php
/**
 * login.php - Login user
 *
 * Mendukung parameter ?next=/halaman.php agar setelah login user
 * dikembalikan ke halaman yang tadi sedang dibuka.
 */
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/chord_svg.php';
require_once __DIR__ . '/inc/layout.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = '';
$old   = ['username' => ''];
$next  = q('next');

// Cegah open redirect: hanya izinkan path internal
if ($next !== '' && preg_match('#^(https?:)?//#', $next)) {
    $next = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $old['username'] = p('username');
    $password        = (string) ($_POST['password'] ?? '');

    if ($old['username'] === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } elseif (login_user($old['username'], $password)) {
        flash_set('success', 'Login berhasil. Selamat datang kembali!');

        // Admin langsung ke dashboard admin, user biasa ke beranda
        $user = current_user();
        if ($user && $user['role'] === 'admin' && $next === '') {
            redirect('admin/index.php');
        }
        // User yang belum memilih level diarahkan ke halaman pilih level
        if ($user && !$user['level'] && $next === '') {
            redirect('level.php');
        }
        redirect($next !== '' ? $next : 'index.php');
    } else {
        $error = 'Username atau password salah.';
    }
}

layout_head('Login', '', []);
?>

<div class="row justify-content-center">
  <div class="col-md-8 col-lg-5">
    <div class="text-center mb-4">
      <h1 class="h3 mb-1">Login</h1>
      <p class="text-dim">Masuk agar progress latihan kamu tersimpan.</p>
    </div>

    <div class="auth-card">
      <form method="post" action="<?= e(url('login.php' . ($next !== '' ? '?next=' . urlencode($next) : ''))) ?>" novalidate>
        <?= csrf_field() ?>

        <?php if ($error !== ''): ?>
          <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="mb-3">
          <label class="form-label" for="username">Username</label>
          <input type="text" id="username" name="username" class="form-control"
                 value="<?= e($old['username']) ?>" required autofocus autocomplete="username">
        </div>

        <div class="mb-3">
          <label class="form-label" for="password">Password</label>
          <input type="password" id="password" name="password" class="form-control"
                 required autocomplete="current-password">
        </div>

        <button type="submit" class="btn btn-accent w-100 mb-3">
          <i class="bi bi-box-arrow-in-right me-1"></i>Login
        </button>

        <p class="text-center text-dim small mb-0">
          Belum punya akun? <a href="<?= e(url('register.php')) ?>">Daftar gratis</a>
        </p>
      </form>
    </div>


<?php layout_foot(); ?>