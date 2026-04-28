<?php
session_start();

// Ensure only Super Admin can access
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != "super_admin"){
    header("Location: super_admin.php");
    exit;
}

include "../component/connect.php";

/* ----------------- DELETE SCHOOL ----------------- */
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    $del = $pdo->prepare("DELETE FROM schools WHERE id = ?");
    $del->execute([$id]);
    header("Location: admin_view_school.php");
    exit;
}

/* ----------------- FETCH ALL SCHOOLS ----------------- */
$schools_stmt = $pdo->prepare("SELECT * FROM schools ORDER BY id DESC");
$schools_stmt->execute();
$schools = $schools_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registered Schools</title>
<link rel="stylesheet" href="../css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
<style>
   /* ===== CSS ONLY - admin_view_school.php ===== */

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
    padding:7rem 15px;
    color: #334155;
    line-height: 1.5;
    -webkit-text-size-adjust: 100%;
}

/* ===== Page Title ===== */
.registered-title {
    font-size: 1.6rem;
    font-weight: 700;
    color: #1e293b;
    margin: 10px 0 20px;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.registered-title i {
    color: #3b82f6;
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

/* ===== Table Wrapper - Horizontal Scroll Container ===== */
.table-container {
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #e2e8f0;
    margin-bottom: 25px;
    position: relative;
    z-index: 1;
}

/* ===== Table Core Styles ===== */
.table-container table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1100px; /* Forces horizontal scroll on mobile */
    table-layout: auto;
}

/* Table Header */
.table-container thead {
    background: linear-gradient(135deg, #1e293b, #334155);
    color: #fff;
}

.table-container th {
    padding: 14px 12px;
    text-align: left;
    font-weight: 600;
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    white-space: nowrap;
    border-bottom: 2px solid #475569;
}

/* Table Body */
.table-container td {
    padding: 12px 12px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.9rem;
    white-space: nowrap;
    vertical-align: middle;
}

/* Row hover effect */
.table-container tbody tr {
    transition: background 0.15s ease;
}
.table-container tbody tr:hover {
    background: #f8fafc;
}

/* Alternating row colors */
.table-container tbody tr:nth-child(even) {
    background: #fafafa;
}
.table-container tbody tr:nth-child(even):hover {
    background: #f1f5f9;
}

/* Last row border removal */
.table-container tbody tr:last-child td {
    border-bottom: none;
}

/* ===== Action Buttons ===== */
.action-buttons {
    display: flex;
    gap: 6px;
    align-items: center;
}

.btn-edit,
.btn-delete {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.2s ease;
    font-size: 0.85rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-edit {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}
.btn-edit:hover {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.btn-delete {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}
.btn-delete:hover {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

/* ===== Empty State ===== */
.no-schools {
    text-align: center;
    padding: 50px 20px;
    color: #64748b;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #e2e8f0;
    margin-bottom: 25px;
}
.no-schools i {
    font-size: 3rem;
    color: #cbd5e1;
    margin-bottom: 15px;
    display: block;
}
.no-schools p {
    margin: 8px 0;
    font-size: 1rem;
}
.no-schools p:last-child {
    font-size: 0.9rem;
    color: #94a3b8;
}

/* ===== Back Button ===== */
.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 22px;
    background: linear-gradient(135deg, #64748b, #475569);
    color: white;
    text-decoration: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    margin: 10px auto;
    box-shadow: 0 4px 14px rgba(100, 116, 139, 0.3);
    transition: transform 0.2s, box-shadow 0.2s;
}
.back-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(100, 116, 139, 0.45);
}
.back-btn:active {
    transform: translateY(0);
}

/* ===== Custom Scrollbar (Desktop) ===== */
.table-container::-webkit-scrollbar {
    height: 9px;
}
.table-container::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 5px;
}
.table-container::-webkit-scrollbar-thumb {
    background: #94a3b8;
    border-radius: 5px;
}
.table-container::-webkit-scrollbar-thumb:hover {
    background: #64748b;
}

/* ===== MOBILE OPTIMIZATIONS (≤768px) ===== */
@media (max-width: 768px) {
    body {
        padding: 10px;
    }
    
    .registered-title {
        font-size: 1.35rem;
        margin: 8px 0 15px;
        flex-wrap: wrap;
    }
    
    .scroll-hint {
        font-size: 0.8rem;
        padding: 9px 12px;
    }
    
    /* Table spacing on mobile */
    .table-container th,
    .table-container td {
        padding: 11px 10px;
        font-size: 0.85rem;
    }
    
    /* Ensure action buttons are tappable */
    .btn-edit,
    .btn-delete {
        width: 36px;
        height: 36px;
        margin: 0 2px;
    }
    
    /* Back button full width on mobile */
    .back-btn {
        width: 100%;
        max-width: 300px;
        justify-content: center;
    }
}

/* ===== EXTRA SMALL MOBILE (≤480px) ===== */
@media (max-width: 480px) {
    .table-container th,
    .table-container td {
        padding: 10px 9px;
        font-size: 0.82rem;
    }
    
    .btn-edit,
    .btn-delete {
        width: 33px;
        height: 33px;
        font-size: 0.8rem;
    }
    
    .registered-title {
        font-size: 1.2rem;
    }
    
    .scroll-hint {
        font-size: 0.75rem;
        gap: 6px;
    }
    .scroll-hint i {
        font-size: 1rem;
    }
}

/* ===== Accessibility ===== */
a:focus, button:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* Prevent horizontal scroll bleed on body */
html, body {
    max-width: 100%;
    overflow-x: hidden;
}

/* ===== Visual Scroll Edge Indicators (Optional Enhancement) ===== */
.table-container::before,
.table-container::after {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    width: 15px;
    z-index: 5;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.2s ease;
}
.table-container::before {
    left: 0;
    background: linear-gradient(to right, rgba(255,255,255,0.95), transparent);
}
.table-container::after {
    right: 0;
    background: linear-gradient(to left, rgba(255,255,255,0.95), transparent);
}
.table-container.scroll-start::before { opacity: 1; }
.table-container.scroll-end::after { opacity: 1; }

/* Hide edge indicators on large screens where scroll isn't needed */
@media (min-width: 1200px) {
    .table-container::before,
    .table-container::after {
        display: none;
    }
}
</style>
</head>
<body>

<?php include 'super_admin_header.php'; ?>

<h1 class="registered-title">
    <i class="fas fa-school" style="margin-right: 10px;"></i>
    Registered Schools
</h1>

<?php if(!empty($schools)): ?>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>School Reg. No</th>
                <th>School Name</th>
                <th>Address</th>
                <th>Region</th>
                <th>District</th>
                <th>Ward</th>
                <th>Phone</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($schools as $school): ?>
            <tr>
                <td><?= htmlspecialchars($school['id']); ?></td>
                <td><?= htmlspecialchars($school['school_id']); ?></td>
                <td><strong><?= htmlspecialchars($school['school_name']); ?></strong></td>
                <td><?= htmlspecialchars($school['address']); ?></td>
                <td><?= htmlspecialchars($school['region']); ?></td>
                <td><?= htmlspecialchars($school['district']); ?></td>
                <td><?= htmlspecialchars($school['ward']); ?></td>
                <td><?= htmlspecialchars($school['phone']); ?></td>
                <td><?= htmlspecialchars($school['created_at']); ?></td>
                <td>
                    <div class="action-buttons">
                        <a href='admin_school_page.php?edit=<?= $school['id']; ?>' class='btn-edit'>
                            <i class="fas fa-edit"></i> 
                        </a>
                        <a href='<?= $_SERVER['PHP_SELF']; ?>?delete=<?= $school['id']; ?>' 
                           class='btn-delete' 
                           onclick="return confirm('Are you sure you want to delete this school?');">
                            <i class="fas fa-trash-alt"></i> 
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="no-schools">
    <i class="fas fa-school"></i>
    <p>No schools registered yet.</p>
    <p style="font-size: 14px; margin-top: 10px;">Click the button above to add your first school.</p>
</div>
<?php endif; ?>

<!-- Back Button -->
<div style="text-align: center;">
    <a href="admin_school_page.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> register new school
    </a>
</div>

<script>
let menuBtn = document.getElementById("menu-btn");
let sidebar = document.getElementById("sidebar");

if(menuBtn && sidebar) {
    menuBtn.addEventListener("click", function() {
        sidebar.classList.toggle("active");
    });
}
</script>

</body>
</html>