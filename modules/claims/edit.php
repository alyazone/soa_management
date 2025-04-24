<?php
ob_start();
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

// Define variables and initialize with empty values
$description = $amount = "";
$description_err = $amount_err = "";

// Fetch claim data
try {
    $stmt = $pdo->prepare("SELECT * FROM claims WHERE claim_id = :id");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    
    if($stmt->rowCount() != 1){
        header("location: index.php");
        exit();
    }
    
    $claim = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if user has permission to edit this claim
    if($claim['staff_id'] != $_SESSION['staff_id'] && $_SESSION['position'] != 'Admin' && $_SESSION['position'] != 'Manager'){
        echo '<div class="col-md-10 ml-sm-auto px-4"><div class="alert alert-danger mt-3">You do not have permission to edit this claim.</div></div>';
        include_once $basePath . "includes/footer.php";
        exit;
    }
    
    // Check if claim is already processed
    if($claim['status'] != 'Pending'){
        echo '<div class="col-md-10 ml-sm-auto px-4"><div class="alert alert-danger mt-3">This claim has already been processed and cannot be edited.</div></div>';
        include_once $basePath . "includes/footer.php";
        exit;
    }
    
    // Set values
    $description = $claim['description'];
    $amount = $claim['amount'];
    
} catch(PDOException $e) {
    die("ERROR: Could not fetch claim. " . $e->getMessage());
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Get hidden input value
    $id = $_POST["id"];
    
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
    
    // Check input errors before updating the database
    if(empty($description_err) && empty($amount_err)){
        // Prepare an update statement
        $sql = "UPDATE claims SET description = :description, amount = :amount WHERE claim_id = :id";
        
        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":description", $param_description, PDO::PARAM_STR);
            $stmt->bindParam(":amount", $param_amount, PDO::PARAM_STR);
            $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
            
            // Set parameters
            $param_description = $description;
            $param_amount = $amount;
            $param_id = $id;
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Records updated successfully. Redirect to view page
                header("location: view.php?id=" . $id);
                exit();
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
        }
        
        // Close statement
        unset($stmt);
    }
}
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Edit Claim</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="view.php?id=<?php echo $_GET['id']; ?>" class="btn btn-sm btn-info mr-2">
                <i class="fas fa-eye"></i> View Claim
            </a>
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
                <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
                
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
                    <i class="fas fa-info-circle"></i> Your claim will remain in "Pending" status after editing and will be reviewed by management.
                </div>
                
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Update Claim">
                    <a href="index.php" class="btn btn-secondary ml-2">Cancel</a>
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
