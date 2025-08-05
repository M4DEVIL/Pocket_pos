<?php
// File: POCKET_POS/admin/login.php
// This script handles the admin login form and authentication.

session_start();

// Include the database connection script
include('../includes/db_connect.php');

// Initialize a message variable for error handling
$message = '';

// --- Initial Setup: Automatically create admin user if none exists ---
$check_admin_sql = "SELECT COUNT(*) FROM users WHERE role = 'admin'";
$result = $conn->query($check_admin_sql);
$admin_exists = $result->fetch_row()[0] > 0;

if (!$admin_exists) {
    // No admin found, so create a default one
    $default_pin = '1234';
    $hashed_pin = password_hash($default_pin, PASSWORD_DEFAULT);
    
    $insert_admin_stmt = $conn->prepare("INSERT INTO users (username, pin, role) VALUES ('admin', ?, 'admin')");
    $insert_admin_stmt->bind_param("s", $hashed_pin);
    if ($insert_admin_stmt->execute()) {
        $message = 'Admin user created with default PIN: **1234**. Please log in.';
    } else {
        $message = 'Error: Failed to create default admin user.';
    }
    $insert_admin_stmt->close();
}

// --- Handle Login Request ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = $_POST['pin'];

    // SQL query to fetch the admin user
    $sql = "SELECT id, pin FROM users WHERE role = 'admin' LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($pin, $user['pin'])) {
            // Success! Set session variables, regenerate session ID, and redirect.
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = 'admin';
            header('Location: index.php');
            exit();
        } else {
            $message = 'Incorrect PIN. Please try again.';
        }
    } else {
        $message = 'Admin user not found.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - POCKET POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background: linear-gradient(to right, #6a11cb 0%, #2575fc 100%); }
        .card { backdrop-filter: blur(10px); background-color: rgba(255, 255, 255, 0.1); }
        .btn-gradient { background-image: linear-gradient(to right, #6a11cb 0%, #2575fc 100%); }
        .pin-input { font-family: monospace; letter-spacing: 0.5em; text-align: center; }
    </style>
</head>
<body class="text-white flex items-center justify-center h-screen">
    <div class="card p-8 rounded-2xl w-96 text-center">
        <i class="bi bi-person-fill-lock text-5xl text-indigo-300"></i>
        <h1 class="text-3xl font-bold mt-4">Admin Login</h1>
        <p class="mt-2 text-indigo-200">Enter your secure PIN to access the dashboard.</p>

        <?php if ($message): ?>
            <div class="bg-red-500/30 text-red-100 p-3 rounded-lg mt-4 transition-all duration-300 ease-in-out">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="mt-6">
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
