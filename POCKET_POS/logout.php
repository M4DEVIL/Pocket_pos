<?php
// File: POCKET_POS/logout.php
// This script destroys the current session and redirects the user to the landing page.

session_start();
session_unset();    // Unset all of the session variables
session_destroy();  // Destroy the session

header('Location: index.html'); // Redirect to the landing page
exit();
?>
