<?php
/**
 * admin/song-form.php - Tambah / Edit Lagu
 * Hanya admin.
 *
 * Menyimpan ke tabel `songs`, dan chord yang dipakai ke `song_chords`.
 * Format lirik: chord ditulis dalam kurung siku, mis. [G]Amazing grace
 */
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/chord_svg.php';
require_once __DIR__ . '/../inc/layout.php';

require_admin();

$id      = (int) q('id');
$editing = $id > 0;

$song = null;
if ($editing) {
    $st = db()->prepare('SELECT * FROM songs WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $song = $st->fetch() ?: null;

    if (!$song) {
        flash_set('error', 'Lagu tidak ditemukan.');
        redirect('admin/songs.php');
    }
}

// Semua chord untuk pilihan "chord yang dipakai"
$allChords = array_values(all_chords());

// Chord yang sudah dipilih lagu ini
$selectedChords = [];
if ($song) {
    $st = db()->prepare('SELECT chord_id FROM song_chords WHERE song_id = ? ORDER BY sort_order, id');
    $st->execute([$id]);
    foreach ($st->fetchAll() as $r) {
        $selectedChords[] = (int) $r['chord_id'];
    }
}

$form = [
    'title'  => $song['title']  ?? '',
    'artist' => $song['artist'] ?? '',
    'level'  => $song['level']  ?? 'beginner',
    'capo'   => $song['capo']   ?? '',
    'tempo'  => $song['tempo']  ?? '',
    'notes'  => $song['notes']  ?? '',
    'lyrics' => $song['lyrics'] ?? '',
    'audio_file' => $song['audio_file'] ?? '',
];

$oldAudio = (string) $form['audio_file'];
$newAudio = $oldAudio;

$errors = [];

// ==========================================================
//  PROSES SIMPAN
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $form['title']  = trim(p('title'));
    $form['artist'] = trim(p('artist'));
    $form['level']  = p('level', 'beginner');
    $form['capo']   = trim(p('capo'));
    $form['tempo']  = trim(p('tempo'));
    $form['notes']  = trim(p('notes'));
    $form['lyrics'] = (string) ($_POST['lyrics'] ?? '');

    $postedChords = array_map('intval', $_POST['chords'] ?? []);

    if ($form['title'] === '') {
        $errors[] = 'Judul lagu wajib diisi.';
    }
    if (!in_array($form['level'], level_options(), true)) {
        $errors[] = 'Level tidak valid.';
    }
    if (trim($form['lyrics']) === '') {
        $errors[] = 'Lirik wajib diisi.';
    }

    // ---------- Upload lagu asli (audio) ----------
    $removeAudio = p('remove_audio') !== '';
    if ($removeAudio) {
        $newAudio = '';
    }

    $allowed = ['mp3', 'ogg', 'wav', 'm4a', 'aac'];
    $manual  = basename(str_replace('\\', '/', trim(p('audio_file_name'))));
    $hasUpload = false;

    $upload = $_FILES['audio'] ?? null;
    if ($upload && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $hasUpload = true;
        if (($upload['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload audio gagal (kode ' . (int) $upload['error'] . ').';
        } else {
            $ext = strtolower(pathinfo((string) $upload['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed, true)) {
                $errors[] = 'Format audio harus salah satu dari: ' . implode(', ', $allowed) . '.';
            } elseif ((int) $upload['size'] > 30 * 1024 * 1024) {
                $errors[] = 'Ukuran audio maksimal 30 MB.';
            } else {
                if (!is_dir(SONG_AUDIO_DIR)) {
                    @mkdir(SONG_AUDIO_DIR, 0775, true);
                }

                $fname = 'song-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest  = SONG_AUDIO_DIR . DIRECTORY_SEPARATOR . $fname;

                if (move_uploaded_file($upload['tmp_name'], $dest)) {
                    $newAudio = $fname;
                } else {
                    $errors[] = 'Tidak bisa menyimpan file audio. Periksa izin folder uploads/songs.';
                }
            }
        }
    }

    // Nama file yang sudah ditaruh manual di folder uploads/songs
    if (!$hasUpload && !$removeAudio && $manual !== '') {
        $ext = strtolower(pathinfo($manual, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            $errors[] = 'Nama file audio manual harus berformat: ' . implode(', ', $allowed) . '.';
        } else {
            $newAudio = $manual;
        }
    }

    if (!$errors) {
        $pdo = db();

        // Hanya simpan chord yang benar-benar ada di library
        $validIds = array_column($allChords, 'id');
        $postedChords = array_values(array_intersect($postedChords, array_map('intval', $validIds)));

        $pdo->beginTransaction();
        try {
            if ($editing) {
                $pdo->prepare(
                    'UPDATE songs
                        SET title = ?, artist = ?, level = ?, capo = ?, tempo = ?, notes = ?, lyrics = ?, audio_file = ?
                      WHERE id = ?'
                )->execute([
                    $form['title'], $form['artist'], $form['level'], $form['capo'] ?: null,
                    $form['tempo'] ?: null, $form['notes'] ?: null, $form['lyrics'],
                    $newAudio ?: null, $id,
                ]);
                $songId = $id;
                $pdo->prepare('DELETE FROM song_chords WHERE song_id = ?')->execute([$songId]);
            } else {
                $pdo->prepare(
                    'INSERT INTO songs (title, artist, level, capo, tempo, notes, lyrics, audio_file)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                )->execute([
                    $form['title'], $form['artist'], $form['level'], $form['capo'] ?: null,
                    $form['tempo'] ?: null, $form['notes'] ?: null, $form['lyrics'],
                    $newAudio ?: null,
                ]);
                $songId = (int) $pdo->lastInsertId();
            }

            $ins = $pdo->prepare(
                'INSERT INTO song_chords (song_id, chord_id, sort_order) VALUES (?, ?, ?)'
            );
            $order = 0;
            foreach ($postedChords as $cid) {
                $ins->execute([$songId, $cid, ++$order]);
            }

            $pdo->commit();

            // Hapus file audio lama bila sudah diganti / dihapus
            if ($oldAudio !== '' && $oldAudio !== $newAudio) {
                $oldPath = SONG_AUDIO_DIR . DIRECTORY_SEPARATOR . basename($oldAudio);
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $form['audio_file'] = $newAudio;

            log_activity($editing ? 'edit_lagu' : 'tambah_lagu',
                ($editing ? 'Mengubah' : 'Menambah') . ' lagu ' . $form['title']);
            flash_set('success', 'Lagu "' . $form['title'] . '" berhasil disimpan.');
            redirect('admin/songs.php');
        } catch (Throwable $ex) {
            $pdo->rollBack();
            $errors[] = 'Gagal menyimpan: ' . $ex->getMessage();
        }
    } else {
        $selectedChords = $postedChords;
    }
}

layout_head($editing ? 'Edit Lagu' : 'Tambah Lagu', 'admin', ['wide' => true]);
?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb mb-1 small">
    <li class="breadcrumb-item"><a href="<?= e(url('admin/index.php')) ?>" class="text-warning text-decoration-none">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="<?= e(url('admin/songs.php')) ?>" class="text-warning text-decoration-none">Kelola Lagu</a></li>
    <li class="breadcrumb-item active text-dim"><?= $editing ? 'Edit' : 'Tambah' ?></li>
  </ol>
</nav>
<h1 class="h3 mb-3">
  <i class="bi bi-<?= $editing ? 'pencil' : 'plus-lg' ?> me-2 text-warning"></i>
  <?= $editing ? 'Edit Lagu: ' . e($form['title']) : 'Tambah Lagu Baru' ?>
</h1>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <strong>Perhatian:</strong>
    <ul class="mb-0 mt-1">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data"
      action="<?= e(url('admin/song-form.php' . ($editing ? '?id=' . $id : ''))) ?>">
  <?= csrf_field() ?>

  <div class="row g-4">
    <!-- ============ DATA LAGU ============ -->
    <div class="col-lg-7">
      <div class="gl-card p-4 mb-4">
        <h2 class="h6 mb-3"><i class="bi bi-info-circle me-2 text-warning"></i>Data Lagu</h2>

        <div class="row g-3">
          <div class="col-md-7">
            <label class="form-label" for="title">Judul lagu</label>
            <input type="text" class="form-control" id="title" name="title" maxlength="150"
                   value="<?= e($form['title']) ?>" required>
          </div>
          <div class="col-md-5">
            <label class="form-label" for="artist">Artis</label>
            <input type="text" class="form-control" id="artist" name="artist" maxlength="150"
                   value="<?= e($form['artist']) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label" for="level">Level</label>
            <select class="form-select" id="level" name="level">
              <?php foreach (level_options() as $lv): ?>
                <option value="<?= e($lv) ?>" <?= $form['level'] === $lv ? 'selected' : '' ?>>
                  <?= e(level_label($lv)) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="capo">Capo</label>
            <input type="text" class="form-control" id="capo" name="capo" maxlength="10"
                   value="<?= e($form['capo']) ?>" placeholder="mis. 2">
          </div>
          <div class="col-md-4">
            <label class="form-label" for="tempo">Tempo</label>
            <input type="text" class="form-control" id="tempo" name="tempo" maxlength="20"
                   value="<?= e($form['tempo']) ?>" placeholder="mis. 90 BPM">
          </div>
          <div class="col-12">
            <label class="form-label" for="notes">Catatan</label>
            <input type="text" class="form-control" id="notes" name="notes"
                   value="<?= e($form['notes']) ?>" placeholder="mis. Chord: C, E7, G">
          </div>
        </div>
      </div>

      <div class="gl-card p-4 mt-4">
        <h2 class="h6 mb-2"><i class="bi bi-music-note-beamed me-2 text-warning"></i>Lagu Asli (Audio)</h2>
        <p class="text-dim small mb-2">
          Unggah file lagu aslinya di sini supaya user bisa mendengarkan irama asli sebelum memainkan.
          File disimpan di folder <code>uploads/songs/</code> pada project.
        </p>

        <?php if (!empty($form['audio_file'])): ?>
          <div class="mb-3">
            <div class="small text-dim mb-1">
              File saat ini: <code><?= e($form['audio_file']) ?></code>
            </div>
            <audio controls preload="metadata" class="w-100"
                   src="<?= e(song_audio_url($form['audio_file'])) ?>"></audio>
            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" id="remove_audio"
                     name="remove_audio" value="1">
              <label class="form-check-label small text-danger" for="remove_audio">
                Hapus lagu asli ini
              </label>
            </div>
          </div>
        <?php endif; ?>

        <label class="form-label" for="audio"><?= !empty($form['audio_file']) ? 'Ganti file audio' : 'File audio lagu asli' ?></label>
        <input type="file" class="form-control" id="audio" name="audio"
               accept=".mp3,.ogg,.wav,.m4a,.aac,audio/*">
        <div class="form-text mb-3">
          Format: mp3, ogg, wav, m4a, aac. Maksimal 30 MB.
        </div>

        <label class="form-label" for="audio_file_name">Atau nama file yang sudah ditaruh manual</label>
        <input type="text" class="form-control" id="audio_file_name" name="audio_file_name"
               value="<?= e($form['audio_file']) ?>" placeholder="mis. amazing-grace.mp3">
        <div class="form-text">
          Taruh file lagu di folder <code>uploads/songs/</code>, lalu tulis nama filenya di sini.
          Kosongkan jika tidak ingin memakai lagu asli.
        </div>
      </div>

      <div class="gl-card p-4 mt-4">
        <h2 class="h6 mb-2"><i class="bi bi-file-text me-2 text-warning"></i>Lirik &amp; Chord</h2>
        <p class="text-dim small mb-2">
          Tulis chord di dalam kurung siku tepat sebelum katanya, contoh:<br>
          <code>[G]Amazing grace how sweet the [C]sound</code>
        </p>
        <textarea class="form-control" id="lyrics" name="lyrics" rows="14"
                  style="font-family: ui-monospace, Consolas, monospace;"><?= e($form['lyrics']) ?></textarea>
      </div>
    </div>

    <!-- ============ CHORD YANG DIPAKAI + PREVIEW ============ -->
    <div class="col-lg-5">
      <div class="gl-card p-4">
        <h2 class="h6 mb-2"><i class="bi bi-diagram-3 me-2 text-warning"></i>Chord yang Dipakai</h2>
        <p class="text-dim small">
          Centang chord yang muncul di lagu ini. Ini yang tampil sebagai chip di kartu lagu.
        </p>

        <?php if (!$allChords): ?>
          <p class="text-dim small mb-0">
            Belum ada chord di library. <a href="<?= e(url('admin/chord-form.php')) ?>" class="text-warning">Tambah chord dulu</a>.
          </p>
        <?php else: ?>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($allChords as $c): ?>
              <div class="form-check form-check-inline m-0">
                <input class="form-check-input" type="checkbox" name="chords[]"
                       id="chord<?= (int) $c['id'] ?>" value="<?= (int) $c['id'] ?>"
                       <?= in_array((int) $c['id'], $selectedChords, true) ? 'checked' : '' ?>>
                <label class="form-check-label" for="chord<?= (int) $c['id'] ?>">
                  <?= e($c['name']) ?>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <?php if ($editing): ?>
        <div class="gl-card p-4 mt-4 small">
          <h2 class="h6 mb-2"><i class="bi bi-clock-history me-2 text-warning"></i>Riwayat</h2>
          <div class="text-dim">Dibuat: <?= e(format_date($song['created_at'])) ?></div>
          <div class="text-dim">Diubah: <?= e(format_date($song['updated_at'])) ?></div>
          <a href="<?= e(url('song-detail.php?id=' . $id)) ?>" target="_blank"
             class="btn btn-sm btn-accent-outline w-100 mt-3">
            <i class="bi bi-eye me-1"></i>Lihat halaman lagu
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="d-flex justify-content-between mt-4">
    <a href="<?= e(url('admin/songs.php')) ?>" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Batal
    </a>
    <button type="submit" class="btn btn-accent">
      <i class="bi bi-save me-1"></i><?= $editing ? 'Simpan Perubahan' : 'Tambah Lagu' ?>
    </button>
  </div>
</form>

<?php layout_foot(); ?>
