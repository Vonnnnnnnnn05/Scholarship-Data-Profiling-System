<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

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
$conn->set_charset("utf8mb4");

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$where = '';
$params = [];
$types = '';

if (!empty($search)) {
    $where = "WHERE scholarship_name LIKE ?";
    $search_param = "%$search%";
    $params[] = $search_param;
    $types .= 's';
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM scholarships $where";
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Get scholarships
$query = "SELECT s.*, COUNT(sc.id) as scholar_count 
          FROM scholarships s 
          LEFT JOIN scholars sc ON s.id = sc.scholarship_id 
          $where
          GROUP BY s.id
          ORDER BY s.scholarship_name ASC 
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$scholarships = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholarships - SKSU SDP</title>
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

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 25px;
        }

        .search-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .search-bar input {
            flex: 1;
            padding: 10px 12px;
            border: 1px solid #dadce0;
            border-radius: 6px;
            font-size: 14px;
        }

        .search-bar input:focus {
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

        .badge-success {
            background: #e6f4ea;
            color: #0f9d58;
        }

        .badge-warning {
            background: #fef7e0;
            color: #f4b400;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
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

        .amount {
            font-weight: 600;
            color: #0f9d58;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }

            .stats-bar {
                flex-direction: column;
                gap: 10px;
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
            
            // Add active class to Scholarships menu item
            document.querySelectorAll('.menu-item').forEach(item => {
                if (item.getAttribute('href') === 'scholarships.php') {
                    item.classList.add('active');
                }
            });
        });
    </script>

    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-award"></i> Scholarship Programs</h1>
            <a href="add-scholarship.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Scholarship
            </a>
        </div>

        <div class="card">
            <div class="stats-bar">
                <div class="stat-item">
                    <i class="fas fa-award"></i>
                    <span>Total Programs: <strong><?php echo number_format($total_records); ?></strong></span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-filter"></i>
                    <span>Showing: <strong><?php echo number_format($scholarships->num_rows); ?></strong></span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-file-alt"></i>
                    <span>Page: <strong><?php echo $page; ?> of <?php echo $total_pages; ?></strong></span>
                </div>
            </div>

            <form method="GET" action="">
                <div class="search-bar">
                    <input type="text" name="search" placeholder="Search scholarship programs..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Scholarship Name</th>
                            <th>Amount per Semester</th>
                            <th>Scholars Enrolled</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($scholarships->num_rows > 0): ?>
                            <?php while ($scholarship = $scholarships->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $scholarship['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($scholarship['scholarship_name']); ?></strong></td>
                                    <td>
                                        <span class="amount">₱<?php echo number_format($scholarship['amount_per_sem'], 2); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-success">
                                            <i class="fas fa-user-graduate"></i> <?php echo $scholarship['scholar_count']; ?> Scholars
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($scholarship['scholar_count'] > 0): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">No Scholars</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit-scholarship.php?id=<?php echo $scholarship['id']; ?>" class="btn btn-secondary btn-sm">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <button type="button" onclick="viewScholars(<?php echo $scholarship['id']; ?>, '<?php echo htmlspecialchars($scholarship['scholarship_name'], ENT_QUOTES); ?>')" class="btn btn-primary btn-sm" title="View Scholars" aria-label="View Scholars">
                                                <i class="fas fa-users"></i> View
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fas fa-award"></i>
                                        <h3>No Scholarship Programs Found</h3>
                                        <p>Try adjusting your search or add a new scholarship program.</p>
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
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Active menu highlighting
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = 'scholarships.php';
            const menuItems = document.querySelectorAll('.menu-item');
            
            menuItems.forEach(item => {
                item.classList.remove('active');
                if (item.getAttribute('href') === currentPage) {
                    item.classList.add('active');
                }
            });
        });

        function escapeHtml(value) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            };
            return String(value).replace(/[&<>"']/g, (char) => map[char]);
        }

        function viewScholars(id, name) {
            fetch(`../get-scholarship-scholars.php?scholarship_id=${encodeURIComponent(id)}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Unable to load scholars.'
                        });
                        return;
                    }

                    if (!data.names || data.names.length === 0) {
                        Swal.fire({
                            icon: 'info',
                            title: name,
                            text: 'No scholars enrolled.'
                        });
                        return;
                    }

                    const listItems = data.names
                        .map((scholar) => `<li>${escapeHtml(scholar)}</li>`)
                        .join('');

                    Swal.fire({
                        title: name,
                        html: `<ul style="text-align:left; padding-left:18px; margin:0;">${listItems}</ul>`,
                        confirmButtonColor: '#1a73e8'
                    });
                })
                .catch(() => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while loading scholars.'
                    });
                });
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
