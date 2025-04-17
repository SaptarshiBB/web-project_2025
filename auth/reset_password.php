<?php
session_start();
require_once '../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate input
    if (empty($email) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = "Email not found";
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashed_password, $email);
            
            if ($stmt->execute()) {
                $success = "Password updated successfully. You can now login with your new password.";
            } else {
                $error = "Error updating password. Please try again.";
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
    <title>Reset Password - AgriCommerce</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">

        <div class="bg-white rounded-lg shadow-md p-8">
        <div class="text-center mb-8">
            <div class="flex items-center justify-center space-x-3 mb-2">
                <i class="fas fa-leaf text-4xl text-green-600"></i>
                <h1 class="text-3xl font-bold text-gray-800">AgriCommerce</h1>
            </div>
            <p class="text-gray-600">Reset your password</p>
        </div>
            <?php if ($error): ?>
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    <?php echo htmlspecialchars($success); ?>
                    <div class="mt-4">
                        <a href="login.php" class="text-green-600 hover:text-green-800">Go to Login</a>
                    </div>
                </div>
            <?php else: ?>
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="email" name="email" required
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
                    </div>

                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                        <input type="password" id="new_password" name="new_password" required
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
                    </div>

                    <div>
                        <button type="submit"
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Reset Password
                        </button>
                    </div>
                </form>

                <div class="mt-6 text-center">
                    <a href="login.php" class="text-sm text-green-600 hover:text-green-800">
                        Back to Login
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 