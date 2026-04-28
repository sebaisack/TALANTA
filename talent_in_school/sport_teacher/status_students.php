<?php
session_start();
include "../component/connect.php";

// Ensure sport teacher is logged in
if (!isset($_SESSION['sport_teacher_id'])) {
    header("Location: sport_teacher_login.php");
    exit;
}

$teacher_id = $_SESSION['sport_teacher_id'];

// Get teacher's school_id
$stmt = $pdo->prepare("SELECT school_id FROM sport_teachers WHERE teacher_id = ?");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teacher || empty($teacher['school_id'])) {
    die("Error: School information not found. Please contact admin.");
}

$school_id = $teacher['school_id'];

// Get club_id from URL
$club_id = isset($_GET['club_id']) ? intval($_GET['club_id']) : 0;

if ($club_id <= 0) {
    die("Invalid club selection.");
}

// Fetch club details
$club_stmt = $pdo->prepare("SELECT * FROM clubs WHERE id = ? AND school_id = ?");
$club_stmt->execute([$club_id, $school_id]);
$club = $club_stmt->fetch(PDO::FETCH_ASSOC);

if (!$club) {
    die("Club not found or you don't have access to this club.");
}

// Fetch students who registered with this club
$students_stmt = $pdo->prepare("
    SELECT id, student_name, username, talent 
    FROM students 
    WHERE school_id = ? 
      AND club = ? 
    ORDER BY student_name ASC
");
$students_stmt->execute([$school_id, $club['club_name']]);
$students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students in <?= htmlspecialchars($club['club_name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">

    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f0f4f8, #d9e2ec);
            margin: 0;
             padding:6rem 20px;
            min-height: 100vh;
        }

        .title {
            text-align: center;
            color: #2c3e50;
            font-size: 2.3rem;
            margin: 30px 0 15px;
            font-weight: 700;
        }

        .club-title {
            text-align: center;
            color: #8e44ad;
            margin-bottom: 30px;
            font-size: 1.65rem;
            font-weight: 600;
        }

        .back-btn {
            display: inline-block;
            padding: 10px 24px;
            background: #34495e;
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #2c3e50;
            transform: translateY(-2px);
        }

        .table-container {
            max-width: 1200px;           /* Increased width */
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 12px 35px rgba(0,0,0,0.15);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;            /* Increased minimum width for better spacing */
        }

        th, td {
            padding: 16px 20px;
            text-align: left;
        }

        th {
            background: linear-gradient(135deg, #8e44ad, #6c3483);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        td {
            border-bottom: 1px solid #f1f1f1;
        }

        tr:hover {
            background: #f8f9fc;
        }

        .student-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .no-students {
            text-align: center;
            padding: 70px 20px;
            color: #777;
            font-size: 1.25rem;
            background: #f8f9fa;
            border-radius: 12px;
        }

        .no-students i {
            font-size: 3.5rem;
            color: #ddd;
            margin-bottom: 15px;
            display: block;
        }
    </style>
</head>
<body>

<?php include 'sport_teacher_header.php'; ?>

<a href="view_clubs.php" class="back-btn">
    ← Back to All Clubs
</a>

<h1 class="title">Club Status</h1>
<div class="club-title">
    <?= htmlspecialchars($club['club_name']) ?> - Registered Students
</div>

<div class="table-container">
    <?php if (!empty($students)): ?>
        <table>
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Username</th>
                    <th>Talent</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                <tr>
                    <td><?= htmlspecialchars($student['id']) ?></td>
                    <td class="student-name"><?= htmlspecialchars($student['student_name']) ?></td>
                    <td><?= htmlspecialchars($student['username']) ?></td>
                    <td>
                        <?= !empty($student['talent']) 
                            ? htmlspecialchars($student['talent']) 
                            : '<em style="color:#999;">No talent submitted yet</em>' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-students">
            <i class="fas fa-users"></i><br>
            No students have joined this club yet.
        </div>
    <?php endif; ?>
</div>
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