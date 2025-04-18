<?php
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
$item_name = $category_id = $supplier_id = $serial_number = $model_number = "";
$purchase_date = $purchase_price = $warranty_expiry = $status = $location = $notes = "";
$item_name_err = $category_id_err = $supplier_id_err = $serial_number_err = "";
$purchase_date_err = $purchase_price_err = $status_err = "";

// Fetch categories for dropdown
try {
    $stmt = $pdo->query("SELECT category_id, category_name FROM inventory_categories ORDER BY category_name");
    $categories = $stmt->fetchAll();
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
    // Get hidden input value
    $id = $_POST["id"];
    
    // Validate item name
    if(empty(trim($_POST["item_name"]))){
        $item_name_err = "Please enter item name.";
    } else{
        $item_name = trim($_POST["item_name"]);
    }
    
    // Validate category
    if(empty($_POST["category_id"])){
        $category_id_err = "Please select category.";
    } else{
        $category_id = $_POST["category_id"];
    }
    
    // Validate supplier
    if(empty($_POST["supplier_id"])){
        $supplier_id_err = "Please select supplier.";
    } else{
        $supplier_id = $_POST["supplier_id"];
    }
    
    // Validate serial number (optional)
    $serial_number = trim($_POST["serial_number"]);
    
    // Validate model number (optional)
    $model_number = trim($_POST["model_number"]);
    
    // Validate purchase date
    if(empty($_POST["purchase_date"])){
        $purchase_date_err = "Please enter purchase date.";
    } else{
        $purchase_date = $_POST["purchase_date"];
    }
    
    // Validate purchase price
    if(empty(trim($_POST["purchase_price"]))){
        $purchase_price_err = "Please enter purchase price.";
    } elseif(!is_numeric(trim($_POST["purchase_price"])) || floatval(trim($_POST["purchase_price"])) < 0){
        $purchase_price_err = "Please enter a valid price.";
    } else{
        $purchase_price = trim($_POST["purchase_price"]);
    }
    
    // Validate warranty expiry (optional)
    $warranty_expiry = !empty($_POST["warranty_expiry"]) ? $_POST["warranty_expiry"] : null;
    
    // Validate status
    if(empty($_POST["status"])){
        $status_err = "Please select status.";
    } else{
        $status = $_POST["status"];
    }
    
    // Validate location (optional)
    $location = trim($_POST["location"]);
    
    // Validate notes (optional)
    $notes = trim($_POST["notes"]);
    
    // Check input errors before updating the database
    if(empty($item_name_err) && empty($category_id_err) && empty($supplier_id_err) && 
       empty($purchase_date_err) && empty($purchase_price_err) && empty($status_err)){
        
        // Get current status before update
        $stmt = $pdo->prepare("SELECT status FROM inventory_items WHERE item_id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $current_item = $stmt->fetch();
        $current_status = $current_item['status'];
        
        // Prepare an update statement
        $sql = "UPDATE inventory_items SET item_name = :item_name, category_id = :category_id, 
                supplier_id = :supplier_id, serial_number = :serial_number, model_number = :model_number, 
                purchase_date = :purchase_date, purchase_price = :purchase_price, 
                warranty_expiry = :warranty_expiry, status = :status, location = :location, 
                notes = :notes WHERE item_id = :id";
        
        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":item_name", $param_item_name, PDO::PARAM_STR);
            $stmt->bindParam(":category_id", $param_category_id, PDO::PARAM_INT);
            $stmt->bindParam(":supplier_id", $param_supplier_id, PDO::PARAM_INT);
            $stmt->bindParam(":serial_number", $param_serial_number, PDO::PARAM_STR);
            $stmt->bindParam(":model_number", $param_model_number, PDO::PARAM_STR);
            $stmt->bindParam(":purchase_date", $param_purchase_date, PDO::PARAM_STR);
            $stmt->bindParam(":purchase_price", $param_purchase_price, PDO::PARAM_STR);
            $stmt->bindParam(":warranty_expiry", $param_warranty_expiry, PDO::PARAM_STR);
            $stmt->bindParam(":status", $param_status, PDO::PARAM_STR);
            $stmt->bindParam(":location", $param_location, PDO::PARAM_STR);
            $stmt->bindParam(":notes", $param_notes, PDO::PARAM_STR);
            $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
            
            // Set parameters
            $param_item_name = $item_name;
            $param_category_id = $category_id;
            $param_supplier_id = $supplier_id;
            $param_serial_number = $serial_number;
            $param_model_number = $model_number;
            $param_purchase_date = $purchase_date;
            $param_purchase_price = $purchase_price;
            $param_warranty_expiry = $warranty_expiry;
            $param_status = $status;
            $param_location = $location;
            $param_notes = $notes;
            $param_id = $id;
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // If status has changed, create a transaction record
                if($current_status != $status) {
                    $transaction_sql = "INSERT INTO inventory_transactions (item_id, transaction_type, from_status, to_status, transaction_date, notes, performed_by) 
                                       VALUES (:item_id, 'Status Change', :from_status, :to_status, NOW(), 'Status updated via edit form', :performed_by)";
                    
                    if($trans_stmt = $pdo->prepare($transaction_sql)){
                        $trans_stmt->bindParam(":item_id", $id, PDO::PARAM_INT);
                        $trans_stmt->bindParam(":from_status", $current_status, PDO::PARAM_STR);
                        $trans_stmt->bindParam(":to_status", $status, PDO::PARAM_STR);
                        $trans_stmt->bindParam(":performed_by", $_SESSION["staff_id"], PDO::PARAM_INT);
                        $trans_stmt->execute();
                    }
                }
                
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
} else {
    // Fetch item data
    try {
        $stmt = $pdo->prepare("SELECT * FROM inventory_items WHERE item_id = :id");
        $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
        $stmt->execute();
        
        if($stmt->rowCount() != 1){
            header("location: index.php");
            exit();
        }
        
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Set values
        $item_name = $item['item_name'];
        $category_id = $item['category_id'];
        $supplier_id = $item['supplier_id'];
        $serial_number = $item['serial_number'];
        $model_number = $item['model_number'];
        $purchase_date = $item['purchase_date'];
        $purchase_price = $item['purchase_price'];
        $warranty_expiry = $item['warranty_expiry'];
        $status = $item['status'];
        $location = $item['location'];
        $notes = $item['notes'];
        
    } catch(PDOException $e) {
        die("ERROR: Could not fetch item. " . $e->getMessage());
    }
}
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Edit Inventory Item</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="view.php?id=<?php echo $_GET['id']; ?>" class="btn btn-sm btn-info mr-2">
                <i class="fas fa-eye"></i> View Item
            </a>
            <a href="index.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Item Information</h6>
        </div>
        <div class="card-body">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
                
                <!-- Basic Information Section -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-info-circle mr-2"></i>Basic Information
                            <span class="badge badge-warning ml-2">Required</span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>
                                    <span class="text-danger">*</span> Item Name
                                </label>
                                <input type="text" name="item_name" class="form-control <?php echo (!empty($item_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $item_name; ?>">
                                <span class="invalid-feedback"><?php echo $item_name_err; ?></span>
                            </div>
                            <div class="form-group col-md-6">
                                <label>
                                    <span class="text-danger">*</span> Category
                                </label>
                                <select name="category_id" class="form-control <?php echo (!empty($category_id_err)) ? 'is-invalid' : ''; ?>">
                                    <option value="">Select Category</option>
                                    <?php foreach($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>" <?php echo ($category_id == $category['category_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="invalid-feedback"><?php echo $category_id_err; ?></span>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>
                                    <span class="text-danger">*</span> Supplier
                                </label>
                                <select name="supplier_id" class="form-control <?php echo (!empty($supplier_id_err)) ? 'is-invalid' : ''; ?>">
                                    <option value="">Select Supplier</option>
                                    <?php foreach($suppliers as $supplier): ?>
                                        <option value="<?php echo $supplier['supplier_id']; ?>" <?php echo ($supplier_id == $supplier['supplier_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="invalid-feedback"><?php echo $supplier_id_err; ?></span>
                            </div>
                            <div class="form-group col-md-6">
                                <label>
                                    <span class="text-danger">*</span> Status
                                </label>
                                <select name="status" class="form-control <?php echo (!empty($status_err)) ? 'is-invalid' : ''; ?>">
                                    <option value="">Select Status</option>
                                    <option value="Available" <?php echo ($status == "Available") ? 'selected' : ''; ?>>Available</option>
                                    <option value="Assigned" <?php echo ($status == "Assigned") ? 'selected' : ''; ?>>Assigned</option>
                                    <option value="Maintenance" <?php echo ($status == "Maintenance") ? 'selected' : ''; ?>>Maintenance</option>
                                    <option value="Disposed" <?php echo ($status == "Disposed") ? 'selected' : ''; ?>>Disposed</option>
                                </select>
                                <span class="invalid-feedback"><?php echo $status_err; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Details Section -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-clipboard-list mr-2"></i>Item Details
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Serial Number</label>
                                <input type="text" name="serial_number" class="form-control" value="<?php echo $serial_number; ?>">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Model Number</label>
                                <input type="text" name="model_number" class="form-control" value="<?php echo $model_number; ?>">
                            </div>
                        </div>
                        
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
                                    <span class="text-danger">*</span> Purchase Price (RM)
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">RM</span>
                                    </div>
                                    <input type="text" name="purchase_price" class="form-control <?php echo (!empty($purchase_price_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $purchase_price; ?>">
                                    <span class="invalid-feedback"><?php echo $purchase_price_err; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Warranty Expiry Date</label>
                                <input type="date" name="warranty_expiry" class="form-control" value="<?php echo $warranty_expiry; ?>">
                                <small class="form-text text-muted">Leave blank if no warranty</small>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Location</label>
                                <input type="text" name="location" class="form-control" value="<?php echo $location; ?>" placeholder="e.g., Office, Storage Room, etc.">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="3"><?php echo $notes; ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="form-group text-center">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-save mr-2"></i>Update Item
                    </button>
                    <a href="view.php?id=<?php echo $_GET['id']; ?>" class="btn btn-secondary btn-lg ml-2 px-5">
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
?>
