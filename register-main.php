<?php
session_start();
require_once __DIR__ . '/dbconnect.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $first     = trim($_POST['first_name'] ?? '');
  $last      = trim($_POST['last_name'] ?? '');
  $username  = trim($_POST['username'] ?? '');
  $email     = trim($_POST['email'] ?? '');
  $password  = $_POST['password'] ?? '';
  $confirm   = $_POST['confirm_password'] ?? '';

  if ($first === '' || !preg_match('/^[A-Za-z ]+$/', $first)) {
    $errors[] = 'First name should only contain letters and spaces.';
  }
  if ($last === '' || !preg_match('/^[A-Za-z ]+$/', $last)) {
    $errors[] = 'Last name should only contain letters and spaces.';
  }
  if ($email === '' || !preg_match('/^[A-Za-z0-9._+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/', $email)) {
    $errors[] = 'Please enter a valid email address.';
  }
  if ($username === '' || !preg_match('/^[A-Za-z0-9]+$/', $username)) {
    $errors[] = 'Username must contain only letters and numbers.';
  }
  $hasLetter  = preg_match('/[A-Za-z]/', $password);
  $hasDigit   = preg_match('/\d/', $password);
  $hasSpecial = preg_match('/[^A-Za-z0-9]/', $password);
  if ($password === '' || !$hasLetter || !$hasDigit || !$hasSpecial) {
    $errors[] = 'Password must include at least one letter, one number, and one special character.';
  }
  if ($password !== $confirm) {
    $errors[] = 'Passwords do not match.';
  }

  if (!$errors) {
    $dup = $dbcnx->prepare("SELECT 1 FROM users WHERE Username = ? OR Email = ? LIMIT 1");
    $dup->bind_param('ss', $username, $email);
    $dup->execute();
    $dup->store_result();
    if ($dup->num_rows > 0) {
      $errors[] = 'Username or email already exists.';
    }
    $dup->close();
  }

  if (!$errors) {
    $ins = $dbcnx->prepare("
      INSERT INTO users (FirstName, LastName, Username, Email, UserPassword, isAdmin)
      VALUES (?, ?, ?, ?, ?, 0)
    ");
    $ins->bind_param('sssss', $first, $last, $username, $email, $password);
    if ($ins->execute()) {
      $_SESSION['flash'] = 'Registration successful! Please log in.';
      header('Location: login-main.php');
      exit;
    } else {
      $errors[] = 'Something went wrong while creating your account. Please try again.';
    }
    $ins->close();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Register</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="styles.css" />
  <style>
    .form_row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    @media (max-width: 640px) { .form_row { grid-template-columns: 1fr; } }
    label { display:block; margin-top:10px; }
    input[type="text"], input[type="password"] {
      width:100%; padding:10px; border-radius:6px; border:1px solid #444; background:#111; color:#eee;
    }
    .error_list { text-align: left; background:#2a0000; border:1px solid #660000; padding:10px; border-radius:6px; font-size:12px; margin-bottom:14px; }
    .field-error { color:#ffb3b3; font-size:12px; margin-top:4px; min-height:14px; }
    .invalid { border-color:#ad2a2a !important; box-shadow: 0 0 0 2px rgba(173,42,42,0.2); }
  </style>
</head>
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
<body class="login_page">
  <div class="login_box">
    <h2>Create your account</h2>

    <?php if (!empty($errors)): ?>
      <div class="error_list">
        <strong>Please fix the following:</strong>
        <ul style="margin:8px 0 0 18px;">
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form id="registerForm" method="post" action="register-main.php" novalidate>
      <div class="form_row">
        <div>
          <label for="first_name">First Name</label>
          <input type="text" id="first_name" name="first_name"
                 value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
                 required autocomplete="given-name" />
          <div class="field-error" id="err-first_name"></div>
        </div>
        <div>
          <label for="last_name">Last Name</label>
          <input type="text" id="last_name" name="last_name"
                 value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
                 required autocomplete="family-name" />
          <div class="field-error" id="err-last_name"></div>
        </div>
      </div>

      <label for="username">Username</label>
      <input type="text" id="username" name="username" maxlength="20"
             value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
             required autocomplete="username" />
      <div class="field-error" id="err-username"></div>

      <label for="email">Email</label>
      <input type="text" id="email" name="email" maxlength="40"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
             required autocomplete="email" />
      <div class="field-error" id="err-email"></div>

      <label for="password">Password</label>
      <input type="password" id="password" name="password"
             required autocomplete="new-password" />
      <div class="field-error" id="err-password"></div>

      <label for="confirm_password">Confirm Password</label>
      <input type="password" id="confirm_password" name="confirm_password"
             required autocomplete="new-password" />
      <div class="field-error" id="err-confirm_password"></div>

      <button type="submit" class="btn" style="margin-top:14px;">Create account</button>
    </form>

    <p style="margin-top:16px;">
      Already have an account? <a href="login-main.php">Log in</a>
    </p>
  </div>

  <script>
    function setError(input, msg) {
      const err = document.getElementById('err-' + input.id);
      if (err) err.textContent = msg || '';
      input.classList.toggle('invalid', !!msg);
    }
    function clearError(input) { setError(input, ''); }

    const nameOk     = v => /^[A-Za-z ]+$/.test(v);
    const emailOk    = v => /^[A-Za-z0-9._+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/.test(v);
    const userOk     = v => /^[A-Za-z0-9]+$/.test(v);
    const passLetter = v => /[A-Za-z]/.test(v);
    const passDigit  = v => /\d/.test(v);
    const passSpec   = v => /[^A-Za-z0-9]/.test(v);

    const first = document.getElementById('first_name');
    const last  = document.getElementById('last_name');
    const email = document.getElementById('email');
    const user  = document.getElementById('username');
    const pass  = document.getElementById('password');
    const conf  = document.getElementById('confirm_password');

    function validateFirst() {
      const v = first.value.trim();
      if (!v) return setError(first, 'Name cannot be left blank.');
      if (!nameOk(v)) return setError(first, 'Only letters and spaces are allowed.');
      return clearError(first);
    }
    function validateLast() {
      const v = last.value.trim();
      if (!v) return setError(last, 'Name cannot be left blank.');
      if (!nameOk(v)) return setError(last, 'Only letters and spaces are allowed.');
      return clearError(last);
    }
    function validateEmail() {
      const v = email.value.trim();
      if (!v) return setError(email, 'Email cannot be left blank.');
      if (!emailOk(v)) return setError(email, 'Please enter a valid email address.');
      return clearError(email);
    }
    function validateUser() {
      const v = user.value.trim();
      if (!v) return setError(user, 'Username cannot be left blank.');
      if (!userOk(v)) return setError(user, 'Letters and numbers only.');
      return clearError(user);
    }
    function validatePass() {
      const v = pass.value;
      if (!v) return setError(pass, 'Password cannot be left blank.');
      if (!(passLetter(v) && passDigit(v) && passSpec(v))) {
        return setError(pass, 'Must include at least one letter, one number, and one special character.');
      }
      return clearError(pass);
    }
    function validateConfirm() {
      if (!conf.value) return setError(conf, 'Please confirm your password.');
      if (conf.value !== pass.value) return setError(conf, 'Passwords do not match.');
      return clearError(conf);
    }

    [first, last, email, user, pass, conf].forEach(el => {
      el.addEventListener('blur', () => {
        switch (el) {
          case first: validateFirst(); break;
          case last:  validateLast(); break;
          case email: validateEmail(); break;
          case user:  validateUser(); break;
          case pass:  validatePass(); break;
          case conf:  validateConfirm(); break;
        }
      });
      el.addEventListener('input', () => {
        switch (el) {
          case first: validateFirst(); break;
          case last:  validateLast(); break;
          case email: validateEmail(); break;
          case user:  validateUser(); break;
          case pass:  validatePass(); validateConfirm(); break;
          case conf:  validateConfirm(); break;
        }
      });
    });

    document.getElementById('registerForm').addEventListener('submit', function(e) {
      validateFirst(); validateLast(); validateEmail(); validateUser(); validatePass(); validateConfirm();
      const firstInvalid = document.querySelector('.invalid');
      if (firstInvalid) {
        e.preventDefault();
        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        firstInvalid.focus({ preventScroll: true });
      }
    });
  </script>
</body>
</html>