<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: ../index.php');
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

include '../conn.php';

// Get scholarship distribution data
$distribution = [];
$result = $conn->query("SELECT 
                            s.scholarship_name,
                            s.amount_per_sem,
                            COUNT(sc.id) as scholar_count,
                            COUNT(sc.id) * s.amount_per_sem as total_amount,
                            GROUP_CONCAT(DISTINCT c.campus_name SEPARATOR ', ') as campuses
                        FROM scholarships s
                        LEFT JOIN scholars sc ON s.id = sc.scholarship_id
                        LEFT JOIN campuses c ON sc.campus_id = c.id
                        GROUP BY s.id
                        ORDER BY scholar_count DESC, s.scholarship_name ASC");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $distribution[] = $row;
    }
}

// Calculate totals
$total_scholars = 0;
$total_budget = 0;
foreach ($distribution as $item) {
    $total_scholars += $item['scholar_count'];
    $total_budget += $item['total_amount'];
}

// Get campus-wise distribution
$campus_distribution = [];
$result = $conn->query("SELECT 
                            c.campus_name,
                            COUNT(DISTINCT sc.id) as scholar_count,
                            COUNT(DISTINCT sc.scholarship_id) as scholarship_count
                        FROM campuses c
                        LEFT JOIN scholars sc ON c.id = sc.campus_id
                        GROUP BY c.id
                        ORDER BY scholar_count DESC");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $campus_distribution[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholarship Distribution Report - SKSU SDP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .btn-secondary {
            background: #5f6368;
            color: white;
        }

        .btn-secondary:hover {
            background: #4a4d52;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-icon.blue { background: linear-gradient(135deg, #1a73e8, #4285f4); }
        .stat-icon.green { background: linear-gradient(135deg, #0f9d58, #34a853); }
        .stat-icon.orange { background: linear-gradient(135deg, #f4b400, #fbbc04); }

        .stat-info h3 {
            font-size: 28px;
            color: #202124;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: #5f6368;
            font-size: 14px;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 20px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }

        .card-header h2 {
            font-size: 20px;
            color: #202124;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
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
            white-space: nowrap;
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

        .amount {
            font-weight: 600;
            color: #0f9d58;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-primary {
            background: #e8f0fe;
            color: #1a73e8;
        }

        .badge-success {
            background: #e6f4ea;
            color: #0f9d58;
        }

        .chart-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .chart-container {
            position: relative;
            height: 350px;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 8px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #1a73e8, #4285f4);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        @media print {
            .sidebar, .btn, .page-header .btn-secondary {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
            }
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .chart-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../sidebars/adminSB.php'; ?>

    <script>
        // Set active menu item
        document.addEventListener('DOMContentLoaded', function() {
            // Remove active class from all menu items
            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Add active class to Distribution Report menu item
            document.querySelectorAll('.menu-item').forEach(item => {
                if (item.getAttribute('href') === 'scholarship-distribution.php') {
                    item.classList.add('active');
                }
            });
        });
    </script>

    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-chart-pie"></i> Scholarship Distribution Report</h1>
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($total_scholars); ?></h3>
                    <p>Total Scholars</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-info">
                    <h3>â‚±<?php echo number_format($total_budget, 2); ?></h3>
                    <p>Total Budget per Semester</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-award"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo count($distribution); ?></h3>
                    <p>Active Scholarship Programs</p>
                </div>
            </div>
        </div>

        <div class="chart-grid">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-chart-bar"></i> Scholars per Scholarship</h2>
                </div>
                <div class="chart-container">
                    <canvas id="scholarshipChart"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-chart-pie"></i> Budget Distribution</h2>
                </div>
                <div class="chart-container">
                    <canvas id="budgetChart"></canvas>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-table"></i> Detailed Distribution Report</h2>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Scholarship Program</th>
                            <th>Amount/Semester</th>
                            <th>Scholars Enrolled</th>
                            <th>Total Budget/Semester</th>
                            <th>Coverage</th>
                            <th>Campuses</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($distribution) > 0): ?>
                            <?php foreach ($distribution as $item): ?>
                                <?php 
                                    $percentage = $total_scholars > 0 ? ($item['scholar_count'] / $total_scholars) * 100 : 0;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['scholarship_name']); ?></strong></td>
                                    <td><span class="amount">â‚±<?php echo number_format($item['amount_per_sem'], 2); ?></span></td>
                                    <td>
                                        <span class="badge badge-primary">
                                            <i class="fas fa-user-graduate"></i> <?php echo $item['scholar_count']; ?>
                                        </span>
                                    </td>
                                    <td><span class="amount">â‚±<?php echo number_format($item['total_amount'], 2); ?></span></td>
                                    <td>
                                        <div style="min-width: 100px;">
                                            <strong><?php echo number_format($percentage, 1); ?>%</strong>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($item['campuses']): ?>
                                            <small style="color: #5f6368;"><?php echo htmlspecialchars($item['campuses']); ?></small>
                                        <?php else: ?>
                                            <small style="color: #dadce0;">No campuses</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr style="background: #f8f9fa; font-weight: 600;">
                                <td colspan="2"><strong>TOTAL</strong></td>
                                <td>
                                    <span class="badge badge-success">
                                        <i class="fas fa-user-graduate"></i> <?php echo number_format($total_scholars); ?>
                                    </span>
                                </td>
                                <td><span class="amount">â‚±<?php echo number_format($total_budget, 2); ?></span></td>
                                <td colspan="2"></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: #5f6368;">
                                    No distribution data available
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-building"></i> Campus Distribution Summary</h2>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Campus</th>
                            <th>Total Scholars</th>
                            <th>Different Scholarships</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($campus_distribution) > 0): ?>
                            <?php foreach ($campus_distribution as $campus): ?>
                                <?php 
                                    $percentage = $total_scholars > 0 ? ($campus['scholar_count'] / $total_scholars) * 100 : 0;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($campus['campus_name']); ?></strong></td>
                                    <td>
                                        <span class="badge badge-primary">
                                            <i class="fas fa-user-graduate"></i> <?php echo $campus['scholar_count']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $campus['scholarship_count']; ?> programs</td>
                                    <td>
                                        <div style="min-width: 120px;">
                                            <strong><?php echo number_format($percentage, 1); ?>%</strong>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%;"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 30px; color: #5f6368;">
                                    No campus data available
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Scholarship Distribution Chart
        const scholarshipCtx = document.getElementById('scholarshipChart').getContext('2d');
        new Chart(scholarshipCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($distribution, 'scholarship_name')); ?>,
                datasets: [{
                    label: 'Number of Scholars',
                    data: <?php echo json_encode(array_column($distribution, 'scholar_count')); ?>,
                    backgroundColor: 'rgba(26, 115, 232, 0.8)',
                    borderColor: 'rgba(26, 115, 232, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        // Budget Distribution Chart
        const budgetCtx = document.getElementById('budgetChart').getContext('2d');
        new Chart(budgetCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($distribution, 'scholarship_name')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($distribution, 'total_amount')); ?>,
                    backgroundColor: [
                        'rgba(26, 115, 232, 0.8)',
                        'rgba(15, 157, 88, 0.8)',
                        'rgba(244, 180, 0, 0.8)',
                        'rgba(147, 51, 234, 0.8)',
                        'rgba(234, 67, 53, 0.8)',
                        'rgba(52, 168, 83, 0.8)',
                        'rgba(66, 133, 244, 0.8)',
                        'rgba(251, 188, 4, 0.8)',
                        'rgba(234, 67, 53, 0.8)',
                        'rgba(147, 51, 234, 0.8)'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 10,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += 'â‚±' + context.parsed.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                return label;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
