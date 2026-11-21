-- =============================================================
--  GUITARTICE - Tambah Lagu Pro
--    1. Dewa 19 - Aku Milikmu
--    2. Sheila On 7 - Anugerah Terindah Yang Pernah Kumiliki
--
--  Jalankan pada database yang sudah ada:
--      mysql -u root db_guitarlearn < sql/08_pro_dewa_sheila.sql
--
--  Menambah chord yang dipakai aransemen pro:
--      G/D, Dm7, Bbmaj7, D7
-- =============================================================

USE db_guitarlearn;

-- -------------------------------------------------------------
-- 1. Chord baru
-- -------------------------------------------------------------
INSERT INTO chords (name, root, quality, difficulty, start_fret, frets_shown, is_barre) VALUES
  ('G/D',    'G',  'slash', 'pro',          1, 4, 0),
  ('Dm7',    'D',  'm7',    'pro',          1, 4, 0),
  ('Bbmaj7', 'Bb', 'maj7',  'pro',          1, 4, 0),
  ('D7',     'D',  '7',     'intermediate', 1, 4, 0)
ON DUPLICATE KEY UPDATE
  root = VALUES(root), quality = VALUES(quality), difficulty = VALUES(difficulty),
  start_fret = VALUES(start_fret), frets_shown = VALUES(frets_shown), is_barre = VALUES(is_barre);

-- G/D (bass D, senar 6 & 5 tidak dipakai)
DELETE cf FROM chord_fingering cf JOIN chords c ON c.id = cf.chord_id WHERE c.name = 'G/D';
INSERT INTO chord_fingering (chord_id, string_num, fret, finger)
SELECT id, 1, 3, 3 FROM chords WHERE name = 'G/D'
UNION ALL SELECT id, 2, 0, NULL FROM chords WHERE name = 'G/D'
UNION ALL SELECT id, 3, 0, NULL FROM chords WHERE name = 'G/D'
UNION ALL SELECT id, 4, 0, NULL FROM chords WHERE name = 'G/D';

-- Dm7 (senar 6 & 5 tidak dipakai)
DELETE cf FROM chord_fingering cf JOIN chords c ON c.id = cf.chord_id WHERE c.name = 'Dm7';
INSERT INTO chord_fingering (chord_id, string_num, fret, finger)
SELECT id, 1, 1, 1 FROM chords WHERE name = 'Dm7'
UNION ALL SELECT id, 2, 1, 1 FROM chords WHERE name = 'Dm7'
UNION ALL SELECT id, 3, 2, 3 FROM chords WHERE name = 'Dm7'
UNION ALL SELECT id, 4, 0, NULL FROM chords WHERE name = 'Dm7';

-- Bbmaj7 (senar 6 tidak dipakai)
DELETE cf FROM chord_fingering cf JOIN chords c ON c.id = cf.chord_id WHERE c.name = 'Bbmaj7';
INSERT INTO chord_fingering (chord_id, string_num, fret, finger)
SELECT id, 1, 1, 1 FROM chords WHERE name = 'Bbmaj7'
UNION ALL SELECT id, 2, 3, 3 FROM chords WHERE name = 'Bbmaj7'
UNION ALL SELECT id, 3, 2, 2 FROM chords WHERE name = 'Bbmaj7'
UNION ALL SELECT id, 4, 3, 4 FROM chords WHERE name = 'Bbmaj7'
UNION ALL SELECT id, 5, 1, 1 FROM chords WHERE name = 'Bbmaj7';

-- D7 (senar 6 & 5 tidak dipakai)
DELETE cf FROM chord_fingering cf JOIN chords c ON c.id = cf.chord_id WHERE c.name = 'D7';
INSERT INTO chord_fingering (chord_id, string_num, fret, finger)
SELECT id, 1, 2, 2 FROM chords WHERE name = 'D7'
UNION ALL SELECT id, 2, 1, 1 FROM chords WHERE name = 'D7'
UNION ALL SELECT id, 3, 2, 3 FROM chords WHERE name = 'D7'
UNION ALL SELECT id, 4, 0, NULL FROM chords WHERE name = 'D7';

-- -------------------------------------------------------------
-- 2. Lagu: Aku Milikmu - Dewa 19 (progresi asli ditranspose ke G)
-- -------------------------------------------------------------
INSERT INTO songs (title, artist, level, capo, tempo, notes, lyrics)
SELECT 'Aku Milikmu', 'Dewa 19', 'pro', NULL, '136 BPM',
  'Aransemen pro dengan chord maj7/7 dan slash chord G/D serta G/B (mengikuti progresi asli, ditranspose ke G).',
  '[Intro]\n[Fmaj7] [Em7] [Dm7] [Fmaj7] [Em7] [Dm7] [Bbmaj7] [G]\n\n[Verse 1]\n[Cmaj7]Terdengar lirih bisikanmu\n[Gmaj7]Di antara bayang-bayangmu\n[Cmaj7]Terucap kata [Em7]cinta\n[G/D]Yang dulu ter[Cmaj7]simpan\n[G/B]Dan tak mau [Am7]pergi\n\n[Verse 2]\n[Cmaj7]Sekejap cinta yang terjalin\n[Gmaj7]Namun jadi sebuah cerita\n[Cmaj7]Yang tak mungkin ter[Em7]lupa\n[G/D]Terukir di [Cmaj7]hati\n[G/B]Dan tak mau [Am7]pergi\n\n[Pre-Chorus]\n[Cmaj7]Mungkinkah ku[G/B]miliki\n[Am7]Cinta seperti ini [G/B]lagi\n[Cmaj7]Jangan biarkan [G/B]aku\n[Am7]Kehilangan [D7]dirimu\n\n[Chorus]\n[G]Coba dengarkanlah [G7]sumpahku\n[Cmaj7](Janji suci) [B7]dari [Em7]hati [A]\n[Fmaj7]Aku cinta [D7]kamu\n[G]Jangan dengar kata [G7]mereka\n[Cmaj7]Yang tak ingin kita [B7]satu [Em7] [A]\n[Fmaj7]Yakinkan aku [Cmaj7]milikmu\n[Fmaj7]Aku [D7]milikmu\n\n[Verse 3]\n[Cmaj7]Jalinan cinta tulus suci\n[Gmaj7]Terpadu terikat erat\n[Cmaj7]Jangan terpisah [Em7]lagi\n[G/D]Waktu ''kan meng[Cmaj7]uji\n[G/B]Cinta kita ber[Am7]dua\n\n[Pre-Chorus]\n[Cmaj7]Mungkinkah ku[G/B]miliki\n[Am7]Cinta seperti ini [G/B]lagi\n[Cmaj7]Jangan biarkan [G/B]aku\n[Am7]Kehilangan [D7]dirimu\n\n[Chorus]\n[G]Coba dengarkanlah [G7]sumpahku\n[Cmaj7](Janji suci) [B7]dari [Em7]hati [A]\n[Fmaj7]Aku cinta [D7]kamu\n[G]Jangan dengar kata [G7]mereka\n[Cmaj7]Yang tak ingin kita [B7]satu [Em7] [A]\n[Fmaj7]Yakinkan aku [Cmaj7]milikmu\n[Fmaj7]Aku [D7]milikmu\n\n[Outro]\n[Fmaj7] [Em7] [Dm7] [Fmaj7] [Em7] [Dm7] [Bbmaj7] [G]'
WHERE NOT EXISTS (SELECT 1 FROM songs WHERE title = 'Aku Milikmu');

SELECT id INTO @sid FROM songs WHERE title = 'Aku Milikmu' LIMIT 1;
DELETE FROM song_chords WHERE song_id = @sid;
INSERT INTO song_chords (song_id, chord_id, sort_order)
SELECT @sid, id, 1  FROM chords WHERE name = 'Fmaj7'
UNION ALL SELECT @sid, id, 2  FROM chords WHERE name = 'Em7'
UNION ALL SELECT @sid, id, 3  FROM chords WHERE name = 'Dm7'
UNION ALL SELECT @sid, id, 4  FROM chords WHERE name = 'Bbmaj7'
UNION ALL SELECT @sid, id, 5  FROM chords WHERE name = 'G'
UNION ALL SELECT @sid, id, 6  FROM chords WHERE name = 'Cmaj7'
UNION ALL SELECT @sid, id, 7  FROM chords WHERE name = 'Gmaj7'
UNION ALL SELECT @sid, id, 8  FROM chords WHERE name = 'G/D'
UNION ALL SELECT @sid, id, 9  FROM chords WHERE name = 'G/B'
UNION ALL SELECT @sid, id, 10 FROM chords WHERE name = 'Am7'
UNION ALL SELECT @sid, id, 11 FROM chords WHERE name = 'D7'
UNION ALL SELECT @sid, id, 12 FROM chords WHERE name = 'G7'
UNION ALL SELECT @sid, id, 13 FROM chords WHERE name = 'B7'
UNION ALL SELECT @sid, id, 14 FROM chords WHERE name = 'A';

-- -------------------------------------------------------------
-- 3. Lagu: Anugerah Terindah Yang Pernah Kumiliki - Sheila On 7
-- -------------------------------------------------------------
INSERT INTO songs (title, artist, level, capo, tempo, notes, lyrics)
SELECT 'Anugerah Terindah Yang Pernah Kumiliki', 'Sheila On 7', 'pro', NULL, '80 BPM',
  'Aransemen pro dengan slash chord C/G untuk latihan bass dan petikan.',
  '[Intro]\n[G] [C/G] [G] [C/G]\n\n[Verse 1]\n[G]Melihat tawamu\n[C/G]Mendengar senandungmu\n[Em]Terlihat jelas di mataku\n[C/G]Warna-warna indahmu\n[G]Menatap langkahmu\n[C/G]Meratapi kisah hidupmu\n[Em]Terlihat jelas bahwa hatimu\n[C/G]Anugerah terindah yang pernah kumiliki\n\n[Verse 2]\n[G]Sifatmu nan s''lalu\n[C/G]Redakan ambisiku\n[Em]Tepikan khilafku\n[C/G]Dari bunga yang layu\n[G]Saat kau di sisiku\n[C/G]Kembali dunia ceria\n[Em]Tegaskan bahwa kamu\n[C/G]Anugerah terindah yang pernah kumiliki\n\n[Bridge]\n[G]Belai lembut jarimu\n[C/G]Sejuk tatap wajahmu\n[Em]Hangat peluk janjimu\n[C/G]Woo...\n\n[Bridge]\n[G]Belai lembut jarimu\n[C/G]Sejuk tatap wajahmu\n[Em]Hangat peluk janjimu\n[C/G]Anugerah terindah yang pernah kumiliki\n\n[Outro]\n[G] [C/G] [G] [C/G]\n[G]'
WHERE NOT EXISTS (SELECT 1 FROM songs WHERE title = 'Anugerah Terindah Yang Pernah Kumiliki');

SELECT id INTO @sid FROM songs WHERE title = 'Anugerah Terindah Yang Pernah Kumiliki' LIMIT 1;
DELETE FROM song_chords WHERE song_id = @sid;
INSERT INTO song_chords (song_id, chord_id, sort_order)
SELECT @sid, id, 1 FROM chords WHERE name = 'G'
UNION ALL SELECT @sid, id, 2 FROM chords WHERE name = 'C/G'
UNION ALL SELECT @sid, id, 3 FROM chords WHERE name = 'Em';
