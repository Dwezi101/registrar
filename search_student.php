<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_registrar";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$q = $_GET['q'] ?? '';

if ($q) {
    $stmt = $conn->prepare("SELECT id, name, course, year_level, major 
                            FROM students 
                            WHERE id LIKE ? OR name LIKE ? OR course LIKE ? OR major LIKE ? 
                            LIMIT 10");
    $like = "%$q%";
    $stmt->bind_param("ssss", $like, $like, $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
    $studentName = htmlspecialchars($row['name'], ENT_QUOTES);
    echo "<li class='px-4 py-2 cursor-pointer hover:bg-blue-100 dark:hover:bg-gray-700 text-gray-800'
             onclick=\"selectStudent('{$studentName}')\">
             {$studentName}
          </li>";
}
    } else {
        echo "<li class='px-4 py-2 text-gray-500'>No student found</li>";
    }
}
?>
