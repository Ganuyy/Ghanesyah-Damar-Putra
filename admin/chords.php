<?php
/**
 * admin/chords.php - Kelola Chord (daftar + hapus)
 * Hanya admin. Form tambah/edit ada di chord-form.php.
 */
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/chord_svg.php';
require_once __DIR__ . '/../inc/layout.php';

require_admin();

// ---------- Hapus chord ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && p('action') === 'delete') {
    csrf_check();
    $id   = (int) p('id');
    $name = $id ? (string) db()->query('SELECT name FROM chords WHERE id = ' . $id)->fetchColumn() : '';

    if ($id && $name !== '') {
        db()->prepare('DELETE FROM chords WHERE id = ?')->execute([$id]);
        log_activity('hapus_chord', 'Menghapus chord ' . $name);
        flash_set('success', 'Chord "' . $name . '" berhasil dihapus.');
    } else {
        flash_set('error', 'Chord tidak ditemukan.');
    }
    redirect('admin/chords.php');
}

// ---------- Ambil daftar chord ----------
$search = q('q');
$sql    = 'SELECT c.*,
                  (SELECT COUNT(*) FROM chord_fingering f WHERE f.chord_id = c.id) AS jml_senar,
                  (SELECT COUNT(*) FROM song_chords sc WHERE sc.chord_id = c.id) AS jml_lagu
             FROM chords c';
$params = [];
if ($search !== '') {
    $sql .= ' WHERE c.name LIKE ? OR c.root LIKE ?';
    $params = ['%' . $search . '%', '%' . $search . '%'];
}
$sql .= ' ORDER BY c.name';

$st = db()->prepare($sql);
$st->execute($params);
$chords = $st->fetchAll();

layout_head('Kelola Chord', 'admin', ['wide' => true]);
?>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
  <div>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-1 small">
        <li class="breadcrumb-item"><a href="<?= e(url('admin/index.php')) ?>" class="text-warning text-decoration-none">Dashboard</a></li>
        <li class="breadcrumb-item active text-dim">Kelola Chord</li>
      </ol>
    </nav>
    <h1 class="h3 mb-0"><i class="bi bi-diagram-3 me-2 text-warning"></i>Kelola Chord</h1>
  </div>
  <div class="d-flex gap-2">
    <form class="d-flex gap-2" method="get" action="<?= e(url('admin/chords.php')) ?>">
      <input type="search" name="q" class="form-control form-control-sm" placeholder="Cari chord"
             value="<?= e($search) ?>" style="min-width:170px">
      <button class="btn btn-sm btn-accent-outline" type="submit">Cari</button>
    </form>
    <a href="<?= e(url('admin/chord-form.php')) ?>" class="btn btn-sm btn-accent">
      <i class="bi bi-plus-lg me-1"></i>Tambah Chord
    </a>
  </div>
</div>

<?php if (!$chords): ?>
  <div class="gl-card p-5 text-center">
    <i class="bi bi-diagram-3 fs-1 text-dim"></i>
    <p class="text-dim mt-2 mb-3"><?= $search ? 'Chord tidak ditemukan.' : 'Belum ada chord.' ?></p>
    <?php if (!$search): ?>
      <a href="<?= e(url('admin/chord-form.php')) ?>" class="btn btn-accent">
        <i class="bi bi-plus-lg me-1"></i>Tambah Chord Pertama
      </a>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="gl-card p-3">
    <div class="table-responsive">
      <table class="table table-dark-gl align-middle mb-0">
        <thead>
          <tr>
            <th style="width:70px">Preview</th>
            <th>Chord</th>
            <th>Jenis</th>
            <th>Tingkat</th>
            <th class="text-center">Senar</th>
            <th class="text-center">Dipakai di</th>
            <th class="text-end" style="width:150px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($chords as $c): ?>
            <tr>
              <td><?= render_chord_svg($c, ['scale' => 0.5, 'show_open' => true]) ?></td>
              <td>
                <span class="fw-bold fs-5 text-warning"><?= e($c['name']) ?></span>
                <?php if ($c['is_barre']): ?>
                  <span class="badge bg-secondary ms-1">barre</span>
                <?php endif; ?>
              </td>
              <td class="small text-dim"><?= e($c['root'] . ' ' . $c['quality']) ?></td>
              <td><span class="badge bg-<?= level_color($c['difficulty']) ?>"><?= e(level_label($c['difficulty'])) ?></span></td>
              <td class="text-center"><?= (int) $c['jml_senar'] ?> / 6</td>
              <td class="text-center"><?= (int) $c['jml_lagu'] ?> lagu</td>
              <td class="text-end">
                <a href="<?= e(url('admin/chord-form.php?id=' . $c['id'])) ?>"
                   class="btn btn-sm btn-accent-outline" title="Edit">
                  <i class="bi bi-pencil"></i>
                </a>
                <form method="post" action="<?= e(url('admin/chords.php')) ?>" class="d-inline"
                      onsubmit="return confirm('Hapus chord <?= e($c['name']) ?>? Progress user yang memakai chord ini juga ikut terhapus.');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
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
  <p class="text-dim small mt-3 mb-0">
    <i class="bi bi-info-circle me-1"></i>
    Kolom "Senar" menunjukkan berapa senar yang punya data posisi jari (maksimal 6).
  </p>
<?php endif; ?>

<?php layout_foot(); ?>
