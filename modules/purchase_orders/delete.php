<?php
ob_start();
$basePath = '../../';

session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}

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
        header("location: index.php?error=" . urlencode("Only draft purchase orders can be deleted."));
        exit();
    }

    // Delete PO (cascade will delete items)
    $del_stmt = $pdo->prepare("DELETE FROM purchase_orders WHERE po_id = :id AND status = 'Draft'");
    $del_stmt->bindParam(':id', $po_id, PDO::PARAM_INT);

    if($del_stmt->execute() && $del_stmt->rowCount() > 0){
        header("location: index.php?success=deleted");
        exit();
    } else {
        header("location: index.php?error=" . urlencode("Failed to delete the purchase order."));
        exit();
    }

} catch(PDOException $e) {
    header("location: index.php?error=" . urlencode("Error deleting purchase order."));
    exit();
}

ob_end_flush();
?>
