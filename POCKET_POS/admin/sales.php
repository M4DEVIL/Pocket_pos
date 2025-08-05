<?php
// File: POCKET_POS/admin/sales.php
// This page displays a log of all sales and allows for filtering and refunds.

session_start();

// Redirect to login if the user is not authenticated as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Include database connection
include('../includes/db_connect.php');

// Define a message variable for success/error alerts
$message = '';

// Handle refund action
if (isset($_POST['refund_sale'])) {
    $sale_id = $_POST['sale_id'];

    // Update the sale record to mark it as refunded
    $stmt = $conn->prepare("UPDATE sales SET is_refund = 1 WHERE id = ?");
    $stmt->bind_param("i", $sale_id);

    if ($stmt->execute()) {
        $message = "<div class='bg-green-500/30 text-green-100 p-3 rounded-lg'>Sale #{$sale_id} has been refunded.</div>";
    } else {
        $message = "<div class='bg-red-500/30 text-red-100 p-3 rounded-lg'>Error refunding sale: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// Fetch all sales, joining with the users table to get seller names
$sales_query = "
    SELECT
        s.id,
        s.total_amount,
        s.created_at,
        s.is_refund,
        u.username AS seller_name
    FROM sales s
    LEFT JOIN users u ON s.seller_id = u.id
    ORDER BY s.created_at DESC
";
$sales_result = $conn->query($sales_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales History - POCKET POS</title>
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
            <a href="index.php" class="flex items-center p-3 rounded-lg text-lg font-semibold hover:bg-white/20 transition-all duration-200 mb-2">
                <i class="bi bi-speedometer2 mr-3"></i> Dashboard
            </a>
            <a href="inventory.php" class="flex items-center p-3 rounded-lg text-lg font-semibold hover:bg-white/20 transition-all duration-200 mb-2">
                <i class="bi bi-box-seam mr-3"></i> Inventory
            </a>
            <a href="sales.php" class="flex items-center p-3 rounded-lg text-lg font-semibold hover:bg-white/20 transition-all duration-200 mb-2 bg-white/20">
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
            <h2 class="text-4xl font-bold text-gray-800">Sales History</h2>
        </header>

        <?php echo $message; ?>

        <!-- Sales List Table -->
        <div class="bg-white p-6 rounded-2xl shadow-md border border-gray-200 mt-8 overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sale ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Seller</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if ($sales_result->num_rows > 0): ?>
                        <?php while ($row = $sales_result->fetch_assoc()): ?>
                            <tr class="<?php echo ($row['is_refund']) ? 'bg-red-50' : ''; ?>">
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['id']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['seller_name'] ?? 'N/A'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">$<?php echo number_format($row['total_amount'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['created_at']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo ($row['is_refund']) ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
                                        <?php echo ($row['is_refund']) ? 'Refunded' : 'Completed'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if (!$row['is_refund']): ?>
                                        <form action="sales.php" method="POST" onsubmit="return confirm('Are you sure you want to refund this sale?');" class="inline-block">
                                            <input type="hidden" name="refund_sale" value="1">
                                            <input type="hidden" name="sale_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900"><i class="bi bi-arrow-return-left"></i> Refund</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No sales records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>
</body>
</html>
