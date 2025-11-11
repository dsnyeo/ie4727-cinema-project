<?php
session_start();
include "dbconnect.php";

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// ---------------------------------------------------
// 1. Base values (from when user first picked a show)
// ---------------------------------------------------

if (empty($_SESSION['booking'])) {
    header("Location: index.php");
    exit;
}

// pull what we stored earlier in the booking session
$b = $_SESSION['booking'];

$movieId     = $b['movie_id']     ?? '';
$movieTitle  = $b['movie_title']  ?? '';
$hallId      = $b['hall_id']      ?? '';
$showDate    = $b['show_date']    ?? '';
$timeslot    = $b['timeslot']     ?? '';     // "HH:MM:SS" for DB
$timeslot12  = $b['timeslot_12']  ?? '';     // "7:30 PM"

// seat map layout
$rows       = ['A','B','C','D','E'];
$colsLeft   = ['1','2','3','4'];  // seats block 1
$colsRight  = ['5','6','7','8'];  // seats block 2

// ---------------------------------------------------
// 2. Check if we're editing an existing cart item
//    (user clicked "Edit seats" in cart.php)
// ---------------------------------------------------

$isEditMode        = false;
$editingIndex      = null;
$preSelectedSeats  = [];  // seats we want to pre-highlight for user

if (!empty($_GET['edit']) && !empty($_SESSION['edit_target'])) {
    $isEditMode = true;

    $editingIndex      = $_SESSION['edit_target']['cart_index']  ?? null;
    $preSelectedSeats  = $_SESSION['edit_target']['prev_seats']  ?? [];

    // OVERWRITE the show details with the exact cart item details,
    // so we show them the correct hall/date/timeslot they are editing
    if (!empty($_SESSION['edit_target']['hall_id'])) {
        $hallId = $_SESSION['edit_target']['hall_id'];
    }
    if (!empty($_SESSION['edit_target']['show_date'])) {
        $showDate = $_SESSION['edit_target']['show_date'];
    }
    if (!empty($_SESSION['edit_target']['timeslot'])) {
        // NOTE: we added 'timeslot' (24h format) into edit_target in edit_item.php
        $timeslot = $_SESSION['edit_target']['timeslot'];
    }
    if (!empty($_SESSION['edit_target']['timeslot12'])) {
        $timeslot12 = $_SESSION['edit_target']['timeslot12'];
    }
    if (!empty($_SESSION['edit_target']['movie_id'])) {
        $movieId = $_SESSION['edit_target']['movie_id'];
    }

    // We didn't store movie_title in edit_target, but we *can* pull it from cart
    if ($editingIndex !== null &&
        isset($_SESSION['cart'][$editingIndex]['movie_title'])) {
        $movieTitle = $_SESSION['cart'][$editingIndex]['movie_title'];
    }
}

// ---------------------------------------------------
// 3. Query all seats already booked for THIS hall/date/timeslot
// ---------------------------------------------------

$bookedSeats = [];

$sqlBooked = "SELECT SeatCode 
              FROM tickets 
              WHERE HallID = ?
                AND ShowDate = ?
                AND TimeSlot = ?";

if ($stmt = $dbcnx->prepare($sqlBooked)) {
    $stmt->bind_param("sss", $hallId, $showDate, $timeslot);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $bookedSeats[] = $row['SeatCode']; // e.g. "A1"
    }
    $stmt->close();
}

// ---------------------------------------------------
// 4. Now you have ready-to-use variables for the HTML below:
//    $movieId, $movieTitle, $hallId, $showDate, $timeslot, $timeslot12
//    $rows, $colsLeft, $colsRight
//    $bookedSeats (from DB)
//    $isEditMode (true/false)
//    $preSelectedSeats (user's old seats to auto-select in UI)
//    $editingIndex (which cart item is being edited)
// ---------------------------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Seat Selection</title>
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
              <li><a href="#">PROMOTIONS</a></li>
              <li><a href="bookings.php">BOOKINGS</a></li>
              <li><a href="profile.php">PROFILE</a></li>
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
  <main class="container">
     <h1>Choose Your Seats</h1>

    <!-- Top summary cards -->
    <div class="seat-header">
      <!-- Movie summary -->
      <div class="summary-box">
        <h4>MOVIE</h4>
        <div class="movie-title"><?= e($movieTitle) ?></div>
        <div class="movie-code">Code: <?= e($movieId) ?></div>
      </div>

      <!-- Session summary -->
      <div class="summary-box">
        <h4>SESSION</h4>
        <div class="session-meta">
          <span>📅 <?= e($showDate) ?></span>
          <span>🕒 <?= e($timeslot12) ?></span>
          <span>🏟️ Hall <?= e($hallId) ?></span>
        </div>
      </div>
    </div>

    <!-- Seat plan box now BELOW, centered -->
    <div id="seatPlanBox" class="seat-plan-wrapper" style="display:block;">
  <div class="plan-header">
    <span>Seat Availability</span>
  </div>

  <div class="screen-label">SCREEN</div>

  <!-- form so we can submit selected seats to PHP -->
  <form method="post" action="proceed2cart.php" style="text-align:center;">

    <div class="seats-grid">
      <?php
foreach ($rows as $r) {
    echo '<div class="seat-row">';

    // LEFT BLOCK seats 1-4
    echo '<div class="block">';
    foreach ($colsLeft as $c) {
        $code = $r.$c; // e.g. "A1"

        $isBooked = in_array($code, $bookedSeats);
        $wasMine  = in_array($code, $preSelectedSeats);

        // build CSS classes
        $classList = 'seat';
        if ($isBooked) {
            $classList .= ' booked';
        } else if ($wasMine) {
            $classList .= ' selected';
        }

        $safeCode = e($code);

        echo "<div class=\"$classList\" data-seat=\"$safeCode\" ";

        if ($isBooked) {
            echo "title=\"$safeCode is taken\">";
        } else {
            echo "title=\"$safeCode is available\" onclick=\"toggleSeat(this)\">";
        }

        echo $safeCode . "</div>";
    }
    echo '</div>';

    // RIGHT BLOCK seats 5-8
    echo '<div class="block">';
    foreach ($colsRight as $c) {
        $code = $r.$c;

        $isBooked = in_array($code, $bookedSeats);
        $wasMine  = in_array($code, $preSelectedSeats);

        $classList = 'seat';
        if ($isBooked) {
            $classList .= ' booked';
        } else if ($wasMine) {
            $classList .= ' selected';
        }

        $safeCode = e($code);

        echo "<div class=\"$classList\" data-seat=\"$safeCode\" ";

        if ($isBooked) {
            echo "title=\"$safeCode is taken\">";
        } else {
            echo "title=\"$safeCode is available\" onclick=\"toggleSeat(this)\">";
        }

        echo $safeCode . "</div>";
    }
    echo '</div>';

    echo '</div>'; // .seat-row
}
?>
    </div>

    <div class="legend">
      <div class="legend-item">
        <div class="legend-swatch"></div>
        <span>Available</span>
      </div>
      <div class="legend-item">
        <div class="legend-swatch booked"></div>
        <span>Booked</span>
      </div>
      <div class="legend-item">
        <div class="legend-swatch" style="background:#0ea5e9;border-color:#0ea5e9;"></div>
        <span>Selected</span>
      </div>
    </div>

    <!-- This will update live -->
    <div id="selectionSummary"
      style="margin-top:1rem; font-size:0.9rem; color:#0f172a; font-weight:500; text-align:center;">
      No seat selected
    </div>

    <!-- Hidden input that will be filled with "A1,B2,..." before submit -->
    <input type="hidden" name="selected_seats" id="selectedSeatsInput" value="">

    <!-- You might also want to send hall/date/timeslot so PHP knows which show -->
    <input type="hidden" name="hall_id" value="<?= e($hallId) ?>">
    <input type="hidden" name="show_date" value="<?= e($showDate) ?>">
    <input type="hidden" name="timeslot" value="<?= e($timeslot) ?>">
    <input type="hidden" name="movie_code" value="<?= e($movieId) ?>">

    <button type="submit"
      style="
        margin-top:1rem;
        padding:.6rem 1rem;
        border-radius:8px;
        border:0;
        background:#0ea5e9;
        color:#fff;
        font-weight:600;
        cursor:pointer;
        font-size:0.95rem;
        line-height:1.2;
      ">
      Confirm Seat
    </button>
  </form>
</div>

  </main>
    <!-- Footer -->
<footer class="site_footer">
  <div class="container footer_links">
    <a href="index.php">HOME</a>
    <a href="#">CONTACT US</a>
    <a href="jobs.php">JOBS AT CineLux Theatre</a>
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
    <small>Credit/Debit Cards and Cash are welcomed</small>
    </div>
  </div>
</footer>
</body>
<script>
 const selectedSeats = new Set([
    <?php
      if (!empty($preSelectedSeats)) {
        $out = [];
        foreach ($preSelectedSeats as $ps) {
          $out[] = '"' . htmlspecialchars($ps, ENT_QUOTES, 'UTF-8') . '"';
        }
        echo implode(",", $out);
      }
    ?>
  ]);

  function sortSeatCodes(arr) {
    return arr.sort((a, b) => {
      const ra = a.charAt(0), rb = b.charAt(0);
      const na = parseInt(a.slice(1), 10), nb = parseInt(b.slice(1), 10);
      return ra === rb ? (na - nb) : ra.localeCompare(rb);
    });
  }

  function updateSelectionSummary() {
    const summaryBox = document.getElementById("selectionSummary");
    const hiddenInput = document.getElementById("selectedSeatsInput");
    if (!summaryBox || !hiddenInput) return;

    const sorted = sortSeatCodes(Array.from(selectedSeats));

    if (sorted.length === 0) {
      summaryBox.textContent = "No seat selected";
      hiddenInput.value = "";
    } else {
      summaryBox.textContent = "Selected seat(s): " + sorted.join(", ");
      hiddenInput.value = sorted.join(",");
    }
  }

  function toggleSeat(seatDiv) {
    const seatCode = seatDiv.getAttribute("data-seat");
    if (seatDiv.classList.contains("selected")) {
      seatDiv.classList.remove("selected");
      selectedSeats.delete(seatCode);
    } else {
      if (seatDiv.classList.contains("booked")) return;
      seatDiv.classList.add("selected");
      selectedSeats.add(seatCode);
    }
    updateSelectionSummary();
  }

  function togglePlan(show){
    const box = document.getElementById('seatPlanBox');
    if (!box) return;
    box.style.display = show ? 'block' : 'none';
  }

  // keep the initial selection summary in sync (and sorted)
  updateSelectionSummary();
</script>
</html>
