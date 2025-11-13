<?php
session_start();
include "dbconnect.php";

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

if (isset($_SESSION['cart'][$itemIndex])) {
    unset($_SESSION['cart'][$itemIndex]);
    $_SESSION['cart'] = array_values($_SESSION['cart']);
}

header("Location: cart.php");
exit;
?>