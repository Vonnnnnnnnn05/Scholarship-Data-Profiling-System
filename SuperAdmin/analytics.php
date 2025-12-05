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

// Get analytics data
// Trend analysis - scholars by month
$trend_query = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as count
                FROM scholars
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY month
                ORDER BY month";
$trend_data = $conn->query($trend_query);
$months = [];
$counts = [];
while ($row = $trend_data->fetch_assoc()) {
    $months[] = date('M Y', strtotime($row['month'] . '-01'));
    $counts[] = $row['count'];
}

// Scholarship distribution
$scholarship_query = "SELECT 
                        sch.scholarship_name,
                        COUNT(*) as count,
                        sch.amount_per_sem
                      FROM scholars s
                      LEFT JOIN scholarships sch ON s.scholarship_id = sch.id
                      GROUP BY s.scholarship_id, sch.scholarship_name
                      ORDER BY count DESC
                      LIMIT 10";
$scholarship_data = $conn->query($scholarship_query);
$scholarship_names = [];
$scholarship_counts = [];
while ($row = $scholarship_data->fetch_assoc()) {
    $scholarship_names[] = $row['scholarship_name'];
    $scholarship_counts[] = $row['count'];
}

// Campus distribution
$campus_query = "SELECT c.campus_name, COUNT(*) as count FROM scholars s LEFT JOIN campuses c ON s.campus_id = c.id GROUP BY s.campus_id, c.campus_name ORDER BY count DESC";
$campus_data = $conn->query($campus_query);
$campus_names = [];
$campus_counts = [];
while ($row = $campus_data->fetch_assoc()) {
    $campus_names[] = $row['campus_name'];
    $campus_counts[] = $row['count'];
}

// Year level distribution
$year_query = "SELECT year_level, COUNT(*) as count FROM scholars GROUP BY year_level ORDER BY year_level";
$year_data = $conn->query($year_query);
$year_levels = [];
$year_counts = [];
while ($row = $year_data->fetch_assoc()) {
    $year_levels[] = $row['year_level'];
    $year_counts[] = $row['count'];
}

// Top encoders
$encoder_query = "SELECT 
                    u.name,
                    COUNT(s.id) as scholar_count
                  FROM users u
                  LEFT JOIN scholars s ON u.id = s.encoded_by
                  WHERE u.role IN ('encoder', 'admin', 'super_admin')
                  GROUP BY u.id
                  ORDER BY scholar_count DESC
                  LIMIT 5";
$encoder_data = $conn->query($encoder_query);

// Overall statistics
$total_scholars = $conn->query("SELECT COUNT(*) as count FROM scholars")->fetch_assoc()['count'];
$total_budget = $conn->query("SELECT SUM(sch.amount_per_sem) as total FROM scholars s LEFT JOIN scholarships sch ON s.scholarship_id = sch.id")->fetch_assoc()['total'];
$total_campuses = $conn->query("SELECT COUNT(DISTINCT campus_id) as count FROM scholars")->fetch_assoc()['count'];
$total_scholarships = $conn->query("SELECT COUNT(*) as count FROM scholarships")->fetch_assoc()['count'];

// This month's growth
$this_month = $conn->query("SELECT COUNT(*) as count FROM scholars WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())")->fetch_assoc()['count'];
$last_month = $conn->query("SELECT COUNT(*) as count FROM scholars WHERE MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))")->fetch_assoc()['count'];
$growth = $last_month > 0 ? (($this_month - $last_month) / $last_month) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - SKSU SDP</title>
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
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 28px;
            color: #202124;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, #1a73e8 0%, #0d47a1 100%);
        }

        .stat-card:nth-child(2)::before {
            background: linear-gradient(180deg, #34a853 0%, #0f9d58 100%);
        }

        .stat-card:nth-child(3)::before {
            background: linear-gradient(180deg, #fbbc04 0%, #f9ab00 100%);
        }

        .stat-card:nth-child(4)::before {
            background: linear-gradient(180deg, #ea4335 0%, #d33828 100%);
        }

        .stat-card i {
            font-size: 32px;
            color: #1a73e8;
            margin-bottom: 15px;
        }

        .stat-card:nth-child(2) i {
            color: #34a853;
        }

        .stat-card:nth-child(3) i {
            color: #fbbc04;
        }

        .stat-card:nth-child(4) i {
            color: #ea4335;
        }

        .stat-card h3 {
            font-size: 36px;
            color: #202124;
            margin-bottom: 5px;
        }

        .stat-card p {
            font-size: 14px;
            color: #5f6368;
        }

        .stat-card .trend {
            margin-top: 10px;
            font-size: 13px;
            font-weight: 500;
        }

        .trend.up {
            color: #34a853;
        }

        .trend.down {
            color: #ea4335;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .chart-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .chart-card h2 {
            font-size: 18px;
            color: #202124;
            margin-bottom: 20px;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .table-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .table-card h2 {
            font-size: 18px;
            color: #202124;
            margin-bottom: 20px;
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

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .charts-grid {
                grid-template-columns: 1fr;
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
                if (item.getAttribute('href') === 'analytics.php') {
                    item.classList.add('active');
                }
            });
        });
    </script>

    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-chart-line"></i> Analytics Dashboard</h1>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <h3><?php echo number_format($total_scholars); ?></h3>
                <p>Total Scholars</p>
                <div class="trend <?php echo $growth >= 0 ? 'up' : 'down'; ?>">
                    <i class="fas fa-arrow-<?php echo $growth >= 0 ? 'up' : 'down'; ?>"></i>
                    <?php echo number_format(abs($growth), 1); ?>% from last month
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-peso-sign"></i>
                <h3>₱<?php echo number_format($total_budget, 0); ?></h3>
                <p>Total Budget</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-school"></i>
                <h3><?php echo number_format($total_campuses); ?></h3>
                <p>Active Campuses</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-graduation-cap"></i>
                <h3><?php echo number_format($total_scholarships); ?></h3>
                <p>Scholarship Programs</p>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <h2><i class="fas fa-chart-line"></i> Scholar Enrollment Trend (Last 6 Months)</h2>
                <div class="chart-container">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h2><i class="fas fa-chart-pie"></i> Campus Distribution</h2>
                <div class="chart-container">
                    <canvas id="campusChart"></canvas>
                </div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <h2><i class="fas fa-chart-bar"></i> Top 10 Scholarships</h2>
                <div class="chart-container">
                    <canvas id="scholarshipChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h2><i class="fas fa-layer-group"></i> Year Level Distribution</h2>
                <div class="chart-container">
                    <canvas id="yearChart"></canvas>
                </div>
            </div>
        </div>

        <div class="table-card">
            <h2><i class="fas fa-trophy"></i> Top Encoders</h2>
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Encoder Name</th>
                        <th>Scholars Encoded</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    while ($encoder = $encoder_data->fetch_assoc()): 
                    ?>
                        <tr>
                            <td><strong>#<?php echo $rank++; ?></strong></td>
                            <td><?php echo htmlspecialchars($encoder['name']); ?></td>
                            <td><?php echo number_format($encoder['scholar_count']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Trend Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'New Scholars',
                    data: <?php echo json_encode($counts); ?>,
                    borderColor: '#1a73e8',
                    backgroundColor: 'rgba(26, 115, 232, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Campus Chart
        const campusCtx = document.getElementById('campusChart').getContext('2d');
        new Chart(campusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($campus_names); ?>,
                datasets: [{
                    data: <?php echo json_encode($campus_counts); ?>,
                    backgroundColor: [
                        '#1a73e8',
                        '#34a853',
                        '#fbbc04',
                        '#ea4335',
                        '#9334e6',
                        '#00bcd4',
                        '#ff5722'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Scholarship Chart
        const scholarshipCtx = document.getElementById('scholarshipChart').getContext('2d');
        new Chart(scholarshipCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($scholarship_names); ?>,
                datasets: [{
                    label: 'Scholars',
                    data: <?php echo json_encode($scholarship_counts); ?>,
                    backgroundColor: '#1a73e8'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Year Level Chart
        const yearCtx = document.getElementById('yearChart').getContext('2d');
        new Chart(yearCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($year_levels); ?>,
                datasets: [{
                    label: 'Scholars',
                    data: <?php echo json_encode($year_counts); ?>,
                    backgroundColor: '#34a853'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
