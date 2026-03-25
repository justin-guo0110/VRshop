<?php
// Test the operations API endpoints
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user'] = ['id' => 1, 'role' => 'admin', 'name' => 'Admin', 'email' => 'admin@example.com'];

require_once __DIR__ . '/api/db.php';

// Include operations file (this will handle the routing)
$_GET['action'] = 'list_products';
echo "=== Testing list_products ===\n";
include __DIR__ . '/api/operations.php';
?>

