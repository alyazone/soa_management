<?php
ob_start();
// Set the base path for includes
$basePath = '../../';

// Include header and sidebar
include_once $basePath . "includes/header.php";
include_once $basePath . "includes/sidebar.php";

// Include database connection
require_once $basePath . "config/database.php";

// Process delete operation
if(isset($_GET["action"]) && $_GET["action"] == "delete" && isset($_GET["id"]) && !empty($_GET["id"])){
    try {
        // Prepare a delete statement
        $sql = "DELETE FROM claims WHERE claim_id = :id";
        
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

// Fetch all claims with staff names
try {
    $stmt = $pdo->query("SELECT c.*, s.full_name as staff_name, p.full_name as processor_name 
                         FROM claims c 
                         JOIN staff s ON c.staff_id = s.staff_id 
                         LEFT JOIN staff p ON c.processed_by = p.staff_id 
                         ORDER BY c.submitted_date DESC");
    $claims = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Claims Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="add.php" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> Submit New Claim
            </a>
        </div>
    </div>
    
    <?php if(isset($_GET["success"]) && $_GET["success"] == "1"): ?>
        <div class="alert alert-success">
            Claim has been deleted successfully.
        </div>
    <?php endif; ?>
    
    <?php if(isset($_GET["processed"]) && $_GET["processed"] == "1"): ?>
        <div class="alert alert-success">
            Claim has been processed successfully.
        </div>
    <?php endif; ?>
    
    <?php if(isset($delete_err)): ?>
        <div class="alert alert-danger">
            <?php echo $delete_err; ?>
        </div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Claims List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Staff</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Submitted Date</th>
                            <th>Status</th>
                            <th>Processed By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($claims as $claim): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($claim['claim_id']); ?></td>
                            <td><?php echo htmlspecialchars($claim['staff_name']); ?></td>
                            <td><?php echo htmlspecialchars(substr($claim['description'], 0, 50)) . (strlen($claim['description']) > 50 ? '...' : ''); ?></td>
                            <td>RM <?php echo number_format($claim['amount'], 2); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($claim['submitted_date'])); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo ($claim['status'] == 'Approved') ? 'success' : 
                                        (($claim['status'] == 'Rejected') ? 'danger' : 'warning'); 
                                ?>">
                                    <?php echo htmlspecialchars($claim['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $claim['processor_name'] ? htmlspecialchars($claim['processor_name']) : 'N/A'; ?></td>
                            <td>
                                <a href="view.php?id=<?php echo $claim['claim_id']; ?>" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if($claim['status'] == 'Pending'): ?>
                                    <?php if($_SESSION['staff_id'] == $claim['staff_id'] || $_SESSION['position'] == 'Admin' || $_SESSION['position'] == 'Manager'): ?>
                                        <a href="edit.php?id=<?php echo $claim['claim_id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if($_SESSION['position'] == 'Admin' || $_SESSION['position'] == 'Manager'): ?>
                                        <a href="process.php?id=<?php echo $claim['claim_id']; ?>" class="btn btn-success btn-sm">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if($_SESSION['staff_id'] == $claim['staff_id'] || $_SESSION['position'] == 'Admin'): ?>
                                    <a href="index.php?action=delete&id=<?php echo $claim['claim_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this claim?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($claims)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No claims found</td>
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
