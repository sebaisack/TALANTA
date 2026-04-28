<?php
session_start();
$message = [];

// Ensure only Super Admin can access
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != "super_admin"){
    header("Location: super_admin.php");
    exit;
}

// Include PDO connection
include "../component/connect.php";

function countTable($pdo, $table){
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table`");
        $stmt->execute();
        return $stmt->fetchColumn();
    } catch(PDOException $e) {
        return 0;   // Return 0 if table doesn't exist
    }
}

// Safe counting with fallback to 0
$school_count          = countTable($pdo, "schools");
$ward_count            = countTable($pdo, "ward_managers");
$district_count        = countTable($pdo, "district_managers");
$regional_count        = countTable($pdo, "regional_managers");
$head_teacher_count    = countTable($pdo, "head_teachers");
$sport_teacher_count   = countTable($pdo, "sport_teachers");
$talent_count          = countTable($pdo, "talents");
$club_count            = countTable($pdo, "clubs");
$student_count         = countTable($pdo, "students");
$assistant_admin_count = countTable($pdo, "assistant_admins");
$admin_count           = countTable($pdo, "admins");
$parent_count          = countTable($pdo, "parents");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    
</head>
<body>

    <?php include 'super_admin_header.php'; ?>

    <h1 class="title">Dashboard</h1>

    <div class="dashboard">

        <!-- School -->
        <div class="card">
            <h3><?= $school_count; ?></h3>
            <p>Schools</p>
            <a href="admin_school_page.php" class="btn">View</a>
        </div>

        <!-- Sport Teacher -->
        <div class="card">
            <h3><?= $sport_teacher_count; ?></h3>
            <p>Sport Teachers</p>
            <a href="admin_sporty_teacher.php" class="btn">View</a>
        </div>

        <!-- Talent -
        <div class="card">
            <h3><?= $talent_count; ?></h3>
            <p>Talents</p>
            <a href="manage_talents.php" class="btn">View</a>
        </div>
-->

         <!-- District Manager -->
        <div class="card">
            <h3><?= $district_count; ?></h3>
            <p>District Managers</p>
            <a href="manage_district_managers.php" class="btn">View</a>
        </div>
        <!-- Head Teacher -->
        <div class="card">
            <h3><?= $head_teacher_count; ?></h3>
            <p>Head Teachers</p>
            <a href="super_admin_header_teacher.php" class="btn">View</a>
        </div>

        <!-- Club 
        <div class="card">
            <h3><?= $club_count; ?></h3>
            <p>Clubs</p>
            <a href="manage_clubs.php" class="btn">View</a>
        </div>-->

        <!-- Student -->
        <div class="card">
            <h3><?= $student_count; ?></h3>
            <p>Students</p>
            <a href="manage_students.php" class="btn">View</a>
        </div>

        <!-- Ward Manager -->
        <div class="card">
            <h3><?= $ward_count; ?></h3>
            <p>Ward Managers</p>
            <a href="manage_ward_managers.php" class="btn">View</a>
        </div>

       

        <!-- Regional Manager -->
        <div class="card">
            <h3><?= $regional_count; ?></h3>
            <p>Regional Managers</p>
            <a href="manage_regional_managers.php" class="btn">View</a>
        </div>

        <!-- Assistant Admin -->
        <div class="card">
            <h3><?= $assistant_admin_count; ?></h3>
            <p>Assistant Admins</p>
            <a href="manage_assistant_admins.php" class="btn">View</a>
        </div>

        <!-- Admin -->
        <div class="card">
            <h3><?= $admin_count; ?></h3>
            <p>Admins</p>
            <a href="manage_admins.php" class="btn">View</a>
        </div>

        <!-- Parent -->
        <div class="card">
            <h3><?= $parent_count; ?></h3>
            <p>Parents</p>
            <a href="manage_parents.php" class="btn">View</a>
        </div>

    </div>

    <script>
        // Safe sidebar toggle
        let menuBtn = document.getElementById("menu-btn");
        let sidebar = document.getElementById("sidebar");

        if (menuBtn && sidebar) {
            menuBtn.addEventListener("click", function () {
                sidebar.classList.toggle("active");
            });
        }
    </script>
</body>
</html>