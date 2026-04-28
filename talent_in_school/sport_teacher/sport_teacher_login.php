<?php
ob_start();
session_start();
include "../component/connect.php";

$message = [];

/* ===============================
   SPORT TEACHER LOGIN
   =============================== */
if (isset($_POST['login'])) {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {

        $select = $pdo->prepare("
            SELECT * FROM sport_teachers 
            WHERE username = ? 
            LIMIT 1
        ");
        $select->execute([$username]);

        if ($select->rowCount() > 0) {
            $teacher = $select->fetch(PDO::FETCH_ASSOC);

            // Secure password verification
            if (password_verify($password, $teacher['password'])) {

                /* SET SESSION VARIABLES */
                $_SESSION['sport_teacher_logged_in'] = true;
                $_SESSION['sport_teacher_id']        = $teacher['teacher_id'];
                $_SESSION['sport_teacher_full_name'] = $teacher['full_name'];
                $_SESSION['user_role']               = $teacher['user_role'];
                $_SESSION['sport_teacher_username']  = $teacher['username'];
                $_SESSION['sport_teacher_email']     = $teacher['email'];
                $_SESSION['school_id']               = $teacher['school_id'];  

                /* REDIRECT TO DASHBOARD */
                header("Location: sport_teacher_dashboard.php");
                exit();
            } else {
                $message[] = "Incorrect password!";
            }
        } else {
            $message[] = "Username not found!";
        }
    } else {
        $message[] = "Please fill all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sport Teacher Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-box {
            background: white;
            padding: 40px 35px;
            width: 100%;
            max-width: 400px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }
        
        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #1e40af;
            font-size: 1.85rem;
            font-weight: 600;
        }
        
        h2 i {
            margin-right: 10px;
            color: #3b82f6;
        }
        
        .message {
            background: #fee2e2;
            color: #b91c1c;
            padding: 14px 16px;
            margin-bottom: 20px;
            border-radius: 10px;
            text-align: center;
            font-size: 0.98rem;
            border-left: 5px solid #ef4444;
        }
        
        .input-box {
            position: relative;
            margin-bottom: 22px;
        }
        
        .input-box input {
            width: 100%;
            padding: 14px 45px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1.02rem;
            transition: all 0.3s ease;
        }
        
        .input-box input:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
        }
        
        .input-box .icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 1.1rem;
        }
        
        .eye {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #64748b;
            font-size: 1.25rem;
            transition: color 0.3s;
        }
        
        .eye:hover {
            color: #3b82f6;
        }
        
        button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(to right, #3b82f6, #1e40af);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            margin-top: 10px;
            transition: all 0.3s ease;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.4);
        }

        @media (max-width: 480px) {
            .login-box {
                padding: 30px 25px;
            }
        }
    </style>
</head>
<body>

<div class="login-box">
    <h2>
        <i class="fas fa-running"></i> Sport Teacher Login
    </h2>

    <?php
    if (!empty($message)) {
        foreach ($message as $msg) {
            echo '<div class="message">' . htmlspecialchars($msg) . '</div>';
        }
    }
    ?>

    <form method="POST">
        <div class="input-box">
            <i class="fas fa-user icon"></i>
            <input type="text" name="username" placeholder="Enter Username" required>
        </div>

        <div class="input-box">
            <i class="fas fa-lock icon"></i>
            <input type="password" name="password" id="password" placeholder="Enter Password" required>
            <i class="fas fa-eye eye" id="togglePassword"></i>
        </div>

        <button type="submit" name="login">Login</button>
    </form>
</div>

<script>
// Toggle password visibility
const togglePassword = document.getElementById("togglePassword");
const passwordField = document.getElementById("password");

togglePassword.addEventListener("click", function(){
    const type = passwordField.type === "password" ? "text" : "password";
    passwordField.type = type;
    this.classList.toggle("fa-eye-slash");
});
</script>

</body>
</html>
<?php ob_end_flush(); ?>