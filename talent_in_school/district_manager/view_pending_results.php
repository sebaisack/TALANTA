<!--<?php
session_start();
include "../component/connect.php";

// 🔐 Ensure district manager is logged in
if (!isset($_SESSION['district_manager_logged_in']) || $_SESSION['district_manager_logged_in'] !== true) {
    header("Location: district_manager_login.php");
    exit;
}

$search     = trim($_GET['search'] ?? '');
$filter_club = trim($_GET['club'] ?? '');
$filter_grade = trim($_GET['grade'] ?? '');

$query = "
    SELECT r.result_id, s.student_name, s.username, s.club, r.talent_name, 
           r.total_score, r.grade, r.uploaded_at
    FROM results r
    JOIN students s ON r.student_id = s.id
    WHERE r.status = 'Pending'
";
$params = [];

if (!empty($search)) {
    $query .= " AND (s.student_name LIKE ? OR s.username LIKE ? OR r.talent_name LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if (!empty($filter_club)) {
    $query .= " AND s.club = ?";
    $params[] = $filter_club;
}
if (!empty($filter_grade)) {
    $query .= " AND r.grade = ?";
    $params[] = $filter_grade;
}

$query .= " ORDER BY r.uploaded_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$pending_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch unique clubs for filter dropdown
$clubs_stmt = $pdo->prepare("SELECT DISTINCT club FROM students ORDER BY club ASC");
$clubs_stmt->execute();
$available_clubs = $clubs_stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Pending Results - District Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6f9; margin: 0; padding-top: 65px; }
        .container { max-width: 1200px; margin: 0 auto; padding: 25px 20px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .page-title { font-size: 1.5rem; color: #2c3e50; font-weight: 600; display: flex; align-items: center; gap: 10px; margin: 0; }
        .filters-bar { background: white; padding: 15px 20px; border-radius: 12px; box-shadow: 0 3px 10px rgba(0,0,0,0.06); margin-bottom: 20px; display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
        .filters-bar input, .filters-bar select { padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; background: #f8fafc; }
        .filters-bar input:focus, .filters-bar select:focus { border-color: #3b82f6; outline: none; }
        .btn-apply { background: #3b82f6; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 500; }
        .btn-apply:hover { background: #2563eb; }
        .btn-reset { background: #94a3b8; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; text-decoration: none; font-weight: 500; }
        .btn-reset:hover { background: #64748b; }
        .table-container { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #34495e; color: white; padding: 14px 12px; text-align: left; font-weight: 600; font-size: 0.9rem; }
        td { padding: 12px; border-bottom: 1px solid #eee; font-size: 0.93rem; vertical-align: middle; }
        tr:hover { background: #f8fafc; }
        .grade-badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 0.85rem; }
        .grade-a { background: #e8f5e9; color: #27ae60; }
        .grade-b { background: #e3f2fd; color: #2980b9; }
        .grade-c { background: #fff3e0; color: #f39c12; }
        .grade-d, .grade-e, .grade-f { background: #ffebee; color: #c0392b; }
        .status-pending { background: #fff3e0; color: #d97706; font-weight: 600; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; }
        .btn-promote { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: #10b981; color: white; text-decoration: none; border-radius: 6px; font-size: 0.9rem; font-weight: 500; }
        .btn-promote:hover { background: #059669; }
        .empty-state { text-align: center; padding: 40px 20px; color: #7f8c8d; }
        .empty-state i { font-size: 2.5rem; margin-bottom: 10px; color: #cbd5e1; }
        @media (max-width: 768px) {
            .filters-bar { flex-direction: column; }
            .filters-bar input, .filters-bar select, .filters-bar button, .filters-bar a { width: 100%; text-align: center; }
            table { min-width: 700px; }
            .table-container { overflow-x: auto; }
        }
    </style>
</head>
<body>

<?php include 'district_manager_header.php'; ?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-clipboard-list"></i> Pending Talent Results</h1>
        <a href="promote_students.php" class="btn-promote"><i class="fas fa-user-check"></i> Go to Promote Page</a>
    </div>

    <!-- 🔍 Filters -->
    <form method="GET" class="filters-bar">
        <input type="text" name="search" placeholder="Search student, username, or talent..." value="<?= htmlspecialchars($search) ?>">
        <select name="club">
            <option value="">All Clubs</option>
            <?php foreach ($available_clubs as $club): ?>
                <option value="<?= htmlspecialchars($club) ?>" <?= $filter_club === $club ? 'selected' : '' ?>><?= htmlspecialchars($club) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="grade">
            <option value="">All Grades</option>
            <?php foreach (['A+','A','B+','B','C','D','E','F'] as $g): ?>
                <option value="<?= $g ?>" <?= $filter_grade === $g ? 'selected' : '' ?>><?= $g ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-apply"><i class="fas fa-filter"></i> Apply</button>
        <?php if ($search || $filter_club || $filter_grade): ?>
            <a href="view_pending_results.php" class="btn-reset">Reset</a>
        <?php endif; ?>
    </form>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Username</th>
                    <th>Club</th>
                    <th>Talent</th>
                    <th>Score</th>
                    <th>Grade</th>
                    <th>Status</th>
                    <th>Submitted</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($pending_results)): ?>
                    <?php foreach ($pending_results as $row): 
                        $gradeClass = match(strtolower($row['grade'][0] ?? '')) {
                            'a' => 'grade-a', 'b' => 'grade-b', 'c' => 'grade-c', default => 'grade-d'
                        };
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['student_name']) ?></strong></td>
                        <td style="font-family:monospace;color:#2980b9"><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['club']) ?></td>
                        <td><?= htmlspecialchars($row['talent_name']) ?></td>
                        <td><strong><?= $row['total_score'] ?>%</strong></td>
                        <td><span class="grade-badge <?= $gradeClass ?>"><?= $row['grade'] ?></span></td>
                        <td><span class="status-pending">Pending</span></td>
                        <td><?= date('M d, Y', strtotime($row['uploaded_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            <i class="fas fa-inbox"></i><br>
                            <strong>No pending results found</strong><br>
                            All assessments have been reviewed or no scores meet the 50% threshold.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>-->