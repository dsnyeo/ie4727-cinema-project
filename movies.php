<?php
include "dbconnect.php";
session_start();

/* helper */
function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

/* --- Page options (server-side only) --- */
/* which set to show: all | now | older (defaults to all) */
$section = isset($_GET['section']) ? strtolower($_GET['section']) : 'all';
$where   = "1";
if ($section === 'now')   $where = "Trending = 1";
if ($section === 'older') $where = "Trending = 0";

/* pagination: 4 cards per page to mirror the 2x2 wireframe */
$per_page = 4;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

/* count total */
$sql_count = "SELECT COUNT(*) AS c FROM movies WHERE $where";
$res_c = $dbcnx->query($sql_count);
$total = $res_c ? (int)$res_c->fetch_assoc()['c'] : 0;
$pages = max(1, (int)ceil($total / $per_page));

/* fetch this page */
$sql = "SELECT 
          MovieCode       AS movie_id,
          Title           AS title,
          PosterPath      AS poster_path,
          Genre           AS genre,
          Rating          AS rating,
          DATE_FORMAT(ReleaseDate, '%Y-%m-%d') AS release_date,
          DurationMinutes AS duration_min,
          Language        AS language,
          Trending        AS trending
        FROM movies
        WHERE $where
        ORDER BY Trending DESC, ReleaseDate DESC
        LIMIT $per_page OFFSET $offset";
$res = $dbcnx->query($sql);
$movies = $res && $res->num_rows ? $res->fetch_all(MYSQLI_ASSOC) : [];

/* fixed daily showtimes across halls */
$showtimes = ['09:30','13:30','17:00','20:30'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Movies | Cinema</title>
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

<main class="section" id="movies_page">
  <div class="container">

    <!-- top bar like your wireframe -->
    <div class="page_headbar">
      <button class="btn btn-ghost" type="button">DATE</button>
      <div class="day_tabs">
        <span class="btn btn-ghost">TODAY<br><small>Mon</small></span>
        <span class="btn btn-ghost">TOMORROW<br><small>Tue</small></span>
        <span class="btn btn-ghost">WED<br><small>Wed</small></span>
        <span class="btn btn-ghost">THU<br><small>Thu</small></span>
        <span class="btn btn-ghost">FRI<br><small>Fri</small></span>
        <span class="btn btn-ghost">SAT<br><small>Sat</small></span>
        <span class="btn btn-ghost">SUN<br><small>Sun</small></span>
      </div>
    </div>

    <!-- 2x2 grid -->
    <div class="movies-grid">
      <?php if (empty($movies)): ?>
        <p>No movies found.</p>
      <?php else: ?>
        <?php foreach ($movies as $m): ?>
          <article class="movie_card">
            <div class="poster_box">
              <img src="<?= e($m['poster_path']) ?>" alt="<?= e($m['title']) ?> Poster">
            </div>

            <div>
              <h3 style="margin:0 0 6px 0;"><?= e($m['title']) ?></h3>
              <div class="movie_meta">Rating: <?= e($m['rating']) ?></div>
              <div class="movie_meta">Duration: <?= (int)$m['duration_min'] ?> min</div>

              <div class="movie_meta" style="margin-top:8px;font-weight:600;">TIMING</div>
              <div class="movie_times">
                <?php foreach ($showtimes as $t): ?>
                  <!-- link straight to your booking/details page (pure PHP) -->
                  <a class="btn btn-outline"
                     href="movie.php?id=<?= urlencode($m['movie_id']) ?>&time=<?= urlencode($t) ?>">
                      <?= $t ?>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- pagination (server-side) -->
    <?php if ($pages > 1): ?>
      <nav class="pagination" aria-label="Movie pages">
        <?php
          $base = "movies.php?section=" . urlencode($section) . "&page=";
          for ($p = 1; $p <= $pages; $p++):
            if ($p == $page):
        ?>
          <span class="active"><?= $p ?></span>
        <?php else: ?>
          <a href="<?= $base . $p ?>"><?= $p ?></a>
        <?php
            endif;
          endfor;
        ?>
      </nav>
    <?php endif; ?>

  </div>
</main>

  <!-- Footer -->
<footer class="site_footer">
  <div class="container footer_links">
    <a href="index.php">HOME</a>
    <a href="#">CONTACT US</a>
    <a href="#">JOBS AT &quot;CINEMA NAME&quot;</a>
  </div>

  <!-- thin line across the container -->
  <div class="container">
    <hr class="footer_divider" />
  </div>

  <!-- two panels: left = connect, right = payment -->
  <div class="container footer_panels">
    <div class="footer_panel left">
      <div class="panel_title">CONNECT WITH US</div>
<ul class="icon_list" aria-label="Social links">
  <li>
    <a href="https://www.facebook.com/" class="icon_btn">
      <!-- Facebook / Meta-style "f" -->
      <img src="./images/fb.svg" alt="Facebook" >
    </a>
  </li>
  <li>
    <a href="https://x.com/?lang=en" class="icon_btn" aria-label="Twitter / X">
      <!-- Twitter bird -->
      <img src="./images/x.svg" alt="Twitter | X">
    </a>
  </li>
  <li>
    <a href="https://www.instagram.com/" class="icon_btn" aria-label="Instagram">
      <!-- Instagram camera -->
      <img src="./images/instagram.svg" alt="Instagram">
    </a>
  </li>
  <li>
    <a href="https://www.tiktok.com/en/" class="icon_btn" aria-label="TikTok">
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
    <small>Credit/Debit Cards and Cash are welcomed</small>
    </div>
  </div>
</footer>

</body>
</html>
