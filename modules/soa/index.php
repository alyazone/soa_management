<?php
ob_start();
// Set the base path for includes
$basePath = '../../';

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
        $sql = "DELETE FROM soa WHERE soa_id = :id";
        
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

// Fetch all SOAs with client and supplier names
try {
    $stmt = $pdo->query("SELECT s.*, c.client_name, sup.supplier_name 
                         FROM soa s 
                         JOIN clients c ON s.client_id = c.client_id 
                         JOIN suppliers sup ON s.supplier_id = sup.supplier_id 
                         ORDER BY s.issue_date DESC");
    $soas = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">SOA Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="add.php" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> Create New SOA
            </a>
        </div>
    </div>
    
    <?php if(isset($_GET["success"]) && $_GET["success"] == "1"): ?>
        <div class="alert alert-success">
            SOA record has been deleted successfully.
        </div>
    <?php endif; ?>
    
    <?php if(isset($delete_err)): ?>
        <div class="alert alert-danger">
            <?php echo $delete_err; ?>
        </div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">SOA List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Account #</th>
                            <th>Client</th>
                            <th>Supplier</th>
                            <th>Issue Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($soas as $soa): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($soa['account_number']); ?></td>
                            <td><?php echo htmlspecialchars($soa['client_name']); ?></td>
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
                                <a href="index.php?action=delete&id=<?php echo $soa['soa_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this SOA?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($soas)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No SOA records found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once $basePath . "includes/footer.php";
ob_end_flush();
?>
