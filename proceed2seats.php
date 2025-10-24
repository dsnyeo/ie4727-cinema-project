<?php
// booking.php
session_start();
require "dbconnect.php";

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

if (!isset($_POST['movie_id'], $_POST['movie_title'], $_POST['show_date'], $_POST['hall_id'], $_POST['timeslot'])) {
  http_response_code(400);
  die("Missing booking data.");
}

$movieId   = trim($_POST['movie_id']);
$movieName = trim($_POST['movie_title']);   // will verify below
$showDate  = trim($_POST['show_date']);     // expected YYYY-MM-DD
$hallId    = trim($_POST['hall_id']);
$timeslot  = trim($_POST['timeslot']);      // HH:MM:SS (24h)

$sql = "SELECT Title FROM movies WHERE MovieCode = ?";
if (!($st = $dbcnx->prepare($sql))) die("Prepare failed (title check).");
$st->bind_param("s", $movieId);
$st->execute();
$res = $st->get_result();
$dbMovie = $res->fetch_assoc();
$st->close();

if ($dbMovie && !empty($dbMovie['Title'])) {
  $movieName = $dbMovie['Title']; // trust DB
}

// Little formatting: keep both raw and pretty time
$timePretty = date("g:i A", strtotime($timeslot)); // e.g., 5:30 PM

$_SESSION['booking'] = [
  'movie_id'    => $movieId,
  'movie_title' => $movieName,
  'show_date'   => $showDate,
  'hall_id'     => $hallId,
  'timeslot'    => $timeslot,
  'timeslot_12' => $timePretty,
];

// Go to seat selection page
header("Location: seat_booking.php");
exit;
