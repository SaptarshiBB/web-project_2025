<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $password);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Registration successful! Please login.";
        header("Location: login.php");
        exit();
    } else {
        $error = "Registration failed. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - AgriCommerce</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full mx-4">
            
            <div class="bg-white rounded-lg shadow-md p-8">
                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                <div class="text-center mb-8">
                <div class="flex items-center justify-center space-x-3 mb-2">
                    <i class="fas fa-leaf text-4xl text-green-600"></i>
                    <h1 class="text-3xl font-bold text-gray-800">AgriCommerce</h1>
                </div>
                <p class="text-gray-600">Create your account</p>
            </div>
                <form method="POST" action="" class="space-y-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                            Full Name
                        </label>
                        <input class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                               id="name" name="name" type="text" required>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                            Email Address
                        </label>
                        <input class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                               id="email" name="email" type="email" required>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                            Password
                        </label>
                        <input class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                               id="password" name="password" type="password" required>
                    </div>

                    <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition duration-200">
                        Register
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        Already have an account? 
                        <a href="login.php" class="text-green-600 hover:text-green-800">Login here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 