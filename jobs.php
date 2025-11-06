<?php
@include "dbconnect.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

$errors = [];
$saved  = false;

function name_ok($v){ return preg_match('/^[A-Za-z ]+$/', trim($v ?? '')); }
function email_ok($v){ return filter_var(trim($v ?? ''), FILTER_VALIDATE_EMAIL); }
function exp_ok($v){ return mb_strlen(trim($v ?? '')) >= 20; }
function start_ok($iso){
  if (!$iso) return false;
  $today = new DateTime('today');
  $start = DateTime::createFromFormat('Y-m-d', $iso);
  return $start && $start > $today;
}
function bday_ok($iso){
  if (!$iso) return false;
  $bday = DateTime::createFromFormat('Y-m-d', $iso);
  $min  = new DateTime('-18 years');
  return $bday && $bday <= $min;
}

$Name = $_POST['Name'] ?? '';
$Email = $_POST['Email'] ?? '';
$StartDate = $_POST['StartDate'] ?? '';
$Birthday = $_POST['Birthday'] ?? '';
$Experience = $_POST['Experience'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!trim($Name))                 $errors['Name'] = 'Name cannot be left blank.';
  elseif (!name_ok($Name))          $errors['Name'] = 'Your name should only contain alphabet and space characters.';

  if (!trim($Email))                $errors['Email'] = 'Email cannot be left blank.';
  elseif (!email_ok($Email))        $errors['Email'] = 'Please enter a valid email address.';

  if (!$StartDate)                  $errors['StartDate'] = 'Please pick a start date.';
  elseif (!start_ok($StartDate))    $errors['StartDate'] = 'Your start date cannot be today or a past date.';

  if (!$Birthday)                   $errors['Birthday'] = 'Please enter your birthday.';
  elseif (!bday_ok($Birthday))      $errors['Birthday'] = 'You must be at least 18 years old to work.';

  if (!trim($Experience))           $errors['myExperience'] = 'Experience cannot be left blank.';
  elseif (!exp_ok($Experience))     $errors['myExperience'] = 'Experience must be at least 20 characters long.';

  if (!$errors) {
    if (!isset($dbcnx) || $dbcnx->connect_errno) {
      $errors['_page'] = 'Database connection not found or failed.';
    } else {
      $sql = "INSERT INTO jobs (name, email, start_date, birthday, experience) VALUES (?,?,?,?,?)";
      if ($stmt = $dbcnx->prepare($sql)) {
        $stmt->bind_param("sssss", $Name, $Email, $StartDate, $Birthday, $Experience);
        if ($stmt->execute()) {
          $saved = true;
          $Name = $Email = $StartDate = $Birthday = $Experience = '';
        } else {
          $errors['_page'] = 'Failed to save your application. Please try again.';
        }
        $stmt->close();
      } else {
        $errors['_page'] = 'Failed to prepare the database statement.';
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Jobs at "CINEMA NAME"</title>
  <link rel="stylesheet" href="styles.css" />
  <style>
    .field-error { color:#ffb3b3; font-size:12px; margin-top:4px; min-height:14px; }
    .invalid { border-color:#ad2a2a !important; box-shadow: 0 0 0 2px rgba(173,42,42,0.2); }
    .notice { padding:12px 14px; border-radius:8px; margin:14px 0; }
    .notice.ok { background:#0e2a18; color:#b6ffd0; border:1px solid #174d2a; }
    .notice.err{ background:#2a0000; color:#ffc7c7; border:1px solid #5a1b1b; }
  </style>
</head>
<body>
  <header>
    <div id="wrapper">
      <div class="container header_bar">
        <a class="brand" href="#">
          <span class="brand_logo">🎬</span>
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
              <li><a href="#">PROFILE</a></li>
            </ul>
          </nav>
        </div>

        <div>
          <?php if (isset($_SESSION['sess_user'])): ?>
            <span class="welcome_text">👋 Welcome, <strong><?= e($_SESSION['sess_user']) ?></strong></span>
            <a class="btn btn_ghost" href="logout.php">LOGOUT</a>
          <?php else: ?>
            <a class="btn btn_ghost" href="login-main.php">LOGIN</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </header>

  <main class="container section" role="main">
    <h1 class="section_title">Jobs at CineLux Theatre</h1>
    <p>Want to work at CineLux Theatre? Fill out the form below to start your application. Required fields are marked with an asterisk *</p>

    <?php if ($saved): ?>
      <div class="notice ok">✅ Your application has been submitted successfully. We’ll be in touch!</div>
    <?php endif; ?>
    <?php if (!empty($errors['_page'])): ?>
      <div class="notice err">⚠️ <?= e($errors['_page']) ?></div>
    <?php endif; ?>

    <form id="jobsform" method="post" action="jobs.php" novalidate>
      <div class="details_layout" style="margin-top:12px;">
        <div class="details_right" style="grid-column:1 / -1;">
          <div class="facts_box">

            <div class="facts_row">
              <label class="facts_label" for="Name">* Name</label>
              <div class="facts_val" style="flex:1; max-width:420px;">
                <input type="text" name="Name" id="Name" required
                       value="<?= e($Name) ?>"
                       placeholder="Enter your name here"
                       class="<?= isset($errors['Name']) ? 'invalid' : '' ?>"
                       style="width:100%; padding:10px; background:#222; border:1px solid #555; border-radius:6px; color:#fff;" />
                <div class="field-error" id="err-Name"><?= isset($errors['Name']) ? e($errors['Name']) : '' ?></div>
              </div>
            </div>

            <div class="facts_row">
              <label class="facts_label" for="Email">* E-mail</label>
              <div class="facts_val" style="flex:1; max-width:420px;">
                <input type="email" name="Email" id="Email" required
                       value="<?= e($Email) ?>"
                       placeholder="Enter your Email-ID here"
                       class="<?= isset($errors['Email']) ? 'invalid' : '' ?>"
                       style="width:100%; padding:10px; background:#222; border:1px solid #555; border-radius:6px; color:#fff;" />
                <div class="field-error" id="err-Email"><?= isset($errors['Email']) ? e($errors['Email']) : '' ?></div>
              </div>
            </div>

            <div class="facts_row">
              <label class="facts_label" for="StartDate">Start Date</label>
              <div class="facts_val" style="flex:1; max-width:420px;">
                <input type="date" name="StartDate" id="StartDate" required
                       value="<?= e($StartDate) ?>"
                       class="<?= isset($errors['StartDate']) ? 'invalid' : '' ?>"
                       style="width:100%; padding:10px; background:#222; border:1px solid #555; border-radius:6px; color:#fff;" />
                <div class="field-error" id="err-StartDate"><?= isset($errors['StartDate']) ? e($errors['StartDate']) : '' ?></div>
              </div>
            </div>

            <div class="facts_row">
              <label class="facts_label" for="Birthday">Birthday</label>
              <div class="facts_val" style="flex:1; max-width:420px;">
                <input type="date" name="Birthday" id="Birthday" required
                       value="<?= e($Birthday) ?>"
                       class="<?= isset($errors['Birthday']) ? 'invalid' : '' ?>"
                       style="width:100%; padding:10px; background:#222; border:1px solid #555; border-radius:6px; color:#fff;" />
                <div class="field-error" id="err-Birthday"><?= isset($errors['Birthday']) ? e($errors['Birthday']) : '' ?></div>
              </div>
            </div>

            <div class="facts_row" style="align-items:flex-start;">
              <label class="facts_label" for="myExperience">* Experience</label>
              <div class="facts_val" style="flex:1; max-width:620px;">
                <textarea name="Experience" id="myExperience" rows="5" required
                          class="<?= isset($errors['myExperience']) ? 'invalid' : '' ?>"
                          placeholder="Enter your past experience here"
                          style="width:100%; padding:10px; background:#222; border:1px solid #555; border-radius:6px; color:#fff;"><?= e($Experience) ?></textarea>
                <div class="field-error" id="err-myExperience"><?= isset($errors['myExperience']) ? e($errors['myExperience']) : '' ?></div>
              </div>
            </div>

          </div>

          <div class="details_actions" style="margin-top:14px;">
            <input type="reset" class="btn btn-outline" value="Clear" />
            <input type="submit" class="btn btn-outline" value="Apply Now" />
          </div>
        </div>
      </div>
    </form>
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
    <a class="icon_btn">
      <!-- Facebook / Meta-style "f" -->
      <img src="./images/visa.svg" alt="visa" >
    </a>
  </li>
  <li>
    <a class="icon_btn" aria-label="mastercard">
      <!-- Twitter bird -->
      <img src="./images/mastercard.svg" alt="mastercard">
    </a>
  </li>
  <li>
    <a class="icon_btn" aria-label="cash">
      <!-- Instagram camera -->
      <img src="./images/cash.svg" alt="cash">
    </a>
  </li>
</ul>
    <small>Credit/Debit Cards and Cash are welcomed</small>
    </div>
  </div>
</footer>

  <!-- Inline real-time validation (register-main style) -->
  <script>
    (function () {
      function setError(input, msg) {
        const err = document.getElementById('err-' + input.id);
        if (err) err.textContent = msg || '';
        input.classList.toggle('invalid', !!msg);
      }
      function clearError(input) { setError(input, ''); }

      const form         = document.getElementById('jobsform');
      const Name         = document.getElementById('Name');
      const Email        = document.getElementById('Email');
      const StartDate    = document.getElementById('StartDate');
      const Birthday     = document.getElementById('Birthday');
      const myExperience = document.getElementById('myExperience');

      const nameOk   = v => /^[A-Za-z ]+$/.test((v||'').trim());
      const emailOk  = v => /^[A-Za-z0-9._+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/.test((v||'').trim());
      const expOk    = v => (v||'').trim().length >= 20;

      function startOk(iso){
        if (!iso) return false;
        const now = new Date();
        const yyyy = now.getFullYear();
        const mm = String(now.getMonth()+1).padStart(2,'0');
        const dd = String(now.getDate()).padStart(2,'0');
        const today = `${yyyy}-${mm}-${dd}`;
        return iso > today;
      }
      function bdayOk(iso){
        if (!iso) return false;
        const b = new Date(iso);
        const now = new Date();
        const min = new Date(now.getFullYear()-18, now.getMonth(), now.getDate());
        return b <= min;
      }

      function vName(){
        const v = Name.value;
        if (!v.trim())      { setError(Name, 'Name cannot be left blank.'); return false; }
        if (!nameOk(v))     { setError(Name, 'Your name should only contain alphabet and space characters.'); return false; }
        clearError(Name); return true;
      }
      function vEmail(){
        const v = Email.value;
        if (!v.trim())      { setError(Email, 'Email cannot be left blank.'); return false; }
        if (!emailOk(v))    { setError(Email, 'Please enter a valid email address.'); return false; }
        clearError(Email); return true;
      }
      function vStart(){
        const v = StartDate.value;
        if (!v)             { setError(StartDate, 'Please pick a start date.'); return false; }
        if (!startOk(v))    { setError(StartDate, 'Your start date cannot be today or a past date.'); return false; }
        clearError(StartDate); return true;
      }
      function vBday(){
        const v = Birthday.value;
        if (!v)             { setError(Birthday, 'Please enter your birthday.'); return false; }
        if (!bdayOk(v))     { setError(Birthday, 'You must be at least 18 years old to work.'); return false; }
        clearError(Birthday); return true;
      }
      function vExp(){
        const v = myExperience.value;
        if (!v.trim())      { setError(myExperience, 'Experience cannot be left blank.'); return false; }
        if (!expOk(v))      { setError(myExperience, 'Experience must be at least 20 characters long.'); return false; }
        clearError(myExperience); return true;
      }

      [[Name, vName],[Email, vEmail],[StartDate, vStart],[Birthday, vBday],[myExperience, vExp]].forEach(([el, fn])=>{
        if (!el) return;
        el.addEventListener('input', fn);
        el.addEventListener('blur', fn);
      });

      form.addEventListener('submit', (e) => {
        const ok = vName() & vEmail() & vStart() & vBday() & vExp();
        const firstInvalid = document.querySelector('.invalid');
        if (!ok || firstInvalid) {
          e.preventDefault();
          firstInvalid?.scrollIntoView({ behavior:'smooth', block:'center' });
          firstInvalid?.focus({ preventScroll:true });
        }
      });
    })();
  </script>
</body>
</html>
