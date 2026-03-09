/* ============================================================
   KPS-IT.de – Features2.js
   Scroll-Fortschrittsbalken | Dark/Light-Mode | Skill-Chart
   Testimonials-Slider | Leaflet-Karte
   ============================================================ */

(function () {
  'use strict';

  /* ============================================================
     1. SCROLL-FORTSCHRITTSBALKEN
     ============================================================ */
  var progressBar = null;

  function initScrollProgress() {
    progressBar = document.createElement('div');
    progressBar.className = 'scroll-progress';
    progressBar.setAttribute('role', 'progressbar');
    progressBar.setAttribute('aria-label', 'Scroll-Fortschritt');
    document.body.insertBefore(progressBar, document.body.firstChild);
    updateScrollProgress();
    window.addEventListener('scroll', updateScrollProgress, { passive: true });
  }

  function updateScrollProgress() {
    if (!progressBar) return;
    var scrollTop = window.scrollY || document.documentElement.scrollTop;
    var docHeight = document.documentElement.scrollHeight - window.innerHeight;
    var pct = docHeight > 0 ? Math.min(100, (scrollTop / docHeight) * 100) : 0;
    progressBar.style.width = pct.toFixed(1) + '%';
  }

  /* ============================================================
     2. DARK / LIGHT MODE
     ============================================================ */
  var THEME_KEY = 'kps_theme';

  function initThemeToggle() {
    var saved = localStorage.getItem(THEME_KEY);
    var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    var theme = saved || (prefersDark ? 'dark' : 'light');
    applyTheme(theme, false);

    // Alle Toggle-Buttons rendern
    var btns = document.querySelectorAll('[data-theme-toggle]');
    btns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var current = document.documentElement.getAttribute('data-theme') || 'dark';
        applyTheme(current === 'dark' ? 'light' : 'dark', true);
      });
    });
  }

  function applyTheme(theme, save) {
    document.documentElement.setAttribute('data-theme', theme);
    if (save) localStorage.setItem(THEME_KEY, theme);

    // Leaflet-Tiles neu rendern falls Karte vorhanden
    if (window._kpsMap) {
      setTimeout(function () {
        window._kpsMap.invalidateSize();
      }, 300);
    }
  }

  /* ============================================================
     3. ANIMIERTES SKILL-CHART
     ============================================================ */
  var SKILLS = [
    { name: 'Preiserhebung',       pct: 97, color: 'c-indigo',  icon: '💰' },
    { name: 'Mystery Shopping',    pct: 93, color: 'c-blue',    icon: '🕵' },
    { name: 'Fotodokumentation',   pct: 90, color: 'c-cyan',    icon: '📷' },
    { name: 'Verfügbarkeits-Check',pct: 88, color: 'c-green',   icon: '📦' },
    { name: 'Berichterstellung',   pct: 85, color: 'c-violet',  icon: '📊' },
    { name: 'DSGVO-Compliance',    pct: 95, color: 'c-amber',   icon: '🔒' },
    { name: 'Kundenkommunikation', pct: 91, color: 'c-pink',    icon: '🤝' }
  ];

  function buildSkillBars(container) {
    var html = '<div class="skill-bars">';
    SKILLS.forEach(function (s, i) {
      html += '<div class="skill-bar-item" data-pct="' + s.pct + '" data-idx="' + i + '">' +
        '<div class="skill-bar-header">' +
          '<div class="skill-bar-name">' +
            '<div class="skill-bar-icon" aria-hidden="true">' + s.icon + '</div>' +
            s.name +
          '</div>' +
          '<div class="skill-bar-pct" aria-label="' + s.pct + ' Prozent">' + s.pct + '%</div>' +
        '</div>' +
        '<div class="skill-bar-track" role="progressbar" aria-valuenow="' + s.pct + '" aria-valuemin="0" aria-valuemax="100" aria-label="' + s.name + '">' +
          '<div class="skill-bar-fill ' + s.color + '" style="width:0%"></div>' +
        '</div>' +
      '</div>';
    });
    html += '</div>';
    container.innerHTML = html;
  }

  function buildRadarChart(container) {
    var size = 260;
    var cx = size / 2, cy = size / 2;
    var maxR = 100;
    var labels = SKILLS.map(function (s) { return s.name; });
    var values = SKILLS.map(function (s) { return s.pct / 100; });
    var n = labels.length;

    function polar(angle, r) {
      return {
        x: cx + r * Math.cos(angle - Math.PI / 2),
        y: cy + r * Math.sin(angle - Math.PI / 2)
      };
    }

    var svg = '<svg class="radar-svg" viewBox="0 0 ' + size + ' ' + size + '" aria-label="Kompetenz-Radar">';

    // Grid-Ringe
    [0.25, 0.5, 0.75, 1].forEach(function (f) {
      var pts = [];
      for (var i = 0; i < n; i++) {
        var p = polar((2 * Math.PI * i) / n, maxR * f);
        pts.push(p.x + ',' + p.y);
      }
      svg += '<polygon class="radar-grid-line" points="' + pts.join(' ') + '" />';
    });

    // Achsen
    for (var i = 0; i < n; i++) {
      var p = polar((2 * Math.PI * i) / n, maxR);
      svg += '<line class="radar-axis-line" x1="' + cx + '" y1="' + cy + '" x2="' + p.x + '" y2="' + p.y + '" />';
    }

    // Daten-Polygon (initial auf 0)
    var zeroPts = [];
    for (var j = 0; j < n; j++) {
      var z = polar((2 * Math.PI * j) / n, 0);
      zeroPts.push(z.x + ',' + z.y);
    }
    svg += '<polygon class="radar-area" id="radar-area" points="' + zeroPts.join(' ') + '" />';

    // Punkte
    for (var k = 0; k < n; k++) {
      var dp = polar((2 * Math.PI * k) / n, maxR * values[k]);
      svg += '<circle class="radar-dot" id="radar-dot-' + k + '" cx="' + cx + '" cy="' + cy + '" />';
    }

    // Labels
    for (var l = 0; l < n; l++) {
      var lp = polar((2 * Math.PI * l) / n, maxR + 22);
      var shortName = labels[l].replace('Verfügbarkeits-Check', 'Verfügb.').replace('Kundenkommunikation', 'Kommunik.').replace('Fotodokumentation', 'Fotodok.').replace('Berichterstellung', 'Berichte').replace('DSGVO-Compliance', 'DSGVO').replace('Mystery Shopping', 'Mystery').replace('Preiserhebung', 'Preise');
      svg += '<text class="radar-label" x="' + lp.x + '" y="' + lp.y + '">' + shortName + '</text>';
    }

    svg += '</svg>';
    container.innerHTML = svg;

    // Animieren wenn sichtbar
    window._radarValues = values;
    window._radarN = n;
    window._radarMaxR = maxR;
    window._radarCx = cx;
    window._radarCy = cy;
    window._radarAnimated = false;
  }

  function animateRadar() {
    if (window._radarAnimated) return;
    window._radarAnimated = true;
    var values = window._radarValues;
    var n = window._radarN;
    var maxR = window._radarMaxR;
    var cx = window._radarCx;
    var cy = window._radarCy;

    function polar(angle, r) {
      return {
        x: cx + r * Math.cos(angle - Math.PI / 2),
        y: cy + r * Math.sin(angle - Math.PI / 2)
      };
    }

    var start = null;
    var duration = 1200;

    function frame(ts) {
      if (!start) start = ts;
      var progress = Math.min((ts - start) / duration, 1);
      var ease = 1 - Math.pow(1 - progress, 3); // ease-out-cubic

      var pts = [];
      for (var i = 0; i < n; i++) {
        var p = polar((2 * Math.PI * i) / n, maxR * values[i] * ease);
        pts.push(p.x + ',' + p.y);
        var dot = document.getElementById('radar-dot-' + i);
        if (dot) {
          dot.setAttribute('cx', p.x);
          dot.setAttribute('cy', p.y);
        }
      }
      var area = document.getElementById('radar-area');
      if (area) area.setAttribute('points', pts.join(' '));

      if (progress < 1) requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
  }

  function animateSkillBars(container) {
    var items = container.querySelectorAll('.skill-bar-item');
    items.forEach(function (item, idx) {
      var pct = parseInt(item.getAttribute('data-pct'), 10);
      var fill = item.querySelector('.skill-bar-fill');
      var pctEl = item.querySelector('.skill-bar-pct');
      setTimeout(function () {
        item.classList.add('animated');
        if (fill) fill.style.width = pct + '%';
      }, idx * 80);
    });
  }

  function initSkillChart() {
    var barsContainer = document.getElementById('skill-bars-container');
    var radarContainer = document.getElementById('skill-radar-container');
    if (!barsContainer && !radarContainer) return;

    if (barsContainer) buildSkillBars(barsContainer);
    if (radarContainer) buildRadarChart(radarContainer);

    // Intersection Observer für Animation
    var observed = false;
    var target = barsContainer || radarContainer;
    if (!target) return;

    if ('IntersectionObserver' in window) {
      var obs = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting && !observed) {
            observed = true;
            if (barsContainer) animateSkillBars(barsContainer);
            if (radarContainer) animateRadar();
          }
        });
      }, { threshold: 0.2 });
      obs.observe(target);
    } else {
      if (barsContainer) animateSkillBars(barsContainer);
      if (radarContainer) animateRadar();
    }
  }

  /* ============================================================
     4. TESTIMONIALS-SLIDER
     ============================================================ */
  var TESTIMONIALS = [
    {
      text: 'Die Erhebungen von KPS-IT.de zeichnen sich durch außergewöhnliche Präzision aus. Die Preisdaten werden systematisch und lückenlos erfasst – ein verlässlicher Partner für unsere Marktforschungsprojekte.',
      name: 'Dr. M. Hoffmann',
      role: 'Projektleitung Marktforschung',
      institute: 'IFH Köln',
      initials: 'MH',
      stars: 5
    },
    {
      text: 'Besonders beeindruckt hat uns die Qualität der Mystery-Shopping-Berichte. Die Beobachtungen sind detailliert, objektiv und liefern uns wertvolle Erkenntnisse zur Optimierung unserer Kundenberatung.',
      name: 'S. Bergmann',
      role: 'Senior Research Manager',
      institute: 'DISQ Berlin',
      initials: 'SB',
      stars: 5
    },
    {
      text: 'Die Fotodokumentation der Regalplatzierungen ist von höchster Qualität. Alle Bilder sind klar, gut beleuchtet und entsprechen exakt unseren Anforderungen. Die Lieferung erfolgt stets pünktlich.',
      name: 'T. Krause',
      role: 'Field Research Coordinator',
      institute: 'GfK SE',
      initials: 'TK',
      stars: 5
    },
    {
      text: 'Zuverlässigkeit und Diskretion sind im Mystery Shopping entscheidend. KPS-IT.de erfüllt beide Anforderungen in vorbildlicher Weise. Die Einsätze werden professionell vorbereitet und sauber dokumentiert.',
      name: 'A. Müller',
      role: 'Qualitätssicherung',
      institute: 'NIM Nürnberg',
      initials: 'AM',
      stars: 5
    },
    {
      text: 'Für unsere DSGVO-sensitiven Erhebungen benötigen wir Partner, die datenschutzrechtliche Anforderungen vollständig verstehen und umsetzen. KPS-IT.de ist hier absolut verlässlich.',
      name: 'K. Weber',
      role: 'Compliance & Data Protection',
      institute: 'IRI Group',
      initials: 'KW',
      stars: 5
    }
  ];

  var sliderState = {
    current: 0,
    total: TESTIMONIALS.length,
    autoplay: null,
    paused: false
  };

  function buildTestimonials(container) {
    var html = '<div class="testimonials-slider" id="testimonials-slider">' +
      '<div class="testimonials-track" id="testimonials-track">';

    TESTIMONIALS.forEach(function (t) {
      var stars = '';
      for (var i = 0; i < t.stars; i++) stars += '<span class="testimonial-star" aria-hidden="true">★</span>';

      html += '<div class="testimonial-slide">' +
        '<div class="testimonial-card">' +
          '<div class="testimonial-quote-icon" aria-hidden="true">"</div>' +
          '<div class="testimonial-stars" aria-label="' + t.stars + ' von 5 Sternen">' + stars + '</div>' +
          '<p class="testimonial-text">' + t.text + '</p>' +
          '<div class="testimonial-author">' +
            '<div class="testimonial-avatar" aria-hidden="true">' + t.initials + '</div>' +
            '<div class="testimonial-author-info">' +
              '<div class="testimonial-author-name">' + t.name + '</div>' +
              '<div class="testimonial-author-role">' + t.role + '</div>' +
            '</div>' +
            '<div class="testimonial-institute-badge">' + t.institute + '</div>' +
          '</div>' +
        '</div>' +
      '</div>';
    });

    html += '</div></div>';

    // Steuerung
    var dots = '';
    TESTIMONIALS.forEach(function (_, i) {
      dots += '<button class="slider-dot' + (i === 0 ? ' active' : '') + '" aria-label="Bewertung ' + (i + 1) + '" onclick="KPS_Slider.goTo(' + i + ')"></button>';
    });

    html += '<div class="testimonials-controls">' +
      '<button class="slider-btn" onclick="KPS_Slider.prev()" aria-label="Vorherige Bewertung">' +
        '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>' +
      '</button>' +
      '<div class="slider-dots" role="tablist" aria-label="Bewertungen">' + dots + '</div>' +
      '<button class="slider-btn" onclick="KPS_Slider.next()" aria-label="Nächste Bewertung">' +
        '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>' +
      '</button>' +
      '<button class="slider-autoplay" id="slider-autoplay-btn" onclick="KPS_Slider.toggleAutoplay()" aria-label="Autoplay pausieren">' +
        '<svg id="autoplay-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>' +
      '</button>' +
    '</div>';

    container.innerHTML = html;

    // Touch/Swipe
    var track = document.getElementById('testimonials-track');
    if (track) {
      var touchStartX = 0;
      track.addEventListener('touchstart', function (e) {
        touchStartX = e.touches[0].clientX;
      }, { passive: true });
      track.addEventListener('touchend', function (e) {
        var diff = touchStartX - e.changedTouches[0].clientX;
        if (Math.abs(diff) > 50) {
          if (diff > 0) KPS_Slider.next();
          else KPS_Slider.prev();
        }
      }, { passive: true });
    }

    startAutoplay();
  }

  function updateSlider(idx) {
    var track = document.getElementById('testimonials-track');
    if (track) track.style.transform = 'translateX(-' + (idx * 100) + '%)';

    var dots = document.querySelectorAll('.slider-dot');
    dots.forEach(function (d, i) {
      d.classList.toggle('active', i === idx);
    });
    sliderState.current = idx;
  }

  function startAutoplay() {
    if (sliderState.autoplay) clearInterval(sliderState.autoplay);
    sliderState.autoplay = setInterval(function () {
      if (!sliderState.paused) {
        KPS_Slider.next();
      }
    }, 5000);
  }

  window.KPS_Slider = {
    goTo: function (idx) {
      updateSlider(idx);
    },
    next: function () {
      var next = (sliderState.current + 1) % sliderState.total;
      updateSlider(next);
    },
    prev: function () {
      var prev = (sliderState.current - 1 + sliderState.total) % sliderState.total;
      updateSlider(prev);
    },
    toggleAutoplay: function () {
      sliderState.paused = !sliderState.paused;
      var icon = document.getElementById('autoplay-icon');
      if (icon) {
        icon.innerHTML = sliderState.paused
          ? '<polygon points="5 3 19 12 5 21 5 3"/>'
          : '<rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/>';
      }
    }
  };

  function initTestimonials() {
    var container = document.getElementById('testimonials-container');
    if (!container) return;
    buildTestimonials(container);
  }

  /* ============================================================
     5. LEAFLET-KARTE
     ============================================================ */
  var BERLIN_BEZIRKE = [
    { name: 'Mitte',                  lat: 52.5200, lng: 13.4050, type: 'berlin', info: 'Einkaufszentren, Flagship-Stores, Tourismusbereich' },
    { name: 'Prenzlauer Berg',        lat: 52.5380, lng: 13.4210, type: 'berlin', info: 'Bio-Supermärkte, Boutiquen, Familieneinkauf' },
    { name: 'Friedrichshain',         lat: 52.5160, lng: 13.4540, type: 'berlin', info: 'Discounter, Drogeriemärkte, Elektronik' },
    { name: 'Kreuzberg',              lat: 52.4990, lng: 13.4030, type: 'berlin', info: 'Wochenmärkte, Spezialgeschäfte, Gastronomie' },
    { name: 'Charlottenburg',         lat: 52.5165, lng: 13.3040, type: 'berlin', info: 'Kurfürstendamm, Luxussegment, Kaufhäuser' },
    { name: 'Tempelhof',              lat: 52.4680, lng: 13.3830, type: 'berlin', info: 'Nahversorgung, Supermärkte, Drogerie' },
    { name: 'Neukölln',               lat: 52.4810, lng: 13.4350, type: 'berlin', info: 'Discounter, Lebensmitteleinzelhandel' },
    { name: 'Lichtenberg',            lat: 52.5100, lng: 13.5010, type: 'berlin', info: 'Fachmarkt-Zentren, Baumärkte, SB-Warenhäuser' },
    { name: 'Marzahn-Hellersdorf',    lat: 52.5350, lng: 13.5800, type: 'berlin', info: 'Einkaufszentren, Verbrauchermärkte' },
    { name: 'Spandau',                lat: 52.5350, lng: 13.2000, type: 'berlin', info: 'Altstadt-Einzelhandel, Fachgeschäfte' },
    { name: 'Reinickendorf',          lat: 52.5850, lng: 13.3330, type: 'berlin', info: 'Nahversorgung, Lebensmittelhandel' },
    { name: 'Treptow-Köpenick',       lat: 52.4570, lng: 13.5760, type: 'berlin', info: 'Fachmarkt-Zentren, Gartenmarkt' },
    { name: 'Steglitz-Zehlendorf',    lat: 52.4560, lng: 13.2480, type: 'berlin', info: 'Wohngebiets-Einzelhandel, Apotheken' },
    { name: 'Pankow',                 lat: 52.5690, lng: 13.4030, type: 'berlin', info: 'Wachstumsgebiet, neue Einkaufszentren' }
  ];

  var MOL_ORTE = [
    { name: 'Strausberg',             lat: 52.5770, lng: 13.8870, type: 'mol',    info: 'Kreisstadt, Einzelhandel, Wochenmärkte' },
    { name: 'Fürstenwalde/Spree',     lat: 52.3600, lng: 14.0600, type: 'mol',    info: 'Verbrauchermärkte, Fachgeschäfte' },
    { name: 'Seelow',                 lat: 52.5320, lng: 14.3760, type: 'mol',    info: 'Kreisstadt MOL, Nahversorgung' },
    { name: 'Bad Saarow',             lat: 52.2900, lng: 14.0380, type: 'mol',    info: 'Tourismus-Einzelhandel, Kurort' },
    { name: 'Müncheberg',             lat: 52.5150, lng: 14.1270, type: 'mol',    info: 'Ländliche Nahversorgung' },
    { name: 'Rüdersdorf',             lat: 52.4750, lng: 13.7960, type: 'mol',    info: 'Industriegebiet, Fachmarkt-Ansiedlungen' },
    { name: 'Erkner',                 lat: 52.4250, lng: 13.7520, type: 'mol',    info: 'Berliner Umland, Pendler-Einkauf' },
    { name: 'Neuenhagen',             lat: 52.5290, lng: 13.6810, type: 'mol',    info: 'Berliner Speckgürtel, Supermärkte' }
  ];

  var HQ = [
    { name: 'KPS-IT.de – Hauptstandort', lat: 52.5200, lng: 13.4050, type: 'hq', info: 'Operativer Hauptstandort für alle Einsätze in Berlin und Brandenburg' }
  ];

  function initMap_DISABLED_USE_KPS_MAP() {
    var mapEl = document.getElementById('kps-map');
    if (!mapEl) return;
    if (typeof L === 'undefined') {
      console.warn('[KPS] Leaflet nicht geladen');
      return;
    }

    var map = L.map('kps-map', {
      center: [52.52, 13.40],
      zoom: 10,
      zoomControl: true,
      attributionControl: true
    });

    window._kpsMap = map;

    // OpenStreetMap Tiles – mit Fallback auf CartoDB Dark
    var tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 19,
      crossOrigin: true
    });
    var fallbackLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
      attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> © <a href="https://carto.com/">CARTO</a>',
      maxZoom: 19,
      crossOrigin: true
    });
    tileLayer.on('tileerror', function() {
      if (!map._fallbackAdded) {
        map._fallbackAdded = true;
        tileLayer.remove();
        fallbackLayer.addTo(map);
      }
    });
    tileLayer.addTo(map);

    // Marker-Funktion
    function createMarker(loc) {
      var colorMap = { berlin: '#6366f1', mol: '#10b981', hq: '#f59e0b' };
      var color = colorMap[loc.type] || '#6366f1';

      var icon = L.divIcon({
        className: '',
        html: '<div class="custom-marker marker-' + loc.type + '" title="' + loc.name + '">' +
              '<div class="custom-marker-inner">📍</div></div>',
        iconSize: [32, 32],
        iconAnchor: [16, 32],
        popupAnchor: [0, -34]
      });

      var marker = L.marker([loc.lat, loc.lng], { icon: icon });
      marker.bindPopup(
        '<div class="map-popup-title">' + loc.name + '</div>' +
        '<div class="map-popup-text">' + loc.info + '</div>'
      );
      return marker;
    }

    // Layer-Gruppen
    var berlinLayer = L.layerGroup();
    var molLayer    = L.layerGroup();
    var hqLayer     = L.layerGroup();

    BERLIN_BEZIRKE.forEach(function (loc) { createMarker(loc).addTo(berlinLayer); });
    MOL_ORTE.forEach(function (loc)       { createMarker(loc).addTo(molLayer); });
    HQ.forEach(function (loc)             { createMarker(loc).addTo(hqLayer); });

    berlinLayer.addTo(map);
    molLayer.addTo(map);
    hqLayer.addTo(map);

    // Einsatzgebiet-Kreise
    L.circle([52.52, 13.40], {
      radius: 25000,
      color: '#6366f1',
      fillColor: '#6366f1',
      fillOpacity: 0.04,
      weight: 1.5,
      dashArray: '6 4'
    }).addTo(map);

    L.circle([52.50, 14.00], {
      radius: 30000,
      color: '#10b981',
      fillColor: '#10b981',
      fillOpacity: 0.04,
      weight: 1.5,
      dashArray: '6 4'
    }).addTo(map);

    // Sidebar-Interaktion
    var regionItems = document.querySelectorAll('.map-region-item');
    regionItems.forEach(function (item) {
      item.addEventListener('click', function () {
        regionItems.forEach(function (r) { r.classList.remove('active'); });
        item.classList.add('active');
        var region = item.getAttribute('data-region');
        if (region === 'berlin') {
          map.flyTo([52.52, 13.40], 11, { duration: 1.2 });
        } else if (region === 'mol') {
          map.flyTo([52.48, 14.05], 10, { duration: 1.2 });
        } else if (region === 'all') {
          map.flyTo([52.50, 13.70], 9, { duration: 1.2 });
        }
      });
    });
  }

  /* ============================================================
     INIT – Alles starten wenn DOM bereit
     ============================================================ */
  function init() {
    initScrollProgress();
    initThemeToggle();
    initSkillChart();
    initTestimonials();

    // SVG-Karte wird von kps-map.js initialisiert (kein Leaflet mehr nötig)
  }

  function loadLeaflet(cb) {
    if (typeof L !== 'undefined') { cb(); return; }

    // CSS
    var link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    link.crossOrigin = 'anonymous';
    document.head.appendChild(link);

    // JS
    var script = document.createElement('script');
    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    script.crossOrigin = 'anonymous';
    script.onload = cb;
    script.onerror = function() {
      // Fallback: cdnjs
      var s2 = document.createElement('script');
      s2.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js';
      s2.onload = cb;
      document.head.appendChild(s2);
    };
    document.head.appendChild(script);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
