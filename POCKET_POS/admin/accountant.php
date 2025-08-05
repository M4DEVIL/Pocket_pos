<?php
// File: POCKET_POS/admin/accountant.php
// This page provides an accounting overview and management for the admin.

session_start();

// Redirect to login if the user is not authenticated as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Include database connection
include('../includes/db_connect.php');

// Initialize message variable
$message = '';
$message_type = '';

// --- Handle Form Submissions ---

// Handle Add Income
if (isset($_POST['add_income'])) {
    $description = trim($_POST['description']);
    $amount = $_POST['amount'];
    $date = date('Y-m-d');

    $stmt = $conn->prepare("INSERT INTO income (description, amount, date) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $description, $amount, $date);

    if ($stmt->execute()) {
        $message = "Income '{$description}' added successfully!";
        $message_type = 'success';
    } else {
        $message = "Error adding income: " . $stmt->error;
        $message_type = 'error';
    }
    $stmt->close();
}

// Handle Add Expense
if (isset($_POST['add_expense'])) {
    $description = trim($_POST['description']);
    $amount = $_POST['amount'];
    $date = date('Y-m-d');

    $stmt = $conn->prepare("INSERT INTO expenses (description, amount, date) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $description, $amount, $date);

    if ($stmt->execute()) {
        $message = "Expense '{$description}' added successfully!";
        $message_type = 'success';
    } else {
        $message = "Error adding expense: " . $stmt->error;
        $message_type = 'error';
    }
    $stmt->close();
}

// Handle Remove Income
if (isset($_POST['remove_income'])) {
    $id = $_POST['income_id'];

    $stmt = $conn->prepare("DELETE FROM income WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $message = "Income record removed successfully!";
        $message_type = 'success';
    } else {
        $message = "Error removing income record: " . $stmt->error;
        $message_type = 'error';
    }
    $stmt->close();
}

// Handle Remove Expense
if (isset($_POST['remove_expense'])) {
    $id = $_POST['expense_id'];

    $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $message = "Expense record removed successfully!";
        $message_type = 'success';
    } else {
        $message = "Error removing expense record: " . $stmt->error;
        $message_type = 'error';
    }
    $stmt->close();
}

// --- Fetch data for calculations and display ---
$total_income = 0;
$total_expenses = 0;
$gross_profit = 0;
$net_profit = 0;

$income_items = [];
$income_query = "SELECT * FROM income ORDER BY date DESC";
$income_result = $conn->query($income_query);
if ($income_result && $income_result->num_rows > 0) {
    while ($row = $income_result->fetch_assoc()) {
        $income_items[] = $row;
        $total_income += $row['amount'];
    }
}

$expense_items = [];
$expense_query = "SELECT * FROM expenses ORDER BY date DESC";
$expense_result = $conn->query($expense_query);
if ($expense_result && $expense_result->num_rows > 0) {
    while ($row = $expense_result->fetch_assoc()) {
        $expense_items[] = $row;
        $total_expenses += $row['amount'];
    }
}

$gross_profit = $total_income;
$net_profit = $total_income - $total_expenses;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accountant - POCKET POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .sidebar { background: linear-gradient(to top, #6a11cb 0%, #2575fc 100%); }
        .btn-gradient { background-image: linear-gradient(to right, #6a11cb 0%, #2575fc 100%); }
        .card-shadow { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
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
            <a href="accountant.php" class="flex items-center p-3 rounded-lg text-lg font-semibold hover:bg-white/20 transition-all duration-200 mb-2 bg-white/20">
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
            <h2 class="text-4xl font-bold text-gray-800">Accountant</h2>
        </header>

        <!-- Timed Notification -->
        <?php if (!empty($message)): ?>
            <div id="notification" class="fixed bottom-4 right-4 z-50 p-4 rounded-xl shadow-lg text-white transition-opacity duration-500
                <?php echo $message_type === 'success' ? 'bg-green-600' : 'bg-red-600'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Financial Overview Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Gross Income Card -->
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white p-6 rounded-2xl card-shadow">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-lg font-semibold">Gross Income</h3>
                    <i class="bi bi-graph-up text-2xl"></i>
                </div>
                <p class="text-5xl font-bold">$<?php echo number_format($gross_profit, 2); ?></p>
            </div>
            <!-- Net Profit Card -->
            <div class="bg-gradient-to-r from-emerald-500 to-green-600 text-white p-6 rounded-2xl card-shadow">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-lg font-semibold">Net Profit</h3>
                    <i class="bi bi-currency-dollar text-2xl"></i>
                </div>
                <p class="text-5xl font-bold">$<?php echo number_format($net_profit, 2); ?></p>
            </div>
        </div>

        <!-- Income and Expense Tables -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Income Table Section -->
            <div class="bg-white p-6 rounded-2xl shadow-md border border-gray-200">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-2xl font-bold text-gray-800">Income</h3>
                    <button onclick="openModal('addIncomeModal')" class="btn-gradient text-white py-2 px-4 rounded-xl font-semibold hover:shadow-lg transition-all duration-200 text-sm">
                        <i class="bi bi-plus-circle mr-2"></i> Add Income
                    </button>
                </div>
                <?php if (!empty($income_items)): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white rounded-lg">
                            <thead>
                                <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                                    <th class="py-3 px-6 text-left">Description</th>
                                    <th class="py-3 px-6 text-left">Amount</th>
                                    <th class="py-3 px-6 text-left">Date</th>
                                    <th class="py-3 px-6 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 text-sm font-light">
                                <?php foreach ($income_items as $item): ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="py-3 px-6 whitespace-nowrap"><?php echo htmlspecialchars($item['description']); ?></td>
                                        <td class="py-3 px-6">$<?php echo htmlspecialchars(number_format($item['amount'], 2)); ?></td>
                                        <td class="py-3 px-6"><?php echo htmlspecialchars($item['date']); ?></td>
                                        <td class="py-3 px-6">
                                            <form action="accountant.php" method="POST" onsubmit="return confirm('Are you sure you want to remove this income record?');">
                                                <input type="hidden" name="income_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" name="remove_income" class="text-red-500 hover:text-red-700 transition-colors duration-200">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500">No income records found.</p>
                <?php endif; ?>
            </div>

            <!-- Expense Table Section -->
            <div class="bg-white p-6 rounded-2xl shadow-md border border-gray-200">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-2xl font-bold text-gray-800">Expenses</h3>
                    <button onclick="openModal('addExpenseModal')" class="bg-red-500 text-white py-2 px-4 rounded-xl font-semibold hover:bg-red-600 transition-all duration-200 text-sm">
                        <i class="bi bi-plus-circle mr-2"></i> Add Expense
                    </button>
                </div>
                <?php if (!empty($expense_items)): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white rounded-lg">
                            <thead>
                                <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                                    <th class="py-3 px-6 text-left">Description</th>
                                    <th class="py-3 px-6 text-left">Amount</th>
                                    <th class="py-3 px-6 text-left">Date</th>
                                    <th class="py-3 px-6 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 text-sm font-light">
                                <?php foreach ($expense_items as $item): ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="py-3 px-6 whitespace-nowrap"><?php echo htmlspecialchars($item['description']); ?></td>
                                        <td class="py-3 px-6">$<?php echo htmlspecialchars(number_format($item['amount'], 2)); ?></td>
                                        <td class="py-3 px-6"><?php echo htmlspecialchars($item['date']); ?></td>
                                        <td class="py-3 px-6">
                                            <form action="accountant.php" method="POST" onsubmit="return confirm('Are you sure you want to remove this expense record?');">
                                                <input type="hidden" name="expense_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" name="remove_expense" class="text-red-500 hover:text-red-700 transition-colors duration-200">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500">No expense records found.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <!-- Add Income Modal -->
    <div id="addIncomeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-2xl bg-white">
            <h3 class="text-2xl font-bold text-gray-800 mb-4">Add New Income</h3>
            <form action="accountant.php" method="POST">
                <div class="mb-4">
                    <label for="income_description" class="block text-gray-700 font-semibold mb-2">Description</label>
                    <input type="text" name="description" id="income_description" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="mb-4">
                    <label for="income_amount" class="block text-gray-700 font-semibold mb-2">Amount ($)</label>
                    <input type="number" step="0.01" name="amount" id="income_amount" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="flex justify-end space-x-4 mt-4">
                    <button type="button" onclick="closeModal('addIncomeModal')" class="bg-gray-300 text-gray-800 py-3 px-6 rounded-xl font-semibold hover:bg-gray-400 transition-all duration-200">Cancel</button>
                    <button type="submit" name="add_income" class="btn-gradient text-white py-3 px-6 rounded-xl font-semibold hover:shadow-lg transition-all duration-200">
                        <i class="bi bi-plus-circle mr-2"></i> Add Income
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Expense Modal -->
    <div id="addExpenseModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-2xl bg-white">
            <h3 class="text-2xl font-bold text-gray-800 mb-4">Add New Expense</h3>
            <form action="accountant.php" method="POST">
                <div class="mb-4">
                    <label for="expense_description" class="block text-gray-700 font-semibold mb-2">Description</label>
                    <input type="text" name="description" id="expense_description" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="mb-4">
                    <label for="expense_amount" class="block text-gray-700 font-semibold mb-2">Amount ($)</label>
                    <input type="number" step="0.01" name="amount" id="expense_amount" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="flex justify-end space-x-4 mt-4">
                    <button type="button" onclick="closeModal('addExpenseModal')" class="bg-gray-300 text-gray-800 py-3 px-6 rounded-xl font-semibold hover:bg-gray-400 transition-all duration-200">Cancel</button>
                    <button type="submit" name="add_expense" class="bg-red-500 text-white py-3 px-6 rounded-xl font-semibold hover:bg-red-600 transition-all duration-200">
                        <i class="bi bi-plus-circle mr-2"></i> Add Expense
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // --- Timed Notification Logic ---
        function closeNotification() {
            const notification = document.getElementById('notification');
            if (notification) {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 500);
            }
        }
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(closeNotification, 5000); // 5 seconds
        });
        
        // --- Modal Logic ---
        function openModal(modalId) {
             const modal = document.getElementById(modalId);
             modal.classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }
    </script>
</body>
</html>
