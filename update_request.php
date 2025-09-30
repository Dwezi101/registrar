<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_registrar";
$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset('utf8mb4');

$id = $_POST['id'];
$status = $_POST['status'];
$release_date = $_POST['release_date'];

$sql = "UPDATE registrar_requests SET status='$status', release_date='$release_date' WHERE id=$id";

if ($conn->query($sql)) {
    echo "Request updated successfully!";
} else {
    echo "Error updating request: " . $conn->error;
}
