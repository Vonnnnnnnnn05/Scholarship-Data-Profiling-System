<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

include '../conn.php';

// Fetch dashboard statistics
$stats = [
    'total_scholars' => 0,
    'total_scholarships' => 0,
    'total_campuses' => 0,
    'recent_scholars' => 0
];

// Get total scholars
$result = $conn->query("SELECT COUNT(*) as count FROM scholars");
if ($result) {
    $stats['total_scholars'] = $result->fetch_assoc()['count'];
}

// Get total scholarships
$result = $conn->query("SELECT COUNT(*) as count FROM scholarships");
if ($result) {
    $stats['total_scholarships'] = $result->fetch_assoc()['count'];
}

// Get total campuses
$result = $conn->query("SELECT COUNT(*) as count FROM campuses");
if ($result) {
    $stats['total_campuses'] = $result->fetch_assoc()['count'];
}

// Get scholars added this month
$result = $conn->query("SELECT COUNT(*) as count FROM scholars WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
if ($result) {
    $stats['recent_scholars'] = $result->fetch_assoc()['count'];
}

// Get recent scholars
$recent_scholars = [];
$result = $conn->query("SELECT s.*, c.campus_name, sc.scholarship_name 
                        FROM scholars s 
                        LEFT JOIN campuses c ON s.campus_id = c.id 
                        LEFT JOIN scholarships sc ON s.scholarship_id = sc.id 
                        ORDER BY s.created_at DESC LIMIT 5");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_scholars[] = $row;
    }
}

// Get scholarship distribution
$scholarship_distribution = [];
$result = $conn->query("SELECT sc.scholarship_name, COUNT(s.id) as scholar_count, sc.amount_per_sem
                        FROM scholarships sc
                        LEFT JOIN scholars s ON sc.id = s.scholarship_id
                        GROUP BY sc.id
                        ORDER BY scholar_count DESC
                        LIMIT 6");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $scholarship_distribution[] = $row;
    }
}

// Get campus distribution
$campus_distribution = [];
$result = $conn->query("SELECT c.campus_name, COUNT(s.id) as scholar_count
                        FROM campuses c
                        LEFT JOIN scholars s ON c.id = s.campus_id
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
    <title>Admin Dashboard - SKSU SDP</title>
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
            margin-bottom: 5px;
        }

        .page-header p {
            color: #5f6368;
            font-size: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
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
        .stat-icon.orange { background: linear-gradient(135deg, #f4b400, #fbbc04); }
        .stat-icon.purple { background: linear-gradient(135deg, #9333ea, #a855f7); }
        .stat-icon.red { background: linear-gradient(135deg, #ea4335, #ef5350); }

        .stat-info h3 {
            font-size: 32px;
            color: #202124;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: #5f6368;
            font-size: 14px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 25px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .card-header h2 {
            font-size: 18px;
            color: #202124;
            font-weight: 600;
        }

        .card-header a {
            color: #1a73e8;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .card-header a:hover {
            text-decoration: underline;
        }

        .chart-container {
            position: relative;
            height: 300px;
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

        .full-width-card {
            grid-column: 1 / -1;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
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
            
            // Add active class to Dashboard menu item
            document.querySelectorAll('.menu-item').forEach(item => {
                if (item.getAttribute('href') === 'dashboard.php') {
                    item.classList.add('active');
                }
            });
        });
    </script>

    <div class="main-content">
        <div class="page-header">
            <h1>Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>! Here's what's happening today.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total_scholars']); ?></h3>
                    <p>Total Scholars</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-award"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total_scholarships']); ?></h3>
                    <p>Scholarship Programs</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total_campuses']); ?></h3>
                    <p>Campuses</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon red">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['recent_scholars']); ?></h3>
                    <p>New This Month</p>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <div class="card-header">
                    <h2>Scholarship Distribution</h2>
                    <a href="scholarship-distribution.php">View Details</a>
                </div>
                <div class="chart-container">
                    <canvas id="scholarshipChart"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Campus Distribution</h2>
                    <a href="campuses.php">View All</a>
                </div>
                <div class="chart-container">
                    <canvas id="campusChart"></canvas>
                </div>
            </div>
        </div>

        <div class="card full-width-card">
            <div class="card-header">
                <h2>Recent Scholars</h2>
                <a href="scholars.php">View All Scholars</a>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Year Level</th>
                            <th>Course</th>
                            <th>Campus</th>
                            <th>Scholarship</th>
                            <th>Date Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_scholars) > 0): ?>
                            <?php foreach ($recent_scholars as $scholar): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($scholar['last_name'] . ', ' . $scholar['first_name'] . ' ' . $scholar['middle_initial'] . '.'); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($scholar['year_level']); ?></td>
                                    <td><?php echo htmlspecialchars($scholar['course']); ?></td>
                                    <td><span class="badge badge-primary"><?php echo htmlspecialchars($scholar['campus_name']); ?></span></td>
                                    <td><?php echo htmlspecialchars($scholar['scholarship_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($scholar['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: #5f6368;">
                                    No scholars added yet
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
                labels: <?php echo json_encode(array_column($scholarship_distribution, 'scholarship_name')); ?>,
                datasets: [{
                    label: 'Number of Scholars',
                    data: <?php echo json_encode(array_column($scholarship_distribution, 'scholar_count')); ?>,
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

        // Campus Distribution Chart
        const campusCtx = document.getElementById('campusChart').getContext('2d');
        new Chart(campusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($campus_distribution, 'campus_name')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($campus_distribution, 'scholar_count')); ?>,
                    backgroundColor: [
                        'rgba(26, 115, 232, 0.8)',
                        'rgba(15, 157, 88, 0.8)',
                        'rgba(244, 180, 0, 0.8)',
                        'rgba(147, 51, 234, 0.8)',
                        'rgba(234, 67, 53, 0.8)',
                        'rgba(52, 168, 83, 0.8)',
                        'rgba(66, 133, 244, 0.8)'
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
                    }
                }
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
