<?php
include "dbconnect.php";
session_start();

if (!isset($_SESSION['user_id']) && isset($_SESSION['sess_user'])) {
  include __DIR__ . '/dbconnect.php';
  if (isset($dbcnx) && $dbcnx instanceof mysqli) {
    if ($stmt = $dbcnx->prepare('SELECT UserID FROM users WHERE Username = ? LIMIT 1')) {
      $stmt->bind_param('s', $_SESSION['sess_user']);
      if ($stmt->execute()) {
        $stmt->bind_result($uid);
        if ($stmt->fetch()) {
          $_SESSION['user_id'] = (int)$uid;
        }
      }
      $stmt->close();
    }
  }
}

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

if (!isset($dbcnx) || $dbcnx->connect_errno) {
  die("Database connection not found or failed.");
}

$movieId = $_GET['id'] ?? '';
if (!$movieId) die("Missing movie id.");
$_SESSION['selected_movie_id'] = $movieId;

/* 1) Fetch movie details (prepared) */
$sqlMovie = "SELECT 
  MovieCode AS movie_id, Title AS title, PosterPath AS poster_path,
  Synopsis AS synopsis, Genre AS genre, TicketPrice AS ticket_price,
  Rating AS rating, DATE_FORMAT(ReleaseDate,'%Y-%m-%d') AS release_date,
  DurationMinutes AS duration_min, Language AS language
FROM movies
WHERE MovieCode = ?";

if (!($stmt = $dbcnx->prepare($sqlMovie))) die("Prepare failed (movie).");
$stmt->bind_param("s", $movieId);
$stmt->execute();
$movie = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$movie) die("Movie not found.");
$_SESSION['ticket_price'] = $movie['ticket_price'];


$sqlSlots = "SELECT 
    TIME_FORMAT(timeslot,'%H:%i:%s') AS time_24,
    TIME_FORMAT(timeslot,'%h:%i %p') AS time_12,
    GROUP_CONCAT(hall_code ORDER BY hall_code SEPARATOR ', ') AS halls
  FROM screentime
  WHERE movie_code = ?
  GROUP BY timeslot
  ORDER BY timeslot";

if (!($st = $dbcnx->prepare($sqlSlots))) die("Prepare failed (slots).");
$st->bind_param("s", $movieId);
$st->execute();
$res = $st->get_result();

$slots = [];
while ($r = $res->fetch_assoc()) $slots[] = $r;
$st->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= e($movie['title']) ?> | Movie Details</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>

<!-- Header -->
  <header>
    <div id="wrapper">
      <div class="container header_bar">
        <a class="brand" href="index.php">
          <span class="brand_logo">
            <img src="./images/cinema_logo.png" alt="Cinema Logo" >
          </span>
          <span class="brand_text">
            <strong>CineLux</strong><br />
            <span>Theatre</span>
          </span>
        </a>

        <div id="main_nav">
          <nav>
            <ul>
              <li><a href="promotions.php">PROMOTIONS</a></li>
              <li><a href="bookings.php">BOOKINGS</a></li>
              <li><a href="profile.php">PROFILE</a></li>
              <li><a href="jobs.php">JOBS @ CineLux Theatre</a></li>
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

<main class="section">
  <div class="container">

    <!-- Title row -->
    <div class="details_titlebar">
      <h1 class="movie_title"><?= e($movie['title']) ?></h1>
      <?php if (!empty($movie['trailer_url'])): ?>
        <a class="btn btn-outline trailer_btn" target="_blank" href="<?= e($movie['trailer_url']) ?>">
          TRAILER
        </a>
      <?php endif; ?>
    </div>

    <!-- Main details layout -->
    <div class="details_layout">
      <!-- Poster (left) -->
      <div class="poster_lg">
        <img src="<?= e($movie['poster_path']) ?>" alt="<?= e($movie['title']) ?> Poster">
      </div>

      <!-- Right column -->
      <div class="details_right">

        <section class="facts_box">
          <div class="facts_row"><span class="facts_label">RELEASE DATE</span><span class="facts_val"><?= e($movie['release_date']) ?></span></div>
          <div class="facts_row"><span class="facts_label">LANGUAGE</span><span class="facts_val"><?= e($movie['language']) ?></span></div>
          <div class="facts_row"><span class="facts_label">RUNNING TIME</span><span class="facts_val"><?= (int)$movie['duration_min'] ?> min</span></div>
          <div class="facts_row"><span class="facts_label">GENRE</span><span class="facts_val"><?= e($movie['genre']) ?></span></div>
          <div class="facts_row"><span class="facts_label">RATING</span><span class="facts_val"><?= e($movie['rating']) ?></span></div>
        </section>

        <section class="synopsis_box">
          <h3>SYNOPSIS</h3>
          <p><?= nl2br(e($movie['synopsis'])) ?></p>
        </section>

        <!-- ===== Timeslot panel (UI) ===== -->
<section class="timeslot_panel" aria-labelledby="ts-title">
  <div class="timeslot_head">
    <h3 id="ts-title" style="margin:0;"> 🕒 Choose your preferred timeslot</h3>
  </div>
  <div class="timeslot_divider"></div>

  <?php if (empty($slots)): ?>
    <p class="ts-meta">No sessions are scheduled for this title.</p>
  <?php else: ?>
    <!-- Optional helper text (first two, or show halls) -->
    <?php
      $hallSet = [];
      foreach ($slots as $s) {
        foreach (array_map('trim', explode(',', $s['halls'])) as $h) {
          if ($h !== '') { $hallSet[$h] = true; }
        }
      }
      $hallList   = array_keys($hallSet);
      $singleHall = $hallList[0] ?? ''; // the only hall (or empty if none found)
    ?>

    <div class="ts-meta">
      Hall: <?= e($singleHall ?: 'TBC') ?>
    </div>

    <!-- Form posts to your booking page -->
    <form method="post" action="proceed2seats.php" class="ts-form">
      <!-- Required: pass movie id & title forward -->
      <input type="hidden" name="movie_id" value="<?= e($movieId) ?>">
      <input type="hidden" name="movie_title" value="<?= e($movie['title']) ?>">

      <!-- Date -->
      <div class="ts-row" style="margin:.75rem 0;">
        <label for="show_date" style="margin-right:.5rem;font-weight:600;">Date:</label>
        <input type="date" id="show_date" name="show_date"
              value="<?= e(date('Y-m-d')) ?>" required>
      </div>
      
      <input type="hidden" name="hall_id" value="<?= e($singleHall) ?>">


      <!-- Timeslots as radio pills -->
      <div class="ts-pills" style="display:flex;gap:.5rem;flex-wrap:wrap;margin:.75rem 0;">
        <?php foreach ($slots as $i => $s): $rid = 'ts_' . $i; ?>
          <input type="radio"
                name="timeslot"
                id="<?= e($rid) ?>"
                value="<?= e($s['time_24']) ?>"
                <?= $i === 0 ? 'checked' : '' ?> required>
          <label for="<?= e($rid) ?>" class="pill"><?= e($s['time_12']) ?></label>
        <?php endforeach; ?>
      </div>

      <div class="details_actions">
        <a class="btn btn-outline" href="index.php">Back to Home</a>
        <button type="submit" class="btn btn-primary" id="btn-continue">Continue</button>
      </div>
    </form>
  <?php endif; ?>
</section>
        
        <!-- actions -->
        
      </div>
    </div>
  </div>
</main>

<footer class="site_footer">

  <!-- two panels: left = connect, right = payment -->
  <div class="container footer_panels">
    <div class="footer_panel left">
      <div class="panel_title">CONNECT WITH US</div>
<ul class="icon_list" aria-label="Social links">
  <li>
    <a class="icon_btn">
      <!-- Facebook / Meta-style "f" -->
      <img src="./images/fb.svg" alt="Facebook" >
    </a>
  </li>
  <li>
    <a class="icon_btn" aria-label="Twitter / X">
      <!-- Twitter bird -->
      <img src="./images/x.svg" alt="Twitter | X">
    </a>
  </li>
  <li>
    <a class="icon_btn" aria-label="Instagram">
      <!-- Instagram camera -->
      <img src="./images/instagram.svg" alt="Instagram">
    </a>
  </li>
  <li>
    <a class="icon_btn" aria-label="TikTok">
      <!-- TikTok note -->
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
      <!-- Facebook / Meta-style "f" -->
      <img src="./images/visa.svg" alt="visa" >
    </a>
  </li>
  <li>
    <a href="#" class="icon_btn" aria-label="mastercard">
      <!-- Twitter bird -->
      <img src="./images/mastercard.svg" alt="mastercard">
    </a>
  </li>
  <li>
    <a href="#" class="icon_btn" aria-label="cash">
      <!-- Instagram camera -->
      <img src="./images/cash.svg" alt="cash">
    </a>
  </li>
</ul>
    
    </div>
  </div>
</footer>

<script>
//Validate date
const show_date = document.getElementById('show_date');
const form      = document.querySelector('.ts-form');

function dateOk(iso) {
  if (!iso) return false;
  const now = new Date();
  const yyyy = now.getFullYear();
  const mm   = String(now.getMonth() + 1).padStart(2, '0');
  const dd   = String(now.getDate()).padStart(2, '0');
  const today = `${yyyy}-${mm}-${dd}`;
  return iso > today;  // disallow today and past
}

function validateShowDate(event) {
  const value = show_date.value;
  if (!value) {
    alert("Please select a date.");
    event.preventDefault();
    return false;
  }

  if (!dateOk(value)) {
    alert("You cannot select today or a past date.");
    event.preventDefault();
    show_date.focus();
    return false;
  }
  return true;
}

// Hook into form submission
if (form) {
  form.addEventListener("submit", validateShowDate);
}

(function () {
  // login flag from PHP
  var IS_LOGGED_IN = <?php echo json_encode(isset($_SESSION['user_id'])); ?>;
  // exact current URL (path + query), e.g. "moviedetails.php?id=123"
  var RETURN_URL = <?php echo json_encode($_SERVER['REQUEST_URI']); ?>;

  function onReady(fn){ if (document.readyState !== 'loading') fn(); else document.addEventListener('DOMContentLoaded', fn); }

  onReady(function () {
    var btn = document.getElementById('btn-continue');
    if (!btn) return;
    var form = btn.closest('form');

    function guard(e) {
      if (!IS_LOGGED_IN) {
        e.preventDefault();
        alert('You need to be logged in to continue.');
        // send them back to the SAME moviedetails page
        window.location.assign(RETURN_URL);
        return false;
      }
    }

    btn.addEventListener('click', guard);
    if (form) form.addEventListener('submit', guard); // covers Enter key
  });
})();
</script>

</body>
</html>
