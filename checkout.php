<?php
session_start();
include "dbconnect.php";

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// --- guard: booking + cart must exist ---
if (empty($_SESSION['booking'])) {
    header("Location: index.php");
    exit;
}
if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    header("Location: seat_booking.php");
    exit;
}

$cartItems = $_SESSION['cart'];
$PRICE_PER_SEAT = 8.00;



// we also accept POST from previous page for consistency
$grandTotalRaw = 0;
foreach ($cartItems as $ci) {
    $seatsArray = (isset($ci['seats']) && is_array($ci['seats'])) ? $ci['seats'] : [];
    $qty = count($seatsArray);

    // skip ghost entry with 0 seats
    if ($qty === 0) {
        continue;
    }

    $grandTotalRaw += $qty * $PRICE_PER_SEAT;
}
$grandTotalFmt = number_format($grandTotalRaw, 2);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Checkout</title>
<link rel="stylesheet" href="styles.css" />

<script>
// toggle credit card fields based on radio select
function toggleCardDetails() {
  const cardBox = document.getElementById('cardBox');
  const cardRadio = document.getElementById('pay_card');
  if (cardRadio.checked){
    cardBox.style.display = 'block';
    // make card inputs required when card is selected
    document.querySelectorAll('.cardBox-required').forEach(inp=>{
      inp.required = true;
    });
  } else {
    cardBox.style.display = 'none';
    document.querySelectorAll('.cardBox-required').forEach(inp=>{
      inp.required = false;
    });
  }
}
</script>

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
              <li><a href="#">PROMOTIONS</a></li>
              <li><a href="bookings.php">BOOKINGS</a></li>
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
  <div class="checkout-container">

<form action="insert_booking.php" method="post" class="checkout-wrapper">
  <!-- BOX 1: Order Summary -->
  <section class="panel full-row">
    <div class="panel-header">
      <div>
        <div class="panel-title">Order Summary</div>
        <div class="panel-desc">Please confirm your movie session(s) and seats.</div>
      </div>
    </div>

    <?php foreach ($cartItems as $i => $item):
    $seatsArray  = (isset($item['seats']) && is_array($item['seats'])) ? $item['seats'] : [];
    $qty         = count($seatsArray);

    // 🚫 skip ghost sessions that have 0 seats
    if ($qty === 0) {
        continue;
    }

    // only compute the rest if it's a valid item
    $movie_id    = $item['movie_id']    ?? '';
    $movie_title = $item['movie_title'] ?? '';
    $hall_id     = $item['hall_id']     ?? '';
    $show_date   = $item['show_date']   ?? '';
    $timeslot12  = $item['timeslot12']  ?? '';
    $timeslot24  = $item['timeslot']    ?? '';

    $itemSubtotalRaw = $qty * $PRICE_PER_SEAT;
    $itemSubtotalFmt = number_format($itemSubtotalRaw, 2);

    $seatCSV = implode(",", $seatsArray);
?>
    <div class="order-item">
      <div class="order-headline">
        <div>
          <p class="white-movie-title"><?php echo e($movie_title); ?></p>
          <div class="meta-line">
            <span>📅 <?php echo e($show_date); ?></span>
            <span>🕒 <?php echo e($timeslot12); ?></span>
            <span>🏟️ Hall <?php echo e($hall_id); ?></span>
            <span class="badge" style="
              background:#1e293b;
              border:1px solid #334155;
              border-radius:6px;
              padding:.25rem .5rem;
              font-size:.7rem;
              font-weight:500;
              color:#e2e8f0;
              line-height:1.2;
              white-space:nowrap;
            ">
              <?php echo $qty; ?> ticket<?php echo $qty==1?'':'s'; ?>
            </span>
          </div>
        </div>
      </div>

      <div class="seat-line">
        <?php foreach ($seatsArray as $seat): ?>
          <span class="seat-chip"><?php echo e($seat); ?></span>
        <?php endforeach; ?>
      </div>

      <div class="calc-row">
        <div class="label">
          Tickets (<?php echo $qty; ?> × $<?php echo number_format($PRICE_PER_SEAT,2); ?>)
        </div>
        <div class="value">
          $<?php echo $itemSubtotalFmt; ?>
        </div>
      </div>

      <!-- Hidden fields so insert_booking.php knows what we're buying -->
      <input type="hidden" name="item[<?php echo $i; ?>][movie_id]" value="<?php echo e($movie_id); ?>">
      <input type="hidden" name="item[<?php echo $i; ?>][movie_title]" value="<?php echo e($movie_title); ?>">
      <input type="hidden" name="item[<?php echo $i; ?>][hall_id]" value="<?php echo e($hall_id); ?>">
      <input type="hidden" name="item[<?php echo $i; ?>][show_date]" value="<?php echo e($show_date); ?>">
      <input type="hidden" name="item[<?php echo $i; ?>][timeslot]" value="<?php echo e($timeslot24); ?>">
      <input type="hidden" name="item[<?php echo $i; ?>][timeslot12]" value="<?php echo e($timeslot12); ?>">
      <input type="hidden" name="item[<?php echo $i; ?>][seats]" value="<?php echo e($seatCSV); ?>">
    </div>
    <?php endforeach; ?>

    <div class="grand-total-box">
      <div class="label">Grand Total</div>
      <div class="value">$<?php echo $grandTotalFmt; ?></div>
    </div>

    <input type="hidden" name="grand_total" value="<?php echo e($grandTotalRaw); ?>">
  </section>

  <!-- BOX 2: Contact Information -->
  <section class="panel">
    <div class="panel-header">
      <div>
        <div class="panel-title">Contact Information</div>
        <div class="panel-desc">We'll send your e-ticket & booking reference here.</div>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="cust_name">Full Name <span style="color:#38bdf8">*</span></label>
      <input class="form-input" type="text" id="cust_name" name="cust_name"
          placeholder="e.g. Tan Wei Ming"
          value="<?= isset($_SESSION['sess_user']) ? e($_SESSION['sess_user']) : '' ?>"
          required>
    </div>

    <div class="form-group">
      <label class="form-label" for="cust_email">Email <span style="color:#38bdf8">*</span></label>
      <input class="form-input" type="email" id="cust_email" name="cust_email"
         placeholder="e.g. weiming@example.com"
         value="<?= isset($_SESSION['sess_user_email']) ? e($_SESSION['sess_user_email']) : '' ?>"
         required>
    </div>

    <div class="form-group">
      <label class="form-label" for="cust_phone">Contact Number <span style="color:#38bdf8">*</span></label>
      <input class="form-input" type="tel" id="cust_phone" name="cust_phone"
             placeholder="e.g. +65 9123 4567"
             required>
    </div>

  </section>

  <!-- BOX 3: Payment Method -->
  <section class="panel">
    <div class="panel-header">
      <div>
        <div class="panel-title">Payment Method</div>
        <div class="panel-desc">Choose how you want to pay.</div>
      </div>
    </div>

    <!-- Radio: Cash -->
    <div class="radio-row">
      <input type="radio"
             id="pay_cash"
             name="payment_method"
             value="cash"
             onclick="toggleCardDetails()"
             checked>
      <label for="pay_cash" style="cursor:pointer;color:#fff;">
        Pay at counter (Cash)
      </label>
    </div>

    <!-- Radio: Card -->
    <div class="radio-row">
      <input type="radio"
             id="pay_card"
             name="payment_method"
             value="card"
             onclick="toggleCardDetails()">
      <label for="pay_card" style="cursor:pointer;color:#fff;">
        Credit / Debit Card
      </label>
    </div>

    <!-- Card detail box, hidden by default -->
    <div id="cardBox" class="card-details" style="display:none;">
      <div class="form-group">
        <label class="form-label" for="card_name">
          Name on Card <span style="color:#38bdf8">*</span>
        </label>
        <input class="form-input cardBox-required"
               type="text"
               id="card_name"
               name="card_name"
               placeholder="e.g. TAN WEI MING">
      </div>

      <div class="form-group">
        <label class="form-label" for="card_number">
          Card Number <span style="color:#38bdf8">*</span>
        </label>
        <input class="form-input cardBox-required"
               type="text"
               id="card_number"
               name="card_number"
               placeholder="XXXX XXXX XXXX XXXX"
               pattern="[0-9\s]{12,23}">
      </div>

      <div class="card-grid">
        <div class="form-group">
          <label class="form-label" for="card_expiry">
            Expiry Date (MM/YY) <span style="color:#38bdf8">*</span>
          </label>
          <input class="form-input cardBox-required"
                 type="text"
                 id="card_expiry"
                 name="card_expiry"
                 placeholder="08/27"
                 pattern="[0-1][0-9]/[0-9]{2}">
        </div>

        <div class="form-group">
          <label class="form-label" for="card_cvv">
            CVV <span style="color:#38bdf8">*</span>
          </label>
          <input class="form-input cardBox-required"
                 type="password"
                 id="card_cvv"
                 name="card_cvv"
                 placeholder="***"
                 pattern="[0-9]{3,4}">
        </div>
      </div>
    </div>
  </section>

  <!-- ACTION BUTTONS -->
  <div class="actions">
    <button type="submit" class="btn-main">Book & Confirm Seats →</button>
    <a class="btn-ghost" href="cart.php">← Back to Cart</a>
  </div>

</form>
  </div>
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
<script>
// run once on load to ensure cardBox hidden/required state matches default radio
toggleCardDetails();
</script>

</body>
</html>
