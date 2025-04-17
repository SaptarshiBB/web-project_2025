<?php
require_once 'config/database.php';

// Read and execute the SQL file
$sql = file_get_contents('sql/orders.sql');

// Split the SQL file into individual queries
$queries = array_filter(array_map('trim', explode(';', $sql)));

// Execute each query
foreach ($queries as $query) {
    if (!empty($query)) {
        if ($conn->query($query)) {
            echo "Successfully executed: " . substr($query, 0, 50) . "...<br>";
        } else {
            echo "Error executing query: " . $conn->error . "<br>";
        }
    }
}

echo "Orders tables setup completed!";
?> 