<?php
session_start();
include "dbconnect.php";

// helper to sanitize for output (not strictly needed for DB insert)
function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// --- 1. Validate required POST fields from checkout form ---
if (
    empty($_POST['cust_name']) ||
    empty($_POST['cust_email']) ||
    empty($_POST['cust_phone']) ||
    empty($_POST['payment_method']) ||
    !isset($_POST['grand_total']) ||
    empty($_POST['item']) || !is_array($_POST['item'])
) {
    // You could redirect back with an error instead
    die("Missing required checkout data.");
}

$custName       = $_POST['cust_name'];
$custEmail      = $_POST['cust_email'];
$custPhone      = $_POST['cust_phone'];
$paymentMethod  = $_POST['payment_method']; // 'cash' or 'card'
$grandTotal     = (float) $_POST['grand_total'];
$userId         = $_SESSION['UserID'] ?? null; // if you track logged-in users

// Safety clamp
if ($grandTotal < 0) { $grandTotal = 0; }

// Optional: capture last4 of card if card payment
$cardLast4 = null;
if ($paymentMethod === 'card') {
    $rawCard = $_POST['card_number'] ?? '';
    // keep only digits
    $digitsOnly = preg_replace('/\D/', '', $rawCard);
    if ($digitsOnly !== '') {
        $cardLast4 = substr($digitsOnly, -4);
    }
}

// --- 2. Start a transaction so inserts are atomic ---
$dbcnx->begin_transaction();

try {

    // --- 3. Insert into bookings (parent row) ---
    // If you added CardLast4 in your schema, include it. If not, skip it.
    // I'll show both.

    // VERSION WITH CardLast4 column in bookings:
    // $sqlBooking = "INSERT INTO bookings
    //   (CustName, CustEmail, CustPhone, PaymentMethod, PaidAmount, UserID, CardLast4)
    //   VALUES (?,?,?,?,?,?,?)";

    // VERSION WITHOUT CardLast4 column (your current schema):
    $sqlBooking = "INSERT INTO bookings
      (CustName, CustEmail, CustPhone, PaymentMethod, PaidAmount, UserID)
      VALUES (?,?,?,?,?,?)";

    if (!($stmt = $dbcnx->prepare($sqlBooking))) {
        throw new Exception("Prepare failed for bookings insert: " . $dbcnx->error);
    }

    // Bind params according to which version you chose:
    // WITH CardLast4:
    // $stmt->bind_param(
    //   "ssssdis",
    //   $custName,
    //   $custEmail,
    //   $custPhone,
    //   $paymentMethod,
    //   $grandTotal,
    //   $userId,
    //   $cardLast4
    // );

    // WITHOUT CardLast4:
    // types: s s s s d i
    // BUT userId can be null, so we have to be careful. We'll cast to int or null properly.
    $userIdForBind = ($userId === null) ? null : (int)$userId;

    $stmt->bind_param(
      "ssssdi",
      $custName,
      $custEmail,
      $custPhone,
      $paymentMethod,
      $grandTotal,
      $userIdForBind
    );

    if (!$stmt->execute()) {
        throw new Exception("Execute failed for bookings insert: " . $stmt->error);
    }

    $orderId = $stmt->insert_id; // <-- new OrderID from bookings table
    $stmt->close();


    // --- 4. Insert each seat into tickets table ---
    // Prep once, reuse in loop (faster)
    $sqlTicket = "INSERT INTO tickets
        (OrderID, HallID, ShowDate, TimeSlot, SeatCode, MovieCode, UserID)
        VALUES (?,?,?,?,?,?,?)";

    if (!($stmtT = $dbcnx->prepare($sqlTicket))) {
        throw new Exception("Prepare failed for tickets insert: " . $dbcnx->error);
    }

    foreach ($_POST['item'] as $it) {
        // pull per-show data
        $movieCode = $it['movie_id']    ?? '';
        $hallId    = $it['hall_id']     ?? '';
        $showDate  = $it['show_date']   ?? '';
        $timeslot  = $it['timeslot']    ?? ''; // "17:00:00"
        $seatsCSV  = $it['seats']       ?? '';

        // explode seats into array
        $seatsArray = array_filter(array_map('trim', explode(',', $seatsCSV)));

        // skip totally empty line items (e.g. 0 seats)
        if (count($seatsArray) === 0) {
            continue;
        }

        foreach ($seatsArray as $seatCode) {

            // bind for each seat
            // types: i  s   s        s        s        s         i
            //        OrderID,HallID,ShowDate,TimeSlot,SeatCode,MovieCode,UserID
            $userIdForBind = ($userId === null) ? null : (int)$userId;

            $stmtT->bind_param(
                "isssssi",
                $orderId,
                $hallId,
                $showDate,
                $timeslot,
                $seatCode,
                $movieCode,
                $userIdForBind
            );

            if (!$stmtT->execute()) {
                // If duplicate seat happens, this will fail because of UNIQUE KEY unique_seat
                throw new Exception("Execute failed for ticket insert (seat $seatCode): " . $stmtT->error);
            }
        }
    }

    $stmtT->close();

    // --- 5. Commit everything ---
    $dbcnx->commit();

    // --- 6. Clear cart so user doesn't buy twice ---
    unset($_SESSION['cart']);

    // --- 7. Redirect to home page for now ---
    header("Location: index.php?order_id=" . urlencode($orderId));
    exit;

} catch (Exception $ex) {

    // rollback if anything failed
    $dbcnx->rollback();

    // you can redirect to an error page instead
    die("Checkout failed: " . $ex->getMessage());
}
