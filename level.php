<?php
/**
 * level.php - Pilih / ganti level belajar
 * Wajib diakses setelah registrasi. User bebas mengganti level kapan saja.
 */
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/chord_svg.php';
require_once __DIR__ . '/inc/layout.php';

require_login();

$user  = current_user();
$level = $user['level'];

// Administrator = hak akses utama yang mengatur website,
// tidak ikut sistem level belajar (beginner/intermediate/pro).
if ($user['role'] === 'admin') {
    flash_set('info', 'Administrator tidak perlu memilih level belajar. '
        . 'Gunakan Dashboard Admin untuk mengelola isi website.');
    redirect('admin/index.php');
}

$levelInfo = [
    'beginner' => [
        'judul'  => 'Beginner',
        'icon'   => 'bi-1-circle',
        'desc'   => 'Chord terbuka yang mudah (C, G, Am, D, Em), lagu sederhana, dan teknik dasar.',
        'poin'   => ['Chord dasar terbuka', 'Power chord (C5, E5, G5)', 'Lagu anak & folk'],
        'warna'  => 'success',
    ],
    'intermediate' => [
        'judul'  => 'Intermediate',
        'icon'   => 'bi-2-circle',
        'desc'   => 'Chord barre di fret 1-3, chord sevenths, dan lagu dengan pola lebih padat.',
        'poin'   => ['Chord barre (F, Bm)', 'Chord 7 & maj7', 'Progression lebih kompleks'],
        'warna'  => 'warning',
    ],
    'pro' => [
        'judul'  => 'Pro',
        'icon'   => 'bi-3-circle',
        'desc'   => 'Barre di fret tinggi, chord jazz, dan repertoar lagu yang lebih teknik.',
        'poin'   => ['Barre di fret tinggi', 'Extended, slash & altered chord', 'Lagu technical'],
        'warna'  => 'danger',
    ],
];

// ---------- Ganti / pilih level ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pilihan = p('level');

    if (!in_array($pilihan, level_options(), true)) {
        flash_set('error', 'Level tidak valid.');
    } elseif (!level_is_unlocked($pilihan, $user['level'])) {
        $blocker = level_blocker($pilihan);
        flash_set('error', 'Level ' . level_label($pilihan) . ' masih terkunci. '
            . 'Selesaikan level ' . level_label($blocker) . ' dulu untuk membukanya.');
    } else {
        db()->prepare('UPDATE users SET level = ? WHERE id = ?')
           ->execute([$pilihan, $user['id']]);

        log_activity('pilih_level', 'Level diubah ke ' . level_label($pilihan));
        flash_set('success', 'Level belajar kamu sekarang ' . level_label($pilihan)
            . '. Ayo selesaikan level ini untuk naik ke tahap berikutnya!');
        redirect('practice.php?level=' . $pilihan);
    }
}

// Jumlah chord & lagu per level untuk ditampilkan di tiap kartu
$chordCount = [];
foreach (db()->query('SELECT difficulty, COUNT(*) n FROM chords GROUP BY difficulty')->fetchAll() as $r) {
    $chordCount[$r['difficulty']] = (int) $r['n'];
}
$songCount = [];
foreach (db()->query('SELECT level, COUNT(*) n FROM songs GROUP BY level')->fetchAll() as $r) {
    $songCount[$r['level']] = (int) $r['n'];
}

// Contoh chord per level
$contoh = [
    'beginner'     => ['C', 'G', 'Am'],
    'intermediate' => ['F', 'G7', 'Fmaj7'],
    'pro'          => ['Bm'],
];

layout_head($level ? 'Ganti Level Belajar' : 'Pilih Level Belajar', '', []);
?>

<div class="row justify-content-center">
  <div class="col-lg-9">
    <div class="text-center mb-4">
      <?php if ($level): ?>
        <span class="badge bg-<?= level_color($level) ?> mb-2">Titik mulai: <?= e(level_label($level)) ?></span>
        <h1 class="h3 mb-1">Ganti Level Belajar</h1>
        <p class="text-dim">Kamu bebas memilih titik mulai. Setelah satu level <strong>selesai</strong>,
          level berikutnya terbuka secara berurutan. Level yang lebih rendah tetap boleh dipilih ulang.</p>
      <?php else: ?>
        <span class="badge bg-warning text-dark mb-2">Langkah terakhir</span>
        <h1 class="h3 mb-1">Pilih Titik Mulai Belajar</h1>
        <p class="text-dim">Pilih level yang paling cocok untukmu. Setelah satu level selesai,
          level berikutnya terbuka <strong>berurutan</strong>.</p>
      <?php endif; ?>
    </div>

    <form method="post" action="<?= e(url('level.php')) ?>">
      <?= csrf_field() ?>

      <div class="row g-3 mb-4">
        <?php foreach (level_options() as $lv):
            $unlocked = level_is_unlocked($lv);
            $isCur    = ($level === $lv);
            $blocker  = !$unlocked ? level_blocker($lv) : null;
            $mastered = level_mastered_count($lv);
            $total    = level_total_chords($lv);
            $percent  = level_progress_percent($lv);
            $songTotal = level_total_songs($lv);
            $songDone  = level_completed_song_count($lv);
            $songPct   = level_song_progress_percent($lv);
        ?>
          <div class="col-md-4">
            <label class="h-100 d-block <?= !$unlocked ? 'pe-none' : '' ?>">
              <input type="radio" name="level" value="<?= e($lv) ?>" class="visually-hidden"
                     <?= $isCur ? 'checked' : '' ?> <?= $unlocked ? '' : 'disabled' ?> required>
              <div class="gl-card h-100 p-4 level-pick <?= !$unlocked ? 'is-locked' : '' ?>">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <i class="bi <?= $levelInfo[$lv]['icon'] ?> fs-3 text-<?= $levelInfo[$lv]['warna'] ?>"></i>
                  <?php if ($isCur): ?>
                    <span class="badge bg-<?= $levelInfo[$lv]['warna'] ?>">Titik mulai</span>
                  <?php elseif (!$unlocked): ?>
                    <span class="badge bg-secondary"><i class="bi bi-lock-fill me-1"></i>Terkunci</span>
                  <?php endif; ?>
                </div>

                <h2 class="h5"><?= e($levelInfo[$lv]['judul']) ?></h2>
                <p class="text-dim small"><?= e($levelInfo[$lv]['desc']) ?></p>

                <ul class="list-unstyled small mb-3">
                  <?php foreach ($levelInfo[$lv]['poin'] as $poin): ?>
                    <li class="mb-1"><i class="bi bi-check-circle text-<?= $levelInfo[$lv]['warna'] ?> me-1"></i><?= e($poin) ?></li>
                  <?php endforeach; ?>
                </ul>

                <div class="d-flex gap-1 flex-wrap mb-3">
                  <?php foreach ($contoh[$lv] as $nm):
                      $c = array_values(array_filter(all_chords(), fn($x) => $x['name'] === $nm))[0] ?? null;
                      if (!$c) { continue; }
                  ?>
                    <span class="chord-chip" data-chord="<?= e($nm) ?>" style="cursor:default"><?= e($nm) ?></span>
                  <?php endforeach; ?>
                </div>

                <div class="d-flex justify-content-between small text-dim mb-1">
                  <span>Progres chord</span><span><?= $mastered ?> / <?= $total ?> chord</span>
                </div>
                <div class="progress progress-thin mb-2">
                  <div class="progress-bar bg-<?= $percent >= 100 ? 'success' : 'warning' ?>"
                       style="width: <?= $percent ?>%"></div>
                </div>

                <?php if ($songTotal > 0): ?>
                  <div class="d-flex justify-content-between small text-dim mb-1">
                    <span>Progres lagu</span><span><?= $songDone ?> / <?= $songTotal ?> lagu</span>
                  </div>
                  <div class="progress progress-thin mb-2">
                    <div class="progress-bar bg-<?= $songPct >= 100 ? 'success' : 'info' ?>"
                         style="width: <?= $songPct ?>%"></div>
                  </div>
                <?php endif; ?>

                <div class="small text-dim border-top pt-2" style="border-color:var(--gl-border)!important">
                  <?= (int) ($chordCount[$lv] ?? 0) ?> chord &middot; <?= (int) ($songCount[$lv] ?? 0) ?> lagu
                  <?php if (!$unlocked): ?>
                    &middot; selesaikan <?= e(level_label($blocker)) ?> dulu
                  <?php endif; ?>
                </div>
              </div>
            </label>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-accent">
          <i class="bi bi-check-lg me-1"></i>Simpan Level
        </button>
        <?php if ($level): ?>
          <a href="<?= e(url('index.php')) ?>" class="btn btn-accent-outline">Kembali</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<?php layout_foot(['chord_db' => true]); ?>
