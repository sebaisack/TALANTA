<?php
session_start();
include "../component/connect.php";

// 🔐 Ensure district manager is logged in
if (!isset($_SESSION['district_manager_logged_in']) || $_SESSION['district_manager_logged_in'] !== true) {
    header("Location: district_manager_login.php");
    exit;
}

$dm_region   = $_SESSION['district_manager_region'] ?? '';
$dm_district = $_SESSION['district_manager_district'] ?? '';
$dm_name     = $_SESSION['district_manager_full_name'] ?? 'District Manager';

/* ===============================
   📊 FETCH DASHBOARD STATISTICS
   =============================== */
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM results r JOIN students s ON r.student_id = s.id JOIN schools sch ON s.school_id = sch.school_id WHERE r.status = 'Pending' AND sch.region = ? AND sch.district = ?");
    $stmt->execute([$dm_region, $dm_district]); $pending_count = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM results r JOIN students s ON r.student_id = s.id JOIN schools sch ON s.school_id = sch.school_id WHERE r.status = 'Promoted' AND sch.region = ? AND sch.district = ?");
    $stmt->execute([$dm_region, $dm_district]); $promoted_count = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT st.teacher_id) FROM sport_teachers st JOIN schools sch ON st.school_id = sch.school_id WHERE sch.region = ? AND sch.district = ?");
    $stmt->execute([$dm_region, $dm_district]); $teachers_count = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM clubs c JOIN schools sch ON c.school_id = sch.school_id WHERE sch.region = ? AND sch.district = ?");
    $stmt->execute([$dm_region, $dm_district]); $clubs_count = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Stats error: " . $e->getMessage());
    $pending_count = $promoted_count = $teachers_count = $clubs_count = 0;
}

/* ===============================
   🏫 SCHOOLS LIST (Search & Pagination)
   =============================== */
$search_school = trim($_GET['search_school'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

$base_where = "WHERE s.region = ? AND s.district = ?";
$params = [$dm_region, $dm_district];

if (!empty($search_school)) {
    $base_where .= " AND (s.school_name LIKE ? OR s.address LIKE ? OR s.ward LIKE ?)";
    $sp = "%$search_school%";
    $params[] = $sp; $params[] = $sp; $params[] = $sp;
}

// Safe COUNT query
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM schools s $base_where");
$count_stmt->execute($params);
$total_schools = $count_stmt->fetchColumn();
$total_pages = ceil($total_schools / $per_page);

// Safe SELECT query with LIMIT/OFFSET
$per_page = (int)$per_page;
$offset   = (int)$offset;
$main_query = "SELECT s.school_id, s.school_name, s.address, s.ward, s.phone,
               (SELECT COUNT(*) FROM results r JOIN students st ON r.student_id = st.id 
                WHERE st.school_id = s.school_id AND r.status = 'Pending') as pending_count
               FROM schools s
               $base_where
               ORDER BY s.school_name ASC
               LIMIT $per_page OFFSET $offset";

$stmt = $pdo->prepare($main_query);
$stmt->execute($params);
$schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - District Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        :root { --primary:#3b82f6; --primary-dark:#1e40af; --success:#27ae60; --danger:#c0392b; --dark:#2c3e50; --light:#f8fafc; --border:#e2e8f0; --text:#334155; --text-light:#64748b; --shadow:0 2px 8px rgba(0,0,0,0.06); --radius:8px; }
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f6f9;margin:0;padding-top:65px;color:var(--text);line-height:1.5;}
        .container{max-width:1200px;margin:0 auto;padding:20px;}
        
        .welcome-banner{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:white;padding:18px 22px;border-radius:var(--radius);margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;}
        .welcome-banner h1{font-size:1.4rem;font-weight:700;}.welcome-banner p{opacity:0.95;font-size:0.9rem;margin:0;}
        
        .stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:22px;}
        .stat-item{background:white;padding:14px 18px;border-radius:var(--radius);text-align:center;box-shadow:var(--shadow);border-top:3px solid var(--primary);}
        .stat-value{font-size:1.6rem;font-weight:700;color:var(--dark);margin-bottom:3px;}.stat-label{color:var(--text-light);font-size:0.82rem;text-transform:uppercase;letter-spacing:0.5px;}
        
        .section-header{display:flex;justify-content:space-between;align-items:center;margin:22px 0 12px;flex-wrap:wrap;gap:12px;}
        .section-title{font-size:1.15rem;color:var(--dark);font-weight:600;display:flex;align-items:center;gap:8px;}.section-title i{color:var(--primary);}
        
        .search-box{display:flex;align-items:center;background:white;border:2px solid var(--border);border-radius:25px;padding:8px 16px;min-width:250px;}
        .search-box input{border:none;outline:none;padding:6px 8px;font-size:0.92rem;width:100%;background:transparent;color:var(--text);}
        .search-box input::placeholder{color:var(--text-light);}
        
        .table-wrapper{background:white;border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;margin-bottom:20px;}
        table{width:100%;border-collapse:collapse;}
        th{background:var(--dark);color:white;padding:12px 14px;text-align:left;font-weight:600;font-size:0.88rem;text-transform:uppercase;letter-spacing:0.3px;}
        td{padding:11px 14px;border-bottom:1px solid var(--border);font-size:0.91rem;vertical-align:middle;}
        tr:last-child td{border-bottom:none;}tr:hover{background:var(--light);}
        
        .btn-view{padding:5px 12px;background:var(--primary);color:white;border:none;border-radius:5px;font-size:0.83rem;cursor:pointer;transition:background 0.2s;text-decoration:none;display:inline-block;}
        .btn-view:hover{background:var(--primary-dark);}
        
        .badge-pending{display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;padding:2px 6px;background:#fee2e2;color:#b91c1c;border-radius:9999px;font-size:0.75rem;font-weight:700;margin-left:5px;}
        
        .pagination{display:flex;justify-content:center;align-items:center;gap:6px;margin:18px 0;flex-wrap:wrap;}
        .page-link{padding:7px 12px;border:1px solid var(--border);border-radius:5px;text-decoration:none;color:var(--text);font-size:0.9rem;transition:all 0.2s;min-width:36px;text-align:center;}
        .page-link:hover{background:var(--light);border-color:var(--primary);color:var(--primary);}
        .page-link.disabled{color:var(--text-light);cursor:not-allowed;opacity:0.6;}
        .page-info{color:var(--text-light);font-size:0.85rem;margin:0 10px;}
        
        .empty-state{text-align:center;padding:35px 20px;color:var(--text-light);}.empty-state i{font-size:2.2rem;margin-bottom:12px;color:#cbd5e1;}
        
        @media(max-width:768px){.welcome-banner{flex-direction:column;text-align:center;}.section-header{flex-direction:column;align-items:flex-start;}.search-box{min-width:100%;width:100%;}table{font-size:0.85rem;min-width:650px;display:block;overflow-x:auto;}}
    </style>
</head>
<body>
<?php include 'district_manager_header.php'; ?>
<div class="container">
    <div class="welcome-banner">
        <div><h1>👋 Welcome, <?= htmlspecialchars(explode(' ', $dm_name)[0]) ?></h1><p><?= htmlspecialchars($dm_region) ?> / <?= htmlspecialchars($dm_district) ?></p></div>
        <div style="text-align:right;font-size:0.85rem;opacity:0.9;"><div><i class="fas fa-user"></i> <?= htmlspecialchars($dm_name) ?></div></div>
    </div>

    <!-- ✅ YOUR EXACT STATS ROW -->
    <div class="stats-row">
        <div class="stat-item">
            <div class="stat-value" style="color:#166534;"><?= number_format($promoted_count) ?></div>
            <div class="stat-label">Promoted</div>
        </div>
        <div class="stat-item">
            <div class="stat-value" style="color:#92400e;"><?= number_format($pending_count) ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-item">
            <div class="stat-value" style="color:#1e40af;"><?= number_format($teachers_count) ?></div>
            <div class="stat-label">Teachers</div>
        </div>
        <div class="stat-item">
            <div class="stat-value" style="color:#7e22ce;"><?= number_format($clubs_count) ?></div>
            <div class="stat-label">Clubs</div>
        </div>
    </div>

    <div class="section-header">
        <h3 class="section-title"><i class="fas fa-school"></i> Schools in <?= htmlspecialchars($dm_district) ?></h3>
        <form method="GET" style="display:flex;gap:8px;">
            <div class="search-box"><i class="fas fa-search" style="margin-right:8px;color:var(--text-light);"></i><input type="text" name="search_school" placeholder="Search schools..." value="<?= htmlspecialchars($search_school) ?>"></div>
            <button type="submit" class="btn-view" style="padding:8px 14px;">Search</button>
        </form>
    </div>

    <?php if(!empty($schools) || !empty($search_school)): ?>
        <div class="table-wrapper">
            <table>
                <thead><tr><th style="width:50px;text-align:center;">#</th><th>School Name</th><th>Address</th><th>Ward</th><th>Phone</th><th style="width:100px;text-align:center;">Pending</th><th style="width:85px;text-align:center;">Action</th></tr></thead>
                <tbody>
                    <?php if(!empty($schools)): $counter = $offset + 1; foreach($schools as $school): ?>
                        <tr>
                            <td style="text-align:center;font-weight:600;"><?= $counter++ ?></td>
                            <td><strong><?= htmlspecialchars($school['school_name']) ?></strong></td>
                            <td><?= htmlspecialchars($school['address']) ?></td>
                            <td><?= htmlspecialchars($school['ward']) ?></td>
                            <td><?= htmlspecialchars($school['phone'] ?: '—') ?></td>
                            <td style="text-align:center;">
                                <?php if($school['pending_count'] > 0): ?><span class="badge-pending"><?= $school['pending_count'] ?> pending</span><?php else: ?><span style="color:var(--text-light);">0</span><?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <a href="view_school_results.php?school_id=<?= htmlspecialchars($school['school_id']) ?>" class="btn-view">View</a>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="7" class="empty-state"><i class="fas fa-search"></i><br><br><strong>No schools found</strong><br>Try adjusting your search term.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($total_pages > 1): ?>
            <div class="pagination">
                <?php if($page > 1): ?><a href="?page=<?= $page-1 ?>&search_school=<?= urlencode($search_school) ?>" class="page-link">‹ Prev</a><?php else: ?><span class="page-link disabled">‹ Prev</span><?php endif; ?>
                <span class="page-info">Page <?= $page ?> of <?= $total_pages ?></span>
                <?php if($page < $total_pages): ?><a href="?page=<?= $page+1 ?>&search_school=<?= urlencode($search_school) ?>" class="page-link">Next ›</a><?php else: ?><span class="page-link disabled">Next ›</span><?php endif; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="empty-state"><i class="fas fa-school"></i><br><br><strong>No schools registered</strong><br>Schools in your district will appear here once added.</div>
    <?php endif; ?>
</div>

<script>
    let menuBtn = document.getElementById("menu-btn"), sidebar = document.getElementById("sidebar");
    if(menuBtn && sidebar) menuBtn.addEventListener("click", () => sidebar.classList.toggle("active"));
</script>
</body>
</html>