<?php
session_start(); include "../component/connect.php";
if (!isset($_SESSION['district_manager_logged_in']) || $_SESSION['district_manager_logged_in'] !== true) {
    header("Location: district_manager_login.php"); exit;
}

$msg = [];
$dm_id = $_SESSION['district_manager_id'];
$stmt = $pdo->prepare("SELECT * FROM district_managers WHERE id = ?");
$stmt->execute([$dm_id]);
$dm = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fn = trim($_POST['first_name'] ?? ''); $sn = trim($_POST['surname'] ?? '');
    $em = trim($_POST['email'] ?? ''); $pw = trim($_POST['new_password'] ?? '');

    if (empty($fn) || empty($sn) || !filter_var($em, FILTER_VALIDATE_EMAIL)) {
        $msg[] = ["type"=>"error","text"=>"Valid name and email required."];
    } else {
        try {
            $check = $pdo->prepare("SELECT id FROM district_managers WHERE email=? AND id!=?");
            $check->execute([$em, $dm_id]);
            if ($check->rowCount() > 0) { $msg[] = ["type"=>"error","text"=>"Email already in use."]; }
            else {
                $sql = "UPDATE district_managers SET first_name=?, surname=?, email=?".($pw ? ", password=?" : "")." WHERE id=?";
                $params = [$fn, $sn, $em];
                if ($pw) $params[] = password_hash($pw, PASSWORD_DEFAULT);
                $params[] = $dm_id;
                $pdo->prepare($sql)->execute($params);
                
                $_SESSION['district_manager_full_name'] = $fn . ' ' . $sn;
                $_SESSION['district_manager_email'] = $em;
                $msg[] = ["type"=>"success","text"=>"✅ Profile updated successfully!"];
                $dm = $pdo->prepare("SELECT * FROM district_managers WHERE id=?")->fetch([$dm_id]);
            }
        } catch(PDOException $e) { $msg[] = ["type"=>"error","text"=>"System error. Try again."]; error_log($e->getMessage()); }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - District Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6f9; margin: 0; padding-top: 65px; }
        .container { max-width: 700px; margin: 0 auto; padding: 25px 20px; }
        .profile-card { background: white; border-radius: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); overflow: hidden; }
        .profile-header { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; padding: 25px; text-align: center; }
        .profile-avatar { width: 90px; height: 90px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 15px; }
        .profile-body { padding: 25px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: 500; color: #374151; margin-bottom: 6px; font-size: 0.95rem; }
        input { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem; transition: 0.2s; background: #f8fafc; }
        input:focus { border-color: #3b82f6; outline: none; background: white; }
        input[readonly] { background: #f1f5f9; color: #64748b; cursor: not-allowed; }
        .btn-save { width: 100%; padding: 14px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-size: 1.05rem; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-save:hover { background: #2563eb; transform: translateY(-1px); }
        .msg { padding: 12px; border-radius: 8px; margin-bottom: 15px; font-size: 0.95rem; }
        .msg-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .msg-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        @media(max-width:600px){.form-row{grid-template-columns:1fr;}}
    </style>
</head>
<body>
<?php include 'district_manager_header.php'; ?>
<div class="container">
    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-avatar"><i class="fas fa-user"></i></div>
            <h2><?= htmlspecialchars($dm['first_name'] . ' ' . $dm['surname']) ?></h2>
            <p style="opacity:0.9"><?= htmlspecialchars($dm['region']) ?> / <?= htmlspecialchars($dm['district']) ?></p>
        </div>
        <div class="profile-body">
            <?php foreach($msg as $m): ?><div class="msg msg-<?= $m['type'] ?>"><?= $m['text'] ?></div><?php endforeach; ?>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group"><label>First Name</label><input type="text" name="first_name" value="<?= htmlspecialchars($dm['first_name']) ?>" required></div>
                    <div class="form-group"><label>Surname</label><input type="text" name="surname" value="<?= htmlspecialchars($dm['surname']) ?>" required></div>
                </div>
                <div class="form-group"><label>Email Address</label><input type="email" name="email" value="<?= htmlspecialchars($dm['email']) ?>" required></div>
                <div class="form-group"><label>New Password (Leave blank to keep current)</label><input type="password" name="new_password" placeholder="Enter new password"></div>
                <div class="form-row">
                    <div class="form-group"><label>Region</label><input type="text" value="<?= htmlspecialchars($dm['region']) ?>" readonly></div>
                    <div class="form-group"><label>District</label><input type="text" value="<?= htmlspecialchars($dm['district']) ?>" readonly></div>
                </div>
                <button type="submit" name="update_profile" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
            </form>
        </div>
    </div>
</div>
</body></html>
