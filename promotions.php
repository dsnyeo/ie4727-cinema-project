<?php
include "dbconnect.php";
session_start();

if (!isset($dbcnx) || $dbcnx->connect_errno) {
  die("Database connection not found or failed.");
}

$sql_promos = "SELECT
                 PromoID AS promo_id,
                 PromoName AS name,
                 PromoImage AS image,
                 PromoDescription AS description,
                 PromoCode AS code
               FROM promotions
               ORDER BY PromoID DESC";
$res_promos = $dbcnx->query($sql_promos);
$promos = $res_promos && $res_promos->num_rows > 0 ? $res_promos->fetch_all(MYSQLI_ASSOC) : [];

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Current Promotions | CineLux Theatre</title>
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
              <li><a href="promotions.php">PROMOTIONS</a></li>
              <li><a href="bookings.php">BOOKINGS</a></li>
              <li><a href="profile.php">PROFILE</a></li>
              <li><a href="jobs.php">JOBS @ CineLux Theatre</a></li>
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

  <main>
    <section class="section">
      <div class="container">
        <h2 class="section_title">CURRENT PROMOTIONS</h2>

        <div class="card_grid">
          <?php if (empty($promos)): ?>
            <p>No promotions are available right now.</p>
          <?php else: ?>
            <?php foreach ($promos as $p): ?>
              <article class="card">
                <div class="poster">
                  <?php
                    $img = trim($p['image'] ?? '');
                    if ($img === '') { $img = 'images/placeholder_promo.jpg'; }
                  ?>
                  <img src="<?= e($img) ?>" alt="<?= e($p['name']) ?> Image" />
                  <div class="overlay">
                    <h3 class="card_title"><?= e($p['name']) ?></h3>
                    <a class="btn btn_outline" href="promotiondetails.php?id=<?= urlencode($p['promo_id']) ?>">VIEW PROMO</a>
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
      <a href="index.php">HOME</a>
      <a href="#">CONTACT US</a>
      <a href="jobs.php">JOBS @ CineLux Theatre</a>
    </div>

    <div class="container">
      <hr class="footer_divider" />
    </div>

    <div class="container footer_panels">
      <div class="footer_panel left">
        <div class="panel_title">CONNECT WITH US</div>
        <ul class="icon_list" aria-label="Social links">
          <li><a class="icon_btn"><img src="./images/fb.svg" alt="Facebook"></a></li>
          <li><a class="icon_btn" aria-label="Twitter / X"><img src="./images/x.svg" alt="Twitter | X"></a></li>
          <li><a class="icon_btn" aria-label="Instagram"><img src="./images/instagram.svg" alt="Instagram"></a></li>
          <li><a class="icon_btn" aria-label="TikTok"><img src="./images/tiktok.svg" alt="TikTok"></a></li>
        </ul>
      </div>

      <div class="footer_panel right">
        <div class="panel_title">SUPPORTED PAYMENT</div>
        <ul class="icon_list" aria-label="Payment">
          <li><a href="#" class="icon_btn"><img src="./images/visa.svg" alt="visa"></a></li>
          <li><a href="#" class="icon_btn" aria-label="mastercard"><img src="./images/mastercard.svg" alt="mastercard"></a></li>
          <li><a href="#" class="icon_btn" aria-label="cash"><img src="./images/cash.svg" alt="cash"></a></li>
        </ul>
        <small>Credit/Debit Cards and Cash are welcomed</small>
      </div>
    </div>
  </footer>
</body>
</html>
