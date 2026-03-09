# KPS-IT.de – Moderne Website & Admin-Dashboard

Eine hochmoderne, interaktive One-Pager-Website für IT- und Servicedienstleistungen, kombiniert mit einem leistungsstarken, maßgeschneiderten PHP-Backend zur Verwaltung von Kundenanfragen, Aufträgen und Terminen.

## ✨ Key Features

### Frontend (UI/UX)
- **Modernes Design:** Dark Mode (Tiefes Dunkelblau/Schwarz mit Indigo-Akzenten) und Glassmorphism-Effekte.
- **Interaktive Elemente:** Animierter Hero-Bereich (Floating Orbs), Scroll-Animationen (AOS), dynamische Counter.
- **Digitaler Dienstausweis:** Inklusive lokal generiertem QR-Code (kein externes CDN) und Druckfunktion.
- **Responsive & Fast:** Mobile-First Ansatz, animiertes Hamburger-Menü und Preloader.

### Backend & Admin-Dashboard (`admin.php`)
- **Hybrides Speichersystem:** Läuft wahlweise mit **MySQL** (PDO) oder komplett ohne Datenbank via **JSON-Fallback** im `data/`-Verzeichnis.
- **Zentrales Dashboard:** KPIs für Aufrufe, Nachrichten, Buchungen und aktive Aufträge.
- **Auftrags-Tracking:** Erstellung von Aufträgen mit individuellem Zugangscode für Kunden.
- **Verwaltung:** Management von Instituten & Partnern, Einsatznachweisen und Buchungsanfragen.
- **Kalender:** Integriertes Tool zur Pflege der eigenen Verfügbarkeit.

### 🛡️ Sicherheit & Spam-Schutz
- **Formularschutz:** CSRF-Tokens, unsichtbares Honeypot-Feld gegen Bots und Input-Sanitizing.
- **Rate-Limiting:** Maximal 5 Anfragen pro IP-Adresse innerhalb von 10 Minuten (threadsicher).
- **Session-Sicherheit:** Verhindert Session-Fixation im Admin-Bereich, Passwörter werden sicher gehasht (`password_hash`).
- **Verzeichnisschutz:** Das sensible `data/`-Verzeichnis wird durch eine eigene `.htaccess` geschützt.

---

## 📁 Dateistruktur (Auszug)

| Datei | Beschreibung |
| :--- | :--- |
| `index.html` | Das Haupt-Frontend (One-Pager) |
| `admin.php` | Das zentrale Admin-Dashboard zur Systemverwaltung |
| `db.php` | Konfiguration für MySQL-Zugang und JSON-Fallback-Logik |
| `send.php` | API für das Kontaktformular (inkl. E-Mail-Versand) |
| `tracking-api.php` | API für das öffentliche Auftrags-Tracking |
| `main.js` | Frontend-Logik (Animationen, Menü, Formularvalidierung) |
| `qrcode.min.js` | Lokale QR-Code-Bibliothek für den Dienstausweis |
| `data/` | Verzeichnis für die JSON-Datenhaltung (falls MySQL deaktiviert ist) |
| `.htaccess` | Apache-Konfiguration (Caching, Security-Header, HTTPS-Routing) |

---

## 🚀 Installation & Setup

Das Projekt kann auf jedem Standard-Webspace (Apache/Nginx) mit **PHP 8.0+** betrieben werden.

### Variante 1: Ohne Datenbank (Flat-File / JSON)
Dies ist der Standardmodus. Es wird keine Datenbank benötigt.
1. Repository klonen oder ZIP entpacken.
2. Alle Dateien per FTP in das Web-Root-Verzeichnis (z. B. `public_html/`) hochladen.
3. Versteckte Dateien (`.htaccess`, `data/.htaccess`) zwingend mitkopieren.
4. **Wichtig:** Das Verzeichnis `data/` benötigt Schreibrechte! (`chmod 755 data/`)

### Variante 2: Mit MySQL-Datenbank
Für größere Datenmengen wird MySQL empfohlen.
1. Führe die Datei `setup-db.sql` in deiner MySQL-Datenbank aus.
2. Öffne die Datei `db.php` und trage deine Zugangsdaten ein (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).
3. Setze in der `db.php` die Konstante `USE_DB` auf `true`.
*(Weitere Details findest du in der Datei `SETUP-MYSQL.md`)*

---

## ⚠️ Pflichtanpassungen vor Go-Live!

Bevor die Seite produktiv genutzt wird, müssen folgende Dinge zwingend angepasst werden:

1. **Admin-Passwort ändern:** In `admin.php` muss der Standard-Passwort-Hash in Zeile 5 ausgetauscht werden. Generiere einen neuen Hash mit PHP `password_hash('DeinNeuesPasswort', PASSWORD_DEFAULT)`.
2. **Kontaktdaten (Frontend):** In der `index.html` alle Platzhaltertexte (E-Mail, Telefonnummer, Name, Ausweis-Nr.) anpassen.
3. **Rechtstexte:** `impressum.html` und `datenschutz.html` mit echten Daten befüllen.
4. **E-Mail-Routing:** In der `send.php` die Konstanten `RECIPIENT_EMAIL` und `SENDER_FROM` auf deine echten Mail-Adressen ändern.
5. **HTTPS aktivieren:** Sobald ein SSL-Zertifikat vorhanden ist, in der Haupt-`.htaccess` die Weiterleitung auf HTTPS aktivieren (Kommentarzeichen `#` entfernen).

---

## 👨‍💻 Technologie-Stack
* **Frontend:** HTML5, CSS3 (Vanilla, CSS Variables), Vanilla JavaScript
* **Backend:** PHP 8.0+
* **Datenbank:** MySQL / MariaDB (via PDO) oder dateibasiertes JSON-Fallback
