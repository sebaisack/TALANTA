<?php
/**
 * Edit Club Details for Sport Teachers
 * 🔐 School-scoped update + CSRF protection + AJAX student count
 */
session_start();
include "../component/connect.php";

$message = [];
$message_type = '';

// 🔐 Authentication Check
if (!isset($_SESSION['sport_teacher_logged_in']) || !isset($_SESSION['sport_teacher_id'])) {
    header("Location: sport_teacher_login.php");
    exit;
}

$teacher_id = $_SESSION['sport_teacher_id'];

// 🔐 Re-fetch school_id securely
$stmt = $pdo->prepare("SELECT school_id, full_name FROM sport_teachers WHERE teacher_id = ? LIMIT 1");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teacher || empty($teacher['school_id'])) {
    session_destroy();
    header("Location: sport_teacher_login.php?error=school_not_found");
    exit;
}

$school_id = $teacher['school_id'];
$teacher_name = htmlspecialchars($teacher['full_name']);

// 🔐 CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 🆔 Get & validate club ID from URL
$club_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($club_id <= 0) {
    header("Location: view_clubs.php");
    exit;
}

// 🔍 Fetch club SECURELY (school isolation)
$club_stmt = $pdo->prepare("
    SELECT id, club_name, focus_area, activities, created_at 
    FROM clubs 
    WHERE id = ? AND school_id = ? 
    LIMIT 1
");
$club_stmt->execute([$club_id, $school_id]);
$club = $club_stmt->fetch(PDO::FETCH_ASSOC);

// Redirect if club not found or doesn't belong to this school
if (!$club) {
    header("Location: view_clubs.php?error=club_not_found");
    exit;
}

// 📋 Predefined Clubs List
$clubs_list = [
    "Rumanyika Club", "Ibanda Club", "Serengeti Club", "Burigi Club",
    "Mikumi Club", "Mtagata Club", "Ngorongoro Club", "Saanane Club",
    "Rubondo Club", "Gombe Club"
];

// ✅ AJAX Endpoint: Fetch student count for this club
if (isset($_GET['ajax_student_count']) && isset($_GET['club_id'])) {
    header('Content-Type: application/json');
    
    $ajax_club_id = (int)$_GET['club_id'];
    
    // Verify club belongs to this school
    $check = $pdo->prepare("SELECT id FROM clubs WHERE id = ? AND school_id = ? LIMIT 1");
    $check->execute([$ajax_club_id, $school_id]);
    
    if ($check->rowCount() === 0) {
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    
    // Count enrolled students
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM club_members 
        WHERE club_id = ? AND status = 'active'
    ");
    $count_stmt->execute([$ajax_club_id]);
    $count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    echo json_encode(['success' => true, 'count' => $count]);
    exit;
}

// 🔄 Handle Update Form Submission
if (isset($_POST['update'])) {
    
    // 🔐 Verify CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $message[] = "❌ Invalid security token. Please refresh and try again.";
        $message_type = 'error';
    } else {
        // Sanitize & validate input
        $new_name     = trim($_POST['club_name'] ?? '');
        $new_focus    = trim($_POST['focus_area'] ?? '');
        $new_activities = trim($_POST['activities'] ?? '');
        
        // Server-side validation
        if (empty($new_name) || empty($new_focus)) {
            $message[] = "⚠️ Club Name and Focus Area are required!";
            $message_type = 'error';
        } elseif (strlen($new_name) > 100) {
            $message[] = "⚠️ Club name is too long (max 100 characters).";
            $message_type = 'error';
        } elseif (strlen($new_focus) > 150) {
            $message[] = "⚠️ Focus area is too long (max 150 characters).";
            $message_type = 'error';
        } else {
            // 🔍 Check if new name conflicts with another club in THIS school (excluding current club)
            $check = $pdo->prepare("
                SELECT id FROM clubs 
                WHERE school_id = ? AND LOWER(club_name) = LOWER(?) AND id != ? 
                LIMIT 1
            ");
            $check->execute([$school_id, $new_name, $club_id]);
            
            if ($check->rowCount() > 0) {
                $message[] = "⚠️ Another club with this name already exists in your school!";
                $message_type = 'error';
            } else {
                // ✅ UPDATE club with school isolation
                $update = $pdo->prepare("
                    UPDATE clubs 
                    SET club_name = ?, 
                        focus_area = ?, 
                        activities = ?, 
                        updated_at = NOW() 
                    WHERE id = ? AND school_id = ?
                ");
                
                try {
                    $update->execute([
                        $new_name,
                        $new_focus,
                        !empty($new_activities) ? $new_activities : null,
                        $club_id,
                        $school_id
                    ]);
                    
                    $message[] = "✅ Club <strong>" . htmlspecialchars($new_name) . "</strong> updated successfully!";
                    $message_type = 'success';
                    
                    // Refresh club data for form display
                    $club['club_name'] = $new_name;
                    $club['focus_area'] = $new_focus;
                    $club['activities'] = $new_activities;
                    
                } catch (PDOException $e) {
                    error_log("Edit club error: " . $e->getMessage());
                    $message[] = "❌ Failed to update club. Please try again.";
                    $message_type = 'error';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Club | Sport Teacher Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        :root {
            --primary: #4f46e5; --primary-dark: #4338ca;
            --success: #10b981; --error: #ef4444; --warning: #f59e0b;
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --card-shadow: 0 10px 40px -10px rgba(0,0,0,0.15);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; 
            background: #f4f6f9;
            min-height: 100vh; 
            padding: 6rem 20px; 
            font-size: 1.25rem;
        }
        .page-wrapper { width: 98%; max-width: 1800px; margin: 2rem auto; }
        
        .header-section { text-align: center; color: var(--bg-gradient); margin-bottom: 2rem; }
        .header-section h1 { 
            font-size: 2.4rem; font-weight: 700; margin-bottom: 0.5rem; 
            display: flex; align-items: center; justify-content: center; gap: 12px; 
        }
        .teacher-info { 
            background: rgba(255,255,255,0.15); padding: 12px 24px; 
            border-radius: 50px; display: inline-flex; align-items: center; 
            gap: 8px; margin-top: 10px; font-size: 1rem; backdrop-filter: blur(10px); 
        }
        
        .message { 
            padding: 18px 22px; border-radius: 12px; margin-bottom: 20px; 
            font-weight: 500; display: flex; align-items: center; gap: 10px; 
            animation: slideIn 0.3s ease; font-size: 1.05rem;
        }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .message.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .message.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        .form-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem 2.5rem;
            box-shadow: var(--card-shadow);
            position: relative;
        }
        
        .back-btn { 
            position: absolute; 
            top: 20px; 
            left: 20px; 
            padding: 10px 18px; 
            background: #f1f5f9; 
            color: #475569; 
            text-decoration: none; 
            border-radius: 10px; 
            font-weight: 600; 
            display: inline-flex; 
            align-items: center; 
            gap: 6px; 
            transition: all 0.2s; 
            font-size: 1rem;
        }
        .back-btn:hover { background: #e2e8f0; color: #1e293b; }
        
        .club-info-bar {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .club-info-bar strong { font-size: 1.15rem; }
        .club-meta { display: flex; gap: 20px; flex-wrap: wrap; }
        .club-meta span { display: flex; align-items: center; gap: 6px; font-size: 1rem; }
        
        .student-count-badge {
            background: rgba(255,255,255,0.2);
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .student-count-badge:hover { background: rgba(255,255,255,0.3); }
        
        .form-group { margin-bottom: 1.8rem; }
        .form-group label { 
            display: block; 
            margin-bottom: 10px; 
            font-weight: 700; 
            color: #1e293b; 
            font-size: 1.1rem; 
        }
        .form-group label .required { color: var(--error); margin-left: 3px; }
        
        .form-control {
            width: 100%;
            padding: 14px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1.05rem;
            transition: all 0.2s;
            background: #f8fafc;
        }
        .form-control:focus { 
            outline: none; 
            border-color: var(--primary); 
            background: white; 
            box-shadow: 0 0 0 4px rgba(79,70,229,0.15); 
        }
        
        select.form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 16px center;
            background-repeat: no-repeat;
            background-size: 1.2em;
            padding-right: 45px;
        }
        
        textarea.form-control { 
            min-height: 140px; 
            resize: vertical; 
            font-family: inherit; 
            line-height: 1.6;
        }
        
        .hint-text { 
            font-size: 0.95rem; 
            color: #64748b; 
            margin-top: 8px; 
            line-height: 1.5; 
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-row { 
            display: flex; 
            gap: 15px; 
            margin-top: 30px; 
            flex-wrap: wrap;
        }
        .btn { 
            flex: 1; 
            min-width: 150px;
            padding: 16px 24px; 
            border: none; 
            border-radius: 14px; 
            font-size: 1.1rem; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.3s; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 10px; 
        }
        .btn-primary { 
            background: linear-gradient(135deg, var(--primary), var(--primary-dark)); 
            color: white; 
        }
        .btn-primary:hover:not(:disabled) { 
            transform: translateY(-2px); 
            box-shadow: 0 12px 30px rgba(79,70,229,0.4); 
        }
        .btn-secondary { 
            background: #f1f5f9; 
            color: #475569; 
            text-decoration: none;
        }
        .btn-secondary:hover { background: #e2e8f0; color: #1e293b; }
        .btn:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }
        
        .club-preview {
            background: #f8fafc;
            border-radius: 12px;
            padding: 18px 20px;
            margin-top: 15px;
            border-left: 4px solid var(--primary);
            display: none;
        }
        .club-preview h4 { 
            color: #1e293b; 
            margin-bottom: 12px; 
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .preview-item { 
            color: #475569; 
            margin: 6px 0; 
            font-size: 1rem;
            display: flex;
            gap: 8px;
        }
        .preview-item strong { color: #1e293b; min-width: 80px; }
        
        /* Modal for Students List */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.6);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 20px;
            animation: fadeIn 0.2s ease;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        
        .modal-content {
            background: white;
            border-radius: 16px;
            width: 100%;
            max-width: 800px;
            max-height: 85vh;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
        }
        
        .modal-header {
            padding: 20px 25px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 { color: #1e293b; font-size: 1.3rem; margin: 0; }
        .modal-close {
            background: none; border: none; font-size: 1.5rem;
            color: #64748b; cursor: pointer; padding: 5px; border-radius: 8px;
        }
        .modal-close:hover { background: #e2e8f0; color: #ef4444; }
        
        .modal-body { padding: 20px 25px; overflow-y: auto; flex: 1; }
        
        .students-table { width: 100%; border-collapse: collapse; font-size: 1rem; }
        .students-table th {
            background: #f8fafc; padding: 14px 16px; text-align: left;
            font-weight: 600; color: #334155; border-bottom: 2px solid #e2e8f0;
        }
        .students-table td {
            padding: 14px 16px; border-bottom: 1px solid #e2e8f0; color: #475569;
        }
        .students-table tr:hover { background: #f1f5f9; }
        
        .modal-empty { text-align: center; padding: 40px 20px; color: #64748b; }
        .modal-empty i { font-size: 2.5rem; margin-bottom: 15px; color: #94a3b8; }
        .loading { text-align: center; padding: 30px; color: #64748b; }
        .loading i { animation: spin 1s linear infinite; margin-right: 10px; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        
        /* Responsive */
        @media (max-width: 768px) {
            .form-card { padding: 1.8rem 1.5rem; }
            .club-info-bar { flex-direction: column; align-items: flex-start; }
            .btn-row { flex-direction: column; }
            .btn { width: 100%; }
            
            /* Modal responsive */
            .students-table, .students-table thead, .students-table tbody, 
            .students-table th, .students-table td, .students-table tr { display: block; }
            .students-table thead { position: absolute; left: -9999px; }
            .students-table tr { margin-bottom: 10px; border: 1px solid #e2e8f0; border-radius: 10px; }
            .students-table td {
                border: none; padding: 10px 15px; text-align: right;
                position: relative; padding-left: 50%;
            }
            .students-table td::before {
                content: attr(data-label); position: absolute; left: 15px;
                width: 45%; font-weight: 600; color: #334155;
                text-transform: uppercase; font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
<?php include 'sport_teacher_header.php'; ?>

<div class="page-wrapper">
    <div class="header-section">
        <h1><i class="fas fa-edit"></i> Edit Club</h1>
        <div class="teacher-info"><i class="fas fa-user-check"></i> Logged in as: <strong><?= $teacher_name ?></strong></div>
    </div>

    <?php if (!empty($message)): ?>
        <?php foreach($message as $msg): ?>
            <div class="message <?= $message_type ?>">
                <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                <span><?= $msg ?></span>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="form-card">
        <a href="view_clubs.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Clubs</a>
        
        <!-- Club Info Bar -->
        <div class="club-info-bar">
            <strong><i class="fas fa-clipboard"></i> <?= htmlspecialchars($club['club_name']) ?></strong>
            <div class="club-meta">
                <span><i class="far fa-calendar-alt"></i> Added: <?= date('M j, Y', strtotime($club['created_at'])) ?></span>
                <!-- ✅ Clickable Student Count Badge -->
                <span class="student-count-badge" id="studentCountBadge" data-club-id="<?= $club_id ?>">
                    <i class="fas fa-users"></i> <span id="studentCount">Loading...</span> Students
                </span>
            </div>
        </div>

        <form method="POST" action="" id="editForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <!-- Club Name -->
            <div class="form-group">
                <label for="club_name">Club Name <span class="required">*</span></label>
                <select name="club_name" id="club_name" class="form-control" required>
                    <option value="">-- Choose Club --</option>
                    <?php foreach($clubs_list as $club_opt): ?>
                        <option value="<?= htmlspecialchars($club_opt) ?>" 
                                <?= ($club['club_name'] === $club_opt) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($club_opt) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="hint-text"><i class="fas fa-info-circle"></i> Select from the predefined list of approved clubs</div>
            </div>

            <!-- Focus Area -->
            <div class="form-group">
                <label for="focus_area">Focus Area <span class="required">*</span></label>
                <input type="text" name="focus_area" id="focus_area" class="form-control" 
                       value="<?= htmlspecialchars($club['focus_area']) ?>" 
                       placeholder="e.g., Football Training, Music Performance, Coding & Robotics..." 
                       maxlength="150" required>
                <div class="hint-text"><i class="fas fa-lightbulb"></i> Briefly describe the main focus of this club</div>
            </div>

            <!-- Activities -->
            <div class="form-group">
                <label for="activities">Activities & Description <small>(Optional)</small></label>
                <textarea name="activities" id="activities" class="form-control" 
                          placeholder="Describe planned activities, meeting schedule, required equipment, etc."><?= htmlspecialchars($club['activities'] ?? '') ?></textarea>
                <div class="hint-text"><i class="fas fa-clipboard-list"></i> Help students understand what to expect</div>
            </div>

            <!-- Live Preview -->
            <div class="club-preview" id="clubPreview">
                <h4><i class="fas fa-eye"></i> Live Preview</h4>
                <div class="preview-item"><strong>Club:</strong> <span id="previewName">-</span></div>
                <div class="preview-item"><strong>Focus:</strong> <span id="previewFocus">-</span></div>
            </div>

            <!-- Action Buttons -->
            <div class="btn-row">
                <button type="submit" name="update" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <a href="view_clubs.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ✅ Modal for Displaying Club Students -->
<div class="modal-overlay" id="studentsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-users"></i> <span id="modalClubName">Club Students</span></h3>
            <button class="modal-close" id="modalClose">&times;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <div class="loading"><i class="fas fa-spinner"></i> Loading students...</div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editForm');
    const clubName = document.getElementById('club_name');
    const focusArea = document.getElementById('focus_area');
    const activities = document.getElementById('activities');
    const preview = document.getElementById('clubPreview');
    const previewName = document.getElementById('previewName');
    const previewFocus = document.getElementById('previewFocus');
    const submitBtn = document.getElementById('submitBtn');
    
    // ✅ Live Preview Functionality
    function updatePreview() {
        const name = clubName.value.trim();
        const focus = focusArea.value.trim();
        
        if (name || focus) {
            preview.style.display = 'block';
            previewName.textContent = name || 'Not selected';
            previewFocus.textContent = focus || 'Not specified';
        } else {
            preview.style.display = 'none';
        }
    }
    
    clubName.addEventListener('change', updatePreview);
    focusArea.addEventListener('input', updatePreview);
    updatePreview(); // Run on load
    
    // ✅ Form Submission Handling
    form.addEventListener('submit', function(e) {
        const name = clubName.value.trim();
        const focus = focusArea.value.trim();
        
        if (!name || !focus) {
            e.preventDefault();
            alert('⚠️ Please fill in all required fields (Club Name and Focus Area).');
            return false;
        }
        
        // Disable button to prevent double submission
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    });
    
    // ✅ Auto-hide success messages
    document.querySelectorAll('.message.success').forEach(msg => {
        setTimeout(() => { 
            msg.style.opacity = '0'; 
            msg.style.transition = 'opacity 0.5s ease'; 
            setTimeout(() => msg.remove(), 500); 
        }, 4000);
    });
    
    // ✅ Student Count Badge - AJAX Fetch
    const studentCountBadge = document.getElementById('studentCountBadge');
    const studentCountSpan = document.getElementById('studentCount');
    const clubId = studentCountBadge?.dataset.clubId;
    
    if (clubId) {
        fetch(`?ajax_student_count=1&club_id=${clubId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    studentCountSpan.textContent = data.count;
                } else {
                    studentCountSpan.textContent = '0';
                }
            })
            .catch(err => {
                console.error('Error fetching student count:', err);
                studentCountSpan.textContent = '0';
            });
    }
    
    // ✅ Open Students Modal on Badge Click
    studentCountBadge?.addEventListener('click', function() {
        const clubId = this.dataset.clubId;
        const clubName = document.getElementById('club_name').value;
        
        const modal = document.getElementById('studentsModal');
        const modalBody = document.getElementById('modalBody');
        const modalClubName = document.getElementById('modalClubName');
        
        modalClubName.textContent = clubName + ' - Students';
        modalBody.innerHTML = '<div class="loading"><i class="fas fa-spinner"></i> Loading students...</div>';
        modal.style.display = 'flex';
        
        // Fetch students via AJAX (same endpoint as view_clubs.php)
        fetch(`?ajax_get_students=1&club_id=${clubId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    modalBody.innerHTML = `<div class="modal-empty"><i class="fas fa-exclamation-triangle"></i><p>${data.error}</p></div>`;
                } else if (data.students && data.students.length > 0) {
                    let table = `
                        <table class="students-table">
                            <thead>
                                <tr>
                                    <th data-label="Name">Full Name</th>
                                    <th data-label="Email">Email</th>
                                    <th data-label="Grade">Grade</th>
                                    <th data-label="Joined">Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    
                    data.students.forEach(student => {
                        table += `
                            <tr>
                                <td data-label="Name">${escapeHtml(student.full_name)}</td>
                                <td data-label="Email">${escapeHtml(student.email || '-')}</td>
                                <td data-label="Grade">${escapeHtml(student.grade || '-')}</td>
                                <td data-label="Joined">${student.joined_date ? new Date(student.joined_date).toLocaleDateString() : '-'}</td>
                            </tr>
                        `;
                    });
                    
                    table += `</tbody></table>`;
                    modalBody.innerHTML = table;
                } else {
                    modalBody.innerHTML = `
                        <div class="modal-empty">
                            <i class="fas fa-user-slash"></i>
                            <p>No students enrolled in this club yet.</p>
                        </div>
                    `;
                }
            })
            .catch(err => {
                console.error('Error fetching students:', err);
                modalBody.innerHTML = `<div class="modal-empty"><i class="fas fa-exclamation-triangle"></i><p>Failed to load students.</p></div>`;
            });
    });
    
    // ✅ Modal Close Handlers
    const modal = document.getElementById('studentsModal');
    const modalClose = document.getElementById('modalClose');
    
    modalClose?.addEventListener('click', () => { modal.style.display = 'none'; });
    modal?.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal) modal.style.display = 'none'; });
    
    // ✅ XSS Protection Helper
    function escapeHtml(text) {
        if (!text) return '-';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }
    
    // ✅ Sidebar Toggle
    let menuBtn = document.getElementById("menu-btn");
    let sidebar = document.getElementById("sidebar");
    if (menuBtn && sidebar) {
        menuBtn.addEventListener("click", function() {
            sidebar.classList.toggle("active");
        });
    }
});
</script>

<?php
// ✅ AJAX Endpoint for fetching students (shared with view_clubs.php)
if (isset($_GET['ajax_get_students']) && isset($_GET['club_id'])) {
    // This block runs before HTML output when AJAX request is made
    // It's placed at the end to avoid headers already sent error
    // In production, consider moving AJAX handlers to a separate api.php file
    
    $ajax_club_id = (int)$_GET['club_id'];
    
    // Verify club belongs to this school
    $check_club = $pdo->prepare("SELECT id FROM clubs WHERE id = ? AND school_id = ? LIMIT 1");
    $check_club->execute([$ajax_club_id, $school_id]);
    
    if ($check_club->rowCount() === 0) {
        echo json_encode(['error' => 'Club not found or access denied']);
        exit;
    }
    
    // Fetch students enrolled in this club
    $students_stmt = $pdo->prepare("
        SELECT s.student_id, s.full_name, s.email, s.grade, s.phone, cm.joined_date 
        FROM students s
        INNER JOIN club_members cm ON s.student_id = cm.student_id
        WHERE cm.club_id = ? AND s.school_id = ?
        ORDER BY s.full_name ASC
    ");
    $students_stmt->execute([$ajax_club_id, $school_id]);
    $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'students' => $students]);
    exit;
}
?>
</body>
</html>