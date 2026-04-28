<?php
session_start();
include "../component/connect.php";

// Ensure sport teacher is logged in
if(!isset($_SESSION['sport_teacher_id'])){
    header("Location: sport_teacher_login.php");
    exit;
}

// Get teacher info to know school_id
$teacher_id = $_SESSION['sport_teacher_id'];
$stmt = $pdo->prepare("SELECT school_id FROM sport_teachers WHERE teacher_id = ?");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$teacher || empty($teacher['school_id'])){
    die("Error: School information not found. Please contact admin.");
}

$school_id = $teacher['school_id'];

// Fetch all students in this school
$students_stmt = $pdo->prepare("SELECT * FROM students WHERE school_id = ? ORDER BY id ASC");
$students_stmt->execute([$school_id]);
$students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Students</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">

    <style>
        :root {
            --primary: #8e44ad;
            --success: #27ae60;
            --danger: #e74c3c;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }

        .title {
            text-align: center;
            color: #2c3e50;
            font-size: 2.4rem;
            margin: 30px 0 25px;
            font-weight: 700;
        }

        .top-btn {
            display: block;
            width: 240px;
            margin: 20px auto;
            padding: 14px 24px;
            background-color: var(--success);
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.35);
        }

        .top-btn:hover {
            background-color: #219a52;
            transform: translateY(-4px);
        }

        .table-container {
            max-width: 1200px;
            margin: 30px auto;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background-color: var(--primary);
            color: white;
            padding: 16px 12px;
            font-weight: 600;
        }

        td {
            padding: 14px 12px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        /* Action Column - Improved */
        .actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
        }

        .edit-btn   { background-color: #3498db; }
        .delete-btn { background-color: var(--danger); }

        .edit-btn:hover   { background-color: #2980b9; transform: translateY(-2px); }
        .delete-btn:hover { background-color: #c0392b; transform: translateY(-2px); }

        @media (max-width: 768px) {
            .actions {
                flex-direction: column;
            }
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<?php include 'sport_teacher_header.php'; ?>

<h1 class="title">Students List</h1>

<a href="sport_teacher_student_registration.php" class="top-btn">
    <i class="fas fa-plus"></i> Register New Student
</a>

<div class="table-container">
    <table>
        <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Age</th>
            <th>Standard</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Username</th>
            <th>School ID</th>
            <th>Actions</th>
        </tr>
        <?php if(!empty($students)): ?>
            <?php foreach($students as $student): ?>
            <tr>
                <td><?= htmlspecialchars($student['id']); ?></td>
                <td><?= htmlspecialchars($student['student_name'] ?? 'N/A'); ?></td>
                <td><?= htmlspecialchars($student['age'] ?? 'N/A'); ?></td>
                <td><?= htmlspecialchars($student['standard'] ?? $student['class'] ?? 'N/A'); ?></td>
                <td><?= htmlspecialchars($student['email'] ?? 'N/A'); ?></td>
                <td><?= htmlspecialchars($student['phone'] ?? 'N/A'); ?></td>
                <td><?= htmlspecialchars($student['username'] ?? 'N/A'); ?></td>
                <td><?= htmlspecialchars($student['school_id']); ?></td>
                <td>
                    <div class="actions">
                        <a href="edit_student.php?id=<?= $student['id']; ?>" class="btn edit-btn">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="delete_student.php?id=<?= $student['id']; ?>" class="btn delete-btn" 
                           onclick="return confirm('Are you sure you want to delete this student?');">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="9">No students found in your school.</td></tr>
        <?php endif; ?>
    </table>
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