<?php
/**
 * KPS-IT.de – Institute API
 * CRUD-Backend für die Institut-Verwaltung im Admin-Dashboard
 * Speichert Institute als JSON in data/institutes.json
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ─── Auth-Check ───────────────────────────────────────────────
if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht autorisiert']);
    exit;
}

// ─── Datei-Pfad ───────────────────────────────────────────────
$DATA_FILE = __DIR__ . '/data/institutes.json';

// ─── Hilfsfunktionen ─────────────────────────────────────────
function loadInstitutes($file) {
    if (!file_exists($file)) return [];
    $json = file_get_contents($file);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function saveInstitutes($file, $data) {
    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function sanitize($str) {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}

function generateId() {
    return 'inst_' . substr(md5(uniqid(mt_rand(), true)), 0, 8);
}

// ─── Request-Routing ─────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method . ':' . $action) {

    // GET: Alle Institute laden
    case 'GET:list':
        $institutes = loadInstitutes($DATA_FILE);
        echo json_encode(['success' => true, 'data' => array_values($institutes)]);
        break;

    // POST: Neues Institut hinzufügen
    case 'POST:add':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) { parse_str(file_get_contents('php://input'), $input); }

        $name = sanitize($input['name'] ?? '');
        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Name ist erforderlich']);
            exit;
        }

        $institute = [
            'id'          => generateId(),
            'name'        => $name,
            'short'       => sanitize($input['short'] ?? strtoupper(substr($name, 0, 3))),
            'type'        => sanitize($input['type'] ?? 'Marktforschung'),
            'description' => sanitize($input['description'] ?? ''),
            'website'     => filter_var($input['website'] ?? '', FILTER_SANITIZE_URL),
            'tags'        => array_map('trim', explode(',', sanitize($input['tags'] ?? ''))),
            'color'       => preg_match('/^#[0-9a-fA-F]{6}$/', $input['color'] ?? '') ? $input['color'] : '#6366f1',
            'featured'    => !empty($input['featured']),
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ];

        $institutes = loadInstitutes($DATA_FILE);
        $institutes[$institute['id']] = $institute;
        saveInstitutes($DATA_FILE, $institutes);

        echo json_encode(['success' => true, 'data' => $institute, 'message' => 'Institut hinzugefügt']);
        break;

    // POST: Institut bearbeiten
    case 'POST:update':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = sanitize($input['id'] ?? '');

        $institutes = loadInstitutes($DATA_FILE);
        if (!isset($institutes[$id])) {
            http_response_code(404);
            echo json_encode(['error' => 'Institut nicht gefunden']);
            exit;
        }

        $inst = $institutes[$id];
        if (!empty($input['name']))        $inst['name']        = sanitize($input['name']);
        if (!empty($input['short']))       $inst['short']       = sanitize($input['short']);
        if (!empty($input['type']))        $inst['type']        = sanitize($input['type']);
        if (isset($input['description']))  $inst['description'] = sanitize($input['description']);
        if (!empty($input['website']))     $inst['website']     = filter_var($input['website'], FILTER_SANITIZE_URL);
        if (isset($input['tags']))         $inst['tags']        = array_map('trim', explode(',', sanitize($input['tags'])));
        if (!empty($input['color']))       $inst['color']       = preg_match('/^#[0-9a-fA-F]{6}$/', $input['color']) ? $input['color'] : $inst['color'];
        if (isset($input['featured']))     $inst['featured']    = !empty($input['featured']);
        $inst['updated_at'] = date('Y-m-d H:i:s');

        $institutes[$id] = $inst;
        saveInstitutes($DATA_FILE, $institutes);

        echo json_encode(['success' => true, 'data' => $inst, 'message' => 'Institut aktualisiert']);
        break;

    // POST: Institut löschen
    case 'POST:delete':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = sanitize($input['id'] ?? '');

        $institutes = loadInstitutes($DATA_FILE);
        if (!isset($institutes[$id])) {
            http_response_code(404);
            echo json_encode(['error' => 'Institut nicht gefunden']);
            exit;
        }

        $name = $institutes[$id]['name'];
        unset($institutes[$id]);
        saveInstitutes($DATA_FILE, $institutes);

        echo json_encode(['success' => true, 'message' => "\"$name\" wurde gelöscht"]);
        break;

    // POST: Reihenfolge speichern
    case 'POST:reorder':
        $input = json_decode(file_get_contents('php://input'), true);
        $order = $input['order'] ?? [];

        $institutes = loadInstitutes($DATA_FILE);
        $reordered = [];
        foreach ($order as $id) {
            if (isset($institutes[$id])) {
                $reordered[$id] = $institutes[$id];
            }
        }
        // Nicht in der Reihenfolge enthaltene anhängen
        foreach ($institutes as $id => $inst) {
            if (!isset($reordered[$id])) $reordered[$id] = $inst;
        }
        saveInstitutes($DATA_FILE, $reordered);
        echo json_encode(['success' => true, 'message' => 'Reihenfolge gespeichert']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unbekannte Aktion: ' . $action]);
}
