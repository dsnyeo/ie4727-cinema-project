<?php
session_start();
include "dbconnect.php";

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

if (!isset($dbcnx) || $dbcnx->connect_errno) {
    die("Database connection not found or failed.");
}

if (!isset($_SESSION['sess_user']) || strtolower($_SESSION['sess_user']) !== 'admin') {
    die("Access denied. Admins only.");
}

$successMsg = "";
$errorMsg   = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $hall_code  = $_POST['hall_code'] ?? '';
    $timeslot   = $_POST['timeslot'] ?? '';
    $movie_code = $_POST['movie_code'] ?? '';

    if ($hall_code && $timeslot && $movie_code) {
        $sql = "UPDATE screentime 
                SET movie_code = ?
                WHERE hall_code = ? AND timeslot = ?";
        $stmt = $dbcnx->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sss", $movie_code, $hall_code, $timeslot);
            if ($stmt->execute()) {
                $successMsg = "Screentime for Hall {$hall_code} at {$timeslot} updated successfully.";
            } else {
                $errorMsg = "Failed to update screentime: " . e($stmt->error);
            }
            $stmt->close();
        } else {
            $errorMsg = "Database error (prepare failed).";
        }
    } else {
        $errorMsg = "Missing hall, timeslot, or movie code.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $new_hall  = $_POST['new_hall_code'] ?? '';
    $new_time  = $_POST['new_timeslot'] ?? '';
    $new_movie = $_POST['new_movie_code'] ?? '';

    if ($new_hall && $new_time && $new_movie) {
        $sql = "INSERT INTO screentime (hall_code, timeslot, movie_code) VALUES (?, ?, ?)";
        $stmt = $dbcnx->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sss", $new_hall, $new_time, $new_movie);
            if ($stmt->execute()) {
                $successMsg = "New screentime added for Hall {$new_hall} at {$new_time}.";
            } else {
                $errorMsg = "Failed to add screentime: " . e($stmt->error);
            }
            $stmt->close();
        } else {
            $errorMsg = "Database error (prepare failed).";
        }
    } else {
        $errorMsg = "Please fill in Hall, Timeslot, and Movie Code for the new screentime.";
    }
}

$movies = [];
$movieSql = "SELECT MovieCode, Title AS MovieTitle FROM movies ORDER BY Title";
$movieRes = $dbcnx->query($movieSql);
if ($movieRes && $movieRes->num_rows > 0) {
    while ($row = $movieRes->fetch_assoc()) {
        $movies[] = $row;
    }
}

$screentimeSql = "
    SELECT s.hall_code, s.timeslot, s.movie_code, m.Title AS MovieTitle
    FROM screentime s
    LEFT JOIN movies m ON s.movie_code = m.MovieCode
    ORDER BY s.hall_code, s.timeslot
";
$screentimeRes = $dbcnx->query($screentimeSql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Screentime</title>
    <style>
    body {
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background:#05060a;
      color:#f5f5f5;
    }
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

    select, input[type="text"], input[type="time"] {
      background:#10101a;
      border:1px solid #2c2c3e;
      color:#f5f5f5;
      padding:.3rem .4rem;
      border-radius:6px;
      font-size:.8rem;
    }
    select:focus, input:focus {
      outline:none;
      border-color:#f5b301;
      box-shadow:0 0 0 1px rgba(245,179,1,0.18);
    }
    .btn {
      padding:.35rem .7rem;
      cursor:pointer;
      border:none;
      border-radius:6px;
      font-size:.78rem;
      background:#f5b301;
      color:#111;
      font-weight:600;
      transition:.2s;
    }
    .btn:hover {
      background:#ffcb47;
      transform:translateY(-1px);
    }

    .msg {
      margin-bottom:10px;
      padding:.5rem .7rem;
      border-radius:8px;
      font-size:.8rem;
    }
    .success {
      background:rgba(63, 180, 98, 0.12);
      border:1px solid #3fb462;
      color:#b5f1c9;
    }
    .error {
      background:rgba(255, 71, 87, 0.1);
      border:1px solid #ff4757;
      color:#ffb3bb;
    }

    form.inline-form {
      margin:0;
      display:flex;
      gap:.35rem;
      align-items:center;
      flex-wrap:wrap;
    }
    </style>
</head>
<body>

<header class="admin-nav">
  <div class="admin-nav-container">
    <div class="admin-brand">CineLux Admin</div>
    <nav class="admin-links">
      <a href="adminMovie.php">Movies</a>
      <a href="adminReport.php">Report</a>
      <a href="adminScreentime.php" class="active">Screentime</a>
      <a href="logout.php" onclick="return confirm('Log out of admin panel?');">Logout</a>
    </nav>
  </div>
</header>

<div class="admin-wrapper">
    <h1>Screentime Configuration</h1>
    <p class="sub">Manage hall timeslots and assign movies to each screening session.</p>

    <?php if ($successMsg): ?>
        <div class="msg success"><?php echo e($successMsg); ?></div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div class="msg error"><?php echo e($errorMsg); ?></div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <h2>Add New Screentime</h2>
            <small>Create a new (Hall, Timeslot) entry and bind it to a movie.</small>

            <form method="post">
                <input type="hidden" name="action" value="add">

                <div style="margin-bottom:.45rem;">
                    <label>
                        Hall Code<br>
                        <input type="text" name="new_hall_code" maxlength="4" required>
                    </label>
                </div>

                <div style="margin-bottom:.45rem;">
                    <label>
                        Timeslot<br>
                        <input type="time" name="new_timeslot" required>
                    </label>
                </div>

                <div style="margin-bottom:.6rem;">
                    <label>
                        Movie<br>
                        <select name="new_movie_code" required>
                            <option value="">-- Select Movie --</option>
                            <?php foreach ($movies as $m): ?>
                                <option value="<?php echo e($m['MovieCode']); ?>">
                                    <?php echo e($m['MovieCode'] . " - " . $m['MovieTitle']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>

                <button type="submit" class="btn">Add Screentime</button>
            </form>
        </div>

        <div class="card">
            <h2>Existing Screentimes</h2>
            <small>Update assigned movies for current hall & timeslot combinations.</small>

            <?php if ($screentimeRes && $screentimeRes->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Hall</th>
                            <th>Timeslot</th>
                            <th>Current Movie</th>
                            <th>Update</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $screentimeRes->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo e($row['hall_code']); ?></td>
                            <td><?php echo e(substr($row['timeslot'], 0, 5)); ?></td>
                            <td>
                                <?php
                                    echo e($row['movie_code']);
                                    echo $row['MovieTitle']
                                        ? " - " . e($row['MovieTitle'])
                                        : " (No title found)";
                                ?>
                            </td>
                            <td>
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="hall_code" value="<?php echo e($row['hall_code']); ?>">
                                    <input type="hidden" name="timeslot" value="<?php echo e($row['timeslot']); ?>">

                                    <select name="movie_code" required>
                                        <option value="">Select</option>
                                        <?php foreach ($movies as $m): ?>
                                            <option value="<?php echo e($m['MovieCode']); ?>"
                                                <?php if ($m['MovieCode'] == $row['movie_code']) echo 'selected'; ?>>
                                                <?php echo e($m['MovieCode'] . " - " . $m['MovieTitle']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <button type="submit" class="btn">Save</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color:#9a9ab5;font-size:.82rem;margin-top:.4rem;">
                    No screentime records found. Use the form on the left to create one.
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
