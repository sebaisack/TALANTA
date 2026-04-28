<?php
session_start();
include "../component/connect.php";

// Ensure student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'] ?? 'Student';

// Fetch the logged-in student's club information from students table
$stmt = $pdo->prepare("
    SELECT id, student_name, club, talent 
    FROM students 
    WHERE id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    header("Location: student_login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Clubs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">

    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f0f4f8, #d9e2ec);
            margin: 0;
            padding: 6rem 20px;
            min-height: 100vh;
        }

        .page-title {
            text-align: center;
            color: #2c3e50;
            font-size: 2.3rem;
            margin: 30px 0 40px;
        }

        .club-card {
            background: white;
            border-radius: 16px;
            padding: 35px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
            max-width: 900px;
            margin: 0 auto;
        }

        .club-name {
            font-size: 1.9rem;
            font-weight: 700;
            color: #8e44ad;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;   /* Horizontal line separator */
        }

        .details-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            font-size: 1.08rem;
            padding-top: 25px;
        }

        .detail-item {
            background: #f8f9fa;
            padding: 18px 22px;
            border-radius: 12px;
            
        }

        .detail-item strong {
            color: #2c3e50;
            display: block;
            margin-bottom: 6px;
            font-size: 1.05rem;
        }

        .no-club {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }

        .no-club i {
            font-size: 4.5rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            padding: 12px 28px;
            background: linear-gradient(to right, #8e44ad, #6c3483);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            margin-top: 30px;
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .details-container {
                grid-template-columns: 1fr;
            }
            .club-name {
                font-size: 1.7rem;
            }
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>


<h1 class="page-title">My Club</h1>

<?php if (!empty($student['club'])): ?>
    <div class="club-card">
        <h2 class="club-name">
            <i class="fas fa-users"></i> <?= htmlspecialchars($student['club']) ?>
        </h2>
        
        <div class="details-container">
            <div class="detail-item">
                <strong>Your Name</strong>
                <?= htmlspecialchars($student['student_name']) ?>
            </div>
            
            <div class="detail-item">
                <strong>Talent / Skill</strong>
                <?= !empty($student['talent']) ? htmlspecialchars($student['talent']) : '<em style="color:#999;">No talent description yet</em>' ?>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="no-club">
        <i class="fas fa-futbol"></i>
        <h2>You have not joined any club yet</h2>
        <p style="color:#666; margin: 15px 0 25px;">
            Contact your sport teacher to register for a club.
        </p>
        <a href="student_dashboard.php" class="btn">Back to Dashboard</a>
    </div>
<?php endif; ?>
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