<?php
session_start();
include "../component/connect.php";

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

$student_name = htmlspecialchars($_SESSION['student_name'] ?? 'Student');
$student_id = $_SESSION['student_id'];

// Fetch recent talent announcements (club events, competitions, auditions)
$announcements = [];
try {
    $stmt = $pdo->prepare("
        SELECT title, message, created_at 
        FROM announcements 
        WHERE visible_to_students = 1 
        AND category IN ('talent', 'club', 'sport', 'event')
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error silently or log it
}

// Fetch talent-related stats (example queries - adjust table/column names as needed)
$stats = [
    'talent_score' => '92/100',
    'club_participation' => '4 clubs',
    'upcoming_auditions' => '2 pending',
    'achievements' => '7 earned'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Talent Dashboard - Student Portal</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    
    <style>
        :root {
            --primary: #8e44ad;
            --primary-dark: #6c3483;
            --secondary: #667eea;
            --gradient-start: #667eea;
            --gradient-end: #764ba2;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --bg-light: #f8f9fa;
            --white: #ffffff;
            --shadow: 0 4px 20px rgba(0,0,0,0.08);
            --shadow-hover: 0 8px 30px rgba(0,0,0,0.12);
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
        }

        /* ===== SIDEBAR STYLES ===== */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            color: var(--white);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding: 25px 0;
            box-shadow: var(--shadow);
            z-index: 1000;
            transition: var(--transition);
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 0 25px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 20px;
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-header h2 i {
            font-size: 1.8rem;
        }

        .nav-links {
            list-style: none;
            padding: 0 10px;
        }

        .nav-links li {
            margin-bottom: 8px;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 20px;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            transition: var(--transition);
            font-size: 1rem;
        }

        .nav-links a:hover,
        .nav-links a.active {
            background: rgba(255,255,255,0.15);
            color: var(--white);
            transform: translateX(4px);
        }

        .nav-links a i {
            width: 24px;
            text-align: center;
            font-size: 1.1rem;
        }

        .nav-links a.logout {
            margin-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.2);
            padding-top: 15px;
            color: #ffcccc;
        }

        .nav-links a.logout:hover {
            background: rgba(255,100,100,0.2);
            color: #fff;
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 30px;
            transition: var(--transition);
        }

        /* Header */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }

        .welcome-section h1 {
            font-size: 1.8rem;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .welcome-section p {
            color: var(--text-light);
            font-size: 1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--white);
            padding: 10px 20px;
            border-radius: 50px;
            box-shadow: var(--shadow);
        }

        .user-info .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .user-info span {
            font-weight: 600;
            color: var(--text-dark);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            padding: 22px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 18px;
            transition: var(--transition);
            border-left: 4px solid var(--primary);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .stat-icon {
            width: 55px;
            height: 55px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .stat-info h3 {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 4px;
        }

        .stat-info p {
            color: var(--text-light);
            font-size: 0.95rem;
            font-weight: 500;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 25px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .card-header h3 {
            font-size: 1.3rem;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header h3 i {
            color: var(--primary);
        }

        .card-header a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .card-header a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        /* Announcements List */
        .announcement-item {
            padding: 16px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .announcement-item:last-child {
            border-bottom: none;
        }

        .announcement-item h4 {
            font-size: 1.05rem;
            color: var(--text-dark);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .announcement-item h4 i {
            color: var(--primary);
            font-size: 0.9rem;
        }

        .announcement-item p {
            color: var(--text-light);
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 8px;
        }

        .announcement-item .date {
            font-size: 0.85rem;
            color: #999;
            font-weight: 500;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px 15px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            text-decoration: none;
            color: var(--text-dark);
            transition: var(--transition);
            text-align: center;
            gap: 10px;
        }

        .action-btn:hover {
            border-color: var(--primary);
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            transform: translateY(-3px);
        }

        .action-btn i {
            font-size: 1.8rem;
            color: var(--primary);
        }

        .action-btn:hover i {
            color: white;
        }

        .action-btn span {
            font-weight: 600;
            font-size: 0.95rem;
        }

        /* Footer */
        .dashboard-footer {
            text-align: center;
            padding: 25px;
            color: var(--text-light);
            font-size: 0.9rem;
            margin-top: 20px;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .menu-toggle {
                display: block !important;
            }
        }

        .menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: var(--primary);
            color: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 10px;
            font-size: 1.3rem;
            cursor: pointer;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .menu-toggle:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
        }

        /* Badge for messages */
        .badge {
            background: #e74c3c;
            color: white;
            font-size: 0.75rem;
            padding: 3px 10px;
            border-radius: 20px;
            margin-left: auto;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <!-- Mobile Menu Toggle -->
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- ===== SIDEBAR ===== -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-graduation-cap"></i> Talent Portal</h2>
        </div>
        
        <ul class="nav-links">
            <li>
                <a href="student_dashboard.php" class="active">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
            </li>
            
            <li>
                <a href="student_results.php">
                    <i class="fas fa-trophy"></i>
                    <span>My Talent Scores</span>
                </a>
            </li>
            
            <li>
                <a href="student_clubs.php">
                    <i class="fas fa-futbol"></i>
                    <span>Clubs & Teams</span>
                </a>
            </li>
            
            <li>
                <a href="manage_announcements.php">
                    <i class="fas fa-bullhorn"></i>
                    <span>Events & Auditions</span>
                </a>
            </li>

            <li>
                <a href="message.php">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                    <span class="badge">3</span>
                </a>
            </li>

            <li>
                <a href="logout.php" class="logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>        
            </li>
        </ul>
    </div>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="main-content">
        
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="welcome-section">
                <h1>Welcome back, <?= $student_name ?>! 🎭</h1>
                <p>Track your talents, clubs, and upcoming performances</p>
            </div>
            <div class="user-info">
                <div class="avatar"><?= strtoupper(substr($student_name, 0, 1)) ?></div>
                <span><?= htmlspecialchars($student_name) ?></span>
            </div>
        </div>

        <!-- Talent Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['talent_score'] ?></h3>
                    <p>Overall Talent Score</p>
                </div>
            </div>
            
            <!--<div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
               <div class="stat-info">
                    <h3><?= $stats['club_participation'] ?></h3>
                    <p>Active Clubs</p>
                </div>
            </div>-->
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-microphone-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['upcoming_auditions'] ?></h3>
                    <p>Pending Auditions</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-medal"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['achievements'] ?></h3>
                    <p>Achievements Earned</p>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="content-grid">
            
            <!-- Left Column: Talent Announcements & Events -->
            <div class="left-column">
                  <!-- Upcoming Talent Deadlines / Performances -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-alt"></i> Upcoming Performances & Deadlines</h3>
                        <a href="#">View Full Calendar</a>
                    </div>
                    <div class="announcement-item">
                        <h4><i class="fas fa-music"></i> School Talent Show Auditions</h4>
                        <p>Submit your performance video for the annual talent showcase</p>
                        <span class="date"><i class="far fa-clock"></i> Due: Apr 28, 2026</span>
                    </div>
                    <div class="announcement-item">
                        <h4><i class="fas fa-futbol"></i> Inter-School Sports Trial</h4>
                        <p>Registration closes soon for regional football trials</p>
                        <span class="date"><i class="far fa-clock"></i> Due: May 2, 2026</span>
                    </div>
                    <div class="announcement-item">
                        <h4><i class="fas fa-palette"></i> Art Exhibition Submission</h4>
                        <p>Submit your best artwork for the spring gallery display</p>
                        <span class="date"><i class="far fa-clock"></i> Due: May 5, 2026</span>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-bullhorn"></i> Talent Events & Auditions</h3>
                        <a href="manage_announcements.php">View All</a>
                    </div>
                    
                    <?php if (!empty($announcements)): ?>
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="announcement-item">
                                <h4><i class="fas fa-circle"></i> <?= htmlspecialchars($announcement['title']) ?></h4>
                                <p><?= htmlspecialchars(substr($announcement['message'], 0, 120)) ?><?= strlen($announcement['message']) > 120 ? '...' : '' ?></p>
                                <span class="date"><i class="far fa-clock"></i> <?= date('M d, Y', strtotime($announcement['created_at'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: var(--text-light); text-align: center; padding: 20px;">
                            <i class="fas fa-info-circle"></i> No upcoming talent events at this time.
                        </p>
                    <?php endif; ?>
                </div>

              
            </div>

            <!-- Right Column: Talent Quick Actions -->
            <div class="right-column">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-bolt"></i> Quick Talent Actions</h3>
                    </div>
                    
                    <div class="quick-actions">
                        <a href="student_results.php" class="action-btn">
                            <i class="fas fa-trophy"></i>
                            <span>View Talent Scores</span>
                        </a>
                        <a href="message.php" class="action-btn">
                            <i class="fas fa-envelope"></i>
                            <span>Contact Coach</span>
                        </a>
                        <a href="student_clubs.php" class="action-btn">
                            <i class="fas fa-futbol"></i>
                            <span>Join a Club</span>
                        </a>
                        <a href="#" class="action-btn">
                            <i class="fas fa-video"></i>
                            <span>Upload Portfolio</span>
                        </a>
                        <a href="#" class="action-btn">
                            <i class="fas fa-user-graduate"></i>
                            <span>My Talent Profile</span>
                        </a>
                        <a href="#" class="action-btn">
                            <i class="fas fa-cog"></i>
                            <span>Notification Settings</span>
                        </a>
                    </div>
                </div>

                <!-- Talent Profile Summary 
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user"></i> Talent Profile</h3>
                    </div>
                    <div style="text-align: center; padding: 15px 0;">
                        <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end)); margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; font-weight: bold;">
                            <?= strtoupper(substr($student_name, 0, 1)) ?>
                        </div>
                        <h4 style="margin-bottom: 5px; color: var(--text-dark);"><?= htmlspecialchars($student_name) ?></h4>
                        <p style="color: var(--text-light); font-size: 0.95rem; margin-bottom: 15px;">Talent ID: #<?= str_pad($student_id, 6, '0', STR_PAD_LEFT) ?></p>
                        <a href="#" style="color: var(--primary); text-decoration: none; font-weight: 500; font-size: 0.95rem;">
                            <i class="fas fa-edit"></i> Update Talent Profile
                        </a>
                    </div>
                </div>-->
            </div>
        </div>

        <!-- Footer -->
        <div class="dashboard-footer">
            <p>&copy; <?= date('Y') ?> School Talent Management System. All rights reserved. | <a href="#" style="color: var(--primary); text-decoration: none;">Privacy Policy</a> | <a href="#" style="color: var(--primary); text-decoration: none;">Help Center</a></p>
        </div>

    </div>

    <!-- JavaScript for Interactions -->
    <script>
        // Mobile sidebar toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 992 && 
                !sidebar.contains(e.target) && 
                !menuToggle.contains(e.target) && 
                sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });

        // Add active class to current page nav item
        document.addEventListener('DOMContentLoaded', () => {
            const currentPath = window.location.pathname.split('/').pop();
            document.querySelectorAll('.nav-links a').forEach(link => {
                const href = link.getAttribute('href');
                if (href === currentPath) {
                    link.classList.add('active');
                }
            });
        });
    </script>

</body>
</html>