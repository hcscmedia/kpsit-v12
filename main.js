/**
 * KPS-IT.de – main.js
 * Features: Preloader, AOS, Counter, Formular-Handling,
 *           QR-Code, Mobile Nav, Back-to-Top, Active Nav
 */

'use strict';

/* ============================================================
   PRELOADER
   ============================================================ */
window.addEventListener('load', function () {
  var preloader = document.getElementById('preloader');
  if (preloader) {
    setTimeout(function () {
      preloader.classList.add('hidden');
    }, 400);
  }

  // Footer-Jahr
  var yearEl = document.getElementById('year');
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  // CSRF-Token laden
  fetchCsrfToken();

  // QR-Code generieren
  generateQrCode();
});

/* ============================================================
   MOBILE NAVIGATION
   ============================================================ */
(function () {
  var toggle = document.getElementById('nav-toggle');
  var menu   = document.getElementById('mobile-menu');
  if (!toggle || !menu) return;

  toggle.addEventListener('click', function () {
    var isOpen = menu.classList.toggle('open');
    toggle.classList.toggle('open', isOpen);
    toggle.setAttribute('aria-expanded', String(isOpen));
    menu.setAttribute('aria-hidden', String(!isOpen));
  });

  // Menü schließen bei Link-Klick
  menu.querySelectorAll('.mobile-nav-link').forEach(function (link) {
    link.addEventListener('click', function () {
      menu.classList.remove('open');
      toggle.classList.remove('open');
      toggle.setAttribute('aria-expanded', 'false');
      menu.setAttribute('aria-hidden', 'true');
    });
  });
})();

/* ============================================================
   HEADER SCROLL EFFECT
   ============================================================ */
(function () {
  var header = document.getElementById('site-header');
  if (!header) return;
  window.addEventListener('scroll', function () {
    header.classList.toggle('scrolled', window.scrollY > 20);
  }, { passive: true });
})();

/* ============================================================
   ACTIVE NAV LINK (Intersection Observer)
   ============================================================ */
(function () {
  var sections = document.querySelectorAll('section[id]');
  var navLinks = document.querySelectorAll('.nav-link');
  if (!sections.length || !navLinks.length) return;

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        var id = entry.target.getAttribute('id');
        navLinks.forEach(function (link) {
          link.classList.toggle('active', link.getAttribute('href') === '#' + id);
        });
      }
    });
  }, { rootMargin: '-40% 0px -55% 0px' });

  sections.forEach(function (s) { observer.observe(s); });
})();

/* ============================================================
   SMOOTH SCROLL
   ============================================================ */
document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
  anchor.addEventListener('click', function (e) {
    var target = document.querySelector(this.getAttribute('href'));
    if (target) {
      e.preventDefault();
      var headerH = document.getElementById('site-header')
        ? document.getElementById('site-header').offsetHeight
        : 0;
      var top = target.getBoundingClientRect().top + window.scrollY - headerH - 16;
      window.scrollTo({ top: top, behavior: 'smooth' });
    }
  });
});

/* ============================================================
   AOS – Scroll Animations (lightweight custom)
   ============================================================ */
(function () {
  var elements = document.querySelectorAll('[data-aos]');
  if (!elements.length) return;

  // Delay-Attribut auslesen
  elements.forEach(function (el) {
    var delay = el.getAttribute('data-aos-delay');
    if (delay) el.style.transitionDelay = delay + 'ms';
  });

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('aos-animate');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

  elements.forEach(function (el) { observer.observe(el); });
})();

/* ============================================================
   ANIMATED COUNTER
   ============================================================ */
(function () {
  var counters = document.querySelectorAll('.stat-number[data-target]');
  if (!counters.length) return;

  function animateCounter(el) {
    var target  = parseInt(el.getAttribute('data-target'), 10);
    var duration = 1800;
    var start    = performance.now();

    function step(now) {
      var elapsed  = now - start;
      var progress = Math.min(elapsed / duration, 1);
      // Ease-out cubic
      var eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.round(eased * target);
      if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        animateCounter(entry.target);
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.5 });

  counters.forEach(function (c) { observer.observe(c); });
})();

/* ============================================================
   BACK TO TOP
   ============================================================ */
(function () {
  var btn = document.getElementById('back-to-top');
  if (!btn) return;

  window.addEventListener('scroll', function () {
    if (window.scrollY > 400) {
      btn.removeAttribute('hidden');
    } else {
      btn.setAttribute('hidden', '');
    }
  }, { passive: true });

  btn.addEventListener('click', function () {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
})();

/* ============================================================
   QR CODE GENERATOR
   ============================================================ */
function generateQrCode() {
  var canvas = document.getElementById('qr-canvas');
  if (!canvas) return;

  // Prüfen ob QRCode-Bibliothek geladen
  if (typeof QRCode === 'undefined') {
    // Fallback: Placeholder-Text
    var ctx = canvas.getContext('2d');
    canvas.width = 64;
    canvas.height = 64;
    ctx.fillStyle = '#f0f0f0';
    ctx.fillRect(0, 0, 64, 64);
    ctx.fillStyle = '#333';
    ctx.font = '8px monospace';
    ctx.fillText('QR', 22, 36);
    return;
  }

  try {
    QRCode.toCanvas(canvas, 'https://kps-it.de/einsatznachweis.html', {
      width: 64,
      margin: 1,
      color: { dark: '#080c14', light: '#ffffff' },
      errorCorrectionLevel: 'M'
    }, function (err) {
      if (err) console.warn('QR-Code Fehler:', err);
    });
  } catch (e) {
    console.warn('QR-Code konnte nicht generiert werden:', e);
  }
}

/* ============================================================
   CSRF TOKEN LADEN
   ============================================================ */
function fetchCsrfToken() {
  var tokenInput = document.getElementById('csrf_token');
  if (!tokenInput) return;

  fetch('send.php?action=csrf', {
    method: 'GET',
    credentials: 'same-origin'
  })
  .then(function (r) { return r.ok ? r.json() : null; })
  .then(function (data) {
    if (data && data.token) tokenInput.value = data.token;
  })
  .catch(function () {
    // Kein CSRF-Token verfügbar (z. B. kein PHP-Server) – Formular trotzdem nutzbar
  });
}

/* ============================================================
   KONTAKTFORMULAR
   ============================================================ */
(function () {
  var form       = document.getElementById('contact-form');
  var submitBtn  = document.getElementById('submit-btn');
  var successBox = document.getElementById('form-success');
  var errorBox   = document.getElementById('form-error');
  var errorMsg   = document.getElementById('form-error-msg');
  if (!form) return;

  // ---- Client-seitige Validierung ----
  function validateField(input) {
    var errorEl = input.closest('.form-group')
      ? input.closest('.form-group').querySelector('.form-error')
      : null;
    var msg = '';

    if (input.required && !input.value.trim()) {
      msg = 'Dieses Feld ist erforderlich.';
    } else if (input.type === 'email' && input.value && !isValidEmail(input.value)) {
      msg = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
    } else if (input.tagName === 'SELECT' && !input.value) {
      msg = 'Bitte wählen Sie eine Option.';
    } else if (input.tagName === 'TEXTAREA' && input.value.trim().length < 10) {
      msg = 'Die Nachricht muss mindestens 10 Zeichen lang sein.';
    } else if (input.type === 'checkbox' && input.required && !input.checked) {
      msg = 'Bitte stimmen Sie zu.';
    }

    if (errorEl) errorEl.textContent = msg;
    input.classList.toggle('error', !!msg);
    return !msg;
  }

  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  // Live-Validierung
  form.querySelectorAll('.form-input, input[type="checkbox"]').forEach(function (input) {
    input.addEventListener('blur', function () { validateField(this); });
    input.addEventListener('input', function () {
      if (this.classList.contains('error')) validateField(this);
    });
  });

  // ---- Formular absenden ----
  form.addEventListener('submit', function (e) {
    e.preventDefault();

    // Alle Felder validieren
    var fields  = form.querySelectorAll('.form-input, input[type="checkbox"]');
    var isValid = true;
    fields.forEach(function (f) {
      if (!validateField(f)) isValid = false;
    });
    if (!isValid) return;

    // UI: Lade-Zustand
    submitBtn.classList.add('loading');
    submitBtn.disabled = true;
    successBox.hidden  = true;
    errorBox.hidden    = true;

    var formData = new FormData(form);
    // Checkbox-Wert korrekt setzen
    var dsgvoCheck = form.querySelector('#dsgvo');
    if (dsgvoCheck && dsgvoCheck.checked) formData.set('dsgvo', 'on');

    fetch(form.action, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (response) {
      return response.json().then(function (data) {
        return { ok: response.ok, data: data };
      });
    })
    .then(function (result) {
      submitBtn.classList.remove('loading');
      submitBtn.disabled = false;

      if (result.data.success) {
        successBox.hidden = false;
        form.reset();
        // Neuen CSRF-Token laden
        fetchCsrfToken();
        successBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      } else {
        errorBox.hidden = false;
        if (errorMsg) errorMsg.textContent = result.data.message || 'Ein unbekannter Fehler ist aufgetreten.';
        errorBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    })
    .catch(function () {
      submitBtn.classList.remove('loading');
      submitBtn.disabled = false;
      errorBox.hidden = false;
      if (errorMsg) errorMsg.textContent = 'Verbindungsfehler. Bitte versuchen Sie es erneut oder kontaktieren Sie mich direkt per E-Mail.';
    });
  });
})();
