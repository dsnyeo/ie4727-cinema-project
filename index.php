<?php
include "dbconnect.php";
session_start();

/* --- safety check for db connection --- */
if (!isset($dbcnx) || $dbcnx->connect_errno) {
  die("Database connection not found or failed.");
}

/* --- Fetch 6 trending movies (NOW SHOWING) --- */
$sql_now = "SELECT 
              MovieCode AS movie_id,
              Title AS title,
              PosterPath AS poster_path,
              Synopsis AS synopsis,
              Genre AS genre,
              TicketPrice AS ticket_price,
              Rating AS rating,
              DATE_FORMAT(ReleaseDate, '%Y-%m-%d') AS release_date,
              DurationMinutes AS duration_min,
              Language AS language
            FROM movies
            WHERE Trending = 1
            ORDER BY ReleaseDate DESC
            LIMIT 6";

$res_now = $dbcnx->query($sql_now);
$now_movies = $res_now && $res_now->num_rows > 0 ? $res_now->fetch_all(MYSQLI_ASSOC) : [];

/* --- Fetch 6 older movies (Trending = 0) --- */
$sql_old = "SELECT 
              MovieCode AS movie_id,
              Title AS title,
              PosterPath AS poster_path,
              Synopsis AS synopsis,
              Genre AS genre,
              TicketPrice AS ticket_price,
              Rating AS rating,
              DATE_FORMAT(ReleaseDate, '%Y-%m-%d') AS release_date,
              DurationMinutes AS duration_min,
              Language AS language
            FROM movies
            WHERE Trending = 0
            ORDER BY ReleaseDate DESC
            LIMIT 6";

$res_old = $dbcnx->query($sql_old);
$old_movies = $res_old && $res_old->num_rows > 0 ? $res_old->fetch_all(MYSQLI_ASSOC) : [];

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Cinema Website</title>
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
              <li><a href="#">MOVIES</a></li>
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

  <main>
    <!-- NOW SHOWING -->
    <section class="section">
      <div class="container">
        <h2 class="section_title">NOW SHOWING</h2>
        <div class="card_grid">
          <?php if (empty($now_movies)): ?>
            <p>No trending movies right now.</p>
          <?php else: ?>
            <?php foreach ($now_movies as $m): ?>
              <article class="card">
                <div class="poster">
                  <img src="<?= e($m['poster_path']) ?>" alt="<?= e($m['title']) ?> Poster" />
                  <div class="overlay">
                    <h3 class="card_title"><?= e($m['title']) ?></h3>
                    <ul class="card_meta">
                      <li>Duration: <?= (int)$m['duration_min'] ?> min</li>
                      <li>Rating: <?= e($m['rating']) ?></li>
                      <li>Release: <?= e($m['release_date']) ?></li>
                    </ul>
                    <a class="btn btn_outline" href="movie.php?id=<?= urlencode($m['movie_id']) ?>">BUY TICKETS</a>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- OLDER MOVIES -->
    <section class="section">
      <div class="container">
        <h2 class="section_title">OLDER MOVIES</h2>
        <div class="card_grid">
          <?php if (empty($old_movies)): ?>
            <p>No older movies found.</p>
          <?php else: ?>
            <?php foreach ($old_movies as $m): ?>
              <article class="card">
                <div class="poster">
                  <img src="<?= e($m['poster_path']) ?>" alt="<?= e($m['title']) ?> Poster" />
                  <div class="overlay">
                    <h3 class="card_title"><?= e($m['title']) ?></h3>
                    <ul class="card_meta">
                      <li>Duration: <?= (int)$m['duration_min'] ?> min</li>
                      <li>Rating: <?= e($m['rating']) ?></li>
                      <li>Release: <?= e($m['release_date']) ?></li>
                    </ul>
                    <a class="btn btn_outline" href="movie.php?id=<?= urlencode($m['movie_id']) ?>">VIEW DETAILS</a>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </main>

  <footer class="site_footer">
    <div class="container footer_links">
      <a href="#">HOME</a>
      <a href="#">CONTACT US</a>
      <a href="#">JOBS AT &quot;CINEMA NAME&quot;</a>
    </div>
    <div class="container"><hr class="footer_divider" /></div>
    <small style="display:block;text-align:center;">© 2025 Cinema Name</small>
  </footer>
</body>
</html>
