/**
 * KPS-IT.de – Interaktive Leaflet-Karte mit OpenStreetMap-Hintergrund
 * Zeigt Berlin & Märkisch-Oderland mit echten Kartenkacheln
 */
(function () {
  'use strict';

  /* ── Standorte ─────────────────────────────────────────── */
  var BERLIN_LOCATIONS = [
    { name: 'Mitte',               lat: 52.5200, lng: 13.4050, type: 'Preiserhebung, Mystery Shopping', main: true },
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

  /* ── Karte initialisieren ──────────────────────────────── */
  var map = null;
  var berlinLayer = null;
  var molLayer = null;
  var allLayer = null;
  var currentRegion = 'all';
  var tileLayer = null;

  function getTheme() {
    return document.documentElement.getAttribute('data-theme') || 'dark';
  }

  function getTileUrl(theme) {
    if (theme === 'light') {
      // CartoDB Positron (hell, dezent)
      return 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';
    } else {
      // CartoDB DarkMatter (dunkel, passt zum Dark-Mode)
      return 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
    }
  }

  function getTileAttrib() {
    return '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>';
  }

  function createMarker(loc, color) {
    var svgIcon = L.divIcon({
      className: '',
      html: '<div style="width:12px;height:12px;border-radius:50%;background:' + color + ';border:2px solid rgba(255,255,255,0.8);box-shadow:0 0 6px ' + color + '88;"></div>',
      iconSize: [12, 12],
      iconAnchor: [6, 6]
    });
    var marker = L.marker([loc.lat, loc.lng], { icon: svgIcon });
    marker.bindPopup(
      '<div style="font-family:inherit;min-width:160px;">' +
      '<strong style="font-size:0.9rem;">' + loc.name + '</strong>' +
      '<div style="font-size:0.75rem;color:#64748b;margin-top:4px;">' + loc.type + '</div>' +
      '</div>',
      { className: 'kps-popup', maxWidth: 220 }
    );
    return marker;
  }

  function initMap() {
    var container = document.getElementById('kps-map');
    if (!container || typeof L === 'undefined') return;

    var theme = getTheme();

    // Karte erstellen
    map = L.map('kps-map', {
      center: [52.4800, 13.6000],
      zoom: 10,
      zoomControl: true,
      scrollWheelZoom: false,
      attributionControl: true
    });

    // Tile-Layer
    tileLayer = L.tileLayer(getTileUrl(theme), {
      attribution: getTileAttrib(),
      subdomains: 'abcd',
      maxZoom: 16
    }).addTo(map);

    // Marker-Gruppen
    berlinLayer = L.layerGroup();
    molLayer = L.layerGroup();
    allLayer = L.layerGroup();

    BERLIN_LOCATIONS.forEach(function(loc) {
      var m = createMarker(loc, '#6366f1');
      berlinLayer.addLayer(m);
      allLayer.addLayer(createMarker(loc, '#6366f1'));
    });

    MOL_LOCATIONS.forEach(function(loc) {
      var m = createMarker(loc, '#10b981');
      molLayer.addLayer(m);
      allLayer.addLayer(createMarker(loc, '#10b981'));
    });

    allLayer.addTo(map);

    // Popup-Styles
    var style = document.createElement('style');
    style.textContent = '.kps-popup .leaflet-popup-content-wrapper { border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); } .leaflet-popup-tip { display: none; }';
    document.head.appendChild(style);

    // Region-Filter
    document.querySelectorAll('[data-region]').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var region = this.getAttribute('data-region');
        document.querySelectorAll('[data-region]').forEach(function(b) { b.classList.remove('active'); });
        this.classList.add('active');

        map.removeLayer(allLayer);
        map.removeLayer(berlinLayer);
        map.removeLayer(molLayer);

        if (region === 'berlin') {
          berlinLayer.addTo(map);
          map.flyTo([52.5200, 13.4050], 11, { duration: 1 });
        } else if (region === 'mol') {
          molLayer.addTo(map);
          map.flyTo([52.4800, 14.0000], 10, { duration: 1 });
        } else {
          allLayer.addTo(map);
          map.flyTo([52.4800, 13.6000], 10, { duration: 1 });
        }
      });
    });

    // Theme-Wechsel: Tile-Layer tauschen
    var observer = new MutationObserver(function() {
      var newTheme = getTheme();
      if (tileLayer) {
        map.removeLayer(tileLayer);
      }
      tileLayer = L.tileLayer(getTileUrl(newTheme), {
        attribution: getTileAttrib(),
        subdomains: 'abcd',
        maxZoom: 16
      }).addTo(map);
      tileLayer.bringToBack();
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
  }

  /* ── Leaflet laden ─────────────────────────────────────── */
  function loadLeafletAndInit() {
    if (typeof L !== 'undefined') {
      initMap();
      return;
    }

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
    script.onload = initMap;
    script.onerror = function() {
      // Fallback CDN
      var s2 = document.createElement('script');
      s2.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js';
      s2.crossOrigin = 'anonymous';
      s2.onload = initMap;
      document.head.appendChild(s2);
    };
    document.head.appendChild(script);
  }

  /* ── Start ─────────────────────────────────────────────── */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadLeafletAndInit);
  } else {
    loadLeafletAndInit();
  }

})();
