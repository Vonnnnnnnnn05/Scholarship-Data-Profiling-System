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

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search and filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$campus_filter = isset($_GET['campus']) ? $_GET['campus'] : '';
$scholarship_filter = isset($_GET['scholarship']) ? $_GET['scholarship'] : '';

// Build query - FILTER BY ENCODED_BY
$where = ["s.encoded_by = ?"];
$params = [$_SESSION['user_id']];
$types = 'i';

if (!empty($search)) {
    $where[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.course LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

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

// Get total count
$count_query = "SELECT COUNT(*) as total FROM scholars s $where_clause";
$stmt = $conn->prepare($count_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Get scholars
$query = "SELECT s.*, c.campus_name, sc.scholarship_name, u.name as encoder_name 
          FROM scholars s 
          LEFT JOIN campuses c ON s.campus_id = c.id 
          LEFT JOIN scholarships sc ON s.scholarship_id = sc.id 
          LEFT JOIN users u ON s.encoded_by = u.id 
          $where_clause
          ORDER BY s.created_at DESC 
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$scholars = $stmt->get_result();

// Get campuses for filter
$campuses = $conn->query("SELECT * FROM campuses ORDER BY campus_name");

// Get scholarships for filter
$scholarships = $conn->query("SELECT * FROM scholarships ORDER BY scholarship_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Scholars - SKSU SDP</title>
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

        .btn-success {
            background: #0f9d58;
            color: white;
        }

        .btn-success:hover {
            background: #0b7a44;
        }

        .btn-secondary {
            background: #5f6368;
            color: white;
        }

        .btn-secondary:hover {
            background: #4a4d52;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 25px;
        }

        .filters {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
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
            transition: border-color 0.3s ease;
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

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .badge-success {
            background: #e6f4ea;
            color: #0f9d58;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #dadce0;
            border-radius: 6px;
            text-decoration: none;
            color: #202124;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background: #f8f9fa;
            border-color: #1a73e8;
        }

        .pagination .active {
            background: #1a73e8;
            color: white;
            border-color: #1a73e8;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #5f6368;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #dadce0;
        }

        .stats-bar {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stat-item i {
            color: #1a73e8;
        }

        .stat-item span {
            font-size: 14px;
            color: #5f6368;
        }

        .stat-item strong {
            color: #202124;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .filters {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }

        @media (max-width: 768px) {
            .stats-bar {
                flex-direction: column;
                gap: 10px;
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
            
            // Add active class to Scholars menu item
            document.querySelectorAll('.menu-item').forEach(item => {
                if (item.getAttribute('href') === 'scholars.php') {
                    item.classList.add('active');
                }
            });
        });
    </script>

    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-user-graduate"></i> My Scholars</h1>
            <div style="display: flex; gap: 10px;">
                <a href="import-scholars.php" class="btn btn-success">
                    <i class="fas fa-file-import"></i> Import CSV
                </a>
                <a href="add-scholar.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Scholar
                </a>
            </div>
        </div>

        <div class="card">
            <div class="stats-bar">
                <div class="stat-item">
                    <i class="fas fa-users"></i>
                    <span>My Total Scholars: <strong><?php echo number_format($total_records); ?></strong></span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-filter"></i>
                    <span>Showing: <strong><?php echo number_format($scholars->num_rows); ?></strong></span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-file-alt"></i>
                    <span>Page: <strong><?php echo $page; ?> of <?php echo $total_pages; ?></strong></span>
                </div>
            </div>

            <form method="GET" action="">
                <div class="filters">
                    <div class="filter-group">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" placeholder="Search by name or course..." value="<?php echo htmlspecialchars($search); ?>">
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
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </div>
            </form>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Year Level</th>
                            <th>Course</th>
                            <th>Campus</th>
                            <th>Scholarship</th>
                            <th>Date Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($scholars->num_rows > 0): ?>
                            <?php while ($scholar = $scholars->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $scholar['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($scholar['last_name'] . ', ' . $scholar['first_name'] . ' ' . $scholar['middle_initial'] . '.'); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($scholar['year_level']); ?></td>
                                    <td><?php echo htmlspecialchars($scholar['course']); ?></td>
                                    <td><span class="badge badge-primary"><?php echo htmlspecialchars($scholar['campus_name']); ?></span></td>
                                    <td><?php echo htmlspecialchars($scholar['scholarship_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($scholar['created_at'])); ?></td>
                                    <td>
                                        <a href="edit-scholar.php?id=<?php echo $scholar['id']; ?>" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <i class="fas fa-user-graduate"></i>
                                        <h3>No Scholars Found</h3>
                                        <p>You haven't encoded any scholars yet or no scholars match your filters.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&campus=<?php echo $campus_filter; ?>&scholarship=<?php echo $scholarship_filter; ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&campus=<?php echo $campus_filter; ?>&scholarship=<?php echo $scholarship_filter; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&campus=<?php echo $campus_filter; ?>&scholarship=<?php echo $scholarship_filter; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
