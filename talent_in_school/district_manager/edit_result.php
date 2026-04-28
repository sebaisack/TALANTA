<?php
// File: /district_manager/edit_result.php
session_start();
include "../component/connect.php";

// 🔐 Ensure district manager is logged in
if (!isset($_SESSION['district_manager_logged_in']) || $_SESSION['district_manager_logged_in'] !== true) {
    header("Location: district_manager_login.php");
    exit;
}

$message = [];
$result_id = (int)($_GET['id'] ?? $_POST['result_id'] ?? 0);

// ✅ Validate result_id
if ($result_id <= 0) {
    header("Location: promote_students.php");
    exit;
}

// ✅ Fetch result + student data
$stmt = $pdo->prepare("
    SELECT r.*, s.student_name, s.username, s.club, s.standard 
    FROM results r 
    JOIN students s ON r.student_id = s.id 
    WHERE r.result_id = ?
");
$stmt->execute([$result_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    $_SESSION['message'] = "❌ Assessment not found!";
    header("Location: promote_students.php");
    exit;
}

/* ===============================
   HANDLE FORM SUBMISSION (UPDATE)
   =============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_changes'])) {
    
    $total_score = (int)($_POST['total_score'] ?? 0);
    $grade = trim($_POST['grade'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    $talent_name = trim($_POST['talent_name'] ?? '');
    
    // ✅ Validation
    if ($total_score < 0 || $total_score > 100) {
        $message[] = "Score must be between 0 and 100.";
    } elseif (!in_array($status, ['Pending', 'Not Pending', 'Promoted'])) {
        $message[] = "Invalid status value.";
    } else {
        try {
            // ✅ UPDATE query (no 'updated_at' column per your schema)
            $update = $pdo->prepare("
                UPDATE results 
                SET total_score = ?, grade = ?, status = ?, remarks = ?, talent_name = ?
                WHERE result_id = ?
            ");
            
            $update->execute([
                $total_score, $grade, $status, $remarks, $talent_name, $result_id
            ]);
            
            $message[] = "<div style='color:#27ae60;font-weight:600;text-align:center;'>
                            ✅ Assessment updated successfully!
                          </div>";
            
            // ✅ Refresh result data to show updated values
            $stmt->execute([$result_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $message[] = "Error: " . htmlspecialchars($e->getMessage());
            error_log("Edit error: " . $e->getMessage());
        }
    }
}

// ✅ Helper: Generate grade options
$grade_options = ['A+','A','B+','B','C','D','E','F'];
$status_options = ['Pending', 'Not Pending', 'Promoted'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Assessment - District Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background: #f4f6f9; 
            margin: 0; 
            padding-top: 65px; 
            min-height: 100vh; 
        }
        .container { max-width: 700px; margin: 0 auto; padding: 25px 20px; }
        
        .page-header { 
            display: flex; justify-content: space-between; align-items: center; 
            margin-bottom: 20px; flex-wrap: wrap; gap: 10px; 
        }
        .page-title { 
            font-size: 1.4rem; color: #2c3e50; font-weight: 600; 
            display: flex; align-items: center; gap: 10px; margin: 0; 
        }
        .btn-back { 
            display: inline-flex; align-items: center; gap: 6px; 
            padding: 10px 18px; background: #94a3b8; color: white; 
            text-decoration: none; border-radius: 8px; font-weight: 500; 
        }
        .btn-back:hover { background: #64748b; }
        
        .edit-card {
            background: white; border-radius: 16px; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.08); 
            overflow: hidden; margin-bottom: 25px;
        }
        .card-header {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white; padding: 20px 25px;
        }
        .card-header h2 { font-size: 1.3rem; margin: 0; display: flex; align-items: center; gap: 10px; }
        .card-body { padding: 25px; }
        
        .student-summary {
            background: #f8fafc; padding: 15px; border-radius: 10px; 
            margin-bottom: 20px; border-left: 4px solid #3b82f6;
        }
        .student-summary p { margin: 4px 0; font-size: 0.95rem; color: #334155; }
        .student-summary strong { color: #1e293b; }
        
        .form-group { margin-bottom: 18px; }
        .form-group label { 
            display: block; font-weight: 600; color: #2c3e50; 
            margin-bottom: 6px; font-size: 0.95rem; 
        }
        .required { color: #e74c3c; margin-left: 3px; }
        
        .form-group input[type="text"], 
        .form-group input[type="number"], 
        .form-group select, 
        .form-group textarea {
            width: 100%; padding: 12px 14px; 
            border: 2px solid #e2e8f0; border-radius: 8px; 
            font-size: 1rem; transition: border-color 0.3s;
            background: #f8fafc;
        }
        .form-group input:focus, 
        .form-group select:focus, 
        .form-group textarea:focus { 
            border-color: #3b82f6; outline: none; 
            background: white; box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .form-group textarea { min-height: 90px; resize: vertical; }
        
        .score-input { 
            display: flex; align-items: center; gap: 10px; 
            max-width: 200px; 
        }
        .score-input input { 
            width: 80px !important; text-align: center; font-weight: 600; 
        }
        .score-hint { 
            font-size: 0.85rem; color: #64748b; margin-top: 4px; 
        }
        
        .grade-preview { 
            display: inline-block; padding: 4px 10px; border-radius: 6px; 
            font-weight: 600; font-size: 0.9rem; margin-left: 10px; 
            background: #e2e8f0; color: #475569;
        }
        .grade-a { background: #d1fae5; color: #065f46; }
        .grade-b { background: #dbeafe; color: #1e40af; }
        .grade-c { background: #fef3c7; color: #92400e; }
        .grade-d { background: #fee2e2; color: #991b1b; }
        
        .message { 
            padding: 15px 20px; margin-bottom: 20px; border-radius: 10px; 
            font-size: 0.95rem; text-align: center; 
        }
        .message.success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .message.error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        
        .btn-save { 
            width: 100%; padding: 14px; 
            background: linear-gradient(135deg, #10b981, #059669); 
            color: white; border: none; border-radius: 8px; 
            font-size: 1.05rem; font-weight: 600; cursor: pointer; 
            margin-top: 10px; transition: transform 0.2s, box-shadow 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-save:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 6px 15px rgba(16, 185, 129, 0.3); 
        }
        
        .btn-cancel {
            display: block; width: 100%; padding: 12px; 
            background: #f1f5f9; color: #475569; border: none; 
            border-radius: 8px; font-weight: 500; cursor: pointer; 
            margin-top: 10px; text-decoration: none; text-align: center;
        }
        .btn-cancel:hover { background: #e2e8f0; }
        
        @media (max-width: 600px) {
            .page-header { flex-direction: column; align-items: flex-start; }
            .score-input { max-width: 100%; }
            .card-body { padding: 20px 15px; }
        }
    </style>
</head>
<body>

<?php include 'district_manager_header.php'; ?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-edit"></i> Edit Assessment</h1>
        <a href="promote_students.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to List</a>
    </div>

    <?php foreach ($message as $msg): ?>
        <div class="message <?= strpos($msg, '✅') !== false ? 'success' : 'error' ?>">
            <?= $msg ?>
        </div>
    <?php endforeach; ?>

    <div class="edit-card">
        <div class="card-header">
            <h2><i class="fas fa-user"></i> <?= htmlspecialchars($result['student_name']) ?></h2>
        </div>
        <div class="card-body">
            <!-- Student Summary -->
            <div class="student-summary">
                <p><strong>Username:</strong> <?= htmlspecialchars($result['username']) ?></p>
                <p><strong>Club:</strong> <?= htmlspecialchars($result['club']) ?> | <strong>Standard:</strong> <?= htmlspecialchars($result['standard']) ?></p>
                <p><strong>Talent:</strong> <span id="talentDisplay"><?= htmlspecialchars($result['talent_name']) ?></span></p>
            </div>

            <form method="POST" id="editForm">
                <input type="hidden" name="result_id" value="<?= $result['result_id'] ?>">
                
                <!-- Talent Name (Editable) -->
                <div class="form-group">
                    <label>Talent Name <span class="required">*</span></label>
                    <input type="text" name="talent_name" id="talentInput" 
                           value="<?= htmlspecialchars($result['talent_name']) ?>" required>
                </div>
                
                <!-- Total Score -->
                <div class="form-group">
                    <label>Total Score (0-100) <span class="required">*</span></label>
                    <div class="score-input">
                        <input type="number" name="total_score" id="scoreInput" 
                               min="0" max="100" value="<?= (int)$result['total_score'] ?>" required>
                        <span class="grade-preview" id="gradePreview"><?= htmlspecialchars($result['grade']) ?></span>
                    </div>
                    <div class="score-hint">
                        <i class="fas fa-info-circle"></i> Grade updates automatically based on score
                    </div>
                </div>
                
                <!-- Grade (Auto-calculated but editable if needed) -->
                <div class="form-group">
                    <label>Grade</label>
                    <select name="grade" id="gradeSelect">
                        <?php foreach ($grade_options as $g): ?>
                            <option value="<?= $g ?>" <?= $result['grade'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Status -->
                <div class="form-group">
                    <label>Status <span class="required">*</span></label>
                    <select name="status" required>
                        <?php foreach ($status_options as $s): ?>
                            <option value="<?= $s ?>" <?= $result['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Remarks -->
                <div class="form-group">
                    <label>Remarks / Notes</label>
                    <textarea name="remarks" placeholder="Add comments about this assessment..."><?= htmlspecialchars($result['remarks'] ?? '') ?></textarea>
                </div>
                
                <button type="submit" name="save_changes" class="btn-save">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <a href="promote_students.php" class="btn-cancel">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script>
// 🔄 Auto-update grade preview when score changes
const scoreInput = document.getElementById('scoreInput');
const gradePreview = document.getElementById('gradePreview');
const gradeSelect = document.getElementById('gradeSelect');
const talentInput = document.getElementById('talentInput');
const talentDisplay = document.getElementById('talentDisplay');

// Update grade preview
function updateGradePreview(score) {
    let grade = 'F', className = 'grade-d';
    if (score >= 90) { grade = 'A+'; className = 'grade-a'; }
    else if (score >= 75) { grade = 'A'; className = 'grade-a'; }
    else if (score >= 60) { grade = 'B+'; className = 'grade-b'; }
    else if (score >= 50) { grade = 'B'; className = 'grade-b'; }
    else if (score >= 40) { grade = 'C'; className = 'grade-c'; }
    else if (score >= 30) { grade = 'D'; className = 'grade-d'; }
    else if (score >= 20) { grade = 'E'; className = 'grade-d'; }
    
    gradePreview.textContent = grade;
    gradePreview.className = `grade-preview ${className}`;
    
    // Optionally auto-select in dropdown (commented out to allow manual override)
    // gradeSelect.value = grade;
}

// Initialize on load
updateGradePreview(scoreInput.value);

// Live update on input
scoreInput.addEventListener('input', function() {
    const score = parseInt(this.value) || 0;
    if (score >= 0 && score <= 100) {
        updateGradePreview(score);
    }
});

// Sync talent input with display (for visual feedback)
talentInput.addEventListener('input', function() {
    talentDisplay.textContent = this.value || '—';
});

// ✅ Prevent form submit if score invalid
document.getElementById('editForm')?.addEventListener('submit', function(e) {
    const score = parseInt(scoreInput.value) || 0;
    if (score < 0 || score > 100) {
        e.preventDefault();
        alert('Score must be between 0 and 100.');
        scoreInput.focus();
    }
});
</script>

</body>
</html>