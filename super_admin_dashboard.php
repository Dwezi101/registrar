<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if ($_SESSION['role'] !== 'super_admin') {
    header("Location: login.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Super Admin Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
/* Floating Box Animation */
.floating-box {
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.floating-box:hover {
  transform: translateY(-5px);
  box-shadow: 0 20px 30px rgba(0,0,0,0.2);
}
</style>
</head>
<body class="bg-gray-100">

<!-- Floating Box -->
<div class="floating-box fixed top-20 right-5 w-72 bg-white rounded-2xl shadow-xl border border-gray-200 p-6 flex flex-col space-y-6 z-50">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-800">👋 Super Admin</h2>
        <button id="closeBox" class="text-gray-400 hover:text-gray-600">&times;</button>
    </div>

    <div class="flex flex-col space-y-3">
        <a href="audit_trail.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-50 transition">
            <i class='bx bx-receipt text-blue-500 mr-3'></i> Audit Trail
        </a>
        <a href="manage_users.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-green-50 transition">
            <i class='bx bx-user text-green-500 mr-3'></i> Manage Users
        </a>
        <a href="settings.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-yellow-50 transition">
            <i class='bx bx-cog text-yellow-500 mr-3'></i> Settings
        </a>
        <a href="logout.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-red-50 transition">
            <i class='bx bx-power-off text-red-500 mr-3'></i> Logout
        </a>
    </div>
</div>

<!-- Page Content -->
<div class="p-10 ml-0 md:ml-0">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Welcome Super Admin!</h1>
    <p class="text-gray-600">This is your dashboard. You can use the floating box at the top right to access quick actions like Audit Trail, Manage Users, Settings, and Logout.</p>
</div>

<script>
    const box = document.querySelector('.floating-box');
    const closeBtn = document.getElementById('closeBox');
    closeBtn.addEventListener('click', () => {
        box.style.display = 'none';
    });
</script>

<!-- Boxicons -->
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

</body>
</html>
