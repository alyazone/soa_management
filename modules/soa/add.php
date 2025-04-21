<?php
ob_start();
// Set the base path for includes
$basePath = '../../';

// Include header and sidebar
include_once $basePath . "includes/header.php";
include_once $basePath . "includes/sidebar.php";

// Include database connection
require_once $basePath . "config/database.php";

// Define variables and initialize with empty values
$account_number = $client_id = $supplier_id = $terms = $purchase_date = $issue_date = "";
$po_number = $invoice_number = $description = $balance_amount = $status = "";
$account_number_err = $client_id_err = $supplier_id_err = $terms_err = $purchase_date_err = $issue_date_err = "";
$po_number_err = $invoice_number_err = $description_err = $balance_amount_err = $status_err = "";

// Pre-select supplier if coming from supplier page
$preselected_supplier = isset($_GET['supplier_id']) ? $_GET['supplier_id'] : '';

// Pre-select client if coming from client page
$preselected_client = isset($_GET['client_id']) ? $_GET['client_id'] : '';

// Fetch clients for dropdown
try {
    $stmt = $pdo->query("SELECT client_id, client_name FROM clients ORDER BY client_name");
    $clients = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Fetch suppliers for dropdown
try {
    $stmt = $pdo->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name");
    $suppliers = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate account number
    if(empty(trim($_POST["account_number"]))){
        $account_number_err = "Please enter account number.";
    } else{
        // Check if account number already exists
        $sql = "SELECT soa_id FROM soa WHERE account_number = :account_number";
        
        if($stmt = $pdo->prepare($sql)){
            $stmt->bindParam(":account_number", $param_account_number, PDO::PARAM_STR);
            $param_account_number = trim($_POST["account_number"]);
            
            if($stmt->execute()){
                if($stmt->rowCount() == 1){
                    $account_number_err = "This account number already exists.";
                } else{
                    $account_number = trim($_POST["account_number"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            
            unset($stmt);
        }
    }
    
    // Validate client
    if(empty($_POST["client_id"])){
        $client_id_err = "Please select client.";
    } else{
        $client_id = $_POST["client_id"];
    }
    
    // Validate supplier
    if(empty($_POST["supplier_id"])){
        $supplier_id_err = "Please select supplier.";
    } else{
        $supplier_id = $_POST["supplier_id"];
    }
    
    // Validate terms
    if(empty(trim($_POST["terms"]))){
        $terms_err = "Please enter terms.";
    } else{
        $terms = trim($_POST["terms"]);
    }
    
    // Validate purchase date
    if(empty($_POST["purchase_date"])){
        $purchase_date_err = "Please enter purchase date.";
    } else{
        $purchase_date = $_POST["purchase_date"];
    }
    
    // Validate issue date
    if(empty($_POST["issue_date"])){
        $issue_date_err = "Please enter issue date.";
    } else{
        $issue_date = $_POST["issue_date"];
    }
    
    // Validate PO number
    if(empty(trim($_POST["po_number"]))){
        $po_number_err = "Please enter PO number.";
    } else{
        $po_number = trim($_POST["po_number"]);
    }
    
    // Validate invoice number
    if(empty(trim($_POST["invoice_number"]))){
        $invoice_number_err = "Please enter invoice number.";
    } else{
        $invoice_number = trim($_POST["invoice_number"]);
    }
    
    // Validate description
    if(empty(trim($_POST["description"]))){
        $description_err = "Please enter description.";
    } else{
        $description = trim($_POST["description"]);
    }
    
    // Validate balance amount
    if(empty(trim($_POST["balance_amount"]))){
        $balance_amount_err = "Please enter balance amount.";
    } elseif(!is_numeric(trim($_POST["balance_amount"])) || floatval(trim($_POST["balance_amount"])) <= 0){
        $balance_amount_err = "Please enter a valid positive number.";
    } else{
        $balance_amount = trim($_POST["balance_amount"]);
    }
    
    // Validate status
    if(empty($_POST["status"])){
        $status_err = "Please select status.";
    } else{
        $status = $_POST["status"];
    }
    
    // Check input errors before inserting in database
    if(empty($account_number_err) && empty($client_id_err) && empty($supplier_id_err) && 
       empty($terms_err) && empty($purchase_date_err) && empty($issue_date_err) && 
       empty($po_number_err) && empty($invoice_number_err) && empty($description_err) && 
       empty($balance_amount_err) && empty($status_err)){
        
        // Prepare an insert statement
        $sql = "INSERT INTO soa (account_number, client_id, supplier_id, terms, purchase_date, issue_date, 
                po_number, invoice_number, description, balance_amount, status, created_by) 
                VALUES (:account_number, :client_id, :supplier_id, :terms, :purchase_date, :issue_date, 
                :po_number, :invoice_number, :description, :balance_amount, :status, :created_by)";
         
        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":account_number", $param_account_number, PDO::PARAM_STR);
            $stmt->bindParam(":client_id", $param_client_id, PDO::PARAM_INT);
            $stmt->bindParam(":supplier_id", $param_supplier_id, PDO::PARAM_INT);
            $stmt->bindParam(":terms", $param_terms, PDO::PARAM_STR);
            $stmt->bindParam(":purchase_date", $param_purchase_date, PDO::PARAM_STR);
            $stmt->bindParam(":issue_date", $param_issue_date, PDO::PARAM_STR);
            $stmt->bindParam(":po_number", $param_po_number, PDO::PARAM_STR);
            $stmt->bindParam(":invoice_number", $param_invoice_number, PDO::PARAM_STR);
            $stmt->bindParam(":description", $param_description, PDO::PARAM_STR);
            $stmt->bindParam(":balance_amount", $param_balance_amount, PDO::PARAM_STR);
            $stmt->bindParam(":status", $param_status, PDO::PARAM_STR);
            $stmt->bindParam(":created_by", $param_created_by, PDO::PARAM_INT);
            
            // Set parameters
            $param_account_number = $account_number;
            $param_client_id = $client_id;
            $param_supplier_id = $supplier_id;
            $param_terms = $terms;
            $param_purchase_date = $purchase_date;
            $param_issue_date = $issue_date;
            $param_po_number = $po_number;
            $param_invoice_number = $invoice_number;
            $param_description = $description;
            $param_balance_amount = $balance_amount;
            $param_status = $status;
            $param_created_by = $_SESSION["staff_id"];
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Get the last inserted ID
                $last_id = $pdo->lastInsertId();
                
                // Records created successfully. Redirect to view page
                header("location: view.php?id=" . $last_id);
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
        <h1 class="h2">Create New SOA</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="index.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">SOA Information</h6>
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
                <small class="text-muted">Basic information for the SOA record</small>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>
                            <span class="text-danger">*</span> Account Number
                        </label>
                        <input type="text" name="account_number" class="form-control <?php echo (!empty($account_number_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $account_number; ?>">
                        <span class="invalid-feedback"><?php echo $account_number_err; ?></span>
                    </div>
                    <div class="form-group col-md-6">
                        <label>
                            <span class="text-danger">*</span> Terms
                        </label>
                        <input type="text" name="terms" class="form-control <?php echo (!empty($terms_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $terms; ?>" placeholder="e.g., Net 30">
                        <span class="invalid-feedback"><?php echo $terms_err; ?></span>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>
                            <span class="text-danger">*</span> Client
                        </label>
                        <select name="client_id" class="form-control <?php echo (!empty($client_id_err)) ? 'is-invalid' : ''; ?>">
                            <option value="">Select Client</option>
                            <?php foreach($clients as $client): ?>
                                <option value="<?php echo $client['client_id']; ?>" <?php echo ($client['client_id'] == $preselected_client || $client['client_id'] == $client_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($client['client_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="invalid-feedback"><?php echo $client_id_err; ?></span>
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
                            <span class="text-danger">*</span> Purchase Date
                        </label>
                        <input type="date" name="purchase_date" class="form-control <?php echo (!empty($purchase_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $purchase_date; ?>">
                        <span class="invalid-feedback"><?php echo $purchase_date_err; ?></span>
                    </div>
                    <div class="form-group col-md-6">
                        <label>
                            <span class="text-danger">*</span> Issue Date
                        </label>
                        <input type="date" name="issue_date" class="form-control <?php echo (!empty($issue_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $issue_date; ?>">
                        <span class="invalid-feedback"><?php echo $issue_date_err; ?></span>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>
                            <span class="text-danger">*</span> PO Number
                        </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                            </div>
                            <input type="text" name="po_number" class="form-control <?php echo (!empty($po_number_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $po_number; ?>">
                            <span class="invalid-feedback"><?php echo $po_number_err; ?></span>
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <label>
                            <span class="text-danger">*</span> Invoice Number
                        </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-file-invoice"></i></span>
                            </div>
                            <input type="text" name="invoice_number" class="form-control <?php echo (!empty($invoice_number_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $invoice_number; ?>">
                            <span class="invalid-feedback"><?php echo $invoice_number_err; ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <span class="text-danger">*</span> Description
                    </label>
                    <textarea name="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>" rows="3"><?php echo $description; ?></textarea>
                    <span class="invalid-feedback"><?php echo $description_err; ?></span>
                    <small class="form-text text-muted">Provide a detailed description of the transaction</small>
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
                <small class="text-muted">Financial information for the SOA</small>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>
                            <span class="text-danger">*</span> Balance Amount (RM)
                        </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">RM</span>
                            </div>
                            <input type="text" name="balance_amount" class="form-control <?php echo (!empty($balance_amount_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $balance_amount; ?>">
                            <span class="invalid-feedback"><?php echo $balance_amount_err; ?></span>
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <label>
                            <span class="text-danger">*</span> Status
                        </label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="statusPending" value="Pending" <?php echo ($status == "Pending" || $status == "") ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="statusPending">
                                <span class="badge badge-warning">Pending</span>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="statusPaid" value="Paid" <?php echo ($status == "Paid") ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="statusPaid">
                                <span class="badge badge-success">Paid</span>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="statusOverdue" value="Overdue" <?php echo ($status == "Overdue") ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="statusOverdue">
                                <span class="badge badge-danger">Overdue</span>
                            </label>
                        </div>
                        <?php if (!empty($status_err)): ?>
                            <div class="text-danger small mt-1"><?php echo $status_err; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Form Actions -->
        <div class="form-group text-center">
            <button type="submit" class="btn btn-primary btn-lg px-5">
                <i class="fas fa-save mr-2"></i>Create SOA
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
