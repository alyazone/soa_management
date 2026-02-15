<?php
ob_start();
$basePath = '../../';

session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}

// Only Admin and Manager can approve
if($_SESSION['position'] != 'Admin' && $_SESSION['position'] != 'Manager'){
    header("location: " . $basePath . "dashboard.php");
    exit;
}

require_once $basePath . "config/database.php";

if(empty($_GET["id"])){
    header("location: index.php");
    exit();
}

$po_id = intval($_GET["id"]);

try {
    // Verify PO exists and is in Draft status
    $stmt = $pdo->prepare("SELECT po_id, po_number, status FROM purchase_orders WHERE po_id = :id");
    $stmt->bindParam(':id', $po_id, PDO::PARAM_INT);
    $stmt->execute();

    if($stmt->rowCount() != 1){
        header("location: index.php?error=" . urlencode("Purchase order not found."));
        exit();
    }

    $po = $stmt->fetch(PDO::FETCH_ASSOC);

    if($po['status'] != 'Draft'){
        header("location: index.php?error=" . urlencode("Only draft purchase orders can be approved."));
        exit();
    }

    // Approve the PO
    $sql = "UPDATE purchase_orders SET status = 'Approved', approved_by = :approved_by, approved_date = NOW() WHERE po_id = :id AND status = 'Draft'";
    $update_stmt = $pdo->prepare($sql);
    $update_stmt->bindParam(':approved_by', $_SESSION['staff_id'], PDO::PARAM_INT);
    $update_stmt->bindParam(':id', $po_id, PDO::PARAM_INT);

    if($update_stmt->execute() && $update_stmt->rowCount() > 0){
        header("location: view.php?id=" . $po_id . "&success=approved");
        exit();
    } else {
        header("location: index.php?error=" . urlencode("Failed to approve the purchase order."));
        exit();
    }

} catch(PDOException $e) {
    header("location: index.php?error=" . urlencode("Error approving purchase order."));
    exit();
}

ob_end_flush();
?>
