<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$message = '';
$error = '';

// Get product details
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$product_id = $_GET['id'];
$sql = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $stock = $_POST['stock'];
    $current_image = $product['image'];

    // Handle image upload if new image is provided
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        $target_dir = "../uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
        
        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if($check !== false) {
            // Generate unique filename
            $new_image = "uploads/" . uniqid() . "." . $imageFileType;
            $target_file = "../" . $new_image;
            
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                // Delete old image if exists
                if ($current_image && file_exists("../" . $current_image)) {
                    unlink("../" . $current_image);
                }
                $current_image = $new_image;
            } else {
                $error = "Sorry, there was an error uploading your file.";
            }
        } else {
            $error = "File is not an image.";
        }
    }

    if (empty($error)) {
        $sql = "UPDATE products SET name = ?, description = ?, price = ?, category = ?, stock = ?, image = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdsisi", $name, $description, $price, $category, $stock, $current_image, $product_id);

        if ($stmt->execute()) {
            $message = "Product updated successfully!";
            // Refresh product data
            $sql = "SELECT * FROM products WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
        } else {
            $error = "Error updating product: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - AgriCommerce</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-green-600 text-white shadow-lg">
        <div class="container mx-auto px-6 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-2xl font-bold">AgriCommerce</a>
                    <span class="ml-4 text-sm">Admin Dashboard</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="hover:text-green-200">Back to Dashboard</a>
                    <a href="../auth/logout.php" class="hover:text-green-200">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-6 py-8">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold">Edit Product</h2>
            </div>

            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded m-6">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded m-6">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data" class="p-6">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                        Product Name
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           id="name" name="name" type="text" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
                        Description
                    </label>
                    <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                              id="description" name="description" rows="4" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="price">
                        Price (₹)
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           id="price" name="price" type="number" step="0.01" value="<?php echo $product['price']; ?>" required>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="category">
                        Category
                    </label>
                    <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="category" name="category" required>
                        <option value="">Select Category</option>
                        <option value="Seeds" <?php echo $product['category'] == 'Seeds' ? 'selected' : ''; ?>>Seeds</option>
                        <option value="Crop Protection" <?php echo $product['category'] == 'Crop Protection' ? 'selected' : ''; ?>>Crop Protection</option>
                        <option value="Nutrients" <?php echo $product['category'] == 'Nutrients' ? 'selected' : ''; ?>>Nutrients</option>
                        <option value="Equipment" <?php echo $product['category'] == 'Equipment' ? 'selected' : ''; ?>>Equipment</option>
                        <option value="Organic" <?php echo $product['category'] == 'Organic' ? 'selected' : ''; ?>>Organic</option>
                        <option value="Animal Care" <?php echo $product['category'] == 'Animal Care' ? 'selected' : ''; ?>>Animal Care</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="stock">
                        Stock
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           id="stock" name="stock" type="number" value="<?php echo $product['stock']; ?>" required>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="image">
                        Product Image
                    </label>
                    <?php if ($product['image']): ?>
                        <div class="mb-2">
                            <img src="<?php echo '../' . htmlspecialchars($product['image']); ?>" 
                                 alt="Current product image" 
                                 class="h-32 w-32 object-cover rounded">
                        </div>
                    <?php endif; ?>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           id="image" name="image" type="file" accept="image/*">
                    <p class="text-sm text-gray-600 mt-1">Leave empty to keep current image</p>
                </div>

                <div class="flex items-center justify-between">
                    <button class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                            type="submit">
                        Update Product
                    </button>
                    <a href="dashboard.php" class="text-green-600 hover:text-green-800">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 