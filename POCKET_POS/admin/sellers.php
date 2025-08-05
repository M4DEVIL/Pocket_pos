<?php
// File: POCKET_POS/admin/sellers.php
// This page allows the admin to manage POS sellers.

session_start();

// Redirect to login if the user is not authenticated as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Include database connection
include('../includes/db_connect.php');

$message = '';

// --- Handle adding a new seller ---
if (isset($_POST['add_seller'])) {
    $username = trim($_POST['username']);
    $pin = $_POST['pin'];
    $start_date = date('Y-m-d'); // Set the start date to today

    if (!empty($username) && !empty($pin)) {
        // Hash the PIN for security
        $hashed_pin = password_hash($pin, PASSWORD_DEFAULT);

        // Prepare and execute the SQL statement to add the new seller
        $stmt = $conn->prepare("INSERT INTO users (username, pin, role, start_date) VALUES (?, ?, 'pos_seller', ?)");
        $stmt->bind_param("sss", $username, $hashed_pin, $start_date);
        
        if ($stmt->execute()) {
            $message = "<div class='bg-green-500/30 text-green-100 p-3 rounded-lg'>Seller '{$username}' added successfully!</div>";
        } else {
            $message = "<div class='bg-red-500/30 text-red-100 p-3 rounded-lg'>Error adding seller: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='bg-red-500/30 text-red-100 p-3 rounded-lg'>Username and PIN are required.</div>";
    }
}

// --- Fetch all sellers for the table display ---
$sellers = [];
$sellers_query = "SELECT id, username, start_date FROM users WHERE role = 'pos_seller' ORDER BY username ASC";
$result = $conn->query($sellers_query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sellers[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sellers - POCKET POS</title>
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
            <a href="sellers.php" class="flex items-center p-3 rounded-lg text-lg font-semibold hover:bg-white/20 transition-all duration-200 mb-2 bg-white/20">
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
            <h2 class="text-4xl font-bold text-gray-800">Manage Sellers</h2>
        </header>

        <?php echo $message; ?>

        <!-- Add New Seller Section -->
        <div class="bg-white p-6 rounded-2xl shadow-md border border-gray-200 mb-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-4">Add New Seller</h3>
            <form action="sellers.php" method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="username" class="block text-gray-700 font-semibold mb-2">Username</label>
                        <input type="text" name="username" id="username" required
                               class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="pin" class="block text-gray-700 font-semibold mb-2">PIN</label>
                        <input type="password" name="pin" id="pin" required maxlength="4" pattern="\d{4}"
                               class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <button type="submit" name="add_seller" class="mt-6 btn-gradient text-white py-3 px-6 rounded-xl font-semibold hover:shadow-lg transition-all duration-200">
                    <i class="bi bi-person-add mr-2"></i> Add Seller
                </button>
            </form>
        </div>

        <!-- Sellers Table Section -->
        <div class="bg-white p-6 rounded-2xl shadow-md border border-gray-200">
            <h3 class="text-2xl font-bold text-gray-800 mb-4">Current Sellers</h3>
            <?php if (!empty($sellers)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white rounded-lg">
                        <thead>
                            <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-6 text-left">Username</th>
                                <th class="py-3 px-6 text-left">Start Date</th>
                                <th class="py-3 px-6 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm font-light">
                            <?php foreach ($sellers as $seller): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="py-3 px-6 text-left whitespace-nowrap"><?php echo htmlspecialchars($seller['username']); ?></td>
                                    <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($seller['start_date']); ?></td>
                                    <td class="py-3 px-6 text-left">
                                        <!-- Actions could include editing or deleting a seller -->
                                        <button class="text-red-500 hover:text-red-700 transition-colors duration-200">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500">No sellers have been added yet.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
