-- =============================================================
--  GUITARTICE - Migrasi untuk database yang SUDAH ADA
--
--  Jalankan file ini SATU KALI lewat phpMyAdmin (tab SQL/Import)
--  atau lewat terminal:
--      mysql -u root db_guitarlearn < sql/03_migration.sql
--
--  Isi migrasi:
--   1. Kolom songs.audio_file -> menyimpan nama file lagu asli
--   2. Kolom user_song_progress.is_completed & completed_at
--   3. Chord baru: power chord (beginner), extended/slash/altered (pro)
--   4. Mengubah nama situs menjadi GuitarTice
--
--  Kalau database masih baru / tidak ada data penting, cara paling
--  mudah: import ulang sql/01_schema.sql lalu sql/02_seed.sql.
-- =============================================================

USE db_guitarlearn;

-- -------------------------------------------------------------
-- 1. Lagu asli (audio) + status lagu selesai
-- -------------------------------------------------------------
ALTER TABLE songs
  ADD COLUMN audio_file VARCHAR(255) DEFAULT NULL
  COMMENT 'nama file lagu asli di folder uploads/songs';

ALTER TABLE user_song_progress
  ADD COLUMN is_completed TINYINT(1) NOT NULL DEFAULT 0
  COMMENT 'ditandai user sudah bisa memainkan lagu',
  ADD COLUMN completed_at DATETIME DEFAULT NULL;

-- -------------------------------------------------------------
-- 2. Chord baru
-- -------------------------------------------------------------
INSERT INTO chords (name, root, quality, difficulty, start_fret, frets_shown, is_barre, description)
SELECT * FROM (
  SELECT 'C5'    AS name, 'C' AS root, 'power' AS quality, 'beginner' AS difficulty, 8 AS start_fret, 3 AS frets_shown, 0 AS is_barre, 'Power chord C5 di senar 6-5-4: root fret 8, kuint dan oktaf di fret 10.' AS description UNION ALL
  SELECT 'D5',    'D', 'power', 'beginner', 10, 3, 0, 'Power chord D5 di senar 6-5-4: root fret 10, kuint dan oktaf di fret 12.' UNION ALL
  SELECT 'E5',    'E', 'power', 'beginner', 1, 5, 0, 'Power chord E5 di senar 6-5-4 dengan senar E terbuka, sering dipakai di musik rock.' UNION ALL
  SELECT 'G5',    'G', 'power', 'beginner', 1, 5, 0, 'Power chord G5 di senar 6-5-4, nada dasar di senar 6 fret 3.' UNION ALL
  SELECT 'A5',    'A', 'power', 'beginner', 5, 3, 0, 'Power chord A5 di senar 6-5-4: root fret 5, kuint dan oktaf di fret 7.' UNION ALL
  SELECT 'C9',    'C', '9', 'pro', 1, 4, 0, 'Extended chord C9: menambahkan nada ke-9 (D) pada C7.' UNION ALL
  SELECT 'Cmaj9', 'C', 'maj9', 'pro', 1, 4, 0, 'Extended chord C major 9, warna suara jazz yang lembut.' UNION ALL
  SELECT 'Em9',   'E', 'm9', 'pro', 1, 4, 0, 'Extended chord E minor 9 memakai banyak senar terbuka.' UNION ALL
  SELECT 'C/G',   'C', 'slash', 'pro', 1, 4, 0, 'Slash chord C/G: chord C dengan nada bass G pada senar 6.' UNION ALL
  SELECT 'D/F#',  'D', 'slash', 'pro', 1, 4, 0, 'Slash chord D/F#: chord D dengan bass F# di senar 6.' UNION ALL
  SELECT 'G/B',   'G', 'slash', 'pro', 1, 4, 0, 'Slash chord G/B: chord G dengan bass B pada senar 5.' UNION ALL
  SELECT 'Am/G',  'A', 'slash', 'pro', 1, 4, 0, 'Slash chord Am/G: A minor dengan bass G di senar 6.' UNION ALL
  SELECT 'C7#9',  'C', '7#9', 'pro', 1, 4, 0, 'Altered chord C7#9, warna blues/funk yang khas.' UNION ALL
  SELECT 'E7#9',  'E', '7#9', 'pro', 1, 4, 0, 'Altered chord E7#9, dikenal sebagai "Hendrix chord".' UNION ALL
  SELECT 'C7b9',  'C', '7b9', 'pro', 1, 4, 0, 'Altered chord C7b9 dengan nada ke-9 diturunkan setengah nada.'
) AS baru
WHERE NOT EXISTS (SELECT 1 FROM chords c WHERE c.name = baru.name);

-- Posisi jari masing-masing chord baru (INSERT IGNORE agar aman diulang)
SELECT id INTO @cid FROM chords WHERE name = 'C5' LIMIT 1;
INSERT IGNORE INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(@cid,6,8,1),(@cid,5,10,3),(@cid,4,10,4);

SELECT id INTO @cid FROM chords WHERE name = 'D5' LIMIT 1;
INSERT IGNORE INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(@cid,6,10,1),(@cid,5,12,3),(@cid,4,12,4);

SELECT id INTO @cid FROM chords WHERE name = 'E5' LIMIT 1;
INSERT IGNORE INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(@cid,6,0,NULL),(@cid,5,2,1),(@cid,4,2,3);

SELECT id INTO @cid FROM chords WHERE name = 'G5' LIMIT 1;
INSERT IGNORE INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(@cid,6,3,1),(@cid,5,5,3),(@cid,4,5,4);

SELECT id INTO @cid FROM chords WHERE name = 'A5' LIMIT 1;
INSERT IGNORE INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(@cid,6,5,1),(@cid,5,7,3),(@cid,4,7,4);

SELECT id INTO @cid FROM chords WHERE name = 'C9' LIMIT 1;
INSERT IGNORE INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(@cid,5,3,3),(@cid,4,2,2),(@cid,3,3,4),(@cid,2,3,1);

SELECT id INTO @cid FROM chords WHERE name = 'Cmaj9' LIMIT 1;
INSERT IGNORE INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(@cid,5,3,3),(@cid,4,2,2),(@cid,3,4,4),(@cid,2,3,1);

SELECT id INTO @cid FROM chords WHERE name = 'Em9' LIMIT 1;
INSERT IGNORE INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(@cid,6,0,NULL),(@cid,5,2,1),(@cid,4,0,NULL),(@cid,3,0,NULL),(@cid,2,0,NULL),(@cid,1,2,2);

SELECT id INTO @cid FROM chords WHERE name = 'C/G' LIMIT 1;
INSERT IGNORE INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(@cid,6,3,3),(@cid,5,3,4),(@cid,4,2,2),(@cid,3,0,NULL),(@cid,2,1,1),(@cid,1,0,NULL);

SELECT id INTO @cid FROM chords WHERE name = 'D/F#' LIMIT 1;
INSERT IGNORE INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(@cid,6,2,1),(@cid,4,0,NULL),(@cid,3,2,2),(@cid,2,3,3),(@cid,1,2,4);

SELECT id INTO @cid FROM chords WHERE name = 'G/B' LIMIT 1;
INSERT IGNORE INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(@cid,5,2,1),(@cid,4,0,NULL),(@cid,3,0,NULL),(@cid,2,3,2),(@cid,1,3,3);

SELECT id INTO @cid FROM chords WHERE name = 'Am/G' LIMIT 1;
INSERT IGNORE INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(@cid,6,3,3),(@cid,5,0,NULL),(@cid,4,2,2),(@cid,3,2,4),(@cid,2,1,1),(@cid,1,0,NULL);

SELECT id INTO @cid FROM chords WHERE name = 'C7#9' LIMIT 1;
INSERT IGNORE INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(@cid,5,3,2),(@cid,4,2,1),(@cid,3,3,3),(@cid,2,4,4);

SELECT id INTO @cid FROM chords WHERE name = 'E7#9' LIMIT 1;
INSERT IGNORE INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(@cid,6,0,NULL),(@cid,5,2,1),(@cid,4,0,NULL),(@cid,3,1,2),(@cid,2,0,NULL),(@cid,1,3,3);

SELECT id INTO @cid FROM chords WHERE name = 'C7b9' LIMIT 1;
INSERT IGNORE INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(@cid,5,3,3),(@cid,4,2,2),(@cid,3,3,4),(@cid,2,2,1);

-- -------------------------------------------------------------
-- 3. Nama situs
-- -------------------------------------------------------------
UPDATE settings SET setting_value = 'GuitarTice' WHERE setting_key = 'site_name';
