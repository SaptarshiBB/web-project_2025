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

// Handle product actions (delete, update stock)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['product_id'])) {
        $product_id = $_POST['product_id'];
        $action = $_POST['action'];
        
        if ($action === 'delete') {
            // First delete related cart items
            $stmt = $conn->prepare("DELETE FROM cart WHERE product_id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            
            // Then get the image path to delete the file
            $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            
            if ($product && $product['image']) {
                $image_path = "../" . $product['image'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            // Finally delete the product from database
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            
            $_SESSION['message'] = "Product deleted successfully!";
            header("Location: products.php");
            exit();
        } elseif ($action === 'update_stock') {
            $new_stock = $_POST['new_stock'];
            $stmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
            $stmt->bind_param("ii", $new_stock, $product_id);
            $stmt->execute();
        }
    }
}

// Get products with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$where_clauses = [];
$params = [];
$types = "";

if ($search) {
    $where_clauses[] = "(name LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if ($category) {
    $where_clauses[] = "category = ?";
    $params[] = $category;
    $types .= "s";
}

$where_sql = $where_clauses ? "WHERE " . implode(" AND ", $where_clauses) : "";

$count_sql = "SELECT COUNT(*) as total FROM products " . $where_sql;
$stmt = $conn->prepare($count_sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_products = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_products / $per_page);

$sql = "SELECT * FROM products " . $where_sql . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
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
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get unique categories for filter
$categories = $conn->query("SELECT DISTINCT category FROM products ORDER BY category")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - AgriCommerce Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                <a href="products.php" class="sidebar-link active group">
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
                        <h2 class="text-2xl font-bold text-gray-800">Product Management</h2>
                        <span class="text-gray-500">|</span>
                        <span class="text-gray-600">Manage all products</span>
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

            <div class="container mx-auto px-6 py-8">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Product Management</h1>
                    <div class="flex space-x-4">
                        <form class="flex items-center space-x-4">
                            <input type="text" name="search" placeholder="Search products..." 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            <select name="category" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>"
                                        <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                                Search
                            </button>
                        </form>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <?php if ($product['image']): ?>
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <img class="h-10 w-10 rounded-full object-cover" 
                                                 src="../<?php echo htmlspecialchars($product['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        </div>
                                        <?php endif; ?>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo substr(htmlspecialchars($product['description']), 0, 50) . '...'; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($product['category']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">₹<?php echo number_format($product['price'], 2); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <form method="POST" class="flex items-center space-x-2">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="action" value="update_stock">
                                        <input type="number" name="new_stock" value="<?php echo $product['stock']; ?>" min="0"
                                               class="w-20 text-sm border rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <button type="submit" class="text-green-600 hover:text-green-900">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </form>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="mt-4 flex justify-center">
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category ? '&category=' . urlencode($category) : ''; ?>" 
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