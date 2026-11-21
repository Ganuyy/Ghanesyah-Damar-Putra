<?php
/**
 * chord_svg.php
 * Generator diagram posisi jari gitar dalam format SVG.
 *
 * Cara kerja:
 *   - Sumber data = tabel `chords` (bentuk chord, fret awal) dan
 *     `chord_fingering` (posisi jari per senar).
 *   - Diagram digambar langsung sebagai kode SVG, jadi tidak perlu
 *     gambar/gambar PNG sama sekali.
 *   - Senar yang tidak punya baris di chord_fingering  -> "X" (tidak dipetik)
 *     Senar dengan fret = 0                            -> "O" (open string)
 *   - BARREREctions: dua senar berurutan dengan fret + nomor jari yang
 *     sama dianggap satu batang, digambar sebagai rounded rect.
 *
 * Semua warna memakai CSS variable (lihat assets/style.css), jadi tema
 * terang/gelap cukup diubah dari CSS tanpa menyentuh PHP ini.
 */

// ==========================================================
//  0. Geometri diagram (satu sumber kebenaran)
//     Dipakai bersama oleh render_chord_svg() dan
//     chord_finger_dots() supaya titik jari dan batang selalu
//     berada tepat di antara dua senar.
// ==========================================================
const GL_PAD_L    = 30;   // ruang kiri untuk nomor fret
const GL_PAD_T    = 34;   // ruang atas untuk penanda O / X
const GL_PAD_R    = 22;
const GL_PAD_B    = 22;
const GL_STR_GAP  = 27;   // jarak antar senar
const GL_FRT_GAP  = 30;   // jarak antar fret

// ==========================================================
//  1. Hitung status tiap senar: 'x' (mute) | 'o' (open) | 'fretted'
// ==========================================================
/**
 * @param array $chord  baris chords + kunci 'fingering'
 * @return array<int, string>  string_num 1..6 => status
 */
function chord_string_status($chord)
{
    $status = [];

    for ($s = 1; $s <= 6; $s++) {
        if (!isset($chord['fingering'][$s])) {
            $status[$s] = 'x';                       // tidak ada data -> mute
        } elseif ((int) $chord['fingering'][$s]['fret'] === 0) {
            $status[$s] = 'o';                       // senar terbuka
        } else {
            $status[$s] = 'fretted';
        }
    }

    return $status;
}

/**
 * Tentukan batang (barre) yang layak digambar.
 *
 *BATANG TIDAK LAGI DITEBAK dari nomor jari. Sebuah batang hanya
 * digambar bila:
 *   1. chord ditandai is_barre = 1 di database, dan
 *   2. ada minimal 2 senar dengan fret + nomor jari yang sama, dan
 *   3. secara fisik mungkin: setiap senar di antara ujung batang
 *      harus dipetik pada fret >= fret batang (bukan di-mute, dan
 *      bukan lebih rendah).
 *
 * Aturan (3) mencegah batang palsu muncul, misalnya saat senar di
 * tengah di-mute sehingga jarinya sebenarnya terputus.
 *
 * @param array $chord baris chords, sudah berisi kunci 'fingering'
 * @param array $dots  hasil perhitungan, urut senar 6 -> 1
 * @return array list of ['x1','x2','y','finger','from','to']
 */
function chord_barres($chord, $dots)
{
    if ((int) ($chord['is_barre'] ?? 0) !== 1) {
        return [];
    }

    $groups = [];

    foreach ($dots as $d) {
        if ($d['finger'] === null) {
            continue;                              // tanpa nomor jari = bukan batang
        }
        $key = $d['fret'] . ':' . $d['finger'];

        if (!isset($groups[$key])) {
            $groups[$key] = [
                'fret'   => $d['fret'],
                'finger' => $d['finger'],
                'from'   => $d['string'],           // senar terkecil (nomor besar)
                'to'     => $d['string'],           // senar terbesar (nomor kecil)
                'x1'     => $d['x'],
                'x2'     => $d['x'],
                'y'      => $d['y'],
            ];
        } else {
            $groups[$key]['from'] = min($groups[$key]['from'], $d['string']);
            $groups[$key]['to']   = max($groups[$key]['to'], $d['string']);
            $groups[$key]['x1']   = min($groups[$key]['x1'], $d['x']);
            $groups[$key]['x2']   = max($groups[$key]['x2'], $d['x']);
        }
    }

    $fretOf = function ($stringNum) use ($chord) {
        if (!isset($chord['fingering'][$stringNum])) {
            return null;                            // di-mute
        }
        return (int) $chord['fingering'][$stringNum]['fret'];
    };

    $barres = [];

    foreach ($groups as $g) {
        if ($g['x2'] <= $g['x1']) {
            continue;                               // hanya satu senar = bukan batang
        }

        // cek kelayakan fisik
        $possible = true;
        for ($s = $g['from']; $s >= $g['to']; $s--) {
            $f = $fretOf($s);
            if ($f === null || $f < $g['fret']) {
                $possible = false;
                break;
            }
        }
        if ($possible) {
            $barres[] = $g;
        }
    }

    return $barres;
}

// ==========================================================
//  2. Generator SVG utama
// ==========================================================
/**
 * render_chord_svg($chord, $opts)
 *
 * @param array $chord  ['name'=>..,'start_fret'=>..,'frets_shown'=>..,
 *                       'fingering'=>[1=>['fret'=>..,'finger'=>..], ...]]
 * @param array $opts   'scale'  : 1.0 (kartu kecil) .. 2.2 (besar)
 *                       'show_open' : tampilkan penanda O / X   (default true)
 *                       'title'    : teks untuk aksesibilitas     (default nama chord)
 *
 * @return string  kode SVG
 */
function render_chord_svg($chord, $opts = [])
{
    $name       = $chord['name'] ?? '?';
    $scale      = (float) ($opts['scale'] ?? 1);
    $showOpen   = $opts['show_open'] ?? true;
    $title      = $opts['title'] ?? ('Diagram chord ' . $name);

    // Ukuran dasar memakai konstanta global (lihat bagian 0)
    $padL   = GL_PAD_L;
    $padT   = GL_PAD_T;
    $padR   = GL_PAD_R;
    $padB   = GL_PAD_B;
    $strGap = GL_STR_GAP;
    $frtGap = GL_FRT_GAP;

    $startFret = max(1, (int) ($chord['start_fret'] ?? 1));
    $frets     = min(7, max(3, (int) ($chord['frets_shown'] ?? 4)));

    $w = ($padL + 5 * $strGap + $padR);
    $h = ($padT + $frets * $frtGap + $padB);

    // Posisi tengah senar ke-6 (paling tebal, kiri) sampai ke-1 (paling kecil, kanan)
    $x = fn($s) => $padL + (6 - $s) * $strGap;

    $svg = [];

    // ---------- Kerangka ----------
    $svg[] = '<svg class="chord-svg" viewBox="0 0 ' . $w . ' ' . $h . '"'
           . ' width="' . round($w * $scale) . '" height="' . round($h * $scale) . '"'
           . ' role="img" aria-label="' . e($title) . '" xmlns="http://www.w3.org/2000/svg">';
    $svg[] = '<title>' . e($title) . '</title>';

    // ---------- Penanda O / X di atas diagram ----------
    $status = chord_string_status($chord);

    if ($showOpen) {
        foreach ($status as $s => $st) {
            if ($st === 'fretted') {
                continue;
            }
            $cx  = $x($s);
            $cy  = $padT - 17;
            $col = $st === 'o' ? 'var(--chord-open)' : 'var(--chord-muted)';
            $txt = $st === 'o' ? 'O' : 'X';
            $svg[] = '<circle cx="' . $cx . '" cy="' . $cy . '" r="8"'
                   . ' fill="none" stroke="' . $col . '" stroke-width="1.6"/>';
            $svg[] = '<text x="' . $cx . '" y="' . ($cy + 4) . '"'
                   . ' text-anchor="middle" font-size="11" font-weight="700"'
                   . ' fill="' . $col . '">' . $txt . '</text>';
        }
    }

    // ---------- 6 garis senar (tebal menipis dari senar 6 ke senar 1) ----------
    for ($s = 1; $s <= 6; $s++) {
        $sw = 3.4 - (6 - $s) * 0.42;              // senar 6 paling tebal
        $col = $status[$s] === 'x'
             ? 'var(--chord-muted-line)'
             : 'var(--chord-line)';

        $svg[] = '<line x1="' . $x($s) . '" y1="' . $padT . '"'
               . ' x2="' . $x($s) . '" y2="' . ($padT + $frets * $frtGap) . '"'
               . ' stroke="' . $col . '" stroke-width="' . round($sw, 2) . '" stroke-linecap="round"/>';
    }

    // ---------- Nut (hanya untuk chord posisi terbuka) ----------
    if ($startFret === 1) {
        $svg[] = '<line x1="' . $padL . '" y1="' . $padT . '"'
               . ' x2="' . ($padL + 5 * $strGap) . '" y2="' . $padT . '"'
               . ' stroke="var(--chord-nut)" stroke-width="5" stroke-linecap="round"/>';
    }

    // ---------- Garis fret ----------
    for ($f = 1; $f <= $frets; $f++) {
        $y  = $padT + $f * $frtGap;

        $svg[] = '<line x1="' . $padL . '" y1="' . $y . '"'
               . ' x2="' . ($padL + 5 * $strGap) . '" y2="' . $y . '"'
               . ' stroke="var(--chord-line)" stroke-width="1.5"'
               . ' stroke-linecap="round"/>';

        // Nomor fret di sebelah kiri setiap baris, supaya jelas posisinya
        $svg[] = '<text x="' . ($padL - 10) . '" y="' . ($y - 3) . '"'
               . ' text-anchor="end" font-size="11" font-weight="600"'
               . ' fill="var(--chord-muted)">' . ($startFret + $f - 1) . '</text>';
    }

    // ---------- Titik jari ----------
    $dots = chord_finger_dots($chord);

    // ---------- Batang (barre) ----------
    $barres = chord_barres($chord, $dots);

    foreach ($barres as $bar) {
        $bw = $bar['x2'] - $bar['x1'] + 20;           // sedikit lebih panjang dari rentang senar
        $svg[] = '<rect x="' . ($bar['x1'] - 10) . '" y="' . ($bar['y'] - 9) . '"'
               . ' width="' . $bw . '" height="18" rx="9"'
               . ' fill="var(--chord-finger)" stroke="var(--chord-finger-ring)" stroke-width="2"/>';
    }

    // ---------- Lingkaran + nomor jari ----------
    foreach ($dots as $d) {
        $svg[] = '<circle cx="' . $d['x'] . '" cy="' . $d['y'] . '" r="10"'
               . ' fill="var(--chord-finger)" stroke="var(--chord-finger-ring)" stroke-width="2"/>';

        if ($d['finger'] !== null) {
            $svg[] = '<text x="' . $d['x'] . '" y="' . ($d['y'] + 4) . '"'
                   . ' text-anchor="middle" font-size="11" font-weight="800"'
                   . ' fill="var(--chord-finger-text)">' . (int) $d['finger'] . '</text>';
        }
    }

    // Kotak pembatas kiri-kanan, biar tidak terlihat melayang
    $svg[] = '<line x1="' . $padL . '" y1="' . $padT . '"'
           . ' x2="' . $padL . '" y2="' . ($padT + $frets * $frtGap) . '"'
           . ' stroke="var(--chord-line)" stroke-width="1.5"/>';
    $svg[] = '<line x1="' . ($padL + 5 * $strGap) . '" y1="' . $padT . '"'
           . ' x2="' . ($padL + 5 * $strGap) . '" y2="' . ($padT + $frets * $frtGap) . '"'
           . ' stroke="var(--chord-line)" stroke-width="1.5"/>';

    $svg[] = '</svg>';

    return implode("\n", $svg);
}

// ==========================================================
//  3. Bawa nama chord sebagai judul di bawah diagram
// ==========================================================
/**
 * Diagram chord lengkap dengan nama chord di bawahnya.
 */
function chord_card_svg($chord, $scale = 1)
{
    return '<div class="chord-diagram">'
         . render_chord_svg($chord, ['scale' => $scale])
         . '<div class="chord-diagram-name">' . e($chord['name']) . '</div>'
         . '</div>';
}

// ==========================================================
//  4. "Database chord" untuk modal di halaman lagu
// ==========================================================
/**
 * Semua diagram chord disembunyikan di satu blok HTML.
 * Mosel di halaman lagu (JavaScript) mengambil diagram berdasarkan
 * nama chord saat chip chord diklik. Jadi tidak perlu request
 * ke server setiap kali chord diklik.
 */
function render_chord_database()
{
    $html = '<div id="chordDb" hidden>';

    foreach (all_chords() as $c) {
        $html .= '<div data-chord="' . e($c['name']) . '"'
               . ' data-text="' . e(chord_text_shape($c)) . '">'
               . render_chord_svg($c, ['scale' => 1.8])
               . '</div>';
    }

    return $html . '</div>';
}

// ==========================================================
//  5. Teks posisi jari (keterangan ASCII) untuk deaf/braille & SEO
// ==========================================================
/**
 * Keterangan posisi jari dalam bentuk teks, contoh:
 *   "Senar 5 fret 3 (jari 3), senar 4 fret 2 (jari 2), ..."
 */
function chord_text_shape($chord)
{
    $status = chord_string_status($chord);
    $dots = chord_finger_dots($chord);
    $barres = chord_barres($chord, $dots);

    $fingerNames = [1 => 'jari telunjuk', 2 => 'jari tengah',
                    3 => 'jari cincin', 4 => 'jari kelingking'];

    // Senar yang ditimpa batang (kecuali kedua ujung batang)
    $barMiddle = [];
    foreach ($barres as $bar) {
        for ($s = $bar['from']; $s >= $bar['to']; $s--) {
            if ($s !== $bar['from'] && $s !== $bar['to']) {
                $barMiddle[$s] = true;
            }
        }
    }

    $parts = [];

    // 1. Keterangan batang ditulis lebih dulu, supaya jelas
    //    senar mana yang ditekan satu jari yang sama
    foreach ($barres as $bar) {
        $parts[] = 'barre ' . $fingerNames[$bar['finger']] . ' di fret ' . $bar['fret']
                 . ' menutupi senar ' . $bar['from'] . ' sampai ' . $bar['to'];
    }

    // 2. Keterangan per senar
    for ($s = 1; $s <= 6; $s++) {
        if ($status[$s] === 'x') {
            continue;
        }
        if ($status[$s] === 'o') {
            $parts[] = 'senar ' . $s . ' terbuka';
            continue;
        }
        if (isset($barMiddle[$s])) {
            continue;                                  // sudah dijelaskan oleh batang
        }
        $f = $chord['fingering'][$s];
        $parts[] = 'senar ' . $s . ' fret ' . $f['fret']
                 . ($f['finger'] ? ' (' . $fingerNames[$f['finger']] . ')' : '');
    }

    if (!$parts) {
        return 'Belum ada data posisi jari untuk chord ini.';
    }

    return ucfirst(implode(', ', $parts)) . '.';
}

/**
 * Hitung posisi titik jari (senar 6 -> 1) untuk sebuah chord.
 * Dipisah supaya chord_barres() dan chord_text_shape() memakai
 * perhitungan yang sama dengan render_chord_svg().
 *
 * @param array $chord baris chords + kunci 'fingering'
 * @return array list of ['string','fret','finger','x','y']
 */
function chord_finger_dots($chord)
{
    $status = chord_string_status($chord);
    $startFret = max(1, (int) ($chord['start_fret'] ?? 1));
    $frets = min(7, max(3, (int) ($chord['frets_shown'] ?? 4)));

    $padL = GL_PAD_L;
    $padT = GL_PAD_T;
    $strGap = GL_STR_GAP;
    $frtGap = GL_FRT_GAP;

    $dots = [];

    for ($s = 6; $s >= 1; $s--) {
        if ($status[$s] !== 'fretted') {
            continue;
        }
        $fret   = (int) $chord['fingering'][$s]['fret'];
        $finger = $chord['fingering'][$s]['finger'];
        $rel    = $fret - $startFret + 1;

        if ($rel < 1 || $rel > $frets) {
            continue;
        }

        $dots[] = [
            'string' => $s,
            'fret'   => $fret,
            'finger' => $finger,
            'x'      => $padL + (6 - $s) * $strGap,
            'y'      => $padT + ($rel - 0.5) * $frtGap,
        ];
    }

    return $dots;
}
