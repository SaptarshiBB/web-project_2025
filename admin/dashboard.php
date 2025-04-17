<?php
session_start();
require_once '../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug: Log session information
error_log("Admin Dashboard - Session info: " . print_r($_SESSION, true));

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    error_log("Access denied to admin dashboard. User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set') . 
              ", Role: " . (isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'not set'));
    header("Location: ../auth/login.php");
    exit();
}

// Get statistics
$stats = [
    'users' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
    'products' => $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'],
    'orders' => $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'],
    'revenue' => $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'")->fetch_assoc()['total'] ?? 0
];

// Get recent orders
$recent_orders = $conn->query("
    SELECT o.*, u.name as user_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Get low stock products
$low_stock_products = $conn->query("
    SELECT * FROM products 
    WHERE stock < 10 
    ORDER BY stock ASC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AgriCommerce</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar-link {
            @apply flex items-center space-x-3 px-4 py-3 text-gray-300 hover:bg-green-700/50 hover:text-white rounded-lg transition duration-200;
        }
        .sidebar-link.active {
            @apply bg-green-700 text-white shadow-lg;
        }
        .stat-card {
            @apply bg-white rounded-lg shadow-md p-6 transition duration-300 hover:shadow-lg;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .nav-item {
            @apply relative overflow-hidden;
        }
        .nav-item::after {
            content: '';
            @apply absolute bottom-0 left-0 w-0 h-0.5 bg-white transition-all duration-300;
        }
        .nav-item:hover::after {
            @apply w-full;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-72 bg-gradient-to-b from-green-800 to-green-900 text-white p-6 shadow-xl">
            <div class="mb-10">
                <div class="flex items-center space-x-3 mb-2">
                    <i class="fas fa-leaf text-3xl text-green-400"></i>
                    <h1 class="text-2xl font-bold">AgriCommerce</h1>
                </div>
                <p class="text-green-200 text-sm ml-9">Admin Dashboard</p>
            </div>
            
            <nav class="flex flex-col space-y-3">
                <a href="dashboard.php" class="sidebar-link active group">
                    <i class="fas fa-chart-line text-lg group-hover:scale-110 transition-transform"></i>
                    <span>Dashboard</span>
                </a>
                <a href="users.php" class="sidebar-link group">
                    <i class="fas fa-users text-lg group-hover:scale-110 transition-transform"></i>
                    <span>Users</span>
                </a>
                <a href="products.php" class="sidebar-link group">
                    <i class="fas fa-box text-lg group-hover:scale-110 transition-transform"></i>
                    <span>Products</span>
                </a>
                <a href="orders.php" class="sidebar-link group">
                    <i class="fas fa-shopping-cart text-lg group-hover:scale-110 transition-transform"></i>
                    <span>Orders</span>
                </a>
                <div class="pt-4 mt-4 border-t border-green-700/50">
                    <a href="add_product.php" class="sidebar-link group">
                        <i class="fas fa-plus text-lg group-hover:scale-110 transition-transform"></i>
                        <span>Add Product</span>
                    </a>
                </div>
                <div class="pt-4 mt-4 border-t border-green-700/50">
                    <a href="../auth/logout.php" class="sidebar-link group text-red-300">
                        <i class="fas fa-sign-out-alt text-lg group-hover:scale-110 transition-transform"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <!-- Top Bar -->
            <div class="bg-white shadow-md p-4 sticky top-0 z-10">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <h2 class="text-2xl font-bold text-gray-800">Dashboard Overview</h2>
                        <span class="text-gray-500">|</span>
                        <span class="text-gray-600">Welcome back, Admin</span>
                    </div>
                    <div class="flex items-center space-x-6">
                        <div class="relative">
                            
                        </div>
                        <div class="flex items-center space-x-3">
                            <img src="https://ui-avatars.com/api/?name=Admin&background=0D9488&color=fff" 
                                 alt="Admin" 
                                 class="w-10 h-10 rounded-full">
                            <span class="text-gray-700 font-medium">Admin</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 p-6">
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500">Total Users</p>
                            <h3 class="text-2xl font-bold"><?php echo $stats['users']; ?></h3>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-users text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-green-600 text-sm">
                            <i class="fas fa-arrow-up"></i> 12% increase
                        </span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500">Total Products</p>
                            <h3 class="text-2xl font-bold"><?php echo $stats['products']; ?></h3>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-box text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-blue-600 text-sm">
                            <i class="fas fa-arrow-up"></i> 8% increase
                        </span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500">Total Orders</p>
                            <h3 class="text-2xl font-bold"><?php echo $stats['orders']; ?></h3>
                        </div>
                        <div class="bg-yellow-100 p-3 rounded-full">
                            <i class="fas fa-shopping-cart text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-yellow-600 text-sm">
                            <i class="fas fa-arrow-up"></i> 15% increase
                        </span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500">Total Revenue</p>
                            <h3 class="text-2xl font-bold">₹<?php echo number_format($stats['revenue'], 2); ?></h3>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-rupee-sign text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-purple-600 text-sm">
                            <i class="fas fa-arrow-up"></i> 20% increase
                        </span>
                    </div>
                </div>
            </div>

            <!-- Charts and Tables -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 p-6">
                <!-- Sales Chart -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold mb-4">Sales Overview</h3>
                    <canvas id="salesChart" height="300"></canvas>
                </div>

                <!-- Recent Orders -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Recent Orders</h3>
                        <a href="orders.php" class="text-green-600 hover:text-green-700">View All</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="text-left text-gray-500 text-sm">
                                    <th class="pb-3">Order ID</th>
                                    <th class="pb-3">Customer</th>
                                    <th class="pb-3">Amount</th>
                                    <th class="pb-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                <tr class="border-t">
                                    <td class="py-3">#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                                    <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="px-2 py-1 rounded-full text-xs 
                                            <?php echo $order['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                                    ($order['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                    'bg-red-100 text-red-800'); ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Low Stock Products -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Low Stock Products</h3>
                        <a href="products.php" class="text-green-600 hover:text-green-700">View All</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="text-left text-gray-500 text-sm">
                                    <th class="pb-3">Product</th>
                                    <th class="pb-3">Category</th>
                                    <th class="pb-3">Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($low_stock_products as $product): ?>
                                <tr class="border-t">
                                    <td class="py-3"><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category']); ?></td>
                                    <td>
                                        <span class="px-2 py-1 rounded-full text-xs bg-red-100 text-red-800">
                                            <?php echo $product['stock']; ?> left
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- User Activity -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold mb-4">User Activity</h3>
                    <canvas id="userActivityChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Sales',
                    data: [12000, 19000, 15000, 25000, 22000, 30000],
                    borderColor: '#059669',
                    tension: 0.4,
                    fill: true,
                    backgroundColor: 'rgba(5, 150, 105, 0.1)'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // User Activity Chart
        const userCtx = document.getElementById('userActivityChart').getContext('2d');
        new Chart(userCtx, {
            type: 'bar',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'New Users',
                    data: [65, 59, 80, 81, 56, 55, 40],
                    backgroundColor: '#059669'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Sidebar Navigation
        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.addEventListener('click', function(e) {
                // Only prevent default for non-navigation links (like logout)
                if (this.classList.contains('text-red-300')) {
                    return; // Allow the logout link to work normally
                }
                
                // Update active state
                document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                // Allow the link to navigate to its destination
                // No need to prevent default
            });
        });
    </script>
</body>
</html> 