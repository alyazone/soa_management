<?php
ob_start();
// Set the base path for includes
$basePath = '../../../';

// Include header and sidebar
include_once $basePath . "includes/header.php";
include_once $basePath . "includes/sidebar.php";

// Check if the user has admin privileges
if($_SESSION['position'] != 'Admin'){
    echo '<div class="col-md-10 ml-sm-auto px-4"><div class="alert alert-danger mt-3">You do not have permission to access this page.</div></div>';
    include_once $basePath . "includes/footer.php";
    exit;
}


// Include database connection
require_once $basePath . "config/database.php";

// Process delete operation
if(isset($_GET["action"]) && $_GET["action"] == "delete" && isset($_GET["id"]) && !empty($_GET["id"])){
    try {
        // Prepare a delete statement
        $sql = "DELETE FROM supplier_soa WHERE soa_id = :id";
        
        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
            
            // Set parameters
            $param_id = trim($_GET["id"]);
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Records deleted successfully. Redirect to landing page
                header("location: index.php?success=1");
                exit();
            } else{
                $delete_err = "Oops! Something went wrong. Please try again later.";
            }
        }
    } catch(PDOException $e) {
        $delete_err = "Error: " . $e->getMessage();
    }
}

// Fetch all Supplier SOAs
try {
    $stmt = $pdo->query("SELECT s.*, sup.supplier_name 
                         FROM supplier_soa s 
                         JOIN suppliers sup ON s.supplier_id = sup.supplier_id 
                         ORDER BY s.issue_date DESC");
    $soas = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Supplier SOA Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="add.php" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> Create New Supplier SOA
            </a>
        </div>
    </div>
    
    <?php if(isset($_GET["success"])): ?>
        <div class="alert alert-success">
            <?php 
            if($_GET["success"] == "1") {
                echo "Supplier SOA record has been deleted successfully.";
            } elseif($_GET["success"] == "2") {
                echo "Supplier SOA record has been added successfully.";
            } elseif($_GET["success"] == "3") {
                echo "Supplier SOA record has been updated successfully.";
            }
            ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($delete_err)): ?>
        <div class="alert alert-danger">
            <?php echo $delete_err; ?>
        </div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Supplier SOA List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Supplier</th>
                            <th>Issue Date</th>
                            <th>Payment Due</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($soas as $soa): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($soa['invoice_number']); ?></td>
                            <td><?php echo htmlspecialchars($soa['supplier_name']); ?></td>
                            <td><?php echo htmlspecialchars($soa['issue_date']); ?></td>
                            <td><?php echo htmlspecialchars($soa['payment_due_date']); ?></td>
                            <td>RM <?php echo number_format($soa['amount'], 2); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo ($soa['payment_status'] == 'Paid') ? 'success' : 
                                        (($soa['payment_status'] == 'Overdue') ? 'danger' : 'warning'); 
                                ?>">
                                    <?php echo htmlspecialchars($soa['payment_status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="view.php?id=<?php echo $soa['soa_id']; ?>" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit.php?id=<?php echo $soa['soa_id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="generate_pdf.php?id=<?php echo $soa['soa_id']; ?>" class="btn btn-success btn-sm" target="_blank">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                                <a href="index.php?action=delete&id=<?php echo $soa['soa_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this Supplier SOA?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($soas)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No Supplier SOA records found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Payment Tracker Summary -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Payment Tracker Summary</h6>
        </div>
        <div class="card-body">
            <?php
            // Calculate summary statistics
            try {
                $stmt = $pdo->query("SELECT 
                                    COUNT(*) as total_soas,
                                    SUM(CASE WHEN payment_status = 'Paid' THEN 1 ELSE 0 END) as paid_count,
                                    SUM(CASE WHEN payment_status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
                                    SUM(CASE WHEN payment_status = 'Overdue' THEN 1 ELSE 0 END) as overdue_count,
                                    SUM(amount) as total_amount,
                                    SUM(CASE WHEN payment_status = 'Paid' THEN amount ELSE 0 END) as paid_amount,
                                    SUM(CASE WHEN payment_status != 'Paid' THEN amount ELSE 0 END) as outstanding_amount
                                FROM supplier_soa");
                $summary = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                echo "Error: " . $e->getMessage();
                $summary = [
                    'total_soas' => 0,
                    'paid_count' => 0,
                    'pending_count' => 0,
                    'overdue_count' => 0,
                    'total_amount' => 0,
                    'paid_amount' => 0,
                    'outstanding_amount' => 0
                ];
            }
            ?>
            
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total SOAs</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $summary['total_soas']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-file-invoice-dollar fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Paid</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $summary['paid_count']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Pending</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $summary['pending_count']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        Overdue</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $summary['overdue_count']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-exclamation-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Total Amount</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">RM <?php echo number_format($summary['total_amount'], 2); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Paid Amount</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">RM <?php echo number_format($summary['paid_amount'], 2); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        Outstanding Amount</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">RM <?php echo number_format($summary['outstanding_amount'], 2); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-hand-holding-usd fa-2x text-gray-300"></i>
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
ob_end_flush();
?>
