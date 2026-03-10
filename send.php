<?php
/**
 * KPS-IT.de – Kontaktformular Backend v11
 * Nutzt db.php für MySQL/JSON-Fallback Speicherung
 */

declare(strict_types=1);

// Ohne MySQL: JSON-Fallback nutzen
if (!defined('USE_DB')) define('USE_DB', false);
require_once __DIR__ . '/db.php';

// ─── KONFIGURATION ──────────────────────────────────────────────
define('RECIPIENT_EMAIL',   'info@kps-it.de');
define('RECIPIENT_NAME',    'KPS-IT.de Service');
// Dies ist der Absender der Mail (Muss eine Adresse deiner Domain sein!)
define('SENDER_FROM',       'info@kps-it.de'); 
define('SITE_NAME',         'KPS-IT.de');
define('RATE_LIMIT_MAX',    5);
define('RATE_LIMIT_WINDOW', 600);
define('CSRF_SESSION_KEY',  'kps_csrf_token');

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure'   => isset($_SERVER['HTTPS']),
        'cookie_samesite' => 'Strict',
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['action'])) {
    http_response_code(405); exit;
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function jsonResponse(bool $success, string $message, int $code = 200): void {
    http_response_code($code);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// CSRF-Token generieren
function generateCsrfToken(): string {
    if (empty($_SESSION[CSRF_SESSION_KEY])) {
        $_SESSION[CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_SESSION_KEY];
}

// CSRF-Token ausgeben (GET-Request)
if (isset($_GET['action']) && $_GET['action'] === 'csrf') {
    echo json_encode(['token' => generateCsrfToken()]);
    exit;
}

// CSRF-Validierung (bei POST)
$submittedToken = $_POST['csrf_token'] ?? '';
if (!empty($submittedToken) && isset($_SESSION[CSRF_SESSION_KEY])) {
    if (!hash_equals($_SESSION[CSRF_SESSION_KEY], $submittedToken)) {
        jsonResponse(false, 'Ungültige Sitzung. Bitte laden Sie die Seite neu.', 403);
    }
}

// Honeypot (Bot-Schutz)
if (!empty($_POST['website'])) {
    jsonResponse(true, 'Nachricht wurde erfolgreich übermittelt.');
}

// Rate-Limiting (dateibasiert, threadsicher mit flock)
function checkRateLimit(string $ip): bool {
    $file = DATA_DIR . 'rate_limits.json';
    $dir  = dirname($file);
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    
    $fp = fopen($file, 'c+');
    if (!$fp || !flock($fp, LOCK_EX)) return false; // Datei sperren

    $raw = stream_get_contents($fp);
    $limits = $raw ? (json_decode($raw, true) ?? []) : [];
    $now = time();
    $key = hash('sha256', $ip);

    if (isset($limits[$key])) {
        $limits[$key] = array_values(array_filter($limits[$key], fn(int $t) => ($now - $t) < RATE_LIMIT_WINDOW));
    } else {
        $limits[$key] = [];
    }

    if (count($limits[$key]) >= RATE_LIMIT_MAX) {
        flock($fp, LOCK_UN);
        fclose($fp);
        return false;
    }

    $limits[$key][] = $now;
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($limits));
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

$clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$clientIp = trim(explode(',', $clientIp)[0]);

if (!checkRateLimit($clientIp)) {
    jsonResponse(false, 'Zu viele Anfragen. Bitte warten Sie einige Minuten.', 429);
}

// Input-Validierung
function sanitize(string $input): string { return trim(strip_tags($input)); }

$name    = sanitize($_POST['name']    ?? '');
$email   = sanitize($_POST['email']   ?? '');
$subject = sanitize($_POST['subject'] ?? '');
$message = sanitize($_POST['message'] ?? '');
$dsgvo   = isset($_POST['dsgvo']) && $_POST['dsgvo'] === 'on';

$errors = [];
if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $errors[] = 'Name muss zwischen 2 und 100 Zeichen lang sein.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 200) $errors[] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
$allowedSubjects = ['Verifizierungsanfrage', 'Kooperationsanfrage', 'Rückfrage zur Erhebung', 'Sonstiges'];
if (!in_array($subject, $allowedSubjects, true)) $errors[] = 'Bitte wählen Sie einen gültigen Betreff.';
if (mb_strlen($message) < 10 || mb_strlen($message) > 3000) $errors[] = 'Die Nachricht muss zwischen 10 und 3000 Zeichen lang sein.';
if (!$dsgvo) $errors[] = 'Bitte stimmen Sie der Datenschutzerklärung zu.';

$spamPatterns = ['http://', 'https://', '<a ', 'onclick', 'javascript:', 'viagra', 'casino'];
foreach ($spamPatterns as $p) {
    if (stripos($message, $p) !== false || stripos($name, $p) !== false) {
        jsonResponse(true, 'Nachricht wurde erfolgreich übermittelt.');
    }
}

if (!empty($errors)) jsonResponse(false, implode(' ', $errors), 422);

// Nachricht speichern
$msgId = bin2hex(random_bytes(8));
$saved = dbSaveMessage([
    'id'      => $msgId,
    'name'    => $name,
    'email'   => $email,
    'phone'   => sanitize($_POST['phone'] ?? ''),
    'subject' => $subject,
    'message' => $message,
    'ip'      => $clientIp,
]);

// ─── ADMIN-MAIL VERSENDEN (An dich) ──────────────────────────────────
$emailName    = htmlspecialchars($name,    ENT_QUOTES, 'UTF-8');
$emailEmail   = htmlspecialchars($email,   ENT_QUOTES, 'UTF-8');
$emailSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
$emailMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
$timestamp    = date('d.m.Y H:i:s');

$htmlBody = <<<HTML
<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><style>
body{font-family:Arial,sans-serif;background:#f4f6f9;margin:0;padding:20px}
.wrap{max-width:600px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.header{background:#080c14;padding:24px 32px}.header h1{color:#6366f1;font-size:1.1rem;margin:0}
.header p{color:#94a3b8;font-size:.8rem;margin:4px 0 0}.body{padding:32px}
.field{margin-bottom:20px}.label{font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#6b7280;margin-bottom:4px}
.value{font-size:.95rem;color:#1a2535;background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:10px 14px}
.footer{background:#f9fafb;padding:16px 32px;border-top:1px solid #e5e7eb;font-size:.75rem;color:#9ca3af;text-align:center}
</style></head><body>
<div class="wrap">
  <div class="header"><h1>KPS-IT.de – Neue Kontaktanfrage</h1><p>Eingegangen am {$timestamp}</p></div>
  <div class="body">
    <div class="field"><div class="label">Name</div><div class="value">{$emailName}</div></div>
    <div class="field"><div class="label">E-Mail</div><div class="value">{$emailEmail}</div></div>
    <div class="field"><div class="label">Betreff</div><div class="value">{$emailSubject}</div></div>
    <div class="field"><div class="label">Nachricht</div><div class="value">{$emailMessage}</div></div>
  </div>
  <div class="footer">Automatisch generiert von kps-it.de · Nachrichten-ID: {$msgId}</div>
</div></body></html>
HTML;

$textBody = "Neue Kontaktanfrage – KPS-IT.de\nEingegangen: {$timestamp}\n\nName: {$name}\nE-Mail: {$email}\nBetreff: {$subject}\n\nNachricht:\n{$message}\n";
$boundary = '----=_Part_' . md5(uniqid('', true));

// Header für die Mail an dich
$headers = implode("\r\n", [
    "From: " . SITE_NAME . " <" . SENDER_FROM . ">",
    "Reply-To: {$name} <{$email}>",
    "MIME-Version: 1.0",
    "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
    "X-Mailer: PHP/" . phpversion()
]);

$body = "--{$boundary}\r\n" .
        "Content-Type: text/plain; charset=UTF-8\r\n" .
        "Content-Transfer-Encoding: 8bit\r\n\r\n" .
        "{$textBody}\r\n" .
        "--{$boundary}\r\n" .
        "Content-Type: text/html; charset=UTF-8\r\n" .
        "Content-Transfer-Encoding: 8bit\r\n\r\n" .
        "{$htmlBody}\r\n" .
        "--{$boundary}--";

$mailSubject = '=?UTF-8?B?' . base64_encode('[KPS-IT.de] ' . $subject . ' – ' . $name) . '?=';
$sent = @mail(RECIPIENT_EMAIL, $mailSubject, $body, $headers);

// CSRF-Token erneuern
if (isset($_SESSION[CSRF_SESSION_KEY])) {
    $_SESSION[CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
}

// ─── BESTÄTIGUNGS-MAIL AN KUNDEN (HTML) ─────────────────────────────
if ($sent) {
    // Array-Methode ist zwingend nötig, damit Server die HTML-Header nicht zerschießen
    $confHeaders = implode("\r\n", [
        "From: " . SITE_NAME . " <" . SENDER_FROM . ">",
        "Reply-To: " . SENDER_FROM,
        "MIME-Version: 1.0",
        "Content-Type: text/html; charset=UTF-8"
    ]);
    
    $confSubject = '=?UTF-8?B?' . base64_encode('Vielen Dank für Ihre Anfrage bei KPS-IT.de') . '?=';
    
    $confBody = <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 20px; color: #334155; -webkit-font-smoothing: antialiased;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f8fafc;">
    <tr>
      <td align="center">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="width: 100%; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-top: 20px; margin-bottom: 20px;">
          
          <tr>
            <td style="background-color: #0f172a; padding: 35px 30px; text-align: center; border-bottom: 4px solid #6366f1;">
              <h1 style="color: #ffffff; font-size: 26px; margin: 0; font-weight: 800; letter-spacing: -0.5px;">KPS<span style="color: #6366f1;">-IT.de</span></h1>
            </td>
          </tr>
          
          <tr>
            <td style="padding: 40px 35px;">
              <h2 style="color: #0f172a; font-size: 20px; margin-top: 0; margin-bottom: 15px; font-weight: 700;">Hallo {$name},</h2>
              <p style="font-size: 15px; line-height: 1.6; margin-bottom: 20px;">vielen Dank für Ihre Nachricht! Wir haben Ihre Anfrage erfolgreich erhalten und werden uns schnellstmöglich darum kümmern.</p>
              
              <div style="background-color: #f8fafc; border-left: 4px solid #6366f1; padding: 15px 20px; border-radius: 0 8px 8px 0; margin: 25px 0;">
                <p style="margin: 0 0 10px 0; font-size: 15px; font-weight: 600; color: #0f172a;">Ihre Angaben im Überblick:</p>
                <p style="margin: 5px 0; font-size: 15px; color: #475569;"><strong style="color: #0f172a; font-weight: 600;">Betreff:</strong> {$subject}</p>
                <p style="margin: 5px 0; font-size: 15px; color: #475569;"><strong style="color: #0f172a; font-weight: 600;">Datum:</strong> {$timestamp}</p>
              </div>
              
              <p style="font-size: 15px; line-height: 1.6; margin-bottom: 20px;">Sollten Sie in der Zwischenzeit Fragen haben oder weitere Informationen nachreichen wollen, können Sie einfach direkt auf diese E-Mail antworten.</p>
              
              <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 35px 0 15px;">
                <tr>
                  <td align="center">
                    <a href="https://kps-it.de" style="background-color: #6366f1; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-weight: 600; font-size: 15px; display: inline-block;">Zurück zur Website</a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          
          <tr>
            <td style="background-color: #f1f5f9; padding: 25px 30px; text-align: center; font-size: 13px; color: #64748b; border-top: 1px solid #e2e8f0;">
              Diese E-Mail wurde automatisch generiert.<br><br>
              &copy; 2026 KPS-IT.de &middot; 
              <a href="https://kps-it.de/impressum.html" style="color: #6366f1; text-decoration: none;">Impressum</a> &middot; 
              <a href="https://kps-it.de/datenschutz.html" style="color: #6366f1; text-decoration: none;">Datenschutz</a>
            </td>
          </tr>
          
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

    @mail($email, $confSubject, $confBody, $confHeaders);
    jsonResponse(true, 'Ihre Nachricht wurde erfolgreich übermittelt. Ich melde mich in Kürze.');
} else {
    if ($saved) {
        jsonResponse(true, 'Ihre Nachricht wurde gespeichert. Ich melde mich in Kürze bei Ihnen.');
    } else {
        jsonResponse(false, 'Die Nachricht konnte nicht übermittelt werden. Bitte kontaktieren Sie mich direkt per E-Mail: info@kps-it.de', 500);
    }
}