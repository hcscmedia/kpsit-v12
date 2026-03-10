<?php
/**
 * KPS-IT.de – Sicherer Admin-Login
 * Ersetzt den unsicheren Klartext-Passwort-Vergleich in admin.php
 *
 * SETUP:
 * 1. Einmalig einen Hash erzeugen (z.B. in der PHP-Konsole):
 *    php -r "echo password_hash('DeinPasswort123!', PASSWORD_BCRYPT);"
 * 2. Den erzeugten Hash in ADMIN_PASSWORD_HASH eintragen
 * 3. Diese Datei per require_once am Anfang von admin.php einbinden
 */

// ─────────────────────────────────────────────
// KONFIGURATION – Hash hier eintragen
// ─────────────────────────────────────────────
define('ADMIN_PASSWORD_HASH', '$2y$12$.Gw6K9RT1Me0BxbiarNCJOmR3ZV4lKT9ECNuPaIJ.J2oExBBfT9l6');
define('SESSION_TIMEOUT',     60 * 60);        // 60 Minuten
define('MAX_LOGIN_ATTEMPTS',  5);
define('LOCKOUT_DURATION',    15 * 60);        // 15 Minuten
define('ACTIVITY_LOG_FILE',   __DIR__ . '/data/activity.log');

// ─────────────────────────────────────────────
// SESSION SICHER STARTEN
// ─────────────────────────────────────────────
function kps_session_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => true,   // Nur über HTTPS
            'httponly' => true,   // Kein JS-Zugriff
            'samesite' => 'Strict'
        ]);
        session_start();
    }
}

// ─────────────────────────────────────────────
// BRUTE-FORCE-SCHUTZ
// ─────────────────────────────────────────────
function kps_is_locked_out(): bool {
    $attempts  = $_SESSION['login_attempts']  ?? 0;
    $lockUntil = $_SESSION['lockout_until']   ?? 0;

    if ($lockUntil && time() < $lockUntil) {
        return true;
    }
    // Sperre abgelaufen – zurücksetzen
    if ($lockUntil && time() >= $lockUntil) {
        unset($_SESSION['login_attempts'], $_SESSION['lockout_until']);
    }
    return false;
}

function kps_record_failed_attempt(): void {
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;

    if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $_SESSION['lockout_until'] = time() + LOCKOUT_DURATION;
        kps_log_activity('LOGIN_BLOCKED', 'Zu viele Fehlversuche – IP gesperrt');
    }
}

function kps_reset_attempts(): void {
    unset($_SESSION['login_attempts'], $_SESSION['lockout_until']);
}

// ─────────────────────────────────────────────
// LOGIN-VERARBEITUNG
// ─────────────────────────────────────────────
function kps_process_login(string $password): bool {
    if (kps_is_locked_out()) {
        return false;
    }

    // Timing-sicherer Vergleich via password_verify()
    if (password_verify($password, ADMIN_PASSWORD_HASH)) {
        // Session-Fixation verhindern: neue Session-ID generieren
        session_regenerate_id(true);

        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_login_time'] = time();
        $_SESSION['admin_ip'] = kps_get_client_ip();

        kps_reset_attempts();
        kps_log_activity('LOGIN_SUCCESS', 'Admin erfolgreich angemeldet');
        return true;
    }

    kps_record_failed_attempt();
    kps_log_activity('LOGIN_FAILED', 'Fehlgeschlagener Login-Versuch');
    return false;
}

// ─────────────────────────────────────────────
// SESSION-VALIDIERUNG (bei jedem Admin-Request)
// ─────────────────────────────────────────────
function kps_require_auth(): void {
    kps_session_start();

    $loggedIn  = $_SESSION['admin_logged_in']  ?? false;
    $loginTime = $_SESSION['admin_login_time'] ?? 0;
    $sessionIp = $_SESSION['admin_ip']         ?? '';

    // Nicht eingeloggt
    if (!$loggedIn) {
        kps_redirect_to_login();
    }

    // Session-Timeout prüfen
    if (time() - $loginTime > SESSION_TIMEOUT) {
        kps_logout('session_timeout');
    }

    // IP-Änderung erkennen (Session-Hijacking-Schutz)
    if ($sessionIp && $sessionIp !== kps_get_client_ip()) {
        kps_logout('ip_mismatch');
        kps_log_activity('SECURITY_ALERT', 'IP-Änderung erkannt – Session beendet');
    }

    // Aktivitäts-Timestamp aktualisieren
    $_SESSION['admin_login_time'] = time();
}

// ─────────────────────────────────────────────
// LOGOUT
// ─────────────────────────────────────────────
function kps_logout(string $reason = 'manual'): void {
    kps_log_activity('LOGOUT', 'Abmeldung: ' . $reason);
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
    kps_redirect_to_login();
}

// ─────────────────────────────────────────────
// HILFSFUNKTIONEN
// ─────────────────────────────────────────────
function kps_redirect_to_login(string $msg = ''): void {
    $url = '/admin.php?action=login' . ($msg ? '&msg=' . urlencode($msg) : '');
    header('Location: ' . $url);
    exit;
}

function kps_get_client_ip(): string {
    // Einfache IP-Ermittlung – kein Vertrauen in X-Forwarded-For ohne Proxy-Konfiguration
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function kps_log_activity(string $event, string $detail = ''): void {
    if (!is_dir(dirname(ACTIVITY_LOG_FILE))) return;
    $line = sprintf(
        "[%s] [%s] %s – %s\n",
        date('Y-m-d H:i:s'),
        kps_get_client_ip(),
        $event,
        $detail
    );
    file_put_contents(ACTIVITY_LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}

function kps_lockout_remaining(): int {
    $lockUntil = $_SESSION['lockout_until'] ?? 0;
    return max(0, $lockUntil - time());
}

// ─────────────────────────────────────────────
// CSRF-TOKEN FÜR ADMIN-FORMULARE
// ─────────────────────────────────────────────
function kps_admin_csrf_token(): string {
    if (empty($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf_token'];
}

function kps_verify_admin_csrf(string $token): bool {
    $valid = $_SESSION['admin_csrf_token'] ?? '';
    return hash_equals($valid, $token);
}

// ─────────────────────────────────────────────
// BEISPIEL: LOGIN-FORMULAR HTML-AUSGABE
// ─────────────────────────────────────────────
function kps_render_login_form(string $error = ''): string {
    $token   = kps_admin_csrf_token();
    $locked  = kps_is_locked_out();
    $remaining = $locked ? ceil(kps_lockout_remaining() / 60) : 0;
    $attempts  = $_SESSION['login_attempts'] ?? 0;
    $left      = MAX_LOGIN_ATTEMPTS - $attempts;

    ob_start(); ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPS-IT Admin · Login</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, sans-serif;
            background: #080c14;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(99,102,241,.3);
            border-radius: 12px;
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
            backdrop-filter: blur(10px);
        }
        h1 { font-size: 1.4rem; margin-bottom: 1.5rem; color: #6366f1; }
        label { display: block; font-size: .85rem; margin-bottom: .4rem; color: #94a3b8; }
        input[type="password"] {
            width: 100%; padding: .75rem 1rem;
            background: rgba(255,255,255,.07);
            border: 1px solid rgba(99,102,241,.4);
            border-radius: 8px; color: #fff;
            font-size: 1rem; margin-bottom: 1.2rem;
        }
        button {
            width: 100%; padding: .8rem;
            background: #6366f1; color: #fff;
            border: none; border-radius: 8px;
            font-size: 1rem; cursor: pointer;
            transition: background .2s;
        }
        button:hover { background: #4f46e5; }
        button:disabled { background: #334155; cursor: not-allowed; }
        .error { background: rgba(239,68,68,.15); border: 1px solid rgba(239,68,68,.4);
                 border-radius: 8px; padding: .75rem 1rem; margin-bottom: 1.2rem;
                 color: #fca5a5; font-size: .9rem; }
        .warning { color: #fbbf24; font-size: .8rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
<div class="login-card">
    <h1>🔐 KPS-IT Admin</h1>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($locked): ?>
        <div class="error">
            ⛔ Zu viele Fehlversuche. Bitte warte noch <strong><?= $remaining ?> Minute(n)</strong>.
        </div>
        <button disabled>Gesperrt</button>
    <?php else: ?>
        <?php if ($attempts > 0): ?>
            <p class="warning">⚠️ Noch <?= $left ?> Versuch(e) vor Sperrung.</p>
        <?php endif; ?>
        <form method="POST" action="/admin.php?action=login">
            <input type="hidden" name="csrf_token" value="<?= $token ?>">
            <label for="password">Passwort</label>
            <input type="password" id="password" name="password"
                   autocomplete="current-password" required autofocus>
            <button type="submit">Anmelden</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
    <?php
    return ob_get_clean();
}

/*
──────────────────────────────────────────────────────
INTEGRATION IN admin.php (Anfang der Datei ersetzen):
──────────────────────────────────────────────────────

require_once __DIR__ . '/admin-auth.php';
kps_session_start();

// Login verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'login') {
    if (!kps_verify_admin_csrf($_POST['csrf_token'] ?? '')) {
        die('CSRF-Fehler');
    }
    if (kps_process_login($_POST['password'] ?? '')) {
        header('Location: /admin.php');
        exit;
    }
    echo kps_render_login_form('Falsches Passwort.');
    exit;
}

// Login-Seite anzeigen
if (($_GET['action'] ?? '') === 'login') {
    echo kps_render_login_form();
    exit;
}

// Alle anderen Admin-Seiten schützen
kps_require_auth();

// ... restlicher Admin-Code ...

──────────────────────────────────────────────────────
PASSWORT-HASH ERZEUGEN (einmalig in Terminal):
──────────────────────────────────────────────────────

php -r "echo password_hash('DeinNeuesPasswort!', PASSWORD_BCRYPT, ['cost'=>12]);"

Den ausgegebenen Hash in ADMIN_PASSWORD_HASH oben eintragen.
*/
