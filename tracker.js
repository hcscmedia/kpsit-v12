/* ============================================================
   KPS-IT.de – Datenschutzkonformer Besuchertracker
   Kein Cookie, keine vollständige IP-Speicherung
   Sendet: Seite, Referrer (nur Domain)
   ============================================================ */

(function () {
  'use strict';

  // Nur tracken wenn Einwilligung vorhanden ODER nur notwendige Cookies
  // Da wir nur technisch notwendige Daten senden (kein Tracking-Cookie),
  // ist keine explizite Einwilligung erforderlich (§ 25 TTDSG Ausnahme)

  function track() {
    var page = window.location.pathname || '/';
    var ref  = document.referrer || 'direct';

    // Fetch API mit Fallback auf XMLHttpRequest
    var payload = 'action=track&page=' + encodeURIComponent(page) + '&referrer=' + encodeURIComponent(ref);

    if (window.fetch) {
      fetch('stats-api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: payload,
        keepalive: true
      }).catch(function () {});
    } else {
      var xhr = new XMLHttpRequest();
      xhr.open('POST', 'stats-api.php', true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.send(payload);
    }
  }

  // Erst nach vollständigem Laden tracken (nicht blockierend)
  if (document.readyState === 'complete') {
    setTimeout(track, 100);
  } else {
    window.addEventListener('load', function () { setTimeout(track, 100); });
  }

})();
