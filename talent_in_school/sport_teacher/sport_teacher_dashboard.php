<?php
session_start();
include "../component/connect.php";

// Ensure only Sport Teacher can access
if (!isset($_SESSION['sport_teacher_id'])) {
    header("Location: sport_teacher_login.php");
    exit;
}

// Include PDO connection
include "../component/connect.php";

// Get teacher full name + school_id
$stmt = $pdo->prepare("SELECT full_name, school_id FROM sport_teachers WHERE teacher_id = ?");
$stmt->execute([$_SESSION['sport_teacher_id']]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teacher) {
    header("Location: sport_teacher_login.php");
    exit;
}

$full_name = $teacher['full_name'];
$school_id = $teacher['school_id'];

// Safe counting function - NOW FILTERED BY SCHOOL_ID
function safeCount($pdo, $table, $school_id) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM `$table` WHERE school_id = ?");
        $stmt->execute([$school_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    } catch (PDOException $e) {
        return 0;   // Table doesn't exist or error → show 0
    }
}

/* Safe Counts - Filtered by School */
$students_count     = safeCount($pdo, "students", $school_id);
$clubs_count        = safeCount($pdo, "clubs", $school_id);
$talents_count      = safeCount($pdo, "talents", $school_id);
$results_count      = safeCount($pdo, "results", $school_id);
$messages_count     = safeCount($pdo, "messages", $school_id);
$parents_count      = safeCount($pdo, "parents", $school_id);
$announcements_count = safeCount($pdo, "announcements", $school_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sport Teacher Dashboard</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            background: #f4f6f9;
            margin-top: 6rem; padding:6rem 20px;
        }

        .welcome {
            font-size: 22px;
            margin: 25px 0 20px 0;
            text-align: center;
            color: #2c3e50;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            padding: 20px;
            margin-top: 1rem;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
            transition: 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .count {
            font-size: 36px;
            font-weight: bold;
            color: #2c3e50;
        }

        .title {
            font-size: 18px;
            margin: 12px 0 15px 0;
            color: #34495e;
        }

        .btn {
            display: inline-block;
            padding: 10px 18px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
        }

        .btn:hover {
            background: #2980b9;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <?php include 'sport_teacher_header.php'; ?>

    <div class="welcome">
        Welcome, <?php echo htmlspecialchars($full_name); ?> 👋
    </div>

    <div class="cards">
        

        <!-- CLUBS -->
        <div class="card">
            <div class="count"><?= $clubs_count; ?></div>
            <div class="title">Clubs</div>
            <a class="btn" href="sport_teacher_register_clubs.php">View</a>
        </div>
        
        <!-- STUDENTS -->
        <div class="card">
            <div class="count"><?= $students_count; ?></div>
            <div class="title">Students</div>
            <a class="btn" href="sport_teacher_student_registration.php">View</a>
        </div>
        <!-- TALENTS 
        <div class="card">
            <div class="count"><?= $talents_count; ?></div>
            <div class="title">Talents</div>
            <a class="btn" href="view_talents.php">View</a>
        </div>-->

        <!-- RESULTS -->
        <div class="card">
           <!-- <div class="count"><?= $results_count; ?></div>-->
            <div class="title">Results</div>
            <a class="btn" href="view_results.php">View</a>
        </div>

        <!-- MESSAGES -->
        <div class="card">
            <div class="count"><?= $messages_count; ?></div>
            <div class="title">Messages</div>
            <a class="btn" href="view_messages.php">View</a>
        </div>

        <!-- PARENTS -->
        <div class="card">
            <div class="count"><?= $parents_count; ?></div>
            <div class="title">Parents</div>
            <a class="btn" href="view_parents.php">View</a>
        </div>

        <!-- ANNOUNCEMENTS -->
        <div class="card">
            <div class="count"><?= $announcements_count; ?></div>
            <div class="title">Announcements</div>
            <a class="btn" href="manage_announcements.php">View</a>
        </div>
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




















