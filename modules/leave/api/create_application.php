<?php
session_start();
require_once '../../../config/database.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['staff_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

try {
    // Validate required fields
    $required_fields = ['staff_id', 'leave_reason', 'start_date', 'end_date', 'total_day'];

    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            exit();
        }
    }

    // Get form data
    $staff_id = intval($_POST['staff_id']);
    $leave_reason = trim($_POST['leave_reason']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $total_day = intval($_POST['total_day']);
    
    // Validate total_day
    if ($total_day <= 0) {
        echo json_encode(['success' => false, 'message' => 'Total days must be a positive number']);
        exit();
    }
    
    // Validate dates
    $startD = new DateTime($start_date);
    $endD = new DateTime($end_date);
    
    if ($endD < $startD) {
        echo json_encode(['success' => false, 'message' => 'End date cannot be earlier than start date']);
        exit();
    }
    
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Insert application
    $sql = "INSERT INTO leave_application (
        staff_id,
        leave_reason,
        start_date,
        end_date,
        total_day
    ) VALUES (?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $staff_id,
        $leave_reason,
        $start_date,
        $end_date,
        $total_day,

    ]);
    
if ($result) {
    $application_id = $pdo->lastInsertId();
    $leave_deduct = "";

    // Mapping leave types to column names
    switch ($leave_reason) {
        case "AL": $leave_deduct = "annual_leave"; break;
        case "EL": $leave_deduct = "emergency_leave"; break;
        case "ML": $leave_deduct = "medical_leave"; break;
        case "OL": $leave_deduct = "outstation_leave"; break;
        case "BL": $leave_deduct = "birthday_leave"; break;
        case "CL": $leave_deduct = "carryforward_leave"; break;
        case "CPL": $leave_deduct = "paternal_leave"; break;
        case "CML": $leave_deduct = "maternal_leave"; break;
        case "SML": $leave_deduct = "marriage_leave"; break;
        case "SHL": $leave_deduct = "umrah_haji_leave"; break;
        case "HL": $leave_deduct = "hospitalization_leave"; break;
        case "ILL": $leave_deduct = "in_lieu_leave"; break;
        default:
            throw new Exception("Invalid leave type selected.");
    }

    // Fetch current balance for validation
    $check_sql = "SELECT $leave_deduct FROM leave_availability WHERE staff_id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$staff_id]);
    $current_balance = $check_stmt->fetchColumn();

    if ($current_balance === false) {
        throw new Exception("Leave availability record not found for this staff.");
    }

    if ($current_balance < $total_day) {
        throw new Exception("Insufficient leave balance. Current balance: $current_balance days.");
    }

    $update_sql = "UPDATE leave_availability 
                   SET $leave_deduct = $leave_deduct - ? 
                   WHERE staff_id = ?";

    $updateStmt = $pdo->prepare($update_sql);
    $updateStmt->execute([$total_day, $staff_id]);

    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Application submitted and leave balance updated successfully',
        'data' => [
            'application_id' => $application_id,
            'total_day' => $total_day,
        ]
    ]);
    } else {
        throw new Exception('Failed to insert application');
    }
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Database Error in create_application.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'A database error occurred. Please try again later.'
    ]);
    
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error in create_application.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>