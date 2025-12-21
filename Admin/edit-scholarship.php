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

// Get scholarship ID
$scholarship_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($scholarship_id <= 0) {
    header('Location: scholarships.php');
    exit;
}

// Get scholarship data
$stmt = $conn->prepare("SELECT * FROM scholarships WHERE id = ?");
$stmt->bind_param("i", $scholarship_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: scholarships.php');
    exit;
}

$scholarship = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scholarship_name = trim($_POST['scholarship_name']);
    $amount_per_sem = trim($_POST['amount_per_sem']);

    // Validation
    $errors = [];

    if (empty($scholarship_name)) {
        $errors[] = "Scholarship name is required";
    }

    if (empty($amount_per_sem) || !is_numeric($amount_per_sem) || $amount_per_sem < 0) {
        $errors[] = "Valid amount per semester is required";
    }

    // Check for duplicate scholarship name (excluding current scholarship)
    if (empty($errors)) {
        $check_stmt = $conn->prepare("SELECT id FROM scholarships WHERE scholarship_name = ? AND id != ?");
        $check_stmt->bind_param("si", $scholarship_name, $scholarship_id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $errors[] = "Scholarship name already exists";
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE scholarships SET scholarship_name = ?, amount_per_sem = ? WHERE id = ?");
        $stmt->bind_param("sdi", $scholarship_name, $amount_per_sem, $scholarship_id);
        
        if ($stmt->execute()) {
            // Log audit
            $user_id = $_SESSION['user_id'];
            $action = "Updated scholarship: $scholarship_name (ID: $scholarship_id)";
            $details = "Updated scholarship information";
            $audit_stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (?, ?, ?)");
            $audit_stmt->bind_param("iss", $user_id, $action, $details);
            $audit_stmt->execute();
            
            $success = true;
            $success_message = "Scholarship updated successfully!";
            
            // Refresh scholarship data
            $stmt = $conn->prepare("SELECT * FROM scholarships WHERE id = ?");
            $stmt->bind_param("i", $scholarship_id);
            $stmt->execute();
            $scholarship = $stmt->get_result()->fetch_assoc();
        } else {
            $errors[] = "Failed to update scholarship";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Scholarship - SKSU SDP</title>
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
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            color: #202124;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-group label .required {
            color: #ea4335;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #dadce0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #1a73e8;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #e6f4ea;
            color: #0f9d58;
            border-left: 4px solid #0f9d58;
        }

        .alert-error {
            background: #fce8e6;
            color: #ea4335;
            border-left: 4px solid #ea4335;
        }

        .alert ul {
            margin: 5px 0 0 20px;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include '../sidebars/adminSB.php'; ?>

    <script>
        // Set active menu item
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
                if (item.getAttribute('href') === 'scholarships.php') {
                    item.classList.add('active');
                }
            });
        });
    </script>

    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-edit"></i> Edit Scholarship</h1>
            <a href="scholarships.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Scholarships
            </a>
        </div>

        <div class="card">
            <?php if (isset($success) && $success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> Please fix the following errors:
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="scholarship_name">
                        Scholarship Name <span class="required">*</span>
                    </label>
                    <input type="text" id="scholarship_name" name="scholarship_name" 
                           value="<?php echo htmlspecialchars($scholarship['scholarship_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="amount_per_sem">
                        Amount Per Semester (₱) <span class="required">*</span>
                    </label>
                    <input type="number" id="amount_per_sem" name="amount_per_sem" 
                           value="<?php echo htmlspecialchars($scholarship['amount_per_sem']); ?>" 
                           step="0.01" min="0" required>
                </div>

                <div class="form-actions">
                    <a href="scholarships.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Scholarship
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (isset($success) && $success): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '<?php echo $success_message; ?>',
            showConfirmButton: false,
            timer: 1500
        }).then(() => {
            window.location.href = 'scholarships.php';
        });
    </script>
    <?php endif; ?>
</body>
</html>
<?php $conn->close(); ?>
