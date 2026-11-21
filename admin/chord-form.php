<?php
/**
 * admin/chord-form.php - Tambah / Edit Chord
 * Hanya admin.
 *
 * Form ini berisi:
 *   - data dasar chord (nama, nada dasar, jenis, tingkat, dll)
 *   - "builder posisi jari": pilih per senar mau diapain
 *     (tidak dipetik / terbuka / ditekan di fret berapa)
 *
 * Saat disimpan, data ditulis ke tabel `chords` dan `chord_fingering`.
 */
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/chord_svg.php';
require_once __DIR__ . '/../inc/layout.php';

require_admin();

$id      = (int) q('id');
$editing = $id > 0;
$chord   = $editing ? get_chord($id) : null;

if ($editing && !$chord) {
    flash_set('error', 'Chord tidak ditemukan.');
    redirect('admin/chords.php');
}

// Daftar jenis chord yang umum
$qualities = ['major', 'minor', '7', 'maj7', 'm7', 'sus2', 'sus4', 'dim', 'aug', 'add9', '9', 'maj9', 'm9', '6', 'm6', 'power', 'slash', '7#9', '7b9', '11', '13'];

// ---------- Nilai form (default untuk tambah / dari DB untuk edit) ----------
$form = [
    'name'        => $chord['name']        ?? '',
    'root'        => $chord['root']        ?? '',
    'quality'     => $chord['quality']     ?? 'major',
    'difficulty'  => $chord['difficulty']  ?? 'beginner',
    'start_fret'  => $chord['start_fret']  ?? 1,
    'frets_shown' => $chord['frets_shown'] ?? 4,
    'is_barre'    => (int) ($chord['is_barre'] ?? 0),
    'description' => $chord['description'] ?? '',
];

// Susunan senar yang sudah ada: [string_num => ['mode'=>..,'fret'=>..,'finger'=>..]]
$strings = [];
for ($s = 1; $s <= 6; $s++) {
    $strings[$s] = ['mode' => 'x', 'fret' => '', 'finger' => ''];
}
if ($chord) {
    foreach ($chord['fingering'] as $s => $f) {
        $fret   = (int) $f['fret'];
        $finger = $f['finger'];
        $strings[$s] = [
            'mode'   => $fret === 0 ? 'o' : 'fret',
            'fret'   => $fret === 0 ? '' : (string) $fret,
            'finger' => $finger === null ? '' : (string) $finger,
        ];
    }
}

$errors = [];

// ==========================================================
//  PROSES SIMPAN
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $form['name']        = trim(p('name'));
    $form['root']        = trim(p('root'));
    $form['quality']     = p('quality', 'major');
    $form['difficulty']  = p('difficulty', 'beginner');
    $form['start_fret']  = (int) p('start_fret');
    $form['frets_shown'] = (int) p('frets_shown');
    $form['is_barre']    = p('is_barre') !== '' ? 1 : 0;
    $form['description'] = trim(p('description'));

    // Nada dasar otomatis dari nama bila kosong
    if ($form['root'] === '' && $form['name'] !== '') {
        $form['root'] = strtoupper(substr($form['name'], 0, 1));
    }

    // Validasi
    if ($form['name'] === '') {
        $errors[] = 'Nama chord wajib diisi.';
    }
    if (!in_array($form['difficulty'], level_options(), true)) {
        $errors[] = 'Tingkat kesulitan tidak valid.';
    }
    if ($form['start_fret'] < 1 || $form['start_fret'] > 24) {
        $errors[] = 'Fret awal harus antara 1 dan 24.';
    }
    if ($form['frets_shown'] < 3 || $form['frets_shown'] > 7) {
        $errors[] = 'Jumlah fret yang digambar harus antara 3 dan 7.';
    }

    // Cek nama unik
    if ($form['name'] !== '') {
        $dup = db()->prepare('SELECT id FROM chords WHERE name = ? AND id <> ?');
        $dup->execute([$form['name'], $id]);
        if ($dup->fetchColumn()) {
            $errors[] = 'Nama chord "' . $form['name'] . '" sudah dipakai.';
        }
    }

    // Baca data senar
    $postedStrings = $_POST['strings'] ?? [];
    $newFingering  = [];
    for ($s = 1; $s <= 6; $s++) {
        $row  = $postedStrings[$s] ?? [];
        $mode = in_array(($row['mode'] ?? 'x'), ['x', 'o', 'fret'], true) ? $row['mode'] : 'x';

        if ($mode === 'o') {
            $newFingering[$s] = ['fret' => 0, 'finger' => null];
        } elseif ($mode === 'fret') {
            $fret   = (int) ($row['fret'] ?? 0);
            $finger = (int) ($row['finger'] ?? 0);
            if ($fret < 1 || $fret > 24) {
                $errors[] = "Senar $s: fret harus antara 1 dan 24.";
            }
            if ($finger < 1 || $finger > 4) {
                $errors[] = "Senar $s: pilih jari 1-4.";
            }
            $newFingering[$s] = ['fret' => $fret, 'finger' => $finger ?: null];
        }
        $strings[$s] = [
            'mode'   => $mode,
            'fret'   => (string) ($row['fret'] ?? ''),
            'finger' => (string) ($row['finger'] ?? ''),
        ];
    }

    if (!$newFingering) {
        $errors[] = 'Minimal satu senar harus dipilih (terbuka atau ditekan).';
    }

    // Simpan bila tidak ada error
    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            if ($editing) {
                $pdo->prepare(
                    'UPDATE chords
                        SET name = ?, root = ?, quality = ?, difficulty = ?,
                            start_fret = ?, frets_shown = ?, is_barre = ?, description = ?
                      WHERE id = ?'
                )->execute([
                    $form['name'], $form['root'], $form['quality'], $form['difficulty'],
                    $form['start_fret'], $form['frets_shown'], $form['is_barre'],
                    $form['description'], $id,
                ]);
                $chordId = $id;
                $pdo->prepare('DELETE FROM chord_fingering WHERE chord_id = ?')->execute([$chordId]);
            } else {
                $pdo->prepare(
                    'INSERT INTO chords
                        (name, root, quality, difficulty, start_fret, frets_shown, is_barre, description)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                )->execute([
                    $form['name'], $form['root'], $form['quality'], $form['difficulty'],
                    $form['start_fret'], $form['frets_shown'], $form['is_barre'], $form['description'],
                ]);
                $chordId = (int) $pdo->lastInsertId();
            }

            $ins = $pdo->prepare(
                'INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES (?, ?, ?, ?)'
            );
            foreach ($newFingering as $s => $f) {
                $ins->execute([$chordId, $s, $f['fret'], $f['finger']]);
            }

            $pdo->commit();

            log_activity($editing ? 'edit_chord' : 'tambah_chord',
                ($editing ? 'Mengubah' : 'Menambah') . ' chord ' . $form['name']);
            flash_set('success', 'Chord "' . $form['name'] . '" berhasil disimpan.');
            redirect('admin/chords.php');
        } catch (Throwable $ex) {
            $pdo->rollBack();
            $errors[] = 'Gagal menyimpan: ' . $ex->getMessage();
        }
    }
}

// Pratinjau chord (untuk edit) — pakai data dari database
$preview = $editing ? get_chord($id) : null;

layout_head($editing ? 'Edit Chord' : 'Tambah Chord', 'admin', ['wide' => true]);
?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb mb-1 small">
    <li class="breadcrumb-item"><a href="<?= e(url('admin/index.php')) ?>" class="text-warning text-decoration-none">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="<?= e(url('admin/chords.php')) ?>" class="text-warning text-decoration-none">Kelola Chord</a></li>
    <li class="breadcrumb-item active text-dim"><?= $editing ? 'Edit' : 'Tambah' ?></li>
  </ol>
</nav>
<h1 class="h3 mb-3">
  <i class="bi bi-<?= $editing ? 'pencil' : 'plus-lg' ?> me-2 text-warning"></i>
  <?= $editing ? 'Edit Chord: ' . e($form['name']) : 'Tambah Chord Baru' ?>
</h1>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <strong>Perhatian:</strong>
    <ul class="mb-0 mt-1">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" id="fingerForm"
      action="<?= e(url('admin/chord-form.php' . ($editing ? '?id=' . $id : ''))) ?>">
  <?= csrf_field() ?>

  <div class="row g-4">
    <!-- ============ DATA DASAR ============ -->
    <div class="col-lg-7">
      <div class="gl-card p-4">
        <h2 class="h6 mb-3"><i class="bi bi-info-circle me-2 text-warning"></i>Data Chord</h2>

        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label" for="name">Nama chord</label>
            <input type="text" class="form-control" id="name" name="name" maxlength="20"
                   value="<?= e($form['name']) ?>" placeholder="mis. C, Am, B7" required>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="root">Nada dasar</label>
            <input type="text" class="form-control" id="root" name="root" maxlength="10"
                   value="<?= e($form['root']) ?>" placeholder="mis. C, F#">
            <div class="form-text">Boleh dikosongkan, otomatis dari nama.</div>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="quality">Jenis</label>
            <select class="form-select" id="quality" name="quality">
              <?php foreach ($qualities as $qa): ?>
                <option value="<?= e($qa) ?>" <?= $form['quality'] === $qa ? 'selected' : '' ?>>
                  <?= e($qa) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="difficulty">Tingkat kesulitan</label>
            <select class="form-select" id="difficulty" name="difficulty">
              <?php foreach (level_options() as $lv): ?>
                <option value="<?= e($lv) ?>" <?= $form['difficulty'] === $lv ? 'selected' : '' ?>>
                  <?= e(level_label($lv)) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="start_fret">Fret awal</label>
            <input type="number" class="form-control" id="start_fret" name="start_fret"
                   min="1" max="24" value="<?= (int) $form['start_fret'] ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label" for="frets_shown">Jumlah fret</label>
            <input type="number" class="form-control" id="frets_shown" name="frets_shown"
                   min="3" max="7" value="<?= (int) $form['frets_shown'] ?>">
          </div>

          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="is_barre" name="is_barre" value="1"
                     <?= $form['is_barre'] ? 'checked' : '' ?>>
              <label class="form-check-label" for="is_barre">
                Chord ini memakai <strong>barre</strong> (satu jari menekan beberapa senar)
              </label>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label" for="description">Deskripsi</label>
            <textarea class="form-control" id="description" name="description" rows="3"
                      placeholder="Penjelasan singkat chord ini"><?= e($form['description']) ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ BUILDER POSISI JARI ============ -->
    <div class="col-lg-5">
      <div class="gl-card p-4">
        <h2 class="h6 mb-2"><i class="bi bi-hand-index-thumb me-2 text-warning"></i>Posisi Jari</h2>
        <p class="text-dim small">
          Untuk setiap senar, pilih: <strong>X</strong> (tidak dipetik),
          <strong>0</strong> (terbuka/tanpa ditekan), atau <strong>Fret</strong> (ditekan).
          Langsung isi kolom <strong>fret</strong> atau <strong>jari</strong> saja &mdash;
          mode senar otomatis berubah jadi "Ditekan".
          Senar <strong>1 paling halus</strong>, senar <strong>6 paling tebal</strong>.
        </p>

        <?php for ($s = 6; $s >= 1; $s--): $row = $strings[$s]; ?>
          <div class="finger-row">
            <span class="string-badge">
              <span class="string-dot"></span> Senar <b><?= $s ?></b>
            </span>

            <select class="form-select form-select-sm" name="strings[<?= $s ?>][mode]"
                    data-string-mode="<?= $s ?>">
              <option value="x"    <?= $row['mode'] === 'x'    ? 'selected' : '' ?>>X - tidak dipetik</option>
              <option value="o"    <?= $row['mode'] === 'o'    ? 'selected' : '' ?>>0 - terbuka</option>
              <option value="fret" <?= $row['mode'] === 'fret' ? 'selected' : '' ?>>Ditekan</option>
            </select>

            <input type="number" class="form-control form-control-sm" name="strings[<?= $s ?>][fret]"
                   min="1" max="24" placeholder="Fret" value="<?= e($row['fret']) ?>"
                   data-string-fret="<?= $s ?>">

            <select class="form-select form-select-sm" name="strings[<?= $s ?>][finger]"
                    data-string-finger="<?= $s ?>">
              <option value="">Jari</option>
              <?php for ($f = 1; $f <= 4; $f++): ?>
                <option value="<?= $f ?>" <?= (string) $row['finger'] === (string) $f ? 'selected' : '' ?>>
                  <?= $f ?>
                </option>
              <?php endfor; ?>
            </select>
          </div>
        <?php endfor; ?>

        <div id="barreHint" class="form-text mt-2"></div>

        <button type="button" id="addStringBtn" class="btn btn-sm btn-outline-secondary mt-3">
          <i class="bi bi-question-circle me-1"></i>Butuh bantuan senar?
        </button>
      </div>

      <?php if ($preview): ?>
        <div class="gl-card p-4 mt-4 text-center">
          <h2 class="h6 mb-3"><i class="bi bi-eye me-2 text-warning"></i>Pratinjau Tersimpan</h2>
          <?= chord_card_svg($preview, 1.4) ?>
          <p class="text-dim small mb-0 mt-2"><?= e(chord_text_shape($preview)) ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="d-flex justify-content-between mt-4">
    <a href="<?= e(url('admin/chords.php')) ?>" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Batal
    </a>
    <button type="submit" class="btn btn-accent">
      <i class="bi bi-save me-1"></i><?= $editing ? 'Simpan Perubahan' : 'Tambah Chord' ?>
    </button>
  </div>
</form>

<script>
  // Sinkronisasi kolom fret & jari dengan mode senar.
  // Field tidak pernah benar-benar "mati": bila admin mengklik fret/jari
  // saat mode senar masih X/0, mode otomatis diubah jadi "Ditekan"
  // supaya kolom jari selalu bisa diisi.
  (function () {
    function sync(s) {
      var mode = document.querySelector('[data-string-mode="' + s + '"]');
      var fret = document.querySelector('[data-string-fret="' + s + '"]');
      var fing = document.querySelector('[data-string-finger="' + s + '"]');
      if (!mode) return;
      var on = mode.value === 'fret';
      [fret, fing].forEach(function (el) {
        if (!el) return;
        el.classList.toggle('field-off', !on);
        el.setAttribute('aria-disabled', on ? 'false' : 'true');
        if (el.tagName === 'INPUT') el.readOnly = !on;
      });
    }

    function forcePressed(s) {
      var mode = document.querySelector('[data-string-mode="' + s + '"]');
      var fret = document.querySelector('[data-string-fret="' + s + '"]');
      if (!mode || mode.value === 'fret') return;
      mode.value = 'fret';
      if (fret && !fret.value) fret.value = '1';
      mode.dispatchEvent(new Event('change', { bubbles: true }));
    }

    for (var s = 1; s <= 6; s++) {
      (function (s) {
        var mode = document.querySelector('[data-string-mode="' + s + '"]');
        var fret = document.querySelector('[data-string-fret="' + s + '"]');
        var fing = document.querySelector('[data-string-finger="' + s + '"]');
        if (mode) mode.addEventListener('change', function () { sync(s); });
        [fret, fing].forEach(function (el) {
          if (!el) return;
          ['mousedown', 'touchstart', 'focus'].forEach(function (ev) {
            el.addEventListener(ev, function () { forcePressed(s); });
          });
        });
        sync(s);
      })(s);
    }
  })();
</script>

<?php layout_foot(); ?>
