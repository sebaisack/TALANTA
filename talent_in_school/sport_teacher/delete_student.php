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

$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($student_id <= 0) {
    die("Invalid request.");
}

// Verify student belongs to this school
$check = $pdo->prepare("SELECT id FROM students WHERE id = ? AND school_id = ?");
$check->execute([$student_id, $school_id]);

if ($check->rowCount() === 0) {
    die("Student not found or you don't have permission to delete this student.");
}

// Delete the student
$delete = $pdo->prepare("DELETE FROM students WHERE id = ? AND school_id = ?");
$delete->execute([$student_id, $school_id]);

// Redirect back to view students page
header("Location: view_student.php?deleted=1");
exit;
?>