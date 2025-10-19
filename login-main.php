<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Cinema Website</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body class="login_page">
  <!-- Header / Top Nav -->
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
          <li><a href="#">MOVIES</a></li>
          <li><a href="#">CINEMAS</a></li>
          <li><a href="#">PROMOTIONS</a></li>
        </ul>
      </nav>
      </div>

      <a class="btn btn_ghost" href="#">LOGIN</a>
    </div>
    </div>
  </header>

  <div class="login_box">
    <h2>Member Login</h2>
    <form action="login.php" method="post" name="loginForm">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" placeholder="Enter username" required>

      <label for="password">Password</label>
      <input type="password" id="password" name="password" placeholder="Enter password" required>

      <button type="submit" class="btn" name="login" id="login">LOGIN</button>
    </form>

    <p>Don’t have an account? <a href="#">Register here</a></p>
  </div>



  <!-- Footer -->
<footer class="site_footer">
  <div class="container footer_links">
    <a href="#">HOME</a>
    <a href="#">CONTACT US</a>
    <a href="#">JOBS AT &quot;CINEMA NAME&quot;</a>
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
    <a href="https://www.facebook.com/" class="icon_btn">
      <!-- Facebook / Meta-style "f" -->
      <img src="./images/fb.svg" alt="Facebook" >
    </a>
  </li>
  <li>
    <a href="https://x.com/?lang=en" class="icon_btn" aria-label="Twitter / X">
      <!-- Twitter bird -->
      <img src="./images/x.svg" alt="Twitter | X">
    </a>
  </li>
  <li>
    <a href="https://www.instagram.com/" class="icon_btn" aria-label="Instagram">
      <!-- Instagram camera -->
      <img src="./images/instagram.svg" alt="Instagram">
    </a>
  </li>
  <li>
    <a href="https://www.tiktok.com/en/" class="icon_btn" aria-label="TikTok">
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
</html>
