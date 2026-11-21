<?php
/**
 * config.php
 * Konfigurasi global + koneksi database (PDO)
 * File ini di-load pertama kali oleh semua halaman.
 */

// ---------- Error reporting ----------
// Saat di-upload ke server, ubah menjadi:
//   error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
//   ini_set('display_errors', '0');
error_reporting(E_ALL);
ini_set('display_errors', '1');

/**
 * BASE_HELPER()
 * Mengembalikan URL dasar project secara otomatis, supaya semua link
 * tetap jalan walau project dipindah ke folder / server lain.
 *
 *   C:\laragon\www\guitarlearn            -> ''                (http://guitarlearn.test)
 *   C:\laragon\www\project\guitarlearn    -> '/project/guitarlearn'
 *   .../guitarlearn/admin/index.php       -> '' (deteksi /admin)
 *
 * Dijalankan sekali lalu disimpan di variabel statis.
 */
function BASE_HELPER()
{
    static $base = null;

    if ($base !== null) {
        return $base;
    }

    // Saat dijalankan lewat CLI (mis. php -l) tidak ada SCRIPT_NAME
    if (PHP_SAPI === 'cli') {
        return $base = '';
    }

    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

    // Halaman di dalam folder /admin satu level lebih dalam
    if (substr($dir, -6) === '/admin') {
        $dir = substr($dir, 0, -6);
    }

    return $base = rtrim($dir, '/');
}

// ---------- Identitas aplikasi ----------
define('APP_NAME',   'GuitarTice');
define('APP_ROOT',   dirname(__DIR__));                    // folder utama project
define('BASE_URL',   BASE_HELPER());                      // '' atau '/subfolder'
define('APP_ASSETS', BASE_URL . '/assets');

// ---------- Folder lagu asli (audio) ----------
// File di-upload admin ke folder ini dan disajikan lewat URL /uploads/songs.
define('SONG_AUDIO_DIR', APP_ROOT . '/uploads/songs');
define('SONG_AUDIO_URL', BASE_URL . '/uploads/songs');

// ---------- Database (setting Laragon default: root / tanpa password) ----------
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'db_guitarlearn');
define('DB_USER', 'root');
define('DB_PASS', '');

// ---------- Zona waktu ----------
date_default_timezone_set('Asia/Jakarta');

// ---------- Session ----------
// Saat file ini dijalankan lewat CLI (mis. untuk cek error), session
// tidak perlu diaktifkan.
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_URL === '' ? '/' : BASE_URL . '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * db() : koneksi PDO (dipakai hanya sekali per request)
 * @return PDO
 */
function db()
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT
             . ';dbname=' . DB_NAME . ';charset=utf8mb4';

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(
                '<div style="font-family:sans-serif;padding:24px">'
                . '<h2>Koneksi database gagal</h2>'
                . '<p>Pastikan MySQL sudah berjalan di Laragon, lalu import file '
                . '<code>sql/01_schema.sql</code> melalui phpMyAdmin.</p>'
                . '<p><small>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</small></p>'
                . '</div>'
            );
        }
    }

    return $pdo;
}