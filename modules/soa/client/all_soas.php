<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
       $sql = "DELETE FROM client_soa WHERE soa_id = :id";
       
       if($stmt = $pdo->prepare($sql)){
           // Bind variables to the prepared statement as parameters
           $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
           
           // Set parameters
           $param_id = trim($_GET["id"]);
           
           // Attempt to execute the prepared statement
           if($stmt->execute()){
               // Records deleted successfully. Redirect to landing page
               header("location: all_soas.php?success=1");
               exit();
           } else{
               $delete_err = "Oops! Something went wrong. Please try again later.";
           }
       }
   } catch(PDOException $e) {
       $delete_err = "Error: " . $e->getMessage();
   }
}

// Process close account operation
if(isset($_GET["action"]) && $_GET["action"] == "close" && isset($_GET["id"]) && !empty($_GET["id"])){
    try {
        // First check the current status
        $check_stmt = $pdo->prepare("SELECT status FROM client_soa WHERE soa_id = :id");
        $check_stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
        $check_stmt->execute();
        $current_status = $check_stmt->fetchColumn();
        
        // Prepare an update statement to close the account
        $sql = "UPDATE client_soa SET status = 'Closed' WHERE soa_id = :id";
        
        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
            
            // Set parameters
            $param_id = trim($_GET["id"]);
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Account closed successfully. Redirect to landing page
                header("location: all_soas.php?success=4");
                exit();
            } else{
                $close_err = "Oops! Something went wrong. Please try again later.";
            }
        }
    } catch(PDOException $e) {
        $close_err = "Error: " . $e->getMessage();
    }
}

// Fetch all Client SOAs
try {
   // Check if the client_soa table exists
   $tableExists = false;
   $stmt = $pdo->query("SHOW TABLES LIKE 'client_soa'");
   if ($stmt->rowCount() > 0) {
       $tableExists = true;
   }
   
   if (!$tableExists) {
       echo '<div class="alert alert-danger">The client_soa table does not exist in the database. Please create it first.</div>';
   } else {
       $stmt = $pdo->query("SELECT s.*, c.client_name 
                           FROM client_soa s 
                           JOIN clients c ON s.client_id = c.client_id 
                           ORDER BY s.issue_date DESC");
       $soas = $stmt->fetchAll();
   }
} catch(PDOException $e) {
   echo '<div class="alert alert-danger">Database Error: ' . $e->getMessage() . '</div>';
}
?>

<div class="col-md-10 ml-sm-auto px-4">
   <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
       <h1 class="h2">All Client SOAs</h1>
       <div class="btn-toolbar mb-2 mb-md-0">
           <a href="add.php" class="btn btn-sm btn-primary">
               <i class="fas fa-plus"></i> Create New Client SOA
           </a>
           <a href="index.php" class="btn btn-sm btn-secondary ml-2">
               <i class="fas fa-arrow-left"></i> Back to Clients
           </a>
       </div>
   </div>
   
   <?php if(isset($_GET["success"])): ?>
       <div class="alert alert-success">
           <?php 
           if($_GET["success"] == "1") {
               echo "Client SOA record has been deleted successfully.";
           } elseif($_GET["success"] == "2") {
               echo "Client SOA record has been added successfully.";
           } elseif($_GET["success"] == "3") {
               echo "Client SOA record has been updated successfully.";
           } elseif($_GET["success"] == "4") {
               echo "Account has been closed successfully.";
           }
           ?>
       </div>
   <?php endif; ?>
   
   <?php if(isset($delete_err)): ?>
       <div class="alert alert-danger">
           <?php echo $delete_err; ?>
       </div>
   <?php endif; ?>
   
   <?php if(isset($close_err)): ?>
       <div class="alert alert-danger">
           <?php echo $close_err; ?>
       </div>
   <?php endif; ?>
   
   <div class="card shadow mb-4">
       <div class="card-header py-3">
           <h6 class="m-0 font-weight-bold text-primary">All Client SOAs</h6>
       </div>
       <div class="card-body">
           <div class="table-responsive">
               <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                   <thead>
                       <tr>
                           <th>Account #</th>
                           <th>Client</th>
                           <th>Issue Date</th>
                           <th>Due Date</th>
                           <th>Amount</th>
                           <th>Status</th>
                           <th>Actions</th>
                       </tr>
                   </thead>
                   <tbody>
                       <?php if(isset($soas) && !empty($soas)): ?>
                           <?php foreach($soas as $soa): ?>
                           <tr>
                               <td><?php echo htmlspecialchars($soa['account_number']); ?></td>
                               <td><?php echo htmlspecialchars($soa['client_name']); ?></td>
                               <td><?php echo htmlspecialchars($soa['issue_date']); ?></td>
                               <td><?php echo htmlspecialchars($soa['due_date']); ?></td>
                               <td>RM <?php echo number_format($soa['total_amount'], 2); ?></td>
                               <td>
                                   <span class="badge badge-<?php 
                                       echo ($soa['status'] == 'Paid') ? 'success' : 
                                           (($soa['status'] == 'Overdue') ? 'danger' : 
                                            (($soa['status'] == 'Closed') ? 'secondary' : 'warning')); 
                                   ?>">
                                       <?php echo htmlspecialchars($soa['status']); ?>
                                   </span>
                               </td>
                               <td>
                                   <a href="view.php?id=<?php echo $soa['soa_id']; ?>" class="btn btn-info btn-sm">
                                       <i class="fas fa-eye"></i>
                                   </a>
                                   <?php if($soa['status'] != 'Closed'): ?>
                                   <a href="edit.php?id=<?php echo $soa['soa_id']; ?>" class="btn btn-primary btn-sm">
                                       <i class="fas fa-edit"></i>
                                   </a>
                                   <?php endif; ?>
                                   <a href="generate_pdf.php?id=<?php echo $soa['soa_id']; ?>" class="btn btn-success btn-sm" target="_blank">
                                       <i class="fas fa-file-pdf"></i>
                                   </a>
                                   <?php if($soa['status'] != 'Closed'): ?>
                                   <a href="all_soas.php?action=delete&id=<?php echo $soa['soa_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this Client SOA?');">
                                       <i class="fas fa-trash"></i>
                                   </a>
                                   <a href="javascript:void(0);" class="btn btn-dark btn-sm" onclick="confirmCloseAccount(<?php echo $soa['soa_id']; ?>, '<?php echo $soa['status']; ?>')">
                                       <i class="fas fa-lock"></i>
                                   </a>
                                   <?php endif; ?>
                               </td>
                           </tr>
                           <?php endforeach; ?>
                       <?php else: ?>
                           <tr>
                               <td colspan="7" class="text-center">No Client SOA records found</td>
                           </tr>
                       <?php endif; ?>
                   </tbody>
               </table>
           </div>
       </div>
   </div>
</div>

<script>
function confirmCloseAccount(soaId, status) {
    let confirmMessage = '';
    
    if (status === 'Paid') {
        confirmMessage = 'Are you sure you want to close this account? This action cannot be undone.';
    } else if (status === 'Pending') {
        confirmMessage = 'WARNING: This account is still PENDING. Are you sure you want to close it? This action cannot be undone.';
    } else if (status === 'Overdue') {
        confirmMessage = 'WARNING: This account is OVERDUE. Are you sure you want to close it? This action cannot be undone.';
    }
    
    if (confirm(confirmMessage)) {
        window.location.href = `all_soas.php?action=close&id=${soaId}`;
    }
}
</script>

<?php
// Include footer
include_once $basePath . "includes/footer.php";
ob_end_flush();
?>
