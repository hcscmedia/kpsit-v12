/* ============================================================
   KPS-IT.de – Schema.org JSON-LD
   Strukturierte Daten für Google Rich Snippets
   Person | LocalBusiness | Service | WebSite | BreadcrumbList
   ============================================================ */

(function () {
  'use strict';

  var BASE_URL = 'https://www.kps-it.de';

  /* ============================================================
     GEMEINSAME SCHEMA-OBJEKTE
     ============================================================ */

  var personSchema = {
    "@context": "https://schema.org",
    "@type": "Person",
    "@id": BASE_URL + "/#person",
    "name": "[Ihr vollständiger Name]",
    "jobTitle": "Zertifizierter Service-Tester & Marktforscher",
    "description": "Professioneller Mystery Shopper und Marktforscher in Berlin und Märkisch-Oderland. Spezialisiert auf Preiserhebungen, Service-Tests, Fotodokumentation und DSGVO-konforme Datenerhebung.",
    "url": BASE_URL,
    "email": "info@kps-it.de",
    "telephone": "+49-30-000000",
    "address": {
      "@type": "PostalAddress",
      "addressLocality": "Berlin",
      "addressRegion": "Berlin",
      "addressCountry": "DE"
    },
    "areaServed": [
      {
        "@type": "City",
        "name": "Berlin",
        "sameAs": "https://www.wikidata.org/wiki/Q64"
      },
      {
        "@type": "AdministrativeArea",
        "name": "Märkisch-Oderland",
        "sameAs": "https://www.wikidata.org/wiki/Q6770"
      }
    ],
    "knowsAbout": [
      "Mystery Shopping",
      "Marktforschung",
      "Preiserhebung",
      "Qualitätssicherung",
      "DSGVO",
      "Fotodokumentation",
      "Einzelhandel"
    ],
    "sameAs": [
      "https://www.linkedin.com/in/kps-berlin"
    ]
  };

  var localBusinessSchema = {
    "@context": "https://schema.org",
    "@type": ["LocalBusiness", "ProfessionalService"],
    "@id": BASE_URL + "/#business",
    "name": "KPS-IT.de",
    "alternateName": "KPS Berlin – Service-Tester & Marktforscher",
    "description": "Zertifizierter Service-Tester und Marktforscher in Berlin. Professionelle Qualitätssicherung im Einzelhandel durch Mystery Shopping, Preiserhebungen und Fotodokumentation.",
    "url": BASE_URL,
    "logo": BASE_URL + "/icons/icon-512.png",
    "image": BASE_URL + "/icons/icon-512.png",
    "telephone": "+49-30-000000",
    "email": "info@kps-it.de",
    "address": {
      "@type": "PostalAddress",
      "addressLocality": "Berlin",
      "addressRegion": "Berlin",
      "postalCode": "10115",
      "addressCountry": "DE"
    },
    "geo": {
      "@type": "GeoCoordinates",
      "latitude": 52.52,
      "longitude": 13.405
    },
    "areaServed": {
      "@type": "GeoCircle",
      "geoMidpoint": {
        "@type": "GeoCoordinates",
        "latitude": 52.52,
        "longitude": 13.405
      },
      "geoRadius": "50000"
    },
    "priceRange": "€€",
    "openingHoursSpecification": [
      {
        "@type": "OpeningHoursSpecification",
        "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
        "opens": "08:00",
        "closes": "20:00"
      },
      {
        "@type": "OpeningHoursSpecification",
        "dayOfWeek": ["Saturday"],
        "opens": "09:00",
        "closes": "17:00"
      }
    ],
    "hasOfferCatalog": {
      "@type": "OfferCatalog",
      "name": "Marktforschungs-Leistungen",
      "itemListElement": [
        {
          "@type": "Offer",
          "itemOffered": {
            "@type": "Service",
            "name": "Preiserhebungen",
            "url": BASE_URL + "/preiserhebungen.html"
          }
        },
        {
          "@type": "Offer",
          "itemOffered": {
            "@type": "Service",
            "name": "Mystery Shopping / Service-Tests",
            "url": BASE_URL + "/service-tests.html"
          }
        },
        {
          "@type": "Offer",
          "itemOffered": {
            "@type": "Service",
            "name": "Fotodokumentation",
            "url": BASE_URL + "/fotodokumentation.html"
          }
        },
        {
          "@type": "Offer",
          "itemOffered": {
            "@type": "Service",
            "name": "Verfügbarkeits-Checks",
            "url": BASE_URL + "/verfuegbarkeits-checks.html"
          }
        },
        {
          "@type": "Offer",
          "itemOffered": {
            "@type": "Service",
            "name": "Berichterstellung",
            "url": BASE_URL + "/berichterstellung.html"
          }
        }
      ]
    },
    "employee": { "@id": BASE_URL + "/#person" }
  };

  var websiteSchema = {
    "@context": "https://schema.org",
    "@type": "WebSite",
    "@id": BASE_URL + "/#website",
    "name": "KPS-IT.de",
    "url": BASE_URL,
    "description": "Digitale Visitenkarte und Legitimationsseite für zertifizierten Service-Tester und Marktforscher in Berlin.",
    "inLanguage": ["de", "en"],
    "publisher": { "@id": BASE_URL + "/#business" },
    "potentialAction": {
      "@type": "ContactAction",
      "target": BASE_URL + "/#kontakt",
      "name": "Kontakt aufnehmen"
    }
  };

  /* ============================================================
     SEITEN-SPEZIFISCHE SCHEMAS
     ============================================================ */

  var PAGE_SCHEMAS = {
    '/': [personSchema, localBusinessSchema, websiteSchema],
    '/index.html': [personSchema, localBusinessSchema, websiteSchema],

    '/preiserhebungen.html': [
      personSchema,
      {
        "@context": "https://schema.org",
        "@type": "Service",
        "name": "Preiserhebungen",
        "description": "Systematische Erfassung von Produktdaten, Preisauszeichnungen und Sonderangeboten gemäß definiertem Erhebungsraster für Wettbewerbsanalysen.",
        "provider": { "@id": BASE_URL + "/#business" },
        "areaServed": "Berlin, Märkisch-Oderland",
        "serviceType": "Marktforschung",
        "url": BASE_URL + "/preiserhebungen.html",
        "breadcrumb": {
          "@type": "BreadcrumbList",
          "itemListElement": [
            { "@type": "ListItem", "position": 1, "name": "Startseite", "item": BASE_URL },
            { "@type": "ListItem", "position": 2, "name": "Preiserhebungen", "item": BASE_URL + "/preiserhebungen.html" }
          ]
        }
      }
    ],

    '/service-tests.html': [
      personSchema,
      {
        "@context": "https://schema.org",
        "@type": "Service",
        "name": "Mystery Shopping & Service-Tests",
        "description": "Verdeckte Beratungsgespräche zur objektiven Bewertung der Kundenberatung und Identifikation von Optimierungspotenzialen in der Servicequalität.",
        "provider": { "@id": BASE_URL + "/#business" },
        "areaServed": "Berlin, Märkisch-Oderland",
        "serviceType": "Mystery Shopping",
        "url": BASE_URL + "/service-tests.html",
        "breadcrumb": {
          "@type": "BreadcrumbList",
          "itemListElement": [
            { "@type": "ListItem", "position": 1, "name": "Startseite", "item": BASE_URL },
            { "@type": "ListItem", "position": 2, "name": "Service-Tests", "item": BASE_URL + "/service-tests.html" }
          ]
        }
      }
    ],

    '/fotodokumentation.html': [
      personSchema,
      {
        "@context": "https://schema.org",
        "@type": "Service",
        "name": "Fotodokumentation",
        "description": "Visuelle Erfassung von Regalplatzierungen, Warenpräsentation und Flächengestaltung für Compliance-Prüfung und Planogramm-Kontrolle.",
        "provider": { "@id": BASE_URL + "/#business" },
        "serviceType": "Fotodokumentation",
        "url": BASE_URL + "/fotodokumentation.html"
      }
    ],

    '/verfuegbarkeits-checks.html': [
      personSchema,
      {
        "@context": "https://schema.org",
        "@type": "Service",
        "name": "Verfügbarkeits-Checks",
        "description": "Systematische Überprüfung von Produktverfügbarkeit, Out-of-Stock-Situationen und Lieferkettenkonformität direkt am Point of Sale.",
        "provider": { "@id": BASE_URL + "/#business" },
        "serviceType": "Verfügbarkeitserhebung",
        "url": BASE_URL + "/verfuegbarkeits-checks.html"
      }
    ],

    '/berichterstellung.html': [
      personSchema,
      {
        "@context": "https://schema.org",
        "@type": "Service",
        "name": "Berichterstellung",
        "description": "Strukturierte Aufbereitung aller Erhebungsdaten in standardisierten Berichten mit Handlungsempfehlungen für Auftraggeber.",
        "provider": { "@id": BASE_URL + "/#business" },
        "serviceType": "Reporting",
        "url": BASE_URL + "/berichterstellung.html"
      }
    ],

    '/dsgvo-compliance.html': [
      personSchema,
      {
        "@context": "https://schema.org",
        "@type": "Service",
        "name": "DSGVO-Compliance",
        "description": "Alle Erhebungen erfolgen unter strikter Einhaltung der Datenschutz-Grundverordnung. Vollständige Anonymisierung aller personenbezogenen Daten.",
        "provider": { "@id": BASE_URL + "/#business" },
        "serviceType": "Datenschutz-Compliance",
        "url": BASE_URL + "/dsgvo-compliance.html"
      }
    ],

    '/institute.html': [
      personSchema,
      {
        "@context": "https://schema.org",
        "@type": "WebPage",
        "name": "Marktforschungsinstitute – KPS-IT.de",
        "description": "Übersicht bekannter Marktforschungsinstitute und Kooperationspartner für Mystery Shopping und Marktforschung in Deutschland.",
        "url": BASE_URL + "/institute.html",
        "breadcrumb": {
          "@type": "BreadcrumbList",
          "itemListElement": [
            { "@type": "ListItem", "position": 1, "name": "Startseite", "item": BASE_URL },
            { "@type": "ListItem", "position": 2, "name": "Institute", "item": BASE_URL + "/institute.html" }
          ]
        }
      }
    ]
  };

  /* ============================================================
     SCHEMA INJIZIEREN
     ============================================================ */

  function injectSchemas() {
    var path = window.location.pathname;
    // Normalisieren: /index.html → /
    if (path === '/index.html') path = '/';

    var schemas = PAGE_SCHEMAS[path] || [personSchema, localBusinessSchema];

    schemas.forEach(function (schema) {
      var script = document.createElement('script');
      script.type = 'application/ld+json';
      script.textContent = JSON.stringify(schema, null, 2);
      document.head.appendChild(script);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', injectSchemas);
  } else {
    injectSchemas();
  }

})();
