<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

// Fetch user's orders
$user_id = $_SESSION['user_id'];
$sql = "SELECT o.*, 
        COUNT(oi.id) as total_items,
        GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.name) SEPARATOR ', ') as items
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - AgriCommerce</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-green-600 text-white shadow-lg">
        <div class="container mx-auto px-6 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center space-x-2">
                        <i class="fas fa-leaf text-2xl"></i>
                        <span class="text-2xl font-bold">AgriCommerce</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="auth/logout.php" class="hover:text-green-200">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Orders Section -->
    <div class="container mx-auto px-6 py-8">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold">My Orders</h1>
            <a href="index.php" class="text-green-600 hover:text-green-800">
                <i class="fas fa-arrow-left mr-2"></i>Back to Home
            </a>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <p class="text-gray-600 mb-4">You haven't placed any orders yet.</p>
                <a href="index.php" class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
                    Start Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($orders as $order): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold">Order #<?php echo $order['id']; ?></h3>
                                    <p class="text-sm text-gray-500">
                                        Placed on <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="inline-block px-3 py-1 rounded-full text-sm 
                                        <?php echo $order['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                            ($order['status'] === 'processing' ? 'bg-blue-100 text-blue-800' : 
                                            ($order['status'] === 'shipped' ? 'bg-purple-100 text-purple-800' : 
                                            ($order['status'] === 'delivered' ? 'bg-green-100 text-green-800' : 
                                            'bg-red-100 text-red-800'))); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="border-t pt-4">
                                <p class="text-sm text-gray-600 mb-2"><?php echo $order['items']; ?></p>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-500">Total Items: <?php echo $order['total_items']; ?></span>
                                    <span class="font-semibold">₹<?php echo number_format($order['total_amount'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>
</body>
</html> 