/* =============================================================
   GUITARTICE - JavaScript utama
   Bertugas:
     1. Modal diagram chord (di halaman lagu / chord library)
     2. Menandai chord sebagai "sudah bisa" per chord
     3. Builder posisi jari di halaman admin
   ============================================================= */

(function () {
  'use strict';

  /* ---------------------------------------------------------
     1. Modal diagram chord
        Semua diagram sudah di-render PHP ke dalam #chordDb,
        jadi klik chord cukup mencari diagram di dalam halaman.
        Tidak ada request ke server -> cepat & offline friendly.
     --------------------------------------------------------- */
  var chordCache = null;

  function getChordCache() {
    if (chordCache) return chordCache;
    var db = document.getElementById('chordDb');
    if (!db) return null;
    chordCache = {};
    Array.prototype.forEach.call(db.children, function (wrap) {
      chordCache[wrap.getAttribute('data-chord')] = wrap;
    });
    return chordCache;
  }

  function initChordModal() {
    var modalEl = document.getElementById('chordModal');
    if (!modalEl || typeof bootstrap === 'undefined') return;

    var cache = getChordCache();
    if (!cache) return;

    var modal  = new bootstrap.Modal(modalEl);
    var nameEl = document.getElementById('chordModalName');
    var bodyEl = document.getElementById('chordModalBody');
    var textEl = document.getElementById('chordModalText');

    function showChord(chordName) {
      var wrap = cache[chordName];
      if (!wrap) {
        nameEl.textContent = chordName;
        bodyEl.innerHTML = '<p class="text-dim mb-0">Diagram untuk chord ini belum tersedia.</p>';
        textEl.textContent = '';
      } else {
        nameEl.textContent = chordName;
        bodyEl.innerHTML = '';
        // cloneNode supaya diagram yang sama bisa dipakai berkali-kali
        bodyEl.appendChild(wrap.cloneNode(true));
        textEl.textContent = wrap.getAttribute('data-text') || '';
      }
      modal.show();
    }

    // Semua elemen ber-kelas .chord-chip bisa membuka modal
    document.addEventListener('click', function (ev) {
      var chip = ev.target.closest('.chord-chip');
      if (!chip) return;
      ev.preventDefault();
      showChord(chip.getAttribute('data-chord'));
    });
  }

  function initChordHoverPreview() {
    var cache = getChordCache();
    if (!cache) return;

    var preview = document.createElement('div');
    preview.className = 'chord-hover-preview';
    preview.setAttribute('role', 'tooltip');
    preview.setAttribute('aria-hidden', 'true');
    preview.innerHTML = '<div class="chord-hover-name"></div>'
      + '<div class="chord-hover-body"></div>'
      + '<div class="chord-hover-text"></div>';
    document.body.appendChild(preview);

    var nameEl = preview.querySelector('.chord-hover-name');
    var bodyEl = preview.querySelector('.chord-hover-body');
    var textEl = preview.querySelector('.chord-hover-text');
    var currentName = null;
    var lastX = 0;
    var lastY = 0;
    var dragging = false;

    function position() {
      var margin = 12;
      var rect = preview.getBoundingClientRect();
      var x = lastX + 18;
      var y = lastY + 18;

      if (x + rect.width + margin > window.innerWidth) {
        x = lastX - rect.width - 18;
      }
      if (x < margin) x = margin;
      if (y + rect.height + margin > window.innerHeight) {
        y = lastY - rect.height - 18;
      }
      if (y < margin) y = margin;

      preview.style.left = x + 'px';
      preview.style.top = y + 'px';
    }

    function show(name, x, y) {
      var wrap = cache[name];
      if (!wrap) {
        hide();
        return;
      }

      if (currentName !== name) {
        currentName = name;
        nameEl.textContent = name;
        bodyEl.innerHTML = '';
        bodyEl.appendChild(wrap.cloneNode(true));
        textEl.textContent = wrap.getAttribute('data-text') || '';
        preview.classList.add('show');
        preview.setAttribute('aria-hidden', 'false');
      }

      lastX = x;
      lastY = y;
      position();
    }

    function hide() {
      if (!currentName) return;
      currentName = null;
      preview.classList.remove('show');
      preview.setAttribute('aria-hidden', 'true');
    }

    function chipFromEvent(ev) {
      var target = ev.target;
      if (!target || !target.closest) return null;
      return target.closest('.chord-chip');
    }

    // Hover / geser mouse: tampilkan ilustrasi chord yang sedang ditunjuk.
    document.addEventListener('pointermove', function (ev) {
      var chip = chipFromEvent(ev);
      if (!chip) {
        // Saat men-drag, jangan sembunyikan supaya ilustrasi tetap mengikuti.
        if (!dragging) hide();
        return;
      }

      var name = chip.getAttribute('data-chord');
      if (!name) {
        if (!dragging) hide();
        return;
      }
      show(name, ev.clientX, ev.clientY);
    }, { passive: true });

    // Tekan lalu drag: langsung tampilkan ilustrasi chord.
    document.addEventListener('pointerdown', function (ev) {
      var chip = chipFromEvent(ev);
      if (!chip) return;
      dragging = true;
      var name = chip.getAttribute('data-chord');
      if (name) show(name, ev.clientX, ev.clientY);
    }, { passive: true });

    document.addEventListener('pointerup', function () {
      dragging = false;
    }, { passive: true });

    document.addEventListener('pointercancel', function () {
      dragging = false;
      hide();
    }, { passive: true });

    document.addEventListener('click', hide, true);
    document.addEventListener('touchstart', function (ev) {
      // Touch: tetap tampilkan ilustrasi saat menyentuh chord.
      var chip = chipFromEvent(ev);
      if (!chip) {
        hide();
        return;
      }
      var touch = ev.touches && ev.touches[0];
      var name = chip.getAttribute('data-chord');
      if (name && touch) show(name, touch.clientX, touch.clientY);
    }, { passive: true });
    document.addEventListener('scroll', hide, true);
    window.addEventListener('resize', hide);
    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape') hide();
    });
  }

  function initMasteryToggle() {
    var buttons = document.querySelectorAll('[data-master-chord]');
    if (!buttons.length) return;

    var loggedIn = document.body.getAttribute('data-logged-in') === '1';

    Array.prototype.forEach.call(buttons, function (btn) {
      btn.addEventListener('click', function () {
        var chordId = btn.getAttribute('data-master-chord');
        var owner = btn.getAttribute('data-scope') || 'chord';
        var nextStatus = btn.getAttribute('data-status') === 'kuasai' ? 'belum' : 'kuasai';

        if (!loggedIn) {
          alert('Progress tidak tersimpan karena kamu belum login.\n'
            + 'Silakan daftar atau login untuk menyimpan progress latihan.');
          return;
        }

        const body = new URLSearchParams();
        body.set('ajax', '1');
        body.set('action', 'master_chord');
        body.set('chord_id', chordId);
        body.set('status', nextStatus);
        if (owner === 'song') {
          body.set('song_id', btn.getAttribute('data-song-id'));
        }

        fetch('progress-action.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: body.toString()
        })
          .then(function (r) { return r.json(); })
          .then(function (res) {
            if (!res || !res.ok) {
              alert(res && res.message ? res.message : 'Gagal menyimpan progress.');
              return;
            }
            applyMasteryUpdate(btn, owner, res);
          })
          .catch(function () { alert('Gagal terhubung ke server.'); });
      });
    });
  }

  /* Perbarui tampilan setelah server mengonfirmasi perubahan */
  function applyMasteryUpdate(btn, owner, res) {
    btn.setAttribute('data-status', res.status);

    if (owner === 'song') {
      // Di halaman lagu: semua chip dengan chord yang sama ikut berubah
      document.querySelectorAll('.chord-chip[data-chord="' + res.name + '"]').forEach(function (chip) {
        chip.classList.toggle('is-mastered', res.status === 'kuasai');
      });

      // Tombol "Sudah Bisa Lagu Ini" aktif saat semua chord lagu dikuasai
      var completeBtn = document.querySelector('[data-complete-song]');
      if (completeBtn) {
        completeBtn.disabled = res.percent < 100;
      }
    }

    var bar = document.getElementById(owner === 'song' ? 'songProgressBar' : 'chordProgressBar');
    var txt = document.getElementById(owner === 'song' ? 'songProgressText' : 'chordProgressText');
    var badge = document.getElementById(owner === 'song' ? 'songProgressBadge' : 'chordProgressBadge');

    if (bar) bar.style.width = res.percent + '%';
    if (txt) txt.textContent = res.mastered + ' / ' + res.total + ' chord';
    if (badge) {
      badge.textContent = res.percent + '%';
      badge.className = 'badge bg-' +
        (res.percent >= 100 ? 'success' : (res.percent >= 50 ? 'warning' : 'secondary'));
    }

    // Ganti label tombol sesuai status baru
    var isMastered = res.status === 'kuasai';
    btn.setAttribute('data-label-on', isMastered ? 'Tandai Belum Bisa' : 'Sudah Bisa');
    btn.textContent = btn.getAttribute('data-label-on');
  }

  /* ---------------------------------------------------------
     3. Builder posisi jari (admin)
        Ubah jumlah baris fret yang tersedia saat difficulty
        diubah ke chord barre.
     --------------------------------------------------------- */
  function initFingerBuilder() {
    var form = document.getElementById('fingerForm');
    if (!form) return;

    var fretsInput = document.getElementById('frets_shown');
    var startInput = document.getElementById('start_fret');
    var hint = document.getElementById('barreHint');

    function syncHint() {
      var start = parseInt(startInput.value, 10) || 1;
      var frets = parseInt(fretsInput.value, 10) || 4;
      if (hint) {
        hint.textContent = start > 1
          ? 'Mode barre aktif: diagram menampilkan fret ' + start + ' sampai ' + (start + frets - 1) + '.'
          : 'Mode chord terbuka: diagram menampilkan fret 1 sampai ' + frets + '.';
      }
    }

    fretsInput.addEventListener('input', syncHint);
    startInput.addEventListener('input', syncHint);
    syncHint();

    // Tombol tambah senar (untuk debugging / perbaikan data)
    var addBtn = document.getElementById('addStringBtn');
    if (addBtn) {
      addBtn.addEventListener('click', function () {
        alert('Semua 6 senar selalu ditampilkan. '
          + 'Senar yang tidak dipilih "ditekan" otomatis ditandai X (tidak dipetik).');
      });
    }
  }

  /* ---------------------------------------------------------
     Filter chord / lagu tanpa reload halaman
     --------------------------------------------------------- */
  function initQuickFilter() {
    document.querySelectorAll('[data-filter-target]').forEach(function (input) {
      input.addEventListener('input', function () {
        var target = document.querySelector(input.getAttribute('data-filter-target'));
        if (!target) return;
        var needle = input.value.toLowerCase().trim();
        Array.prototype.forEach.call(target.children, function (item) {
          var hay = item.getAttribute('data-search') || '';
          item.style.display = (needle === '' || hay.toLowerCase().indexOf(needle) !== -1) ? '' : 'none';
        });
      });
    });
  }

  /* ---------------------------------------------------------
     Beranda (tamu): animasi chord per level belajar
     Beginner -> Intermediate -> Pro, berganti tiap 4 detik.
     --------------------------------------------------------- */
  function initChordShowcase() {
    var root = document.querySelector('[data-chord-showcase]');
    if (!root) return;

    var panes = root.querySelectorAll('[data-showcase-pane]');
    var dots = root.querySelectorAll('.showcase-dot');
    var badge = root.querySelector('[data-showcase-badge]');
    if (!panes.length) return;

    var index = 0;
    var timer = null;

    function show(i) {
      index = (i + panes.length) % panes.length;
      Array.prototype.forEach.call(panes, function (pane, n) {
        pane.classList.toggle('active', n === index);
      });
      Array.prototype.forEach.call(dots, function (dot, n) {
        dot.classList.toggle('active', n === index);
      });
      if (badge) {
        badge.textContent = panes[index].getAttribute('data-level-label') || '';
      }
    }

    function start() {
      stop();
      timer = window.setInterval(function () { show(index + 1); }, 4000);
    }

    function stop() {
      if (timer) {
        window.clearInterval(timer);
        timer = null;
      }
    }

    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    document.addEventListener('visibilitychange', function () {
      if (document.hidden) { stop(); } else { start(); }
    });

    show(0);
    start();
  }

  /* ---------------------------------------------------------
     4. Modal beranda: pilih level, konfirmasi, login/registrasi
     --------------------------------------------------------- */
  function initHomeModals() {
    if (typeof bootstrap === 'undefined') return;

    var levelModalEl   = document.getElementById('levelModal');
    var confirmModalEl = document.getElementById('levelConfirmModal');
    var authModalEl    = document.getElementById('authModal');
    if (!levelModalEl && !confirmModalEl && !authModalEl) return;

    var levelModal   = levelModalEl   ? new bootstrap.Modal(levelModalEl)   : null;
    var confirmModal = confirmModalEl ? new bootstrap.Modal(confirmModalEl) : null;
    var authModal    = authModalEl    ? new bootstrap.Modal(authModalEl)    : null;

    var confirmInput = document.getElementById('levelConfirmInput');
    var confirmName  = document.getElementById('levelConfirmName');
    var confirmNext  = document.getElementById('levelConfirmNext');

    function showConfirm() {
      if (confirmModal) confirmModal.show();
    }

    function openConfirm(level, label, next) {
      if (confirmInput) confirmInput.value = level;
      if (confirmName)  confirmName.textContent = label || level;
      if (confirmNext)  confirmNext.textContent = next || 'level tertinggi';

      // Kalau modal pilih level sedang terbuka, tunggu sampai tertutup dulu
      // supaya backdrop tidak menumpuk.
      if (levelModal && levelModalEl.classList.contains('show')) {
        levelModalEl.addEventListener('hidden.bs.modal', function handler() {
          levelModalEl.removeEventListener('hidden.bs.modal', handler);
          showConfirm();
        });
        levelModal.hide();
      } else {
        showConfirm();
      }
    }

    document.querySelectorAll('[data-open-levels]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        if (levelModal) levelModal.show();
      });
    });

    document.querySelectorAll('[data-open-auth]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        if (authModal) authModal.show();
      });
    });

    document.querySelectorAll('[data-choose-level]').forEach(function (el) {
      el.addEventListener('click', function (e) {
        e.preventDefault();
        if (el.getAttribute('data-unlocked') === '0') return;
        openConfirm(
          el.getAttribute('data-choose-level'),
          el.getAttribute('data-label'),
          el.getAttribute('data-next')
        );
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initChordModal();
    initChordHoverPreview();
    initMasteryToggle();
    initFingerBuilder();
    initQuickFilter();
    initChordShowcase();
    initHomeModals();
  });
})();