<?php
session_start();
include "../component/connect.php";

$message = [];

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != "super_admin"){
    header("Location: super_admin.php");
    exit;
}
/* ===============================
   ADD OR UPDATE DISTRICT MANAGER
   =============================== */
if (isset($_POST['submit'])) {

    // ✅ Use null coalescing to safely handle POST keys
    $dm_id        = (int)($_POST['dm_id'] ?? 0);
    $first_name   = trim($_POST['first_name'] ?? '');
    $middle_name  = trim($_POST['middle_name'] ?? '');
    $surname      = trim($_POST['surname'] ?? '');
    $gender       = trim($_POST['gender'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $region       = trim($_POST['region'] ?? '');
    $district     = trim($_POST['district'] ?? '');

    // ✅ Validation
    if (empty($first_name) || empty($surname) || empty($gender) || empty($email) || empty($region) || empty($district)) {
        $message[] = "All fields are required!";
    } 
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message[] = "Please enter a valid email address!";
    }
    else {
        try {
            // Check if email already exists (for both add & update)
            if ($dm_id > 0) {
                // Updating: exclude current manager from check
                $check_email = $pdo->prepare("SELECT id FROM district_managers WHERE email = ? AND id != ?");
                $check_email->execute([$email, $dm_id]);
            } else {
                // Adding: check all managers
                $check_email = $pdo->prepare("SELECT id FROM district_managers WHERE email = ?");
                $check_email->execute([$email]);
            }
            
            if ($check_email->rowCount() > 0) {
                $message[] = "This email is already registered to another manager!";
            } else {
                
                // 🆕 ADD NEW MANAGER
                if ($dm_id == 0) {
                    
                    // Generate unique district_manager_id (DM-00001, DM-00002, etc.)
                    $last = $pdo->query("SELECT district_manager_id FROM district_managers ORDER BY id DESC LIMIT 1")->fetchColumn();
                    $num = $last ? (int)str_replace('DM-', '', $last) + 1 : 1;
                    $district_manager_id = 'DM-' . str_pad($num, 5, '0', STR_PAD_LEFT);

                    // Generate unique username: dm_surname, dm_surname1, etc.
                    $base_username = 'dm_' . strtolower($surname);
                    $username = $base_username;
                    $counter = 1;
                    while (true) {
                        $check = $pdo->prepare("SELECT id FROM district_managers WHERE username = ?");
                        $check->execute([$username]);
                        if ($check->rowCount() == 0) break;
                        $username = $base_username . $counter++;
                    }

                    // Generate secure random password
                    $random_num = rand(1000, 9999);
                    $password_plain = ucfirst(strtolower($surname)) . $random_num . "!";
                    $password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);

                    // ✅ INSERT new manager (no 'updated_at' column)
                    $insert = $pdo->prepare("
                        INSERT INTO district_managers 
                        (district_manager_id, first_name, middle_name, surname, gender, email, username, password, region, district)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $insert->execute([
                        $district_manager_id, $first_name, $middle_name, $surname, $gender,
                        $email, $username, $password_hashed, $region, $district
                    ]);
                    
                    // Show credentials for new manager
                    $message[] = "
                        <div style='text-align:center;color:#27ae60;font-weight:600;'>
                            ✅ New District Manager ADDED successfully!<br><br>
                            <span style='font-size:0.9rem;font-weight:normal;color:#555;'>
                                Username: <strong style='color:#2980b9;'>$username</strong><br>
                                Password: <strong style='color:#e74c3c;'>$password_plain</strong><br>
                                <em style='display:block;margin-top:8px;font-size:0.85rem;'>
                                    ⚠️ Save these credentials - password won't be shown again!
                                </em>
                            </span>
                        </div>
                    ";
                    
                } 
                // ✏️ UPDATE EXISTING MANAGER
                else {
                    // ✅ UPDATE existing manager (no 'updated_at' column)
                    $update = $pdo->prepare("
                        UPDATE district_managers 
                        SET first_name = ?, middle_name = ?, surname = ?, gender = ?, 
                            email = ?, region = ?, district = ?
                        WHERE id = ?
                    ");
                    
                    $update->execute([
                        $first_name, $middle_name, $surname, $gender,
                        $email, $region, $district, $dm_id
                    ]);
                    
                    $message[] = "<div style='text-align:center;color:#27ae60;font-weight:600;'>
                                    ✅ District Manager updated successfully!
                                  </div>";
                }
            }
        } catch (PDOException $e) {
            $message[] = "Error: " . htmlspecialchars($e->getMessage());
            error_log("Database error: " . $e->getMessage());
        }
    }
}

/* ===============================
   FETCH MANAGER FOR EDIT
   =============================== */
$manager = null;
$edit_id = (int)($_GET['edit_id'] ?? 0);

if ($edit_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM district_managers WHERE id = ?");
    $stmt->execute([$edit_id]);
    $manager = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$manager) {
        $message[] = "Manager not found!";
    }
}

/* ===============================
   FETCH ALL MANAGERS FOR LIST
   =============================== */
$managers = [];
$search = trim($_GET['search'] ?? '');

$query = "SELECT id, district_manager_id, first_name, surname, email, region, district, gender, created_at 
          FROM district_managers";
$params = [];

if (!empty($search)) {
    $query .= " WHERE first_name LIKE ? OR surname LIKE ? OR email LIKE ? OR district_manager_id LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%", "%$search%"];
}

$query .= " ORDER BY surname ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$managers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage District Managers</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background: #f4f6f9; 
            padding: 6rem 20px; 
            min-height: 100vh; 
        }
        .page-title { 
            text-align: center; 
            color: #2c3e50; 
            font-size: 2rem; 
            margin: 1.5rem 0 0.5rem; 
            font-weight: 700; 
        }
        .page-subtitle { 
            text-align: center; 
            color: #7f8c8d; 
            margin-bottom: 2rem; 
            font-size: 1rem; 
        }
        
        /* 🔍 Search & Actions Bar - Responsive Design */
        .actions-bar { 
            max-width: 1100px; 
            margin: 0 auto 20px; 
            display: flex; 
            gap: 15px; 
            flex-wrap: wrap; 
            justify-content: space-between; 
            align-items: center;
            padding: 15px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }
        
        /* Search Box Container */
        .search-wrapper {
            flex: 1;
            min-width: 200px;
            display: flex;
            align-items: center;
        }
        
        .search-box { 
            display: flex; 
            align-items: center; 
            background: #f8f9fa; 
            border: 2px solid #dfe6e9; 
            border-radius: 25px; 
            padding: 8px 15px; 
            width: 100%;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .search-box:focus-within {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.15);
            background: white;
        }
        .search-box i { 
            color: #7f8c8d; 
            margin-right: 10px; 
            font-size: 1rem;
        }
        .search-box input { 
            border: none; 
            outline: none; 
            padding: 6px 10px; 
            font-size: 0.95rem; 
            width: 100%;
            background: transparent;
            color: #2c3e50;
        }
        .search-box input::placeholder {
            color: #95a5a6;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .btn-add { 
            padding: 10px 20px; 
            background: linear-gradient(135deg, #27ae60, #2ecc71); 
            color: white; 
            border: none; 
            border-radius: 25px; 
            font-size: 0.95rem; 
            font-weight: 600;
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            gap: 6px;
            transition: transform 0.2s, box-shadow 0.2s;
            white-space: nowrap;
        }
        .btn-add:hover { 
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39,174,96,0.3);
        }
        .btn-add:active {
            transform: translateY(0);
        }
        
        .search-count {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-left: 10px;
            white-space: nowrap;
        }
        
        /* Table Styles */
        .table-container { 
            max-width: 1100px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 12px; 
            overflow: hidden; 
            box-shadow: 0 5px 20px rgba(0,0,0,0.1); 
        }
        .managers-table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 0.9rem; 
        }
        .managers-table th { 
            background: #34495e; 
            color: white; 
            padding: 14px 12px; 
            text-align: left; 
            font-weight: 600; 
        }
        .managers-table td { 
            padding: 12px; 
            border-bottom: 1px solid #eee; 
            vertical-align: middle; 
        }
        .managers-table tr:hover { 
            background: #f8f9fa; 
        }
        .col-id { min-width: 90px; font-family: monospace; color: #2980b9; }
        .col-name { min-width: 180px; font-weight: 500; }
        .col-email { min-width: 200px; color: #2980b9; }
        .col-location { min-width: 180px; }
        .col-gender { min-width: 80px; text-align: center; }
        .col-actions { min-width: 120px; text-align: center; }
        .btn-edit { 
            padding: 6px 12px; 
            background: #3498db; 
            color: white; 
            border: none; 
            border-radius: 6px; 
            font-size: 0.85rem; 
            cursor: pointer; 
            margin-right: 5px; 
        }
        .btn-edit:hover { background: #2980b9; }
        .btn-delete { 
            padding: 6px 12px; 
            background: #e74c3c; 
            color: white; 
            border: none; 
            border-radius: 6px; 
            font-size: 0.85rem; 
            cursor: pointer; 
        }
        .btn-delete:hover { background: #c0392b; }
        
        /* Modal/Edit Form */
        .modal { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.5); 
            z-index: 1000; 
            justify-content: center; 
            align-items: center; 
            padding: 20px;
        }
        .modal.active { display: flex; }
        .modal-content { 
            background: white; 
            border-radius: 16px; 
            width: 100%; 
            max-width: 620px; 
            max-height: 90vh; 
            overflow-y: auto; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.2); 
        }
        .modal-header { 
            background: linear-gradient(135deg, #27ae60, #2ecc71); 
            color: white; 
            padding: 20px 25px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .modal-header h2 { font-size: 1.4rem; margin: 0; }
        .modal-close { 
            background: none; 
            border: none; 
            color: white; 
            font-size: 1.5rem; 
            cursor: pointer; 
            padding: 5px 10px;
            border-radius: 50%;
            transition: background 0.2s;
        }
        .modal-close:hover { background: rgba(255,255,255,0.2); }
        .modal-body { padding: 25px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { 
            display: block; 
            font-weight: 600; 
            color: #2c3e50; 
            margin-bottom: 6px; 
        }
        .required { color: #e74c3c; }
        .form-group input[type="text"], 
        .form-group input[type="email"], 
        .form-group select {
            width: 100%; 
            padding: 12px 14px; 
            border: 2px solid #dfe6e9; 
            border-radius: 8px; 
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        .form-group input:focus, 
        .form-group select:focus { 
            border-color: #27ae60; 
            outline: none; 
            box-shadow: 0 0 0 3px rgba(39,174,96,0.1); 
        }
        
        /* ✅ Gender Radio Buttons */
        .gender-options { 
            display: flex; 
            gap: 20px; 
            margin-top: 8px; 
            flex-wrap: wrap;
        }
        .gender-options label { 
            display: flex; 
            align-items: center; 
            gap: 6px; 
            cursor: pointer; 
            font-weight: normal; 
        }
        .gender-options input[type="radio"] { 
            width: auto; 
            margin: 0; 
        }
        
        .message { 
            padding: 15px 20px; 
            margin-bottom: 20px; 
            border-radius: 10px; 
            font-size: 0.95rem; 
        }
        .message.success { 
            background: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb; 
        }
        .message.error { 
            background: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb; 
        }
        
        .btn-submit { 
            width: 100%; 
            padding: 14px; 
            background: linear-gradient(135deg, #27ae60, #2ecc71); 
            color: white; 
            border: none; 
            border-radius: 8px; 
            font-size: 1.05rem; 
            font-weight: 600; 
            cursor: pointer; 
            margin-top: 10px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-submit:hover { 
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39,174,96,0.3);
        }
        
        .empty-state { 
            text-align: center; 
            padding: 40px 20px; 
            color: #7f8c8d; 
        }
        .empty-state i { 
            font-size: 2.5rem; 
            margin-bottom: 12px; 
            color: #bdc3c7; 
        }
        
        .back-link { 
            display: inline-block; 
            margin: 1rem auto; 
            padding: 10px 22px; 
            background: #3498db; 
            color: white; 
            text-decoration: none; 
            border-radius: 50px; 
            font-weight: 600; 
        }
        .back-link:hover { background: #2980b9; }
        
        /* 📱 Mobile Responsive Styles */
        @media (max-width: 768px) {
            body { padding: 5rem 15px; }
            .page-title { font-size: 1.6rem; }
            .page-subtitle { font-size: 0.95rem; margin-bottom: 1.5rem; }
            .actions-bar { flex-direction: column; align-items: stretch; padding: 12px 15px; gap: 12px; }
            .search-wrapper { width: 100%; order: 1; }
            .search-box { width: 100%; padding: 10px 15px; }
            .search-box input { font-size: 1rem; padding: 8px 12px; }
            .action-buttons { width: 100%; justify-content: space-between; order: 2; margin-top: 5px; }
            .btn-add { flex: 1; justify-content: center; padding: 12px 15px; font-size: 1rem; }
            .search-count { display: none; }
            .table-container { border-radius: 8px; overflow-x: auto; -webkit-overflow-scrolling: touch; }
            .managers-table { font-size: 0.85rem; min-width: 600px; }
            .managers-table th, .managers-table td { padding: 10px 8px; }
            .col-email, .col-location { display: none; }
            .col-actions { min-width: 100px; }
            .btn-edit, .btn-delete { padding: 5px 10px; font-size: 0.8rem; }
            .modal { padding: 10px; align-items: flex-start; }
            .modal-content { max-height: 95vh; border-radius: 12px; }
            .modal-header { padding: 15px 20px; }
            .modal-header h2 { font-size: 1.2rem; }
            .modal-body { padding: 20px 15px; }
            .form-group input[type="text"], .form-group input[type="email"], .form-group select { padding: 10px 12px; font-size: 1rem; }
            .gender-options { flex-direction: column; gap: 10px; }
            .btn-submit { padding: 12px; font-size: 1rem; }
        }
        @media (max-width: 480px) {
            .page-title { font-size: 1.4rem; }
            .actions-bar { padding: 10px 12px; }
            .search-box { padding: 8px 12px; }
            .btn-add { padding: 10px 12px; font-size: 0.95rem; }
            .managers-table { font-size: 0.8rem; }
            .col-name { min-width: 140px; }
            .col-gender, .col-id { display: none; }
        }
    </style>
</head>
<body>

<?php include 'super_admin_header.php'; ?>

<h1 class="page-title">👥 Manage District Managers</h1>
<p class="page-subtitle">View, search, add, and update district manager accounts</p>

<!-- 🔍 Search & Actions Bar - Responsive -->
<div class="actions-bar">
    <div class="search-wrapper">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <form method="GET" style="display:flex; align-items:center; width:100%;">
                <input type="text" name="search" placeholder="Search name, email, or ID..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" style="background:none; border:none; color:#3498db; cursor:pointer; margin-left:5px; padding:5px;">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>
    
    <div class="action-buttons">
        <span class="search-count"><?= count($managers) ?> manager<?= count($managers) !== 1 ? 's' : '' ?></span>
        <button class="btn-add" onclick="openModal()">
            <i class="fas fa-plus"></i> <span class="btn-text">Add Manager</span>
        </button>
    </div>
</div>

<?php foreach ($message as $msg): ?>
    <div class="message <?= strpos($msg, '✅') !== false ? 'success' : 'error' ?>">
        <?= $msg ?>
    </div>
<?php endforeach; ?>

<!-- 📋 Managers Table -->
<div class="table-container">
    <table class="managers-table">
        <thead>
            <tr>
                <th class="col-id">ID</th>
                <th class="col-name">Name</th>
                <th class="col-email">Email</th>
                <th class="col-location">Region / District</th>
                <th class="col-gender">Gender</th>
                <th class="col-actions">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($managers)): ?>
                <?php foreach ($managers as $m): ?>
                    <tr>
                        <td class="col-id"><?= htmlspecialchars($m['district_manager_id']) ?></td>
                        <td class="col-name"><?= htmlspecialchars($m['first_name'] . ' ' . $m['surname']) ?></td>
                        <td class="col-email"><?= htmlspecialchars($m['email']) ?></td>
                        <td class="col-location"><?= htmlspecialchars($m['region']) ?> / <?= htmlspecialchars($m['district']) ?></td>
                        <td class="col-gender"><?= htmlspecialchars($m['gender']) ?></td>
                        <td class="col-actions">
                            <button class="btn-edit" onclick="openModal(<?= htmlspecialchars(json_encode($m)) ?>)">
                                <i class="fas fa-edit"></i> <span class="btn-text">Edit</span>
                            </button>
                            <button class="btn-delete" onclick="confirmDelete(<?= $m['id'] ?>, '<?= addslashes($m['first_name']) ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="empty-state">
                        <i class="fas fa-users-slash"></i><br><br>
                        <strong>No managers found</strong><br>
                        <?= !empty($search) ? 'Try a different search term.' : 'Click "Add Manager" to create your first district manager.' ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!--<a href="super_admin_dashboard.php" class="back-link">← Back to Dashboard</a>-->

<!-- ✏️ Add/Edit Modal (Same modal for both) -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle"><i class="fas fa-user-plus"></i> Add District Manager</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" id="editForm">
                <input type="hidden" name="dm_id" id="edit_dm_id">
                
                <div class="form-group">
                    <label>First Name <span class="required">*</span></label>
                    <input type="text" name="first_name" id="edit_first_name" required>
                </div>
                
                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" id="edit_middle_name">
                </div>
                
                <div class="form-group">
                    <label>Surname <span class="required">*</span></label>
                    <input type="text" name="surname" id="edit_surname" required>
                </div>
                
                <!-- ✅ Gender Radio Buttons -->
                <div class="form-group">
                    <label>Gender <span class="required">*</span></label>
                    <div class="gender-options">
                        <label>
                            <input type="radio" name="gender" value="Male" id="gender_male" required> Male
                        </label>
                        <label>
                            <input type="radio" name="gender" value="Female" id="gender_female" required> Female
                        </label>
                        <label>
                            <input type="radio" name="gender" value="Other" id="gender_other" required> Other
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Email Address <span class="required">*</span></label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                
                <!-- ✅ Region → District Cascading -->
                <div class="form-group">
                    <label>Region <span class="required">*</span></label>
                    <select name="region" id="edit_region" required onchange="loadDistricts()">
                        <option value="">Select Region</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>District <span class="required">*</span></label>
                    <select name="district" id="edit_district" required>
                        <option value="">Select District</option>
                    </select>
                </div>
                
                <button type="submit" name="submit" class="btn-submit" id="submitBtn">
                    <i class="fas fa-user-plus"></i> <span id="btnText">Add Manager</span>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
// 🗺️ Tanzania Regions & Districts Data
const tanzaniaData = {
    "Arusha": { "Arusha City": [], "Arusha District": [], "Karatu": [], "Longido": [], "Meru": [], "Monduli": [], "Ngorongoro": [] },
    "Dar es Salaam": { "Ilala": [], "Kinondoni": [], "Temeke": [], "Ubungo": [], "Kigamboni": [] },
    "Dodoma": { "Dodoma City": [], "Bahi": [], "Chamwino": [], "Chemba": [], "Kondoa": [], "Kondoa Town Council": [] },
    "Geita": { "Geita": [], "Bukombe": [], "Chato": [], "Mbogwe": [], "Nyang'hwale": [] },
    "Iringa": { "Iringa Urban": [], "Iringa Rural": [], "Mafinga": [], "Mufindi": [], "Kilolo": [] },
    "Kagera": { "Bukoba Urban": [], "Bukoba Rural": [], "Karagwe": [], "Kyerwa": [], "Missenyi": [], "Muleba": [], "Ngara": [], "Biharamulo": [] },
    "Katavi": { "Mpanda Urban": [], "Mpanda Rural": [], "Mlele": [] },
    "Kigoma": { "Kigoma Urban": [], "Kigoma Rural": [], "Buhigwe": [], "Kasulu": [], "Kibondo": [], "Kakonko": [], "Uvinza": [], "Tanganyika": [] },
    "Kilimanjaro": { "Moshi Urban": [], "Moshi Rural": [], "Hai": [], "Siha": [], "Mwanga": [], "Rombo": [] },
    "Lindi": { "Kilwa": [], "Nachingwea": [], "Ruangwa": [], "Lindi Municipal": [], "Mtama": [], "Liwale": [] },
    "Manyara": { "Babati Urban": [], "Babati Rural": [], "Hanang": [], "Kiteto": [], "Mbulu": [], "Simanjiro": [] },
    "Mara": { "Musoma Urban": [], "Musoma Rural": [], "Rorya": [], "Serengeti": [], "Butiama": [], "Bunda": [], "Tarime": [] },
    "Mbeya": { "Mbeya City": [], "Mbeya District": [], "Chunya": [], "Kyela": [], "Mbarali": [], "Rungwe": [], "Busokelo": [] },
    "Morogoro": { "Morogoro Urban": [], "Morogoro Rural": [], "Kilombero": [], "Gairo": [], "Kilosa": [], "Malinyi": [], "Ulanga": [] },
    "Mtwara": { "Mtwara Urban": [], "Mtwara Rural": [], "Masasi": [], "Nanyumbu": [], "Newala": [], "Mtwara Mikindani": [], "Tandahimba": [] },
    "Mwanza": { "Nyamagana": [], "Ilemela": [], "Sengerema": [], "Misungwi": [], "Magu": [], "Kwimba": [], "Ukerewe": [] },
    "Njombe": { "Njombe Urban": [], "Njombe Rural": [], "Makambako": [], "Ludewa": [], "Makete": [], "Wanging'ombe": [] },
    "Pwani": { "Kibaha Town": [], "Kibaha District": [], "Bagamoyo": [], "Kisarawe": [], "Mafia": [], "Mkuranga": [], "Rufiji": [] },
    "Rukwa": { "Sumbawanga Urban": [], "Sumbawanga Rural": [], "Nkasi": [], "Kalambo": [] },
    "Ruvuma": { "Songea Urban": [], "Songea Rural": [], "Mbinga": [], "Namtumbo": [], "Nyasa": [], "Tunduru": [] },
    "Shinyanga": { "Shinyanga Urban": [], "Shinyanga Rural": [], "Kahama": [], "Kishapu": [], "Ushetu": [] },
    "Simiyu": { "Bariadi": [], "Busega": [], "Itilima": [], "Maswa": [], "Meatu": [] },
    "Singida": { "Singida Urban": [], "Singida Rural": [], "Ikungi": [], "Manyoni": [], "Iramba": [], "Mkalama": [] },
    "Songwe": { "Songwe": [], "Ileje": [], "Mbozi": [], "Momba": [], "Tunduma": [] },
    "Tabora": { "Tabora Urban": [], "Tabora Rural": [], "Igunga": [], "Kaliua": [], "Nzega": [], "Sikonge": [], "Urambo": [] },
    "Tanga": { "Tanga City": [], "Muheza": [], "Korogwe": [], "Lushoto": [], "Handeni": [], "Kilindi": [], "Mkinga": [], "Bumbuli": [], "Handeni Town": [], "Korogwe Town": [] },
    "Mjini Magharibi": { "Magharibi": [], "Mjini": [] },
    "Pemba North": { "Micheweni": [], "Wete": [] },
    "Pemba South": { "Chake Chake": [], "Mkoani": [] },
    "Unguja North": { "Kaskazini A": [], "Kaskazini B": [] },
    "Unguja South": { "Kati": [], "Kusini": [] }
};

// ✅ Load Regions into dropdown
function loadRegions() {
    const regionSelect = document.getElementById('edit_region');
    if (!regionSelect) return;
    regionSelect.innerHTML = '<option value="">Select Region</option>';
    Object.keys(tanzaniaData).forEach(region => {
        const option = document.createElement('option');
        option.value = region;
        option.textContent = region;
        regionSelect.appendChild(option);
    });
}

// ✅ Load Districts based on selected Region
function loadDistricts() {
    const region = document.getElementById('edit_region').value;
    const districtSelect = document.getElementById('edit_district');
    districtSelect.innerHTML = '<option value="">Select District</option>';
    if (region && tanzaniaData[region]) {
        Object.keys(tanzaniaData[region]).forEach(district => {
            const option = document.createElement('option');
            option.value = district;
            option.textContent = district;
            districtSelect.appendChild(option);
        });
    }
}

// ✅ Set district after loading districts (for edit mode)
function setDistrict(districtName) {
    const districtSelect = document.getElementById('edit_district');
    if (districtSelect && districtName) {
        setTimeout(() => {
            for (let option of districtSelect.options) {
                if (option.value === districtName) {
                    option.selected = true;
                    break;
                }
            }
        }, 50);
    }
}

// ✅ Modal Functions - Handles BOTH Add and Edit
function openModal(manager = null) {
    const modal = document.getElementById('editModal');
    const modalTitle = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    
    // Load regions first
    loadRegions();
    
    if (manager) {
        // ✏️ EDIT MODE
        modalTitle.innerHTML = '<i class="fas fa-user-edit"></i> Edit District Manager';
        btnText.textContent = 'Update Manager';
        submitBtn.innerHTML = '<i class="fas fa-save"></i> <span id="btnText">Update Manager</span>';
        
        // Populate form with manager data
        document.getElementById('edit_dm_id').value = manager.id || '';
        document.getElementById('edit_first_name').value = manager.first_name || '';
        document.getElementById('edit_middle_name').value = manager.middle_name || '';
        document.getElementById('edit_surname').value = manager.surname || '';
        document.getElementById('edit_email').value = manager.email || '';
        
        // Set region, load districts, then set district
        const regionSelect = document.getElementById('edit_region');
        regionSelect.value = manager.region || '';
        loadDistricts();
        setDistrict(manager.district || '');
        
        // Set gender radio button
        const gender = manager.gender || '';
        document.getElementById('gender_male').checked = (gender === 'Male');
        document.getElementById('gender_female').checked = (gender === 'Female');
        document.getElementById('gender_other').checked = (gender === 'Other');
        
    } else {
        // 🆕 ADD MODE
        modalTitle.innerHTML = '<i class="fas fa-user-plus"></i> Add District Manager';
        btnText.textContent = 'Add Manager';
        submitBtn.innerHTML = '<i class="fas fa-user-plus"></i> <span id="btnText">Add Manager</span>';
        
        // Clear form for new manager
        document.getElementById('editForm').reset();
        document.getElementById('edit_dm_id').value = '';
        loadDistricts(); // Reset districts when no region selected
    }
    
    modal.classList.add('active');
}

function closeModal() {
    document.getElementById('editModal').classList.remove('active');
}

// Close modal when clicking outside
document.getElementById('editModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// ✅ Delete Confirmation
function confirmDelete(id, name) {
    if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
        window.location.href = `delete_district_manager.php?id=${id}`;
    }
}

// ✅ Sidebar toggle
let menuBtn = document.getElementById("menu-btn");
let sidebar = document.getElementById("sidebar");
if (menuBtn && sidebar) {
    menuBtn.addEventListener("click", function() {
        sidebar.classList.toggle("active");
    });
}

// ✅ Auto-submit search on Enter
document.querySelector('.search-box input')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        this.closest('form').submit();
    }
});

// ✅ Initialize regions when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadRegions();
});
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