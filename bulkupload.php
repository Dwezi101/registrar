<?php
// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_registrar";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Force UTF-8
if (!$conn->set_charset("utf8mb4")) {
    printf("Error loading character set utf8mb4: %s\n", $conn->error);
    exit();
}

$message = ""; // store status message

if (isset($_POST['upload'], $_POST['academic_year'], $_POST['semester'])) {
    $academic_year = $_POST['academic_year'];  
    $semester      = $_POST['semester'];       

    $fileName = $_FILES['file']['name'];  
    $tmpName  = $_FILES['file']['tmp_name'];

    $today = date("Y-m-d");

    // Prevent duplicate uploads
    $checkUpload = $conn->prepare("SELECT id FROM uploads WHERE file_name=? AND upload_date=?");
    $checkUpload->bind_param("ss", $fileName, $today);
    $checkUpload->execute();
    $checkUpload->store_result();

    if ($checkUpload->num_rows > 0) {
        $message = '<p class="text-red-600 font-semibold">❌ This file has already been uploaded today!</p>';
    } else {
        $checkUpload->close();

        if ($_FILES['file']['size'] > 0) {
            $file = fopen($tmpName, "r");
            fgetcsv($file); // Skip header if needed

            $currentStudent = [];

            while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
                if (!empty(trim($column[0]))) {
                    $el      = trim($column[0]);
                    $name    = trim($column[1]);
                    $sex_m   = trim($column[2]);
                    $sex_f   = trim($column[3]);
                    $course  = trim($column[4]);
                    $year    = trim($column[5]);
                    $major   = trim($column[6]);
                    $remarks = isset($column[20]) ? trim($column[20]) : '';

                    if (empty($name) || $name == '--') continue;

                    $sex = ($sex_m !== '' && $sex_f === '') ? 'M' : (($sex_f !== '' && $sex_m === '') ? 'F' : 'U');

                    $check = $conn->prepare("SELECT id FROM students WHERE name=? AND course=? AND year_level=? LIMIT 1");
                    $check->bind_param("ssi", $name, $course, $year);
                    $check->execute();
                    $check->bind_result($student_id);
                    $exists = $check->fetch();
                    $check->close();

                    if (!$exists) {
                        $stmt = $conn->prepare("INSERT INTO students (name, sex, course, year_level, major, remarks) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("sssiss", $name, $sex, $course, $year, $major, $remarks);
                        $stmt->execute();
                        $student_id = $stmt->insert_id;
                        $stmt->close();
                    }

                    $currentStudent = [
                        'id' => $student_id
                    ];
                }

                if (!empty($currentStudent)) {
                    for ($i = 7; $i < count($column); $i += 2) {
                        $subject_code = isset($column[$i]) ? trim($column[$i]) : '';
                        $units        = isset($column[$i + 1]) ? trim($column[$i + 1]) : '';

                        if (!empty($subject_code) && $units !== '' && $units != '--') {
                            $checkSub = $conn->prepare("SELECT id FROM student_subjects WHERE student_id=? AND subject_code=? AND academic_year=? AND semester=? LIMIT 1");
                            $checkSub->bind_param("isss", $currentStudent['id'], $subject_code, $academic_year, $semester);
                            $checkSub->execute();
                            $checkSub->store_result();

                            if ($checkSub->num_rows == 0) {
                                $stmt = $conn->prepare("INSERT INTO student_subjects (student_id, subject_code, units, grade, academic_year, semester) VALUES (?, ?, ?, NULL, ?, ?)");
                                $stmt->bind_param("isiss", $currentStudent['id'], $subject_code, $units, $academic_year, $semester);
                                $stmt->execute();
                                $stmt->close();
                            }
                            $checkSub->close();
                        }
                    }
                }
            }

            fclose($file);

            $insertLog = $conn->prepare("INSERT INTO uploads (file_name, upload_date) VALUES (?, ?)");
            $insertLog->bind_param("ss", $fileName, $today);
            $insertLog->execute();
            $insertLog->close();

            // ✅ Audit Trail logging
            $action = "Bulk uploaded file: $fileName for $academic_year $semester";
            $user_id = $_SESSION['user_id'] ?? 0; // logged-in user ID
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

            $stmtAudit = $conn->prepare("INSERT INTO audit_trail (user_id, action, timestamp, ip_address) VALUES (?, ?, NOW(), ?)");
            $stmtAudit->bind_param("iss", $user_id, $action, $ip_address);
            $stmtAudit->execute();
            $stmtAudit->close();

            $message = '<p class="text-green-600 font-semibold">✅ Bulk upload completed successfully!</p>';
        } else {
            $message = '<p class="text-yellow-600 font-semibold">⚠️ Please upload a valid file.</p>';
        }
    }
}
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
    <script src="https://cdn.tailwindcss.com"></script>

	<!-- My CSS -->
	<link rel="stylesheet" href="css/registrar.css">

	<title>AdminHub</title>

    <style>
        @keyframes slideUp {
        from { transform: translateY(100%); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
        }
        .animate-slide-up {
        animation: slideUp 0.4s ease-out;
        }
    </style>

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
			<li class="active">
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
				<a href="registrar_calendar.php">
					<i class='bx bxs-calendar bx-sm'></i>
					<span class="text">Calendar</span>
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
<div id="toast" class="fixed bottom-5 right-5 hidden bg-white shadow-lg rounded-xl p-4 border border-gray-200 flex items-center space-x-3 transition-opacity duration-500">
    <i id="toastIcon" class="bx bx-time-five text-blue-600 text-2xl"></i>
    <div>
        <p id="toastTitle" class="text-gray-800 font-semibold">Upload Estimate</p>
        <p id="toastMessage" class="text-gray-600 text-sm"></p>
    </div>
</div>


		<!-- MAIN -->
		<main class="flex justify-center items-center min-h-screen bg-gray-100">
    <div class="bg-white shadow-lg rounded-xl p-8 w-full max-w-4xl flex space-x-6">
        
        <!-- Instructions / Sidebar -->
        <div class="w-1/3 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-md flex flex-col justify-start space-y-2">
            <div class="flex items-center space-x-2">
                <i class='bx bxs-error text-yellow-500 text-2xl'></i>
                <span class="font-semibold text-gray-800">Bulk Upload Instructions</span>
            </div>
            <p class="text-gray-700 text-sm">
                Use <span class="font-semibold">UTF-8 encoded CSV</span> for bulk upload.  
                If using Excel, ensure the header matches exactly:
            </p>
            <p class="font-mono text-xs bg-gray-100 p-2 rounded overflow-x-auto text-gray-800">
el, name, sex_m, sex_f, course, year, major, subject_code, units, subject_code, units, subject_code, units, subject_code, units, subject_code, units, subject_code, units, subject_code, units, remarks
            </p>
            <p class="text-gray-700 text-sm font-semibold mt-2">Duplicate uploads are not allowed.</p>
        </div>

        <!-- Form -->
        <div class="w-2/3">
            <h1 class="text-2xl font-bold mb-6 text-center text-gray-800">Bulk Upload Students</h1>

            <?php if (!empty($message)) echo "<div class='mb-4 text-center'>$message</div>"; ?>

            <form id="uploadForm" method="POST" enctype="multipart/form-data" class="space-y-5">
                <div>
                    <label class="block text-gray-700 mb-2 font-medium">Academic Year</label>
                    <select name="academic_year" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="">Select Year</option>
                        <option value="2024-2025">2024-2025</option>
                        <option value="2025-2026">2025-2026</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 mb-2 font-medium">Semester</label>
                    <select name="semester" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="">Select Semester</option>
                        <option value="1st Sem">1st Sem</option>
                        <option value="2nd Sem">2nd Sem</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 mb-2 font-medium">Upload CSV</label>
                    <input type="file" name="file" accept=".csv" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"/>
                </div>
                <div>
                    <button type="submit" name="upload" class="w-full bg-blue-600 text-white py-2 rounded-md font-semibold hover:bg-blue-700 transition duration-200">
                        Upload CSV
                    </button>
                </div>
            </form>
        </div>

    </div>
</main>

		<!-- MAIN -->

        <script>
        document.getElementById("uploadForm").addEventListener("submit", function(e) {
            const fileInput = document.querySelector("input[name='file']");
            const toast = document.getElementById("toast");
            const toastMsg = document.getElementById("toastMessage");

            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const reader = new FileReader();

                reader.onload = function(event) {
                    // Count rows in the CSV
                    const lines = event.target.result.split(/\r\n|\n/).filter(line => line.trim() !== "").length;

                    // Remove header row if exists
                    const rows = lines > 1 ? lines - 1 : lines;

                    // Estimate time: ~0.05 sec per row (tweakable)
                    let seconds = Math.ceil(rows * 0.05);
                    if (seconds < 2) seconds = 2;

                    // Convert seconds → minutes:seconds
                    const mins = Math.floor(seconds / 60);
                    const secs = seconds % 60;
                    let timeStr = "";
                    if (mins > 0) {
                        timeStr = `${mins}m ${secs}s`;
                    } else {
                        timeStr = `${secs}s`;
                    }

                    // Update toast message
                    toastMsg.textContent = `Estimated upload time: ~${timeStr} for ${rows} rows`;

                    // Show toast
                    toast.classList.remove("hidden");
                    toast.classList.add("animate-slide-up");

                    setTimeout(() => {
                        toast.classList.add("hidden");
                        toast.classList.remove("animate-slide-up");
                    }, 5000);
                };

                reader.readAsText(file);
            }
        });
        </script>





        
	</section>
	<!-- CONTENT -->
	

	<script src="js/script.js"></script>
</body>
</html>