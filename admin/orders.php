<?php
session_start();
require_once '../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    
    $update_sql = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $status, $order_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Order status updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating order status.";
    }
    
    header("Location: orders.php");
    exit();
}

// Get orders with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$where_clauses = [];
$params = [];
$types = "";

if ($search) {
    $where_clauses[] = "(o.id LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if ($status_filter) {
    $where_clauses[] = "o.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$where_sql = $where_clauses ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get total orders count
$count_sql = "SELECT COUNT(*) as total FROM orders o JOIN users u ON o.user_id = u.id " . $where_sql;
$stmt = $conn->prepare($count_sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_orders = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $per_page);

// Get orders with user information
$sql = "SELECT o.*, 
        u.name as user_name,
        u.email as user_email,
        COUNT(oi.id) as total_items,
        GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.name) SEPARATOR ', ') as items
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        " . $where_sql . "
        GROUP BY o.id, o.user_id, o.total_amount, o.status, o.created_at, u.name, u.email
        ORDER BY o.created_at DESC 
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if ($params) {
    $params[] = $per_page;
    $params[] = $offset;
    $types .= "ii";
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param("ii", $per_page, $offset);
}
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get unique order statuses for filter
$statuses = $conn->query("SELECT DISTINCT status FROM orders ORDER BY status")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - AgriCommerce Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar-link {
            @apply flex items-center space-x-3 px-4 py-3 text-gray-300 hover:bg-green-700/50 hover:text-white rounded-lg transition duration-200;
        }
        .sidebar-link.active {
            @apply bg-green-700 text-white shadow-lg;
        }
        .status-badge {
            @apply px-2 py-1 rounded-full text-xs font-medium;
        }
        .status-pending {
            @apply bg-yellow-100 text-yellow-800;
        }
        .status-processing {
            @apply bg-blue-100 text-blue-800;
        }
        .status-completed {
            @apply bg-green-100 text-green-800;
        }
        .status-cancelled {
            @apply bg-red-100 text-red-800;
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
                <a href="dashboard.php" class="sidebar-link group">
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
                <a href="orders.php" class="sidebar-link active group">
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
                        <h2 class="text-2xl font-bold text-gray-800">Order Management</h2>
                        <span class="text-gray-500">|</span>
                        <span class="text-gray-600">Manage all orders</span>
                    </div>
                    <div class="flex items-center space-x-6">
                        <div class="flex items-center space-x-3">
                            <img src="https://ui-avatars.com/api/?name=Admin&background=0D9488&color=fff" 
                                 alt="Admin" 
                                 class="w-10 h-10 rounded-full">
                            <span class="text-gray-700 font-medium">Admin</span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded m-6">
                    <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded m-6">
                    <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="p-6">
                <!-- Search and Filter -->
                <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                    <form action="orders.php" method="GET" class="flex flex-wrap items-center gap-4">
                        <div class="flex-1 min-w-[200px]">
                            <input type="text" name="search" placeholder="Search by order ID, customer name or email" 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div class="min-w-[150px]">
                            <select name="status" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">All Statuses</option>
                                <?php foreach ($statuses as $status): ?>
                                <option value="<?php echo htmlspecialchars($status['status']); ?>"
                                        <?php echo $status_filter === $status['status'] ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(htmlspecialchars($status['status'])); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition duration-200">
                            <i class="fas fa-search mr-2"></i>Search
                        </button>
                    </form>
                </div>

                <!-- Orders Table -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    No orders found
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">#<?php echo $order['id']; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($order['user_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($order['user_email']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo $order['items']; ?></div>
                                    <div class="text-sm text-gray-500">Total Items: <?php echo $order['total_items']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">₹<?php echo number_format($order['total_amount'], 2); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-block px-3 py-1 rounded-full text-sm 
                                        <?php echo $order['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                            ($order['status'] === 'processing' ? 'bg-blue-100 text-blue-800' : 
                                            ($order['status'] === 'shipped' ? 'bg-purple-100 text-purple-800' : 
                                            ($order['status'] === 'delivered' ? 'bg-green-100 text-green-800' : 
                                            'bg-red-100 text-red-800'))); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo date('g:i A', strtotime($order['created_at'])); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <form action="orders.php" method="POST" class="inline">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="status" onchange="this.form.submit()" 
                                                class="text-sm border rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-green-500">
                                            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="mt-4 flex justify-center">
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50
                                  <?php echo $i === $page ? 'z-10 bg-green-50 border-green-500 text-green-600' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 