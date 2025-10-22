<?php
include "dbconnect.php";
session_start();

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

/* 2) Fetch showtimes for this movie
      - GROUP BY timeslot so you get unique times (e.g., 09:30 and 17:00)
      - halls: optional list of halls that run this time */
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

/* -- from here render your details page + the timeslot picker form -- */
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
        <a class="brand" href="#">
          <span class="brand_logo">🎬</span>
          <span class="brand_text">
            <strong>CINEMA</strong><br />
            <span>NAME</span>
          </span>
        </a>

        <div id="main_nav">
          <nav>
            <ul>
              <li><a href="movies.php">MOVIES</a></li>
              <li><a href="#">BOOKINGS</a></li>
              <li><a href="#">PROMOTIONS</a></li>
            </ul>
          </nav>
        </div>

        <div>
          <?php if (isset($_SESSION['sess_user'])): ?>
            <span class="welcome_text">
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
        <!-- facts box -->
        <section class="facts_box">
          <div class="facts_row"><span class="facts_label">RELEASE DATE</span><span class="facts_val"><?= e($movie['release_date']) ?></span></div>
          <div class="facts_row"><span class="facts_label">LANGUAGE</span><span class="facts_val"><?= e($movie['language']) ?></span></div>
          <div class="facts_row"><span class="facts_label">RUNNING TIME</span><span class="facts_val"><?= (int)$movie['duration_min'] ?> min</span></div>
          <div class="facts_row"><span class="facts_label">GENRE</span><span class="facts_val"><?= e($movie['genre']) ?></span></div>
          <div class="facts_row"><span class="facts_label">RATING</span><span class="facts_val"><?= e($movie['rating']) ?></span></div>
        </section>

        <!-- synopsis box -->
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
      // Build a short descriptor like "H1 | H5" if you want to show halls next to the time
      $hallSet = [];
foreach ($slots as $s) {
  foreach (array_map('trim', explode(',', $s['halls'])) as $h) {
    if ($h !== '') { $hallSet[$h] = true; }
  }
}
$hallList = array_keys($hallSet);
    ?>
    <div class="ts-meta">
      Available halls: <?= e(implode(' · ', $hallList)) ?>
    </div>

    <!-- Form posts to your booking page -->
    <form method="post" action="booking.php" class="ts-form">
      <input type="hidden" name="movie_id" value="<?= e($movieId) ?>">

      <div class="ts-pills">
        <?php foreach ($slots as $i => $s): 
              // radio id must be unique
              $rid = 'ts_' . $i;
        ?>
          <input type="radio"
                 name="timeslot"
                 id="<?= e($rid) ?>"
                 value="<?= e($s['time_24']) ?>"
                 <?= $i === 0 ? 'checked' : '' ?> required>
          <label for="<?= e($rid) ?>"><?= e($s['time_12']) ?></label>
        <?php endforeach; ?>
      </div>

      <!-- (Optional) let user pick preferred hall if the same movie plays in multiple halls at the same time)
           If you want hall selection too, uncomment:

      <div style="margin-top:10px;">
        <label for="hall">Hall:</label>
        <select id="hall" name="hall_code">
          <?php foreach ($hallList as $h): ?>
            <option value="<?= e($h) ?>"><?= e($h) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      -->

      <div style="margin-top:12px;">
        
      </div>
    </form>
  <?php endif; ?>
</section>
        

        <!-- actions -->
        <div class="details_actions">
          <a class="btn btn-outline" href="index.php">Back to Home</a>
          <button type="submit" class="btn btn-primary">Continue</button>
          <!-- Example booking entry; you can add ?time= later -->
        </div>
      </div>
    </div>

  </div>



</main>

<footer class="site_footer">
  <div class="container footer_links">
    <a href="index.php">HOME</a>
    <a href="#">CONTACT US</a>
    <a href="#">CAREERS</a>
  </div>
  <div class="container"><hr class="footer_divider" /></div>
  <small style="display:block;text-align:center;">© <?= date('Y') ?> Cinema Name</small>
</footer>

</body>
</html>
