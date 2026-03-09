/**
 * KPS-IT.de Cookie-Banner
 * DSGVO-konform: Nur technisch notwendige Cookies
 * Kein Tracking, keine Analyse-Cookies
 */

(function(window, document) {
  'use strict';

  var CONSENT_KEY = 'kps_cookie_consent';
  var CONSENT_VERSION = '1.0';

  var CookieBanner = {

    init: function() {
      // Bereits zugestimmt?
      var consent = this.getConsent();
      if (consent && consent.version === CONSENT_VERSION) return;

      // Banner nach kurzem Delay einblenden
      var self = this;
      setTimeout(function() {
        self.show();
      }, 1200);
    },

    getConsent: function() {
      try {
        var raw = localStorage.getItem(CONSENT_KEY);
        return raw ? JSON.parse(raw) : null;
      } catch(e) { return null; }
    },

    saveConsent: function() {
      try {
        localStorage.setItem(CONSENT_KEY, JSON.stringify({
          version:   CONSENT_VERSION,
          timestamp: new Date().toISOString(),
          necessary: true,
        }));
      } catch(e) {}
    },

    show: function() {
      var banner = document.getElementById('cookie-banner');
      if (banner) {
        banner.classList.add('visible');
      }
    },

    hide: function() {
      var banner = document.getElementById('cookie-banner');
      if (banner) {
        banner.classList.remove('visible');
        // Nach Animation entfernen
        setTimeout(function() {
          if (banner.parentNode) banner.parentNode.removeChild(banner);
        }, 500);
      }
    },

    accept: function() {
      this.saveConsent();
      this.hide();
      this.closeModal();
    },

    openModal: function() {
      var overlay = document.getElementById('cookie-modal-overlay');
      if (overlay) overlay.classList.add('visible');
    },

    closeModal: function() {
      var overlay = document.getElementById('cookie-modal-overlay');
      if (overlay) overlay.classList.remove('visible');
    }
  };

  window.CookieBanner = CookieBanner;

  // Auto-Init
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() { CookieBanner.init(); });
  } else {
    CookieBanner.init();
  }

})(window, document);
