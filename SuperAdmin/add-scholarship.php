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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scholarship_name = trim($_POST['scholarship_name']);
    $amount_per_sem = trim($_POST['amount_per_sem']);

    // Validation
    $errors = [];

    if (empty($scholarship_name)) {
        $errors[] = "Scholarship name is required";
    }
    
    if (empty($amount_per_sem)) {
        $errors[] = "Amount per semester is required";
    } elseif (!is_numeric($amount_per_sem) || $amount_per_sem < 0) {
        $errors[] = "Amount must be a valid positive number";
    }

    // Check for duplicate name
    if (!empty($scholarship_name)) {
        $stmt = $conn->prepare("SELECT id FROM scholarships WHERE scholarship_name = ?");
        $stmt->bind_param("s", $scholarship_name);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Scholarship name already exists";
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO scholarships (scholarship_name, amount_per_sem) VALUES (?, ?)");
        $stmt->bind_param("sd", $scholarship_name, $amount_per_sem);
        
        if ($stmt->execute()) {
            // Log audit
            $scholarship_id = $stmt->insert_id;
            $user_id = $_SESSION['user_id'];
            $action = "Added new scholarship: $scholarship_name (ID: $scholarship_id)";
            $audit_stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
            $audit_stmt->bind_param("is", $user_id, $action);
            $audit_stmt->execute();
            
            $success = true;
            $success_message = "Scholarship added successfully!";
            
            // Clear form
            $scholarship_name = '';
            $amount_per_sem = '';
        } else {
            $errors[] = "Failed to add scholarship";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Scholarship - SKSU SDP</title>
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
            max-width: 600px;
            margin: 0 auto;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 20px;
        }

        .form-group label {
            font-size: 14px;
            color: #202124;
            font-weight: 500;
        }

        .form-group label .required {
            color: #ea4335;
        }

        .form-group input {
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

        .form-group small {
            color: #5f6368;
            font-size: 12px;
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
                width: 100%;
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
            <h1><i class="fas fa-plus-circle"></i> Add New Scholarship</h1>
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
                    <label for="scholarship_name">Scholarship Name <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="scholarship_name" 
                        name="scholarship_name" 
                        value="<?php echo isset($scholarship_name) ? htmlspecialchars($scholarship_name) : ''; ?>"
                        required
                        placeholder="e.g., CHED Scholarship, DOST-SEI Merit Scholarship">
                </div>

                <div class="form-group">
                    <label for="amount_per_sem">Amount per Semester <span class="required">*</span></label>
                    <input 
                        type="number" 
                        id="amount_per_sem" 
                        name="amount_per_sem" 
                        value="<?php echo isset($amount_per_sem) ? htmlspecialchars($amount_per_sem) : ''; ?>"
                        step="0.01" 
                        min="0" 
                        required
                        placeholder="0.00">
                    <small>Enter the scholarship amount in Philippine Peso (₱)</small>
                </div>

                <div class="form-actions">
                    <a href="scholarships.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Scholarship
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        <?php if (isset($success) && $success): ?>
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Scholarship added successfully',
            showConfirmButton: false,
            timer: 1500
        });
        <?php endif; ?>
    </script>
</body>
</html>
<?php $conn->close(); ?>
