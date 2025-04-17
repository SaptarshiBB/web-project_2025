<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cart_id'])) {
    $cart_id = $_POST['cart_id'];
    $user_id = $_SESSION['user_id'];

    // Verify cart item belongs to user and delete it
    $sql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $cart_id, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Item removed from cart successfully!";
    } else {
        $_SESSION['message'] = "Error removing item from cart.";
    }
}

header("Location: ../cart.php");
exit(); 