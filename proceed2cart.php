<?php
session_start();
include "dbconnect.php";

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// Pull what was submitted from seat selection form
$seatListCSV = $_POST['selected_seats'] ?? '';     // "A1,B2,B3"
$hallId      = $_POST['hall_id']      ?? '';
$showDate    = $_POST['show_date']    ?? '';
$timeslot    = $_POST['timeslot']     ?? '';
$movieCode   = $_POST['movie_code']   ?? '';

// We ALSO need movie title + pretty time. We already have them in session['booking'].
$booking = $_SESSION['booking'] ?? [];
$movieTitle = $booking['movie_title'] ?? '';
$timeslot12 = $booking['timeslot_12'] ?? '';

// Convert "A1,B2,B3" -> ["A1","B2","B3"]
$seats = [];
if ($seatListCSV !== '') {
    foreach (explode(',', $seatListCSV) as $p) {
        $p = trim($p);
        if ($p !== '') $seats[] = $p;
    }
}

// Init cart as array if first time
if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// detect edit mode
if (!empty($_SESSION['edit_target']) && isset($_SESSION['edit_target']['cart_index'])) {
    $editIdx = $_SESSION['edit_target']['cart_index'];

    // replace that index instead of pushing new
    if (isset($_SESSION['cart'][$editIdx])) {
        $_SESSION['cart'][$editIdx] = $newItem;
    }

    // clear edit_target so it doesn't affect next booking
    unset($_SESSION['edit_target']);
} else {
    // normal add-to-cart flow
    $_SESSION['cart'][] = $newItem;
}


// Build the new line item
$newItem = [
    'movie_id'    => $movieCode,
    'movie_title' => $movieTitle,
    'hall_id'     => $hallId,
    'show_date'   => $showDate,
    'timeslot'    => $timeslot,
    'timeslot12'  => $timeslot12,
    'seats'       => $seats
];

// Add it to the cart
$_SESSION['cart'][] = $newItem;

// Send user to cart view
header("Location: cart.php");
exit;
