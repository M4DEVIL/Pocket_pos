<?php
// File: POCKET_POS/pos/login.php
// This script handles the login for POS sellers.

session_start();

// Include the database connection script
include('../includes/db_connect.php');

$message = '';

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seller_id = $_POST['seller_id'];
    $pin = $_POST['pin'];

    $stmt = $conn->prepare("SELECT id, username, pin FROM users WHERE id = ? AND role = 'pos_seller'");
    $stmt->bind_param("i", $seller_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($pin, $user['pin'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = 'pos_seller';
            header('Location: index.php');
            exit();
        } else {
            $message = 'Incorrect PIN. Please try again.';
        }
    } else {
        $message = 'Invalid user selection or user not found.';
    }
    $stmt->close();
}

// Fetch all pos_seller users to populate the dropdown
$sellers_query = "SELECT id, username FROM users WHERE role = 'pos_seller' ORDER BY username ASC";
$sellers_result = $conn->query($sellers_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Login - POCKET POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background: linear-gradient(to right, #2575fc 0%, #6a11cb 100%); }
        .card { backdrop-filter: blur(10px); background-color: rgba(255, 255, 255, 0.1); }
        .btn-gradient { background-image: linear-gradient(to right, #6a11cb 0%, #2575fc 100%); }
        .pin-input { font-family: monospace; letter-spacing: 0.5em; text-align: center; }
    </style>
</head>
<body class="text-white flex items-center justify-center h-screen">
    <div class="card p-8 rounded-2xl w-96 text-center">
        <i class="bi bi-shop text-5xl text-indigo-300"></i>
        <h1 class="text-3xl font-bold mt-4">POS Login</h1>
        <p class="mt-2 text-indigo-200">Select your name and enter your PIN.</p>

        <?php if ($message): ?>
            <div class="bg-red-500/30 text-red-100 p-3 rounded-lg mt-4 transition-all duration-300 ease-in-out">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="mt-6">
            <div class="mb-4">
                <select name="seller_id" required
                        class="w-full p-4 rounded-xl bg-white/10 text-white border-2 border-indigo-400/50 focus:border-indigo-400 focus:outline-none transition-colors duration-200 ease-in-out">
                    <option value="" class="text-gray-800">-- Select User --</option>
                    <?php if ($sellers_result->num_rows > 0): ?>
                        <?php while ($seller = $sellers_result->fetch_assoc()): ?>
                            <option value="<?php echo $seller['id']; ?>" class="text-gray-800">
                                <?php echo htmlspecialchars($seller['username']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <option class="text-gray-800" disabled>No sellers found. Add one in Admin Settings.</option>
                    <?php endif; ?>
                </select>
            </div>
            <input type="password" name="pin" id="pin" required maxlength="4"
                   class="pin-input w-full p-4 rounded-xl bg-white/10 text-white placeholder-indigo-200 border-2 border-indigo-400/50 focus:border-indigo-400 focus:outline-none transition-colors duration-200 ease-in-out"
                   placeholder="****" pattern="\d{4}" title="Please enter a 4-digit PIN">
            <button type="submit" class="mt-6 w-full py-3 text-lg font-semibold rounded-xl btn-gradient hover:shadow-lg">
                Login
            </button>
        </form>

    </div>
</body>
</html>
