<?php
/**
 * KPS-IT.de Admin Dashboard v2 – Modern Dark Design
 */
define('ADMIN_PASSWORD', 'Chkk#231088blnKoellner');
define('DATA_DIR', __DIR__ . '/data/');
define('SESSION_TIMEOUT', 3600);
// db.php für MySQL/JSON-Fallback laden (USE_DB=false = nur JSON)
define('USE_DB', false); // Auf true setzen wenn MySQL konfiguriert
require_once __DIR__ . '/db.php';

session_start();

function isLoggedIn(): bool {
    if (!isset($_SESSION['admin_logged_in'])) return false;
    if (time() - ($_SESSION['last_activity'] ?? 0) > SESSION_TIMEOUT) {
        session_destroy(); return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

$lockFile = DATA_DIR . '.login_attempts';
function getAttempts(): array {
    global $lockFile;
    if (!file_exists($lockFile)) return ['count'=>0,'time'=>0];
    return json_decode(file_get_contents($lockFile), true) ?: ['count'=>0,'time'=>0];
}
function saveAttempts(array $a): void { global $lockFile; @file_put_contents($lockFile, json_encode($a)); }

$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $attempts = getAttempts();
    if ($attempts['count'] >= 5 && time() - $attempts['time'] < 900) {
        $loginError = 'Zu viele Fehlversuche. Bitte warten Sie 15 Minuten.';
    } elseif ($_POST['password'] === ADMIN_PASSWORD) {
        saveAttempts(['count'=>0,'time'=>0]);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['last_activity'] = time();
        header('Location: admin.php'); exit;
    } else {
        $attempts['count']++; $attempts['time'] = time(); saveAttempts($attempts);
        $loginError = 'Falsches Passwort. (' . $attempts['count'] . '/5 Versuche)';
    }
}
if (isset($_GET['logout'])) { session_destroy(); header('Location: admin.php'); exit; }

@mkdir(DATA_DIR, 0750, true);

function loadMessages(): array {
    $f = DATA_DIR . 'messages.json';
    return file_exists($f) ? (json_decode(file_get_contents($f), true) ?: []) : [];
}
function saveMessages(array $msgs): void {
    file_put_contents(DATA_DIR . 'messages.json', json_encode($msgs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
function loadBookings(): array {
    $f = DATA_DIR . 'bookings.json';
    return file_exists($f) ? (json_decode(file_get_contents($f), true) ?: []) : [];
}
function loadOrders(): array {
    $f = DATA_DIR . 'orders.json';
    return file_exists($f) ? (json_decode(file_get_contents($f), true) ?: []) : [];
}
function saveOrders(array $orders): void {
    file_put_contents(DATA_DIR . 'orders.json', json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
function loadAvailability(): array {
    $f = DATA_DIR . 'availability.json';
    return file_exists($f) ? (json_decode(file_get_contents($f), true) ?: []) : [];
}
function saveAvailability(array $av): void {
    file_put_contents(DATA_DIR . 'availability.json', json_encode($av, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
function loadStats(): array {
    $f = DATA_DIR . 'stats.json';
    return file_exists($f) ? (json_decode(file_get_contents($f), true) ?: []) : [];
}

if (isLoggedIn()) {
    if (isset($_GET['delete_msg'])) {
        $msgs = loadMessages();
        $msgs = array_values(array_filter($msgs, fn($m) => $m['id'] !== $_GET['delete_msg']));
        saveMessages($msgs);
        header('Location: admin.php?section=messages&deleted=1'); exit;
    }
    if (isset($_GET['read_msg'])) {
        $msgs = loadMessages();
        foreach ($msgs as &$m) { if ($m['id'] === $_GET['read_msg']) $m['read'] = true; }
        saveMessages($msgs);
        header('Location: admin.php?section=messages&id=' . urlencode($_GET['read_msg'])); exit;
    }
    if (isset($_POST['delete_all_messages'])) {
        saveMessages([]);
        header('Location: admin.php?section=messages&deleted=all'); exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_order'])) {
        $orders = loadOrders();
        if ($_POST['action_order'] === 'create') {
            // Eigene ID oder auto-generiert
            $customId = strtoupper(trim($_POST['id'] ?? ''));
            $autoId   = 'AUF-' . date('Y') . '-' . str_pad(count($orders)+1, 4, '0', STR_PAD_LEFT);
            $newId    = $customId ?: $autoId;
            // Zugangscode: aus Formular oder auto-generiert
            $customCode = strtoupper(trim($_POST['code'] ?? ''));
            $autoCode   = strtoupper(substr(md5(uniqid()), 0, 8));
            $newCode    = $customCode ?: $autoCode;
            $orders[] = [
                'id'          => $newId,
                'code'        => $newCode,        // Kompatibilität mit altem Code
                'access_code' => $newCode,        // Für tracking-api.php
                'client'      => htmlspecialchars($_POST['client'] ?? ''),
                'type'        => htmlspecialchars($_POST['type'] ?? ''),
                'location'    => htmlspecialchars($_POST['location'] ?? ''),
                'date'        => htmlspecialchars($_POST['date'] ?? ''),
                'status'      => 1,
                'progress'    => 0,
                'notes'       => htmlspecialchars($_POST['notes'] ?? ''),
                'created'     => date('Y-m-d H:i:s'),
                'created_at'  => date('d.m.Y H:i'),
                'updated_at'  => date('d.m.Y H:i'),
            ];
        } elseif ($_POST['action_order'] === 'update' && isset($_POST['order_id'])) {
            foreach ($orders as &$o) {
                if ($o['id'] === $_POST['order_id']) {
                    $o['status']     = (int)($_POST['status'] ?? $o['status']);
                    $o['progress']   = (int)($_POST['progress'] ?? $o['progress']);
                    $o['notes']      = htmlspecialchars($_POST['notes'] ?? $o['notes']);
                    $o['updated_at'] = date('d.m.Y H:i');
                }
            }
        } elseif ($_POST['action_order'] === 'delete' && isset($_POST['order_id'])) {
            $orders = array_values(array_filter($orders, fn($o) => $o['id'] !== $_POST['order_id']));
        }
        saveOrders($orders);
        header('Location: admin.php?section=orders'); exit;
    }
    // Buchungsanfrage löschen
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_booking']) && $_POST['action_booking'] === 'delete') {
        $bookings = loadBookings();
        $bid = $_POST['booking_id'] ?? '';
        $bookings = array_values(array_filter($bookings, function($b) use ($bid) {
            return ($b['id'] ?? '') !== $bid && (string)array_search($b, $bookings) !== $bid;
        }));
        // Alternativ: nach Index löschen wenn ID numerisch
        if (is_numeric($bid) && isset($bookings[(int)$bid])) {
            array_splice($bookings, (int)$bid, 1);
        }
        $f = DATA_DIR . 'bookings.json';
        if (!is_dir(DATA_DIR)) @mkdir(DATA_DIR, 0750, true);
        @file_put_contents($f, json_encode(array_values($bookings), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        header('Location: admin.php?section=bookings&deleted=1'); exit;
    }
    // Nachweis löschen (Admin)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_nachweis']) && $_POST['action_nachweis'] === 'delete') {
        $nid = trim($_POST['nachweis_id'] ?? '');
        $nf  = DATA_DIR . 'nachweise.json';
        $nachweise = [];
        if (file_exists($nf)) { $raw = @file_get_contents($nf); $nachweise = $raw ? (json_decode($raw, true) ?? []) : []; }
        $nachweise = array_values(array_filter($nachweise, fn($n) => ($n['id'] ?? '') !== $nid));
        if (!is_dir(DATA_DIR)) @mkdir(DATA_DIR, 0750, true);
        @file_put_contents($nf, json_encode($nachweise, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        header('Location: admin.php?section=nachweise&deleted=1'); exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_availability'])) {
        $av = loadAvailability();
        $date = $_POST['av_date'] ?? '';
        $status = $_POST['av_status'] ?? 'available';
        if ($date) { $av[$date] = $status; saveAvailability($av); }
        header('Location: admin.php?section=calendar&saved=1'); exit;
    }
}

$section  = $_GET['section'] ?? 'dashboard';
$msgs     = isLoggedIn() ? loadMessages() : [];
$bookings = isLoggedIn() ? loadBookings() : [];
$orders   = isLoggedIn() ? loadOrders() : [];
$av       = isLoggedIn() ? loadAvailability() : [];
$stats    = isLoggedIn() ? loadStats() : [];

$unread       = count(array_filter($msgs, fn($m) => !($m['read'] ?? false)));
$totalViews   = array_sum(array_column($stats, 'views'));
$todayViews   = $stats[date('Y-m-d')]['views'] ?? 0;
$activeOrders = count(array_filter($orders, fn($o) => ($o['status'] ?? 0) < 5));

$selectedMsg = null;
if ($section === 'messages' && isset($_GET['id'])) {
    foreach ($msgs as $m) { if ($m['id'] === $_GET['id']) { $selectedMsg = $m; break; } }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <title>Admin – KPS-IT.de</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <style>
    :root{--bg:#070b14;--bg2:#0d1220;--bg3:#111827;--bg4:#1a2235;--border:rgba(255,255,255,.07);--border2:rgba(99,102,241,.25);--accent:#6366f1;--accent2:#818cf8;--accent-sub:rgba(99,102,241,.1);--green:#10b981;--green-sub:rgba(16,185,129,.1);--yellow:#f59e0b;--yellow-sub:rgba(245,158,11,.1);--red:#ef4444;--red-sub:rgba(239,68,68,.1);--blue:#0ea5e9;--blue-sub:rgba(14,165,233,.1);--text:#e2e8f0;--text2:#94a3b8;--text3:#64748b;--r:12px;--r2:8px;--r3:6px;--shadow:0 4px 24px rgba(0,0,0,.4);--sidebar:260px;}
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    html{font-size:15px;}
    body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh;line-height:1.5;}
    a{color:inherit;text-decoration:none;}
    button{font-family:inherit;cursor:pointer;}
    input,select,textarea{font-family:inherit;}
    ::-webkit-scrollbar{width:5px;height:5px;}
    ::-webkit-scrollbar-track{background:transparent;}
    ::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:3px;}
    /* LOGIN */
    .login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1.5rem;background:radial-gradient(ellipse 80% 60% at 50% 0%,rgba(99,102,241,.12),transparent);}
    .login-box{width:100%;max-width:400px;background:var(--bg2);border:1px solid var(--border);border-radius:20px;padding:2.5rem;box-shadow:var(--shadow);}
    .login-logo{display:flex;align-items:center;gap:.75rem;margin-bottom:2rem;}
    .login-logo-mark{width:42px;height:42px;background:var(--accent);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
    .login-logo-text .t1{font-size:1rem;font-weight:800;letter-spacing:-.02em;}
    .login-logo-text .t2{font-size:.7rem;color:var(--text3);}
    .login-title{font-size:1.35rem;font-weight:800;margin-bottom:.3rem;letter-spacing:-.02em;}
    .login-sub{font-size:.82rem;color:var(--text2);margin-bottom:1.75rem;}
    .field{margin-bottom:1.1rem;}
    .field label{display:block;font-size:.75rem;font-weight:600;color:var(--text2);margin-bottom:.4rem;letter-spacing:.04em;text-transform:uppercase;}
    .field input{width:100%;padding:.72rem 1rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:var(--r2);color:var(--text);font-size:.9rem;transition:border-color .2s,box-shadow .2s;}
    .field input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(99,102,241,.15);}
    .btn-login{width:100%;padding:.8rem;background:var(--accent);color:white;border:none;border-radius:var(--r2);font-size:.92rem;font-weight:600;transition:all .2s;margin-top:.5rem;}
    .btn-login:hover{background:#4f46e5;transform:translateY(-1px);box-shadow:0 6px 20px rgba(99,102,241,.35);}
    .login-error{background:var(--red-sub);border:1px solid rgba(239,68,68,.25);border-radius:var(--r3);padding:.65rem .9rem;font-size:.82rem;color:#fca5a5;margin-bottom:1rem;}
    /* LAYOUT */
    .admin-wrap{display:flex;min-height:100vh;}
    /* SIDEBAR */
    .sidebar{width:var(--sidebar);background:var(--bg2);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:100;transition:transform .3s;}
    .sidebar-logo{padding:1.4rem 1.25rem 1rem;border-bottom:1px solid var(--border);}
    .sidebar-logo-inner{display:flex;align-items:center;gap:.7rem;}
    .slm{width:36px;height:36px;background:var(--accent);border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
    .slt .t1{font-size:.88rem;font-weight:800;letter-spacing:-.02em;}
    .slt .t2{font-size:.65rem;color:var(--text3);}
    .sidebar-nav{flex:1;overflow-y:auto;padding:.75rem 0;}
    .nav-section{padding:.3rem .9rem .15rem;font-size:.62rem;font-weight:700;color:var(--text3);letter-spacing:.1em;text-transform:uppercase;margin-top:.5rem;}
    .nav-item{display:flex;align-items:center;gap:.7rem;padding:.6rem 1.1rem;margin:.1rem .5rem;border-radius:var(--r2);font-size:.83rem;font-weight:500;color:var(--text2);transition:all .18s;position:relative;}
    .nav-item:hover{background:rgba(255,255,255,.04);color:var(--text);}
    .nav-item.active{background:var(--accent-sub);color:var(--accent2);font-weight:600;}
    .nav-item.active::before{content:'';position:absolute;left:0;top:20%;bottom:20%;width:3px;background:var(--accent);border-radius:0 3px 3px 0;}
    .nav-item svg{flex-shrink:0;opacity:.7;}
    .nav-item.active svg{opacity:1;}
    .nav-badge{margin-left:auto;background:var(--red);color:white;font-size:.6rem;font-weight:700;padding:.15rem .45rem;border-radius:20px;min-width:18px;text-align:center;}
    .sidebar-footer{padding:1rem 1.1rem;border-top:1px solid var(--border);}
    .sidebar-user{display:flex;align-items:center;gap:.65rem;padding:.6rem .8rem;background:rgba(255,255,255,.03);border-radius:var(--r2);margin-bottom:.6rem;}
    .su-avatar{width:32px;height:32px;background:var(--accent);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0;}
    .su-name{font-size:.78rem;font-weight:600;}
    .su-role{font-size:.65rem;color:var(--text3);}
    .btn-logout{display:flex;align-items:center;justify-content:center;gap:.5rem;width:100%;padding:.55rem;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.15);border-radius:var(--r2);color:#fca5a5;font-size:.78rem;font-weight:500;transition:all .2s;}
    .btn-logout:hover{background:rgba(239,68,68,.15);}
    /* MAIN */
    .main-area{margin-left:var(--sidebar);flex:1;display:flex;flex-direction:column;min-height:100vh;}
    .topbar{background:var(--bg2);border-bottom:1px solid var(--border);padding:.9rem 1.75rem;display:flex;align-items:center;gap:1rem;position:sticky;top:0;z-index:50;}
    .topbar-title{font-size:1rem;font-weight:700;flex:1;}
    .topbar-sub{font-size:.75rem;color:var(--text3);}
    .topbar-actions{display:flex;align-items:center;gap:.6rem;}
    .btn-sm{display:inline-flex;align-items:center;gap:.35rem;padding:.42rem .85rem;border-radius:var(--r3);font-size:.75rem;font-weight:500;transition:all .18s;border:1px solid var(--border);background:rgba(255,255,255,.03);color:var(--text2);}
    .btn-sm:hover{border-color:var(--accent);color:var(--accent);}
    .btn-sm.primary{background:var(--accent);border-color:var(--accent);color:white;}
    .btn-sm.primary:hover{background:#4f46e5;}
    .btn-sm.danger{background:var(--red-sub);border-color:rgba(239,68,68,.2);color:#fca5a5;}
    .content{padding:1.75rem;flex:1;}
    /* KPI */
    .kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.75rem;}
    @media(max-width:900px){.kpi-grid{grid-template-columns:repeat(2,1fr);}}
    .kpi{background:var(--bg2);border:1px solid var(--border);border-radius:var(--r);padding:1.25rem;position:relative;overflow:hidden;transition:border-color .2s;}
    .kpi:hover{border-color:var(--border2);}
    .kpi-icon{width:40px;height:40px;border-radius:var(--r2);display:flex;align-items:center;justify-content:center;margin-bottom:.9rem;}
    .kpi-icon.blue{background:var(--blue-sub);}
    .kpi-icon.green{background:var(--green-sub);}
    .kpi-icon.yellow{background:var(--yellow-sub);}
    .kpi-icon.purple{background:var(--accent-sub);}
    .kpi-val{font-size:1.75rem;font-weight:800;letter-spacing:-.04em;line-height:1;}
    .kpi-label{font-size:.72rem;color:var(--text3);margin-top:.3rem;}
    .kpi-trend{position:absolute;top:1rem;right:1rem;font-size:.68rem;font-weight:600;padding:.2rem .5rem;border-radius:20px;}
    .kpi-trend.up{background:var(--green-sub);color:var(--green);}
    /* CARD */
    .card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--r);overflow:hidden;}
    .card-head{padding:1rem 1.25rem;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:.75rem;}
    .card-head-title{font-size:.88rem;font-weight:700;flex:1;}
    .card-head-sub{font-size:.72rem;color:var(--text3);}
    .card-body{padding:1.25rem;}
    /* TABLE */
    .tbl{width:100%;border-collapse:collapse;}
    .tbl th{font-size:.68rem;font-weight:600;color:var(--text3);text-transform:uppercase;letter-spacing:.07em;padding:.6rem .9rem;text-align:left;border-bottom:1px solid var(--border);background:rgba(255,255,255,.02);}
    .tbl td{padding:.7rem .9rem;font-size:.82rem;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:middle;}
    .tbl tr:last-child td{border-bottom:none;}
    .tbl tr:hover td{background:rgba(255,255,255,.02);}
    /* BADGE */
    .badge{display:inline-flex;align-items:center;gap:.3rem;font-size:.67rem;font-weight:600;padding:.22rem .6rem;border-radius:20px;border:1px solid;}
    .badge.unread{background:var(--blue-sub);border-color:rgba(14,165,233,.25);color:var(--blue);}
    .badge.read{background:rgba(255,255,255,.04);border-color:var(--border);color:var(--text3);}
    .badge.s1{background:var(--yellow-sub);border-color:rgba(245,158,11,.25);color:var(--yellow);}
    .badge.s2{background:var(--blue-sub);border-color:rgba(14,165,233,.25);color:var(--blue);}
    .badge.s3{background:var(--accent-sub);border-color:var(--border2);color:var(--accent2);}
    .badge.s4{background:var(--green-sub);border-color:rgba(16,185,129,.25);color:var(--green);}
    .badge.s5{background:rgba(255,255,255,.05);border-color:var(--border);color:var(--text3);}
    /* MSG DETAIL */
    .msg-detail{background:var(--bg3);border:1px solid var(--border);border-radius:var(--r);padding:1.5rem;}
    .msg-meta{display:flex;flex-wrap:wrap;gap:.5rem 1.5rem;margin-bottom:1.25rem;padding-bottom:1.25rem;border-bottom:1px solid var(--border);}
    .msg-meta-item{font-size:.78rem;color:var(--text2);}
    .msg-meta-item strong{color:var(--text);font-weight:600;}
    .msg-body{font-size:.88rem;line-height:1.75;color:var(--text2);white-space:pre-wrap;}
    /* FORMS */
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;}
    @media(max-width:600px){.form-grid{grid-template-columns:1fr;}}
    .form-field{display:flex;flex-direction:column;gap:.35rem;}
    .form-field label{font-size:.72rem;font-weight:600;color:var(--text2);text-transform:uppercase;letter-spacing:.05em;}
    .form-field input,.form-field select,.form-field textarea{padding:.62rem .85rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:var(--r3);color:var(--text);font-size:.85rem;transition:border-color .2s;}
    .form-field input:focus,.form-field select:focus,.form-field textarea:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(99,102,241,.12);}
    .form-field select option{background:var(--bg3);}
    .form-field textarea{resize:vertical;min-height:80px;}
    .form-actions{display:flex;gap:.6rem;margin-top:1.25rem;flex-wrap:wrap;}
    /* PROGRESS */
    .progress-bar{height:6px;background:rgba(255,255,255,.07);border-radius:3px;overflow:hidden;margin-top:.35rem;}
    .progress-fill{height:100%;background:linear-gradient(90deg,var(--accent),var(--accent2));border-radius:3px;}
    /* CALENDAR */
    .cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:.35rem;margin-top:1rem;}
    .cal-head{font-size:.65rem;font-weight:700;color:var(--text3);text-align:center;padding:.3rem 0;text-transform:uppercase;}
    .cal-day{aspect-ratio:1;border-radius:var(--r3);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:500;cursor:pointer;border:1px solid transparent;transition:all .15s;}
    .cal-day.empty{cursor:default;}
    .cal-day.today{border-color:var(--accent);color:var(--accent2);font-weight:700;}
    .cal-day.available{background:var(--green-sub);color:var(--green);}
    .cal-day.booked{background:var(--red-sub);color:var(--red);}
    .cal-day.partial{background:var(--yellow-sub);color:var(--yellow);}
    .cal-day:not(.empty):hover{border-color:var(--accent);transform:scale(1.05);}
    /* CHARTS */
    .chart-bars{display:flex;align-items:flex-end;gap:3px;height:80px;margin-top:.75rem;}
    .chart-bar{flex:1;background:var(--accent-sub);border-radius:3px 3px 0 0;min-height:4px;transition:background .2s;position:relative;}
    .chart-bar:hover{background:var(--accent);}
    .chart-bar:hover::after{content:attr(data-val);position:absolute;bottom:100%;left:50%;transform:translateX(-50%);font-size:.6rem;color:var(--text);background:var(--bg3);padding:.15rem .4rem;border-radius:4px;white-space:nowrap;margin-bottom:3px;}
    /* EMPTY */
    .empty-state{text-align:center;padding:3rem 1rem;color:var(--text3);}
    .empty-state svg{display:block;margin:0 auto 1rem;opacity:.3;}
    .empty-state p{font-size:.85rem;}
    /* ALERTS */
    .alert{padding:.7rem 1rem;border-radius:var(--r3);font-size:.8rem;margin-bottom:1rem;border:1px solid;}
    .alert.success{background:var(--green-sub);border-color:rgba(16,185,129,.25);color:#6ee7b7;}
    /* MOBILE */
    .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:99;}
    .menu-toggle{display:none;background:none;border:none;color:var(--text);padding:.4rem;}
    @media(max-width:768px){.sidebar{transform:translateX(-100%);}.sidebar.open{transform:translateX(0);}.sidebar-overlay.show{display:block;}.main-area{margin-left:0;}.menu-toggle{display:flex;}.kpi-grid{grid-template-columns:repeat(2,1fr);}.tbl{display:block;overflow-x:auto;}}
    /* LAYOUT HELPERS */
    .two-col{display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;}
    @media(max-width:900px){.two-col{grid-template-columns:1fr;}}
    /* ACTIVITY */
    .activity-list{display:flex;flex-direction:column;gap:.5rem;}
    .activity-item{display:flex;align-items:flex-start;gap:.75rem;padding:.65rem;border-radius:var(--r3);background:rgba(255,255,255,.02);border:1px solid var(--border);}
    .activity-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;margin-top:.35rem;}
    .activity-dot.blue{background:var(--blue);}
    .activity-dot.green{background:var(--green);}
    .activity-dot.yellow{background:var(--yellow);}
    .activity-dot.purple{background:var(--accent);}
    .activity-text{font-size:.8rem;flex:1;}
    .activity-time{font-size:.7rem;color:var(--text3);flex-shrink:0;}
    /* QUICK ACTIONS */
    .qa-grid{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;}
    .qa{display:flex;align-items:center;gap:.75rem;padding:.9rem;background:rgba(255,255,255,.02);border:1px solid var(--border);border-radius:var(--r2);transition:all .2s;}
    .qa:hover{border-color:var(--border2);background:var(--accent-sub);}
    .qa-icon{width:36px;height:36px;border-radius:var(--r3);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
    .qa-text{font-size:.8rem;font-weight:600;}
    .qa-sub{font-size:.68rem;color:var(--text3);}
  </style>
</head>
<body>
<?php if (!isLoggedIn()): ?>
<div class="login-wrap">
  <div class="login-box">
    <div class="login-logo">
      <div class="login-logo-mark"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg></div>
      <div class="login-logo-text"><div class="t1">KPS-IT.de</div><div class="t2">Admin Dashboard</div></div>
    </div>
    <h1 class="login-title">Anmelden</h1>
    <p class="login-sub">Bitte geben Sie Ihr Administratorpasswort ein.</p>
    <?php if ($loginError): ?><div class="login-error"><?= htmlspecialchars($loginError) ?></div><?php endif; ?>
    <form method="POST" autocomplete="off">
      <div class="field"><label for="pw">Passwort</label><input type="password" id="pw" name="password" autofocus placeholder="••••••••••" required /></div>
      <button type="submit" class="btn-login">Einloggen</button>
    </form>
    <p style="margin-top:1.5rem;font-size:.72rem;color:var(--text3);text-align:center;"><a href="index.html" style="color:var(--text3);">← Zurück zur Website</a></p>
  </div>
</div>
<?php else: ?>
<div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>
<div class="admin-wrap">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <div class="sidebar-logo-inner">
        <div class="slm"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg></div>
        <div class="slt"><div class="t1">KPS-IT.de</div><div class="t2">Admin Dashboard</div></div>
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section">Übersicht</div>
      <a href="admin.php?section=dashboard" class="nav-item <?= $section==='dashboard'?'active':'' ?>"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard</a>
      <div class="nav-section">Kommunikation</div>
      <a href="admin.php?section=messages" class="nav-item <?= $section==='messages'?'active':'' ?>"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>Nachrichten<?php if($unread>0): ?><span class="nav-badge"><?= $unread ?></span><?php endif;?></a>
      <div class="nav-section">Verwaltung</div>
      <a href="admin.php?section=calendar" class="nav-item <?= $section==='calendar'?'active':'' ?>"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Kalender</a>
      <a href="admin.php?section=bookings" class="nav-item <?= $section==='bookings'?'active':'' ?>"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>Buchungsanfragen<?php $nb=count(array_filter($bookings,fn($b)=>!($b['read']??false)));if($nb>0):?><span class="nav-badge"><?=$nb?></span><?php endif;?></a>
      <a href="admin.php?section=orders" class="nav-item <?= $section==='orders'?'active':'' ?>"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>Aufträge<?php if($activeOrders>0):?><span class="nav-badge" style="background:var(--accent);"><?=$activeOrders?></span><?php endif;?></a>
      <a href="admin.php?section=institutes" class="nav-item <?= $section==='institutes'?'active':'' ?>"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>Institute &amp; Partner</a>
      <a href="admin.php?section=nachweise" class="nav-item <?= $section==='nachweise'?'active':'' ?>"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="15" y2="17"/></svg>Einsatznachweise</a>
      <div class="nav-section">Analyse</div>
      <a href="admin.php?section=stats" class="nav-item <?= $section==='stats'?'active':'' ?>"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>Statistiken</a>
      <div class="nav-section">Website</div>
      <a href="index.html" target="_blank" class="nav-item"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>Website ansehen</a>
      <a href="einsatznachweis.html" target="_blank" class="nav-item"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Einsatznachweis</a>
    </nav>
    <div class="sidebar-footer">
      <div class="sidebar-user"><div class="su-avatar">A</div><div><div class="su-name">Administrator</div><div class="su-role">KPS-IT.de</div></div></div>
      <a href="admin.php?logout=1" class="btn-logout"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Abmelden</a>
    </div>
  </aside>
  <div class="main-area">
    <header class="topbar">
      <button class="menu-toggle" onclick="openSidebar()" aria-label="Menü"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <div><div class="topbar-title"><?php $t=['dashboard'=>'Dashboard','messages'=>'Nachrichten','calendar'=>'Kalender','bookings'=>'Buchungsanfragen','orders'=>'Aufträge','nachweise'=>'Einsatznachweise','stats'=>'Statistiken','institutes'=>'Institute & Partner'];echo htmlspecialchars($t[$section]??'Dashboard');?></div><div class="topbar-sub"><?=date('d. F Y')?></div></div>
      <div class="topbar-actions">
        <?php if($section==='messages'&&count($msgs)>0):?><form method="POST" onsubmit="return confirm('Alle Nachrichten löschen?');" style="display:inline;"><button type="submit" name="delete_all_messages" value="1" class="btn-sm danger">Alle löschen</button></form><?php endif;?>
        <?php if($section==='orders'):?><button class="btn-sm primary" onclick="document.getElementById('nof').style.display=document.getElementById('nof').style.display==='none'?'block':'none'">+ Neuer Auftrag</button><?php endif;?>
      </div>
    </header>
    <main class="content">
      <?php if(isset($_GET['deleted'])):?><div class="alert success">Erfolgreich gelöscht.</div><?php endif;?>
      <?php if(isset($_GET['saved'])):?><div class="alert success">Gespeichert.</div><?php endif;?>
      <?php
      // DASHBOARD
      if($section==='dashboard'):?>
      <div class="kpi-grid">
        <div class="kpi"><div class="kpi-icon blue"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div><div class="kpi-val"><?=count($msgs)?></div><div class="kpi-label">Nachrichten</div><?php if($unread>0):?><div class="kpi-trend up"><?=$unread?> neu</div><?php endif;?></div>
        <div class="kpi"><div class="kpi-icon green"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div><div class="kpi-val"><?=count($bookings)?></div><div class="kpi-label">Buchungsanfragen</div></div>
        <div class="kpi"><div class="kpi-icon yellow"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--yellow)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><div class="kpi-val"><?=$activeOrders?></div><div class="kpi-label">Aktive Aufträge</div></div>
        <div class="kpi"><div class="kpi-icon purple"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent2)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></div><div class="kpi-val"><?=$totalViews?></div><div class="kpi-label">Seitenaufrufe</div><?php if($todayViews>0):?><div class="kpi-trend up">+<?=$todayViews?> heute</div><?php endif;?></div>
      </div>
      <div class="two-col">
        <div class="card"><div class="card-head"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent2)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg><span class="card-head-title">Letzte Aktivitäten</span></div><div class="card-body"><div class="activity-list"><?php
        $acts=[];
        foreach(array_slice(array_reverse($msgs),0,3) as $m){$acts[]=['dot'=>'blue','text'=>'Nachricht von '.htmlspecialchars($m['name']??''),'time'=>date('d.m H:i',strtotime($m['created_at']??'now'))];}
        foreach(array_slice(array_reverse($bookings),0,2) as $b){$acts[]=['dot'=>'green','text'=>'Buchung für '.htmlspecialchars($b['date']??''),'time'=>date('d.m H:i',strtotime($b['created_at']??'now'))];}
        if(empty($acts)){echo '<div class="empty-state"><p>Noch keine Aktivitäten.</p></div>';}
        else{foreach($acts as $a):?><div class="activity-item"><div class="activity-dot <?=$a['dot']?>"></div><div class="activity-text"><?=$a['text']?></div><div class="activity-time"><?=$a['time']?></div></div><?php endforeach;}?></div></div></div>
        <div class="card"><div class="card-head"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent2)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg><span class="card-head-title">Schnellzugriff</span></div><div class="card-body"><div class="qa-grid">
          <a href="admin.php?section=messages" class="qa"><div class="qa-icon" style="background:var(--blue-sub);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div><div><div class="qa-text">Nachrichten</div><div class="qa-sub"><?=$unread?> ungelesen</div></div></a>
          <a href="admin.php?section=orders" class="qa"><div class="qa-icon" style="background:var(--yellow-sub);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--yellow)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><div><div class="qa-text">Aufträge</div><div class="qa-sub"><?=$activeOrders?> aktiv</div></div></a>
          <a href="admin.php?section=calendar" class="qa"><div class="qa-icon" style="background:var(--green-sub);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div><div><div class="qa-text">Kalender</div><div class="qa-sub">Verfügbarkeit</div></div></a>
          <a href="admin.php?section=stats" class="qa"><div class="qa-icon" style="background:var(--accent-sub);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent2)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></div><div><div class="qa-text">Statistiken</div><div class="qa-sub"><?=$totalViews?> Aufrufe</div></div></a>
        </div></div></div>
      </div>
      <?php
      // MESSAGES
      elseif($section==='messages'):
      if($selectedMsg):?>
      <div style="margin-bottom:1rem;"><a href="admin.php?section=messages" class="btn-sm">← Zurück</a></div>
      <div class="msg-detail">
        <div class="msg-meta">
          <div class="msg-meta-item"><strong>Von:</strong> <?=htmlspecialchars($selectedMsg['name']??'')?></div>
          <div class="msg-meta-item"><strong>E-Mail:</strong> <?=htmlspecialchars($selectedMsg['email']??'')?></div>
          <?php if(!empty($selectedMsg['phone'])):?><div class="msg-meta-item"><strong>Tel:</strong> <?=htmlspecialchars($selectedMsg['phone'])?></div><?php endif;?>
          <div class="msg-meta-item"><strong>Datum:</strong> <?=date('d.m.Y H:i',strtotime($selectedMsg['created_at']??'now'))?></div>
        </div>
        <div class="msg-body"><?=htmlspecialchars($selectedMsg['message']??'')?></div>
        <div style="margin-top:1.25rem;display:flex;gap:.6rem;flex-wrap:wrap;">
          <a href="mailto:<?=htmlspecialchars($selectedMsg['email']??'')?>" class="btn-sm primary">Antworten</a>
          <a href="admin.php?delete_msg=<?=urlencode($selectedMsg['id']??'')?>" class="btn-sm danger" onclick="return confirm('Löschen?')">Löschen</a>
        </div>
      </div>
      <?php else:?>
      <?php if(empty($msgs)):?><div class="card"><div class="card-body"><div class="empty-state"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg><p>Noch keine Nachrichten.</p></div></div></div>
      <?php else:?><div class="card"><div class="card-head"><span class="card-head-title">Nachrichten</span><span class="card-head-sub"><?=count($msgs)?> gesamt · <?=$unread?> ungelesen</span></div><table class="tbl"><thead><tr><th>Von</th><th>E-Mail</th><th>Nachricht</th><th>Datum</th><th>Status</th><th>Aktionen</th></tr></thead><tbody>
      <?php foreach(array_reverse($msgs) as $m):$r=$m['read']??false;?><tr><td style="font-weight:<?=$r?'400':'600'?>"><?=htmlspecialchars($m['name']??'')?></td><td><?=htmlspecialchars($m['email']??'')?></td><td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?=htmlspecialchars(substr($m['message']??'',0,50))?>…</td><td><?=date('d.m.Y',strtotime($m['created_at']??'now'))?></td><td><span class="badge <?=$r?'read':'unread'?>"><?=$r?'Gelesen':'Neu'?></span></td><td><a href="admin.php?section=messages&read_msg=<?=urlencode($m['id']??'')?>" class="btn-sm">Lesen</a> <a href="admin.php?delete_msg=<?=urlencode($m['id']??'')?>" class="btn-sm danger" onclick="return confirm('Löschen?')" style="margin-left:.3rem">Del</a></td></tr><?php endforeach;?></tbody></table></div><?php endif;endif;
      // CALENDAR
      elseif($section==='calendar'):
      $month=(int)($_GET['month']??date('n'));$year=(int)($_GET['year']??date('Y'));
      if($month<1){$month=12;$year--;}if($month>12){$month=1;$year++;}
      $fd=mktime(0,0,0,$month,1,$year);$dim=date('t',$fd);$sdow=(int)date('N',$fd);?>
      <div class="two-col">
        <div class="card">
          <div class="card-head"><a href="admin.php?section=calendar&month=<?=$month-1?>&year=<?=$year?>" class="btn-sm" style="padding:.3rem .6rem;">‹</a><span class="card-head-title" style="text-align:center;"><?=date('F Y',$fd)?></span><a href="admin.php?section=calendar&month=<?=$month+1?>&year=<?=$year?>" class="btn-sm" style="padding:.3rem .6rem;">›</a></div>
          <div class="card-body">
            <div class="cal-grid">
              <?php foreach(['Mo','Di','Mi','Do','Fr','Sa','So'] as $d):?><div class="cal-head"><?=$d?></div><?php endforeach;?>
              <?php for($i=1;$i<$sdow;$i++):?><div class="cal-day empty"></div><?php endfor;?>
              <?php for($d=1;$d<=$dim;$d++):$k=sprintf('%04d-%02d-%02d',$year,$month,$d);$st=$av[$k]??'none';$cl=$st!=='none'?$st:'';$td=$k===date('Y-m-d')?'today':'';?><div class="cal-day <?=$cl?> <?=$td?>" onclick="setAvDate('<?=$k?>')" title="<?=$k?>"><?=$d?></div><?php endfor;?>
            </div>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1rem;font-size:.72rem;"><span style="color:var(--green);">■ Verfügbar</span><span style="color:var(--red);">■ Gebucht</span><span style="color:var(--yellow);">■ Teilweise</span><span style="color:var(--accent2);">■ Heute</span></div>
          </div>
        </div>
        <div class="card"><div class="card-head"><span class="card-head-title">Verfügbarkeit setzen</span></div><div class="card-body"><form method="POST"><div class="form-field" style="margin-bottom:.85rem;"><label>Datum</label><input type="date" name="av_date" id="av-date-input" value="<?=date('Y-m-d')?>" required /></div><div class="form-field" style="margin-bottom:1.1rem;"><label>Status</label><select name="av_status"><option value="available">Verfügbar</option><option value="booked">Gebucht</option><option value="partial">Teilweise</option><option value="none">Löschen</option></select></div><button type="submit" name="save_availability" value="1" class="btn-sm primary">Speichern</button></form></div></div>
      </div>
      <?php
      // BOOKINGS
      elseif($section==='bookings'):?>
      <?php if(empty($bookings)):?><div class="card"><div class="card-body"><div class="empty-state"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg><p>Noch keine Buchungsanfragen.</p></div></div></div>
      <?php else:?>
      <div style="display:flex;flex-direction:column;gap:1rem;">
      <?php foreach(array_reverse($bookings) as $bidx=>$b):
        $bid=$b['id']??$bidx;
      ?>
      <div class="card">
        <div class="card-head">
          <div style="flex:1;">
            <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;">
              <strong><?=htmlspecialchars($b['name']??'')?></strong>
              <?php if(!empty($b['organization'])):?><span style="font-size:.78rem;color:var(--text3);"><?=htmlspecialchars($b['organization'])?></span><?php endif;?>
              <?php if(!($b['read']??false)):?><span class="badge s1" style="font-size:.65rem;">Neu</span><?php endif;?>
            </div>
            <div style="font-size:.78rem;color:var(--text3);margin-top:.2rem;"><?=htmlspecialchars($b['type']??'')?> &middot; Gewuenschtes Datum: <?=htmlspecialchars($b['date']??'–')?> &middot; Eingegangen: <?=date('d.m.Y H:i',strtotime($b['created_at']??'now'))?></div>
          </div>
          <form method="POST" onsubmit="return confirm('Buchungsanfrage loeschen?');" style="margin-left:1rem;">
            <input type="hidden" name="action_booking" value="delete" />
            <input type="hidden" name="booking_id" value="<?=htmlspecialchars((string)$bid)?>" />
            <button type="submit" class="btn-sm danger">&#128465; Loeschen</button>
          </form>
        </div>
        <?php if(!empty($b['message'])||!empty($b['notes'])||!empty($b['email'])||!empty($b['phone'])):?>
        <div class="card-body">
          <?php if(!empty($b['email'])||!empty($b['phone'])):?>
          <div style="display:flex;gap:1.5rem;margin-bottom:.75rem;font-size:.8rem;">
            <?php if(!empty($b['email'])):?><span>&#9993; <a href="mailto:<?=htmlspecialchars($b['email'])?>" style="color:var(--accent);"><?=htmlspecialchars($b['email'])?></a></span><?php endif;?>
            <?php if(!empty($b['phone'])):?><span>&#128222; <?=htmlspecialchars($b['phone'])?></span><?php endif;?>
          </div>
          <?php endif;?>
          <?php if(!empty($b['message'])||!empty($b['notes'])):?>
          <div style="background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:var(--r3);padding:.75rem 1rem;font-size:.82rem;color:var(--text2);white-space:pre-wrap;"><?=nl2br(htmlspecialchars($b['message']??$b['notes']??''))?></div>
          <?php endif;?>
        </div>
        <?php endif;?>
      </div>
      <?php endforeach;?>
      </div>
      <?php endif;
      // ORDERS
      elseif($section==='orders'):$sl=['','Neu','In Bearbeitung','Vor Ort','Abgeschlossen','Archiviert'];?>
      <div id="nof" style="display:none;margin-bottom:1.25rem;"><div class="card"><div class="card-head"><span class="card-head-title">Neuen Auftrag anlegen</span></div><div class="card-body"><form method="POST"><input type="hidden" name="action_order" value="create" /><div class="form-grid"><div class="form-field"><label>Auftraggeber *</label><input type="text" name="client" required placeholder="z.B. Rewe Berlin" /></div><div class="form-field"><label>Erhebungstyp</label><select name="type"><option>Preiserhebung</option><option>Mystery Shopping</option><option>Fotodokumentation</option><option>Verfügbarkeits-Check</option><option>Berichterstellung</option><option>DSGVO-Compliance</option></select></div><div class="form-field"><label>Einsatzort</label><input type="text" name="location" placeholder="z.B. Berlin Mitte" /></div><div class="form-field"><label>Datum</label><input type="date" name="date" /></div><div class="form-field"><label>Auftrags-ID (leer = auto)</label><input type="text" name="id" placeholder="z.B. AUF-2026-001" style="font-family:monospace;" /></div><div class="form-field"><label>Zugangscode für Tracking *</label><input type="text" name="code" required placeholder="z.B. KPS2026" style="font-family:monospace;text-transform:uppercase;" oninput="this.value=this.value.toUpperCase()" /><small style="color:var(--text3);font-size:.72rem;">Dieser Code wird dem Auftraggeber mitgeteilt, um den Auftrag zu tracken.</small></div></div><div class="form-field" style="margin-top:.85rem;"><label>Notizen</label><textarea name="notes" placeholder="Interne Notizen zum Auftrag..."></textarea></div><div class="form-actions"><button type="submit" class="btn-sm primary">&#10003; Auftrag anlegen</button><button type="button" class="btn-sm" onclick="document.getElementById('nof').style.display='none'">Abbrechen</button></div></form></div></div></div>
      <?php if(empty($orders)):?><div class="card"><div class="card-body"><div class="empty-state"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><p>Noch keine Aufträge. Klicken Sie auf „+ Neuer Auftrag".</p></div></div></div>
      <?php else:?><div style="display:flex;flex-direction:column;gap:1rem;"><?php foreach(array_reverse($orders) as $o):$st=(int)($o['status']??1);$pr=(int)($o['progress']??0);?><div class="card"><div class="card-head"><div style="flex:1;"><div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;"><span style="font-size:.82rem;font-weight:700;font-family:monospace;"><?=htmlspecialchars($o['id']??'')?></span><span class="badge s<?=$st?>"><?=$sl[$st]??''?></span><span style="font-size:.7rem;color:var(--text3);">Code: <strong style="color:var(--text2);"><?=htmlspecialchars($o['code']??'')?></strong></span></div><div style="font-size:.88rem;font-weight:600;margin-top:.25rem;"><?=htmlspecialchars($o['client']??'')?> · <?=htmlspecialchars($o['type']??'')?></div></div></div><div class="card-body"><div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem 1.5rem;margin-bottom:1rem;font-size:.8rem;"><?php if(!empty($o['location'])):?><div><span style="color:var(--text3);">Ort:</span> <?=htmlspecialchars($o['location'])?></div><?php endif;?><?php if(!empty($o['date'])):?><div><span style="color:var(--text3);">Datum:</span> <?=htmlspecialchars($o['date'])?></div><?php endif;?><div><span style="color:var(--text3);">Fortschritt:</span> <?=$pr?>%</div></div><div class="progress-bar"><div class="progress-fill" style="width:<?=$pr?>%"></div></div><?php if(!empty($o['notes'])):?><div style="margin-top:.75rem;font-size:.78rem;color:var(--text2);background:rgba(255,255,255,.02);border:1px solid var(--border);border-radius:var(--r3);padding:.6rem .8rem;"><?=nl2br(htmlspecialchars($o['notes']))?></div><?php endif;?><form method="POST" style="margin-top:1rem;"><input type="hidden" name="action_order" value="update" /><input type="hidden" name="order_id" value="<?=htmlspecialchars($o['id']??'')?>" /><div class="form-grid"><div class="form-field"><label>Status</label><select name="status"><?php for($i=1;$i<=5;$i++):?><option value="<?=$i?>" <?=$st===$i?'selected':''?>><?=$sl[$i]?></option><?php endfor;?></select></div><div class="form-field"><label>Fortschritt (%)</label><input type="number" name="progress" min="0" max="100" value="<?=$pr?>" /></div></div><div class="form-field" style="margin-top:.75rem;"><label>Notizen</label><textarea name="notes"><?=htmlspecialchars($o['notes']??'')?></textarea></div><div class="form-actions"><button type="submit" class="btn-sm primary">Speichern</button><button type="submit" name="action_order" value="delete" class="btn-sm danger" onclick="return confirm('Löschen?')">Löschen</button></div></form></div></div><?php endforeach;?></div><?php endif;
      // NACHWEISE
      elseif($section==='nachweise'):
        $nf = DATA_DIR.'nachweise.json';
        $nachweise = [];
        if(file_exists($nf)){$raw=@file_get_contents($nf);$nachweise=$raw?(json_decode($raw,true)??[]):[]; }
        $nachweise = array_reverse($nachweise);
      ?>
      <?php if(empty($nachweise)):?>
      <div class="card"><div class="card-body"><div class="empty-state"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><p>Noch keine Einsatznachweise gespeichert.</p></div></div></div>
      <?php else:?>
      <div class="card"><div class="card-head"><span class="card-head-title">Einsatznachweise</span><span class="card-head-sub"><?=count($nachweise)?> gesamt</span></div>
      <div class="card-body" style="padding:0;">
      <table class="tbl">
        <thead><tr><th>ID</th><th>Name</th><th>Filiale</th><th>Typ</th><th>Datum</th><th>Institut</th><th>Gespeichert</th><th>Aktion</th></tr></thead>
        <tbody>
        <?php foreach($nachweise as $n):?>
        <tr>
          <td style="font-family:monospace;font-size:.72rem;"><?=htmlspecialchars($n['id']??'')?></td>
          <td><?=htmlspecialchars($n['name']??'')?></td>
          <td><?=htmlspecialchars($n['filiale']??'')?></td>
          <td><span class="badge s2" style="font-size:.65rem;"><?=htmlspecialchars($n['typ']??'')?></span></td>
          <td><?=htmlspecialchars($n['datum']??'')?></td>
          <td><?=htmlspecialchars($n['institut']??'–')?></td>
          <td style="font-size:.72rem;color:var(--text3);"><?=htmlspecialchars(substr($n['savedAt']??'',0,16))?></td>
          <td>
            <form method="POST" onsubmit="return confirm('Nachweis loeschen?');" style="display:inline;">
              <input type="hidden" name="action_nachweis" value="delete" />
              <input type="hidden" name="nachweis_id" value="<?=htmlspecialchars($n['id']??'')?>" />
              <button type="submit" class="btn-sm danger" style="padding:.2rem .5rem;font-size:.72rem;">&#128465;</button>
            </form>
          </td>
        </tr>
        <?php endforeach;?>
        </tbody>
      </table>
      </div></div>
<?php endif; ?>
      
      <?php
      // INSTITUTES
      elseif($section==='institutes'):
      ?>
?>
<div class="section-header-bar">
  <div>
    <h2 class="section-title">Institute &amp; Partner</h2>
    <p class="section-sub">Verwalten Sie alle Institute und Crowdsourcing-Plattformen</p>
  </div>
  <button class="btn btn-primary" onclick="openInstModal()">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Neues Institut
  </button>
</div>

<div class="card" style="margin-bottom:1.5rem;">
  <div class="card-body" style="padding:1rem 1.25rem;">
    <div style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;">
      <input type="text" id="inst-search" placeholder="Institut suchen..." class="form-input" style="max-width:280px;margin:0;" oninput="filterInstitutes(this.value)">
      <select id="inst-filter-type" class="form-input" style="max-width:200px;margin:0;" onchange="filterInstitutes()">
        <option value="">Alle Typen</option>
        <option>Marktforschung</option>
        <option>Mystery Shopping</option>
        <option>Crowdsourcing</option>
        <option>Preiserhebung</option>
        <option>Sonstiges</option>
      </select>
      <span id="inst-count" style="color:var(--text-muted);font-size:.85rem;margin-left:auto;"></span>
    </div>
  </div>
</div>

<div id="inst-list" class="inst-grid">
  <div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--text-muted);">
    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite;"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
    <p style="margin-top:.75rem;">Lade Institute...</p>
  </div>
</div>

<!-- Institut-Modal (v8 verbessert) -->
<div id="inst-modal" class="modal-overlay" style="display:none;" onclick="if(event.target===this)closeInstModal()">
  <div class="modal-box" style="max-width:640px;">
    <div class="modal-header" style="padding:1.25rem 1.5rem;border-bottom:1px solid var(--border);">
      <div>
        <h3 id="inst-modal-title" style="margin:0;font-size:1.1rem;">Neues Institut</h3>
        <p style="margin:.25rem 0 0;font-size:.78rem;color:var(--text-muted);">Alle Pflichtfelder sind mit * markiert</p>
      </div>
      <button class="modal-close" onclick="closeInstModal()" style="font-size:1.4rem;">&times;</button>
    </div>
    <form id="inst-form" onsubmit="saveInstitute(event)" style="padding:1.5rem;">
      <input type="hidden" id="inst-id" name="id" value="">

      <!-- Sektion: Grunddaten -->
      <div style="font-size:.68rem;font-weight:700;color:var(--accent);text-transform:uppercase;letter-spacing:.1em;margin-bottom:.75rem;">Grunddaten</div>
      <div style="display:grid;grid-template-columns:2fr 1fr;gap:1rem;margin-bottom:1rem;">
        <div class="form-group" style="margin:0;">
          <label class="form-label">Institutsname *</label>
          <input type="text" id="inst-name" name="name" class="form-input" required placeholder="z.B. IFH K&ouml;ln GmbH">
        </div>
        <div class="form-group" style="margin:0;">
          <label class="form-label">K&uuml;rzel</label>
          <input type="text" id="inst-short" name="short" class="form-input" placeholder="IFH" maxlength="6">
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-bottom:1.25rem;">
        <div class="form-group" style="margin:0;">
          <label class="form-label">Typ *</label>
          <select id="inst-type" name="type" class="form-input">
            <option>Marktforschung</option>
            <option>Mystery Shopping</option>
            <option>Crowdsourcing</option>
            <option>Preiserhebung</option>
            <option>Sonstiges</option>
          </select>
        </div>
        <div class="form-group" style="margin:0;">
          <label class="form-label">Priorit&auml;t</label>
          <select id="inst-priority" name="priority" class="form-input">
            <option value="normal">Normal</option>
            <option value="high">Hoch</option>
            <option value="low">Niedrig</option>
          </select>
        </div>
        <div class="form-group" style="margin:0;">
          <label class="form-label">Akzentfarbe</label>
          <div style="display:flex;gap:.5rem;align-items:center;">
            <input type="color" id="inst-color" name="color" value="#6366f1" style="width:44px;height:38px;border:1px solid var(--border);border-radius:8px;cursor:pointer;background:transparent;" oninput="document.getElementById('inst-color-val').textContent=this.value">
            <span id="inst-color-val" style="font-size:.8rem;color:var(--text-muted);font-family:monospace;">#6366f1</span>
          </div>
        </div>
      </div>

      <!-- Sektion: Kontakt -->
      <div style="font-size:.68rem;font-weight:700;color:var(--accent);text-transform:uppercase;letter-spacing:.1em;margin-bottom:.75rem;margin-top:.25rem;">Kontakt &amp; Online</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
        <div class="form-group" style="margin:0;">
          <label class="form-label">Website</label>
          <input type="url" id="inst-website" name="website" class="form-input" placeholder="https://www.beispiel.de">
        </div>
        <div class="form-group" style="margin:0;">
          <label class="form-label">Ansprechpartner</label>
          <input type="text" id="inst-contact" name="contact" class="form-input" placeholder="Max Mustermann">
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.25rem;">
        <div class="form-group" style="margin:0;">
          <label class="form-label">E-Mail</label>
          <input type="email" id="inst-email" name="email" class="form-input" placeholder="kontakt@beispiel.de">
        </div>
        <div class="form-group" style="margin:0;">
          <label class="form-label">Telefon</label>
          <input type="tel" id="inst-phone" name="phone" class="form-input" placeholder="+49 30 000 000">
        </div>
      </div>

      <!-- Sektion: Details -->
      <div style="font-size:.68rem;font-weight:700;color:var(--accent);text-transform:uppercase;letter-spacing:.1em;margin-bottom:.75rem;">Details</div>
      <div class="form-group" style="margin-bottom:1rem;">
        <label class="form-label">Beschreibung</label>
        <textarea id="inst-description" name="description" class="form-input" rows="3" placeholder="Kurze Beschreibung des Instituts und der Zusammenarbeit..."></textarea>
      </div>
      <div style="display:grid;grid-template-columns:1fr auto;gap:1rem;align-items:start;margin-bottom:1.25rem;">
        <div class="form-group" style="margin:0;">
          <label class="form-label">Tags (kommagetrennt)</label>
          <input type="text" id="inst-tags" name="tags" class="form-input" placeholder="Mystery Shopping, Preiserhebung, Berlin">
        </div>
        <div class="form-group" style="margin:0;">
          <label class="form-label">Status</label>
          <select id="inst-status" name="status" class="form-input">
            <option value="active">Aktiv</option>
            <option value="inactive">Inaktiv</option>
            <option value="pending">Ausstehend</option>
          </select>
        </div>
      </div>
      <div class="form-group" style="display:flex;align-items:center;gap:.75rem;padding:.75rem;background:var(--bg-surface);border-radius:8px;border:1px solid var(--border);margin-bottom:0;">
        <input type="checkbox" id="inst-featured" name="featured" style="width:18px;height:18px;cursor:pointer;accent-color:var(--accent);">
        <div>
          <label for="inst-featured" class="form-label" style="margin:0;cursor:pointer;font-size:.85rem;">Als Featured markieren</label>
          <div style="font-size:.72rem;color:var(--text-muted);">Wird auf der &ouml;ffentlichen Institute-Seite hervorgehoben</div>
        </div>
      </div>

      <div class="modal-footer" style="padding:1.25rem 0 0;border-top:1px solid var(--border);margin-top:1.5rem;">
        <button type="button" class="btn btn-secondary" onclick="closeInstModal()">Abbrechen</button>
        <button type="submit" class="btn btn-primary" id="inst-save-btn" style="min-width:120px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:.4rem;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
          Speichern
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Loeschbestaetigung -->
<div id="inst-delete-modal" class="modal-overlay" style="display:none;" onclick="if(event.target===this)this.style.display='none'">
  <div class="modal-box" style="max-width:400px;text-align:center;">
    <div style="font-size:2.5rem;margin-bottom:1rem;">&#9888;</div>
    <h3 style="margin-bottom:.5rem;">Institut loeschen?</h3>
    <p id="inst-delete-name" style="color:var(--text-muted);margin-bottom:1.5rem;"></p>
    <div style="display:flex;gap:1rem;justify-content:center;">
      <button class="btn btn-secondary" onclick="document.getElementById('inst-delete-modal').style.display='none'">Abbrechen</button>
      <button class="btn" style="background:#ef4444;color:#fff;" onclick="confirmDeleteInstitute()">Loeschen</button>
    </div>
  </div>
</div>

<style>
.inst-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1rem;}
.inst-card{background:var(--card-bg);border:1px solid var(--border);border-radius:12px;padding:1.25rem;position:relative;transition:border-color .2s;}
.inst-card:hover{border-color:var(--accent2);}
.inst-card-header{display:flex;align-items:center;gap:.85rem;margin-bottom:.85rem;}
.inst-badge{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.75rem;color:#fff;flex-shrink:0;letter-spacing:.05em;}
.inst-card-name{font-weight:600;font-size:.95rem;color:var(--text-primary);line-height:1.3;}
.inst-card-type{font-size:.75rem;color:var(--text-muted);}
.inst-card-desc{font-size:.82rem;color:var(--text-muted);line-height:1.6;margin-bottom:.85rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.inst-card-tags{display:flex;flex-wrap:wrap;gap:.35rem;margin-bottom:.85rem;}
.inst-tag{font-size:.7rem;background:var(--accent-sub);color:var(--accent2);border-radius:20px;padding:.2rem .6rem;}
.inst-card-actions{display:flex;gap:.5rem;}
.inst-card-actions button{flex:1;padding:.45rem;font-size:.8rem;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--text-muted);cursor:pointer;transition:all .2s;}
.inst-card-actions button:hover{background:var(--accent-sub);color:var(--accent2);border-color:var(--accent2);}
.inst-card-actions .btn-del:hover{background:rgba(239,68,68,.1);color:#ef4444;border-color:#ef4444;}
.inst-featured-badge{position:absolute;top:.75rem;right:.75rem;font-size:.65rem;background:rgba(245,158,11,.1);color:#f59e0b;border:1px solid rgba(245,158,11,.3);border-radius:20px;padding:.15rem .5rem;}
@media(max-width:480px){.inst-grid{grid-template-columns:1fr;}}
@keyframes spin{to{transform:rotate(360deg);}}
</style>

<script>
let allInstitutes=[];
let deleteTargetId=null;

// 1. Institute vom Server laden
async function loadInstitutes(){
  try {
    const res = await fetch('institutes-api.php?action=list');
    const json = await res.json();
    if(json.success) {
      allInstitutes = json.data || [];
      renderInstitutes(allInstitutes);
    }
  } catch(e) {
    console.error('Ladefehler:', e);
    document.getElementById('inst-list').innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:3rem;">Fehler beim Laden der Institute vom Server.</div>';
  }
}

// (Hier bleibt die alte renderInstitutes, filterInstitutes, openInstModal und closeInstModal Funktion genau so stehen wie sie war!)
function renderInstitutes(list){
  const c=document.getElementById('inst-list');
  const cnt=document.getElementById('inst-count');
  if(cnt)cnt.textContent=list.length+' Institute';
  if(!list.length){
    c.innerHTML='<div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--text-muted);"><p>Noch keine Institute. Klicken Sie auf &quot;+ Neues Institut&quot;.</p></div>';
    return;
  }
  c.innerHTML=list.map(i=>`
    <div class="inst-card" data-id="${i.id}">
      ${i.featured?'<span class="inst-featured-badge">&#9733; Featured</span>':''}
      <div class="inst-card-header">
        <div class="inst-badge" style="background:${i.color||'#6366f1'}">${(i.short||i.name.substring(0,3)).toUpperCase()}</div>
        <div><div class="inst-card-name">${i.name}</div><div class="inst-card-type">${i.type||'Marktforschung'}</div></div>
      </div>
      ${i.description?`<div class="inst-card-desc">${i.description}</div>`:''}
      ${i.tags&&i.tags.length?`<div class="inst-card-tags">${i.tags.filter(t=>t).map(t=>`<span class="inst-tag">${t}</span>`).join('')}</div>`:''}
      <div class="inst-card-actions">
        ${i.website?`<button onclick="window.open('${i.website}','_blank')">&#127760; Website</button>`:''}
        <button onclick="editInstitute('${i.id}')">&#9998; Bearbeiten</button>
        <button class="btn-del" onclick="deleteInstitute('${i.id}','${i.name.replace(/'/g,"\\'")}')">&#128465; Loeschen</button>
      </div>
    </div>
  `).join('');
}

function filterInstitutes(v){
  const q=(v!==undefined?v:document.getElementById('inst-search').value).toLowerCase();
  const type=document.getElementById('inst-filter-type').value;
  renderInstitutes(allInstitutes.filter(i=>{
    const mq=!q||i.name.toLowerCase().includes(q)||(i.description||'').toLowerCase().includes(q)||(i.tags||[]).some(t=>t.toLowerCase().includes(q));
    const mt=!type||i.type===type;
    return mq&&mt;
  }));
}

function openInstModal(data){
  document.getElementById('inst-modal-title').textContent=data?'Institut bearbeiten':'Neues Institut';
  document.getElementById('inst-id').value=data?data.id:'';
  document.getElementById('inst-name').value=data?data.name:'';
  document.getElementById('inst-short').value=data?data.short:'';
  document.getElementById('inst-type').value=data?data.type:'Marktforschung';
  document.getElementById('inst-priority').value=data?data.priority||'normal':'normal';
  document.getElementById('inst-color').value=data?data.color:'#6366f1';
  document.getElementById('inst-color-val').textContent=data?data.color:'#6366f1';
  document.getElementById('inst-website').value=data?data.website:'';
  document.getElementById('inst-contact').value = data ? data.contact || '' : '';
  document.getElementById('inst-email').value   = data ? data.email || '' : '';
  document.getElementById('inst-phone').value   = data ? data.phone || '' : '';
  document.getElementById('inst-description').value=data?data.description:'';
  document.getElementById('inst-tags').value=data?(data.tags||[]).join(', '):'';
  document.getElementById('inst-status').value=data?data.status||'active':'active';
  document.getElementById('inst-featured').checked=data?!!data.featured:false;
  document.getElementById('inst-modal').style.display='flex';
  setTimeout(()=>document.getElementById('inst-name').focus(),100);
}

function closeInstModal(){document.getElementById('inst-modal').style.display='none';}
function editInstitute(id){const i=allInstitutes.find(x=>x.id===id);if(i)openInstModal(i);}

// 2. Institut auf dem Server speichern
async function saveInstitute(e){
  e.preventDefault();
  const btn=document.getElementById('inst-save-btn');
  btn.disabled=true;btn.textContent='Speichern...';
  
  const id=document.getElementById('inst-id').value;
  const name=document.getElementById('inst-name').value.trim();
  if(!name){showAdminToast('Name ist erforderlich','error');btn.disabled=false;btn.textContent='Speichern';return;}
  
  const payload={
    id: id,
    name: name,
    short: document.getElementById('inst-short').value.trim(),
    type: document.getElementById('inst-type').value,
    priority: document.getElementById('inst-priority').value,
    color: document.getElementById('inst-color').value,
    website: document.getElementById('inst-website').value.trim(),
    contact: document.getElementById('inst-contact').value.trim(),
    email: document.getElementById('inst-email').value.trim(),
    phone: document.getElementById('inst-phone').value.trim(),
    description: document.getElementById('inst-description').value.trim(),
    tags: document.getElementById('inst-tags').value,
    status: document.getElementById('inst-status').value,
    featured: document.getElementById('inst-featured').checked
  };

  try {
    const action = id ? 'update' : 'add';
    const res = await fetch('institutes-api.php?action=' + action, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(payload)
    });
    const result = await res.json();
    if(result.success) {
      showAdminToast(result.message, 'success');
      closeInstModal();
      loadInstitutes(); // Lade frische Daten vom Server
    } else {
      showAdminToast(result.error || 'Fehler', 'error');
    }
  } catch(e) {
    showAdminToast('Netzwerkfehler', 'error');
  }
  
  btn.disabled=false;btn.textContent='Speichern';
}

function deleteInstitute(id,name){
  deleteTargetId=id;
  document.getElementById('inst-delete-name').textContent='"'+name+'" wird unwiderruflich geloescht.';
  document.getElementById('inst-delete-modal').style.display='flex';
}

// 3. Institut vom Server löschen
async function confirmDeleteInstitute(){
  if(!deleteTargetId)return;
  try {
    const res = await fetch('institutes-api.php?action=delete', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({id: deleteTargetId})
    });
    const result = await res.json();
    if(result.success) {
      showAdminToast(result.message, 'success');
      document.getElementById('inst-delete-modal').style.display='none';
      loadInstitutes(); // Lade frische Daten vom Server
    }
  } catch(e) {
    showAdminToast('Fehler beim Löschen', 'error');
  }
  deleteTargetId=null;
}

function showAdminToast(msg,type){
  const t=document.createElement('div');
  t.style.cssText='position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;padding:.75rem 1.25rem;border-radius:10px;font-size:.875rem;font-weight:500;color:#fff;box-shadow:0 4px 20px rgba(0,0,0,.3);';
  t.style.background=type==='success'?'#10b981':'#ef4444';
  t.textContent=msg;
  document.body.appendChild(t);
  setTimeout(()=>t.remove(),3500);
}

document.getElementById('inst-color').addEventListener('input',function(){
  document.getElementById('inst-color-val').textContent=this.value;
});

// Init
loadInstitutes();
</script>
<?php
      elseif($section==='stats'):
      $l30=[];for($i=29;$i>=0;$i--){$k=date('Y-m-d',strtotime("-{$i} days"));$l30[$k]=$stats[$k]['views']??0;}
      $mx=max(array_values($l30))?:1;
      $tp=[];foreach($stats as $day=>$data){if(!is_array($data))continue;foreach(($data['pages']??[]) as $p=>$c){$tp[$p]=($tp[$p]??0)+$c;}}arsort($tp);$tp=array_slice($tp,0,8,true);?>
      <div class="kpi-grid">
        <div class="kpi"><div class="kpi-icon purple"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent2)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></div><div class="kpi-val"><?=$totalViews?></div><div class="kpi-label">Gesamtaufrufe</div></div>
        <div class="kpi"><div class="kpi-icon blue"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg></div><div class="kpi-val"><?=$todayViews?></div><div class="kpi-label">Heute</div></div>
        <div class="kpi"><div class="kpi-icon green"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div><div class="kpi-val"><?=array_sum($l30)?></div><div class="kpi-label">Letzte 30 Tage</div></div>
        <div class="kpi"><div class="kpi-icon yellow"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--yellow)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div><div class="kpi-val"><?=count($stats)?></div><div class="kpi-label">Aktive Tage</div></div>
      </div>
      <div class="two-col">
        <div class="card"><div class="card-head"><span class="card-head-title">Aufrufe – letzte 30 Tage</span></div><div class="card-body"><div class="chart-bars"><?php foreach($l30 as $k=>$v):$h=max(4,round(($v/$mx)*80));?><div class="chart-bar" style="height:<?=$h?>px;" data-val="<?=$v?>" title="<?=$k?>: <?=$v?>"></div><?php endforeach;?></div></div></div>
        <div class="card"><div class="card-head"><span class="card-head-title">Top-Seiten</span></div><div class="card-body"><?php if(empty($tp)):?><div class="empty-state"><p>Noch keine Daten.</p></div><?php else:foreach($tp as $p=>$c):$pct=round(($c/max(array_values($tp)))*100);?><div style="margin-bottom:.75rem;"><div style="display:flex;justify-content:space-between;font-size:.78rem;margin-bottom:.25rem;"><span><?=htmlspecialchars(basename($p)?:'/')?></span><span style="color:var(--text3);"><?=$c?></span></div><div class="progress-bar"><div class="progress-fill" style="width:<?=$pct?>%"></div></div></div><?php endforeach;endif;?></div></div>
      </div>
      <?php endif;?>
    </main>
  </div>
</div>
<script>
function openSidebar(){document.getElementById('sidebar').classList.add('open');document.getElementById('overlay').classList.add('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('overlay').classList.remove('show');}
function setAvDate(d){var el=document.getElementById('av-date-input');if(el)el.value=d;}
</script>
<?php endif;?>
</body>
</html>
