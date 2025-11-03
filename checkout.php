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
<style>
  .checkout-wrapper{
    width:100%;
    max-width:1000px;
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:1.5rem;
  }

  /* full width row across both columns */
  .full-row{
    grid-column:1 / span 2;
  }

  .panel{
    background:#1e293b;
    border:1px solid #334155;
    border-radius:16px;
    padding:1.25rem 1.5rem 1.5rem;
    box-shadow:0 24px 60px rgba(0,0,0,.8);
  }

  .panel-header{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    margin-bottom:1rem;
    border-bottom:1px solid #334155;
    padding-bottom:.75rem;
  }
  .panel-title{
    font-size:1rem;
    font-weight:600;
    color:#fff;
    line-height:1.3;
  }
  .panel-desc{
    font-size:.8rem;
    color:#94a3b8;
    line-height:1.4;
    margin-top:.25rem;
  }

  /* order summary table-ish layout */
  .order-item{
    background:#0f172a;
    border:1px solid #334155;
    border-radius:12px;
    padding:1rem;
    margin-bottom:1rem;
    font-size:.85rem;
    line-height:1.4;
    color:#e2e8f0;
  }

  .order-headline{
    display:flex;
    flex-wrap:wrap;
    justify-content:space-between;
    gap:.5rem;
    margin-bottom:.5rem;
  }

  .movie-title{
    font-size:1rem;
    font-weight:600;
    color:#fff;
    line-height:1.3;
    margin:0;
  }

  .meta-line{
    font-size:.75rem;
    color:#94a3b8;
    display:flex;
    flex-wrap:wrap;
    gap:.5rem .75rem;
  }

  .seat-line{
    display:flex;
    flex-wrap:wrap;
    gap:.4rem;
    margin-top:.75rem;
    margin-bottom:.75rem;
  }
  .seat-chip{
    background:#0ea5e9;
    border-radius:8px;
    padding:.4rem .6rem;
    font-size:.8rem;
    font-weight:600;
    color:#0f172a;
    border:2px solid #0ea5e9;
    line-height:1.2;
  }

  .calc-row{
    display:flex;
    justify-content:space-between;
    font-size:.8rem;
    line-height:1.4;
    margin-bottom:.25rem;
  }
  .calc-row .label{ color:#94a3b8; }
  .calc-row .value{ color:#fff;font-weight:600; }

  .grand-total-box{
    border-top:1px solid #334155;
    margin-top:1rem;
    padding-top:1rem;
    display:flex;
    justify-content:space-between;
    font-size:1rem;
    line-height:1.4;
  }
  .grand-total-box .label{
    color:#fff;
    font-weight:600;
  }
  .grand-total-box .value{
    color:#38bdf8;
    font-weight:600;
    font-size:1.05rem;
  }

  /* form styles */
  .form-group{
    margin-bottom:1rem;
    display:flex;
    flex-direction:column;
  }

  .form-label{
    font-size:.8rem;
    font-weight:500;
    color:#cbd5e1;
    margin-bottom:.4rem;
    display:flex;
    justify-content:space-between;
    line-height:1.3;
  }

  .form-input{
    background:#0f172a;
    border:1px solid #334155;
    border-radius:8px;
    padding:.7rem .75rem;
    font-size:.9rem;
    line-height:1.2;
    color:#fff;
    outline:none;
  }
  .form-input:focus{
    border-color:#0ea5e9;
    box-shadow:0 0 0 3px rgba(14,165,233,.3);
  }

  .radio-row{
    display:flex;
    align-items:center;
    gap:.5rem;
    font-size:.9rem;
    line-height:1.2;
    color:#fff;
    margin-bottom:.75rem;
  }

  .radio-row input[type="radio"]{
    width:1rem;
    height:1rem;
    cursor:pointer;
  }

  /* credit card details box that toggles */
  .card-details{
    background:#0f172a;
    border:1px solid #334155;
    border-radius:12px;
    padding:1rem;
    margin-top:.5rem;
  }

  .card-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:1rem;
  }

  .actions{
    margin-top:2rem;
    grid-column:1 / span 2;
    display:flex;
    flex-wrap:wrap;
    gap:.75rem;
  }

  .btn-main{
    flex:1 1 auto;
    min-width:200px;
    cursor:pointer;
    appearance:none;
    border:0;
    border-radius:10px;
    background:#0ea5e9;
    color:#0f172a;
    font-weight:600;
    font-size:.95rem;
    line-height:1.2;
    padding:.9rem 1rem;
    text-align:center;
  }

  .btn-ghost{
    flex:1 1 auto;
    min-width:200px;
    cursor:pointer;
    appearance:none;
    background:transparent;
    color:#94a3b8;
    font-weight:500;
    font-size:.9rem;
    line-height:1.2;
    border:1px solid #334155;
    border-radius:10px;
    padding:.9rem 1rem;
    text-align:center;
    text-decoration:none;
  }

  @media(max-width:850px){
    .checkout-wrapper{
      grid-template-columns:1fr;
    }
    .full-row{
      grid-column:1;
    }
    .actions{
      grid-column:1;
      flex-direction:column;
    }
  }
</style>

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
              <li><a href="#">BOOKINGS</a></li>
              <li><a href="#">PROFILE</a></li>
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
          <p class="movie-title"><?php echo e($movie_title); ?></p>
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

      <!-- Hidden fields so place_order.php knows what we're buying -->
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
             required>
    </div>

    <div class="form-group">
      <label class="form-label" for="cust_email">Email <span style="color:#38bdf8">*</span></label>
      <input class="form-input" type="email" id="cust_email" name="cust_email"
             placeholder="e.g. weiming@example.com"
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
    <button type="submit" class="btn-main">Place Order & Confirm Seats →</button>
    <a class="btn-ghost" href="cart.php">← Back to Cart</a>
  </div>

</form>

<script>
// run once on load to ensure cardBox hidden/required state matches default radio
toggleCardDetails();
</script>

</body>
</html>
