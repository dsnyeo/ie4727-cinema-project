<?php
session_start();
include "dbconnect.php";


// 1. Make sure the cart exists
if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    // Nothing to remove — just go back
    header("Location: cart.php");
    exit;
}

// 2. Get which item to remove from POST
$itemIndex = $_POST['item_index'] ?? null;

// Basic validation: must be numeric and must exist in cart
if ($itemIndex === null || !is_numeric($itemIndex)) {
    // Invalid index provided
    header("Location: cart.php");
    exit;
}

$itemIndex = (int)$itemIndex;

// 3. If that index exists, unset it
if (isset($_SESSION['cart'][$itemIndex])) {
    unset($_SESSION['cart'][$itemIndex]);

    // 4. Reindex array so future loops use 0,1,2,... nicely
    $_SESSION['cart'] = array_values($_SESSION['cart']);
}

// 5. Redirect back to cart page
header("Location: cart.php");
exit;
?>