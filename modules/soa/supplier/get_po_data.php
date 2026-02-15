<?php
/**
 * AJAX endpoint: returns PO data for the Supplier SOA form.
 *
 * Actions:
 *   ?action=list_pos&supplier_id=X   → Approved/Partially Invoiced POs for a supplier
 *   ?action=po_details&po_id=X       → Full PO details + remaining balance
 */
header('Content-Type: application/json');

$basePath = '../../../';
require_once $basePath . "config/database.php";

session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

// Auto-migrate: ensure supplier_soa has po_id column
try {
    $cols = $pdo->query("SHOW COLUMNS FROM supplier_soa LIKE 'po_id'")->fetchAll();
    if(count($cols) === 0){
        $pdo->exec("ALTER TABLE supplier_soa ADD COLUMN po_id INT NULL AFTER supplier_id");
        // Only add FK if purchase_orders table exists
        try {
            $pdo->query("SELECT 1 FROM purchase_orders LIMIT 1");
            $pdo->exec("ALTER TABLE supplier_soa ADD FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id) ON DELETE SET NULL");
        } catch(PDOException $e) {
            // purchase_orders table doesn't exist yet, skip FK
        }
    }
} catch(PDOException $e) {
    // supplier_soa table might not exist, ignore
}

// Auto-migrate: ensure purchase_orders status ENUM includes new statuses
try {
    $col = $pdo->query("SHOW COLUMNS FROM purchase_orders LIKE 'status'")->fetch(PDO::FETCH_ASSOC);
    if($col && strpos($col['Type'], 'Partially Invoiced') === false){
        $pdo->exec("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('Draft','Approved','Partially Invoiced','Closed','Received','Cancelled') DEFAULT 'Draft'");
    }
} catch(PDOException $e) {
    // ignore if purchase_orders doesn't exist
}

if($action === 'list_pos'){
    // Return approved / partially invoiced POs for a given supplier
    $supplier_id = isset($_GET['supplier_id']) ? intval($_GET['supplier_id']) : 0;
    if($supplier_id <= 0){
        echo json_encode([]);
        exit;
    }

    try {
        $sql = "SELECT po.po_id, po.po_number, po.total_amount, po.status,
                       COALESCE(SUM(ss.amount), 0) as invoiced_amount
                FROM purchase_orders po
                LEFT JOIN supplier_soa ss ON ss.po_id = po.po_id
                WHERE po.supplier_id = :supplier_id
                  AND po.status IN ('Approved', 'Partially Invoiced')
                GROUP BY po.po_id, po.po_number, po.total_amount, po.status
                ORDER BY po.order_date DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
        $stmt->execute();
        $pos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach($pos as $po){
            $remaining = floatval($po['total_amount']) - floatval($po['invoiced_amount']);
            if($remaining > 0){
                $result[] = [
                    'po_id'            => intval($po['po_id']),
                    'po_number'        => $po['po_number'],
                    'total_amount'     => floatval($po['total_amount']),
                    'invoiced_amount'  => floatval($po['invoiced_amount']),
                    'remaining_amount' => round($remaining, 2)
                ];
            }
        }

        echo json_encode($result);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if($action === 'po_details'){
    // Return full details for a single PO
    $po_id = isset($_GET['po_id']) ? intval($_GET['po_id']) : 0;
    if($po_id <= 0){
        echo json_encode(['error' => 'Invalid PO ID']);
        exit;
    }

    try {
        // PO header
        $stmt = $pdo->prepare("SELECT po.*, s.supplier_name
                               FROM purchase_orders po
                               JOIN suppliers s ON po.supplier_id = s.supplier_id
                               WHERE po.po_id = :id");
        $stmt->bindParam(':id', $po_id, PDO::PARAM_INT);
        $stmt->execute();
        $po = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$po){
            echo json_encode(['error' => 'PO not found']);
            exit;
        }

        // PO line items → build description
        $item_stmt = $pdo->prepare("SELECT description, quantity, unit_price, total_price
                                    FROM purchase_order_items
                                    WHERE po_id = :id ORDER BY item_id");
        $item_stmt->bindParam(':id', $po_id, PDO::PARAM_INT);
        $item_stmt->execute();
        $items = $item_stmt->fetchAll(PDO::FETCH_ASSOC);

        $desc_parts = [];
        foreach($items as $item){
            $desc_parts[] = $item['description'] . ' (Qty: ' . rtrim(rtrim(number_format($item['quantity'], 2), '0'), '.') . ' x RM ' . number_format($item['unit_price'], 2) . ')';
        }
        $description = implode("\n", $desc_parts);

        // Already invoiced amount
        $inv_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as invoiced FROM supplier_soa WHERE po_id = :id");
        $inv_stmt->bindParam(':id', $po_id, PDO::PARAM_INT);
        $inv_stmt->execute();
        $invoiced = floatval($inv_stmt->fetchColumn());

        $remaining = round(floatval($po['total_amount']) - $invoiced, 2);

        echo json_encode([
            'po_id'            => intval($po['po_id']),
            'po_number'        => $po['po_number'],
            'supplier_id'      => intval($po['supplier_id']),
            'supplier_name'    => $po['supplier_name'],
            'total_amount'     => floatval($po['total_amount']),
            'invoiced_amount'  => $invoiced,
            'remaining_amount' => $remaining,
            'description'      => $description
        ]);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['error' => 'Invalid action']);
?>
