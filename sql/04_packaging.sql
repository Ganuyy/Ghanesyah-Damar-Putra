-- =============================================================
--  GUITARTICE - Migrasi "Packaging"
--
--  Jalankan SATU KALI pada database yang sudah berisi data:
--      mysql -u root db_guitarlearn < sql/04_packaging.sql
--
--  Isi:
--   1. Hapus lagu Amazing Grace
--   2. Semua power chord (C5, D5, E5, G5, A5) dimainkan di senar 4,5,6
--   3. Lengkapi lirik + chord Pompeii
--   4. Tambah lagu supaya tiap level punya 3 lagu
--      (termasuk lagu Indonesia)
-- =============================================================

USE db_guitarlearn;

-- -------------------------------------------------------------
-- 1. Hapus lagu Amazing Grace
-- -------------------------------------------------------------
DELETE FROM songs WHERE title = 'Amazing Grace';

-- -------------------------------------------------------------
-- 2. Power chord: semua di senar 4, 5, 6
--    string 6 = root, string 5 = kuint, string 4 = oktaf
-- -------------------------------------------------------------

-- C5 : senar 6 fret 8, senar 5 fret 10, senar 4 fret 10
UPDATE chords SET start_fret = 8, frets_shown = 3 WHERE name = 'C5';
DELETE cf FROM chord_fingering cf
  JOIN chords c ON c.id = cf.chord_id WHERE c.name = 'C5';
INSERT INTO chord_fingering (chord_id, string_num, fret, finger)
SELECT id, 6,  8, 1 FROM chords WHERE name = 'C5'
UNION ALL SELECT id, 5, 10, 3 FROM chords WHERE name = 'C5'
UNION ALL SELECT id, 4, 10, 4 FROM chords WHERE name = 'C5';

-- D5 : senar 6 fret 10, senar 5 fret 12, senar 4 fret 12
UPDATE chords SET start_fret = 10, frets_shown = 3 WHERE name = 'D5';
DELETE cf FROM chord_fingering cf
  JOIN chords c ON c.id = cf.chord_id WHERE c.name = 'D5';
INSERT INTO chord_fingering (chord_id, string_num, fret, finger)
SELECT id, 6, 10, 1 FROM chords WHERE name = 'D5'
UNION ALL SELECT id, 5, 12, 3 FROM chords WHERE name = 'D5'
UNION ALL SELECT id, 4, 12, 4 FROM chords WHERE name = 'D5';

-- E5 : senar 6 terbuka, senar 5 fret 2, senar 4 fret 2
UPDATE chords SET start_fret = 1, frets_shown = 5 WHERE name = 'E5';
DELETE cf FROM chord_fingering cf
  JOIN chords c ON c.id = cf.chord_id WHERE c.name = 'E5';
INSERT INTO chord_fingering (chord_id, string_num, fret, finger)
SELECT id, 6, 0, NULL FROM chords WHERE name = 'E5'
UNION ALL SELECT id, 5, 2, 1 FROM chords WHERE name = 'E5'
UNION ALL SELECT id, 4, 2, 3 FROM chords WHERE name = 'E5';

-- G5 : senar 6 fret 3, senar 5 fret 5, senar 4 fret 5
UPDATE chords SET start_fret = 1, frets_shown = 5 WHERE name = 'G5';
DELETE cf FROM chord_fingering cf
  JOIN chords c ON c.id = cf.chord_id WHERE c.name = 'G5';
INSERT INTO chord_fingering (chord_id, string_num, fret, finger)
SELECT id, 6, 3, 1 FROM chords WHERE name = 'G5'
UNION ALL SELECT id, 5, 5, 3 FROM chords WHERE name = 'G5'
UNION ALL SELECT id, 4, 5, 4 FROM chords WHERE name = 'G5';

-- A5 : senar 6 fret 5, senar 5 fret 7, senar 4 fret 7
UPDATE chords SET start_fret = 5, frets_shown = 3 WHERE name = 'A5';
DELETE cf FROM chord_fingering cf
  JOIN chords c ON c.id = cf.chord_id WHERE c.name = 'A5';
INSERT INTO chord_fingering (chord_id, string_num, fret, finger)
SELECT id, 6, 5, 1 FROM chords WHERE name = 'A5'
UNION ALL SELECT id, 5, 7, 3 FROM chords WHERE name = 'A5'
UNION ALL SELECT id, 4, 7, 4 FROM chords WHERE name = 'A5';

-- -------------------------------------------------------------
-- 3. Lagu: Pompeii (lirik lengkap) + lagu baru tiap level
--    Tiap level berisi tepat 3 lagu.
-- -------------------------------------------------------------

-- ---------- BEGINNER ----------
-- Pompeii - Bastille (C, G, Em, D)
INSERT INTO songs (title, artist, level, capo, tempo, notes, lyrics)
SELECT 'Pompeii', 'Bastille', 'beginner', NULL, '127 BPM',
  'Latihan perpindahan chord C - G - Em - D dengan tempo sedang.',
  '[Intro] [2x]\n[C] [G] [Em] [D]\n\n[Verse 1]\n[C]I was left to my [G]own de[Em]vices[D]\n[C]Many days fell a[G]way with nothing to [Em]show[D]\n[C]And the walls kept tumbling [G]down\nIn the [Em]city that we [D]love\n[C]Great clouds roll over the [G]hills\nBringing [Em]darkness from a[D]bove\n\n[Chorus]\n[C]But if you close your [G]eyes\nDoes it almost feel like [Em]nothing changed at [D]all?\n[C]And if you close your [G]eyes\nDoes it almost feel like [Em]you''ve been here be[D]fore?\n[Em]How am I gonna be an [D]optimist about [C]this?\n[Em]Oh, where do we [D]begin?[C]\n\n[Verse 2]\n[C]We were caught up and [G]lost in all of the [Em]vices[D]\n[C]In your pose as the [G]dust settled a[Em]round[D]\n[C]And the walls kept tumbling [G]down\nIn the [Em]city that we [D]love\n[C]Great clouds roll over the [G]hills\nBringing [Em]darkness from a[D]bove\n\n[Chorus]\n[C]But if you close your [G]eyes\nDoes it almost feel like [Em]nothing changed at [D]all?\n[C]And if you close your [G]eyes\nDoes it almost feel like [Em]you''ve been here be[D]fore?\n[Em]How am I gonna be an [D]optimist about [C]this?\n[Em]Oh, where do we [D]begin?[C]\n\n[Bridge]\n[C]Oh, where do we [G]begin?\n[Em]Oh, where do we [D]begin?[C]\n[C]Oh, where do we [G]begin?\n[Em]Oh, where do we [D]begin?[C]\n\n[Verse 3]\n[C]And the walls kept tumbling [G]down\nIn the [Em]city that we [D]love\n[C]Great clouds roll over the [G]hills\nBringing [Em]darkness from a[D]bove\n\n[Chorus]\n[C]But if you close your [G]eyes\nDoes it almost feel like [Em]nothing changed at [D]all?\n[C]And if you close your [G]eyes\nDoes it almost feel like [Em]you''ve been here be[D]fore?\n[Em]How am I gonna be an [D]optimist about [C]this?\n[Em]Oh, where do we [D]begin?[C]\n\n[Outro]\n[Em]How am I gonna be an [D]optimist about [C]this?\n[Em]Oh, where do we [D]begin?[C]\n[C] [G] [Em] [D]\n[C] [G] [Em] [D]\n[C]'
WHERE NOT EXISTS (SELECT 1 FROM songs WHERE title = 'Pompeii');

UPDATE songs SET artist = 'Bastille', level = 'beginner', capo = NULL, tempo = '127 BPM',
  notes = 'Latihan perpindahan chord C - G - Em - D dengan tempo sedang.',
  lyrics = '[Intro] [2x]\n[C] [G] [Em] [D]\n\n[Verse 1]\n[C]I was left to my [G]own de[Em]vices[D]\n[C]Many days fell a[G]way with nothing to [Em]show[D]\n[C]And the walls kept tumbling [G]down\nIn the [Em]city that we [D]love\n[C]Great clouds roll over the [G]hills\nBringing [Em]darkness from a[D]bove\n\n[Chorus]\n[C]But if you close your [G]eyes\nDoes it almost feel like [Em]nothing changed at [D]all?\n[C]And if you close your [G]eyes\nDoes it almost feel like [Em]you''ve been here be[D]fore?\n[Em]How am I gonna be an [D]optimist about [C]this?\n[Em]Oh, where do we [D]begin?[C]\n\n[Verse 2]\n[C]We were caught up and [G]lost in all of the [Em]vices[D]\n[C]In your pose as the [G]dust settled a[Em]round[D]\n[C]And the walls kept tumbling [G]down\nIn the [Em]city that we [D]love\n[C]Great clouds roll over the [G]hills\nBringing [Em]darkness from a[D]bove\n\n[Chorus]\n[C]But if you close your [G]eyes\nDoes it almost feel like [Em]nothing changed at [D]all?\n[C]And if you close your [G]eyes\nDoes it almost feel like [Em]you''ve been here be[D]fore?\n[Em]How am I gonna be an [D]optimist about [C]this?\n[Em]Oh, where do we [D]begin?[C]\n\n[Bridge]\n[C]Oh, where do we [G]begin?\n[Em]Oh, where do we [D]begin?[C]\n[C]Oh, where do we [G]begin?\n[Em]Oh, where do we [D]begin?[C]\n\n[Verse 3]\n[C]And the walls kept tumbling [G]down\nIn the [Em]city that we [D]love\n[C]Great clouds roll over the [G]hills\nBringing [Em]darkness from a[D]bove\n\n[Chorus]\n[C]But if you close your [G]eyes\nDoes it almost feel like [Em]nothing changed at [D]all?\n[C]And if you close your [G]eyes\nDoes it almost feel like [Em]you''ve been here be[D]fore?\n[Em]How am I gonna be an [D]optimist about [C]this?\n[Em]Oh, where do we [D]begin?[C]\n\n[Outro]\n[Em]How am I gonna be an [D]optimist about [C]this?\n[Em]Oh, where do we [D]begin?[C]\n[C] [G] [Em] [D]\n[C] [G] [Em] [D]\n[C]'
WHERE title = 'Pompeii';

SELECT id INTO @sid FROM songs WHERE title = 'Pompeii' LIMIT 1;
DELETE FROM song_chords WHERE song_id = @sid;
INSERT INTO song_chords (song_id, chord_id, sort_order)
SELECT @sid, id, 1 FROM chords WHERE name = 'C'
UNION ALL SELECT @sid, id, 2 FROM chords WHERE name = 'G'
UNION ALL SELECT @sid, id, 3 FROM chords WHERE name = 'Em'
UNION ALL SELECT @sid, id, 4 FROM chords WHERE name = 'D';

-- Cinta Luar Biasa - Andmesh (G, D, Em, C)
INSERT INTO songs (title, artist, level, capo, tempo, notes, lyrics)
SELECT 'Cinta Luar Biasa', 'Andmesh Kamaleng', 'beginner', NULL, '75 BPM',
  'Chord dasar G - D - Em - C, cocok untuk latihan ritme santai.',
  '[Intro] [G] [D] [Em] [C]\n\n[Verse 1]\n[G]Saat pertama kali ku melihat[D]mu\n[Em]Kau tersenyum manis di [C]mataku\n[G]Jantungku berdebar tak [D]karuan\n[Em]Mungkin ini cinta yang [C]ditunggu\n\n[Verse 2]\n[G]Ku tak pernah merasakan [D]sebelumnya\n[Em]Cinta yang begitu indah [C]rasanya\n[G]Ku ingin kau tahu [D]isi hatiku\n[Em]Bahwa ku mencintaimu [C]sepenuh hati\n\n[Chorus]\n[C]Kau adalah [D]cinta luar [G]biasa\n[C]Yang pernah [D]ada dalam [G]hidupku\n[C]Kau adalah [D]cinta luar [G]biasa\n[Em]Ku ingin [C]kau selalu [D]di sini\n\n[Verse 3]\n[G]Ku berharap kau juga [D]merasakan\n[Em]Cinta yang tulus dari [C]hatiku\n[G]Ku ingin kau tahu [D]isi hatiku\n[Em]Bahwa ku mencintaimu [C]sepenuh hati\n\n[Chorus]\n[C]Kau adalah [D]cinta luar [G]biasa\n[C]Yang pernah [D]ada dalam [G]hidupku\n[C]Kau adalah [D]cinta luar [G]biasa\n[Em]Ku ingin [C]kau selalu [D]di sini\n\n[Bridge]\n[C]Cinta ini [D]tulus untuk[G]mu\n[C]Takkan pernah [D]pudar oleh [G]waktu\n[C]Ku ingin [D]kau selalu [G]di sini\n[Em]Menemani [C]hidup[D]ku\n\n[Chorus]\n[C]Kau adalah [D]cinta luar [G]biasa\n[C]Yang pernah [D]ada dalam [G]hidupku\n[C]Kau adalah [D]cinta luar [G]biasa\n[Em]Ku ingin [C]kau selalu [D]di sini\n\n[Outro]\n[G] [D] [Em] [C]\n[G]'
WHERE NOT EXISTS (SELECT 1 FROM songs WHERE title = 'Cinta Luar Biasa');

SELECT id INTO @sid FROM songs WHERE title = 'Cinta Luar Biasa' LIMIT 1;
DELETE FROM song_chords WHERE song_id = @sid;
INSERT INTO song_chords (song_id, chord_id, sort_order)
SELECT @sid, id, 1 FROM chords WHERE name = 'G'
UNION ALL SELECT @sid, id, 2 FROM chords WHERE name = 'D'
UNION ALL SELECT @sid, id, 3 FROM chords WHERE name = 'Em'
UNION ALL SELECT @sid, id, 4 FROM chords WHERE name = 'C';

-- Akad - Payung Teduh (Am, Dm, G, C, E)
INSERT INTO songs (title, artist, level, capo, tempo, notes, lyrics)
SELECT 'Akad', 'Payung Teduh', 'beginner', NULL, '80 BPM',
  'Chord Am - Dm - G - C - E dengan nuansa folk yang tenang.',
  '[Intro] [Am] [Dm] [G] [C] [E]\n\n[Verse 1]\n[Am]Perempuan yang cantik\n[Dm]Ini adalah tentang kita\n[G]Kita yang berjalan di [C]bawah langit\n[E]Yang tak pernah memandang\n\n[Verse 2]\n[Am]Ku ingin engkau tahu\n[Dm]Betapa ku mencintaimu\n[G]Dan aku tak bisa [C]melepaskanmu\n[E]Walau hanya sekejap\n\n[Chorus]\n[Am]Akad [Dm]telah [G]terikat [C]\n[E]Kau dan aku [Am]satu\n[Am]Sampai [Dm]akhir [G]waktu [C]\n[E]Selamanya\n\n[Verse 3]\n[Am]Ku ingin engkau tahu\n[Dm]Betapa ku mencintaimu\n[G]Dan aku tak bisa [C]melepaskanmu\n[E]Walau hanya sekejap\n\n[Chorus]\n[Am]Akad [Dm]telah [G]terikat [C]\n[E]Kau dan aku [Am]satu\n[Am]Sampai [Dm]akhir [G]waktu [C]\n[E]Selamanya\n\n[Bridge]\n[Am]Perempuan yang cantik\n[Dm]Ini adalah tentang kita\n[G]Kita yang berjalan di [C]bawah langit\n[E]Yang tak pernah memandang\n\n[Chorus]\n[Am]Akad [Dm]telah [G]terikat [C]\n[E]Kau dan aku [Am]satu\n[Am]Sampai [Dm]akhir [G]waktu [C]\n[E]Selamanya\n[E]Selamanya\n\n[Outro]\n[Am] [Dm] [G] [C] [E]\n[Am]'
WHERE NOT EXISTS (SELECT 1 FROM songs WHERE title = 'Akad');

SELECT id INTO @sid FROM songs WHERE title = 'Akad' LIMIT 1;
DELETE FROM song_chords WHERE song_id = @sid;
INSERT INTO song_chords (song_id, chord_id, sort_order)
SELECT @sid, id, 1 FROM chords WHERE name = 'Am'
UNION ALL SELECT @sid, id, 2 FROM chords WHERE name = 'Dm'
UNION ALL SELECT @sid, id, 3 FROM chords WHERE name = 'G'
UNION ALL SELECT @sid, id, 4 FROM chords WHERE name = 'C'
UNION ALL SELECT @sid, id, 5 FROM chords WHERE name = 'E';

-- ---------- INTERMEDIATE ----------
-- Melukis Senja - Budi Doremi (C, G, Am, F, Dm)
INSERT INTO songs (title, artist, level, capo, tempo, notes, lyrics)
SELECT 'Melukis Senja', 'Budi Doremi', 'intermediate', NULL, '78 BPM',
  'Latihan chord F (barre) dan perpindahan C - G - Am - F.',
  '[Intro] [C] [G] [Am] [F]\n\n[Verse 1]\n[C]Ku ingin engkau tahu\n[G]Betapa ku menyayangimu\n[Am]Ku ingin engkau tahu\n[F]Betapa ku mencintaimu\n\n[Verse 2]\n[C]Aku mengerti pera[G]saanmu\n[Am]Yang selama ini tak [F]kau ungkapkan\n[C]Aku mengerti kamu [G]mencintaiku\n[Am]Namun tak berani [F]mengungkapkan\n\n[Chorus]\n[C]Izinkan ku melukis [G]senja\n[Am]Untukmu yang sedang [F]berjuang\n[C]Izinkan ku menghibur [G]hatimu\n[Am]Walau hanya lewat [F]lagu\n\n[Verse 3]\n[C]Ku ingin engkau tahu\n[G]Betapa ku menyayangimu\n[Am]Ku ingin engkau tahu\n[F]Betapa ku mencintaimu\n\n[Chorus]\n[C]Izinkan ku melukis [G]senja\n[Am]Untukmu yang sedang [F]berjuang\n[C]Izinkan ku menghibur [G]hatimu\n[Am]Walau hanya lewat [F]lagu\n\n[Bridge]\n[Dm]Ku ingin engkau [G]bahagia\n[C]Walau tak bersama[F]ku\n[Dm]Ku ingin engkau [G]bahagia\n[C]Selamanya\n\n[Chorus]\n[C]Izinkan ku melukis [G]senja\n[Am]Untukmu yang sedang [F]berjuang\n[C]Izinkan ku menghibur [G]hatimu\n[Am]Walau hanya lewat [F]lagu\n\n[Outro]\n[Dm]Ku ingin engkau [G]bahagia\n[C]Walau tak bersama[F]ku\n[Dm]Ku ingin engkau [G]bahagia\n[C]Selamanya\n[C]'
WHERE NOT EXISTS (SELECT 1 FROM songs WHERE title = 'Melukis Senja');

SELECT id INTO @sid FROM songs WHERE title = 'Melukis Senja' LIMIT 1;
DELETE FROM song_chords WHERE song_id = @sid;
INSERT INTO song_chords (song_id, chord_id, sort_order)
SELECT @sid, id, 1 FROM chords WHERE name = 'C'
UNION ALL SELECT @sid, id, 2 FROM chords WHERE name = 'G'
UNION ALL SELECT @sid, id, 3 FROM chords WHERE name = 'Am'
UNION ALL SELECT @sid, id, 4 FROM chords WHERE name = 'F'
UNION ALL SELECT @sid, id, 5 FROM chords WHERE name = 'Dm';

-- Kangen - Dewa 19 (C, G, Am, Em, F, Dm)
INSERT INTO songs (title, artist, level, capo, tempo, notes, lyrics)
SELECT 'Kangen', 'Dewa 19', 'intermediate', NULL, '72 BPM',
  'Lagu ballada dengan chord C - G - Am - Em - F - Dm.',
  '[Intro] [C] [G] [Am] [Em] [F] [Dm] [G]\n\n[Chorus]\n[C]Kangen, [G]aku kangen [Am]kamu [Em]\n[F]Kangen, [Dm]sangat kangen [G]kamu\n[C]Kangen, [G]aku kangen [Am]kamu [Em]\n[F]Kangen, [Dm]sangat kangen [G]kamu\n\n[Verse 1]\n[C]Separuh nafasku [G]terbang bersamamu\n[Am]Ku menahan tangis [Em]di hari ini\n[F]Karena kau pergi [Dm]tinggalkan aku\n[G]Tanpa alasan yang pasti\n\n[Chorus]\n[C]Kangen, [G]aku kangen [Am]kamu [Em]\n[F]Kangen, [Dm]sangat kangen [G]kamu\n[C]Kangen, [G]aku kangen [Am]kamu [Em]\n[F]Kangen, [Dm]sangat kangen [G]kamu\n\n[Verse 2]\n[C]Ku ingin kau tahu [G]betapa ku mencintaimu\n[Am]Ku ingin kau tahu [Em]betapa ku merindukanmu\n[F]Ku ingin kau kembali [Dm]padaku\n[G]Jangan tinggalkan aku\n\n[Chorus]\n[C]Kangen, [G]aku kangen [Am]kamu [Em]\n[F]Kangen, [Dm]sangat kangen [G]kamu\n[C]Kangen, [G]aku kangen [Am]kamu [Em]\n[F]Kangen, [Dm]sangat kangen [G]kamu\n\n[Bridge]\n[Am]Ku tak bisa [Em]melupakanmu\n[F]Ku tak bisa [C]tanpamu\n[Am]Ku tak bisa [Em]melupakanmu\n[F]Ku tak bisa [G]tanpamu\n\n[Chorus]\n[C]Kangen, [G]aku kangen [Am]kamu [Em]\n[F]Kangen, [Dm]sangat kangen [G]kamu\n[C]Kangen, [G]aku kangen [Am]kamu [Em]\n[F]Kangen, [Dm]sangat kangen [G]kamu\n\n[Outro]\n[C] [G] [Am] [Em]\n[F] [Dm] [G] [C]'
WHERE NOT EXISTS (SELECT 1 FROM songs WHERE title = 'Kangen');

SELECT id INTO @sid FROM songs WHERE title = 'Kangen' LIMIT 1;
DELETE FROM song_chords WHERE song_id = @sid;
INSERT INTO song_chords (song_id, chord_id, sort_order)
SELECT @sid, id, 1 FROM chords WHERE name = 'C'
UNION ALL SELECT @sid, id, 2 FROM chords WHERE name = 'G'
UNION ALL SELECT @sid, id, 3 FROM chords WHERE name = 'Am'
UNION ALL SELECT @sid, id, 4 FROM chords WHERE name = 'Em'
UNION ALL SELECT @sid, id, 5 FROM chords WHERE name = 'F'
UNION ALL SELECT @sid, id, 6 FROM chords WHERE name = 'Dm';

-- Bintang di Surga - Peterpan (C, G, Am, F)
INSERT INTO songs (title, artist, level, capo, tempo, notes, lyrics)
SELECT 'Bintang di Surga', 'Peterpan', 'intermediate', NULL, '76 BPM',
  'Latihan dinamika strumming dengan chord C - G - Am - F.',
  '[Intro] [C] [G] [Am] [F]\n\n[Verse 1]\n[C]Masih ku rasakan [G]getaran itu\n[Am]Saat kau menyentuh [F]tanganku\n[C]Masih ku rasakan [G]hangat pelukan\n[Am]Yang kau berikan [F]padaku\n\n[Verse 2]\n[C]Masih ku rasakan [G]getaran itu\n[Am]Saat kau menyentuh [F]tanganku\n[C]Masih ku rasakan [G]hangat pelukan\n[Am]Yang kau berikan [F]padaku\n\n[Chorus]\n[C]Bintang di surga [G]tetaplah bersinar\n[Am]Walau kau tak lagi [F]di sini\n[C]Bintang di surga [G]tetaplah bersinar\n[Am]Di dalam hatiku [F]selamanya\n\n[Verse 3]\n[C]Dan aku takkan pernah [G]lupa\n[Am]Semua yang pernah kita [F]lalui\n[C]Dan aku takkan pernah [G]bisa\n[Am]Melupakan dirimu [F]\n\n[Chorus]\n[C]Bintang di surga [G]tetaplah bersinar\n[Am]Walau kau tak lagi [F]di sini\n[C]Bintang di surga [G]tetaplah bersinar\n[Am]Di dalam hatiku [F]selamanya\n\n[Bridge]\n[C]Walau kau jauh [G]di sana\n[Am]Ku kan selalu [F]menjagamu\n[C]Walau kau jauh [G]di sana\n[Am]Ku kan selalu [F]mencintaimu\n\n[Chorus]\n[C]Bintang di surga [G]tetaplah bersinar\n[Am]Walau kau tak lagi [F]di sini\n[C]Bintang di surga [G]tetaplah bersinar\n[Am]Di dalam hatiku [F]selamanya\n\n[Outro]\n[C] [G] [Am] [F]\n[C] [G] [Am] [F]\n[C]'
WHERE NOT EXISTS (SELECT 1 FROM songs WHERE title = 'Bintang di Surga');

SELECT id INTO @sid FROM songs WHERE title = 'Bintang di Surga' LIMIT 1;
DELETE FROM song_chords WHERE song_id = @sid;
INSERT INTO song_chords (song_id, chord_id, sort_order)
SELECT @sid, id, 1 FROM chords WHERE name = 'C'
UNION ALL SELECT @sid, id, 2 FROM chords WHERE name = 'G'
UNION ALL SELECT @sid, id, 3 FROM chords WHERE name = 'Am'
UNION ALL SELECT @sid, id, 4 FROM chords WHERE name = 'F';

-- ---------- PRO ----------
-- Bukan Cinta Biasa - Afgan (C, G/B, Am, F, Dm, E7)
INSERT INTO songs (title, artist, level, capo, tempo, notes, lyrics)
SELECT 'Bukan Cinta Biasa', 'Afgan', 'pro', NULL, '70 BPM',
  'Slash chord G/B untuk latihan bass berjalan dan transisi chord pro.',
  '[Intro] [C] [G/B] [Am] [F] [Dm] [E7]\n\n[Verse 1]\n[C]Ku ingin engkau tahu [G/B]isi hatiku\n[Am]Betapa ku menyayangi[F]mu\n[C]Ku ingin engkau tahu [G/B]rasa cintaku\n[Am]Yang selama ini [F]kupendam\n\n[Chorus]\n[Dm]Bukan cinta [E7]biasa\n[Am]Yang kurasakan [F]untukmu\n[Dm]Bukan cinta [E7]biasa\n[Am]Yang membuatku [F]terpuruk\n\n[Verse 2]\n[C]Ku ingin engkau tahu [G/B]isi hatiku\n[Am]Betapa ku menyayangi[F]mu\n[C]Ku ingin engkau tahu [G/B]rasa cintaku\n[Am]Yang selama ini [F]kupendam\n\n[Chorus]\n[Dm]Bukan cinta [E7]biasa\n[Am]Yang kurasakan [F]untukmu\n[Dm]Bukan cinta [E7]biasa\n[Am]Yang membuatku [F]terpuruk\n\n[Bridge]\n[C]Ku tak ingin kau [G/B]pergi\n[Am]Tinggalkan aku [F]sendiri\n[C]Ku tak ingin kau [G/B]pergi\n[Am]Jangan tinggalkan [F]aku\n\n[Chorus]\n[Dm]Bukan cinta [E7]biasa\n[Am]Yang kurasakan [F]untukmu\n[Dm]Bukan cinta [E7]biasa\n[Am]Yang membuatku [F]terpuruk\n\n[Outro]\n[C] [G/B] [Am] [F]\n[Dm] [E7] [C]'
WHERE NOT EXISTS (SELECT 1 FROM songs WHERE title = 'Bukan Cinta Biasa');

SELECT id INTO @sid FROM songs WHERE title = 'Bukan Cinta Biasa' LIMIT 1;
DELETE FROM song_chords WHERE song_id = @sid;
INSERT INTO song_chords (song_id, chord_id, sort_order)
SELECT @sid, id, 1 FROM chords WHERE name = 'C'
UNION ALL SELECT @sid, id, 2 FROM chords WHERE name = 'G/B'
UNION ALL SELECT @sid, id, 3 FROM chords WHERE name = 'Am'
UNION ALL SELECT @sid, id, 4 FROM chords WHERE name = 'F'
UNION ALL SELECT @sid, id, 5 FROM chords WHERE name = 'Dm'
UNION ALL SELECT @sid, id, 6 FROM chords WHERE name = 'E7';

-- Terima Kasih Cinta - Afgan (C, G/B, Am, F, Dm, E)
INSERT INTO songs (title, artist, level, capo, tempo, notes, lyrics)
SELECT 'Terima Kasih Cinta', 'Afgan', 'pro', NULL, '68 BPM',
  'Slash chord G/B dan E mayor untuk latihan transisi chord lanjutan.',
  '[Intro] [C] [G/B] [Am] [F]\n\n[Verse 1]\n[C]Aku tak bisa [G/B]terlalu jauh\n[Am]Untuk melupakan [F]dirimu\n[C]Aku tak bisa [G/B]berpaling arah\n[Am]Meninggalkan semua [F]kenangan\n\n[Chorus]\n[Dm]Terima kasih [E]cinta\n[Am]Kau telah mengajar[F]kan aku\n[Dm]Arti kesetiaan [E]dan ketulusan\n[Am]Dalam mencintai [F]dirimu\n\n[Verse 2]\n[C]Aku tak bisa [G/B]terlalu jauh\n[Am]Untuk melupakan [F]dirimu\n[C]Aku tak bisa [G/B]berpaling arah\n[Am]Meninggalkan semua [F]kenangan\n\n[Chorus]\n[Dm]Terima kasih [E]cinta\n[Am]Kau telah mengajar[F]kan aku\n[Dm]Arti kesetiaan [E]dan ketulusan\n[Am]Dalam mencintai [F]dirimu\n\n[Bridge]\n[C]Ku takkan pernah [G/B]bisa\n[Am]Melupakan [F]dirimu\n[C]Ku takkan pernah [G/B]bisa\n[Am]Meninggalkan [F]dirimu\n\n[Chorus]\n[Dm]Terima kasih [E]cinta\n[Am]Kau telah mengajar[F]kan aku\n[Dm]Arti kesetiaan [E]dan ketulusan\n[Am]Dalam mencintai [F]dirimu\n\n[Outro]\n[C] [G/B] [Am] [F]\n[C]'
WHERE NOT EXISTS (SELECT 1 FROM songs WHERE title = 'Terima Kasih Cinta');

SELECT id INTO @sid FROM songs WHERE title = 'Terima Kasih Cinta' LIMIT 1;
DELETE FROM song_chords WHERE song_id = @sid;
INSERT INTO song_chords (song_id, chord_id, sort_order)
SELECT @sid, id, 1 FROM chords WHERE name = 'C'
UNION ALL SELECT @sid, id, 2 FROM chords WHERE name = 'G/B'
UNION ALL SELECT @sid, id, 3 FROM chords WHERE name = 'Am'
UNION ALL SELECT @sid, id, 4 FROM chords WHERE name = 'F'
UNION ALL SELECT @sid, id, 5 FROM chords WHERE name = 'Dm'
UNION ALL SELECT @sid, id, 6 FROM chords WHERE name = 'E';

-- Runtuh - Feby Putri (G, D, Em, C, Am, Bm)
INSERT INTO songs (title, artist, level, capo, tempo, notes, lyrics)
SELECT 'Runtuh', 'Feby Putri', 'pro', NULL, '70 BPM',
  'Menggunakan Bm (barre) untuk latihan chord lanjutan.',
  '[Intro] [G] [D] [Em] [C] [Am] [Bm]\n\n[Verse 1]\n[G]Ku takut kehilangan[D]mu\n[Em]Ku takut sendiri [C]\n[Am]Namun ku tahu [Bm]semua\n[G]Akan berakhir [D]juga\n\n[Chorus]\n[G]Runtuhlah semua [D]harapanku\n[Em]Bila kau tak di [C]sini\n[Am]Biarlah waktu [Bm]menyembuhkan\n[G]Luka yang ter[D]dalam ini\n\n[Verse 2]\n[G]Ku takut kehilangan[D]mu\n[Em]Ku takut sendiri [C]\n[Am]Namun ku tahu [Bm]semua\n[G]Akan berakhir [D]juga\n\n[Chorus]\n[G]Runtuhlah semua [D]harapanku\n[Em]Bila kau tak di [C]sini\n[Am]Biarlah waktu [Bm]menyembuhkan\n[G]Luka yang ter[D]dalam ini\n\n[Bridge]\n[Am]Ku ingin kau tahu [Bm]betapa ku mencintaimu\n[G]Ku ingin kau tahu [D]betapa ku menyayangimu\n[Am]Ku ingin kau tahu [Bm]betapa ku mencintaimu\n[G]Ku ingin kau tahu [D]betapa ku menyayangimu\n\n[Chorus]\n[G]Runtuhlah semua [D]harapanku\n[Em]Bila kau tak di [C]sini\n[Am]Biarlah waktu [Bm]menyembuhkan\n[G]Luka yang ter[D]dalam ini\n\n[Outro]\n[G] [D] [Em] [C]\n[Am] [Bm] [G]'
WHERE NOT EXISTS (SELECT 1 FROM songs WHERE title = 'Runtuh');

SELECT id INTO @sid FROM songs WHERE title = 'Runtuh' LIMIT 1;
DELETE FROM song_chords WHERE song_id = @sid;
INSERT INTO song_chords (song_id, chord_id, sort_order)
SELECT @sid, id, 1 FROM chords WHERE name = 'G'
UNION ALL SELECT @sid, id, 2 FROM chords WHERE name = 'D'
UNION ALL SELECT @sid, id, 3 FROM chords WHERE name = 'Em'
UNION ALL SELECT @sid, id, 4 FROM chords WHERE name = 'C'
UNION ALL SELECT @sid, id, 5 FROM chords WHERE name = 'Am'
UNION ALL SELECT @sid, id, 6 FROM chords WHERE name = 'Bm';

-- -------------------------------------------------------------
-- 4. Tagline situs
-- -------------------------------------------------------------
UPDATE settings SET setting_value = 'Belajar Gitar Santai' WHERE setting_key = 'site_tagline';
