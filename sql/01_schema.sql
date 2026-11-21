-- =============================================================
--  GUITARTICE - Struktur Database
--  Project : Website Belajar Gitar
--  DB      : db_guitarlearn
--  Cara pakai: buka phpMyAdmin -> Import -> pilih file ini
--
--  PERINGATAN: file ini MENGHAPUS database db_guitarlearn.
--  Hanya jalankan untuk instalasi baru. Untuk database yang sudah
--  berisi data, gunakan sql/03_migration.sql.
-- =============================================================

DROP DATABASE IF EXISTS db_guitarlearn;
CREATE DATABASE db_guitarlearn
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE db_guitarlearn;

-- -------------------------------------------------------------
-- 1. users : data seluruh user (user biasa + admin)
--    level NULL = user belum memilih level belajar
-- -------------------------------------------------------------
CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL,
  email         VARCHAR(100) NOT NULL,
  password      VARCHAR(255) NOT NULL COMMENT 'hasil password_hash()',
  fullname      VARCHAR(100) DEFAULT NULL,
  level         ENUM('beginner','intermediate','pro') DEFAULT NULL,
  role          ENUM('user','admin') NOT NULL DEFAULT 'user',
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 2. chords : data utama chord library
--    start_fret = fret paling atas yang digambar (untuk chord barre)
-- -------------------------------------------------------------
CREATE TABLE chords (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(20)  NOT NULL COMMENT 'mis. C, Am, B7',
  root          VARCHAR(10)  NOT NULL DEFAULT '' COMMENT 'nada dasar: C, G, A...',
  quality       VARCHAR(20)  NOT NULL DEFAULT 'major' COMMENT 'major/minor/7/maj7/sus2/dus4',
  difficulty    ENUM('beginner','intermediate','pro') NOT NULL DEFAULT 'beginner',
  start_fret    TINYINT UNSIGNED NOT NULL DEFAULT 1,
  frets_shown   TINYINT UNSIGNED NOT NULL DEFAULT 4 COMMENT 'jumlah garis fret yang digambar',
  is_barre      TINYINT(1)   NOT NULL DEFAULT 0,
  description   TEXT,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_chords_name (name)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 3. chord_fingering : posisi jari (satu baris = satu senar)
--    string_num : 1 = senar paling kecil (high e)
--                 6 = senar paling tebal  (low E)
--    fret       : 0 = open string, 1..n = fret
--    finger     : 1..5 (jari), NULL = open / tidak ditekan
--    BARRES otomatis: 2 senar berurutan dengan fret + finger
--                     yang sama dianggap satu batang.
-- -------------------------------------------------------------
CREATE TABLE chord_fingering (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  chord_id    INT UNSIGNED  NOT NULL,
  string_num  TINYINT UNSIGNED NOT NULL,
  fret        TINYINT UNSIGNED NOT NULL DEFAULT 0,
  finger      TINYINT UNSIGNED DEFAULT NULL,
  UNIQUE KEY uq_fingering (chord_id, string_num),
  CONSTRAINT fk_fingering_chord
    FOREIGN KEY (chord_id) REFERENCES chords(id) ON DELETE CASCADE,
  CONSTRAINT chk_fingering_string CHECK (string_num BETWEEN 1 AND 6),
  CONSTRAINT chk_fingering_fret   CHECK (fret BETWEEN 0 AND 24),
  CONSTRAINT chk_fingering_finger CHECK (finger IS NULL OR finger BETWEEN 1 AND 5)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 4. songs : daftar lagu
--    lyrics memakai format chord dalam kurung siku, contoh:
--    [G]Amazing grace how sweet the sound
-- -------------------------------------------------------------
CREATE TABLE songs (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title       VARCHAR(150) NOT NULL,
  artist      VARCHAR(150) NOT NULL DEFAULT '',
  level       ENUM('beginner','intermediate','pro') NOT NULL DEFAULT 'beginner',
  capo        VARCHAR(10)  DEFAULT NULL COMMENT 'contoh: 2',
  tempo       VARCHAR(20)  DEFAULT NULL,
  notes       TEXT,
  lyrics      LONGTEXT     NOT NULL,
  audio_file  VARCHAR(255) DEFAULT NULL COMMENT 'nama file lagu asli di folder uploads/songs',
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_songs_level (level)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 5. song_chords : chord yang dipakai sebuah lagu
-- -------------------------------------------------------------
CREATE TABLE song_chords (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  song_id     INT UNSIGNED NOT NULL,
  chord_id    INT UNSIGNED NOT NULL,
  sort_order  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY uq_song_chord (song_id, chord_id),
  KEY idx_sc_song (song_id),
  CONSTRAINT fk_sc_song  FOREIGN KEY (song_id)  REFERENCES songs(id)  ON DELETE CASCADE,
  CONSTRAINT fk_sc_chord FOREIGN KEY (chord_id) REFERENCES chords(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 6. user_chord_progress : progres latihan satu chord
--    status: belum -> latih -> kuasai
-- -------------------------------------------------------------
CREATE TABLE user_chord_progress (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id           INT UNSIGNED NOT NULL,
  chord_id          INT UNSIGNED NOT NULL,
  status            ENUM('belum','latih','kuasai') NOT NULL DEFAULT 'belum',
  practice_count    INT UNSIGNED NOT NULL DEFAULT 0,
  last_practiced_at DATETIME     DEFAULT NULL,
  UNIQUE KEY uq_user_chord (user_id, chord_id),
  CONSTRAINT fk_ucp_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
  CONSTRAINT fk_ucp_chord FOREIGN KEY (chord_id) REFERENCES chords(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 7. user_song_progress : progres belajar satu lagu (dihitung ulang)
--    status: belum -> sedang -> selesai
-- -------------------------------------------------------------
CREATE TABLE user_song_progress (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id        INT UNSIGNED NOT NULL,
  song_id        INT UNSIGNED NOT NULL,
  total_chords   INT UNSIGNED NOT NULL DEFAULT 0,
  mastered_chords INT UNSIGNED NOT NULL DEFAULT 0,
  percent        TINYINT UNSIGNED NOT NULL DEFAULT 0,
  status         ENUM('belum','sedang','selesai') NOT NULL DEFAULT 'belum',
  is_completed   TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'ditandai user sudah bisa memainkan lagu',
  completed_at   DATETIME     DEFAULT NULL,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_song (user_id, song_id),
  CONSTRAINT fk_usp_user  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_usp_song  FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 8. activity_log : riwayat aktivitas (sumber halaman statistik admin)
-- -------------------------------------------------------------
CREATE TABLE activity_log (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED DEFAULT NULL,
  action     VARCHAR(50)  NOT NULL COMMENT 'mis. login, chord_kuasai, lagu_mulai',
  detail     VARCHAR(255) DEFAULT NULL,
  ip         VARCHAR(45)  DEFAULT NULL,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_log_user (user_id),
  KEY idx_log_created (created_at),
  CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 9. settings : pengaturan situs
-- -------------------------------------------------------------
CREATE TABLE settings (
  setting_key   VARCHAR(50) PRIMARY KEY,
  setting_value TEXT
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 10. login_attempt : riwayat percobaan login (keamanan)
-- -------------------------------------------------------------
CREATE TABLE login_attempt (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username   VARCHAR(50) NOT NULL,
  ip         VARCHAR(45)  DEFAULT NULL,
  success    TINYINT(1)   NOT NULL DEFAULT 0,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_attempt_username (username)
) ENGINE=InnoDB;