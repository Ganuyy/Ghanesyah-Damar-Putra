-- =============================================================
--  GUITARTICE - Tambah Lagu: Naff - Kau Masih Kekasihku
--
--  Jalankan pada database yang sudah ada:
--      mysql -u root db_guitarlearn < sql/06_naff_kau_masih_kekasihku.sql
--
--  Lagu level beginner dengan chord dasar G - D - Em - C.
-- =============================================================

USE db_guitarlearn;

INSERT INTO songs (title, artist, level, capo, tempo, notes, lyrics)
SELECT 'Kau Masih Kekasihku', 'Naff', 'beginner', NULL, '72 BPM',
  'Chord dasar G - D - Em - C, cocok untuk latihan petikan dan perpindahan chord santai.',
  '[Intro]\n[G] [D] [C]\n\n[Verse 1]\n[G]Jauh di lubuk [D]hatiku\n[Em]Masih terukir [C]namamu\n[G]Jauh di dasar [D]jiwaku\n[Em]Engkau masih ke[C]kasihku\n\n[Verse 2]\n[G]Tak bisa kutahan laju angin\n[Em]Untuk semua kenangan yang berlalu\n[D]Hembuskan sepi, merobek [C]hati\n\n[Verse 3]\n[G]Meski raga ini tak lagi milikku\n[Em]Namun di dalam hatiku sungguh engkau hidup\n[D]Entah sampai kapan kutahankan [C]rasa cinta ini\n\n[Reff]\n[G]Jauh di lubuk [D]hatiku\n[Em]Masih terukir [C]namamu\n[G]Jauh di dasar [D]jiwaku\n[Em]Engkau masih ke[C]kasihku\n[G]Dan kuberharap semua ini\n[Em]Bukanlah kekeliruan seperti yang kukira\n[D]Seumur hidupku [C]akan menjadi doa untukmu\n\n[Reff]\n[G]Jauh di lubuk [D]hatiku\n[Em]Masih terukir [C]namamu\n[G]Jauh di dasar [D]jiwaku\n[Em]Engkau masih ke[C]kasihku\n[G]Dan kuberharap semua ini\n[Em]Bukanlah kekeliruan seperti yang kukira\n[D]Seumur hidupku [C]akan menjadi doa untukmu\n\n[Bridge]\n[D]Andai saja waktu [C]bisa terulang [D]kembali\n[C]Akan kuserahkan hidupku [D]di sisimu\n[C]Namun ku tahu itu tak ''kan [D]mungkin terjadi\n[C]Rasa ini menyiksaku, sungguh-sungguh menyiksaku\n\n[Interlude]\n[E] [A] [E] [A]\n[C#m] [B] [A] [F] [Bb]\n\n[Reff] (Overtune)\n[Bb]Jauh di lubuk [F]hatiku\n[Gm]Masih terukir [Eb]namamu\n[Bb]Jauh di dasar [F]jiwaku\n[Gm]Engkau masih ke[Eb]kasihku\n[Bb]Dan kuberharap semua ini\n[Gm]Bukanlah kekeliruan seperti yang kukira\n[F]Seumur hidupku [Eb]akan menjadi doa untukmu\n\n[Outro]\n[Bb]Jauh di lubuk hatiku'
WHERE NOT EXISTS (SELECT 1 FROM songs WHERE title = 'Kau Masih Kekasihku');

SELECT id INTO @sid FROM songs WHERE title = 'Kau Masih Kekasihku' LIMIT 1;
DELETE FROM song_chords WHERE song_id = @sid;
INSERT INTO song_chords (song_id, chord_id, sort_order)
SELECT @sid, id, 1 FROM chords WHERE name = 'G'
UNION ALL SELECT @sid, id, 2 FROM chords WHERE name = 'D'
UNION ALL SELECT @sid, id, 3 FROM chords WHERE name = 'Em'
UNION ALL SELECT @sid, id, 4 FROM chords WHERE name = 'C'
UNION ALL SELECT @sid, id, 5 FROM chords WHERE name = 'E'
UNION ALL SELECT @sid, id, 6 FROM chords WHERE name = 'A';
