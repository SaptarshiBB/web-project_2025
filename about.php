<?php
session_start();
require_once 'config/database.php';

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
    <title>About Us - AgriCommerce</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Top Bar -->
    <div class="bg-green-700 text-white text-sm py-2 px-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex space-x-4">
                <a href="orders.php" class="hover:text-green-200"><i class="fas fa-truck mr-1"></i> Track Order</a>
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
    <nav class="bg-white shadow-lg">
        <div class="container mx-auto px-6 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center space-x-2">
                        <i class="fas fa-leaf text-2xl text-green-600"></i>
                        <span class="text-2xl font-bold text-gray-800">AgriCommerce</span>
                    </a>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="index.php" class="text-gray-600 hover:text-green-600">Home</a>
                    <a href="index.php#products" class="text-gray-600 hover:text-green-600">Products</a>
                    <a href="about.php" class="text-green-600 font-medium">About Us</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- About Us Section -->
    <div class="container mx-auto px-6 py-12">
        <div class="text-center mb-12" data-aos="fade-up">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">About Our Team</h1>
            <p class="text-gray-600 max-w-2xl mx-auto">
                Meet the dedicated team behind AgriCommerce, working to revolutionize agricultural commerce 
                and bring the best products to farmers across the country.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- Team Member 1 -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden" data-aos="fade-up" data-aos-delay="100">
                <div class="p-6">
                    <div class="text-center mb-4">
                        <div class="w-24 h-24 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-4">
                            <img src="public/tho.jpeg" class="w-full h-full object-cover">
                        </div>
                        <h3 class="text-xl font-bold text-gray-800">Anugrah Thomas</h3>
                        <p class="text-green-600 font-medium">Full Stack Developer</p>
                    </div>
                    <p class="text-gray-600 text-center mb-4">
                    Skilled in both frontend and backend technologies, designs and develops robust, end-to-end web solutions tailored for scalable agri-tech platforms.


                    </p>
                    <div class="text-center text-gray-500">
                        <span class="block text-sm">ID: 12300458</span>
                    </div>
                </div>
            </div>

            <!-- Team Member 2 -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden" data-aos="fade-up" data-aos-delay="200">
                <div class="p-6">
                    <div class="text-center mb-4">
                        <div class="w-24 h-24 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-4">
                        <img src="public/kun.jpeg" class="w-full h-full object-cover">
                        </div>
                        <h3 class="text-xl font-bold text-gray-800">Kunal Sharma</h3>
                        <p class="text-green-600 font-medium">Frontend Developer</p>
                    </div>
                    <p class="text-gray-600 text-center mb-4">
                    Specializes in crafting intuitive and responsive user interfaces, ensures a seamless user experience across all devices and platforms.
                    </p>
                    <div class="text-center text-gray-500">
                        <span class="block text-sm">ID: 12303416</span>
                    </div>
                </div>
            </div>

            <!-- Team Member 3 -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden" data-aos="fade-up" data-aos-delay="300">
                <div class="p-6">
                    <div class="text-center mb-4">
                        <div class="w-24 h-24 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-4">
                        <img src="public/ben.png" class="w-full h-full object-cover">
                        </div>
                        <h3 class="text-xl font-bold text-gray-800">Saptarshi Benerjee</h3>
                        <p class="text-green-600 font-medium">Backend Developer</p>
                    </div>
                    <p class="text-gray-600 text-center mb-4">
                    Focused on building secure and efficient server-side applications, integrates complex systems and APIs to power data-driven platforms.
                    </p>
                    <div class="text-center text-gray-500">
                        <span class="block text-sm">ID: 12305581</span>
                    </div>
                </div>
            </div>

            <!-- Team Member 4 -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden" data-aos="fade-up" data-aos-delay="400">
                <div class="p-6">
                    <div class="text-center mb-4">
                        <div class="w-24 h-24 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-4">
                        <img src="public/prem.jpeg" class="w-full h-full object-cover">
                        </div>
                        <h3 class="text-xl font-bold text-gray-800">Prem Chandra Gupta</h3>
                        <p class="text-green-600 font-medium">Database Developer</p>
                    </div>
                    <p class="text-gray-600 text-center mb-4">
                    Expert in designing, optimizing, and maintaining complex database systems to ensure data integrity, scalability, and high performance.
                    </p>
                    <div class="text-center text-gray-500">
                        <span class="block text-sm">ID: 12301953</span>
                    </div>
                </div>
            </div>
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