<?php
ob_start();
// Set the base path for includes
$basePath = '../../../';

// Include header and sidebar
include_once $basePath . "includes/header.php";
include_once $basePath . "includes/sidebar.php";

// Include database connection
require_once $basePath . "config/database.php";

// Define variables and initialize with empty values
$invoice_number = $supplier_id = $issue_date = $payment_due_date = "";
$purchase_description = $amount = $payment_status = $payment_method = "";
$invoice_number_err = $supplier_id_err = $issue_date_err = $payment_due_date_err = "";
$purchase_description_err = $amount_err = $payment_status_err = "";

// Pre-select supplier if coming from supplier page
$preselected_supplier = isset($_GET['supplier_id']) ? $_GET['supplier_id'] : '';

// Fetch suppliers for dropdown
try {
    $stmt = $pdo->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name");
    $suppliers = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate invoice number
    if(empty(trim($_POST["invoice_number"]))){
        $invoice_number_err = "Please enter invoice number.";
    } else{
        // Check if invoice number already exists
        $sql = "SELECT soa_id FROM supplier_soa WHERE invoice_number = :invoice_number";
        
        if($stmt = $pdo->prepare($sql)){
            $stmt->bindParam(":invoice_number", $param_invoice_number, PDO::PARAM_STR);
            $param_invoice_number = trim($_POST["invoice_number"]);
            
            if($stmt->execute()){
                if($stmt->rowCount() == 1){
                    $invoice_number_err = "This invoice number already exists.";
                } else{
                    $invoice_number = trim($_POST["invoice_number"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            
            unset($stmt);
        }
    }
    
    // Validate supplier
    if(empty($_POST["supplier_id"])){
        $supplier_id_err = "Please select supplier.";
    } else{
        $supplier_id = $_POST["supplier_id"];
    }
    
    // Validate issue date
    if(empty($_POST["issue_date"])){
        $issue_date_err = "Please enter issue date.";
    } else{
        $issue_date = $_POST["issue_date"];
    }
    
    // Validate payment due date
    if(empty($_POST["payment_due_date"])){
        $payment_due_date_err = "Please enter payment due date.";
    } else{
        $payment_due_date = $_POST["payment_due_date"];
    }
    
    // Validate purchase description
    if(empty(trim($_POST["purchase_description"]))){
        $purchase_description_err = "Please enter purchase description.";
    } else{
        $purchase_description = trim($_POST["purchase_description"]);
    }
    
    // Validate amount
    if(empty(trim($_POST["amount"]))){
        $amount_err = "Please enter amount.";
    } elseif(!is_numeric(trim($_POST["amount"])) || floatval(trim($_POST["amount"])) <= 0){
        $amount_err = "Please enter a valid positive number.";
    } else{
        $amount = trim($_POST["amount"]);
    }
    
    // Validate payment status
    if(empty($_POST["payment_status"])){
        $payment_status_err = "Please select payment status.";
    } else{
        $payment_status = $_POST["payment_status"];
    }
    
    // Get payment method if provided
    $payment_method = !empty($_POST["payment_method"]) ? trim($_POST["payment_method"]) : NULL;
    
    // Check input errors before inserting in database
    if(empty($invoice_number_err) && empty($supplier_id_err) && empty($issue_date_err) && 
       empty($payment_due_date_err) && empty($purchase_description_err) && empty($amount_err) && 
       empty($payment_status_err)){
        
        // Prepare an insert statement
        $sql = "INSERT INTO supplier_soa (invoice_number, supplier_id, issue_date, payment_due_date, 
                purchase_description, amount, payment_status, payment_method, created_by) 
                VALUES (:invoice_number, :supplier_id, :issue_date, :payment_due_date, 
                :purchase_description, :amount, :payment_status, :payment_method, :created_by)";
         
        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":invoice_number", $param_invoice_number, PDO::PARAM_STR);
            $stmt->bindParam(":supplier_id", $param_supplier_id, PDO::PARAM_INT);
            $stmt->bindParam(":issue_date", $param_issue_date, PDO::PARAM_STR);
            $stmt->bindParam(":payment_due_date", $param_payment_due_date, PDO::PARAM_STR);
            $stmt->bindParam(":purchase_description", $param_purchase_description, PDO::PARAM_STR);
            $stmt->bindParam(":amount", $param_amount, PDO::PARAM_STR);
            $stmt->bindParam(":payment_status", $param_payment_status, PDO::PARAM_STR);
            $stmt->bindParam(":payment_method", $param_payment_method, PDO::PARAM_STR);
            $stmt->bindParam(":created_by", $param_created_by, PDO::PARAM_INT);
            
            // Set parameters
            $param_invoice_number = $invoice_number;
            $param_supplier_id = $supplier_id;
            $param_issue_date = $issue_date;
            $param_payment_due_date = $payment_due_date;
            $param_purchase_description = $purchase_description;
            $param_amount = $amount;
            $param_payment_status = $payment_status;
            $param_payment_method = $payment_method;
            $param_created_by = $_SESSION["staff_id"];
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Records created successfully. Redirect to index page
                header("location: index.php?success=2");
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
}
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Create New Supplier SOA</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="index.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Supplier SOA Information</h6>
        </div>
        <div class="card-body">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <!-- Basic Information Section -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-info-circle mr-2"></i>Basic Information
                            <span class="badge badge-warning ml-2">Required</span>
                        </h6>
                        <small class="text-muted">Basic information for the Supplier SOA record</small>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>
                                    <span class="text-danger">*</span> Invoice Number
                                </label>
                                <input type="text" name="invoice_number" class="form-control <?php echo (!empty($invoice_number_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $invoice_number; ?>">
                                <span class="invalid-feedback"><?php echo $invoice_number_err; ?></span>
                            </div>
                            <div class="form-group col-md-6">
                                <label>
                                    <span class="text-danger">*</span> Supplier
                                </label>
                                <select name="supplier_id" class="form-control <?php echo (!empty($supplier_id_err)) ? 'is-invalid' : ''; ?>">
                                    <option value="">Select Supplier</option>
                                    <?php foreach($suppliers as $supplier): ?>
                                        <option value="<?php echo $supplier['supplier_id']; ?>" <?php echo ($supplier['supplier_id'] == $preselected_supplier || $supplier['supplier_id'] == $supplier_id) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="invalid-feedback"><?php echo $supplier_id_err; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Transaction Details Section -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-file-invoice-dollar mr-2"></i>Transaction Details
                            <span class="badge badge-warning ml-2">Required</span>
                        </h6>
                        <small class="text-muted">Details about the transaction</small>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>
                                    <span class="text-danger">*</span> Issue Date
                                </label>
                                <input type="date" name="issue_date" class="form-control <?php echo (!empty($issue_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $issue_date; ?>">
                                <span class="invalid-feedback"><?php echo $issue_date_err; ?></span>
                            </div>
                            <div class="form-group col-md-6">
                                <label>
                                    <span class="text-danger">*</span> Payment Due Date
                                </label>
                                <input type="date" name="payment_due_date" class="form-control <?php echo (!empty($payment_due_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $payment_due_date; ?>">
                                <span class="invalid-feedback"><?php echo $payment_due_date_err; ?></span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <span class="text-danger">*</span> Purchase Description
                            </label>
                            <textarea name="purchase_description" class="form-control <?php echo (!empty($purchase_description_err)) ? 'is-invalid' : ''; ?>" rows="3"><?php echo $purchase_description; ?></textarea>
                            <span class="invalid-feedback"><?php echo $purchase_description_err; ?></span>
                            <small class="form-text text-muted">Provide a detailed description of the purchase</small>
                        </div>
                    </div>
                </div>
                
                <!-- Financial Details Section -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-money-bill-wave mr-2"></i>Financial Details
                            <span class="badge badge-warning ml-2">Required</span>
                        </h6>
                        <small class="text-muted">Financial information for the Supplier SOA</small>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>
                                    <span class="text-danger">*</span> Amount (RM)
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">RM</span>
                                    </div>
                                    <input type="text" name="amount" class="form-control <?php echo (!empty($amount_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $amount; ?>">
                                    <span class="invalid-feedback"><?php echo $amount_err; ?></span>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label>
                                    <span class="text-danger">*</span> Payment Status
                                </label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_status" id="statusPending" value="Pending" <?php echo ($payment_status == "Pending" || $payment_status == "") ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="statusPending">
                                        <span class="badge badge-warning">Pending</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_status" id="statusPaid" value="Paid" <?php echo ($payment_status == "Paid") ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="statusPaid">
                                        <span class="badge badge-success">Paid</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_status" id="statusOverdue" value="Overdue" <?php echo ($payment_status == "Overdue") ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="statusOverdue">
                                        <span class="badge badge-danger">Overdue</span>
                                    </label>
                                </div>
                                <?php if (!empty($payment_status_err)): ?>
                                    <div class="text-danger small mt-1"><?php echo $payment_status_err; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Payment Method (if paid)</label>
                            <select name="payment_method" class="form-control">
                                <option value="">Select Payment Method</option>
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Check">Check</option>
                                <option value="Credit Card">Credit Card</option>
                            </select>
                            <small class="form-text text-muted">Optional - only required if payment status is "Paid"</small>
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="form-group text-center">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-save mr-2"></i>Create Supplier SOA
                    </button>
                    <a href="index.php" class="btn btn-secondary btn-lg ml-2 px-5">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
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
