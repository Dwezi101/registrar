<?php
// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_registrar";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

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
        echo '<p class="text-red-600 font-semibold">❌ This file has already been uploaded today!</p>';
        exit();
    }
    $checkUpload->close();

    if ($_FILES['file']['size'] > 0) {
        $file = fopen($tmpName, "r");
        fgetcsv($file); // Skip header if needed

        $currentStudent = []; // To store info if subjects are on multiple rows

        while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {

            // Check if this row has a student ID -> new student
            if (!empty(trim($column[0]))) {
                // New student row
                $el      = trim($column[0]);
                $name    = trim($column[1]);
                $sex_m   = trim($column[2]);
                $sex_f   = trim($column[3]);
                $course  = trim($column[4]);
                $year    = trim($column[5]);
                $major   = trim($column[6]);
                $remarks = isset($column[20]) ? trim($column[20]) : '';

                echo "<pre>";
print_r($column);
echo "</pre>";


                if (empty($name) || $name == '--') continue;

                $sex = ($sex_m !== '' && $sex_f === '') ? 'M' : (($sex_f !== '' && $sex_m === '') ? 'F' : 'U');

                // Check if student exists
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

            // Now read subjects (columns 7 onward, in pairs)
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

        // Log upload
        $insertLog = $conn->prepare("INSERT INTO uploads (file_name, upload_date) VALUES (?, ?)");
        $insertLog->bind_param("ss", $fileName, $today);
        $insertLog->execute();
        $insertLog->close();

        echo '<p class="text-green-600 font-semibold">✅ Bulk upload completed successfully!</p>';
    } else {
        echo '<p class="text-yellow-600 font-semibold">⚠️ Please upload a valid file.</p>';
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bulk Upload Students</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="bg-white shadow-lg rounded-xl p-8 w-full max-w-md">
    <h1 class="text-2xl font-bold mb-6 text-center text-gray-800">Bulk Upload Students</h1>

    <form method="POST" enctype="multipart/form-data" class="space-y-5">
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
</body>
</html>
