<?php
session_start();
include "../component/connect.php";

// 🔐 Ensure district manager is logged in
if (!isset($_SESSION['district_manager_logged_in']) || $_SESSION['district_manager_logged_in'] !== true) {
    header("Location: district_manager_login.php");
    exit;
}

$message = '';
$search  = trim($_GET['search'] ?? '');

// 📥 Handle Promotion Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['promote_students'])) {
    $selected_ids = $_POST['promote_ids'] ?? [];
    
    if (empty($selected_ids)) {
        $message = "❌ Please select at least one student to promote.";
    } else {
        try {
            // Use prepared statement with dynamic placeholders
            $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
            $sql = "UPDATE results SET status = 'Promoted' WHERE result_id IN ($placeholders) AND status = 'Pending'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($selected_ids);
            
            $promoted_count = $stmt->rowCount();
            $message = "✅ <strong>$promoted_count student(s)</strong> successfully promoted to 'Promoted' status!";
            
            // Clear search/promote IDs to refresh list
            $selected_ids = [];
        } catch (PDOException $e) {
            error_log("Promotion error: " . $e->getMessage());
            $message = "❌ System error while promoting students. Please try again.";
        }
    }
}

// 📋 Fetch ALL Results (not just Pending) - Students remain visible after promotion
$query = "
    SELECT r.result_id, s.student_name, s.username, s.club, r.talent_name, r.total_score, r.grade, r.status, r.uploaded_at
    FROM results r
    JOIN students s ON r.student_id = s.id
    WHERE 1=1
";
$params = [];
if (!empty($search)) {
    $query .= " AND (s.student_name LIKE ? OR s.username LIKE ? OR r.talent_name LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}
$query .= " ORDER BY r.uploaded_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$all_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Student Assessments - District Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6f9; margin: 0; padding-top: 65px; }
        .container { max-width: 1200px; margin: 0 auto; padding: 25px 20px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .page-title { font-size: 1.5rem; color: #2c3e50; font-weight: 600; display: flex; align-items: center; gap: 10px; margin: 0; }
        .message { padding: 15px 20px; margin-bottom: 20px; border-radius: 10px; font-size: 0.95rem; text-align: center; }
        .msg-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .msg-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        
        .search-box { background: white; padding: 15px; border-radius: 12px; box-shadow: 0 3px 10px rgba(0,0,0,0.06); margin-bottom: 20px; display: flex; gap: 10px; }
        .search-box input { flex: 1; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; }
        .search-box button { background: #3b82f6; color: white; border: none; padding: 0 20px; border-radius: 8px; cursor: pointer; }
        .search-box button:hover { background: #2563eb; }
        
        .table-container { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #34495e; color: white; padding: 14px 12px; text-align: left; font-weight: 600; font-size: 0.9rem; }
        td { padding: 12px; border-bottom: 1px solid #eee; font-size: 0.93rem; vertical-align: middle; }
        tr:hover { background: #f8fafc; }
        input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
        .grade-badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 0.85rem; }
        .grade-a { background: #e8f5e9; color: #27ae60; }
        .grade-b { background: #e3f2fd; color: #2980b9; }
        .grade-c { background: #fff3e0; color: #f39c12; }
        .grade-d, .grade-e, .grade-f { background: #ffebee; color: #c0392b; }
        
        /* ✅ Status Badges */
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; }
        .status-pending { background: #fff3e0; color: #d97706; }
        .status-not-pending { background: #ffebee; color: #dc2626; }
        .status-promoted { background: #d1fae5; color: #059669; }
        
        /* ✅ Edit Button */
        .btn-edit { 
            display: inline-flex; align-items: center; gap: 4px; 
            padding: 6px 12px; background: #3b82f6; color: white; 
            text-decoration: none; border-radius: 6px; font-size: 0.85rem; font-weight: 500;
        }
        .btn-edit:hover { background: #2563eb; }
        
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; flex-wrap: wrap; gap: 15px; }
        .selection-info { font-weight: 500; color: #475569; }
        .btn-submit { background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-size: 1rem; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
        .btn-back { background: #94a3b8; color: white; text-decoration: none; padding: 12px 25px; border-radius: 8px; font-weight: 500; }
        .btn-back:hover { background: #64748b; }
        
        .empty-state { text-align: center; padding: 40px 20px; color: #7f8c8d; }
        .empty-state i { font-size: 2.5rem; margin-bottom: 10px; color: #cbd5e1; }
        @media (max-width: 768px) {
            .search-box { flex-direction: column; }
            .action-bar { flex-direction: column; align-items: stretch; text-align: center; }
            table { min-width: 800px; }
            .table-container { overflow-x: auto; }
        }
    </style>
</head>
<body>

<?php include 'district_manager_header.php'; ?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-users-cog"></i> Manage Student Assessments</h1>
    </div>

    <?php if ($message): ?>
        <div class="message <?= strpos($message, '✅') !== false ? 'msg-success' : 'msg-error' ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <form method="GET" class="search-box">
        <input type="text" name="search" placeholder="Search by name, username, talent, or status..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit"><i class="fas fa-search"></i></button>
    </form>

    <form method="POST" id="promoteForm">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width:50px; text-align:center;"><input type="checkbox" id="selectAll"></th>
                        <th>Student Name</th>
                        <th>Username</th>
                        <th>Club</th>
                        <th>Talent</th>
                        <th>Score</th>
                        <th>Grade</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th style="width:90px;">Edit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($all_results)): ?>
                        <?php foreach ($all_results as $row): 
                            $gradeClass = match(strtolower($row['grade'][0] ?? '')) {
                                'a' => 'grade-a', 'b' => 'grade-b', 'c' => 'grade-c', default => 'grade-d'
                            };
                            // ✅ Status badge class
                            $statusClass = match($row['status']) {
                                'Pending' => 'status-pending',
                                'Promoted' => 'status-promoted',
                                default => 'status-not-pending'
                            };
                        ?>
                        <tr>
                            <td style="text-align:center;">
                                <!-- Only show checkbox for Pending items (can only promote pending) -->
                                <?php if ($row['status'] === 'Pending'): ?>
                                    <input type="checkbox" name="promote_ids[]" value="<?= $row['result_id'] ?>">
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($row['student_name']) ?></strong></td>
                            <td style="font-family:monospace;color:#2980b9"><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= htmlspecialchars($row['club']) ?></td>
                            <td><?= htmlspecialchars($row['talent_name']) ?></td>
                            <td><strong><?= $row['total_score'] ?>%</strong></td>
                            <td><span class="grade-badge <?= $gradeClass ?>"><?= $row['grade'] ?></span></td>
                            <!-- ✅ Status Column -->
                            <td><span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                            <td><?= date('M d, Y', strtotime($row['uploaded_at'])) ?></td>
                            <!-- ✅ Edit Column (Final Column) -->
                            <td style="text-align:center;">
                                <a href="edit_result.php?id=<?= $row['result_id'] ?>" class="btn-edit" title="Edit this assessment">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="empty-state">
                                <i class="fas fa-inbox"></i><br>
                                <strong>No assessments found</strong><br>
                                Talent results will appear here once uploaded by sport teachers.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="action-bar">
            <div class="selection-info">
                <span id="selectedCount">0</span> pending student(s) selected for promotion
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a href="district_manager_dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                <button type="submit" name="promote_students" class="btn-submit" id="submitBtn" disabled>
                    <i class="fas fa-check-circle"></i> Promote Selected
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    // Toggle all checkboxes (only for Pending items)
    document.getElementById('selectAll')?.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="promote_ids[]"]');
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateSelectionCount();
    });

    // Update count on individual check
    document.querySelectorAll('input[name="promote_ids[]"]').forEach(cb => {
        cb.addEventListener('change', updateSelectionCount);
    });

    function updateSelectionCount() {
        const checked = document.querySelectorAll('input[name="promote_ids[]"]:checked').length;
        document.getElementById('selectedCount').textContent = checked;
        document.getElementById('submitBtn').disabled = checked === 0;
    }

    // Prevent accidental submission if none selected
    document.getElementById('promoteForm')?.addEventListener('submit', function(e) {
        const checked = document.querySelectorAll('input[name="promote_ids[]"]:checked').length;
        if (checked === 0) {
            e.preventDefault();
            alert('Please select at least one pending student to promote.');
        }
    });
</script>

</body>
</html>