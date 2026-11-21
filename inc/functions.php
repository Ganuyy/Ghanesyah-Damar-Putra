<?php
/**
 * functions.php
 * Kumpulan fungsi bantu: output aman, session/flash, autentikasi,
 * CSRF, ambil data chord, dan menghitung progres user.
 */

// ==========================================================
//  A. OUTPUT & URL
// ==========================================================

/** Escape output supaya aman dari XSS. Pemakaian: <?= e($teks) ?> */
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** URL absolut ke halaman project, contoh: url('songs.php') */
function url($path = '')
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** URL file statis (css, js, gambar) */
function asset($path)
{
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

/** Pindah halaman lalu hentikan script */
function redirect($path)
{
    if (strpos($path, 'http') === 0) {
        $loc = $path;
    } elseif (BASE_URL !== '' && strpos($path, BASE_URL . '/') === 0) {
        // Path sudah mengandung BASE_URL (mis. nilai "back" dari form),
        // jangan ditambah base lagi supaya tidak jadi /project/project/...
        $loc = $path;
    } else {
        $loc = url($path);
    }

    header('Location: ' . $loc);
    exit;
}

/** Ambil nilai dari $_GET */
function q($key, $default = '')
{
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}

/** Ambil nilai dari $_POST */
function p($key, $default = '')
{
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
}

// ==========================================================
//  B. FLASH MESSAGE (pesan singkat antar halaman)
// ==========================================================

function flash_set($type, $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_take()
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

function flash_show()
{
    $f = flash_take();
    if (!$f) {
        return '';
    }

    $map = [
        'success' => ['alert-success', 'Berhasil'],
        'error'   => ['alert-danger',  'Gagal'],
        'warning' => ['alert-warning', 'Perhatian'],
        'info'    => ['alert-info',    'Info'],
    ];
    list($class, $title) = $map[$f['type']] ?? $map['info'];

    return '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">'
         . '<strong>' . e($title) . ':</strong> ' . e($f['message'])
         . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>'
         . '</div>';
}

// ==========================================================
//  C. AUTENTIKASI & HAK AKSES
// ==========================================================

/**
 * Ambil data user yang sedang login (dari session).
 * @return array|null
 */
function current_user()
{
    static $cache = null;

    if (!is_logged_in()) {
        return null;
    }

    if ($cache === null) {
        $st = db()->prepare(
            'SELECT id, username, email, fullname, level, role, created_at
               FROM users WHERE id = ? LIMIT 1'
        );
        $st->execute([$_SESSION['user_id']]);
        $cache = $st->fetch() ?: null;

        // Akun dihapus admin saat user sedang login -> paksa logout
        if (!$cache) {
            logout_user();
            return null;
        }
    }

    return $cache;
}

function is_logged_in()
{
    return !empty($_SESSION['user_id']);
}

function is_admin()
{
    $u = current_user();
    return $u !== null && $u['role'] === 'admin';
}

/** Admin otomatis dianggap sudah "memilih level" */
function user_level()
{
    $u = current_user();
    return $u['level'] ?? null;
}

/**
 * Halaman khusus user yang sudah login.
 * Guest diarahkan ke halaman login, sambil mengingat halaman tujuan.
 */
function require_login()
{
    if (!is_logged_in()) {
        $next = $_SERVER['REQUEST_URI'] ?? '';
        flash_set('info', 'Silakan login dulu agar progress kamu tersimpan.');
        redirect('login.php?next=' . urlencode($next));
    }
}

/** Halaman khusus admin */
function require_admin()
{
    if (!is_logged_in()) {
        flash_set('info', 'Silakan login dulu.');
        redirect('login.php');
    }
    if (!is_admin()) {
        http_response_code(403);
        exit('Akses ditolak. Halaman ini hanya untuk admin.');
    }
}

/** Proses login: cek password, set session, catat aktivitas */
function login_user($username, $password)
{
    $st = db()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $st->execute([$username]);
    $user = $st->fetch();

    $valid = $user && password_verify($password, $user['password']);

    // Catat percobaan login (dipakai admin untuk monitoring)
    try {
        db()->prepare('INSERT INTO login_attempt (username, ip, success) VALUES (?, ?, ?)')
           ->execute([$username, client_ip(), $valid ? 1 : 0]);
    } catch (Throwable $e) {
        // abaikan, hanya pelengkap
    }

    if (!$valid) {
        return false;
    }
    if ((int) $user['is_active'] !== 1) {
        flash_set('error', 'Akun kamu dinonaktifkan oleh admin.');
        return false;
    }

    // Cegah session fixation
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];

    log_activity('login', 'Login sebagai ' . $user['username']);
    return true;
}

function logout_user()
{
    if (is_logged_in()) {
        log_activity('logout', 'Logout dari akun ' . ($_SESSION['username'] ?? ''));
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function client_ip()
{
    return substr($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', 0, 45);
}

/** Catat riwayat aktivitas user */
function log_activity($action, $detail = null)
{
    if (!is_logged_in()) {
        return;
    }
    db()->prepare(
        'INSERT INTO activity_log (user_id, action, detail, ip) VALUES (?, ?, ?, ?)'
    )->execute([$_SESSION['user_id'], $action, $detail, client_ip()]);
}

// ==========================================================
//  D. CSRF TOKEN (formulir admin / ubah profil)
// ==========================================================

function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Cetak input hidden token, wajib ada di dalam setiap <form> POST */
function csrf_field()
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Validasi token, menutup form dengan exit() bila tidak cocok */
function csrf_check()
{
    if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) {
        flash_set('error', 'Sesi tidak valid. Silakan coba lagi.');
        redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
    }
}

// ==========================================================
//  E. LEVEL BELAJAR
// ==========================================================

function level_label($level)
{
    return [
        'beginner'     => 'Beginner',
        'intermediate' => 'Intermediate',
        'pro'          => 'Pro',
    ][$level] ?? 'Belum dipilih';
}

function level_color($level)
{
    return [
        'beginner'     => 'success',
        'intermediate' => 'warning',
        'pro'          => 'danger',
    ][$level] ?? 'secondary';
}

function level_options()
{
    return ['beginner', 'intermediate', 'pro'];
}

// ---------------- Progres & penguncian level ----------------

/**
 * Jumlah chord per tingkat kesulitan (di-cache sekali per request).
 * @return array<string,int> level => jumlah
 */
function chord_count_by_level()
{
    static $counts = null;

    if ($counts === null) {
        $counts = ['beginner' => 0, 'intermediate' => 0, 'pro' => 0];
        foreach (db()->query('SELECT difficulty, COUNT(*) n FROM chords GROUP BY difficulty')->fetchAll() as $r) {
            $counts[$r['difficulty']] = (int) $r['n'];
        }
    }

    return $counts;
}

/** Total chord pada satu level */
function level_total_chords($level)
{
    return chord_count_by_level()[$level] ?? 0;
}

/**
 * Jumlah lagu pada satu level (di-cache sekali per request).
 * @return array<string,int>
 */
function song_count_by_level()
{
    static $counts = null;

    if ($counts === null) {
        $counts = ['beginner' => 0, 'intermediate' => 0, 'pro' => 0];
        foreach (db()->query('SELECT level, COUNT(*) n FROM songs GROUP BY level')->fetchAll() as $r) {
            $counts[$r['level']] = (int) $r['n'];
        }
    }

    return $counts;
}

/** Total lagu pada satu level */
function level_total_songs($level)
{
    return song_count_by_level()[$level] ?? 0;
}

/**
 * Jumlah chord pada satu level yang sudah dikuasai user yang login.
 * Guest selalu 0 (progres tamu tidak tersimpan).
 */
function level_mastered_count($level)
{
    if (!is_logged_in()) {
        return 0;
    }

    $progress = user_chord_progress();
    $n = 0;
    foreach (all_chords($level) as $c) {
        if (($progress[$c['id']]['status'] ?? '') === 'kuasai') {
            $n++;
        }
    }

    return $n;
}

/** Level dianggap selesai bila semua chord DAN semua lagu level itu selesai */
function level_is_complete($level)
{
    $total = level_total_chords($level);
    if ($total <= 0 || level_mastered_count($level) < $total) {
        return false;
    }

    return level_songs_complete($level);
}

/**
 * Jumlah lagu pada satu level yang sudah ditandai "sudah bisa" oleh user.
 */
function level_completed_song_count($level)
{
    if (!is_logged_in()) {
        return 0;
    }

    $st = db()->prepare(
        'SELECT COUNT(*)
           FROM user_song_progress usp
           JOIN songs s ON s.id = usp.song_id
          WHERE usp.user_id = ? AND s.level = ? AND usp.is_completed = 1'
    );
    $st->execute([$_SESSION['user_id'], $level]);

    return (int) $st->fetchColumn();
}

/** Semua lagu pada level sudah selesai (level tanpa lagu otomatis dianggap selesai) */
function level_songs_complete($level)
{
    $total = level_total_songs($level);
    return $total <= 0 || level_completed_song_count($level) >= $total;
}

/** Persentase lagu yang sudah ditandai selesai pada satu level */
function level_song_progress_percent($level)
{
    $total = level_total_songs($level);
    if ($total <= 0) {
        return 0;
    }
    return (int) round(level_completed_song_count($level) / $total * 100);
}

/**
 * Tahap belajar pada satu level:
 *   'chords' = masih menguasai chord
 *   'songs'  = chord selesai, lanjut belajar lagu
 *   'done'   = chord dan lagu selesai, siap naik level
 */
function level_stage($level)
{
    $total = level_total_chords($level);
    if ($total <= 0 || level_mastered_count($level) < $total) {
        return 'chords';
    }
    if (!level_songs_complete($level)) {
        return 'songs';
    }
    return 'done';
}

/** Persentase chord yang sudah dikuasai pada satu level */
function level_progress_percent($level)
{
    $total = level_total_chords($level);
    if ($total <= 0) {
        return 0;
    }
    return (int) round(level_mastered_count($level) / $total * 100);
}

/** Level aktif (titik mulai) user yang login, atau null */
function current_level()
{
    $u = current_user();
    return $u['level'] ?? null;
}

/** Posisi level dalam urutan (0-based). -1 kalau tidak dikenal */
function level_index($level)
{
    $i = array_search($level, level_options(), true);
    return $i === false ? -1 : (int) $i;
}

/**
 * Aturan penguncian level:
 *   - user belum punya level -> semua level bebas dipilih (titik mulai).
 *   - level <= level aktif  -> bebas (boleh turun / pilih ulang).
 *   - level >  level aktif  -> hanya terbuka bila level-level sebelumnya
 *                              (dari level aktif sampai tepat sebelum target)
 *                              sudah selesai, sehingga naik level berurutan.
 */
function level_is_unlocked($level, $current = null)
{
    $order = level_options();
    $i = array_search($level, $order, true);
    if ($i === false) {
        return false;
    }

    $cur = func_num_args() > 1 ? $current : current_level();
    if (!$cur) {
        return true;
    }

    $ci = array_search($cur, $order, true);
    if ($ci === false) {
        return true;
    }

    if ($i <= $ci) {
        return true;
    }

    for ($k = $ci; $k < $i; $k++) {
        if (!level_is_complete($order[$k])) {
            return false;
        }
    }

    return true;
}

/** Level berikutnya dalam urutan, atau null bila sudah paling tinggi */
function next_level($level)
{
    $order = level_options();
    $i = array_search($level, $order, true);
    if ($i === false || $i >= count($order) - 1) {
        return null;
    }
    return $order[$i + 1];
}

/** Level sebelumnya dalam urutan, atau null bila paling dasar */
function prev_level($level)
{
    $order = level_options();
    $i = array_search($level, $order, true);
    if ($i === false || $i <= 0) {
        return null;
    }
    return $order[$i - 1];
}

/**
 * Level yang menahan (menghalangi) sebuah level: level pertama yang belum
 * selesai mulai dari level aktif sampai tepat sebelum target.
 * @return string|null
 */
function level_blocker($level)
{
    $order = level_options();
    $ti = array_search($level, $order, true);
    $cur = current_level();
    $ci = $cur ? array_search($cur, $order, true) : 0;

    if ($ti === false || $ci === false) {
        return null;
    }

    for ($k = $ci; $k < $ti; $k++) {
        if (!level_is_complete($order[$k])) {
            return $order[$k];
        }
    }

    return null;
}

// ==========================================================
//  F. AMBIL DATA CHORD
// ==========================================================

/**
 * Semua chord + posisi jarinya, dikelompokkan per chord.
 * Hanya 2 query, dipakai untuk daftar chord, filter chord di lagu, dll.
 * @return array<int, array>  array chord => ['id'=>..,'name'=>..,'fingering'=>[string_num=>['fret'=>,'finger'=>]]]
 */
function all_chords($difficulty = null)
{
    static $cache = null;

    if ($cache === null) {
        $cache = [];

        foreach (db()->query('SELECT * FROM chords ORDER BY name')->fetchAll() as $c) {
            $c['fingering'] = [];
            $cache[$c['id']] = $c;
        }

        foreach (db()->query('SELECT chord_id, string_num, fret, finger
                                FROM chord_fingering ORDER BY chord_id, string_num')->fetchAll() as $f) {
            $cache[$f['chord_id']]['fingering'][$f['string_num']] = [
                'fret'  => (int) $f['fret'],
                'finger' => $f['finger'] === null ? null : (int) $f['finger'],
            ];
        }
    }

    if ($difficulty === null) {
        return $cache;
    }

    return array_filter($cache, fn($c) => $c['difficulty'] === $difficulty);
}

/**
 * Ambil 1 chord beserta posisi jarinya.
 * @return array|null
 */
function get_chord($id)
{
    $id = (int) $id;
    $all = all_chords();
    return $all[$id] ?? null;
}

/** Cari chord berdasarkan nama (mis. "Am"), dipakai saat parse lirik */
function chord_names_list()
{
    return array_column(all_chords(), 'name');
}

// ---------------- Nada penyusun chord (untuk bunyi & cek suara) ----------------

/**
 * Nada MIDI senar terbuka, standar tuning EADGBE (senar 6 -> 1).
 * @return array<int,int> string_num => midi
 */
function guitar_open_midi()
{
    // E2=40, A2=45, D3=50, G3=55, B3=59, E4=64
    return [6 => 40, 5 => 45, 4 => 50, 3 => 55, 2 => 59, 1 => 64];
}

/**
 * Daftar nada MIDI yang dibunyikan sebuah chord (senar yang di-mute dilewati),
 * diurutkan dari nada terendah.
 * @return int[]
 */
function chord_midi_notes($chord)
{
    $open = guitar_open_midi();
    $notes = [];

    foreach (($chord['fingering'] ?? []) as $s => $f) {
        $s = (int) $s;
        if (!isset($open[$s])) {
            continue;
        }
        $notes[] = $open[$s] + (int) $f['fret'];
    }

    sort($notes);
    return $notes;
}

/** Frekuensi (Hz) nada MIDI */
function midi_to_freq($midi)
{
    return round(440 * pow(2, ($midi - 69) / 12), 2);
}

/**
 * Kelas nada (pitch class 0..11, 0 = C) yang menyusun chord.
 * Dipakai untuk mencocokkan hasil deteksi suara.
 * @return int[]
 */
function chord_pitch_classes($chord)
{
    $pcs = [];
    foreach (chord_midi_notes($chord) as $m) {
        $pcs[$m % 12] = true;
    }
    $out = array_keys($pcs);
    sort($out);
    return $out;
}

/**
 * Panel HTML "Cek Keakuratan Suara" + tombol dengarkan.
 * Dipakai di halaman Latihan dan detail chord.
 */
function sound_panel($chord, $opts = [])
{
    $id    = (int) $chord['id'];
    $name  = $chord['name'];
    $pcsArr = chord_pitch_classes($chord);
    $pcs   = implode(',', $pcsArr);
    $notes = implode(',', chord_midi_notes($chord));
    $title = $opts['title'] ?? ('Dengarkan & Cek Keakuratan Chord ' . $name);
    $noteNames = array_map('midi_note_name', $pcsArr);

    ob_start();
    ?>
    <div class="gl-card p-3 sound-panel"
         data-sound-panel
         data-chord-id="<?= $id ?>"
         data-chord-name="<?= e($name) ?>"
         data-pitch-classes="<?= e($pcs) ?>"
         data-notes="<?= e($notes) ?>">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <h3 class="h6 mb-0"><i class="bi bi-mic-fill text-warning me-1"></i><?= e($title) ?></h3>
        <button type="button" class="btn btn-sm btn-accent-outline"
                data-play-chord data-notes="<?= e($notes) ?>">
          <i class="bi bi-volume-up me-1"></i>Dengarkan contoh <?= e($name) ?>
        </button>
      </div>

      <p class="small text-dim mb-2">
        Panel ini <strong>hanya mengecek chord <span class="text-warning"><?= e($name) ?></span></strong>
        yang sedang kamu lihat. Petik chord tersebut, lalu tekan <em>Mulai Cek</em>.
      </p>

      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <div class="small text-dim">
          Nada penyusun:
          <strong class="text-warning"><?= e(implode(' - ', $noteNames)) ?></strong>
        </div>
        <div class="small text-dim">
          Terdengar: <span class="text-warning" data-sound-detected>-</span>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 mb-2">
        <button type="button" class="btn btn-sm btn-accent" data-sound-start>
          <i class="bi bi-play-fill me-1"></i>Mulai Cek
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary d-none" data-sound-stop>
          <i class="bi bi-stop-fill me-1"></i>Berhenti
        </button>
      </div>

      <div class="sound-notes mb-2" data-sound-notes>
        <?php foreach ($pcsArr as $pc): ?>
          <span class="sound-note" data-pc="<?= $pc ?>"
                data-note="<?= e(midi_note_name($pc)) ?>"><?= e(midi_note_name($pc)) ?></span>
        <?php endforeach; ?>
      </div>

      <div class="progress progress-thin mb-2">
        <div class="progress-bar bg-warning" data-sound-bar style="width:0%"></div>
      </div>

      <div class="small text-dim" data-sound-msg>
        <i class="bi bi-info-circle me-1"></i>Mikrofon akan diminta izin saat pertama kali dipakai.
      </div>
    </div>
    <?php
    return ob_get_clean();
}

/** Nama nada dari pitch class (0 = C) */
function midi_note_name($pc)
{
    $names = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];
    $pc = ((int) $pc) % 12;
    return $names[($pc + 12) % 12];
}

// ==========================================================
//  G. PROGRES USER
// ==========================================================

/**
 * Status progres chord milik user.
 * Guest = array kosong (tidak ada yang bisa disimpan).
 *
 * @param bool $refresh  true untuk memuat ulang dari database
 * @return array<int, array{status:string, practice_count:int, last_practiced_at:?string}>  chord_id => data
 */
function user_chord_progress($refresh = false)
{
    static $cache = null;

    if (!is_logged_in()) {
        return [];
    }
    if ($cache === null || $refresh) {
        $cache = [];
        $st = db()->prepare(
            'SELECT chord_id, status, practice_count, last_practiced_at
               FROM user_chord_progress WHERE user_id = ?'
        );
        $st->execute([$_SESSION['user_id']]);
        foreach ($st->fetchAll() as $r) {
            $cache[(int) $r['chord_id']] = [
                'status'         => $r['status'],
                'practice_count' => (int) $r['practice_count'],
                'last_practiced_at' => $r['last_practiced_at'],
            ];
        }
    }

    return $cache;
}

/**
 * Ubah status satu chord milik user yang login.
 * Status selalu mengikuti aksi terakhir user, termasuk saat user
 * mengulang latihan ('latih') atau mengulang dari awal ('belum').
 * Guest: tidak melakukan apa-apa dan mengembalikan false.
 */
function set_chord_status($chordId, $status, $increment = true)
{
    if (!is_logged_in()) {
        return false;
    }

    $sql = "INSERT INTO user_chord_progress
                (user_id, chord_id, status, practice_count, last_practiced_at)
            VALUES (?, ?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                practice_count = practice_count + " . ($increment ? '1' : '0') . ",
                last_practiced_at = NOW()";

    db()->prepare($sql)->execute([$_SESSION['user_id'], (int) $chordId, $status]);

    return true;
}

/**
 * Hitung ulang progres satu lagu untuk user yang login.
 * Persentase = chord unik di lagu yang sudah dikuasai / total chord lagu.
 * 'completed' terpisah: ditandai manual oleh user lewat tombol
 * "Sudah Bisa Lagu Ini" dan dipakai untuk syarat naik level.
 *
 * @return array{total:int, mastered:int, percent:int, status:string, completed:bool}
 */
function song_progress($songId)
{
    $songId = (int) $songId;

    $total = (int) db()->query(
        'SELECT COUNT(*) FROM song_chords WHERE song_id = ' . $songId
    )->fetchColumn();

    $mastered = 0;
    $completed = false;
    if (is_logged_in()) {
        $st = db()->prepare(
            'SELECT COUNT(*)
               FROM song_chords sc
               LEFT JOIN user_chord_progress ucp
                      ON ucp.chord_id = sc.chord_id
                     AND ucp.user_id  = ?
                     AND ucp.status   = \'kuasai\'
              WHERE sc.song_id = ?'
        );
        $st->execute([$_SESSION['user_id'], $songId]);
        $mastered = (int) $st->fetchColumn();

        $st = db()->prepare(
            'SELECT is_completed FROM user_song_progress
              WHERE user_id = ? AND song_id = ? LIMIT 1'
        );
        $st->execute([$_SESSION['user_id'], $songId]);
        $completed = (bool) $st->fetchColumn();
    }

    $percent = $total > 0 ? (int) round($mastered / $total * 100) : 0;

    $status = 'belum';
    if ($total > 0 && $mastered === $total) {
        $status = 'selesai';
    } elseif ($mastered > 0) {
        $status = 'sedang';
    }

    // Simpan ke tabel jika user login (is_completed tidak diubah di sini)
    if (is_logged_in()) {
        db()->prepare(
            'INSERT INTO user_song_progress
                (user_id, song_id, total_chords, mastered_chords, percent, status)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                total_chords    = VALUES(total_chords),
                mastered_chords = VALUES(mastered_chords),
                percent         = VALUES(percent),
                status          = VALUES(status),
                updated_at      = NOW()'
        )->execute([$_SESSION['user_id'], $songId, $total, $mastered, $percent, $status]);
    }

    return compact('total', 'mastered', 'percent', 'status', 'completed');
}

/**
 * Tandai satu lagu sudah bisa dimainkan (atau batalkan tandanya).
 * Syarat untuk menandai selesai: semua chord lagu sudah dikuasai.
 */
function set_song_completed($songId, $done = true)
{
    if (!is_logged_in()) {
        return false;
    }

    $songId = (int) $songId;
    $prog   = song_progress($songId);

    if ($done && ($prog['total'] <= 0 || $prog['mastered'] < $prog['total'])) {
        return false;
    }

    db()->prepare(
        'UPDATE user_song_progress
            SET is_completed = ?, completed_at = ?
          WHERE user_id = ? AND song_id = ?'
    )->execute([
        $done ? 1 : 0,
        $done ? date('Y-m-d H:i:s') : null,
        $_SESSION['user_id'],
        $songId,
    ]);

    return true;
}

/**
 * Urutkan daftar chord: yang terakhir dilatih tampil paling atas,
 * sisanya mengikuti urutan asli. Dipakai di halaman Latihan & Progres.
 */
function sort_chords_by_recent(array $chords, array $progress)
{
    $original = [];
    foreach ($chords as $i => $c) {
        $original[(int) $c['id']] = $i;
    }

    usort($chords, function ($a, $b) use ($progress, $original) {
        $ta = $progress[$a['id']]['last_practiced_at'] ?? null;
        $tb = $progress[$b['id']]['last_practiced_at'] ?? null;

        if ($ta && $tb && $ta !== $tb) {
            return strcmp($tb, $ta);
        }
        if ($ta && !$tb) {
            return -1;
        }
        if ($tb && !$ta) {
            return 1;
        }

        return ($original[(int) $a['id']] ?? 0) <=> ($original[(int) $b['id']] ?? 0);
    });

    return $chords;
}

/** URL publik file lagu asli, kosong bila lagu belum punya audio */
function song_audio_url($file)
{
    $file = trim((string) $file);
    if ($file === '') {
        return '';
    }
    return url('uploads/songs/' . rawurlencode(basename($file)));
}

/** Ringkasan progres chord untuk halaman progress.php */
function chord_overview()
{
    $st = db()->prepare(
        'SELECT ucp.status, COUNT(*) AS jumlah
           FROM user_chord_progress ucp
           JOIN chords c ON c.id = ucp.chord_id
          WHERE ucp.user_id = ?
          GROUP BY ucp.status'
    );
    $st->execute([$_SESSION['user_id']]);

    $out = ['kuasai' => 0, 'latih' => 0, 'total' => (int) db()->query('SELECT COUNT(*) FROM chords')->fetchColumn()];
    foreach ($st->fetchAll() as $r) {
        $out[$r['status']] = (int) $r['jumlah'];
    }
    $out['persen'] = $out['total'] > 0 ? (int) round($out['kuasai'] / $out['total'] * 100) : 0;

    return $out;
}

// ==========================================================
//  H. TAMPILKAN LIRIK + CHIP CHORD
// ==========================================================

/**
 * Ubah teks lirik menjadi HTML.
 * Chord ditulis dalam kurung siku, contoh: [G]Amazing grace
 * Chord yang ada di $knownChords menjadi tombol yang bisa diklik
 * (untuk menampilkan diagram posisi jari).
 * Chord yang ada di $masteredChords diberi class "is-mastered"
 * supaya tampil hijau (khusus user yang sudah menguasainya).
 *
 * @param string $lyrics
 * @param array  $knownChords    daftar nama chord yang valid (opsional)
 * @param array  $masteredChords daftar nama chord yang sudah dikuasai (opsional)
 */
function render_lyrics($lyrics, $knownChords = null, $masteredChords = [])
{
    $html = '';

    foreach (preg_split("/\r\n|\n|\r/", (string) $lyrics) as $line) {
        $trimmed = trim($line);

        // Baris kosong = jeda baris
        if ($trimmed === '') {
            $html .= '<div class="lyric-break"></div>';
            continue;
        }

        $html .= '<div class="lyric-line">';
        $html .= render_lyric_segments($line, $knownChords, $masteredChords);
        $html .= '</div>';
    }

    return $html;
}

/**
 * Pecah satu baris lirik menjadi segmen chord + teks.
 * Chord ditempatkan di atas teks yang mengikutinya, sehingga posisi
 * masuknya chord tetap terlihat.
 */
function render_lyric_segments($line, $knownChords = null, $masteredChords = [])
{
    $segments = [];
    $pattern  = '/\[([^\]]{1,15})\]/';

    if (preg_match_all($pattern, $line, $matches, PREG_OFFSET_CAPTURE)) {
        $count    = count($matches[0]);
        $firstPos = $matches[0][0][1];

        if ($firstPos > 0) {
            $segments[] = ['chord' => null, 'text' => substr($line, 0, $firstPos)];
        }

        for ($i = 0; $i < $count; $i++) {
            $full    = $matches[0][$i][0];
            $pos     = $matches[0][$i][1];
            $name    = trim($matches[1][$i][0]);
            $nextPos = ($i + 1 < $count) ? $matches[0][$i + 1][1] : strlen($line);
            $start   = $pos + strlen($full);

            $segments[] = [
                'chord' => $name,
                'text'  => substr($line, $start, max(0, $nextPos - $start)),
            ];
        }
    } else {
        $segments[] = ['chord' => null, 'text' => $line];
    }

    $html = '';

    foreach ($segments as $segment) {
        $chord = $segment['chord'];
        $text  = $segment['text'];

        $html .= '<span class="lyric-seg">';
        $html .= '<span class="lyric-chord">';

        if ($chord !== null) {
            if ($knownChords !== null && !in_array($chord, $knownChords, true)) {
                $html .= '<span class="chord-plain">' . e($chord) . '</span>';
            } else {
                $isMastered = in_array($chord, $masteredChords, true);
                $html .= '<button type="button" class="chord-chip' . ($isMastered ? ' is-mastered' : '') . '"'
                       . ' data-chord="' . e($chord) . '"'
                       . ($isMastered ? ' data-mastered="1"' : '')
                       . ' aria-label="Lihat posisi jari chord ' . e($chord) . '">' . e($chord) . '</button>';
            }
        }

        $html .= '</span>';
        $html .= '<span class="lyric-text">' . e($text) . '</span>';
        $html .= '</span>';
    }

    return $html;
}

// ==========================================================
//  I. HELPER TAMPILAN KECIL
// ==========================================================

/** Badge level belajar */
function level_badge($level)
{
    if (!$level) {
        return '<span class="badge bg-secondary">Belum pilih level</span>';
    }
    return '<span class="badge bg-' . level_color($level) . '">'
         . e(level_label($level)) . '</span>';
}

function format_date($datetime)
{
    if (!$datetime || $datetime === '0000-00-00 00:00:00') {
        return '-';
    }
    return date('d M Y H:i', strtotime($datetime));
}

/** Persentase jadi badge warna */
function percent_badge($percent)
{
    $color = $percent >= 100 ? 'success' : ($percent >= 50 ? 'warning' : 'secondary');
    return '<span class="badge bg-' . $color . '">' . (int) $percent . '%</span>';
}
