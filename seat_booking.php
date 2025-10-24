<?php
session_start();
include "dbconnect.php";

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

if (empty($_SESSION['booking'])) {
  header("Location: index.php");
  exit;
}

$b = $_SESSION['booking'];

$movieId    = $b['movie_id'];
$movieTitle = $b['movie_title'];
$hallId     = $b['hall_id'];
$showDate   = $b['show_date'];
$timeslot   = $b['timeslot'];     // "HH:MM:SS"
$timeslot12 = $b['timeslot_12'];

// New layout definition
$rows       = ['A','B','C','D','E'];
$colsLeft   = ['1','2','3','4'];      // seats close together (block 1)
$colsRight  = ['5','6','7','8'];      // seats close together (block 2)

// Get booked seats for this hall/date/time (same as before)
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Seat Selection</title>
  <link rel="stylesheet" href="styles.css" />
  <style>
   /* Center page container stays same */
.page-container{
  max-width:1000px;
  margin:0 auto;
  padding:1.5rem;
  font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", sans-serif;
  color:#0f172a;
}
h1{
  margin:0 0 1rem;
  font-size:1.5rem;
  font-weight:600;
  color:#0f172a;
}

/* Summary header boxes */
.seat-header{
  display:flex;
  gap:1rem;
  flex-wrap:wrap;
  margin:1.5rem 0;
  align-items:flex-start;
  justify-content:flex-start;
}
.summary-box{
  flex:1 1 260px;
  border:1px solid #e2e8f0;
  padding:1rem 1.25rem;
  border-radius:12px;
  background:#fafafa;
}
.summary-box h4{
  margin:0 0 .5rem;
  font-size:0.8rem;
  font-weight:600;
  letter-spacing:.05em;
  color:#475569;
}
.movie-title{
  font-size:1.25rem;
  font-weight:700;
  color:#0f172a;
}
.movie-code{
  margin-top:.25rem;
  color:#64748b;
  font-size:0.9rem;
}
.session-meta{
  font-size:1rem;
  color:#0f172a;
  line-height:1.4;
}
.session-meta span{
  display:block;
  margin-bottom:.25rem;
}
.btn-viewplan{
  appearance:none;
  border:0;
  cursor:pointer;
  background:#0ea5e9;
  color:#fff;
  font-weight:600;
  font-size:0.9rem;
  line-height:1.2;
  padding:.6rem .9rem;
  border-radius:8px;
  white-space:nowrap;
  margin-top:.75rem;
}

/* Seat plan wrapper now sits BELOW both summary boxes */
.seat-plan-wrapper{
  border:1px solid #e2e8f0;
  border-radius:12px;
  background:#fff;
  padding:1rem 1.25rem 1.5rem;
  max-width:560px;
  margin:2rem auto 0 auto; /* <-- center it under everything */
  display:none;            /* hidden by default, toggled with JS */
  text-align:center;
}

.plan-header{
  font-size:1rem;
  font-weight:600;
  color:#0f172a;
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:1rem;
}
.close-plan-btn{
  appearance:none;
  border:0;
  background:#e2e8f0;
  color:#0f172a;
  font-size:0.8rem;
  font-weight:600;
  border-radius:6px;
  padding:.3rem .5rem;
  cursor:pointer;
  line-height:1.2;
}

.screen-label{
  text-align:center;
  font-size:0.8rem;
  color:#475569;
  letter-spacing:.1em;
  font-weight:600;
  border-bottom:2px solid #94a3b8;
  padding-bottom:.25rem;
  margin-bottom:1rem;
}

/* Seating grid */
.seats-grid{
  display:flex;
  flex-direction:column;
  row-gap:0.9rem; /* vertical space between rows */
  align-items:center;
}

/* Each row now has 2 blocks of seats */
.seat-row{
  display:flex;
  align-items:center;
  column-gap:3rem; /* <-- aisle gap between the left block and right block */
}

/* Each block = seats close together */
.block{
  display:flex;
  column-gap:0.5rem; /* seats inside block are near each other */
}

/* Seat style */
.seat{
  width:44px;
  height:44px;
  border-radius:8px;
  border:2px solid #475569;
  background:#fff;
  font-size:0.8rem;
  font-weight:600;
  color:#0f172a;
  display:flex;
  align-items:center;
  justify-content:center;
  cursor:pointer;
  line-height:1;
  user-select:none;
}

.seat.booked{
  background:#94a3b8;
  border-color:#94a3b8;
  color:#fff;
  cursor:not-allowed;
  text-decoration:line-through;
}

.seat.selected{
  background:#0ea5e9;
  border-color:#0ea5e9;
  color:#fff;
}

/* make booked seats not clickable at all */
.seat.booked{
  background:#94a3b8;
  border-color:#94a3b8;
  color:#fff;
  cursor:not-allowed;
  text-decoration:line-through;
  pointer-events:none;
}

.legend{
  margin-top:1rem;
  font-size:0.8rem;
  color:#475569;
  display:flex;
  flex-wrap:wrap;
  gap:1rem;
  justify-content:center;
}
.legend-item{
  display:flex;
  align-items:center;
  gap:.4rem;
}
.legend-swatch{
  width:16px;
  height:16px;
  border-radius:4px;
  border:2px solid #475569;
  background:#fff;
}
.legend-swatch.booked{
  background:#94a3b8;
  border-color:#94a3b8;
}

  </style>
</head>
<body>
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

        <button type="button" class="btn-viewplan" onclick="togglePlan(true)">
          View seating plan
        </button>
      </div>
    </div>

    <!-- Seat plan box now BELOW, centered -->
    <div id="seatPlanBox" class="seat-plan-wrapper">
  <div class="plan-header">
    <span>Seat Availability</span>
    <button class="close-plan-btn" type="button" onclick="togglePlan(false)">Close</button>
  </div>

  <div class="screen-label">SCREEN</div>

  <!-- form so we can submit selected seats to PHP -->
  <form method="post" action="save_seats.php" style="text-align:center;">

    <div class="seats-grid">
      <?php
      foreach ($rows as $r) {
        echo '<div class="seat-row">';

        // LEFT BLOCK seats 1-4
        echo '<div class="block">';
        foreach ($colsLeft as $c) {
          $code = $r.$c; // e.g. A1
          $isBooked = in_array($code, $bookedSeats);
          $classList = $isBooked ? 'seat booked' : 'seat';
          $safeCode  = e($code);

          echo "<div class=\"$classList\" ";
          echo "data-seat=\"$safeCode\" ";
          if ($isBooked) {
            echo "title=\"$safeCode is taken\">";
          } else {
            echo "title=\"$safeCode is available\" onclick=\"toggleSeat(this)\">";
          }
          echo "$safeCode</div>";
        }
        echo '</div>';

        // RIGHT BLOCK seats 5-8
        echo '<div class="block">';
        foreach ($colsRight as $c) {
          $code = $r.$c; // e.g. A5
          $isBooked = in_array($code, $bookedSeats);
          $classList = $isBooked ? 'seat booked' : 'seat';
          $safeCode  = e($code);

          echo "<div class=\"$classList\" ";
          echo "data-seat=\"$safeCode\" ";
          if ($isBooked) {
            echo "title=\"$safeCode is taken\">";
          } else {
            echo "title=\"$safeCode is available\" onclick=\"toggleSeat(this)\">";
          }
          echo "$safeCode</div>";
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
      Add to Cart
    </button>
  </form>
</div>
        <div id="selectionSummary" style="margin-top:1rem; font-size:0.9rem; color:#0f172a; font-weight:500; text-align:center;">
            No seat selected
        </div>
    <p style="margin-top:2rem; color:#64748b; text-align:center;">
      (Next step: make seats clickable and store the chosen seat(s) before payment.)
    </p>
  </main>
</body>
  <script>
     // track selected seats in memory in the browser
  const selectedSeats = new Set();

  // called when user clicks on a seat div
  function toggleSeat(seatDiv) {
    const seatCode = seatDiv.getAttribute("data-seat");

    if (seatDiv.classList.contains("selected")) {
      // unselect
      seatDiv.classList.remove("selected");
      selectedSeats.delete(seatCode);
    } else {
      // select
      seatDiv.classList.add("selected");
      selectedSeats.add(seatCode);
    }

    updateSelectionSummary();
  }

  function updateSelectionSummary() {
    const summaryBox = document.getElementById("selectionSummary");
    const hiddenInput = document.getElementById("selectedSeatsInput");

    if (!summaryBox || !hiddenInput) return;

    if (selectedSeats.size === 0) {
      summaryBox.textContent = "No seat selected";
      hiddenInput.value = "";
    } else {
      const seatList = Array.from(selectedSeats).join(", ");
      summaryBox.textContent = "Selected seat(s): " + seatList;
      hiddenInput.value = Array.from(selectedSeats).join(","); // "A1,B2,B3"
    }
  }

  function togglePlan(show){
    const box = document.getElementById('seatPlanBox');
    if (!box) return;
    box.style.display = show ? 'block' : 'none';
  }
  </script>
</html>
