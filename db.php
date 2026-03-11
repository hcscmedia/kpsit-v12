<?php
/**
 * KPS-IT.de – Datenbankverbindung
 * Konfiguration: Bitte DB_HOST, DB_NAME, DB_USER, DB_PASS anpassen!
 */

define('DB_HOST', getenv('KPS_DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('KPS_DB_NAME') ?: 'kpsit_db');
define('DB_USER', getenv('KPS_DB_USER') ?: 'kpsit_user');
define('DB_PASS', getenv('KPS_DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Fallback: JSON-Dateien (wenn kein MySQL verfügbar)
if (!defined('USE_DB')) define('USE_DB', filter_var(getenv('KPS_USE_DB') ?: 'false', FILTER_VALIDATE_BOOLEAN));
if (!defined('DATA_DIR')) define('DATA_DIR', __DIR__ . '/data/');

function getDB(): ?PDO {
    if (!USE_DB) return null;
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // Fallback auf JSON wenn DB nicht erreichbar
        return null;
    }
}

// ============================================================
// NACHRICHTEN (Kontaktformular)
// ============================================================
function dbSaveMessage(array $data): bool {
    $db = getDB();
    if ($db) {
        $sql = "INSERT INTO messages (id, name, email, phone, subject, message, ip, created_at)
                VALUES (:id, :name, :email, :phone, :subject, :message, :ip, NOW())";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':id'      => $data['id'],
            ':name'    => $data['name'],
            ':email'   => $data['email'],
            ':phone'   => $data['phone'] ?? '',
            ':subject' => $data['subject'],
            ':message' => $data['message'],
            ':ip'      => $data['ip'] ?? '',
        ]);
    }
    // JSON-Fallback
    return jsonAppend(DATA_DIR . 'messages.json', $data);
}

function dbLoadMessages(): array {
    $db = getDB();
    if ($db) {
        return $db->query("SELECT * FROM messages ORDER BY created_at DESC")->fetchAll();
    }
    return jsonLoad(DATA_DIR . 'messages.json');
}

function dbDeleteMessage(string $id): bool {
    $db = getDB();
    if ($db) {
        $stmt = $db->prepare("DELETE FROM messages WHERE id = ?");
        return $stmt->execute([$id]);
    }
    return jsonDelete(DATA_DIR . 'messages.json', 'id', $id);
}

function dbMarkMessageRead(string $id): bool {
    $db = getDB();
    if ($db) {
        $stmt = $db->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }
    return jsonUpdate(DATA_DIR . 'messages.json', 'id', $id, ['read' => true]);
}

function dbDeleteAllMessages(): bool {
    $db = getDB();
    if ($db) {
        $db->exec("DELETE FROM messages");
        return true;
    }
    jsonSave(DATA_DIR . 'messages.json', []);
    return true;
}

// ============================================================
// AUFTRÄGE (Tracking)
// ============================================================
function dbSaveOrder(array $data): bool {
    $db = getDB();
    if ($db) {
        $sql = "INSERT INTO orders (id, access_code, client, type, location, date, status, progress, notes, created_at, updated_at)
                VALUES (:id, :code, :client, :type, :location, :date, 1, 0, :notes, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                  client=VALUES(client), type=VALUES(type), location=VALUES(location),
                  date=VALUES(date), notes=VALUES(notes), updated_at=NOW()";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':id'       => $data['id'],
            ':code'     => $data['code'],
            ':client'   => $data['client'],
            ':type'     => $data['type'],
            ':location' => $data['location'] ?? '',
            ':date'     => $data['date'] ?? null,
            ':notes'    => $data['notes'] ?? '',
        ]);
    }
    return jsonAppend(DATA_DIR . 'orders.json', $data);
}

function dbLoadOrders(): array {
    $db = getDB();
    if ($db) {
        return $db->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();
    }
    return array_reverse(jsonLoad(DATA_DIR . 'orders.json'));
}

function dbUpdateOrder(string $id, int $status, int $progress, string $notes): bool {
    $db = getDB();
    if ($db) {
        $stmt = $db->prepare("UPDATE orders SET status=?, progress=?, notes=?, updated_at=NOW() WHERE id=?");
        return $stmt->execute([$status, $progress, $notes, $id]);
    }
    return jsonUpdate(DATA_DIR . 'orders.json', 'id', $id, [
        'status' => $status, 'progress' => $progress, 'notes' => $notes
    ]);
}

function dbDeleteOrder(string $id): bool {
    $db = getDB();
    if ($db) {
        $stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
        return $stmt->execute([$id]);
    }
    return jsonDelete(DATA_DIR . 'orders.json', 'id', $id);
}

function dbGetOrder(string $id, string $code): ?array {
    $db = getDB();
    if ($db) {
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND access_code = ?");
        $stmt->execute([$id, $code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
    $orders = jsonLoad(DATA_DIR . 'orders.json');
    foreach ($orders as $o) {
        if (strtoupper($o['id'] ?? '') === strtoupper($id) &&
            ($o['access_code'] ?? $o['code'] ?? '') === $code) {
            return $o;
        }
    }
    return null;
}

// ============================================================
// BUCHUNGSANFRAGEN
// ============================================================
function dbLoadBookings(): array {
    $db = getDB();
    if ($db) {
        return $db->query("SELECT * FROM bookings ORDER BY created_at DESC")->fetchAll();
    }
    return array_reverse(jsonLoad(DATA_DIR . 'bookings.json'));
}

function dbDeleteBooking(string $id): bool {
    $db = getDB();
    if ($db) {
        $stmt = $db->prepare("DELETE FROM bookings WHERE id = ?");
        return $stmt->execute([$id]);
    }
    return jsonDelete(DATA_DIR . 'bookings.json', 'id', $id);
}

// ============================================================
// INSTITUTE
// ============================================================
function dbLoadInstitutes(): array {
    $db = getDB();
    if ($db) {
        $rows = $db->query("SELECT * FROM institutes ORDER BY priority DESC, name ASC")->fetchAll();
        foreach ($rows as &$r) {
            $r['tags'] = $r['tags'] ? json_decode($r['tags'], true) : [];
            $r['featured'] = (bool)$r['featured'];
        }
        return $rows;
    }
    return jsonLoad(DATA_DIR . 'institutes.json');
}

function dbSaveInstitute(array $data): bool {
    $db = getDB();
    if ($db) {
        $tags = is_array($data['tags']) ? json_encode($data['tags']) : ($data['tags'] ?? '[]');
        $sql = "INSERT INTO institutes (id, name, short, type, priority, color, website, contact, email, phone, description, tags, status, featured, created_at, updated_at)
                VALUES (:id, :name, :short, :type, :priority, :color, :website, :contact, :email, :phone, :description, :tags, :status, :featured, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                  name=VALUES(name), short=VALUES(short), type=VALUES(type), priority=VALUES(priority),
                  color=VALUES(color), website=VALUES(website), contact=VALUES(contact),
                  email=VALUES(email), phone=VALUES(phone), description=VALUES(description),
                  tags=VALUES(tags), status=VALUES(status), featured=VALUES(featured), updated_at=NOW()";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':id'          => $data['id'],
            ':name'        => $data['name'],
            ':short'       => $data['short'] ?? substr($data['name'], 0, 3),
            ':type'        => $data['type'] ?? 'Sonstiges',
            ':priority'    => $data['priority'] ?? 'normal',
            ':color'       => $data['color'] ?? '#6366f1',
            ':website'     => $data['website'] ?? '',
            ':contact'     => $data['contact'] ?? '',
            ':email'       => $data['email'] ?? '',
            ':phone'       => $data['phone'] ?? '',
            ':description' => $data['description'] ?? '',
            ':tags'        => $tags,
            ':status'      => $data['status'] ?? 'active',
            ':featured'    => $data['featured'] ? 1 : 0,
        ]);
    }
    return jsonUpsert(DATA_DIR . 'institutes.json', 'id', $data);
}

function dbDeleteInstitute(string $id): bool {
    $db = getDB();
    if ($db) {
        $stmt = $db->prepare("DELETE FROM institutes WHERE id = ?");
        return $stmt->execute([$id]);
    }
    return jsonDelete(DATA_DIR . 'institutes.json', 'id', $id);
}

// ============================================================
// NACHWEISE (Einsatznachweise – serverseitig)
// ============================================================
function dbSaveNachweis(array $data): bool {
    $db = getDB();
    if ($db) {
        $sql = "INSERT INTO nachweise (id, name, auftrag, datum, uhrzeit, filiale, typ, dauer, institut, notiz, created_at)
                VALUES (:id, :name, :auftrag, :datum, :uhrzeit, :filiale, :typ, :dauer, :institut, :notiz, NOW())";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':id'       => $data['id'],
            ':name'     => $data['name'],
            ':auftrag'  => $data['auftrag'] ?? '',
            ':datum'    => $data['datum'] ?? null,
            ':uhrzeit'  => $data['uhrzeit'] ?? '',
            ':filiale'  => $data['filiale'] ?? '',
            ':typ'      => $data['typ'] ?? '',
            ':dauer'    => $data['dauer'] ?? '',
            ':institut' => $data['institut'] ?? '',
            ':notiz'    => $data['notiz'] ?? '',
        ]);
    }
    return jsonAppend(DATA_DIR . 'nachweise.json', $data);
}

function dbLoadNachweise(): array {
    $db = getDB();
    if ($db) {
        return $db->query("SELECT * FROM nachweise ORDER BY created_at DESC")->fetchAll();
    }
    return array_reverse(jsonLoad(DATA_DIR . 'nachweise.json'));
}

function dbDeleteNachweis(string $id): bool {
    $db = getDB();
    if ($db) {
        $stmt = $db->prepare("DELETE FROM nachweise WHERE id = ?");
        return $stmt->execute([$id]);
    }
    return jsonDelete(DATA_DIR . 'nachweise.json', 'id', $id);
}

// ============================================================
// JSON-Hilfsfunktionen (Fallback ohne MySQL)
// ============================================================
function jsonLoad(string $file): array {
    if (!file_exists($file)) return [];
    $raw = @file_get_contents($file);
    return $raw ? (json_decode($raw, true) ?? []) : [];
}

function jsonSave(string $file, array $data): bool {
    $dir = dirname($file);
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    return (bool)@file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function jsonAppend(string $file, array $item): bool {
    $data = jsonLoad($file);
    $data[] = $item;
    return jsonSave($file, $data);
}

function jsonDelete(string $file, string $key, string $value): bool {
    $data = jsonLoad($file);
    $data = array_values(array_filter($data, fn($i) => ($i[$key] ?? '') !== $value));
    return jsonSave($file, $data);
}

function jsonUpdate(string $file, string $key, string $value, array $updates): bool {
    $data = jsonLoad($file);
    foreach ($data as &$item) {
        if (($item[$key] ?? '') === $value) {
            foreach ($updates as $k => $v) $item[$k] = $v;
        }
    }
    return jsonSave($file, $data);
}

function jsonUpsert(string $file, string $key, array $item): bool {
    $data = jsonLoad($file);
    $found = false;
    foreach ($data as &$existing) {
        if (($existing[$key] ?? '') === ($item[$key] ?? '')) {
            $existing = $item;
            $found = true;
            break;
        }
    }
    if (!$found) $data[] = $item;
    return jsonSave($file, $data);
}
