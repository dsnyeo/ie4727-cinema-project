<?php
session_start();
include "dbconnect.php";

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// 1) Validate
if (
    empty($_POST['cust_name']) ||
    empty($_POST['cust_email']) ||
    empty($_POST['cust_phone']) ||
    empty($_POST['payment_method']) ||
    !isset($_POST['grand_total']) ||
    empty($_POST['item']) || !is_array($_POST['item'])
) { die("Missing required checkout data."); }

$custName      = $_POST['cust_name'];
$custEmail     = $_POST['cust_email'];
$custPhone     = $_POST['cust_phone'];
$paymentMethod = $_POST['payment_method'];
$grandTotal    = (float) $_POST['grand_total'];
$userId        = $_SESSION['sess_user_id'] ?? null;
if ($grandTotal < 0) $grandTotal = 0;

// Optional: last4
$cardLast4 = null;
if ($paymentMethod === 'card') {
    $digits = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
    if ($digits !== '') $cardLast4 = substr($digits, -4);
}

$dbcnx->begin_transaction();

try {
    // 2) Insert booking (no CardLast4 column)
    $sqlBooking = "INSERT INTO bookings
      (CustName, CustEmail, CustPhone, PaymentMethod, PaidAmount, UserID)
      VALUES (?,?,?,?,?,?)";
    if (!($stmt = $dbcnx->prepare($sqlBooking))) {
        throw new Exception("Prepare failed for bookings insert: " . $dbcnx->error);
    }
    $userIdForBind = ($userId === null) ? null : (int)$userId;
    $stmt->bind_param("ssssdi",
        $custName, $custEmail, $custPhone, $paymentMethod, $grandTotal, $userIdForBind
    );
    if (!$stmt->execute()) {
        throw new Exception("Execute failed for bookings insert: " . $stmt->error);
    }
    $orderId = $stmt->insert_id;
    $stmt->close();

    // 3) Insert tickets
    $sqlTicket = "INSERT INTO tickets
      (OrderID, HallID, ShowDate, TimeSlot, SeatCode, MovieCode, UserID)
      VALUES (?,?,?,?,?,?,?)";
    if (!($stmtT = $dbcnx->prepare($sqlTicket))) {
        throw new Exception("Prepare failed for tickets insert: " . $dbcnx->error);
    }

    // Build a summary for the email while looping
    $emailRows = [];

    foreach ($_POST['item'] as $it) {
        $movieCode = $it['movie_id']    ?? '';
        $movieTitle= $it['movie_title'] ?? '';
        $hallId    = $it['hall_id']     ?? '';
        $showDate  = $it['show_date']   ?? '';
        $timeslot  = $it['timeslot']    ?? ''; // 24h
        $timeslot12= $it['timeslot12']  ?? '';
        $seatsCSV  = $it['seats']       ?? '';
        $seatsArr  = array_filter(array_map('trim', explode(',', $seatsCSV)));

        if (!$seatsArr) continue;

        foreach ($seatsArr as $seatCode) {
            $userIdForBind = ($userId === null) ? null : (int)$userId;
            $stmtT->bind_param("isssssi",
                $orderId, $hallId, $showDate, $timeslot, $seatCode, $movieCode, $userIdForBind
            );
            if (!$stmtT->execute()) {
                throw new Exception("Execute failed for ticket insert (seat $seatCode): " . $stmtT->error);
            }
        }

        // one email row per showtime (list all seats for that showtime)
        $emailRows[] = [
            'movie' => $movieTitle,
            'date'  => $showDate,
            'time'  => $timeslot12 ?: $timeslot,
            'hall'  => $hallId,
            'seats' => implode(', ', $seatsArr),
            'count' => count($seatsArr)
        ];
    }
    $stmtT->close();

    // 4) Commit
    $dbcnx->commit();

    // 5) Clear cart
    unset($_SESSION['cart']);

    // 6) Send acknowledgment email (Mercury local dev)
    //    If you’re testing locally, make sure custEmail is a local mailbox or just override:
    //    $custEmail = 'f31ee@localhost';

    $from   = 'cineluxadm@localhost.com';
    $EOL    = "\r\n";
    $subject= "Booking Confirmation • Ref #{$orderId}";
    $rowsHtml = '';
    foreach ($emailRows as $r) {
        $rowsHtml .= '<tr>'.
            '<td style="padding:8px;border:1px solid #e5e7eb">'.e($r['movie']).'</td>'.
            '<td style="padding:8px;border:1px solid #e5e7eb">'.e($r['date']).'</td>'.
            '<td style="padding:8px;border:1px solid #e5e7eb">'.e($r['time']).'</td>'.
            '<td style="padding:8px;border:1px solid #e5e7eb">Hall '.e($r['hall']).'</td>'.
            '<td style="padding:8px;border:1px solid #e5e7eb">'.e($r['seats']).'</td>'.
            '<td style="padding:8px;border:1px solid #e5e7eb">'.(int)$r['count'].'</td>'.
        '</tr>';
    }

    $body = '<div style="font-family:Arial,Helvetica,sans-serif;color:#0f172a">
      <h2 style="margin:0 0 .5rem">Thanks, '.e($custName).'! Your booking is confirmed.</h2>
      <p style="margin:.25rem 0">Reference: <strong>#'.(int)$orderId.'</strong></p>
      <table style="border-collapse:collapse;margin:12px 0">
        <thead>
          <tr style="background:#f1f5f9">
            <th style="padding:8px;border:1px solid #e5e7eb;text-align:left">Movie</th>
            <th style="padding:8px;border:1px solid #e5e7eb;text-align:left">Date</th>
            <th style="padding:8px;border:1px solid #e5e7eb;text-align:left">Time</th>
            <th style="padding:8px;border:1px solid #e5e7eb;text-align:left">Hall</th>
            <th style="padding:8px;border:1px solid #e5e7eb;text-align:left">Seats</th>
            <th style="padding:8px;border:1px solid #e5e7eb;text-align:left">Qty</th>
          </tr>
        </thead>
        <tbody>'.$rowsHtml.'</tbody>
      </table>
      <p style="margin:.25rem 0"><strong>Grand Total:</strong> $'.number_format($grandTotal,2).'</p>'.
      ($paymentMethod==='card' && $cardLast4 ? '<p>Paid by card • **** **** **** '.e($cardLast4).'</p>' : '<p>Payment method: '.e(strtoupper($paymentMethod)).'</p>').
      '<p style="margin-top:12px">Enjoy your movie! 🎬</p>
    </div>';

    $headers  = "From: {$from}{$EOL}Reply-To: {$from}{$EOL}";
    $headers .= "MIME-Version: 1.0{$EOL}Content-Type: text/html; charset=UTF-8{$EOL}";
    @mail($custEmail, $subject, $body, $headers, '-f'.$from);

    // 7) Redirect
    header("Location: index.php?order_id=" . urlencode($orderId));
    exit;

} catch (Exception $ex) {
    $dbcnx->rollback();
    die("Checkout failed: " . $ex->getMessage());
}
