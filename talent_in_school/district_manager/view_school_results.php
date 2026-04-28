<?php
session_start();
include "../component/connect.php";

if (!isset($_SESSION['district_manager_logged_in']) || $_SESSION['district_manager_logged_in'] !== true) {
    header("Location: district_manager_login.php"); exit;
}

$dm_region   = $_SESSION['district_manager_region'] ?? '';
$dm_district = $_SESSION['district_manager_district'] ?? '';
$dm_name     = $_SESSION['district_manager_full_name'] ?? 'District Manager';

$school_id = $_GET['school_id'] ?? null;
$promo_success = '';
$promo_error   = '';

/* ===============================
   📦 HANDLE PROMOTION (POST) - AT VERY TOP
   =============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'promote') {
    
    $selected_ids = $_POST['promote_ids'] ?? [];
    $target_school = $_POST['school_id'] ?? '';
    
    error_log("Promotion attempt: school=$target_school, ids=" . json_encode($selected_ids));
    
    // Verify ownership
    $stmt = $pdo->prepare("SELECT id FROM schools WHERE school_id = ? AND region = ? AND district = ?");
    $stmt->execute([$target_school, $dm_region, $dm_district]);
    
    if ($stmt->rowCount() === 0) {
        error_log("Promotion failed: School ownership verification failed");
        $promo_error = "❌ Access denied: Invalid school.";
    } elseif (empty($selected_ids)) {
        $promo_error = "❌ No students selected.";
    } else {
        try {
            $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
            $sql = "UPDATE results SET status = 'Promoted' WHERE result_id IN ($placeholders) AND status = 'Pending'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($selected_ids);
            
            $count = $stmt->rowCount();
            error_log("Promotion successful: $count rows updated");
            
            // Build redirect URL with filters preserved
            $redirect = "view_school_results.php?school_id=" . urlencode($target_school) . "&promo_success=1&count=$count";
            if (!empty($_GET['results_search'])) $redirect .= "&results_search=" . urlencode($_GET['results_search']);
            if (!empty($_GET['date_from'])) $redirect .= "&date_from=" . urlencode($_GET['date_from']);
            if (!empty($_GET['date_to'])) $redirect .= "&date_to=" . urlencode($_GET['date_to']);
            
            header("Location: $redirect");
            exit;
            
        } catch (PDOException $e) {
            error_log("Promotion DB error: " . $e->getMessage());
            $promo_error = "❌ Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Show success message after redirect
if (isset($_GET['promo_success'])) {
    $promo_success = "✅ " . (int)($_GET['count'] ?? 0) . " student(s) successfully promoted!";
}

/* ===============================
   📋 FETCH SCHOOL & RESULTS
   =============================== */
$school_results = [];
$school_info = null;

if ($school_id) {
    $stmt = $pdo->prepare("SELECT school_name, region, district FROM schools WHERE school_id = ?");
    $stmt->execute([$school_id]);
    $school_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$school_info || $school_info['region'] !== $dm_region || $school_info['district'] !== $dm_district) {
        die("Access denied: School not in your district.");
    }
    
    $r_query = "SELECT r.result_id, s.student_name, s.username, s.club, r.talent_name, r.total_score, r.grade, r.status, r.uploaded_at 
                FROM results r 
                JOIN students s ON r.student_id = s.id 
                WHERE s.school_id = ?";
    $r_params = [$school_id];
    
    $search_r = trim($_GET['results_search'] ?? '');
    $date_from = trim($_GET['date_from'] ?? '');
    $date_to   = trim($_GET['date_to'] ?? '');
    
    if ($date_from && $date_to) {
        $r_query .= " AND DATE(r.uploaded_at) BETWEEN ? AND ?";
        $r_params[] = $date_from; $r_params[] = $date_to;
    } elseif ($date_from) {
        $r_query .= " AND DATE(r.uploaded_at) >= ?";
        $r_params[] = $date_from;
    } elseif ($date_to) {
        $r_query .= " AND DATE(r.uploaded_at) <= ?";
        $r_params[] = $date_to;
    }
    
    if (!empty($search_r)) {
        $sp = "%$search_r%";
        $r_query .= " AND (s.student_name LIKE ? OR s.username LIKE ? OR r.talent_name LIKE ?)";
        $r_params[] = $sp; $r_params[] = $sp; $r_params[] = $sp;
    }
    
    $r_query .= " ORDER BY r.status ASC, r.uploaded_at DESC";
    $stmt = $pdo->prepare($r_query);
    $stmt->execute($r_params);
    $school_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results - <?= htmlspecialchars($school_info['school_name'] ?? '') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        :root { --primary:#3b82f6; --primary-dark:#1e40af; --success:#27ae60; --danger:#c0392b; --dark:#2c3e50; --light:#f8fafc; --border:#e2e8f0; --text:#334155; --text-light:#64748b; --shadow:0 2px 8px rgba(0,0,0,0.06); --radius:8px; }
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f6f9;margin:0;padding-top:65px;color:var(--text);line-height:1.5;}
        .container{max-width:1200px;margin:0 auto;padding:20px;}
        
        .page-header{background:white;padding:18px 22px;border-radius:var(--radius);margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;box-shadow:var(--shadow);flex-wrap:wrap;gap:10px;}
        .btn-back{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#94a3b8;color:white;border:none;border-radius:6px;cursor:pointer;font-size:0.9rem;text-decoration:none;transition:background 0.2s;}
        .btn-back:hover{background:#64748b;}
        
        .section-header{display:flex;justify-content:space-between;align-items:center;margin:22px 0 12px;flex-wrap:wrap;gap:12px;}
        .section-title{font-size:1.15rem;color:var(--dark);font-weight:600;display:flex;align-items:center;gap:8px;}
        
        .search-box{display:flex;align-items:center;background:white;border:2px solid var(--border);border-radius:25px;padding:8px 16px;min-width:200px;}
        .search-box input{border:none;outline:none;padding:6px 8px;font-size:0.92rem;width:100%;background:transparent;color:var(--text);}
        .date-inputs{display:flex;gap:8px;align-items:center;}.date-inputs input{padding:7px 10px;border:2px solid var(--border);border-radius:6px;font-size:0.9rem;}
        .btn-filter{padding:8px 14px;background:var(--primary);color:white;border:none;border-radius:5px;cursor:pointer;}
        
        .table-wrapper{background:white;border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;margin-bottom:20px;}
        table{width:100%;border-collapse:collapse;}
        th{background:var(--dark);color:white;padding:12px 14px;text-align:left;font-weight:600;font-size:0.88rem;}
        td{padding:11px 14px;border-bottom:1px solid var(--border);font-size:0.91rem;}
        tr:hover{background:var(--light);}
        
        .btn-promote{padding:10px 20px;background:var(--success);color:white;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:1rem;display:flex;align-items:center;gap:8px;}
        .btn-promote:hover:not(:disabled){box-shadow:0 4px 12px rgba(39,174,96,0.4);}
        .btn-promote:disabled{opacity:0.5;cursor:not-allowed;}
        
        .grade-badge{display:inline-block;padding:3px 9px;border-radius:5px;font-weight:600;font-size:0.83rem;}
        .grade-a{background:#dcfce7;color:#166534;}.grade-b{background:#dbeafe;color:#1e40af;}.grade-c{background:#fef3c7;color:#92400e;}.grade-d{background:#fed7aa;color:#c2410c;}.grade-f{background:#fee2e2;color:#b91c1c;}
        .status-badge{display:inline-block;padding:4px 12px;border-radius:15px;font-size:0.78rem;font-weight:600;text-transform:uppercase;}
        .status-pending{background:#fef3c7;color:#92400e;}.status-not-pending{background:#fee2e2;color:#b91c1c;}.status-promoted{background:#dcfce7;color:#166534;}
        
        .alert-msg{padding:12px 18px;margin-bottom:15px;border-radius:8px;font-weight:500;}
        .alert-success{background:#dcfce7;color:#166534;border-left:4px solid var(--success);}
        .alert-error{background:#fee2e2;color:#b91c1c;border-left:4px solid var(--danger);}
        .action-bar{display:flex;justify-content:space-between;align-items:center;margin:15px 0;flex-wrap:wrap;gap:10px;padding:10px;background:#f8fafc;border-radius:8px;}
        .empty-state{text-align:center;padding:35px 20px;color:var(--text-light);}
        
        @media(max-width:768px){.page-header{flex-direction:column;text-align:center;}.section-header{flex-direction:column;align-items:flex-start;}.search-box,.date-inputs{min-width:100%;width:100%;}table{font-size:0.85rem;min-width:650px;display:block;overflow-x:auto;}}
    </style>
</head>
<body>
<?php include 'district_manager_header.php'; ?>
<div class="container">
    <div class="page-header">
        <div><h2><i class="fas fa-clipboard-list"></i> <?= htmlspecialchars($school_info['school_name'] ?? 'Select a School') ?></h2></div>
        <a href="district_manager_dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Schools</a>
    </div>

    <?php if($promo_success): ?><div class="alert-msg alert-success"><i class="fas fa-check-circle"></i> <?= $promo_success ?></div><?php endif; ?>
    <?php if($promo_error): ?><div class="alert-msg alert-error"><i class="fas fa-exclamation-circle"></i> <?= $promo_error ?></div><?php endif; ?>

    <?php if($school_info): ?>
        
        <!-- 🔍 SEARCH FORM (GET) -->
        <form method="GET" style="margin-bottom:20px;">
            <input type="hidden" name="school_id" value="<?= htmlspecialchars($school_id) ?>">
            <div class="section-header">
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;width:100%;">
                    <div class="search-box" style="flex:1;min-width:200px;">
                        <i class="fas fa-search" style="margin-right:8px;color:var(--text-light);"></i>
                        <input type="text" name="results_search" placeholder="Search students..." value="<?= htmlspecialchars($_GET['results_search'] ?? '') ?>">
                    </div>
                    <div class="date-inputs">
                        <input type="date" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
                        <span style="color:var(--text-light);">to</span>
                        <input type="date" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn-filter">Filter</button>
                    <?php if(!empty($_GET['results_search']) || !empty($_GET['date_from'])): ?>
                        <a href="?school_id=<?= htmlspecialchars($school_id) ?>" class="btn-back" style="padding:8px 12px;font-size:0.85rem;">Clear</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <!-- ✅ PROMOTION SECTION -->
        <?php if(!empty($school_results)): ?>
            <form method="POST" id="promoteForm">
                <input type="hidden" name="action" value="promote">
                <input type="hidden" name="school_id" value="<?= htmlspecialchars($school_id) ?>">
                
                <div class="action-bar">
                    <label style="display:flex;align-items:center;gap:6px;font-weight:500;cursor:pointer;">
                        <input type="checkbox" id="selectAllPending"> Select All Pending
                    </label>
                    <button type="submit" class="btn-promote" id="promoteBtn" disabled>
                        <i class="fas fa-check-circle"></i> Promote Selected (<span id="selCount">0</span>)
                    </button>
                </div>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:45px;text-align:center;"><input type="checkbox" id="selectAllMain"></th>
                                <th>Student</th>
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
                            <?php $pendingCount = 0; foreach($school_results as $r): 
                                $isPending = ($r['status'] === 'Pending');
                                if($isPending) $pendingCount++;
                                $gradeClass = match(strtolower($r['grade'][0] ?? '')){'a'=>'grade-a','b'=>'grade-b','c'=>'grade-c','d'=>'grade-d',default=>'grade-f'};
                                $statusClass = match($r['status']){'Pending'=>'status-pending','Promoted'=>'status-promoted',default=>'status-not-pending'};
                            ?>
                            <tr>
                                <td style="text-align:center;">
                                    <?php if($isPending): ?>
                                        <input type="checkbox" name="promote_ids[]" value="<?= $r['result_id'] ?>" class="promote-check">
                                    <?php else: ?>
                                        <span style="color:#cbd5e1;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= htmlspecialchars($r['student_name']) ?></strong></td>
                                <td style="font-family:monospace;color:#2563eb"><?= htmlspecialchars($r['username']) ?></td>
                                <td><?= htmlspecialchars($r['club']) ?></td>
                                <td><?= htmlspecialchars($r['talent_name']) ?></td>
                                <td><strong><?= $r['total_score'] ?>%</strong></td>
                                <td><span class="grade-badge <?= $gradeClass ?>"><?= htmlspecialchars($r['grade']) ?></span></td>
                                <td><span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                                <td><?= date('M d, Y', strtotime($r['uploaded_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if($pendingCount === 0): ?>
                    <div style="text-align:center;padding:15px;color:var(--text-light);">
                        <i class="fas fa-info-circle"></i> No pending students to promote.
                    </div>
                <?php endif; ?>
            </form>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i><br><br>
                <strong>No results found</strong><br>
                <?php if(!empty($_GET['results_search']) || !empty($_GET['date_from'])): ?>
                    Try adjusting filters, or <a href="?school_id=<?= htmlspecialchars($school_id) ?>" style="color:var(--primary);">clear all</a>.
                <?php else: ?>
                    No assessments uploaded yet.
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-exclamation-triangle"></i><br><br>
            <strong>Invalid School</strong><br>
            <a href="district_manager_dashboard.php" class="btn-back" style="margin-top:10px;">Back to Dashboard</a>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const promoteForm = document.getElementById('promoteForm');
    const promoteBtn = document.getElementById('promoteBtn');
    const selCount = document.getElementById('selCount');
    const checkboxes = document.querySelectorAll('.promote-check');
    const selectAllMain = document.getElementById('selectAllMain');
    const selectAllPending = document.getElementById('selectAllPending');
    
    console.log('🔍 Promotion script loaded. Checkboxes found:', checkboxes.length);
    
    function updateCount() {
        const checked = document.querySelectorAll('.promote-check:checked').length;
        selCount.textContent = checked;
        promoteBtn.disabled = (checked === 0);
        console.log('✅ Count updated:', checked, '- Button disabled:', promoteBtn.disabled);
    }
    
    // Attach events to checkboxes
    checkboxes.forEach((cb, idx) => {
        cb.addEventListener('change', function() {
            console.log('📦 Checkbox', idx, 'changed:', this.checked);
            updateCount();
        });
    });
    
    // Select All Pending
    if(selectAllPending) {
        selectAllPending.addEventListener('change', function(e) {
            console.log('🎯 Select All Pending:', e.target.checked);
            checkboxes.forEach(cb => cb.checked = e.target.checked);
            updateCount();
        });
    }
    
    // Header Select All
    if(selectAllMain) {
        selectAllMain.addEventListener('change', function(e) {
            console.log('🎯 Select All Main:', e.target.checked);
            checkboxes.forEach(cb => cb.checked = e.target.checked);
            updateCount();
        });
    }
    
    // Form submit handler
    if(promoteForm) {
        promoteForm.addEventListener('submit', function(e) {
            const checked = document.querySelectorAll('.promote-check:checked').length;
            console.log('🚀 Form submitted. Checked:', checked);
            
            if(checked === 0) { 
                e.preventDefault(); 
                alert('⚠️ Please select at least one pending student.');
                console.log('❌ Submission blocked: No selections');
                return false;
            }
            
            if(!confirm(`✅ Promote ${checked} student(s) to "Promoted" status?\n\n⚠️ This cannot be undone.`)) {
                e.preventDefault();
                console.log('❌ Submission cancelled by user');
                return false;
            }
            
            console.log('✅ Submission proceeding...');
        });
    }
    
    // Initial count
    updateCount();
});
</script>
</body>
</html>