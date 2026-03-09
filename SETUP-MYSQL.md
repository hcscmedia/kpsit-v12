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

## Schritt 3: db.php konfigurieren

Öffne `db.php` und trage deine Zugangsdaten ein:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'kpsit_db');
define('DB_USER', 'kpsit_user');
define('DB_PASS', 'DEIN_SICHERES_PASSWORT');
```

---

## Schritt 4: MySQL aktivieren

In `db.php` ändere:

```php
define('USE_DB', true);
```

Oder setze es in jeder API-Datei (`send.php`, `tracking-api.php`, `nachweis-api.php`):

```php
define('USE_DB', true);
```

---

## Schritt 5: data/ Verzeichnis anlegen

Auch ohne MySQL benötigt die Website ein beschreibbares `data/`-Verzeichnis:

```bash
mkdir -p /var/www/html/kps-berlin/data
chmod 755 /var/www/html/kps-berlin/data
chown www-data:www-data /var/www/html/kps-berlin/data
```

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

Standard-Login für `admin.php`:

- **Benutzername:** `admin`
- **Passwort:** `kps2026!`

**Wichtig:** Passwort in `admin.php` Zeile ~15 ändern:

```php
define('ADMIN_PASS', password_hash('DEIN_NEUES_PASSWORT', PASSWORD_DEFAULT));
```

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
