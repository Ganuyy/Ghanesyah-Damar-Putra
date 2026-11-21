<?php
/**
 * layout.php
 * Template bersama: <head>, navbar (berbeda per hak akses), footer,
 * modal diagram chord, dan pemanggilan asset.
 *
 * Pemakaian di setiap halaman:
 *     require_once __DIR__ . '/inc/layout.php';
 *     layout_head('Judul Halaman', 'menu_aktif');
 *     ... isi halaman ...
 *     layout_foot();
 *
 * Opsi layout_head:
 *     'title'     : judul halaman
 *     'active'    : menu aktif: home | chords | songs | practice | progress | admin
 *     'chord_db'  : true jika halaman ini butuh modal diagram chord
 *     'wide'      : true untuk halaman dengan tabel lebar (admin)
 *     'full'      : true untuk halaman tanpa container (login/register)
 */

// ==========================================================
//  Warna & Ukuran (dibaca dari CSS lewat variable, lihat style.css)
// ==========================================================
function layout_head($title = null, $active = '', $opts = [])
{
    $siteName   = 'GuitarTice';
    $tagline    = 'Belajar Gitar Santai';
    $fullTitle  = $title ? $title . ' - ' . $siteName : $siteName . ' - ' . $tagline;
    $user       = current_user();

    // Disimpan supaya layout_foot() tahu cara menutup tag dengan benar
    $GLOBALS['gl_layout_opts'] = $opts;
    ?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($fullTitle) ?></title>

    <!-- Favicon GuitarTice -->
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('favicon.svg')) ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset('favicon-32.png')) ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= e(asset('favicon-16.png')) ?>">
    <link rel="icon" href="<?= e(asset('favicon.ico')) ?>" sizes="any">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= e(asset('apple-touch-icon.png')) ?>">
    <meta name="theme-color" content="#0d0912">

    <!-- Bootstrap 5 + Bootstrap Icons (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Tema aplikasi (versi file ikut di URL agar browser tidak memakai cache lama) -->
    <link href="<?= e(asset('style.css')) ?>?v=<?= e((string) (@filemtime(APP_ROOT . '/assets/style.css') ?: 1)) ?>" rel="stylesheet">
</head>
<body class="gl-body" data-logged-in="<?= is_logged_in() ? '1' : '0' ?>">

<?php if (empty($opts['full'])) { layout_navbar($active, $user); } ?>

<?php if (empty($opts['full'])) : ?>
<main class="container py-4">
    <?php $msg = flash_show(); if ($msg) { echo $msg; } ?>
<?php else : ?>
<main class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-md-7 col-lg-5">
    <?php $msg = flash_show(); if ($msg) { echo $msg; } ?>
<?php endif; ?>
<?php
}

// ==========================================================
//  Navbar : menu menyesuaikan hak akses
// ==========================================================
function layout_navbar($active, $user)
{
    $level   = $user['level'] ?? null;
    $isAdmin = $user && $user['role'] === 'admin';
    ?>
<nav class="navbar navbar-expand-lg gl-navbar sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= e(url('index.php')) ?>">
      <span class="brand-mark"><i class="bi bi-music-note-beamed"></i></span>
      <span>Guitar<span class="brand-accent">Tice</span></span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#glNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="glNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link <?= $active === 'home' ? 'active' : '' ?>"
             href="<?= e(url('index.php')) ?>">Beranda</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $active === 'chords' ? 'active' : '' ?>"
             href="<?= e(url('chords.php')) ?>">Chord Library</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $active === 'songs' ? 'active' : '' ?>"
             href="<?= e(url('songs.php')) ?>">Lagu</a>
        </li>
        <?php if (!$isAdmin): ?>
          <li class="nav-item">
            <a class="nav-link <?= $active === 'practice' ? 'active' : '' ?>"
               href="<?= e(url('practice.php')) ?>">Latihan</a>
          </li>
        <?php endif; ?>

        <?php if ($user && !$isAdmin): ?>
          <li class="nav-item">
            <a class="nav-link <?= $active === 'progress' ? 'active' : '' ?>"
               href="<?= e(url('progress.php')) ?>">Progres Saya</a>
          </li>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?= $active === 'admin' ? 'active' : '' ?>"
               href="#" data-bs-toggle="dropdown" role="button">Menu Admin</a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="<?= e(url('admin/index.php')) ?>">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard &amp; Statistik</a></li>
              <li><a class="dropdown-item" href="<?= e(url('admin/songs.php')) ?>">
                    <i class="bi bi-music-note-list me-2"></i>Kelola Lagu</a></li>
              <li><a class="dropdown-item" href="<?= e(url('admin/chords.php')) ?>">
                    <i class="bi bi-diagram-3 me-2"></i>Kelola Chord</a></li>
              <li><a class="dropdown-item" href="<?= e(url('admin/users.php')) ?>">
                    <i class="bi bi-people me-2"></i>Kelola User</a></li>
            </ul>
          </li>
        <?php endif; ?>
      </ul>

      <ul class="navbar-nav align-items-lg-center">
        <?php if ($user): ?>
          <li class="nav-item d-flex align-items-center gap-2 me-lg-2">
            <?= $isAdmin ? '<span class="badge bg-warning">Admin</span>' : level_badge($level) ?>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2"
               href="#" data-bs-toggle="dropdown" role="button">
              <i class="bi bi-person-circle fs-5"></i>
              <?= e($user['username']) ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="<?= e(url('profil.php')) ?>">
                    <i class="bi bi-gear me-2"></i>Profil</a></li>
              <?php if (!$isAdmin && !$level): ?>
                <li><a class="dropdown-item text-warning" href="<?= e(url('level.php')) ?>">
                    <i class="bi bi-flag me-2"></i>Pilih level belajar</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="<?= e(url('profil.php#password')) ?>">
                    <i class="bi bi-key me-2"></i>Ganti password</a></li>
              <li><a class="dropdown-item text-danger" href="<?= e(url('logout.php')) ?>">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="btn btn-outline-light btn-sm me-2" href="<?= e(url('login.php')) ?>">Login</a>
          </li>
          <li class="nav-item">
            <a class="btn btn-accent btn-sm" href="<?= e(url('register.php')) ?>">Daftar</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<?php
}

// ==========================================================
//  Penutup halaman + modal diagram chord
// ==========================================================
function layout_foot($opts = [])
{
    $head = $GLOBALS['gl_layout_opts'] ?? [];
    $opts = array_merge($head, $opts);
    ?>
    <?php if (!empty($opts['full'])) : ?>
    </div><!-- /.col -->
  </div><!-- /.row -->
    <?php endif; ?>
</main>

<footer class="gl-footer mt-5 py-4">
  <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
    <span>&copy; <?= date('Y') ?> Genjrengin bae udah, benar salah belakangan</span>
    <span class="text-dim">GuitarTice</span>
  </div>
</footer>

<?php if (!empty($opts['chord_db'])) : ?>
<!-- Modal: muncul ketika chord di-klik pada halaman lagu -->
<div class="modal fade" id="chordModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content gl-modal">
      <div class="modal-header">
        <h5 class="modal-title">Posisi Jari</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body text-center">
        <div id="chordModalName" class="chord-modal-name"></div>
        <div id="chordModalBody" class="d-flex justify-content-center"></div>
        <p id="chordModalText" class="chord-modal-text"></p>
      </div>
    </div>
  </div>
</div>
<?= render_chord_database() ?>
<?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= e(asset('chord-audio.js')) ?>?v=<?= e((string) (@filemtime(APP_ROOT . '/assets/chord-audio.js') ?: 1)) ?>"></script>
    <script src="<?= e(asset('app.js')) ?>?v=<?= e((string) (@filemtime(APP_ROOT . '/assets/app.js') ?: 1)) ?>"></script>
</body>
</html>
<?php
}