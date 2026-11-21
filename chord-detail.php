<?php
/**
 * chord-detail.php - Detail satu chord
 * Menampilkan diagram besar, keterangan posisi jari, tombol latihan,
 * dan daftar lagu yang memakai chord ini.
 */
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/chord_svg.php';
require_once __DIR__ . '/inc/layout.php';

$chord = get_chord((int) q('id'));

if (!$chord) {
    http_response_code(404);
    layout_head('Chord tidak ditemukan', 'chords', ['chord_db' => true]);
    echo '<div class="gl-card p-5 text-center">'
       . '<i class="bi bi-exclamation-triangle fs-1 text-warning"></i>'
       . '<h1 class="h5 mt-3">Chord tidak ditemukan</h1>'
       . '<a href="' . e(url('chords.php')) . '" class="btn btn-accent mt-2">Kembali ke Chord Library</a>'
       . '</div>';
    layout_foot(['chord_db' => true]);
    exit;
}

// Lagu yang memakai chord ini
$stmt = db()->prepare(
    'SELECT s.id, s.title, s.artist, s.level
       FROM songs s
       JOIN song_chords sc ON sc.song_id = s.id
      WHERE sc.chord_id = ?
      ORDER BY s.title'
);
$stmt->execute([$chord['id']]);
$songs = $stmt->fetchAll();

$progress = user_chord_progress();
$st       = $progress[$chord['id']] ?? null;
$status   = $st['status'] ?? 'belum';

layout_head('Chord ' . $chord['name'], 'chords', ['chord_db' => true]);
?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Beranda</a></li>
    <li class="breadcrumb-item"><a href="<?= e(url('chords.php')) ?>">Chord Library</a></li>
    <li class="breadcrumb-item active" aria-current="page"><?= e($chord['name']) ?></li>
  </ol>
</nav>

<div class="row g-4">
  <!-- Diagram -->
  <div class="col-lg-5">
    <div class="gl-card p-4 text-center">
      <div class="display-4 fw-bold text-warning mb-1"><?= e($chord['name']) ?></div>
      <div class="d-flex justify-content-center gap-2 mb-3">
        <span class="badge bg-<?= level_color($chord['difficulty']) ?>"><?= e(level_label($chord['difficulty'])) ?></span>
        <span class="badge bg-secondary"><?= e($chord['quality']) ?></span>
        <?php if ($chord['is_barre']): ?>
          <span class="badge bg-secondary">Teknik barre/palang/gantung</span>
        <?php endif; ?>
      </div>

      <div class="chord-diagram-lg">
        <?= render_chord_svg($chord, ['scale' => 1.9]) ?>
      </div>

      <p class="text-dim small mt-3 mb-0"><?= e(chord_text_shape($chord)) ?></p>

      <hr style="border-color:var(--gl-border)">

      <div class="gl-card p-3 mb-3">
        <p class="small mb-2">
          <strong class="text-white">Cara membaca diagram:</strong>
        </p>
        <ul class="small text-dim mb-0 ps-3">
          <li>
            <span class="badge bg-secondary">O</span> senar terbuka, tidak ditekan
            &nbsp;&middot;&nbsp;
            <span class="badge bg-secondary">X</span> senar tidak dipetik
          </li>
          <li>
            Garis paling kiri = senar nomor 6 (paling tebal), paling kanan =
            senar nomor 1 (paling tipis). Lingkaran ungu = letak jari
            ditekan, angka di dalamnya = nomor jari.
          </li>
          <li>
            Batang ungu mendatar = <strong>barre</strong>, yaitu satu jari ditekan
            mendatar sekaligus di beberapa senar pada fret yang sama.
          </li>
          <li>
            Nomor fret dicetak di kiri setiap baris fret, jadi tahu mulai dari fret berapa.
          </li>
        </ul>

        <hr style="border-color:var(--gl-border)">

        <p class="small mb-2">
          <strong class="text-white">Arti nomor jari:</strong>
        </p>
        <div class="d-flex flex-wrap gap-2 small text-dim">
          <?php
          $legend = [
              1 => ['jari telunjuk', 'paling sering dipakai untuk barre'],
              2 => ['jari tengah', 'tulang tengah tangan'],
              3 => ['jari manis', 'jari ke-4 dari jempol'],
              4 => ['jari kelingking', 'paling kecil'],
          ];
          foreach ($legend as $num => $info):
          ?>
            <span class="badge bg-accent"><?= $num ?></span>
            <span><?= e($info[0]) ?> &mdash; <?= e($info[1]) ?></span>
          <?php endforeach; ?>
        </div>
      </div>

      <?php if ($chord['is_barre']): ?>
        <p class="small text-dim mb-0">
          Chord ini memakai teknik <strong>barre/palang/gantung</strong>. Tekan jari telunjuk
          mendatar pada fret yang ditandai, tahan sampai senar yang diberi
          lingkaran nomor <strong>1</strong>, lalu tekan jari lain di atasnya.
        </p>
      <?php elseif ($chord['start_fret'] > 1): ?>
        <p class="text-dim small mb-0">
          Diagram ini dimulai dari fret <strong><?= (int) $chord['start_fret'] ?></strong>.
          Angka di kiri setiap baris fret menunjukkan jarak senar dari
          kepala gitar.
        </p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Deskripsi + aksi -->
  <div class="col-lg-7">
    <div class="gl-card p-4 mb-4">
      <h2 class="h5">Tentang chord <?= e($chord['name']) ?></h2>
      <p class="text-dim"><?= e((string) $chord['description']) ?></p>

      <?php if ($user): ?>
        <?php
        $btnLabel = $status === 'kuasai' ? 'Tandai Belum Bisa' : 'Sudah Bisa';
        $btnClass = $status === 'kuasai' ? 'btn-success' : 'btn-accent';
        ?>
        <div class="d-flex flex-wrap align-items-center gap-3">
          <button class="btn <?= $btnClass ?>"
                  data-master-chord="<?= (int) $chord['id'] ?>"
                  data-status="<?= e($status) ?>"
                  data-label-on="<?= e($btnLabel) ?>"
                  data-scope="chord">
            <?= e($btnLabel) ?>
          </button>
          <a class="btn btn-accent-outline" href="<?= e(url('practice.php?chord=' . $chord['id'])) ?>">
            <i class="bi bi-play-circle me-1"></i>Latihan chord ini
          </a>
          <?php if ($st): ?>
            <span class="text-dim small">
              Sudah dilatih <strong><?= (int) $st['practice_count'] ?>x</strong>
              &middot; terakhir <?= e(format_date($st['last_practiced_at'])) ?>
            </span>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <button class="btn btn-accent" disabled>Tandai Sudah Bisa</button>
          <span class="text-dim small">
            <i class="bi bi-info-circle me-1"></i>
            Progress tidak tersimpan karena kamu belum login.
            <a href="<?= e(url('login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? ''))) ?>">Login</a>
            atau <a href="<?= e(url('register.php')) ?>">daftar</a> dulu.
          </span>
        </div>
      <?php endif; ?>
    </div>

    <div class="mb-4">
      <?= sound_panel($chord) ?>
    </div>

    <div class="gl-card p-4">
      <h2 class="h5 mb-3">
        Lagu yang memakai chord <?= e($chord['name']) ?>
        <span class="badge bg-secondary ms-1"><?= count($songs) ?></span>
      </h2>

      <?php if (!$songs): ?>
        <p class="text-dim mb-0">
          Belum ada lagu yang memakai chord ini.
          Admin bisa menambahkannya lewat menu <em>Admin &rarr; Kelola Lagu</em>.
        </p>
      <?php else: ?>
        <ul class="list-unstyled mb-0">
          <?php foreach ($songs as $s): ?>
            <li class="py-2 border-bottom" style="border-color:var(--gl-border)!important">
              <a class="song-row-link d-flex justify-content-between align-items-center"
                 href="<?= e(url('song-detail.php?id=' . $s['id'])) ?>">
                <span>
                  <?= e($s['title']) ?>
                  <?php if ($s['artist']): ?>
                    <span class="text-dim small">- <?= e($s['artist']) ?></span>
                  <?php endif; ?>
                </span>
                <span class="badge bg-<?= level_color($s['level']) ?>"><?= e(level_label($s['level'])) ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php layout_foot(['chord_db' => true]); ?>