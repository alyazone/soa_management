<?php
// Set the base path for includes
$basePath = '../../';

// Include header and sidebar
include_once $basePath . "includes/header.php";
include_once $basePath . "includes/sidebar.php";

// Include database connection
require_once $basePath . "config/database.php";

// Check if id parameter is set
if(empty($_GET["id"])){
    header("location: index.php");
    exit();
}

// Fetch item data with category and supplier details
try {
    $stmt = $pdo->prepare("SELECT i.*, c.category_name, s.supplier_name, s.pic_name as supplier_contact_name, 
                          s.pic_contact as supplier_contact, s.pic_email as supplier_email,
                          st.full_name as created_by_name
                          FROM inventory_items i 
                          LEFT JOIN inventory_categories c ON i.category_id = c.category_id 
                          LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id 
                          LEFT JOIN staff st ON i.created_by = st.staff_id
                          WHERE i.item_id = :id");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    
    if($stmt->rowCount() != 1){
        header("location: index.php");
        exit();
    }
    
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("ERROR: Could not fetch item. " . $e->getMessage());
}

// Fetch transaction history
try {
    $stmt = $pdo->prepare("SELECT t.*, s.full_name as performed_by_name, 
                          a.full_name as assigned_to_name
                          FROM inventory_transactions t 
                          LEFT JOIN staff s ON t.performed_by = s.staff_id 
                          LEFT JOIN staff a ON t.assigned_to = a.staff_id
                          WHERE t.item_id = :id 
                          ORDER BY t.transaction_date DESC");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    $transactions = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Fetch maintenance records
try {
    $stmt = $pdo->prepare("SELECT m.*, s.full_name as created_by_name
                          FROM inventory_maintenance m
                          LEFT JOIN staff s ON m.created_by = s.staff_id
                          WHERE m.item_id = :id 
                          ORDER BY m.maintenance_date DESC");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    $maintenance_records = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Fetch documents related to this item
try {
    $stmt = $pdo->prepare("SELECT * FROM documents 
                          WHERE reference_type = 'Inventory' AND reference_id = :id 
                          ORDER BY upload_date DESC");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    $documents = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Inventory Item Details</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="edit.php?id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-primary mr-2">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="transactions.php?id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-warning mr-2">
                <i class="fas fa-exchange-alt"></i> Manage Status
            </a>
            <a href="maintenance.php?id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-info mr-2">
                <i class="fas fa-tools"></i> Add Maintenance
            </a>
            <a href="index.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Item Information</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr>
                                <th width="35%">Item ID</th>
                                <td><?php echo htmlspecialchars($item['item_id']); ?></td>
                            </tr>
                            <tr>
                                <th>Item Name</th>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Category</th>
                                <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Serial Number</th>
                                <td><?php echo htmlspecialchars($item['serial_number'] ?: 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Model Number</th>
                                <td><?php echo htmlspecialchars($item['model_number'] ?: 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo ($item['status'] == 'Available') ? 'success' : 
                                            (($item['status'] == 'Assigned') ? 'primary' : 
                                            (($item['status'] == 'Maintenance') ? 'warning' : 'secondary')); 
                                    ?>">
                                        <?php echo htmlspecialchars($item['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Location</th>
                                <td><?php echo htmlspecialchars($item['location'] ?: 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Purchase Date</th>
                                <td><?php echo date('d M Y', strtotime($item['purchase_date'])); ?></td>
                            </tr>
                            <tr>
                                <th>Purchase Price</th>
                                <td>RM <?php echo number_format($item['purchase_price'], 2); ?></td>
                            </tr>
                            <tr>
                                <th>Warranty Expiry</th>
                                <td>
                                    <?php 
                                    if(!empty($item['warranty_expiry'])) {
                                        echo date('d M Y', strtotime($item['warranty_expiry']));
                                        
                                        // Check if warranty is expired
                                        $today = new DateTime();
                                        $warranty_date = new DateTime($item['warranty_expiry']);
                                        if($today > $warranty_date) {
                                            echo ' <span class="badge badge-danger">Expired</span>';
                                        } else {
                                            $interval = $today->diff($warranty_date);
                                            echo ' <span class="badge badge-info">' . $interval->format('%y years, %m months remaining') . '</span>';
                                        }
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Created By</th>
                                <td><?php echo htmlspecialchars($item['created_by_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Created At</th>
                                <td><?php echo date('d M Y H:i', strtotime($item['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <th>Notes</th>
                                <td><?php echo nl2br(htmlspecialchars($item['notes'] ?: 'No notes available')); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Supplier Information</h6>
                    <a href="<?php echo $basePath; ?>modules/suppliers/view.php?id=<?php echo $item['supplier_id']; ?>" class="btn btn-sm btn-info">
                        <i class="fas fa-external-link-alt"></i> View Supplier
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr>
                                <th width="35%">Supplier Name</th>
                                <td><?php echo htmlspecialchars($item['supplier_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Contact Person</th>
                                <td><?php echo htmlspecialchars($item['supplier_contact_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Contact Number</th>
                                <td><?php echo htmlspecialchars($item['supplier_contact']); ?></td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td><?php echo htmlspecialchars($item['supplier_email']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Related Documents</h6>
                    <a href="<?php echo $basePath; ?>modules/documents/upload.php?reference_type=Inventory&reference_id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-upload"></i> Upload Document
                    </a>
                </div>
                <div class="card-body">
                    <?php if(!empty($documents)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>File Name</th>
                                        <th>Upload Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($documents as $document): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($document['document_type']); ?></td>
                                        <td><?php echo htmlspecialchars($document['file_name']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($document['upload_date'])); ?></td>
                                        <td>
                                            <a href="<?php echo $basePath . $document['file_path']; ?>" class="btn btn-info btn-sm" target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo $basePath . $document['file_path']; ?>" class="btn btn-success btn-sm" download>
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No documents found for this item. <a href="<?php echo $basePath; ?>modules/documents/upload.php?reference_type=Inventory&reference_id=<?php echo $item['item_id']; ?>">Upload a document</a> such as a receipt, warranty, or manual.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Transaction History</h6>
                    <a href="transactions.php?id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-warning">
                        <i class="fas fa-plus"></i> New Transaction
                    </a>
                </div>
                <div class="card-body">
                    <?php if(!empty($transactions)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Status Change</th>
                                        <th>Performed By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($transactions as $transaction): ?>
                                    <tr>
                                        <td><?php echo date('d M Y H:i', strtotime($transaction['transaction_date'])); ?></td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo ($transaction['transaction_type'] == 'Purchase') ? 'success' : 
                                                    (($transaction['transaction_type'] == 'Assignment') ? 'primary' : 
                                                    (($transaction['transaction_type'] == 'Return') ? 'info' : 
                                                    (($transaction['transaction_type'] == 'Maintenance') ? 'warning' : 'danger'))); 
                                            ?>">
                                                <?php echo htmlspecialchars($transaction['transaction_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            if(!empty($transaction['from_status'])) {
                                                echo htmlspecialchars($transaction['from_status']) . ' → ';
                                            }
                                            echo htmlspecialchars($transaction['to_status'] ?: 'N/A'); 
                                            
                                            if($transaction['transaction_type'] == 'Assignment' && !empty($transaction['assigned_to'])) {
                                                echo '<br><small>Assigned to: ' . htmlspecialchars($transaction['assigned_to_name']) . '</small>';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($transaction['performed_by_name']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No transaction history found for this item.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Maintenance Records</h6>
                    <a href="maintenance.php?id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-info">
                        <i class="fas fa-plus"></i> Add Maintenance Record
                    </a>
                </div>
                <div class="card-body">
                    <?php if(!empty($maintenance_records)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Cost</th>
                                        <th>Performed By</th>
                                        <th>Next Maintenance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($maintenance_records as $record): ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($record['maintenance_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($record['maintenance_type']); ?></td>
                                        <td>RM <?php echo number_format($record['cost'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($record['performed_by']); ?></td>
                                        <td>
                                            <?php 
                                            if(!empty($record['next_maintenance_date'])) {
                                                echo date('d M Y', strtotime($record['next_maintenance_date']));
                                                
                                                // Check if next maintenance is due
                                                $today = new DateTime();
                                                $next_date = new DateTime($record['next_maintenance_date']);
                                                if($today > $next_date) {
                                                    echo ' <span class="badge badge-danger">Overdue</span>';
                                                } else {
                                                    $interval = $today->diff($next_date);
                                                    if($interval->days <= 30) {
                                                        echo ' <span class="badge badge-warning">Due soon</span>';
                                                    }
                                                }
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No maintenance records found for this item.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="transactions.php?id=<?php echo $item['item_id']; ?>&type=Assignment" class="btn btn-primary btn-block">
                                <i class="fas fa-user-plus mr-2"></i> Assign Item
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="transactions.php?id=<?php echo $item['item_id']; ?>&type=Return" class="btn btn-info btn-block">
                                <i class="fas fa-undo mr-2"></i> Return Item
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="transactions.php?id=<?php echo $item['item_id']; ?>&type=Maintenance" class="btn btn-warning btn-block">
                                <i class="fas fa-tools mr-2"></i> Send for Maintenance
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="transactions.php?id=<?php echo $item['item_id']; ?>&type=Disposal" class="btn btn-danger btn-block">
                                <i class="fas fa-trash-alt mr-2"></i> Dispose Item
                            </a>
                        </div>
                        <div class="col-md-12">
                            <a href="<?php echo $basePath; ?>modules/documents/upload.php?reference_type=Inventory&reference_id=<?php echo $item['item_id']; ?>" class="btn btn-secondary btn-block">
                                <i class="fas fa-file-upload mr-2"></i> Upload Document
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once $basePath . "includes/footer.php";
?>
