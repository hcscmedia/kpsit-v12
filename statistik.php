<?php
/**
 * KPS-IT.de – Besucherstatistik-Dashboard
 * Nur zugänglich nach Admin-Login (admin.php)
 */
declare(strict_types=1);
session_start();

if (empty($_SESSION['kps_admin'])) {
    header('Location: admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <title>Besucherstatistik – KPS-IT.de Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    :root {
      --bg:       #080c14;
      --bg2:      #0d1220;
      --bg3:      #111827;
      --border:   rgba(99,102,241,.18);
      --indigo:   #6366f1;
      --green:    #10b981;
      --amber:    #f59e0b;
      --red:      #ef4444;
      --cyan:     #06b6d4;
      --text:     #e2e8f0;
      --muted:    #64748b;
      --card-bg:  rgba(255,255,255,.04);
      --radius:   12px;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; font-size: 14px; min-height: 100vh; }

    /* Layout */
    .layout { display: flex; min-height: 100vh; }
    .sidebar {
      width: 220px; flex-shrink: 0; background: var(--bg2);
      border-right: 1px solid var(--border); padding: 1.5rem 1rem;
      display: flex; flex-direction: column; gap: .5rem;
    }
    .sidebar-logo { font-size: 1.1rem; font-weight: 700; color: var(--indigo); padding: .5rem .75rem 1rem; }
    .sidebar-link {
      display: flex; align-items: center; gap: .6rem; padding: .6rem .75rem;
      border-radius: 8px; color: var(--muted); text-decoration: none;
      font-size: .85rem; transition: all .2s;
    }
    .sidebar-link:hover, .sidebar-link.active { background: rgba(99,102,241,.12); color: var(--text); }
    .sidebar-link svg { flex-shrink: 0; }
    .sidebar-sep { height: 1px; background: var(--border); margin: .5rem 0; }
    .main { flex: 1; padding: 2rem; overflow-x: hidden; }

    /* Header */
    .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
    .page-title { font-size: 1.5rem; font-weight: 700; }
    .page-subtitle { color: var(--muted); font-size: .85rem; margin-top: .2rem; }
    .refresh-btn {
      display: flex; align-items: center; gap: .5rem; padding: .5rem 1rem;
      background: rgba(99,102,241,.15); border: 1px solid var(--border);
      border-radius: 8px; color: var(--text); cursor: pointer; font-size: .85rem;
      transition: all .2s;
    }
    .refresh-btn:hover { background: rgba(99,102,241,.25); }

    /* KPI-Karten */
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
    .kpi-card {
      background: var(--card-bg); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 1.25rem;
    }
    .kpi-label { color: var(--muted); font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; margin-bottom: .5rem; }
    .kpi-value { font-size: 2rem; font-weight: 700; line-height: 1; }
    .kpi-sub { color: var(--muted); font-size: .75rem; margin-top: .4rem; }
    .kpi-card.c-indigo .kpi-value { color: var(--indigo); }
    .kpi-card.c-green  .kpi-value { color: var(--green); }
    .kpi-card.c-amber  .kpi-value { color: var(--amber); }
    .kpi-card.c-cyan   .kpi-value { color: var(--cyan); }

    /* Charts-Grid */
    .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
    @media (max-width: 900px) { .charts-grid { grid-template-columns: 1fr; } }
    .chart-card {
      background: var(--card-bg); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 1.5rem;
    }
    .chart-title { font-weight: 600; margin-bottom: 1.25rem; font-size: .95rem; }
    .chart-wrap { position: relative; height: 200px; }

    /* Balken-Charts (custom) */
    .bar-chart { display: flex; flex-direction: column; gap: .5rem; }
    .bar-row { display: flex; align-items: center; gap: .75rem; }
    .bar-label { width: 120px; flex-shrink: 0; font-size: .78rem; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .bar-track { flex: 1; height: 8px; background: rgba(255,255,255,.06); border-radius: 4px; overflow: hidden; }
    .bar-fill { height: 100%; border-radius: 4px; transition: width 1s ease; }
    .bar-count { width: 40px; text-align: right; font-size: .78rem; color: var(--muted); }

    /* Tages-Chart */
    .day-chart { display: flex; align-items: flex-end; gap: 3px; height: 160px; }
    .day-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px; }
    .day-bar { width: 100%; background: var(--indigo); border-radius: 3px 3px 0 0; min-height: 2px; transition: height .8s ease; opacity: .8; }
    .day-bar:hover { opacity: 1; }
    .day-label { font-size: .6rem; color: var(--muted); white-space: nowrap; }

    /* Donut-Chart */
    .donut-wrap { display: flex; align-items: center; gap: 1.5rem; }
    .donut-svg { flex-shrink: 0; }
    .donut-legend { display: flex; flex-direction: column; gap: .5rem; }
    .donut-item { display: flex; align-items: center; gap: .5rem; font-size: .82rem; }
    .donut-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }

    /* Stunden-Chart */
    .hour-chart { display: flex; align-items: flex-end; gap: 2px; height: 100px; }
    .hour-col { flex: 1; display: flex; flex-direction: column; align-items: center; }
    .hour-bar { width: 100%; background: var(--cyan); border-radius: 2px 2px 0 0; min-height: 2px; opacity: .7; }
    .hour-bar:hover { opacity: 1; }
    .hour-label { font-size: .55rem; color: var(--muted); margin-top: 3px; }

    /* Tabelle */
    .data-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
    .data-table th { color: var(--muted); font-weight: 500; text-align: left; padding: .6rem .75rem; border-bottom: 1px solid var(--border); }
    .data-table td { padding: .6rem .75rem; border-bottom: 1px solid rgba(255,255,255,.04); }
    .data-table tr:last-child td { border-bottom: none; }
    .data-table tr:hover td { background: rgba(255,255,255,.02); }

    /* Loading */
    .loading { display: flex; align-items: center; justify-content: center; height: 200px; color: var(--muted); gap: .75rem; }
    .spinner { width: 20px; height: 20px; border: 2px solid var(--border); border-top-color: var(--indigo); border-radius: 50%; animation: spin .8s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* Responsive */
    @media (max-width: 768px) {
      .sidebar { display: none; }
      .main { padding: 1rem; }
    }
  </style>
</head>
<body>

<div class="layout">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-logo">KPS Admin</div>
    <a href="admin.php" class="sidebar-link">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Übersicht
    </a>
    <a href="statistik.php" class="sidebar-link active">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      Statistiken
    </a>
    <div class="sidebar-sep"></div>
    <a href="index.html" class="sidebar-link" target="_blank">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
      Website
    </a>
    <a href="admin.php?logout=1" class="sidebar-link" style="margin-top:auto; color:#ef4444;">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Abmelden
    </a>
  </aside>

  <!-- Main -->
  <main class="main">
    <div class="page-header">
      <div>
        <div class="page-title">Besucherstatistik</div>
        <div class="page-subtitle">Datenschutzkonforme Auswertung – keine Cookies, keine IP-Speicherung</div>
      </div>
      <button class="refresh-btn" onclick="loadStats()">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
        Aktualisieren
      </button>
    </div>

    <!-- KPI-Karten -->
    <div class="kpi-grid" id="kpi-grid">
      <div class="loading"><div class="spinner"></div> Lade Daten…</div>
    </div>

    <!-- Charts -->
    <div class="charts-grid" id="charts-grid" style="display:none;">

      <!-- Tagesverlauf -->
      <div class="chart-card" style="grid-column: 1 / -1;">
        <div class="chart-title">Seitenaufrufe – letzte 30 Tage</div>
        <div id="day-chart-wrap"></div>
      </div>

      <!-- Gerätetypen -->
      <div class="chart-card">
        <div class="chart-title">Gerätetypen</div>
        <div id="device-chart-wrap"></div>
      </div>

      <!-- Stunden-Verteilung -->
      <div class="chart-card">
        <div class="chart-title">Tageszeit-Verteilung (letzte 7 Tage)</div>
        <div id="hour-chart-wrap"></div>
      </div>

      <!-- Top-Seiten -->
      <div class="chart-card">
        <div class="chart-title">Top-Seiten</div>
        <div id="pages-chart-wrap"></div>
      </div>

      <!-- Top-Referrer -->
      <div class="chart-card">
        <div class="chart-title">Traffic-Quellen</div>
        <div id="refs-chart-wrap"></div>
      </div>

    </div>

    <!-- Tages-Tabelle -->
    <div class="chart-card" id="days-table-wrap" style="display:none;">
      <div class="chart-title">Tagesübersicht</div>
      <div id="days-table"></div>
    </div>

  </main>
</div>

<script>
var COLORS = ['#6366f1','#10b981','#f59e0b','#06b6d4','#ec4899','#8b5cf6','#f97316','#14b8a6','#a855f7','#22c55e'];

function loadStats() {
  document.getElementById('kpi-grid').innerHTML = '<div class="loading"><div class="spinner"></div> Lade Daten…</div>';
  document.getElementById('charts-grid').style.display = 'none';
  document.getElementById('days-table-wrap').style.display = 'none';

  fetch('stats-api.php?action=get')
    .then(function(r) { return r.json(); })
    .then(function(d) {
      if (!d.success) { showError(d.message || 'Fehler'); return; }
      renderKPIs(d);
      renderDayChart(d.days || []);
      renderDeviceChart(d.devices || {});
      renderHourChart(d.hourly_7d || []);
      renderBarChart('pages-chart-wrap', d.top_pages || {}, COLORS[0]);
      renderBarChart('refs-chart-wrap', d.top_referrers || {}, COLORS[1]);
      renderDaysTable(d.days || []);
      document.getElementById('charts-grid').style.display = 'grid';
      document.getElementById('days-table-wrap').style.display = 'block';
    })
    .catch(function(e) { showError('Verbindungsfehler: ' + e.message); });
}

function showError(msg) {
  document.getElementById('kpi-grid').innerHTML = '<div class="loading" style="color:#ef4444;">' + msg + '</div>';
}

function renderKPIs(d) {
  var today = (d.days && d.days[0]) ? d.days[0] : {views:0, visitors:0};
  var html = [
    kpiCard('Gesamt Aufrufe', d.total || 0, 'Alle Zeiten', 'c-indigo'),
    kpiCard('Aufrufe (30 Tage)', d.views_30d || 0, 'Seitenaufrufe', 'c-green'),
    kpiCard('Besucher (30 Tage)', d.visitors_30d || 0, 'Eindeutige Besucher', 'c-cyan'),
    kpiCard('Heute', today.views || 0, (today.visitors || 0) + ' Besucher', 'c-amber')
  ].join('');
  document.getElementById('kpi-grid').innerHTML = html;
}

function kpiCard(label, value, sub, cls) {
  return '<div class="kpi-card ' + cls + '">' +
    '<div class="kpi-label">' + label + '</div>' +
    '<div class="kpi-value">' + value.toLocaleString('de-DE') + '</div>' +
    '<div class="kpi-sub">' + sub + '</div>' +
  '</div>';
}

function renderDayChart(days) {
  var wrap = document.getElementById('day-chart-wrap');
  if (!days.length) { wrap.innerHTML = '<div class="loading">Keine Daten</div>'; return; }
  var max = Math.max.apply(null, days.map(function(d){ return d.views; })) || 1;
  var reversed = days.slice().reverse();
  var html = '<div class="day-chart">';
  reversed.forEach(function(d) {
    var pct = Math.round((d.views / max) * 100);
    var label = d.date ? d.date.slice(5) : '';
    html += '<div class="day-col" title="' + d.date + ': ' + d.views + ' Aufrufe, ' + d.visitors + ' Besucher">' +
      '<div class="day-bar" style="height:' + Math.max(pct, 2) + '%;"></div>' +
      '<div class="day-label">' + label + '</div>' +
    '</div>';
  });
  html += '</div>';
  wrap.innerHTML = html;
}

function renderDeviceChart(devices) {
  var wrap = document.getElementById('device-chart-wrap');
  var labels = { desktop: 'Desktop', mobile: 'Smartphone', tablet: 'Tablet' };
  var total = Object.values(devices).reduce(function(a,b){ return a+b; }, 0) || 1;
  var r = 60, cx = 70, cy = 70, size = 140;
  var svg = '<svg class="donut-svg" width="' + size + '" height="' + size + '" viewBox="0 0 ' + size + ' ' + size + '">';
  var startAngle = -Math.PI / 2;
  var keys = Object.keys(devices);
  var legendHtml = '';

  keys.forEach(function(key, i) {
    var val = devices[key] || 0;
    var angle = (val / total) * 2 * Math.PI;
    var endAngle = startAngle + angle;
    var x1 = cx + r * Math.cos(startAngle), y1 = cy + r * Math.sin(startAngle);
    var x2 = cx + r * Math.cos(endAngle),   y2 = cy + r * Math.sin(endAngle);
    var large = angle > Math.PI ? 1 : 0;
    var color = COLORS[i % COLORS.length];
    svg += '<path d="M ' + cx + ' ' + cy + ' L ' + x1.toFixed(1) + ' ' + y1.toFixed(1) +
           ' A ' + r + ' ' + r + ' 0 ' + large + ' 1 ' + x2.toFixed(1) + ' ' + y2.toFixed(1) + ' Z"' +
           ' fill="' + color + '" opacity="0.85"/>';
    legendHtml += '<div class="donut-item"><div class="donut-dot" style="background:' + color + '"></div>' +
                  (labels[key] || key) + ' (' + Math.round(val/total*100) + '%)</div>';
    startAngle = endAngle;
  });
  svg += '<circle cx="' + cx + '" cy="' + cy + '" r="30" fill="var(--bg3)"/>';
  svg += '<text x="' + cx + '" y="' + (cy+5) + '" text-anchor="middle" fill="var(--text)" font-size="11" font-family="Inter">' + total + '</text>';
  svg += '</svg>';

  wrap.innerHTML = '<div class="donut-wrap">' + svg + '<div class="donut-legend">' + legendHtml + '</div></div>';
}

function renderHourChart(hours) {
  var wrap = document.getElementById('hour-chart-wrap');
  var max = Math.max.apply(null, hours) || 1;
  var html = '<div class="hour-chart">';
  hours.forEach(function(v, h) {
    var pct = Math.round((v / max) * 100);
    html += '<div class="hour-col" title="' + h + ':00 Uhr – ' + v + ' Aufrufe">' +
      '<div class="hour-bar" style="height:' + Math.max(pct, 2) + '%;"></div>' +
      '<div class="hour-label">' + (h % 6 === 0 ? h + 'h' : '') + '</div>' +
    '</div>';
  });
  html += '</div>';
  wrap.innerHTML = html;
}

function renderBarChart(wrapperId, data, color) {
  var wrap = document.getElementById(wrapperId);
  var entries = Object.entries(data);
  if (!entries.length) { wrap.innerHTML = '<div class="loading">Keine Daten</div>'; return; }
  var max = Math.max.apply(null, entries.map(function(e){ return e[1]; })) || 1;
  var html = '<div class="bar-chart">';
  entries.forEach(function(e) {
    var pct = Math.round((e[1] / max) * 100);
    html += '<div class="bar-row">' +
      '<div class="bar-label" title="' + e[0] + '">' + e[0] + '</div>' +
      '<div class="bar-track"><div class="bar-fill" style="width:' + pct + '%;background:' + color + ';"></div></div>' +
      '<div class="bar-count">' + e[1] + '</div>' +
    '</div>';
  });
  html += '</div>';
  wrap.innerHTML = html;
}

function renderDaysTable(days) {
  var wrap = document.getElementById('days-table');
  if (!days.length) { wrap.innerHTML = '<div class="loading">Keine Daten</div>'; return; }
  var html = '<table class="data-table"><thead><tr>' +
    '<th>Datum</th><th>Aufrufe</th><th>Besucher</th>' +
  '</tr></thead><tbody>';
  days.forEach(function(d) {
    html += '<tr><td>' + d.date + '</td><td>' + d.views + '</td><td>' + d.visitors + '</td></tr>';
  });
  html += '</tbody></table>';
  wrap.innerHTML = html;
}

// Beim Laden sofort Daten abrufen
loadStats();
</script>

</body>
</html>
