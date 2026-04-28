<?php
// File: /sport_teacher/view_results.php
session_start();
include "../component/connect.php";

// 🔐 Ensure sport teacher is logged in
if (!isset($_SESSION['sport_teacher_id'])) {
    header("Location: sport_teacher_login.php");
    exit;
}

$teacher_id = $_SESSION['sport_teacher_id'];

// Fetch teacher's school_id
$stmt = $pdo->prepare("SELECT school_id, full_name FROM sport_teachers WHERE teacher_id = ?");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);
$school_id = $teacher['school_id'] ?? null;

if (!$school_id) {
    die("Error: School information not found. Please contact admin.");
}

// 🎯 Get Grade color class
function getGradeClass($grade) {
    $first = strtolower($grade[0] ?? '');
    return match($first) {
        'a' => 'grade-a',
        'b' => 'grade-b', 
        'c' => 'grade-c',
        'd', 'e' => 'grade-d',
        'f' => 'grade-f',
        default => ''
    };
}

// 🎯 Get Status badge class
function getStatusClass($status) {
    return match($status) {
        'Pending' => 'status-pending',
        'Promoted' => 'status-promoted',
        default => 'status-not-pending'
    };
}

// 🔍 Handle search & filters
$search = trim($_GET['search'] ?? '');
$filter_club = $_GET['club'] ?? '';
$filter_grade = $_GET['grade'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Build query with filters
$query = "
    SELECT 
        s.id as student_id, s.student_name, s.username, s.club, s.talent,
        r.total_score, r.grade, r.grade_point, r.status, r.remarks, r.auto_remarks, r.uploaded_at
    FROM results r
    JOIN students s ON r.student_id = s.id
    WHERE s.school_id = ?
";
$params = [$school_id];

if (!empty($search)) {
    $query .= " AND (s.username LIKE ? OR s.student_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($filter_club)) {
    $query .= " AND s.club = ?";
    $params[] = $filter_club;
}
if (!empty($filter_grade)) {
    $query .= " AND r.grade = ?";
    $params[] = $filter_grade;
}
if (!empty($filter_status)) {
    $query .= " AND r.status = ?";
    $params[] = $filter_status;
}

$query .= " ORDER BY s.student_name ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 📋 Fetch unique clubs for filter dropdown
$clubs_stmt = $pdo->prepare("SELECT DISTINCT club FROM students WHERE school_id = ? ORDER BY club ASC");
$clubs_stmt->execute([$school_id]);
$available_clubs = $clubs_stmt->fetchAll(PDO::FETCH_COLUMN);

// 📊 Stats for summary
$total_results = count($results);
$pending_count = count(array_filter($results, fn($r) => $r['status'] === 'Pending'));
$promoted_count = count(array_filter($results, fn($r) => $r['status'] === 'Promoted'));
$avg_score = $total_results > 0 ? round(array_sum(array_column($results, 'total_score')) / $total_results, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Results</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6f9; padding: 6rem 20px; min-height: 100vh; }
        .page-title { text-align: center; color: #2c3e50; font-size: 2rem; margin: 1.5rem 0 0.5rem; font-weight: 700; }
        .page-subtitle { text-align: center; color: #7f8c8d; margin-bottom: 2rem; font-size: 1rem; }
        
        /* Stats Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; max-width: 1100px; margin: 0 auto 2rem; }
        .stat-card { background: white; padding: 15px; border-radius: 12px; text-align: center; box-shadow: 0 3px 10px rgba(0,0,0,0.08); }
        .stat-card .value { font-size: 1.8rem; font-weight: 700; color: #2c3e50; }
        .stat-card .label { font-size: 0.85rem; color: #7f8c8d; margin-top: 5px; }
        .stat-card.pending .value { color: #ef6c00; }
        .stat-card.promoted .value { color: #27ae60; }
        .stat-card.avg .value { color: #3498db; }
        
        /* Filters & Search */
        .filters-bar { max-width: 1100px; margin: 0 auto 20px; display: flex; gap: 12px; flex-wrap: wrap; align-items: center; justify-content: space-between; }
        .search-box { display: flex; align-items: center; background: white; border: 2px solid #dfe6e9; border-radius: 25px; padding: 8px 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .search-box input { border: none; outline: none; padding: 5px 10px; font-size: 0.95rem; width: 180px; background: transparent; }
        .search-box i { color: #7f8c8d; margin-right: 8px; }
        .filter-select { padding: 10px 15px; border: 2px solid #dfe6e9; border-radius: 8px; font-size: 0.9rem; background: white; min-width: 140px; }
        .filter-select:focus { border-color: #3498db; outline: none; }
        .btn-export { padding: 10px 20px; background: #27ae60; color: white; border: none; border-radius: 8px; font-size: 0.95rem; cursor: pointer; display: flex; align-items: center; gap: 6px; }
        .btn-export:hover { background: #219a52; }
        .btn-reset { padding: 10px 20px; background: #95a5a6; color: white; border: none; border-radius: 8px; font-size: 0.95rem; cursor: pointer; }
        .btn-reset:hover { background: #7f8c8d; }
        .back-btn { display: inline-block; margin: 1rem auto; padding: 10px 22px; background: #3498db; color: white; text-decoration: none; border-radius: 50px; font-weight: 600; }
        .back-btn:hover { background: #2980b9; }
        
        /* Table */
        .table-container { max-width: 1100px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .results-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .results-table th { background: #34495e; color: white; padding: 14px 12px; text-align: left; font-weight: 600; }
        .results-table td { padding: 12px; border-bottom: 1px solid #eee; vertical-align: top; }
        .results-table tr:hover { background: #f8f9fa; }
        .col-name { min-width: 160px; font-weight: 500; }
        .col-username { min-width: 110px; color: #2980b9; font-family: monospace; font-size: 0.85rem; }
        .col-club { min-width: 130px; }
        .col-talent { min-width: 130px; }
        .col-marks { min-width: 70px; text-align: center; font-weight: 600; }
        .col-grade { min-width: 70px; text-align: center; font-weight: 600; }
        .grade-a { color: #27ae60; }
        .grade-b { color: #2980b9; }
        .grade-c { color: #f39c12; }
        .grade-d { color: #e67e22; }
        .grade-f { color: #c0392b; }
        .col-status { min-width: 100px; text-align: center; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; }
        .status-not-pending { background: #ffebee; color: #c62828; }
        .status-pending { background: #fff3e0; color: #ef6c00; }
        .status-promoted { background: #e8f5e9; color: #2e7d32; }
        .col-remarks { min-width: 150px; font-size: 0.85rem; color: #555; }
        .col-auto-remarks { min-width: 200px; font-size: 0.85rem; color: #666; background: #f8f9fa; padding: 8px; border-radius: 6px; }
        .col-date { min-width: 140px; font-size: 0.85rem; color: #7f8c8d; }
        
        .empty-state { text-align: center; padding: 50px 20px; color: #7f8c8d; }
        .empty-state i { font-size: 3rem; margin-bottom: 15px; color: #bdc3c7; }
        
        .results-count { text-align: center; color: #7f8c8d; font-size: 0.9rem; margin: 15px 0; }
        
        @media (max-width: 768px) {
            .filters-bar { flex-direction: column; align-items: stretch; }
            .search-box { width: 100%; }
            .search-box input { width: 100%; }
            .filter-select { width: 100%; }
        }
    </style>
</head>
<body>
<?php include 'sport_teacher_header.php'; ?>

<h1 class="page-title">📊 View Uploaded Results</h1>
<p class="page-subtitle">Review, filter, and export student talent assessment results</p>

<!-- 📈 Stats Summary -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="value"><?= $total_results ?></div>
        <div class="label">Total Results</div>
    </div>
    <div class="stat-card pending">
        <div class="value"><?= $pending_count ?></div>
        <div class="label">Pending Review</div>
    </div>
    <div class="stat-card promoted">
        <div class="value"><?= $promoted_count ?></div>
        <div class="label">Promoted</div>
    </div>
    <div class="stat-card avg">
        <div class="value"><?= $avg_score ?>%</div>
        <div class="label">Average Score</div>
    </div>
</div>

<a href="upload_results.php" class="back-btn">
    <i class=""></i>Upload result
</a>

<!-- 🔍 Filters & Search Bar -->
<div class="filters-bar">
    <div class="search-box">
        <i class="fas fa-search"></i>
        <form method="GET" style="display:flex; align-items:center; gap:8px; width:100%;">
            <input type="text" name="search" placeholder="Search username or name..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" style="background:none; border:none; color:#3498db; cursor:pointer;"><i class="fas fa-search"></i></button>
        </form>
    </div>
    
    <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
        <!-- Preserve search in filters -->
        <?php if (!empty($search)): ?>
            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
        <?php endif; ?>
        
        <select name="club" class="filter-select" onchange="this.form.submit()">
            <option value="">All Clubs</option>
            <?php foreach($available_clubs as $club): ?>
                <option value="<?= htmlspecialchars($club) ?>" <?= $filter_club === $club ? 'selected' : '' ?>>
                    <?= htmlspecialchars($club) ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <select name="grade" class="filter-select" onchange="this.form.submit()">
            <option value="">All Grades</option>
            <?php foreach(['A+','A','B+','B','C','D','E','F'] as $g): ?>
                <option value="<?= $g ?>" <?= $filter_grade === $g ? 'selected' : '' ?>><?= $g ?></option>
            <?php endforeach; ?>
        </select>
        
        <select name="status" class="filter-select" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="Promoted" <?= $filter_status === 'Promoted' ? 'selected' : '' ?>>Promoted</option>
            <option value="Not Pending" <?= $filter_status === 'Not Pending' ? 'selected' : '' ?>>Not Pending</option>
        </select>
        
        <?php if ($search || $filter_club || $filter_grade || $filter_status): ?>
            <a href="view_results.php" class="btn-reset">
                <i class="fas fa-times"></i> Reset
            </a>
        <?php endif; ?>
    </form>
    
    <button class="btn-export" onclick="exportToCSV()">
        <i class="fas fa-file-csv"></i> Export CSV
    </button>
</div>

<!-- 📊 Results Table -->
<div class="table-container">
    <table class="results-table">
        <thead>
            <tr>
                <th class="col-name">Student</th>
                <th class="col-username">Username</th>
                <th class="col-club">Club</th>
                <th class="col-talent">Talent</th>
                <th class="col-marks">Marks</th>
                <th class="col-grade">Grade</th>
                <th class="col-status">Status</th>
                <!--<th class="col-remarks">Remarks</th>-->
                <th class="col-auto-remarks">Auto Remarks</th>
                <th class="col-date">Uploaded</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($results)): ?>
                <?php foreach ($results as $row): ?>
                    <tr>
                        <td class="col-name"><?= htmlspecialchars($row['student_name']) ?></td>
                        <td class="col-username"><?= htmlspecialchars($row['username']) ?></td>
                        <td class="col-club"><?= htmlspecialchars($row['club']) ?></td>
                        <td class="col-talent"><?= htmlspecialchars($row['talent']) ?></td>
                        <td class="col-marks"><?= $row['total_score'] ?></td>
                        <td class="col-grade">
                            <span class="<?= getGradeClass($row['grade']) ?>"><strong><?= $row['grade'] ?></strong></span>
                        </td>
                        <td class="col-status">
                            <span class="status-badge <?= getStatusClass($row['status']) ?>">
                                <?= $row['status'] ?>
                            </span>
                        </td>
                        <!--<td class="col-remarks"><?= htmlspecialchars($row['remarks'] ?? '-') ?></td>-->
                        <td class="col-auto-remarks"><?= htmlspecialchars($row['auto_remarks'] ?? '-') ?></td>
                        <td class="col-date"><?= date('M d, Y', strtotime($row['uploaded_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" class="empty-state">
                        <i class="fas fa-clipboard-list"></i><br><br>
                        <strong>No results found</strong><br>
                        <?= $search || $filter_club || $filter_grade || $filter_status 
                            ? 'Try adjusting your filters or search terms.' 
                            : 'Upload results first to view them here.' ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($total_results > 0): ?>
    <p class="results-count">
        Showing <?= $total_results ?> result<?= $total_results !== 1 ? 's' : '' ?> 
        <?= $search || $filter_club || $filter_grade || $filter_status ? '(filtered)' : '' ?>
    </p>
<?php endif; ?>

<script>
// 📤 Export to CSV Function
function exportToCSV() {
    const rows = document.querySelectorAll('.results-table tr');
    let csv = [];
    
    // Headers
    const headers = [];
    rows[0].querySelectorAll('th').forEach(th => {
        headers.push('"' + th.textContent.replace(/"/g, '""') + '"');
    });
    csv.push(headers.join(','));
    
    // Data rows (skip if empty state)
    if (rows.length > 1 && !rows[1].querySelector('.empty-state')) {
        for (let i = 1; i < rows.length; i++) {
            const cols = rows[i].querySelectorAll('td');
            const row = [];
            cols.forEach(td => {
                // Get text content, handle badges/spans
                let text = td.textContent.trim();
                // Skip status badge styling, keep text only
                row.push('"' + text.replace(/"/g, '""') + '"');
            });
            csv.push(row.join(','));
        }
    }
    
    // Download
    const csvString = csv.join('\n');
    const blob = new Blob(['\ufeff' + csvString], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'talent_results_<?= date("Y-m-d") ?>.csv';
    link.click();
}

// 🔍 Auto-submit search on Enter key
document.querySelector('.search-box input')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        this.closest('form').submit();
    }
});
</script>

<script>
    // Sidebar toggle (keep existing)
    let menuBtn = document.getElementById("menu-btn");
    let sidebar = document.getElementById("sidebar");
    if (menuBtn && sidebar) {
        menuBtn.addEventListener("click", function() {
            sidebar.classList.toggle("active");
        });
    }
</script>

</body>
</html>