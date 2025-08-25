<?php
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_registrar";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if(isset($_POST['id'], $_POST['column'], $_POST['value'])){
    $id = intval($_POST['id']);
    $column = $_POST['column'];
    $value = $_POST['value'];

    // Only allow these columns
    $allowed = ['subject_code','units','grade'];
    if(!in_array($column, $allowed)){
        echo "❌ Invalid column!";
        exit;
    }

    $stmt = $conn->prepare("UPDATE student_subjects SET $column=? WHERE id=?");

    // If it's numeric like units, bind as integer
    if($column == 'units'){
        $value = intval($value);
        $stmt->bind_param("ii", $value, $id);
    } else {
        $stmt->bind_param("si", $value, $id);
    }

    if($stmt->execute()){
        echo "✅ Subject updated!";

        $user_id = $_SESSION['user_id'] ?? 0;
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $action = "Updated Grades ID=$id: set $column='$value'";

        $stmtAudit = $conn->prepare("INSERT INTO audit_trail (user_id, action, timestamp, ip_address) VALUES (?, ?, NOW(), ?)");
        $stmtAudit->bind_param("iss", $user_id, $action, $ip_address);
        $stmtAudit->execute();
        $stmtAudit->close();
    } else {
        echo "❌ Update failed!";
    }
    $stmt->close();
}
?>
