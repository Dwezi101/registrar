<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_registrar";
$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset('utf8mb4');

if (isset($_POST['ajax'])) {
    $search = $conn->real_escape_string($_POST['search'] ?? '');
    $page = intval($_POST['page'] ?? 1);
    $limit = 5;
    $offset = ($page - 1) * $limit;

    // Count total rows for pagination
    $countSql = "SELECT COUNT(*) as total 
                 FROM registrar_requests 
                 WHERE status='Released' AND 
                       (first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR document_type LIKE '%$search%')";
    $countResult = $conn->query($countSql);
    $totalRows = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalRows / $limit);

    // Fetch rows
    $sql = "SELECT first_name, last_name, document_type, release_date 
            FROM registrar_requests 
            WHERE status='Released' AND 
                  (first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR document_type LIKE '%$search%')
            ORDER BY release_date DESC
            LIMIT $limit OFFSET $offset";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        echo '<div class="overflow-x-auto">';
        echo '<table class="min-w-full border border-gray-200 rounded-lg">';
        echo '<thead class="bg-blue-600 text-white">';
        echo '<tr>
                <th class="py-2 px-4 text-left">Full Name</th>
                <th class="py-2 px-4 text-left">Document</th>
                <th class="py-2 px-4 text-left">Release Date</th>
              </tr>';
        echo '</thead>';
        echo '<tbody class="divide-y divide-gray-200">';
        while ($row = $result->fetch_assoc()) {
            $fullname = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
            $doc = htmlspecialchars($row['document_type']);
            $date = date('F d, Y', strtotime($row['release_date']));
            echo "<tr class='hover:bg-gray-50 transition'>
                    <td class='py-2 px-4 text-gray-700 font-medium'>$fullname</td>
                    <td class='py-2 px-4 text-gray-700'>$doc</td>
                    <td class='py-2 px-4 text-gray-700'>$date</td>
                  </tr>";
        }
        echo '</tbody></table></div>';

        // Pagination buttons
        echo '<div class="mt-4 flex justify-center space-x-2">';
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = ($i == $page) ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700';
            echo "<button class='px-3 py-1 rounded $active' onclick='fetchReleasedRequests(\"$search\", $i)'>$i</button>";
        }
        echo '</div>';
    } else {
        echo '<p class="text-gray-500">No released requests found.</p>';
    }
    exit;
}
?>
