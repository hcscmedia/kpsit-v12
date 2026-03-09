-- ============================================================
-- KPS-IT.de – Datenbankschema für MySQL 8.0
-- Datenbank: dbs15415310
-- ============================================================
-- ANLEITUNG phpMyAdmin:
--   1. Links auf "dbs15415310" klicken (Datenbank auswählen)
--   2. Oben auf "Importieren" klicken
--   3. "Datei auswählen" → diese Datei hochladen
--   4. Zeichensatz: utf8mb4 (Standard lassen)
--   5. "OK" / "Ausführen" klicken
-- ============================================================
-- HINWEIS: Kein CREATE DATABASE, kein CREATE USER –
-- direkt in bestehende Datenbank importierbar!
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+01:00';
SET foreign_key_checks = 0;

-- ============================================================
-- Tabelle: messages (Kontaktformular-Nachrichten)
-- ============================================================
CREATE TABLE IF NOT EXISTS `messages` (
  `id`         VARCHAR(32)  NOT NULL,
  `name`       VARCHAR(100) NOT NULL DEFAULT '',
  `email`      VARCHAR(200) NOT NULL DEFAULT '',
  `phone`      VARCHAR(50)  NOT NULL DEFAULT '',
  `subject`    VARCHAR(200) NOT NULL DEFAULT '',
  `message`    TEXT         NOT NULL,
  `ip`         VARCHAR(64)  NOT NULL DEFAULT '',
  `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_read` (`is_read`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabelle: bookings (Buchungsanfragen)
-- ============================================================
CREATE TABLE IF NOT EXISTS `bookings` (
  `id`           VARCHAR(32)  NOT NULL,
  `name`         VARCHAR(100) NOT NULL DEFAULT '',
  `email`        VARCHAR(200) NOT NULL DEFAULT '',
  `phone`        VARCHAR(50)  NOT NULL DEFAULT '',
  `organization` VARCHAR(200) NOT NULL DEFAULT '',
  `date`         DATE         DEFAULT NULL,
  `type`         VARCHAR(100) NOT NULL DEFAULT '',
  `message`      TEXT         NOT NULL,
  `is_read`      TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_date` (`date`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabelle: orders (Aufträge / Auftrags-Tracking)
-- ============================================================
CREATE TABLE IF NOT EXISTS `orders` (
  `id`           VARCHAR(50)  NOT NULL,
  `access_code`  VARCHAR(20)  NOT NULL DEFAULT '',
  `client`       VARCHAR(200) NOT NULL DEFAULT '',
  `type`         VARCHAR(100) NOT NULL DEFAULT 'Preiserhebung',
  `location`     VARCHAR(200) NOT NULL DEFAULT '',
  `date`         DATE         DEFAULT NULL,
  `status`       TINYINT UNSIGNED NOT NULL DEFAULT 1
                 COMMENT '1=Neu 2=In Bearbeitung 3=Vor Ort 4=Abgeschlossen 5=Archiviert',
  `progress`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `notes`        TEXT,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_access_code` (`access_code`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabelle: institutes (Institute & Partner)
-- ============================================================
CREATE TABLE IF NOT EXISTS `institutes` (
  `id`          VARCHAR(32)  NOT NULL,
  `name`        VARCHAR(200) NOT NULL DEFAULT '',
  `short`       VARCHAR(10)  NOT NULL DEFAULT '',
  `type`        VARCHAR(100) NOT NULL DEFAULT 'Sonstiges',
  `priority`    VARCHAR(20)  NOT NULL DEFAULT 'normal',
  `color`       VARCHAR(10)  NOT NULL DEFAULT '#6366f1',
  `website`     VARCHAR(300) NOT NULL DEFAULT '',
  `contact`     VARCHAR(200) NOT NULL DEFAULT '',
  `email`       VARCHAR(200) NOT NULL DEFAULT '',
  `phone`       VARCHAR(50)  NOT NULL DEFAULT '',
  `description` TEXT,
  `tags`        JSON         DEFAULT NULL,
  `status`      VARCHAR(20)  NOT NULL DEFAULT 'active',
  `featured`    TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_featured` (`featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabelle: nachweise (Einsatznachweise)
-- ============================================================
CREATE TABLE IF NOT EXISTS `nachweise` (
  `id`         VARCHAR(32)  NOT NULL,
  `name`       VARCHAR(100) NOT NULL DEFAULT '',
  `auftrag`    VARCHAR(100) NOT NULL DEFAULT '',
  `datum`      DATE         DEFAULT NULL,
  `uhrzeit`    VARCHAR(10)  NOT NULL DEFAULT '',
  `filiale`    VARCHAR(200) NOT NULL DEFAULT '',
  `typ`        VARCHAR(100) NOT NULL DEFAULT '',
  `dauer`      VARCHAR(20)  NOT NULL DEFAULT '',
  `institut`   VARCHAR(200) NOT NULL DEFAULT '',
  `notiz`      TEXT,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_datum` (`datum`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabelle: availability (Verfügbarkeitskalender)
-- ============================================================
CREATE TABLE IF NOT EXISTS `availability` (
  `date`       DATE        NOT NULL,
  `status`     VARCHAR(20) NOT NULL DEFAULT 'available'
               COMMENT 'available|booked|partial|none',
  `updated_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabelle: stats (Besucher-Statistiken)
-- ============================================================
CREATE TABLE IF NOT EXISTS `stats` (
  `date`  DATE         NOT NULL,
  `page`  VARCHAR(200) NOT NULL DEFAULT '',
  `views` INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`date`, `page`),
  KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;

-- ============================================================
-- Erfolgsmeldung
-- ============================================================
SELECT 'KPS-IT.de Datenbank erfolgreich eingerichtet!' AS Ergebnis,
       NOW() AS Zeitstempel;
