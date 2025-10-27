<?php
session_start();
include "dbconnect.php";

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// booking info (movie title etc. lives here for convenience)
if (empty($_SESSION['booking'])) {
    header("Location: index.php");
    exit;
}

// cart must exist AND be an array
if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    header("Location: seat_booking.php");
    exit;
}

$cartItems = $_SESSION['cart']; // array of ticket groups
$PRICE_PER_SEAT = 8.00;

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
<style>


  .cart-shell{
    width:100%;
    max-width:700px;
    background:#1e293b;
    border-radius:16px;
    padding:1.5rem 2rem 2rem;
    box-shadow:0 24px 60px rgba(0,0,0,.8);
    display:flex;
    flex-direction:column;
    gap:1.5rem;
  }

  /* header */
  .cart-header{
    border-bottom:1px solid #334155;
    padding-bottom:1rem;
  }
  .cart-title{
    font-size:1.25rem;
    font-weight:600;
    color:#fff;
    line-height:1.3;
  }
  .cart-sub{
    font-size:.8rem;
    color:#94a3b8;
    line-height:1.4;
    margin-top:.25rem;
  }

  /* list of items */
  .cart-items-list{
    display:flex;
    flex-direction:column;
    gap:1rem;
  }

  /* each item card */
  .cart-item{
    background:#0f172a;
    border:1px solid #334155;
    border-radius:12px;
    padding:1rem 1rem .9rem;
    display:flex;
    flex-direction:column;
    gap:.75rem;
  }

  .item-topline{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    flex-wrap:wrap;
    gap:.5rem;
  }

  .movie-block{
    min-width:0;
  }
  .movie-title{
    font-size:1rem;
    line-height:1.3;
    font-weight:600;
    color:#fff;
    margin:0;
    word-break:break-word;
  }
  .movie-meta-row{
    display:flex;
    flex-wrap:wrap;
    gap:.5rem .75rem;
    font-size:.75rem;
    color:#94a3b8;
    line-height:1.3;
    margin-top:.4rem;
  }
  .badge{
    background:#1e293b;
    border:1px solid #334155;
    border-radius:6px;
    padding:.25rem .5rem;
    font-size:.7rem;
    font-weight:500;
    color:#e2e8f0;
    line-height:1.2;
    white-space:nowrap;
  }

  /* seats */
  .seat-line{
    display:flex;
    flex-wrap:wrap;
    gap:.5rem;
  }
  .seat-chip{
    background:#0ea5e9;
    border-radius:8px;
    padding:.4rem .6rem;
    font-size:.8rem;
    font-weight:600;
    color:#0f172a;
    line-height:1.2;
    border:2px solid #0ea5e9;
  }

  /* price per item */
  .item-price-row{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    font-size:.85rem;
    line-height:1.4;
    color:#e2e8f0;
  }
  .item-price-row .label{
    color:#94a3b8;
  }
  .item-price-row .value{
    color:#fff;
    font-weight:600;
  }

  /* divider line between items */
  .item-divider{
    height:1px;
    background:#334155;
    margin-top:.5rem;
  }

  /* grand total */
  .cart-summary{
    background:#0f172a;
    border:1px solid #334155;
    border-radius:12px;
    padding:1rem 1rem .9rem;
    color:#e2e8f0;
  }
  .summary-row{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    font-size:.9rem;
    line-height:1.4;
    margin-bottom:.5rem;
  }
  .summary-row .label{
    color:#94a3b8;
  }
  .summary-row .value{
    font-weight:600;
    color:#fff;
  }
  .summary-total{
    border-top:1px solid #334155;
    margin-top:.75rem;
    padding-top:.75rem;
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    font-size:1rem;
    line-height:1.4;
  }
  .summary-total .label{
    font-weight:600;
    color:#fff;
  }
  .summary-total .value{
    font-size:1.05rem;
    color:#38bdf8;
    font-weight:600;
  }

  /* actions row */
  .actions{
    display:flex;
    flex-wrap:wrap;
    gap:.75rem;
  }
  .btn-main{
    flex:1 1 auto;
    min-width:180px;
    cursor:pointer;
    appearance:none;
    border:0;
    border-radius:10px;
    background:#0ea5e9;
    color:#0f172a;
    font-weight:600;
    font-size:.95rem;
    line-height:1.2;
    padding:.8rem 1rem;
    text-align:center;
  }
  .btn-ghost{
    flex:1 1 auto;
    min-width:180px;
    cursor:pointer;
    appearance:none;
    background:transparent;
    color:#94a3b8;
    font-weight:500;
    font-size:.9rem;
    line-height:1.2;
    border:1px solid #334155;
    border-radius:10px;
    padding:.8rem 1rem;
    text-align:center;
    text-decoration:none;
  }

  @media(max-width:600px){
    .actions{
      flex-direction:column;
    }
  }
</style>
</head>
<body>
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
      <?php foreach ($cartItems as $index => $item):

      // seats safety
      $seatsArray = (isset($item['seats']) && is_array($item['seats']))
                    ? $item['seats']
                    : [];

      $qty = count($seatsArray);

      // 🚫 skip completely if no seats selected
      if ($qty === 0) {
          continue;
      }

      // now pull other fields only for non-empty items
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
            <p class="movie-title"><?= e($movie_title) ?></p>

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
      <?php endforeach; ?>
    </div>

    <!-- Grand total summary -->
    <div class="cart-summary">
      <div class="summary-row">
        <div class="label">Items in cart</div>
        <div class="value"><?= count($cartItems) ?></div>
      </div>

      <div class="summary-total">
        <div class="label">Grand Total</div>
        <div class="value">$<?= $grandTotalFmt ?></div>
      </div>
    </div>

    <!-- Final actions -->
    <div class="actions">
      <!-- Checkout all items -->
      <form action="checkout.php" method="post" style="flex:1 1 auto; min-width:180px;">
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

        <button type="submit" class="btn-main">Checkout →</button>
      </form>

      <!-- Continue shopping / add more -->
      <a class="btn-ghost" href="index.php">Add more showtimes</a>
    </div>

  </div>
</body>
</html>
