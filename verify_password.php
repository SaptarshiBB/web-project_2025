<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Admin password from database
$stored_hash = '$2y$10$8TqZc1qgVxlxU1sL0VLj2ODIx.kpLd6DmHGRwqYpeJXHQJKzXxE0G';
$test_password = 'admin123';

// Verify the password
$result = password_verify($test_password, $stored_hash);

echo "Password verification result: " . ($result ? "TRUE" : "FALSE") . "<br>";

// Generate a new hash for comparison
$new_hash = password_hash($test_password, PASSWORD_DEFAULT);
echo "New hash for 'admin123': " . $new_hash . "<br>";

// Verify with the new hash
$result2 = password_verify($test_password, $new_hash);
echo "Verification with new hash: " . ($result2 ? "TRUE" : "FALSE") . "<br>";
?> 