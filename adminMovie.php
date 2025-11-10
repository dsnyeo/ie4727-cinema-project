<?php
session_start();
include "dbconnect.php";

/* --- SIMPLE ADMIN CHECK (adjust as needed) --- */
if (!isset($_SESSION['sess_user'])) {
    die("Access denied. Please log in as admin.");
}

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

if (!isset($dbcnx) || $dbcnx->connect_errno) {
    die("Database connection not found or failed.");
}

$errors = [];
$messages = [];

/* --- FETCH ALL MOVIES (for sidebar list) --- */
$movies = [];
$sql_all = "SELECT 
              MovieCode,
              Title,
              Trending,
              DATE_FORMAT(ReleaseDate, '%Y-%m-%d') AS release_date
            FROM movies
            ORDER BY Trending DESC, Title ASC";
if ($res = $dbcnx->query($sql_all)) {
    while ($row = $res->fetch_assoc()) {
        $movies[] = $row;
    }
    $res->close();
} else {
    $errors[] = "Failed to fetch movies list: " . e($dbcnx->error);
}

/* --- DETERMINE SELECTED MOVIE CODE --- */
$selectedCode = $_POST['selected_code'] ?? ($_GET['edit'] ?? '');
if ($selectedCode === '' && !empty($movies)) {
    $selectedCode = $movies[0]['MovieCode']; // default to first
}

/* --- HANDLE UPDATE SUBMISSION --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_movie') {
    $movieCode  = trim($_POST['movie_code'] ?? '');
    $title      = trim($_POST['title'] ?? '');
    $posterPath = trim($_POST['poster_path'] ?? '');
    $synopsis   = trim($_POST['synopsis'] ?? '');
    $genre      = trim($_POST['genre'] ?? '');
    $ticket     = trim($_POST['ticket_price'] ?? '');
    $rating     = trim($_POST['rating'] ?? '');
    $release    = trim($_POST['release_date'] ?? '');
    $duration   = trim($_POST['duration_min'] ?? '');
    $language   = trim($_POST['language'] ?? '');
    $trending   = isset($_POST['trending']) && $_POST['trending'] == '1' ? 1 : 0;

    if ($movieCode === '' || $title === '' || $posterPath === '' || $ticket === '' || $release === '' || $duration === '') {
        $errors[] = "Please fill in all required fields (*).";
    }

    if ($ticket !== '' && !is_numeric($ticket)) {
        $errors[] = "Ticket price must be numeric.";
    }

    if ($duration !== '' && !ctype_digit($duration)) {
        $errors[] = "Duration must be a whole number in minutes.";
    }

    if (empty($errors)) {
        $sql_upd = "UPDATE movies SET
                        Title = ?,
                        PosterPath = ?,
                        Synopsis = ?,
                        Genre = ?,
                        TicketPrice = ?,
                        Rating = ?,
                        ReleaseDate = ?,
                        DurationMinutes = ?,
                        Language = ?,
                        Trending = ?
                    WHERE MovieCode = ?";

        if ($stmt = $dbcnx->prepare($sql_upd)) {
            $ticket_f   = (float)$ticket;
            $duration_i = (int)$duration;
            $stmt->bind_param(
                "ssssdssisis",
                $title,
                $posterPath,
                $synopsis,
                $genre,
                $ticket_f,
                $rating,
                $release,
                $duration_i,
                $language,
                $trending,
                $movieCode
            );

            if ($stmt->execute()) {
                if ($stmt->affected_rows >= 0) {
                    $messages[] = "Movie \"{$movieCode}\" updated successfully.";
                    $selectedCode = $movieCode;
                } else {
                    $errors[] = "No changes were made.";
                }
            } else {
                $errors[] = "Update failed: " . e($stmt->error);
            }
            $stmt->close();
        } else {
            $errors[] = "Prepare failed: " . e($dbcnx->error);
        }
    }
}


/* --- FETCH SELECTED MOVIE DETAILS FOR FORM --- */
$selected = null;
if ($selectedCode !== '') {
    $sql_one = "SELECT 
                  MovieCode,
                  Title,
                  PosterPath,
                  Synopsis,
                  Genre,
                  TicketPrice,
                  Rating,
                  DATE_FORMAT(ReleaseDate, '%Y-%m-%d') AS release_date,
                  DurationMinutes,
                  Language,
                  Trending
                FROM movies
                WHERE MovieCode = ?
                LIMIT 1";
    if ($stmt = $dbcnx->prepare($sql_one)) {
        $stmt->bind_param("s", $selectedCode);
        $stmt->execute();
        $res = $stmt->get_result();
        $selected = $res->fetch_assoc();
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>CineLux Admin - Update Movies</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="styles.css">

  <style>
    body {
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background:#05060a;
      color:#f5f5f5;
    }

/* === Admin Navigation Bar === */
.admin-nav {
  background: #0c0c14;
  border-bottom: 1px solid #2c2c3e;
  padding: 0.6rem 0;
  position: sticky;
  top: 0;
  z-index: 999;
}

.admin-nav-container {
  max-width: 1150px;
  margin: 0 auto;
  padding: 0 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.admin-brand {
  font-size: 1.1rem;
  color: #f5b301;
  letter-spacing: .04em;
}

.admin-links a {
  margin-left: 1.2rem;
  color: #cfd0e0;
  text-decoration: none;
  font-size: 0.9rem;
  padding: 0.3rem 0.5rem;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.admin-links a:hover {
  color: #fff;
  background: #2a2a44;
}

.admin-links a.active {
  color: #111;
  background: #f5b301;
  font-weight: 600;
}


    .admin-wrapper {
      max-width: 1150px;
      margin: 2rem auto;
      padding: 1.5rem;
      background:#12121c;
      border-radius:14px;
      box-shadow:0 10px 25px rgba(0,0,0,0.6);
    }
    .admin-header {
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      gap:1rem;
      margin-bottom:1.25rem;
    }
    .badge {
      display:inline-block;
      padding:.2rem .6rem;
      border-radius:999px;
      font-size:.7rem;
      background:#23263a;
      color:#9ad0ff;
      text-transform:uppercase;
      letter-spacing:.06em;
    }
    h1 {
      margin:.25rem 0 .4rem;
      font-size:1.5rem;
    }
    p.sub {
      margin:0;
      color:#9a9ab5;
      font-size:.9rem;
    }


    .btn-primary {
      background:#f5b301;
      color:#111;
      font-weight:600;
    }

    .msg, .err {
      padding:.6rem .9rem;
      border-radius:8px;
      font-size:.85rem;
      margin-bottom:.35rem;
    }
    .msg { background:#133019; color:#9be7a4; border:1px solid #1e7a2e; }
    .err { background:#3a1115; color:#ffb3b8; border:1px solid #92252e; }
    .layout {
      display:grid;
      grid-template-columns: 260px minmax(0, 1fr);
      gap:1.25rem;
      margin-top:1rem;
    }
    .card {
      background:#171725;
      border-radius:12px;
      padding:.8rem .9rem;
      border:1px solid #26263a;
    }
    .card h2 {
      font-size:.98rem;
      margin:0 0 .4rem;
    }
    .card small {
      display:block;
      margin-bottom:.4rem;
      color:#8f90aa;
      font-size:.76rem;
    }
    .movie-list {
      max-height:460px;
      overflow-y:auto;
      margin-top:.3rem;
    }
    .movie-item {
      padding:.3rem .4rem;
      border-radius:6px;
      font-size:.8rem;
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:.3rem;
      cursor:pointer;
      border:1px solid transparent;
      margin-bottom:.2rem;
    }
    .movie-item span.code {
      font-family:monospace;
      color:#9ad0ff;
      font-size:.75rem;
    }
    .movie-item span.badge-mini {
      font-size: .65rem;
    padding: .1rem .4rem;
    border-radius: 999px;
    border: 1px solid #3a3a5c;
    white-space: nowrap;          /* <— Prevents text split */
    display: inline-block;        /* <— Ensures it's treated as a single unit */
    line-height: 1;
    }
    .movie-item.now span.badge-mini {
      color:#0fd4a3;
      border-color:#0fd4a3;
    }
    .movie-item.old span.badge-mini {
      color:#f5b301;
      border-color:#f5b301;
    }
    .movie-item.active {
      background:#202036;
      border-color:#f5b301;
    }
    label {
      display:block;
      font-size:.78rem;
      margin-top:.35rem;
      margin-bottom:.1rem;
      color:#c9c9df;
    }
    input[type="text"],
    input[type="number"],
    input[type="date"],
    textarea {
      width:100%;
      padding:.4rem .5rem;
      border-radius:6px;
      border:1px solid #33354a;
      background:#0d0e16;
      color:#f5f5ff;
      font-size:.82rem;
    }
    textarea { resize:vertical; min-height:70px; }
    .radio-row {
      display:flex;
      gap:1rem;
      margin-top:.15rem;
      font-size:.78rem;
    }
    .hint {
      font-size:.74rem;
      color:#8f90aa;
      margin-top:.1rem;
    }
    .strong-note {
      font-size:.78rem;
      color:#f5b301;
      margin-top:.15rem;
    }
    .two-cols {
      display:grid;
      grid-template-columns: repeat(2, minmax(0,1fr));
      gap:.5rem;
    }
  </style>
</head>
<body>
<!-- Admin Navigation Bar -->
<header class="admin-nav">
  <div class="admin-nav-container">
    <div class="admin-brand">
      <strong>CineLux</strong> Admin Panel
    </div>
    <nav class="admin-links">
      <a href="adminMovies.php" class="<?= basename($_SERVER['PHP_SELF']) === 'adminMovie.php' ? 'active' : '' ?>">Movies</a>
      <a href="adminReport.php" class="<?= basename($_SERVER['PHP_SELF']) === 'adminReport.php' ? 'active' : '' ?>">Reports</a>
      <a href="adminScreentime.php" class="<?= basename($_SERVER['PHP_SELF']) === 'adminScreentime.php' ? 'active' : '' ?>">Screentime</a>
      <a href="logout.php"class="btn btn-outline" onclick="return confirm('Log out of admin panel?');">Logout</a>
    </nav>
  </div>
</header>

<div class="admin-wrapper">
  <div class="admin-header">
    <div>
      <div class="badge">CineLux Admin</div>
      <h1>Update Movies</h1>
      <p class="sub">
        Your cinema is limited to 12 movies. Use this panel to update details
        and control whether each movie appears in <strong>NOW SHOWING</strong> (Trending = 1)
        or <strong>OLDER MOVIES</strong> (Trending = 0) on the homepage.
      </p>
    </div>

  </div>

  <?php foreach ($messages as $m): ?>
    <div class="msg"><?= e($m) ?></div>
  <?php endforeach; ?>

  <?php foreach ($errors as $er): ?>
    <div class="err"><?= e($er) ?></div>
  <?php endforeach; ?>

  <div class="layout">
    <!-- LEFT: MOVIE LIST -->
    <div class="card">
      <h2>Movies (max 12)</h2>
      <small>Select a movie to edit its details.</small>
      <?php if (count($movies) > 12): ?>
        <div class="strong-note">
          Note: There are currently <?= count($movies) ?> movies. Please keep only 12 active entries.
        </div>
      <?php endif; ?>
      <div class="movie-list">
        <?php if (empty($movies)): ?>
          <p style="font-size:.8rem;color:#888;">No movies found in database.</p>
        <?php else: ?>
          <?php foreach ($movies as $mv):
              $isActive = ($mv['MovieCode'] === $selectedCode);
              $cls = 'movie-item ' . ($mv['Trending'] ? 'now' : 'old') . ($isActive ? ' active' : '');
          ?>
            <div class="<?= $cls ?>"
                 onclick="selectMovie('<?= e($mv['MovieCode']) ?>')">
              <div>
                <strong><?= e($mv['Title']) ?></strong><br>
                <span class="code"><?= e($mv['MovieCode']) ?></span>
              </div>
              <div>
                <span class="badge-mini">
                  <?= $mv['Trending'] ? 'Now Showing' : 'Older' ?>
                </span>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- RIGHT: UPDATE FORM -->
    <div class="card">
      <h2>Edit Movie Details</h2>
      <?php if (!$selected): ?>
        <p style="font-size:.8rem;color:#888;">Select a movie on the left to edit.</p>
      <?php else: ?>
        <form method="post">
          <input type="hidden" name="action" value="update_movie">
          <input type="hidden" name="movie_code" value="<?= e($selected['MovieCode']) ?>">
          <input type="hidden" id="selected_code" name="selected_code" value="<?= e($selected['MovieCode']) ?>">

          <div class="two-cols">
            <div>
              <label>Movie Code *</label>
              <input type="text" value="<?= e($selected['MovieCode']) ?>" disabled>
              <div class="hint">Primary key; not editable.</div>
            </div>
            <div>
              <label>Trending (Section) *</label>
              <div class="radio-row">
                <label>
                  <input type="radio" name="trending" value="1"
                    <?= $selected['Trending'] == 1 ? 'checked' : '' ?>>
                  Now Showing
                </label>
                <label>
                  <input type="radio" name="trending" value="0"
                    <?= $selected['Trending'] == 0 ? 'checked' : '' ?>>
                  Older Movies
                </label>
              </div>
            </div>
          </div>

          <label for="title">Title *</label>
          <input type="text" id="title" name="title"
                 value="<?= e($selected['Title']) ?>" required>

          <label for="poster_path">Poster Path (e.g. images/posters/xxx.jpg) *</label>
          <input type="text" id="poster_path" name="poster_path"
                 value="<?= e($selected['PosterPath']) ?>" required>

          <label for="synopsis">Synopsis</label>
          <textarea id="synopsis" name="synopsis"><?= e($selected['Synopsis']) ?></textarea>

          <div class="two-cols">
            <div>
              <label for="genre">Genre</label>
              <input type="text" id="genre" name="genre"
                     value="<?= e($selected['Genre']) ?>">
            </div>
            <div>
              <label for="ticket_price">Ticket Price (e.g. 8.00) *</label>
              <input type="number" step="0.01" id="ticket_price" name="ticket_price"
                     value="<?= e($selected['TicketPrice']) ?>" required>
            </div>
          </div>

          <div class="two-cols">
            <div>
              <label for="rating">Rating (e.g. PG13, NC16)</label>
              <input type="text" id="rating" name="rating"
                     value="<?= e($selected['Rating']) ?>">
            </div>
            <div>
              <label for="release_date">Release Date *</label>
              <input type="date" id="release_date" name="release_date"
                     value="<?= e($selected['release_date']) ?>" required>
            </div>
          </div>

          <div class="two-cols">
            <div>
              <label for="duration_min">Duration (minutes) *</label>
              <input type="number" id="duration_min" name="duration_min"
                     value="<?= e($selected['DurationMinutes']) ?>" required>
            </div>
            <div>
              <label for="language">Language</label>
              <input type="text" id="language" name="language"
                     value="<?= e($selected['Language']) ?>">
            </div>
          </div>

          <div style="margin-top:.85rem;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
// when clicking on a movie in the sidebar, reload page for that movie
function selectMovie(code) {
  if (!code) return;
  // simple GET reload with ?edit=
  window.location.href = 'adminMovie.php?edit=' + encodeURIComponent(code);
}
</script>

</body>
</html>