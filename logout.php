<?php
/**
 * logout.php - Keluar dari akun
 */
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/functions.php';

logout_user();

// Session sudah dihapus di atas, jadi mulai sesi baru hanya untuk
// menampung pesan perpisahan sebelum redirect.
session_start();
flash_set('success', 'Kamu sudah logout. Sampai jumpa lagi!');
redirect('index.php');