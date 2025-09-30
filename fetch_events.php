<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_registrar";
$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset('utf8mb4');

$query = "SELECT id, first_name, last_name, document_type, contact_no, release_date, status FROM registrar_requests";
$result = $conn->query($query);

$events = [];

while ($row = $result->fetch_assoc()) {
    $events[] = [
        'id' => $row['id'],
        'title' => $row['first_name'] . ' ' . $row['last_name'] . ' (' . $row['document_type'] . ')',
        'start' => $row['release_date'],
        'date' => $row['release_date'],
        'contact' => $row['contact_no'],
        'status' => $row['status'],
        'className' => strtolower($row['status']) // 👈 adds "pending" or "released" as class
    ];
}

header('Content-Type: application/json');
echo json_encode($events);
?>
