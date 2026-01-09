<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

include '../conn.php';

$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// Get current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$profile_photo = isset($user['profile_photo']) ? $user['profile_photo'] : '';

// Handle photo upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['photo_upload'])) {
    if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Please select a photo to upload';
    } elseif ($_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Failed to upload photo';
    } else {
        $column_check = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_photo'");
        if (!$column_check || $column_check->num_rows === 0) {
            $add_column = $conn->query("ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) DEFAULT NULL");
            if (!$add_column) {
                $errors[] = 'Profile photo feature is not available. Please add profile_photo column to users table.';
            }
        }

        if (empty($errors)) {
            $max_size = 2 * 1024 * 1024;
            if ($_FILES['profile_photo']['size'] > $max_size) {
                $errors[] = 'Photo must be 2MB or smaller';
            } else {
                $tmp_name = $_FILES['profile_photo']['tmp_name'];
                $mime = '';
                if (class_exists('finfo')) {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($tmp_name);
                }
                if ($mime === '' && function_exists('mime_content_type')) {
                    $mime = mime_content_type($tmp_name);
                }
                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif'
                ];

                if (!isset($allowed[$mime])) {
                    $errors[] = 'Only JPG, PNG, or GIF images are allowed';
                } else {
                    $upload_dir = dirname(__DIR__) . '/images/profile-photos';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $filename = 'user_' . $user_id . '_' . time() . '.' . $allowed[$mime];
                    $relative_path = 'images/profile-photos/' . $filename;
                    $target_path = $upload_dir . '/' . $filename;

                    if (move_uploaded_file($tmp_name, $target_path)) {
                        $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                        $stmt->bind_param("si", $relative_path, $user_id);
                        if ($stmt->execute()) {
                            if (!empty($profile_photo)) {
                                $old_path = dirname(__DIR__) . '/' . $profile_photo;
                                if (is_file($old_path)) {
                                    unlink($old_path);
                                }
                            }

                            $profile_photo = $relative_path;
                            $action = "Updated profile photo";
                            $log_stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
                            $log_stmt->bind_param("is", $user_id, $action);
                            $log_stmt->execute();

                            $success = 'Profile photo updated successfully!';
                        } else {
                            $errors[] = 'Failed to save profile photo';
                        }
                    } else {
                        $errors[] = 'Failed to save uploaded photo';
                    }
                }
            }
        }
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['photo_upload'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validation
    if (empty($name)) {
        $errors[] = 'Name is required';
    }

    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    // Check if email exists (excluding current user)
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = 'Email already exists';
    }

    // Password change validation
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = 'Current password is required to change password';
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = 'Current password is incorrect';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'New password must be at least 6 characters';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match';
        }
    }

    if (empty($errors)) {
        if (!empty($new_password)) {
            // Update with new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $email, $hashed_password, $user_id);
        } else {
            // Update without changing password
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $email, $user_id);
        }

        if ($stmt->execute()) {
            // Update session name
            $_SESSION['name'] = $name;

            // Log the action
            $action = "Updated profile";
            $log_stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
            $log_stmt->bind_param("is", $user_id, $action);
            $log_stmt->execute();

            $success = 'Profile updated successfully!';
            
            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $profile_photo = isset($user['profile_photo']) ? $user['profile_photo'] : $profile_photo;
        } else {
            $errors[] = 'Failed to update profile';
        }
    }
}

// Get user's recent activity
$activity_query = "SELECT action, created_at FROM audit_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
$stmt = $conn->prepare($activity_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$activities = $stmt->get_result();

// Get user's statistics
$stats = [];
if ($_SESSION['role'] == 'encoder' || $_SESSION['role'] == 'admin' || $_SESSION['role'] == 'super_admin') {
    $stats_query = "SELECT COUNT(*) as count FROM scholars WHERE encoded_by = ?";
    $stmt = $conn->prepare($stats_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['scholars_encoded'] = $stmt->get_result()->fetch_assoc()['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - SKSU SDP</title>
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
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 28px;
            color: #202124;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        .profile-header {
            text-align: center;
            padding: 20px 0;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 48px;
            color: white;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .profile-header h2 {
            font-size: 24px;
            color: #202124;
            margin-bottom: 5px;
        }

        .profile-header p {
            color: #5f6368;
            font-size: 14px;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            margin-top: 10px;
        }

        .badge-super {
            background: #fce8e6;
            color: #ea4335;
        }

        .badge-admin {
            background: #e8f0fe;
            color: #1a73e8;
        }

        .badge-encoder {
            background: #e6f4ea;
            color: #0f9d58;
        }

        .profile-stats {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e0e0e0;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .stat-item:last-child {
            border-bottom: none;
        }

        .stat-label {
            color: #5f6368;
            font-size: 14px;
        }

        .stat-value {
            color: #202124;
            font-weight: 600;
            font-size: 14px;
        }

        .card h3 {
            font-size: 18px;
            color: #202124;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #202124;
            font-weight: 500;
            font-size: 14px;
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

        .form-group small {
            display: block;
            margin-top: 5px;
            color: #5f6368;
            font-size: 12px;
        }

        .btn {
            padding: 12px 24px;
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

        .btn-sm {
            padding: 8px 14px;
            font-size: 13px;
        }

        .btn-primary {
            background: #1a73e8;
            color: white;
        }

        .btn-primary:hover {
            background: #1557b0;
        }

        .activity-list {
            list-style: none;
        }

        .activity-item {
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            gap: 15px;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            background: #e8f0fe;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a73e8;
            flex-shrink: 0;
        }

        .activity-details {
            flex: 1;
        }

        .activity-action {
            font-size: 14px;
            color: #202124;
            margin-bottom: 5px;
        }

        .activity-time {
            font-size: 12px;
            color: #5f6368;
        }

        .photo-form {
            margin-top: 10px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
        }

        .photo-input {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            border: 0;
        }

        .photo-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 6px;
            background: #f1f3f4;
            color: #202124;
            font-size: 13px;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .photo-label:hover {
            background: #e8eaed;
        }

        .photo-filename {
            font-size: 12px;
            color: #5f6368;
        }

        .photo-hint {
            font-size: 12px;
            color: #5f6368;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .profile-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php 
    if ($_SESSION['role'] == 'super_admin') {
        include '../sidebars/superadminSB.php';
    } elseif ($_SESSION['role'] == 'admin') {
        include '../sidebars/adminSB.php';
    } else {
        include '../sidebars/encoderSB.php';
    }
    ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
            });
            
            document.querySelectorAll('.menu-item').forEach(item => {
                if (item.getAttribute('href') === 'profile.php') {
                    item.classList.add('active');
                }
            });
        });

        <?php if ($success): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '<?php echo $success; ?>',
                showConfirmButton: false,
                timer: 1500
            });
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            Swal.fire({
                icon: 'error',
                title: 'Validation Errors',
                html: '<?php echo implode("<br>", $errors); ?>'
            });
        <?php endif; ?>

        const photoInput = document.getElementById('profile_photo');
        const photoFilename = document.getElementById('photo_filename');
        if (photoInput && photoFilename) {
            photoInput.addEventListener('change', () => {
                photoFilename.textContent = photoInput.files.length
                    ? photoInput.files[0].name
                    : 'No file selected';
            });
        }
    </script>

    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-user-circle"></i> My Profile</h1>
        </div>

        <div class="profile-grid">
            <div class="card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php if (!empty($profile_photo)): ?>
                            <img src="../<?php echo htmlspecialchars($profile_photo); ?>" alt="Profile Photo">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <form class="photo-form" method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="photo_upload" value="1">
                        <input class="photo-input" type="file" name="profile_photo" id="profile_photo" accept="image/*">
                        <label class="photo-label" for="profile_photo">
                            <i class="fas fa-image"></i> Choose Photo
                        </label>
                        <span class="photo-filename" id="photo_filename">No file selected</span>
                        <span class="photo-hint">Max size 2MB (JPG/PNG/GIF)</span>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-upload"></i> Upload Photo
                        </button>
                    </form>
                    <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                    <?php if ($user['role'] == 'super_admin'): ?>
                        <span class="badge badge-super">Super Admin</span>
                    <?php elseif ($user['role'] == 'admin'): ?>
                        <span class="badge badge-admin">Admin</span>
                    <?php else: ?>
                        <span class="badge badge-encoder">Encoder</span>
                    <?php endif; ?>
                </div>

                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-label">Member Since</span>
                        <span class="stat-value"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                    <?php if (isset($stats['scholars_encoded'])): ?>
                        <div class="stat-item">
                            <span class="stat-label">Scholars Encoded</span>
                            <span class="stat-value"><?php echo number_format($stats['scholars_encoded']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <div class="card" style="margin-bottom: 20px;">
                    <h3><i class="fas fa-user-edit"></i> Update Profile</h3>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="name">Full Name *</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <hr style="margin: 30px 0; border: none; border-top: 1px solid #e0e0e0;">

                        <h3 style="margin-bottom: 20px;"><i class="fas fa-key"></i> Change Password</h3>

                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password">
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password">
                            <small>Leave blank to keep current password</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </form>
                </div>

                <div class="card">
                    <h3><i class="fas fa-history"></i> Recent Activity</h3>
                    <ul class="activity-list">
                        <?php if ($activities->num_rows > 0): ?>
                            <?php while ($activity = $activities->fetch_assoc()): ?>
                                <li class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-circle"></i>
                                    </div>
                                    <div class="activity-details">
                                        <div class="activity-action"><?php echo htmlspecialchars($activity['action']); ?></div>
                                        <div class="activity-time"><?php echo date('M d, Y h:i A', strtotime($activity['created_at'])); ?></div>
                                    </div>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li class="activity-item">
                                <div class="activity-details" style="text-align: center; color: #5f6368;">
                                    No recent activity
                                </div>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
