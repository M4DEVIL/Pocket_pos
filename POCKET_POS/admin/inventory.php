<?php
// File: POCKET_POS/admin/inventory.php
// This page allows the admin to manage inventory items with barcode scanning

session_start();

// Redirect to login if not authenticated as admin
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

// Handle Add Item
if (isset($_POST['add_item'])) {
    $name = trim($_POST['name']);
    $buy_price = $_POST['buy_price'];
    $sell_price = $_POST['sell_price'];
    $margin = $_POST['margin'];
    $barcode = $_POST['barcode'];
    $provider = trim($_POST['provider']);
    $stock_quantity = $_POST['stock_quantity'];

    $stmt = $conn->prepare("INSERT INTO items (name, buy_price, sell_price, margin, barcode, provider, stock_quantity) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sdddsis", $name, $buy_price, $sell_price, $margin, $barcode, $provider, $stock_quantity);

    if ($stmt->execute()) {
        $message = "Item '{$name}' added successfully!";
        $message_type = 'success';
    } else {
        $message = "Error adding item: " . $stmt->error;
        $message_type = 'error';
    }
    $stmt->close();
}

// Handle Update Item
if (isset($_POST['update_item'])) {
    $id = $_POST['item_id'];
    $name = trim($_POST['name']);
    $buy_price = $_POST['buy_price'];
    $sell_price = $_POST['sell_price'];
    $margin = $_POST['margin'];
    $barcode = $_POST['barcode'];
    $provider = trim($_POST['provider']);
    $stock_quantity = $_POST['stock_quantity'];

    $stmt = $conn->prepare("UPDATE items SET name = ?, buy_price = ?, sell_price = ?, margin = ?, barcode = ?, provider = ?, stock_quantity = ? WHERE id = ?");
    $stmt->bind_param("sdddsisi", $name, $buy_price, $sell_price, $margin, $barcode, $provider, $stock_quantity, $id);

    if ($stmt->execute()) {
        $message = "Item '{$name}' updated successfully!";
        $message_type = 'success';
    } else {
        $message = "Error updating item: " . $stmt->error;
        $message_type = 'error';
    }
    $stmt->close();
}

// Handle Delete Item
if (isset($_POST['delete_item'])) {
    $id = $_POST['item_id'];

    $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $message = "Item deleted successfully!";
        $message_type = 'success';
    } else {
        $message = "Error deleting item: " . $stmt->error;
        $message_type = 'error';
    }
    $stmt->close();
}

// --- Fetch all items for the table display ---
$items = [];
$items_query = "SELECT * FROM items ORDER BY name ASC";
$result = $conn->query($items_query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - POCKET POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- QuaggaJS for barcode scanning -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .sidebar { background: linear-gradient(to top, #6a11cb 0%, #2575fc 100%); }
        .btn-gradient { background-image: linear-gradient(to right, #6a11cb 0%, #2575fc 100%); }
        .pin-input { font-family: monospace; letter-spacing: 0.5em; text-align: center; }
        
        /* Scanner modal styles */
        #scannerModal { transition: all 0.3s ease; }
        #scannerModal .scanner-container { width: 100%; max-width: 500px; margin: 0 auto; }
        #scannerModal #scanner-viewport { width: 100%; height: 300px; background: black; position: relative; }
        #scannerModal #scanner-viewport canvas { display: none; }
        #scannerModal #scanner-viewport video { width: 100%; height: 100%; object-fit: cover; }
        #scannerModal .scanner-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
        #scannerModal .scanner-crosshair { 
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80%;
            height: 100px;
            border: 2px solid rgba(255, 255, 255, 0.7);
            box-shadow: 0 0 0 100vmax rgba(0, 0, 0, 0.5);
        }
        
        /* HTTPS warning */
        #httpsWarning { display: none; }
    </style>
</head>
<body class="flex min-h-screen">

    <!-- HTTPS Warning (shown only when needed) -->
    <div id="httpsWarning" class="hidden bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4 fixed top-0 left-0 right-0 z-50">
        <p class="font-semibold">⚠️ Barcode scanning requires HTTPS. For full functionality:</p>
        <ol class="list-decimal ml-5 mt-2">
            <li>Access this page via HTTPS (secure connection)</li>
            <li>Allow camera permissions when prompted</li>
        </ol>
    </div>

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
            <a href="inventory.php" class="flex items-center p-3 rounded-lg text-lg font-semibold hover:bg-white/20 transition-all duration-200 mb-2 bg-white/20">
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
            <h2 class="text-4xl font-bold text-gray-800">Inventory</h2>
            <button onclick="openModal('addItemModal')" class="btn-gradient text-white py-3 px-6 rounded-xl font-semibold hover:shadow-lg transition-all duration-200">
                <i class="bi bi-plus-circle mr-2"></i> Add New Item
            </button>
        </header>

        <!-- Timed Notification -->
        <?php if (!empty($message)): ?>
            <div id="notification" class="fixed bottom-4 right-4 z-50 p-4 rounded-xl shadow-lg text-white transition-opacity duration-500
                <?php echo $message_type === 'success' ? 'bg-green-600' : 'bg-red-600'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Inventory Table Section -->
        <div class="bg-white p-6 rounded-2xl shadow-md border border-gray-200">
            <h3 class="text-2xl font-bold text-gray-800 mb-4">Current Inventory</h3>
            <?php if (!empty($items)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white rounded-lg">
                        <thead>
                            <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-6 text-left">Name</th>
                                <th class="py-3 px-6 text-left">Buy Price</th>
                                <th class="py-3 px-6 text-left">Sell Price</th>
                                <th class="py-3 px-6 text-left">Margin</th>
                                <th class="py-3 px-6 text-left">Barcode</th>
                                <th class="py-3 px-6 text-left">Provider</th>
                                <th class="py-3 px-6 text-left">Stock</th>
                                <th class="py-3 px-6 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm font-light">
                            <?php foreach ($items as $item): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="py-3 px-6 whitespace-nowrap"><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td class="py-3 px-6">$<?php echo htmlspecialchars(number_format($item['buy_price'], 2)); ?></td>
                                    <td class="py-3 px-6">$<?php echo htmlspecialchars(number_format($item['sell_price'], 2)); ?></td>
                                    <td class="py-3 px-6">$<?php echo htmlspecialchars(number_format($item['margin'], 2)); ?></td>
                                    <td class="py-3 px-6"><?php echo htmlspecialchars($item['barcode']); ?></td>
                                    <td class="py-3 px-6"><?php echo htmlspecialchars($item['provider']); ?></td>
                                    <td class="py-3 px-6"><?php echo htmlspecialchars($item['stock_quantity']); ?></td>
                                    <td class="py-3 px-6 flex items-center space-x-2">
                                        <button onclick="editItem(<?php echo htmlspecialchars(json_encode($item)); ?>)" class="text-blue-500 hover:text-blue-700 transition-colors duration-200">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <form action="inventory.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" name="delete_item" class="text-red-500 hover:text-red-700 transition-colors duration-200">
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
                <p class="text-gray-500">No items have been added to the inventory yet.</p>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Add Item Modal -->
    <div id="addItemModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-40">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-2xl bg-white">
            <h3 class="text-2xl font-bold text-gray-800 mb-4">Add New Item</h3>
            <form action="inventory.php" method="POST" id="addItemForm">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-gray-700 font-semibold mb-2">Name</label>
                        <input type="text" name="name" id="name" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <!-- Buy Price -->
                    <div>
                        <label for="buy_price" class="block text-gray-700 font-semibold mb-2">Buy Price ($)</label>
                        <input type="number" step="0.01" name="buy_price" id="buy_price" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <!-- Selling Price -->
                    <div>
                        <label for="sell_price" class="block text-gray-700 font-semibold mb-2">Selling Price ($)</label>
                        <input type="number" step="0.01" name="sell_price" id="sell_price" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <!-- Margin -->
                    <div>
                        <label for="margin" class="block text-gray-700 font-semibold mb-2">Margin ($)</label>
                        <input type="number" step="0.01" name="margin" id="margin" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <!-- Barcode -->
                    <div class="col-span-1">
                        <label for="barcode" class="block text-gray-700 font-semibold mb-2">Barcode</label>
                        <div class="flex items-center">
                            <input type="text" name="barcode" id="barcode" class="w-full p-3 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <!-- Barcode Scanner Button -->
                            <button type="button" onclick="openScanner('barcode')" class="bg-indigo-500 text-white p-3 rounded-r-lg hover:bg-indigo-600 transition-colors duration-200">
                                <i class="bi bi-qr-code-scan"></i>
                            </button>
                        </div>
                    </div>
                    <!-- Provider -->
                    <div>
                        <label for="provider" class="block text-gray-700 font-semibold mb-2">Provider (Optional)</label>
                        <input type="text" name="provider" id="provider" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <!-- Stock Quantity -->
                    <div>
                        <label for="stock_quantity" class="block text-gray-700 font-semibold mb-2">Stock Quantity</label>
                        <input type="number" name="stock_quantity" id="stock_quantity" value="0" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <div class="flex justify-end space-x-4 mt-4">
                    <button type="button" onclick="closeModal('addItemModal')" class="bg-gray-300 text-gray-800 py-3 px-6 rounded-xl font-semibold hover:bg-gray-400 transition-all duration-200">Cancel</button>
                    <button type="submit" name="add_item" class="btn-gradient text-white py-3 px-6 rounded-xl font-semibold hover:shadow-lg transition-all duration-200">
                        <i class="bi bi-plus-circle mr-2"></i> Add Item
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Item Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-40">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-2xl bg-white">
            <h3 class="text-2xl font-bold text-gray-800 mb-4">Edit Item</h3>
            <form action="inventory.php" method="POST" id="editItemForm">
                <input type="hidden" name="item_id" id="edit_item_id">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                    <!-- Name -->
                    <div>
                        <label for="edit_name" class="block text-gray-700 font-semibold mb-2">Name</label>
                        <input type="text" name="name" id="edit_name" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <!-- Buy Price -->
                    <div>
                        <label for="edit_buy_price" class="block text-gray-700 font-semibold mb-2">Buy Price ($)</label>
                        <input type="number" step="0.01" name="buy_price" id="edit_buy_price" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <!-- Selling Price -->
                    <div>
                        <label for="edit_sell_price" class="block text-gray-700 font-semibold mb-2">Selling Price ($)</label>
                        <input type="number" step="0.01" name="sell_price" id="edit_sell_price" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <!-- Margin -->
                    <div>
                        <label for="edit_margin" class="block text-gray-700 font-semibold mb-2">Margin ($)</label>
                        <input type="number" step="0.01" name="margin" id="edit_margin" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <!-- Barcode -->
                    <div>
                        <label for="edit_barcode" class="block text-gray-700 font-semibold mb-2">Barcode</label>
                        <div class="flex items-center">
                            <input type="text" name="barcode" id="edit_barcode" class="w-full p-3 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <!-- Barcode Scanner Button -->
                            <button type="button" onclick="openScanner('edit_barcode')" class="bg-indigo-500 text-white p-3 rounded-r-lg hover:bg-indigo-600 transition-colors duration-200">
                                <i class="bi bi-qr-code-scan"></i>
                            </button>
                        </div>
                    </div>
                    <!-- Provider -->
                    <div>
                        <label for="edit_provider" class="block text-gray-700 font-semibold mb-2">Provider</label>
                        <input type="text" name="provider" id="edit_provider" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <!-- Stock Quantity -->
                    <div>
                        <label for="edit_stock_quantity" class="block text-gray-700 font-semibold mb-2">Stock Quantity</label>
                        <input type="number" name="stock_quantity" id="edit_stock_quantity" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <div class="flex justify-end space-x-4 mt-4">
                    <button type="button" onclick="closeModal('editModal')" class="bg-gray-300 text-gray-800 py-3 px-6 rounded-xl font-semibold hover:bg-gray-400 transition-all duration-200">Cancel</button>
                    <button type="submit" name="update_item" class="btn-gradient text-white py-3 px-6 rounded-xl font-semibold hover:shadow-lg transition-all duration-200">
                        <i class="bi bi-arrow-repeat mr-2"></i> Update Item
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Barcode Scanner Modal -->
    <div id="scannerModal" class="fixed inset-0 bg-gray-900 bg-opacity-90 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 w-11/12 md:w-2/3 lg:w-1/2">
            <div class="bg-white p-6 rounded-2xl shadow-lg">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-2xl font-bold text-gray-800">Scan Barcode</h3>
                    <button onclick="closeScanner()" class="text-gray-500 hover:text-gray-700">
                        <i class="bi bi-x-lg text-2xl"></i>
                    </button>
                </div>
                
                <div class="scanner-container">
                    <div id="scanner-viewport">
                        <div class="scanner-overlay">
                            <div class="scanner-crosshair"></div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 text-center">
                    <p class="text-gray-600 mb-4">Point your camera at a barcode to scan it</p>
                    <button onclick="closeScanner()" class="bg-red-500 text-white py-2 px-6 rounded-lg font-semibold hover:bg-red-600 transition-all duration-200">
                        Cancel Scanning
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show HTTPS warning if not on HTTPS or localhost
        if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost') {
            document.getElementById('httpsWarning').classList.remove('hidden');
        }

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
        
        // --- Auto-Calculation Logic ---
        document.addEventListener('DOMContentLoaded', () => {
            const forms = [
                { formId: 'addItemForm', buyPriceId: 'buy_price', sellPriceId: 'sell_price', marginId: 'margin' },
                { formId: 'editItemForm', buyPriceId: 'edit_buy_price', sellPriceId: 'edit_sell_price', marginId: 'edit_margin' }
            ];

            forms.forEach(formInfo => {
                const buyPriceInput = document.getElementById(formInfo.buyPriceId);
                const sellPriceInput = document.getElementById(formInfo.sellPriceId);
                const marginInput = document.getElementById(formInfo.marginId);

                if (buyPriceInput && sellPriceInput && marginInput) {
                    buyPriceInput.addEventListener('input', calculateValues);
                    sellPriceInput.addEventListener('input', calculateValues);
                    marginInput.addEventListener('input', calculateValues);
                }
            });

            function calculateValues(event) {
                const form = event.target.form;
                const buyPrice = parseFloat(form.querySelector('[name="buy_price"]').value);
                const sellPrice = parseFloat(form.querySelector('[name="sell_price"]').value);
                const margin = parseFloat(form.querySelector('[name="margin"]').value);
                const sellPriceInput = form.querySelector('[name="sell_price"]');
                const marginInput = form.querySelector('[name="margin"]');

                if (!isNaN(buyPrice)) {
                    if (event.target.id.includes('sell_price') && !isNaN(sellPrice)) {
                        // Calculate margin if selling price is entered
                        marginInput.value = (sellPrice - buyPrice).toFixed(2);
                    } else if (event.target.id.includes('margin') && !isNaN(margin)) {
                        // Calculate selling price if margin is entered
                        sellPriceInput.value = (buyPrice + margin).toFixed(2);
                    }
                }
            }
        });

        // --- Barcode Scanner Functions ---
        let currentBarcodeField = null;
        let scannerActive = false;

        function openScanner(fieldId) {
            currentBarcodeField = fieldId;
            openModal('scannerModal');
            
            // Check for browser compatibility
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert("Camera access is not supported in your browser or the page is not served securely (HTTPS required).");
                closeScanner();
                return;
            }

            // Initialize the scanner after a short delay to allow modal to open
            setTimeout(initScanner, 300);
        }

        function closeScanner() {
            if (scannerActive) {
                Quagga.stop();
                scannerActive = false;
            }
            closeModal('scannerModal');
        }

        function initScanner() {
            if (scannerActive) return;
            
            Quagga.init({
                inputStream: {
                    name: "Live",
                    type: "LiveStream",
                    target: document.querySelector('#scanner-viewport'),
                    constraints: {
                        facingMode: "environment", // Use the rear camera
                        width: { min: 640, ideal: 1280, max: 1920 },
                        height: { min: 480, ideal: 720, max: 1080 }
                    },
                },
                decoder: {
                    readers: [
                        "ean_reader",  // EAN-13
                        "ean_8_reader", // EAN-8
                        "upc_reader",  // UPC-A
                        "code_128_reader", // Code 128
                        "code_39_reader" // Code 39
                    ]
                },
                locate: true,
                frequency: 10
            }, function(err) {
                if (err) {
                    console.error(err);
                    let errorMessage = "Error initializing barcode scanner: ";
                    
                    if (err.message.includes('Permission denied')) {
                        errorMessage += "Camera access was denied. Please allow camera access to use the scanner.";
                    } else if (err.message.includes('NotFoundError')) {
                        errorMessage += "No camera found. Please check your device has a camera.";
                    } else if (err.message.includes('NotAllowedError')) {
                        errorMessage += "Camera access is not allowed. The page must be served over HTTPS.";
                    } else {
                        errorMessage += err.message;
                    }
                    
                    alert(errorMessage);
                    closeScanner();
                    return;
                }
                scannerActive = true;
                Quagga.start();
            });

            Quagga.onDetected(function(result) {
                if (result.codeResult) {
                    const code = result.codeResult.code;
                    if (code && currentBarcodeField) {
                        document.getElementById(currentBarcodeField).value = code;
                        closeScanner();
                        
                        // Show success message
                        const notification = document.createElement('div');
                        notification.className = 'fixed bottom-4 right-4 z-50 p-4 rounded-xl shadow-lg text-white bg-green-600';
                        notification.textContent = 'Barcode scanned successfully!';
                        document.body.appendChild(notification);
                        
                        setTimeout(() => {
                            notification.style.opacity = '0';
                            setTimeout(() => notification.remove(), 500);
                        }, 3000);
                    }
                }
            });
        }

        // --- Modal Logic ---
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        function editItem(item) {
            document.getElementById('edit_item_id').value = item.id;
            document.getElementById('edit_name').value = item.name;
            document.getElementById('edit_buy_price').value = item.buy_price;
            document.getElementById('edit_sell_price').value = item.sell_price;
            document.getElementById('edit_margin').value = item.margin;
            document.getElementById('edit_barcode').value = item.barcode;
            document.getElementById('edit_provider').value = item.provider;
            document.getElementById('edit_stock_quantity').value = item.stock_quantity;
            openModal('editModal');
        }
    </script>
</body>
</html>