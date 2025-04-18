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
$maintenance_date = $maintenance_type = $performed_by = $cost = $description = $next_maintenance_date = "";
$maintenance_date_err = $maintenance_type_err = $performed_by_err = $cost_err = $description_err = "";
$update_status = false;

// Fetch item data
try {
    $stmt = $pdo->prepare("SELECT i.*, c.category_name 
                          FROM inventory_items i 
                          LEFT JOIN inventory_categories c ON i.category_id = c.category_id 
                          WHERE i.item_id = :id");
    $stmt->bindParam(":id", $item_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if($stmt->rowCount() != 1){
        header("location: index.php");
        exit();
    }
    
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("ERROR: Could not fetch item. " . $e->getMessage());
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate maintenance date
    if(empty($_POST["maintenance_date"])){
        $maintenance_date_err = "Please enter maintenance date.";
    } else{
        $maintenance_date = $_POST["maintenance_date"];
    }
    
    // Validate maintenance type
    if(empty(trim($_POST["maintenance_type"]))){
        $maintenance_type_err = "Please enter maintenance type.";
    } else{
        $maintenance_type = trim($_POST["maintenance_type"]);
    }
    
    // Validate performed by
    if(empty(trim($_POST["performed_by"]))){
        $performed_by_err = "Please enter who performed the maintenance.";
    } else{
        $performed_by = trim($_POST["performed_by"]);
    }
    
    // Validate cost
    if(empty(trim($_POST["cost"]))){
        $cost_err = "Please enter maintenance cost.";
    } elseif(!is_numeric(trim($_POST["cost"])) || floatval(trim($_POST["cost"])) < 0){
        $cost_err = "Please enter a valid cost.";
    } else{
        $cost = trim($_POST["cost"]);
    }
    
    // Validate description
    if(empty(trim($_POST["description"]))){
        $description_err = "Please enter maintenance description.";
    } else{
        $description = trim($_POST["description"]);
    }
    
    // Validate next maintenance date (optional)
    $next_maintenance_date = !empty($_POST["next_maintenance_date"]) ? $_POST["next_maintenance_date"] : null;
    
    // Check if status should be updated
    if(isset($_POST["update_status"]) && $_POST["update_status"] == "1"){
        $update_status = true;
    }
    
    // Check input errors before inserting in database
    if(empty($maintenance_date_err) && empty($maintenance_type_err) && empty($performed_by_err) && 
       empty($cost_err) && empty($description_err)){
        
        // Begin transaction
        $pdo->beginTransaction();
        
        try {
            // Prepare an insert statement for maintenance record
            $sql = "INSERT INTO inventory_maintenance (item_id, maintenance_date, maintenance_type, 
                    performed_by, cost, description, next_maintenance_date, created_by) 
                    VALUES (:item_id, :maintenance_date, :maintenance_type, :performed_by, 
                    :cost, :description, :next_maintenance_date, :created_by)";
            
            if($stmt = $pdo->prepare($sql)){
                // Bind variables to the prepared statement as parameters
                $stmt->bindParam(":item_id", $param_item_id, PDO::PARAM_INT);
                $stmt->bindParam(":maintenance_date", $param_maintenance_date, PDO::PARAM_STR);
                $stmt->bindParam(":maintenance_type", $param_maintenance_type, PDO::PARAM_STR);
                $stmt->bindParam(":performed_by", $param_performed_by, PDO::PARAM_STR);
                $stmt->bindParam(":cost", $param_cost, PDO::PARAM_STR);
                $stmt->bindParam(":description", $param_description, PDO::PARAM_STR);
                $stmt->bindParam(":next_maintenance_date", $param_next_maintenance_date, PDO::PARAM_STR);
                $stmt->bindParam(":created_by", $param_created_by, PDO::PARAM_INT);
                
                // Set parameters
                $param_item_id = $item_id;
                $param_maintenance_date = $maintenance_date;
                $param_maintenance_type = $maintenance_type;
                $param_performed_by = $performed_by;
                $param_cost = $cost;
                $param_description = $description;
                $param_next_maintenance_date = $next_maintenance_date;
                $param_created_by = $_SESSION["staff_id"];
                
                // Attempt to execute the prepared statement
                $stmt->execute();
            }
            
            // If status update is requested
            if($update_status){
                // Get current status
                $current_status = $item['status'];
                $new_status = 'Maintenance';
                
                // Only update if status is not already 'Maintenance'
                if($current_status != $new_status){
                    // Update item status
                    $update_sql = "UPDATE inventory_items SET status = :status WHERE item_id = :item_id";
                    $update_stmt = $pdo->prepare($update_sql);
                    $update_stmt->bindParam(":status", $new_status, PDO::PARAM_STR);
                    $update_stmt->bindParam(":item_id", $item_id, PDO::PARAM_INT);
                    $update_stmt->execute();
                    
                    // Create transaction record
                    $transaction_sql = "INSERT INTO inventory_transactions (item_id, transaction_type, 
                                        from_status, to_status, transaction_date, notes, performed_by) 
                                        VALUES (:item_id, 'Maintenance', :from_status, :to_status, 
                                        NOW(), :notes, :performed_by)";
                    
                    $trans_stmt = $pdo->prepare($transaction_sql);
                    $trans_stmt->bindParam(":item_id", $item_id, PDO::PARAM_INT);
                    $trans_stmt->bindParam(":from_status", $current_status, PDO::PARAM_STR);
                    $trans_stmt->bindParam(":to_status", $new_status, PDO::PARAM_STR);
                    $trans_stmt->bindParam(":notes", $param_description, PDO::PARAM_STR);
                    $trans_stmt->bindParam(":performed_by", $param_created_by, PDO::PARAM_INT);
                    $trans_stmt->execute();
                }
            }
            
            // Commit transaction
            $pdo->commit();
            
            // Records created successfully. Redirect to view page
            header("location: view.php?id=" . $item_id);
            exit();
            
        } catch(PDOException $e) {
            // Roll back transaction on error
            $pdo->rollBack();
            echo "Error: " . $e->getMessage();
        }
    }
}
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Add Maintenance Record</h1>
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
        
        <!-- Maintenance Form -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Maintenance Details</h6>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $item_id); ?>" method="post">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>
                                    <span class="text-danger">*</span> Maintenance Date
                                </label>
                                <input type="date" name="maintenance_date" class="form-control <?php echo (!empty($maintenance_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $maintenance_date ?: date('Y-m-d'); ?>">
                                <span class="invalid-feedback"><?php echo $maintenance_date_err; ?></span>
                            </div>
                            <div class="form-group col-md-6">
                                <label>
                                    <span class="text-danger">*</span> Maintenance Type
                                </label>
                                <input type="text" name="maintenance_type" class="form-control <?php echo (!empty($maintenance_type_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $maintenance_type; ?>" placeholder="e.g., Repair, Service, Upgrade">
                                <span class="invalid-feedback"><?php echo $maintenance_type_err; ?></span>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>
                                    <span class="text-danger">*</span> Performed By
                                </label>
                                <input type="text" name="performed_by" class="form-control <?php echo (!empty($performed_by_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $performed_by; ?>" placeholder="Person or company who performed maintenance">
                                <span class="invalid-feedback"><?php echo $performed_by_err; ?></span>
                            </div>
                            <div class="form-group col-md-6">
                                <label>
                                    <span class="text-danger">*</span> Cost (RM)
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">RM</span>
                                    </div>
                                    <input type="text" name="cost" class="form-control <?php echo (!empty($cost_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $cost; ?>">
                                    <span class="invalid-feedback"><?php echo $cost_err; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <span class="text-danger">*</span> Description
                            </label>
                            <textarea name="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>" rows="3" placeholder="Detailed description of maintenance performed"><?php echo $description; ?></textarea>
                            <span class="invalid-feedback"><?php echo $description_err; ?></span>
                        </div>
                        
                        <div class="form-group">
                            <label>Next Scheduled Maintenance Date</label>
                            <input type="date" name="next_maintenance_date" class="form-control" value="<?php echo $next_maintenance_date; ?>">
                            <small class="form-text text-muted">Leave blank if no scheduled maintenance is planned</small>
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="updateStatus" name="update_status" value="1" <?php echo ($item['status'] != 'Maintenance') ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="updateStatus">
                                    Update item status to "Maintenance"
                                </label>
                                <small class="form-text text-muted">
                                    <?php if($item['status'] == 'Maintenance'): ?>
                                        Item is already in Maintenance status
                                    <?php else: ?>
                                        Check this to change the item status to "Maintenance"
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class="form-group text-center mt-4">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-save mr-2"></i>Save Record
                            </button>
                            <a href="view.php?id=<?php echo $item_id; ?>" class="btn btn-secondary btn-lg ml-2 px-5">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Maintenance History -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Maintenance History</h6>
        </div>
        <div class="card-body">
            <?php
            // Fetch maintenance records
            try {
                $stmt = $pdo->prepare("SELECT m.*, s.full_name as created_by_name
                                      FROM inventory_maintenance m
                                      LEFT JOIN staff s ON m.created_by = s.staff_id
                                      WHERE m.item_id = :id 
                                      ORDER BY m.maintenance_date DESC");
                $stmt->bindParam(":id", $item_id, PDO::PARAM_INT);
                $stmt->execute();
                $maintenance_records = $stmt->fetchAll();
            } catch(PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
            ?>
            
            <?php if(!empty($maintenance_records)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Performed By</th>
                                <th>Cost</th>
                                <th>Description</th>
                                <th>Next Maintenance</th>
                                <th>Recorded By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($maintenance_records as $record): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($record['maintenance_date'])); ?></td>
                                <td><?php echo htmlspecialchars($record['maintenance_type']); ?></td>
                                <td><?php echo htmlspecialchars($record['performed_by']); ?></td>
                                <td>RM <?php echo number_format($record['cost'], 2); ?></td>
                                <td><?php echo htmlspecialchars(substr($record['description'], 0, 50)) . (strlen($record['description']) > 50 ? '...' : ''); ?></td>
                                <td>
                                    <?php 
                                    if(!empty($record['next_maintenance_date'])) {
                                        echo date('d M Y', strtotime($record['next_maintenance_date']));
                                        
                                        // Check if next maintenance is due
                                        $today = new DateTime();
                                        $next_date = new DateTime($record['next_maintenance_date']);
                                        if($today > $next_date) {
                                            echo ' <span class="badge badge-danger">Overdue</span>';
                                        } else {
                                            $interval = $today->diff($next_date);
                                            if($interval->days <= 30) {
                                                echo ' <span class="badge badge-warning">Due soon</span>';
                                            }
                                        }
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($record['created_by_name']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No maintenance records found for this item.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
include_once $basePath . "includes/footer.php";

// Flush the output buffer and send the content to the browser
ob_end_flush();
?>
