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

// Fetch client data
try {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = :id");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    
    if($stmt->rowCount() != 1){
        header("location: index.php");
        exit();
    }
    
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("ERROR: Could not fetch client. " . $e->getMessage());
}

// Fetch SOAs related to this client
try {
    $stmt = $pdo->prepare("SELECT s.*, sup.supplier_name 
                           FROM soa s 
                           JOIN suppliers sup ON s.supplier_id = sup.supplier_id 
                           WHERE s.client_id = :id 
                           ORDER BY s.issue_date DESC");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    $soas = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Fetch documents related to this client
try {
    $stmt = $pdo->prepare("SELECT * FROM documents 
                           WHERE reference_type = 'Client' AND reference_id = :id 
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
        <h1 class="h2">Client Details</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="edit.php?id=<?php echo $client['client_id']; ?>" class="btn btn-sm btn-primary mr-2">
                <i class="fas fa-edit"></i> Edit
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
                    <h6 class="m-0 font-weight-bold text-primary">Client Information</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr>
                                <th>Client ID</th>
                                <td><?php echo htmlspecialchars($client['client_id']); ?></td>
                            </tr>
                            <tr>
                                <th>Client Name</th>
                                <td><?php echo htmlspecialchars($client['client_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Address</th>
                                <td><?php echo nl2br(htmlspecialchars($client['address'])); ?></td>
                            </tr>
                            <tr>
                                <th>PIC Name</th>
                                <td><?php echo htmlspecialchars($client['pic_name']); ?></td>
                            </tr>
                            <tr>
                                <th>PIC Contact</th>
                                <td><?php echo htmlspecialchars($client['pic_contact']); ?></td>
                            </tr>
                            <tr>
                                <th>PIC Email</th>
                                <td><?php echo htmlspecialchars($client['pic_email']); ?></td>
                            </tr>
                            <tr>
                                <th>Created At</th>
                                <td><?php echo date('Y-m-d H:i', strtotime($client['created_at'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Related Documents</h6>
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
                                        <td><?php echo date('Y-m-d', strtotime($document['upload_date'])); ?></td>
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
                        <p>No documents found for this client.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Related SOAs</h6>
                    <a href="<?php echo $basePath; ?>modules/soa/add.php?client_id=<?php echo $client['client_id']; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Create SOA
                    </a>
                </div>
                <div class="card-body">
                    <?php if(!empty($soas)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Account #</th>
                                        <th>Supplier</th>
                                        <th>Issue Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($soas as $soa): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo $basePath; ?>modules/soa/view.php?id=<?php echo $soa['soa_id']; ?>">
                                                <?php echo htmlspecialchars($soa['account_number']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($soa['supplier_name']); ?></td>
                                        <td><?php echo htmlspecialchars($soa['issue_date']); ?></td>
                                        <td>RM <?php echo number_format($soa['balance_amount'], 2); ?></td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo ($soa['status'] == 'Paid') ? 'success' : 
                                                    (($soa['status'] == 'Overdue') ? 'danger' : 'warning'); 
                                            ?>">
                                                <?php echo htmlspecialchars($soa['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No SOAs found for this client.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Client Statistics</h6>
                </div>
                <div class="card-body">
                    <?php
                    // Calculate statistics
                    $total_soas = count($soas);
                    $total_amount = 0;
                    $pending_amount = 0;
                    $paid_amount = 0;
                    
                    foreach($soas as $soa) {
                        $total_amount += $soa['balance_amount'];
                        if($soa['status'] == 'Paid') {
                            $paid_amount += $soa['balance_amount'];
                        } else {
                            $pending_amount += $soa['balance_amount'];
                        }
                    }
                    ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total SOAs</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_soas; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-file-invoice-dollar fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Total Amount</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">RM <?php echo number_format($total_amount, 2); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Pending Amount</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">RM <?php echo number_format($pending_amount, 2); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Paid Amount</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">RM <?php echo number_format($paid_amount, 2); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
