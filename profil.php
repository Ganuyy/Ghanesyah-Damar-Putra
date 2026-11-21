<?php
/**
 * profil.php - Profil user: ganti email, ganti password, lihat ringkasan akun
 */
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/chord_svg.php';
require_once __DIR__ . '/inc/layout.php';

require_login();

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = p('aksi');

    // ---------- Ganti email ----------
    if ($aksi === 'email') {
        $email = p('email');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash_set('error', 'Format email tidak valid.');
        } else {
            $cek = db()->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
            $cek->execute([$email, $user['id']]);

            if ($cek->fetch()) {
                flash_set('error', 'Email tersebut sudah dipakai user lain.');
            } else {
                db()->prepare('UPDATE users SET email = ? WHERE id = ?')->execute([$email, $user['id']]);
                log_activity('ganti_email', 'Email diubah');
                flash_set('success', 'Email berhasil diubah.');
            }
        }
        redirect('profil.php');
    }

    // ---------- Ganti password ----------
    if ($aksi === 'password') {
        $lama    = (string) ($_POST['password_lama'] ?? '');
        $baru    = (string) ($_POST['password_baru'] ?? '');
        $konfirm = (string) ($_POST['password_ulang'] ?? '');

        // Password lama diverifikasi langsung ke hash di database
        $stmt = db()->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $hashLama = (string) $stmt->fetchColumn();

        if (!password_verify($lama, $hashLama)) {
            flash_set('error', 'Password lama salah.');
            redirect('profil.php#password');
        }

        if (strlen($baru) < 6) {
            flash_set('error', 'Password baru minimal 6 karakter.');
        } elseif ($baru !== $konfirm) {
            flash_set('error', 'Konfirmasi password baru tidak sama.');
        } else {
            db()->prepare('UPDATE users SET password = ? WHERE id = ?')
               ->execute([password_hash($baru, PASSWORD_DEFAULT), $user['id']]);

            log_activity('ganti_password', 'Password diubah');
            flash_set('success', 'Password berhasil diubah.');
        }
        redirect('profil.php#password');
    }
}

// Ringkasan untuk ditampilkan
$overview = chord_overview();
$stLagu = db()->prepare('SELECT COUNT(*) FROM user_song_progress WHERE user_id = ?');
$stLagu->execute([$user['id']]);
$jmlLagu = (int) $stLagu->fetchColumn();

layout_head('Profil Saya', '', []);
?>

<div class="mb-4">
  <h1 class="h3 mb-1">Profil Saya</h1>
  <p class="text-dim">Kelola data akun dan lihat ringkasan belajarmu.</p>
</div>

<div class="row g-4">
  <!-- Ringkasan -->
  <div class="col-lg-4">
    <div class="gl-card p-4 mb-4 text-center">
      <i class="bi bi-person-circle" style="font-size:4rem;color:var(--gl-accent)"></i>
      <h2 class="h5 mt-2 mb-0"><?= e($user['username']) ?></h2>
      <div class="text-dim small mb-2"><?= e($user['email']) ?></div>
      <div class="mb-3">
        <?php if ($user['role'] === 'admin'): ?>
          <span class="badge bg-warning"><i class="bi bi-shield-lock-fill me-1"></i>Administrator</span>
        <?php else: ?>
          <?= level_badge($user['level']) ?>
        <?php endif; ?>
      </div>
      <div class="small text-dim mt-3">Bergabung sejak <?= e(format_date($user['created_at'])) ?></div>
    </div>
    <?php if ($user['role'] === 'admin'): ?>

    <div class="gl-card p-4">
      <h3 class="h6 mb-3"><i class="bi bi-sliders me-2"></i>Hak Akses</h3>
      <ul class="list-unstyled small mb-2">
        <li class="mb-2"><i class="bi bi-diagram-3 me-2 text-warning"></i>Mengelola Chord Library</li>
        <li class="mb-2"><i class="bi bi-music-note-list me-2 text-warning"></i>Mengelola Lagu & Lirik</li>
        <li class="mb-2"><i class="bi bi-people me-2 text-warning"></i>Mengelola User</li>
        <li class="mb-0"><i class="bi bi-bar-chart me-2 text-warning"></i>Melihat Statistik & Log</li>
      </ul>
      <a href="<?= e(url('admin/index.php')) ?>" class="btn btn-accent btn-sm w-100 mt-2">
        <i class="bi bi-speedometer2 me-1"></i>Buka Dashboard Admin
      </a>
    </div>
    <?php else: ?>

    <div class="gl-card p-4">
      <h3 class="h6 mb-3">Ringkasan Belajar</h3>
      <table class="table table-sm table-borderless mb-0">
        <tr><td class="text-dim">Chord dikuasai</td><td class="text-end fw-bold"><?= $overview['kuasai'] ?> / <?= $overview['total'] ?></td></tr>
        <tr><td class="text-dim">Sedang dilatih</td><td class="text-end fw-bold"><?= $overview['latih'] ?></td></tr>
        <tr><td class="text-dim">Progres chord</td><td class="text-end"><?= percent_badge($overview['persen']) ?></td></tr>
        <tr><td class="text-dim">Lagu dipelajari</td><td class="text-end fw-bold"><?= $jmlLagu ?></td></tr>
      </table>
      <a href="<?= e(url('progress.php')) ?>" class="btn btn-accent-outline btn-sm w-100 mt-3">
        Lihat Progres Lengkap
      </a>
    </div>
    <?php endif; ?>
  </div>

  <!-- Form -->
  <div class="col-lg-8">
    <?php if ($user['role'] === 'admin'): ?>
    <div class="gl-card p-4 mb-4">
      <h2 class="h5 mb-3"><i class="bi bi-shield-lock me-1"></i>Administrator</h2>
      <p class="text-dim small mb-3">
        Akun ini adalah hak akses utama yang mengatur seluruh isi website:
        chord, lagu, user, dan statistik. Administrator tidak ikut sistem level belajar
        beginner/intermediate/pro tersebut.
      </p>
      <a href="<?= e(url('admin/index.php')) ?>" class="btn btn-accent btn-sm">
        <i class="bi bi-gear me-1"></i>Kelola Website
      </a>
    </div>
    <?php else: ?>
    <div class="gl-card p-4 mb-4">
      <h2 class="h5 mb-3"><i class="bi bi-flag me-1"></i>Level Belajar</h2>
      <p class="text-dim small mb-2">
        Titik mulai kamu: <strong><?= e(level_label($user['level'])) ?></strong>.
        Selesaikan level ini untuk membuka level berikutnya secara berurutan.
        Kamu masih boleh memilih ulang level yang lebih rendah.
      </p>
      <?php $lvTotal = level_total_chords($user['level'] ?: 'beginner');
            $lvDone  = level_mastered_count($user['level'] ?: 'beginner'); ?>
      <div class="d-flex justify-content-between small text-dim mb-1">
        <span>Progres level ini</span><span><?= $lvDone ?> / <?= $lvTotal ?> chord</span>
      </div>
      <div class="progress progress-thin mb-3">
        <div class="progress-bar bg-<?= ($lvTotal > 0 && $lvDone >= $lvTotal) ? 'success' : 'warning' ?>"
             style="width: <?= level_progress_percent($user['level'] ?: 'beginner') ?>%"></div>
      </div>
      <a href="<?= e(url('level.php')) ?>" class="btn btn-accent btn-sm">
        <i class="bi bi-arrow-left-right me-1"></i>Ganti Level
      </a>
    </div>
    <?php endif; ?>

    <div class="gl-card p-4 mb-4">
      <h2 class="h5 mb-3"><i class="bi bi-envelope me-1"></i>Ganti Email</h2>
      <form method="post" action="<?= e(url('profil.php')) ?>" class="row g-2 align-items-end">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="email">
        <div class="col-sm-8">
          <label class="form-label small" for="email">Email baru</label>
          <input type="email" id="email" name="email" class="form-control"
                 value="<?= e($user['email']) ?>" required>
        </div>
        <div class="col-sm-4">
          <button type="submit" class="btn btn-accent-outline w-100">Simpan</button>
        </div>
      </form>
    </div>

    <div class="gl-card p-4" id="password">
      <h2 class="h5 mb-3"><i class="bi bi-key me-1"></i>Ganti Password</h2>
      <form method="post" action="<?= e(url('profil.php')) ?>" class="row g-3">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="password">

        <div class="col-md-4">
          <label class="form-label small" for="password_lama">Password lama</label>
          <input type="password" id="password_lama" name="password_lama" class="form-control"
                 required autocomplete="current-password">
        </div>
        <div class="col-md-4">
          <label class="form-label small" for="password_baru">Password baru</label>
          <input type="password" id="password_baru" name="password_baru" class="form-control"
                 required minlength="6" autocomplete="new-password">
          <div class="form-text">minimal 6 karakter</div>
        </div>
        <div class="col-md-4">
          <label class="form-label small" for="password_ulang">Ulangi password baru</label>
          <input type="password" id="password_ulang" name="password_ulang" class="form-control"
                 required autocomplete="new-password">
        </div>

        <div class="col-12">
          <button type="submit" class="btn btn-accent">
            <i class="bi bi-shield-check me-1"></i>Ubah Password
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php layout_foot(); ?>