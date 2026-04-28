<?php
// File: /admin/edit_district_manager.php
session_start();
include "../component/connect.php";

$message = [];

// 🔐 Ensure super admin is logged in
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != "super_admin"){
    header("Location: super_admin.php");
    exit;
}

// ✅ Get manager ID
$dm_id = (int)($_GET['id'] ?? $_POST['dm_id'] ?? 0);

if ($dm_id <= 0) {
    $message[] = "❌ Invalid manager ID.";
    $manager = null;
} else {
    // ✅ Fetch existing manager data
    $stmt = $pdo->prepare("SELECT * FROM district_managers WHERE id = ?");
    $stmt->execute([$dm_id]);
    $manager = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$manager) {
        $message[] = "❌ Manager not found!";
    }
}

/* ===============================
   HANDLE FORM SUBMISSION (UPDATE)
   =============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit']) && $manager) {

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
            // ✅ Check if email exists for another manager
            $check_email = $pdo->prepare("SELECT id FROM district_managers WHERE email = ? AND id != ?");
            $check_email->execute([$email, $dm_id]);
            
            if ($check_email->rowCount() > 0) {
                $message[] = "This email is already registered to another manager!";
            } else {
                // ✅ UPDATE query (no 'updated_at' column)
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
                
                $message[] = "<div style='color:#27ae60;font-weight:600;text-align:center;'>
                                ✅ District Manager updated successfully!
                              </div>";
                
                // ✅ Refresh manager data to show updated values
                $stmt = $pdo->prepare("SELECT * FROM district_managers WHERE id = ?");
                $stmt->execute([$dm_id]);
                $manager = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $message[] = "Error: " . htmlspecialchars($e->getMessage());
            error_log("Update error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit District Manager</title>
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
        .edit-card {
            max-width: 620px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .card-header h1 { font-size: 1.6rem; margin: 0; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .card-body { padding: 30px 35px; }
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
            border-color: #3498db; 
            outline: none; 
            box-shadow: 0 0 0 3px rgba(52,152,219,0.15); 
        }
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
        .gender-options input[type="radio"] { width: auto; margin: 0; }
        
        .message { 
            padding: 15px 20px; 
            margin-bottom: 20px; 
            border-radius: 10px; 
            font-size: 0.95rem; 
        }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .btn-submit { 
            width: 100%; 
            padding: 14px; 
            background: linear-gradient(135deg, #3498db, #2980b9); 
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
            box-shadow: 0 5px 15px rgba(52,152,219,0.3);
        }
        
        .btn-cancel {
            display: inline-block;
            margin-top: 15px;
            padding: 12px 25px;
            background: #95a5a6;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            width: 100%;
        }
        .btn-cancel:hover { background: #7f8c8d; }
        
        .manager-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        .manager-info p { margin: 5px 0; font-size: 0.95rem; }
        .manager-info strong { color: #2c3e50; }
        
        @media (max-width: 768px) {
            body { padding: 5rem 15px; }
            .page-title { font-size: 1.6rem; }
            .card-body { padding: 25px 20px; }
            .gender-options { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body>

<?php include 'super_admin_header.php'; ?>

<h1 class="page-title">✏️ Edit District Manager</h1>
<p class="page-subtitle">Update manager details and location assignment</p>

<?php if ($manager): ?>
<div class="edit-card">
    <div class="card-header">
        <h1><i class="fas fa-user-edit"></i> <?= htmlspecialchars($manager['first_name'] . ' ' . $manager['surname']) ?></h1>
    </div>
    
    <div class="card-body">
        <!-- Manager Info Summary -->
        <div class="manager-info">
            <p><strong>ID:</strong> <?= htmlspecialchars($manager['district_manager_id']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($manager['email']) ?></p>
            <p><strong>Location:</strong> <?= htmlspecialchars($manager['region']) ?> / <?= htmlspecialchars($manager['district']) ?></p>
        </div>
        
        <?php foreach ($message as $msg): ?>
            <div class="message <?= strpos($msg, '✅') !== false ? 'success' : 'error' ?>">
                <?= $msg ?>
            </div>
        <?php endforeach; ?>
        
        <form method="POST" id="editForm">
            <input type="hidden" name="dm_id" value="<?= $manager['id'] ?>">
            
            <div class="form-group">
                <label>First Name <span class="required">*</span></label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($manager['first_name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Middle Name</label>
                <input type="text" name="middle_name" value="<?= htmlspecialchars($manager['middle_name']) ?>">
            </div>
            
            <div class="form-group">
                <label>Surname <span class="required">*</span></label>
                <input type="text" name="surname" value="<?= htmlspecialchars($manager['surname']) ?>" required>
            </div>
            
            <!-- Gender Radio Buttons -->
            <div class="form-group">
                <label>Gender <span class="required">*</span></label>
                <div class="gender-options">
                    <label>
                        <input type="radio" name="gender" value="Male" 
                            <?= ($manager['gender'] ?? '') === 'Male' ? 'checked' : '' ?> required> Male
                    </label>
                    <label>
                        <input type="radio" name="gender" value="Female" 
                            <?= ($manager['gender'] ?? '') === 'Female' ? 'checked' : '' ?> required> Female
                    </label>
                    <label>
                        <input type="radio" name="gender" value="Other" 
                            <?= ($manager['gender'] ?? '') === 'Other' ? 'checked' : '' ?> required> Other
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label>Email Address <span class="required">*</span></label>
                <input type="email" name="email" value="<?= htmlspecialchars($manager['email']) ?>" required>
            </div>
            
            <!-- Region → District Cascading -->
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
            
            <button type="submit" name="submit" class="btn-submit">
                <i class="fas fa-save"></i> Update Manager
            </button>
        </form>
        
        <a href="manage_district_managers.php" class="btn-cancel">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<?php else: ?>
    <!-- Error State: Manager not found -->
    <div style="max-width:600px;margin:40px auto;background:white;padding:30px;border-radius:12px;text-align:center;box-shadow:0 5px 20px rgba(0,0,0,0.1);">
        <i class="fas fa-exclamation-triangle fa-3x" style="color:#e74c3c;margin-bottom:15px;"></i>
        <h3 style="color:#2c3e50;margin-bottom:10px;">Manager Not Found</h3>
        <p style="color:#7f8c8d;margin-bottom:20px;">The requested district manager could not be found or has been deleted.</p>
        <a href="manage_district_managers.php" class="btn-cancel" style="display:inline-block;width:auto;">
            <i class="fas fa-arrow-left"></i> Return to List
        </a>
    </div>
<?php endif; ?>

<script>
// 🗺️ Tanzania Regions & Districts Data (Same as other pages)
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

// ✅ Set district after loading (for pre-filled edit)
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

// ✅ Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadRegions();
    
    // If we have a manager, set their region/district
    <?php if ($manager): ?>
        const regionSelect = document.getElementById('edit_region');
        regionSelect.value = <?= json_encode($manager['region']) ?>;
        
        // Load districts for this region, then set the district
        loadDistricts();
        setDistrict(<?= json_encode($manager['district']) ?>);
    <?php endif; ?>
});
</script>

</body>
</html>