<?php
// Set the base path for includes
$basePath = '../../';

// Include header and sidebar
include_once $basePath . "includes/header.php";
include_once $basePath . "includes/sidebar.php";

// Check if user has admin or manager privileges
if($_SESSION['position'] != 'Admin' && $_SESSION['position'] != 'Manager'){
    header("location: index.php");
    exit;
}

// Include database connection
require_once $basePath . "config/database.php";

// Check if id parameter is set
if(empty($_GET["id"])){
    header("location: index.php");
    exit();
}

// Define variables and initialize with empty values
$status = $processed_date = "";
$status_err = "";

// Fetch claim data
try {
    $stmt = $pdo->prepare("SELECT c.*, s.full_name as staff_name 
                           FROM claims c 
                           JOIN staff s ON c.staff_id = s.staff_id 
                           WHERE c.claim_id = :id");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    
    if($stmt->rowCount() != 1){
        header("location: index.php");
        exit();
    }
    
    $claim = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if claim is already processed
    if($claim['status'] != 'Pending'){
        header("location: index.php");
        exit();
    }
    
} catch(PDOException $e) {
    die("ERROR: Could not fetch claim. " . $e->getMessage());
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate status
    if(empty($_POST["status"])){
        $status_err = "Please select status.";
    } else{
        $status = $_POST["status"];
    }
    
    // Check input errors before updating in database
    if(empty($status_err)){
        try {
            // Prepare an update statement
            $sql = "UPDATE claims SET status = :status, processed_date = NOW(), processed_by = :processed_by WHERE claim_id = :id";
            
            if($stmt = $pdo->prepare($sql)){
                // Bind variables to the prepared statement as parameters
                $stmt->bindParam(":status", $param_status, PDO::PARAM_STR);
                $stmt->bindParam(":processed_by", $param_processed_by, PDO::PARAM_INT);
                $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
                
                // Set parameters
                $param_status = $status;
                $param_processed_by = $_SESSION["staff_id"];
                $param_id = $claim["claim_id"];
                
                // Attempt to execute the prepared statement
                if($stmt->execute()){
                    // Records updated successfully. Redirect to landing page
                    header("location: index.php?processed=1");
                    exit();
                } else{
                    echo "Oops! Something went wrong. Please try again later.";
                }
            }
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Process Claim</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="index.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Claim Information</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr>
                                <th>Claim ID</th>
                                <td><?php echo htmlspecialchars($claim['claim_id']); ?></td>
                            </tr>
                            <tr>
                                <th>Staff</th>
                                <td><?php echo htmlspecialchars($claim['staff_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td><?php echo nl2br(htmlspecialchars($claim['description'])); ?></td>
                            </tr>
                            <tr>
                                <th>Amount</th>
                                <td>RM <?php echo number_format($claim['amount'], 2); ?></td>
                            </tr>
                            <tr>
                                <th>Submitted Date</th>
                                <td><?php echo date('Y-m-d H:i', strtotime($claim['submitted_date'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Process Claim</h6>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $claim['claim_id']; ?>" method="post">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control <?php echo (!empty($status_err)) ? 'is-invalid' : ''; ?>">
                                <option value="">Select Status</option>
                                <option value="Approved" <?php echo ($status == "Approved") ? 'selected' : ''; ?>>Approved</option>
                                <option value="Rejected" <?php echo ($status == "Rejected") ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                            <span class="invalid-feedback"><?php echo $status_err; ?></span>
                        </div>
                        <div class="form-group">
                            <input type="submit" class="btn btn-primary" value="Process">
                            <a href="index.php" class="btn btn-secondary ml-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Related Documents -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Related Documents</h6>
                </div>
                <div class="card-body">
                    <?php
                    // Fetch related documents
                    try {
                        $stmt = $pdo->prepare("SELECT * FROM documents WHERE reference_type = 'Staff' AND reference_id = :staff_id AND document_type = 'Claim'");
                        $stmt->bindParam(":staff_id", $claim['staff_id'], PDO::PARAM_INT);
                        $stmt->execute();
                        $documents = $stmt->fetchAll();
                    } catch(PDOException $e) {
                        echo "Error: " . $e->getMessage();
                    }
                    ?>
                    
                    <?php if(!empty($documents)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Upload Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($documents as $document): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($document['file_name']); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($document['upload_date'])); ?></td>
                                        <td>
                                            <a href="<?php echo $basePath . $document['file_path']; ?>" class="btn btn-info btn-sm" target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No related documents found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once $basePath . "includes/footer.php";
?>
