/**
 * KPS-IT.de Service Worker
 * Strategie: Cache-First für Assets, Network-First für HTML-Seiten
 * Offline-Fallback auf /offline.html
 */

const CACHE_NAME    = 'kps-it-v11-20260308';
const OFFLINE_URL   = '/offline.html';

// Alle Ressourcen, die beim Install gecacht werden
const PRECACHE_URLS = [
  '/',
  '/index.html',
  '/style.css',
  '/subpage.css',
  '/main.js',
  '/qrcode.min.js',
  '/manifest.json',
  '/offline.html',
  '/einsatznachweis.html',
  '/institute.html',
  '/preiserhebungen.html',
  '/service-tests.html',
  '/fotodokumentation.html',
  '/verfuegbarkeits-checks.html',
  '/berichterstellung.html',
  '/dsgvo-compliance.html',
  '/impressum.html',
  '/datenschutz.html',
  // Google Fonts (werden gecacht sobald einmal geladen)
];

// Domains die NIEMALS gecacht werden (Karten-Tiles, externe CDNs)
const BYPASS_PATTERNS = [
  'tile.openstreetmap.org', 'openstreetmap.org/tiles',
  'basemaps.cartocdn.com', 'cartodb-basemaps', 'tile.carto.com',
  'unpkg.com', 'cdnjs.cloudflare.com', 'cdn.jsdelivr.net',
  'fonts.googleapis.com', 'fonts.gstatic.com'
];
function shouldBypass(url) {
  return BYPASS_PATTERNS.some(function(p) { return url.href.includes(p); });
}

// ---- INSTALL: Pre-Cache aller definierten URLs ----
self.addEventListener('install', function(event) {
  event.waitUntil(
    caches.open(CACHE_NAME).then(function(cache) {
      return cache.addAll(PRECACHE_URLS.filter(function(url) {
        // Nur lokale Ressourcen beim Install cachen (externe können fehlschlagen)
        return !url.startsWith('http');
      }));
    }).then(function() {
      return self.skipWaiting();
    })
  );
});

// ---- ACTIVATE: Alte Caches löschen ----
self.addEventListener('activate', function(event) {
  event.waitUntil(
    caches.keys().then(function(cacheNames) {
      return Promise.all(
        cacheNames.filter(function(name) {
          return name !== CACHE_NAME;
        }).map(function(name) {
          return caches.delete(name);
        })
      );
    }).then(function() {
      return self.clients.claim();
    })
  );
});

// ---- FETCH: Anfragen abfangen ----
self.addEventListener('fetch', function(event) {
  var url = new URL(event.request.url);

  // Nur GET-Anfragen behandeln
  if (event.request.method !== 'GET') return;

  // Karten-Tiles und externe CDNs: IMMER direkt ans Netz (nie cachen!)
  if (shouldBypass(url)) {
    event.respondWith(fetch(event.request));
    return;
  }

  // PHP-Formulare (send.php, admin) immer ans Netzwerk durchleiten
  if (url.pathname.endsWith('.php')) return;

  // HTML-Seiten: Network-First (frische Inhalte bevorzugen, Offline-Fallback)
  if (event.request.headers.get('accept') &&
      event.request.headers.get('accept').includes('text/html')) {
    event.respondWith(
      fetch(event.request)
        .then(function(response) {
          // Erfolgreiche Antwort im Cache speichern
          if (response && response.status === 200) {
            var responseClone = response.clone();
            caches.open(CACHE_NAME).then(function(cache) {
              cache.put(event.request, responseClone);
            });
          }
          return response;
        })
        .catch(function() {
          // Offline: aus Cache laden oder Offline-Seite zeigen
          return caches.match(event.request).then(function(cached) {
            return cached || caches.match(OFFLINE_URL);
          });
        })
    );
    return;
  }

  // CSS, JS, Fonts, Bilder: Cache-First
  event.respondWith(
    caches.match(event.request).then(function(cached) {
      if (cached) return cached;
      return fetch(event.request).then(function(response) {
        if (response && response.status === 200) {
          var responseClone = response.clone();
          caches.open(CACHE_NAME).then(function(cache) {
            cache.put(event.request, responseClone);
          });
        }
        return response;
      }).catch(function() {
        // Stille Fehler für nicht-kritische Assets
        return new Response('', { status: 408, statusText: 'Offline' });
      });
    })
  );
});

// ---- PUSH: Optionale Benachrichtigungen (Vorbereitung) ----
self.addEventListener('push', function(event) {
  if (!event.data) return;
  var data = event.data.json();
  event.waitUntil(
    self.registration.showNotification(data.title || 'KPS-IT.de', {
      body: data.body || '',
      icon: '/icons/icon-192.png',
      badge: '/icons/icon-72.png'
    })
  );
});
