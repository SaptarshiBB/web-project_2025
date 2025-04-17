<?php
session_start();
require_once '../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        $sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                // Debug: Log successful login
                error_log("User logged in successfully. ID: " . $user['id'] . ", Role: " . $user['role']);
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: ../admin/dashboard.php");
                } else {
                    header("Location: ../index.php");
                }
                exit();
            } else {
                $error = "Invalid email or password";
            }
        } else {
            $error = "Invalid email or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AgriCommerce</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full mx-4">
            
            
            <div class="bg-white rounded-lg shadow-md p-8">
            <div class="text-center mb-8">
                <div class="flex items-center justify-center space-x-3 mb-2">
                    <i class="fas fa-leaf text-4xl text-green-600"></i>
                    <h1 class="text-3xl font-bold text-gray-800">AgriCommerce</h1>
                </div>
                <p class="text-gray-600">Login to your account</p>
            </div>
                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" class="space-y-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                            Email Address
                        </label>
                        <input type="email" id="email" name="email" required
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                            Password
                        </label>
                        <input type="password" id="password" name="password" required
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input type="checkbox" id="remember" name="remember" class="h-4 w-4 text-green-600">
                            <label for="remember" class="ml-2 text-sm text-gray-600">Remember me</label>
                        </div>
                        <a href="reset_password.php" class="text-sm text-green-600 hover:text-green-800">Forgot password?</a>
                    </div>

                    <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition duration-200">
                        Login
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        Don't have an account? 
                        <a href="register.php" class="text-green-600 hover:text-green-800">Register here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 