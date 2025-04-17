<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

// Fetch cart items with product details
$user_id = $_SESSION['user_id'];
$sql = "SELECT c.id as cart_id, c.quantity, p.* 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - AgriCommerce</title>
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

    <!-- Cart Section -->
    <div class="container mx-auto px-6 py-8">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold">Your Shopping Cart</h1>
            <a href="index.php" class="text-green-600 hover:text-green-800">
                <i class="fas fa-arrow-left mr-2"></i>Continue Shopping
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

        <?php if (empty($cart_items)): ?>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <p class="text-gray-600 mb-4">Your cart is empty</p>
                <a href="index.php" class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
                    Continue Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <img class="h-16 w-16 object-cover rounded" 
                                             src="<?php echo htmlspecialchars($item['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">₹<?php echo number_format($item['price'], 2); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <form action="cart/update.php" method="POST" class="flex items-center">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                               min="1" class="w-16 px-2 py-1 border rounded">
                                        <button type="submit" class="ml-2 text-green-600 hover:text-green-900">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </form>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        ₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <form action="cart/remove.php" method="POST" class="inline">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="px-6 py-4 bg-gray-50">
                    <div class="flex justify-between items-center">
                        <div class="text-lg font-semibold">
                            Total: ₹<?php echo number_format($total, 2); ?>
                        </div>
                        <div class="flex space-x-4">
                            <a href="index.php" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                                Continue Shopping
                            </a>
                            <a href="checkout.php" class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
                                Proceed to Checkout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>
</body>
</html> 