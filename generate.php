<?php
session_start();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>

<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_registrar";
$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset('utf8mb4');

// Read filter from query string, or pick latest AY/Sem from your data
$academic_year = $_GET['academic_year'] ?? null;
$semester      = $_GET['semester'] ?? null;

if (!$academic_year || !$semester) {
    // Try to infer the latest available combo from student_subjects
    $res = $conn->query("
        SELECT academic_year, semester
        FROM student_subjects
        GROUP BY academic_year, semester
        ORDER BY academic_year DESC,
                 FIELD(semester,'1st Sem','2nd Sem','Summer') DESC
        LIMIT 1
    ");
    if ($row = $res->fetch_assoc()) {
        $academic_year = $academic_year ?: $row['academic_year'];
        $semester      = $semester ?: $row['semester'];
    }
}

// Safe fallbacks if table is empty
$academic_year = $academic_year ?: '2024-2025';
$semester      = $semester      ?: '1st Sem';

// --------- COUNTS ---------

$totalStudents = 0;

if (!empty($academic_year) && !empty($semester)) {
    $sql = "SELECT COUNT(DISTINCT students.id) AS c
            FROM students
            JOIN student_subjects 
              ON students.id = student_subjects.student_id
            WHERE student_subjects.academic_year = '$academic_year'
              AND student_subjects.semester = '$semester'";
    $res = $conn->query($sql);
    if ($res) {
        $row = $res->fetch_assoc();
        $totalStudents = (int)$row['c'];
    }
}

// Total distinct subjects for selected AY/Sem (no 'subjects' table needed)
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT subject_code) AS c
    FROM student_subjects
    WHERE academic_year = ? AND semester = ?
");
$stmt->bind_param("ss", $academic_year, $semester);
$stmt->execute();
$totalSubjects = (int)$stmt->get_result()->fetch_assoc()['c'];

// Total students enrolled (have any subject this AY/Sem)
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT student_id) AS c
    FROM student_subjects
    WHERE academic_year = ? AND semester = ?
");
$stmt->bind_param("ss", $academic_year, $semester);
$stmt->execute();
$totalEnrolled = (int)$stmt->get_result()->fetch_assoc()['c'];

// Students WITH grades (grade not null/empty)
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT student_id) AS c
    FROM student_subjects
    WHERE academic_year = ? AND semester = ?
      AND grade IS NOT NULL AND grade <> ''
");
$stmt->bind_param("ss", $academic_year, $semester);
$stmt->execute();
$totalWithGrades = (int)$stmt->get_result()->fetch_assoc()['c'];

// Students WITHOUT grades yet
$totalWithoutGrades = max(0, $totalEnrolled - $totalWithGrades);

// Dropdown data
$yearsRes = $conn->query("SELECT DISTINCT academic_year FROM student_subjects ORDER BY academic_year DESC");
$semestersList = ['1st Sem','2nd Sem','Summer'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="img/logo.png">
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<!-- Boxicons -->
	<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
	<link href='https://unpkg.com/boxicons@2.1.4/dist/boxicons.js' rel='stylesheet'>

	<!-- My CSS -->
	<link rel="stylesheet" href="css/registrar.css">

    <style>
/* Custom select style */
select.custom-select {
    appearance: none;              /* Remove default arrow */
    -webkit-appearance: none;
    -moz-appearance: none;
    background-color: #f9fafb;     /* light gray background */
    border: 1px solid #d1d5db;     /* gray border */
    padding: 10px 40px 10px 12px;  /* space for custom arrow */
    border-radius: 0.75rem;        /* rounded-lg */
    font-size: 0.95rem;
    color: #111827;
    cursor: pointer;
    transition: all 0.2s ease;
    background-image: url("data:image/svg+xml;utf8,<svg fill='none' stroke='%236b7280' stroke-width='2' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'></path></svg>");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 20px;
}

select.custom-select:focus {
    outline: none;
    border-color: #2563eb; /* blue-600 */
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.3);
}

.dark select.custom-select {
    background-color: #1f2937; /* dark mode */
    border-color: #374151;
    color: #f3f4f6;
    background-image: url("data:image/svg+xml;utf8,<svg fill='none' stroke='%23d1d5db' stroke-width='2' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'></path></svg>");
}
</style>

	<title>AdminHub</title>
</head>
<body>
	<!-- SIDEBAR -->
	<section id="sidebar">
		<a href="#" class="brand">
			<img src="img/logo.png" alt="Logo" style="width:60px; height:60px;">
			<span class="text">AdminHub</span>
		</a>
		<ul class="side-menu top">
			<li>
				<a href="registrar_dashboard.php">
					<i class='bx bxs-dashboard bx-sm' ></i>
					<span class="text">Dashboard</span>
				</a>
			</li>
			<li>
				<a href="bulkupload.php">
					<i class='bx bxs-group bx-sm' ></i>
					<span class="text">Bulk Upload</span>
				</a>
			</li>
			<li>
				<a href="grades.php">
					<i class='bx bxs-doughnut-chart bx-sm' ></i>
					<span class="text">Grades</span>
				</a>
			</li>
			<li class="active">
				<a href="#">
					<i class='bx bxs-message-dots bx-sm' ></i>
					<span class="text">Report</span>
				</a>
			</li>
			<li>
                <a href="registrar_calendar.php">
                    <i class='bx bx-file bx-sm'></i>
                    <span class="text">Request</span>
                </a>
            </li>
		</ul>
		<ul class="side-menu bottom">
			<li>
				<a href="#">
					<i class='bx bxs-cog bx-sm bx-spin-hover' ></i>
					<span class="text">Settings</span>
				</a>
			</li>
			<li>
				<a href="logout.php" class="logout">
                    <i class='bx bx-power-off bx-sm bx-burst-hover'></i>
                    <span class="text">Logout</span>
                </a>
			</li>
		</ul>
	</section>
	<!-- SIDEBAR -->



	<!-- CONTENT -->
	<section id="content">
		<!-- NAVBAR -->
<nav>
    <i class='bx bx-menu bx-sm' ></i>
    <a href="#" class="nav-link">Categories</a>
    <form action="#">
        <div class="form-input">
            <input type="search" placeholder="Search...">
            <button type="submit" class="search-btn"><i class='bx bx-search' ></i></button>
        </div>
    </form>
    <input type="checkbox" class="checkbox" id="switch-mode" hidden />
    <label class="swith-lm" for="switch-mode">
        <i class="bx bxs-moon"></i>
        <i class="bx bx-sun"></i>
        <div class="ball"></div>
    </label>

    <!-- Notification Bell -->
    <a href="#" class="notification" id="notificationIcon">
        <i class='bx bxs-bell bx-tada-hover' ></i>
        <span class="num">8</span>
    </a>
    <div class="notification-menu" id="notificationMenu">
        <ul>
            <li>New message from John</li>
            <li>Your order has been shipped</li>
            <li>New comment on your post</li>
            <li>Update available for your app</li>
            <li>Reminder: Meeting at 3PM</li>
        </ul>
    </div>

    <!-- Profile Menu -->
    <a href="#" class="profile" id="profileIcon">
        <img src="https://placehold.co/600x400/png" alt="Profile">
    </a>
    <div class="profile-menu" id="profileMenu">
        <ul>
            <li><a href="#">My Profile</a></li>
            <li><a href="#">Settings</a></li>
            <li><a href="#">Log Out</a></li>
        </ul>
    </div>
</nav>
<!-- NAVBAR -->


		<!-- MAIN -->
		<main class="p-6">
    <h1 class="text-3xl font-bold mb-6 text-gray-800 flex items-center gap-2">
        <i class='bx bxs-report bx-tada text-blue-600'></i> 
        Generate Student Reports
    </h1>

    <form action="report_view.php" method="get" target="_blank" 
          class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl p-6 space-y-6 max-w-4xl border border-gray-200 dark:border-gray-700">

        <!-- Grid Layout -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Academic Year -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                    Academic Year
                </label>
                <select name="academic_year" 
                    class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 px-4 py-3 shadow-sm
                           focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition cursor-pointer hover:shadow-md">
                    <option value="all">All Academic Years</option>
                    <?php while($yr = $yearsRes->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($yr['academic_year']) ?>">
                        <?= htmlspecialchars($yr['academic_year']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Semester -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                    Semester
                </label>
                <select name="semester" 
                    class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 px-4 py-3 shadow-sm
                           focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition cursor-pointer hover:shadow-md">
                    <option value="all">All Semesters</option>
                    <option value="1st Sem">1st Sem</option>
                    <option value="2nd Sem">2nd Sem</option>
                    <option value="Summer">Summer</option>
                </select>
            </div>

            <!-- Report Type -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                    Report Type
                </label>
                <select name="report_type" id="report_type" onchange="toggleStudentInput()"
                    class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 px-4 py-3 shadow-sm
                           focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition cursor-pointer hover:shadow-md">
                    <option value="all_students">All Students</option>
                    <option value="with_grades">Students with Grades</option>
                    <option value="without_grades">Students without Grades</option>
                    <option value="by_course">By Course</option>
                    <option value="specific_student">Specific Student</option>
                </select>
            </div>

            <!-- Student Input (Hidden by default) -->
            <div id="studentInput" style="display:none;">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                    Enter Student ID or Name
                </label>
                <div class="relative">
                    <input type="text" id="student_query" name="student_query" 
                        placeholder="e.g., 20231234 or Juan Dela Cruz"
                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 px-4 py-3 shadow-sm
                            focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition placeholder-gray-400 hover:shadow-md" 
                        onkeyup="searchStudent(this.value)" autocomplete="off" />

                    <!-- Suggestion box -->
                    <ul id="suggestions" 
                        class="absolute z-10 w-full bg-white border border-gray-300 rounded-xl mt-1 shadow-lg hidden max-h-60 overflow-y-auto">
                    </ul>
                </div>
            </div>

        <!-- Button -->
        <div class="flex justify-end pt-4">
            <button type="submit" 
                class="bg-gradient-to-r from-blue-600 to-indigo-600 
                       hover:from-blue-700 hover:to-indigo-700 
                       text-white font-semibold px-6 py-3 
                       rounded-xl shadow-md transition transform duration-200 
                       hover:-translate-y-0.5 hover:shadow-lg flex items-center gap-2">
                <i class='bx bx-line-chart text-xl'></i> Generate Report
            </button>
        </div>
    </form>
</main>

<script>
function searchStudent(query) {
    let suggestionBox = document.getElementById("suggestions");
    if (query.length < 2) { 
        suggestionBox.innerHTML = "";
        suggestionBox.classList.add("hidden");
        return;
    }

    fetch("search_student.php?q=" + encodeURIComponent(query))
        .then(response => response.text())
        .then(data => {
            suggestionBox.innerHTML = data;
            suggestionBox.classList.remove("hidden");
        });
}

function selectStudent(value) {
    document.getElementById("student_query").value = value;
    document.getElementById("suggestions").classList.add("hidden");
}
</script>

<script>
function toggleStudentInput() {
    const reportType = document.getElementById("report_type").value;
    const studentInput = document.getElementById("studentInput");
    studentInput.style.display = (reportType === "specific_student") ? "block" : "none";
}
</script>



	</section>
	<!-- CONTENT -->
	
<script src="https://cdn.tailwindcss.com"></script>
	<script src="js/script.js"></script>
</body>
</html>