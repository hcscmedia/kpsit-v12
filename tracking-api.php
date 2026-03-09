<?php
/**
 * KPS-IT.de – Auftrags-Tracking API v10
 * Nutzt db.php für MySQL/JSON-Fallback
 * GET  ?action=get&id=AUF-...&code=XXXXXXXX → Auftragsstatus abrufen
 * GET  ?action=list (Admin-Session)          → Alle Aufträge
 */

declare(strict_types=1);

if (!defined('USE_DB')) define('USE_DB', false);
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict']);
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-cache, no-store');

function jsonOut(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function sanitize(string $s): string { return trim(strip_tags($s)); }

$action = sanitize($_GET['action'] ?? $_POST['action'] ?? '');

// ── Auftrag abrufen ────────────────────────────────────────────────────────
if ($action === 'get') {
    $id   = strtoupper(sanitize($_GET['id']   ?? ''));
    $code = strtoupper(sanitize($_GET['code'] ?? ''));

    if (!$id || !$code) {
        jsonOut(['success' => false, 'message' => 'Auftrags-ID und Zugangscode erforderlich.'], 400);
    }

    // Direkt aus JSON-Datei laden (kompatibel mit admin.php das 'code' speichert)
    $ordersFile = DATA_DIR . 'orders.json';
    $orders = [];
    if (file_exists($ordersFile)) {
        $raw = @file_get_contents($ordersFile);
        $orders = $raw ? (json_decode($raw, true) ?? []) : [];
    }

    // Auch aus MySQL laden falls verfügbar
    $db = getDB();
    if ($db) {
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND access_code = ?");
        $stmt->execute([$id, $code]);
        $row = $stmt->fetch();
        if ($row) {
            $orders = [$row];
        }
    }

    $found = null;
    foreach ($orders as $o) {
        $oId   = strtoupper($o['id'] ?? '');
        // Unterstütze beide Feldnamen: 'code' (admin.php) und 'access_code' (tracking-api)
        $oCode = strtoupper($o['access_code'] ?? $o['code'] ?? '');
        if ($oId === $id && $oCode === $code) {
            $found = $o;
            break;
        }
    }

    if (!$found) {
        jsonOut(['success' => false, 'message' => 'Auftrag nicht gefunden. Bitte Auftrags-ID und Zugangscode prüfen.'], 404);
    }

    $statusLabels = ['', 'Neu', 'In Bearbeitung', 'Vor Ort / Erhebung', 'Abgeschlossen', 'Archiviert'];
    $st = (int)($found['status'] ?? 1);
    $pr = (int)($found['progress'] ?? 0);

    $steps = [
        ['label' => 'Auftragseingang',      'icon' => '📋', 'done' => $st >= 1, 'active' => $st === 1],
        ['label' => 'In Bearbeitung',       'icon' => '⚙️', 'done' => $st >= 2, 'active' => $st === 2],
        ['label' => 'Vor Ort / Erhebung',   'icon' => '📍', 'done' => $st >= 3, 'active' => $st === 3],
        ['label' => 'Abgeschlossen',        'icon' => '✅', 'done' => $st >= 4, 'active' => $st === 4],
        ['label' => 'Archiviert',           'icon' => '📁', 'done' => $st >= 5, 'active' => $st === 5],
    ];

    jsonOut([
        'success' => true,
        'order'   => [
            'id'           => $found['id'],
            'access_code'  => strtoupper($found['access_code'] ?? $found['code'] ?? ''),
            'client'       => $found['client'] ?? '',
            'type'         => $found['type'] ?? '',
            'location'     => $found['location'] ?? '',
            'date'         => $found['date'] ?? '',
            'status'       => $st,
            'status_label' => $statusLabels[$st] ?? '',
            'progress'     => $pr,
            'notes'        => $found['notes'] ?? '',
            'created_at'   => $found['created_at'] ?? $found['created'] ?? '',
            'updated_at'   => $found['updated_at'] ?? '',
            'steps'        => $steps,
        ],
    ]);
}

// ── Alle Aufträge (Admin) ──────────────────────────────────────────────────
if ($action === 'list') {
    if (empty($_SESSION['admin_logged_in'])) {
        jsonOut(['success' => false, 'message' => 'Nicht autorisiert.'], 401);
    }
    $orders = dbLoadOrders();
    jsonOut(['success' => true, 'orders' => $orders]);
}

jsonOut(['success' => false, 'message' => 'Unbekannte Aktion.'], 400);
