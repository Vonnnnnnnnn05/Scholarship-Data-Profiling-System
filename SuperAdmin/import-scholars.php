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

$success_count = 0;
$error_count = 0;
$errors = [];
$success_message = '';

// Handle CSV upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    // Validate file
    if ($file['error'] === UPLOAD_ERR_OK) {
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($file_ext !== 'csv') {
            $errors[] = "Please upload a CSV file";
        } else {
            // Open and read CSV
            if (($handle = fopen($file['tmp_name'], 'r')) !== FALSE) {
                // Skip header row
                $header = fgetcsv($handle);
                
                $row_number = 1;
                
                while (($data = fgetcsv($handle)) !== FALSE) {
                    $row_number++;
                    
                    // Skip empty rows
                    if (empty(array_filter($data))) {
                        continue;
                    }
                    
                    // Expected format: First Name, Last Name, Middle Initial, Year Level, Course, Campus, Scholarship
                    if (count($data) < 7) {
                        $errors[] = "Row $row_number: Incomplete data";
                        $error_count++;
                        continue;
                    }
                    
                    $first_name = trim($data[0]);
                    $last_name = trim($data[1]);
                    $middle_initial = trim($data[2]);
                    $year_level = trim($data[3]);
                    $course = trim($data[4]);
                    $campus_name = trim($data[5]);
                    $scholarship_name = trim($data[6]);
                    
                    // Validate required fields
                    if (empty($first_name) || empty($last_name) || empty($year_level) || empty($course) || empty($campus_name) || empty($scholarship_name)) {
                        $errors[] = "Row $row_number: Missing required fields";
                        $error_count++;
                        continue;
                    }
                    
                    // Get campus ID
                    $campus_stmt = $conn->prepare("SELECT id FROM campuses WHERE campus_name = ?");
                    $campus_stmt->bind_param("s", $campus_name);
                    $campus_stmt->execute();
                    $campus_result = $campus_stmt->get_result();
                    
                    if ($campus_result->num_rows === 0) {
                        $errors[] = "Row $row_number: Campus '$campus_name' not found";
                        $error_count++;
                        continue;
                    }
                    
                    $campus_id = $campus_result->fetch_assoc()['id'];
                    
                    // Get scholarship ID
                    $scholarship_stmt = $conn->prepare("SELECT id FROM scholarships WHERE scholarship_name = ?");
                    $scholarship_stmt->bind_param("s", $scholarship_name);
                    $scholarship_stmt->execute();
                    $scholarship_result = $scholarship_stmt->get_result();
                    
                    if ($scholarship_result->num_rows === 0) {
                        $errors[] = "Row $row_number: Scholarship '$scholarship_name' not found";
                        $error_count++;
                        continue;
                    }
                    
                    $scholarship_id = $scholarship_result->fetch_assoc()['id'];
                    
                    // Insert scholar
                    $encoded_by = $_SESSION['user_id'];
                    $insert_stmt = $conn->prepare("INSERT INTO scholars (first_name, last_name, middle_initial, year_level, course, campus_id, scholarship_id, encoded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $insert_stmt->bind_param("sssssiii", $first_name, $last_name, $middle_initial, $year_level, $course, $campus_id, $scholarship_id, $encoded_by);
                    
                    if ($insert_stmt->execute()) {
                        $success_count++;
                    } else {
                        $errors[] = "Row $row_number: Failed to insert - " . $conn->error;
                        $error_count++;
                    }
                }
                
                fclose($handle);
                
                // Log audit
                if ($success_count > 0) {
                    $user_id = $_SESSION['user_id'];
                    $action = "Imported $success_count scholars via CSV";
                    $audit_stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
                    $audit_stmt->bind_param("is", $user_id, $action);
                    $audit_stmt->execute();
                }
                
                $success_message = "$success_count scholars imported successfully!";
            } else {
                $errors[] = "Failed to open CSV file";
            }
        }
    } else {
        $errors[] = "Error uploading file";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Scholars - SKSU SDP</title>
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

        .btn-success {
            background: #0f9d58;
            color: white;
        }

        .btn-success:hover {
            background: #0b7a44;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 20px;
        }

        .card h2 {
            font-size: 20px;
            color: #202124;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .upload-section {
            border: 2px dashed #dadce0;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .upload-section:hover {
            border-color: #1a73e8;
            background: #f8f9fa;
        }

        .upload-icon {
            font-size: 64px;
            color: #1a73e8;
            margin-bottom: 20px;
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            margin: 20px 0;
        }

        input[type="file"] {
            display: none;
        }

        .file-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: #1a73e8;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .file-label:hover {
            background: #1557b0;
        }

        .file-name {
            display: block;
            margin-top: 15px;
            color: #5f6368;
            font-size: 14px;
        }

        .instructions {
            background: #e8f0fe;
            border-left: 4px solid #1a73e8;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .instructions h3 {
            color: #1a73e8;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .instructions ol {
            margin-left: 20px;
            color: #202124;
        }

        .instructions li {
            margin-bottom: 8px;
            line-height: 1.6;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
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
            margin: 10px 0 0 20px;
        }

        .alert li {
            margin-bottom: 5px;
        }

        .sample-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 13px;
        }

        .sample-table th,
        .sample-table td {
            border: 1px solid #dadce0;
            padding: 10px;
            text-align: left;
        }

        .sample-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #5f6368;
        }

        .sample-table td {
            color: #202124;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            text-align: center;
        }

        .stat-box .number {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-box .number.success {
            color: #0f9d58;
        }

        .stat-box .number.error {
            color: #ea4335;
        }

        .stat-box .label {
            color: #5f6368;
            font-size: 14px;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
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
            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
            });
            
            document.querySelectorAll('.menu-item').forEach(item => {
                if (item.getAttribute('href') === 'scholars.php') {
                    item.classList.add('active');
                }
            });
        });

        // Display selected file name
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('csv_file');
            const fileName = document.getElementById('file-name');
            
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        fileName.textContent = 'Selected: ' + this.files[0].name;
                    } else {
                        fileName.textContent = '';
                    }
                });
            }
        });
    </script>

    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-file-import"></i> Import Scholars from CSV</h1>
            <a href="scholars.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Scholars
            </a>
        </div>

        <?php if ($success_count > 0 || $error_count > 0): ?>
            <div class="card">
                <h2><i class="fas fa-chart-bar"></i> Import Results</h2>
                <div class="stats">
                    <div class="stat-box">
                        <div class="number success"><?php echo $success_count; ?></div>
                        <div class="label">Successful Imports</div>
                    </div>
                    <div class="stat-box">
                        <div class="number error"><?php echo $error_count; ?></div>
                        <div class="label">Failed Imports</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <strong>Errors occurred during import:</strong>
                <ul>
                    <?php foreach (array_slice($errors, 0, 10) as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                    <?php if (count($errors) > 10): ?>
                        <li><em>... and <?php echo count($errors) - 10; ?> more errors</em></li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2><i class="fas fa-info-circle"></i> Instructions</h2>
            <div class="instructions">
                <h3>CSV File Format Requirements:</h3>
                <ol>
                    <li>The CSV file must have a header row with column names</li>
                    <li>Required columns (in this exact order):
                        <ul>
                            <li><strong>First Name</strong></li>
                            <li><strong>Last Name</strong></li>
                            <li><strong>Middle Initial</strong> (can be empty)</li>
                            <li><strong>Year Level</strong></li>
                            <li><strong>Course</strong></li>
                            <li><strong>Campus</strong> (must match existing campus name exactly)</li>
                            <li><strong>Scholarship</strong> (must match existing scholarship name exactly)</li>
                        </ul>
                    </li>
                    <li>Campus and Scholarship names must already exist in the system</li>
                    <li>Save your file with .csv extension</li>
                </ol>

                <h3 style="margin-top: 20px;">Sample CSV Format:</h3>
                <table class="sample-table">
                    <thead>
                        <tr>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Middle Initial</th>
                            <th>Year Level</th>
                            <th>Course</th>
                            <th>Campus</th>
                            <th>Scholarship</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Juan</td>
                            <td>Dela Cruz</td>
                            <td>P</td>
                            <td>3rd Year</td>
                            <td>BS Computer Science</td>
                            <td>ACCESS</td>
                            <td>CHED FULL SSP</td>
                        </tr>
                        <tr>
                            <td>Maria</td>
                            <td>Santos</td>
                            <td>L</td>
                            <td>2nd Year</td>
                            <td>BS Information Technology</td>
                            <td>TACURONG</td>
                            <td>SEN. IMEE MARCOS</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 30px; text-align: center;">
                <a href="sample-scholars.csv" class="btn btn-success" download>
                    <i class="fas fa-download"></i> Download Sample CSV Template
                </a>
            </div>
        </div>

        <div class="card">
            <h2><i class="fas fa-upload"></i> Upload CSV File</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="upload-section">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <h3>Select CSV File to Import</h3>
                    <p style="color: #5f6368; margin: 10px 0;">Choose a CSV file containing scholar information</p>
                    
                    <div class="file-input-wrapper">
                        <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                        <label for="csv_file" class="file-label">
                            <i class="fas fa-folder-open"></i> Choose File
                        </label>
                        <span id="file-name" class="file-name"></span>
                    </div>

                    <div style="margin-top: 30px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-file-import"></i> Import Scholars
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($success_count > 0): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Import Completed!',
            text: '<?php echo $success_count; ?> scholars imported successfully',
            showConfirmButton: true
        });
    </script>
    <?php endif; ?>
</body>
</html>
<?php $conn->close(); ?>
