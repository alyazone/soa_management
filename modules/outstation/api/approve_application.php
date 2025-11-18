<?php
session_start();
require_once '../../../config/database.php';

// Check if user is logged in and has permission
if (!isset($_SESSION['staff_id']) || !in_array($_SESSION['position'], ['Admin', 'Manager'])) {
    if(isset($_POST['application_id'])) {
        header("Location: ../view.php?id=" . intval($_POST['application_id']) . "&error=unauthorized");
    } else {
        header("Location: ../index.php?error=unauthorized");
    }
    exit();
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit();
}

try {
    $application_id = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($application_id <= 0 || !in_array($action, ['approve', 'reject'])) {
        header("Location: ../index.php?error=invalid_request");
        exit();
    }

    // Fetch application
    $check_sql = "SELECT * FROM outstation_applications WHERE application_id = :id";
    $stmt = $pdo->prepare($check_sql);
    $stmt->bindParam(':id', $application_id, PDO::PARAM_INT);
    $stmt->execute();
    $app = $stmt->fetch();

    if (!$app) {
        header("Location: ../index.php?error=not_found");
        exit();
    }

    // Only allow action on pending applications
    if ($app['status'] != 'Pending') {
        header("Location: ../view.php?id=$application_id&error=not_pending");
        exit();
    }

    // Begin transaction
    $pdo->beginTransaction();

    if ($action === 'approve') {
        // Approve application
        $sql = "UPDATE outstation_applications SET
                status = 'Approved',
                approved_by = :approved_by,
                approved_at = CURRENT_TIMESTAMP
                WHERE application_id = :application_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':approved_by' => $_SESSION['staff_id'],
            ':application_id' => $application_id
        ]);

        $pdo->commit();
        header("Location: ../view.php?id=$application_id&success=approved");

    } elseif ($action === 'reject') {
        // Reject application
        $rejection_reason = isset($_POST['rejection_reason']) ? trim($_POST['rejection_reason']) : '';

        if (empty($rejection_reason)) {
            $pdo->rollBack();
            header("Location: ../view.php?id=$application_id&error=reason_required");
            exit();
        }

        $sql = "UPDATE outstation_applications SET
                status = 'Rejected',
                approved_by = :approved_by,
                approved_at = CURRENT_TIMESTAMP,
                rejection_reason = :rejection_reason
                WHERE application_id = :application_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':approved_by' => $_SESSION['staff_id'],
            ':rejection_reason' => $rejection_reason,
            ':application_id' => $application_id
        ]);

        $pdo->commit();
        header("Location: ../view.php?id=$application_id&success=rejected");
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Database Error: " . $e->getMessage());
    header("Location: ../index.php?error=database");
}

exit();
?>
