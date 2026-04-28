<?php
// File: /admin/delete_district_manager.php
session_start();
include "../component/connect.php";

// 🔐 Ensure super admin is logged in
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != "super_admin"){
    header("Location: super_admin.php");
    exit;
}

// ✅ Get manager ID from URL
$dm_id = (int)($_GET['id'] ?? 0);

if ($dm_id <= 0) {
    $_SESSION['message'] = "❌ Invalid manager ID.";
    header("Location: manage_district_managers.php");
    exit;
}

try {
    // ✅ Fetch manager details for confirmation/logging
    $stmt = $pdo->prepare("SELECT district_manager_id, first_name, surname FROM district_managers WHERE id = ?");
    $stmt->execute([$dm_id]);
    $manager = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$manager) {
        $_SESSION['message'] = "❌ Manager not found!";
        header("Location: manage_district_managers.php");
        exit;
    }
    
    // ✅ Delete the manager (CASCADE will handle related data if configured)
    $delete = $pdo->prepare("DELETE FROM district_managers WHERE id = ?");
    $delete->execute([$dm_id]);
    
    // ✅ Log the deletion (optional but recommended)
    error_log("Super Admin {$_SESSION['user_id']} deleted manager: {$manager['district_manager_id']} - {$manager['first_name']} {$manager['surname']}");
    
    $_SESSION['message'] = "✅ Manager <strong>" . htmlspecialchars($manager['first_name'] . ' ' . $manager['surname']) . "</strong> deleted successfully!";
    
} catch (PDOException $e) {
    error_log("Delete error: " . $e->getMessage());
    $_SESSION['message'] = "❌ Error deleting manager: " . htmlspecialchars($e->getMessage());
}

// ✅ Redirect back to management page
header("Location: manage_district_managers.php");
exit;
?>