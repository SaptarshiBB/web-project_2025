<?php
session_start();
require_once 'config/database.php';

// Get category filter from URL
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Get search query from URL
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch products with category filter and search
if (!empty($category) && !empty($search)) {
    // Both category and search filter
    $sql = "SELECT * FROM products WHERE category = ? AND (name LIKE ? OR description LIKE ?) ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $search_param = "%$search%";
    $stmt->bind_param("sss", $category, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} elseif (!empty($category)) {
    // Only category filter
    $sql = "SELECT * FROM products WHERE category = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
} elseif (!empty($search)) {
    // Only search filter
    $sql = "SELECT * FROM products WHERE name LIKE ? OR description LIKE ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $search_param = "%$search%";
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // No filters
    $sql = "SELECT * FROM products ORDER BY created_at DESC";
    $result = $conn->query($sql);
}
$products = $result->fetch_all(MYSQLI_ASSOC);

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
    <title>AgriCommerce - Your Agricultural Marketplace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.8s ease-out forwards;
        }
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .product-card:hover {
            transform: scale(1.03);
        }
        .hero-bg {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
        }
        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 0 0 0.5rem 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 50;
            max-height: 300px;
            overflow-y: auto;
            display: none;
        }
        .suggestion-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .suggestion-item:hover {
            background-color: #f9f9f9;
        }
        .suggestion-item:last-child {
            border-bottom: none;
        }
        .suggestion-category {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Top Bar -->
    <div class="bg-green-700 text-white text-sm py-2 px-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex space-x-4">
                <!-- <span><i class="fas fa-globe mr-1"></i> English</span> -->
                <a href="orders.php" class="hover:text-green-200"><i class="fas fa-truck mr-1"></i> Track Order</a>
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
                    <a href="index.php?category=Seeds" class="font-medium hover:text-green-600 <?php echo $category === 'Seeds' ? 'text-green-600' : ''; ?>">Seeds</a>
                    <a href="index.php?category=Crop Protection" class="font-medium hover:text-green-600 <?php echo $category === 'Crop Protection' ? 'text-green-600' : ''; ?>">Crop Protection</a>
                    <a href="index.php?category=Nutrients" class="font-medium hover:text-green-600 <?php echo $category === 'Nutrients' ? 'text-green-600' : ''; ?>">Crop Nutrition</a>
                    <a href="index.php?category=Equipment" class="font-medium hover:text-green-600 <?php echo $category === 'Equipment' ? 'text-green-600' : ''; ?>">Equipment</a>
                    <a href="index.php?category=Organic" class="font-medium hover:text-green-600 <?php echo $category === 'Organic' ? 'text-green-600' : ''; ?>">Organic</a>
                    <a href="index.php?category=Animal Care" class="font-medium hover:text-green-600 <?php echo $category === 'Animal Care' ? 'text-green-600' : ''; ?>">Animal Care</a>
                </div>
                
                <div class="md:hidden">
                    <button class="text-gray-600 focus:outline-none">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Search Bar -->
    <div class="bg-green-50 py-4">
        <div class="container mx-auto px-6">
            <div class="relative max-w-2xl mx-auto">
                <form action="index.php" method="GET" class="flex">
                    <?php if (!empty($category)): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                    <?php endif; ?>
                    <input type="text" name="search" id="search-input" placeholder="Search for seeds, fertilizers, tools..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="w-full py-3 px-5 rounded-l-full border border-green-300 focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-r-full transition duration-300">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                <div id="search-suggestions" class="search-suggestions"></div>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <div class="hero-bg text-white py-24">
        <div class="container mx-auto text-center px-4" data-aos="fade-up">
            <h1 class="text-4xl md:text-5xl font-bold mb-6">GROW MORE, WORRY LESS</h1>
            <p class="text-xl md:text-2xl mb-8">Premium Agricultural Products for Maximum Yield</p>
            <div class="flex justify-center space-x-4">
                <a href="#products" class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-full font-medium transition duration-300">Shop Now</a>
                <a href="about.php" class="bg-white hover:bg-gray-100 text-green-600 px-8 py-3 rounded-full font-medium transition duration-300">About Us</a>
            </div>
        </div>
    </div>

    <!-- Categories Section -->
    <div class="container mx-auto px-6 py-12">
        <h2 class="text-2xl font-bold mb-8 text-center">Shop By Category</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
            <a href="index.php?category=Seeds" class="category-card bg-white rounded-lg shadow-md p-6 text-center transition duration-300 <?php echo $category === 'Seeds' ? 'ring-2 ring-green-500' : ''; ?>" data-aos="fade-up" data-aos-delay="100">
                <div class="bg-green-100 rounded-full p-4 inline-block mb-3">
                    <i class="fas fa-seedling text-green-600 text-2xl"></i>
                </div>
                <h3 class="font-semibold">Seeds</h3>
            </a>
            <a href="index.php?category=Crop Protection" class="category-card bg-white rounded-lg shadow-md p-6 text-center transition duration-300 <?php echo $category === 'Crop Protection' ? 'ring-2 ring-green-500' : ''; ?>" data-aos="fade-up" data-aos-delay="200">
                <div class="bg-green-100 rounded-full p-4 inline-block mb-3">
                    <i class="fas fa-spray-can text-green-600 text-2xl"></i>
                </div>
                <h3 class="font-semibold">Crop Protection</h3>
            </a>
            <a href="index.php?category=Nutrients" class="category-card bg-white rounded-lg shadow-md p-6 text-center transition duration-300 <?php echo $category === 'Nutrients' ? 'ring-2 ring-green-500' : ''; ?>" data-aos="fade-up" data-aos-delay="300">
                <div class="bg-green-100 rounded-full p-4 inline-block mb-3">
                    <i class="fas fa-flask text-green-600 text-2xl"></i>
                </div>
                <h3 class="font-semibold">Nutrients</h3>
            </a>
            <a href="index.php?category=Equipment" class="category-card bg-white rounded-lg shadow-md p-6 text-center transition duration-300 <?php echo $category === 'Equipment' ? 'ring-2 ring-green-500' : ''; ?>" data-aos="fade-up" data-aos-delay="400">
                <div class="bg-green-100 rounded-full p-4 inline-block mb-3">
                    <i class="fas fa-tools text-green-600 text-2xl"></i>
                </div>
                <h3 class="font-semibold">Equipment</h3>
            </a>
            <a href="index.php?category=Organic" class="category-card bg-white rounded-lg shadow-md p-6 text-center transition duration-300 <?php echo $category === 'Organic' ? 'ring-2 ring-green-500' : ''; ?>" data-aos="fade-up" data-aos-delay="500">
                <div class="bg-green-100 rounded-full p-4 inline-block mb-3">
                    <i class="fas fa-leaf text-green-600 text-2xl"></i>
                </div>
                <h3 class="font-semibold">Organic</h3>
            </a>
            <a href="index.php?category=Animal Care" class="category-card bg-white rounded-lg shadow-md p-6 text-center transition duration-300 <?php echo $category === 'Animal Care' ? 'ring-2 ring-green-500' : ''; ?>" data-aos="fade-up" data-aos-delay="600">
                <div class="bg-green-100 rounded-full p-4 inline-block mb-3">
                    <i class="fas fa-paw text-green-600 text-2xl"></i>
                </div>
                <h3 class="font-semibold">Animal Care</h3>
            </a>
        </div>
    </div>

    <!-- Featured Banner -->
    <div class="bg-green-700 text-white py-8 my-8">
        <div class="container mx-auto px-6 text-center" data-aos="zoom-in">
            <h2 class="text-2xl md:text-3xl font-bold mb-2">PREMIUM QUALITY, GUARANTEED</h2>
            <p class="text-lg">Trusted by farmers across the country</p>
        </div>
    </div>

    <!-- Products Section -->
    <div class="container mx-auto px-6 py-12" id="products">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-2xl font-bold">
                <?php if (!empty($category) && !empty($search)): ?>
                    <?php echo htmlspecialchars($category); ?> Products - Search: "<?php echo htmlspecialchars($search); ?>"
                <?php elseif (!empty($category)): ?>
                    <?php echo htmlspecialchars($category); ?> Products
                <?php elseif (!empty($search)): ?>
                    Search Results: "<?php echo htmlspecialchars($search); ?>"
                <?php else: ?>
                    Featured Products
                <?php endif; ?>
            </h2>
            <?php if (!empty($category) || !empty($search)): ?>
                <a href="index.php" class="text-green-600 hover:text-green-800 font-medium">View All Products</a>
            <?php else: ?>
                <a href="#" class="text-green-600 hover:text-green-800 font-medium">View All</a>
            <?php endif; ?>
        </div>
        
        <?php if (empty($products)): ?>
            <div class="text-center py-8" data-aos="fade-up">
                <p class="text-gray-600">No products available at the moment.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($products as $index => $product): ?>
                    <div class="product-card bg-white rounded-lg shadow-md overflow-hidden transition duration-300" 
                         data-aos="fade-up" data-aos-delay="<?php echo ($index % 4) * 100; ?>">
                        <a href="product.php?id=<?php echo $product['id']; ?>" class="block">
                            <div class="relative">
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     class="w-full h-48 object-cover">
                                <div class="absolute top-2 right-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded">
                                    NEW
                                </div>
                            </div>
                            <div class="p-4">
                                <h3 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="text-gray-600 mb-4 text-sm"><?php echo htmlspecialchars($product['description']); ?></p>
                                <div class="flex items-center justify-between">
                                    <span class="text-green-600 font-bold">₹<?php echo number_format($product['price'], 2); ?></span>
                                </div>
                            </div>
                        </a>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="p-4 pt-0">
                                <form action="cart/add.php" method="POST" class="inline">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-full text-sm transition duration-300">
                                        Add to Cart
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="p-4 pt-0">
                                <a href="auth/login.php" class="block w-full text-center text-green-600 hover:text-green-800 text-sm">
                                    Login to Purchase
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Testimonials -->
    <div class="bg-gray-50 py-16">
        <div class="container mx-auto px-6">
            <h2 class="text-2xl font-bold mb-12 text-center">What Farmers Say</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white p-6 rounded-lg shadow-md" data-aos="fade-up">
                    <div class="flex items-center mb-4">
                        <div class="text-yellow-400 mr-2">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <p class="text-gray-600 mb-4">"The seeds from AgriCommerce gave me 20% better yield compared to local market seeds. Highly recommended!"</p>
                    <div class="flex items-center">
                        <div class="bg-green-100 rounded-full w-10 h-10 flex items-center justify-center mr-3">
                            <i class="fas fa-user text-green-600"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold">Rajesh Kumar</h4>
                            <p class="text-gray-500 text-sm">Farmer, Punjab</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="100">
                    <div class="flex items-center mb-4">
                        <div class="text-yellow-400 mr-2">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <p class="text-gray-600 mb-4">"Their delivery is super fast and products are always fresh. Saved my crops during pest attack last season."</p>
                    <div class="flex items-center">
                        <div class="bg-green-100 rounded-full w-10 h-10 flex items-center justify-center mr-3">
                            <i class="fas fa-user text-green-600"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold">Priya Sharma</h4>
                            <p class="text-gray-500 text-sm">Farm Owner, Maharashtra</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="200">
                    <div class="flex items-center mb-4">
                        <div class="text-yellow-400 mr-2">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                    </div>
                    <p class="text-gray-600 mb-4">"Excellent customer service. They helped me choose the right fertilizer for my soil type. Yield increased significantly."</p>
                    <div class="flex items-center">
                        <div class="bg-green-100 rounded-full w-10 h-10 flex items-center justify-center mr-3">
                            <i class="fas fa-user text-green-600"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold">Anil Patel</h4>
                            <p class="text-gray-500 text-sm">Agriculturalist, Gujarat</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Newsletter -->
    <div class="bg-green-600 text-white py-12">
        <div class="container mx-auto px-6 text-center" data-aos="zoom-in">
            <h2 class="text-2xl md:text-3xl font-bold mb-4">Stay Updated</h2>
            <p class="mb-6 max-w-2xl mx-auto">Subscribe to our newsletter for farming tips, new products, and exclusive offers.</p>
            <div class="max-w-md mx-auto flex">
                <input type="email" placeholder="Your email address" class="flex-grow py-3 px-4 rounded-l-full focus:outline-none text-gray-800">
                <button class="bg-green-800 hover:bg-green-900 py-3 px-6 rounded-r-full font-medium transition duration-300">
                    Subscribe
                </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-green-800 text-white pt-12 pb-6">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">AgriCommerce</h3>
                    <p class="text-green-200">Your trusted partner in agricultural growth and success.</p>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="index.php" class="text-green-200 hover:text-white">Home</a></li>
                        <li><a href="index.php#products" class="text-green-200 hover:text-white">Products</a></li>
                        <li><a href="about.php" class="text-green-200 hover:text-white">About Us</a></li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li><a href="cart.php" class="text-green-200 hover:text-white">My Cart</a></li>
                            <li><a href="orders.php" class="text-green-200 hover:text-white">My Orders</a></li>
                        <?php else: ?>
                            <li><a href="auth/login.php" class="text-green-200 hover:text-white">Login</a></li>
                            <li><a href="auth/register.php" class="text-green-200 hover:text-white">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Categories</h4>
                    <ul class="space-y-2">
                        <li><a href="index.php?category=Seeds" class="text-green-200 hover:text-white">Seeds</a></li>
                        <li><a href="index.php?category=Crop Protection" class="text-green-200 hover:text-white">Crop Protection</a></li>
                        <li><a href="index.php?category=Nutrients" class="text-green-200 hover:text-white">Nutrients</a></li>
                        <li><a href="index.php?category=Equipment" class="text-green-200 hover:text-white">Equipment</a></li>
                        <li><a href="index.php?category=Organic" class="text-green-200 hover:text-white">Organic</a></li>
                        <li><a href="index.php?category=Animal Care" class="text-green-200 hover:text-white">Animal Care</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Contact Us</h4>
                    <ul class="space-y-2">
                        <li class="flex items-center"><i class="fas fa-map-marker-alt mr-2"></i> Lovely Professional University</li>
                        <li class="flex items-center"><i class="fas fa-phone mr-2"></i> +91 871912XXXX</li>
                        <li class="flex items-center"><i class="fas fa-envelope mr-2"></i> info@agricommerce.com</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-green-700 pt-6 flex flex-col md:flex-row justify-between items-center">
                <p>&copy; 2025 AgriCommerce. All rights reserved.</p>
                <div class="flex space-x-4 mt-4 md:mt-0">
                    <a href="https://github.com/anugrahthomas" class="text-green-200 hover:text-white"><i class="fab fa-github"></i></a>
                    <a href="https://x.com/_anugrahthomas" class="text-green-200 hover:text-white"><i class="fab fa-twitter"></i></a>
                    <a href="https://www.instagram.com/_anugrahthomas/" class="text-green-200 hover:text-white"><i class="fab fa-instagram"></i></a>
                    <a href="https://in.linkedin.com/in/anugrah-thomas-5070a9266" class="text-green-200 hover:text-white"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true
        });

        // Search suggestions functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-input');
            const suggestionsContainer = document.getElementById('search-suggestions');
            let currentCategory = '<?php echo htmlspecialchars($category); ?>';
            let debounceTimer;

            // Show suggestions when input is focused and has content
            searchInput.addEventListener('focus', function() {
                if (this.value.length >= 2) {
                    fetchSuggestions(this.value);
                }
            });

            // Fetch suggestions as user types
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                if (this.value.length >= 2) {
                    debounceTimer = setTimeout(() => {
                        fetchSuggestions(this.value);
                    }, 300);
                } else {
                    suggestionsContainer.style.display = 'none';
                }
            });

            // Hide suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                    suggestionsContainer.style.display = 'none';
                }
            });

            // Fetch suggestions from server
            function fetchSuggestions(query) {
                let url = `search_suggestions.php?q=${encodeURIComponent(query)}`;
                if (currentCategory) {
                    url += `&category=${encodeURIComponent(currentCategory)}`;
                }

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0) {
                            displaySuggestions(data);
                        } else {
                            suggestionsContainer.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching suggestions:', error);
                        suggestionsContainer.style.display = 'none';
                    });
            }

            // Display suggestions in the container
            function displaySuggestions(suggestions) {
                suggestionsContainer.innerHTML = '';
                
                suggestions.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'suggestion-item';
                    div.innerHTML = `
                        <div>${item.name}</div>
                        <div class="suggestion-category">${item.category}</div>
                    `;
                    
                    div.addEventListener('click', function() {
                        searchInput.value = item.name;
                        suggestionsContainer.style.display = 'none';
                        document.querySelector('form').submit();
                    });
                    
                    suggestionsContainer.appendChild(div);
                });
                
                suggestionsContainer.style.display = 'block';
            }

            // Handle category navigation
            document.querySelectorAll('a[href^="index.php?category="]').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const category = this.getAttribute('href').split('=')[1];
                    loadProductsByCategory(category);
                    
                    // Update active state
                    document.querySelectorAll('a[href^="index.php?category="]').forEach(l => {
                        l.classList.remove('text-green-600');
                    });
                    this.classList.add('text-green-600');
                    
                    // Update URL without reload
                    const url = new URL(window.location);
                    url.searchParams.set('category', category);
                    window.history.pushState({}, '', url);
                });
            });

            // Function to load products by category
            function loadProductsByCategory(category) {
                const productsContainer = document.querySelector('.grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-4');
                const productsTitle = document.querySelector('h2.text-2xl.font-bold');
                
                // Show loading state
                productsContainer.innerHTML = '<div class="col-span-full text-center py-8">Loading products...</div>';
                
                fetch(`index.php?category=${encodeURIComponent(category)}`)
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        
                        // Update products grid
                        const newProducts = doc.querySelector('.grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-4');
                        if (newProducts) {
                            productsContainer.innerHTML = newProducts.innerHTML;
                        }
                        
                        // Update title
                        const newTitle = doc.querySelector('h2.text-2xl.font-bold');
                        if (newTitle) {
                            productsTitle.innerHTML = newTitle.innerHTML;
                        }
                        
                        // Reinitialize AOS for new products
                        AOS.refresh();
                    })
                    .catch(error => {
                        console.error('Error loading products:', error);
                        productsContainer.innerHTML = '<div class="col-span-full text-center py-8 text-red-600">Error loading products. Please try again.</div>';
                    });
            }
        });
    </script>
</body>
</html>