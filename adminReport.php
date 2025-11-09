<?php
session_start();
include "dbconnect.php";

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

if (!isset($dbcnx) || $dbcnx->connect_errno) {
    die("Database connection not found or failed.");
}

/* --- Simple admin check --- */
if (!isset($_SESSION['sess_user']) || strtolower($_SESSION['sess_user']) !== 'admin') {
    die("Access denied. Admins only.");
}

/* --- Fetch daily sales (by ShowDate) --- */
$daily = [];
$sql = "
    SELECT 
        t.ShowDate,
        COUNT(*) AS tickets_sold,
        SUM(m.TicketPrice) AS total_sales
    FROM tickets t
    JOIN movies m ON t.MovieCode = m.MovieCode
    GROUP BY t.ShowDate
    ORDER BY t.ShowDate DESC
    LIMIT 7
";
if ($res = $dbcnx->query($sql)) {
    while ($row = $res->fetch_assoc()) {
        $daily[] = $row;
    }
    $res->close();
}

/* --- Fetch top-selling movies (by tickets sold) --- */
$topMovies = [];
$sqlTop = "
    SELECT 
        m.MovieCode,
        m.Title,
        COUNT(t.TicketID) AS tickets_sold,
        SUM(m.TicketPrice) AS total_sales
    FROM tickets t
    JOIN movies m ON t.MovieCode = m.MovieCode
    GROUP BY t.MovieCode, m.Title
    ORDER BY tickets_sold DESC
    LIMIT 10
";
if ($res = $dbcnx->query($sqlTop)) {
    while ($row = $res->fetch_assoc()) {
        $topMovies[] = $row;
    }
    $res->close();
}

$topMovie = $topMovies[0] ?? null;


/* Prepare data for chart */
$labels = [];
$sales = [];
$maxSales = 0.0;
foreach (array_reverse($daily) as $row) { // left->right oldest->newest
    $labels[] = $row['ShowDate'];
    $amt = (float)$row['total_sales'];
    $sales[] = $amt;
    if ($amt > $maxSales) $maxSales = $amt;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>CineLux Admin - Reports</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="styles.css">

<style>
body {
  font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  background:#05060a;
  color:#f5f5f5;
}
/* Nav reused from before */
.admin-nav {
  background:#0c0c14;
  border-bottom:1px solid #2c2c3e;
  padding:.6rem 0;
  position:sticky;
  top:0; z-index:999;
}
.admin-nav-container {
  max-width:1150px;margin:0 auto;padding:0 1rem;
  display:flex;justify-content:space-between;align-items:center;
}
.admin-brand {font-size:1.1rem;color:#f5b301;letter-spacing:.04em;}
.admin-links a {
  margin-left:1.2rem;color:#cfd0e0;text-decoration:none;
  font-size:.9rem;padding:.3rem .5rem;border-radius:6px;transition:.2s;
}
.admin-links a:hover {color:#fff;background:#2a2a44;}
.admin-links a.active {color:#111;background:#f5b301;font-weight:600;}

.admin-wrapper {
  max-width:1150px;margin:1.5rem auto 2rem;padding:1.5rem;
  background:#12121c;border-radius:14px;box-shadow:0 10px 25px rgba(0,0,0,0.6);
}
h1{margin:0 0 .3rem;font-size:1.5rem;}
p.sub{margin:0 0 1.2rem;color:#9a9ab5;font-size:.9rem;}

.grid{display:grid;grid-template-columns:2fr 3fr;gap:1.5rem;align-items:flex-start;}
.card{background:#171725;border-radius:12px;padding:.9rem 1rem;border:1px solid #26263a;}
.card h2{margin:0 0 .3rem;font-size:1rem;}
.card small{display:block;margin-bottom:.5rem;color:#8f90aa;font-size:.76rem;}

table{width:100%;border-collapse:collapse;font-size:.82rem;margin-top:.3rem;}
th,td{padding:.4rem .45rem;border-bottom:1px solid #25263a;}
th{text-align:left;color:#9fa0c2;font-weight:500;}
tr:hover td{background:#151624;}

.top-movie {
  background:#1b1b2c;
  padding:.7rem 1rem;
  border:1px solid #2a2a44;
  border-radius:8px;
  margin-bottom:1rem;
}
.top-movie strong {color:#f5b301;}
.chart-wrap {
  width:100%;
  height:220px;
  position:relative;
}
svg#lineChart {
  width:100%;
  height:100%;
}
.line {
  fill:none;
  stroke:#f5b301;
  stroke-width:2.5;
}
.dot {
  fill:#f5b301;
}
.axis-label {
  fill:#9a9ab5;
  font-size:10px;
  text-anchor:middle;
}
.axis-line {
  stroke:#34354a;
  stroke-width:1;
}
</style>
</head>
<body>

<!-- Admin Nav -->
<header class="admin-nav">
  <div class="admin-nav-container">
    <div class="admin-brand"><strong>CineLux</strong> Admin Panel</div>
    <nav class="admin-links">
      <a href="adminMovie.php">Movies</a>
      <a href="adminReport.php" class="active">Reports</a>
      <a href="logout.php" onclick="return confirm('Log out of admin panel?');">Logout</a>
    </nav>
  </div>
</header>

<div class="admin-wrapper">
  <h1>Sales Report</h1>
  <p class="sub">Daily ticket sales and top-performing movie.</p>

  <?php if ($topMovie): ?>
    <div class="top-movie">
      The top selling movie is <strong><?= e($topMovie['Title']) ?></strong>  
      with <?= (int)$topMovie['tickets_sold'] ?> tickets sold, 
      generating <strong>$<?= number_format((float)$topMovie['total_sales'], 2) ?></strong> in sales.
    </div>
  <?php endif; ?>

  <?php if (!empty($topMovies)): ?>
  <div class="card" style="margin-top:1.5rem;">
    <h2>Top Movies by Tickets Sold</h2>
    <small>Ranked based on total tickets sold across all show dates.</small>
    <table>
      <thead>
        <tr>
          <th style="width:40px;">#</th>
          <th>Movie Code</th>
          <th>Title</th>
          <th>Tickets Sold</th>
          <th>Total Sales ($)</th>
        </tr>
      </thead>
      <tbody>
      <?php $rank = 1; ?>
      <?php foreach ($topMovies as $mv): ?>
        <tr>
          <td><?= $rank++ ?></td>
          <td><?= e($mv['MovieCode']) ?></td>
          <td><?= e($mv['Title']) ?></td>
          <td><?= (int)$mv['tickets_sold'] ?></td>
          <td><?= number_format((float)$mv['total_sales'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

        <br>
  <?php if (empty($daily)): ?>
    <p style="color:#9a9ab5;font-size:.9rem;">No sales data available yet.</p>
  <?php else: ?>
    <div class="grid">
      <!-- Table -->
      <div class="card">
        <h2>Daily Breakdown</h2>
        <small>Summarised by show date from tickets × movie ticket price.</small>
        <table>
          <thead>
            <tr><th>Date</th><th>Tickets Sold</th><th>Total Sales ($)</th></tr>
          </thead>
          <tbody>
          <?php foreach ($daily as $row): ?>
            <tr>
              <td><?= e($row['ShowDate']) ?></td>
              <td><?= (int)$row['tickets_sold'] ?></td>
              <td><?= number_format((float)$row['total_sales'], 2) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Line Chart -->
      <div class="card">
        <h2>Sales Trend (Line Chart)</h2>
        <small>Total sales per day, showing last 7 show dates.</small>
        <div class="chart-wrap">
          <svg id="lineChart"></svg>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
// ===== Line chart render (no library) =====
(function(){
  const labels = <?= json_encode($labels) ?>;
  const data = <?= json_encode($sales) ?>;
  if (!labels.length) return;

  const svg = document.getElementById('lineChart');
  const width = svg.clientWidth || 400;
  const height = svg.clientHeight || 200;
  const padding = 30;

  const maxY = Math.max(...data) * 1.1;
  const minY = 0;
  const n = data.length;

  const xStep = (width - padding * 2) / (n - 1);
  const yScale = (val) => height - padding - (val - minY) / (maxY - minY) * (height - padding * 1.5);

  // draw axes
  const createLine = (x1,y1,x2,y2,cls) => {
    const l = document.createElementNS('http://www.w3.org/2000/svg','line');
    l.setAttribute('x1',x1); l.setAttribute('y1',y1);
    l.setAttribute('x2',x2); l.setAttribute('y2',y2);
    l.setAttribute('class',cls);
    svg.appendChild(l);
  };
  createLine(padding, height-padding, width-padding, height-padding, 'axis-line'); // x-axis
  createLine(padding, padding/2, padding, height-padding, 'axis-line'); // y-axis

  // path for line
  let pathD = '';
  data.forEach((val,i)=>{
    const x = padding + i * xStep;
    const y = yScale(val);
    pathD += (i===0?'M':'L') + x + ' ' + y + ' ';
  });
  const path = document.createElementNS('http://www.w3.org/2000/svg','path');
  path.setAttribute('d',pathD);
  path.setAttribute('class','line');
  svg.appendChild(path);

  // dots + labels
  data.forEach((val,i)=>{
    const x = padding + i * xStep;
    const y = yScale(val);
    const dot = document.createElementNS('http://www.w3.org/2000/svg','circle');
    dot.setAttribute('cx',x); dot.setAttribute('cy',y);
    dot.setAttribute('r',3.5); dot.setAttribute('class','dot');
    svg.appendChild(dot);

    const lbl = document.createElementNS('http://www.w3.org/2000/svg','text');
    lbl.setAttribute('x',x);
    lbl.setAttribute('y',height - padding + 14);
    lbl.setAttribute('class','axis-label');
    lbl.textContent = labels[i].slice(5); // MM-DD
    svg.appendChild(lbl);
  });
})();
</script>

</body>
</html>
