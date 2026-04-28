<?php
session_start();
include "../component/connect.php";

/* ===============================
   ENSURE SUPER ADMIN LOGIN
   =============================== */
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != "super_admin"){
    header("Location: login_admin.php");
    exit;
}

$message = [];


?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ward managert</title>
<link rel="stylesheet" href="../css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">



</head>
<body>

<?php include 'super_admin_header.php'; ?>


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