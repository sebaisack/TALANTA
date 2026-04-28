<?php
include "../component/connect.php";
$club_id = $_GET['club_id'] ?? 0;
$school_id = /* get from session or teacher */; // better pass via session

$stmt = $pdo->prepare("
    SELECT s.id, s.full_name, s.registration_number 
    FROM students s 
    WHERE s.club_id = ? AND s.school_id = ?
    ORDER BY s.full_name
");
$stmt->execute([$club_id, $_SESSION['school_id'] ?? 1]); // adjust accordingly
$students = $stmt->fetchAll();

if (empty($students)) {
    echo "<p>No students found in this club.</p>";
    exit;
}
?>

<h3>Students in this Club</h3>
<?php foreach($students as $student): ?>
    <div class="result-row">
        <strong><?= htmlspecialchars($student['full_name']) ?> (<?= $student['registration_number'] ?>)</strong>
        
        <?php 
        // Fetch all talents
        $talents = $pdo->query("SELECT * FROM talents WHERE school_id = $school_id")->fetchAll();
        foreach($talents as $talent): 
        ?>
            <div style="margin-left:15px;">
                <small><?= htmlspecialchars($talent['talent_name']) ?></small><br>
                <input type="number" 
                       name="score[<?= $student['id'] ?>][<?= $talent['id'] ?>]" 
                       class="score-input" 
                       min="0" max="100" step="0.01" 
                       placeholder="Score /100" required>
                <span class="auto-grade"></span>
                
                <input type="hidden" 
                       name="grade[<?= $student['id'] ?>][<?= $talent['id'] ?>]" 
                       class="hidden-grade">
                
                <textarea name="remarks[<?= $student['id'] ?>][<?= $talent['id'] ?>]" 
                          placeholder="Remarks (optional)" rows="1"></textarea>
            </div>
        <?php endforeach; ?>
    </div>
    <hr>
<?php endforeach; ?>