<?php
// report_view.php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_registrar";
$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset('utf8mb4');

// Inputs
$academic_year = $_GET['academic_year'] ?? '';
$semester      = $_GET['semester'] ?? '';
$report_type   = $_GET['report_type'] ?? 'all_students';
$student_id    = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null; 
$course        = $_GET['course'] ?? null; 

// Guard
if ($academic_year === '' || $semester === '') {
    header("Content-Type: text/html; charset=utf-8");
    echo "<p>Missing academic_year or semester.</p>";
    exit;
}

$sql    = '';
$params = [];
$types  = '';

switch ($report_type) {
    case 'with_grades':
        $sql = "SELECT s.id, s.name, s.course, ss.subject_code, ss.units, ss.grade, ss.academic_year, ss.semester
                FROM students s
                JOIN student_subjects ss ON s.id = ss.student_id
                WHERE ss.academic_year = ? AND ss.semester = ?
                  AND ss.grade IS NOT NULL AND ss.grade <> ''
                ORDER BY s.name ASC, ss.subject_code ASC";
        $params = [$academic_year, $semester];
        $types  = "ss";
        break;

    case 'without_grades':
        $sql = "SELECT s.id, s.name, s.course, ss.subject_code, ss.units, ss.grade, ss.academic_year, ss.semester
                FROM students s
                JOIN student_subjects ss ON s.id = ss.student_id
                WHERE ss.academic_year = ? AND ss.semester = ?
                  AND (ss.grade IS NULL OR ss.grade = '')
                ORDER BY s.name ASC, ss.subject_code ASC";
        $params = [$academic_year, $semester];
        $types  = "ss";
        break;

    case 'by_course':
        if ($course && $course !== '') {
            $sql = "SELECT s.course, s.id, s.name, ss.subject_code, ss.units, ss.grade, ss.academic_year, ss.semester
                    FROM students s
                    JOIN student_subjects ss ON s.id = ss.student_id
                    WHERE ss.academic_year = ? AND ss.semester = ? AND s.course = ?
                    ORDER BY s.course ASC, s.name ASC, ss.subject_code ASC";
            $params = [$academic_year, $semester, $course];
            $types  = "sss";
        } else {
            $sql = "SELECT s.course, s.id, s.name, ss.subject_code, ss.units, ss.grade, ss.academic_year, ss.semester
                    FROM students s
                    JOIN student_subjects ss ON s.id = ss.student_id
                    WHERE ss.academic_year = ? AND ss.semester = ?
                    ORDER BY s.course ASC, s.name ASC, ss.subject_code ASC";
            $params = [$academic_year, $semester];
            $types  = "ss";
        }
        break;

    case 'specific_student':
    $student_query = $_GET['student_query'] ?? null;
    $academic_year = $_GET['academic_year'] ?? 'all';
    $semester      = $_GET['semester'] ?? 'all';

    if (!$student_query) {
        die("Missing student_query for specific_student report.");
    }

    // numeric ID or name lookup
    if (is_numeric($student_query)) {
        $student_id = (int)$student_query;
    } else {
        $stmt_lookup = $conn->prepare("SELECT id FROM students WHERE name LIKE ?");
        $like = "%" . $student_query . "%";
        $stmt_lookup->bind_param("s", $like);
        $stmt_lookup->execute();
        $res_lookup = $stmt_lookup->get_result();

        if ($res_lookup->num_rows === 0) {
            die("No student found matching '$student_query'.");
        }

        $student = $res_lookup->fetch_assoc();
        $student_id = $student['id'];
    }

    // --- Build SQL dynamically ---
    $sql = "SELECT s.id, s.name, s.course, s.year_level, 
               ss.academic_year, ss.semester, ss.subject_code, ss.grade
            FROM students s
            JOIN student_subjects ss ON s.id = ss.student_id
            WHERE s.id = ?";

    $params = [$student_id];
    $types  = "i";

    // Add Academic Year filter only if not "all"
    if ($academic_year !== 'all') {
        $sql .= " AND ss.academic_year = ?";
        $params[] = $academic_year;
        $types   .= "s";
    }

    // Add Semester filter only if not "all"
    if ($semester !== 'all') {
        $sql .= " AND ss.semester = ?";
        $params[] = $semester;
        $types   .= "s";
    }

    $sql .= " ORDER BY ss.academic_year ASC,
                    FIELD(ss.semester,'1st Sem','2nd Sem','Summer') ASC,
                    ss.subject_code ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    break;


    case 'all_students':
    default:
        $sql = "SELECT s.id, s.name, s.course, ss.subject_code, ss.units, ss.grade, ss.academic_year, ss.semester
                FROM students s
                JOIN student_subjects ss ON s.id = ss.student_id
                WHERE ss.academic_year = ? AND ss.semester = ?
                ORDER BY s.name ASC, ss.subject_code ASC";
        $params = [$academic_year, $semester];
        $types  = "ss";
        break;
}

// --- Single prepare & execute ---
$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Output
header("Content-Type: text/html; charset=utf-8");

$niceTitle = [
    'all_students'     => 'All Students',
    'with_grades'      => 'Students with Grades',
    'without_grades'   => 'Students without Grades',
    'by_course'        => 'By Course' . ($course ? " ({$course})" : ''),
    'specific_student' => 'Specific Student' . ($student_id ? " (ID: {$student_id})" : '')
];

echo "<h2>" . htmlspecialchars($niceTitle[$report_type] ?? $report_type) . "</h2>";
echo "<p>Academic Year: " . htmlspecialchars($academic_year) . " | Semester: " . htmlspecialchars($semester) . "</p>";

echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr>";
if ($result->num_rows > 0) {
    $fields = $result->fetch_fields();
    foreach ($fields as $f) {
        echo "<th>" . htmlspecialchars((string)$f->name) . "</th>";
    }
    echo "</tr>";

    $result->data_seek(0);

    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $cell) {
            echo "<td>" . htmlspecialchars((string)($cell ?? '')) . "</td>";
        }
        echo "</tr>";
    }
} else {
    echo "<th>No data</th></tr><tr><td>No records found.</td></tr>";
}
echo "</table>";

$stmt->close();
$conn->close();
