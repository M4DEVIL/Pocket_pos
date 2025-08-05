<?php
// File: POCKET_POS/admin/index.php
// This is the main dashboard for the admin.

session_start();

// Redirect to login if the user is not authenticated as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Include database connection
include('../includes/db_connect.php');

$anniversary_notifications = [];

// --- Check for Monthly Seller Anniversaries ---
// This logic finds sellers whose work anniversary is today (e.g., if they started on the 15th, a notification will show on the 15th of every subsequent month).
$sellers_query = "SELECT username, start_date FROM users WHERE role = 'pos_seller' AND start_date IS NOT NULL";
$sellers_result = $conn->query($sellers_query);

if ($sellers_result && $sellers_result->num_rows > 0) {
    $today = new DateTime();
    while ($seller = $sellers_result->fetch_assoc()) {
        $start_date = new DateTime($seller['start_date']);
        $interval = $today->diff($start_date);
        
        // We only care about anniversaries if at least one month has passed
        if ($interval->m > 0 || $interval->y > 0) {
            // Check if today's day and month match the anniversary
            if ($today->format('m-d') === $start_date->format('m-d')) {
                $anniversary_notifications[] = htmlspecialchars($seller['username']);
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
    <title>Admin Dashboard - POCKET POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .sidebar { background: linear-gradient(to top, #6a11cb 0%, #2575fc 100%); }
    </style>
</head>
<body class="flex min-h-screen">

    <!-- Sidebar -->
    <aside class="sidebar w-64 p-6 flex flex-col text-white fixed h-full shadow-lg">
        <div class="flex items-center mb-8">
            <i class="bi bi-wallet-fill text-3xl mr-2"></i>
            <h1 class="text-2xl font-bold">POCKET POS</h1>
        </div>
        <nav class="flex-1">
            <a href="index.php" class="flex items-center p-3 rounded-lg text-lg font-semibold hover:bg-white/20 transition-all duration-200 mb-2 bg-white/20">
                <i class="bi bi-speedometer2 mr-3"></i> Dashboard
            </a>
            <a href="inventory.php" class="flex items-center p-3 rounded-lg text-lg font-semibold hover:bg-white/20 transition-all duration-200 mb-2">
                <i class="bi bi-box-seam mr-3"></i> Inventory
            </a>
            <a href="sales.php" class="flex items-center p-3 rounded-lg text-lg font-semibold hover:bg-white/20 transition-all duration-200 mb-2">
                <i class="bi bi-graph-up mr-3"></i> Sales History
            </a>
            <a href="accountant.php" class="flex items-center p-3 rounded-lg text-lg font-semibold hover:bg-white/20 transition-all duration-200 mb-2">
                <i class="bi bi-calculator mr-3"></i> Accountant
            </a>
            <a href="sellers.php" class="flex items-center p-3 rounded-lg text-lg font-semibold hover:bg-white/20 transition-all duration-200 mb-2">
                <i class="bi bi-people mr-3"></i> Sellers
            </a>
            <a href="settings.php" class="flex items-center p-3 rounded-lg text-lg font-semibold hover:bg-white/20 transition-all duration-200 mb-2">
                <i class="bi bi-gear mr-3"></i> Settings
            </a>
        </nav>
        <a href="../logout.php" class="flex items-center p-3 rounded-lg text-lg font-semibold hover:bg-white/20 transition-all duration-200">
            <i class="bi bi-box-arrow-left mr-3"></i> Logout
        </a>
    </aside>

    <!-- Main Content -->
    <main class="ml-64 p-8 w-full">
        <header class="flex justify-between items-center mb-8">
            <h2 class="text-4xl font-bold text-gray-800">Admin Dashboard</h2>
        </header>

        <!-- Anniversary Notifications -->
        <?php if (!empty($anniversary_notifications)): ?>
            <div class="bg-indigo-100 text-indigo-800 border-l-4 border-indigo-500 p-4 mb-8 rounded-lg shadow-md">
                <div class="flex items-center">
                    <i class="bi bi-bell-fill text-2xl mr-3"></i>
                    <p class="font-semibold text-lg">Work Anniversary!</p>
                </div>
                <ul class="mt-2 ml-8 list-disc">
                    <?php foreach ($anniversary_notifications as $seller_name): ?>
                        <li><?php echo $seller_name; ?> has completed a month of service.</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Card 1: Total Sales -->
            <div class="bg-white p-6 rounded-2xl shadow-md border border-gray-200 flex items-center justify-between">
                <div>
                    <h3 class="text-gray-500 text-sm font-medium">Total Sales</h3>
                    <p class="mt-1 text-3xl font-semibold text-gray-900">$1,234.50</p>
                </div>
                <div class="text-green-500 p-3 bg-green-100 rounded-full">
                    <i class="bi bi-currency-dollar text-2xl"></i>
                </div>
            </div>

            <!-- Card 2: Items in Stock -->
            <div class="bg-white p-6 rounded-2xl shadow-md border border-gray-200 flex items-center justify-between">
                <div>
                    <h3 class="text-gray-500 text-sm font-medium">Items in Stock</h3>
                    <p class="mt-1 text-3xl font-semibold text-gray-900">125</p>
                </div>
                <div class="text-blue-500 p-3 bg-blue-100 rounded-full">
                    <i class="bi bi-box-seam text-2xl"></i>
                </div>
            </div>

            <!-- Card 3: Today's Revenue -->
            <div class="bg-white p-6 rounded-2xl shadow-md border border-gray-200 flex items-center justify-between">
                <div>
                    <h3 class="text-gray-500 text-sm font-medium">Today's Revenue</h3>
                    <p class="mt-1 text-3xl font-semibold text-gray-900">$234.50</p>
                </div>
                <div class="text-orange-500 p-3 bg-orange-100 rounded-full">
                    <i class="bi bi-arrow-up-right text-2xl"></i>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
