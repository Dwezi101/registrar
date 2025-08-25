<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    die("Access Denied: Super Admins only.");
}

$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_registrar";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Rows per page selector
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Filters
$search = trim($_GET['search'] ?? '');
$start_date = trim($_GET['start_date'] ?? '');
$end_date = trim($_GET['end_date'] ?? '');

// Build WHERE clause
$where = "1=1";
$params = [];
$types = "";

if ($search !== '') {
    $where .= " AND (u.email LIKE ? OR a.action LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

if ($start_date !== '') {
    $where .= " AND a.timestamp >= ?";
    $params[] = str_replace('T', ' ', $start_date);
    $types .= "s";
}

if ($end_date !== '') {
    $where .= " AND a.timestamp <= ?";
    $params[] = str_replace('T', ' ', $end_date);
    $types .= "s";
}

// Get total count
$sqlCount = "SELECT COUNT(*) AS total 
             FROM audit_trail a
             JOIN users u ON a.user_id = u.id
             WHERE $where";
$stmtCount = $conn->prepare($sqlCount);
if (!empty($params)) $stmtCount->bind_param($types, ...$params);
$stmtCount->execute();
$totalResult = $stmtCount->get_result()->fetch_assoc();
$total = $totalResult['total'];
$totalPages = ceil($total / $limit);

// Fetch audit trail
$sql = "SELECT a.id, u.email, a.action, a.timestamp, a.ip_address
        FROM audit_trail a
        JOIN users u ON a.user_id = u.id
        WHERE $where
        ORDER BY a.timestamp DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);

$paramsWithLimit = $params;
$typesWithLimit = $types . "ii";
$paramsWithLimit[] = $limit;
$paramsWithLimit[] = $offset;

$stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Audit Trail - Super Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4">📝 Audit Trail</h2>

    <div class="mb-3">
        <a href="super_admin_dashboard.php" class="btn btn-secondary shadow-sm">
            <i class="bi bi-arrow-left-circle me-1"></i> Back to Dashboard
        </a>
    </div>

    <!-- Filters -->
<div class="card p-4 mb-4 shadow-sm border-0 bg-white">
    <h5 class="mb-3 text-primary">Filter Audit Trail</h5>
    <p class="text-muted mb-3">Search by email or action, filter by start and end date/time, and choose how many rows to display per page.</p>
    <form method="GET" class="row g-3 align-items-end">
        <!-- Search -->
        <div class="col-md-3">
            <label class="form-label">Search</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Email or Action" value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>

        <!-- Start Date -->
        <div class="col-md-3">
            <label class="form-label">Start Date & Time</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar-event"></i></span>
                <input type="datetime-local" name="start_date" class="form-control border-start-0" value="<?= htmlspecialchars($start_date) ?>">
            </div>
        </div>

        <!-- End Date -->
        <div class="col-md-3">
            <label class="form-label">End Date & Time</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar-event"></i></span>
                <input type="datetime-local" name="end_date" class="form-control border-start-0" value="<?= htmlspecialchars($end_date) ?>">
            </div>
        </div>

        <!-- Rows per page -->
        <div class="col-md-2">
            <label class="form-label">Rows per page</label>
            <select name="limit" class="form-select" onchange="this.form.submit()">
                <option value="10" <?= $limit==10?'selected':'' ?>>10</option>
                <option value="20" <?= $limit==20?'selected':'' ?>>20</option>
                <option value="30" <?= $limit==30?'selected':'' ?>>30</option>
                <option value="50" <?= $limit==50?'selected':'' ?>>50</option>
            </select>
        </div>

        <!-- Filter Button -->
        <div class="col-md-1 d-grid">
            <button class="btn btn-primary" type="submit"><i class="bi bi-funnel-fill me-1"></i> Filter</button>
        </div>
    </form>
</div>

    <!-- Audit Table -->
    <div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>User Email</th>
                <th>Action</th>
                <th>Time</th>
                <th>IP Address</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['action']) ?></td>
                <td><?= $row['timestamp'] ?></td>
                <td><?= $row['ip_address'] ?></td>
            </tr>
            <?php endwhile; ?>
            <?php if($result->num_rows == 0): ?>
            <tr><td colspan="5" class="text-center">No records found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>

    <!-- Pagination -->
    <nav>
      <ul class="pagination justify-content-center">
        <?php if($page > 1): ?>
          <li class="page-item">
            <a class="page-link" href="?page=<?= $page-1 ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>">Previous</a>
          </li>
        <?php endif; ?>

        <?php for($p=1; $p<=$totalPages; $p++): ?>
          <li class="page-item <?= $p==$page?'active':'' ?>">
            <a class="page-link" href="?page=<?= $p ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>"><?= $p ?></a>
          </li>
        <?php endfor; ?>

        <?php if($page < $totalPages): ?>
          <li class="page-item">
            <a class="page-link" href="?page=<?= $page+1 ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>">Next</a>
          </li>
        <?php endif; ?>
      </ul>
    </nav>

</div>
</body>
</html>
