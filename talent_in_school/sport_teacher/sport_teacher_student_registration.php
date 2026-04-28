<?php
session_start();
include "../component/connect.php";

$messages = [];

// Ensure sport teacher is logged in
if (!isset($_SESSION['sport_teacher_id'])) {
    header("Location: sport_teacher_login.php");
    exit;
}

$teacher_id = $_SESSION['sport_teacher_id'];

// Fetch teacher info including school_id
$stmt = $pdo->prepare("SELECT school_id, full_name FROM sport_teachers WHERE teacher_id = ?");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teacher || empty($teacher['school_id'])) {
    die("Error: School information not found. Please contact admin.");
}

$school_id = $teacher['school_id'];

// Fetch ONLY clubs registered by this sport teacher in their school
$clubs_stmt = $pdo->prepare("
    SELECT id, club_name 
    FROM clubs 
    WHERE school_id = ? 
    ORDER BY club_name ASC
");
$clubs_stmt->execute([$school_id]);
$registered_clubs = $clubs_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {

    // Get and sanitize inputs
    $student_name = trim($_POST['student_name'] ?? '');
    $age          = filter_var($_POST['age'] ?? 0, FILTER_VALIDATE_INT);
    $standard     = trim($_POST['standard'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $club         = trim($_POST['club'] ?? '');
    $talent       = trim($_POST['talent'] ?? '');

    // Basic validation
    if (empty($student_name) || empty($standard) || empty($email) || empty($club) || empty($talent)) {
        $messages[] = ["type" => "error", "text" => "Please fill all required fields!"];
    } 
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $messages[] = ["type" => "error", "text" => "Please enter a valid email address!"];
    }
    elseif (!empty($phone) && (!preg_match('/^[0-9]{10}$/', $phone))) {
        $messages[] = ["type" => "error", "text" => "Phone number must be exactly 10 digits (numbers only)!"];
    }
    elseif ($age !== false && $age > 25) {
        $messages[] = ["type" => "error", "text" => "Age must not exceed 25 years!"];
    }
    else {
        // Check if student already exists
        $check = $pdo->prepare("
            SELECT id FROM students 
            WHERE (student_name = ? AND school_id = ?) 
               OR email = ?
        ");
        $check->execute([$student_name, $school_id, $email]);

        if ($check->rowCount() > 0) {
            $messages[] = ["type" => "error", "text" => "A student with this name in your school or this email already exists!"];
        } else {
            // Generate username and password
            $base_username = strtolower(str_replace([' ', '-'], '.', $student_name));
            $username = $base_username;
            $counter = 1;

            while (true) {
                $stmt = $pdo->prepare("SELECT username FROM students WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->rowCount() == 0) {
                    break;
                }
                $username = $base_username . $counter++;
            }

            $password_plain = ucfirst(explode(' ', $student_name)[0] ?? 'Student') . rand(100, 999);
            $password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);

            // Insert new student
            try {
                $insert = $pdo->prepare("
                    INSERT INTO students 
                    (student_name, age, standard, school_id, email, phone, club, talent, username, password)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $insert->execute([
                    $student_name,
                    $age,
                    $standard,
                    $school_id,
                    $email,
                    $phone,
                    $club,
                    $talent,
                    $username,
                    $password_hashed
                ]);

                $messages[] = [
                    "type" => "success", 
                    "text" => "Student registered successfully!",
                    "username" => $username,
                    "password" => $password_plain
                ];

            } catch (PDOException $e) {
                $messages[] = ["type" => "error", "text" => "Registration failed! Please try again later."];
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
    <title>Register New Student</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            padding:6rem 20px;
        }
        .title {
            text-align: center;
            color: #2c3e50;
            margin: 2rem 0;
        }
        .message {
            padding: 12px 15px;
            margin: 15px auto;
            border-radius: 5px;
            width: 90%;
            max-width: 520px;
            text-align: center;
        }
        .success { background: #dff0d8; color: #3c763d; }
        .error   { background: #f2dede; color: #a94442; }

        .form-container {
            max-width: 520px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .form-container input,
        .form-container select {
            width: 100%;
            padding: 0.9rem;
            margin-bottom: 1rem;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 1rem;
            box-sizing: border-box;
        }
        .form-container input[type="submit"] {
            background-color: #8e44ad;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
            font-size: 1.05rem;
            padding: 0.9rem;
        }
        .form-container input[type="submit"]:hover {
            background-color: #6c3483;
        }
        .credentials {
            background: #e8f5e9;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: center;
            border: 2px solid #27ae60;
        }
        .view-btn {
            display: block;
            width: 220px;
            margin: 1.5rem auto;
            padding: 0.9rem 1.5rem;
            background-color: #2980b9;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }
        .view-btn:hover { background-color: #1c5980; }
        
        /* Phone input styling for validation feedback */
        .form-container input[name="phone"]:invalid:not(:placeholder-shown) {
            border-color: #a94442;
            background-color: #f2dede;
        }
        .form-container input[name="phone"]:valid:not(:placeholder-shown) {
            border-color: #3c763d;
            background-color: #dff0d8;
        }
    </style>
</head>
<body>

<?php include 'sport_teacher_header.php'; ?>

<h1 class="title">Register New Student</h1>

<?php foreach ($messages as $msg): ?>
    <div class="message <?= $msg['type'] === 'success' ? 'success' : 'error' ?>">
        <i class="fas <?= $msg['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
        <?= htmlspecialchars($msg['text']) ?>
    </div>
<?php endforeach; ?>

<a href="view_student.php" class="view-btn">
    <i class="fas fa-eye"></i> View My Students
</a>

<div class="form-container">
    <form method="POST" autocomplete="off" id="registrationForm">
        <input type="text" name="student_name" placeholder="Full Student Name" required>
        
        <!-- Age: Removed minimum limit, kept max 25 as reasonable upper bound -->
        <input type="number" name="age" max="25" placeholder="Age (e.g., 4, 10, 18)" required>
        
        <input type="text" name="standard" placeholder="Standard / Class (e.g. Form 2, Grade 7)" required>
        <input type="email" name="email" placeholder="Email Address" required>
        
        <!-- Phone: 10 digits only, numbers only -->
        <input type="tel" name="phone" pattern="[0-9]{10}" maxlength="10" 
               placeholder="Phone Number (10 digits only)" 
               title="Please enter exactly 10 digits (0-9 only, no spaces or symbols)"
               inputmode="numeric">
        
        <!-- Club Selection - ONLY registered clubs by this teacher -->
        <select name="club" required>
            <option value="">-- Select Registered Club --</option>
            <?php if (count($registered_clubs) > 0): ?>
                <?php foreach($registered_clubs as $club): ?>
                    <option value="<?= htmlspecialchars($club['club_name']) ?>">
                        <?= htmlspecialchars($club['club_name']) ?>
                    </option>
                <?php endforeach; ?>
            <?php else: ?>
                <option value="" disabled>No clubs registered yet. Please register a club first.</option>
            <?php endif; ?>
        </select>

        <!-- Talent Selection - Dropdown with Global Talent Categories -->
        <select name="talent" required>
            <option value="">-- Select Talent --</option>
            
            <optgroup label="Music">
                <option value="Classical Music (Instrumental)">Classical Music (Instrumental)</option>
                <option value="Contemporary Music (Vocal)">Contemporary Music (Vocal)</option>
                <option value="Jazz">Jazz</option>
                <option value="Opera">Opera</option>
                <option value="Choir Singing">Choir Singing</option>
                <option value="Composition/Arrangement">Composition/Arrangement</option>
                <option value="Music Production (Digital)">Music Production (Digital)</option>
                <option value="DJing/Turntablism">DJing/Turntablism</option>
            </optgroup>
            
            <optgroup label="Dance">
                <option value="Ballet">Ballet</option>
                <option value="Hip-Hop Dance">Hip-Hop Dance</option>
                <option value="Contemporary Dance">Contemporary Dance</option>
                <option value="Jazz Dance">Jazz Dance</option>
                <option value="Ballroom Dance">Ballroom Dance</option>
                <option value="Traditional Cultural Dance">Traditional Cultural Dance</option>
                <option value="Breakdancing">Breakdancing</option>
                <option value="Salsa">Salsa</option>
            </optgroup>
            
            <optgroup label="Theater/Drama">
                <option value="Acting (Stage)">Acting (Stage)</option>
                <option value="Dramatic Monologue">Dramatic Monologue</option>
                <option value="Improv Acting">Improv Acting</option>
                <option value="Comedy Skits">Comedy Skits</option>
                <option value="Puppetry">Puppetry</option>
                <option value="Theater Directing">Theater Directing</option>
                <option value="Stage Design and Lighting">Stage Design and Lighting</option>
            </optgroup>
            
            <optgroup label="Visual Arts">
                <option value="Painting">Painting</option>
                <option value="Sculpting">Sculpting</option>
                <option value="Photography">Photography</option>
                <option value="Graphic Design">Graphic Design</option>
                <option value="Animation (Digital)">Animation (Digital)</option>
                <option value="Fashion Design">Fashion Design</option>
            </optgroup>
            
            <optgroup label="Literary Arts">
                <option value="Poetry">Poetry</option>
                <option value="Story Writing">Story Writing</option>
                <option value="Spoken Word">Spoken Word</option>
                <option value="Public Speaking">Public Speaking</option>
                <option value="Creative Writing (Short Stories)">Creative Writing (Short Stories)</option>
                <option value="Debate (Public Speaking)">Debate (Public Speaking)</option>
            </optgroup>
            
            <optgroup label="Sports Talent">
                <option value="Basketball">Basketball</option>
                <option value="Soccer">Soccer</option>
                <option value="Swimming">Swimming</option>
                <option value="Track and Field">Track and Field</option>
                <option value="Martial Arts">Martial Arts</option>
                <option value="Gymnastics">Gymnastics</option>
            </optgroup>
            
            <optgroup label="Culinary Arts">
                <option value="Cooking (Professional Level)">Cooking (Professional Level)</option>
                <option value="Baking (Cakes, Pastries)">Baking (Cakes, Pastries)</option>
                <option value="Food Photography and Styling">Food Photography and Styling</option>
                <option value="Barista Art (Coffee Making)">Barista Art (Coffee Making)</option>
            </optgroup>
            
            <optgroup label="Technology-Related Talents">
                <option value="Robotics">Robotics</option>
                <option value="Game Development (Coding)">Game Development (Coding)</option>
                <option value="Web Development (Design & Development)">Web Development (Design & Development)</option>
                <option value="3D Printing">3D Printing</option>
                <option value="Virtual Reality Development">Virtual Reality Development</option>
            </optgroup>
            
            <optgroup label="ACADEMIC MASTERY TALENT">
                <option value="Academic">AWALI (KUSOMA)</option>
                <option value="Academic">MSINGI STNA (KUSOMA)</option>
                <option value="Academic">MSINGI STNA (KUHESABU)</option>
                <option value="Academic">MSINGI PSLE (HISABATI & SAYANSI)</option>
                <option value="Academic">MSINGI PSLE (HTM & JIOGRAFIA)</option>
                <option value="Academic">MAHIRI CSE (PHYSICS & MATHS)</option>
                <option value="Academic">MAHIRI CSE (CHEMISTRY & BIOLOGY)</option>
                <option value="Academic">MAHIRI CSE (KISWAHILI & ENGLISH)</option>
                 <option value="Academic">MAHIRI CSE (HISTORY & GEOGRAPHY)</option>
                  <option value="Academic"> MAHIRI CSE (AGRICULTURE)</option>
                   <option value="Academic">MAKINI ACSE (PHYSICS & MATHS)</option>
                    <option value="Academic">MAKINI ACSE (KISWAHILI & ENGLISH)</option>
                     <option value="Academic">MAKINI ACSE (HISTORY & GEOGRAPHY)</option>
                      <option value="Academic">MAKINI ACSE (AGRICULTURE)</option>
                       <option value="Academic">MAKINI ACSE (BIOLOGY & NUTRITION)</option>
                        <option value="Academic">MAKINI ACSE (TAILORING)</option>
                        <option value="Academic">MAKINI ACSE (MUSIC LIVE BAND)</option>
                        <option value="Academic">MAKINI ACSE (FUNDI UMEME)</option>
            </optgroup>
        </select>

        <input type="submit" name="submit" value="Register Student">
    </form>

    <?php if (!empty($messages) && end($messages)['type'] === 'success'): 
        $last = end($messages);
    ?>
        <div class="credentials">
            <strong>✅ Student Registered Successfully!</strong><br><br>
            <strong>Username:</strong> <?= htmlspecialchars($last['username']) ?><br>
            <strong>Password:</strong> <?= htmlspecialchars($last['password']) ?><br><br>
            <small>Please save these credentials securely. They will not be shown again.</small>
        </div>
    <?php endif; ?>
</div>

<script>
// Menu toggle
let menuBtn = document.getElementById("menu-btn");
let sidebar = document.getElementById("sidebar");

if (menuBtn && sidebar) {
    menuBtn.addEventListener("click", function() {
        sidebar.classList.toggle("active");
    });
}

// Phone number validation: Allow only digits, auto-format to 10 digits max
document.addEventListener('DOMContentLoaded', function() {
    const phoneInput = document.querySelector('input[name="phone"]');
    
    if (phoneInput) {
        // Restrict input to digits only
        phoneInput.addEventListener('input', function(e) {
            // Remove any non-digit characters
            this.value = this.value.replace(/[^0-9]/g, '');
            // Limit to 10 digits
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
        });
        
        // Prevent paste of non-digit content
        phoneInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text');
            const digitsOnly = pasted.replace(/[^0-9]/g, '').slice(0, 10);
            this.value = digitsOnly;
        });
    }
    
    // Form submission validation for phone
    const form = document.getElementById('registrationForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const phone = phoneInput?.value.trim();
            if (phone && !/^[0-9]{10}$/.test(phone)) {
                e.preventDefault();
                alert('Phone number must be exactly 10 digits (numbers only)!');
                phoneInput?.focus();
            }
        });
    }
});
</script>


<script>
// Menu toggle
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