<?php
/**
 * songs.php - Daftar Lagu
 * Dapat diakses semua orang.
 *
 * Kategori lagu: beginner / intermediate / pro
 * User bebas memilih kategori mana pun, tidak terkunci pada level-nya.
 * Kalau user sudah memilih level, tab level tersebut yang di buka dulu.
 */
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/chord_svg.php';
require_once __DIR__ . '/inc/layout.php';

$user  = current_user();
$myLv  = $user['level'] ?? null;

// Tab aktif: dari URL, kalau tidak ada default "semua"
$active = q('level');
if (!in_array($active, level_options(), true) && $active !== 'semua') {
    $active = 'semua';
}
$search = q('q');

$sql = 'SELECT s.*,
               (SELECT COUNT(*) FROM song_chords sc WHERE sc.song_id = s.id) AS jumlah_chord
          FROM songs s WHERE 1 = 1';
$params = [];

if ($active !== '' && $active !== 'semua') {
    $sql .= ' AND s.level = ?';
    $params[] = $active;
}
if ($search !== '') {
    $sql .= ' AND (s.title LIKE ? OR s.artist LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
$sql .= ' ORDER BY s.title';

$st = db()->prepare($sql);
$st->execute($params);
$songs = $st->fetchAll();

// Jumlah lagu per kategori untuk ditampilkan di tab
$songCount = ['semua' => (int) db()->query('SELECT COUNT(*) FROM songs')->fetchColumn()];
foreach (db()->query('SELECT level, COUNT(*) n FROM songs GROUP BY level')->fetchAll() as $r) {
    $songCount[$r['level']] = (int) $r['n'];
}

// Progres lagu milik user
$myProgress = [];
if ($user) {
    foreach (db()->query(
        'SELECT song_id, percent, status, is_completed FROM user_song_progress WHERE user_id = '
        . (int) $user['id']
    )->fetchAll() as $r) {
        $myProgress[(int) $r['song_id']] = $r;
    }
}

// Nama chord tiap lagu untuk chip pratinjau
$chordNameById = [];
foreach (all_chords() as $c) {
    $chordNameById[$c['id']] = $c['name'];
}

// Status chord milik user (kosong untuk guest)
$progress = user_chord_progress();

$songChords = [];
if ($songs) {
    $ids = array_column($songs, 'id');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $st2 = db()->prepare("SELECT song_id, chord_id FROM song_chords WHERE song_id IN ($in) ORDER BY sort_order, id");
    $st2->execute($ids);
    foreach ($st2->fetchAll() as $r) {
        $songChords[(int) $r['song_id']][] = (int) $r['chord_id'];
    }
}

layout_head('Daftar Lagu', 'songs', ['chord_db' => true]);
?>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
  <div>
    <h1 class="h3 mb-1">Daftar Lagu</h1>
      <p class="text-dim mb-0">
      Lirik + chord + Strumming, dikelompokkan per tingkat kesulitan.
      <?php if ($myLv): ?>
        Tab <strong>Semua</strong> dibuka otomatis; kamu bebas membuka kategori lain kapan saja.
      <?php endif; ?>
    </p>
  </div>

  <form class="d-flex gap-2" method="get" action="<?= e(url('songs.php')) ?>">
    <input type="hidden" name="level" value="<?= e($active) ?>">
    <input type="search" name="q" class="form-control form-control-sm" placeholder="Cari judul / artis"
           value="<?= e($search) ?>" style="min-width:190px">
    <button class="btn btn-sm btn-accent" type="submit">Cari</button>
  </form>
</div>

<!-- Tab kategori -->
<div class="level-tabs mb-4">
  <a class="level-tab <?= $active === 'semua' || $active === '' ? 'active' : '' ?>"
     href="<?= e(url('songs.php?level=semua' . ($search ? '&q=' . urlencode($search) : ''))) ?>">
    Semua <span class="text-dim">(<?= $songCount['semua'] ?>)</span>
  </a>
  <?php foreach (level_options() as $lv): ?>
    <a class="level-tab <?= $active === $lv ? 'active' : '' ?>"
       href="<?= e(url('songs.php?level=' . $lv . ($search ? '&q=' . urlencode($search) : ''))) ?>">
      <?= e(level_label($lv)) ?> <span class="text-dim">(<?= (int) ($songCount[$lv] ?? 0) ?>)</span>
      <?php if ($myLv === $lv): ?><i class="bi bi-check-circle-fill ms-1" title="Level kamu"></i><?php endif; ?>
    </a>
  <?php endforeach; ?>
</div>

<?php if (!$songs): ?>
  <div class="gl-card p-5 text-center">
    <i class="bi bi-music-note-list fs-1 text-dim"></i>
    <p class="text-dim mt-2">
      Belum ada lagu di kategori ini.
      <?= $search ? 'Coba kata kunci lain.' : 'Admin bisa menambahkannya lewat menu Admin &rarr; Kelola Lagu.' ?>
    </p>
  </div>
<?php else: ?>

  <div class="row g-3">
    <?php foreach ($songs as $s):
        $prog = $myProgress[(int) $s['id']] ?? null;
        $chordIds = $songChords[(int) $s['id']] ?? [];
    ?>
      <div class="col-md-6 col-lg-4">
        <div class="gl-card h-100 p-3 d-flex flex-column">

          <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
            <div>
              <h2 class="h6 mb-0"><?= e($s['title']) ?></h2>
              <?php if ($s['artist']): ?>
                <div class="small text-dim"><?= e($s['artist']) ?></div>
              <?php endif; ?>
            </div>
            <span class="badge bg-<?= level_color($s['level']) ?>"><?= e(level_label($s['level'])) ?></span>
          </div>

          <div class="mb-2">
            <?php foreach ($chordIds as $cid):
                if (!isset($chordNameById[$cid])) { continue; }
                $cStatus = $progress[$cid]['status'] ?? 'belum';
            ?>
              <span class="chord-chip <?= $cStatus === 'kuasai' ? 'is-mastered' : '' ?>"
                    data-chord="<?= e($chordNameById[$cid]) ?>"
                    aria-label="Lihat posisi jari chord <?= e($chordNameById[$cid]) ?>"><?= e($chordNameById[$cid]) ?></span>
            <?php endforeach; ?>
            <?php if (!$chordIds): ?>
              <span class="text-dim small">Belum ada chord terdaftar</span>
            <?php endif; ?>
          </div>

          <div class="small text-dim mb-3">
            <?php if ($s['tempo']): ?><i class="bi bi-speedometer me-1"></i><?= e($s['tempo']) ?>&nbsp;&nbsp;<?php endif; ?>
            <?php if ($s['capo']): ?><i class="bi bi-scissors me-1"></i>Capo fret <?= e($s['capo']) ?>&nbsp;&nbsp;<?php endif; ?>
            <i class="bi bi-diagram-3 me-1"></i><?= count($chordIds) ?> chord
            <?php if (!empty($s['audio_file'])): ?>
              <span class="badge bg-info ms-1"><i class="bi bi-volume-up me-1"></i>Lagu asli</span>
            <?php endif; ?>
          </div>

          <?php if ($prog): ?>
            <div class="mt-auto">
              <div class="d-flex justify-content-between small text-dim mb-1">
                <span>
                  Progres belajar
                  <?php if (!empty($prog['is_completed'])): ?>
                    <span class="badge bg-success ms-1"><i class="bi bi-check-lg"></i> Selesai</span>
                  <?php endif; ?>
                </span>
                <?= percent_badge((int) $prog['percent']) ?>
              </div>
              <div class="progress progress-thin">
                <div class="progress-bar bg-<?= (int) $prog['percent'] >= 100 ? 'success' : 'warning' ?>"
                     style="width: <?= (int) $prog['percent'] ?>%"></div>
              </div>
            </div>
          <?php endif; ?>

          <a class="btn btn-sm <?= $prog ? 'btn-accent-outline' : 'btn-accent' ?> mt-3"
             href="<?= e(url('song-detail.php?id=' . $s['id'])) ?>">
            <i class="bi bi-play-fill me-1"></i>Buka Lagu &amp; Lirik
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <p class="text-dim small mt-4">
    <i class="bi bi-hand-index-thumb me-1"></i>
    Chord pada kartu lagu bisa diklik untuk melihat gambar posisi jari,
    lalu buka lagunya untuk membaca lirik lengkapnya.
  </p>
<?php endif; ?>

<?php layout_foot(['chord_db' => true]); ?>