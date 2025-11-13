<?php
session_start();

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


$movie_id = $_POST['movie_id']   ?? '';
$show_date = $_POST['show_date']  ?? '';
$timeslot = $_POST['timeslot']   ?? '';
$timeslot12 = $_POST['timeslot12'] ?? '';
$hall_id = $_POST['hall_id']    ?? '';
$seatsCSV = $_POST['seats']      ?? ''; 

$prevSeats = [];
if ($seatsCSV !== '') {
    foreach (explode(',', $seatsCSV) as $s) {
        $s = trim($s);
        if ($s !== '') $prevSeats[] = $s;
    }
}

$_SESSION['edit_target'] = [
    'cart_index' => $itemIndex,     
    'movie_id' => $movie_id,
    'show_date' => $show_date,
    'timeslot' => $timeslot,
    'timeslot12' => $timeslot12,    
    'hall_id' => $hall_id,
    'prev_seats' => $prevSeats,     
];

header("Location: seat_booking.php?edit=1");
exit;
