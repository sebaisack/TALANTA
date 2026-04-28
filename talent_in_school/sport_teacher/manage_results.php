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
    die("Error: School information not found.");
}

$school_id = $teacher['school_id'];

// Fetch clubs for this school
$clubs_stmt = $pdo->prepare("SELECT id, club_name FROM clubs WHERE school_id = ? ORDER BY club_name");
$clubs_stmt->execute([$school_id]);
$clubs = $clubs_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch students in this school
$students_stmt = $pdo->prepare("SELECT id, student_name FROM students WHERE school_id = ? ORDER BY student_name");
$students_stmt->execute([$school_id]);
$students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

$message = [];

// Handle form submission
if (isset($_POST['submit'])) {
    $student_id  = intval($_POST['student_id']);
    $club_id     = intval($_POST['club_id']);
    $total_score = floatval($_POST['total_score']);

    if ($total_score < 0 || $total_score > 100) {
        $message[] = "Score must be between 0 and 100!";
    } else {
        $insert = $pdo->prepare("
            INSERT INTO results (student_id, club_id, total_score, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $insert->execute([$student_id, $club_id, $total_score]);

        $message[] = "✅ Result for student uploaded successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Results - Sport Teacher</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">

    <style>
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #f0f4f8, #d9e2ec); padding: 20px; }
        .title { text-align: center; color: #2c3e50; font-size: 2.4rem; margin: 30px 0 40px; }
        .form-container {
            max-width: 720px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }
        label { display: block; margin: 18px 0 8px; font-weight: 600; color: #2c3e50; }
        select, input[type="number"] {
            width: 100%;
            padding: 14px;
            border: 2px solid #dfe6e9;
            border-radius: 10px;
            font-size: 1.05rem;
        }
        .grade-display {
            margin-top: 10px;
            font-size: 1.4rem;
            font-weight: bold;
            padding: 12px;
            border-radius: 10px;
            min-height: 50px;
        }
        .btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(to right, #8e44ad, #6c3483);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.15rem;
            margin-top: 30px;
            cursor: pointer;
        }
        .message { padding: 15px; border-radius: 8px; margin: 15px 0; text-align: center; }
    </style>
</head>
<body>

<?php include 'sport_teacher_header.php'; ?>

<h1 class="title">Upload Student Results</h1>

<div class="form-container">
    <?php if (!empty($message)): ?>
        <?php foreach($message as $msg): ?>
            <div class="message" style="background:#d4edda; color:#155724;"><?= $msg ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <form method="POST" id="resultForm">
        <label>Select Club</label>
        <select name="club_id" required>
            <option value="">-- Choose Club --</option>
            <?php foreach($clubs as $club): ?>
                <option value="<?= $club['id'] ?>"><?= htmlspecialchars($club['club_name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Select Student</label>
        <select name="student_id" required>
            <option value="">-- Choose Student --</option>
            <?php foreach($students as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['student_name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Enter Total Score (Out of 100)</label>
        <input type="number" id="total_score" name="total_score" min="0" max="100" step="0.1" placeholder="e.g. 82.5" required>

        <div id="grade_display" class="grade-display"></div>

        <button type="submit" name="submit" class="btn">Upload Result</button>
    </form>
</div>

<script>
// Auto calculate grade as teacher types score
document.getElementById('total_score').addEventListener('input', function() {
    let score = parseFloat(this.value);
    let display = document.getElementById('grade_display');

    if (isNaN(score)) {
        display.innerHTML = '';
        return;
    }

    let grade = getGrade(score);
    let gradeClass = getGradeClass(score);

    display.innerHTML = `<span style="color:#27ae60;">Grade: </span><strong class="${gradeClass}">${grade}</strong>`;
});

function getGrade(score) {
    if (score >= 90) return 'A+ (Excellent)';
    if (score >= 75) return 'A (Very Good / Vizuri sana)';
    if (score >= 60) return 'B+ (Good)';
    if (score >= 50) return 'B (Satisfactory / Inaridhisha)';
    if (score >= 40) return 'C (Fair / Wastani)';
    if (score >= 30) return 'D (Poor / Dhaifu)';
    if (score >= 20) return 'E (Unsatisfactory / Hairidhishi)';
    return 'F (Failure / Kushindwa)';
}

function getGradeClass(score) {
    if (score >= 90) return 'A-plus';
    if (score >= 75) return 'A';
    if (score >= 60) return 'B-plus';
    if (score >= 50) return 'B';
    if (score >= 40) return 'C';
    if (score >= 30) return 'D';
    return 'E';
}
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