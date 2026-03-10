/**
 * KPS-IT.de Service Worker v2.1
 * Kombiniert bestehende Logik mit optimierten Strategien
 *
 * Strategien:
 *  - Statische Assets (CSS, JS, Bilder)  → Cache First
 *  - HTML-Seiten                          → Network First + Offline-Fallback
 *  - PHP-APIs                             → Network First mit Cache-Fallback
 *  - Karten-Tiles / externe CDNs          → Bypass (nie cachen)
 */

const SW_VERSION  = 'kps-it-v2.1';
const CACHE_STATIC = SW_VERSION + '-static';
const CACHE_PAGES  = SW_VERSION + '-pages';
const OFFLINE_URL  = '/offline.html';

// ── Assets die beim Install sofort gecacht werden (App Shell) ──
const PRECACHE_URLS = [
  '/',
  '/index.html',
  '/offline.html',
  '/style.css',
  '/subpage.css',
  '/features.css',
  '/features2.css',
  '/main.js',
  '/features2.js',
  '/kps-map.js',
  '/cookie-banner.js',
  '/tracker.js',
  '/i18n.js',
  '/schema.js',
  '/qrcode.min.js',
  '/manifest.json',
  '/logo.png',
  '/logo-footer.png',
  '/einsatznachweis.html',
  '/kalender.html',
  '/tracking.html',
  '/institute.html',
  '/preiserhebungen.html',
  '/service-tests.html',
  '/fotodokumentation.html',
  '/verfuegbarkeits-checks.html',
  '/berichterstellung.html',
  '/dsgvo-compliance.html',
  '/ueber-mich.html',
  '/impressum.html',
  '/datenschutz.html',
];

// ── Diese Domains NIEMALS cachen ──────────────────────────────
const BYPASS_PATTERNS = [
  'tile.openstreetmap.org',
  'openstreetmap.org/tiles',
  'basemaps.cartocdn.com',
  'cartodb-basemaps',
  'tile.carto.com',
  'unpkg.com',
  'cdnjs.cloudflare.com',
  'cdn.jsdelivr.net',
  'fonts.googleapis.com',
  'fonts.gstatic.com',
];

function shouldBypass(url) {
  return BYPASS_PATTERNS.some(p => url.href.includes(p));
}

function isStaticAsset(pathname) {
  return /\.(css|js|png|jpg|jpeg|webp|svg|ico|woff2?|ttf|gif)$/i.test(pathname);
}

// INSTALL
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_STATIC)
      .then(cache => cache.addAll(
        PRECACHE_URLS.filter(url => !url.startsWith('http'))
      ))
      .then(() => self.skipWaiting())
      .catch(err => {
        console.warn('[SW] Install-Fehler (unkritisch):', err);
        return self.skipWaiting();
      })
  );
});

// ACTIVATE
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(
        keys
          .filter(key => key !== CACHE_STATIC && key !== CACHE_PAGES)
          .map(key => caches.delete(key))
      ))
      .then(() => {
        self.clients.matchAll({ includeUncontrolled: true }).then(clients => {
          clients.forEach(client =>
            client.postMessage({ type: 'SW_UPDATED', version: SW_VERSION })
          );
        });
        return self.clients.claim();
      })
  );
});

// FETCH
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);
  if (event.request.method !== 'GET') return;
  if (!url.protocol.startsWith('http')) return;
  if (shouldBypass(url)) {
    event.respondWith(fetch(event.request));
    return;
  }
  if (url.pathname.endsWith('.php')) {
    event.respondWith(networkFirst(event.request, CACHE_PAGES, 6000));
    return;
  }
  if (isStaticAsset(url.pathname)) {
    event.respondWith(cacheFirst(event.request, CACHE_STATIC));
    return;
  }
  if (event.request.headers.get('accept') && event.request.headers.get('accept').includes('text/html')) {
    event.respondWith(networkFirstHtml(event.request));
    return;
  }
  event.respondWith(cacheFirst(event.request, CACHE_STATIC));
});

// Cache First
async function cacheFirst(request, cacheName) {
  const cached = await caches.match(request);
  if (cached) return cached;
  try {
    const response = await fetch(request);
    if (response && response.status === 200) {
      const cache = await caches.open(cacheName);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    return new Response('', { status: 408, statusText: 'Offline' });
  }
}

// Network First mit Timeout
async function networkFirst(request, cacheName, timeoutMs) {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), timeoutMs);
  try {
    const response = await fetch(request, { signal: controller.signal });
    clearTimeout(timeout);
    if (response && response.status === 200) {
      const cache = await caches.open(cacheName);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    clearTimeout(timeout);
    const cached = await caches.match(request);
    if (cached) return cached;
    return new Response(
      JSON.stringify({ success: false, error: 'Offline' }),
      { headers: { 'Content-Type': 'application/json' } }
    );
  }
}

// Network First fuer HTML
async function networkFirstHtml(request) {
  try {
    const response = await fetch(request);
    if (response && response.status === 200) {
      const cache = await caches.open(CACHE_PAGES);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    const cached = await caches.match(request);
    if (cached) return cached;
    const offline = await caches.match(OFFLINE_URL);
    return offline || new Response('Offline', { status: 503 });
  }
}

// PUSH
self.addEventListener('push', event => {
  if (!event.data) return;
  const data = event.data.json();
  event.waitUntil(
    self.registration.showNotification(data.title || 'KPS-IT.de', {
      body:  data.body  || '',
      icon:  '/icons/icon-192.png',
      badge: '/icons/icon-72.png',
    })
  );
});

// MESSAGES
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') self.skipWaiting();
  if (event.data && event.data.type === 'GET_VERSION') {
    if (event.ports[0]) event.ports[0].postMessage({ version: SW_VERSION });
  }
});
