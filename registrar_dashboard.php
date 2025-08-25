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

// Prepare data for student comparison by AY
$semTotals = [];
$res = $conn->query("
    SELECT academic_year, semester, COUNT(DISTINCT student_id) AS total
    FROM student_subjects
    GROUP BY academic_year, semester
    ORDER BY academic_year ASC, semester ASC
");

while ($row = $res->fetch_assoc()) {
    $ay = $row['academic_year'];
    $sem = $row['semester'];
    $semTotals[$ay][$sem] = (int)$row['total'];
}

// Build arrays
$ayLabels = array_keys($semTotals);
$firstSem = [];
$secondSem = [];

foreach ($ayLabels as $ay) {
    $firstSem[] = $semTotals[$ay]['1st Sem'] ?? 0;
    $secondSem[] = $semTotals[$ay]['2nd Sem'] ?? 0;
}

$ayLabels = json_encode($ayLabels);
$firstSem = json_encode($firstSem);
$secondSem = json_encode($secondSem);



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
			<li class="active">
				<a href="#">
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
			<li>
				<a href="generate.php">
					<i class='bx bxs-message-dots bx-sm' ></i>
					<span class="text">Report</span>
				</a>
			</li>
			<li>
				<a href="#">
					<i class='bx bxs-group bx-sm' ></i>
					<span class="text">Next version</span>
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
		<main>
			<div class="head-title">
				<div class="left">
					<h1>Dashboard</h1>
					<ul class="breadcrumb">
						<li>
							<a href="#">Dashboard</a>
						</li>
						<li><i class='bx bx-chevron-right' ></i></li>
						<li>
							<a class="active" href="#">Home</a>
						</li>
					</ul>
				</div>
				<a href="javascript:void(0)" class="btn-download" onclick="openModal()">
					<span class="text">V1.0 Released</span>
				</a>
			</div>

			<form method="get" class="filter-form">
				<select name="academic_year" class="filter-select">
					<?php while($yr = $yearsRes->fetch_assoc()): ?>
					<option value="<?= htmlspecialchars($yr['academic_year']) ?>"
						<?= $yr['academic_year']===$academic_year ? 'selected' : '' ?>>
						<?= htmlspecialchars($yr['academic_year']) ?>
					</option>
					<?php endwhile; ?>
				</select>

				<select name="semester" class="filter-select">
					<?php foreach ($semestersList as $semOpt): ?>
					<option value="<?= htmlspecialchars($semOpt) ?>"
						<?= $semOpt===$semester ? 'selected' : '' ?>>
						<?= htmlspecialchars($semOpt) ?>
					</option>
					<?php endforeach; ?>
				</select>

				<button type="submit" class="filter-btn">
					Apply
				</button>

				<span class="filter-label">
					Showing: <strong><?= htmlspecialchars($academic_year) ?> • <?= htmlspecialchars($semester) ?></strong>
				</span>
			</form>


			<ul class="box-info">
				<li>
					<i class='bx bxs-user'></i>
					<span class="text">
						<h3><?php echo $totalStudents; ?></h3>
						<p>Total Students (<?php echo $academic_year . ' - ' . $semester; ?>)</p>
					</span>
				</li>

				<li>
					<i class='bx bxs-book'></i>
					<span class="text">
					<h3><?= number_format($totalSubjects) ?></h3>
					<p>Total Subjects (<?= htmlspecialchars($academic_year) ?> • <?= htmlspecialchars($semester) ?>)</p>
					</span>
				</li>

				<li>
					<i class='bx bxs-check-circle'></i>
					<span class="text">
					<h3><?= number_format($totalWithGrades) ?></h3>
					<p>Students with Grades</p>
					</span>
				</li>

				<li>
					<i class='bx bxs-time-five'></i>
					<span class="text">
					<h3><?= number_format($totalWithoutGrades) ?></h3>
					<p>Students without Grades</p>
					</span>
				</li>
			</ul>

			<div class="card" style="margin-top:20px; background:#fff; padding:20px; border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,0.1);">
    <h3 class="text-xl font-semibold mb-4">📈 Students Trend by Academic Year & Semester</h3>
    <canvas id="studentsChart" height="100"></canvas>
</div>

			
		</main>

		<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('studentsChart').getContext('2d');
const studentsChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= $ayLabels ?>, // academic years
        datasets: [
            {
                label: '1st Semester',
                data: <?= $firstSem ?>,
                fill: false,
                tension: 0.4,
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                pointRadius: 5,
                pointHoverRadius: 7,
                borderWidth: 2
            },
            {
                label: '2nd Semester',
                data: <?= $secondSem ?>,
                fill: false,
                tension: 0.4,
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                pointBackgroundColor: 'rgba(255, 99, 132, 1)',
                pointRadius: 5,
                pointHoverRadius: 7,
                borderWidth: 2
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: { mode: 'index', intersect: false }
        },
        interaction: {
            mode: 'nearest',
            axis: 'x',
            intersect: false
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { precision: 0 }
            }
        }
    }
});
</script>


		<!-- Modal -->
		<div id="downloadModal" class="modal">
		<div class="modal-content">
			<span class="close" onclick="closeModal()">&times;</span>
			<h2>🚀 V1.0</h2>
			<p>Version <strong>1.0</strong> has been released.  
			Download now and enjoy the latest features.</p>
		</div>
		</div>

		<script>
			function openModal() {
			document.getElementById("downloadModal").style.display = "block";
			}
			function closeModal() {
			document.getElementById("downloadModal").style.display = "none";
			}

			// Close if user clicks outside modal
			window.onclick = function(event) {
			let modal = document.getElementById("downloadModal");
			if (event.target == modal) {
				modal.style.display = "none";
			}
			}
		</script>
		<!-- MAIN -->
	</section>
	<!-- CONTENT -->
	

	<script src="js/script.js"></script>
</body>
</html>