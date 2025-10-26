<?php
session_start();

// make sure cart exists and index is valid
if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

$itemIndex = $_POST['item_index'] ?? null;
if ($itemIndex === null || !is_numeric($itemIndex)) {
    header("Location: cart.php");
    exit;
}
$itemIndex = (int)$itemIndex;

if (!isset($_SESSION['cart'][$itemIndex])) {
    header("Location: cart.php");
    exit;
}

// read posted info
$movie_id   = $_POST['movie_id']   ?? '';
$show_date  = $_POST['show_date']  ?? '';
$timeslot   = $_POST['timeslot']   ?? '';
$timeslot12 = $_POST['timeslot12'] ?? '';
$hall_id    = $_POST['hall_id']    ?? '';
$seatsCSV   = $_POST['seats']      ?? ''; // "A1,B2,B3"

// turn seats string into array
$prevSeats = [];
if ($seatsCSV !== '') {
    foreach (explode(',', $seatsCSV) as $s) {
        $s = trim($s);
        if ($s !== '') $prevSeats[] = $s;
    }
}

// save edit context in session
$_SESSION['edit_target'] = [
    'cart_index' => $itemIndex,     // which cart item we're editing
    'movie_id'   => $movie_id,
    'show_date'  => $show_date,
    'timeslot'   => $timeslot,
    'timeslot12' => $timeslot12,    // nice display time
    'hall_id'    => $hall_id,
    'prev_seats' => $prevSeats,     // seats to pre-highlight
];

// now send them to the seat selection page for THIS showtime
// change "seat_booking.php" to your actual seat picking page filename
header("Location: seat_booking.php?edit=1");
exit;
