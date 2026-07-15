<?php

header('Content-Type: application/json');

$basePath = '../../../';

// Include database connection
require_once $basePath . "config/database.php";


session_start();

// 1. Check if user is logged in
if (!isset($_SESSION['staff_id']) || !isset($_SESSION['position'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired or not logged in']);
    exit();
}

// 2. Get IDs as integers for reliable comparison
$requested_staff_id = isset($_GET['staff_id']) ? intval($_GET['staff_id']) : 0;
$session_staff_id = intval($_SESSION['staff_id']);
$user_position = $_SESSION['position'];

// 3. Authorization logic: Admin/Manager OR requesting own data
$is_authorized = (in_array($user_position, ['Admin', 'Manager']) || $session_staff_id === $requested_staff_id);

if (!$is_authorized) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}
//Fetch data and sends as JSON object
if (isset($_GET['staff_id'])) {
    $staff_id = intval($_GET['staff_id']);
    $stmt = $pdo->prepare("SELECT * FROM leave_availability WHERE staff_id = ?");
    $stmt->execute([$staff_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'message' => 'Staff not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Missing staff_id']);
}
?>