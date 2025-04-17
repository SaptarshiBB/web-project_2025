<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

// Check if cart is empty
$user_id = $_SESSION['user_id'];
$sql = "SELECT c.id as cart_id, c.quantity, p.* 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($cart_items)) {
    $_SESSION['message'] = "Your cart is empty. Please add items before checkout.";
    header("Location: cart.php");
    exit();
}

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Handle order confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $conn->begin_transaction();

        // Create new order
        $order_sql = "INSERT INTO orders (user_id, total_amount, status, created_at) VALUES (?, ?, 'pending', NOW())";
        $stmt = $conn->prepare($order_sql);
        $stmt->bind_param("id", $user_id, $total);
        $stmt->execute();
        $order_id = $conn->insert_id;

        // Add order items
        $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($item_sql);
        
        foreach ($cart_items as $item) {
            $stmt->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
            $stmt->execute();
        }

        // Clear the cart
        $clear_cart_sql = "DELETE FROM cart WHERE user_id = ?";
        $stmt = $conn->prepare($clear_cart_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        $_SESSION['message'] = "Order placed successfully! Order ID: #" . $order_id;
        header("Location: orders.php");
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = "Error placing order. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - AgriCommerce</title>
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

    <!-- Checkout Section -->
    <div class="container mx-auto px-6 py-8">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold">Checkout</h1>
            <a href="cart.php" class="text-green-600 hover:text-green-800">
                <i class="fas fa-arrow-left mr-2"></i>Back to Cart
            </a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Order Summary -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold mb-4">Order Summary</h2>
                <div class="space-y-4">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="flex justify-between items-center">
                            <div class="flex items-center">
                                <img class="h-12 w-12 object-cover rounded" 
                                     src="<?php echo htmlspecialchars($item['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <div class="ml-4">
                                    <div class="text-sm font-medium"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="text-sm text-gray-500">Quantity: <?php echo $item['quantity']; ?></div>
                                </div>
                            </div>
                            <div class="text-sm font-medium">
                                ₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="border-t pt-4">
                        <div class="flex justify-between items-center font-bold">
                            <span>Total</span>
                            <span>₹<?php echo number_format($total, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Confirmation Form -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold mb-4">Confirm Order</h2>
                <p class="text-gray-600 mb-6">Please review your order and click the button below to confirm your purchase.</p>
                
                <form action="checkout.php" method="POST">
                    <button type="submit" class="w-full bg-green-500 text-white py-3 px-6 rounded-lg hover:bg-green-600 transition duration-300">
                        Confirm Order
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>
</body>
</html> 