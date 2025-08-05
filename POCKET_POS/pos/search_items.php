<?php
// File: POCKET_POS/pos/search_items.php
// This script handles AJAX requests from the POS terminal to search for inventory items.

// Include database connection
include('../includes/db_connect.php');

// Initialize an empty array for items
$items = [];

// Get the search query from the GET request
if (isset($_GET['query']) && !empty(trim($_GET['query']))) {
    $query = trim($_GET['query']);

    // Use a prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, name, sell_price, stock_quantity, barcode FROM items WHERE name LIKE ? OR barcode LIKE ? LIMIT 10");
    $search_param = "%" . $query . "%";
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Only add items that are in stock
            if ($row['stock_quantity'] > 0) {
                $items[] = $row;
            }
        }
    }
    $stmt->close();
}

// Set the header to return JSON data
header('Content-Type: application/json');
echo json_encode($items);

$conn->close();
?>
