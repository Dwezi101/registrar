<?php
session_start();

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_registrar";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset('utf8mb4');

// --- LOGIN HANDLER ---
if (isset($_POST['login'])) {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_email'] = $row['email'];
                $_SESSION['user_role'] = $row['role']; // super_admin / registrar
                header("Location: dashboard.php");
                exit;
            } else {
                $msg = "Invalid password.";
            }
        } else {
            $msg = "User not found.";
        }
    } else {
        $msg = "Please enter email and password.";
    }
}

// --- CREATE USER ---
if (isset($_POST['create_user'])) {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    if ($email && $password && $role) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $hashedPassword, $role);

        if ($stmt->execute()) {
            $msg = "User created successfully!";
        } else {
            $msg = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $msg = "All fields are required.";
    }
}

// --- DELETE USER ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM users WHERE id=$id");
    header("Location: manage_users.php");
    exit;
}

// --- FETCH ALL USERS ---
$result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management - Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4">👤 User Management (Super Admin)</h2>

    <div class="mb-3">
        <a href="super_admin_dashboard.php" class="btn btn-secondary shadow-sm">
            <i class="bi bi-arrow-left-circle me-1"></i> Back to Dashboard
        </a>
    </div>
    
    <?php if (isset($msg)): ?>
        <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- Create User Form -->
    <div class="card mb-4">
        <div class="card-header">Create New User</div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label>Email</label>
                    <input type="email" name="email" required class="form-control">
                </div>
                <div class="mb-3">
                    <label>Password</label>
                    <input type="password" name="password" required class="form-control">
                </div>
                <div class="mb-3">
                    <label>Role</label>
                    <select name="role" class="form-control" required>
                        <option value="registrar">Registrar</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                </div>
                <button type="submit" name="create_user" class="btn btn-primary">Create User</button>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-header">All Users</div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th><th>Email</th><th>Role</th><th>Created</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= $row['role'] ?></td>
                            <td><?= $row['created_at'] ?></td>
                            <td>
                                <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm"
                                   onclick="return confirm('Delete this user?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</body>
</html>
