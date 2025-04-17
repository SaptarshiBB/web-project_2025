<?php
session_start();
require_once 'config/database.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch product details
$sql = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

// If product not found, redirect to home page
if (!$product) {
    header("Location: index.php");
    exit();
}

// Get cart count if user is logged in
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $cart_query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($cart_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_result = $stmt->get_result()->fetch_assoc();
    $cart_count = $cart_result['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - AgriCommerce</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Top Bar -->
    <div class="bg-green-700 text-white text-sm py-2 px-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex space-x-4">
                <!-- <span><i class="fas fa-globe mr-1"></i> English</span> -->
                <!-- <span><i class="fas fa-truck mr-1"></i> Track Order</span> -->
                <!-- <span><i class="fas fa-heart mr-1"></i> Wishlist</span> -->
            </div>
            <div class="flex space-x-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="auth/logout.php" class="hover:text-green-200">Logout</a>
                <?php else: ?>
                    <a href="auth/login.php" class="hover:text-green-200"><i class="fas fa-user mr-1"></i> Login</a>
                <?php endif; ?>
                <a href="cart.php" class="relative hover:text-green-200">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($cart_count > 0): ?>
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">
                            <?php echo $cart_count; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-6 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center space-x-2">
                        <i class="fas fa-leaf text-2xl text-green-600"></i>
                        <span class="text-2xl font-bold text-green-600">AgriCommerce</span>
                    </a>
                </div>
                
                <div class="hidden md:flex items-center space-x-8">
                    <a href="index.php?category=Seeds" class="font-medium hover:text-green-600">Seeds</a>
                    <a href="index.php?category=Crop Protection" class="font-medium hover:text-green-600">Crop Protection</a>
                    <a href="index.php?category=Nutrients" class="font-medium hover:text-green-600">Crop Nutrition</a>
                    <a href="index.php?category=Equipment" class="font-medium hover:text-green-600">Equipment</a>
                    <a href="index.php?category=Organic" class="font-medium hover:text-green-600">Organic</a>
                    <a href="index.php?category=Animal Care" class="font-medium hover:text-green-600">Animal Care</a>
                </div>
                
                <div class="md:hidden">
                    <button class="text-gray-600 focus:outline-none">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Product Details -->
    <div class="container mx-auto px-6 py-12">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 p-8">
                <!-- Product Image -->
                <div class="relative">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         class="w-full h-96 object-cover rounded-lg">
                    <div class="absolute top-4 right-4 bg-green-500 text-white text-sm font-bold px-3 py-1 rounded">
                        <?php echo htmlspecialchars($product['category']); ?>
                    </div>
                </div>

                <!-- Product Info -->
                <div class="flex flex-col">
                    <h1 class="text-3xl font-bold mb-4"><?php echo htmlspecialchars($product['name']); ?></h1>
                    <div class="text-2xl font-bold text-green-600 mb-4">
                        ₹<?php echo number_format($product['price'], 2); ?>
                    </div>
                    <div class="text-gray-600 mb-6">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </div>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <form action="cart/add.php" method="POST" class="mt-auto">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                                Add to Cart
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="auth/login.php" class="block w-full text-center bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                            Login to Purchase
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Back to Products -->
        <div class="mt-8">
            <a href="index.php" class="text-green-600 hover:text-green-800 font-medium">
                <i class="fas fa-arrow-left mr-2"></i>Back to Products
            </a>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true
        });
    </script>
</body>
</html> 