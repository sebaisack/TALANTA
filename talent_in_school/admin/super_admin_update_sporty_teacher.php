<?php
session_start();

// Ensure only Super Admin can access
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != "super_admin"){
    header("Location: super_admin_login.php");
    exit;
}

include "../component/connect.php";

$message = [];

// Get teacher ID from URL
if(!isset($_GET['id'])){
    header("Location: admin_view_sporty_teacher.php");
    exit;
}
$id = $_GET['id'];

// Fetch the current teacher details
$stmt = $pdo->prepare("SELECT * FROM sport_teachers WHERE id = ?");
$stmt->execute([$id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$teacher){
    $message[] = "Sport Teacher not found!";
}

// Handle form submission
if(isset($_POST['update'])){
    $full_name = $_POST['full_name'];
    $gender    = $_POST['gender'];
    $phone     = $_POST['phone'];
    $email     = $_POST['email'];

    $update = $pdo->prepare("
        UPDATE sport_teachers
        SET full_name = ?, gender = ?, phone = ?, email = ?
        WHERE id = ?
    ");
    $update->execute([$full_name, $gender, $phone, $email, $id]);

    $message[] = "Sport Teacher updated successfully!";
    // Refresh the teacher data
    $stmt = $pdo->prepare("SELECT * FROM sport_teachers WHERE id = ?");
    $stmt->execute([$id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Sport Teacher</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
<link rel="stylesheet" href="../css/admin.css">
<style>
.form-container {
    max-width: 600px;
    margin: 2rem auto;
    padding: 2rem;
    background: #f4f4f4;
    border-radius: 8px;
}
.form-container input, 
.form-container select {
    width: 100%;
    padding: 0.7rem;
    margin-bottom: 1rem;
    border-radius: 5px;
    border: 1px solid #ccc;
}
.btn {
    display: inline-block;
    padding: 0.7rem 1.5rem;
    background-color: #2980b9;
    color: #fff;
    text-decoration: none;
    border-radius: 5px;
    cursor: pointer;
}
.btn:hover {
    background-color: #1c5980;
}
.message {
    margin: 1rem auto;
    padding: 1rem;
    width: 90%;
    background: #dff0d8;
    color: #3c763d;
    border-radius: 5px;
}
</style>
</head>
<body>

<?php include 'super_admin_header.php'; ?>

<h1 class="title">Edit Sport Teacher</h1>

<?php
if(!empty($message)){
    foreach($message as $msg){
        echo '<div class="message">'.$msg.'</div>';
    }
}
?>

<div class="form-container">
<form method="POST" action="admin_edit_sport_teacher.php?id=<?= $id; ?>">

    <input type="text" name="full_name" placeholder="Full Name" required
        value="<?= htmlspecialchars($teacher['full_name']); ?>">

    <select name="gender" required>
        <option value="">Select Gender</option>
        <option value="Male" <?= $teacher['gender']=='Male' ? 'selected' : ''; ?>>Male</option>
        <option value="Female" <?= $teacher['gender']=='Female' ? 'selected' : ''; ?>>Female</option>
    </select>

    <input type="text" name="phone" placeholder="Phone Number" required
        value="<?= htmlspecialchars($teacher['phone']); ?>">

    <input type="email" name="email" placeholder="Email" required
        value="<?= htmlspecialchars($teacher['email']); ?>">

    <input type="submit" name="update" value="Update Sport Teacher" class="btn">

</form>
</div>

<script>
// Sidebar toggle
let menuBtn = document.getElementById("menu-btn");
let sidebar = document.getElementById("sidebar");
menuBtn && menuBtn.addEventListener("click", function() {
    sidebar && sidebar.classList.toggle("active");
});
</script>

</body>
</html>