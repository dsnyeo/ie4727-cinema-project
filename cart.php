
<?php
session_start();
include "dbconnect.php";

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// cart must exist AND be an array
if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cartItems = $_SESSION['cart']; // raw items from session

$PRICE_PER_SEAT = $_SESSION['ticket_price'];

/**
 * Merge items that refer to the same show:
 * same movie_id, hall_id, show_date, timeslot.
 */
$merged = [];

foreach ($cartItems as $ci) {
    $movie_id    = $ci['movie_id']    ?? '';
    $movie_title = $ci['movie_title'] ?? '';
    $hall_id     = $ci['hall_id']     ?? '';
    $show_date   = $ci['show_date']   ?? '';
    $timeslot    = $ci['timeslot']    ?? '';   // use 24h as canonical
    $timeslot12  = $ci['timeslot12']  ?? '';
    $seats       = (isset($ci['seats']) && is_array($ci['seats'])) ? $ci['seats'] : [];

    // skip broken/empty entries
    if (!$movie_id || !$show_date || !$timeslot || empty($seats)) {
        continue;
    }

    // unique key for a showtime
    $key = implode('|', [$movie_id, $hall_id, $show_date, $timeslot]);

    if (!isset($merged[$key])) {
        $merged[$key] = [
            'movie_id'    => $movie_id,
            'movie_title' => $movie_title,
            'hall_id'     => $hall_id,
            'show_date'   => $show_date,
            'timeslot'    => $timeslot,
            'timeslot12'  => $timeslot12,
            'seats'       => [],
        ];
    }

    // merge seats & remove duplicates
    $merged[$key]['seats'] = array_values(array_unique(
        array_merge($merged[$key]['seats'], $seats)
    ));
}

// use merged items everywhere below
$cartItems = array_values($merged);

// optional: keep session clean so other pages also see merged version
$_SESSION['cart'] = $cartItems;



// compute grand total safely
$grandTotalRaw = 0;
$displayItemCount = 0;

foreach ($cartItems as $ci) {
    $seatList = (isset($ci['seats']) && is_array($ci['seats'])) ? $ci['seats'] : [];
    $qty = count($seatList);

    if ($qty === 0) {
        continue; // ignore empty ghost entries
    }

    $grandTotalRaw += $qty * $PRICE_PER_SEAT;
    $displayItemCount++;
}

$grandTotalFmt = number_format($grandTotalRaw, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Your Cart</title>
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
  <div class="cart-container">

   <div class="cart-shell">

    <!-- Cart Header -->
    <div class="cart-header">
      <div class="cart-title">Your Cart</div>
      <div class="cart-sub">
        Review all your tickets before checkout.
      </div>
    </div>

    <!-- Cart Items List -->
    <div class="cart-items-list">
      <?php
  // filter out any empty entries
    $nonEmptyItems = array_values(array_filter($cartItems, function($ci){
      return !empty($ci['seats']) && is_array($ci['seats']) && count($ci['seats']) > 0;
  }));

  if (empty($nonEmptyItems)): ?>
      <div class="cart-empty">
        🛒 <strong>Your cart is empty.</strong><br>
      </div>
      <?php else: 
          foreach ($nonEmptyItems as $index => $item):
              $seatsArray = $item['seats'];
              $qty = count($seatsArray);
              $movie_id    = $item['movie_id']    ?? '';
              $movie_title = $item['movie_title'] ?? '';
              $hall_id     = $item['hall_id']     ?? '';
              $show_date   = $item['show_date']   ?? '';
              $timeslot12  = $item['timeslot12']  ?? '';
              $itemSubtotalRaw = $qty * $PRICE_PER_SEAT;
              $itemSubtotalFmt = number_format($itemSubtotalRaw, 2);
              $seatCSV = implode(",", $seatsArray);
      ?>
      <div class="cart-item">

        <!-- movie + session row -->
        <div class="item-topline">
          <div class="movie-block">
            <p class="white-movie-title"><?= e($movie_title) ?></p>

            <div class="movie-meta-row">
              <span class="badge"><?= e($movie_id) ?></span>
              <span>📅 <?= e($show_date) ?></span>
              <span>🕒 <?= e($timeslot12) ?></span>
              <span>🏟️ Hall <?= e($hall_id) ?></span>
            </div>
          </div>

          <div class="badge">
            <?= $qty ?> seat<?= $qty == 1 ? '' : 's' ?>
          </div>
        </div>

        <!-- seats -->
        <div class="seat-line">
          <?php foreach ($seatsArray as $seat): ?>
            <span class="seat-chip"><?= e($seat) ?></span>
          <?php endforeach; ?>
        </div>

        <!-- per-item price -->
        <div class="item-price-row">
          <div class="label">
            Tickets (<?= $qty ?> × $<?= number_format($PRICE_PER_SEAT,2) ?>/seat)
          </div>
          <div class="value">
            $<?= $itemSubtotalFmt ?>
          </div>
        </div>

        <!-- edit/remove actions for this line item -->
        <div class="actions" style="margin-top:.5rem;">

          <!-- Edit seats for this specific session -->
          <form action="edit_item.php" method="post" style="flex:1 1 auto; min-width:140px;">
            <input type="hidden" name="item_index" value="<?= $index ?>">
            <input type="hidden" name="movie_id" value="<?= e($movie_id) ?>">
            <input type="hidden" name="show_date" value="<?= e($show_date) ?>">
            <input type="hidden" name="timeslot" value="<?= e($item['timeslot'] ?? '') ?>">
            <input type="hidden" name="timeslot12" value="<?= e($timeslot12) ?>">
            <input type="hidden" name="hall_id" value="<?= e($hall_id) ?>">
            <input type="hidden" name="seats" value="<?= e($seatCSV) ?>">
            <button class="btn-ghost" type="submit">Edit seats</button>
          </form>

          <!-- Remove this session from cart -->
          <form action="remove_item.php" method="post" style="flex:1 1 auto; min-width:140px;">
            <input type="hidden" name="item_index" value="<?= $index ?>">
            <button class="btn-ghost" type="submit">Remove</button>
          </form>
        </div>

        <?php if ($index < count($cartItems)-1): ?>
          <div class="item-divider"></div>
        <?php endif; ?>

      </div>
      <?php endforeach; endif; ?>
    </div>

    <!-- Grand total summary -->
    <div class="cart-summary">
      <div class="summary-row">
        <div class="label">Items in cart</div>
        <div class="value"><?= $displayItemCount ?></div>
      </div>

      <div class="summary-total">
        <div class="label">Grand Total</div>
        <div class="value">$<?= $grandTotalFmt ?></div>
      </div>
    </div>

    <!-- Final actions -->
    <div class="actions">
      <!-- Checkout all items -->
      <form action="checkout.php" method="post" id="checkoutForm" style="flex:1 1 auto; min-width:180px;">
        <!-- Send primitive fields only -->
        <input type="hidden" name="grand_total" value="<?= e($grandTotalRaw) ?>">

        <!-- We'll also send minimal info for each item as normal POST fields -->
        <?php foreach ($cartItems as $i => $ci): 
            $ci_movie_id    = $ci['movie_id']    ?? '';
            $ci_movie_title = $ci['movie_title'] ?? '';
            $ci_hall_id     = $ci['hall_id']     ?? '';
            $ci_show_date   = $ci['show_date']   ?? '';
            $ci_timeslot12  = $ci['timeslot12']  ?? '';
            $ci_timeslot    = $ci['timeslot']    ?? '';
            $ci_seatsArray  = (isset($ci['seats']) && is_array($ci['seats'])) ? $ci['seats'] : [];
            $ci_seatsCSV    = implode(",", $ci_seatsArray);
        ?>
          <input type="hidden" name="item[<?= $i ?>][movie_id]" value="<?= e($ci_movie_id) ?>">
          <input type="hidden" name="item[<?= $i ?>][movie_title]" value="<?= e($ci_movie_title) ?>">
          <input type="hidden" name="item[<?= $i ?>][hall_id]" value="<?= e($ci_hall_id) ?>">
          <input type="hidden" name="item[<?= $i ?>][show_date]" value="<?= e($ci_show_date) ?>">
          <input type="hidden" name="item[<?= $i ?>][timeslot]" value="<?= e($ci_timeslot) ?>">
          <input type="hidden" name="item[<?= $i ?>][timeslot12]" value="<?= e($ci_timeslot12) ?>">
          <input type="hidden" name="item[<?= $i ?>][seats]" value="<?= e($ci_seatsCSV) ?>">
        <?php endforeach; ?>

        <button type="submit" class="btn-checkout">Checkout →</button>
      </form>

      <!-- Continue shopping / add more -->
      <a class="btn-ghost" href="index.php">Add more showtimes</a>
    </div>

  </div>
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
  document.addEventListener("DOMContentLoaded", function() {
  const checkoutForm = document.getElementById("checkoutForm");
  if (!checkoutForm) return;

  checkoutForm.addEventListener("submit", function(e) {
    // PHP passes this value into JS safely
    const cartEmpty = <?= empty($nonEmptyItems) ? 'true' : 'false' ?>;
    if (cartEmpty) {
      e.preventDefault(); // stop form submission
      alert("No items in cart. Please add tickets before checking out.");
    }
  });
});
  </script>
</body>
</html>
