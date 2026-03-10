<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/admin-auth.php';
kps_session_start();
define('DATA_DIR', __DIR__ . '/data/');
require_once __DIR__ . '/db.php';
@mkdir(DATA_DIR, 0750, true);
echo "Basis OK<br>";

// Teste alle Funktionen die admin.php verwendet
echo "loadMessages: " . (function_exists('loadMessages') ? 'existiert' : 'FEHLT') . "<br>";
echo "loadOrders: "   . (function_exists('loadOrders')   ? 'existiert' : 'FEHLT') . "<br>";
echo "loadBookings: " . (function_exists('loadBookings') ? 'existiert' : 'FEHLT') . "<br>";
echo "dbLoadMessages: " . (function_exists('dbLoadMessages') ? 'existiert' : 'FEHLT') . "<br>";

// Teste ob admin.php selbst einen Parse-Fehler hat
echo "<br>Teste admin.php include...<br>";
// Simuliere eingeloggten User
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_login_time'] = time();
$_SESSION['admin_ip'] = $_SERVER['REMOTE_ADDR'];

ob_start();
try {
    include __DIR__ . '/admin.php';
    $out = ob_get_clean();
    echo "admin.php geladen! Ausgabe-Länge: " . strlen($out) . " Zeichen<br>";
} catch (Throwable $e) {
    ob_end_clean();
    echo "<strong>FEHLER in admin.php:</strong><br>";
    echo "Typ: " . get_class($e) . "<br>";
    echo "Meldung: " . $e->getMessage() . "<br>";
    echo "Zeile: " . $e->getLine() . "<br>";
    echo "Datei: " . $e->getFile() . "<br>";
}