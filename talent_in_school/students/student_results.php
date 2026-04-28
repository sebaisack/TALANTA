<?php
// File: /student_results.php
session_start();
include "../component/connect.php";

// 🔐 CRITICAL: Ensure student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

// 🔒 Get ONLY the logged-in student's ID from session (NEVER from user input)
$student_id = $_SESSION['student_id'];

// 🔒 Fetch ONLY this student's results - SCOPED BY SESSION ID
$results = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            s.student_name,
            s.username,
            s.club,
            s.talent,
            r.total_score as score,
            r.grade,
            r.auto_remarks,
            r.pending status,
            r.uploaded_at as submitted
        FROM results r
        INNER JOIN students s ON r.student_id = s.id
        WHERE r.student_id = ?  -- ⚠️ ONLY the session student_id
        ORDER BY r.uploaded_at DESC
    ");
    $stmt->execute([$student_id]); // ← Session ID, never user-controlled
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Results error: " . $e->getMessage());
}

// 🎯 Helper functions for display
function getGradeClass($grade) {
    $g = strtoupper($grade[0] ?? '');
    return match($g) {
        'A' => 'grade-a',
        'B' => 'grade-b',
        'C' => 'grade-c',
        'D', 'E' => 'grade-d',
        'F' => 'grade-f',
        default => ''
    };
}
function getStatusClass($status) {
    return match($status) {
        'Pending' => 'status-pending',
        'Promoted' => 'status-promoted',
        'Reviewed' => 'status-reviewed',
        default => 'status-default'
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Results - Student Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        /* ===== CSS VARIABLES ===== */
        :root {
            --primary: #8e44ad;
            --primary-dark: #6c3483;
            --gradient-start: #667eea;
            --gradient-end: #764ba2;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --bg-light: #f4f6f9;
            --white: #ffffff;
            --shadow: 0 4px 20px rgba(0,0,0,0.08);
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }

        /* ===== GLOBAL RESET ===== */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
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

        .badge {
            background: #e74c3c;
            color: white;
            font-size: 0.75rem;
            padding: 3px 10px;
            border-radius: 20px;
            margin-left: auto;
            font-weight: 600;
        }

        /* ===== MAIN CONTENT WRAPPER ===== */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 20px;
            transition: var(--transition);
            min-height: 100vh;
            width: 100%;
        }

        /* ===== CONTAINER & HEADER ===== */
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .header {
            padding: 20px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .header h2 {
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 0.95rem;
            margin-top: 5px;
        }

        .table-wrap {
            padding: 10px;
        }

        /* ===== VERTICAL TWO-COLUMN TABLE STYLES ===== */
        .result-card {
            margin-bottom: 20px;
            border: 1px solid #eee;
            border-radius: 10px;
            overflow: hidden;
        }

        .result-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
        }

        .result-card-header .checkbox-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .result-card-header .result-index {
            font-weight: 600;
            color: var(--primary);
            font-size: 0.95rem;
        }

        .vertical-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        .vertical-table th {
            width: 40%;
            background: #f8f9fa;
            color: var(--text-dark);
            padding: 14px 20px;
            text-align: left;
            font-weight: 600;
            border-bottom: 1px solid #eee;
            font-size: 0.9rem;
        }

        .vertical-table td {
            width: 60%;
            padding: 14px 20px;
            border-bottom: 1px solid #eee;
            color: var(--text-dark);
        }

        .vertical-table tr:last-child th,
        .vertical-table tr:last-child td {
            border-bottom: none;
        }

        .vertical-table tr:hover {
            background: #f8f9ff;
        }

        /* Value styling */
        .value-username { color: #2980b9; font-family: monospace; font-size: 0.9rem; }
        .value-score { font-weight: 700; font-size: 1.1rem; color: var(--text-dark); }
        .value-date { color: var(--text-light); font-size: 0.9rem; }

        /* Grade badges */
        .grade-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .grade-a { background: #d4edda; color: #155724; }
        .grade-b { background: #cce5ff; color: #004085; }
        .grade-c { background: #fff3cd; color: #856404; }
        .grade-d { background: #ffe5d0; color: #995406; }
        .grade-f { background: #f8d7da; color: #721c24; }

        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending { background: #fff3e0; color: #ef6c00; }
        .status-promoted { background: #e8f5e9; color: #2e7d32; }
        .status-reviewed { background: #e3f2fd; color: #1565c0; }
        .status-default { background: #f5f5f5; color: #666; }

        /* Auto Remarks cell styling */
        .auto-remarks-cell {
            font-size: 0.9rem;
            color: #444;
            line-height: 1.5;
            white-space: normal;
            word-wrap: break-word;
            background: #f8f9fa;
            padding: 12px 15px;
            border-radius: 6px;
            border-left: 3px solid var(--primary);
        }
        .auto-remarks-cell:empty::before {
            content: 'No auto feedback';
            color: #999;
            font-style: italic;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-light);
        }
        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 15px;
        }

        /* Checkbox styling */
        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary);
        }

        /* ===== MOBILE MENU TOGGLE ===== */
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

        /* ===== RESPONSIVE BREAKPOINTS ===== */
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .menu-toggle { display: block; }
        }

        @media (max-width: 768px) {
            body { padding: 10px; }
            .header { padding: 15px 20px; }
            .vertical-table th,
            .vertical-table td { padding: 12px 15px; font-size: 0.9rem; }
            .vertical-table th { width: 45%; }
            .vertical-table td { width: 55%; }
            .result-card-header { flex-direction: column; align-items: flex-start; gap: 10px; }
        }
    </style>
</head>
<body>

    <!-- Mobile Menu Toggle Button -->
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- ===== SIDEBAR ===== -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-graduation-cap"></i> Student Portal</h2>
        </div>
        
        <ul class="nav-links">
            <li>
                <a href="student_dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
            </li>
            
            <li>
                <a href="student_results.php" class="active">
                    <i class="fas fa-trophy"></i>
                    <span>Results</span>
                </a>
            </li>
            
            <li>
                <a href="student_clubs.php">
                    <i class="fas fa-futbol"></i>
                    <span>Clubs</span>
                </a>
            </li>
            
            <li>
                <a href="manage_announcements.php">
                    <i class="fas fa-bullhorn"></i>
                    <span>Announcements</span>
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

    <!-- ===== MAIN CONTENT WRAPPER ===== -->
    <div class="main-content">

        <!-- ===== CONTAINER & HEADER ===== -->
        <div class="container">
            <div class="header">
                <h2><i class="fas fa-trophy"></i> My Results</h2>
                <p>Viewing results for: <strong><?= htmlspecialchars($_SESSION['student_name'] ?? 'Student') ?></strong></p>
            </div>

            <div class="table-wrap">
                
                <?php if (!empty($results)): ?>
                    <?php $index = 1; ?>
                    <?php foreach ($results as $row): ?>
                    <!-- ===== SINGLE RESULT CARD (Vertical 2-Column, 9 Rows) ===== -->
                    <div class="result-card">
                        
                        <!-- Card Header with Checkbox -->
                        <div class="result-card-header">
                            <div class="checkbox-wrap">
                                <input type="checkbox" class="result-check" id="result_<?= $index ?>" value="<?= htmlspecialchars($row['score']) ?>">
                                <label for="result_<?= $index ?>" style="cursor:pointer; font-weight:500;">Result #<?= $index ?></label>
                            </div>
                            <span class="result-index"><?= date('M d, Y', strtotime($row['submitted'])) ?></span>
                        </div>

                        <!-- Vertical Table: 2 Columns × 9 Rows -->
                        <table class="vertical-table">
                            <tbody>
                                <!-- Row 1: Student -->
                                <tr>
                                    <th>Student</th>
                                    <td><?= htmlspecialchars($row['student_name']) ?></td>
                                </tr>
                                
                                <!-- Row 2: Username -->
                                <tr>
                                    <th>Username</th>
                                    <td class="value-username"><?= htmlspecialchars($row['username']) ?></td>
                                </tr>
                                
                                <!-- Row 3: Club -->
                                <tr>
                                    <th>Club</th>
                                    <td><?= htmlspecialchars($row['club'] ?? '-') ?></td>
                                </tr>
                                
                                <!-- Row 4: Talent -->
                                <tr>
                                    <th>Talent</th>
                                    <td><?= htmlspecialchars($row['talent'] ?? '-') ?></td>
                                </tr>
                                
                                <!-- Row 5: Score -->
                                <tr>
                                    <th>Score</th>
                                    <td class="value-score"><?= (int)$row['score'] ?></td>
                                </tr>
                                
                                <!-- Row 6: Grade -->
                                <tr>
                                    <th>Grade</th>
                                    <td>
                                        <span class="grade-badge <?= getGradeClass($row['grade']) ?>">
                                            <?= htmlspecialchars($row['grade']) ?>
                                        </span>
                                    </td>
                                </tr>
                                
                                <!-- Row 7: Auto Remarks -->
                                <tr>
                                    <th>Auto Remarks</th>
                                    <td class="auto-remarks-cell"><?= htmlspecialchars($row['auto_remarks'] ?? '-') ?></td>
                                </tr>
                                
                                <!-- Row 8: Status -->
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span class="status-badge <?= getStatusClass($row['pending status']) ?>">
                                            <?= htmlspecialchars($row[' pending status']) ?>
                                        </span>
                                    </td>
                                </tr>
                                
                                <!-- Row 9: Submitted -->
                                <tr>
                                    <th>Submitted</th>
                                    <td class="value-date"><?= date('M d, Y \a\t h:i A', strtotime($row['submitted'])) ?></td>
                                </tr>
                            </tbody>
                        </table>
                        
                    </div> <!-- End .result-card -->
                    <?php $index++; ?>
                    <?php endforeach; ?>
                    
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i><br><br>
                        <strong>No results found</strong><br>
                        <p>Your results will appear here once uploaded by your instructor.</p>
                    </div>
                <?php endif; ?>

            </div>
        </div>

    </div> <!-- End .main-content -->

    <!-- ===== JAVASCRIPT FOR SIDEBAR & CHECKBOXES ===== -->
    <script>
        // ✅ Mobile sidebar toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        
        menuToggle?.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 992 && 
                !sidebar.contains(e.target) && 
                !menuToggle?.contains(e.target) && 
                sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });

        // ✅ Select all / deselect all checkbox (targets .result-check inside cards)
        document.getElementById('selectAllMain')?.addEventListener('change', function() {
            document.querySelectorAll('.result-check').forEach(cb => {
                cb.checked = this.checked;
            });
        });

        // ✅ Individual checkbox sync with "select all"
        document.querySelectorAll('.result-check').forEach(cb => {
            cb.addEventListener('change', function() {
                const all = document.querySelectorAll('.result-check');
                const main = document.getElementById('selectAllMain');
                if (main) {
                    main.checked = Array.from(all).every(c => c.checked);
                    main.indeterminate = Array.from(all).some(c => c.checked) && !all[0]?.checked;
                }
            });
        });

        // ✅ Highlight active nav link based on current page
        document.addEventListener('DOMContentLoaded', () => {
            const currentPath = window.location.pathname.split('/').pop();
            document.querySelectorAll('.nav-links a').forEach(link => {
                const href = link.getAttribute('href');
                if (href === currentPath) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        });
    </script>

</body>
</html>