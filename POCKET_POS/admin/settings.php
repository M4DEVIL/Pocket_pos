<?php
// File: POCKET_POS/admin/settings.php
// This page provides settings for the admin, specifically to change their PIN.

session_start();

// Redirect to login if the user is not authenticated as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Include database connection
include('../includes/db_connect.php');

$message = '';

// --- Handle PIN Change ---
if (isset($_POST['change_pin'])) {
    $new_pin = $_POST['new_pin'];
    $confirm_pin = $_POST['confirm_pin'];

    if ($new_pin !== $confirm_pin) {
        $message = "<div class='bg-red-500/30 text-red-100 p-3 rounded-lg'>New PINs do not match.</div>";
    } else {
        $hashed_pin = password_hash($new_pin, PASSWORD_DEFAULT);
        $admin_id = $_SESSION['user_id'];

        $stmt = $conn->prepare("UPDATE users SET pin = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_pin, $admin_id);
        
        if ($stmt->execute()) {
            $message = "<div class='bg-green-500/30 text-green-100 p-3 rounded-lg'>Admin PIN changed successfully!</div>";
        } else {
            $message = "<div class='bg-red-500/30 text-red-100 p-3 rounded-lg'>Error changing PIN.</div>";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - POCKET POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .sidebar { background: linear-gradient(to top, #6a11cb 0%, #2575fc 100%); }
        .btn-gradient { background-image: linear-gradient(to right, #6a11cb 0%, #2575fc 100%); }
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
            <a href="index.php" class="flex items-center p-3 rounded-lg text-lg font-semibold hover:bg-white/20 transition-all duration-200 mb-2">
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
            <a href="settings.php" class="flex items-center p-3 rounded-lg text-lg font-semibold hover:bg-white/20 transition-all duration-200 mb-2 bg-white/20">
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
            <h2 class="text-4xl font-bold text-gray-800">Settings</h2>
        </header>

        <?php echo $message; ?>

        <!-- Change Admin PIN Section -->
        <div class="bg-white p-6 rounded-2xl shadow-md border border-gray-200 mb-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-4">Change Admin PIN</h3>
            <form action="settings.php" method="POST">
                <div class="mb-4">
                    <label for="new_pin" class="block text-gray-700 font-semibold mb-2">New PIN</label>
                    <input type="password" name="new_pin" id="new_pin" required maxlength="4" pattern="\d{4}"
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="mb-6">
                    <label for="confirm_pin" class="block text-gray-700 font-semibold mb-2">Confirm New PIN</label>
                    <input type="password" name="confirm_pin" id="confirm_pin" required maxlength="4" pattern="\d{4}"
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <button type="submit" name="change_pin" class="btn-gradient text-white py-3 px-6 rounded-xl font-semibold hover:shadow-lg transition-all duration-200">
                    <i class="bi bi-key mr-2"></i> Change PIN
                </button>
            </form>
        </div>
    </main>
</body>
</html>
