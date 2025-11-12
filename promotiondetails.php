<?php
include "dbconnect.php";
session_start();

if (!isset($dbcnx) || $dbcnx->connect_errno) {
  die("Database connection not found or failed.");
}

$promo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($promo_id <= 0) {
  http_response_code(400);
  $error = "Invalid promotion ID.";
}

$promo = null;
if (!isset($error)) {
  $stmt = $dbcnx->prepare("SELECT PromoID, PromoName, PromoImage, PromoDescription, PromoCode FROM promotions WHERE PromoID = ?");
  $stmt->bind_param("i", $promo_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $promo = $result && $result->num_rows ? $result->fetch_assoc() : null;
  $stmt->close();
}

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
$img = ($promo && !empty($promo['PromoImage'])) ? $promo['PromoImage'] : 'images/placeholder_promo.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= isset($promo['PromoName']) ? e($promo['PromoName']).' | ' : '' ?>CineLux Theatre</title>
  <link rel="stylesheet" href="styles.css" />
  <style>
    body, h1, h2, h3, p, span, a, strong, small { color: #fff !important; }

    .promo-wrap { padding-top: 30px; padding-bottom: 80px; }

    .promo-hero {
      display: grid;
      grid-template-columns: 380px 1fr;
      gap: 48px;
      align-items: start;
      max-width: 1200px;
      margin: 0 auto;
    }

    .poster-stage {
      background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02));
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 14px;
      padding: 12px;
      box-shadow: 0 16px 30px rgba(0,0,0,.45);
    }
    .poster-media {
      width: 100%;
      aspect-ratio: 2 / 3;
      background: #0f1014;
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      overflow: hidden;
    }
    .poster-media img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    .promo-card {
      background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.03));
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 14px;
      padding: 28px 34px;
      box-shadow: 0 18px 40px rgba(0,0,0,.35);
    }

    .promo-title {
      font-size: 30px; letter-spacing: .02em; margin-bottom: 16px;
      color: #fff;
    }

    .promo-desc {
      color: #e0e2e8;
      line-height: 1.75;
      margin-bottom: 20px;
      font-size: 16px;
      text-align: justify;
    }

    .code-row { display:flex; gap:12px; align-items:center; margin-top: 12px; }
    .promo-code {
      display:inline-flex; align-items:center; gap:10px;
      border:1px dashed #999;
      border-radius: 12px;
      padding: 12px 18px;
      background: rgba(255,255,255,0.08);
      font-size: 16px;
    }

    .copy-btn {
      border: 1px solid rgba(255,255,255,0.3);
      background: rgba(255,255,255,0.12);
      color: #fff;
      padding: 12px 16px;
      border-radius: 10px;
      cursor: pointer;
      transition: transform .15s ease, filter .15s ease;
    }
    .copy-btn:hover { transform: translateY(-1px); filter: brightness(1.1); }

    .actions {
      display:flex;
      gap:14px;
      margin-top: 28px;
    }

    .actions .btn,
    .actions .btn_outline {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    min-height: 44px;
    line-height: 1;
    padding: 12px 18px;
    }
  </style>
</head>
<body>
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
    <section class="section promo-wrap">
      <div class="container">
        <?php if (isset($error)): ?>
          <h2 class="section_title">PROMOTION</h2>
          <p><?= e($error) ?></p>
          <div class="actions">
            <a class="btn btn_outline" href="index.php">BACK TO HOME</a>
            <a class="btn btn_outline" href="promotions.php">VIEW OTHER PROMOTIONS</a>
          </div>
        <?php elseif (!$promo): ?>
          <h2 class="section_title">PROMOTION NOT FOUND</h2>
          <p>The promotion you’re looking for doesn’t exist or may have been removed.</p>
          <div class="actions">
            <a class="btn btn_outline" href="index.php">BACK TO HOME</a>
            <a class="btn btn_outline" href="promotions.php">VIEW OTHER PROMOTIONS</a>
          </div>
        <?php else: ?>
          <div class="promo-hero">
            <div class="poster-stage">
              <div class="poster-media">
                <img src="<?= e($img) ?>" alt="<?= e($promo['PromoName']) ?> Poster" />
              </div>
            </div>
            <div class="promo-card">
              <h1 class="promo-title"><?= e($promo['PromoName']) ?></h1>
              <?php if (!empty($promo['PromoDescription'])): ?>
                <div class="promo-desc"><?= nl2br(e($promo['PromoDescription'])) ?></div>
              <?php endif; ?>
              <?php if (!empty($promo['PromoCode'])): ?>
                <div class="code-row">
                  <div class="promo-code" id="promoCode" data-code="<?= e($promo['PromoCode']) ?>">
                    <strong>Promo Code:</strong> <span><?= e($promo['PromoCode']) ?></span>
                  </div>
                  <button class="copy-btn" id="copyBtn" type="button">Copy Code</button>
                </div>
              <?php endif; ?>
              <div class="actions">
                <a class="btn btn_outline" href="index.php">BACK TO HOME</a>
                <a class="btn btn_outline" href="promotions.php">VIEW OTHER PROMOTIONS</a>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>
  <footer class="site_footer">
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
          <li><a href="#" class="icon_btn"><img src="./images/mastercard.svg" alt="mastercard"></a></li>
          <li><a href="#" class="icon_btn"><img src="./images/cash.svg" alt="cash"></a></li>
        </ul>
      </div>
    </div>
  </footer>
  <script>
    const btn = document.getElementById('copyBtn');
    const pill = document.getElementById('promoCode');
    if (btn && pill) {
      btn.addEventListener('click', () => {
        const code = pill.getAttribute('data-code');
        navigator.clipboard.writeText(code).then(() => {
          const oldText = btn.textContent;
          btn.textContent = 'Copied!';
          setTimeout(() => btn.textContent = oldText, 1200);
        });
      });
    }
  </script>
</body>
</html>
