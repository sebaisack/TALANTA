<?php
if(session_status() === PHP_SESSION_NONE) session_start();
$dm_name = $_SESSION['district_manager_full_name'] ?? 'District Manager';
$dm_role = 'District Manager';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
/* 🔹 Header CSS */
.navbar {
    position: fixed; top: 0; left: 0; right: 0;
    background: linear-gradient(135deg, #1e3a8a, #3b82f6);
    color: white; padding: 0 20px; height: 65px;
    display: flex; align-items: center; justify-content: space-between;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1); z-index: 1000;
}
.nav-brand { display: flex; align-items: center; gap: 12px; font-size: 1.3rem; font-weight: 600; }
.menu-btn { background: none; border: none; color: white; font-size: 1.4rem; cursor: pointer; display: none; }
.nav-links { display: flex; gap: 15px; align-items: center; }
.nav-links a { color: rgba(255,255,255,0.85); text-decoration: none; font-weight: 500; padding: 8px 14px; border-radius: 8px; transition: 0.3s; font-size: 0.95rem; }
.nav-links a:hover, .nav-links a.active { background: rgba(255,255,255,0.15); color: white; }
.user-profile { display: flex; align-items: center; gap: 12px; }
.user-info { text-align: right; }
.user-info .name { font-weight: 600; font-size: 0.95rem; }
.user-info .role { font-size: 0.8rem; opacity: 0.85; }
.logout-btn { background: rgba(255,255,255,0.2); color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 0.9rem; transition: 0.3s; text-decoration: none; display: flex; align-items: center; gap: 6px; }
.logout-btn:hover { background: rgba(255,255,255,0.35); }

/* 🔹 Sidebar CSS */
.sidebar {
    position: fixed; top: 65px; left: -280px; width: 280px; height: calc(100% - 65px);
    background: white; box-shadow: 2px 0 10px rgba(0,0,0,0.08);
    transition: left 0.3s ease; z-index: 999; padding: 15px 0; overflow-y: auto;
}
.sidebar.active { left: 0; }
.sidebar a { display: flex; align-items: center; gap: 14px; padding: 14px 25px; color: #2c3e50; text-decoration: none; font-weight: 500; transition: 0.2s; border-left: 4px solid transparent; }
.sidebar a:hover, .sidebar a.active { background: #f0f4f8; color: #3b82f6; border-left-color: #3b82f6; }
.sidebar a i { width: 22px; text-align: center; color: #7f8c8d; }
.sidebar a.active i { color: #3b82f6; }

/* 🔹 Overlay */
.overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: 998; display: none; }
.overlay.active { display: block; }

/* 🔹 Responsive */
@media (max-width: 900px) {
    .nav-links { display: none; }
    .menu-btn { display: block; }
    .user-info .name { display: none; }
}
@media (max-width: 480px) {
    .navbar { padding: 0 15px; }
    .nav-brand span { font-size: 1.1rem; }
    .logout-btn span { display: none; }
}
</style>

<nav class="navbar">
    <button class="menu-btn" id="menu-btn" aria-label="Toggle Menu"><i class="fas fa-bars"></i></button>
    <div class="nav-brand">
        <i class="fas fa-school"></i> <span>TalentHub DM</span>
    </div>
    <div class="nav-links">
        <a href="district_manager_dashboard.php" class="<?= $current_page == 'district_manager_dashboard.php' ? 'active' : '' ?>">Dashboard</a>
       <!-- <a href="view_pending_results.php" class="<?= $current_page == 'view_pending_results.php' ? 'active' : '' ?>">Pending Results</a>
        <a href="promote_students.php" class="<?= $current_page == 'promote_students.php' ? 'active' : '' ?>">Promote Students</a>-->
        <a href="reports.php">Reports</a>
    </div>
    <div class="user-profile">
        <div class="user-info">
            <div class="name"><?= htmlspecialchars($dm_name) ?></div>
            <div class="role"><?= htmlspecialchars($dm_role) ?></div>
        </div>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>
</nav>

<div class="sidebar" id="sidebar">
    <a href="district_manager_dashboard.php" class="<?= $current_page == 'district_manager_dashboard.php' ? 'active' : '' ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    <a href="view_pending_results.php">
        <i class="fas fa-clipboard-list"></i> Pending Results
    </a>
    <a href="promote_students.php">
        <i class="fas fa-user-check"></i> Promote Students
    </a>
    <a href="reports.php">
        <i class="fas fa-chart-bar"></i> Reports & Analytics
    </a>
    <a href="profile.php">
        <i class="fas fa-user-cog"></i> My Profile
    </a>
    <a href="logout.php">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</div>

<div class="overlay" id="overlay"></div>

<script>
    const menuBtn = document.getElementById('menu-btn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    
    function toggleMenu() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    }
    
    menuBtn?.addEventListener('click', toggleMenu);
    overlay?.addEventListener('click', toggleMenu);
</script>