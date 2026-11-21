/* =============================================================
   GUITARTICE - Modul audio chord
   Berisi:
     1. Memainkan bunyi chord (sintesis Web Audio, tanpa file audio)
     2. Mendeteksi keakuratan chord via mikrofon (analisis frekuensi)
   Dipakai di Chord Library, detail chord, dan halaman Latihan.
   ============================================================= */

(function () {
  'use strict';

  var NOTE_NAMES = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];

  function noop() {}

  function midiToFreq(m) {
    return 440 * Math.pow(2, (m - 69) / 12);
  }

  function freqToPc(freq) {
    var midi = Math.round(69 + 12 * Math.log(freq / 440) / Math.LN2);
    return ((midi % 12) + 12) % 12;
  }

  /* ---------------------------------------------------------
     Web Audio context + master output
     --------------------------------------------------------- */
  var audioCtx = null;
  var master = null;

  function ctx() {
    if (!audioCtx) {
      var AC = window.AudioContext || window.webkitAudioContext;
      if (!AC) return null;
      audioCtx = new AC();
    }
    if (audioCtx.state === 'suspended') {
      audioCtx.resume();
    }
    return audioCtx;
  }

  function ensureMaster(ac) {
    if (master) return master;
    master = ac.createGain();
    master.gain.value = 0.35;
    var comp = ac.createDynamicsCompressor();
    master.connect(comp);
    comp.connect(ac.destination);
    return master;
  }

  /* ---------------------------------------------------------
     1. Memainkan chord: satu oscillator per nada, dengan
        sedikit jeda antar senar supaya terasa seperti dipetik.
     --------------------------------------------------------- */
  function play(notes) {
    var ac = ctx();
    if (!ac || !notes || !notes.length) return false;

    var out = ensureMaster(ac);
    var now = ac.currentTime + 0.02;
    var dur = 2.4;

    notes.forEach(function (midi, i) {
      var osc = ac.createOscillator();
      var g = ac.createGain();
      osc.type = 'triangle';
      osc.frequency.value = midiToFreq(midi);

      var t = now + i * 0.045;
      g.gain.setValueAtTime(0.0001, t);
      g.gain.exponentialRampToValueAtTime(0.85, t + 0.015);
      g.gain.exponentialRampToValueAtTime(0.0001, t + dur);

      osc.connect(g);
      g.connect(out);
      osc.start(t);
      osc.stop(t + dur + 0.05);
    });

    return true;
  }

  /* ---------------------------------------------------------
     2. Deteksi keakuratan: baca spektrum, cari puncak nada,
        petakan ke pitch class, bandingkan dengan nada chord.
     --------------------------------------------------------- */
  function analyzeFrame(db, sampleRate, fftSize) {
    var binHz = sampleRate / fftSize;
    var n = db.length;
    var minBin = Math.max(1, Math.floor(70 / binHz));
    var maxBin = Math.min(n - 1, Math.ceil(1400 / binHz));
    var j;

    var maxMag = 0;
    for (j = minBin; j <= maxBin; j++) {
      var m = Math.pow(10, db[j] / 20);
      if (m > maxMag) maxMag = m;
    }

    var frame = {};
    for (j = minBin; j <= maxBin; j++) {
      var cur = Math.pow(10, db[j] / 20);
      if (cur < maxMag * 0.12) continue;
      var prev = Math.pow(10, db[j - 1] / 20);
      var next = Math.pow(10, db[j + 1] / 20);
      if (cur < prev || cur < next) continue;          // bukan puncak
      var pc = freqToPc(j * binHz);
      frame[pc] = (frame[pc] || 0) + cur;
    }

    var pcs = Object.keys(frame).map(Number);
    var maxE = 0;
    pcs.forEach(function (p) { if (frame[p] > maxE) maxE = frame[p]; });

    var levels = {};
    pcs.forEach(function (p) { levels[p] = maxE > 0 ? frame[p] / maxE : 0; });

    return { pcs: pcs, levels: levels };
  }

  function detect(expected, opts) {
    opts = opts || {};
    var onUpdate = opts.onUpdate || noop;
    var onDone   = opts.onDone || noop;
    var onError  = opts.onError || noop;
    var threshold = opts.threshold != null ? opts.threshold : 0.7;
    var duration  = opts.duration || 12000;

    var ac = ctx();
    if (!ac) {
      onError('Web Audio tidak didukung browser ini.');
      return { stop: noop };
    }
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      onError('Mikrofon membutuhkan HTTPS atau akses via localhost.');
      return { stop: noop };
    }

    var stream = null, analyser = null, source = null;
    var timer = null, timeout = null;
    var stopped = false, buf = null;
    var smooth = {}, passes = 0, best = 0;
    var required = Math.max(1, expected.length - (expected.length >= 4 ? 1 : 0));
    var lastMatched = [], lastMissing = expected.slice(), lastDetected = [];

    function finish(result) {
      if (stopped) return;
      stopped = true;
      if (timer) clearInterval(timer);
      if (timeout) clearTimeout(timeout);
      if (stream) stream.getTracks().forEach(function (t) { t.stop(); });

      result = result || {};
      if (result.matched == null) result.matched = lastMatched;
      if (result.missing == null) result.missing = lastMissing;
      if (result.detected == null) result.detected = lastDetected;
      if (result.expected == null) result.expected = expected.slice();
      onDone(result);
    }

    navigator.mediaDevices.getUserMedia({
      audio: { echoCancellation: false, noiseSuppression: false, autoGainControl: false }
    }).then(function (s) {
      if (stopped) { s.getTracks().forEach(function (t) { t.stop(); }); return; }
      stream = s;
      source = ac.createMediaStreamSource(s);
      analyser = ac.createAnalyser();
      analyser.fftSize = 8192;
      analyser.smoothingTimeConstant = 0.4;
      source.connect(analyser);
      buf = new Float32Array(analyser.frequencyBinCount);

      timeout = setTimeout(function () {
        finish({
          ok: false,
          score: best,
          matched: lastMatched,
          missing: lastMissing,
          detected: lastDetected,
          expected: expected.slice(),
          message: 'Belum terdeteksi akurat.'
        });
      }, duration);

      timer = setInterval(function () {
        analyser.getFloatFrequencyData(buf);
        var frame = analyzeFrame(buf, ac.sampleRate, analyser.fftSize);

        // peluruhan + penambahan energi tiap pitch class
        Object.keys(smooth).forEach(function (k) { smooth[k] *= 0.85; });
        frame.pcs.forEach(function (p) {
          smooth[p] = (smooth[p] || 0) * 0.7 + (frame.levels[p] || 0) * 0.3;
        });

        var detected = Object.keys(smooth)
          .filter(function (p) { return smooth[p] >= 0.35; })
          .map(Number);

        var matched = expected.filter(function (p) { return detected.indexOf(p) !== -1; });
        var missing = expected.filter(function (p) { return detected.indexOf(p) === -1; });
        var score = expected.length ? matched.length / expected.length : 0;
        var isMatch = matched.length >= required && score >= threshold;

        lastMatched = matched;
        lastMissing = missing;
        lastDetected = detected;

        if (score > best) best = score;
        if (isMatch) passes++; else passes = 0;

        onUpdate({
          detected: detected,
          levels: smooth,
          score: score,
          matched: matched,
          missing: missing,
          expected: expected.slice(),
          required: required,
          isMatch: isMatch
        });

        if (passes >= 3) {
          finish({
            ok: true,
            score: score,
            matched: matched,
            missing: missing,
            detected: detected,
            expected: expected.slice()
          });
        }
      }, 120);
    }).catch(function (err) {
      onError('Tidak bisa mengakses mikrofon (' + (err && err.name ? err.name : err) + ').');
      finish({ ok: false, error: true });
    });

    return {
      stop: function () {
        finish({
          ok: false,
          score: best,
          cancelled: true,
          matched: lastMatched,
          missing: lastMissing,
          detected: lastDetected,
          expected: expected.slice()
        });
      }
    };
  }

  /* ---------------------------------------------------------
     Tandai chord "kuasai" lewat progress-action.php
     --------------------------------------------------------- */
  function markMastered(chordId, chordName, msgEl) {
    var loggedIn = document.body.getAttribute('data-logged-in') === '1';
    if (!loggedIn) {
      if (msgEl) {
        msgEl.innerHTML += ' <a href="login.php">Login</a> / <a href="register.php">Daftar</a> untuk menyimpan centang ini.';
      }
      return;
    }

    var body = new URLSearchParams();
    body.set('ajax', '1');
    body.set('action', 'master_chord');
    body.set('chord_id', chordId);
    body.set('status', 'kuasai');

    fetch('progress-action.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString()
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res || !res.ok) return;

        document.querySelectorAll('.chord-chip[data-chord="' + chordName + '"]')
          .forEach(function (c) { c.classList.add('is-mastered'); });

        var mbtn = document.querySelector('[data-master-chord="' + chordId + '"]');
        if (mbtn) {
          mbtn.setAttribute('data-status', 'kuasai');
          mbtn.classList.add('btn-success');
          mbtn.setAttribute('data-label-on', 'Tandai Belum Bisa');
          mbtn.textContent = 'Tandai Belum Bisa';
        }

        if (msgEl) {
          msgEl.innerHTML += ' <span class="badge bg-success"><i class="bi bi-check-lg"></i> Tersimpan</span>';
        }
      })
      .catch(noop);
  }

  /* ---------------------------------------------------------
     Inisialisasi tombol & panel
     --------------------------------------------------------- */
  function parseNotes(raw) {
    return (raw || '')
      .split(',')
      .map(function (x) { return parseInt(x, 10); })
      .filter(function (x) { return !isNaN(x); });
  }

  function initPlayButtons() {
    document.addEventListener('click', function (ev) {
      var btn = ev.target.closest('[data-play-chord]');
      if (!btn) return;
      ev.preventDefault();
      ev.stopPropagation();

      var notes = parseNotes(btn.getAttribute('data-notes'));
      if (!notes.length) return;

      if (!play(notes)) {
        if (!btn.getAttribute('data-fallback')) {
          btn.setAttribute('data-fallback', '1');
        }
        return;
      }
      btn.classList.add('playing');
      setTimeout(function () { btn.classList.remove('playing'); }, 400);
    });
  }

  function initSoundPanels() {
    document.querySelectorAll('[data-sound-panel]').forEach(function (panel) {
      var expected  = parseNotes(panel.getAttribute('data-pitch-classes'));
      var chordId   = panel.getAttribute('data-chord-id');
      var chordName = panel.getAttribute('data-chord-name');

      var startBtn = panel.querySelector('[data-sound-start]');
      var stopBtn  = panel.querySelector('[data-sound-stop]');
      var bar      = panel.querySelector('[data-sound-bar]');
      var msg      = panel.querySelector('[data-sound-msg]');

      var noteEls = {};
      panel.querySelectorAll('[data-sound-notes] [data-pc]').forEach(function (el) {
        noteEls[el.getAttribute('data-pc')] = el;
      });

      var controller = null;

      var detectedEl = panel.querySelector('[data-sound-detected]');

      function noteName(pc) {
        return NOTE_NAMES[((pc % 12) + 12) % 12];
      }

      function resetButtons() {
        if (startBtn) startBtn.classList.remove('d-none');
        if (stopBtn) stopBtn.classList.add('d-none');
      }

      function resetVisuals() {
        if (bar) {
          bar.style.width = '0%';
          bar.classList.remove('bg-success');
          bar.classList.add('bg-warning');
        }
        if (detectedEl) detectedEl.textContent = '-';
        Object.keys(noteEls).forEach(function (pc) {
          noteEls[pc].classList.remove('active', 'missing');
        });
      }

      function render(result) {
        if (bar) bar.style.width = Math.round((result.score || 0) * 100) + '%';
        if (detectedEl) {
          var names = (result.detected || []).slice()
            .sort(function (a, b) { return a - b; })
            .map(noteName);
          detectedEl.textContent = names.length ? names.join(' - ') : '-';
        }
        Object.keys(noteEls).forEach(function (pc) {
          var lv = (result.levels && result.levels[pc]) || 0;
          noteEls[pc].classList.toggle('active', lv >= 0.35);
          noteEls[pc].classList.toggle('missing', (result.missing || []).indexOf(Number(pc)) !== -1);
        });
      }

      function start() {
        resetVisuals();
        if (startBtn) startBtn.classList.add('d-none');
        if (stopBtn) stopBtn.classList.remove('d-none');
        if (msg) {
          msg.innerHTML = '<i class="bi bi-broadcast me-1"></i>Mendengarkan chord <strong>'
            + chordName + '</strong>... petik dengan jelas.';
          msg.classList.remove('text-warning', 'text-success');
        }

        controller = detect(expected, {
          threshold: 0.7,
          duration: 12000,
          onUpdate: render,
          onError: function (m) {
            if (msg) msg.innerHTML = '<span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>' + m + '</span>';
            resetButtons();
          },
          onDone: function (res) {
            resetButtons();
            if (res && res.ok) {
              if (bar) {
                bar.style.width = '100%';
                bar.classList.remove('bg-warning');
                bar.classList.add('bg-success');
              }
              if (msg) {
                msg.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>'
                  + 'Chord <strong>' + chordName + '</strong> terdeteksi akurat!'
                  + ' <span class="text-dim">(' + (res.matched || []).length + '/' + expected.length + ' nada)</span></span>';
              }
              markMastered(chordId, chordName, msg);
            } else if (res && res.error) {
            } else if (res && res.cancelled) {
              if (msg) msg.innerHTML = '<i class="bi bi-stop-circle me-1"></i>Pengecekan dihentikan.';
            } else {
              var missingNames = ((res && res.missing) || []).slice()
                .sort(function (a, b) { return a - b; })
                .map(noteName);
              var detail = missingNames.length
                ? ' Nada yang belum terdengar: <strong>' + missingNames.join(', ') + '</strong>.'
                : '';
              if (msg) {
                msg.innerHTML = '<span class="text-warning"><i class="bi bi-x-circle me-1"></i>'
                  + 'Belum akurat untuk chord <strong>' + chordName + '</strong>.' + detail
                  + ' Coba petik ulang lebih bersih, atau tandai manual dengan tombol "Sudah Bisa".</span>';
              }
            }
          }
        });
      }

      if (startBtn) startBtn.addEventListener('click', start);
      if (stopBtn) {
        stopBtn.addEventListener('click', function () {
          if (controller) controller.stop();
          resetButtons();
        });
      }
    });
  }

  window.ChordAudio = {
    play: play,
    detect: detect,
    noteName: function (pc) { return NOTE_NAMES[((pc % 12) + 12) % 12]; }
  };

  document.addEventListener('DOMContentLoaded', function () {
    initPlayButtons();
    initSoundPanels();
  });
})();
