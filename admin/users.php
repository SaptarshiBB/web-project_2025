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

// Handle user actions (delete, change role)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $user_id = $_POST['user_id'];
        $action = $_POST['action'];
        
        if ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
        } elseif ($action === 'change_role') {
            $new_role = $_POST['new_role'];
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->bind_param("si", $new_role, $user_id);
            $stmt->execute();
        }
    }
}

// Get users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$where_clause = $search ? "WHERE name LIKE ? OR email LIKE ?" : "";

$count_sql = "SELECT COUNT(*) as total FROM users " . $where_clause;
if ($search) {
    $search_param = "%$search%";
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param("ss", $search_param, $search_param);
} else {
    $stmt = $conn->prepare($count_sql);
}
$stmt->execute();
$total_users = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_users / $per_page);

$sql = "SELECT * FROM users " . $where_clause . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
if ($search) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $search_param, $search_param, $per_page, $offset);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $per_page, $offset);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - AgriCommerce Admin</title>
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
                <a href="users.php" class="sidebar-link active group">
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
                        <h2 class="text-2xl font-bold text-gray-800">User Management</h2>
                        <span class="text-gray-500">|</span>
                        <span class="text-gray-600">Manage all users</span>
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
                    <h1 class="text-2xl font-bold text-gray-800">User Management</h1>
                    <div class="flex space-x-4">
                        <form class="flex items-center">
                            <input type="text" name="search" placeholder="Search users..." 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            <button type="submit" class="ml-2 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                                Search
                            </button>
                        </form>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                <span class="text-gray-500 text-lg"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <form method="POST" class="flex items-center space-x-2">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="action" value="change_role">
                                        <select name="new_role" onchange="this.form.submit()" 
                                                class="text-sm border rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                <?php echo $user['role'] === 'admin' ? 'disabled' : ''; ?>>
                                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if ($user['role'] !== 'admin'): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                    </form>
                                    <?php endif; ?>
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
                        <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
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