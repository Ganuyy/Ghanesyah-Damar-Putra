<?php
/**
 * index.php - Halaman utama / Beranda
 * bisa diakses semua orang (guest, user, admin).
 * isi: penjelasan tentang web + alasan dibuat, penjelasan tiap level,
 * daftar level belajar (popup pilih level + konfirmasi), dan popup
 * login/register untuk tamu yang ingin mulai latihan.
 */
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/chord_svg.php';
require_once __DIR__ . '/inc/layout.php';

$user    = current_user();
$level   = $user['level'] ?? null;
$isAdmin = $user && $user['role'] === 'admin';

// Angka untuk kartu ringkasan
$totalChord = (int) db()->query('SELECT COUNT(*) FROM chords')->fetchColumn();
$totalSong  = (int) db()->query('SELECT COUNT(*) FROM songs')->fetchColumn();
$totalUser  = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();

// Jumlah chord per tingkat kesulitan
$chordByDiff = ['beginner' => 0, 'intermediate' => 0, 'pro' => 0];
foreach (db()->query('SELECT difficulty, COUNT(*) n FROM chords GROUP BY difficulty')->fetchAll() as $r) {
    $chordByDiff[$r['difficulty']] = (int) $r['n'];
}

// Jumlah lagu per kategori
$songByLevel = ['beginner' => 0, 'intermediate' => 0, 'pro' => 0];
foreach (db()->query('SELECT level, COUNT(*) n FROM songs GROUP BY level')->fetchAll() as $r) {
    $songByLevel[$r['level']] = (int) $r['n'];
}

// Chord yang cocok untuk level user (atau beginner untuk guest)
$suggestLevel = $level ?: 'beginner';
$suggested    = array_values(all_chords($suggestLevel));
$suggested    = array_slice($suggested, 0, 8);

// Chord contoh untuk tamu: beberapa chord dari setiap level, dipakai
// pada panel animasi beranda (Beginner -> Intermediate -> Pro).
$guestShowcase = [];
foreach (level_options() as $lv) {
    $guestShowcase[$lv] = array_slice(array_values(all_chords($lv)), 0, 6);
}

// Ringkasan progres kalau user sudah login
$overview = $user ? chord_overview() : null;

$levelInfo = [
    'beginner' => [
        'icon'  => 'bi-1-circle',
        'desc'  => 'Belum pernah pegang gitar? Mulai dari sini. Chord terbuka yang mudah, lagu-lagu sederhana, dan teknik dasar.',
        'poin'  => ['Chord terbuka dasar (C, G, Am, D, Em)', 'Power chord sederhana (C5, E5, G5)', 'Lagu anak, folk, dan pop mudah'],
    ],
    'intermediate' => [
        'icon'  => 'bi-2-circle',
        'desc'  => 'Sudah bisa chord dasar? Waktunya masuk chord barre, chord sevenths, dan lagu yang lebih menantang.',
        'poin'  => ['Chord barre di fret 1-3 (F, Bm)', 'Chord 7 & maj7', 'Progression yang lebih padat'],
    ],
    'pro' => [
        'icon'  => 'bi-3-circle',
        'desc'  => 'Untuk yang sudah konsisten. Barre di fret tinggi, chord jazz, dan repertoar yang lebih teknik.',
        'poin'  => ['Barre di fret tinggi', 'Extended, slash & altered chord', 'Lagu teknik & repertoar luas'],
    ],
];

if ($isAdmin) {
    $totalAdmin    = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    $totalSemuaUser = $totalUser + $totalAdmin;
    $totalAktivitas = (int) db()->query('SELECT COUNT(*) FROM activity_log')->fetchColumn();
    $totalDikuasai = (int) db()->query("SELECT COUNT(*) FROM user_chord_progress WHERE status = 'kuasai'")->fetchColumn();

    $latestChords = db()->query(
        'SELECT id, name, difficulty FROM chords ORDER BY created_at DESC, id DESC LIMIT 5'
    )->fetchAll();
    $latestSongs = db()->query(
        'SELECT id, title, artist, level FROM songs ORDER BY created_at DESC, id DESC LIMIT 5'
    )->fetchAll();

    layout_head('Beranda Admin', 'home');
    ?>

    <section class="hero p-4 p-md-5 mb-4">
      <div class="row align-items-center g-4">
        <div class="col-lg-8">
          <span class="badge bg-warning text-dark mb-3">
            <i class="bi bi-shield-lock me-1"></i>Halo, <?= e($user['username']) ?>!
          </span>
          <h1 class="display-6 mb-3">Beranda <span class="text-warning">Admin</span></h1>
          <p class="lead-dim mb-4">
            Ringkasan info website: jumlah chord, lagu, user, dan aktivitas.
            Kelola konten langsung lewat tombol di bawah.
          </p>
          <div class="d-flex flex-wrap gap-2">
            <a href="<?= e(url('admin/chord-form.php')) ?>" class="btn btn-accent">
              <i class="bi bi-plus-lg me-1"></i>Tambah Chord
            </a>
            <a href="<?= e(url('admin/song-form.php')) ?>" class="btn btn-accent-outline">
              <i class="bi bi-plus-lg me-1"></i>Tambah Lagu
            </a>
            <a href="<?= e(url('admin/index.php')) ?>" class="btn btn-outline-light">
              <i class="bi bi-speedometer2 me-1"></i>Dashboard Lengkap
            </a>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="gl-card p-4 text-center">
            <div class="text-dim small mb-1">TOTAL KONTEN</div>
            <div class="display-5 fw-bold text-warning"><?= $totalChord + $totalSong ?></div>
            <div class="small text-dim"><?= $totalChord ?> chord &middot; <?= $totalSong ?> lagu</div>
          </div>
        </div>
      </div>
    </section>

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
          <div class="stat-label">Total User</div>
          <div class="stat-value"><?= $totalSemuaUser ?></div>
          <a href="<?= e(url('admin/users.php')) ?>" class="small text-warning text-decoration-none">kelola &rarr;</a>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-box h-100">
          <div class="stat-label">Admin</div>
          <div class="stat-value"><?= $totalAdmin ?></div>
          <span class="small text-dim"><?= $totalAktivitas ?> aktivitas tercatat</span>
        </div>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-lg-7">
        <div class="gl-card p-4 h-100">
          <h2 class="h5 mb-3"><i class="bi bi-bar-chart me-2 text-warning"></i>Sebaran Konten per Level</h2>

          <div class="small text-dim mb-2">CHORD</div>
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

          <div class="small text-dim mb-2">LAGU</div>
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

      <div class="col-lg-5">
        <div class="gl-card p-4 h-100">
          <h2 class="h5 mb-3"><i class="bi bi-lightning-charge me-2 text-warning"></i>Aksi Cepat</h2>
          <div class="d-grid gap-2">
            <a href="<?= e(url('admin/chord-form.php')) ?>" class="btn btn-accent">
              <i class="bi bi-plus-lg me-1"></i>Tambah Chord
            </a>
            <a href="<?= e(url('admin/song-form.php')) ?>" class="btn btn-accent">
              <i class="bi bi-plus-lg me-1"></i>Tambah Lagu
            </a>
            <a href="<?= e(url('admin/chords.php')) ?>" class="btn btn-accent-outline">
              <i class="bi bi-diagram-3 me-1"></i>Kelola Chord
            </a>
            <a href="<?= e(url('admin/songs.php')) ?>" class="btn btn-accent-outline">
              <i class="bi bi-music-note-list me-1"></i>Kelola Lagu
            </a>
            <a href="<?= e(url('admin/users.php')) ?>" class="btn btn-outline-secondary">
              <i class="bi bi-people me-1"></i>Kelola User
            </a>
          </div>

          <hr style="border-color:var(--gl-border)">

          <h3 class="h6 mb-3">Info Website</h3>
          <table class="table table-sm table-borderless mb-0">
            <tr><td class="text-dim">Total chord</td><td class="text-end fw-bold"><?= $totalChord ?></td></tr>
            <tr><td class="text-dim">Total lagu</td><td class="text-end fw-bold"><?= $totalSong ?></td></tr>
            <tr><td class="text-dim">User terdaftar</td><td class="text-end fw-bold"><?= $totalSemuaUser ?></td></tr>
            <tr><td class="text-dim">Chord dikuasai user</td><td class="text-end fw-bold"><?= $totalDikuasai ?></td></tr>
          </table>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-6">
        <div class="gl-card p-4 h-100">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0"><i class="bi bi-diagram-3 me-2 text-warning"></i>Chord Terbaru</h2>
            <a href="<?= e(url('admin/chords.php')) ?>" class="small text-warning text-decoration-none">semua &rarr;</a>
          </div>
          <?php if (!$latestChords): ?>
            <p class="text-dim small mb-0">Belum ada chord.</p>
          <?php else: ?>
            <table class="table table-sm table-dark-gl align-middle mb-0">
              <tbody>
                <?php foreach ($latestChords as $c): ?>
                  <tr>
                    <td class="fw-bold text-warning"><?= e($c['name']) ?></td>
                    <td><span class="badge bg-<?= level_color($c['difficulty']) ?>"><?= e(level_label($c['difficulty'])) ?></span></td>
                    <td class="text-end">
                      <a href="<?= e(url('admin/chord-form.php?id=' . $c['id'])) ?>" class="btn btn-sm btn-accent-outline">
                        <i class="bi bi-pencil"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="gl-card p-4 h-100">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0"><i class="bi bi-music-note-list me-2 text-warning"></i>Lagu Terbaru</h2>
            <a href="<?= e(url('admin/songs.php')) ?>" class="small text-warning text-decoration-none">semua &rarr;</a>
          </div>
          <?php if (!$latestSongs): ?>
            <p class="text-dim small mb-0">Belum ada lagu.</p>
          <?php else: ?>
            <table class="table table-sm table-dark-gl align-middle mb-0">
              <tbody>
                <?php foreach ($latestSongs as $s): ?>
                  <tr>
                    <td>
                      <div class="fw-bold"><?= e($s['title']) ?></div>
                      <?php if ($s['artist']): ?><div class="small text-dim"><?= e($s['artist']) ?></div><?php endif; ?>
                    </td>
                    <td><span class="badge bg-<?= level_color($s['level']) ?>"><?= e(level_label($s['level'])) ?></span></td>
                    <td class="text-end">
                      <a href="<?= e(url('admin/song-form.php?id=' . $s['id'])) ?>" class="btn btn-sm btn-accent-outline">
                        <i class="bi bi-pencil"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php
    layout_foot();
    exit;
}

layout_head(null, 'home');
?>

<!-- ============ HERO ============ -->
<section class="hero p-4 p-md-5 mb-4">
  <div class="row align-items-center g-4">
    <div class="col-lg-7">
      <?php if ($user && $user['role'] === 'admin'): ?>
        <span class="badge bg-warning text-dark mb-3">
          <i class="bi bi-shield-lock-fill me-1"></i>Halo, <?= e($user['username']) ?>!
        </span>
        <h1 class="display-6 mb-3">
          Panel Administrator,<br>
          <span class="text-warning">kelola isi GuitarTice</span>
        </h1>
        <p class="lead-dim mb-4">
          Kamu adalah hak akses utama yang mengatur website ini:
          chord, lagu, user, dan statistik semua tersedia di Dashboard Admin.
        </p>
        <div class="d-flex flex-wrap gap-2">
          <a href="<?= e(url('admin/index.php')) ?>" class="btn btn-accent">
            <i class="bi bi-speedometer2 me-1"></i>Dashboard Admin
          </a>
          <a href="<?= e(url('admin/song-form.php')) ?>" class="btn btn-accent-outline">
            <i class="bi bi-plus-lg me-1"></i>Tambah Lagu
          </a>
        </div>
      <?php elseif ($user): ?>
        <span class="badge bg-warning text-dark mb-3">
          <i class="bi bi-person-check me-1"></i>Halo, <?= e($user['username']) ?>!
        </span>
        <h1 class="display-6 mb-3">
          <?= $level ? 'Lanjutkan belajar gitar' : 'Selamat datang di GuitarTice' ?>,<br>
          <span class="text-warning"><?= $level ? e(level_label($level)) . ' Stage' : 'pilih titik mulai kamu' ?></span>
        </h1>
        <p class="lead-dim mb-4">
          <?php if (!$level): ?>
            Kamu belum memilih titik mulai. Tekan <strong>Mulai Belajar</strong> untuk memilih
            level dan mulai perjalananmu.
          <?php elseif ($overview['kuasai'] > 0): ?>
            Kamu sudah menguasai <strong><?= $overview['kuasai'] ?> dari <?= $overview['total'] ?> chord</strong>.
            Terus lanjutkan sampai level <?= e(level_label($level)) ?> selesai!
          <?php else: ?>
            Yuk mulai dari chord pertama di level <strong><?= e(level_label($level)) ?></strong>!
          <?php endif; ?>
        </p>
        <div class="d-flex flex-wrap gap-2">
          <button type="button" class="btn btn-accent" data-open-levels>
            <i class="bi bi-flag me-1"></i><?= $level ? 'Ganti / Lihat Level' : 'Mulai Belajar' ?>
          </button>
          <a href="<?= e(url('practice.php' . ($level ? '?level=' . $level : ''))) ?>" class="btn btn-accent-outline">
            <i class="bi bi-hand-index-thumb me-1"></i>Latihan Chord
          </a>
        </div>
      <?php else: ?>
        <span class="badge bg-warning text-dark mb-3">
          <i class="bi bi-stars me-1"></i>Gratis, Gausah install yang laen
        </span>
        <h1 class="display-5 mb-3">
          Belajar Gitar <span class="text-warning">Santai</span>
        </h1>
        <p class="lead-dim mb-4">
          Chord library lengkap dengan diagram posisi jari, bunyi tiap chord,
          latihan terpandu dari level Beginner sampai Pro, dan progres belajar
          yang otomatis tersimpan di akun kamu.
        </p>
        <div class="d-flex flex-wrap gap-2">
          <button type="button" class="btn btn-accent" data-open-auth>
            <i class="bi bi-hand-index-thumb me-1"></i>Mulai Latihan
          </button>
          <a href="<?= e(url('chords.php')) ?>" class="btn btn-accent-outline">
            <i class="bi bi-diagram-3 me-1"></i>Coba Chord Library
          </a>
        </div>
      <?php endif; ?>
    </div>

    <div class="col-lg-5">
      <?php if ($isAdmin): ?>
        <div class="gl-card p-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-dim small">MENU ADMIN</span>
            <a href="<?= e(url('admin/index.php')) ?>" class="small text-warning text-decoration-none">
              dashboard <i class="bi bi-arrow-right"></i>
            </a>
          </div>
          <h2 class="h6 mb-2">Mau nambah atau edit chord / lagu?</h2>
          <p class="small text-dim mb-3">
            Kelola isi Chord Library dan daftar lagu langsung dari beranda admin.
          </p>
          <div class="d-grid gap-2">
            <a href="<?= e(url('admin/chord-form.php')) ?>" class="btn btn-sm btn-accent">
              <i class="bi bi-plus-lg me-1"></i>Tambah Chord
            </a>
            <a href="<?= e(url('admin/song-form.php')) ?>" class="btn btn-sm btn-accent">
              <i class="bi bi-plus-lg me-1"></i>Tambah Lagu
            </a>
            <div class="d-flex gap-2">
              <a href="<?= e(url('admin/chords.php')) ?>" class="btn btn-sm btn-outline-secondary flex-fill">
                Kelola Chord
              </a>
              <a href="<?= e(url('admin/songs.php')) ?>" class="btn btn-sm btn-outline-secondary flex-fill">
                Kelola Lagu
              </a>
            </div>
          </div>
        </div>
      <?php elseif ($user): ?>
        <div class="gl-card p-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-dim small">CHORD UNTUK LEVEL <?= e(strtoupper(level_label($suggestLevel))) ?></span>
            <a href="<?= e(url('chords.php?level=' . $suggestLevel)) ?>" class="small text-warning text-decoration-none">
              semua <i class="bi bi-arrow-right"></i>
            </a>
          </div>
          <div class="d-flex justify-content-between flex-wrap gap-2">
            <?php foreach ($suggested as $c) : ?>
              <a href="<?= e(url('chord-detail.php?id=' . $c['id'])) ?>" class="text-center text-decoration-none">
                <?= render_chord_svg($c, ['scale' => 0.62, 'show_open' => false]) ?>
                <div class="small fw-bold text-warning"><?= e($c['name']) ?></div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php else: ?>
        <div class="gl-card p-3 chord-showcase" data-chord-showcase>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-dim small">CHORD SETIAP LEVEL</span>
            <span class="badge bg-warning text-dark" data-showcase-badge>Beginner</span>
          </div>
          <p class="small text-dim mb-3">
            Contoh chord di tiap level belajar. Berganti otomatis setiap beberapa detik.
          </p>

          <?php $firstPane = true; foreach (level_options() as $lv): ?>
            <div class="showcase-pane <?= $firstPane ? 'active' : '' ?>"
                 data-showcase-pane
                 data-level-label="<?= e(level_label($lv)) ?>"
                 data-level-url="<?= e(url('chords.php?level=' . $lv)) ?>">
              <div class="d-flex justify-content-between flex-wrap gap-2">
                <?php foreach ($guestShowcase[$lv] as $c): ?>
                  <a href="<?= e(url('chord-detail.php?id=' . $c['id'])) ?>"
                     class="text-center text-decoration-none showcase-chord">
                    <?= render_chord_svg($c, ['scale' => 0.62, 'show_open' => false]) ?>
                    <div class="small fw-bold text-warning"><?= e($c['name']) ?></div>
                  </a>
                <?php endforeach; ?>
              </div>
              <div class="text-end mt-2">
                <a href="<?= e(url('chords.php?level=' . $lv)) ?>"
                   class="small text-warning text-decoration-none">
                  semua chord <?= e(level_label($lv)) ?> <i class="bi bi-arrow-right"></i>
                </a>
              </div>
            </div>
          <?php $firstPane = false; endforeach; ?>

          <div class="showcase-dots d-flex justify-content-center gap-2 mt-2">
            <?php $firstDot = true; foreach (level_options() as $lv): ?>
              <span class="showcase-dot <?= $firstDot ? 'active' : '' ?>"></span>
            <?php $firstDot = false; endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!--=== ANGKA SINGKAT ===-->
<div class="row g-3 mb-4">
  <div class="col-12 col-md-4">
    <div class="stat-box">
      <div class="stat-label">Chord</div>
      <div class="stat-value"><?= $totalChord ?></div>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="stat-box">
      <div class="stat-label">Lagu</div>
      <div class="stat-value"><?= $totalSong ?></div>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="stat-box">
      <div class="stat-label">Level Belajar</div>
      <div class="stat-value">3</div>
      <div class="small text-dim">Beginner · Intermediate · Pro</div>
    </div>
  </div>
</div>

<!-- === TENTANG GUITARTICE ===-->
<section class="gl-card p-4 p-md-5 mb-4" id="tentang">
  <div class="row g-4 align-items-center">
    <div class="col-lg-7">
      <span class="badge bg-accent mb-2">Tentang</span>
      <h2 class="h3 mb-3">Kenapa GuitarTice dibuat?</h2>
      <p class="text-dim">
        GuitarTice lahir dari satu masalah sederhana: belajar gitar untuk pemula
        sering terasa membingungkan. Banyak yang ingin mulai, tetapi tidak tahu
        harus mulai dari chord apa, cara menekan jari yang benar, dan kapan siap
        naik ke tahap berikutnya.
      </p>
      <p class="text-dim mb-0">
        Website ini dibuat sebagai media belajar gitar yang <strong>, gratis,
        dan bisa diakses siapa saja</strong>. Semua chord digambar jelas lengkap dengan
        nomor jari, dilengkapi contoh lagu, dan dibagi menjadi tiga tingkat belajar
        yang berurutan agar kamu naik level di saat yang tepat &mdash; bukan melompat
        terlalu cepat.
      </p>
    </div>
    <div class="col-lg-5">
      <ul class="list-unstyled mb-0 about-list">
        <li><i class="bi bi-diagram-3 text-warning"></i> <span>Diagram posisi jari untuk tiap chord, termasuk barre.</span></li>
        <li><i class="bi bi-volume-up text-warning"></i> <span>Dengarkan bunyi chord dan cek keakuratan permainanmu.</span></li>
        <li><i class="bi bi-signpost-split text-warning"></i> <span>Jalur belajar bertahap: selesaikan satu level, lanjut berikutnya.</span></li>
        <li><i class="bi bi-cloud-check text-warning"></i> <span>Progres tersimpan otomatis di akunmu.</span></li>
      </ul>
    </div>
  </div>
</section>

<!-- ============ TIGA TINGKAT BELAJAR ============ -->
<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
  <div>
    <h2 class="h4 mb-1">Tiga Tingkat Belajar</h2>
    <p class="text-dim mb-0">
      Pilih titik mulai yang paling cocok, lalu naik level <strong>secara berurutan</strong>
      setelah tiap level selesai.
    </p>
  </div>
  <?php if ($user): ?>
    <button type="button" class="btn btn-accent btn-sm" data-open-levels>
      <i class="bi bi-flag me-1"></i>Pilih Level
    </button>
  <?php else: ?>
    <button type="button" class="btn btn-accent btn-sm" data-open-auth>
      <i class="bi bi-box-arrow-in-right me-1"></i>Masuk untuk memilih
    </button>
  <?php endif; ?>
</div>

<div class="row g-3 mb-4">
  <?php foreach (level_options() as $lv):
      $unlocked  = level_is_unlocked($lv);
      $isCurrent = ($level === $lv);
      $complete  = $user ? level_is_complete($lv) : false;
      $mastered  = $user ? level_mastered_count($lv) : 0;
      $total     = level_total_chords($lv);
      $percent   = $user ? level_progress_percent($lv) : 0;
      $songTotal = level_total_songs($lv);
      $songDone  = $user ? level_completed_song_count($lv) : 0;
      $songPct   = $user ? level_song_progress_percent($lv) : 0;
      $stage     = $user ? level_stage($lv) : null;
      $blocker   = ($user && !$unlocked) ? level_blocker($lv) : null;
      $nx        = next_level($lv);
  ?>
    <div class="col-md-4">
      <div class="gl-card h-100 p-4 d-flex flex-column level-card <?= !$unlocked ? 'is-locked' : '' ?>">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <i class="bi <?= $levelInfo[$lv]['icon'] ?> fs-3 text-<?= level_color($lv) ?>"></i>
          <?php if ($isCurrent): ?>
            <span class="badge bg-<?= level_color($lv) ?>">Titik mulai kamu</span>
          <?php elseif ($user && $complete): ?>
            <span class="badge bg-success"><i class="bi bi-check-lg"></i> Selesai</span>
          <?php elseif ($user && !$unlocked): ?>
            <span class="badge bg-secondary"><i class="bi bi-lock-fill me-1"></i>Terkunci</span>
          <?php endif; ?>
        </div>

        <h3 class="h5"><?= e(level_label($lv)) ?> <span class="text-dim small">Level <?= level_index($lv) + 1 ?></span></h3>
        <p class="text-dim small"><?= e($levelInfo[$lv]['desc']) ?></p>

        <ul class="list-unstyled small mb-3 flex-grow-1">
          <?php foreach ($levelInfo[$lv]['poin'] as $poin): ?>
            <li class="mb-1">
              <i class="bi bi-check-circle text-<?= level_color($lv) ?> me-1"></i><?= e($poin) ?>
            </li>
          <?php endforeach; ?>
        </ul>

        <?php if ($user): ?>
          <div class="mb-3">
            <div class="d-flex justify-content-between small text-dim mb-1">
              <span>Progres chord</span>
              <span><?= $mastered ?> / <?= $total ?> chord</span>
            </div>
            <div class="progress progress-thin mb-2">
              <div class="progress-bar bg-<?= $percent >= 100 ? 'success' : 'warning' ?>"
                   style="width: <?= $percent ?>%"></div>
            </div>

            <?php if ($songTotal > 0): ?>
              <div class="d-flex justify-content-between small text-dim mb-1">
                <span>Progres lagu</span>
                <span><?= $songDone ?> / <?= $songTotal ?> lagu</span>
              </div>
              <div class="progress progress-thin">
                <div class="progress-bar bg-<?= $songPct >= 100 ? 'success' : 'info' ?>"
                     style="width: <?= $songPct ?>%"></div>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between border-top pt-2 mb-3" style="border-color:var(--gl-border)!important">
          <span class="small text-dim"><?= $chordByDiff[$lv] ?> chord</span>
          <span class="small text-dim"><?= $songByLevel[$lv] ?> lagu</span>
        </div>

        <?php if ($user): ?>
          <?php if (!$unlocked): ?>
            <button class="btn btn-sm btn-secondary" disabled>
              <i class="bi bi-lock me-1"></i>Terkunci
            </button>
            <p class="small text-dim mt-2 mb-0">
              Selesaikan level <strong><?= e(level_label($blocker)) ?></strong> dulu untuk membukanya.
            </p>
          <?php elseif ($isCurrent && $stage === 'songs'): ?>
            <a href="<?= e(url('songs.php?level=' . $lv)) ?>" class="btn btn-sm btn-accent">
              <i class="bi bi-music-note-list me-1"></i>Belajar Lagu Level Ini
            </a>
            <p class="small text-dim mt-2 mb-0">
              Semua chord sudah dikuasai. Lanjut belajar lagu, lalu tandai lagu sudah bisa.
            </p>
          <?php elseif ($isCurrent): ?>
            <a href="<?= e(url('practice.php?level=' . $lv)) ?>" class="btn btn-sm btn-accent">
              <i class="bi bi-play-fill me-1"></i>Lanjutkan belajar
            </a>
          <?php else: ?>
            <button type="button" class="btn btn-sm btn-accent-outline"
                    data-choose-level="<?= e($lv) ?>"
                    data-label="<?= e(level_label($lv)) ?>"
                    data-unlocked="1"
                    data-next="<?= e($nx ? level_label($nx) : '') ?>">
              <i class="bi bi-flag me-1"></i>Pilih &amp; mulai
            </button>
          <?php endif; ?>
        <?php else: ?>
          <button type="button" class="btn btn-sm btn-accent-outline" data-open-auth>
            <i class="bi bi-box-arrow-in-right me-1"></i>Daftar untuk memilih
          </button>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- ============ FITUR ============ -->
<h2 class="h4 mb-3">Fitur Utama</h2>
<div class="row g-3 mb-4">
  <div class="col-md-6 col-lg-3">
    <div class="gl-card h-100 p-3">
      <i class="bi bi-diagram-3 fs-4 text-warning"></i>
      <h3 class="h6 mt-2">Chord Library</h3>
      <p class="text-dim small mb-0">Diagram posisi jari digambar otomatis untuk tiap chord, termasuk chord barre.</p>
    </div>
  </div>
  <div class="col-md-6 col-lg-3">
    <div class="gl-card h-100 p-3">
      <i class="bi bi-music-note-list fs-4 text-warning"></i>
      <h3 class="h6 mt-2">Lagu + Lirik</h3>
      <p class="text-dim small mb-0">Klik chord di dalam lirik untuk langsung melihat gambar posisi jarinya.</p>
    </div>
  </div>
  <div class="col-md-6 col-lg-3">
    <div class="gl-card h-100 p-3">
      <i class="bi bi-signpost-split fs-4 text-warning"></i>
      <h3 class="h6 mt-2">Jalur Level</h3>
      <p class="text-dim small mb-0">Mulai dari satu level, selesaikan, lalu naik ke level berikutnya secara berurutan.</p>
    </div>
  </div>
  <div class="col-md-6 col-lg-3">
    <div class="gl-card h-100 p-3">
      <i class="bi bi-graph-up-arrow fs-4 text-warning"></i>
      <h3 class="h6 mt-2">Progres</h3>
      <p class="text-dim small mb-0">Persentase chord dan lagu yang dikuasai bisa dilacak kapan saja.</p>
    </div>
  </div>
</div>

<!-- ============ CARA KERJA ============ -->
<h2 class="h4 mb-3">Cara Kerja</h2>
<div class="gl-card p-4 mb-4">
  <div class="row g-4">
    <div class="col-md-4">
      <div class="step-num mb-2">1</div>
      <h3 class="h6">Pilih Titik Mulai</h3>
      <p class="text-dim small mb-0">
        Daftar akun lalu pilih level yang paling cocok: Beginner, Intermediate, atau Pro.
        Pilihanmu menjadi titik awal jalur belajar.
      </p>
    </div>
    <div class="col-md-4">
      <div class="step-num mb-2">2</div>
      <h3 class="h6">Belajar Sampai Selesai</h3>
      <p class="text-dim small mb-0">
        Kuasai semua chord dan lagu di level itu. Bisa latihan sebagai tamu,
        tapi progres hanya tersimpan kalau punya akun.
      </p>
    </div>
    <div class="col-md-4">
      <div class="step-num mb-2">3</div>
      <h3 class="h6">Naik Level Berurutan</h3>
      <p class="text-dim small mb-0">
        Setelah satu level selesai, level berikutnya terbuka. Lanjut terus sampai
        kamu menuntaskan level Pro.
      </p>
    </div>
  </div>
</div>

<?php if ($user): ?>
<!-- ============ MODAL: PILIH LEVEL ============ -->
<div class="modal fade" id="levelModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content gl-modal">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-flag me-2"></i>Pilih Level Belajar</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <p class="text-dim small">
          Pilih titik mulai belajarmu. Setelah satu level <strong>selesai</strong>,
          kamu akan naik ke level berikutnya secara berurutan.
        </p>
        <div class="row g-3">
          <?php foreach (level_options() as $lv):
              $unlocked = level_is_unlocked($lv);
              $isCurrent = ($level === $lv);
              $blocker  = ($user && !$unlocked) ? level_blocker($lv) : null;
              $nx       = next_level($lv);
          ?>
            <div class="col-md-4">
              <div class="level-choice h-100 p-3 <?= !$unlocked ? 'is-locked' : '' ?>">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <i class="bi <?= $levelInfo[$lv]['icon'] ?> fs-4 text-<?= level_color($lv) ?>"></i>
                  <?php if ($isCurrent): ?>
                    <span class="badge bg-<?= level_color($lv) ?>">Titik mulai</span>
                  <?php elseif (!$unlocked): ?>
                    <span class="badge bg-secondary"><i class="bi bi-lock-fill"></i></span>
                  <?php endif; ?>
                </div>
                <h3 class="h6 mb-1"><?= e(level_label($lv)) ?></h3>
                <p class="text-dim small mb-2"><?= $chordByDiff[$lv] ?> chord &middot; <?= $songByLevel[$lv] ?> lagu</p>

                <?php if (!$unlocked): ?>
                  <button class="btn btn-sm btn-secondary w-100" disabled>
                    <i class="bi bi-lock me-1"></i>Terkunci
                  </button>
                  <p class="small text-dim mt-2 mb-0">Selesaikan <?= e(level_label($blocker)) ?> dulu.</p>
                <?php else: ?>
                  <button type="button" class="btn btn-sm btn-accent w-100"
                          data-choose-level="<?= e($lv) ?>"
                          data-label="<?= e(level_label($lv)) ?>"
                          data-unlocked="1"
                          data-next="<?= e($nx ? level_label($nx) : '') ?>">
                    <?= $isCurrent ? 'Pilih ulang' : 'Pilih level ini' ?>
                  </button>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ============ MODAL: KONFIRMASI LEVEL ============ -->
<div class="modal fade" id="levelConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content gl-modal">
      <div class="modal-body text-center p-4">
        <i class="bi bi-patch-question fs-1 text-warning"></i>
        <h5 class="mt-2 mb-2">Yakin mulai dari level <span id="levelConfirmName" class="text-warning">—</span>?</h5>
        <p class="text-dim small">
          Kamu akan belajar di level ini sampai <strong>selesai</strong> &mdash;
          setelah itu barulah kamu naik ke level berikutnya
          (<span id="levelConfirmNext" class="text-warning">—</span>) secara berurutan.
          Pilihan ini masih bisa diganti nanti.
        </p>

        <form id="levelConfirmForm" method="post" action="<?= e(url('level.php')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="level" id="levelConfirmInput" value="">
          <button type="submit" class="btn btn-accent w-100">
            <i class="bi bi-check-lg me-1"></i>Ya, mulai belajar
          </button>
        </form>
        <button type="button" class="btn btn-outline-secondary w-100 mt-2" data-bs-dismiss="modal">
          Pilih ulang
        </button>
      </div>
    </div>
  </div>
</div>
<?php else: ?>
<!-- ============ MODAL: LOGIN / REGISTRASI (GUEST) ============ -->
<div class="modal fade" id="authModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content gl-modal">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-person-lock me-2"></i>Ingin mulai latihan?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <p class="text-dim small">
          Login atau daftar dulu supaya <strong>progres latihanmu tersimpan</strong>.
          Tersimpan berarti kamu bisa melanjutkan level dari mana pun, kapan pun.
        </p>

        <a href="<?= e(url('login.php')) ?>" class="btn btn-accent w-100 mb-2">
          <i class="bi bi-box-arrow-in-right me-1"></i>Login
        </a>
        <a href="<?= e(url('register.php')) ?>" class="btn btn-accent-outline w-100 mb-3">
          <i class="bi bi-person-plus me-1"></i>Daftar Gratis
        </a>

        <hr style="border-color:var(--gl-border)">

        <a href="<?= e(url('practice.php')) ?>" class="btn btn-outline-light w-100">
          <i class="bi bi-hand-index-thumb me-1"></i>Tetap latihan sebagai tamu
        </a>
        <p class="small text-dim text-center mt-2 mb-0">
          <i class="bi bi-exclamation-triangle me-1"></i>Progres kamu <strong>tidak akan tersimpan</strong>.
        </p>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php layout_foot(); ?>
