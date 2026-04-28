<?php
session_start();
include "../component/connect.php";

// Ensure sport teacher is logged in
if(!isset($_SESSION['sport_teacher_id'])){
    header("Location: sport_teacher_login.php");
    exit;
}

// Get teacher info to know school_id
$teacher_id = $_SESSION['sport_teacher_id'];
$stmt = $pdo->prepare("SELECT school_id FROM sport_teachers WHERE teacher_id = ?");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$teacher || empty($teacher['school_id'])){
    die("Error: School information not found. Please contact admin.");
}

$school_id = $teacher['school_id'];

// Fetch all students in this school
$students_stmt = $pdo->prepare("SELECT * FROM students WHERE school_id = ? ORDER BY id ASC");
$students_stmt->execute([$school_id]);
$students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>View Students - Sports Teacher Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
/* ===== Reset & Base ===== */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f8fafc;
    margin: 0;
    padding: 15px;
    color: #334155;
    line-height: 1.5;
    -webkit-text-size-adjust: 100%;
}

/* ===== Title ===== */
.title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    margin: 10px 0 20px;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.title i {
    color: #3b82f6;
}

/* ===== Register Button ===== */
.register-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    text-decoration: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.95rem;
    margin: 0 auto 15px;
    box-shadow: 0 4px 14px rgba(59, 130, 246, 0.3);
    transition: transform 0.2s, box-shadow 0.2s;
}
.register-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.45);
}
.register-btn:active {
    transform: translateY(0);
}

/* ===== Scroll Hint Banner ===== */
.scroll-hint {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: linear-gradient(135deg, #dbeafe, #bfdbfe);
    color: #1e40af;
    padding: 10px 15px;
    border-radius: 10px;
    margin: 0 0 12px;
    font-size: 0.85rem;
    font-weight: 500;
    border: 1px solid #93c5fd;
    animation: hintPulse 2.5s infinite;
}
@keyframes hintPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.85; }
}
.scroll-hint i {
    font-size: 1.1rem;
}

/* ===== Table Wrapper - KEY FOR MOBILE SCROLL ===== */
.table-wrapper {
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch; /* Smooth iOS scrolling */
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #e2e8f0;
    margin-bottom: 25px;
    /* Critical: Prevent content from being cut off */
    position: relative;
    z-index: 1;
}

/* ===== Table Core Styles ===== */
.student-table {
    width: 100%;
    border-collapse: collapse;
    /* CRITICAL: Force table to be wider than viewport on mobile */
    min-width: 1000px;
    table-layout: auto;
}

/* Header */
.student-table thead {
    background: linear-gradient(135deg, #1e293b, #334155);
    color: #fff;
}

.student-table th {
    padding: 14px 12px;
    text-align: left;
    font-weight: 600;
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    white-space: nowrap;
    border-bottom: 2px solid #475569;
}

/* Body */
.student-table td {
    padding: 12px 12px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.9rem;
    white-space: nowrap;
    vertical-align: middle;
}

/* Row hover effect */
.student-table tbody tr {
    transition: background 0.15s ease;
}
.student-table tbody tr:hover {
    background: #f8fafc;
}

/* Alternating rows */
.student-table tbody tr:nth-child(even) {
    background: #fafafa;
}
.student-table tbody tr:nth-child(even):hover {
    background: #f1f5f9;
}

/* Last row border */
.student-table tbody tr:last-child td {
    border-bottom: none;
}

/* ===== Action Buttons ===== */
.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 8px;
    text-decoration: none;
    margin: 0 3px;
    transition: all 0.2s ease;
    font-size: 0.85rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.action-btn.edit {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}
.action-btn.edit:hover {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.action-btn.delete {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}
.action-btn.delete:hover {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

/* ===== Empty State ===== */
.empty-state {
    text-align: center;
    padding: 45px 20px;
    color: #64748b;
}
.empty-state i {
    font-size: 2.8rem;
    color: #cbd5e1;
    margin-bottom: 12px;
    display: block;
}
.empty-state p {
    margin: 6px 0;
    font-size: 0.95rem;
}
.empty-state a {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    color: #27ae60;
    text-decoration: none;
    margin-top: 12px;
}
.empty-state a:hover {
    text-decoration: underline;
}

/* ===== Custom Scrollbar (Desktop) ===== */
.table-wrapper::-webkit-scrollbar {
    height: 9px;
}
.table-wrapper::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 5px;
}
.table-wrapper::-webkit-scrollbar-thumb {
    background: #94a3b8;
    border-radius: 5px;
}
.table-wrapper::-webkit-scrollbar-thumb:hover {
    background: #64748b;
}

/* ===== MOBILE OPTIMIZATIONS ===== */
@media (max-width: 768px) {
    body {
        padding: 10px;
    }
    
    .title {
        font-size: 1.3rem;
        margin: 8px 0 15px;
    }
    
    .register-btn {
        width: 100%;
        max-width: 320px;
        padding: 11px 20px;
        font-size: 0.9rem;
    }
    
    .scroll-hint {
        font-size: 0.8rem;
        padding: 9px 12px;
    }
    
    /* Table spacing on mobile */
    .student-table th,
    .student-table td {
        padding: 11px 10px;
        font-size: 0.86rem;
    }
    
    /* Ensure action buttons are tappable */
    .action-btn {
        width: 36px;
        height: 36px;
        margin: 0 4px;
    }
    
    /* Email links */
    .student-table td a[href^="mailto:"] {
        color: #2563eb;
        text-decoration: none;
        word-break: break-all;
    }
}

/* ===== Extra Small Mobile (≤480px) ===== */
@media (max-width: 480px) {
    .student-table th,
    .student-table td {
        padding: 10px 9px;
        font-size: 0.83rem;
    }
    
    .action-btn {
        width: 33px;
        height: 33px;
        font-size: 0.8rem;
    }
    
    .title {
        font-size: 1.2rem;
    }
}

/* ===== Accessibility ===== */
a:focus, button:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* Prevent horizontal scroll bleed */
html, body {
    max-width: 100%;
    overflow-x: hidden;
}
    </style>
</head>
<body>

<?php include 'sport_teacher_header.php'; ?>

<h1 class="title"><i class="fas fa-users"></i> Students List</h1>

<div style="text-align: center;">
    <a href="sport_teacher_student_registration.php" class="register-btn">
        <i class="fas fa-plus"></i> Register New Student
    </a>
</div>

<!-- Mobile scroll hint -->
<div class="scroll-hint">
    <i class="fas fa-arrows-left-right"></i>
    <span>Swipe table horizontally to see all details</span>
    <i class="fas fa-arrows-left-right"></i>
</div>

<!-- Table Container with Horizontal Scroll -->
<div class="table-wrapper">
    <table class="student-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Club</th>
                <th>Talent</th>
                <th>Age</th>
                <th>Standard</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Username</th>
                <th>School ID</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($students)): ?>
                <?php foreach($students as $student): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($student['id']); ?></strong></td>
                    <td><?php echo htmlspecialchars($student['student_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($student['club'] ?? 'Not Assigned'); ?></td>
                    <td><?php echo htmlspecialchars($student['talent'] ?? 'No Talent'); ?></td>
                    <td><?php echo htmlspecialchars($student['age'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($student['standard'] ?? $student['class'] ?? 'N/A'); ?></td>
                    <td>
                        <?php if(!empty($student['email'])): ?>
                            <a href="mailto:<?php echo htmlspecialchars($student['email']); ?>">
                                <?php echo htmlspecialchars($student['email']); ?>
                            </a>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($student['username'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($student['school_id']); ?></td>
                    <td>
                        <a href="edit_student.php?id=<?php echo (int)$student['id']; ?>" class="action-btn edit" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="delete_student.php?id=<?php echo (int)$student['id']; ?>" 
                           class="action-btn delete" 
                           title="Delete"
                           onclick="return confirm('Are you sure you want to delete this student? This action cannot be undone.');">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="11">
                        <div class="empty-state">
                            <i class="fas fa-users-slash"></i>
                            <p><strong>No students found</strong> in your school.</p>
                            <a href="sport_teacher_student_registration.php">
                                <i class="fas fa-plus-circle"></i> Register your first student
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ===== Menu Toggle =====
    const menuBtn = document.getElementById("menu-btn");
    const sidebar = document.getElementById("sidebar");
    
    if (menuBtn && sidebar) {
        menuBtn.addEventListener("click", function(e) {
            e.stopPropagation();
            sidebar.classList.toggle("active");
        });
        
        // Close sidebar on outside click (mobile)
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768 && 
                sidebar.classList.contains('active') && 
                !sidebar.contains(e.target) && 
                !menuBtn.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });
    }
    
    // ===== Horizontal Scroll Enhancement =====
    const tableWrapper = document.querySelector('.table-wrapper');
    
    if (tableWrapper) {
        // Touch swipe support for smoother mobile scrolling
        let touchStartX = 0;
        let touchEndX = 0;
        
        tableWrapper.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        
        tableWrapper.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, { passive: true });
        
        function handleSwipe() {
            const threshold = 40;
            const diff = touchStartX - touchEndX;
            
            if (Math.abs(diff) > threshold) {
                const scrollAmount = diff > 0 ? 200 : -200;
                tableWrapper.scrollBy({ 
                    left: scrollAmount, 
                    behavior: 'smooth' 
                });
            }
        }
        
        // Visual feedback on scroll
        tableWrapper.addEventListener('scroll', function() {
            // Optional: Add shadow effects based on scroll position
            if (this.scrollLeft > 0) {
                this.style.boxShadow = '0 6px 25px rgba(0,0,0,0.12)';
            } else {
                this.style.boxShadow = '0 4px 20px rgba(0,0,0,0.08)';
            }
        });
    }
    
    // ===== Button Touch Feedback =====
    document.querySelectorAll('.action-btn, .register-btn').forEach(btn => {
        btn.addEventListener('touchstart', function() {
            this.style.opacity = '0.85';
        });
        btn.addEventListener('touchend', function() {
            this.style.opacity = '1';
        });
    });
    
});
</script>


   <script>
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