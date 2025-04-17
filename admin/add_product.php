<?php
session_start();
require_once '../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug: Log session information
error_log("Add Product - Session info: " . print_r($_SESSION, true));

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    error_log("Access denied to add_product.php. User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set') . 
              ", Role: " . (isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'not set'));
    header("Location: ../auth/login.php");
    exit();
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $category = trim($_POST['category']);
    
    // Validate inputs
    if (empty($name) || empty($description) || $price <= 0 || $stock < 0) {
        $error = "Please fill all required fields correctly.";
    } else {
        // Handle image upload
        $image_path = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/products/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = uniqid() . '.' . $file_extension;
                $target_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    $image_path = 'uploads/products/' . $new_filename;
                } else {
                    $error = "Failed to upload image.";
                }
            } else {
                $error = "Invalid file type. Allowed types: " . implode(', ', $allowed_extensions);
            }
        }
        
        if (empty($error)) {
            // Insert product into database
            $sql = "INSERT INTO products (name, description, price, stock, category, image) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdiis", $name, $description, $price, $stock, $category, $image_path);
            
            if ($stmt->execute()) {
                $message = "Product added successfully!";
                // Clear form data
                $name = $description = $category = '';
                $price = $stock = 0;
            } else {
                $error = "Error adding product: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - AgriCommerce Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-green-800 text-white shadow-lg">
            <div class="container mx-auto px-6 py-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <a href="dashboard.php" class="text-2xl font-bold">AgriCommerce</a>
                        <span class="ml-4 text-sm">Admin Panel</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="dashboard.php" class="hover:text-green-200">Dashboard</a>
                        <a href="../auth/logout.php" class="hover:text-green-200">Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container mx-auto px-6 py-8">
            <div class="max-w-2xl mx-auto">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold mb-6">Add New Product</h2>
                    
                    <?php if ($message): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form action="add_product.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                                Product Name *
                            </label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>"
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                   required>
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
                                Description *
                            </label>
                            <textarea id="description" name="description" rows="4"
                                      class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                      required><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="price">
                                    Price (₹) *
                                </label>
                                <input type="number" id="price" name="price" step="0.01" min="0"
                                       value="<?php echo $price ?? '0.00'; ?>"
                                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                       required>
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="stock">
                                    Stock *
                                </label>
                                <input type="number" id="stock" name="stock" min="0"
                                       value="<?php echo $stock ?? '0'; ?>"
                                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                       required>
                            </div>
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="category">
                                Category *
                            </label>
                            <select id="category" name="category"
                                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                    required>
                                <option value="">Select Category</option>
                                <option value="Seeds" <?php echo (isset($category) && $category === 'Seeds') ? 'selected' : ''; ?>>Seeds</option>
                                <option value="Crop Protection" <?php echo (isset($category) && $category === 'Crop Protection') ? 'selected' : ''; ?>>Crop Protection</option>
                                <option value="Nutrients" <?php echo (isset($category) && $category === 'Nutrients') ? 'selected' : ''; ?>>Nutrients</option>
                                <option value="Equipment" <?php echo (isset($category) && $category === 'Equipment') ? 'selected' : ''; ?>>Equipment</option>
                                <option value="Organic" <?php echo (isset($category) && $category === 'Organic') ? 'selected' : ''; ?>>Organic</option>
                                <option value="Animal Care" <?php echo (isset($category) && $category === 'Animal Care') ? 'selected' : ''; ?>>Animal Care</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="image">
                                Product Image
                            </label>
                            <input type="file" id="image" name="image" accept="image/*"
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            <p class="text-sm text-gray-500 mt-1">Allowed formats: JPG, JPEG, PNG, WEBP</p>
                        </div>

                        <div class="flex items-center justify-between pt-4">
                            <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition duration-200">
                                Add Product
                            </button>
                            <a href="dashboard.php" class="text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Preview image before upload
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.createElement('img');
                    preview.src = e.target.result;
                    preview.className = 'mt-2 max-w-xs rounded-lg shadow-md';
                    
                    const container = document.getElementById('image').parentElement;
                    const existingPreview = container.querySelector('img');
                    if (existingPreview) {
                        container.removeChild(existingPreview);
                    }
                    container.appendChild(preview);
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html> 