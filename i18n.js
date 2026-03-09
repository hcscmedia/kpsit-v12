/**
 * KPS-IT.de i18n – Übersetzungssystem
 * Unterstützte Sprachen: de (Deutsch), en (Englisch)
 * Verwendung: window.KPS_i18n.t('key') oder data-i18n="key" im HTML
 */

(function(window) {
  'use strict';

  var translations = {

    /* ============================================================
       DEUTSCH (Standard)
       ============================================================ */
    de: {
      // Navigation
      nav_services:      'Leistungen',
      nav_calendar:      'Verfügbarkeit',
      nav_tracking:      'Auftrags-Tracking',
      nav_institutes:    'Institute',
      nav_contact:       'Kontakt',

      // Hero
      hero_badge:        'Offizieller Tätigkeitsnachweis',
      hero_title_1:      'Qualitätssicherung im',
      hero_title_2:      'Einzelhandel',
      hero_title_3:      '– Professionelle Datenerhebung.',
      hero_sub:          'Im Auftrag führender Marktforschungsinstitute und Handelsunternehmen erhebe ich strukturierte Daten zur Verbesserung von Servicequalität und Kundenerlebnis.',
      hero_btn_proof:    'Einsatznachweis öffnen',
      hero_btn_info:     'Tätigkeitsnachweis',

      // Leistungen
      section_services:  'Tätigkeitsbereiche',
      services_title:    'Was ich tue',
      services_sub:      'Systematische Datenerhebung im Einzelhandel – diskret, präzise und DSGVO-konform.',

      // Statistiken
      stats_assignments: 'Abgeschlossene Aufträge',
      stats_institutes:  'Kooperierende Institute',
      stats_regions:     'Berliner Bezirke',
      stats_years:       'Jahre Erfahrung',

      // Information
      section_info:      'Für das Marktpersonal',
      info_title:        'Information für Filialleiter & Marktpersonal',
      info_text:         'Diese Erhebungen dienen der Marktforschung und Qualitätsverbesserung. Alle Daten werden anonymisiert und gemäß DSGVO verarbeitet.',
      info_btn:          'DSGVO-Details ansehen',

      // Kontakt
      section_contact:   'Kontakt',
      contact_title:     'Kontakt aufnehmen',
      contact_sub:       'Für Verifizierungsanfragen, Kooperationen oder Rückfragen stehe ich gerne zur Verfügung.',
      contact_available: 'Verfügbar für neue Aufträge',
      form_name:         'Name',
      form_email:        'E-Mail',
      form_phone:        'Telefon (optional)',
      form_subject:      'Betreff',
      form_subject_ph:   'Bitte wählen…',
      form_subject_1:    'Verifizierungsanfrage',
      form_subject_2:    'Kooperationsanfrage',
      form_subject_3:    'Rückfrage zur Erhebung',
      form_subject_4:    'Sonstiges',
      form_message:      'Nachricht',
      form_message_ph:   'Ihre Nachricht…',
      form_dsgvo:        'Ich habe die Datenschutzerklärung gelesen und stimme der Verarbeitung meiner Daten zu.',
      form_submit:       'Nachricht senden',
      form_success:      'Ihre Nachricht wurde erfolgreich übermittelt. Ich melde mich in Kürze.',
      form_error:        'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.',

      // Kalender
      cal_title:         'Verfügbarkeitskalender',
      cal_sub:           'Sehen Sie meine aktuellen Verfügbarkeiten und buchen Sie einen Termin.',
      cal_available:     'Verfügbar',
      cal_booked:        'Gebucht',
      cal_partial:       'Teilweise verfügbar',
      cal_today:         'Heute',
      cal_prev:          'Vorheriger Monat',
      cal_next:          'Nächster Monat',
      cal_book_btn:      'Termin anfragen',
      cal_legend:        'Legende',
      cal_week_mo:       'Mo',
      cal_week_tu:       'Di',
      cal_week_we:       'Mi',
      cal_week_th:       'Do',
      cal_week_fr:       'Fr',
      cal_week_sa:       'Sa',
      cal_week_su:       'So',

      // Tracking
      track_title:       'Auftrags-Tracking',
      track_sub:         'Verfolgen Sie den Status Ihres laufenden Auftrags in Echtzeit.',
      track_id_label:    'Auftrags-ID',
      track_id_ph:       'z. B. AUF-2024-0042',
      track_pw_label:    'Zugangs-Code',
      track_pw_ph:       '••••••••',
      track_btn:         'Auftrag abrufen',
      track_status_1:    'Auftrag erhalten',
      track_status_2:    'In Bearbeitung',
      track_status_3:    'Erhebung läuft',
      track_status_4:    'Bericht wird erstellt',
      track_status_5:    'Abgeschlossen',
      track_not_found:   'Auftrag nicht gefunden. Bitte prüfen Sie ID und Zugangs-Code.',
      track_details:     'Auftragsdetails',
      track_client:      'Auftraggeber',
      track_type:        'Erhebungstyp',
      track_location:    'Einsatzort',
      track_date:        'Einsatzdatum',
      track_progress:    'Fortschritt',

      // Cookie-Banner
      cookie_title:      'Datenschutz-Einstellungen',
      cookie_text:       'Diese Website verwendet ausschließlich technisch notwendige Cookies. Es werden keine Tracking- oder Analyse-Cookies eingesetzt.',
      cookie_accept:     'Verstanden',
      cookie_details:    'Details ansehen',
      cookie_necessary:  'Notwendige Cookies',
      cookie_necessary_desc: 'Session-Verwaltung und Sicherheit (CSRF-Schutz). Können nicht deaktiviert werden.',

      // Footer
      footer_tagline:    'Zertifizierter Service-Tester & Marktforscher',
      footer_imprint:    'Impressum',
      footer_privacy:    'Datenschutz',
      footer_admin:      'Admin',

      // Allgemein
      read_more:         'Details ansehen',
      back:              '← Zurück',
      loading:           'Wird geladen…',
      error:             'Fehler',
      close:             'Schließen',
    },

    /* ============================================================
       ENGLISCH
       ============================================================ */
    en: {
      // Navigation
      nav_services:      'Services',
      nav_calendar:      'Availability',
      nav_tracking:      'Order Tracking',
      nav_institutes:    'Institutes',
      nav_contact:       'Contact',

      // Hero
      hero_badge:        'Official Activity Certificate',
      hero_title_1:      'Quality Assurance in',
      hero_title_2:      'Retail',
      hero_title_3:      '– Professional On-Site Data Collection.',
      hero_sub:          'On behalf of leading market research institutes and retail companies, I collect structured data to improve service quality and customer experience.',
      hero_btn_proof:    'Open Assignment Certificate',
      hero_btn_info:     'Activity Certificate',

      // Leistungen
      section_services:  'Service Areas',
      services_title:    'What I Do',
      services_sub:      'Systematic data collection in retail – discreet, precise and GDPR-compliant.',

      // Statistiken
      stats_assignments: 'Completed Assignments',
      stats_institutes:  'Cooperating Institutes',
      stats_regions:     'Berlin Districts',
      stats_years:       'Years of Experience',

      // Information
      section_info:      'For Store Staff',
      info_title:        'Information for Store Managers & Staff',
      info_text:         'These surveys serve market research and quality improvement. All data is anonymised and processed in accordance with GDPR.',
      info_btn:          'View GDPR Details',

      // Kontakt
      section_contact:   'Contact',
      contact_title:     'Get in Touch',
      contact_sub:       'Available for verification requests, cooperations or enquiries.',
      contact_available: 'Available for new assignments',
      form_name:         'Name',
      form_email:        'Email',
      form_phone:        'Phone (optional)',
      form_subject:      'Subject',
      form_subject_ph:   'Please select…',
      form_subject_1:    'Verification Request',
      form_subject_2:    'Cooperation Enquiry',
      form_subject_3:    'Question about Survey',
      form_subject_4:    'Other',
      form_message:      'Message',
      form_message_ph:   'Your message…',
      form_dsgvo:        'I have read the privacy policy and consent to the processing of my data.',
      form_submit:       'Send Message',
      form_success:      'Your message has been sent successfully. I will get back to you shortly.',
      form_error:        'An error occurred. Please try again or contact me directly by email.',

      // Kalender
      cal_title:         'Availability Calendar',
      cal_sub:           'View my current availability and request an appointment.',
      cal_available:     'Available',
      cal_booked:        'Booked',
      cal_partial:       'Partially available',
      cal_today:         'Today',
      cal_prev:          'Previous month',
      cal_next:          'Next month',
      cal_book_btn:      'Request appointment',
      cal_legend:        'Legend',
      cal_week_mo:       'Mon',
      cal_week_tu:       'Tue',
      cal_week_we:       'Wed',
      cal_week_th:       'Thu',
      cal_week_fr:       'Fri',
      cal_week_sa:       'Sat',
      cal_week_su:       'Sun',

      // Tracking
      track_title:       'Order Tracking',
      track_sub:         'Track the status of your ongoing assignment in real time.',
      track_id_label:    'Order ID',
      track_id_ph:       'e.g. AUF-2024-0042',
      track_pw_label:    'Access Code',
      track_pw_ph:       '••••••••',
      track_btn:         'Retrieve Order',
      track_status_1:    'Order Received',
      track_status_2:    'In Progress',
      track_status_3:    'Survey Running',
      track_status_4:    'Report Being Prepared',
      track_status_5:    'Completed',
      track_not_found:   'Order not found. Please check the ID and access code.',
      track_details:     'Order Details',
      track_client:      'Client',
      track_type:        'Survey Type',
      track_location:    'Location',
      track_date:        'Assignment Date',
      track_progress:    'Progress',

      // Cookie-Banner
      cookie_title:      'Privacy Settings',
      cookie_text:       'This website uses only technically necessary cookies. No tracking or analytics cookies are used.',
      cookie_accept:     'Understood',
      cookie_details:    'View Details',
      cookie_necessary:  'Necessary Cookies',
      cookie_necessary_desc: 'Session management and security (CSRF protection). Cannot be disabled.',

      // Footer
      footer_tagline:    'Certified Service Tester & Market Researcher',
      footer_imprint:    'Imprint',
      footer_privacy:    'Privacy Policy',
      footer_admin:      'Admin',

      // Allgemein
      read_more:         'View Details',
      back:              '← Back',
      loading:           'Loading…',
      error:             'Error',
      close:             'Close',
    }
  };

  /* ============================================================
     i18n-Engine
     ============================================================ */
  var KPS_i18n = {
    currentLang: 'de',

    /** Sprache initialisieren (aus localStorage oder Browser) */
    init: function() {
      var saved = localStorage.getItem('kps_lang');
      if (saved && translations[saved]) {
        this.currentLang = saved;
      } else {
        var browserLang = (navigator.language || 'de').substring(0, 2).toLowerCase();
        this.currentLang = translations[browserLang] ? browserLang : 'de';
      }
      this.apply();
      this.updateToggle();
    },

    /** Übersetzung abrufen */
    t: function(key) {
      var lang = translations[this.currentLang] || translations['de'];
      return lang[key] || translations['de'][key] || key;
    },

    /** Sprache wechseln */
    setLang: function(lang) {
      if (!translations[lang]) return;
      this.currentLang = lang;
      localStorage.setItem('kps_lang', lang);
      this.apply();
      this.updateToggle();
      // HTML lang-Attribut aktualisieren
      document.documentElement.lang = lang;
    },

    /** Alle data-i18n Elemente übersetzen */
    apply: function() {
      var self = this;
      // Texte
      document.querySelectorAll('[data-i18n]').forEach(function(el) {
        var key = el.getAttribute('data-i18n');
        el.textContent = self.t(key);
      });
      // Platzhalter
      document.querySelectorAll('[data-i18n-ph]').forEach(function(el) {
        el.placeholder = self.t(el.getAttribute('data-i18n-ph'));
      });
      // Aria-Labels
      document.querySelectorAll('[data-i18n-aria]').forEach(function(el) {
        el.setAttribute('aria-label', self.t(el.getAttribute('data-i18n-aria')));
      });
      // HTML-Inhalt (für formatierte Texte)
      document.querySelectorAll('[data-i18n-html]').forEach(function(el) {
        var key = el.getAttribute('data-i18n-html');
        var lang = translations[self.currentLang] || translations['de'];
        el.innerHTML = lang[key] || translations['de'][key] || key;
      });
      // Seiten-Titel
      var titleEl = document.querySelector('title[data-i18n-title]');
      if (titleEl) {
        var base = titleEl.getAttribute('data-i18n-title');
        titleEl.textContent = self.t(base) + ' | KPS-IT.de';
      }
      document.documentElement.lang = this.currentLang;
    },

    /** Sprachumschalter-Button aktualisieren */
    updateToggle: function() {
      var toggles = document.querySelectorAll('.lang-toggle');
      toggles.forEach(function(btn) {
        btn.setAttribute('data-lang', this.currentLang);
        var deSpan = btn.querySelector('.lang-de');
        var enSpan = btn.querySelector('.lang-en');
        if (deSpan) deSpan.classList.toggle('lang-active', this.currentLang === 'de');
        if (enSpan) enSpan.classList.toggle('lang-active', this.currentLang === 'en');
      }, this);
    },

    /** Sprache umschalten (toggle) */
    toggle: function() {
      this.setLang(this.currentLang === 'de' ? 'en' : 'de');
    }
  };

  window.KPS_i18n = KPS_i18n;

  // Auto-Init wenn DOM bereit
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() { KPS_i18n.init(); });
  } else {
    KPS_i18n.init();
  }

})(window);
