# KPS-IT.de – Website

## Enthaltene Dateien

| Datei              | Beschreibung                                                  |
|--------------------|---------------------------------------------------------------|
| `index.html`       | One-Pager (alle Sektionen, Dark Design, alle Features)        |
| `style.css`        | Dark Mode CSS (Mobile First, Glassmorphism)                   |
| `main.js`          | JavaScript (Animationen, Formular, QR-Code, Counter, Nav)     |
| `send.php`         | PHP-Backend für Kontaktformular (CSRF, Honeypot, Rate-Limit)  |
| `qrcode.min.js`    | QR-Code-Bibliothek (lokal, keine CDN-Abhängigkeit)            |
| `impressum.html`   | Impressum (Platzhalter ausfüllen!)                            |
| `datenschutz.html` | Datenschutzerklärung (Platzhalter ausfüllen!)                 |
| `.htaccess`        | Apache-Konfiguration (Caching, Sicherheit, Header)            |
| `data/.htaccess`   | Schützt das data/-Verzeichnis vor direktem Zugriff            |

## Neue Features (v2)

- **Dark Mode Design** – Tiefes Dunkelblau/Schwarz mit Indigo-Akzenten
- **Glassmorphism** – Frosted-Glass-Effekte auf Header und Karten
- **Animierter Hero** – Floating Orbs, Grid-Hintergrund, Scroll-Indikator
- **6 Leistungskarten** – Mit Hover-Glow und Tag-Labels
- **Animierte Statistiken** – Zahlen zählen beim Einblenden hoch
- **Digitaler Dienstausweis** – Mit QR-Code und Druckfunktion
- **Kontaktformular** – Mit PHP-Backend, CSRF-Schutz, Honeypot, Rate-Limiting
- **Bestätigungs-E-Mail** – Automatische Antwort an den Absender
- **Scroll-Animationen** – Elemente blenden beim Scrollen ein
- **Aktive Navigation** – Aktueller Abschnitt wird im Menü hervorgehoben
- **Mobile Hamburger-Menü** – Animiertes Menü für Smartphones
- **Back-to-Top-Button** – Erscheint beim Scrollen
- **Preloader** – Kurze Ladeanimation beim ersten Aufruf

## Pflichtanpassungen vor Go-Live

### `index.html`
```
E-Mail:     info@kps-it.de  → Ihre E-Mail
Telefon:    +49 30 000 000      → Ihre Telefonnummer
LinkedIn:   /in/kps-it → Ihr LinkedIn-Profil
Name:       [Ihr Name]          → Ihr vollständiger Name
Ausweis-Nr: KPS-2024-001        → Ihre Ausweisnummer
Gültig bis: 31.12.2026          → Ihr Datum
```

### `send.php`
```php
define('RECIPIENT_EMAIL', 'info@kps-it.de');  // → Ihre E-Mail
define('RECIPIENT_NAME',  'KPS-IT.de Service');   // → Ihr Name
define('SENDER_FROM',     'noreply@kps-it.de'); // → Ihre Domain
```

### `impressum.html` & `datenschutz.html`
- Alle `[Platzhalter]` durch Ihre echten Daten ersetzen.

### `.htaccess`
- HTTPS-Weiterleitung aktivieren (Kommentarzeichen `#` entfernen), sobald SSL-Zertifikat vorhanden.

## Upload-Anleitung

1. ZIP entpacken
2. **Alle Dateien** per FTP in `public_html/` hochladen
3. Versteckte Dateien (`.htaccess`, `data/.htaccess`) mitübertragen
4. Schreibrechte für `data/`-Verzeichnis prüfen: `chmod 755 data/`
5. PHP-Version auf dem Hoster: mindestens **PHP 7.4**

## Technische Anforderungen

- Apache-Webhoster mit PHP 7.4+
- `mod_rewrite`, `mod_headers`, `mod_expires` (Standard bei allen gängigen Hostern)
- PHP `mail()`-Funktion aktiviert (Standard)
- Schreibrechte auf `data/`-Verzeichnis für Rate-Limiting

## Sicherheitsfeatures (Kontaktformular)

| Feature        | Beschreibung                                              |
|----------------|-----------------------------------------------------------|
| CSRF-Token     | Verhindert Cross-Site-Request-Forgery-Angriffe            |
| Honeypot       | Unsichtbares Feld fängt automatische Bots ab              |
| Rate-Limiting  | Max. 5 Anfragen pro IP in 10 Minuten                      |
| Input-Sanitize | Alle Eingaben werden bereinigt und validiert              |
| Spam-Filter    | Erkennt URLs und typische Spam-Muster in Nachrichten      |
| Whitelist      | Nur erlaubte Betreff-Optionen werden akzeptiert           |
