<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_registrar";
$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset('utf8mb4');

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $sql = "DELETE FROM registrar_requests WHERE id = $id";
    if ($conn->query($sql)) {
        echo "Request deleted successfully.";
    } else {
        echo "Error deleting request: " . $conn->error;
    }
}
?>
