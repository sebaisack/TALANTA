<?php
// File: /district_manager/district_manager_login.php
ob_start();
session_start();
include "../component/connect.php";

$message = [];

/* ===============================
   DISTRICT MANAGER LOGIN
   =============================== */
if (isset($_POST['login'])) {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $message[] = "Please fill in all fields.";
    } else {
        try {
            // ✅ Check if username OR email exists
            $select = $pdo->prepare("
                SELECT * FROM district_managers 
                WHERE username = ? OR email = ?
                LIMIT 1
            ");
            $select->execute([$username, $username]);

            if ($select->rowCount() > 0) {
                $manager = $select->fetch(PDO::FETCH_ASSOC);

                // ✅ Secure password verification
                if (password_verify($password, $manager['password'])) {

                    /* ✅ SET SESSION VARIABLES */
                    $_SESSION['district_manager_logged_in'] = true;
                    $_SESSION['district_manager_id']        = $manager['id'];
                    $_SESSION['district_manager_full_name'] = $manager['first_name'] . ' ' . $manager['surname'];
                    $_SESSION['district_manager_username']  = $manager['username'];
                    $_SESSION['district_manager_email']     = $manager['email'];
                    $_SESSION['district_manager_region']    = $manager['region'];
                    $_SESSION['district_manager_district']  = $manager['district'];
                    $_SESSION['user_role']                  = 'district_manager';

                    /* ✅ REDIRECT TO DASHBOARD */
                    header("Location: district_manager_dashboard.php");
                    exit();
                    
                } else {
                    $message[] = "Incorrect password!";
                }
            } else {
                $message[] = "Account not found. Please check your username or email.";
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $message[] = "System error. Please try again later.";
        }
    }
}

// ✅ Redirect if already logged in
if (isset($_SESSION['district_manager_logged_in']) && $_SESSION['district_manager_logged_in'] === true) {
    header("Location: district_manager_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>District Manager Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

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
            max-width: 420px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            animation: slideUp 0.4s ease-out;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header i {
            font-size: 3rem;
            color: #3b82f6;
            margin-bottom: 15px;
            display: block;
        }
        
        h2 {
            color: #1e40af;
            font-size: 1.7rem;
            font-weight: 600;
            margin: 0;
        }
        
        .subtitle {
            color: #64748b;
            font-size: 0.95rem;
            margin-top: 8px;
        }
        
        .message {
            background: #fee2e2;
            color: #b91c1c;
            padding: 14px 16px;
            margin-bottom: 20px;
            border-radius: 10px;
            text-align: center;
            font-size: 0.95rem;
            border-left: 5px solid #ef4444;
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
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
            background: #f8fafc;
        }
        
        .input-box input:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
            background: white;
        }
        
        .input-box .icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 1.1rem;
            pointer-events: none;
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
            padding: 5px;
            border-radius: 50%;
        }
        
        .eye:hover {
            color: #3b82f6;
            background: #f1f5f9;
        }
        
        .options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 0.9rem;
        }
        
        .remember {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #475569;
        }
        
        .remember input {
            width: auto;
            margin: 0;
        }
        
        .forgot {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        
        .forgot:hover {
            text-decoration: underline;
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
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .footer {
            text-align: center;
            margin-top: 25px;
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .footer a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-box {
                padding: 30px 25px;
            }
            h2 { font-size: 1.5rem; }
            .input-box input { padding: 12px 40px; font-size: 1rem; }
            button { padding: 13px; font-size: 1rem; }
        }
    </style>
</head>
<body>

<div class="login-box">
    <div class="login-header">
        <i class="fas fa-user-shield"></i>
        <h2>District Manager Login</h2>
        <p class="subtitle">Access your talent management dashboard</p>
    </div>

    <?php if (!empty($message)): ?>
        <?php foreach ($message as $msg): ?>
            <div class="message">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($msg) ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <form method="POST" id="loginForm">
        <div class="input-box">
            <i class="fas fa-user icon"></i>
            <input type="text" name="username" placeholder="Username or Email" 
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autocomplete="username">
        </div>

        <div class="input-box">
            <i class="fas fa-lock icon"></i>
            <input type="password" name="password" id="password" placeholder="Enter Password" 
                   required autocomplete="current-password">
            <i class="fas fa-eye eye" id="togglePassword" title="Show/Hide Password"></i>
        </div>

        <div class="options">
            <label class="remember">
                <input type="checkbox" name="remember"> Remember me
            </label>
            <a href="forgot_password.php" class="forgot">Forgot password?</a>
        </div>

        <button type="submit" name="login">
            <i class="fas fa-sign-in-alt"></i> Login
        </button>
    </form>

    <div class="footer">
        <p>Need help? <a href="mailto:support@talentsystem.tz">Contact Support</a></p>
        <p style="margin-top:8px;font-size:0.85rem;color:#94a3b8;">
            <i class="fas fa-lock"></i> Secure connection • TLS 1.3
        </p>
    </div>
</div>

<script>
// 🔐 Toggle password visibility
const togglePassword = document.getElementById("togglePassword");
const passwordField = document.getElementById("password");

togglePassword.addEventListener("click", function(){
    const type = passwordField.type === "password" ? "text" : "password";
    passwordField.type = type;
    
    // Toggle icon
    this.classList.toggle("fa-eye");
    this.classList.toggle("fa-eye-slash");
    
    // Update title
    this.title = type === "password" ? "Show password" : "Hide password";
});

// 🎯 Allow Enter key to submit form
document.getElementById("loginForm")?.addEventListener("keypress", function(e) {
    if (e.key === "Enter") {
        e.preventDefault();
        this.requestSubmit();
    }
});

// ✨ Add subtle focus animation to inputs
document.querySelectorAll(".input-box input").forEach(input => {
    input.addEventListener("focus", function() {
        this.parentElement.style.transform = "scale(1.02)";
        this.parentElement.style.transition = "transform 0.2s ease";
    });
    input.addEventListener("blur", function() {
        this.parentElement.style.transform = "scale(1)";
    });
});
</script>
<script>
let menuBtn = document.getElementById("menu-btn");
let sidebar = document.getElementById("sidebar");

if(menuBtn && sidebar) {
    menuBtn.addEventListener("click", function() {
        sidebar.classList.toggle("active");
    });
}
</script>
</body>
</html>
<?php ob_end_flush(); ?>