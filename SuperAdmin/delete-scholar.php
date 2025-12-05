<?php
session_start();

// Check if user is logged in and is super admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SESSION['role'] !== 'super_admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include '../conn.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? (int)$input['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid scholar ID']);
    exit;
}

// Check if scholar exists
$stmt = $conn->prepare("SELECT * FROM scholars WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Scholar not found']);
    exit;
}

// Delete scholar
$stmt = $conn->prepare("DELETE FROM scholars WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // Log audit
    $user_id = $_SESSION['user_id'];
    $action = "Deleted scholar ID: $id";
    $audit_stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
    $audit_stmt->bind_param("is", $user_id, $action);
    $audit_stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Scholar deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete scholar']);
}

$conn->close();
?>
