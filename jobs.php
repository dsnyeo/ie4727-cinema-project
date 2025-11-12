<?php
@include "dbconnect.php";
if (session_status() === PHP_SESSION_NONE) {session_start();}
if (!function_exists('e')) {function e($s){return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');}}

$errors = [];
$saved = false;

function name_ok($v){return preg_match('/^[A-Za-z ]+$/', trim($v ?? ''));}
function email_ok($v){return filter_var(trim($v ?? ''), FILTER_VALIDATE_EMAIL);}
function exp_ok($v){return mb_strlen(trim($v ?? '')) >= 20;}
function start_ok($iso){
  if (!$iso) return false;
  $today = new DateTime('today');
  $start = DateTime::createFromFormat('Y-m-d', $iso);
  return $start && $start > $today;
}
function bday_ok($iso){
  if (!$iso) return false;
  $bday = DateTime::createFromFormat('Y-m-d', $iso);
  $min = new DateTime('-18 years');
  return $bday && $bday <= $min;
}

$prefillNameFromDB = '';
$prefillEmailFromDB = '';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($dbcnx) && !$dbcnx->connect_errno) {
  if (isset($_SESSION['sess_user']) || isset($_SESSION['user_id'])) {
    if (!isset($_SESSION['user_id']) && isset($_SESSION['sess_user'])) {
      if ($stmt = $dbcnx->prepare('SELECT UserID FROM users WHERE Username = ? LIMIT 1')) {
        $stmt->bind_param('s', $_SESSION['sess_user']);
        $stmt->execute();
        $stmt->bind_result($uid);
        if ($stmt->fetch()) $_SESSION['user_id'] = (int)$uid;
        $stmt->close();
      }
    }
    if (isset($_SESSION['user_id'])) {
      $uid = (int)$_SESSION['user_id'];
      if ($stmt = $dbcnx->prepare('SELECT FirstName, LastName, Email FROM users WHERE UserID = ? LIMIT 1')) {
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $stmt->bind_result($first, $last, $email);
        if ($stmt->fetch()) {
          $prefillNameFromDB = trim(($first ?? '') . ' ' . ($last ?? ''));
          $prefillEmailFromDB = (string)$email;
        }
        $stmt->close();
      }
    }
  }
}

$Name = ($_SERVER['REQUEST_METHOD'] === 'POST') ? ($_POST['Name'] ?? ''): $prefillNameFromDB;
$Email = ($_SERVER['REQUEST_METHOD'] === 'POST') ? ($_POST['Email'] ?? ''): $prefillEmailFromDB;
$StartDate = $_POST['StartDate']  ?? '';
$Birthday = $_POST['Birthday']   ?? '';
$Experience = $_POST['Experience'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!trim($Name)) $errors['Name'] = 'Name cannot be left blank.';
  elseif (!name_ok($Name)) $errors['Name'] = 'Your name should only contain alphabet and space characters.';

  if (!trim($Email)) $errors['Email'] = 'Email cannot be left blank.';
  elseif (!email_ok($Email)) $errors['Email'] = 'Please enter a valid email address.';

  if (!$StartDate) $errors['StartDate'] = 'Please pick a start date.';
  elseif (!start_ok($StartDate)) $errors['StartDate'] = 'Your start date cannot be today or a past date.';

  if (!$Birthday) $errors['Birthday'] = 'Please enter your birthday.';
  elseif (!bday_ok($Birthday)) $errors['Birthday'] = 'You must be at least 18 years old to work.';

  if (!trim($Experience)) $errors['myExperience'] = 'Experience cannot be left blank.';
  elseif (!exp_ok($Experience)) $errors['myExperience'] = 'Experience must be at least 20 characters long.';

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
    .field-error{
      color:#ffb3b3;
      font-size:12px;
      margin-top:4px;
      min-height:14px;
    }

    .invalid{
      border-color:#ad2a2a !important;
      box-shadow: 0 0 0 2px rgba(173,42,42,0.2);
    }

    .notice{
      padding:12px 14px;
      border-radius:8px;
      margin:14px 0;
    }

    .notice.ok{
      background:#0e2a18;
      color:#b6ffd0;
      border:1px solid #174d2a;
    }

    .notice.err{
      background:#2a0000;
      color:#ffc7c7;
      border:1px solid #5a1b1b;
    }

    #StartDate::-webkit-calendar-picker-indicator,
    #Birthday::-webkit-calendar-picker-indicator {
      filter: invert(1) brightness(1.7);
      opacity: 0;
      cursor: pointer;
    }

    #StartDate, #Birthday {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='22' height='22' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M7 2a1 1 0 0 0-1 1v1H5a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3h-1V3a1 1 0 1 0-2 0v1H8V3a1 1 0 0 0-1-1ZM5 8h14v11a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V8Zm3 3v2h2v-2H8Zm0 4v2h2v-2H8Zm4-4v2h2v-2h-2Zm0 4v2h2v-2h-2Zm4-4v2h2v-2h-2Zm0 4v2h2v-2h-2Z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 10px center;
      background-size: 20px 20px;
      padding-right: 44px;
      color-scheme: dark;
    }

    #StartDate:hover,
    #Birthday:hover {
      filter: brightness(1.05);
    }
  </style>
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
                       style="width:100%; padding:10px; background-color:#222; border:1px solid #555; border-radius:6px; color:#fff;" />
                <div class="field-error" id="err-StartDate"><?= isset($errors['StartDate']) ? e($errors['StartDate']) : '' ?></div>
              </div>
            </div>

            <div class="facts_row">
              <label class="facts_label" for="Birthday">Birthday</label>
              <div class="facts_val" style="flex:1; max-width:420px;">
                <input type="date" name="Birthday" id="Birthday" required
                       value="<?= e($Birthday) ?>"
                       class="<?= isset($errors['Birthday']) ? 'invalid' : '' ?>"
                       style="width:100%; padding:10px; background-color:#222; border:1px solid #555; border-radius:6px; color:#fff;" />
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

<footer class="site_footer">
  <div class="container footer_panels">
    <div class="footer_panel left">
      <div class="panel_title">CONNECT WITH US</div>
<ul class="icon_list" aria-label="Social links">
  <li>
    <a class="icon_btn">
      <img src="./images/fb.svg" alt="Facebook" >
    </a>
  </li>
  <li>
    <a class="icon_btn" aria-label="Twitter / X">
      <img src="./images/x.svg" alt="Twitter | X">
    </a>
  </li>
  <li>
    <a class="icon_btn" aria-label="Instagram">
      <img src="./images/instagram.svg" alt="Instagram">
    </a>
  </li>
  <li>
    <a class="icon_btn" aria-label="TikTok">
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
      <img src="./images/visa.svg" alt="visa" >
    </a>
  </li>
  <li>
    <a class="icon_btn" aria-label="mastercard">
      <img src="./images/mastercard.svg" alt="mastercard">
    </a>
  </li>
  <li>
    <a class="icon_btn" aria-label="cash">
      <img src="./images/cash.svg" alt="cash">
    </a>
  </li>
</ul>
    </div>
  </div>
</footer>

  <script>
    (function () {
      function setError(input, msg) {
        const err = document.getElementById('err-' + input.id);
        if (err) err.textContent = msg || '';
        input.classList.toggle('invalid', !!msg);
      }
      function clearError(input) { setError(input, ''); }

      const form = document.getElementById('jobsform');
      const Name = document.getElementById('Name');
      const Email = document.getElementById('Email');
      const StartDate = document.getElementById('StartDate');
      const Birthday = document.getElementById('Birthday');
      const myExperience = document.getElementById('myExperience');

      const nameOk = v => /^[A-Za-z ]+$/.test((v||'').trim());
      const emailOk = v => /^[A-Za-z0-9._+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/.test((v||'').trim());
      const expOk = v => (v||'').trim().length >= 20;

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
        if (!v.trim()){
          setError(Name, 'Name cannot be left blank.');
          return false;
        }
        if (!nameOk(v)){
          setError(Name, 'Your name should only contain alphabet and space characters.');
          return false;
        }
        clearError(Name);
        return true;
      }
      function vEmail(){
        const v = Email.value;
        if (!v.trim()){
          setError(Email, 'Email cannot be left blank.');
          return false;
        }
        if (!emailOk(v)){
          setError(Email, 'Please enter a valid email address.');
          return false;
        }
        clearError(Email);
        return true;
      }
      function vStart(){
        const v = StartDate.value;
        if (!v){
          setError(StartDate, 'Please pick a start date.');
          return false;
        }
        if (!startOk(v)){
          setError(StartDate, 'Your start date cannot be today or a past date.');
          return false;
        }
        clearError(StartDate);
        return true;
      }
      function vBday(){
        const v = Birthday.value;
        if (!v){
          setError(Birthday, 'Please enter your birthday.');
          return false;
        }
        if (!bdayOk(v)){
          setError(Birthday, 'You must be at least 18 years old to work.');
          return false;
        }
        clearError(Birthday);
        return true;
      }
      function vExp(){
        const v = myExperience.value;
        if (!v.trim()){
          setError(myExperience, 'Experience cannot be left blank.');
          return false;
        }
        if (!expOk(v)){
          setError(myExperience, 'Experience must be at least 20 characters long.');
          return false;
        }
        clearError(myExperience);
        return true;
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
