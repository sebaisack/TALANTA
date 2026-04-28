<?php
session_start();
include "../component/connect.php";

// Ensure Super Admin is logged in
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != "super_admin"){
    header("Location: login_admin.php");
    exit;
}

$message = [];

/* ===============================
   FETCH SCHOOLS WITHOUT HEAD TEACHER
   =============================== */
$stmt = $pdo->prepare("
    SELECT school_id, school_name
    FROM schools
    WHERE school_id NOT IN (
        SELECT school_id FROM head_teachers
    )
    ORDER BY school_name ASC
");
$stmt->execute();
$schools = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   REGISTER HEAD TEACHER
   =============================== */
if(isset($_POST['submit'])){
    $full_name = $_POST['full_name'];
    $email     = $_POST['email'];
    $school_id = $_POST['school_id'];

    // Check if school already has a head teacher
    $check = $pdo->prepare("SELECT * FROM head_teachers WHERE school_id = ?");
    $check->execute([$school_id]);

    if($check->rowCount() > 0){
        $message[] = "This school already has a Head Teacher!";
    } else {

        // Generate teacher ID
        $last = $pdo->query("SELECT teacher_id FROM head_teachers ORDER BY id DESC LIMIT 1")->fetchColumn();
        $num = $last ? (int)str_replace('HT-', '', $last) + 1 : 1;
        $teacher_id = 'HT-' . str_pad($num, 4, '0', STR_PAD_LEFT);

        // Generate username automatically
        $name_parts = explode(' ', strtolower($full_name));
        $username = 'ht_' . implode('_', $name_parts);

        // Generate random password
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
        $password_plain = substr(str_shuffle($chars), 0, 8);
        $password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);

        // Get school name
        $school_name = '';
        foreach($schools as $school){
            if($school['school_id'] == $school_id){
                $school_name = $school['school_name'];
                break;
            }
        }

        // Handle image upload
        $image_name = null;
        if(isset($_FILES['image']) && $_FILES['image']['error'] === 0){
            if(!is_dir('../uploaded_img')){
                mkdir('../uploaded_img', 0777, true); // create folder if not exists
            }
            $img_tmp = $_FILES['image']['tmp_name'];
            $img_name = time() . '_' . $_FILES['image']['name'];
            $img_dir = '../uploaded_img/' . $img_name;
            if(move_uploaded_file($img_tmp, $img_dir)){
                $image_name = $img_name;
            } else {
                $message[] = "Failed to upload image!";
            }
        }

        // Insert into head_teachers
        $insert = $pdo->prepare("
            INSERT INTO head_teachers
            (teacher_id, username, full_name, email, school_id, school_name, password, image)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insert->execute([
            $teacher_id, 
            $username, 
            $full_name, 
            $email,
            $school_id,
            $school_name,
            $password_hashed, 
            $image_name
        ]);

        $message[] = "Head Teacher Registered Successfully!<br>Username: $username | Password: $password_plain";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register Head Teacher</title>

<link rel="stylesheet" href="../css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

<style>
body{
    font-family: Arial, sans-serif;
    background: #f7f7f7;
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
}
.form-container{
    max-width: 500px;
    margin: 20px auto;
    padding: 25px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0px 0px 10px #ccc;
}
.form-container input[type="text"],
.form-container input[type="email"],
.form-container select,
.form-container input[type="file"]{
    width: 100%;
    padding: 10px;
    margin: 10px 0px;
    border-radius: 5px;
    border: 1px solid #ccc;
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
}
.form-container input[type="submit"]:hover{
    background: #1c5980;
}

/* VIEW BUTTON */
.view-btn{
    display: inline-block;
    margin: 10px 0 20px 0;
    padding: 10px 15px;
    background: #27ae60;
    color: #fff;
    text-decoration: none;
    border-radius: 5px;
    text-align: center;
}
.view-btn:hover{
    background: #1e8449;
}

label{
    font-weight: bold;
    display: block;
    margin-top: 10px;
}
</style>
</head>
<body>

<?php include 'super_admin_header.php'; ?>

<h1 class="title">Register Head Teacher</h1>

<?php
if(!empty($message)){
    foreach($message as $msg){
        echo '<div class="message">'.$msg.'</div>';
    }
}
?>

<!-- VIEW HEAD TEACHERS BUTTON -->
<a href="super_admin_head_teacher_view.php" class="view-btn">
    <i class="fas fa-eye"></i> View Head Teachers List
</a>

<div class="form-container">
<form method="POST" enctype="multipart/form-data">

    <input type="text" name="full_name" placeholder="Full Name" required>

    <input type="email" name="email" placeholder="Email" required>

    <label>Select School</label>
    <select name="school_id" required>
        <option value="">Select School</option>
        <?php foreach($schools as $school): ?>
        <option value="<?= $school['school_id']; ?>">
            <?= htmlspecialchars($school['school_name']); ?> (<?= $school['school_id']; ?>)
        </option>
        <?php endforeach; ?>
    </select>

    <label>Upload Image</label>
    <input type="file" name="image" accept="image/*">

    <input type="submit" name="submit" value="Register Head Teacher">
</form>
</div>
 <script>
        // Safe sidebar toggle
        let menuBtn = document.getElementById("menu-btn");
        let sidebar = document.getElementById("sidebar");

        if (menuBtn && sidebar) {
            menuBtn.addEventListener("click", function () {
                sidebar.classList.toggle("active");
            });
        }
    </script>
</body>
</html>