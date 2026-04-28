<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != "super_admin"){
    header("Location: super_admin.php");
    exit;
}

include "../component/connect.php";

/* ----------------- DELETE SPORT TEACHER ----------------- */
if(isset($_GET['delete'])){
    $id = $_GET['delete'];

    $del = $pdo->prepare("
        DELETE FROM sport_teachers
        WHERE id = ?
    ");

    $del->execute([(int)$id]);

    header("Location: admin_view_sporty_teacher.php");
    exit;
}

/* ----------------- FETCH ALL SPORT TEACHERS ----------------- */
$stmt = $pdo->prepare("
    SELECT *
    FROM sport_teachers
    ORDER BY id DESC
");

$stmt->execute();

$sport_teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Registered Sport Teachers</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
/* ===== Reset & Base ===== */
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f8fafc;margin:0;padding:7rem 15px;color:#334155;line-height:1.5;-webkit-text-size-adjust:100%}

/* ===== Page Title ===== */
.registered-title{font-size:1.6rem;font-weight:700;color:#1e293b;margin:10px 0 20px;text-align:center;display:flex;align-items:center;justify-content:center;gap:10px}
.registered-title i{color:#3b82f6}

/* ===== Scroll Hint Banner ===== */
.scroll-hint{display:flex;align-items:center;justify-content:center;gap:10px;background:linear-gradient(135deg,#dbeafe,#bfdbfe);color:#1e40af;padding:10px 15px;border-radius:10px;margin:0 0 12px;font-size:0.85rem;font-weight:500;border:1px solid #93c5fd;animation:hintPulse 2.5s infinite}
@keyframes hintPulse{0%,100%{opacity:1}50%{opacity:0.85}}
.scroll-hint i{font-size:1.1rem}

/* ===== Table Wrapper - Horizontal Scroll Container ===== */
.table-container{width:100%;overflow-x:auto;overflow-y:hidden;-webkit-overflow-scrolling:touch;background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,0.08);border:1px solid #e2e8f0;margin-bottom:25px;position:relative;z-index:1}

/* ===== Table Core Styles ===== */
.table-container table{width:100%;border-collapse:collapse;min-width:1400px;table-layout:auto}
.table-container thead{background:linear-gradient(135deg,#1e293b,#334155);color:#fff}
.table-container th{padding:14px 12px;text-align:left;font-weight:600;font-size:0.9rem;text-transform:uppercase;letter-spacing:0.3px;white-space:nowrap;border-bottom:2px solid #475569}
.table-container td{padding:12px 12px;border-bottom:1px solid #f1f5f9;font-size:1.01rem;white-space:nowrap;vertical-align:middle}
.table-container tbody tr{transition:background 0.15s ease}
.table-container tbody tr:hover{background:#f8fafc}
.table-container tbody tr:nth-child(even){background:#fafafa}
.table-container tbody tr:nth-child(even):hover{background:#f1f5f9}
.table-container tbody tr:last-child td{border-bottom:none}

/* ===== Action Buttons ===== */
.btn-delete{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:8px 14px;background:linear-gradient(135deg,#ef4444,#dc2626);color:white;text-decoration:none;border-radius:8px;font-size:0.82rem;font-weight:500;transition:all 0.2s ease;box-shadow:0 2px 4px rgba(0,0,0,0.1);white-space:nowrap}
.btn-delete:hover{background:linear-gradient(135deg,#dc2626,#b91c1c);transform:translateY(-2px);box-shadow:0 4px 12px rgba(239,68,68,0.4)}
.btn-delete i{font-size:0.9rem}

/* ===== Empty State ===== */
.no-teachers{text-align:center;padding:50px 20px;color:#64748b;background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,0.08);border:1px solid #e2e8f0;margin-bottom:25px}
.no-teachers i{font-size:3rem;color:#cbd5e1;margin-bottom:15px;display:block}
.no-teachers p{margin:8px 0;font-size:1rem}
.no-teachers p:last-child{font-size:0.9rem;color:#94a3b8}

/* ===== Back Button ===== */
.back-btn{display:inline-flex;align-items:center;gap:8px;padding:11px 22px;background:linear-gradient(135deg,#64748b,#475569);color:white;text-decoration:none;border-radius:10px;font-weight:600;font-size:0.9rem;margin:10px auto;box-shadow:0 4px 14px rgba(100,116,139,0.3);transition:transform 0.2s,box-shadow 0.2s}
.back-btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(100,116,139,0.45)}
.back-btn:active{transform:translateY(0)}

/* ===== Custom Scrollbar (Desktop) ===== */
.table-container::-webkit-scrollbar{height:9px}
.table-container::-webkit-scrollbar-track{background:#f1f5f9;border-radius:5px}
.table-container::-webkit-scrollbar-thumb{background:#94a3b8;border-radius:5px}
.table-container::-webkit-scrollbar-thumb:hover{background:#64748b}

/* ===== MOBILE OPTIMIZATIONS (≤768px) ===== */
@media (max-width:768px){
    body{padding:10px}
    .registered-title{font-size:1.35rem;margin:8px 0 15px;flex-wrap:wrap}
    .scroll-hint{font-size:0.8rem;padding:9px 12px}
    .table-container th,.table-container td{padding:11px 9px;font-size:1.01rem}
    .btn-delete{padding:7px 12px;font-size:0.8rem;gap:4px}
    .btn-delete i{font-size:0.85rem}
    .back-btn{width:100%;max-width:280px;justify-content:center}
}

/* ===== EXTRA SMALL MOBILE (≤480px) ===== */
@media (max-width:480px){
    .table-container th,.table-container td{padding:10px 8px;font-size:0.8rem}
    .btn-delete{padding:6px 10px;font-size:0.78rem}
    .registered-title{font-size:1.2rem}
    .scroll-hint{font-size:0.75rem;gap:6px}
    .scroll-hint i{font-size:1rem}
}

/* ===== Accessibility ===== */
a:focus,button:focus{outline:2px solid #3b82f6;outline-offset:2px}
html,body{max-width:100%;overflow-x:hidden}

/* ===== Visual Scroll Edge Indicators ===== */
.table-container::before,.table-container::after{content:'';position:absolute;top:0;bottom:0;width:15px;z-index:5;pointer-events:none;opacity:0;transition:opacity 0.2s ease}
.table-container::before{left:0;background:linear-gradient(to right,rgba(255,255,255,0.95),transparent)}
.table-container::after{right:0;background:linear-gradient(to left,rgba(255,255,255,0.95),transparent)}
.table-container.scroll-start::before{opacity:1}
.table-container.scroll-end::after{opacity:1}
@media (min-width:1400px){.table-container::before,.table-container::after{display:none}}

/* ===== SIDEBAR & MENU ICON - FULL SCREEN ===== */
#menu-btn{cursor:pointer;padding:8px 12px;border-radius:8px;transition:background 0.2s ease,transform 0.15s ease;display:flex;align-items:center;justify-content:center}
#menu-btn:hover{background:rgba(59,130,246,0.15);transform:scale(1.05)}
#menu-btn:active{transform:scale(0.98)}
#menu-btn i{font-size:1.3rem;color:#334155;transition:color 0.2s}
#menu-btn:hover i{color:#2563eb}
#user-btn{cursor:pointer;padding:8px 12px;border-radius:8px;transition:background 0.2s ease,transform 0.15s ease;display:flex;align-items:center;justify-content:center}
#user-btn:hover{background:rgba(59,130,246,0.15);transform:scale(1.05)}
#user-btn i{font-size:1.3rem;color:#334155}
.icon-bar{display:flex;align-items:center;gap:8px}
.sidebar{position:fixed;top:0;left:-280px;width:280px;height:100vh;background:#fff;box-shadow:4px 0 25px rgba(0,0,0,0.18);z-index:1000;transition:left 0.35s cubic-bezier(0.4,0,0.2,1);overflow-y:auto;overflow-x:hidden;padding:20px 0}
.sidebar.active{left:0}
.sidebar-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.45);z-index:999;opacity:0;visibility:hidden;transition:opacity 0.3s ease,visibility 0.3s ease;backdrop-filter:blur(2px)}
.sidebar-overlay.active{opacity:1;visibility:visible}
.sidebar .nav-links{list-style:none;padding:0;margin:0}
.sidebar .nav-links li{border-bottom:1px solid #f1f5f9}
.sidebar .nav-links li:last-child{border-bottom:none}
.sidebar .nav-links a{display:flex;align-items:center;gap:14px;padding:15px 26px;text-decoration:none;color:#334155;font-weight:500;font-size:0.98rem;transition:all 0.25s ease;position:relative}
.sidebar .nav-links a::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:transparent;transition:background 0.2s}
.sidebar .nav-links a:hover,.sidebar .nav-links a.active{background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;padding-left:30px}
.sidebar .nav-links a:hover::before,.sidebar .nav-links a.active::before{background:#fff}
.sidebar .nav-links a i{font-size:1.15rem;width:22px;text-align:center;flex-shrink:0}
.sidebar-close{position:absolute;top:12px;right:12px;background:#f1f5f9;border:none;font-size:1.3rem;color:#64748b;cursor:pointer;width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;transition:all 0.2s ease}
.sidebar-close:hover{background:#ef4444;color:#fff;transform:rotate(90deg)}
body.sidebar-open{overflow:hidden}
@media (max-width:480px){.sidebar{width:260px}.sidebar .nav-links a{padding:13px 22px;font-size:0.93rem;gap:12px}.sidebar .nav-links a i{font-size:1.05rem}}
    </style>
</head>
<body>

<?php include 'super_admin_header.php'; ?>

<!-- Sidebar Overlay (for close on tap outside) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<h1 class="registered-title">
    <i class="fas fa-chalkboard-teacher"></i> Registered Sport Teachers
</h1>

<!-- Mobile scroll hint -->
<div class="scroll-hint">
    <i class="fas fa-arrows-left-right"></i>
    <span>Swipe table horizontally to see all details</span>
    <i class="fas fa-arrows-left-right"></i>
</div>

<?php if(!empty($sport_teachers)): ?>
<div class="table-container" id="teacherTable">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Teacher ID</th>
                <th>Username</th>
                <th>Full Name</th>
                <th>Gender</th>
                <th>Phone</th>
                <th>Email</th>
                <th>School Reg. No</th>
                <th>School Name</th>
                <th>Region</th>
                <th>District</th>
                <th>Ward</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($sport_teachers as $teacher): ?>
            <tr>
                <td><strong><?= (int)$teacher['id']; ?></strong></td>
                <td><?= htmlspecialchars($teacher['teacher_id']); ?></td>
                <td><?= htmlspecialchars($teacher['username']); ?></td>
                <td><?= htmlspecialchars($teacher['full_name']); ?></td>
                <td><?= htmlspecialchars($teacher['gender']); ?></td>
                <td><?= htmlspecialchars($teacher['phone']); ?></td>
                <td>
                    <a href="mailto:<?= htmlspecialchars($teacher['email']); ?>" style="color:#2563eb;text-decoration:none;">
                        <?= htmlspecialchars($teacher['email']); ?>
                    </a>
                </td>
                <td><?= htmlspecialchars($teacher['school_id']); ?></td>
                <td><?= htmlspecialchars($teacher['school_name']); ?></td>
                <td><?= htmlspecialchars($teacher['region']); ?></td>
                <td><?= htmlspecialchars($teacher['district']); ?></td>
                <td><?= htmlspecialchars($teacher['ward']); ?></td>
                <td><?= htmlspecialchars($teacher['created_at']); ?></td>
                <td>
                    <a href='<?= $_SERVER['PHP_SELF']; ?>?delete=<?= (int)$teacher['id']; ?>'
                       class='btn-delete'
                       onclick="return confirm('Are you sure you want to delete this Sport Teacher? This action cannot be undone.');">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="no-teachers">
    <i class="fas fa-chalkboard-teacher"></i>
    <p>No Sport Teachers registered yet.</p>
    <p style="font-size: 14px; margin-top: 10px;">Use the registration form to add your first sport teacher.</p>
</div>
<?php endif; ?>

<!-- BACK BUTTON -->
<div style="text-align: center;">
    <a href="admin_sporty_teacher.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back
    </a>
</div>

<script>
// ===== MENU ICON FUNCTIONALITY - FULL SCREEN =====
document.addEventListener('DOMContentLoaded', function() {
    const menuBtn = document.getElementById("menu-btn");
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("sidebarOverlay");
    const closeBtn = document.querySelector(".sidebar-close");
    
    // ===== Toggle Sidebar on Menu Button Click (ALL SCREENS) =====
    function toggleSidebar() {
        sidebar.classList.toggle("active");
        overlay.classList.toggle("active");
        document.body.classList.toggle("sidebar-open");
    }
    
    if(menuBtn) {
        menuBtn.addEventListener("click", function(e) {
            e.stopPropagation();
            toggleSidebar();
        });
    }
    
    // ===== Close Sidebar via Overlay Click =====
    if(overlay) {
        overlay.addEventListener("click", function() {
            sidebar.classList.remove("active");
            overlay.classList.remove("active");
            document.body.classList.remove("sidebar-open");
        });
    }
    
    // ===== Close Sidebar via Close Button =====
    if(closeBtn) {
        closeBtn.addEventListener("click", function(e) {
            e.stopPropagation();
            sidebar.classList.remove("active");
            overlay.classList.remove("active");
            document.body.classList.remove("sidebar-open");
        });
    }
    
    // ===== Close Sidebar on Escape Key =====
    document.addEventListener("keydown", function(e) {
        if(e.key === "Escape" && sidebar.classList.contains("active")) {
            sidebar.classList.remove("active");
            overlay.classList.remove("active");
            document.body.classList.remove("sidebar-open");
        }
    });
    
    // ===== Prevent Sidebar Content Click from Closing =====
    if(sidebar) {
        sidebar.addEventListener("click", function(e) {
            e.stopPropagation();
        });
    }
});
</script>

</body>
</html>