<!--<?php
session_start();
include "../component/connect.php";

if (!isset($_SESSION['district_manager_logged_in']) || $_SESSION['district_manager_logged_in'] !== true) {
    header("Location: district_manager_login.php"); exit;
}

$search     = trim($_GET['search'] ?? '');
$filter_club = trim($_GET['club'] ?? '');

$query = "SELECT r.result_id, s.student_name, s.username, s.club, r.talent_name, r.total_score, r.grade, r.uploaded_at
          FROM results r JOIN students s ON r.student_id = s.id
          WHERE r.status = 'Promoted'";
$params = [];

if ($search) { $query .= " AND (s.student_name LIKE ? OR s.username LIKE ? OR r.talent_name LIKE ?)"; $params = ["%$search%","%$search%","%$search%"]; }
if ($filter_club) { $query .= " AND s.club = ?"; $params[] = $filter_club; }
$query .= " ORDER BY r.uploaded_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$promoted = $stmt->fetchAll(PDO::FETCH_ASSOC);

$clubs = $pdo->query("SELECT DISTINCT club FROM students ORDER BY club ASC")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promoted Results - District Manager</title>
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
        .table-container { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #10b981; color: white; padding: 14px 12px; text-align: left; font-weight: 600; font-size: 0.9rem; }
        td { padding: 12px; border-bottom: 1px solid #eee; font-size: 0.93rem; vertical-align: middle; }
        tr:hover { background: #f8fafc; }
        .grade-badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 0.85rem; }
        .grade-a { background: #d1fae5; color: #065f46; } .grade-b { background: #dbeafe; color: #1e40af; }
        .grade-c { background: #fef3c7; color: #92400e; } .grade-d { background: #fee2e2; color: #991b1b; }
        .status-promoted { background: #d1fae5; color: #065f46; font-weight: 600; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; }
        .btn-export { background: #059669; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .empty-state { text-align: center; padding: 40px 20px; color: #7f8c8d; }
        @media (max-width: 768px) {
            .filters-bar { flex-direction: column; } .filters-bar > * { width: 100%; }
            table { min-width: 700px; } .table-container { overflow-x: auto; }
        }
    </style>
</head>
<body>
<?php include 'district_manager_header.php'; ?>
<div class="container">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-trophy"></i> Promoted Students</h1>
        <button class="btn-export" onclick="exportCSV()"><i class="fas fa-file-csv"></i> Export CSV</button>
    </div>
    <form method="GET" class="filters-bar">
        <input type="text" name="search" placeholder="Search student, username, or talent..." value="<?= htmlspecialchars($search) ?>">
        <select name="club"><option value="">All Clubs</option>
            <?php foreach($clubs as $c): ?><option value="<?= htmlspecialchars($c) ?>" <?= $filter_club==$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="btn-apply"><i class="fas fa-filter"></i> Apply</button>
        <?php if($search||$filter_club): ?><a href="view_promoted_results.php" class="btn-export" style="background:#64748b">Reset</a><?php endif; ?>
    </form>
    <div class="table-container" id="resultsTable">
        <table>
            <thead><tr><th>Student</th><th>Username</th><th>Club</th><th>Talent</th><th>Score</th><th>Grade</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php if($promoted): foreach($promoted as $r):
                $gc = match(strtolower($r['grade'][0])){'a'=>'grade-a','b'=>'grade-b','c'=>'grade-c',default=>'grade-d'};
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($r['student_name']) ?></strong></td>
                <td style="font-family:monospace;color:#2563eb"><?= htmlspecialchars($r['username']) ?></td>
                <td><?= htmlspecialchars($r['club']) ?></td><td><?= htmlspecialchars($r['talent_name']) ?></td>
                <td><strong><?= $r['total_score'] ?>%</strong></td><td><span class="grade-badge <?= $gc ?>"><?= $r['grade'] ?></span></td>
                <td><span class="status-promoted">Promoted</span></td><td><?= date('M d, Y', strtotime($r['uploaded_at'])) ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="8" class="empty-state"><i class="fas fa-check-circle fa-2x"></i><br><strong>No promoted students found</strong></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
function exportCSV(){
    let rows=document.querySelectorAll('#resultsTable table tr'),csv=[];
    rows.forEach(r=>{let cells=[];r.querySelectorAll('th,td').forEach(c=>cells.push('"'+c.textContent.trim().replace(/"/g,'""')+'"'));csv.push(cells.join(','));});
    let blob=new Blob(['\ufeff'+csv.join('\n')],{type:'text/csv;charset=utf-8;'}),a=document.createElement('a');
    a.href=URL.createObjectURL(blob);a.download='promoted_students_<?=date("Y-m-d")?>.csv';a.click();
}
</script>
</body></html>-->