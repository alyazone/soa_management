<?php
// Start output buffering to prevent "headers already sent" errors
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
$item_id = $_GET["id"];
$transaction_type = isset($_GET["type"]) ? $_GET["type"] : "";
$from_status = $to_status = $assigned_to = $notes = "";
$transaction_type_err = $to_status_err = $assigned_to_err = "";

// Fetch item data
try {
    $stmt = $pdo->prepare("SELECT i.*, c.category_name, s.supplier_name 
                          FROM inventory_items i 
                          LEFT JOIN inventory_categories c ON i.category_id = c.category_id 
                          LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id 
                          WHERE i.item_id = :id");
    $stmt->bindParam(":id", $item_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if($stmt->rowCount() != 1){
        header("location: index.php");
        exit();
    }
    
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    $from_status = $item['status']; // Current status becomes from_status
    
} catch(PDOException $e) {
    die("ERROR: Could not fetch item. " . $e->getMessage());
}

// Fetch staff for dropdown (for assignment)
try {
    $stmt = $pdo->query("SELECT staff_id, full_name, department FROM staff ORDER BY full_name");
    $staff_members = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Set default to_status based on transaction type from URL
if(!empty($transaction_type)) {
    switch($transaction_type) {
        case "Assignment":
            $to_status = "Assigned";
            break;
        case "Return":
            $to_status = "Available";
            break;
        case "Maintenance":
            $to_status = "Maintenance";
            break;
        case "Disposal":
            $to_status = "Disposed";
            break;
    }
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate transaction type
    if(empty($_POST["transaction_type"])){
        $transaction_type_err = "Please select transaction type.";
    } else{
        $transaction_type = $_POST["transaction_type"];
    }
    
    // Validate to_status
    if(empty($_POST["to_status"])){
        $to_status_err = "Please select new status.";
    } else{
        $to_status = $_POST["to_status"];
    }
    
    // Validate assigned_to if transaction type is Assignment
    if($transaction_type == "Assignment") {
        if(empty($_POST["assigned_to"])){
            $assigned_to_err = "Please select staff member.";
        } else{
            $assigned_to = $_POST["assigned_to"];
        }
    }
    
    // Get notes
    $notes = trim($_POST["notes"]);
    
    // Check input errors before inserting in database
    if(empty($transaction_type_err) && empty($to_status_err) && 
       ($transaction_type != "Assignment" || empty($assigned_to_err))){
        
        // Prepare an insert statement for the transaction
        $sql = "INSERT INTO inventory_transactions (item_id, transaction_type, from_status, to_status, 
                assigned_to, notes, performed_by) 
                VALUES (:item_id, :transaction_type, :from_status, :to_status, 
                :assigned_to, :notes, :performed_by)";
        
        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":item_id", $param_item_id, PDO::PARAM_INT);
            $stmt->bindParam(":transaction_type", $param_transaction_type, PDO::PARAM_STR);
            $stmt->bindParam(":from_status", $param_from_status, PDO::PARAM_STR);
            $stmt->bindParam(":to_status", $param_to_status, PDO::PARAM_STR);
            $stmt->bindParam(":assigned_to", $param_assigned_to, PDO::PARAM_INT);
            $stmt->bindParam(":notes", $param_notes, PDO::PARAM_STR);
            $stmt->bindParam(":performed_by", $param_performed_by, PDO::PARAM_INT);
            
            // Set parameters
            $param_item_id = $item_id;
            $param_transaction_type = $transaction_type;
            $param_from_status = $from_status;
            $param_to_status = $to_status;
            $param_assigned_to = ($transaction_type == "Assignment") ? $assigned_to : null;
            $param_notes = $notes;
            $param_performed_by = $_SESSION["staff_id"];
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Update the item status
                $update_sql = "UPDATE inventory_items SET status = :status WHERE item_id = :id";
                if($update_stmt = $pdo->prepare($update_sql)){
                    $update_stmt->bindParam(":status", $to_status, PDO::PARAM_STR);
                    $update_stmt->bindParam(":id", $item_id, PDO::PARAM_INT);
                    $update_stmt->execute();
                }
                
                // Transaction created successfully. Redirect to view page
                header("location: view.php?id=" . $item_id);
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
        <h1 class="h2">Create Inventory Transaction</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="view.php?id=<?php echo $item_id; ?>" class="btn btn-sm btn-info mr-2">
                <i class="fas fa-eye"></i> View Item
            </a>
            <a href="index.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="row">
        <!-- Item Information -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Item Information</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%">Item ID</th>
                                <td><?php echo htmlspecialchars($item['item_id']); ?></td>
                            </tr>
                            <tr>
                                <th>Item Name</th>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Category</th>
                                <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Serial Number</th>
                                <td><?php echo htmlspecialchars($item['serial_number'] ?: 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Current Status</th>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo ($item['status'] == 'Available') ? 'success' : 
                                            (($item['status'] == 'Assigned') ? 'primary' : 
                                            (($item['status'] == 'Maintenance') ? 'warning' : 'secondary')); 
                                    ?>">
                                        <?php echo htmlspecialchars($item['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Transaction Form -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Transaction Details</h6>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $item_id); ?>" method="post">
                        <div class="form-group">
                            <label>Transaction Type</label>
                            <select name="transaction_type" id="transaction_type" class="form-control <?php echo (!empty($transaction_type_err)) ? 'is-invalid' : ''; ?>" onchange="updateFormFields()">
                                <option value="">Select Transaction Type</option>
                                <?php if($item['status'] == 'Available'): ?>
                                <option value="Assignment" <?php echo ($transaction_type == "Assignment") ? 'selected' : ''; ?>>Assignment</option>
                                <option value="Maintenance" <?php echo ($transaction_type == "Maintenance") ? 'selected' : ''; ?>>Send for Maintenance</option>
                                <option value="Disposal" <?php echo ($transaction_type == "Disposal") ? 'selected' : ''; ?>>Dispose Item</option>
                                <?php elseif($item['status'] == 'Assigned'): ?>
                                <option value="Return" <?php echo ($transaction_type == "Return") ? 'selected' : ''; ?>>Return Item</option>
                                <option value="Maintenance" <?php echo ($transaction_type == "Maintenance") ? 'selected' : ''; ?>>Send for Maintenance</option>
                                <?php elseif($item['status'] == 'Maintenance'): ?>
                                <option value="Return" <?php echo ($transaction_type == "Return") ? 'selected' : ''; ?>>Return from Maintenance</option>
                                <option value="Disposal" <?php echo ($transaction_type == "Disposal") ? 'selected' : ''; ?>>Dispose Item</option>
                                <?php endif; ?>
                            </select>
                            <span class="invalid-feedback"><?php echo $transaction_type_err; ?></span>
                        </div>
                        
                        <div class="form-group">
                            <label>New Status</label>
                            <select name="to_status" class="form-control <?php echo (!empty($to_status_err)) ? 'is-invalid' : ''; ?>">
                                <option value="">Select New Status</option>
                                <option value="Available" <?php echo ($to_status == "Available") ? 'selected' : ''; ?>>Available</option>
                                <option value="Assigned" <?php echo ($to_status == "Assigned") ? 'selected' : ''; ?>>Assigned</option>
                                <option value="Maintenance" <?php echo ($to_status == "Maintenance") ? 'selected' : ''; ?>>Maintenance</option>
                                <option value="Disposed" <?php echo ($to_status == "Disposed") ? 'selected' : ''; ?>>Disposed</option>
                            </select>
                            <span class="invalid-feedback"><?php echo $to_status_err; ?></span>
                        </div>
                        
                        <div id="assignment_section" class="form-group" style="display: <?php echo ($transaction_type == 'Assignment') ? 'block' : 'none'; ?>">
                            <label>Assign To</label>
                            <select name="assigned_to" class="form-control <?php echo (!empty($assigned_to_err)) ? 'is-invalid' : ''; ?>">
                                <option value="">Select Staff Member</option>
                                <?php foreach($staff_members as $staff): ?>
                                <option value="<?php echo $staff['staff_id']; ?>" <?php echo ($assigned_to == $staff['staff_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($staff['full_name'] . ' (' . $staff['department'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="invalid-feedback"><?php echo $assigned_to_err; ?></span>
                        </div>
                        
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="3"><?php echo $notes; ?></textarea>
                            <small class="form-text text-muted">Add any additional information about this transaction.</small>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Transaction
                            </button>
                            <a href="view.php?id=<?php echo $item_id; ?>" class="btn btn-secondary ml-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateFormFields() {
    const transactionType = document.getElementById('transaction_type').value;
    const assignmentSection = document.getElementById('assignment_section');
    
    // Show/hide assignment section based on transaction type
    if (transactionType === 'Assignment') {
        assignmentSection.style.display = 'block';
    } else {
        assignmentSection.style.display = 'none';
    }
    
    // Update status dropdown based on transaction type
    const statusDropdown = document.querySelector('select[name="to_status"]');
    
    // Clear current options
    while (statusDropdown.options.length > 1) {
        statusDropdown.remove(1);
    }
    
    // Add appropriate options based on transaction type
    if (transactionType === 'Assignment') {
        statusDropdown.add(new Option('Assigned', 'Assigned', false, true));
    } else if (transactionType === 'Return') {
        statusDropdown.add(new Option('Available', 'Available', false, true));
    } else if (transactionType === 'Maintenance') {
        statusDropdown.add(new Option('Maintenance', 'Maintenance', false, true));
    } else if (transactionType === 'Disposal') {
        statusDropdown.add(new Option('Disposed', 'Disposed', false, true));
    } else {
        // Add all options if no specific transaction type is selected
        statusDropdown.add(new Option('Available', 'Available'));
        statusDropdown.add(new Option('Assigned', 'Assigned'));
        statusDropdown.add(new Option('Maintenance', 'Maintenance'));
        statusDropdown.add(new Option('Disposed', 'Disposed'));
    }
}

// Initialize form fields on page load
document.addEventListener('DOMContentLoaded', function() {
    updateFormFields();
});
</script>

<?php
// Include footer
include_once $basePath . "includes/footer.php";

// End output buffering and send content to browser
ob_end_flush();
?>
