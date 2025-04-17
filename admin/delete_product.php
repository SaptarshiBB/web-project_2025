<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

if (isset($_GET['id'])) {
    $product_id = $_GET['id'];

    // Get product image path before deletion
    $sql = "SELECT image FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    // Delete product
    $sql = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);

    if ($stmt->execute()) {
        // Delete product image if exists
        if ($product['image'] && file_exists("../" . $product['image'])) {
            unlink("../" . $product['image']);
        }
        $_SESSION['message'] = "Product deleted successfully!";
    } else {
        $_SESSION['message'] = "Error deleting product.";
    }
}

header("Location: dashboard.php");
exit(); 