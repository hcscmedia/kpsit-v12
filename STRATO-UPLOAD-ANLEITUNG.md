# Strato Upload-Anleitung – kps-it.de

**Ziel:** Die KPS-IT.de Website auf Strato Hosting Basic live schalten  
**Dauer:** ca. 15–20 Minuten  
**Voraussetzung:** Zugang zu login.strato.de

---

## Schritt 1 – Strato-Kundenbereich öffnen

1. Öffnen Sie **https://www.strato.de** in Ihrem Browser
2. Klicken Sie oben rechts auf **„Mein Konto"** → **„Login"**
3. Melden Sie sich mit Ihrer Kundennummer und Ihrem Passwort an
4. Sie befinden sich jetzt im **Strato Kundenbereich (IONOS-ähnliche Oberfläche)**

---

## Schritt 2 – Dateimanager öffnen

1. Klicken Sie im Kundenbereich auf **„Hosting"** oder **„Pakete"**
2. Wählen Sie Ihr **Hosting Basic**-Paket aus
3. Suchen Sie den Bereich **„Webspace"** oder **„Dateiverwaltung"**
4. Klicken Sie auf **„Dateimanager"** (öffnet sich in einem neuen Tab)

> **Alternativ per FTP:** Falls Sie ein FTP-Programm (z.B. FileZilla) bevorzugen, finden Sie die FTP-Zugangsdaten im Strato-Kundenbereich unter **„Hosting" → „FTP-Zugänge"**.

---

## Schritt 3 – Richtiges Verzeichnis wählen

Im Dateimanager sehen Sie eine Ordnerstruktur. Navigieren Sie in den Ordner:

```
/htdocs/
```

> **Wichtig:** Bei Strato Hosting Basic heißt das öffentliche Webverzeichnis **`htdocs`** (nicht `public_html` oder `www`). Alle Dateien müssen direkt in diesen Ordner hochgeladen werden.

Falls bereits eine Datei `index.html` oder `index.php` vorhanden ist (Strato-Platzhalterseite), löschen Sie diese zuerst.

---

## Schritt 4 – Dateien hochladen

### Option A: ZIP hochladen und entpacken (empfohlen)

1. Klicken Sie im Dateimanager auf **„Hochladen"** oder **„Upload"**
2. Wählen Sie die Datei **`kps-it-website-final.zip`** von Ihrem Computer aus
3. Warten Sie bis der Upload abgeschlossen ist
4. Klicken Sie mit der **rechten Maustaste** auf die ZIP-Datei
5. Wählen Sie **„Entpacken"** oder **„Extrahieren"**
6. Stellen Sie sicher, dass als Zielverzeichnis **`/htdocs/`** gewählt ist
7. Bestätigen Sie mit **„OK"**

> **Achtung:** Nach dem Entpacken darf kein Unterordner entstehen. Alle Dateien (`index.html`, `style.css` usw.) müssen **direkt** in `/htdocs/` liegen – nicht in `/htdocs/kps-berlin/`.

### Option B: Dateien einzeln hochladen

Falls das ZIP-Entpacken nicht funktioniert, entpacken Sie die ZIP-Datei zuerst lokal auf Ihrem Computer und laden Sie dann alle Dateien und Ordner einzeln hoch.

---

## Schritt 5 – Versteckte Dateien sichtbar machen (.htaccess)

Die Datei `.htaccess` beginnt mit einem Punkt und ist standardmäßig **versteckt**. So stellen Sie sicher, dass sie hochgeladen wurde:

1. Aktivieren Sie im Dateimanager die Option **„Versteckte Dateien anzeigen"** (meist ein Häkchen oder Einstellung)
2. Prüfen Sie, ob `.htaccess` im `/htdocs/`-Verzeichnis vorhanden ist
3. Falls nicht: Laden Sie sie separat hoch

---

## Schritt 6 – data/-Verzeichnis anlegen und schützen

Das PHP-Backend benötigt einen beschreibbaren Ordner zum Speichern von Daten:

1. Erstellen Sie im Dateimanager unter `/htdocs/` einen neuen Ordner namens **`data`**
2. Setzen Sie die **Berechtigungen (CHMOD)** des `data`-Ordners auf **`755`** (Rechtsklick → Eigenschaften → Berechtigungen)
3. Laden Sie die Datei **`data/.htaccess`** in diesen Ordner hoch (schützt die JSON-Dateien vor direktem Zugriff)

---

## Schritt 7 – SSL-Zertifikat aktivieren (kostenlos bei Strato)

1. Gehen Sie zurück in den Strato-Kundenbereich
2. Navigieren Sie zu **„Domains"** → wählen Sie **kps-it.de**
3. Klicken Sie auf **„SSL/TLS-Zertifikat"** oder **„HTTPS"**
4. Aktivieren Sie das **kostenlose Let's Encrypt-Zertifikat**
5. Warten Sie 5–15 Minuten bis es aktiv ist

> Nach der SSL-Aktivierung leitet die `.htaccess` automatisch alle HTTP-Aufrufe auf HTTPS um.

---

## Schritt 8 – Website testen

Öffnen Sie nach dem Upload folgende URLs in Ihrem Browser:

| URL | Erwartetes Ergebnis |
|---|---|
| `https://www.kps-it.de` | Startseite lädt korrekt |
| `https://www.kps-it.de/admin.php` | Admin-Login erscheint |
| `https://www.kps-it.de/kalender.html` | Kalender wird angezeigt |
| `https://www.kps-it.de/institute.html` | Institute-Seite lädt |
| `https://www.kps-it.de/einsatznachweis.html` | Einsatznachweis-Formular |
| `http://kps-it.de` | Weiterleitung auf https://www. |

Wenn Sie SSH-Zugriff auf das Hosting haben, führen Sie zusätzlich den Runtime-Check aus:

```bash
php runtime-check.php
```

So sehen Sie sofort, ob `KPS_USE_DB`/DB-Zugang und `data/` korrekt konfiguriert sind.

---

## Schritt 9 – Pflichtangaben anpassen

Öffnen Sie diese Dateien direkt im Strato-Dateimanager (Rechtsklick → Bearbeiten) und ersetzen Sie die Platzhalter:

### `index.html` und alle Unterseiten
```
[Ihr Name]          → Ihren vollständigen Namen
info@kps-it.de      → Ihre echte E-Mail-Adresse
+49 30 000 000      → Ihre Telefonnummer
/in/kps-it → Ihr LinkedIn-Profil-Pfad
```

### `impressum.html`
```
[Ihr vollständiger Name]    → Pflichtangabe
[Straße und Hausnummer]     → Pflichtangabe
[PLZ] [Ort]                 → Pflichtangabe
```

### `admin-auth.php`
Admin-Login nutzt Passwort-Hash. Erzeuge lokal einen neuen Hash:

```bash
php -r "echo password_hash('DEIN_NEUES_PASSWORT', PASSWORD_BCRYPT, ['cost'=>12]);"
```

Trage den Hash danach in `admin-auth.php` bei `ADMIN_PASSWORD_HASH` ein.

### ENV-Konfiguration fuer PHP (wichtig)
Die App liest DB- und Sicherheitswerte aus Umgebungsvariablen.
Nimm `.env.example` als Vorlage und setze die Werte auf Strato in `.htaccess` per `SetEnv`:

```apache
SetEnv KPS_USE_DB true
SetEnv KPS_DB_HOST localhost
SetEnv KPS_DB_NAME kpsit_db
SetEnv KPS_DB_USER kpsit_user
SetEnv KPS_DB_PASS DEIN_SICHERES_PASSWORT
SetEnv KPS_STATS_SALT EIN-LANGER-ZUFAELLIGER-WERT
```

Wenn du ohne MySQL arbeiten willst, setze:

```apache
SetEnv KPS_USE_DB false
```

---

## Schritt 10 – Google Search Console einrichten (empfohlen)

1. Öffnen Sie **https://search.google.com/search-console**
2. Klicken Sie auf **„Property hinzufügen"**
3. Geben Sie `https://www.kps-it.de` ein
4. Wählen Sie die Verifizierungsmethode **„HTML-Tag"**
5. Kopieren Sie den Meta-Tag und fügen Sie ihn in `index.html` im `<head>`-Bereich ein
6. Klicken Sie auf **„Bestätigen"**
7. Reichen Sie die Sitemap ein: `https://www.kps-it.de/sitemap.xml`

---

## Häufige Probleme und Lösungen

| Problem | Ursache | Lösung |
|---|---|---|
| Seite zeigt Strato-Platzhalter | Alte `index.html` nicht gelöscht | Strato-Datei löschen, eigene hochladen |
| `.htaccess` funktioniert nicht | Datei nicht hochgeladen | Versteckte Dateien anzeigen, erneut hochladen |
| Kontaktformular sendet nicht | `data/`-Ordner fehlt oder falsche Rechte | Ordner anlegen, CHMOD 755 setzen |
| Admin-Dashboard nicht erreichbar | PHP nicht aktiv | Strato-Support: PHP-Version auf 8.x setzen |
| Karte lädt nicht | CSP-Header zu restriktiv | `.htaccess` CSP-Zeile prüfen |
| SSL-Zertifikat fehlt | Noch nicht aktiviert | Strato-Kundenbereich → Domains → SSL |

---

## Support-Kontakte

- **Strato Kundenservice:** 030 300 146 0 (Mo–Fr 8–20 Uhr, Sa 9–18 Uhr)
- **Strato Hilfe-Center:** https://www.strato.de/faq/
- **FTP-Zugangsdaten:** Strato-Kundenbereich → Hosting → FTP-Zugänge

---

*Erstellt für kps-it.de | Strato Hosting Basic | Stand: März 2026*
