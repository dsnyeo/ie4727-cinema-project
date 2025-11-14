<?php
session_start();
include "dbconnect.php";

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$seatListCSV = $_POST['selected_seats'] ?? ''; 
$hallId      = $_POST['hall_id']      ?? '';
$showDate    = $_POST['show_date']    ?? '';
$timeslot    = $_POST['timeslot']     ?? '';
$movieCode   = $_POST['movie_code']   ?? '';

$booking = $_SESSION['booking'] ?? [];
$movieTitle = $booking['movie_title'] ?? '';
$timeslot12 = $booking['timeslot_12'] ?? '';

$seats = [];
if ($seatListCSV !== '') {
    foreach (explode(',', $seatListCSV) as $p) {
        $p = trim($p);
        if ($p !== '') $seats[] = $p;
    }
}

if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (!empty($_SESSION['edit_target']) && isset($_SESSION['edit_target']['cart_index'])) {
    $editIdx = $_SESSION['edit_target']['cart_index'];

    if (isset($_SESSION['cart'][$editIdx])) {
        $_SESSION['cart'][$editIdx] = $newItem;
    }

    unset($_SESSION['edit_target']);
} else {
    $_SESSION['cart'][] = $newItem;
}


$newItem = [
    'movie_id'    => $movieCode,
    'movie_title' => $movieTitle,
    'hall_id'     => $hallId,
    'show_date'   => $showDate,
    'timeslot'    => $timeslot,
    'timeslot12'  => $timeslot12,
    'seats'       => $seats
];

$_SESSION['cart'][] = $newItem;

header("Location: cart.php");
exit;
