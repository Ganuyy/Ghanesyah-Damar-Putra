-- =============================================================
--  GUITARTICE - Tambah Lagu Intermediate
--    1. NOAH - Separuh Aku
--    2. Virgoun feat. Audy - Selamat Tinggal
--
--  Jalankan pada database yang sudah ada:
--      mysql -u root db_guitarlearn < sql/07_intermediate_noah_virgoun.sql
--
--  Sekaligus menambah chord barre yang dipakai kedua lagu:
--      F#, G#m, C#m, D#m, A/C#, Bb
-- =============================================================

USE db_guitarlearn;

-- -------------------------------------------------------------
-- 1. Chord baru (barre / slash)
-- -------------------------------------------------------------
INSERT INTO chords (name, root, quality, difficulty, start_fret, frets_shown, is_barre) VALUES
  ('F#',   'F#', 'major', 'intermediate', 1, 4, 1),
  ('G#m',  'G#', 'minor', 'intermediate', 4, 4, 1),
  ('C#m',  'C#', 'minor', 'intermediate', 4, 4, 1),
  ('D#m',  'D#', 'minor', 'intermediate', 6, 4, 1),
  ('A/C#', 'A',  'slash', 'intermediate', 1, 4, 0),
  ('Bb',   'Bb', 'major', 'intermediate', 1, 4, 1)
ON DUPLICATE KEY UPDATE
  root = VALUES(root), quality = VALUES(quality), difficulty = VALUES(difficulty),
  start_fret = VALUES(start_fret), frets_shown = VALUES(frets_shown), is_barre = VALUES(is_barre);

-- F# (barre fret 2)
DELETE cf FROM chord_fingering cf JOIN chords c ON c.id = cf.chord_id WHERE c.name = 'F#';
INSERT INTO chord_fingering (chord_id, string_num, fret, finger)
SELECT id, 1, 2, 1 FROM chords WHERE name = 'F#'
UNION ALL SELECT id, 2, 2, 1 FROM chords WHERE name = 'F#'
UNION ALL SELECT id, 3, 3, 2 FROM chords WHERE name = 'F#'
UNION ALL SELECT id, 4, 4, 4 FROM chords WHERE name = 'F#'
UNION ALL SELECT id, 5, 4, 3 FROM chords WHERE name = 'F#'
UNION ALL SELECT id, 6, 2, 1 FROM chords WHERE name = 'F#';

-- G#m (barre fret 4)
DELETE cf FROM chord_fingering cf JOIN chords c ON c.id = cf.chord_id WHERE c.name = 'G#m';
INSERT INTO chord_fingering (chord_id, string_num, fret, finger)
SELECT id, 1, 4, 1 FROM chords WHERE name = 'G#m'
UNION ALL SELECT id, 2, 4, 1 FROM chords WHERE name = 'G#m'
UNION ALL SELECT id, 3, 4, 1 FROM chords WHERE name = 'G#m'
UNION ALL SELECT id, 4, 6, 3 FROM chords WHERE name = 'G#m'
UNION ALL SELECT id, 5, 6, 4 FROM chords WHERE name = 'G#m'
UNION ALL SELECT id, 6, 4, 1 FROM chords WHERE name = 'G#m';

-- C#m (barre fret 4, senar 6 tidak dipakai)
DELETE cf FROM chord_fingering cf JOIN chords c ON c.id = cf.chord_id WHERE c.name = 'C#m';
INSERT INTO chord_fingering (chord_id, string_num, fret, finger)
SELECT id, 1, 4, 1 FROM chords WHERE name = 'C#m'
UNION ALL SELECT id, 2, 5, 2 FROM chords WHERE name = 'C#m'
UNION ALL SELECT id, 3, 6, 4 FROM chords WHERE name = 'C#m'
UNION ALL SELECT id, 4, 6, 3 FROM chords WHERE name = 'C#m'
UNION ALL SELECT id, 5, 4, 1 FROM chords WHERE name = 'C#m';

-- D#m (barre fret 6, senar 6 tidak dipakai)
DELETE cf FROM chord_fingering cf JOIN chords c ON c.id = cf.chord_id WHERE c.name = 'D#m';
INSERT INTO chord_fingering (chord_id, string_num, fret, finger)
SELECT id, 1, 6, 1 FROM chords WHERE name = 'D#m'
UNION ALL SELECT id, 2, 7, 2 FROM chords WHERE name = 'D#m'
UNION ALL SELECT id, 3, 8, 4 FROM chords WHERE name = 'D#m'
UNION ALL SELECT id, 4, 8, 3 FROM chords WHERE name = 'D#m'
UNION ALL SELECT id, 5, 6, 1 FROM chords WHERE name = 'D#m';

-- A/C# (senar 6 tidak dipakai)
DELETE cf FROM chord_fingering cf JOIN chords c ON c.id = cf.chord_id WHERE c.name = 'A/C#';
INSERT INTO chord_fingering (chord_id, string_num, fret, finger)
SELECT id, 1, 0, NULL FROM chords WHERE name = 'A/C#'
UNION ALL SELECT id, 2, 2, 1 FROM chords WHERE name = 'A/C#'
UNION ALL SELECT id, 3, 2, 1 FROM chords WHERE name = 'A/C#'
UNION ALL SELECT id, 4, 2, 2 FROM chords WHERE name = 'A/C#'
UNION ALL SELECT id, 5, 4, 3 FROM chords WHERE name = 'A/C#';

-- Bb (barre fret 1)
DELETE cf FROM chord_fingering cf JOIN chords c ON c.id = cf.chord_id WHERE c.name = 'Bb';
INSERT INTO chord_fingering (chord_id, string_num, fret, finger)
SELECT id, 1, 1, 1 FROM chords WHERE name = 'Bb'
UNION ALL SELECT id, 2, 1, 1 FROM chords WHERE name = 'Bb'
UNION ALL SELECT id, 3, 3, 3 FROM chords WHERE name = 'Bb'
UNION ALL SELECT id, 4, 3, 4 FROM chords WHERE name = 'Bb'
UNION ALL SELECT id, 5, 3, 3 FROM chords WHERE name = 'Bb'
UNION ALL SELECT id, 6, 1, 1 FROM chords WHERE name = 'Bb';

-- -------------------------------------------------------------
-- 2. Lagu: Separuh Aku - NOAH (key D)
-- -------------------------------------------------------------
INSERT INTO songs (title, artist, level, capo, tempo, notes, lyrics)
SELECT 'Separuh Aku', 'NOAH', 'intermediate', NULL, '76 BPM',
  'Chord Bm - D - G - Em - A dengan barre chord F#, D#m, G#m, C#m untuk latihan chord menengah.',
  '[Intro]\n[Bm] [D] [G] [Em] [A] [2x]\n\n[Verse 1]\n[Bm]Dan terjadi lagi\n[G]Kisah lama yang terulang [Em]kembali [A]\n[Bm]Kau terluka [D]lagi\n[Em]Dari cinta rumit yang [G]kau [A]jalani\n\n[Pre-Chorus]\n[Em]Aku ingin [A]kau [Bm]merasa\n[A]Kamu mengerti aku [D]mengerti [A]kamu [Bm]\n[Em]Aku ingin [A]kau [Bm]sadari\n[G]Cinta[C]mu bukanlah [F#]dia\n\n[Chorus]\n[B]Dengar [D#m]laraku [G#m]\nSuara [F#]hati ini [B]memanggil [D#m]namamu [E]\n[D#m]Karena [C#m]separuh [F#]aku dirimu\n\n[Interlude]\n[Bm] [D] [G] [Em] [A]\n\n[Verse 2]\n[Bm]Ku ada di sini\n[Em]Pahamilah kau tak [G]pernah sendiri [A]\n[Bm]Karena aku [D]selalu\n[G]Di dekatmu [Em]saat engkau terjatuh [A]\n\n[Pre-Chorus]\n[Em]Aku ingin [A]kau [Bm]merasa\n[A]Kamu mengerti aku [D]mengerti [A]kamu [Bm]\n[Em]Aku ingin [A]kau [Bm]pahami\n[G]Cinta[C]mu bukanlah [F#]dia\n\n[Chorus]\n[B]Dengar [D#m]laraku [G#m]\nSuara [F#]hati ini [B]memanggil [D#m]namamu [E]\n[D#m]Karena [C#m]separuh [F#]aku dirimu\n\n[Interlude]\n[E]\n\n[Chorus 2]\n[B]Dengar [D#m]laraku [G#m]\nSuara [F#]hati ini [B]memanggil [D#m]namamu [E]\n[D#m]Karena [C#m]separuh [F#]aku\n[B]Menyentuh [D#m]laramu [G#m]\nSemua [F#]lukamu telah [B]menjadi [D#m]milikku [E]\n[D#m]Karena [C#m]separuh [F#]aku dirimu\n\n[Outro]\n[E]'
WHERE NOT EXISTS (SELECT 1 FROM songs WHERE title = 'Separuh Aku');

SELECT id INTO @sid FROM songs WHERE title = 'Separuh Aku' LIMIT 1;
DELETE FROM song_chords WHERE song_id = @sid;
INSERT INTO song_chords (song_id, chord_id, sort_order)
SELECT @sid, id, 1  FROM chords WHERE name = 'Bm'
UNION ALL SELECT @sid, id, 2  FROM chords WHERE name = 'D'
UNION ALL SELECT @sid, id, 3  FROM chords WHERE name = 'G'
UNION ALL SELECT @sid, id, 4  FROM chords WHERE name = 'Em'
UNION ALL SELECT @sid, id, 5  FROM chords WHERE name = 'A'
UNION ALL SELECT @sid, id, 6  FROM chords WHERE name = 'F#'
UNION ALL SELECT @sid, id, 7  FROM chords WHERE name = 'B'
UNION ALL SELECT @sid, id, 8  FROM chords WHERE name = 'D#m'
UNION ALL SELECT @sid, id, 9  FROM chords WHERE name = 'G#m'
UNION ALL SELECT @sid, id, 10 FROM chords WHERE name = 'E'
UNION ALL SELECT @sid, id, 11 FROM chords WHERE name = 'C#m'
UNION ALL SELECT @sid, id, 12 FROM chords WHERE name = 'A/C#'
UNION ALL SELECT @sid, id, 13 FROM chords WHERE name = 'C';

-- -------------------------------------------------------------
-- 3. Lagu: Selamat Tinggal - Virgoun feat. Audy (key C)
-- -------------------------------------------------------------
INSERT INTO songs (title, artist, level, capo, tempo, notes, lyrics)
SELECT 'Selamat Tinggal', 'Virgoun feat. Audy', 'intermediate', NULL, '80 BPM',
  'Chord C - F - Em - Dm - G - Am dengan barre Bb untuk latihan chord menengah.',
  '[Intro]\n[C] [F]\n\n[Verse 1]\n[C]Di tempat ini\n[F]Di tempat pertama aku menemukanmu\n[Em]Kembali ku[Dm]datangi tempat [G]ini\n[C]Tapi ku dengan yang [G]lain\n\n[Verse 2]\n[C]Samar ku dengar\n[F]Suara yang selalu ku kenal, itu suaramu\n[Em]Kau ter[Dm]lihat bahagia [G]bersamanya\n[C]Dia kekasihmu yang [G]baru\n\n[Verse 3]\n[C]Aku pun terdiam\n[F]Saat gadis kecil berlari ke arahmu\n[Em]Gadis [Dm]kecil yang miliki [G]mata indah\n[C]Persis seperti [G]matamu\n\n[Verse 4]\n[C]Dan kau tersenyum (aku pun tersenyum)\n[F]Dan ku genggam tangan wanita di sampingku\n[Em](Dan kau genggam tangannya, [Dm]dan kau genggam [G]tangannya)\n[Em]Dan [Dm]berkata lirih di dalam [G]hati\n[C]Tentang semua [G]ini\n\n[Chorus]\n[F]Andai dulu kau tak [G]pergi dari hidupku\n[Em]Takkan mungkin ku [Am]temui cinta yang [G]kini ku [F]miliki\n[Em]Cinta yang [Dm]menerima [Em]kekurangan [F]\n[Bb]Dan merubah caraku [G]memandang dunia\n\n[F]Andai dulu ku paksakan [G]terus bersamamu\n[Em]Belum tentu kisah kita [Am]berdua [G]berakhir [F]bahagia\n[Em]Kisah yang [Dm]mendewasakan [Em]kita berdua [F]\n[Bb]Meski lewat [G]luka\n\n[Post-Chorus]\n[Am]Satu hal yang [G]kini aku [F]mengerti\n[Em]Meski [Dm]berat bibir ini [Em]mengucap [F]\n[Em]Akan [Dm]selalu ada kata [Em]selamat [G]\n[Am]Dalam setiap kata selamat tinggal\n\n[Instrumental]\n[Am] [F] [C] [Dm] [Em] [F] [G]\n\n[Chorus]\n[F]Andai dulu kau tak [G]pergi dari hidupku\n[Em]Takkan mungkin ku [Am]temui cinta yang [G]kini ku [F]miliki\n[Em]Cinta yang [Dm]menerima [Em]kekurangan [F]\n[Bb]Dan merubah caraku [G]memandang dunia\n\n[F]Andai dulu ku paksakan [G]terus bersamamu\n[Em]Belum tentu kisah kita [Am]berdua [G]berakhir [F]bahagia\n[Em]Kisah yang [Dm]mendewasakan [Em]kita berdua [F]\n[Bb]Meski lewat [G]luka\n\n[Post-Chorus]\n[Am]Satu hal yang [G]kini aku [F]mengerti\n[Em]Meski [Dm]berat bibir ini [Em]mengucap [F]\n[Em]Akan [Dm]selalu ada kata [Em]selamat\n[C]Dalam setiap kata selamat tinggal\n\n[Outro]\n[F]Selamat tinggal (selamat tinggal)\n[G]Samar ku dengar\n[F]Di tempat ini\n[C](Suara yang selalu ku kenal, itu suaramu)\n[G]Di tempat pertama aku [C]menemukanmu'
WHERE NOT EXISTS (SELECT 1 FROM songs WHERE title = 'Selamat Tinggal');

SELECT id INTO @sid FROM songs WHERE title = 'Selamat Tinggal' LIMIT 1;
DELETE FROM song_chords WHERE song_id = @sid;
INSERT INTO song_chords (song_id, chord_id, sort_order)
SELECT @sid, id, 1 FROM chords WHERE name = 'C'
UNION ALL SELECT @sid, id, 2 FROM chords WHERE name = 'F'
UNION ALL SELECT @sid, id, 3 FROM chords WHERE name = 'Em'
UNION ALL SELECT @sid, id, 4 FROM chords WHERE name = 'Dm'
UNION ALL SELECT @sid, id, 5 FROM chords WHERE name = 'G'
UNION ALL SELECT @sid, id, 6 FROM chords WHERE name = 'Am'
UNION ALL SELECT @sid, id, 7 FROM chords WHERE name = 'Bb';
