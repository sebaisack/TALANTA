<?php
session_start();
include "../component/connect.php";

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != "super_admin"){
    header("Location: super_admin.php");
    exit;
}
$message = [];

/* ===============================
   FETCH SCHOOLS WITHOUT SPORT TEACHER
   =============================== */
$stmt = $pdo->prepare("
    SELECT s.school_id, s.school_name 
    FROM schools s
    LEFT JOIN sport_teachers st ON s.school_id = st.school_id
    WHERE st.school_id IS NULL
    ORDER BY s.school_name ASC
");
$stmt->execute();
$schools = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   INSERT SPORT TEACHER
   =============================== */
if(isset($_POST['submit'])){

    $full_name = trim($_POST['full_name']);
    $gender    = $_POST['gender'];
    $phone     = trim($_POST['phone']);
    $email     = trim($_POST['email']);
    $school_id = $_POST['school_id'];

    // Basic Validation
    if(empty($full_name) || empty($gender) || empty($phone) || empty($email) || empty($school_id)){
        $message[] = "All fields are required!";
    } 
    else {
        // === CHECK 1: Email already exists? ===
        $check_email = $pdo->prepare("SELECT * FROM sport_teachers WHERE email = ?");
        $check_email->execute([$email]);

        if($check_email->rowCount() > 0){
            $message[] = "Error: This email is already registered for a Sport Teacher!";
        } 
        // === CHECK 2: School already has a Sport Teacher? ===
        else {
            $check_school = $pdo->prepare("SELECT * FROM sport_teachers WHERE school_id = ?");
            $check_school->execute([$school_id]);

            if($check_school->rowCount() > 0){
                $message[] = "This school already has a Sport Teacher assigned!";
            } 
            else {
                // Generate Teacher ID
                $last = $pdo->query("SELECT teacher_id FROM sport_teachers ORDER BY id DESC LIMIT 1")->fetchColumn();
                $num = $last ? (int)str_replace('SPT-', '', $last) + 1 : 1;
                $teacher_id = 'SPT-' . str_pad($num, 4, '0', STR_PAD_LEFT);

                // Get School Details
                $school_stmt = $pdo->prepare("SELECT * FROM schools WHERE school_id = ?");
                $school_stmt->execute([$school_id]);
                $school = $school_stmt->fetch(PDO::FETCH_ASSOC);

                $school_name  = $school['school_name'];
                $region       = $school['region'];
                $district     = $school['district'];
                $ward         = $school['ward'];
                $address      = $school['address'] ?? '';
                $school_phone = $school['phone'] ?? '';

                // Generate Username
                $name_parts = explode(' ', strtolower($full_name));
                $username   = 'spt_' . implode('_', $name_parts);

                // ============== NEW SIMPLE PASSWORD GENERATION ==============
                // Based on name + numbers (simple and memorable)
                $name_clean = strtolower(str_replace(' ', '', $full_name)); // remove spaces
                $name_short = substr($name_clean, 0, 8);                   // take first 8 characters
                $year = date('Y');                                         // current year
                $random_num = rand(10, 99);                                // 2 random digits

                $password_plain = $name_short . $random_num;               // e.g., johnmich25

                $password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);
                // ============================================================

                // INSERT
                try {
                    $insert = $pdo->prepare("
                        INSERT INTO sport_teachers 
                        (teacher_id, username, full_name, gender, phone, email, 
                         school_id, school_name, region, district, ward, password)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $insert->execute([
                        $teacher_id,
                        $username,
                        $full_name,
                        $gender,
                        $phone,
                        $email,
                        $school_id,
                        $school_name,
                        $region,
                        $district,
                        $ward,
                        $password_hashed
                    ]);

                    $message[] = "Sport Teacher Registered Successfully!<br>
                                  <strong>Username:</strong>$username <br>
                                  <strong>Password:</strong><strong>$password_plain</strong>";

                } catch(PDOException $e) {
                    $message[] = "Database Error: " . $e->getMessage();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Sport Teacher</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">

    <style>
        :root {
            --primary: #8e44ad;
            --primary-dark: #6c3483;
            --success: #27ae60;
            --danger: #e74c3c;
            --text: #2c3e50;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            margin: 0;
            padding:6rem 20px;
            min-height: 100vh;
            color: #333;
        }

        .title {
            text-align: center;
            color: var(--text);
            font-size: 2.3rem;
            margin: 25px 0 20px;
            font-weight: 700;
        }

        .message {
            max-width: 680px;
            margin: 12px auto;
            padding: 14px 20px;
            border-radius: 12px;
            font-size: 1.02rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border-left: 6px solid var(--success);
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 6px solid var(--danger);
        }

        .view-btn {
            display: block;
            width: 280px;
            margin: 20px auto;
            padding: 12px 22px;
            background-color: var(--success);
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 50px;
            font-size: 1.08rem;
            font-weight: 600;
            box-shadow: 0 5px 18px rgba(39, 174, 96, 0.3);
            transition: all 0.3s ease;
        }

        .view-btn:hover {
            background-color: #219a52;
            transform: translateY(-3px);
        }

        .form-container {
            max-width: 520px;
            margin: 20px auto;
            background: white;
            padding: 30px 30px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .form-container label {
            display: block;
            margin: 16px 0 8px;
            font-weight: 600;
            color: var(--text);
            font-size: 1.02rem;
        }

        .form-container input[type="text"],
        .form-container input[type="email"],
        .form-container select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #dfe6e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-container input:focus,
        .form-container select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(142, 68, 173, 0.15);
            outline: none;
        }

        .form-container .btn {
            width: 100%;
            margin-top: 25px;
            padding: 14px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.15rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .form-container .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        @media (max-width: 576px) {
            .form-container {
                padding: 25px 20px;
            }
            .title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>

<?php include 'super_admin_header.php'; ?>

<h1 class="title">Register Sport Teacher</h1>

<?php
if(!empty($message)){
    foreach($message as $msg){
        $class = strpos($msg, '✅') !== false ? 'success' : 'error';
        echo '<div class="message ' . $class . '">' . $msg . '</div>';
    }
}
?>

<a href="admin_view_sporty_teacher.php" class="view-btn">
    <i class="fas fa-eye"></i> View Sport Teachers
</a>

<div class="form-container">
    <form method="POST">
        <label>Full Name</label>
        <input type="text" name="full_name" placeholder="Enter full name" required 
               value="<?= isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : '' ?>">

        <label>Gender</label>
        <select name="gender" required>
            <option value="">Select Gender</option>
            <option value="Male" <?= isset($_POST['gender']) && $_POST['gender']=='Male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= isset($_POST['gender']) && $_POST['gender']=='Female' ? 'selected' : '' ?>>Female</option>
        </select>

        <label>Phone Number</label>
        <input type="text" name="phone" placeholder="Enter phone number" required 
               value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">

        <label>Email Address</label>
        <input type="email" name="email" placeholder="Enter email address" required 
               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">

        <label>Select School (Only schools without Sport Teacher)</label>
        <select name="school_id" required>
            <option value="">-- Select School --</option>
            <?php if(count($schools) > 0): ?>
                <?php foreach($schools as $school): ?>
                    <option value="<?= $school['school_id']; ?>" 
                        <?= isset($_POST['school_id']) && $_POST['school_id']==$school['school_id'] ? 'selected' : '' ?>>
                        <?= $school['school_id']; ?> - <?= htmlspecialchars($school['school_name']); ?>
                    </option>
                <?php endforeach; ?>
            <?php else: ?>
                <option value="" abled>No schools available without a Sport Teacher</option>
            <?php endif; ?>
        </select>

        <input type="submit" name="submit" value="Register Sport Teacher" class="btn">
    </form>
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