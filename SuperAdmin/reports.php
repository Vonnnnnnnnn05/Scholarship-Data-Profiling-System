<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

if ($_SESSION['role'] !== 'super_admin') {
    header('Location: ../index.php');
    exit;
}

include '../conn.php';

// Get filter parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-t');
$campus_filter = isset($_GET['campus']) ? $_GET['campus'] : '';
$scholarship_filter = isset($_GET['scholarship']) ? $_GET['scholarship'] : '';
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'summary';

// Build query conditions
$where = [];
$params = [];
$types = '';

$where[] = "DATE(s.created_at) BETWEEN ? AND ?";
$params[] = $date_from;
$params[] = $date_to;
$types .= 'ss';

if (!empty($campus_filter)) {
    $where[] = "s.campus_id = ?";
    $params[] = $campus_filter;
    $types .= 'i';
}

if (!empty($scholarship_filter)) {
    $where[] = "s.scholarship_id = ?";
    $params[] = $scholarship_filter;
    $types .= 'i';
}

$where_clause = 'WHERE ' . implode(' AND ', $where);

// Get campuses for filter
$campuses = $conn->query("SELECT DISTINCT c.id, c.campus_name FROM scholars s LEFT JOIN campuses c ON s.campus_id = c.id ORDER BY c.campus_name");

// Get scholarships for filter
$scholarships = $conn->query("SELECT scholarship_name FROM scholarships ORDER BY scholarship_name");

// Get report data based on type
if ($report_type == 'summary') {
    // Summary Report
    $query = "SELECT 
                c.campus_name,
                sch.scholarship_name,
                COUNT(*) as scholar_count,
                sch.amount_per_sem,
                (COUNT(*) * sch.amount_per_sem) as total_budget
              FROM scholars s
              LEFT JOIN scholarships sch ON s.scholarship_id = sch.id
              LEFT JOIN campuses c ON s.campus_id = c.id
              $where_clause
              GROUP BY s.campus_id, s.scholarship_id, c.campus_name, sch.scholarship_name
              ORDER BY c.campus_name, sch.scholarship_name";
} elseif ($report_type == 'detailed') {
    // Detailed Report
    $query = "SELECT 
                s.*,
                c.campus_name,
                sch.scholarship_name,
                sch.amount_per_sem,
                u.name as encoded_by_name
              FROM scholars s
              LEFT JOIN scholarships sch ON s.scholarship_id = sch.id
              LEFT JOIN campuses c ON s.campus_id = c.id
              LEFT JOIN users u ON s.encoded_by = u.id
              $where_clause
              ORDER BY c.campus_name, s.last_name";
} else {
    // Campus Report
    $query = "SELECT 
                c.campus_name,
                COUNT(*) as total_scholars,
                COUNT(DISTINCT s.scholarship_id) as scholarship_types,
                SUM(sch.amount_per_sem) as total_budget
              FROM scholars s
              LEFT JOIN scholarships sch ON s.scholarship_id = sch.id
              LEFT JOIN campuses c ON s.campus_id = c.id
              $where_clause
              GROUP BY s.campus_id, c.campus_name
              ORDER BY total_scholars DESC";
}

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$report_data = $stmt->get_result();

// Get overall statistics
$stats_query = "SELECT 
                    COUNT(*) as total_scholars,
                    COUNT(DISTINCT campus_id) as total_campuses,
                    COUNT(DISTINCT scholarship_id) as total_scholarships
                FROM scholars s $where_clause";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param($types, ...$params);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - SKSU SDP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f5f5;
        }

        .main-content {
            margin-left: 260px;
            padding: 30px;
            min-height: 100vh;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 28px;
            color: #202124;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #1a73e8;
            color: white;
        }

        .btn-primary:hover {
            background: #1557b0;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            border-radius: 8px;
            color: white;
        }

        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stat-card i {
            font-size: 32px;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .stat-card h3 {
            font-size: 32px;
            margin-bottom: 5px;
        }

        .stat-card p {
            font-size: 14px;
            opacity: 0.9;
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-size: 13px;
            color: #5f6368;
            font-weight: 500;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px 12px;
            border: 1px solid #dadce0;
            border-radius: 6px;
            font-size: 14px;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #1a73e8;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-size: 13px;
            color: #5f6368;
            font-weight: 600;
            border-bottom: 2px solid #e0e0e0;
        }

        table td {
            padding: 12px;
            font-size: 14px;
            color: #202124;
            border-bottom: 1px solid #e0e0e0;
        }

        table tr:hover {
            background: #f8f9fa;
        }

        @media print {
            .main-content {
                margin-left: 0;
            }
            .page-header, .filters, .btn {
                display: none;
            }
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include '../sidebars/superadminSB.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
            });
            
            document.querySelectorAll('.menu-item').forEach(item => {
                if (item.getAttribute('href') === 'reports.php') {
                    item.classList.add('active');
                }
            });
        });

        function printReport() {
            window.print();
        }
    </script>

    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-file-alt"></i> Reports</h1>
            <button onclick="printReport()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <h3><?php echo number_format($stats['total_scholars']); ?></h3>
                <p>Total Scholars</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-school"></i>
                <h3><?php echo number_format($stats['total_campuses']); ?></h3>
                <p>Campuses</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-graduation-cap"></i>
                <h3><?php echo number_format($stats['total_scholarships']); ?></h3>
                <p>Scholarship Types</p>
            </div>
        </div>

        <div class="card">
            <form method="GET" action="">
                <div class="filters">
                    <div class="filter-group">
                        <label for="report_type">Report Type</label>
                        <select id="report_type" name="report_type">
                            <option value="summary" <?php echo $report_type == 'summary' ? 'selected' : ''; ?>>Summary Report</option>
                            <option value="detailed" <?php echo $report_type == 'detailed' ? 'selected' : ''; ?>>Detailed Report</option>
                            <option value="campus" <?php echo $report_type == 'campus' ? 'selected' : ''; ?>>Campus Report</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="date_from">Date From</label>
                        <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                    </div>
                    <div class="filter-group">
                        <label for="date_to">Date To</label>
                        <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                    </div>
                    <div class="filter-group">
                        <label for="campus">Campus</label>
                        <select id="campus" name="campus">
                            <option value="">All Campuses</option>
                            <?php while ($campus = $campuses->fetch_assoc()): ?>
                                <option value="<?php echo $campus['id']; ?>" <?php echo $campus_filter == $campus['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($campus['campus_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="scholarship">Scholarship</label>
                        <select id="scholarship" name="scholarship">
                            <option value="">All Scholarships</option>
                            <?php while ($scholarship = $scholarships->fetch_assoc()): ?>
                                <option value="<?php echo $scholarship['id']; ?>" <?php echo $scholarship_filter == $scholarship['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($scholarship['scholarship_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group" style="justify-content: flex-end;">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sync"></i> Generate
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="card">
            <h2 style="margin-bottom: 20px; color: #202124;">
                <?php 
                    if ($report_type == 'summary') echo 'Summary Report';
                    elseif ($report_type == 'detailed') echo 'Detailed Scholar Report';
                    else echo 'Campus Report';
                ?>
            </h2>
            
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <?php if ($report_type == 'summary'): ?>
                                <th>Campus</th>
                                <th>Scholarship Type</th>
                                <th>Scholar Count</th>
                                <th>Amount/Sem</th>
                                <th>Total Budget</th>
                            <?php elseif ($report_type == 'detailed'): ?>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Campus</th>
                                <th>Course</th>
                                <th>Scholarship</th>
                                <th>Amount/Sem</th>
                                <th>Encoded By</th>
                            <?php else: ?>
                                <th>Campus</th>
                                <th>Total Scholars</th>
                                <th>Scholarship Types</th>
                                <th>Total Budget</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($report_data->num_rows > 0): ?>
                            <?php while ($row = $report_data->fetch_assoc()): ?>
                                <tr>
                                    <?php if ($report_type == 'summary'): ?>
                                        <td><?php echo htmlspecialchars($row['campus_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['scholarship_name']); ?></td>
                                        <td><?php echo number_format($row['scholar_count']); ?></td>
                                        <td>₱<?php echo number_format($row['amount_per_sem'], 2); ?></td>
                                        <td>₱<?php echo number_format($row['total_budget'], 2); ?></td>
                                    <?php elseif ($report_type == 'detailed'): ?>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['last_name'] . ', ' . $row['first_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['campus_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['course']); ?></td>
                                        <td><?php echo htmlspecialchars($row['scholarship_name']); ?></td>
                                        <td>₱<?php echo number_format($row['amount_per_sem'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($row['encoded_by_name']); ?></td>
                                    <?php else: ?>
                                        <td><?php echo htmlspecialchars($row['campus_name']); ?></td>
                                        <td><?php echo number_format($row['total_scholars']); ?></td>
                                        <td><?php echo number_format($row['scholarship_types']); ?></td>
                                        <td>₱<?php echo number_format($row['total_budget'], 2); ?></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo $report_type == 'detailed' ? '7' : ($report_type == 'summary' ? '5' : '4'); ?>" style="text-align: center; padding: 30px;">
                                    No data found for selected filters
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
