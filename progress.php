<?php
/**
 * progress.php - Progres Belajar User
 *
 * Halaman ini BISA dibuka oleh guest (untuk menunjukkan bahwa progresnya
 * memang kosong / tidak tersimpan), tetapi hanya user yang punya data di
 * tabel user_chord_progress & user_song_progress.
 */
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/chord_svg.php';
require_once __DIR__ . '/inc/layout.php';

$user = current_user();

// ---------- Hapus semua progres (POST + CSRF) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p('aksi') === 'reset') {
    require_login();
    csrf_check();

    db()->prepare('DELETE FROM user_chord_progress WHERE user_id = ?')->execute([$user['id']]);
    db()->prepare('DELETE FROM user_song_progress  WHERE user_id = ?')->execute([$user['id']]);
    log_activity('reset_progress', 'Menghapus semua progres');

    flash_set('success', 'Semua progres berhasil dihapus. Mulai lagi dari yang paling dasar!');
    redirect('progress.php');
}

// ---------- Data progres ----------
$overview  = $user ? chord_overview() : ['kuasai' => 0, 'latih' => 0, 'total' => 0, 'persen' => 0];
$progress  = user_chord_progress();

// Detail per chord: khusus chord level Beginner
$rowsChord = [];
$beginnerKuasai = 0;
$beginnerLatih  = 0;
foreach (all_chords('beginner') as $c) {
    $st = $progress[$c['id']] ?? null;
    $status = $st['status'] ?? 'belum';
    if ($status === 'kuasai') {
        $beginnerKuasai++;
    } elseif ($status === 'latih') {
        $beginnerLatih++;
    }
    $rowsChord[] = [
        'chord'   => $c,
        'status'  => $status,
        'count'   => $st['practice_count'] ?? 0,
        'last'    => $st['last_practiced_at'] ?? null,
    ];
}

// Chord yang terakhir dilatih tampil paling atas
usort($rowsChord, function ($a, $b) {
    $ta = $a['last'];
    $tb = $b['last'];
    if ($ta && $tb && $ta !== $tb) {
        return strcmp($tb, $ta);
    }
    if ($ta && !$tb) {
        return -1;
    }
    if ($tb && !$ta) {
        return 1;
    }
    return strcmp($a['chord']['name'], $b['chord']['name']);
});

// Progres per lagu
$songRows = [];
if ($user) {
    $stmt = db()->prepare(
        'SELECT usp.*, s.title, s.artist, s.level
           FROM user_song_progress usp
           JOIN songs s ON s.id = usp.song_id
          WHERE usp.user_id = ?
          ORDER BY usp.percent DESC, usp.updated_at DESC'
    );
    $stmt->execute([$user['id']]);
    $songRows = $stmt->fetchAll();
}

// Chord yang paling sering dilatih
$topChords = [];
if ($user) {
    $topChords = db()->prepare(
        'SELECT c.name, ucp.practice_count, ucp.status
           FROM user_chord_progress ucp
           JOIN chords c ON c.id = ucp.chord_id
          WHERE ucp.user_id = ? AND ucp.practice_count > 0
          ORDER BY ucp.practice_count DESC LIMIT 8'
    );
    $topChords->execute([$user['id']]);
    $topChords = $topChords->fetchAll();
}

layout_head('Progres Saya', 'progress', ['chord_db' => true]);
?>

<div class="mb-3">
  <h1 class="h3 mb-1">Progres Saya</h1>
  <p class="text-dim mb-0">
    <?php if ($user): ?>
      Ringkasan chord yang sudah dikuasai dan lagu yang sedang dipelajari.
    <?php else: ?>
      Halaman ini bisa kamu buka sebagai tamu, tapi datanya akan selalu kosong -
      progres baru tersimpan setelah kamu login.
    <?php endif; ?>
  </p>
</div>

<?php if (!$user): ?>
  <div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-1"></i>
    Kamu belum login, jadi <strong>progres tidak tersimpan</strong>.
    Latihan yang kamu lakukan sebagai tamu akan hilang saat halaman ditutup.
    <a href="<?= e(url('login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? ''))) ?>">Login</a>
    atau <a href="<?= e(url('register.php')) ?>">daftar</a> untuk mulai menyimpan progres.
  </div>
<?php endif; ?>

<!-- ============ KARTU RINGKASAN ============ -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="stat-box">
      <div class="stat-label">Chord dikuasai</div>
      <div class="stat-value"><?= $overview['kuasai'] ?></div>
      <div class="small text-dim">dari <?= $overview['total'] ?> chord</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-box">
      <div class="stat-label">Sedang dilatih</div>
      <div class="stat-value text-warning"><?= $overview['latih'] ?></div>
      <div class="small text-dim">sudah pernah dicoba</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-box">
      <div class="stat-label">Progres chord</div>
      <div class="stat-value <?= $overview['persen'] >= 50 ? 'text-success' : '' ?>"><?= $overview['persen'] ?>%</div>
      <div class="progress progress-thin mt-1">
        <div class="progress-bar bg-<?= $overview['persen'] >= 100 ? 'success' : 'warning' ?>"
             style="width: <?= $overview['persen'] ?>%"></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-box">
      <div class="stat-label">Lagu dipelajari</div>
      <div class="stat-value"><?= count($songRows) ?></div>
      <div class="small text-dim"><?= count($songRows) > 0 ? 'lihat di bawah' : 'belum ada' ?></div>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- ============ PROGRESS BAR CHORD ============ -->
  <div class="col-lg-8">
    <div class="gl-card p-4 mb-4">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
          <h2 class="h5 mb-0">Status Semua Chord</h2>
          <span class="small text-dim">Khusus chord level Beginner</span>
        </div>
        <span class="small text-dim">
          <span class="badge bg-success"><?= $beginnerKuasai ?> dikuasai</span>
          <span class="badge bg-warning"><?= $beginnerLatih ?> dilatih</span>
        </span>
      </div>

      <div class="table-responsive">
        <table class="table table-dark-gl table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>Chord</th>
              <th>Kesulitan</th>
              <th>Status</th>
              <th class="text-center">Diblatih</th>
              <th>Terakhir</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rowsChord as $r): ?>
              <tr>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <span class="chord-chip" data-chord="<?= e($r['chord']['name']) ?>" style="cursor:pointer">
                      <?= e($r['chord']['name']) ?>
                    </span>
                  </div>
                </td>
                <td><span class="badge bg-<?= level_color($r['chord']['difficulty']) ?>"><?= e(level_label($r['chord']['difficulty'])) ?></span></td>
                <td>
                  <?php if ($r['status'] === 'kuasai'): ?>
                    <span class="badge bg-success"><i class="bi bi-check-lg"></i> Dikuasai</span>
                  <?php elseif ($r['status'] === 'latih'): ?>
                    <span class="badge bg-warning">Dilatih</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Belum</span>
                  <?php endif; ?>
                </td>
                <td class="text-center"><?= (int) $r['count'] ?>?x</td>
                <td class="small text-dim"><?= $r['count'] > 0 ? e(format_date($r['last'])) : '-' ?></td>
                <td class="text-end">
                  <a class="btn btn-sm btn-accent-outline"
                     href="<?= e(url('practice.php?chord=' . $r['chord']['id'])) ?>">Latih</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ============ SISI KANAN ============ -->
  <div class="col-lg-4">
    <!-- Progres lagu -->
    <div class="gl-card p-4 mb-4">
      <h2 class="h5 mb-3">Progres Lagu</h2>

      <?php if (!$songRows): ?>
        <p class="text-dim small mb-3">
          Belum ada progres lagu.
          Buka sebuah lagu lalu tekan <em>Sudah Bisa</em> pada chordnya.
        </p>
        <a href="<?= e(url('songs.php')) ?>" class="btn btn-accent btn-sm w-100">Cari Lagu</a>
      <?php else: ?>
        <?php foreach ($songRows as $s): ?>
          <div class="py-2 border-bottom" style="border-color:var(--gl-border)!important">
            <div class="d-flex justify-content-between align-items-start gap-2">
              <a class="song-row-link fw-bold" href="<?= e(url('song-detail.php?id=' . $s['song_id'])) ?>">
                <?= e($s['title']) ?>
              </a>
              <span class="d-flex align-items-center gap-1">
                <?php if (!empty($s['is_completed'])): ?>
                  <span class="badge bg-success"><i class="bi bi-check-lg"></i> Selesai</span>
                <?php endif; ?>
                <?= percent_badge((int) $s['percent']) ?>
              </span>
            </div>
            <div class="small text-dim mb-1">
              <?= (int) $s['mastered_chords'] ?> / <?= (int) $s['total_chords'] ?> chord dikuasai
              &middot; <?= e(format_date($s['updated_at'])) ?>
            </div>
            <div class="progress progress-thin">
              <div class="progress-bar bg-<?= (int) $s['percent'] >= 100 ? 'success' : 'warning' ?>"
                   style="width: <?= (int) $s['percent'] ?>%"></div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Chord yang paling sering dilatih -->
    <?php if ($topChords): ?>
      <div class="gl-card p-4 mb-4">
        <h2 class="h6 mb-3"><i class="bi bi-fire me-1"></i>Paling Sering Dilatih</h2>
        <?php foreach ($topChords as $t): ?>
          <div class="d-flex justify-content-between align-items-center py-1">
            <span class="chord-chip" data-chord="<?= e($t['name']) ?>" style="cursor:pointer"><?= e($t['name']) ?></span>
            <span class="small text-dim"><?= (int) $t['practice_count'] ?>x latihan</span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Aksi -->
    <div class="gl-card p-4">
      <h2 class="h6 mb-3"><i class="bi bi-lightning-charge me-1"></i>Aksi</h2>
      <a href="<?= e(url('practice.php')) ?>" class="btn btn-accent w-100 mb-2">
        <i class="bi bi-play-circle me-1"></i>Mulai Latihan
      </a>
      <a href="<?= e(url('songs.php?level=' . ($user['level'] ?? ''))) ?>" class="btn btn-accent-outline w-100 mb-2">
        <i class="bi bi-music-note-list me-1"></i>Belajar Lagu
      </a>
      <?php if ($user && ($overview['kuasai'] > 0 || $songRows)): ?>
        <form method="post" action="<?= e(url('progress.php')) ?>"
              onsubmit="return confirm('Hapus SEMUA progres chord dan lagu kamu? Tindakan ini tidak bisa dibatalkan.')">
          <?= csrf_field() ?>
          <input type="hidden" name="aksi" value="reset">
          <button type="submit" class="btn btn-outline-danger w-100">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Progres
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php layout_foot(['chord_db' => true]); ?>