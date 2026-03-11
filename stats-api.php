<?php
/**
 * KPS-IT.de – Selbstgehostete Besucherstatistik
 * Datenschutzkonform: Keine Cookies, keine IP-Speicherung (nur Hash)
 * Speicherung: data/stats.json
 */

declare(strict_types=1);

// ── Konfiguration ──────────────────────────────────────────────
define('STATS_FILE', __DIR__ . '/data/stats.json');
define('STATS_MAX_DAYS', 90);   // Daten älter als 90 Tage werden gelöscht
define('STATS_SALT', getenv('KPS_STATS_SALT') ?: 'change-me-in-env'); // Für anonymen Besucher-Hash

// ── CORS & Header ──────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache');

$action = $_GET['action'] ?? $_POST['action'] ?? 'track';

// ── Datei initialisieren ───────────────────────────────────────
if (!file_exists(STATS_FILE)) {
    $empty = ['days' => [], 'pages' => [], 'referrers' => [], 'devices' => [], 'total' => 0];
    file_put_contents(STATS_FILE, json_encode($empty));
}

function loadStats(): array {
    $raw = @file_get_contents(STATS_FILE);
    if (!$raw) return ['days' => [], 'pages' => [], 'referrers' => [], 'devices' => [], 'total' => 0];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : ['days' => [], 'pages' => [], 'referrers' => [], 'devices' => [], 'total' => 0];
}

function saveStats(array $data): void {
    file_put_contents(STATS_FILE, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
}

function pruneOldDays(array &$data): void {
    $cutoff = date('Y-m-d', strtotime('-' . STATS_MAX_DAYS . ' days'));
    foreach (array_keys($data['days']) as $day) {
        if ($day < $cutoff) unset($data['days'][$day]);
    }
}

function detectDevice(): string {
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (preg_match('/mobile|android|iphone|ipad|tablet/i', $ua)) {
        if (preg_match('/tablet|ipad/i', $ua)) return 'tablet';
        return 'mobile';
    }
    return 'desktop';
}

function getAnonymousId(): string {
    // Datenschutzkonform: IP wird gehasht + täglich rotiert, nie gespeichert
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $day = date('Y-m-d');
    return substr(hash('sha256', STATS_SALT . $ip . $day), 0, 16);
}

function sanitizePage(string $page): string {
    $page = preg_replace('/[^a-zA-Z0-9\/_\-\.]/u', '', $page);
    return substr($page, 0, 100) ?: '/';
}

function sanitizeReferrer(string $ref): string {
    if (empty($ref) || $ref === 'direct') return 'Direkt';
    $host = parse_url($ref, PHP_URL_HOST) ?: '';
    $host = preg_replace('/^www\./', '', strtolower($host));
    return substr($host, 0, 60) ?: 'Direkt';
}

// ── TRACKING ──────────────────────────────────────────────────
if ($action === 'track') {
    $page    = sanitizePage($_POST['page'] ?? '/');
    $ref     = sanitizeReferrer($_POST['referrer'] ?? 'direct');
    $device  = detectDevice();
    $day     = date('Y-m-d');
    $hour    = (int) date('G');
    $visId   = getAnonymousId();

    $data = loadStats();
    pruneOldDays($data);

    // Tagesstatistik
    if (!isset($data['days'][$day])) {
        $data['days'][$day] = ['views' => 0, 'visitors' => [], 'hours' => array_fill(0, 24, 0)];
    }
    $data['days'][$day]['views']++;
    $data['days'][$day]['visitors'][$visId] = true;
    $data['days'][$day]['hours'][$hour]++;

    // Seitenstatistik
    $data['pages'][$page] = ($data['pages'][$page] ?? 0) + 1;

    // Referrer
    $data['referrers'][$ref] = ($data['referrers'][$ref] ?? 0) + 1;

    // Gerätetyp
    $data['devices'][$device] = ($data['devices'][$device] ?? 0) + 1;

    // Gesamtzähler
    $data['total'] = ($data['total'] ?? 0) + 1;

    saveStats($data);
    echo json_encode(['success' => true]);
    exit;
}

// ── STATISTIKEN AUSGEBEN (nur für Admin-Session) ───────────────
if ($action === 'get') {
    // Einfacher Session-Schutz: nur wenn Admin eingeloggt
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['admin_logged_in'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
        exit;
    }

    $data = loadStats();

    // Tages-Zusammenfassung aufbereiten
    $days = [];
    foreach ($data['days'] as $day => $info) {
        $days[] = [
            'date'     => $day,
            'views'    => $info['views'],
            'visitors' => count($info['visitors'] ?? []),
            'hours'    => $info['hours'] ?? array_fill(0, 24, 0)
        ];
    }
    usort($days, fn($a, $b) => strcmp($b['date'], $a['date']));

    // Top-Seiten
    arsort($data['pages']);
    $topPages = array_slice($data['pages'], 0, 10, true);

    // Top-Referrer
    arsort($data['referrers']);
    $topRefs = array_slice($data['referrers'], 0, 10, true);

    // Geräte
    $devices = $data['devices'] ?? [];

    // Letzte 30 Tage aggregiert
    $last30 = array_slice($days, 0, 30);
    $totalViews30    = array_sum(array_column($last30, 'views'));
    $totalVisitors30 = array_sum(array_column($last30, 'visitors'));

    // Stunden-Verteilung (letzte 7 Tage)
    $hourly = array_fill(0, 24, 0);
    foreach (array_slice($days, 0, 7) as $d) {
        foreach (($d['hours'] ?? []) as $h => $v) {
            $hourly[$h] = ($hourly[$h] ?? 0) + $v;
        }
    }

    echo json_encode([
        'success'          => true,
        'total'            => $data['total'] ?? 0,
        'views_30d'        => $totalViews30,
        'visitors_30d'     => $totalVisitors30,
        'days'             => $last30,
        'top_pages'        => $topPages,
        'top_referrers'    => $topRefs,
        'devices'          => $devices,
        'hourly_7d'        => $hourly
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unbekannte Aktion']);
