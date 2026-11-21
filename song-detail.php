<?php
/**
 * song-detail.php - Detail Lagu: lirik + chord yang bisa diklik
 *
 * Fitur utama halaman ini:
 *   - Setiap chord di dalam lirik adalah tombol. Diklik -> muncul modal
 *     berisi gambar posisi jari (SVG) dari chord tersebut.
 *   - Chord yang sudah dikuasai ditandai hijau (khusus user yang login).
 *   - User bisa menandai chord "sudah bisa", progres lagu dihitung ulang
 *     dari chord unik di lagu ini yang sudah dikuasai.
 *   - Guest tetap bisa membaca & melakukan latihan, tetapi progres TIDAK tersimpan.
 */
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/chord_svg.php';
require_once __DIR__ . '/inc/layout.php';

$user = current_user();
$song = null;
$id   = (int) q('id');

if ($id > 0) {
    $st = db()->prepare('SELECT * FROM songs WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $song = $st->fetch() ?: null;
}

if (!$song) {
    http_response_code(404);
    layout_head('Lagu tidak ditemukan', 'songs', ['chord_db' => true]);
    echo '<div class="gl-card p-5 text-center">'
       . '<i class="bi bi-exclamation-triangle fs-1 text-warning"></i>'
       . '<h1 class="h5 mt-3">Lagu tidak ditemukan</h1>'
       . '<a href="' . e(url('songs.php')) . '" class="btn btn-accent mt-2">Kembali ke Daftar Lagu</a>'
       . '</div>';
    layout_foot(['chord_db' => true]);
    exit;
}

// ---------- Tandai lagu sudah bisa / batalkan ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p('aksi') === 'song_complete') {
    require_login();
    csrf_check();

    $done = p('done', '1') === '1';

    if ($done) {
        $cek = song_progress($song['id']);
        if (!level_is_unlocked($song['level'])) {
            $blocker = level_blocker($song['level']);
            flash_set('warning', 'Level ' . level_label($song['level']) . ' masih terkunci. Selesaikan level '
                . level_label($blocker) . ' dulu.');
        } elseif ($cek['total'] <= 0 || $cek['mastered'] < $cek['total']) {
            flash_set('warning', 'Semua chord lagu ini harus dikuasai dulu sebelum lagu ditandai selesai.');
        } elseif (set_song_completed($song['id'], true)) {
            log_activity('lagu_selesai', 'Lagu ' . $song['title'] . ' ditandai sudah bisa');
            flash_set('success', 'Mantap! Lagu "' . $song['title'] . '" ditandai sudah bisa dimainkan.');
        }
    } else {
        set_song_completed($song['id'], false);
        log_activity('lagu_batal_selesai', 'Tanda selesai lagu ' . $song['title'] . ' dibatalkan');
        flash_set('info', 'Tanda selesai lagu "' . $song['title'] . '" dibatalkan.');
    }

    redirect('song-detail.php?id=' . (int) $song['id']);
}

// Chord yang dipakai lagu ini
$st = db()->prepare(
    'SELECT c.* FROM chords c
       JOIN song_chords sc ON sc.chord_id = c.id
      WHERE sc.song_id = ?
      ORDER BY sc.sort_order, c.name'
);
$st->execute([$song['id']]);
$songChords = $st->fetchAll();

// Lengkapi data fingering supaya diagram posisi jari benar-benar tampil.
$allChords = all_chords();
foreach ($songChords as &$c) {
    if (isset($allChords[$c['id']])) {
        $c = $allChords[$c['id']];
    }
}
unset($c);

// Chord unik yang muncul di teks lirik (untuk statistik akurasi progres)
$progress = song_progress($song['id']);
$chordStatus = user_chord_progress();
$allMastered = $progress['total'] > 0 && $progress['mastered'] >= $progress['total'];
$songLocked  = $user && !level_is_unlocked($song['level']);
$songBlocker = $songLocked ? level_blocker($song['level']) : null;

// Chord yang sudah dikuasai -> chip lirik diberi warna hijau
$masteredNames = [];
foreach ($songChords as $c) {
    if (($chordStatus[$c['id']]['status'] ?? '') === 'kuasai') {
        $masteredNames[] = $c['name'];
    }
}

layout_head($song['title'], 'songs', ['chord_db' => true]);
?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Beranda</a></li>
    <li class="breadcrumb-item"><a href="<?= e(url('songs.php?level=' . $song['level'])) ?>"><?= e(level_label($song['level'])) ?></a></li>
    <li class="breadcrumb-item active" aria-current="page"><?= e($song['title']) ?></li>
  </ol>
</nav>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
  <div>
    <h1 class="h3 mb-1"><?= e($song['title']) ?></h1>
    <p class="text-dim mb-0">
      <?php if ($song['artist']): ?><?= e($song['artist']) ?><?php endif; ?>
      <?= level_badge($song['level']) ?>
      <?php if ($song['tempo']): ?>
        <span class="ms-2 small"><i class="bi bi-speedometer me-1"></i><?= e($song['tempo']) ?></span>
      <?php endif; ?>
      <?php if ($song['capo']): ?>
        <span class="ms-2 small"><i class="bi bi-scissors me-1"></i>Capo fret <?= e($song['capo']) ?></span>
      <?php endif; ?>
      <?php if ($progress['completed']): ?>
        <span class="badge bg-success ms-2"><i class="bi bi-check-lg me-1"></i>Lagu sudah bisa</span>
      <?php endif; ?>
    </p>
  </div>
  <a class="btn btn-sm btn-accent-outline" href="<?= e(url('songs.php?level=' . $song['level'])) ?>">
    <i class="bi bi-arrow-left me-1"></i>Lagu <?= e(level_label($song['level'])) ?> lain
  </a>
</div>

<?php if ($song['notes']): ?>
  <div class="alert alert-info py-2 small">
    <i class="bi bi-info-circle me-1"></i><strong>Catatan:</strong> <?= nl2br(e($song['notes'])) ?>
  </div>
<?php endif; ?>

<?php if (!empty($song['audio_file'])): ?>
  <!-- ================= LAGU ASLI ================= -->
  <div class="gl-card p-3 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
      <h2 class="h6 mb-0">
        <i class="bi bi-music-note-beamed me-1 text-warning"></i>Dengarkan Lagu Asli
      </h2>
      <span class="small text-dim"><?= e($song['title']) ?><?= $song['artist'] ? ' - ' . e($song['artist']) : '' ?></span>
    </div>
    <audio controls preload="metadata" class="w-100"
           src="<?= e(song_audio_url($song['audio_file'])) ?>">
      Browser kamu tidak mendukung pemutar audio.
    </audio>
    <p class="small text-dim mb-0 mt-2">
      <i class="bi bi-info-circle me-1"></i>
      Dengarkan lagu aslinya dulu supaya kamu tahu irama dan feel lagu sebelum memainkannya.
    </p>
  </div>
<?php endif; ?>

<!-- ================= PROGRES ================= -->
<div class="gl-card p-3 mb-4">
  <div class="row align-items-center g-3">
    <div class="col-md-5">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="small text-dim">Progres belajar lagu ini</span>
        <span id="songProgressBadge" class="badge bg-secondary"><?= (int) $progress['percent'] ?>%</span>
      </div>
      <div class="progress progress-thin">
        <div id="songProgressBar" class="progress-bar bg-warning" style="width: <?= (int) $progress['percent'] ?>%"></div>
      </div>
      <div id="songProgressText" class="small text-dim mt-1">
        <?= $progress['mastered'] ?> / <?= $progress['total'] ?> chord sudah dikuasai
      </div>
    </div>

    <div class="col-md-7">
      <?php if (!$user): ?>
        <div class="small text-dim">
          <i class="bi bi-exclamation-triangle me-1 text-warning"></i>
          Kamu sedang membaca sebagai <strong>tamu</strong>. Chord tetap bisa diklik untuk
          melihat posisi jarinya, dan kamu bisa latihan — tetapi
          <strong>progres tidak tersimpan</strong> karena belum login.
          <a href="<?= e(url('login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? ''))) ?>">Login</a>
          atau <a href="<?= e(url('register.php')) ?>">daftar</a> untuk menyimpan progres.
        </div>
      <?php else: ?>
        <div class="small text-dim">
          Tandai chord yang sudah bisa dikuasai dengan menekan tombolnya di daftar
          di bawah. Arahkan atau geser (drag) kursor di atas chord pada lirik untuk
          melihat ilustrasi posisi jarinya.
          Progres dihitung dari chord unik yang sudah dikuasai
          (<code><?= $progress['mastered'] ?>/<?= $progress['total'] ?></code>).
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- ================= LIRIK ================= -->
  <div class="col-lg-7">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h2 class="h5 mb-0"><i class="bi bi-music-note-beamed me-1"></i>Lirik + Chord + Strumming</h2>
      <span class="small text-dim">Chord di atas lirik; arahkan/geser kursor di atas chord untuk melihat ilustrasi diagramnya, dan pola strumming up(U) dan down(D)</span>
    </div>
    <div class="lyric-sheet">
      <?= render_lyrics($song['lyrics'], chord_names_list(), $masteredNames) ?>
    </div>
  </div>

  <!-- ================= DAFTAR CHORD ================= -->
  <div class="col-lg-5">
    <h2 class="h5 mb-2">Chord yang Dipakai</h2>
    <p class="text-dim small">
      Chord di bawah ini yang dipakai dalam lagu &quot;<?= e($song['title']) ?>&quot;.
      Tekan <em>Sudah Bisa</em> untuk menyimpan progres.
    </p>

    <?php if (!$songChords): ?>
      <div class="gl-card p-4 text-center text-dim">
        Belum ada chord yang terhubung ke lagu ini.
      </div>
    <?php else: ?>
      <div class="gl-card p-3">
        <?php foreach ($songChords as $c):
            $cStatus  = $chordStatus[$c['id']]['status'] ?? 'belum';
            $isMaster = $cStatus === 'kuasai';
            $btnLabel = $isMaster ? 'Tandai Belum Bisa' : 'Sudah Bisa';
            $btnClass = $isMaster ? 'btn-success' : 'btn-accent-outline';
        ?>
          <div class="d-flex align-items-center gap-3 py-2 border-bottom" style="border-color:var(--gl-border)!important">
            <div class="chord-diagram" style="width:78px">
              <?= render_chord_svg($c, ['scale' => 0.62, 'show_open' => false]) ?>
              <div class="chord-diagram-name" style="font-size:.85rem"><?= e($c['name']) ?></div>
            </div>

            <div class="flex-grow-1">
              <button class="chord-chip border-0 mb-1" data-chord="<?= e($c['name']) ?>"
                      aria-label="Lihat posisi jari chord <?= e($c['name']) ?>"><?= e($c['name']) ?></button>
              <div class="small text-dim"><?= e(chord_text_shape($c)) ?></div>
            </div>

            <div class="text-end">
              <button class="btn btn-sm <?= $btnClass ?>"
                      data-master-chord="<?= (int) $c['id'] ?>"
                      data-status="<?= e($cStatus) ?>"
                      data-label-on="<?= e($btnLabel) ?>"
                      data-scope="song"
                      data-song-id="<?= (int) $song['id'] ?>">
                <?= e($btnLabel) ?>
              </button>
              <?php if ($cStatus !== 'belum'): ?>
                <div class="small <?= $isMaster ? 'text-success' : 'text-warning' ?>">
                  <?= $isMaster ? 'Dikuasai' : 'Dilatih' ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <a class="btn btn-accent w-100 mt-3" href="<?= e(url('practice.php?song=' . $song['id'])) ?>">
        <i class="bi bi-hand-index-thumb me-1"></i>Latihan Chord Lagu Ini
      </a>
    <?php endif; ?>

    <!-- ================= TANDAI LAGU SELESAI ================= -->
    <div class="gl-card p-3 mt-3" id="songCompleteCard">
      <h3 class="h6 mb-2"><i class="bi bi-patch-check me-1 text-warning"></i>Status Lagu</h3>

      <?php if ($progress['completed']): ?>
        <p class="small text-success mb-2">
          <i class="bi bi-check-circle-fill me-1"></i>
          Lagu ini sudah kamu tandai <strong>sudah bisa dimainkan</strong>.
        </p>
        <?php if ($user): ?>
          <form method="post" action="<?= e(url('song-detail.php?id=' . $song['id'])) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="song_complete">
            <input type="hidden" name="done" value="0">
            <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
              <i class="bi bi-arrow-counterclockwise me-1"></i>Batalkan tanda selesai
            </button>
          </form>
        <?php endif; ?>
      <?php else: ?>
        <p class="small text-dim mb-2">
          Lagu boleh ditandai selesai setelah <strong>semua chord lagu ini dikuasai</strong>
          (<?= (int) $progress['mastered'] ?>/<?= (int) $progress['total'] ?>).
          Tanda ini dipakai sebagai syarat naik ke level berikutnya.
        </p>
        <?php if ($songLocked): ?>
          <p class="small text-warning mb-2">
            <i class="bi bi-lock me-1"></i>Level <?= e(level_label($song['level'])) ?> masih terkunci.
            Selesaikan level <?= e($songBlocker ? level_label($songBlocker) : 'sebelumnya') ?> dulu.
          </p>
        <?php endif; ?>
        <?php if (!$user): ?>
          <a href="<?= e(url('login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? ''))) ?>"
             class="btn btn-sm btn-accent w-100">
            <i class="bi bi-box-arrow-in-right me-1"></i>Login untuk menandai lagu
          </a>
        <?php else: ?>
          <form method="post" action="<?= e(url('song-detail.php?id=' . $song['id'])) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="song_complete">
            <input type="hidden" name="done" value="1">
            <button type="submit" class="btn btn-sm btn-accent w-100"
                    data-complete-song <?= ($allMastered && !$songLocked) ? '' : 'disabled' ?>>
              <i class="bi bi-check-circle me-1"></i>Sudah Bisa Lagu Ini
            </button>
            <?php if (!$allMastered): ?>
              <p class="small text-warning mb-0 mt-2">
                <i class="bi bi-lock me-1"></i>Kuasai semua chord di atas dulu.
              </p>
            <?php endif; ?>
          </form>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php layout_foot(['chord_db' => true]); ?>