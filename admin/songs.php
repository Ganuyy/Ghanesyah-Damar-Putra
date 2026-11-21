<?php
/**
 * admin/songs.php - Kelola Lagu (daftar + hapus)
 * Hanya admin. Form tambah/edit ada di song-form.php.
 */
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/chord_svg.php';
require_once __DIR__ . '/../inc/layout.php';

require_admin();

// ---------- Hapus lagu ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p('action') === 'delete') {
    csrf_check();
    $id   = (int) p('id');
    $song = null;
    if ($id) {
        $st = db()->prepare('SELECT title, audio_file FROM songs WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $song = $st->fetch() ?: null;
    }

    if ($song) {
        db()->prepare('DELETE FROM songs WHERE id = ?')->execute([$id]);

        if (!empty($song['audio_file'])) {
            $path = SONG_AUDIO_DIR . DIRECTORY_SEPARATOR . basename($song['audio_file']);
            if (is_file($path)) {
                @unlink($path);
            }
        }

        log_activity('hapus_lagu', 'Menghapus lagu ' . $song['title']);
        flash_set('success', 'Lagu "' . $song['title'] . '" berhasil dihapus.');
    } else {
        flash_set('error', 'Lagu tidak ditemukan.');
    }
    redirect('admin/songs.php');
}

// ---------- Ambil daftar lagu ----------
$search = q('q');
$sql    = 'SELECT s.*,
                  (SELECT COUNT(*) FROM song_chords sc WHERE sc.song_id = s.id) AS jml_chord
             FROM songs s';
$params = [];
if ($search !== '') {
    $sql .= ' WHERE s.title LIKE ? OR s.artist LIKE ?';
    $params = ['%' . $search . '%', '%' . $search . '%'];
}
$sql .= ' ORDER BY s.title';

$st = db()->prepare($sql);
$st->execute($params);
$songs = $st->fetchAll();

layout_head('Kelola Lagu', 'admin', ['wide' => true]);
?>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
  <div>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-1 small">
        <li class="breadcrumb-item"><a href="<?= e(url('admin/index.php')) ?>" class="text-warning text-decoration-none">Dashboard</a></li>
        <li class="breadcrumb-item active text-dim">Kelola Lagu</li>
      </ol>
    </nav>
    <h1 class="h3 mb-0"><i class="bi bi-music-note-list me-2 text-warning"></i>Kelola Lagu</h1>
  </div>
  <div class="d-flex gap-2">
    <form class="d-flex gap-2" method="get" action="<?= e(url('admin/songs.php')) ?>">
      <input type="search" name="q" class="form-control form-control-sm" placeholder="Cari judul / artis"
             value="<?= e($search) ?>" style="min-width:170px">
      <button class="btn btn-sm btn-accent-outline" type="submit">Cari</button>
    </form>
    <a href="<?= e(url('admin/song-form.php')) ?>" class="btn btn-sm btn-accent">
      <i class="bi bi-plus-lg me-1"></i>Tambah Lagu
    </a>
  </div>
</div>

<?php if (!$songs): ?>
  <div class="gl-card p-5 text-center">
    <i class="bi bi-music-note-list fs-1 text-dim"></i>
    <p class="text-dim mt-2 mb-3"><?= $search ? 'Lagu tidak ditemukan.' : 'Belum ada lagu.' ?></p>
    <?php if (!$search): ?>
      <a href="<?= e(url('admin/song-form.php')) ?>" class="btn btn-accent">
        <i class="bi bi-plus-lg me-1"></i>Tambah Lagu Pertama
      </a>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="gl-card p-3">
    <div class="table-responsive">
      <table class="table table-dark-gl align-middle mb-0">
        <thead>
          <tr>
            <th>Judul</th>
            <th>Artis</th>
            <th>Level</th>
            <th class="text-center">Chord</th>
            <th>Tempo / Capo</th>
            <th class="text-end" style="width:150px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($songs as $s): ?>
            <tr>
              <td>
                <a href="<?= e(url('song-detail.php?id=' . $s['id'])) ?>" target="_blank"
                   class="song-row-link fw-bold"><?= e($s['title']) ?>
                  <i class="bi bi-box-arrow-up-right small"></i>
                </a>
                <?php if (!empty($s['audio_file'])): ?>
                  <span class="badge bg-info ms-1" title="Lagu asli tersedia">
                    <i class="bi bi-volume-up"></i> audio
                  </span>
                <?php endif; ?>
              </td>
              <td class="small text-dim"><?= e($s['artist'] ?: '-') ?></td>
              <td><span class="badge bg-<?= level_color($s['level']) ?>"><?= e(level_label($s['level'])) ?></span></td>
              <td class="text-center"><?= (int) $s['jml_chord'] ?></td>
              <td class="small text-dim">
                <?= $s['tempo'] ? e($s['tempo']) : '-' ?>
                <?= $s['capo'] ? ' / capo ' . e($s['capo']) : '' ?>
              </td>
              <td class="text-end">
                <a href="<?= e(url('admin/song-form.php?id=' . $s['id'])) ?>"
                   class="btn btn-sm btn-accent-outline" title="Edit">
                  <i class="bi bi-pencil"></i>
                </a>
                <form method="post" action="<?= e(url('admin/songs.php')) ?>" class="d-inline"
                      onsubmit="return confirm('Hapus lagu <?= e($s['title']) ?>?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php layout_foot(); ?>
