<?php
/**
 * KPS-IT.de Kalender-API
 * GET  ?action=get&month=YYYY-MM   → Verfügbarkeiten für einen Monat
 * POST action=book                 → Buchungsanfrage senden
 * POST action=set (Admin)          → Verfügbarkeit setzen
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: same-origin');

define('CAL_DATA_FILE',    __DIR__ . '/data/calendar.json');
define('BOOKINGS_FILE',    __DIR__ . '/data/bookings.json');
define('RECIPIENT_EMAIL',  'info@kps-it.de');
define('SITE_NAME',        'KPS-IT.de');

// Session für Admin-Prüfung
if (session_status() === PHP_SESSION_NONE) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict']);
}

function jsonOut(bool $ok, $data = null, string $msg = '', int $code = 200): void {
    http_response_code($code);
    echo json_encode(['success' => $ok, 'data' => $data, 'message' => $msg]);
    exit;
}

function loadJson(string $file): array {
    if (!file_exists($file)) return [];
    $raw = @file_get_contents($file);
    return $raw ? (json_decode($raw, true) ?? []) : [];
}

function saveJson(string $file, array $data): void {
    $dir = dirname($file);
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    @file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function sanitize(string $s): string { return trim(strip_tags($s)); }

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

/* ============================================================
   GET: Verfügbarkeiten für einen Monat abrufen
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get') {
    $month = $_GET['month'] ?? date('Y-m');
    // Validierung: YYYY-MM Format
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        jsonOut(false, null, 'Ungültiges Monatsformat', 400);
    }

    $calData = loadJson(CAL_DATA_FILE);
    $monthData = $calData[$month] ?? [];

    // Automatisch Wochenenden als "available" markieren falls nicht gesetzt
    $year  = (int)substr($month, 0, 4);
    $mon   = (int)substr($month, 5, 2);
    $days  = cal_days_in_month(CAL_GREGORIAN, $mon, $year);
    $result = [];

    for ($d = 1; $d <= $days; $d++) {
        $dateStr  = sprintf('%04d-%02d-%02d', $year, $mon, $d);
        $dayOfWeek = (int)date('N', strtotime($dateStr)); // 1=Mo, 7=So
        $isPast   = strtotime($dateStr) < strtotime(date('Y-m-d'));

        if (isset($monthData[$dateStr])) {
            $status = $monthData[$dateStr];
        } elseif ($isPast) {
            $status = 'past';
        } elseif ($dayOfWeek >= 6) {
            $status = 'available'; // Wochenenden standardmäßig frei
        } else {
            $status = 'available'; // Werktage standardmäßig frei
        }

        $result[$dateStr] = [
            'status'    => $status,
            'note'      => $calData['notes'][$dateStr] ?? '',
            'dayOfWeek' => $dayOfWeek,
            'isPast'    => $isPast,
        ];
    }

    jsonOut(true, $result);
}

/* ============================================================
   POST: Buchungsanfrage senden
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'book') {
    $date    = sanitize($_POST['date']    ?? '');
    $name    = sanitize($_POST['name']    ?? '');
    $email   = sanitize($_POST['email']   ?? '');
    $org     = sanitize($_POST['org']     ?? '');
    $type    = sanitize($_POST['type']    ?? '');
    $message = sanitize($_POST['message'] ?? '');

    // Validierung
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
        jsonOut(false, null, 'Ungültiges Datum', 400);
    if (mb_strlen($name) < 2)
        jsonOut(false, null, 'Name zu kurz', 422);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        jsonOut(false, null, 'Ungültige E-Mail', 422);

    // Prüfen ob Datum verfügbar
    $calData = loadJson(CAL_DATA_FILE);
    $month   = substr($date, 0, 7);
    $status  = $calData[$month][$date] ?? 'available';
    if ($status === 'booked') {
        jsonOut(false, null, 'Dieser Tag ist bereits gebucht.', 409);
    }

    // Buchung speichern
    $bookings = loadJson(BOOKINGS_FILE);
    $bookingId = 'BK-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    $bookings[] = [
        'id'        => $bookingId,
        'date'      => $date,
        'name'      => $name,
        'email'     => $email,
        'org'       => $org,
        'type'      => $type,
        'message'   => $message,
        'timestamp' => time(),
        'status'    => 'pending',
    ];
    saveJson(BOOKINGS_FILE, $bookings);

    // E-Mail an Admin
    $subject = '=?UTF-8?B?' . base64_encode('[KPS-IT.de] Neue Buchungsanfrage: ' . $date) . '?=';
    $body    = "Neue Buchungsanfrage\n\n"
             . "Datum:        {$date}\n"
             . "Name:         {$name}\n"
             . "E-Mail:       {$email}\n"
             . "Organisation: {$org}\n"
             . "Typ:          {$type}\n"
             . "Nachricht:    {$message}\n"
             . "Buchungs-ID:  {$bookingId}\n";
    $headers = "From: " . SITE_NAME . " <noreply@kps-it.de>\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n";
    @mail(RECIPIENT_EMAIL, $subject, $body, $headers);

    jsonOut(true, ['booking_id' => $bookingId], 'Buchungsanfrage erfolgreich übermittelt.');
}

/* ============================================================
   POST: Verfügbarkeit setzen (nur Admin)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'set') {
    // Admin-Check
    if (empty($_SESSION['admin_logged_in'])) {
        jsonOut(false, null, 'Nicht autorisiert', 401);
    }

    $date   = sanitize($_POST['date']   ?? '');
    $status = sanitize($_POST['status'] ?? '');
    $note   = sanitize($_POST['note']   ?? '');

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
        jsonOut(false, null, 'Ungültiges Datum', 400);
    if (!in_array($status, ['available', 'booked', 'partial', 'blocked'], true))
        jsonOut(false, null, 'Ungültiger Status', 400);

    $calData = loadJson(CAL_DATA_FILE);
    $month   = substr($date, 0, 7);
    if (!isset($calData[$month])) $calData[$month] = [];
    $calData[$month][$date] = $status;
    if ($note) {
        if (!isset($calData['notes'])) $calData['notes'] = [];
        $calData['notes'][$date] = $note;
    }
    saveJson(CAL_DATA_FILE, $calData);

    jsonOut(true, null, 'Verfügbarkeit gespeichert.');
}

/* ============================================================
   GET: Buchungen abrufen (nur Admin)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'bookings') {
    if (empty($_SESSION['admin_logged_in'])) {
        jsonOut(false, null, 'Nicht autorisiert', 401);
    }
    $bookings = loadJson(BOOKINGS_FILE);
    jsonOut(true, array_reverse($bookings));
}

jsonOut(false, null, 'Unbekannte Aktion', 400);
