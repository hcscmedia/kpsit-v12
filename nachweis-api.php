<?php
/**
 * KPS-IT.de – Einsatznachweis API v10
 * POST action=save   → Nachweis speichern
 * GET  action=list   → Alle Nachweise (öffentlich, da kein Login auf der Seite)
 * POST action=delete → Nachweis löschen (nur Admin)
 * GET  action=delete_admin&id=... → Nachweis löschen (Admin-Session)
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

// ── Nachweis speichern ─────────────────────────────────────────────────────
if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'id'       => 'EN-' . bin2hex(random_bytes(6)),
        'name'     => sanitize($_POST['name']     ?? ''),
        'auftrag'  => sanitize($_POST['auftrag']  ?? ''),
        'datum'    => sanitize($_POST['datum']    ?? ''),
        'uhrzeit'  => sanitize($_POST['uhrzeit']  ?? ''),
        'filiale'  => sanitize($_POST['filiale']  ?? ''),
        'typ'      => sanitize($_POST['typ']      ?? ''),
        'dauer'    => sanitize($_POST['dauer']    ?? ''),
        'institut' => sanitize($_POST['institut'] ?? ''),
        'notiz'    => sanitize($_POST['notiz']    ?? ''),
        'savedAt'  => date('c'),
    ];

    if (!$data['name'] || !$data['datum'] || !$data['filiale'] || !$data['typ']) {
        jsonOut(['success' => false, 'message' => 'Name, Datum, Filiale und Typ sind Pflichtfelder.'], 422);
    }

    $saved = dbSaveNachweis($data);
    if ($saved) {
        jsonOut(['success' => true, 'id' => $data['id'], 'message' => 'Nachweis gespeichert.']);
    } else {
        jsonOut(['success' => false, 'message' => 'Fehler beim Speichern.'], 500);
    }
}

// ── Alle Nachweise laden ───────────────────────────────────────────────────
if ($action === 'list') {
    $nachweise = dbLoadNachweise();
    jsonOut(['success' => true, 'nachweise' => $nachweise]);
}

// ── Nachweis löschen (nur Admin) ──────────────────────────────────────────
if ($action === 'delete') {
    if (empty($_SESSION['admin_logged_in'])) {
        jsonOut(['success' => false, 'message' => 'Nicht autorisiert. Nur Admins können Nachweise löschen.'], 401);
    }
    $id = sanitize($_GET['id'] ?? $_POST['id'] ?? '');
    if (!$id) jsonOut(['success' => false, 'message' => 'ID erforderlich.'], 400);

    $deleted = dbDeleteNachweis($id);
    jsonOut(['success' => $deleted, 'message' => $deleted ? 'Nachweis gelöscht.' : 'Fehler beim Löschen.']);
}

jsonOut(['success' => false, 'message' => 'Unbekannte Aktion.'], 400);
