<?php
session_start();
$message = [];

// Ensure only Super Admin can access
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != "super_admin"){
    header("Location: super_admin_login.php");
    exit;
}

// Include PDO connection
include "../component/connect.php";




function countTable($pdo, $table){

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table");
    $stmt->execute();

    return $stmt->fetchColumn();

}


$school_count = countTable($pdo, "schools");
$ward_count = countTable($pdo, "ward_managers");
$district_count = countTable($pdo, "district_managers");
$regional_count = countTable($pdo, "regional_managers");
$head_teacher_count = countTable($pdo, "head_teachers");
$sport_teacher_count = countTable($pdo, "sport_teachers");
$talent_count = countTable($pdo, "talents");
$club_count = countTable($pdo, "clubs");
$student_count = countTable($pdo, "students");
$assistant_admin_count = countTable($pdo, "assistant_admins");
$admin_count = countTable($pdo, "admins");
$parent_count = countTable($pdo, "parents");


?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Super Admin Registration Dashboard</title>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
<link rel="stylesheet" href="../css/admin.css">

</head>
<body>

<?php include 'sport_teacher_header.php'; ?>



<script>

// select elements
let menuBtn = document.getElementById("menu-btn");
let sidebar = document.getElementById("sidebar");

// toggle sidebar
menuBtn.addEventListener("click", function() {
    sidebar.classList.toggle("active");
});




</script>
</body>
</html>