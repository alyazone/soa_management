<?php
// Set the base path for includes
$basePath = '../../';
ob_start();

// Include header and sidebar
include_once $basePath . "includes/header.php";
include_once $basePath . "includes/sidebar.php";

// Include database connection
require_once $basePath . "config/database.php";

// Check if user has admin/manager privileges
if($_SESSION['position'] != 'Admin' && $_SESSION['position'] != 'Manager'){
    echo '<div class="col-md-10 ml-sm-auto px-4"><div class="alert alert-danger mt-3">You do not have permission to perform this action.</div></div>';
    include_once $basePath . "includes/footer.php";
    exit;
}

// Check if id and status parameters are set
if(empty($_GET["id"]) || empty($_GET["status"])){
    header("location: index.php");
    exit();
}

$claim_id = $_GET["id"];
$new_status = $_GET["status"];
$rejection_reason = "";
$payment_details = "";
$error_message = "";

// Validate status
$valid_statuses = ['Pending', 'Approved', 'Rejected', 'Paid'];
if(!in_array($new_status, $valid_statuses)){
    header("location: index.php");
    exit();
}

// Fetch claim data
try {
    $stmt = $pdo->prepare("SELECT * FROM claims WHERE claim_id = :id");
    $stmt->bindParam(":id", $claim_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if($stmt->rowCount() != 1){
        header("location: index.php");
        exit();
    }
    
    $claim = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("ERROR: Could not fetch claim. " . $e->getMessage());
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Get form data
    $new_status = $_POST["status"];
    
    if($new_status == "Rejected"){
        // Validate rejection reason
        if(empty(trim($_POST["rejection_reason"]))){
            $error_message = "Please provide a reason for rejection.";
        } else {
            $rejection_reason = trim($_POST["rejection_reason"]);
        }
    }
    
    if($new_status == "Paid"){
        // Validate payment details
        if(empty(trim($_POST["payment_details"]))){
            $error_message = "Please provide payment details.";
        } else {
            $payment_details = trim($_POST["payment_details"]);
        }
    }
    
    // Update claim status if no errors
    if(empty($error_message)){
        try {
            // Check if approval columns exist in the table
            $columnCheckStmt = $pdo->prepare("SHOW COLUMNS FROM claims LIKE 'approved_by'");
            $columnCheckStmt->execute();
            $approvalColumnsExist = $columnCheckStmt->rowCount() > 0;
            
            // Prepare update statement based on column existence
            if($approvalColumnsExist) {
                if($new_status == "Rejected"){
                    $sql = "UPDATE claims SET status = :status, rejection_reason = :rejection_reason, 
                            approved_by = :approved_by, approval_signature = 1, approval_date = CURDATE() 
                            WHERE claim_id = :id";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(":rejection_reason", $rejection_reason, PDO::PARAM_STR);
                    $stmt->bindParam(":approved_by", $_SESSION["username"], PDO::PARAM_STR);
                    
                } else if($new_status == "Paid"){
                    $sql = "UPDATE claims SET status = :status, payment_details = :payment_details, 
                            approved_by = :approved_by, approval_signature = 1, approval_date = CURDATE() 
                            WHERE claim_id = :id";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(":payment_details", $payment_details, PDO::PARAM_STR);
                    $stmt->bindParam(":approved_by", $_SESSION["username"], PDO::PARAM_STR);
                    
                } else {
                    $sql = "UPDATE claims SET status = :status, approved_by = :approved_by, 
                            approval_signature = 1, approval_date = CURDATE() 
                            WHERE claim_id = :id";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(":approved_by", $_SESSION["username"], PDO::PARAM_STR);
                }
            } else {
                // Simplified update if approval columns don't exist
                if($new_status == "Rejected"){
                    $sql = "UPDATE claims SET status = :status WHERE claim_id = :id";
                } else if($new_status == "Paid"){
                    $sql = "UPDATE claims SET status = :status WHERE claim_id = :id";
                } else {
                    $sql = "UPDATE claims SET status = :status WHERE claim_id = :id";
                }
                
                $stmt = $pdo->prepare($sql);
            }
            
            // Bind common parameters
            $stmt->bindParam(":status", $new_status, PDO::PARAM_STR);
            $stmt->bindParam(":id", $claim_id, PDO::PARAM_INT);
            
            // Execute the statement
            if($stmt->execute()){
                // Redirect to view page
                header("location: view.php?id=" . $claim_id);
                exit();
            } else {
                $error_message = "Something went wrong. Please try again later.";
            }
            
        } catch(PDOException $e) {
            $error_message = "ERROR: Could not execute query. " . $e->getMessage();
        }
    }
}
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Update Claim Status</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="view.php?id=<?php echo $claim_id; ?>" class="btn btn-sm btn-info mr-2">
                <i class="fas fa-eye"></i> View Claim
            </a>
            <a href="index.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Change Status for Claim #<?php echo $claim_id; ?></h6>
        </div>
        <div class="card-body">
            <?php if(!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $claim_id . "&status=" . $new_status; ?>" method="post">
                <div class="form-group">
                    <label>Current Status</label>
                    <input type="text" class="form-control" value="<?php echo $claim['status']; ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label>New Status</label>
                    <select name="status" class="form-control" id="statusSelect">
                        <option value="Pending" <?php echo ($new_status == "Pending") ? 'selected' : ''; ?>>Pending</option>
                        <option value="Approved" <?php echo ($new_status == "Approved") ? 'selected' : ''; ?>>Approved</option>
                        <option value="Rejected" <?php echo ($new_status == "Rejected") ? 'selected' : ''; ?>>Rejected</option>
                        <option value="Paid" <?php echo ($new_status == "Paid") ? 'selected' : ''; ?>>Paid</option>
                    </select>
                </div>
                
                <div id="rejectionReasonDiv" class="form-group" style="display: <?php echo ($new_status == 'Rejected') ? 'block' : 'none'; ?>">
                    <label>Rejection Reason</label>
                    <textarea name="rejection_reason" class="form-control" rows="3"><?php echo $rejection_reason; ?></textarea>
                    <small class="form-text text-muted">Please provide a reason for rejecting this claim.</small>
                </div>
                
                <div id="paymentDetailsDiv" class="form-group" style="display: <?php echo ($new_status == 'Paid') ? 'block' : 'none'; ?>">
                    <label>Payment Details</label>
                    <textarea name="payment_details" class="form-control" rows="3"><?php echo $payment_details; ?></textarea>
                    <small class="form-text text-muted">Please provide payment details such as payment date, reference number, etc.</small>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Changing the status will update the claim record accordingly.
                </div>
                
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Update Status">
                    <a href="view.php?id=<?php echo $claim_id; ?>" class="btn btn-secondary ml-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Show/hide additional fields based on status
    $("#statusSelect").change(function() {
        var selectedStatus = $(this).val();
        
        if(selectedStatus == "Rejected") {
            $("#rejectionReasonDiv").show();
            $("#paymentDetailsDiv").hide();
        } else if(selectedStatus == "Paid") {
            $("#rejectionReasonDiv").hide();
            $("#paymentDetailsDiv").show();
        } else {
            $("#rejectionReasonDiv").hide();
            $("#paymentDetailsDiv").hide();
        }
    });
});
</script>

<?php
// Include footer
include_once $basePath . "includes/footer.php";
ob_end_flush();
?>
