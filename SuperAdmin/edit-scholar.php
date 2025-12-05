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

// Get scholar ID
$scholar_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($scholar_id <= 0) {
    header('Location: scholars.php');
    exit;
}

// Get scholar data
$stmt = $conn->prepare("SELECT * FROM scholars WHERE id = ?");
$stmt->bind_param("i", $scholar_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: scholars.php');
    exit;
}

$scholar = $result->fetch_assoc();

// Get campuses
$campuses = $conn->query("SELECT * FROM campuses ORDER BY campus_name");

// Get scholarships
$scholarships = $conn->query("SELECT * FROM scholarships ORDER BY scholarship_name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $middle_initial = trim($_POST['middle_initial']);
    $year_level = trim($_POST['year_level']);
    $course = trim($_POST['course']);
    $campus_id = $_POST['campus_id'];
    $scholarship_id = $_POST['scholarship_id'];

    // Validation
    $errors = [];

    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($year_level)) $errors[] = "Year level is required";
    if (empty($course)) $errors[] = "Course is required";
    if (empty($campus_id)) $errors[] = "Campus is required";
    if (empty($scholarship_id)) $errors[] = "Scholarship is required";

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE scholars SET first_name = ?, last_name = ?, middle_initial = ?, year_level = ?, course = ?, campus_id = ?, scholarship_id = ? WHERE id = ?");
        $stmt->bind_param("sssssiii", $first_name, $last_name, $middle_initial, $year_level, $course, $campus_id, $scholarship_id, $scholar_id);
        
        if ($stmt->execute()) {
            // Log audit
            $user_id = $_SESSION['user_id'];
            $action = "Updated scholar: $last_name, $first_name (ID: $scholar_id)";
            $audit_stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
            $audit_stmt->bind_param("is", $user_id, $action);
            $audit_stmt->execute();
            
            $success = true;
            $success_message = "Scholar updated successfully!";
            
            // Refresh scholar data
            $stmt = $conn->prepare("SELECT * FROM scholars WHERE id = ?");
            $stmt->bind_param("i", $scholar_id);
            $stmt->execute();
            $scholar = $stmt->get_result()->fetch_assoc();
        } else {
            $errors[] = "Failed to update scholar";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Scholar - SKSU SDP</title>
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
            max-width: 1000px;
            margin: 0 auto;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-size: 14px;
            color: #202124;
            font-weight: 500;
        }

        .form-group label .required {
            color: #ea4335;
        }

        .form-group input,
        .form-group select {
            padding: 12px;
            border: 1px solid #dadce0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #1a73e8;
        }

        .form-group input:disabled,
        .form-group select:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
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
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
                width: 100%;
                justify-content: center;
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
            <h1><i class="fas fa-edit"></i> Edit Scholar</h1>
            <a href="scholars.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Scholars
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

            <form method="POST" action="" id="scholarForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="first_name">First Name <span class="required">*</span></label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($scholar['first_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($scholar['last_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="middle_initial">Middle Initial</label>
                        <input type="text" id="middle_initial" name="middle_initial" maxlength="1" value="<?php echo htmlspecialchars($scholar['middle_initial']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="year_level">Year Level <span class="required">*</span></label>
                        <select id="year_level" name="year_level" required>
                            <option value="">Select Year Level</option>
                            <option value="1st Year" <?php echo $scholar['year_level'] == '1st Year' ? 'selected' : ''; ?>>1st Year</option>
                            <option value="2nd Year" <?php echo $scholar['year_level'] == '2nd Year' ? 'selected' : ''; ?>>2nd Year</option>
                            <option value="3rd Year" <?php echo $scholar['year_level'] == '3rd Year' ? 'selected' : ''; ?>>3rd Year</option>
                            <option value="4th Year" <?php echo $scholar['year_level'] == '4th Year' ? 'selected' : ''; ?>>4th Year</option>
                            <option value="5th Year" <?php echo $scholar['year_level'] == '5th Year' ? 'selected' : ''; ?>>5th Year</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="campus_id">Campus <span class="required">*</span></label>
                        <select id="campus_id" name="campus_id" required>
                            <option value="">Select Campus</option>
                            <?php while ($campus = $campuses->fetch_assoc()): ?>
                                <option value="<?php echo $campus['id']; ?>" <?php echo $scholar['campus_id'] == $campus['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($campus['campus_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="course">Course <span class="required">*</span></label>
                        <select id="course" name="course" required>
                            <option value="">Select Course</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="scholarship_id">Scholarship Program <span class="required">*</span></label>
                        <select id="scholarship_id" name="scholarship_id" required>
                            <option value="">Select Scholarship</option>
                            <?php while ($scholarship = $scholarships->fetch_assoc()): ?>
                                <option value="<?php echo $scholarship['id']; ?>" <?php echo $scholar['scholarship_id'] == $scholarship['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($scholarship['scholarship_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="scholars.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Scholar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const coursesByCampus = {
            'ACCESS': [
                'Bachelor of Science in Nursing (BSN)',
                'Bachelor of Science in Midwifery (BSM)',
                'Bachelor of Science in Medical Technology',
                'Bachelor Science in Criminology (BSCrim)',
                'Bachelor Science in Industrial Security Management (BSISM)',
                'Bachelor of Science in Agricultural Technology (BAT)',
                'Bachelor in Elementary Education (BEED)',
                'Bachelor in Secondary Education major in English (BSEd-English)',
                'Bachelor in Secondary Education major in Filipino (BSEd-Filipino)',
                'Bachelor in Secondary Education major in Science (BSEd-Science)',
                'Bachelor in Secondary Education major in Social Studies (BSEd-Social Studies)',
                'Bachelor in Secondary Education major in Mathematics (BSEd-Mathematics)',
                'Bachelor of Physical Education (BPEd)',
                'Bachelor of Laws',
            ],
            'ISULAN': [
                'Bachelor of Science in Computer Science (BSCS)',
                'Bachelor of Science in Information Technology (BSIT)',
                'Bachelor of Science in Information Systems (BSIS)',
                'Bachelor in Technical-Vocational Teacher Education (BTVTEd)',
                'Bachelor of Science in Industrial Technology (BS Indus. Tech.)',
                'Bachelor of Science in Civil Engineering (BSCE)',
                'Bachelor of Science in Computer Engineering (BSCpE)',
                'Bachelor of Science in Electronics Engineering (BSECE)'
            ],
            'TACURONG': [
                'Bachelor of Arts in Economics (AB Econ)',
                'Bachelor of Science in Entrepreneurship (BSEntre)',
                'Bachelor of Arts in Political Science (AB PolSci)',
                'Bachelor of Science in Biology (BSBio)',
                'Bachelor of Science in Accountancy (BSA)',
                'Bachelor of Science in Management Accounting (BS MA)',
                'Bachelor of Science in Hospitality Management (BSHM)',
                'Bachelor of Science in Accounting Information System (BSAIS)',
                'Bachelor of Science in Environmental Science (BS Environmental Science)',
                'Bachelor of Science in Tourism Management (BS TM)'
            ],
            'KALAMANSIG': [
                'Bachelor of Science in Fisheries (BSFi)',
                'Bachelor of Science in Secondary Education major in Filipino',
                'Bachelor of Science in Secondary Education major in English',
                'Bachelor of Science in Secondary Education major in Science',
                'Bachelor in Elementary Education (BEED)',
                'Bachelor in Marine Biology',
                'Bachelor of Science in Criminology (BSCrim)',
                'Diploma in Teaching (DIT)',
                'Bachelor of Science in Information Technology (BSIT)'
            ],
            'LUTAYAN': [
                'Bachelor in Agricultural Technology (BAT)',
                'Bachelor of Science in Agriculture (BSA)',
                'Bachelor in Elementary Education (BEED)'
            ],
            'PALIMBANG': [
                'Bachelor in Elementary Education (BEED)',
                'Bachelor of Science in Agribusiness (BS Agribus)'
            ],
            'BAGUMBAYAN': [
                'Bachelor of Science in Agribusiness (BS Agribus)',
                'Bachelor of Technology and Livelihood Education (BTLED)'
            ]
        };

        const campusSelect = document.getElementById('campus_id');
        const courseSelect = document.getElementById('course');
        const currentCourse = "<?php echo htmlspecialchars($scholar['course']); ?>";

        function populateCourses() {
            const selectedCampusId = campusSelect.value;
            const selectedCampusName = campusSelect.options[campusSelect.selectedIndex].text;
            
            courseSelect.innerHTML = '<option value="">Select Course</option>';
            
            if (selectedCampusId && coursesByCampus[selectedCampusName]) {
                coursesByCampus[selectedCampusName].forEach(course => {
                    const option = document.createElement('option');
                    option.value = course;
                    option.textContent = course;
                    if (course === currentCourse) {
                        option.selected = true;
                    }
                    courseSelect.appendChild(option);
                });
                courseSelect.disabled = false;
            } else {
                courseSelect.disabled = true;
            }
        }

        campusSelect.addEventListener('change', populateCourses);
        
        // Initialize courses on page load
        populateCourses();

        <?php if (isset($success) && $success): ?>
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Scholar updated successfully',
            showConfirmButton: false,
            timer: 1500
        });
        <?php endif; ?>
    </script>
</body>
</html>
<?php $conn->close(); ?>
