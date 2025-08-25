<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_registrar";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$rowsPerPage = 50;
$offset = ($page - 1) * $rowsPerPage;

$ay = $_GET['ay'] ?? '';
$semester = $_GET['semester'] ?? '';
$search = $_GET['search'] ?? '';

// DEBUG
error_log("DEBUG: AY=$ay | SEM=$semester | SEARCH=$search | PAGE=$page");

// 1️⃣ Count filtered students for pagination
$countSql = "
SELECT COUNT(DISTINCT s.id) AS total
FROM students s
JOIN student_subjects ss ON ss.student_id = s.id
WHERE ss.academic_year = ? AND ss.semester = ?
";

$params = [$ay, $semester];
$types = "ss";

if(!empty($search)){
    $countSql .= " AND (s.name LIKE ? OR s.course LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

$countStmt = $conn->prepare($countSql);
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalStudents = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalStudents / $rowsPerPage);

// 2️⃣ Fetch students with LIMIT
$sql = "
SELECT DISTINCT s.*
FROM students s
JOIN student_subjects ss ON ss.student_id = s.id
WHERE ss.academic_year = ? AND ss.semester = ?
";

$params = [$ay, $semester];
$types = "ss";

if(!empty($search)){
    $sql .= " AND (s.name LIKE ? OR s.course LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

$sql .= " ORDER BY s.id DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $rowsPerPage;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$students = $stmt->get_result();

// 3️⃣ Output table
ob_start();
?>
<table class="min-w-full divide-y divide-gray-200 table-auto">
<thead class="bg-gray-200 sticky top-0 z-10">
<tr>
    <th class="px-4 py-2 text-left">Name</th>
    <th class="px-4 py-2 text-left">Sex</th>
    <th class="px-4 py-2 text-left">Course</th>
    <th class="px-4 py-2 text-left">Year Level</th>
    <th class="px-4 py-2 text-left">Major</th>
    <th class="px-4 py-2 text-left">Total Units</th>
    <th class="px-4 py-2 text-left">Subject Code</th>
    <th class="px-4 py-2 text-left">Units</th>
    <th class="px-4 py-2 text-left">Grade</th>
</tr>
</thead>
<tbody class="bg-white divide-y divide-gray-200">
<?php while($student = $students->fetch_assoc()):
    $student_id = $student['id'];

    // Fetch subjects for this student filtered by AY/semester
    $subStmt = $conn->prepare("
        SELECT * FROM student_subjects
        WHERE student_id = ? AND academic_year = ? AND semester = ?
    ");
    $subStmt->bind_param("iss", $student_id, $ay, $semester);
    $subStmt->execute();
    $subjects = $subStmt->get_result();
    $subCount = $subjects->num_rows;

    // Total units
    $totalStmt = $conn->prepare("
        SELECT SUM(units) AS total_units
        FROM student_subjects
        WHERE student_id = ? AND academic_year = ? AND semester = ?
    ");
    $totalStmt->bind_param("iss", $student_id, $ay, $semester);
    $totalStmt->execute();
    $totalUnits = $totalStmt->get_result()->fetch_assoc()['total_units'] ?? 0;
    $totalStmt->close();

    $first = true;
?>
    <?php while($sub = $subjects->fetch_assoc()): 
        $gradeValue = floatval($sub['grade']);
        if($gradeValue >= 1.0 && $gradeValue <= 3.0){
            $gradeClass = "bg-green-200 text-green-800";
        } elseif($gradeValue == 5.0){
            $gradeClass = "bg-red-200 text-red-800";
        } else {
            $gradeClass = "";
        }
    ?>
    <tr data-student-id="<?= $student_id ?>" data-subject-id="<?= $sub['id'] ?>" class="<?= ($sub['id']%2==0?'bg-gray-50':'bg-white') ?> hover:bg-gray-100">
        <?php if($first): ?>
            <td rowspan="<?= $subCount ?>"><?= htmlspecialchars($student['name']) ?></td>
            <td rowspan="<?= $subCount ?>"><?= htmlspecialchars($student['sex']) ?></td>
            <td rowspan="<?= $subCount ?>"><?= htmlspecialchars($student['course']) ?></td>
            <td rowspan="<?= $subCount ?>"><?= $student['year_level'] ?></td>
            <td rowspan="<?= $subCount ?>"><?= htmlspecialchars($student['major']) ?></td>
            <td rowspan="<?= $subCount ?>"><?= $totalUnits ?></td>
            <?php $first = false; ?>
        <?php endif; ?>
        <td contenteditable="true" class="editableSubject px-4 py-2 border rounded" data-column="subject_code"><?= htmlspecialchars($sub['subject_code']) ?></td>
        <td contenteditable="true" class="editableSubject px-4 py-2 border rounded" data-column="units"><?= $sub['units'] ?></td>
        <td contenteditable="true" class="editableSubject px-4 py-2 border rounded <?= $gradeClass ?>" data-column="grade"><?= htmlspecialchars($sub['grade'] ?? '') ?></td>
    </tr>
    <?php endwhile; $subStmt->close(); endwhile; ?>
</tbody>
</table>

<?php
$tableHTML = ob_get_clean();

// 4️⃣ Pagination
$paginationHTML = '<div class="flex gap-2">';
$window = 5;
$start = max(1, $page - floor($window/2));
$end = min($totalPages, $start + $window - 1);
$start = max(1, $end - $window + 1);

if($page > 1){
    $paginationHTML .= '<button class="paginationBtn px-3 py-1 border rounded" data-page="1">First</button>';
    $paginationHTML .= '<button class="paginationBtn px-3 py-1 border rounded" data-page="'.($page-1).'">&lt;</button>';
}

for($i=$start;$i<=$end;$i++){
    $paginationHTML .= '<button class="paginationBtn px-3 py-1 border rounded '.($i==$page?'bg-blue-500 text-white':'').'" data-page="'.$i.'">'.$i.'</button>';
}

if($page < $totalPages){
    $paginationHTML .= '<button class="paginationBtn px-3 py-1 border rounded" data-page="'.($page+1).'">&gt;</button>';
    $paginationHTML .= '<button class="paginationBtn px-3 py-1 border rounded" data-page="'.$totalPages.'">Last</button>';
}

$paginationHTML .= '</div>';

echo json_encode(['table'=>$tableHTML,'pagination'=>$paginationHTML]);
?>
