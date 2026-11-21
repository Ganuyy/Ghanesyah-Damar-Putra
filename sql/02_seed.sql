-- =============================================================
--  GUITARTICE - Data Awal (Seed)
--  Jalankan SETELAH sql/01_schema.sql
--  Berisi: 2 user, 38 chord + posisi jari, 9 lagu (3 per level)
-- =============================================================

USE db_guitarlearn;

-- -------------------------------------------------------------
-- A. USER
--    admin / admin123   -> role admin
--    demo  / demo123    -> user biasa level beginner (opsional,
--                          dipakai supaya halaman statistik admin
--                          punya data contoh)
-- -------------------------------------------------------------
INSERT INTO users (username, email, password, fullname, level, role) VALUES
('admin', 'admin@guitarlearn.test',
 '$2y$10$0SXdYE2TqbDRFYEtZdIh1OQFzMCsSBu87kWQc6qx1khmXyZVUvhae',
 'Administrator', 'pro', 'admin'),

('demo', 'demo@guitarlearn.test',
 '$2y$10$DVKMtpGx8fHqSNgFkQbIZOFuUK7/KkwDbdA/PeNkMmOjY.P7GH9FG',
 'Demo User Biasa', 'beginner', 'user');

-- -------------------------------------------------------------
-- B. CHORD LIBRARY
-- -------------------------------------------------------------
INSERT INTO chords (id, name, root, quality, difficulty, start_fret, frets_shown, is_barre, description) VALUES
(1,  'C',      'C', 'major',  'beginner',     1, 4, 0, 'Chord dasar C mayor, semua senar terbuka. Fundamental untuk lagu anak dan pembulaan.'),
(2,  'Am',     'A', 'minor',  'beginner',     1, 4, 0, 'Chord A minor, salah satu chord dasar paling sering dipakai. Senar A dibiarkan terbuka.'),
(3,  'G',      'G', 'major',  'beginner',     1, 4, 0, 'Chord G mayor. Hampir semua lagu pop memakai triad C - G - Am.'),
(4,  'D',      'D', 'major',  'beginner',     1, 4, 0, 'Chord D mayor. Pasangan umum untuk C dan G.'),
(5,  'Em',     'E', 'minor',  'beginner',     1, 3, 0, 'Chord E minor, sangat umum dalam lagu-lagu ballad.'),
(6,  'Dm',     'D', 'minor',  'beginner',     1, 4, 0, 'Chord D minor, pasangan gelap dari D mayor.'),
(7,  'F',      'F', 'major',  'intermediate', 1, 4, 1, 'Chord F mayor dengan teknik barre: telunjuk ditekan mendatar di fret 1, menutupi senar 6 sampai 1.'),
(8,  'B7',     'B', '7',      'intermediate',     1, 4, 0, 'Chord B dominant seventh, bentuk terbuka.'),
(9,  'C7',     'C', '7',      'intermediate',     1, 4, 0, 'Chord C7, memberi warna blues pada progression.'),
(10, 'A7',     'A', '7',      'intermediate',     1, 4, 0, 'Chord A7, sering mengarahkan ke chord D.'),
(11, 'E7',     'E', '7',      'intermediate',     1, 4, 0, 'Chord E7, transisi menuju A minor atau A mayor.'),
(12, 'G7',     'G', '7',      'intermediate', 1, 4, 0, 'Chord G7, resolve ke C mayor. Bentuk terbuka, tidak memakai barre.'),
(13, 'Fmaj7',  'F', 'maj7',   'intermediate', 1, 4, 0, 'Chord F major seventh. Senar 1 dibiarkan terbuka agar nada E (maj7) terdengar.'),
(14, 'Cmaj7',  'C', 'maj7',   'intermediate', 1, 4, 0, 'Chord C major seventh, dua senar atas dibiarkan terbuka.'),
(15, 'Am7',    'A', 'm7',     'intermediate', 1, 4, 0, 'Chord A minor seventh, transisi menuju D atau G.'),
(16, 'Dsus4',  'D', 'sus4',   'intermediate',     1, 4, 0, 'Chord D suspended fourth, sering menjadi persiapan ke C atau G.'),
(17, 'Asus2',  'A', 'sus2',   'intermediate',     1, 4, 0, 'Chord A suspended second, transisi menuju D atau E.'),
(18, 'Em7',    'E', 'm7',     'intermediate', 1, 4, 0, 'Chord E minor seventh, transisi ke A dan D.'),
(19, 'Gmaj7',  'G', 'maj7',   'intermediate', 1, 4, 0, 'Chord G major seventh, senar 5 di-mute agar jari lebih longgar.'),
(20, 'Bm',     'B', 'minor',  'pro',          2, 4, 1, 'Chord B minor dengan barre telunjuk di fret 2, termasuk chord untuk tahap lanjutan.'),
-- Power chord (Beginner)
(21, 'C5',     'C', 'power',  'beginner',     8, 3, 0, 'Power chord C5 di senar 6-5-4: root fret 8, kuint dan oktaf di fret 10.'),
(22, 'D5',     'D', 'power',  'beginner',    10, 3, 0, 'Power chord D5 di senar 6-5-4: root fret 10, kuint dan oktaf di fret 12.'),
(23, 'E5',     'E', 'power',  'beginner',     1, 5, 0, 'Power chord E5 di senar 6-5-4 dengan senar E terbuka, sering dipakai di musik rock.'),
(24, 'G5',     'G', 'power',  'beginner',     1, 5, 0, 'Power chord G5 di senar 6-5-4, nada dasar di senar 6 fret 3.'),
(25, 'A5',     'A', 'power',  'beginner',     5, 3, 0, 'Power chord A5 di senar 6-5-4: root fret 5, kuint dan oktaf di fret 7.'),
-- Extended chord (Pro)
(26, 'C9',     'C', '9',      'pro',          1, 4, 0, 'Extended chord C9: menambahkan nada ke-9 (D) pada C7.'),
(27, 'Cmaj9',  'C', 'maj9',   'pro',          1, 4, 0, 'Extended chord C major 9, warna suara jazz yang lembut.'),
(28, 'Em9',    'E', 'm9',     'pro',          1, 4, 0, 'Extended chord E minor 9 memakai banyak senar terbuka.'),
-- Slash chord (Pro)
(29, 'C/G',    'C', 'slash',  'pro',          1, 4, 0, 'Slash chord C/G: chord C dengan nada bass G pada senar 6.'),
(30, 'D/F#',   'D', 'slash',  'pro',          1, 4, 0, 'Slash chord D/F#: chord D dengan bass F# di senar 6.'),
(31, 'G/B',    'G', 'slash',  'pro',          1, 4, 0, 'Slash chord G/B: chord G dengan bass B pada senar 5.'),
(32, 'Am/G',   'A', 'slash',  'pro',          1, 4, 0, 'Slash chord Am/G: A minor dengan bass G di senar 6.'),
-- Altered chord (Pro)
(33, 'C7#9',   'C', '7#9',    'pro',          1, 4, 0, 'Altered chord C7#9, warna blues/funk yang khas.'),
(34, 'E7#9',   'E', '7#9',    'pro',          1, 4, 0, 'Altered chord E7#9, dikenal sebagai "Hendrix chord".'),
(35, 'C7b9',   'C', '7b9',    'pro',          1, 4, 0, 'Altered chord C7b9 dengan nada ke-9 diturunkan setengah nada.'),
-- Chord dasar tambahan
(36, 'A',      'A', 'major',  'beginner',     1, 4, 0, 'Chord A mayor terbuka, jari 1-2-3 di senar 4-3-2 fret 2.'),
(37, 'B',      'B', 'major',  'intermediate', 2, 3, 1, 'Chord B mayor dengan barre telunjuk di fret 2.'),
(38, 'E',      'E', 'major',  'beginner',     1, 4, 0, 'Chord E mayor terbuka: jari 1 di senar 3 fret 1, jari 2 dan 3 di fret 2.');

-- -------------------------------------------------------------
-- C. POSISI JARI (chord_fingering)
--    string_num : 1 = senar paling kecil, 6 = senar paling tebal
--    fret       : 0 = open string (dinyanyikan tanpa ditekan)
--    finger     : 1..4 = jari telunjuk/tengah/cincin/kelingking
--                 NULL  = senar terbuka, tidak dipakai jari
--                 (barre tetap diisi 1, jadi nomor 1 = telunjuk)
--    WAJIB: setiap senar yang dipetik harus punya baris, termasuk
--           senar terbuka (fret 0). Senar yang sengaja tidak
--           dipetik TIDAK diberi baris sama sekali -> muncul "X".
--
--    Bentuk ditulis dari senar 6 (tebal) ke senar 1 (halus).
--    Sudah divalidasi: bentuk, nada, nomor jari, is_barre, area gambar.
-- -------------------------------------------------------------

-- C  : x 3 2 0 1 0   (jari 3-2-1)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(1, 5,3,3),(1, 4,2,2),(1, 3,0,NULL),(1, 2,1,1),(1, 1,0,NULL);

-- Am : x 0 2 2 1 0   (A string open, jari 2-3-1)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(2, 5,0,NULL),(2, 4,2,2),(2, 3,2,3),(2, 2,1,1),(2, 1,0,NULL);

-- G  : 3 2 0 0 0 3   (jari 2-1-3)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(3, 6,3,2),(3, 5,2,1),(3, 4,0,NULL),(3, 3,0,NULL),(3, 2,0,NULL),(3, 1,3,3);

-- D  : x x 0 2 3 2   (senar D terbuka, jari 2-3-1)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(4, 4,0,NULL),(4, 3,2,2),(4, 2,3,3),(4, 1,2,1);

-- Em : 0 2 2 0 0 0   (senar G terbuka, jari 1-2)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(5, 6,0,NULL),(5, 5,2,1),(5, 4,2,2),(5, 3,0,NULL),(5, 2,0,NULL),(5, 1,0,NULL);

-- Dm : x x 0 2 3 1   (senar D terbuka, jari 2-3-1)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(6, 4,0,NULL),(6, 3,2,2),(6, 2,3,3),(6, 1,1,1);

-- F  : 1 3 3 2 1 1   (barre telunjuk di fret 1, senar 6-1)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(7, 6,1,1),(7, 5,3,3),(7, 4,3,4),(7, 3,2,2),(7, 2,1,1),(7, 1,1,1);

-- B7 : x 2 1 2 0 2   (jari 1-1-2-3)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(8, 5,2,1),(8, 4,1,1),(8, 3,2,2),(8, 2,0,NULL),(8, 1,2,3);

-- C7 : x 3 2 3 1 0   (jari 3-2-4-1)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(9, 5,3,3),(9, 4,2,2),(9, 3,3,4),(9, 2,1,1),(9, 1,0,NULL);

-- A7 : x 0 2 0 2 0   (A string open, jari 2-1)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(10, 5,0,NULL),(10, 4,2,2),(10, 3,0,NULL),(10, 2,2,1),(10, 1,0,NULL);

-- E7 : 0 2 0 1 0 0   (senar D terbuka, jari 2-1)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(11, 6,0,NULL),(11, 5,2,2),(11, 4,0,NULL),(11, 3,1,1),(11, 2,0,NULL),(11, 1,0,NULL);

-- G7 : 3 2 0 0 0 1   (jari 1-2-3)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(12, 6,3,1),(12, 5,2,2),(12, 4,0,NULL),(12, 3,0,NULL),(12, 2,0,NULL),(12, 1,1,3);

-- Fmaj7 : x x 3 2 1 0   (senar 1 terbuka = nada E, itulah maj7-nya)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(13, 4,3,4),(13, 3,2,2),(13, 2,1,1),(13, 1,0,NULL);

-- Cmaj7 : x 3 2 0 0 0   (senar B & E terbuka, jari 3-2)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(14, 5,3,3),(14, 4,2,2),(14, 3,0,NULL),(14, 2,0,NULL),(14, 1,0,NULL);

-- Am7 : x 0 2 0 1 0   (A string open, jari 1-2)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(15, 5,0,NULL),(15, 4,2,1),(15, 3,0,NULL),(15, 2,1,2),(15, 1,0,NULL);

-- Dsus4 : x x 0 2 3 3   (senar D terbuka, jari 1-2-3)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(16, 4,0,NULL),(16, 3,2,1),(16, 2,3,2),(16, 1,3,3);

-- Asus2 : x 0 2 2 0 0   (jari 1-2-3)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(17, 5,0,NULL),(17, 4,2,1),(17, 3,2,2),(17, 2,0,NULL),(17, 1,0,NULL);

-- Em7 : 0 2 0 0 0 0   (jari 2 saja)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(18, 6,0,NULL),(18, 5,2,2),(18, 4,0,NULL),(18, 3,0,NULL),(18, 2,0,NULL),(18, 1,0,NULL);

-- Gmaj7 : 3 x 0 0 0 2   (senar 5 di-mute, jari 1-2)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(19, 6,3,1),(19, 4,0,NULL),(19, 3,0,NULL),(19, 2,0,NULL),(19, 1,2,2);

-- Bm : x 2 4 4 3 2   (barre telunjuk di fret 2, senar 5-1)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(20, 5,2,1),(20, 4,4,3),(20, 3,4,4),(20, 2,3,2),(20, 1,2,1);

-- C5 : 8 10 10 x x x   (power chord di senar 6-5-4)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(21, 6,8,1),(21, 5,10,3),(21, 4,10,4);

-- D5 : 10 12 12 x x x   (power chord di senar 6-5-4)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(22, 6,10,1),(22, 5,12,3),(22, 4,12,4);

-- E5 : 0 2 2 x x x   (power chord di senar 6-5-4)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(23, 6,0,NULL),(23, 5,2,1),(23, 4,2,3);

-- G5 : 3 5 5 x x x   (power chord di senar 6-5-4)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(24, 6,3,1),(24, 5,5,3),(24, 4,5,4);

-- A5 : 5 7 7 x x x   (power chord di senar 6-5-4)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(25, 6,5,1),(25, 5,7,3),(25, 4,7,4);

-- C9 : x 3 2 3 3 x
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(26, 5,3,3),(26, 4,2,2),(26, 3,3,4),(26, 2,3,1);

-- Cmaj9 : x 3 2 4 3 x
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(27, 5,3,3),(27, 4,2,2),(27, 3,4,4),(27, 2,3,1);

-- Em9 : 0 2 0 0 0 2
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(28, 6,0,NULL),(28, 5,2,1),(28, 4,0,NULL),(28, 3,0,NULL),(28, 2,0,NULL),(28, 1,2,2);

-- C/G : 3 3 2 0 1 0
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(29, 6,3,3),(29, 5,3,4),(29, 4,2,2),(29, 3,0,NULL),(29, 2,1,1),(29, 1,0,NULL);

-- D/F# : 2 x 0 2 3 2
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(30, 6,2,1),(30, 4,0,NULL),(30, 3,2,2),(30, 2,3,3),(30, 1,2,4);

-- G/B : x 2 0 0 3 3
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(31, 5,2,1),(31, 4,0,NULL),(31, 3,0,NULL),(31, 2,3,2),(31, 1,3,3);

-- Am/G : 3 0 2 2 1 0
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(32, 6,3,3),(32, 5,0,NULL),(32, 4,2,2),(32, 3,2,4),(32, 2,1,1),(32, 1,0,NULL);

-- C7#9 : x 3 2 3 4 x
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(33, 5,3,2),(33, 4,2,1),(33, 3,3,3),(33, 2,4,4);

-- E7#9 : 0 2 0 1 0 3
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(34, 6,0,NULL),(34, 5,2,1),(34, 4,0,NULL),(34, 3,1,2),(34, 2,0,NULL),(34, 1,3,3);

-- C7b9 : x 3 2 3 2 x
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(35, 5,3,3),(35, 4,2,2),(35, 3,3,4),(35, 2,2,1);

-- A : x 0 2 2 2 0   (jari 1-2-3)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(36, 5,0,NULL),(36, 4,2,3),(36, 3,2,2),(36, 2,2,1),(36, 1,0,NULL);

-- B : x 2 4 4 4 2   (barre telunjuk di fret 2)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(37, 5,2,1),(37, 4,4,2),(37, 3,4,3),(37, 2,4,4),(37, 1,2,1);

-- E : 0 2 2 1 0 0   (jari 1-2-3)
INSERT INTO chord_fingering (chord_id, string_num, fret, finger) VALUES
(38, 6,0,NULL),(38, 5,2,2),(38, 4,2,3),(38, 3,1,1),(38, 2,0,NULL),(38, 1,0,NULL);

-- -------------------------------------------------------------
-- D. LAGU
--    Format lirik: chord ditulis dalam kurung siku [X]
--    Setiap level belajar berisi 3 lagu, lirik lengkap sampai akhir.
-- -------------------------------------------------------------
INSERT INTO songs (id, title, artist, level, capo, tempo, notes, lyrics) VALUES
(1, 'Pompeii', 'Bastille', 'beginner', NULL, '127 BPM',
 'Latihan perpindahan chord C - G - Em - D dengan tempo sedang.',
 '[Intro] [2x]\n[C] [G] [Em] [D]\n\n[Verse 1]\n[C]I was left to my [G]own de[Em]vices[D]\n[C]Many days fell a[G]way with nothing to [Em]show[D]\n[C]And the walls kept tumbling [G]down\nIn the [Em]city that we [D]love\n[C]Great clouds roll over the [G]hills\nBringing [Em]darkness from a[D]bove\n\n[Chorus]\n[C]But if you close your [G]eyes\nDoes it almost feel like [Em]nothing changed at [D]all?\n[C]And if you close your [G]eyes\nDoes it almost feel like [Em]you''ve been here be[D]fore?\n[Em]How am I gonna be an [D]optimist about [C]this?\n[Em]Oh, where do we [D]begin?[C]\n\n[Verse 2]\n[C]We were caught up and [G]lost in all of the [Em]vices[D]\n[C]In your pose as the [G]dust settled a[Em]round[D]\n[C]And the walls kept tumbling [G]down\nIn the [Em]city that we [D]love\n[C]Great clouds roll over the [G]hills\nBringing [Em]darkness from a[D]bove\n\n[Chorus]\n[C]But if you close your [G]eyes\nDoes it almost feel like [Em]nothing changed at [D]all?\n[C]And if you close your [G]eyes\nDoes it almost feel like [Em]you''ve been here be[D]fore?\n[Em]How am I gonna be an [D]optimist about [C]this?\n[Em]Oh, where do we [D]begin?[C]\n\n[Bridge]\n[C]Oh, where do we [G]begin?\n[Em]Oh, where do we [D]begin?[C]\n[C]Oh, where do we [G]begin?\n[Em]Oh, where do we [D]begin?[C]\n\n[Verse 3]\n[C]And the walls kept tumbling [G]down\nIn the [Em]city that we [D]love\n[C]Great clouds roll over the [G]hills\nBringing [Em]darkness from a[D]bove\n\n[Chorus]\n[C]But if you close your [G]eyes\nDoes it almost feel like [Em]nothing changed at [D]all?\n[C]And if you close your [G]eyes\nDoes it almost feel like [Em]you''ve been here be[D]fore?\n[Em]How am I gonna be an [D]optimist about [C]this?\n[Em]Oh, where do we [D]begin?[C]\n\n[Outro]\n[Em]How am I gonna be an [D]optimist about [C]this?\n[Em]Oh, where do we [D]begin?[C]\n[C] [G] [Em] [D]\n[C] [G] [Em] [D]\n[C]'),

(2, 'Cinta Luar Biasa', 'Andmesh Kamaleng', 'beginner', NULL, '75 BPM',
 'Chord dasar G - D - Em - C, cocok untuk latihan ritme santai.',
 '[Intro] [G] [D] [Em] [C]\n\n[Verse 1]\n[G]Saat pertama kali ku melihat[D]mu\n[Em]Kau tersenyum manis di [C]mataku\n[G]Jantungku berdebar tak [D]karuan\n[Em]Mungkin ini cinta yang [C]ditunggu\n\n[Verse 2]\n[G]Ku tak pernah merasakan [D]sebelumnya\n[Em]Cinta yang begitu indah [C]rasanya\n[G]Ku ingin kau tahu [D]isi hatiku\n[Em]Bahwa ku mencintaimu [C]sepenuh hati\n\n[Chorus]\n[C]Kau adalah [D]cinta luar [G]biasa\n[C]Yang pernah [D]ada dalam [G]hidupku\n[C]Kau adalah [D]cinta luar [G]biasa\n[Em]Ku ingin [C]kau selalu [D]di sini\n\n[Verse 3]\n[G]Ku berharap kau juga [D]merasakan\n[Em]Cinta yang tulus dari [C]hatiku\n[G]Ku ingin kau tahu [D]isi hatiku\n[Em]Bahwa ku mencintaimu [C]sepenuh hati\n\n[Chorus]\n[C]Kau adalah [D]cinta luar [G]biasa\n[C]Yang pernah [D]ada dalam [G]hidupku\n[C]Kau adalah [D]cinta luar [G]biasa\n[Em]Ku ingin [C]kau selalu [D]di sini\n\n[Bridge]\n[C]Cinta ini [D]tulus untuk[G]mu\n[C]Takkan pernah [D]pudar oleh [G]waktu\n[C]Ku ingin [D]kau selalu [G]di sini\n[Em]Menemani [C]hidup[D]ku\n\n[Chorus]\n[C]Kau adalah [D]cinta luar [G]biasa\n[C]Yang pernah [D]ada dalam [G]hidupku\n[C]Kau adalah [D]cinta luar [G]biasa\n[Em]Ku ingin [C]kau selalu [D]di sini\n\n[Outro]\n[G] [D] [Em] [C]\n[G]'),

(3, 'Akad', 'Payung Teduh', 'beginner', NULL, '80 BPM',
 'Chord Am - Dm - G - C - E dengan nuansa folk yang tenang.',
 '[Intro] [Am] [Dm] [G] [C] [E]\n\n[Verse 1]\n[Am]Perempuan yang cantik\n[Dm]Ini adalah tentang kita\n[G]Kita yang berjalan di [C]bawah langit\n[E]Yang tak pernah memandang\n\n[Verse 2]\n[Am]Ku ingin engkau tahu\n[Dm]Betapa ku mencintaimu\n[G]Dan aku tak bisa [C]melepaskanmu\n[E]Walau hanya sekejap\n\n[Chorus]\n[Am]Akad [Dm]telah [G]terikat [C]\n[E]Kau dan aku [Am]satu\n[Am]Sampai [Dm]akhir [G]waktu [C]\n[E]Selamanya\n\n[Verse 3]\n[Am]Ku ingin engkau tahu\n[Dm]Betapa ku mencintaimu\n[G]Dan aku tak bisa [C]melepaskanmu\n[E]Walau hanya sekejap\n\n[Chorus]\n[Am]Akad [Dm]telah [G]terikat [C]\n[E]Kau dan aku [Am]satu\n[Am]Sampai [Dm]akhir [G]waktu [C]\n[E]Selamanya\n\n[Bridge]\n[Am]Perempuan yang cantik\n[Dm]Ini adalah tentang kita\n[G]Kita yang berjalan di [C]bawah langit\n[E]Yang tak pernah memandang\n\n[Chorus]\n[Am]Akad [Dm]telah [G]terikat [C]\n[E]Kau dan aku [Am]satu\n[Am]Sampai [Dm]akhir [G]waktu [C]\n[E]Selamanya\n[E]Selamanya\n\n[Outro]\n[Am] [Dm] [G] [C] [E]\n[Am]'),

(4, 'Melukis Senja', 'Budi Doremi', 'intermediate', NULL, '78 BPM',
 'Latihan chord F (barre) dan perpindahan C - G - Am - F.',
 '[Intro] [C] [G] [Am] [F]\n\n[Verse 1]\n[C]Ku ingin engkau tahu\n[G]Betapa ku menyayangimu\n[Am]Ku ingin engkau tahu\n[F]Betapa ku mencintaimu\n\n[Verse 2]\n[C]Aku mengerti pera[G]saanmu\n[Am]Yang selama ini tak [F]kau ungkapkan\n[C]Aku mengerti kamu [G]mencintaiku\n[Am]Namun tak berani [F]mengungkapkan\n\n[Chorus]\n[C]Izinkan ku melukis [G]senja\n[Am]Untukmu yang sedang [F]berjuang\n[C]Izinkan ku menghibur [G]hatimu\n[Am]Walau hanya lewat [F]lagu\n\n[Verse 3]\n[C]Ku ingin engkau tahu\n[G]Betapa ku menyayangimu\n[Am]Ku ingin engkau tahu\n[F]Betapa ku mencintaimu\n\n[Chorus]\n[C]Izinkan ku melukis [G]senja\n[Am]Untukmu yang sedang [F]berjuang\n[C]Izinkan ku menghibur [G]hatimu\n[Am]Walau hanya lewat [F]lagu\n\n[Bridge]\n[Dm]Ku ingin engkau [G]bahagia\n[C]Walau tak bersama[F]ku\n[Dm]Ku ingin engkau [G]bahagia\n[C]Selamanya\n\n[Chorus]\n[C]Izinkan ku melukis [G]senja\n[Am]Untukmu yang sedang [F]berjuang\n[C]Izinkan ku menghibur [G]hatimu\n[Am]Walau hanya lewat [F]lagu\n\n[Outro]\n[Dm]Ku ingin engkau [G]bahagia\n[C]Walau tak bersama[F]ku\n[Dm]Ku ingin engkau [G]bahagia\n[C]Selamanya\n[C]'),

(5, 'Kangen', 'Dewa 19', 'intermediate', NULL, '72 BPM',
 'Lagu ballada dengan chord C - G - Am - Em - F - Dm.',
 '[Intro] [C] [G] [Am] [Em] [F] [Dm] [G]\n\n[Chorus]\n[C]Kangen, [G]aku kangen [Am]kamu [Em]\n[F]Kangen, [Dm]sangat kangen [G]kamu\n[C]Kangen, [G]aku kangen [Am]kamu [Em]\n[F]Kangen, [Dm]sangat kangen [G]kamu\n\n[Verse 1]\n[C]Separuh nafasku [G]terbang bersamamu\n[Am]Ku menahan tangis [Em]di hari ini\n[F]Karena kau pergi [Dm]tinggalkan aku\n[G]Tanpa alasan yang pasti\n\n[Chorus]\n[C]Kangen, [G]aku kangen [Am]kamu [Em]\n[F]Kangen, [Dm]sangat kangen [G]kamu\n[C]Kangen, [G]aku kangen [Am]kamu [Em]\n[F]Kangen, [Dm]sangat kangen [G]kamu\n\n[Verse 2]\n[C]Ku ingin kau tahu [G]betapa ku mencintaimu\n[Am]Ku ingin kau tahu [Em]betapa ku merindukanmu\n[F]Ku ingin kau kembali [Dm]padaku\n[G]Jangan tinggalkan aku\n\n[Chorus]\n[C]Kangen, [G]aku kangen [Am]kamu [Em]\n[F]Kangen, [Dm]sangat kangen [G]kamu\n[C]Kangen, [G]aku kangen [Am]kamu [Em]\n[F]Kangen, [Dm]sangat kangen [G]kamu\n\n[Bridge]\n[Am]Ku tak bisa [Em]melupakanmu\n[F]Ku tak bisa [C]tanpamu\n[Am]Ku tak bisa [Em]melupakanmu\n[F]Ku tak bisa [G]tanpamu\n\n[Chorus]\n[C]Kangen, [G]aku kangen [Am]kamu [Em]\n[F]Kangen, [Dm]sangat kangen [G]kamu\n[C]Kangen, [G]aku kangen [Am]kamu [Em]\n[F]Kangen, [Dm]sangat kangen [G]kamu\n\n[Outro]\n[C] [G] [Am] [Em]\n[F] [Dm] [G] [C]'),

(6, 'Bintang di Surga', 'Peterpan', 'intermediate', NULL, '76 BPM',
 'Latihan dinamika strumming dengan chord C - G - Am - F.',
 '[Intro] [C] [G] [Am] [F]\n\n[Verse 1]\n[C]Masih ku rasakan [G]getaran itu\n[Am]Saat kau menyentuh [F]tanganku\n[C]Masih ku rasakan [G]hangat pelukan\n[Am]Yang kau berikan [F]padaku\n\n[Verse 2]\n[C]Masih ku rasakan [G]getaran itu\n[Am]Saat kau menyentuh [F]tanganku\n[C]Masih ku rasakan [G]hangat pelukan\n[Am]Yang kau berikan [F]padaku\n\n[Chorus]\n[C]Bintang di surga [G]tetaplah bersinar\n[Am]Walau kau tak lagi [F]di sini\n[C]Bintang di surga [G]tetaplah bersinar\n[Am]Di dalam hatiku [F]selamanya\n\n[Verse 3]\n[C]Dan aku takkan pernah [G]lupa\n[Am]Semua yang pernah kita [F]lalui\n[C]Dan aku takkan pernah [G]bisa\n[Am]Melupakan dirimu [F]\n\n[Chorus]\n[C]Bintang di surga [G]tetaplah bersinar\n[Am]Walau kau tak lagi [F]di sini\n[C]Bintang di surga [G]tetaplah bersinar\n[Am]Di dalam hatiku [F]selamanya\n\n[Bridge]\n[C]Walau kau jauh [G]di sana\n[Am]Ku kan selalu [F]menjagamu\n[C]Walau kau jauh [G]di sana\n[Am]Ku kan selalu [F]mencintaimu\n\n[Chorus]\n[C]Bintang di surga [G]tetaplah bersinar\n[Am]Walau kau tak lagi [F]di sini\n[C]Bintang di surga [G]tetaplah bersinar\n[Am]Di dalam hatiku [F]selamanya\n\n[Outro]\n[C] [G] [Am] [F]\n[C] [G] [Am] [F]\n[C]'),

(7, 'Bukan Cinta Biasa', 'Afgan', 'pro', NULL, '70 BPM',
 'Slash chord G/B untuk latihan bass berjalan dan transisi chord pro.',
 '[Intro] [C] [G/B] [Am] [F] [Dm] [E7]\n\n[Verse 1]\n[C]Ku ingin engkau tahu [G/B]isi hatiku\n[Am]Betapa ku menyayangi[F]mu\n[C]Ku ingin engkau tahu [G/B]rasa cintaku\n[Am]Yang selama ini [F]kupendam\n\n[Chorus]\n[Dm]Bukan cinta [E7]biasa\n[Am]Yang kurasakan [F]untukmu\n[Dm]Bukan cinta [E7]biasa\n[Am]Yang membuatku [F]terpuruk\n\n[Verse 2]\n[C]Ku ingin engkau tahu [G/B]isi hatiku\n[Am]Betapa ku menyayangi[F]mu\n[C]Ku ingin engkau tahu [G/B]rasa cintaku\n[Am]Yang selama ini [F]kupendam\n\n[Chorus]\n[Dm]Bukan cinta [E7]biasa\n[Am]Yang kurasakan [F]untukmu\n[Dm]Bukan cinta [E7]biasa\n[Am]Yang membuatku [F]terpuruk\n\n[Bridge]\n[C]Ku tak ingin kau [G/B]pergi\n[Am]Tinggalkan aku [F]sendiri\n[C]Ku tak ingin kau [G/B]pergi\n[Am]Jangan tinggalkan [F]aku\n\n[Chorus]\n[Dm]Bukan cinta [E7]biasa\n[Am]Yang kurasakan [F]untukmu\n[Dm]Bukan cinta [E7]biasa\n[Am]Yang membuatku [F]terpuruk\n\n[Outro]\n[C] [G/B] [Am] [F]\n[Dm] [E7] [C]'),

(8, 'Terima Kasih Cinta', 'Afgan', 'pro', NULL, '68 BPM',
 'Slash chord G/B dan E mayor untuk latihan transisi chord lanjutan.',
 '[Intro] [C] [G/B] [Am] [F]\n\n[Verse 1]\n[C]Aku tak bisa [G/B]terlalu jauh\n[Am]Untuk melupakan [F]dirimu\n[C]Aku tak bisa [G/B]berpaling arah\n[Am]Meninggalkan semua [F]kenangan\n\n[Chorus]\n[Dm]Terima kasih [E]cinta\n[Am]Kau telah mengajar[F]kan aku\n[Dm]Arti kesetiaan [E]dan ketulusan\n[Am]Dalam mencintai [F]dirimu\n\n[Verse 2]\n[C]Aku tak bisa [G/B]terlalu jauh\n[Am]Untuk melupakan [F]dirimu\n[C]Aku tak bisa [G/B]berpaling arah\n[Am]Meninggalkan semua [F]kenangan\n\n[Chorus]\n[Dm]Terima kasih [E]cinta\n[Am]Kau telah mengajar[F]kan aku\n[Dm]Arti kesetiaan [E]dan ketulusan\n[Am]Dalam mencintai [F]dirimu\n\n[Bridge]\n[C]Ku takkan pernah [G/B]bisa\n[Am]Melupakan [F]dirimu\n[C]Ku takkan pernah [G/B]bisa\n[Am]Meninggalkan [F]dirimu\n\n[Chorus]\n[Dm]Terima kasih [E]cinta\n[Am]Kau telah mengajar[F]kan aku\n[Dm]Arti kesetiaan [E]dan ketulusan\n[Am]Dalam mencintai [F]dirimu\n\n[Outro]\n[C] [G/B] [Am] [F]\n[C]'),

(9, 'Runtuh', 'Feby Putri', 'pro', NULL, '70 BPM',
 'Menggunakan Bm (barre) untuk latihan chord lanjutan.',
 '[Intro] [G] [D] [Em] [C] [Am] [Bm]\n\n[Verse 1]\n[G]Ku takut kehilangan[D]mu\n[Em]Ku takut sendiri [C]\n[Am]Namun ku tahu [Bm]semua\n[G]Akan berakhir [D]juga\n\n[Chorus]\n[G]Runtuhlah semua [D]harapanku\n[Em]Bila kau tak di [C]sini\n[Am]Biarlah waktu [Bm]menyembuhkan\n[G]Luka yang ter[D]dalam ini\n\n[Verse 2]\n[G]Ku takut kehilangan[D]mu\n[Em]Ku takut sendiri [C]\n[Am]Namun ku tahu [Bm]semua\n[G]Akan berakhir [D]juga\n\n[Chorus]\n[G]Runtuhlah semua [D]harapanku\n[Em]Bila kau tak di [C]sini\n[Am]Biarlah waktu [Bm]menyembuhkan\n[G]Luka yang ter[D]dalam ini\n\n[Bridge]\n[Am]Ku ingin kau tahu [Bm]betapa ku mencintaimu\n[G]Ku ingin kau tahu [D]betapa ku menyayangimu\n[Am]Ku ingin kau tahu [Bm]betapa ku mencintaimu\n[G]Ku ingin kau tahu [D]betapa ku menyayangimu\n\n[Chorus]\n[G]Runtuhlah semua [D]harapanku\n[Em]Bila kau tak di [C]sini\n[Am]Biarlah waktu [Bm]menyembuhkan\n[G]Luka yang ter[D]dalam ini\n\n[Outro]\n[G] [D] [Em] [C]\n[Am] [Bm] [G]');

-- chord yang dipakai tiap lagu
INSERT INTO song_chords (song_id, chord_id, sort_order) VALUES
-- Pompeii
(1, 1, 1), (1, 3, 2), (1, 5, 3), (1, 4, 4),
-- Cinta Luar Biasa
(2, 3, 1), (2, 4, 2), (2, 5, 3), (2, 1, 4),
-- Akad
(3, 2, 1), (3, 6, 2), (3, 3, 3), (3, 1, 4), (3, 38, 5),
-- Melukis Senja
(4, 1, 1), (4, 3, 2), (4, 2, 3), (4, 7, 4), (4, 6, 5),
-- Kangen
(5, 1, 1), (5, 3, 2), (5, 2, 3), (5, 5, 4), (5, 7, 5), (5, 6, 6),
-- Bintang di Surga
(6, 1, 1), (6, 3, 2), (6, 2, 3), (6, 7, 4),
-- Bukan Cinta Biasa
(7, 1, 1), (7, 31, 2), (7, 2, 3), (7, 7, 4), (7, 6, 5), (7, 11, 6),
-- Terima Kasih Cinta
(8, 1, 1), (8, 31, 2), (8, 2, 3), (8, 7, 4), (8, 6, 5), (8, 38, 6),
-- Runtuh
(9, 3, 1), (9, 4, 2), (9, 5, 3), (9, 1, 4), (9, 2, 5), (9, 20, 6);

-- -------------------------------------------------------------
-- E. PENGATURAN
-- -------------------------------------------------------------
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'GuitarTice'),
('site_tagline', 'Belajar Gitar Santai'),
('site_description', 'Platform belajar gitar dengan chord library, latihan finger, dan progres belajar yang tersimpan.');
