<?php
session_start();
include "../component/connect.php";

$message = [];

// Ensure sport teacher is logged in
if (!isset($_SESSION['sport_teacher_id'])) {
    header("Location: sport_teacher_login.php");
    exit;
}

$teacher_id = $_SESSION['sport_teacher_id'];

// Get teacher's school_id from sport_teachers table
$stmt = $pdo->prepare("SELECT school_id FROM sport_teachers WHERE teacher_id = ?");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teacher || empty($teacher['school_id'])) {
    die("Error: School information not found. Please contact admin.");
}

$school_id = $teacher['school_id'];

// Predefined 10 Clubs
$clubs_list = [
    "Rumanyika Club", "Ibanda Club", "Serengeti Club"
    //, "Burigi Club",
   // "Mikumi Club", "Mtagata Club", "Ngorongoro Club", "Saanane Club",
   // "Rubondo Club", "Gombe Club"
];

// Get total students in this school
$total_students_stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE school_id = ?");
$total_students_stmt->execute([$school_id]);
$total_students = $total_students_stmt->fetchColumn();

// Determine maximum allowed clubs based on school size
if ($total_students <= 200) {
    $max_clubs = 3;
} elseif ($total_students <= 499) {
    $max_clubs = 5;
} else {
    $max_clubs = 10;
}

// Get current number of clubs for this school
$current_stmt = $pdo->prepare("SELECT COUNT(*) FROM clubs WHERE school_id = ?");
$current_stmt->execute([$school_id]);
$current_clubs = $current_stmt->fetchColumn();

// Handle form submission
if (isset($_POST['submit'])) {

    $club_name  = trim($_POST['club_name']);
    $focus_area = trim($_POST['focus_area']);
    $activities = trim($_POST['activities']);

    if (empty($club_name) || empty($focus_area)) {
        $message[] = "Club Name and Focus Area are required!";
    } 
    elseif ($current_clubs >= $max_clubs) {
        $message[] = "Maximum limit reached! Your school can only have $max_clubs clubs.";
    } 
    else {
        // Check if club already exists in THIS school
        $check = $pdo->prepare("SELECT id FROM clubs WHERE school_id = ? AND club_name = ?");
        $check->execute([$school_id, $club_name]);

        if ($check->rowCount() > 0) {
            $message[] = "This club is already registered in your school!";
        } else {
            // INSERT with correct school_id from sport_teachers table
            $insert = $pdo->prepare("
                INSERT INTO clubs (school_id, club_name, focus_area, activities)
                VALUES (?, ?, ?, ?)
            ");
            $insert->execute([$school_id, $club_name, $focus_area, $activities]);

            $message[] = "✅ Club <strong>$club_name</strong> registered successfully!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register New Club</title>
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
            font-size: 2.4rem;
            margin: 30px 0 10px;
            font-weight: 700;
        }
        .form-container {
            margin-top: 6rem;
            max-width: 680px;
            margin: 0 auto;
            background: white;
            padding: 45px 40px;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
            position: relative;
        }

        /* View Button - Top Right */
        .view-btn {
            position: absolute;
            top: 25px;
            right: 40px;
            padding: 10px 20px;
            background: linear-gradient(to right, #27ae60, #219653);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
            transition: all 0.3s ease;
            z-index: 10;
        }

        .view-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(39, 174, 96, 0.4);
        }

        label {
            display: block;
            margin: 20px 0 10px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.05rem;
        }
        input[type="text"], textarea, select {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #dfe6e9;
            border-radius: 12px;
            font-size: 1.02rem;
        }
        input:focus, textarea:focus, select:focus {
            border-color: #8e44ad;
            box-shadow: 0 0 0 4px rgba(142,68,173,0.12);
            outline: none;
        }
        textarea { min-height: 110px; resize: vertical; }
        .btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(to right, #8e44ad, #6c3483);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.15rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 30px;
        }
        .btn:hover { background: #6c3483; }
        .message {
            padding: 15px 18px;
            border-radius: 10px;
            margin: 15px 0;
            text-align: center;
            font-weight: 500;
        }
        .limit-info {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 500;
        }
    </style>
</head>
<body>

<?php include 'sport_teacher_header.php'; ?>

<h1 class="title">Register New Club</h1>

<div class="limit-info">
    <strong>School Size:</strong> <?= number_format($total_students) ?> students<br>
    <strong>Maximum Clubs Allowed:</strong> <?= $max_clubs ?> 
    (Current: <?= $current_clubs ?> / <?= $max_clubs ?>)
</div>

<?php if (!empty($message)): ?>
    <?php foreach($message as $msg): ?>
        <div class="message" style="background:#d4edda; color:#155724;"><?= $msg ?></div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="form-container">
    <!-- View All Clubs Button - Top Right -->
    <a href="view_clubs.php" class="view-btn">
        <i class="fas fa-eye"></i> View All Clubs
    </a>

    <form method="POST">
        <label>Select Club Name</label>
        <select name="club_name" required>
            <option value="">-- Choose Club --</option>
            <?php foreach($clubs_list as $club): ?>
                <option value="<?= htmlspecialchars($club) ?>"><?= htmlspecialchars($club) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Focus Area</label>
        <input type="text" name="focus_area" placeholder="e.g. Singing, Football, Art, Robotics..." required>

        <label>Activities (Optional)</label>
        <textarea name="activities" placeholder="Describe the main activities and events..."></textarea>

        <button type="submit" name="submit" class="btn">Register Club</button>
    </form>
</div>

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