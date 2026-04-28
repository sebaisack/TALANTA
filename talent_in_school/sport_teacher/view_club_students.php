<?php
/**
 * View Students in a Specific Club for Sport Teachers
 * 🔐 School-scoped + club-scoped listing + secure remove with CSRF
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
$club_id = isset($_GET['club_id']) ? (int)$_GET['club_id'] : 0;
if ($club_id <= 0) {
    header("Location: view_clubs.php");
    exit;
}

// 🔍 Fetch club details SECURELY (school isolation)
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

// 🗑️ Handle Remove Student Request (POST only for security)
if (isset($_POST['remove_student'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $message[] = "❌ Invalid security token.";
        $message_type = 'error';
    } else {
        $student_id = (int)$_POST['student_id'];
        
        // ✅ Strict school + club isolation on DELETE from club_members
        $remove = $pdo->prepare("
            DELETE FROM club_members 
            WHERE club_id = ? AND student_id = ? 
            AND student_id IN (SELECT student_id FROM students WHERE school_id = ?)
            LIMIT 1
        ");
        $remove->execute([$club_id, $student_id, $school_id]);
        
        if ($remove->rowCount() > 0) {
            $message[] = "✅ Student removed from club successfully.";
            $message_type = 'success';
            // Refresh page to prevent duplicate POST on reload
            header("Location: view_club_students.php?club_id={$club_id}&msg=removed");
            exit;
        } else {
            $message[] = "❌ Student not found or already removed.";
            $message_type = 'error';
        }
    }
}

// 📊 Fetch students enrolled in THIS club for THIS school only
$students_stmt = $pdo->prepare("
    SELECT 
        s.student_id, 
        s.full_name, 
        s.email, 
        s.phone, 
        s.grade, 
        s.gender,
        cm.joined_date,
        cm.status as membership_status
    FROM students s
    INNER JOIN club_members cm ON s.student_id = cm.student_id
    WHERE cm.club_id = ? AND s.school_id = ?
    ORDER BY s.full_name ASC
");
$students_stmt->execute([$club_id, $school_id]);
$students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

// 📈 Get total count for display
$total_students = count($students);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($club['club_name']) ?> - Students | Sport Teacher Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        :root {
            --primary: #4f46e5; --primary-dark: #4338ca;
            --success: #10b981; --error: #ef4444; --warning: #f59e0b;
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --table-header: #f8fafc;
            --table-border: #e2e8f0;
            --table-hover: #f1f5f9;
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
        /* ✅ INCREASED WIDTH: 98% viewport, max 1800px */
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
        
        /* Messages */
        .message { 
            padding: 18px 22px; border-radius: 12px; margin-bottom: 20px; 
            font-weight: 500; display: flex; align-items: center; gap: 10px; 
            animation: slideIn 0.3s ease; font-size: 1.05rem;
        }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .message.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .message.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* Club Info Header */
        .club-header {
            background: white;
            border-radius: 16px;
            padding: 25px 30px;
            margin-bottom: 25px;
            box-shadow: var(--card-shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .club-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .club-title h2 {
            color: #1e293b;
            font-size: 1.8rem;
            margin: 0;
        }
        .club-meta {
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
            align-items: center;
        }
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #475569;
            font-size: 1.05rem;
        }
        .meta-item i { color: var(--primary); }
        .student-count {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 8px 18px;
            border-radius: 25px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        /* Table Container - Holds search (top-right) */
        .table-container {
            background: white;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            position: relative;
            padding-top: 65px;
        }

        /* ✅ Search Box - Top Right */
        .search-box { 
            position: absolute;
            top: 15px;
            right: 20px;
            z-index: 10;
            width: 300px; 
            flex-shrink: 0;
        }
        .search-box input { 
            width: 100%; padding: 12px 20px 12px 45px; border: 2px solid #e2e8f0; 
            border-radius: 12px; font-size: 1rem; background: white; transition: all 0.2s; 
        }
        .search-box input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79,70,229,0.15); }
        .search-box i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 1rem; }

        .table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table.students-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        table.students-table thead {
            background: var(--table-header);
            border-bottom: 2px solid var(--table-border);
        }

        /* ✅ Larger font for headers */
        table.students-table th {
            padding: 18px 22px;
            text-align: left;
            font-weight: 700;
            color: #1e293b;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ✅ Larger font for cells */
        table.students-table td {
            padding: 18px 22px;
            border-bottom: 1px solid var(--table-border);
            color: #334155;
            font-size: 1.05rem;
            vertical-align: top;
        }

        table.students-table tbody tr {
            transition: background 0.2s ease;
        }

        table.students-table tbody tr:hover {
            background: var(--table-hover);
        }

        table.students-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Column-specific styling */
        .col-actions { width: 140px; text-align: right; }
        .col-date { width: 130px; white-space: nowrap; color: #475569; font-size: 1rem; }
        .col-email { width: 220px; }
        .col-name { font-weight: 700; color: #1e293b; font-size: 1.1rem; }
        .col-phone { width: 150px; }
        .col-grade { width: 100px; text-align: center; }
        .col-gender { width: 100px; text-align: center; }
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-active { background: #dcfce7; color: #166534; }
        .status-inactive { background: #fee2e2; color: #991b1b; }

        /* Action Buttons in Table - Larger */
        .action-btn {
            padding: 10px 18px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            font-size: 1rem;
            text-decoration: none;
        }
        .remove-btn {
            background: #fee2e2;
            color: var(--error);
        }
        .remove-btn:hover { background: #fecaca; }

        /* Empty State */
        .empty-state { 
            text-align: center; padding: 70px 25px; 
            background: white; border-radius: 20px; box-shadow: var(--card-shadow); 
        }
        .empty-state i { font-size: 3.5rem; color: #94a3b8; margin-bottom: 15px; }
        .empty-state h3 { color: #334155; margin-bottom: 12px; font-size: 1.4rem; }
        .empty-state p { color: #475569; margin-bottom: 25px; font-size: 1.1rem; }
        .empty-state .btn {
            display: inline-flex;
            padding: 14px 28px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }
        .empty-state .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(79,70,229,0.4); }

        /* Back Button */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 20px;
            padding: 10px 18px;
            background: #e0e7ff;
            border-radius: 10px;
            transition: all 0.2s;
        }
        .back-link:hover { background: #c7d2fe; }

        /* Responsive for Mobile */
        @media (max-width: 768px) {
            .club-header { flex-direction: column; align-items: flex-start; }
            .club-meta { width: 100%; justify-content: space-between; }
            
            .search-box { 
                position: static;
                width: 100%;
                margin-bottom: 15px;
            }
            .table-container { padding-top: 0; }
            
            /* Stack table rows */
            table.students-table, 
            table.students-table thead, 
            table.students-table tbody, 
            table.students-table th, 
            table.students-table td, 
            table.students-table tr { display: block; }
            table.students-table thead { position: absolute; left: -9999px; top: -9999px; }
            table.students-table tr {
                margin-bottom: 15px;
                border: 1px solid var(--table-border);
                border-radius: 12px;
                background: white;
                padding: 15px;
            }
            table.students-table td {
                border: none;
                padding: 12px 15px;
                text-align: right;
                position: relative;
                padding-left: 50%;
                font-size: 1rem;
            }
            table.students-table td::before {
                content: attr(data-label);
                position: absolute;
                left: 15px;
                width: 45%;
                font-weight: 700;
                color: #1e293b;
                text-transform: uppercase;
                font-size: 0.85rem;
                letter-spacing: 0.5px;
            }
            table.students-table td.col-actions { text-align: center; padding-left: 15px; }
            table.students-table td.col-actions::before { display: none; }
            .action-btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
<?php include 'sport_teacher_header.php'; ?>

<div class="page-wrapper">
    
    <!-- Back Link -->
    <a href="view_clubs.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Clubs
    </a>
    
    <div class="header-section">
        <h1><i class="fas fa-users"></i> Club Students</h1>
        <div class="teacher-info"><i class="fas fa-user-check"></i> Logged in as: <strong><?= $teacher_name ?></strong></div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="message <?= $message_type ?>">
            <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
            <span><?= implode('<br>', array_map('htmlspecialchars', $message)) ?></span>
        </div>
    <?php endif; ?>

    <!-- Club Info Header -->
    <div class="club-header">
        <div class="club-title">
            <i class="fas fa-clipboard" style="font-size: 2rem; color: var(--primary);"></i>
            <div>
                <h2><?= htmlspecialchars($club['club_name']) ?></h2>
                <div style="color: #64748b; font-size: 1.05rem; margin-top: 4px;">
                    <i class="fas fa-bullseye"></i> <?= htmlspecialchars($club['focus_area']) ?>
                </div>
            </div>
        </div>
        <div class="club-meta">
            <div class="meta-item"><i class="far fa-calendar-alt"></i> Created: <?= date('M j, Y', strtotime($club['created_at'])) ?></div>
            <div class="student-count"><i class="fas fa-users"></i> <?= $total_students ?> Student<?= $total_students !== 1 ? 's' : '' ?></div>
        </div>
    </div>

    <?php if (empty($students)): ?>
        <!-- Empty State -->
        <div class="empty-state">
            <i class="fas fa-user-slash"></i>
            <h3>No Students Enrolled Yet</h3>
            <p>This club doesn't have any students registered. Share the club with students to start building your team!</p>
            <a href="view_clubs.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Clubs</a>
        </div>
    <?php else: ?>
        <!-- Students Table -->
        <div class="table-container">
            
            <!-- Search Box - Top Right -->
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search students...">
            </div>
            
            <div class="table-wrapper">
                <table class="students-table" id="studentsTable">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Grade</th>
                            <th>Gender</th>
                            <th>Joined</th>
                            <th>Status</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr data-name="<?= strtolower(htmlspecialchars($student['full_name'])) ?>" 
                                data-email="<?= strtolower(htmlspecialchars($student['email'] ?? '')) ?>"
                                data-grade="<?= strtolower(htmlspecialchars($student['grade'] ?? '')) ?>">
                                <td class="col-name" data-label="Student Name">
                                    <?= htmlspecialchars($student['full_name']) ?>
                                </td>
                                <td class="col-email" data-label="Email">
                                    <?= htmlspecialchars($student['email'] ?? '-') ?>
                                </td>
                                <td class="col-phone" data-label="Phone">
                                    <?= htmlspecialchars($student['phone'] ?? '-') ?>
                                </td>
                                <td class="col-grade" data-label="Grade">
                                    <?= htmlspecialchars($student['grade'] ?? '-') ?>
                                </td>
                                <td class="col-gender" data-label="Gender">
                                    <?= htmlspecialchars($student['gender'] ?? '-') ?>
                                </td>
                                <td class="col-date" data-label="Joined">
                                    <?= $student['joined_date'] ? date('M j, Y', strtotime($student['joined_date'])) : '-' ?>
                                </td>
                                <td data-label="Status">
                                    <span class="status-badge status-<?= $student['membership_status'] === 'active' ? 'active' : 'inactive' ?>">
                                        <?= htmlspecialchars($student['membership_status'] ?? 'active') ?>
                                    </span>
                                </td>
                                <td class="col-actions" data-label="Actions">
                                    <form method="POST" class="remove-form" onsubmit="return confirmRemove(this);" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="student_id" value="<?= (int)$student['student_id'] ?>">
                                        <button type="submit" name="remove_student" class="action-btn remove-btn" title="Remove from club">
                                            <i class="fas fa-user-minus"></i> Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// ✅ Confirm Remove Student Function
function confirmRemove(form) {
    return confirm('⚠️ Are you sure you want to remove this student from the club? This action can be undone by re-enrolling them.');
}

// ✅ Client-side search filter for table
document.getElementById('searchInput').addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#studentsTable tbody tr');
    
    rows.forEach(row => {
        const name = row.dataset.name;
        const email = row.dataset.email;
        const grade = row.dataset.grade;
        row.style.display = (name.includes(term) || email.includes(term) || grade.includes(term)) ? '' : 'none';
    });
});

// ✅ Auto-hide success messages
document.querySelectorAll('.message.success').forEach(msg => {
    setTimeout(() => { 
        msg.style.opacity = '0'; 
        msg.style.transition = 'opacity 0.5s ease'; 
        setTimeout(() => msg.remove(), 500); 
    }, 4000);
});

// ✅ Sidebar Toggle (from your original code)
let menuBtn = document.getElementById("menu-btn");
let sidebar = document.getElementById("sidebar");
if (menuBtn && sidebar) {
    menuBtn.addEventListener("click", function() {
        sidebar.classList.toggle("active");
    });
}
</script>
<script>
// Menu toggle
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