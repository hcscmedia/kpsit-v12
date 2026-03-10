/**
 * KPS-IT.de – Zuverlässige Leaflet-Karte (Satelliten-Hybrid)
 * Features: Kleine Google Maps Pins, ResizeObserver (Anti-Grau Bug), Cache-sicher
 */
(function () {
  'use strict';

  /* ── Standorte ─────────────────────────────────────────── */
  var BERLIN_LOCATIONS = [
    { name: 'Mitte',               lat: 52.5200, lng: 13.4050, type: 'Preiserhebung, Mystery Shopping' },
    { name: 'Prenzlauer Berg',     lat: 52.5390, lng: 13.4150, type: 'Preiserhebung, Fotodokumentation' },
    { name: 'Friedrichshain',      lat: 52.5130, lng: 13.4540, type: 'Mystery Shopping, Verfügbarkeit' },
    { name: 'Kreuzberg',           lat: 52.4990, lng: 13.4030, type: 'Preiserhebung, Service-Test' },
    { name: 'Neukölln',            lat: 52.4820, lng: 13.4350, type: 'Preiserhebung, Fotodokumentation' },
    { name: 'Tempelhof',           lat: 52.4680, lng: 13.3840, type: 'Mystery Shopping, Verfügbarkeit' },
    { name: 'Schöneberg',          lat: 52.4880, lng: 13.3560, type: 'Preiserhebung, Service-Test' },
    { name: 'Charlottenburg',      lat: 52.5160, lng: 13.2980, type: 'Mystery Shopping, Preiserhebung' },
    { name: 'Spandau',             lat: 52.5350, lng: 13.2000, type: 'Verfügbarkeit, Fotodokumentation' },
    { name: 'Reinickendorf',       lat: 52.5760, lng: 13.3430, type: 'Preiserhebung, Mystery Shopping' },
    { name: 'Pankow',              lat: 52.5690, lng: 13.4030, type: 'Preiserhebung, Verfügbarkeit' },
    { name: 'Lichtenberg',         lat: 52.5200, lng: 13.4990, type: 'Mystery Shopping, Service-Test' },
    { name: 'Marzahn-Hellersdorf', lat: 52.5380, lng: 13.5700, type: 'Preiserhebung, Fotodokumentation' },
    { name: 'Treptow-Köpenick',    lat: 52.4430, lng: 13.5800, type: 'Verfügbarkeit, Mystery Shopping' }
  ];

  var MOL_LOCATIONS = [
    { name: 'Strausberg',    lat: 52.5760, lng: 13.8820, type: 'Preiserhebung, Verfügbarkeit' },
    { name: 'Erkner',        lat: 52.4230, lng: 13.7520, type: 'Preiserhebung, Fotodokumentation' },
    { name: 'Fürstenwalde',  lat: 52.3600, lng: 14.0600, type: 'Mystery Shopping, Preiserhebung' },
    { name: 'Bad Saarow',    lat: 52.3050, lng: 14.0380, type: 'Verfügbarkeit, Service-Test' },
    { name: 'Müncheberg',    lat: 52.5100, lng: 14.1340, type: 'Preiserhebung, Fotodokumentation' },
    { name: 'Seelow',        lat: 52.5310, lng: 14.3760, type: 'Preiserhebung, Verfügbarkeit' },
    { name: 'Neuenhagen',    lat: 52.5260, lng: 13.6810, type: 'Mystery Shopping, Preiserhebung' },
    { name: 'Rüdersdorf',    lat: 52.4680, lng: 13.7960, type: 'Verfügbarkeit, Fotodokumentation' }
  ];

  var map = null;
  var berlinGroup = null;
  var molGroup = null;

  function injectMapStyles() {
    if (document.getElementById('kps-map-styles')) return;
    var style = document.createElement('style');
    style.id = 'kps-map-styles';
    style.textContent = `
      #kps-map { background: #111; z-index: 1; min-height: 400px; border-radius: 8px; }
      .custom-pin { background: transparent; border: none; }
      :root {
        --map-ui-bg: #ffffff;
        --map-ui-text: #0f172a;
        --map-ui-border: rgba(0,0,0,0.2);
        --map-attr-bg: rgba(255,255,255,0.7);
      }
      html[data-theme="dark"] {
        --map-ui-bg: #1e293b;
        --map-ui-text: #e2e8f0;
        --map-ui-border: rgba(255,255,255,0.1);
        --map-attr-bg: rgba(0,0,0,0.6);
      }
      .kps-popup .leaflet-popup-content-wrapper { background: var(--map-ui-bg) !important; color: var(--map-ui-text) !important; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.4); }
      .kps-popup .leaflet-popup-tip { background: var(--map-ui-bg) !important; }
      .leaflet-bar a, .leaflet-bar a:hover { background-color: var(--map-ui-bg) !important; color: var(--map-ui-text) !important; border-bottom: 1px solid var(--map-ui-border) !important; }
      .leaflet-bar { border: 1px solid var(--map-ui-border) !important; box-shadow: 0 2px 10px rgba(0,0,0,0.2) !important; }
      .leaflet-control-attribution { background: var(--map-attr-bg) !important; color: #94a3b8 !important; }
      .leaflet-control-attribution a { color: #6366f1 !important; }
    `;
    document.head.appendChild(style);
  }

  function createMarker(loc, color) {
    var svgIcon = L.divIcon({
      className: 'custom-pin',
      html: `<svg width="21" height="30" viewBox="0 0 30 42" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0px 2px 3px rgba(0,0,0,0.6)); transform-origin: bottom center; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.15)'" onmouseout="this.style.transform='scale(1)'">
               <path d="M15 0C6.716 0 0 6.716 0 15c0 9 15 27 15 27s15-18 15-27c0-8.284-6.716-15-15-15z" fill="${color}" stroke="#ffffff" stroke-width="1.5"/>
               <circle cx="15" cy="15" r="5" fill="#ffffff"/>
             </svg>`,
      iconSize: [21, 30],
      iconAnchor: [10.5, 30],
      popupAnchor: [0, -28]
    });

    var marker = L.marker([loc.lat, loc.lng], { icon: svgIcon });
    marker.bindPopup(
      '<div style="font-family:inherit;min-width:160px;">' +
      '<strong style="font-size:0.95rem;">' + loc.name + '</strong>' +
      '<div style="font-size:0.75rem;opacity:0.8;margin-top:4px;">' + loc.type + '</div>' +
      '</div>',
      { className: 'kps-popup', maxWidth: 220 }
    );
    return marker;
  }

  function initMap() {
    var container = document.getElementById('kps-map');
    if (!container || typeof L === 'undefined') return;

    // Mehrfach-Initialisierung bei Cache-Reloads verhindern
    if (container._leaflet_id) {
       map.invalidateSize();
       return;
    }

    injectMapStyles();

    map = L.map('kps-map', { zoomControl: true, scrollWheelZoom: false, attributionControl: true });

    L.tileLayer('https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
      maxZoom: 20,
      subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
      attribution: '&copy; Google Maps'
    }).addTo(map);

    berlinGroup = L.featureGroup();
    molGroup = L.featureGroup();

    BERLIN_LOCATIONS.forEach(function(loc) { berlinGroup.addLayer(createMarker(loc, '#818cf8')); });
    MOL_LOCATIONS.forEach(function(loc) { molGroup.addLayer(createMarker(loc, '#34d399')); });

    berlinGroup.addTo(map);
    molGroup.addTo(map);

    var allBounds = berlinGroup.getBounds().extend(molGroup.getBounds());
    map.fitBounds(allBounds, { padding: [30, 30] });

    // WICHTIG: Der "Anti-Graue-Kacheln" Trick!
    // Überwacht den Container und erzwingt das Zeichnen der Karte, sobald der Container existiert.
    if ('ResizeObserver' in window) {
      new ResizeObserver(function() {
        if (map) map.invalidateSize();
      }).observe(container);
    } else {
      setTimeout(function() { if(map) map.invalidateSize(); }, 500);
    }

    document.querySelectorAll('[data-region]').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var region = this.getAttribute('data-region');
        document.querySelectorAll('[data-region]').forEach(function(b) { b.classList.remove('active'); });
        this.classList.add('active');

        map.removeLayer(berlinGroup);
        map.removeLayer(molGroup);

        if (region === 'berlin') {
          berlinGroup.addTo(map);
          map.flyToBounds(berlinGroup.getBounds(), { padding: [40, 40], duration: 1 });
        } else if (region === 'mol') {
          molGroup.addTo(map);
          map.flyToBounds(molGroup.getBounds(), { padding: [40, 40], duration: 1 });
        } else {
          berlinGroup.addTo(map);
          molGroup.addTo(map);
          map.flyToBounds(berlinGroup.getBounds().extend(molGroup.getBounds()), { padding: [30, 30], duration: 1 });
        }
      });
    });
  }

  function loadLeafletAndInit() {
    if (typeof L !== 'undefined') {
      initMap();
      return;
    }
    var link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(link);

    var script = document.createElement('script');
    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    script.onload = initMap;
    document.head.appendChild(script);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadLeafletAndInit);
  } else {
    loadLeafletAndInit();
  }

})();