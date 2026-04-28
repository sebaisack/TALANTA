<?php
session_start();
include "../component/connect.php";

$messages = [];

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $messages[] = ["type" => "error", "text" => "Please enter both username and password!"];
    } else {
        $stmt = $pdo->prepare("
            SELECT id, student_name, username, password 
            FROM students 
            WHERE username = ?
        ");
        $stmt->execute([$username]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student && password_verify($password, $student['password'])) {
            
            $_SESSION['student_id']   = $student['id'];
            $_SESSION['student_name'] = $student['student_name'];
            $_SESSION['username']     = $student['username'];

            header("Location: student_dashboard.php");
            exit;

        } else {
            $messages[] = ["type" => "error", "text" => "Invalid username or password!"];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: #fff;
            max-width: 420px;
            width: 100%;
            margin: 20px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .login-header {
            background: #8e44ad;
            color: white;
            padding: 25px;
            text-align: center;
        }

        .login-header h1 {
            margin: 0;
            font-size: 1.8rem;
        }

        .login-header p {
            margin: 8px 0 0;
            opacity: 0.9;
        }

        .login-body {
            padding: 30px 35px;
        }

        .message {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .success { background: #dff0d8; color: #3c763d; }
        .error   { background: #f2dede; color: #a94442; }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
        }

        .form-group input {
            width: 100%;
            padding: 14px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border 0.3s;
        }

        .form-group input:focus {
            border-color: #8e44ad;
            outline: none;
            box-shadow: 0 0 0 3px rgba(142, 68, 173, 0.1);
        }

        /* Password Toggle Icon */
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 38px;
            cursor: pointer;
            color: #666;
            font-size: 1.1rem;
        }

        .toggle-password:hover {
            color: #8e44ad;
        }

        .forgot-password {
            display: block;
            text-align: right;
            margin-top: 8px;
            font-size: 0.95rem;
            color: #8e44ad;
            text-decoration: none;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background-color: #8e44ad;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
            transition: background 0.3s;
        }

        .login-btn:hover {
            background-color: #6c3483;
        }

        .footer-links {
            text-align: center;
            margin-top: 25px;
            font-size: 0.95rem;
        }

        .footer-links a {
            color: #8e44ad;
            text-decoration: none;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }

        .icon {
            font-size: 3.5rem;
            color: #8e44ad;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <i class="fas fa-user-graduate icon"></i>
        <h1>Student Login</h1>
        <p>Welcome back! Please login to your account</p>
    </div>

    <div class="login-body">
        <?php foreach ($messages as $msg): ?>
            <div class="message <?= $msg['type'] === 'success' ? 'success' : 'error' ?>">
                <i class="fas <?= $msg['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <?= htmlspecialchars($msg['text']) ?>
            </div>
        <?php endforeach; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                <i class="fas fa-eye toggle-password" id="togglePassword"></i>
            </div>

            <!-- Forgot Password Link -->
            <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>

            <button type="submit" name="login" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
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