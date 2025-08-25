<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_registrar";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if(isset($_POST['id'], $_POST['column'], $_POST['value'])) {
    $id = intval($_POST['id']);
    $column = $_POST['column'];
    $value = $_POST['value'];

    // Only allow these columns to be updated
    $allowed = ['name','sex','course','year_level','major','remarks'];
    if(!in_array($column, $allowed)) {
        echo "❌ Invalid column!";
        exit;
    }

    $stmt = $conn->prepare("UPDATE students SET $column=? WHERE id=?");
    if($column == 'year_level') {
        $stmt->bind_param("ii", $value, $id);
    } else {
        $stmt->bind_param("si", $value, $id);
    }
    if($stmt->execute()) {
        echo "✅ Updated successfully!";
    } else {
        echo "❌ Update failed!";
    }
    $stmt->close();
}
?>
