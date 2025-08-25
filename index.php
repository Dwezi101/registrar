<?php
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_registrar";
$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8mb4");

function log_action($conn, $user_id, $action) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $stmt = $conn->prepare("INSERT INTO audit_trail (user_id, action, ip_address) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $action, $ip);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, password, role, must_change_password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();

    if (password_verify($password, $row['password'])) {
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['role'] = $row['role'];

        // ✅ Log successful login
        log_action($conn, $row['id'], "Successful login");

        if ($row['must_change_password'] == 1) {
            // Log redirect to password change
            log_action($conn, $row['id'], "Redirected to change password page");
            header("Location: change_password.php");
        } else {
            if ($row['role'] === 'super_admin') {
                header("Location: super_admin_dashboard.php");
            } elseif ($row['role'] === 'registrar') {
                header("Location: registrar_dashboard.php");
            }
        }
        exit;
    } else {
        // Log failed password attempt
        log_action($conn, $row['id'], "Failed login: Invalid password");
        $error = "Invalid password ❌";
    }
} else {
    // Log failed login (email not found)
    log_action($conn, 0, "Failed login: Email not found ($email)");
    $error = "Incorrect Email or Password ❌";
}

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="ring">
    <i style="--clr:#00ff0a;"></i>
    <i style="--clr:#ff0057;"></i>
    <i style="--clr:#fffd44;"></i>
    <div class="login">
        <h2>Login</h2>
        <form action="" method="POST">
            <div class="inputBx">
                <input type="email" name="email" placeholder="Email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
            </div><br>
            <div class="inputBx">
                <input type="password" name="password" placeholder="Password" required>
            </div><br>
            <div class="inputBx">
                <input type="submit" value="Sign in">
            </div>

            <!-- ✅ Show error here -->
            <?php if (!empty($error)): ?>
                <div style="color:red; margin-top:10px; text-align:center;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
        </form>
    </div>
    </div>
</body>
</html>
