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
    echo json_encode(['success' => false, 'message' => 'Invalid campus ID']);
    exit;
}

// Check if campus exists
$stmt = $conn->prepare("SELECT * FROM campuses WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Campus not found']);
    exit;
}

// Check if campus has scholars
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM scholars WHERE campus_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$count = $stmt->get_result()->fetch_assoc()['count'];

if ($count > 0) {
    echo json_encode(['success' => false, 'message' => "Cannot delete campus. It has $count enrolled scholar(s)."]);
    exit;
}

// Delete campus
$stmt = $conn->prepare("DELETE FROM campuses WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // Log audit
    $user_id = $_SESSION['user_id'];
    $action = "Deleted campus ID: $id";
    $audit_stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
    $audit_stmt->bind_param("is", $user_id, $action);
    $audit_stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Campus deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete campus']);
}

$conn->close();
?>
