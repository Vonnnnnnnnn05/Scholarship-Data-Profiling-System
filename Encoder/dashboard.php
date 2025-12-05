<?php
session_start();

// Check if user is logged in and is encoder
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

if ($_SESSION['role'] !== 'encoder') {
    header('Location: ../index.php');
    exit;
}

include '../conn.php';

// Fetch statistics for encoder (only scholars they encoded)
$user_id = $_SESSION['user_id'];

$stats = [
    'my_scholars' => 0,
    'this_month' => 0,
    'total_scholars' => 0
];

// Get scholars encoded by this user
$result = $conn->query("SELECT COUNT(*) as count FROM scholars WHERE encoded_by = $user_id");
if ($result) {
    $stats['my_scholars'] = $result->fetch_assoc()['count'];
}

// Get scholars encoded this month
$result = $conn->query("SELECT COUNT(*) as count FROM scholars WHERE encoded_by = $user_id AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
if ($result) {
    $stats['this_month'] = $result->fetch_assoc()['count'];
}

// Get total scholars in system
$result = $conn->query("SELECT COUNT(*) as count FROM scholars");
if ($result) {
    $stats['total_scholars'] = $result->fetch_assoc()['count'];
}

// Get recent scholars by this encoder
$recent_scholars = [];
$result = $conn->query("SELECT s.*, c.campus_name, sc.scholarship_name 
                        FROM scholars s 
                        LEFT JOIN campuses c ON s.campus_id = c.id 
                        LEFT JOIN scholarships sc ON s.scholarship_id = sc.id 
                        WHERE s.encoded_by = $user_id
                        ORDER BY s.created_at DESC LIMIT 10");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_scholars[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encoder Dashboard - SKSU SDP</title>
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
        .stat-icon.green { background: linear-gradient(135deg, #0f9d58, #34a853); }
        .stat-icon.orange { background: linear-gradient(135deg, #f4b400, #fbbc04); }

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

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .action-btn {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-decoration: none;
            color: #202124;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .action-btn i {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
            background: linear-gradient(135deg, #1a73e8, #4285f4);
        }

        .action-btn span {
            font-size: 15px;
            font-weight: 500;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
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
    <?php include '../sidebars/encoderSB.php'; ?>

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
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>! Here's your encoding summary.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['my_scholars']); ?></h3>
                    <p>Scholars I Encoded</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['this_month']); ?></h3>
                    <p>Encoded This Month</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-database"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total_scholars']); ?></h3>
                    <p>Total Scholars in System</p>
                </div>
            </div>
        </div>

        <div class="quick-actions">
            <a href="add-scholar.php" class="action-btn">
                <i class="fas fa-user-plus"></i>
                <span>Add New Scholar</span>
            </a>
            <a href="scholars.php" class="action-btn">
                <i class="fas fa-list"></i>
                <span>View My Scholars</span>
            </a>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>My Recent Entries</h2>
                <a href="scholars.php">View All</a>
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
                                    <i class="fas fa-info-circle" style="font-size: 48px; margin-bottom: 15px; display: block; color: #dadce0;"></i>
                                    <p>You haven't encoded any scholars yet.</p>
                                    <p style="margin-top: 10px;"><a href="add-scholar.php" style="color: #1a73e8;">Add your first scholar</a></p>
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
