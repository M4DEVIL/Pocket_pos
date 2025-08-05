<?php
// File: POCKET_POS/includes/db_connect.php
// This file establishes a connection to the MySQL database.
// It's included in all other PHP files that need database access.

// Database credentials
$servername = "localhost";
$username = "root"; // XAMPP default username
$password = "";     // XAMPP default password
$dbname = "pocket_pos";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // We'll die here, as the app can't function without a database connection.
    die("Connection failed: " . $conn->connect_error);
}
?>
