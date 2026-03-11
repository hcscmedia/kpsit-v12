# KPS-IT.de – MySQL Datenbank Setup

## Voraussetzungen

- PHP 8.0+ mit PDO und PDO_MySQL
- MySQL 5.7+ oder MariaDB 10.3+
- Webserver (Apache/Nginx) mit PHP-Unterstützung

---

## Schritt 1: Datenbank anlegen

Führe folgende Befehle in deiner MySQL-Konsole aus:

```sql
CREATE DATABASE kpsit_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'kpsit_user'@'localhost' IDENTIFIED BY 'DEIN_SICHERES_PASSWORT';
GRANT ALL PRIVILEGES ON kpsit_db.* TO 'kpsit_user'@'localhost';
FLUSH PRIVILEGES;
```

---

## Schritt 2: Tabellen erstellen

Importiere die Datei `setup-db.sql`:

```bash
mysql -u kpsit_user -p kpsit_db < setup-db.sql
```

Oder führe die SQL-Datei direkt in phpMyAdmin aus.

---

## Schritt 3: Umgebungsvariablen setzen (empfohlen)

Die Zugangsdaten stehen nicht mehr im Code, sondern werden aus Umgebungsvariablen gelesen:

Als Vorlage kannst du `.env.example` verwenden.

```bash
export KPS_DB_HOST="localhost"
export KPS_DB_NAME="kpsit_db"
export KPS_DB_USER="kpsit_user"
export KPS_DB_PASS="DEIN_SICHERES_PASSWORT"
```

Optional für die Statistik-Hashung:

```bash
export KPS_STATS_SALT="EIN-LANGER-ZUFAELLIGER-WERT"
```

Hinweis fuer Shared Hosting ohne Shell-Umgebung:
Statt `export` die Werte in `.htaccess` per `SetEnv` setzen.

```apache
SetEnv KPS_USE_DB true
SetEnv KPS_DB_HOST localhost
SetEnv KPS_DB_NAME kpsit_db
SetEnv KPS_DB_USER kpsit_user
SetEnv KPS_DB_PASS DEIN_SICHERES_PASSWORT
SetEnv KPS_STATS_SALT EIN-LANGER-ZUFAELLIGER-WERT
```

---

## Schritt 4: MySQL aktivieren

Aktiviere Datenbankmodus über Umgebungsvariable:

```bash
export KPS_USE_DB="true"
```

Ohne diese Variable bleibt automatisch der JSON-Fallback aktiv.

---

## Schritt 5: data/ Verzeichnis anlegen

Auch ohne MySQL benötigt die Website ein beschreibbares `data/`-Verzeichnis:

```bash
mkdir -p /var/www/html/kps-berlin/data
chmod 755 /var/www/html/kps-berlin/data
chown www-data:www-data /var/www/html/kps-berlin/data
```

---

## Schritt 6: Runtime-Check ausführen (CLI)

Mit dem Script `runtime-check.php` kannst du nach dem Deployment prüfen,
ob ENV-Variablen, `USE_DB`, `data/` und die DB-Verbindung korrekt sind.

```bash
php runtime-check.php
echo $?
```

Exit-Codes:

- `0` = OK (oder DB-Check bewusst übersprungen bei JSON-Fallback)
- `2` = DB-Verbindung fehlgeschlagen
- `3` = DB verbunden, aber Test-Query fehlgeschlagen

---

## Ohne MySQL (Standard)

Die Website funktioniert **auch ohne MySQL** mit JSON-Datei-Speicherung im `data/`-Verzeichnis:

| Funktion | Ohne MySQL | Mit MySQL |
|---|---|---|
| Kontaktformular | ✓ (data/messages.json) | ✓ (Tabelle: messages) |
| Buchungsanfragen | ✓ (data/bookings.json) | ✓ (Tabelle: bookings) |
| Auftrags-Tracking | ✓ (data/orders.json) | ✓ (Tabelle: orders) |
| Einsatznachweise | ✓ (data/nachweise.json) | ✓ (Tabelle: nachweise) |
| Institute | ✓ (localStorage im Browser) | ✓ (Tabelle: institutes) |
| Statistiken | ✓ (data/stats.json) | ✓ (Tabelle: stats) |

---

## Admin-Zugangsdaten

Der Admin-Login nutzt Passwort-Hash in `admin-auth.php` (Konstante `ADMIN_PASSWORD_HASH`).

Hash einmalig erzeugen:

```bash
php -r "echo password_hash('DEIN_NEUES_PASSWORT', PASSWORD_BCRYPT, ['cost'=>12]);"
```

Danach den ausgegebenen Hash in `admin-auth.php` bei `ADMIN_PASSWORD_HASH` eintragen.

---

## Datei-Struktur

```
kps-berlin/
├── admin.php          ← Admin-Dashboard
├── send.php           ← Kontaktformular-API
├── tracking-api.php   ← Auftrags-Tracking-API
├── nachweis-api.php   ← Einsatznachweis-API
├── db.php             ← Datenbankverbindung (hier konfigurieren!)
├── setup-db.sql       ← MySQL-Schema
├── data/              ← JSON-Fallback-Speicherung (muss beschreibbar sein!)
│   ├── messages.json
│   ├── bookings.json
│   ├── orders.json
│   ├── nachweise.json
│   ├── availability.json
│   └── stats.json
└── ...
```
