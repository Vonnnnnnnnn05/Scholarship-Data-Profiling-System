<?php
session_start();

// Check if user is logged in and is super admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

if ($_SESSION['role'] !== 'super_admin') {
    header('Location: ../index.php');
    exit;
}

include '../conn.php';

// Get all campuses with scholar count
$campuses = [];
$result = $conn->query("SELECT 
                            c.*,
                            COUNT(DISTINCT s.id) as scholar_count,
                            COUNT(DISTINCT s.scholarship_id) as scholarship_count
                        FROM campuses c
                        LEFT JOIN scholars s ON c.id = s.campus_id
                        GROUP BY c.id
                        ORDER BY c.campus_name ASC");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $campuses[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campuses Management - SKSU SDP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        .btn-danger {
            background: #ea4335;
            color: white;
        }

        .btn-danger:hover {
            background: #d33828;
        }

        .btn-secondary {
            background: #5f6368;
            color: white;
        }

        .btn-secondary:hover {
            background: #4a4d52;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }

        .stats-bar {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }

        .stat-icon.blue { background: linear-gradient(135deg, #1a73e8, #4285f4); }
        .stat-icon.green { background: linear-gradient(135deg, #0f9d58, #34a853); }
        .stat-icon.orange { background: linear-gradient(135deg, #f4b400, #fbbc04); }

        .stat-info h3 {
            font-size: 24px;
            color: #202124;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .stat-info p {
            color: #5f6368;
            font-size: 13px;
        }

        .campus-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .campus-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 25px;
            transition: all 0.3s ease;
        }

        .campus-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .campus-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }

        .campus-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: linear-gradient(135deg, #1a73e8, #4285f4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
        }

        .campus-title h3 {
            font-size: 20px;
            color: #202124;
            margin-bottom: 5px;
        }

        .campus-title p {
            color: #5f6368;
            font-size: 13px;
        }

        .campus-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }

        .stat-box h4 {
            font-size: 28px;
            color: #1a73e8;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-box p {
            color: #5f6368;
            font-size: 12px;
        }

        .campus-actions {
            display: flex;
            gap: 10px;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-success {
            background: #e6f4ea;
            color: #0f9d58;
        }

        .badge-warning {
            background: #fef7e0;
            color: #f4b400;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #5f6368;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #dadce0;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .campus-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }

        @media (max-width: 768px) {
            .campus-grid {
                grid-template-columns: 1fr;
            }

            .stats-bar {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include '../sidebars/superadminSB.php'; ?>

    <script>
        // Set active menu item
        document.addEventListener('DOMContentLoaded', function() {
            // Remove active class from all menu items
            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Add active class to Campuses menu item
            document.querySelectorAll('.menu-item').forEach(item => {
                if (item.getAttribute('href') === 'campuses.php') {
                    item.classList.add('active');
                }
            });
        });
    </script>

    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-building"></i> Campus Management</h1>
            <a href="add-campus.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Campus
            </a>
        </div>

        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-icon blue">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo count($campuses); ?></h3>
                    <p>Total Campuses</p>
                </div>
            </div>

            <div class="stat-item">
                <div class="stat-icon green">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo array_sum(array_column($campuses, 'scholar_count')); ?></h3>
                    <p>Total Scholars</p>
                </div>
            </div>

            <div class="stat-item">
                <div class="stat-icon orange">
                    <i class="fas fa-award"></i>
                </div>
                <div class="stat-info">
                    <h3><?php 
                        $unique_scholarships = [];
                        foreach ($campuses as $campus) {
                            if ($campus['scholarship_count'] > 0) {
                                $unique_scholarships[] = $campus['id'];
                            }
                        }
                        echo count($unique_scholarships);
                    ?></h3>
                    <p>Active Campuses</p>
                </div>
            </div>
        </div>

        <?php if (count($campuses) > 0): ?>
            <div class="campus-grid">
                <?php foreach ($campuses as $campus): ?>
                    <div class="campus-card">
                        <div class="campus-header">
                            <div class="campus-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="campus-title">
                                <h3><?php echo htmlspecialchars($campus['campus_name']); ?></h3>
                                <?php if ($campus['scholar_count'] > 0): ?>
                                    <span class="badge badge-success">
                                        <i class="fas fa-check-circle"></i> Active
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-warning">
                                        <i class="fas fa-exclamation-circle"></i> No Scholars
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="campus-stats">
                            <div class="stat-box">
                                <h4><?php echo number_format($campus['scholar_count']); ?></h4>
                                <p>Total Scholars</p>
                            </div>
                            <div class="stat-box">
                                <h4><?php echo number_format($campus['scholarship_count']); ?></h4>
                                <p>Scholarship Types</p>
                            </div>
                        </div>

                        <div class="campus-actions">
                            <a href="edit-campus.php?id=<?php echo $campus['id']; ?>" class="btn btn-secondary btn-sm" style="flex: 1;">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <button onclick="deleteCampus(<?php echo $campus['id']; ?>, '<?php echo htmlspecialchars($campus['campus_name'], ENT_QUOTES); ?>', <?php echo $campus['scholar_count']; ?>)" class="btn btn-danger btn-sm" style="flex: 1;">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-building"></i>
                <h3>No Campuses Found</h3>
                <p>Start by adding your first campus.</p>
                <a href="add-campus.php" class="btn btn-primary" style="margin-top: 20px;">
                    <i class="fas fa-plus"></i> Add Campus
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function deleteCampus(id, name, scholarCount) {
            if (scholarCount > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Cannot Delete',
                    text: `Campus "${name}" has ${scholarCount} scholar(s). Please remove or reassign the scholars first.`,
                    confirmButtonColor: '#1a73e8'
                });
                return;
            }

            Swal.fire({
                title: 'Are you sure?',
                text: `You are about to delete "${name}"`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ea4335',
                cancelButtonColor: '#5f6368',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('delete-campus.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ id: id })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: data.message,
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while deleting.'
                        });
                    });
                }
            });
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
