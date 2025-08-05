<?php
// File: POCKET_POS/pos/index.php
// This is the main POS terminal interface with open/close register functionality.

session_start();

// Include database connection
include('../includes/db_connect.php');

// --- User Authentication and Session Check ---
$sellers_check = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'pos_seller'");
$has_sellers = $sellers_check->fetch_row()[0] > 0;

if ($has_sellers) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pos_seller') {
        header('Location: login.php');
        exit();
    }
} else {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = 0; // A special ID for the "guest" user
        $_SESSION['role'] = 'pos_guest';
    }
}

$seller_id = $_SESSION['user_id'];
$message = '';
$is_register_open = false;
$open_session = null;
$todays_sales_total = 0;

// Check for an existing open session for today
$stmt_session = $conn->prepare("SELECT * FROM cash_register_sessions WHERE seller_id = ? AND DATE(created_at) = CURDATE() AND closed_at IS NULL");
$stmt_session->bind_param("i", $seller_id);
$stmt_session->execute();
$result_session = $stmt_session->get_result();

if ($result_session->num_rows > 0) {
    $is_register_open = true;
    $open_session = $result_session->fetch_assoc();
    
    // Get total sales for the open session
    $stmt_sales_total = $conn->prepare("SELECT SUM(total_amount) AS total FROM sales WHERE seller_id = ? AND DATE(created_at) = CURDATE() AND is_refund = 0");
    $stmt_sales_total->bind_param("i", $seller_id);
    $stmt_sales_total->execute();
    $result_sales_total = $stmt_sales_total->get_result();
    $sales_row = $result_sales_total->fetch_assoc();
    $todays_sales_total = $sales_row['total'] ?? 0;
    $stmt_sales_total->close();
}
$stmt_session->close();


// --- Handle Form Submissions ---

// Handle Open Register Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['open_register'])) {
    $start_cash = $_POST['start_cash'];

    $stmt = $conn->prepare("INSERT INTO cash_register_sessions (seller_id, start_cash) VALUES (?, ?)");
    $stmt->bind_param("id", $seller_id, $start_cash);
    
    if ($stmt->execute()) {
        $message = "<div class='bg-green-500/30 text-green-100 p-3 rounded-lg'>Register opened successfully with $". number_format($start_cash, 2) ." cash.</div>";
        // Reload to show main POS interface
        header("Refresh:0");
        exit();
    } else {
        $message = "<div class='bg-red-500/30 text-red-100 p-3 rounded-lg'>Error opening register: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// Handle Close Register Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_register'])) {
    $tomorrow_cash = $_POST['tomorrow_cash'];
    $closing_sales_total = $_POST['closing_sales_total'];
    $start_cash = $_POST['start_cash'];
    $session_id = $_POST['session_id'];

    // Calculate the total cash in the register
    $total_cash_in_register = $start_cash + $closing_sales_total;

    // Calculate the amount to be added to accountant
    $amount_to_add_as_income = $total_cash_in_register - $tomorrow_cash;

    // Insert into income table for accountant tab
    if ($amount_to_add_as_income > 0) {
        $description = "Daily Register Close for " . date('Y-m-d');
        $stmt_income = $conn->prepare("INSERT INTO income (description, amount, date) VALUES (?, ?, CURDATE())");
        $stmt_income->bind_param("sd", $description, $amount_to_add_as_income);
        $stmt_income->execute();
        $stmt_income->close();
    }

    // Update the cash register session as closed
    $stmt_close = $conn->prepare("UPDATE cash_register_sessions SET end_cash = ?, sales_total = ?, closed_at = NOW() WHERE id = ?");
    $stmt_close->bind_param("ddi", $tomorrow_cash, $closing_sales_total, $session_id);
    
    if ($stmt_close->execute()) {
        $message = "<div class='bg-green-500/30 text-green-100 p-3 rounded-lg'>Register closed successfully. Profit of $". number_format($amount_to_add_as_income, 2) ." added to income.</div>";
        // Destroy session and redirect to login
        session_destroy();
        header('Location: login.php');
        exit();
    } else {
        $message = "<div class='bg-red-500/30 text-red-100 p-3 rounded-lg'>Error closing register: " . $stmt_close->error . "</div>";
    }
    $stmt_close->close();
}

// Handle Checkout Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    if (!$is_register_open) {
        $message = "<div class='bg-red-500/30 text-red-100 p-3 rounded-lg'>Please open the register before making a sale.</div>";
    } else {
        $cart_json = $_POST['cart_data'];
        $cart_items = json_decode($cart_json, true);

        if (empty($cart_items)) {
            $message = "<div class='bg-red-500/30 text-red-100 p-3 rounded-lg'>Your cart is empty. Please add items.</div>";
        } else {
            $total_amount = 0;
            foreach ($cart_items as $item) {
                $total_amount += $item['price'] * $item['quantity'];
            }
            $sale_details = json_encode($cart_items);

            $stmt = $conn->prepare("INSERT INTO sales (seller_id, total_amount, sale_details) VALUES (?, ?, ?)");
            $stmt->bind_param("ids", $seller_id, $total_amount, $sale_details);
            
            if ($stmt->execute()) {
                foreach ($cart_items as $item) {
                    $update_stock_stmt = $conn->prepare("UPDATE items SET stock_quantity = stock_quantity - ? WHERE id = ?");
                    $update_stock_stmt->bind_param("ii", $item['quantity'], $item['id']);
                    $update_stock_stmt->execute();
                    $update_stock_stmt->close();
                }
                $message = "<div class='bg-green-500/30 text-green-100 p-3 rounded-lg'>Checkout successful!</div>";
                // Redirect to reload the page and update sales total
                header("Refresh:0");
                exit();
            } else {
                $message = "<div class='bg-red-500/30 text-red-100 p-3 rounded-lg'>Error during checkout: " . $stmt->error . "</div>";
            }
            $stmt->close();
        }
    }
}

// Handle Refund Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refund_sale'])) {
    $sale_id = $_POST['sale_id'];

    $update_sale_stmt = $conn->prepare("UPDATE sales SET is_refund = 1 WHERE id = ?");
    $update_sale_stmt->bind_param("i", $sale_id);
    if ($update_sale_stmt->execute()) {
        $message = "<div class='bg-green-500/30 text-green-100 p-3 rounded-lg'>Sale #{$sale_id} has been refunded.</div>";
        header("Refresh:0");
        exit();
    } else {
        $message = "<div class='bg-red-500/30 text-red-100 p-3 rounded-lg'>Error refunding sale: " . $update_sale_stmt->error . "</div>";
    }
    $update_sale_stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Terminal - POCKET POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .card { backdrop-filter: blur(5px); background-color: rgba(255, 255, 255, 0.7); }
        .btn-gradient { background-image: linear-gradient(to right, #6a11cb 0%, #2575fc 100%); }
        .fade-in { animation: fadeIn 0.5s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        /* Style for the search results dropdown */
        .search-results { max-height: 200px; overflow-y: auto; z-index: 10; }
        .search-item:hover { background-color: #e2e8f0; }
    </style>
</head>
<body class="flex flex-col md:flex-row min-h-screen">
    
    <?php if ($is_register_open): ?>
    <!-- POS Section (Visible when register is open) -->
    <div class="w-full md:w-3/5 p-8 bg-white shadow-lg flex flex-col">
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800">POS Terminal</h1>
            <div class="flex items-center space-x-4">
                <button onclick="openModal('closeRegisterModal')" class="bg-red-500 text-white py-2 px-4 rounded-xl font-semibold hover:bg-red-600 transition-all duration-200">
                    <i class="bi bi-door-closed mr-2"></i> Close Register
                </button>
                <a href="../logout.php" class="btn-gradient text-white py-2 px-4 rounded-xl font-semibold hover:shadow-lg transition-all duration-200">
                    <i class="bi bi-box-arrow-left mr-2"></i> Logout
                </a>
            </div>
        </header>

        <?php echo $message; ?>

        <!-- Search Bar and Barcode -->
        <div class="mb-8">
            <div class="relative">
                <input type="text" id="item-search" placeholder="Search by name or barcode..." 
                        class="w-full p-4 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-gray-800">
                <div id="search-results" class="search-results absolute w-full mt-2 bg-white rounded-xl shadow-lg hidden">
                    <!-- Search results will appear here -->
                </div>
            </div>
            <!-- Barcode Scanner Placeholder -->
            <div class="mt-4 text-center text-gray-500">
                <i class="bi bi-upc-scan text-2xl mr-2"></i> Or use a barcode scanner
            </div>
        </div>

        <!-- Cart Section -->
        <div class="flex-1 overflow-y-auto mb-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-4">Cart</h3>
            <table class="min-w-full table-auto">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left">Item</th>
                        <th class="px-4 py-2 text-left">Price</th>
                        <th class="px-4 py-2 text-left">Qty</th>
                        <th class="px-4 py-2 text-left">Subtotal</th>
                        <th class="px-4 py-2 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody id="cart-table-body" class="divide-y divide-gray-200">
                    <!-- Cart items will be added here by JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Total and Checkout -->
        <div class="border-t-2 border-gray-200 pt-4">
            <div class="flex justify-between items-center text-3xl font-bold text-gray-800">
                <span>Total:</span>
                <span id="cart-total">$0.00</span>
            </div>
            <form action="index.php" method="POST" onsubmit="return handleCheckout(event);" class="mt-4 flex gap-4">
                <input type="hidden" name="checkout" value="1">
                <input type="hidden" name="cart_data" id="cart-data-input">
                <button type="submit" class="w-full py-4 text-xl font-bold rounded-xl text-white btn-gradient hover:shadow-lg transition-all duration-200">
                    <i class="bi bi-credit-card mr-2"></i> Checkout
                </button>
                <button type="button" onclick="clearCart()" class="w-1/3 py-4 text-xl font-bold rounded-xl text-gray-800 bg-gray-200 hover:bg-gray-300 transition-all duration-200">
                    <i class="bi bi-x-circle mr-2"></i> Clear
                </button>
            </form>
        </div>
    </div>
    
    <!-- Today's Sales Section -->
    <div class="w-full md:w-2/5 p-8 bg-gray-100 shadow-inner overflow-y-auto">
        <h3 class="text-2xl font-bold text-gray-800 mb-4">Today's Sales</h3>
        <div class="bg-white p-4 rounded-xl shadow-md">
            <table class="min-w-full table-auto text-sm">
                <thead>
                    <tr class="text-left text-gray-500">
                        <th class="py-2">Time</th>
                        <th class="py-2">Total</th>
                        <th class="py-2">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch today's sales for the current seller
                    $today = date('Y-m-d');
                    $sales_query = "
                        SELECT id, total_amount, created_at, is_refund 
                        FROM sales 
                        WHERE seller_id = ? AND DATE(created_at) = ?
                        ORDER BY created_at DESC
                    ";
                    $stmt = $conn->prepare($sales_query);
                    $stmt->bind_param("is", $seller_id, $today);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        while ($sale = $result->fetch_assoc()) {
                            $is_refunded = $sale['is_refund'];
                            echo "<tr class='" . ($is_refunded ? 'text-gray-400 line-through' : 'text-gray-800') . "'>";
                            echo "<td class='py-2'>" . date('H:i', strtotime($sale['created_at'])) . "</td>";
                            echo "<td class='py-2'>$" . number_format($sale['total_amount'], 2) . "</td>";
                            echo "<td class='py-2'>";
                            if (!$is_refunded) {
                                echo "<form action='index.php' method='POST' onsubmit='return confirm(\"Refund sale #" . $sale['id'] . "?\")' class='inline-block'>";
                                echo "<input type='hidden' name='refund_sale' value='1'>";
                                echo "<input type='hidden' name='sale_id' value='" . $sale['id'] . "'>";
                                echo "<button type='submit' class='text-red-500 hover:text-red-700 font-semibold'><i class='bi bi-arrow-return-left'></i></button>";
                                echo "</form>";
                            } else {
                                echo "Refunded";
                            }
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3' class='text-center text-gray-500 py-4'>No sales today.</td></tr>";
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Close Register Modal -->
    <div id="closeRegisterModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-2xl bg-white">
            <h3 class="text-2xl font-bold text-gray-800 mb-4">Close Register</h3>
            <p class="mb-4 text-gray-600">Review today's financial summary before closing the register.</p>
            <div class="bg-gray-100 p-4 rounded-xl mb-4">
                <p class="flex justify-between items-center text-lg font-semibold text-gray-700">Starting Cash: <span class="text-blue-600">$<?php echo number_format($open_session['start_cash'], 2); ?></span></p>
                <p class="flex justify-between items-center text-lg font-semibold text-gray-700">Today's Sales: <span class="text-green-600">$<span id="sales-total-display"><?php echo number_format($todays_sales_total, 2); ?></span></span></p>
                <hr class="my-2 border-gray-300">
                <p class="flex justify-between items-center text-2xl font-bold text-gray-800">Total Cash: <span class="text-indigo-600">$<span id="total-cash-display"><?php echo number_format($open_session['start_cash'] + $todays_sales_total, 2); ?></span></span></p>
            </div>
            
            <form action="index.php" method="POST" onsubmit="return confirm('Are you sure you want to close the register for the day?');">
                <input type="hidden" name="close_register" value="1">
                <input type="hidden" name="session_id" value="<?php echo $open_session['id']; ?>">
                <input type="hidden" name="start_cash" value="<?php echo $open_session['start_cash']; ?>">
                <input type="hidden" name="closing_sales_total" value="<?php echo $todays_sales_total; ?>">
                
                <div class="mb-4">
                    <label for="tomorrow_cash" class="block text-gray-700 font-semibold mb-2">Cash to leave for tomorrow</label>
                    <input type="number" step="0.01" name="tomorrow_cash" id="tomorrow_cash" value="<?php echo number_format($open_session['start_cash'], 2, '.', ''); ?>" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <div class="flex justify-end space-x-4 mt-4">
                    <button type="button" onclick="closeModal('closeRegisterModal')" class="bg-gray-300 text-gray-800 py-3 px-6 rounded-xl font-semibold hover:bg-gray-400 transition-all duration-200">Cancel</button>
                    <button type="submit" class="bg-red-500 text-white py-3 px-6 rounded-xl font-semibold hover:bg-red-600 transition-all duration-200">
                        <i class="bi bi-door-closed mr-2"></i> Close Register
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php else: ?>
    <!-- Open Register Modal (Visible when register is closed) -->
    <div id="openRegisterModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center">
        <div class="relative mx-auto p-8 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-2xl bg-white text-center">
            <h3 class="text-3xl font-bold text-gray-800 mb-4">Open Register</h3>
            <p class="mb-6 text-gray-600">Please enter the starting cash for today's session to begin.</p>
            <?php echo $message; ?>
            <form action="index.php" method="POST">
                <input type="hidden" name="open_register" value="1">
                <div class="mb-6">
                    <label for="start_cash" class="block text-gray-700 font-semibold mb-2">Starting Cash ($)</label>
                    <input type="number" step="0.01" name="start_cash" id="start_cash" value="0.00" required class="w-full p-4 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-center">
                </div>
                <button type="submit" class="w-full py-4 text-xl font-bold rounded-xl text-white btn-gradient hover:shadow-lg transition-all duration-200">
                    <i class="bi bi-door-open mr-2"></i> Open Register
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
    // Global cart variable
    let cart = [];
    const cartTableBody = document.getElementById('cart-table-body');
    const cartTotalElement = document.getElementById('cart-total');
    const searchInput = document.getElementById('item-search');
    const searchResultsDiv = document.getElementById('search-results');

    // Add an event listener to the search input for predictive search
    searchInput.addEventListener('input', function() {
        const query = this.value;
        if (query.length < 2) {
            searchResultsDiv.classList.add('hidden');
            return;
        }

        // AJAX request to a backend script for item search
        fetch('search_items.php?query=' + query)
            .then(response => response.json())
            .then(items => {
                searchResultsDiv.innerHTML = '';
                if (items.length > 0) {
                    items.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'search-item p-3 cursor-pointer hover:bg-gray-100 rounded-lg transition-colors';
                        div.textContent = `${item.name} ($${parseFloat(item.sell_price).toFixed(2)})`;
                        div.onclick = () => addItemToCart(item);
                        searchResultsDiv.appendChild(div);
                    });
                    searchResultsDiv.classList.remove('hidden');
                } else {
                    searchResultsDiv.innerHTML = `<div class="p-3 text-gray-500">No items found.</div>`;
                    searchResultsDiv.classList.remove('hidden');
                }
            })
            .catch(error => console.error('Error fetching search results:', error));
    });
    
    // Hide search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResultsDiv.contains(e.target)) {
            searchResultsDiv.classList.add('hidden');
        }
    });

    // Add an item to the cart
    function addItemToCart(item) {
        let existingItem = cart.find(cartItem => cartItem.id === item.id);
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push({
                id: item.id,
                name: item.name,
                price: parseFloat(item.sell_price),
                quantity: 1
            });
        }
        updateCartDisplay();
        searchResultsDiv.classList.add('hidden');
        searchInput.value = '';
    }

    // Update the cart display in the UI
    function updateCartDisplay() {
        cartTableBody.innerHTML = '';
        let total = 0;
        cart.forEach(item => {
            const subtotal = item.price * item.quantity;
            total += subtotal;
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="px-4 py-2">${item.name}</td>
                <td class="px-4 py-2">$${item.price.toFixed(2)}</td>
                <td class="px-4 py-2 flex items-center">
                    <input type="number" value="${item.quantity}" min="1" onchange="updateItemQuantity(${item.id}, this.value)"
                            class="w-16 text-center border rounded-lg px-2 py-1">
                </td>
                <td class="px-4 py-2">$${subtotal.toFixed(2)}</td>
                <td class="px-4 py-2">
                    <button onclick="removeItemFromCart(${item.id})" class="text-red-500 hover:text-red-700">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </td>
            `;
            cartTableBody.appendChild(row);
        });
        cartTotalElement.textContent = `$${total.toFixed(2)}`;
    }

    // Update the quantity of an item in the cart
    function updateItemQuantity(itemId, newQuantity) {
        let item = cart.find(cartItem => cartItem.id === itemId);
        if (item) {
            item.quantity = parseInt(newQuantity);
            if (item.quantity <= 0) {
                removeItemFromCart(itemId);
            } else {
                updateCartDisplay();
            }
        }
    }

    // Remove an item from the cart
    function removeItemFromCart(itemId) {
        cart = cart.filter(item => item.id !== itemId);
        updateCartDisplay();
    }
    
    // Clear the entire cart
    function clearCart() {
        cart = [];
        updateCartDisplay();
    }
    
    // Handle the checkout process
    function handleCheckout(event) {
        if (cart.length === 0) {
            alert("Your cart is empty!"); // Use custom modal in production
            event.preventDefault();
            return false;
        }

        // Serialize the cart data into a JSON string and put it in the hidden input
        document.getElementById('cart-data-input').value = JSON.stringify(cart);
        return true;
    }

    // Modal logic
    function openModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    }
    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    // Initial cart display
    updateCartDisplay();
    </script>
</body>
</html>
