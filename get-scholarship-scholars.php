<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized.'
    ]);
    exit;
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'], true)) {
    echo json_encode([
        'success' => false,
        'message' => 'Forbidden.'
    ]);
    exit;
}

include 'conn.php';
$conn->set_charset("utf8mb4");

$scholarship_id = isset($_GET['scholarship_id']) ? (int) $_GET['scholarship_id'] : 0;
if ($scholarship_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid scholarship.'
    ]);
    exit;
}

$stmt = $conn->prepare("SELECT first_name, last_name, middle_initial FROM scholars WHERE scholarship_id = ? ORDER BY last_name, first_name");
$stmt->bind_param("i", $scholarship_id);
$stmt->execute();
$result = $stmt->get_result();

$names = [];
while ($row = $result->fetch_assoc()) {
    $middle = trim($row['middle_initial']);
    $middle = $middle !== '' ? ' ' . $middle . '.' : '';
    $names[] = $row['last_name'] . ', ' . $row['first_name'] . $middle;
}

echo json_encode([
    'success' => true,
    'names' => $names
]);

$stmt->close();
$conn->close();
