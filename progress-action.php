<?php
/**
 * progress-action.php - Endpoint AJAX untuk menyimpan progres.
 *
 * Dipanggil dari assets/app.js saat user menekan tombol "Sudah Bisa".
 * Semua request harus berformat POST.
 *
 * Guest TIDAK bisa memakai halaman ini: fungsi akan mengembalikan
 * {"ok":false} dengan pesan "belum login" supaya progress tamu
 * benar-benar tidak tersimpan di database.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/functions.php';

/** Balas JSON lalu berhenti */
function json_out(array $data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Hanya POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_out(['ok' => false, 'message' => 'Metode tidak diizinkan.'], 405);
}

$action = p('action');

// Guest ditolak di sini: inilah penjaga "progres tamu tidak tersimpan"
if (!is_logged_in()) {
    json_out([
        'ok'      => false,
        'message' => 'Kamu belum login, jadi progress tidak bisa disimpan.',
    ], 401);
}

switch ($action) {

    // -------------------------------------------------------------
    // Tandai satu chord sudah bisa / belum bisa, lalu hitung ulang
    // progres lagu bila chord itu dipakai di sebuah lagu.
    // -------------------------------------------------------------
    case 'master_chord':
        $chordId = (int) p('chord_id');
        $status  = p('status');
        $songId  = (int) p('song_id');

        if (!in_array($status, ['belum', 'kuasai'], true)) {
            json_out(['ok' => false, 'message' => 'Status tidak valid.'], 400);
        }
        if (!get_chord($chordId)) {
            json_out(['ok' => false, 'message' => 'Chord tidak ditemukan.'], 404);
        }

        set_chord_status($chordId, $status);
        log_activity(
            $status === 'kuasai' ? 'chord_kuasai' : 'chord_belum',
            'Chord ' . get_chord($chordId)['name']
        );

        $chord = get_chord($chordId);
        $saved = user_chord_progress(true);     // muat ulang supaya status terbaru terbaca
        $now   = $saved[$chordId]['status'] ?? $status;

        // Progres lagu: pakai song_id bila dikirim, kalau tidak ambil satu
        // lagu yang memakai chord ini sebagai referensinya
        $songIds = [];
        if ($songId > 0) {
            $songIds[] = $songId;
        } else {
            $find = db()->prepare('SELECT song_id FROM song_chords WHERE chord_id = ? LIMIT 1');
            $find->execute([$chordId]);
            $songIds = array_map('intval', array_column($find->fetchAll(), 'song_id'));
        }

        $progress = ['total' => 0, 'mastered' => 0, 'percent' => 0, 'status' => 'belum'];
        if ($songIds) {
            $progress = song_progress($songIds[0]);
        }

        json_out([
            'ok'        => true,
            'name'      => $chord['name'],
            'status'    => $now,
            'total'     => (int) $progress['total'],
            'mastered'  => (int) $progress['mastered'],
            'percent'   => (int) $progress['percent'],
            'message'   => 'Progres tersimpan.',
        ]);

    // -------------------------------------------------------------
    // Reset semua progres milik user yang sedang login
    // -------------------------------------------------------------
    case 'reset_progress':
        csrf_check();

        db()->prepare('DELETE FROM user_chord_progress WHERE user_id = ?')->execute([$_SESSION['user_id']]);
        db()->prepare('DELETE FROM user_song_progress  WHERE user_id = ?')->execute([$_SESSION['user_id']]);
        log_activity('reset_progress', 'Menghapus semua progres');

        json_out(['ok' => true, 'message' => 'Semua progres berhasil dihapus.']);

    default:
        json_out(['ok' => false, 'message' => 'Aksi tidak dikenal.'], 400);
}