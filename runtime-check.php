<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/db.php';

function out(string $label, string $value, string $status = 'INFO'): void {
    $pad = str_pad($status, 5, ' ', STR_PAD_RIGHT);
    echo "[$pad] $label: $value\n";
}

function envStatus(string $key): string {
    $value = getenv($key);
    if ($value === false || $value === '') {
        return 'nicht gesetzt';
    }
    return 'gesetzt';
}

out('PHP', PHP_VERSION);
out('SAPI', PHP_SAPI);
out('USE_DB', (defined('USE_DB') && USE_DB) ? 'true' : 'false');
out('DATA_DIR', defined('DATA_DIR') ? DATA_DIR : 'nicht definiert');

$envKeys = ['KPS_USE_DB', 'KPS_DB_HOST', 'KPS_DB_NAME', 'KPS_DB_USER', 'KPS_DB_PASS', 'KPS_STATS_SALT'];
foreach ($envKeys as $key) {
    $state = envStatus($key);
    $status = ($state === 'gesetzt') ? 'OK' : 'WARN';
    out($key, $state, $status);
}

if (defined('DATA_DIR')) {
    if (is_dir(DATA_DIR)) {
        out('data/ Verzeichnis', 'vorhanden', 'OK');
        out('data/ schreibbar', is_writable(DATA_DIR) ? 'ja' : 'nein', is_writable(DATA_DIR) ? 'OK' : 'WARN');
    } else {
        out('data/ Verzeichnis', 'fehlt', 'WARN');
    }
}

if (!(defined('USE_DB') && USE_DB)) {
    out('DB-Check', 'übersprungen (JSON-Fallback aktiv)', 'INFO');
    exit(0);
}

$pdo = getDB();
if (!$pdo) {
    out('DB-Verbindung', 'fehlgeschlagen (getDB() lieferte null)', 'FAIL');
    exit(2);
}

try {
    $version = (string)$pdo->query('SELECT VERSION()')->fetchColumn();
    $now = (string)$pdo->query('SELECT NOW()')->fetchColumn();
    out('DB-Verbindung', 'erfolgreich', 'OK');
    out('DB-Version', $version, 'OK');
    out('DB-Zeit', $now, 'OK');
    exit(0);
} catch (Throwable $e) {
    out('DB-Query', 'fehlgeschlagen: ' . $e->getMessage(), 'FAIL');
    exit(3);
}
