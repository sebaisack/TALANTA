<?php
session_start();
include "../component/connect.php";

/* ===============================
   ENSURE SUPER ADMIN LOGIN
   =============================== */
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != "super_admin"){
    header("Location: login_admin.php");
    exit;
}

$message = [];

/* ===============================
   DELETE HEAD TEACHER
   =============================== */
if(isset($_GET['delete'])){
    $id = $_GET['delete'];

    // Get image name first
    $select = $pdo->prepare("SELECT image FROM head_teachers WHERE id = ?");
    $select->execute([$id]);
    $teacher = $select->fetch(PDO::FETCH_ASSOC);

    if($teacher && !empty($teacher['image'])){
        $image_path = "../uploaded_img/" . $teacher['image'];
        if(file_exists($image_path)){
            unlink($image_path); // delete image file
        }
    }

    // Delete record
    $delete = $pdo->prepare("DELETE FROM head_teachers WHERE id = ?");
    $delete->execute([$id]);

    // Set success message and redirect
    $_SESSION['message'] = "Head Teacher deleted successfully!";
    header("Location: super_admin_head_teacher_view.php");
    exit;
}

/* ===============================
   FETCH ALL HEAD TEACHERS
   =============================== */
$stmt = $pdo->prepare("SELECT * FROM head_teachers ORDER BY id DESC");
$stmt->execute();
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Display message if exists
if(isset($_SESSION['message'])){
    $message[] = $_SESSION['message'];
    unset($_SESSION['message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Head Teachers List</title>
<link rel="stylesheet" href="../css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

<style>

body{
    font-family: Arial, sans-serif;
    background: #f4f6f9;
     padding:6rem 20px;
}

h1{
    text-align: center;
    color: #2c3e50;
    margin-top: 20px;
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

/* TABLE CONTAINER */
.table-container{
    width: 100%;
    overflow-x: auto;
    margin-top: 20px;
}

/* TABLE STYLE */
table{
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

thead{
    background: #2980b9;
    color: white;
}

th, td{
    padding: 12px;
    border: 1px solid #ddd;
    text-align: center;
}

tr:nth-child(even){
    background: #f2f2f2;
}

tr:hover{
    background: #e6f2ff;
}

/* IMAGE STYLE */
.teacher-img{
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 50%;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    border: 2px solid #ddd;
}

.teacher-img:hover{
    transform: scale(1.1);
    box-shadow: 0 0 10px rgba(0,0,0,0.2);
    border-color: #2980b9;
}

/* BUTTONS */
.action-buttons{
    display: flex;
    justify-content: center;
    gap: 8px;
}

.btn-edit{
    background: #f39c12;
    color: white;
    padding: 6px 12px;
    text-decoration: none;
    border-radius: 4px;
    display: inline-block;
    font-size: 13px;
    transition: background 0.3s;
}

.btn-edit:hover{
    background: #d68910;
    color: white;
}

.btn-delete{
    background: #e74c3c;
    color: white;
    padding: 6px 12px;
    text-decoration: none;
    border-radius: 4px;
    display: inline-block;
    font-size: 13px;
    transition: background 0.3s;
}

.btn-delete:hover{
    background: #c0392b;
    color: white;
}

/* ADD BUTTON */
.add-btn{
    display: inline-block;
    margin: 20px 0;
    padding: 10px 20px;
    background: #27ae60;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    transition: background 0.3s;
}

.add-btn:hover{
    background: #229954;
    color: white;
}

/* BACK BUTTON */
.back-btn{
    display: inline-block;
    margin-top: 20px;
    padding: 10px 20px;
    background: #34495e;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    transition: background 0.3s;
}

.back-btn:hover{
    background: #2c3e50;
    color: white;
}

/* NO DATA */
.no-data{
    text-align: center;
    background: #fff;
    padding: 40px;
    margin-top: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.no-data i{
    font-size: 48px;
    color: #7f8c8d;
    margin-bottom: 10px;
}

.no-data p{
    font-size: 18px;
    color: #7f8c8d;
}

/* MODAL STYLES */
.modal {
    display: none;
    position: fixed;
    z-index: 9999;
    padding-top: 50px;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.95);
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from {opacity: 0;}
    to {opacity: 1;}
}

.modal-content {
    margin: auto;
    display: block;
    max-width: 90%;
    max-height: 90%;
    object-fit: contain;
    animation: zoomIn 0.3s;
    border-radius: 8px;
    box-shadow: 0 0 20px rgba(0,0,0,0.5);
}

@keyframes zoomIn {
    from {transform: scale(0.8); opacity: 0;}
    to {transform: scale(1); opacity: 1;}
}

.close-modal {
    position: absolute;
    top: 20px;
    right: 35px;
    color: #f1f1f1;
    font-size: 45px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;
    z-index: 10000;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(0,0,0,0.5);
}

.close-modal:hover {
    color: #fff;
    background: rgba(255,0,0,0.7);
    transform: rotate(90deg);
}

.modal-caption {
    margin: auto;
    display: block;
    width: 80%;
    max-width: 700px;
    text-align: center;
    color: #fff;
    padding: 15px 0;
    font-size: 18px;
    font-weight: bold;
    background: rgba(0,0,0,0.6);
    border-radius: 5px;
    margin-top: 10px;
}

/* Responsive */
@media (max-width: 768px) {
    th, td{
        padding: 8px;
        font-size: 12px;
    }
    
    .btn-edit, .btn-delete{
        padding: 4px 8px;
        font-size: 11px;
    }
    
    .teacher-img{
        width: 45px;
        height: 45px;
    }
    
    .modal-content {
        max-width: 95%;
        max-height: 85%;
    }
    
    .close-modal {
        top: 10px;
        right: 20px;
        font-size: 35px;
        width: 40px;
        height: 40px;
    }
}

/* Print styles */
@media print {
    .action-buttons, .back-btn, .modal, .btn-edit, .btn-delete, .add-btn {
        display: none;
    }
    
    body {
        background: white;
        padding: 0;
    }
    
    table {
        border: 1px solid #ddd;
    }
}

</style>

</head>
<body>

<?php include 'super_admin_header.php'; ?>

<h1>
    <i class="fas fa-user-tie"></i>
    Registered Head Teachers
</h1>

<?php
if(!empty($message)){
    foreach($message as $msg){
        echo '<div class="message"><i class="fas fa-check-circle"></i> '.$msg.'</div>';
    }
}
?>

<div style="text-align: center;">
    <a href="super_admin_head_teacher.php" class="add-btn">
        <i class="fas fa-plus-circle"></i> Register New Head Teacher
    </a>
</div>

<?php if(count($teachers) > 0): ?>

<div class="table-container">
     <table>
        <thead>
             <tr>
                <th>ID</th>
                <th>Teacher ID</th>
                <th>Username</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>School ID</th>
                <th>School Name</th>
                <th>Image</th>
                <th>Created At</th>
                <th>Actions</th>
             </tr>
        </thead>
        <tbody>
            <?php foreach($teachers as $teacher): ?>
             <tr>
                <td><?= htmlspecialchars($teacher['id']); ?></td>
                <td><?= htmlspecialchars($teacher['teacher_id']); ?></td>
                <td><?= htmlspecialchars($teacher['username']); ?></td>
                <td>
                    <strong><?= htmlspecialchars($teacher['full_name']); ?></strong>
                </td>
                <td><?= htmlspecialchars($teacher['email']); ?></td>
                <td><?= htmlspecialchars($teacher['school_id']); ?></td>
                <td><?= htmlspecialchars($teacher['school_name']); ?></td>
                <td>
                    <?php if(!empty($teacher['image'])): ?>
                        <img 
                            src="../uploaded_img/<?= $teacher['image']; ?>" 
                            class="teacher-img"
                            onclick="openModal('../uploaded_img/<?= $teacher['image']; ?>', '<?= htmlspecialchars($teacher['full_name']); ?>')"
                            alt="<?= htmlspecialchars($teacher['full_name']); ?>"
                            title="Click to zoom"
                        >
                    <?php else: ?>
                        <span style="color: #999; font-size: 12px;">
                            <i class="fas fa-image"></i> No Image
                        </span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($teacher['created_at']); ?></td>
                <td>
                    <div class="action-buttons">
                        <a href="admin_edit_head_teacher.php?id=<?= $teacher['id']; ?>" class="btn-edit">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a 
                            href="super_admin_head_teacher_view.php?delete=<?= $teacher['id']; ?>" 
                            class="btn-delete"
                            onclick="return confirm('Are you sure you want to delete this Head Teacher?\n\nTeacher: <?= htmlspecialchars($teacher['full_name']); ?>\nSchool: <?= htmlspecialchars($teacher['school_name']); ?>\n\nThis action cannot be undone!');"
                        >
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </div>
                </td>
             </tr>
            <?php endforeach; ?>
        </tbody>
     </table>
</div>

<div style="text-align: center; margin-top: 20px;">
    <p style="color: #7f8c8d;">
        <i class="fas fa-chalkboard-teacher"></i> 
        Total Head Teachers: <strong><?= count($teachers); ?></strong>
    </p>
</div>

<?php else: ?>

<div class="no-data">
    <i class="fas fa-user-graduate"></i>
    <p>No Head Teachers Registered Yet.</p>
    <a href="super_admin_head_teacher.php" style="display: inline-block; margin-top: 15px; padding: 8px 16px; background: #2980b9; color: white; text-decoration: none; border-radius: 5px;">
        <i class="fas fa-plus"></i> Register New Head Teacher
    </a>
</div>

<?php endif; ?>

<div style="text-align:center;">
    <a href="super_admin_home.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
</div>

<!-- Modal for image zoom -->
<div id="imageModal" class="modal">
    <span class="close-modal" onclick="closeModal()">&times;</span>
    <img class="modal-content" id="modalImage">
    <div id="modalCaption" class="modal-caption"></div>
</div>

<script>

// Toggle sidebar
let menuBtn = document.getElementById("menu-btn");
let sidebar = document.getElementById("sidebar");

if(menuBtn && sidebar) {
    menuBtn.addEventListener("click", function() {
        sidebar.classList.toggle("active");
    });
}

// Modal functions
function openModal(imageSrc, teacherName) {
    const modal = document.getElementById("imageModal");
    const modalImg = document.getElementById("modalImage");
    const captionText = document.getElementById("modalCaption");
    
    modal.style.display = "block";
    modalImg.src = imageSrc;
    captionText.innerHTML = '<i class="fas fa-user"></i> ' + teacherName;
    
    // Prevent body scrolling when modal is open
    document.body.style.overflow = "hidden";
}

function closeModal() {
    const modal = document.getElementById("imageModal");
    modal.style.display = "none";
    
    // Re-enable body scrolling
    document.body.style.overflow = "auto";
}

// Close modal when clicking outside the image
window.onclick = function(event) {
    const modal = document.getElementById("imageModal");
    if (event.target == modal) {
        closeModal();
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === "Escape") {
        const modal = document.getElementById("imageModal");
        if (modal.style.display === "block") {
            closeModal();
        }
    }
});

// Auto hide success message after 3 seconds
setTimeout(function() {
    let messages = document.querySelectorAll('.message');
    messages.forEach(function(message) {
        message.style.display = 'none';
    });
}, 3000);

</script>
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