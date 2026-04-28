<?php
// export_results.php - Export school results to CSV
session_start();
include "../component/connect.php";

// 🔐 Security check
if (!isset($_SESSION['district_manager_logged_in']) || !isset($_GET['school_id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

$school_id = $_GET['school_id'];
$format = $_GET['format'] ?? 'csv';

// Verify school belongs to this district manager
$stmt = $pdo->prepare("SELECT school_name, region, district FROM schools WHERE school_id = ?");
$stmt->execute([$school_id]);
$school = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$school || $school['region'] !== $_SESSION['district_manager_region'] || 
    $school['district'] !== $_SESSION['district_manager_district']) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

// Fetch results
$stmt = $pdo->prepare("
    SELECT s.student_name, s.username, s.club, r.talent_name, 
           r.total_score, r.grade, r.status, r.uploaded_at, r.remarks
    FROM results r
    JOIN students s ON r.student_id = s.id
    WHERE s.school_id = ?
    ORDER BY r.uploaded_at DESC
");
$stmt->execute([$school_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// CSV Export
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="results_' . preg_replace('/[^A-Za-z0-9]/', '_', $school['school_name']) . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // BOM for Excel UTF-8 compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Headers
    fputcsv($output, [
        'Student Name', 'Username', 'Club', 'Talent', 
        'Score (%)', 'Grade', 'Status', 'Submitted Date', 'Remarks'
    ]);
    
    // Data rows
    foreach ($results as $row) {
        fputcsv($output, [
            $row['student_name'],
            $row['username'],
            $row['club'],
            $row['talent_name'],
            $row['total_score'] . '%',
            $row['grade'],
            $row['status'],
            date('Y-m-d H:i', strtotime($row['uploaded_at'])),
            $row['remarks'] ?? ''
        ]);
    }
    
    fclose($output);
    exit;
}

// PDF Export (simple - uses browser print)
// For advanced PDF, integrate TCPDF or Dompdf
?>