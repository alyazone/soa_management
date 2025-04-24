<?php
// Set the base path for includes
ob_start();
$basePath = '../../';

// Include header and sidebar
include_once $basePath . "includes/header.php";
include_once $basePath . "includes/sidebar.php";

// Include database connection
require_once $basePath . "config/database.php";

// Define variables and initialize with empty values
$description = $amount = "";
$description_err = $amount_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate description
    if(empty(trim($_POST["description"]))){
        $description_err = "Please enter a description.";
    } else{
        $description = trim($_POST["description"]);
    }
    
    // Validate amount
    if(empty(trim($_POST["amount"]))){
        $amount_err = "Please enter an amount.";
    } elseif(!is_numeric(trim($_POST["amount"])) || floatval(trim($_POST["amount"])) <= 0){
        $amount_err = "Please enter a valid positive number.";
    } else{
        $amount = trim($_POST["amount"]);
    }
    
    // Check input errors before inserting in database
    if(empty($description_err) && empty($amount_err)){
        // Prepare an insert statement
        $sql = "INSERT INTO claims (staff_id, description, amount, status) VALUES (:staff_id, :description, :amount, 'Pending')";
         
        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":staff_id", $param_staff_id, PDO::PARAM_INT);
            $stmt->bindParam(":description", $param_description, PDO::PARAM_STR);
            $stmt->bindParam(":amount", $param_amount, PDO::PARAM_STR);
            
            // Set parameters
            $param_staff_id = $_SESSION["staff_id"];
            $param_description = $description;
            $param_amount = $amount;
            
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
        <h1 class="h2">Submit New Claim</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="index.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Claim Information</h6>
        </div>
        <div class="card-body">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>" rows="5"><?php echo $description; ?></textarea>
                    <small class="form-text text-muted">Please provide a detailed description of your claim including purpose, date, and relevant details.</small>
                    <span class="invalid-feedback"><?php echo $description_err; ?></span>
                </div>
                
                <div class="form-group">
                    <label>Amount (RM)</label>
                    <input type="text" name="amount" class="form-control <?php echo (!empty($amount_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $amount; ?>">
                    <span class="invalid-feedback"><?php echo $amount_err; ?></span>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Your claim will be submitted with a "Pending" status and will be reviewed by management.
                </div>
                
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Submit Claim">
                    <a href="index.php" class="btn btn-secondary ml-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Supporting Documents</h6>
        </div>
        <div class="card-body">
            <p>After submitting your claim, you can upload supporting documents such as receipts or invoices from the claim details page.</p>
            <p>Supported file types: PDF, DOC, DOCX, JPG, JPEG, PNG.</p>
        </div>
    </div>
</div>

<?php
// Include footer
include_once $basePath . "includes/footer.php";
ob_end_flush();
?>
