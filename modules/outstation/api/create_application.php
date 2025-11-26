<?php
session_start();
require_once '../../../config/database.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['staff_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

try {
    // Validate required fields
    $required_fields = ['application_number', 'staff_id', 'purpose', 'purpose_details', 'destination', 'departure_date', 'return_date', 'transportation_mode'];

    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            exit();
        }
    }

    // Get form data
    $application_number = trim($_POST['application_number']);
    $staff_id = intval($_POST['staff_id']);
    $purpose = trim($_POST['purpose']);
    $purpose_details = trim($_POST['purpose_details']);
    $destination = trim($_POST['destination']);
    $departure_date = $_POST['departure_date'];
    $departure_time = $_POST['departure_time'] ?? null;
    $return_date = $_POST['return_date'];
    $return_time = $_POST['return_time'] ?? null;
    $total_nights = intval($_POST['total_nights']);
    $is_claimable = intval($_POST['is_claimable']);
    $transportation_mode = trim($_POST['transportation_mode']);
    $estimated_cost = isset($_POST['estimated_cost']) ? floatval($_POST['estimated_cost']) : 0.00;
    $accommodation_details = $_POST['accommodation_details'] ?? null;
    $remarks = $_POST['remarks'] ?? null;
    
    // Validate staff_id matches session
    if ($staff_id != intval($_SESSION['staff_id'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid staff ID']);
        exit();
    }
    
    // Validate dates
    $departure = new DateTime($departure_date);
    $return = new DateTime($return_date);
    
    if ($return < $departure) {
        echo json_encode(['success' => false, 'message' => 'Return date cannot be earlier than departure date']);
        exit();
    }
    
    // Recalculate nights to ensure accuracy
    $interval = $departure->diff($return);
    $calculated_nights = $interval->days;

    if ($calculated_nights != $total_nights) {
        $total_nights = $calculated_nights;
        $is_claimable = $calculated_nights >= 2 ? 1 : 0;
    }
    
    // Check if application number already exists
    $stmt = $pdo->prepare("SELECT application_id FROM outstation_applications WHERE application_number = ?");
    $stmt->execute([$application_number]);
    
    if ($stmt->fetch()) {
        // Generate new application number
        $application_number = 'OSL-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Insert application
    $sql = "INSERT INTO outstation_applications (
        application_number,
        staff_id,
        purpose,
        purpose_details,
        destination,
        departure_date,
        departure_time,
        return_date,
        return_time,
        total_nights,
        is_claimable,
        transportation_mode,
        estimated_cost,
        accommodation_details,
        remarks,
        status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $application_number,
        $staff_id,
        $purpose,
        $purpose_details,
        $destination,
        $departure_date,
        $departure_time,
        $return_date,
        $return_time,
        $total_nights,
        $is_claimable,
        $transportation_mode,
        $estimated_cost,
        $accommodation_details,
        $remarks
    ]);
    
    if ($result) {
        $application_id = $pdo->lastInsertId();
        
        // Commit transaction
        $pdo->commit();
        
        // Send success response
        echo json_encode([
            'success' => true,
            'message' => 'Application submitted successfully',
            'data' => [
                'application_id' => $application_id,
                'application_number' => $application_number,
                'total_nights' => $total_nights,
                'is_claimable' => $is_claimable
            ]
        ]);
    } else {
        throw new Exception('Failed to insert application');
    }
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Database Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred. Please try again.'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>