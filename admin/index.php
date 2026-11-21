<?php
/**
 * admin/index.php - Dashboard & Statistik
 * Hanya bisa diakses admin (lihat require_admin()).
 *
 * Menampilkan angka ringkasan: jumlah chord, lagu, user, progres,
 * distribusi level, aktivitas terbaru, dan riwayat percobaan login.
 */
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/chord_svg.php';
require_once __DIR__ . '/../inc/layout.php';

require_admin();

$pdo = db();

// ---------- Angka ringkasan ----------
$totalChord = (int) $pdo->query('SELECT COUNT(*) FROM chords')->fetchColumn();
$totalSong  = (int) $pdo->query('SELECT COUNT(*) FROM songs')->fetchColumn();
$totalUser  = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalAdmin = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$totalAktif = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE is_active = 1')->fetchColumn();

$mastered = (int) $pdo->query(
    "SELECT COUNT(*) FROM user_chord_progress WHERE status = 'kuasai'"
)->fetchColumn();
$latih = (int) $pdo->query(
    "SELECT COUNT(*) FROM user_chord_progress WHERE status = 'latih'"
)->fetchColumn();

// ---------- Distribusi per level / kesulitan ----------
function count_by($pdo, $table, $column)
{
    $rows = $pdo->query("SELECT $column AS k, COUNT(*) AS n FROM $table GROUP BY $column")->fetchAll();
    $out = ['beginner' => 0, 'intermediate' => 0, 'pro' => 0];
    foreach ($rows as $r) {
        if ($r['k'] !== null) {
            $out[$r['k']] = (int) $r['n'];
        }
    }
    return $out;
}

$chordByDiff = count_by($pdo, 'chords', 'difficulty');
$songByLevel = count_by($pdo, 'songs', 'level');

// Distribusi level belajar user
$userLevel = ['Belum pilih' => 0, 'beginner' => 0, 'intermediate' => 0, 'pro' => 0];
foreach ($pdo->query(
    "SELECT COALESCE(level, 'Belum pilih') AS k, COUNT(*) AS n
       FROM users WHERE role = 'user' GROUP BY k"
)->fetchAll() as $r) {
    $userLevel[$r['k']] = (int) $r['n'];
}

// ---------- Aktivitas terbaru ----------
$recentActivity = $pdo->query(
    'SELECT a.action, a.detail, a.created_at, u.username
       FROM activity_log a
       LEFT JOIN users u ON u.id = a.user_id
      ORDER BY a.created_at DESC, a.id DESC
      LIMIT 8'
)->fetchAll();

// ---------- Percobaan login terbaru ----------
$recentLogin = $pdo->query(
    'SELECT username, ip, success, created_at
       FROM login_attempt
      ORDER BY created_at DESC, id DESC
      LIMIT 6'
)->fetchAll();

// ---------- User paling rajin (chord dikuasai terbanyak) ----------
$topUsers = $pdo->query(
    "SELECT u.username, u.level, COUNT(*) AS dikuasai
       FROM user_chord_progress ucp
       JOIN users u ON u.id = ucp.user_id
      WHERE ucp.status = 'kuasai'
      GROUP BY u.id
      ORDER BY dikuasai DESC
      LIMIT 5"
)->fetchAll();

layout_head('Dashboard Admin', 'admin', ['wide' => true]);
?>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
  <div>
    <h1 class="h3 mb-1"><i class="bi bi-speedometer2 me-2 text-warning"></i>Dashboard Admin</h1>
    <p class="text-dim mb-0">Ringkasan isi website GuitarTice.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= e(url('admin/chords.php')) ?>" class="btn btn-sm btn-accent">
      <i class="bi bi-diagram-3 me-1"></i>Kelola Chord
    </a>
    <a href="<?= e(url('admin/songs.php')) ?>" class="btn btn-sm btn-accent-outline">
      <i class="bi bi-music-note-list me-1"></i>Kelola Lagu
    </a>
  </div>
</div>

<!-- ============ ANGKA RINGKASAN ============ -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="stat-box h-100">
      <div class="stat-label">Total Chord</div>
      <div class="stat-value"><?= $totalChord ?></div>
      <a href="<?= e(url('admin/chords.php')) ?>" class="small text-warning text-decoration-none">kelola &rarr;</a>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-box h-100">
      <div class="stat-label">Total Lagu</div>
      <div class="stat-value"><?= $totalSong ?></div>
      <a href="<?= e(url('admin/songs.php')) ?>" class="small text-warning text-decoration-none">kelola &rarr;</a>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-box h-100">
      <div class="stat-label">User Terdaftar</div>
      <div class="stat-value"><?= $totalUser ?></div>
      <a href="<?= e(url('admin/users.php')) ?>" class="small text-warning text-decoration-none">kelola &rarr;</a>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-box h-100">
      <div class="stat-label">Chord Dikuasai</div>
      <div class="stat-value"><?= $mastered ?></div>
      <span class="small text-dim"><?= $latih ?> sedang dilatih</span>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <!-- ============ DISTRIBUSI ============ -->
  <div class="col-lg-6">
    <div class="gl-card p-4 h-100">
      <h2 class="h6 mb-3"><i class="bi bi-bar-chart me-2 text-warning"></i>Chord per Tingkat Kesulitan</h2>
      <?php foreach (level_options() as $lv):
          $n = (int) $chordByDiff[$lv];
          $p = $totalChord > 0 ? round($n / $totalChord * 100) : 0;
      ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between small mb-1">
            <span><?= e(level_label($lv)) ?></span>
            <span class="text-dim"><?= $n ?> chord</span>
          </div>
          <div class="progress progress-thin">
            <div class="progress-bar bg-<?= level_color($lv) ?>" style="width: <?= $p ?>%"></div>
          </div>
        </div>
      <?php endforeach; ?>

      <hr style="border-color:var(--gl-border)">

      <h2 class="h6 mb-3"><i class="bi bi-music-note me-2 text-warning"></i>Lagu per Level</h2>
      <?php foreach (level_options() as $lv):
          $n = (int) $songByLevel[$lv];
          $p = $totalSong > 0 ? round($n / $totalSong * 100) : 0;
      ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between small mb-1">
            <span><?= e(level_label($lv)) ?></span>
            <span class="text-dim"><?= $n ?> lagu</span>
          </div>
          <div class="progress progress-thin">
            <div class="progress-bar bg-<?= level_color($lv) ?>" style="width: <?= $p ?>%"></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ============ LEVEL USER & TOP USER ============ -->
  <div class="col-lg-6">
    <div class="gl-card p-4 h-100">
      <h2 class="h6 mb-3"><i class="bi bi-people me-2 text-warning"></i>Level Belajar User
        <span class="text-dim small">(<?= $totalUser ?> user)</span>
      </h2>
      <table class="table table-sm table-dark-gl align-middle mb-4">
        <tbody>
          <?php foreach ($userLevel as $label => $n): ?>
            <tr>
              <td><?= $label === 'Belum pilih' ? e($label) : e(level_label($label)) ?></td>
              <td class="text-end fw-bold"><?= (int) $n ?></td>
            </tr>
          <?php endforeach; ?>
          <tr class="border-top" style="border-color:var(--gl-border)!important">
            <td class="text-dim">Aktif login</td>
            <td class="text-end fw-bold"><?= $totalAktif ?> / <?= $totalUser + $totalAdmin ?></td>
          </tr>
        </tbody>
      </table>

      <h2 class="h6 mb-3"><i class="bi bi-trophy me-2 text-warning"></i>User Paling Rajin</h2>
      <?php if ($topUsers): ?>
        <table class="table table-sm table-dark-gl align-middle mb-0">
          <thead>
            <tr><th>User</th><th>Level</th><th class="text-end">Chord dikuasai</th></tr>
          </thead>
          <tbody>
            <?php foreach ($topUsers as $t): ?>
              <tr>
                <td><?= e($t['username']) ?></td>
                <td><?= level_badge($t['level']) ?></td>
                <td class="text-end fw-bold"><?= (int) $t['dikuasai'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="text-dim small mb-0">Belum ada user yang menguasai chord.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- ============ AKTIVITAS TERBARU ============ -->
  <div class="col-lg-7">
    <div class="gl-card p-4 h-100">
      <h2 class="h6 mb-3"><i class="bi bi-activity me-2 text-warning"></i>Aktivitas Terbaru</h2>
      <?php if ($recentActivity): ?>
        <div class="table-responsive">
          <table class="table table-sm table-dark-gl align-middle mb-0">
            <thead>
              <tr><th>Waktu</th><th>User</th><th>Aksi</th></tr>
            </thead>
            <tbody>
              <?php foreach ($recentActivity as $a): ?>
                <tr>
                  <td class="text-dim small text-nowrap"><?= e(format_date($a['created_at'])) ?></td>
                  <td><?= $a['username'] ? e($a['username']) : '<span class="text-dim">-</span>' ?></td>
                  <td class="small"><?= e($a['detail'] ?: $a['action']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-dim small mb-0">Belum ada aktivitas tercatat.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- ============ RIWAYAT LOGIN ============ -->
  <div class="col-lg-5">
    <div class="gl-card p-4 h-100">
      <h2 class="h6 mb-3"><i class="bi bi-shield-lock me-2 text-warning"></i>Percobaan Login Terbaru</h2>
      <?php if ($recentLogin): ?>
        <table class="table table-sm table-dark-gl align-middle mb-0">
          <thead>
            <tr><th>Username</th><th>IP</th><th class="text-end">Status</th></tr>
          </thead>
          <tbody>
            <?php foreach ($recentLogin as $l): ?>
              <tr>
                <td class="small"><?= e($l['username']) ?></td>
                <td class="small text-dim"><?= e($l['ip']) ?></td>
                <td class="text-end">
                  <?php if ((int) $l['success'] === 1): ?>
                    <span class="badge bg-success">Berhasil</span>
                  <?php else: ?>
                    <span class="badge bg-danger">Gagal</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="text-dim small mb-0">Belum ada percobaan login.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php layout_foot(); ?>
