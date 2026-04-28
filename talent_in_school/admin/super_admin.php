<?php
session_start();
$message = [];

// Include PDO connection
include "../component/connect.php";

// Ensure Super Admin exists (auto-create if not)
$stmt = $pdo->prepare("SELECT * FROM super_admins WHERE email=?");
$stmt->execute(['superadmin@example.com']);
if($stmt->rowCount() == 0){
    $hashed_password = password_hash("3901", PASSWORD_DEFAULT);
    $insert = $pdo->prepare("INSERT INTO super_admins(name,email,password) VALUES(?,?,?)");
    $insert->execute([
        "Super Admin",
        "superadmin@example.com",
        $hashed_password
    ]);
}

// Handle Super Admin login
if($_SERVER['REQUEST_METHOD'] == "POST"){
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM super_admins WHERE email=?");
    $stmt->execute([$email]);
    $super = $stmt->fetch(PDO::FETCH_ASSOC);

    if($super && password_verify($password, $super['password'])){
        $_SESSION['user_id'] = $super['id'];
        $_SESSION['user_name'] = $super['name'];
        $_SESSION['user_role'] = "super_admin";

        header("Location: super_admin_home.php");
        exit;
    } else {
        $message[] = "Invalid Super Admin email or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Login</title>
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
            /*background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);*/
            background: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-box {
            background: white;
            width: 100%;
            max-width: 400px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
        }

        .login-header {
            background: linear-gradient(to right, #8e44ad, #6c3483);
            padding: 35px 20px;
            text-align: center;
            color: white;
        }

        .login-header i {
            font-size: 3.5rem;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .login-header h2 {
            font-size: 1.9rem;
            margin: 0;
            font-weight: 600;
        }

        .login-body {
            padding: 40px 35px;
        }

        .message {
            background: #fee2e2;
            color: #b91c1c;
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 22px;
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1.02rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: #8e44ad;
            outline: none;
            box-shadow: 0 0 0 4px rgba(142, 68, 173, 0.1);
        }

        /* Password Toggle */
        .toggle-password {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.3rem;
            color: #64748b;
            cursor: pointer;
            transition: color 0.3s;
        }

        .toggle-password:hover {
            color: #8e44ad;
        }

        button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(to right, #8e44ad, #6c3483);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.15rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.3s;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(142, 68, 173, 0.3);
        }

        .footer {
            text-align: center;
            margin-top: 25px;
            font-size: 0.97rem;
        }

        .footer a {
            color: #8e44ad;
            text-decoration: none;
            font-weight: 500;
        }

        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="login-box">
    <!-- Header -->
    <div class="login-header">
        <i class="fas fa-user-shield"></i>
        <h2>Super Admin Login</h2>
    </div>

    <!-- Body -->
    <div class="login-body">
        <?php if(!empty($message)): ?>
            <?php foreach($message as $msg): ?>
                <div class="message">
                    <span><?= htmlspecialchars($msg) ?></span>
                    <i class="fas fa-times" onclick="this.parentElement.remove();" style="cursor:pointer;"></i>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <input type="email" name="email" placeholder="Super Admin Email" required>
            </div>

            <div class="form-group">
                <input type="password" name="password" id="password" placeholder="Password" required>
                <i class="fas fa-eye toggle-password" id="togglePassword"></i>
            </div>

            <button type="submit">Sign In</button>
        </form>

       
    </div>
</div>

<script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');

    togglePassword.addEventListener('click', function () {
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);
        
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });
</script>

</body>
</html>