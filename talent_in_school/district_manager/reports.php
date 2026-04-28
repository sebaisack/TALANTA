<?php
// File: /district_manager/reports.php
session_start();
include "../component/connect.php";

// 🔐 Ensure district manager is logged in
if (!isset($_SESSION['district_manager_logged_in']) || $_SESSION['district_manager_logged_in'] !== true) {
    header("Location: district_manager_login.php");
    exit;
}

/* ===============================
   📊 REPORT FILTERS & DATA FETCH
   =============================== */
$date_from   = $_GET['from'] ?? date('Y-01-01');
$date_to     = $_GET['to'] ?? date('Y-m-d');
$filter_club = $_GET['club'] ?? '';
$filter_grade = $_GET['grade'] ?? '';
$filter_status = $_GET['status'] ?? 'Promoted'; // Default to promoted

// Build dynamic query with filters
$query = "SELECT r.*, s.student_name, s.username, s.club, s.standard 
          FROM results r 
          JOIN students s ON r.student_id = s.id 
          WHERE 1=1";
$params = [];

if ($date_from) { $query .= " AND DATE(r.uploaded_at) >= ?"; $params[] = $date_from; }
if ($date_to) { $query .= " AND DATE(r.uploaded_at) <= ?"; $params[] = $date_to; }
if ($filter_club) { $query .= " AND s.club = ?"; $params[] = $filter_club; }
if ($filter_grade) { $query .= " AND r.grade = ?"; $params[] = $filter_grade; }
if ($filter_status) { $query .= " AND r.status = ?"; $params[] = $filter_status; }

$query .= " ORDER BY r.uploaded_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 📈 Aggregate statistics for charts
$stats = [
    'total' => count($report_data),
    'avg_score' => $report_data ? round(array_sum(array_column($report_data, 'total_score')) / count($report_data), 1) : 0,
    'pass_rate' => $report_data ? round((count(array_filter($report_data, fn($r) => $r['total_score'] >= 50)) / count($report_data)) * 100, 1) : 0,
];

// Grade distribution for pie chart
$grade_dist = $pdo->query("
    SELECT grade, COUNT(*) as cnt 
    FROM results 
    WHERE status = 'Promoted' 
    GROUP BY grade 
    ORDER BY grade
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Club performance for bar chart
$club_perf = $pdo->query("
    SELECT s.club, AVG(r.total_score) as avg_score, COUNT(*) as cnt
    FROM results r
    JOIN students s ON r.student_id = s.id
    WHERE r.status = 'Promoted'
    GROUP BY s.club
    ORDER BY avg_score DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Monthly trend for line chart
$monthly = $pdo->query("
    SELECT DATE_FORMAT(uploaded_at, '%Y-%m') as month, COUNT(*) as cnt
    FROM results
    WHERE status = 'Promoted'
    GROUP BY month
    ORDER BY month ASC
    LIMIT 12
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch filter options
$clubs = $pdo->query("SELECT DISTINCT club FROM students ORDER BY club ASC")->fetchAll(PDO::FETCH_COLUMN);
$grades = ['A+','A','B+','B','C','D','E','F'];
$statuses = ['Pending', 'Promoted', 'Not Pending'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - District Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --primary: #3b82f6; --success: #10b981; --warning: #f59e0b; --danger: #ef4444; --gray: #64748b; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6f9; margin: 0; padding-top: 65px; color: #1e293b; }
        .container { max-width: 1400px; margin: 0 auto; padding: 25px 20px; }
        
        /* Page Header */
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .page-title { font-size: 1.6rem; color: #1e293b; font-weight: 700; display: flex; align-items: center; gap: 12px; }
        .export-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn { padding: 10px 18px; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; font-size: 0.95rem; transition: 0.2s; text-decoration: none; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #2563eb; transform: translateY(-1px); }
        .btn-outline { background: white; color: var(--primary); border: 2px solid var(--primary); }
        .btn-outline:hover { background: #eff6ff; }
        
        /* Filters Card */
        .filters-card { background: white; padding: 20px; border-radius: 14px; box-shadow: 0 3px 10px rgba(0,0,0,0.06); margin-bottom: 25px; }
        .filters-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; align-items: end; }
        .filter-group label { display: block; font-weight: 500; color: #334155; margin-bottom: 6px; font-size: 0.9rem; }
        .filter-group input, .filter-group select { width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; background: #f8fafc; }
        .filter-group input:focus, .filter-group select:focus { border-color: var(--primary); outline: none; background: white; }
        .btn-apply { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 500; height: 42px; }
        .btn-apply:hover { background: #2563eb; }
        
        /* Stats Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .stat-card { background: white; padding: 20px; border-radius: 14px; box-shadow: 0 3px 10px rgba(0,0,0,0.06); text-align: center; }
        .stat-card .icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-size: 1.4rem; color: white; }
        .stat-card .icon.blue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        .stat-card .icon.green { background: linear-gradient(135deg, #10b981, #059669); }
        .stat-card .icon.orange { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-card .icon.purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .stat-card .value { font-size: 1.8rem; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
        .stat-card .label { color: var(--gray); font-size: 0.9rem; }
        
        /* Charts Grid */
        .charts-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .chart-card { background: white; padding: 20px; border-radius: 14px; box-shadow: 0 3px 10px rgba(0,0,0,0.06); }
        .chart-card h3 { font-size: 1.1rem; color: #1e293b; margin-bottom: 15px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .chart-container { position: relative; height: 280px; }
        
        /* Data Table */
        .table-section { background: white; border-radius: 14px; box-shadow: 0 3px 10px rgba(0,0,0,0.06); overflow: hidden; margin-bottom: 25px; }
        .table-header { padding: 15px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .table-title { font-weight: 600; color: #1e293b; font-size: 1.1rem; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th { background: #334155; color: white; padding: 14px 12px; text-align: left; font-weight: 600; font-size: 0.9rem; position: sticky; top: 0; }
        td { padding: 12px; border-bottom: 1px solid #f1f5f9; font-size: 0.93rem; vertical-align: middle; }
        tr:hover { background: #f8fafc; }
        .grade-badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 0.85rem; }
        .grade-a { background: #d1fae5; color: #065f46; } .grade-b { background: #dbeafe; color: #1e40af; }
        .grade-c { background: #fef3c7; color: #92400e; } .grade-d { background: #fee2e2; color: #991b1b; }
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-promoted { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .empty-state { text-align: center; padding: 40px 20px; color: var(--gray); }
        
        /* Print Styles */
        @media print {
            .no-print { display: none !important; }
            body { padding-top: 0; background: white; }
            .container { max-width: 100%; padding: 10px; }
            .chart-card { break-inside: avoid; page-break-inside: avoid; }
            table { font-size: 0.85rem; }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .page-header { flex-direction: column; align-items: flex-start; }
            .filters-grid { grid-template-columns: 1fr; }
            .charts-grid { grid-template-columns: 1fr; }
            .export-actions { width: 100%; justify-content: center; }
            .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<?php include 'district_manager_header.php'; ?>

<div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-chart-pie"></i> District Reports & Analytics</h1>
        <div class="export-actions no-print">
            <button class="btn btn-outline" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
            <button class="btn btn-primary" onclick="exportCSV()"><i class="fas fa-file-csv"></i> Export CSV</button>
            <button class="btn btn-outline" onclick="exportPDF()"><i class="fas fa-file-pdf"></i> Export PDF</button>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="filters-card no-print">
        <form method="GET" class="filters-grid">
            <div class="filter-group">
                <label><i class="far fa-calendar"></i> From Date</label>
                <input type="date" name="from" value="<?= htmlspecialchars($date_from) ?>">
            </div>
            <div class="filter-group">
                <label><i class="far fa-calendar"></i> To Date</label>
                <input type="date" name="to" value="<?= htmlspecialchars($date_to) ?>">
            </div>
            <div class="filter-group">
                <label><i class="fas fa-users"></i> Club</label>
                <select name="club">
                    <option value="">All Clubs</option>
                    <?php foreach($clubs as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= $filter_club === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-award"></i> Grade</label>
                <select name="grade">
                    <option value="">All Grades</option>
                    <?php foreach($grades as $g): ?>
                        <option value="<?= $g ?>" <?= $filter_grade === $g ? 'selected' : '' ?>><?= $g ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-toggle-on"></i> Status</label>
                <select name="status">
                    <?php foreach($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= $filter_status === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <button type="submit" class="btn-apply"><i class="fas fa-filter"></i> Apply Filters</button>
            </div>
        </form>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="icon blue"><i class="fas fa-list"></i></div>
            <div class="value"><?= number_format($stats['total']) ?></div>
            <div class="label">Total Records</div>
        </div>
        <div class="stat-card">
            <div class="icon green"><i class="fas fa-percentage"></i></div>
            <div class="value"><?= $stats['avg_score'] ?>%</div>
            <div class="label">Average Score</div>
        </div>
        <div class="stat-card">
            <div class="icon orange"><i class="fas fa-check-circle"></i></div>
            <div class="value"><?= $stats['pass_rate'] ?>%</div>
            <div class="label">Pass Rate (≥50%)</div>
        </div>
        <div class="stat-card">
            <div class="icon purple"><i class="fas fa-calendar-week"></i></div>
            <div class="value"><?= count($monthly) ?></div>
            <div class="label">Months Tracked</div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-grid">
        <!-- Grade Distribution Pie Chart -->
        <div class="chart-card">
            <h3><i class="fas fa-chart-pie"></i> Grade Distribution</h3>
            <div class="chart-container">
                <canvas id="gradeChart"></canvas>
            </div>
        </div>
        
        <!-- Club Performance Bar Chart -->
        <div class="chart-card">
            <h3><i class="fas fa-chart-bar"></i> Top Clubs by Avg Score</h3>
            <div class="chart-container">
                <canvas id="clubChart"></canvas>
            </div>
        </div>
        
        <!-- Monthly Trend Line Chart -->
        <div class="chart-card" style="grid-column: 1 / -1;">
            <h3><i class="fas fa-chart-line"></i> Promotions Over Time</h3>
            <div class="chart-container" style="height: 320px;">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Data Table Section -->
    <div class="table-section">
        <div class="table-header">
            <div class="table-title"><i class="fas fa-table"></i> Detailed Results (<?= count($report_data) ?> records)</div>
            <div class="no-print">
                <span style="color:var(--gray);font-size:0.9rem;">Showing filtered data</span>
            </div>
        </div>
        <div class="table-wrap">
            <table id="reportTable">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Username</th>
                        <th>Club</th>
                        <th>Standard</th>
                        <th>Talent</th>
                        <th>Score</th>
                        <th>Grade</th>
                        <th>Status</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($report_data): ?>
                        <?php foreach ($report_data as $row): 
                            $gradeClass = match(strtolower($row['grade'][0] ?? '')) {
                                'a' => 'grade-a', 'b' => 'grade-b', 'c' => 'grade-c', default => 'grade-d'
                            };
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['student_name']) ?></strong></td>
                            <td style="font-family:monospace;color:#2563eb"><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= htmlspecialchars($row['club']) ?></td>
                            <td><?= htmlspecialchars($row['standard']) ?></td>
                            <td><?= htmlspecialchars($row['talent_name']) ?></td>
                            <td><strong><?= $row['total_score'] ?>%</strong></td>
                            <td><span class="grade-badge <?= $gradeClass ?>"><?= $row['grade'] ?></span></td>
                            <td><span class="status-badge status-<?= strtolower(str_replace(' ', '-', $row['status'])) ?>"><?= $row['status'] ?></span></td>
                            <td><?= date('M d, Y', strtotime($row['uploaded_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="empty-state"><i class="fas fa-inbox fa-2x"></i><br><br><strong>No results match your filters</strong><br>Try adjusting date range or criteria.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// 📊 Chart.js Configuration

// Grade Distribution Pie Chart
<?php if (!empty($grade_dist)): ?>
new Chart(document.getElementById('gradeChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_keys($grade_dist)) ?>,
        datasets: [{
            data: <?= json_encode(array_values($grade_dist)) ?>,
            backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#6366f1', '#14b8a6'],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true } } }
    }
});
<?php endif; ?>

// Club Performance Bar Chart
<?php if (!empty($club_perf)): ?>
new Chart(document.getElementById('clubChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($club_perf, 'club')) ?>,
        datasets: [{
            label: 'Average Score',
            data: <?= json_encode(array_column($club_perf, 'avg_score')) ?>,
            backgroundColor: 'rgba(59, 130, 246, 0.7)',
            borderColor: '#3b82f6',
            borderWidth: 2,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, max: 100, title: { display: true, text: 'Score (%)' } },
            x: { ticks: { maxRotation: 45, minRotation: 45 } }
        }
    }
});
<?php endif; ?>

// Monthly Trend Line Chart
<?php if (!empty($monthly)): ?>
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($monthly, 'month')) ?>,
        datasets: [{
            label: 'Promotions',
            data: <?= json_encode(array_column($monthly, 'cnt')) ?>,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#10b981',
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 5 }, title: { display: true, text: 'Count' } },
            x: { title: { display: true, text: 'Month' } }
        }
    }
});
<?php endif; ?>

// 📤 Export Functions
function exportCSV() {
    const table = document.getElementById('reportTable');
    let rows = Array.from(table.querySelectorAll('tr'));
    if (rows.length <= 1) { alert('No data to export'); return; }
    
    let csv = [];
    rows.forEach(row => {
        let cells = [];
        row.querySelectorAll('th, td').forEach(cell => {
            let text = cell.textContent.trim().replace(/"/g, '""');
            cells.push(`"${text}"`);
        });
        csv.push(cells.join(','));
    });
    
    const blob = new Blob(['\ufeff' + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `district_report_<?= date('Y-m-d') ?>.csv`;
    link.click();
}

function exportPDF() {
    // Simple print-based PDF export (browser-native)
    window.print();
    // For advanced PDF: integrate jsPDF + html2canvas
}

// 🔍 Auto-submit filters on date change (optional UX enhancement)
document.querySelectorAll('.filters-grid input[type="date"], .filters-grid select').forEach(el => {
    el.addEventListener('change', function() {
        // Optional: auto-submit on change
        // this.closest('form').submit();
    });
});
</script>

</body>
</html>