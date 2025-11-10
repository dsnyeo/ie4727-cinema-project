<?php
session_start();

if (!isset($_SESSION['sess_user'])) {
  echo "<script>
          alert('Please log in to access your profile.');
          window.history.back();
        </script>";
  exit;
}

include __DIR__ . '/dbconnect.php';
if (!isset($dbcnx) || !($dbcnx instanceof mysqli)) {
  http_response_code(500);
  echo 'Database connection not initialized.';
  exit;
}

if (!isset($_SESSION['user_id']) && isset($_SESSION['sess_user'])) {
  if ($stmt = $dbcnx->prepare('SELECT UserID FROM users WHERE Username = ? LIMIT 1')) {
    $stmt->bind_param('s', $_SESSION['sess_user']);
    $stmt->execute();
    $stmt->bind_result($uid);
    if ($stmt->fetch()) $_SESSION['user_id'] = (int)$uid;
    $stmt->close();
  }
}

if (!isset($_SESSION['user_id'])) {
  header('Location: login-main.php?redirect=profile.php');
  exit;
}

$userId = (int)$_SESSION['user_id'];

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$me = ['FirstName'=>'','LastName'=>'','Username'=>'','Email'=>''];
if ($stmt = $dbcnx->prepare("SELECT FirstName, LastName, Username, Email FROM users WHERE UserID = ? LIMIT 1")) {
  $stmt->bind_param('i', $userId);
  $stmt->execute();
  $stmt->bind_result($me['FirstName'], $me['LastName'], $me['Username'], $me['Email']);
  $stmt->fetch();
  $stmt->close();
}

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $first = trim($_POST['first_name'] ?? '');
  $last = trim($_POST['last_name'] ?? '');
  $user = trim($_POST['username'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $pass = $_POST['password'] ?? '';
  $pass2 = $_POST['password_confirm'] ?? '';

  if ($first !== '' && !preg_match('/^[A-Za-z ]+$/', $first)) {
    $error = 'First name should only contain letters and spaces.';
  }
  if (!$error && $last !== '' && !preg_match('/^[A-Za-z ]+$/', $last)) {
    $error = 'Last name should only contain letters and spaces.';
  }
  if (!$error && $user !== '' && !preg_match('/^[A-Za-z0-9]+$/', $user)) {
    $error = 'Username must contain only letters and numbers.';
  }
  if (!$error && $email !== '') {
    if (!preg_match('/^[A-Za-z0-9._+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/', $email)) {
      $error = 'Please enter a valid email address.';
    }
  }
  if (!$error && $pass !== '') {
    $hasLetter = preg_match('/[A-Za-z]/', $pass);
    $hasDigit = preg_match('/\d/', $pass);
    $hasSpecial = preg_match('/[^A-Za-z0-9]/', $pass);
    if (!($hasLetter && $hasDigit && $hasSpecial)) {
      $error = 'Password must include at least one letter, one number, and one special character.';
    } elseif ($pass !== $pass2) {
      $error = 'Passwords do not match.';
    }
  }

  if (!$error && $user !== '') {
    if ($stmt = $dbcnx->prepare("SELECT 1 FROM users WHERE Username = ? AND UserID <> ? LIMIT 1")) {
      $stmt->bind_param('si', $user, $userId);
      $stmt->execute(); $stmt->store_result();
      if ($stmt->num_rows > 0) $error = 'That username is already taken.';
      $stmt->close();
    }
  }
  if (!$error && $email !== '') {
    if ($stmt = $dbcnx->prepare("SELECT 1 FROM users WHERE Email = ? AND UserID <> ? LIMIT 1")) {
      $stmt->bind_param('si', $email, $userId);
      $stmt->execute(); $stmt->store_result();
      if ($stmt->num_rows > 0) $error = 'That email is already in use.';
      $stmt->close();
    }
  }

  if (!$error) {
    $set = [];
    $types = '';
    $vals = [];

    if ($first !== '') { $set[]='FirstName = ?'; $types.='s'; $vals[]=$first; }
    if ($last !== '') { $set[]='LastName = ?'; $types.='s'; $vals[]=$last;  }
    if ($user !== '') { $set[]='Username = ?'; $types.='s'; $vals[]=$user;  }
    if ($email !== '') { $set[]='Email = ?'; $types.='s'; $vals[]=$email; }
    if ($pass !== '') { $set[]='UserPassword = ?'; $types.='s'; $vals[]=$pass; }

    if (count($set) === 0) {
      $success = 'Nothing to update. (All fields left blank.)';
    } else {
      $sql = "UPDATE users SET ".implode(', ',$set)." WHERE UserID = ?";
      $types .= 'i';
      $vals[] = $userId;

      if ($stmt = $dbcnx->prepare($sql)) {
        $params = array_merge([$types], $vals);
        $refs = [];
        foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
        call_user_func_array([$stmt, 'bind_param'], $refs);

        if ($stmt->execute()) {
          $success = 'Profile updated successfully.';

          if ($first !== '') $me['FirstName'] = $first;
          if ($last !== '') $me['LastName'] = $last;
          if ($user !== '') { $me['Username'] = $user; $_SESSION['sess_user'] = $user; }
          if ($email !== '') $me['Email'] = $email;
        } else {
          $error = 'Update failed. Please try again.';
        }
        $stmt->close();
      } else {
        $error = 'Could not prepare update.';
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Profile</title>
  <link rel="stylesheet" href="styles.css"/>
  <style>
    body{background:#0b1220;}
    .profile-wrap{max-width:900px;margin:2rem auto;padding:0 1rem;}
    .page-title{color:#fff;font-size:2rem;font-weight:800;margin:0 0 1rem;}
    .card{
      background:#0f172a;border:1px solid #334155;border-radius:18px;padding:1.25rem;color:#e2e8f0;
      box-shadow:0 10px 30px rgba(0,0,0,.25);
    }
    .card-header{display:flex;align-items:center;gap:1rem;margin-bottom:1rem;border-bottom:1px dashed #263247;padding-bottom:1rem;}
    .avatar{
      width:60px;height:60px;border-radius:999px;display:grid;place-items:center;
      background:linear-gradient(135deg,#1e293b,#0b1220);border:1px solid #334155;color:#c7d2fe;font-weight:800;font-size:1.25rem;
    }
    .subtle{color:#94a3b8;font-size:.9rem;}
    .grid{display:grid;gap:1rem;}
    @media(min-width:760px){.grid.two{grid-template-columns:1fr 1fr;}}
    label{display:block;color:#94a3b8;font-size:.9rem;margin-bottom:.35rem;}
    input[type="text"],input[type="email"],input[type="password"]{
      width:100%;background:#0b1220;border:1px solid #334155;color:#e2e8f0;padding:.75rem .85rem;border-radius:12px;outline:none;
    }
    input::placeholder{color:#6b7280;}
    .hint{color:#94a3b8;font-size:.85rem;margin-top:.25rem;}
    .actions{display:flex;gap:.75rem;margin-top:1.25rem;align-items:stretch;}
    .btn-main,.btn-ghost{
      display:block;width:100%;padding:.9rem 1rem;border-radius:12px;text-align:center;font-weight:700;text-decoration:none;
    }
    .btn-main{background:#22c55e;border:1px solid #16a34a;color:#052e16;}
    .btn-ghost{background:transparent;border:1px solid #334155;color:#e2e8f0;}
    .alert-success{background:#052e16;border:1px solid #16a34a;color:#dcfce7;padding:.8rem 1rem;border-radius:12px;margin:1rem 0;}
    .alert-danger{background:#3b0d0d;border:1px solid #7f1d1d;color:#fee2e2;padding:.8rem 1rem;border-radius:12px;margin:1rem 0;}

    .field-error{color:#fca5a5;font-size:.85rem;margin-top:.3rem;}
    input.invalid{border-color:#7f1d1d; box-shadow:0 0 0 2px rgba(127,29,29,.15);}
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
              <li><a href="#">PROMOTIONS</a></li>
              <li><a href="bookings.php">BOOKINGS</a></li>
              <li><a href="profile.php">PROFILE</a></li>
            </ul>
          </nav>
        </div>
        <div>
          <?php if (isset($_SESSION['sess_user'])): ?>
            <span class="welcome_text">
              <a href="cart.php" style="text-decoration:none;">🛒</a>
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
    <div class="profile-wrap">
      <h1 class="page-title">My Profile</h1>

      <?php if ($success): ?>
        <div class="alert-success"><?= e($success) ?></div>
      <?php elseif ($error): ?>
        <div class="alert-danger"><?= e($error) ?></div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header">
          <div class="avatar" aria-hidden="true">
            <?php
              $initials = strtoupper(substr($me['FirstName'] ?: $me['Username'],0,1).substr($me['LastName'] ?: '',0,1));
              echo e(trim($initials) ?: 'U');
            ?>
          </div>
          <div>
            <div style="font-size:1.15rem;font-weight:800;color:#fff;"><?= e($me['Username']) ?></div>
            <div class="subtle"><?= e($me['Email']) ?></div>
          </div>
        </div>

        <form method="post" novalidate>
          <div class="grid two">
            <div>
              <label for="first_name">First name</label>
              <input id="first_name" name="first_name" type="text" maxlength="40"
                     placeholder="<?= e($me['FirstName'] ?: 'Leave blank to keep') ?>">
            </div>
            <div>
              <label for="last_name">Last name</label>
              <input id="last_name" name="last_name" type="text" maxlength="40"
                     placeholder="<?= e($me['LastName'] ?: 'Leave blank to keep') ?>">
            </div>
          </div>

          <div class="grid two">
            <div>
              <label for="username">Username</label>
              <input id="username" name="username" type="text" maxlength="20"
                     placeholder="<?= e($me['Username']) ?> (leave blank to keep)">
            </div>
            <div>
              <label for="email">Email</label>
              <input id="email" name="email" type="email" maxlength="40"
                     placeholder="<?= e($me['Email']) ?> (leave blank to keep)">
            </div>
          </div>

          <div class="grid two">
            <div>
              <label for="password">New password (optional)</label>
              <input id="password" name="password" type="password" maxlength="40"
                     placeholder="Leave blank to keep current">
              <div class="hint">Must include a letter, a number, and a special character.</div>
            </div>
            <div>
              <label for="password_confirm">Confirm new password</label>
              <input id="password_confirm" name="password_confirm" type="password" maxlength="40"
                     placeholder="Repeat new password">
            </div>
          </div>

          <div class="actions">
            <button type="submit" class="btn-main">Save changes</button>
            <a class="btn-ghost" href="index.php">Back to home</a>
          </div>
        </form>
      </div>
    </div>
  </main>

  <footer class="site_footer">
    <div class="container footer_links">
      <a href="index.php">HOME</a>
      <a href="#">CONTACT US</a>
      <a href="jobs.php">JOBS AT CineLux Theatre</a>
    </div>
    <div class="container"><hr class="footer_divider"/></div>
  </footer>

  <script>
    function setError(input, msg){
      const id = 'err-' + input.id;
      let n = document.getElementById(id);
      if (!n){n = document.createElement('div'); n.id = id; n.className='field-error'; input.parentElement.appendChild(n);}
      n.textContent = msg || '';
      input.classList.toggle('invalid', !!msg);
    }
    function clearError(i){setError(i, '');}

    const nameOk = v => /^[A-Za-z ]+$/.test((v||'').trim());
    const emailOk = v => /^[A-Za-z0-9._+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/.test((v||'').trim());
    const userOk = v => /^[A-Za-z0-9]+$/.test((v||'').trim());
    const passLet = v => /[A-Za-z]/.test(v||'');
    const passDig = v => /\d/.test(v||'');
    const passSpec= v => /[^A-Za-z0-9]/.test(v||'');

    const first = document.getElementById('first_name');
    const last = document.getElementById('last_name');
    const user = document.getElementById('username');
    const email = document.getElementById('email');
    const pass = document.getElementById('password');
    const conf = document.getElementById('password_confirm');

    function vFirst(){
      const v=first.value.trim();
      if(!v)
        {clearError(first);return true;}
      if(!nameOk(v))
        {setError(first,'Only letters and spaces are allowed.');return false;}
      clearError(first);return true;
    }

    function vLast(){
      const v=last.value.trim();
      if(!v)
        {clearError(last);return true;}
      if(!nameOk(v))
        {setError(last,'Only letters and spaces are allowed.'); return false;}
      clearError(last); return true; 
    }

    function vUser(){
      const v=user.value.trim();
      if(!v)
        {clearError(user);return true;}
      if(!userOk(v))
        {setError(user,'Letters and numbers only.'); return false;}
      clearError(user); return true;
    }

    function vEmail(){
      const v=email.value.trim();
      if(!v)
        {clearError(email);return true;}
      if(!emailOk(v))
        {setError(email,'Please enter a valid email.'); return false;}
      clearError(email);return true;
    }
    
    function vPass(){
      const v=pass.value;
      if(!v)
        {clearError(pass); clearError(conf); return true;}
      if(!(passLet(v)&&passDig(v)&&passSpec(v)))
        {setError(pass,'Must include letter, number, and special.'); return false;}
      if(conf.value && conf.value!==v)
        {setError(conf,'Passwords do not match.'); return false;}clearError(pass); 
      if(conf.value)
      {clearError(conf);} return true;
    }
    function vConf(){
      if(!pass.value && !conf.value)
        {clearError(conf); return true;}
      if(conf.value !== pass.value)
        {setError(conf,'Passwords do not match.'); return false;}
      clearError(conf); return true;
    }

    [[first,vFirst],[last,vLast],[user,vUser],[email,vEmail],[pass,vPass],[conf,vConf]].forEach(([el,fn])=>{
      if(!el) return; el.addEventListener('input', fn); el.addEventListener('blur', fn);
    });

    document.querySelector('form').addEventListener('submit', (e)=>{
      const ok = vFirst() & vLast() & vUser() & vEmail() & vPass() & vConf();
      if(!ok) e.preventDefault();
    });
  </script>
</body>
</html>
