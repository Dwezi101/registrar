<?php
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_registrar";
$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8mb4");

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if user must change password
$stmt = $conn->prepare("SELECT must_change_password, role FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if ($row['must_change_password'] == 0) {
        // Already changed, redirect based on role
        if ($row['role'] === 'super_admin') {
            header("Location: super_admin_dashboard.php");
        } else {
            header("Location: registrar_dashboard.php");
        }
        exit;
    }
} else {
    // User not found, logout
    session_destroy();
    header("Location: index.php");
    exit;
}

$msg = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password !== $confirm_password) {
        $msg = "Passwords do not match ❌";
    } elseif (strlen($new_password) < 6) {
        $msg = "Password must be at least 6 characters ❌";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=?, must_change_password=0 WHERE id=?");
        $stmt->bind_param("si", $hashed, $user_id);
        $stmt->execute();
        $stmt->close();

        // Redirect to dashboard after changing password
        if ($row['role'] === 'super_admin') {
            header("Location: super_admin_dashboard.php");
        } else {
            header("Location: registrar_dashboard.php");
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Change Password</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="bg-white shadow-2xl rounded-2xl p-10 w-full max-w-md">
    <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">🔒 Change Password</h2>

    <?php if($msg): ?>
        <div class="mb-4 text-center text-red-600 font-medium"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-5">
        <div>
            <label class="block text-gray-700 font-medium mb-2">New Password</label>
            <input type="password" name="new_password" required 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-gray-700 font-medium mb-2">Confirm Password</label>
            <input type="password" name="confirm_password" required
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <button type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition duration-200">
            Update Password
        </button>
    </form>

    <p class="mt-6 text-sm text-gray-500 text-center">Ensure your password is strong and secure.</p>
</div>

</body>
</html>
