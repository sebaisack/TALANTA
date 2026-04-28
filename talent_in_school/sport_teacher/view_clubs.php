<?php
/**
 * View Registered Clubs for Sport Teachers - TABLE LAYOUT
 * 🔐 School-scoped listing + secure delete with CSRF
 * ✅ "Students" button redirects to view_student.php?club_id=XXX
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

// 🗑️ Handle Delete Request (POST only for security)
if (isset($_POST['delete_club'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $message[] = "❌ Invalid security token.";
        $message_type = 'error';
    } else {
        $club_id = (int)$_POST['club_id'];
        
        // ✅ Strict school isolation on DELETE
        $delete = $pdo->prepare("DELETE FROM clubs WHERE id = ? AND school_id = ? LIMIT 1");
        $delete->execute([$club_id, $school_id]);
        
        if ($delete->rowCount() > 0) {
            $message[] = "✅ Club deleted successfully.";
            $message_type = 'success';
            header("Location: view_clubs.php?msg=deleted");
            exit;
        } else {
            $message[] = "❌ Club not found or access denied.";
            $message_type = 'error';
        }
    }
}

// 📊 Fetch clubs for THIS school only
$clubs_stmt = $pdo->prepare("
    SELECT id, club_name, focus_area, activities, created_at 
    FROM clubs 
    WHERE school_id = ? 
    ORDER BY created_at DESC
");
$clubs_stmt->execute([$school_id]);
$clubs = $clubs_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Clubs | Sport Teacher Portal</title>
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

        .table-container {
            background: white;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            position: relative;
            padding-top: 65px;
        }

        /* ✅ Register Clubs Button - Positioned at TOP-RIGHT inside table container */
        .view-btn {
            position: absolute;
            top: 15px;
            right: 20px;
            z-index: 10;
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
            transition: all 0.3s ease;
        }
        .view-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
        }
        .view-btn i {
            font-size: 1rem;
        }

        .table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table.clubs-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        table.clubs-table thead {
            background: var(--table-header);
            border-bottom: 2px solid var(--table-border);
        }

        table.clubs-table th {
            padding: 18px 22px;
            text-align: left;
            font-weight: 700;
            color: #1e293b;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table.clubs-table td {
            padding: 18px 22px;
            border-bottom: 1px solid var(--table-border);
            color: #334155;
            font-size: 1.05rem;
            vertical-align: top;
        }

        table.clubs-table tbody tr { transition: background 0.2s ease; }
        table.clubs-table tbody tr:hover { background: var(--table-hover); }
        table.clubs-table tbody tr:last-child td { border-bottom: none; }

        .col-actions { width: 240px; text-align: right; }
        .col-date { width: 140px; white-space: nowrap; color: #475569; font-size: 1rem; }
        .col-focus { width: 240px; }
        .col-name { font-weight: 700; color: #1e293b; font-size: 1.1rem; }
        .col-activities { max-width: 400px; }
        .activities-text {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.6;
        }

        .action-group { display: flex; gap: 10px; justify-content: flex-end; }
        .action-btn {
            padding: 10px 16px;
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
        .students-btn { background: #dbeafe; color: var(--primary); }
        .students-btn:hover { background: #bfdbfe; }
        .edit-btn { background: #e0e7ff; color: var(--primary); }
        .edit-btn:hover { background: #c7d2fe; }
        .delete-btn { background: #fee2e2; color: var(--error); }
        .delete-btn:hover { background: #fecaca; }

        .empty-state { 
            text-align: center; padding: 70px 25px; 
            background: white; border-radius: 20px; box-shadow: var(--card-shadow); 
        }
        .empty-state i { font-size: 3.5rem; color: #94a3b8; margin-bottom: 15px; }
        .empty-state h3 { color: #334155; margin-bottom: 12px; font-size: 1.4rem; }
        .empty-state p { color: #475569; margin-bottom: 25px; font-size: 1.1rem; }

        /* ✅ Mobile Responsive - Stack button on small screens */
        @media (max-width: 768px) {
            .view-btn {
                position: static;
                display: block;
                margin: 0 auto 15px;
                width: fit-content;
            }
            
            .table-container { padding-top: 0; }
            
            table.clubs-table, table.clubs-table thead, table.clubs-table tbody, 
            table.clubs-table th, table.clubs-table td, table.clubs-table tr { display: block; }
            table.clubs-table thead { position: absolute; left: -9999px; top: -9999px; }
            table.clubs-table tr {
                margin-bottom: 15px;
                border: 1px solid var(--table-border);
                border-radius: 12px;
                background: white;
                padding: 15px;
            }
            table.clubs-table td {
                border: none;
                padding: 12px 15px;
                text-align: right;
                position: relative;
                padding-left: 50%;
                font-size: 1rem;
            }
            table.clubs-table td::before {
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
            table.clubs-table td.col-actions { text-align: center; padding-left: 15px; }
            table.clubs-table td.col-actions::before { display: none; }
            .action-group { justify-content: center; flex-wrap: wrap; }
            .col-activities { max-width: 100%; }
        }

        .focus-badge {
            display: inline-block;
            background: #f1f5f9;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.95rem;
            color: #1e293b;
            font-weight: 600;
        }
    </style>
</head>
<body>
<?php include 'sport_teacher_header.php'; ?>

<div class="page-wrapper">
    <div class="header-section">
        <h1><i class="fas fa-clipboard-list"></i> My Registered Clubs</h1>
        <div class="teacher-info"><i class="fas fa-user-check"></i> Logged in as: <strong><?= $teacher_name ?></strong></div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="message <?= $message_type ?>">
            <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
            <span><?= implode('<br>', array_map('htmlspecialchars', $message)) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($clubs)): ?>
    <div class="table-container">
        
        <!-- ✅ Register Clubs Button - Now positioned at TOP-RIGHT via CSS -->
        <a href="sport_teacher_register_clubs.php" class="view-btn">
            <i class="fas fa-plus"></i> Register Clubs
        </a>

        <div class="table-wrapper">
            <table class="clubs-table" id="clubsTable">
                <thead>
                    <tr>
                        <th>Club Name</th>
                        <th>Focus Area</th>
                        <th>Activities</th>
                        <th>Date Added</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clubs as $club): ?>
                        <tr data-name="<?= strtolower(htmlspecialchars($club['club_name'])) ?>" 
                            data-focus="<?= strtolower(htmlspecialchars($club['focus_area'])) ?>">
                            <td class="col-name" data-label="Club Name">
                                <?= htmlspecialchars($club['club_name']) ?>
                            </td>
                            <td class="col-focus" data-label="Focus Area">
                                <span class="focus-badge"><?= htmlspecialchars($club['focus_area']) ?></span>
                            </td>
                            <td class="col-activities" data-label="Activities">
                                <div class="activities-text">
                                    <?= htmlspecialchars($club['activities'] ?? 'No activities specified.') ?>
                                </div>
                            </td>
                            <td class="col-date" data-label="Date Added">
                                <?= date('M j, Y', strtotime($club['created_at'])) ?>
                            </td>
                            <td class="col-actions" data-label="Actions">
                                <div class="action-group">
                                    <!-- ✅ Students Button - REDIRECTS to view_student.php?club=XXX -->
                                    <a href="view_student.php?club=<?= urlencode($club['club_name']) ?>" 
                                    class="action-btn students-btn" 
                                    title="View students in <?= htmlspecialchars($club['club_name']) ?>">
                                        <i class="fas fa-users"></i> Students
                                    </a>
                                    
                                    <!-- ✅ Edit Button -->
                                    <a href="edit_club.php?id=<?= (int)$club['id'] ?>" class="action-btn edit-btn">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                   
                                    <!-- ✅ Delete Form -->
                                    <form method="POST" class="delete-form" onsubmit="return confirmDelete(this);" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="club_id" value="<?= (int)$club['id'] ?>">
                                        <button type="submit" name="delete_club" class="action-btn delete-btn">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
        <div class="table-container">
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <h3>No Clubs Registered Yet</h3>
                <p>Start by registering your first sports or activity club for your school.</p>
                <a href="sport_teacher_register_clubs.php" class="action-btn students-btn" style="padding:14px 28px;font-size:1.1rem;margin-top:15px;">
                    <i class="fas fa-plus"></i> Add Your First Club
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// ✅ Confirm Delete Function
function confirmDelete(form) {
    return confirm('⚠️ Are you sure you want to delete this club? This action cannot be undone.');
}

// ✅ Client-side search filter for table (if search box is added later)
// document.getElementById('searchInput')?.addEventListener('input', function(e) {
//     const term = e.target.value.toLowerCase();
//     const rows = document.querySelectorAll('#clubsTable tbody tr');
//     rows.forEach(row => {
//         const name = row.dataset.name;
//         const focus = row.dataset.focus;
//         row.style.display = (name.includes(term) || focus.includes(term)) ? '' : 'none';
//     });
// });

// ✅ Auto-hide success messages
document.querySelectorAll('.message.success').forEach(msg => {
    setTimeout(() => { 
        msg.style.opacity = '0'; 
        msg.style.transition = 'opacity 0.5s ease'; 
        setTimeout(() => msg.remove(), 500); 
    }, 4000);
});

// ✅ Sidebar Toggle
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