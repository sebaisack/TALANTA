<?php
session_start();
include "../component/connect.php";

$messages = [];

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

if (!$teacher || empty($teacher['school_id'])) {
    die("Error: School information not found. Please contact admin.");
}

$school_id = $teacher['school_id'];

// 🎯 Get Status based on marks: <50 = Not Pending, >=50 = Pending
function getStatus($marks) {
    return ($marks >= 50) ? 'Pending' : 'Not Pending';
}

// 🎯 Get Grade based on marks
function getGrade($marks) {
    if ($marks >= 90) return 'A+';
    elseif ($marks >= 75) return 'A';
    elseif ($marks >= 60) return 'B+';
    elseif ($marks >= 50) return 'B';
    elseif ($marks >= 40) return 'C';
    elseif ($marks >= 30) return 'D';
    elseif ($marks >= 20) return 'E';
    else return 'F';
}

// 🎯 Auto-generate remarks based on marks/grade
function getAutoRemarks($marks) {
    if ($marks >= 90) return "Excellent";
    elseif ($marks >= 75) return "Very good";
    elseif ($marks >= 60) return "Good";
    elseif ($marks >= 50) return "Satisfactory";
    elseif ($marks >= 40) return "Fair";
    elseif ($marks >= 30) return "Poor";
    elseif ($marks >= 20) return "Unsatisfactory";
    else return "Failed";
}

// 📥 Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_results'])) {
    
    $results = $_POST['results'] ?? [];
    
    if (empty($results)) {
        $messages[] = ["type" => "error", "text" => "No student scores to upload."];
    } 
    else {
        $success_count = 0;
        $error_count = 0;
        
        foreach ($results as $student_id => $data) {
            $marks = (int)($data['marks'] ?? 0);
            $remarks = trim($data['remarks'] ?? '');
            $talent = trim($data['talent'] ?? '');
            $club_name = trim($data['club_name'] ?? '');
            
            if ($marks < 0 || $marks > 100) {
                $error_count++;
                continue;
            }
            
            $grade = getGrade($marks);
            $status = getStatus($marks);
            $auto_remarks = getAutoRemarks($marks);
            $grade_point = match($grade) {
                'A+' => 1.0, 'A' => 1.5, 'B+' => 2.0, 'B' => 2.5,
                'C' => 3.0, 'D' => 3.5, 'E' => 4.0, default => 4.5
            };
            
            try {
                $check = $pdo->prepare("SELECT result_id FROM results WHERE student_id = ? AND talent_name = ?");
                $check->execute([$student_id, $talent]);
                
                if ($check->rowCount() > 0) {
                    $clubStmt = $pdo->prepare("SELECT club_id FROM clubs WHERE club_name = ? AND school_id = ?");
                    $clubStmt->execute([$club_name, $school_id]);
                    $club_id = $clubStmt->fetchColumn() ?: 0;
                    
                    $query = "UPDATE results SET 
                        club_id = ?, total_score = ?, grade = ?, grade_point = ?, status = ?, 
                        remarks = ?, auto_remarks = ?, uploaded_by = ?, updated_at = NOW()
                        WHERE student_id = ? AND talent_name = ?";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([$club_id, $marks, $grade, $grade_point, $status, $remarks, $auto_remarks, $teacher_id, $student_id, $talent]);
                } else {
                    $clubStmt = $pdo->prepare("SELECT club_id FROM clubs WHERE club_name = ? AND school_id = ?");
                    $clubStmt->execute([$club_name, $school_id]);
                    $club_id = $clubStmt->fetchColumn() ?: 0;
                    
                    $query = "INSERT INTO results 
                        (student_id, club_id, talent_name, total_score, grade, grade_point, status, remarks, auto_remarks, uploaded_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([$student_id, $club_id, $talent, $marks, $grade, $grade_point, $status, $remarks, $auto_remarks, $teacher_id]);
                }
                $success_count++;
                
            } catch (PDOException $e) {
                $error_count++;
                error_log("Result upload error: " . $e->getMessage());
            }
        }
        
        if ($success_count > 0) {
            $messages[] = ["type" => "success", "text" => "$success_count result(s) uploaded successfully!"];
        }
        if ($error_count > 0) {
            $messages[] = ["type" => "error", "text" => "$error_count entry(ies) failed. Ensure marks are between 0-100."];
        }
    }
}

// 👥 Fetch ALL students for this teacher's school
$students_in_school = [];
$stmt = $pdo->prepare("
    SELECT id, student_name, username, talent, standard, club
    FROM students 
    WHERE school_id = ?
    ORDER BY student_name ASC
");
$stmt->execute([$school_id]);
$students_in_school = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Talent Results</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6f9; padding: 6rem 20px; min-height: 100vh; }
        .page-title { text-align: center; color: #2c3e50; font-size: 2rem; margin: 1.5rem 0 0.5rem; font-weight: 700; }
        .page-subtitle { text-align: center; color: #7f8c8d; margin-bottom: 2rem; font-size: 1rem; }
        .message { padding: 12px 18px; margin: 15px auto; border-radius: 8px; width: 90%; max-width: 1200px; text-align: center; font-weight: 500; }
        .success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .error   { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .form-card { max-width: 1200px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        
        /* 🔍 Search Bar Styles */
        .search-container {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 15px;
            gap: 10px;
        }
        .search-box {
            display: flex;
            align-items: center;
            background: white;
            border: 2px solid #dfe6e9;
            border-radius: 25px;
            padding: 8px 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: border-color 0.3s;
        }
        .search-box:focus-within {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.15);
        }
        .search-box input {
            border: none;
            outline: none;
            padding: 5px 10px;
            font-size: 0.95rem;
            width: 200px;
            background: transparent;
        }
        .search-box i {
            color: #7f8c8d;
            margin-right: 8px;
        }
        .search-clear {
            background: none;
            border: none;
            color: #95a5a6;
            cursor: pointer;
            padding: 5px;
            display: none;
        }
        .search-clear.visible {
            display: block;
        }
        .search-clear:hover {
            color: #e74c3c;
        }
        .search-count {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-left: 10px;
            align-self: center;
        }
        
        .table-container { overflow-x: auto; margin-top: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .results-table { width: 100%; border-collapse: collapse; background: white; font-size: 0.9rem; }
        .results-table th { background: #34495e; color: white; padding: 12px 10px; text-align: left; font-weight: 600; border: 1px solid #2c3e50; }
        .results-table td { padding: 10px; border: 1px solid #eee; vertical-align: middle; }
        .results-table tr:nth-child(even) { background: #f8f9fa; }
        .results-table tr:hover { background: #e8f4f8; }
        .results-table tr.filtered { display: none; }
        .col-name { min-width: 160px; font-weight: 500; }
        .col-username { min-width: 110px; color: #2980b9; font-family: monospace; font-size: 0.9rem; }
        .col-club { min-width: 130px; }
        .col-talent { min-width: 130px; }
        .col-marks { min-width: 90px; text-align: center; }
        .col-marks input { width: 65px; padding: 6px; text-align: center; border: 2px solid #ccc; border-radius: 6px; font-weight: 600; font-size: 0.95rem; }
        .col-marks input:focus { border-color: #3498db; outline: none; }
        .col-grade { min-width: 80px; text-align: center; font-weight: 600; }
        .grade-a { color: #27ae60; }
        .grade-b { color: #2980b9; }
        .grade-c { color: #f39c12; }
        .grade-d { color: #e67e22; }
        .grade-f { color: #c0392b; }
        .col-status { min-width: 110px; text-align: center; }
        .col-remarks { min-width: 180px; }
        .col-remarks textarea { width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 6px; min-height: 40px; resize: vertical; font-size: 0.85rem; }
        .col-auto-remarks { min-width: 220px; font-size: 0.85rem; color: #555; background: #f8f9fa; padding: 8px; border-radius: 6px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; }
        .status-not-pending { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }
        .status-pending { background: #fff3e0; color: #ef6c00; border: 1px solid #ffcc80; }
        .status-promoted { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .btn-submit { width: 100%; padding: 14px; background: #27ae60; color: white; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; margin-top: 20px; transition: background 0.3s; }
        .btn-submit:hover { background: #219a52; }
        .status-legend { background: #e8f4f8; padding: 12px 20px; border-radius: 8px; margin: 15px 0; display: flex; gap: 25px; flex-wrap: wrap; justify-content: center; font-size: 0.9rem; }
        .legend-item { display: flex; align-items: center; gap: 6px; }
        .legend-dot { width: 10px; height: 10px; border-radius: 50%; }
        .dot-not-pending { background: #c62828; }
        .dot-pending { background: #ef6c00; }
        .dot-promoted { background: #2e7d32; }
        .view-btn { display: inline-block; margin: 1rem auto; padding: 10px 22px; background: #3498db; color: white; text-decoration: none; border-radius: 50px; font-weight: 600; font-size: 0.95rem; }
        .view-btn:hover { background: #2980b9; }
        .info-note { background: #e3f2fd; padding: 12px 18px; border-radius: 8px; margin: 15px 0; font-size: 0.92rem; color: #0d47a1; border-left: 4px solid #2196f3; }
        .empty-state { text-align: center; padding: 40px 20px; color: #7f8c8d; }
        .empty-state i { font-size: 2.5rem; margin-bottom: 12px; color: #bdc3c7; }
        .no-results { text-align: center; padding: 30px; color: #7f8c8d; font-style: italic; display: none; }
        .no-results.visible { display: block; }
        .auto-remarks-label { font-size: 0.8rem; color: #7f8c8d; font-style: italic; display: block; margin-bottom: 4px; }
    </style>
</head>
<body>

<?php include 'sport_teacher_header.php'; ?>

<h1 class="page-title">📤 Upload Talent Results</h1>
<p class="page-subtitle">Enter marks (0-100). Grade & Remarks auto-generated. Status: <strong>0-49% = Not Pending</strong> | <strong>50-100% = Pending</strong></p>

<?php foreach ($messages as $msg): ?>
    <div class="message <?= $msg['type'] ?>">
        <i class="fas <?= $msg['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
        <?= htmlspecialchars($msg['text']) ?>
    </div>
<?php endforeach; ?>

<a href="view_results.php" class="view-btn">
    <i class="fas fa-table"></i> View All Results
</a>

<div class="status-legend">
    <div class="legend-item"><span class="legend-dot dot-not-pending"></span> <strong>Not Pending</strong> (0-49%)</div>
    <div class="legend-item"><span class="legend-dot dot-pending"></span> <strong>Pending</strong> (50-100%)</div>
    <div class="legend-item"><span class="legend-dot dot-promoted"></span> <strong>Promoted</strong> (Selected by DM)</div>
</div>

<div class="form-card">
    <form method="POST" id="resultForm">

      <!--  <div class="info-note">
            <strong>📋 Note:</strong> Grade and auto-remarks are generated automatically based on marks. 
            You can add optional manual remarks in the "Remarks" column.
        </div>-->

        <?php if (!empty($students_in_school)): ?>
            <!-- 🔍 Search Bar (Top Right of Table) -->
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchUsername" placeholder="Search by username...">
                    <button type="button" class="search-clear" id="searchClear">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <span class="search-count" id="searchCount">Showing <?= count($students_in_school) ?> students</span>
            </div>

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
                           
                            <th class="col-auto-remarks">Auto Remarks</th>
                             <th class="col-status">pending Status</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php foreach($students_in_school as $student): 
                            $existing = null;
                            $student_talent = $student['talent'];
                            $student_club = $student['club'];
                            
                            if (!empty($student_talent)) {
                                $chk = $pdo->prepare("SELECT * FROM results WHERE student_id = ? AND talent_name = ?");
                                $chk->execute([$student['id'], $student_talent]);
                                $existing = $chk->fetch(PDO::FETCH_ASSOC);
                            }
                            $saved_marks = $existing ? $existing['total_score'] : '';
                            $current_grade = $saved_marks !== '' ? getGrade($saved_marks) : '';
                            $current_status = $saved_marks !== '' ? getStatus($saved_marks) : '';
                            $current_auto_remarks = $existing ? $existing['auto_remarks'] : '';
                        ?>
                        <tr data-username="<?= strtolower(htmlspecialchars($student['username'])) ?>">
                            <td class="col-name"><?= htmlspecialchars($student['student_name']) ?></td>
                            <td class="col-username"><?= htmlspecialchars($student['username']) ?></td>
                            <td class="col-club"><?= htmlspecialchars($student_club) ?></td>
                            <td class="col-talent"><?= htmlspecialchars($student_talent) ?></td>
                            <td class="col-marks">
                                <input type="number" name="results[<?= $student['id'] ?>][marks]" 
                                       min="0" max="100" placeholder="0-100"
                                       value="<?= htmlspecialchars($saved_marks) ?>"
                                       class="marks-input" 
                                       data-student="<?= $student['id'] ?>">
                                <input type="hidden" name="results[<?= $student['id'] ?>][club_name]" value="<?= htmlspecialchars($student_club) ?>">
                                <input type="hidden" name="results[<?= $student['id'] ?>][talent]" value="<?= htmlspecialchars($student_talent) ?>">
                            </td>
                            <td class="col-grade" id="grade_<?= $student['id'] ?>">
                                <?php if ($current_grade): ?>
                                    <span class="grade-<?= strtolower($current_grade[0]) ?>"><?= $current_grade ?></span>
                                <?php else: ?>
                                    <span style="color:#95a5a6">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-auto-remarks" id="auto_remarks_<?= $student['id'] ?>">
                                <?php if ($current_auto_remarks): ?>
                                    <?= htmlspecialchars($current_auto_remarks) ?>
                                <?php else: ?>
                                    <span style="color:#95a5a6; font-style:italic;">Enter marks to generate</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-status" id="status_<?= $student['id'] ?>">
                                <?php if ($current_status): ?>
                                    <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $current_status)) ?>">
                                        <?= $current_status ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:#95a5a6">—</span>
                                <?php endif; ?>
                            </td>
                            
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="no-results" id="noResults">
                    <i class="fas fa-user-slash fa-2x"></i><br><br>
                    No student found with that username.
                </div>
            </div>

            <button type="submit" name="submit_results" class="btn-submit" id="submitBtn">
                <i class="fas fa-upload"></i> Upload Results
            </button>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-users-slash"></i><br><br>
                <strong>No students registered yet</strong><br>
                Register students first to upload results.
            </div>
        <?php endif; ?>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 🔍 Search functionality
    const searchInput = document.getElementById('searchUsername');
    const searchClear = document.getElementById('searchClear');
    const searchCount = document.getElementById('searchCount');
    const noResults = document.getElementById('noResults');
    const tableRows = document.querySelectorAll('#tableBody tr');
    const totalStudents = tableRows.length;
    
    // Live search filter
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        let visibleCount = 0;
        
        tableRows.forEach(row => {
            const username = row.dataset.username;
            if (username.includes(query)) {
                row.classList.remove('filtered');
                visibleCount++;
            } else {
                row.classList.add('filtered');
            }
        });
        
        // Update count and no-results message
        searchCount.textContent = `Showing ${visibleCount} of ${totalStudents} students`;
        
        if (visibleCount === 0 && query !== '') {
            noResults.classList.add('visible');
        } else {
            noResults.classList.remove('visible');
        }
        
        // Show/hide clear button
        if (query !== '') {
            searchClear.classList.add('visible');
        } else {
            searchClear.classList.remove('visible');
        }
    });
    
    // Clear search
    searchClear.addEventListener('click', function() {
        searchInput.value = '';
        searchInput.focus();
        searchInput.dispatchEvent(new Event('input'));
    });
    
    // Auto-update Grade, Status & Auto-Remarks when marks change
    document.querySelectorAll('.marks-input').forEach(input => {
        input.addEventListener('input', function() {
            const studentId = this.dataset.student;
            const marks = parseInt(this.value) || 0;
            updateAll(studentId, marks);
        });
    });
    
    function updateAll(studentId, marks) {
        const gradeEl = document.getElementById(`grade_${studentId}`);
        const statusEl = document.getElementById(`status_${studentId}`);
        const autoRemarksEl = document.getElementById(`auto_remarks_${studentId}`);
        
        if (marks < 0 || marks > 100) {
            gradeEl.innerHTML = '<span style="color:#e74c3c">Invalid</span>';
            statusEl.innerHTML = '<span style="color:#e74c3c">—</span>';
            autoRemarksEl.innerHTML = '<span style="color:#e74c3c; font-style:italic;">Invalid marks</span>';
            return;
        }
        
        let grade, gradeClass;
        if (marks >= 90) { grade='A+'; gradeClass='grade-a'; }
        else if (marks >= 75) { grade='A'; gradeClass='grade-a'; }
        else if (marks >= 60) { grade='B+'; gradeClass='grade-b'; }
        else if (marks >= 50) { grade='B'; gradeClass='grade-b'; }
        else if (marks >= 40) { grade='C'; gradeClass='grade-c'; }
        else if (marks >= 30) { grade='D'; gradeClass='grade-d'; }
        else if (marks >= 20) { grade='E'; gradeClass='grade-d'; }
        else { grade='F'; gradeClass='grade-f'; }
        
        const status = (marks >= 50) ? 'Pending' : 'Not Pending';
        const statusClass = `status-${status.toLowerCase().replace(' ', '-')}`;
        const autoRemarks = getAutoRemarks(marks);
        
        gradeEl.innerHTML = `<span class="${gradeClass}"><strong>${grade}</strong></span>`;
        statusEl.innerHTML = `<span class="status-badge ${statusClass}">${status}</span>`;
        autoRemarksEl.innerHTML = autoRemarks;
    }
    
    function getAutoRemarks(marks) {
        if (marks >= 90) return "Excellent";
        else if (marks >= 75) return "Very good";
        else if (marks >= 60) return "Good";
        else if (marks >= 50) return "Satisfactory";
        else if (marks >= 40) return "Fair";
        else if (marks >= 30) return "Poor";
        else if (marks >= 20) return "Unsatisfactor";
        else return "Failed";
    }
});
</script>

<script>
    // Sidebar toggle
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