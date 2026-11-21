<?php
/**
 * practice.php - Halaman latihan chord
 *
 * Alur:
 *   - Tampilkan satu chord sebagai target latihan (diagram besar).
 *   - User menekan: "Sudah Bisa" | "Latihan Dulu" | "Belum Bisa".
 *   - User LOGIN  -> status tersimpan ke tabel user_chord_progress.
 *   - Guest       -> HANYA tampilkan peringatan, TIDAK ada yang ditulis
 *                     ke database (progres tamu tidak tersimpan).
 *
 * Parameter:
 *   ?chord=ID  -> langsung buka chord tertentu
 *   ?song=ID   -> buka daftar chord yang dipakai sebuah lagu
 */
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/chord_svg.php';
require_once __DIR__ . '/inc/layout.php';

$user  = current_user();
$level = $user['level'] ?? 'beginner';

// ---------- Proses tombol latihan (POST biasa, tanpa AJAX) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chordId = (int) p('chord_id');
    $aksi    = p('aksi');          // kuasai | latih | belum
    $status  = ['kuasai' => 'kuasai', 'latih' => 'latih', 'belum' => 'belum'][$aksi] ?? null;
    $back    = p('back', 'practice.php');

    if ($status === null || !get_chord($chordId)) {
        flash_set('error', 'Aksi latihan tidak valid.');
        redirect('practice.php');
    }

    if (!$user) {
        // Guest: sengaja tidak menyentuh database
        flash_set('warning', 'Progress tidak tersimpan karena kamu belum login. '
            . 'Silakan daftar atau login untuk menyimpan progress latihan.');
    } else {
        set_chord_status($chordId, $status);

        $nama = get_chord($chordId)['name'];
        if ($status === 'kuasai') {
            log_activity('chord_kuasai', 'Chord ' . $nama . ' dikuasai');
            flash_set('success', 'Mantap! Chord ' . $nama . ' ditandai sudah bisa.');
        } elseif ($status === 'latih') {
            log_activity('chord_latih', 'Chord ' . $nama . ' sedang dilatih');
            flash_set('info', 'Chord ' . $nama . ' tercatat sedang kamu latih.');
        } else {
            log_activity('chord_belum', 'Chord ' . $nama . ' diulang dari awal');
            flash_set('info', 'Chord ' . $nama . ' ditandai belum bisa.');
        }
    }

    redirect($back);
}

// ---------- Chord yang akan dilatih ----------
$semua  = all_chords();

// Filter daftar: sesuai level user (guest = semua)
if ($level !== '' && isset($semua)) {
    $daftar = array_values(array_filter($semua, fn($c) => $c['difficulty'] === $level));
} else {
    $daftar = array_values($semua);
}

// Mode: latihan untuk satu lagu
$songId = (int) q('song');
$song   = null;
if ($songId > 0) {
    $stmt = db()->prepare('SELECT * FROM songs WHERE id = ?');
    $stmt->execute([$songId]);
    $song = $stmt->fetch() ?: null;
}

if ($song) {
    $stmt = db()->prepare(
        'SELECT c.* FROM chords c
           JOIN song_chords sc ON sc.chord_id = c.id
          WHERE sc.song_id = ? ORDER BY sc.sort_order, c.name'
    );
    $stmt->execute([$songId]);
    $daftar = $stmt->fetchAll();

    // Lengkapi data fingering supaya diagram posisi jari tampil.
    $allById = all_chords();
    foreach ($daftar as &$c) {
        if (isset($allById[$c['id']])) {
            $c = $allById[$c['id']];
        }
    }
    unset($c);

    if (!$daftar) {
        $daftar = array_values($semua);
    }
}

// Chord target
$progress = user_chord_progress();

// Chord yang baru saja dilatih tampil paling atas (aktivitas terbaru)
if ($user && $progress) {
    $daftar = sort_chords_by_recent($daftar, $progress);
}

$targetId = (int) q('chord');
$target   = $targetId > 0 ? get_chord($targetId) : ($daftar[0] ?? null);

if (!$target) {
    layout_head('Latihan', 'practice');
    echo '<div class="gl-card p-5 text-center"><p class="text-dim">Belum ada chord untuk dilatih.</p></div>';
    layout_foot();
    exit;
}

$overview = $user ? chord_overview() : null;
$back     = $song ? url('song-detail.php?id=' . $songId) : url('practice.php');

layout_head('Latihan Chord', 'practice', ['chord_db' => true]);
?>

<div class="mb-3">
  <h1 class="h3 mb-1">Latihan Chord</h1>
  <p class="text-dim mb-0">
    <?php if ($song): ?>
      Mode: latihan untuk lagu <strong><?= e($song['title']) ?></strong>
      (<?= count($daftar) ?> chord).
    <?php else: ?>
      Daftar chord di bawah mengikuti level kamu
      (<?= e(level_label($level)) ?>). Ganti level di halaman Profil.
    <?php endif; ?>
  </p>
</div>

<?php if (!$user): ?>
  <div class="alert alert-warning py-2 small">
    <i class="bi bi-exclamation-triangle me-1"></i>
    Kamu sedang latihan sebagai <strong>tamu</strong>: tombolnya bisa ditekan untuk latihan,
    tetapi <strong>progres tidak akan tersimpan</strong> karena belum login.
    <a href="<?= e(url('login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? ''))) ?>">Login</a>
    atau <a href="<?= e(url('register.php')) ?>">daftar</a> dulu.
  </div>
<?php else: ?>
  <div class="alert alert-info py-2 small">
    <i class="bi bi-cloud-check me-1"></i>
    Progres latihan kamu tersimpan di akun <strong><?= e($user['username']) ?></strong>.
    Saat ini <strong><?= $overview['kuasai'] ?> dari <?= $overview['total'] ?> chord</strong> sudah dikuasai.
    <a href="<?= e(url('progress.php')) ?>">Lihat progres lengkap</a>
  </div>
<?php endif; ?>

<?php if ($user):
    $lvTotal       = level_total_chords($level);
    $lvMastered    = level_mastered_count($level);
    $lvPercent     = level_progress_percent($level);
    $lvChordDone   = ($lvTotal > 0 && $lvMastered >= $lvTotal);
    $lvSongTotal   = level_total_songs($level);
    $lvSongDone    = level_completed_song_count($level);
    $lvSongPercent = level_song_progress_percent($level);
    $stage         = level_stage($level);
    $nx            = next_level($level);
?>
  <div class="gl-card p-3 mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
      <span class="fw-bold"><i class="bi bi-flag text-warning me-1"></i>Level <?= e(level_label($level)) ?></span>
      <span class="small text-dim"><?= $lvMastered ?> / <?= $lvTotal ?> chord dikuasai</span>
    </div>
    <div class="progress progress-thin mb-2">
      <div class="progress-bar bg-<?= $lvChordDone ? 'success' : 'warning' ?>" style="width: <?= $lvPercent ?>%"></div>
    </div>

    <?php if ($lvSongTotal > 0): ?>
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1 mt-3">
        <span class="fw-bold"><i class="bi bi-music-note-list text-warning me-1"></i>Lagu Level <?= e(level_label($level)) ?></span>
        <span class="small text-dim"><?= $lvSongDone ?> / <?= $lvSongTotal ?> lagu selesai</span>
      </div>
      <div class="progress progress-thin">
        <div class="progress-bar bg-<?= $lvSongPercent >= 100 ? 'success' : 'info' ?>" style="width: <?= $lvSongPercent ?>%"></div>
      </div>
    <?php endif; ?>

    <?php if ($stage === 'done' && $nx): ?>
      <div class="alert alert-success d-flex flex-wrap justify-content-between align-items-center gap-2 mb-0 mt-3">
        <span>
          <i class="bi bi-trophy-fill me-1"></i>
          <strong>Level <?= e(level_label($level)) ?> selesai!</strong>
          Chord dan lagu sudah tuntas. Lanjut ke tahap berikutnya.
        </span>
        <form method="post" action="<?= e(url('level.php')) ?>" class="m-0">
          <?= csrf_field() ?>
          <input type="hidden" name="level" value="<?= e($nx) ?>">
          <button type="submit" class="btn btn-sm btn-accent">
            <i class="bi bi-arrow-up-circle me-1"></i>Naik ke <?= e(level_label($nx)) ?>
          </button>
        </form>
      </div>
    <?php elseif ($stage === 'done' && !$nx): ?>
      <p class="small text-dim mb-0 mt-3">
        <i class="bi bi-star-fill text-warning me-1"></i>Ini level tertinggi. Chord dan lagunya sudah kamu tuntaskan!
      </p>
    <?php elseif ($stage === 'songs'): ?>
      <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2 mb-0 mt-3">
        <span>
          <i class="bi bi-music-note-beamed me-1"></i>
          Semua chord level ini sudah dikuasai. Sekarang <strong>belajar lagu</strong>
          dari chord tersebut, lalu tandai <em>Sudah Bisa Lagu Ini</em>.
        </span>
        <a class="btn btn-sm btn-accent" href="<?= e(url('songs.php?level=' . $level)) ?>">
          <i class="bi bi-music-note-list me-1"></i>Belajar Lagu
        </a>
      </div>
    <?php else: ?>
      <p class="small text-dim mb-0 mt-3">
        Kuasai semua chord level ini dulu. Setelah itu kamu lanjut ke tahap
        <strong>belajar lagu</strong> dari chord yang sudah dipelajari.
      </p>
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="row g-4">
  <!-- ============ TARGET LATIHAN ============ -->
  <div class="col-lg-6">
    <div class="practice-target">
      <div class="text-center">
        <div class="small text-dim">LATIHAN CHORD</div>
        <div class="display-4 fw-bold text-warning"><?= e($target['name']) ?></div>
        <div class="d-flex justify-content-center gap-2 mb-3">
          <span class="badge bg-<?= level_color($target['difficulty']) ?>"><?= e(level_label($target['difficulty'])) ?></span>
          <?php if ($target['is_barre']): ?><span class="badge bg-secondary">barre</span><?php endif; ?>
        </div>

        <div class="chord-diagram-lg">
          <?= render_chord_svg($target, ['scale' => 2.1]) ?>
        </div>

        <p class="text-dim small mb-3"><?= e(chord_text_shape($target)) ?></p>
      </div>

      <form method="post" action="<?= e(url('practice.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="chord_id" value="<?= (int) $target['id'] ?>">
        <input type="hidden" name="back" value="<?= e($back) ?>">

        <div class="d-grid gap-2">
          <button type="submit" name="aksi" value="kuasai" class="btn btn-accent">
            <i class="bi bi-check-circle me-1"></i>Sudah Bisa
          </button>
          <button type="submit" name="aksi" value="latih" class="btn btn-accent-outline">
            <i class="bi bi-hourglass-split me-1"></i>Masih Latihan
          </button>
          <button type="submit" name="aksi" value="belum" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Ulangi dari Awal
          </button>
        </div>
      </form>

      <?php $st = $progress[$target['id']] ?? null; ?>
      <?php if ($st): ?>
        <p class="small text-dim text-center mt-3 mb-0">
          Status saat ini:
          <?php if ($st['status'] === 'kuasai'): ?>
            <span class="badge bg-success">Dikuasai</span>
          <?php elseif ($st['status'] === 'latih'): ?>
            <span class="badge bg-warning">Sedang dilatih</span>
          <?php else: ?>
            <span class="badge bg-secondary">Belum bisa</span>
          <?php endif; ?>
          &middot; sudah dilatih <?= (int) $st['practice_count'] ?>x
        </p>
      <?php endif; ?>
    </div>

    <div class="mt-3">
      <?= sound_panel($target) ?>
    </div>
  </div>

  <!-- ============ DAFTAR CHORD ============ -->
  <div class="col-lg-6">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
      <h2 class="h5 mb-0">Pilih Chord</h2>
      <span class="small text-dim"><?= count($daftar) ?> chord</span>
    </div>

    <div class="chord-grid" style="grid-template-columns:repeat(auto-fill,minmax(120px,1fr))">
      <?php foreach ($daftar as $c):
          $cs    = $progress[$c['id']]['status'] ?? 'belum';
          $on    = (int) $c['id'] === (int) $target['id'];
      ?>
        <a class="chord-tile <?= $on ? 'border-warning' : '' ?>"
           style="<?= $on ? 'border:2px solid var(--gl-accent);' : '' ?>"
           href="<?= e(url('practice.php?chord=' . $c['id'] . ($song ? '&song=' . $songId : ''))) ?>">
          <?php if ($cs === 'kuasai'): ?>
            <span class="position-absolute top-0 start-0 m-1 badge bg-success"><i class="bi bi-check-lg"></i></span>
          <?php endif; ?>
          <?= render_chord_svg($c, ['scale' => 0.68, 'show_open' => false]) ?>
          <div class="chord-diagram-name" style="font-size:.9rem"><?= e($c['name']) ?></div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php layout_foot(['chord_db' => true]); ?>
