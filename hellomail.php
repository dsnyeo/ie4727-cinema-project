<?php
// hellomail.php — quick local mail test for XAMPP + Mercury
function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$sent = null; 
$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? 'Booking Confirmation (Test)');
    $isHtml = ($_POST['body_type'] ?? 'html') === 'html';

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $sent = false;
        $status = 'Invalid email address.';
    } else {
        // make sure no space in sender address
        $from = 'cineluxadm@localhost';
        $EOL  = "\r\n";

        $headers  = "From: {$from}{$EOL}";
        $headers .= "Reply-To: {$from}{$EOL}";
        $headers .= "X-Mailer: PHP/" . phpversion() . $EOL;

        if ($isHtml) {
            $headers .= "MIME-Version: 1.0{$EOL}";
            $headers .= "Content-Type: text/html; charset=UTF-8{$EOL}";
            $message = '
                <div style="font-family:Arial,Helvetica,sans-serif">
                    <h2>Thanks for your booking! 🎬</h2>
                    <p>This is an <b>HTML</b> test from <code>hellomail.php</code>.</p>
                </div>';
        } else {
            $message = "Thanks for your booking!\r\nPlain text test from hellomail.php.";
        }

        // send the email
        $ok = @mail($to, $subject, $message, $headers, '-f' . $from);
        $sent = (bool)$ok;
        $status = $ok ? "✅ Email sent to {$to}." : "❌ mail() returned false. Check Mercury logs and php.ini.";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Local Mail Test</title>
</head>
<body style="font-family:system-ui,Arial;margin:24px;background:#0b1220;color:#e5e7eb">
  <form method="post" style="max-width:560px;margin:0 auto;background:#1e293b;padding:20px;border-radius:10px">
    <h1>Local Mail Test (Mercury + PHP)</h1>
    <?php if ($sent !== null): ?>
      <p><?= e($status) ?></p>
      <?php if ($sent): ?>
        <script>
          alert("✅ Email has been sent successfully to <?= e($to) ?>!");
        </script>
      <?php else: ?>
        <script>
          alert("❌ Failed to send email. Please check Mercury settings or php.ini.");
        </script>
      <?php endif; ?>
    <?php endif; ?>

    <label>To:</label><br>
    <input type="email" name="email" required 
           value="<?= e($_POST['email'] ?? 'f31ee@localhost') ?>" 
           style="width:100%;padding:8px;margin-bottom:10px"><br>

    <label>Subject:</label><br>
    <input name="subject" 
           value="<?= e($_POST['subject'] ?? 'Booking Confirmation (Test)') ?>" 
           style="width:100%;padding:8px;margin-bottom:10px"><br>

    <label>Body type:</label><br>
    <select name="body_type" style="padding:6px;margin-bottom:10px">
      <option value="html" <?= (($_POST['body_type']??'html')==='html')?'selected':''; ?>>HTML</option>
      <option value="text" <?= (($_POST['body_type']??'')==='text')?'selected':''; ?>>Plain Text</option>
    </select><br>

    <button style="padding:8px 16px;background:#2563eb;color:white;border:0;border-radius:6px;cursor:pointer">
      Send Test Email
    </button>
  </form>
</body>
</html>
