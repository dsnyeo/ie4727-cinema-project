<?php

session_start();

if (!isset($_SESSION['user_id'])) {
  if (isset($_SESSION['sess_user'])) {
    include __DIR__ . '/dbconnect.php';
    if (isset($dbcnx) && $dbcnx instanceof mysqli) {
      if ($stmt = $dbcnx->prepare('SELECT UserID FROM users WHERE Username = ? LIMIT 1')) {
        $stmt->bind_param('s', $_SESSION['sess_user']);
        $stmt->execute();
        $stmt->bind_result($uid);
        if ($stmt->fetch()) {
          $_SESSION['user_id'] = (int)$uid;
        }
        $stmt->close();
      }
    }
  }
}

if (!isset($_SESSION['user_id'])) {
  header('Location: login-main.php?redirect=bookings.php');
  exit;
}

include __DIR__ . '/dbconnect.php';
if (!isset($dbcnx) || !($dbcnx instanceof mysqli)) {
  http_response_code(500);
  echo 'Database connection not initialized.';
  exit;
}

$userId = (int)$_SESSION['user_id'];

$sql = "
  SELECT
    t.OrderID,
    t.HallID,
    t.ShowDate,
    t.TimeSlot,
    t.SeatCode,
    t.MovieCode,
    m.Title AS MovieTitle,
    b.PaidAmount,
    b.CreatedAt
  FROM tickets t
  INNER JOIN movies m ON m.MovieCode = t.MovieCode
  INNER JOIN bookings b ON b.OrderID = t.OrderID
  WHERE t.UserID = ?
  ORDER BY t.ShowDate DESC, t.TimeSlot DESC, t.OrderID DESC
";

$rows = [];
$stmt = $dbcnx->prepare($sql);
if ($stmt) {
  $stmt->bind_param('i', $userId);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($r = $result->fetch_assoc()) {
    $rows[] = $r;
  }
  $stmt->close();
}

$groups = [];
foreach ($rows as $r) {
  $key = implode('|', [
    $r['OrderID'], $r['MovieTitle'], $r['ShowDate'], $r['TimeSlot'], $r['HallID']
  ]);

  if (!isset($groups[$key])) {
    $groups[$key] = [
      'OrderID'    => (int)$r['OrderID'],
      'MovieTitle' => $r['MovieTitle'],
      'MovieCode'  => $r['MovieCode'],
      'ShowDate'   => $r['ShowDate'],
      'TimeSlot'   => $r['TimeSlot'],
      'HallID'     => $r['HallID'],
      'PaidAmount' => (string)$r['PaidAmount'],
      'CreatedAt'  => $r['CreatedAt'],
      'Seats'      => [],
    ];
  }

  $groups[$key]['Seats'][] = $r['SeatCode'];
}

function fmt_date($d){ if(!$d) return ''; $ts = strtotime($d); return date('D, j M Y', $ts); }
function fmt_time($t){ if(!$t) return ''; return date('g:i A', strtotime($t)); }
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Bookings</title>
    <link rel="stylesheet" href="styles.css" />
    <style>
        .cart-shell{max-width:1100px;margin:0 auto;padding:1rem;}
        .cart-title{font-size:1.75rem;margin:1rem 0; color:#fff; }
        .cart-items-list{display:grid;grid-template-columns:1fr;gap:1rem;}
        @media(min-width:760px){.cart-items-list{grid-template-columns:1fr 1fr;}}
        .order-item{background:#0f172a;border:1px solid #334155;border-radius:12px;padding:1rem;color:#e2e8f0;}
        .movie-title{font-size:1.1rem;margin:0 0 .25rem;}
        .meta-line{font-size:.85rem;color:#94a3b8;margin:.25rem 0;}
        .seat-line{display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.35rem;}
        .seat-chip{background:#111827;border:1px solid #475569;border-radius:999px;padding:.25rem .6rem;font-family:ui-monospace,Menlo,monospace;font-size:.85rem;}
        .summary-row{display:flex;justify-content:space-between;align-items:center;margin-top:.6rem;}
        .summary-total{font-weight:700;}
        .actions{margin-top:.8rem;display:flex;gap:.5rem;}
        .btn-main{background:#22c55e;border:1px solid #16a34a;border-radius:10px;color:#052e16;padding:.55rem .8rem;text-decoration:none;}
        .btn-ghost{background:transparent;border:1px solid #334155;border-radius:10px;color:#e2e8f0;padding:.55rem .8rem;text-decoration:none;}
        .empty{color:#94a3b8;text-align:center;padding:2.5rem 1rem;}
    </style>
</head>
<body>
    <header>
        <div id="wrapper">
        <div class="container header_bar">
            <a class="brand" href="index.php">
            <span class="brand_logo" img src="./images/cinema_logo.png"></span>
            <span class="brand_text">
                <strong>CineLux</strong><br />
                <span>Theatre</span>
            </span>
            </a>

        <div id="main_nav">
            <nav>
                <ul>
                <li><a href="#">PROMOTIONS</a></li>
                <?php if (isset($_SESSION['sess_user'])): ?>
                    <li><a href="bookings.php">BOOKINGS</a></li>
                <?php else: ?>
                    <li><a href="login-main.php?redirect=bookings.php">BOOKINGS</a></li>
                <?php endif; ?>
                    <li><a href="#">PROFILE</a></li>
                </ul>
            </nav>
        </div>

        <div>
            <?php if (isset($_SESSION['sess_user'])): ?>
                <span class="welcome_text">
                <a href="cart.php" style="text-decoration: none;">
                🛒
                </a>
                👋 Welcome, <strong><?= e($_SESSION['sess_user']) ?></strong>
                </span>
                <a class="btn btn_ghost" href="logout.php">LOGOUT</a>
            <?php else: ?>
                <a class="btn btn_ghost" href="login-main.php">LOGIN</a>
            <?php endif; ?>
            </div>
        </div>
        </div>
    </header>

    <main>
    <div class="cart-shell">
        <h1 class="cart-title">My Bookings</h1>

        <?php if (empty($groups)): ?>
            <div class="empty">
                <p>You have no bookings yet.</p>
            <div class="actions">
                <a class="btn-main" href="index.php">Browse Movies</a>
            </div>
            </div>
        <?php else: ?>
            <div class="cart-items-list">
                <?php foreach ($groups as $g): ?>
                <article class="order-item">
                <h3 class="movie-title"><?= e($g['MovieTitle']) ?></h3>
                <div class="meta-line">Order #<?= e($g['OrderID']) ?> • Purchased <?= e(date('j M Y, g:i A', strtotime($g['CreatedAt']))) ?></div>
                <div class="meta-line">Date: <?= e(fmt_date($g['ShowDate'])) ?> • Time: <?= e(fmt_time($g['TimeSlot'])) ?> • Hall: <?= e($g['HallID']) ?></div>

            <div class="seat-line">
                <?php foreach ($g['Seats'] as $seat): ?>
                    <span class="seat-chip"><?= e($seat) ?></span>
                <?php endforeach; ?>
                <span class="seat-chip">Qty: <?= e(count($g['Seats'])) ?></span>
            </div>

            <div class="summary-row">
                <span class="meta-line">Total Paid</span>
                <span class="summary-total">$<?= number_format((float)$g['PaidAmount'], 2) ?></span>
            </div>

            <div class="actions">
                <a class="btn-ghost" href="moviedetails.php?id=<?= urlencode($g['MovieCode']) ?>">View Details</a>
                <a class="btn-main" href="index.php">Book Another</a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
</div>
</main>

<footer class="site_footer">
  <div class="container footer_links">
    <a href="index.php">HOME</a>
    <a href="#">CONTACT US</a>
    <a href="jobs.php">JOBS AT CineLux Theatre</a>
  </div>

  <div class="container">
    <hr class="footer_divider" />
  </div>

  <div class="container footer_panels">
    <div class="footer_panel left">
      <div class="panel_title">CONNECT WITH US</div>
<ul class="icon_list" aria-label="Social links">
  <li>
    <a class="icon_btn">
      <img src="./images/fb.svg" alt="Facebook" >
    </a>
  </li>
  <li>
    <a class="icon_btn" aria-label="Twitter / X">
      <img src="./images/x.svg" alt="Twitter | X">
    </a>
  </li>
  <li>
    <a class="icon_btn" aria-label="Instagram">
      <img src="./images/instagram.svg" alt="Instagram">
    </a>
  </li>
  <li>
    <a class="icon_btn" aria-label="TikTok">
      <img src="./images/tiktok.svg" alt="TikTok">
    </a>
  </li>
</ul>
    </div>

    <div class="footer_panel right">
      <div class="panel_title">SUPPORTED PAYMENT</div>
<ul class="icon_list" aria-label="Payment">
  <li>
    <a href="#" class="icon_btn">
      <img src="./images/visa.svg" alt="visa" >
    </a>
  </li>
  <li>
    <a href="#" class="icon_btn" aria-label="mastercard">
      <img src="./images/mastercard.svg" alt="mastercard">
    </a>
  </li>
  <li>
    <a href="#" class="icon_btn" aria-label="cash">
      <img src="./images/cash.svg" alt="cash">
    </a>
  </li>
</ul>
    <small>Credit/Debit Cards and Cash are welcomed</small>
    </div>
  </div>
</footer>
</body>
</html>
