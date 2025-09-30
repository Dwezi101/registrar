<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_registrar";
$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset('utf8mb4');

function addWorkingDays($startDate, $days) {
    $currentDate = strtotime($startDate);
    $addedDays = 0;
    while ($addedDays < $days) {
        $currentDate = strtotime('+1 day', $currentDate);
        $dayOfWeek = date('N', $currentDate);
        if ($dayOfWeek < 6) { // Monday to Friday only
            $addedDays++;
        }
    }
    return date('Y-m-d', $currentDate);
}

// ✅ Handle AJAX Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $contact_no = $_POST['contact_no'];
    $document_type = $_POST['document_type'];
    $request_date = date('Y-m-d');
    $release_date = addWorkingDays($request_date, 15);

    $sql = "INSERT INTO registrar_requests (first_name, last_name, contact_no, document_type, request_date, release_date)
            VALUES ('$first_name', '$last_name', '$contact_no', '$document_type', '$request_date', '$release_date')";
    mysqli_query($conn, $sql);

    echo json_encode([
        'success' => true,
        'full_name' => "$first_name $last_name",
        'document' => $document_type,
        'release_date' => date('F d, Y', strtotime($release_date))
    ]);
    exit;
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $contact_no = $_POST['contact_no'];
    $document_type = $_POST['document_type'];
    $request_date = date('Y-m-d');
    $release_date = addWorkingDays($request_date, 15);

    $sql = "INSERT INTO registrar_requests (first_name, last_name, contact_no, document_type, request_date, release_date)
            VALUES ('$first_name', '$last_name', '$contact_no', '$document_type', '$request_date', '$release_date')";
    mysqli_query($conn, $sql);
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
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>

	<!-- My CSS -->
	<link rel="stylesheet" href="css/registrar.css">

	<title>Registrar Calendar</title>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js'></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { display: flex; gap: 20px; }
        .form-box { width: 300px; }
        #calendar { flex: 1; max-width: 800px; }
        input, select { width: 100%; padding: 8px; margin: 5px 0; }
        button { padding: 10px 15px; }
    </style>

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
			<li class="active">
				<a href="#">
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

		<!-- MAIN -->
<main class="flex justify-center items-start min-h-screen bg-gray-100 py-10">
  <div class="container mx-auto flex flex-col lg:flex-row gap-10 w-full max-w-6xl px-4">

    <!-- ✅ Form Card -->
    <div class="w-full lg:w-1/3 bg-white rounded-2xl shadow-lg p-6 border border-gray-200 animate-slide-up">
      <h2 class="text-2xl font-semibold text-gray-800 mb-6 text-center">📄 Add New Request</h2>
      <form id="requestForm" class="space-y-4">

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
          <input type="text" name="first_name" placeholder="Enter first name" required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
          <input type="text" name="last_name" placeholder="Enter last name" required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Contact No.</label>
          <input type="text" name="contact_no" placeholder="09xxxxxxxxx" required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Document Type</label>
          <select name="document_type" required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <option value="">Select Document</option>
            <option value="TOR">Transcript of Records (TOR)</option>
            <option value="COG">Certificate of Grades (COG)</option>
          </select>
        </div>

        <button type="submit"
          class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg transition duration-200 shadow-md">
          ➕ Add Request
        </button>
      </form>
    </div>

    <!-- ✅ Calendar Placeholder -->
       

        <div id="calendar" class="flex-1 bg-white p-6 rounded-2xl shadow-lg border border-gray-200 animate-slide-up">
    <a href="calendar.php" 
        class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-md transition duration-200">
        Go to Calendar
    </a>
    <br><br>

    <h2 class="text-xl font-semibold text-gray-800 mb-4">📄 Released Requests</h2>

    <!-- 🔍 Search Input -->
    <input type="text" id="searchInput" placeholder="Search by name or document..."
        class="w-full mb-4 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">

    <div id="tableContainer">
        <!-- Table will load here via AJAX -->
    </div>
</div>

<script>
let currentPage = 1;

// Fetch released requests with search + pagination
function fetchReleasedRequests(query = '', page = 1) {
    currentPage = page;
    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('search', query);
    formData.append('page', page);

    fetch('fetch_released_requests.php', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(html => {
            document.getElementById('tableContainer').innerHTML = html;
        });
}

// Initial load
fetchReleasedRequests();

// Live search
document.getElementById('searchInput').addEventListener('input', function() {
    fetchReleasedRequests(this.value, 1); // reset to page 1 on search
});
</script>
</div>

</main>

<!-- ✅ Modal -->
  <div id="invoiceModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50">
    <div class="bg-white w-96 rounded-xl p-6 shadow-xl relative">
      <h2 class="text-2xl font-bold text-center text-blue-600 mb-4">🧾 Request Invoice</h2>
      <div id="invoiceContent" class="space-y-2">
        <p class="text-gray-800"><strong>Name:</strong> <span id="modalName"></span></p>
        <p class="text-gray-800"><strong>Document:</strong> <span id="modalDoc"></span></p>
        <p class="text-gray-800"><strong>Release Date:</strong> <span id="modalDate"></span></p>
        <div id="qrcode" class="mt-4 flex justify-center"></div>
      </div>
      <div class="mt-6 flex justify-between">
        <button onclick="downloadPDF()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">⬇️ Download</button>
        <button onclick="closeModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">OK</button>
      </div>
    </div>
  </div>

<script>
  document.getElementById('requestForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('ajax', '1');

    fetch('', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          document.getElementById('modalName').textContent = data.full_name;
          document.getElementById('modalDoc').textContent = data.document;
          document.getElementById('modalDate').textContent = data.release_date;

          // Generate QR Code
          const qrContent = `Name: ${data.full_name}\nDocument: ${data.document}\nRelease Date: ${data.release_date}`;
          const qrContainer = document.getElementById('qrcode');
          qrContainer.innerHTML = '';
          new QRCode(qrContainer, { text: qrContent, width: 100, height: 100 });

          document.getElementById('invoiceModal').classList.remove('hidden');
          this.reset();
        }
      });
  });

  function closeModal() {
    document.getElementById('invoiceModal').classList.add('hidden');
  }

  function downloadPDF() {
    const { jsPDF } = window.jspdf;

    // Half-letter size: 5.5 x 8.5 inches in points
    const pageWidth = 396; // 5.5 inches
    const pageHeight = 612; // 8.5 inches

    const doc = new jsPDF({
        orientation: "portrait",
        unit: "pt",
        format: [pageWidth, pageHeight]
    });

    const name = document.getElementById('modalName').textContent || "Julius Dador";
    const documentType = document.getElementById('modalDoc').textContent || "TOR";
    const releaseDate = document.getElementById('modalDate').textContent || "October 21, 2025";

    const marginX = 20;
    const marginTop = 40;

    // -------------------
    // Header: Logo + College Name
    // -------------------
    const logoImg = new Image();
    logoImg.src = 'img/logo.png';
    logoImg.onload = function() {
        const logoWidth = 60;
        const logoHeight = 60;
        doc.addImage(logoImg, "PNG", marginX, marginTop - 10, logoWidth, logoHeight);

        // College Name
        doc.setFontSize(16);
        doc.setFont("helvetica", "bold");
        doc.setTextColor(0, 40, 80); // modern dark blue
        doc.text("Concepcion Holy Cross College, Inc.", marginX + logoWidth + 10, marginTop + 20);

        // -------------------
        // Title
        // -------------------
        doc.setFontSize(14);
        doc.setFont("helvetica", "bold");
        doc.setTextColor(0);
        doc.text("Student Document Request Invoice", pageWidth / 2, marginTop + 60, { align: "center" });

        // Student Info
        const infoY = marginTop + 85;
        doc.setFontSize(12);
        doc.setFont("helvetica", "normal");
        doc.text(`Name: ${name}`, marginX, infoY);
        doc.text(`Document: ${documentType}`, marginX, infoY + 20);
        doc.text(`Release Date: ${releaseDate}`, marginX, infoY + 40);

        // Optional Note
        const noteY = infoY + 70;
        doc.setFontSize(11);
        doc.setTextColor(80);
        const noteText = "This document confirms your request for official student records. Please present this invoice when claiming your requested document. Keep this invoice for your reference, as it serves as proof of submission and expected release date. For inquiries, contact the Office of the Registrar.";

        const maxWidth = pageWidth - 2 * marginX; // width of text area
        const lines = doc.splitTextToSize(noteText, maxWidth);

        // Add text with justification
        doc.setFontSize(11);
        doc.setTextColor(80);
        doc.text(lines, marginX, noteY, { align: 'justify', maxWidth: maxWidth });

        // -------------------
        // QR code bottom-right
        // -------------------
        const qrCanvas = document.querySelector('#qrcode canvas');
        if (qrCanvas) {
            const qrData = qrCanvas.toDataURL("image/png");
            doc.addImage(qrData, "PNG", pageWidth - marginX - 80, pageHeight - 100, 80, 80);
        }

        // -------------------
        // Signature bottom-left
        // -------------------
        const signatureImg = new Image();
        signatureImg.src = 'img/sample.png';
        signatureImg.onload = function() {
            const sigWidth = 80;
            const sigHeight = 40;
            const sigY = pageHeight - 100; // signature above footer
            doc.addImage(signatureImg, "PNG", marginX, sigY, sigWidth, sigHeight);

            // Name and title under signature
            doc.setFontSize(12);
            doc.setFont("helvetica", "bold");
            doc.setTextColor(0);
            doc.text("Julius Dador", marginX + sigWidth / 2, sigY + sigHeight + 12, { align: "center" });
            doc.setFontSize(10);
            doc.setFont("helvetica", "normal");
            doc.text("Registrar", marginX + sigWidth / 2, sigY + sigHeight + 26, { align: "center" });

            // -------------------
            // Footer
            // -------------------
            doc.setFontSize(9);
            doc.setTextColor(150);
            doc.text("OFFICE OF THE REGISTRAR", pageWidth / 2, pageHeight - 20, { align: "center" });

            // Save PDF
            doc.save(`Request_${name}.pdf`);
        }
    }
}

  </script>


</body>
</html>
