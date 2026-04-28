<?php
session_start();
include "../component/connect.php";

if (!isset($_SESSION['sport_teacher_id'])) {
    header("Location: sport_teacher_login.php");
    exit;
}

$teacher_id = $_SESSION['sport_teacher_id'];

// Get teacher's school_id for security
$stmt = $pdo->prepare("SELECT school_id FROM sport_teachers WHERE teacher_id = ?");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);
$school_id = $teacher['school_id'] ?? 0;

// Get student ID from URL
$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($student_id <= 0) {
    die("Invalid student ID.");
}

// Fetch student data (only from the same school)
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ? AND school_id = ?");
$stmt->execute([$student_id, $school_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student not found or you don't have permission to edit this student.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_name = trim($_POST['student_name']);
    $age          = intval($_POST['age']);
    $standard     = trim($_POST['standard']);
    $email        = trim($_POST['email']);
    $phone        = trim($_POST['phone']);

    if (empty($student_name) || empty($standard) || empty($email)) {
        $error = "Please fill all required fields!";
    } else {
        $update = $pdo->prepare("
            UPDATE students 
            SET student_name = ?, age = ?, standard = ?, email = ?, phone = ?
            WHERE id = ? AND school_id = ?
        ");
        $update->execute([$student_name, $age, $standard, $email, $phone, $student_id, $school_id]);

        $success = "Student information updated successfully!";
        
        // Refresh data
        $stmt->execute([$student_id, $school_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
             padding:6rem 20px;
        }
        .form-container {
            max-width: 600px;
            margin: 40px auto;
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }
        .save-btn {
            background: #27ae60;
            color: white;
        }
        .cancel-btn {
            background: #95a5a6;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>

<?php include 'sport_teacher_header.php'; ?>

<div class="form-container">
    <h2 style="text-align:center; margin-bottom:30px;">Edit Student Information</h2>

    <?php if (isset($success)): ?>
        <div style="background:#d4edda; color:#155724; padding:15px; border-radius:8px; margin-bottom:20px;">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div style="background:#f8d7da; color:#721c24; padding:15px; border-radius:8px; margin-bottom:20px;">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label>Full Name</label>
        <input type="text" name="student_name" value="<?= htmlspecialchars($student['student_name']) ?>" required>

        <label>Age</label>
        <input type="number" name="age" value="<?= htmlspecialchars($student['age']) ?>" min="5" max="25" required>

        <label>Standard / Class</label>
        <input type="text" name="standard" value="<?= htmlspecialchars($student['standard']) ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($student['email']) ?>" required>

        <label>Phone Number</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($student['phone'] ?? '') ?>">

        <div style="margin-top:30px; display:flex; gap:15px;">
            <button type="submit" class="btn save-btn">Save Changes</button>
            <a href="view_student.php" class="btn cancel-btn">Cancel</a>
        </div>
    </form>
</div>
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