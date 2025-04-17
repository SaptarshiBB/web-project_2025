<?php
require_once 'config/database.php';

// Get search query
$search = isset($_GET['q']) ? $_GET['q'] : '';

// Return empty array if search is too short
if (strlen($search) < 2) {
    echo json_encode([]);
    exit;
}

// Get category filter if provided
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Prepare query based on filters
if (!empty($category)) {
    $sql = "SELECT id, name, category FROM products WHERE category = ? AND (name LIKE ? OR description LIKE ?) ORDER BY name LIMIT 5";
    $stmt = $conn->prepare($sql);
    $search_param = "%$search%";
    $stmt->bind_param("sss", $category, $search_param, $search_param);
} else {
    $sql = "SELECT id, name, category FROM products WHERE name LIKE ? OR description LIKE ? ORDER BY name LIMIT 5";
    $stmt = $conn->prepare($sql);
    $search_param = "%$search%";
    $stmt->bind_param("ss", $search_param, $search_param);
}

$stmt->execute();
$result = $stmt->get_result();
$suggestions = [];

while ($row = $result->fetch_assoc()) {
    $suggestions[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'category' => $row['category']
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($suggestions);
?> 