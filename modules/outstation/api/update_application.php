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
    $required_fields = ['application_id', 'purpose', 'purpose_details', 'destination', 'departure_date', 'return_date', 'transportation_mode'];

    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            exit();
        }
    }

    // Get form data
    $application_id = intval($_POST['application_id']);
    $purpose = trim($_POST['purpose']);
    $purpose_details = trim($_POST['purpose_details']);
    $destination = trim($_POST['destination']);
    $departure_date = $_POST['departure_date'];
    $departure_time = $_POST['departure_time'] ?? null;
    $return_date = $_POST['return_date'];
    $return_time = $_POST['return_time'] ?? null;
    $transportation_mode = trim($_POST['transportation_mode']);
    $estimated_cost = isset($_POST['estimated_cost']) ? floatval($_POST['estimated_cost']) : 0.00;
    $accommodation_details = $_POST['accommodation_details'] ?? null;
    $remarks = $_POST['remarks'] ?? null;

    // Check ownership or admin/manager permission
    $is_admin_or_manager = ($_SESSION['position'] == 'Admin' || $_SESSION['position'] == 'Manager');

    $check_sql = "SELECT staff_id, status FROM outstation_applications WHERE application_id = :id";
    $stmt = $pdo->prepare($check_sql);
    $stmt->bindParam(':id', $application_id, PDO::PARAM_INT);
    $stmt->execute();
    $app = $stmt->fetch();

    if (!$app) {
        echo json_encode(['success' => false, 'message' => 'Application not found']);
        exit();
    }

    // Check permissions
    if (!$is_admin_or_manager && $app['staff_id'] != $_SESSION['staff_id']) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized to modify this application']);
        exit();
    }

    // Only allow editing of pending applications
    if ($app['status'] != 'Pending') {
        echo json_encode(['success' => false, 'message' => 'Can only edit pending applications']);
        exit();
    }

    // Validate dates
    $departure = new DateTime($departure_date);
    $return = new DateTime($return_date);

    if ($return < $departure) {
        echo json_encode(['success' => false, 'message' => 'Return date cannot be earlier than departure date']);
        exit();
    }

    // Calculate nights and claimability
    $interval = $departure->diff($return);
    $total_nights = $interval->days;
    $is_claimable = $total_nights >= 1 ? 1 : 0;

    // Begin transaction
    $pdo->beginTransaction();

    // Update application
    $sql = "UPDATE outstation_applications SET
            purpose = :purpose,
            purpose_details = :purpose_details,
            destination = :destination,
            departure_date = :departure_date,
            departure_time = :departure_time,
            return_date = :return_date,
            return_time = :return_time,
            total_nights = :total_nights,
            is_claimable = :is_claimable,
            transportation_mode = :transportation_mode,
            estimated_cost = :estimated_cost,
            accommodation_details = :accommodation_details,
            remarks = :remarks,
            updated_at = CURRENT_TIMESTAMP
            WHERE application_id = :application_id";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':purpose' => $purpose,
        ':purpose_details' => $purpose_details,
        ':destination' => $destination,
        ':departure_date' => $departure_date,
        ':departure_time' => $departure_time,
        ':return_date' => $return_date,
        ':return_time' => $return_time,
        ':total_nights' => $total_nights,
        ':is_claimable' => $is_claimable,
        ':transportation_mode' => $transportation_mode,
        ':estimated_cost' => $estimated_cost,
        ':accommodation_details' => $accommodation_details,
        ':remarks' => $remarks,
        ':application_id' => $application_id
    ]);

    if ($result) {
        // Commit transaction
        $pdo->commit();

        // Send success response
        echo json_encode([
            'success' => true,
            'message' => 'Application updated successfully',
            'data' => [
                'application_id' => $application_id,
                'total_nights' => $total_nights,
                'is_claimable' => $is_claimable
            ]
        ]);
    } else {
        throw new Exception('Failed to update application');
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
