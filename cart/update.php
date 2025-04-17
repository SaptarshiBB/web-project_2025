<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cart_id']) && isset($_POST['quantity'])) {
    $cart_id = $_POST['cart_id'];
    $quantity = (int)$_POST['quantity'];
    $user_id = $_SESSION['user_id'];

    if ($quantity < 1) {
        $quantity = 1;
    }

    // Verify cart item belongs to user
    $sql = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $quantity, $cart_id, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Cart updated successfully!";
    } else {
        $_SESSION['message'] = "Error updating cart.";
    }
}

header("Location: ../cart.php");
exit(); 