<?php
session_start();
include "../component/connect.php";

// Ensure Super Admin is logged in
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != "super_admin"){
    header("Location: login_admin.php");
    exit;
}

$message = [];

// Check if ID is provided
if(!isset($_GET['id'])){
    header("Location: admin_view_head_teacher.php");
    exit;
}

$id = $_GET['id'];

// Fetch head teacher data
$stmt = $pdo->prepare("SELECT * FROM head_teachers WHERE id = ?");
$stmt->execute([$id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$teacher){
    $_SESSION['error'] = "Head Teacher not found!";
    header("Location: admin_view_head_teacher.php");
    exit;
}

// Fetch schools for dropdown
$schools_stmt = $pdo->prepare("SELECT school_id, school_name FROM schools ORDER BY school_name ASC");
$schools_stmt->execute();
$schools = $schools_stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   UPDATE HEAD TEACHER
   =============================== */
if(isset($_POST['update'])){
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $school_id = $_POST['school_id'];
    $new_password = $_POST['new_password'];
    
    // Get school name
    $school_name = '';
    foreach($schools as $school){
        if($school['school_id'] == $school_id){
            $school_name = $school['school_name'];
            break;
        }
    }
    
    // Handle image upload
    $image_name = $teacher['image']; // Keep old image by default
    
    if(isset($_FILES['image']) && $_FILES['image']['error'] === 0){
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        
        if(in_array($file_type, $allowed_types)){
            if(!is_dir('../uploaded_img')){
                mkdir('../uploaded_img', 0777, true);
            }
            
            // Delete old image if exists
            if(!empty($teacher['image']) && file_exists("../uploaded_img/" . $teacher['image'])){
                unlink("../uploaded_img/" . $teacher['image']);
            }
            
            $img_tmp = $_FILES['image']['tmp_name'];
            $img_name = time() . '_' . $_FILES['image']['name'];
            $img_dir = '../uploaded_img/' . $img_name;
            
            if(move_uploaded_file($img_tmp, $img_dir)){
                $image_name = $img_name;
                $message[] = "Image updated successfully!";
            } else {
                $message[] = "Failed to upload new image!";
            }
        } else {
            $message[] = "Only JPG, JPEG, PNG & GIF files are allowed!";
        }
    }
    
    // Update query
    if(!empty($new_password)){
        // Update with new password
        $password_hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $pdo->prepare("
            UPDATE head_teachers 
            SET full_name = ?, email = ?, school_id = ?, school_name = ?, password = ?, image = ?
            WHERE id = ?
        ");
        $update->execute([$full_name, $email, $school_id, $school_name, $password_hashed, $image_name, $id]);
        $message[] = "Head Teacher updated successfully with new password!<br>New Password: " . $new_password;
    } else {
        // Update without changing password
        $update = $pdo->prepare("
            UPDATE head_teachers 
            SET full_name = ?, email = ?, school_id = ?, school_name = ?, image = ?
            WHERE id = ?
        ");
        $update->execute([$full_name, $email, $school_id, $school_name, $image_name, $id]);
        $message[] = "Head Teacher updated successfully!";
    }
    
    // Refresh teacher data after update
    $stmt = $pdo->prepare("SELECT * FROM head_teachers WHERE id = ?");
    $stmt->execute([$id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Display error from session if exists
if(isset($_SESSION['error'])){
    $message[] = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Head Teacher</title>
<link rel="stylesheet" href="../css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

<style>
body{
    font-family: Arial, sans-serif;
    background: #f4f6f9;
     padding:6rem 20px;
}

h1.title{
    text-align: center;
    color: #2c3e50;
    margin-top: 2rem;
}

.message{
    background: #dff0d8;
    color: #3c763d;
    padding: 10px 15px;
    margin: 15px auto;
    border-radius: 5px;
    width: 90%;
    text-align: center;
    animation: fadeOut 3s ease-in-out forwards;
}

.message-error{
    background: #f2dede;
    color: #a94442;
}

@keyframes fadeOut {
    0% { opacity: 1; }
    70% { opacity: 1; }
    100% { opacity: 0; visibility: hidden; }
}

.form-container{
    max-width: 600px;
    margin: 20px auto;
    padding: 30px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
}

.form-container input[type="text"],
.form-container input[type="email"],
.form-container input[type="password"],
.form-container select{
    width: 100%;
    padding: 10px;
    margin: 10px 0px;
    border-radius: 5px;
    border: 1px solid #ddd;
    font-size: 14px;
}

.form-container input[type="file"]{
    width: 100%;
    padding: 8px;
    margin: 10px 0px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.form-container input[type="submit"]{
    width: 100%;
    padding: 12px;
    background: #2980b9;
    color: #fff;
    font-size: 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s;
}

.form-container input[type="submit"]:hover{
    background: #1c5980;
}

label{
    font-weight: bold;
    display: block;
    margin-top: 15px;
    margin-bottom: 5px;
    color: #2c3e50;
}

.current-image{
    text-align: center;
    margin: 15px 0;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 5px;
}

.current-image img{
    width: 150px;
    height: 150px;
    object-fit: cover;
    border-radius: 50%;
    border: 3px solid #2980b9;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.back-btn{
    display: inline-block;
    margin: 10px 0;
    padding: 10px 15px;
    background: #95a5a6;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    transition: background 0.3s;
}

.back-btn:hover{
    background: #7f8c8d;
    color: white;
}

.info-text{
    font-size: 12px;
    color: #7f8c8d;
    margin-top: 5px;
}

.password-info{
    background: #f8f9fa;
    padding: 10px;
    border-left: 3px solid #f39c12;
    margin: 10px 0;
}

hr{
    margin: 20px 0;
    border: none;
    border-top: 1px solid #eee;
}

</style>
</head>
<body>

<?php include 'super_admin_header.php'; ?>

<h1 class="title">
    <i class="fas fa-user-edit"></i>
    Edit Head Teacher
</h1>

<?php
if(!empty($message)){
    foreach($message as $msg){
        $class = strpos($msg, 'error') !== false ? 'message-error' : '';
        echo '<div class="message ' . $class . '"><i class="fas fa-info-circle"></i> ' . $msg . '</div>';
    }
}
?>

<div class="form-container">
    <a href="admin_view_head_teacher.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to List
    </a>
    
    <form method="POST" enctype="multipart/form-data">
        
        <label>Teacher ID</label>
        <input type="text" value="<?= htmlspecialchars($teacher['teacher_id']); ?>" disabled>
        <div class="info-text">Teacher ID cannot be changed</div>
        
        <label>Username</label>
        <input type="text" value="<?= htmlspecialchars($teacher['username']); ?>" disabled>
        <div class="info-text">Username cannot be changed</div>
        
        <label>Full Name *</label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($teacher['full_name']); ?>" required>
        
        <label>Email *</label>
        <input type="email" name="email" value="<?= htmlspecialchars($teacher['email']); ?>" required>
        
        <label>Select School *</label>
        <select name="school_id" required>
            <option value="">Select School</option>
            <?php foreach($schools as $school): ?>
                <option value="<?= $school['school_id']; ?>" <?= ($teacher['school_id'] == $school['school_id']) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($school['school_name']); ?> (<?= $school['school_id']; ?>)
                </option>
            <?php endforeach; ?>
        </select>
        
        <?php if(!empty($teacher['image'])): ?>
        <div class="current-image">
            <label>Current Image</label><br>
            <img src="../uploaded_img/<?= $teacher['image']; ?>" alt="Current Image">
            <div class="info-text">Current profile image</div>
        </div>
        <?php endif; ?>
        
        <label>Change Image</label>
        <input type="file" name="image" accept="image/*">
        <div class="info-text">Leave empty to keep current image. Allowed: JPG, JPEG, PNG, GIF</div>
        
        <hr>
        
        <div class="password-info">
            <i class="fas fa-lock"></i> <strong>Password Settings</strong><br>
            <small>Leave password field empty to keep current password. Enter new password to change it.</small>
        </div>
        
        <label>New Password</label>
        <input type="password" name="new_password" placeholder="Enter new password (leave empty to keep current)">
        
        <hr>
        
        <input type="submit" name="update" value="Update Head Teacher">
        
    </form>
    
    <div style="text-align: center; margin-top: 20px;">
        <a href="admin_view_head_teacher.php?delete=<?= $teacher['id']; ?>" 
           class="btn-delete" 
           onclick="return confirm('Are you sure you want to delete this Head Teacher?\n\nTeacher: <?= htmlspecialchars($teacher['full_name']); ?>\nSchool: <?= htmlspecialchars($teacher['school_name']); ?>\n\nThis action cannot be undone!');"
           style="background: #e74c3c; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; display: inline-block;">
            <i class="fas fa-trash"></i> Delete This Head Teacher
        </a>
    </div>
</div>

<script>
// Auto hide success message after 3 seconds
setTimeout(function() {
    let messages = document.querySelectorAll('.message');
    messages.forEach(function(message) {
        if(!message.classList.contains('message-error')) {
            message.style.display = 'none';
        }
    });
}, 3000);
</script>

</body>
</html>