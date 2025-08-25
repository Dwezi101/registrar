<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_registrar";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$ay = trim($_GET['ay'] ?? '');
$semester = trim($_GET['semester'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="img/logo.png">
<meta charset="UTF-8">
<title>Student List</title>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">

<div class="max-w-7xl mx-auto bg-white p-6 rounded shadow">

<div class="mb-4">
    <a href="registrar_dashboard.php" 
       class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded hover:bg-gray-700 shadow">
        ← Back to Dashboard
    </a>
</div>

    <!-- Filter Form -->
    <form id="filterForm" class="flex gap-2 mb-4">
        <select name="ay" id="aySelect" class="border rounded px-3 py-2">
            <option value="2021-2022" <?= $ay=='2021-2022'?'selected':'' ?>>2021-2022</option>
            <option value="2022-2023" <?= $ay=='2022-2023'?'selected':'' ?>>2022-2023</option>
            <option value="2023-2024" <?= $ay=='2023-2024'?'selected':'' ?>>2023-2024</option>
            <option value="2024-2025" <?= $ay=='2024-2025'?'selected':'' ?>>2024-2025</option>
            <option value="2025-2026" <?= $ay=='2025-2026'?'selected':'' ?>>2025-2026</option>
        </select>
        <select name="semester" id="semSelect" class="border rounded px-3 py-2">
            <option value="1st Sem" <?= $semester=='1st Sem'?'selected':'' ?>>1st Sem</option>
            <option value="2nd Sem" <?= $semester=='2nd Sem'?'selected':'' ?>>2nd Sem</option>
        </select>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Submit</button>
    </form>

    <!-- Display Selected AY/Semester -->
    <div class="mb-2 text-gray-700 font-semibold" id="selectedAYSemester">
        <!-- Filled dynamically via JS -->
    </div>

    <!-- Search -->
    <input type="text" id="searchInput" placeholder="Search by Name, Course, Subject..."
           class="border rounded px-3 py-2 mb-6 w-full focus:outline-none focus:ring-2 focus:ring-blue-400">

    <!-- Table Container -->
    <div class="overflow-y-auto h-[500px] rounded border border-gray-300 shadow" id="studentsTableContainer"></div>

    <!-- Pagination -->
    <div class="mt-4 flex justify-center" id="paginationControls"></div>

</div>

<script>
function loadStudents(page=1){
    var ay = $('#aySelect').val();
    var sem = $('#semSelect').val();
    var search = $('#searchInput').val();

    // Update the top span
    $('#selectedAYSemester').text('Academic Year: ' + ay + ' | Semester: ' + sem);

    history.replaceState(null, '', `grades.php?ay=${encodeURIComponent(ay)}&semester=${encodeURIComponent(sem)}`);

    $.ajax({
        url: 'fetch_students.php',
        type: 'GET',
        data: {
            page: page,
            ay: $('#aySelect').val(),
            semester: $('#semSelect').val(),
            search: $('#searchInput').val()

            
        },
        dataType: 'json',
        success: function(response){
            $('#studentsTableContainer').html(response.table);
            $('#paginationControls').html(response.pagination);
        },
        error: function(xhr, status, err){
            console.error('AJAX Error:', err, xhr.responseText);
        }

    });
    console.log("Sending AJAX: AY =", ay, "SEM =", sem, "Search =", search);
}

// Initial load
loadStudents();

// Search filter
$('#searchInput').on('keyup', function(){ loadStudents(1); });

// Filter form submit
$('#filterForm').submit(function(e){
    e.preventDefault();
    loadStudents(1);
});

// Pagination click
$(document).on('click', '.paginationBtn', function(e){
    e.preventDefault();
    const page = $(this).data('page');
    loadStudents(page);
});

// Editable subject update
$(document).on('blur', '.editableSubject', function() {
    var subId = $(this).closest('tr').data('subject-id');
    var column = $(this).data('column');
    var value = $(this).text().trim();

    if (!subId) return;

    if (column === 'grade') {
        // ✅ Allow blank (no grade yet)
        if (value === "") {
            $.post('update_subject_ajax.php', { id: subId, column: column, value: value }, function(resp) {
                var cell = $('tr[data-subject-id="'+subId+'"] td[data-column="grade"]');
                cell.removeClass('bg-green-200 text-green-800 bg-red-200 text-red-800');
            });
            return;
        }

        var gradeValue = parseFloat(value);

        // ✅ Validation: must be number, between 1.00 and 5.00, and multiple of 0.25
        if (isNaN(gradeValue) || gradeValue < 1 || gradeValue > 5 || (gradeValue * 100) % 25 !== 0) {
            alert("Invalid grade! Please enter a value between 1.00 and 5.00 (steps of 0.25).");
            loadStudents(); // reload table to restore previous valid value
            return;
        }
    }

    $.post('update_subject_ajax.php', { id: subId, column: column, value: value }, function(resp) {
        // Only handle grade coloring if column is grade
        if (column === 'grade') {
            var gradeValue = parseFloat(value);
            var cell = $('tr[data-subject-id="'+subId+'"] td[data-column="grade"]');

            cell.removeClass('bg-green-200 text-green-800 bg-red-200 text-red-800');

            if (gradeValue >= 1 && gradeValue <= 3) {
                cell.addClass('bg-green-200 text-green-800');
            } else if (gradeValue === 5) {
                cell.addClass('bg-red-200 text-red-800');
            }
        }
    });
});

</script>
</body>
</html>
