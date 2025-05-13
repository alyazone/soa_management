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

// Define variables and initialize with empty values
$invoice_number = $supplier_id = $issue_date = $payment_due_date = $purchase_description = $amount = $payment_status = $payment_method = "";
$invoice_number_err = $supplier_id_err = $issue_date_err = $payment_due_date_err = $purchase_description_err = $amount_err = $payment_status_err = $payment_method_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate invoice number
    $input_invoice_number = trim($_POST["invoice_number"]);
    if(empty($input_invoice_number)){
        $invoice_number_err = "Please enter an invoice number.";
    } else{
        $invoice_number = $input_invoice_number;
    }
    
    // Validate supplier
    $input_supplier_id = trim($_POST["supplier_id"]);
    if(empty($input_supplier_id)){
        $supplier_id_err = "Please select a supplier.";
    } else{
        $supplier_id = $input_supplier_id;
    }
    
    // Validate issue date
    $input_issue_date = trim($_POST["issue_date"]);
    if(empty($input_issue_date)){
        $issue_date_err = "Please enter the issue date.";
    } else{
        $issue_date = $input_issue_date;
    }
    
    // Validate payment due date
    $input_payment_due_date = trim($_POST["payment_due_date"]);
    if(empty($input_payment_due_date)){
        $payment_due_date_err = "Please enter the payment due date.";
    } else{
        $payment_due_date = $input_payment_due_date;
    }
    
    // Validate purchase description
    $input_purchase_description = trim($_POST["purchase_description"]);
    if(empty($input_purchase_description)){
        $purchase_description_err = "Please enter a purchase description.";
    } else{
        $purchase_description = $input_purchase_description;
    }
    
    // Validate amount
    $input_amount = trim($_POST["amount"]);
    if(empty($input_amount)){
        $amount_err = "Please enter the amount.";
    } elseif(!is_numeric($input_amount) || $input_amount <= 0){
        $amount_err = "Please enter a positive amount.";
    } else{
        $amount = $input_amount;
    }
    
    // Validate payment status
    $input_payment_status = trim($_POST["payment_status"]);
    if(empty($input_payment_status)){
        $payment_status_err = "Please select a payment status.";
    } else{
        $payment_status = $input_payment_status;
    }
    
    // Validate payment method
    $input_payment_method = trim($_POST["payment_method"]);
    $payment_method = $input_payment_method; // Optional field
    
    // Check input errors before inserting in database
    if(empty($invoice_number_err) && empty($supplier_id_err) && empty($issue_date_err) && empty($payment_due_date_err) && empty($purchase_description_err) && empty($amount_err) && empty($payment_status_err)){
        // Prepare an update statement
        $sql = "UPDATE supplier_soa SET invoice_number=:invoice_number, supplier_id=:supplier_id, issue_date=:issue_date, payment_due_date=:payment_due_date, purchase_description=:purchase_description, amount=:amount, payment_status=:payment_status, payment_method=:payment_method WHERE soa_id=:soa_id";
        
        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":invoice_number", $param_invoice_number);
            $stmt->bindParam(":supplier_id", $param_supplier_id);
            $stmt->bindParam(":issue_date", $param_issue_date);
            $stmt->bindParam(":payment_due_date", $param_payment_due_date);
            $stmt->bindParam(":purchase_description", $param_purchase_description);
            $stmt->bindParam(":amount", $param_amount);
            $stmt->bindParam(":payment_status", $param_payment_status);
            $stmt->bindParam(":payment_method", $param_payment_method);
            $stmt->bindParam(":soa_id", $param_soa_id);
            
            // Set parameters
            $param_invoice_number = $invoice_number;
            $param_supplier_id = $supplier_id;
            $param_issue_date = $issue_date;
            $param_payment_due_date = $payment_due_date;
            $param_purchase_description = $purchase_description;
            $param_amount = $amount;
            $param_payment_status = $payment_status;
            $param_payment_method = $payment_method;
            $param_soa_id = $_GET["id"];
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Records updated successfully. Redirect to landing page
                header("location: index.php?success=3");
                exit();
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
        }
        
        // Close statement
        unset($stmt);
    }
    
    // Close connection
    unset($pdo);
} else {
    // Check existence of id parameter before processing further
    if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
        // Get URL parameter
        $soa_id =  trim($_GET["id"]);
        
        // Prepare a select statement
        $sql = "SELECT * FROM supplier_soa WHERE soa_id = :soa_id";
        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":soa_id", $param_soa_id);
            
            // Set parameters
            $param_soa_id = $soa_id;
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                if($stmt->rowCount() == 1){
                    /* Fetch result row as an associative array. Since the result set
                    contains only one row, we don't need to use while loop */
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Retrieve individual field value
                    $invoice_number = $row["invoice_number"];
                    $supplier_id = $row["supplier_id"];
                    $issue_date = $row["issue_date"];
                    $payment_due_date = $row["payment_due_date"];
                    $purchase_description = $row["purchase_description"];
                    $amount = $row["amount"];
                    $payment_status = $row["payment_status"];
                    $payment_method = $row["payment_method"];
                } else{
                    // URL doesn't contain valid id. Redirect to error page
                    header("location: error.php");
                    exit();
                }
                
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
        }
        
        // Close statement
        unset($stmt);
    } else{
        // URL doesn't contain id parameter. Redirect to error page
        header("location: error.php");
        exit();
    }
}

// Fetch all suppliers for dropdown
try {
    $stmt = $pdo->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name");
    $suppliers = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Edit Supplier SOA</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="index.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Edit Supplier SOA Details</h6>
        </div>
        <div class="card-body">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $soa_id; ?>" method="post">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Invoice Number</label>
                        <input type="text" name="invoice_number" class="form-control <?php echo (!empty($invoice_number_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $invoice_number; ?>">
                        <span class="invalid-feedback"><?php echo $invoice_number_err;?></span>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Supplier</label>
                        <select name="supplier_id" class="form-control <?php echo (!empty($supplier_id_err)) ? 'is-invalid' : ''; ?>">
                            <option value="">Select Supplier</option>
                            <?php foreach($suppliers as $supplier): ?>
                                <option value="<?php echo $supplier['supplier_id']; ?>" <?php echo ($supplier_id == $supplier['supplier_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="invalid-feedback"><?php echo $supplier_id_err;?></span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Issue Date</label>
                        <input type="date" name="issue_date" class="form-control <?php echo (!empty($issue_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $issue_date; ?>">
                        <span class="invalid-feedback"><?php echo $issue_date_err;?></span>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Payment Due Date</label>
                        <input type="date" name="payment_due_date" class="form-control <?php echo (!empty($payment_due_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $payment_due_date; ?>">
                        <span class="invalid-feedback"><?php echo $payment_due_date_err;?></span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Purchase Description</label>
                    <textarea name="purchase_description" class="form-control <?php echo (!empty($purchase_description_err)) ? 'is-invalid' : ''; ?>"><?php echo $purchase_description; ?></textarea>
                    <span class="invalid-feedback"><?php echo $purchase_description_err;?></span>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Amount (RM)</label>
                        <input type="number" step="0.01" name="amount" class="form-control <?php echo (!empty($amount_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $amount; ?>">
                        <span class="invalid-feedback"><?php echo $amount_err;?></span>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Payment Status</label>
                        <select name="payment_status" class="form-control <?php echo (!empty($payment_status_err)) ? 'is-invalid' : ''; ?>">
                            <option value="Pending" <?php echo ($payment_status == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="Paid" <?php echo ($payment_status == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                            <option value="Overdue" <?php echo ($payment_status == 'Overdue') ? 'selected' : ''; ?>>Overdue</option>
                        </select>
                        <span class="invalid-feedback"><?php echo $payment_status_err;?></span>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Payment Method</label>
                        <input type="text" name="payment_method" class="form-control" value="<?php echo $payment_method; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Update">
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Include footer
include_once $basePath . "includes/footer.php";
ob_end_flush();
?>
