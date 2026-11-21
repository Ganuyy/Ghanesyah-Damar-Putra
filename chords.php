<?php
/**
 * chords.php - Chord Library
 * Dapat diakses semua orang (guest, user, admin).
 *
 * Filter:
 *   ?level=beginner|intermediate|pro   -> tingkat kesulitan
 *   ?q=...                             -> pencarian nama chord
 */
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/chord_svg.php';
require_once __DIR__ . '/inc/layout.php';

$user   = current_user();
$filter = q('level');
$search = q('q');

if (!in_array($filter, level_options(), true)) {
    $filter = '';
}

$chords = all_chords($filter ?: null);

// Pencarian nama chord di sisi server
if ($search !== '') {
    $needle = mb_strtolower($search);
    $chords = array_filter($chords, function ($c) use ($needle) {
        return mb_strpos(mb_strtolower($c['name']), $needle) !== false
            || mb_strpos(mb_strtolower((string) $c['description']), $needle) !== false;
    });
}

// Progres chord milik user (kosong untuk guest, dilewati untuk admin)
$progress = $user && $user['role'] !== 'admin' ? user_chord_progress() : [];

layout_head('Chord Library', 'chords', ['chord_db' => true]);
?>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
  <div>
    <h1 class="h3 mb-1">Chord Library</h1>
    <p class="text-dim mb-0">
      <?= count($chords) ?> chord ditampilkan
      <?= $user ? '' : '- masuk dulu untuk menyimpan progres latihanmu' ?>
    </p>
  </div>

  <form class="d-flex gap-2" method="get" action="<?= e(url('chords.php')) ?>">
    <input type="hidden" name="level" value="<?= e($filter) ?>">
    <input type="search" name="q" class="form-control form-control-sm" placeholder="Cari chord, mis. Am"
           value="<?= e($search) ?>" style="min-width:190px">
    <button class="btn btn-sm btn-accent" type="submit">Cari</button>
  </form>
</div>

<!-- Tab tingkat kesulitan -->
<div class="level-tabs mb-3">
  <a class="level-tab <?= $filter === '' ? 'active' : '' ?>"
     href="<?= e(url('chords.php' . ($search ? '?q=' . urlencode($search) : ''))) ?>">Semua</a>
  <?php foreach (level_options() as $lv) : ?>
    <a class="level-tab <?= $filter === $lv ? 'active' : '' ?>"
       href="<?= e(url('chords.php?level=' . $lv . ($search ? '&q=' . urlencode($search) : ''))) ?>">
      <?= e(level_label($lv)) ?>
      <span class="text-dim">(<?= count(all_chords($lv)) ?>)</span>
    </a>
  <?php endforeach; ?>
</div>

<?php if (!$chords): ?>
  <div class="gl-card p-5 text-center">
    <i class="bi bi-search fs-1 text-dim"></i>
    <p class="text-dim mt-2 mb-0">Chord "<strong><?= e($search) ?></strong>" tidak ditemukan.</p>
  </div>
<?php else: ?>

  <div class="chord-grid">
    <?php foreach ($chords as $c):
        $st = $progress[$c['id']] ?? null;
        $isMastered = $st && $st['status'] === 'kuasai';
        $midiNotes = implode(',', chord_midi_notes($c));
    ?>
      <div class="chord-tile-wrap" data-search="<?= e($c['name'] . ' ' . $c['root'] . ' ' . $c['quality']) ?>">
        <a class="chord-tile"
           href="<?= e(url('chord-detail.php?id=' . $c['id'])) ?>">

          <?php if ($isMastered): ?>
            <span class="position-absolute top-0 start-0 m-2 badge bg-success" title="Sudah dikuasai">
              <i class="bi bi-check-lg"></i>
            </span>
          <?php endif; ?>

          <?= render_chord_svg($c, ['scale' => 1]) ?>

          <div class="chord-diagram-name"><?= e($c['name']) ?></div>
          <div class="d-flex justify-content-center gap-1 mb-1">
            <span class="badge bg-<?= level_color($c['difficulty']) ?>"><?= e(level_label($c['difficulty'])) ?></span>
            <?php if ($c['is_barre']): ?>
              <span class="badge bg-secondary">barre</span>
            <?php endif; ?>
          </div>
        </a>
        <button type="button" class="chord-play"
                data-play-chord data-notes="<?= e($midiNotes) ?>"
                title="Dengarkan chord <?= e($c['name']) ?>"
                aria-label="Dengarkan chord <?= e($c['name']) ?>">
          <i class="bi bi-volume-up-fill"></i>
        </button>
      </div>
    <?php endforeach; ?>
  </div>

  <p class="text-dim small mt-4">
    <i class="bi bi-info-circle me-1"></i>
    Tekan ikon <i class="bi bi-volume-up-fill"></i> untuk <strong>mendengarkan bunyi chord</strong>,
    atau klik chord untuk melihat diagram besar, keterangan posisi jari, dan daftar lagu yang memakainya.
  </p>
<?php endif; ?>

<?php layout_foot(['chord_db' => true]); ?>
