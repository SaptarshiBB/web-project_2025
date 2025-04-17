<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$conn = new mysqli('localhost', 'root', '', 'agri_ecommerce');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Admin credentials
$name = "Admin";
$email = "admin@example.com";
$password = "admin123";
$role = "admin";

// Generate a fresh password hash
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Check if admin already exists
$check_sql = "SELECT id FROM users WHERE email = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $email);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing admin
    $update_sql = "UPDATE users SET password = ? WHERE email = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ss", $password_hash, $email);
    
    if ($update_stmt->execute()) {
        echo "Admin user updated successfully!<br>";
        echo "Email: " . $email . "<br>";
        echo "Password: " . $password . "<br>";
    } else {
        echo "Error updating admin user: " . $conn->error;
    }
} else {
    // Create new admin
    $insert_sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ssss", $name, $email, $password_hash, $role);
    
    if ($insert_stmt->execute()) {
        echo "Admin user created successfully!<br>";
        echo "Email: " . $email . "<br>";
        echo "Password: " . $password . "<br>";
    } else {
        echo "Error creating admin user: " . $conn->error;
    }
}

// Verify the password hash
$verify_result = password_verify($password, $password_hash);
echo "Password verification test: " . ($verify_result ? "SUCCESS" : "FAILED") . "<br>";

$conn->close();
?> 