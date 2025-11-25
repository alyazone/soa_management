<?php
// Set the base path for includes
$basePath = '../../';

// Include database connection
require_once $basePath . "config/database.php";

// Check if user is logged in
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}

// Check if the user has admin privileges
if($_SESSION['position'] != 'Admin'){
    header("location: index.php");
    exit;
}

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

    // Validate supplier (optional)
    if(!empty($_POST["supplier_id"])){
        $supplier_id = $_POST["supplier_id"];
    } else {
        $supplier_id = null;
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
    if(empty($item_name_err) && empty($category_id_err) &&
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
            if ($supplier_id === null) {
                $stmt->bindValue(":supplier_id", null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(":supplier_id", $supplier_id, PDO::PARAM_INT);
            }
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

                // Records updated successfully. Redirect to index page
                header("location: index.php?success=2");
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Inventory Item - SOA Management System</title>

    <!-- Modern CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/modern-dashboard.css">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Animation -->
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Sidebar -->
    <?php include_once $basePath . "includes/modern-sidebar.php"; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="dashboard-header">
            <div class="header-content">
                <div class="header-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="header-title">
                        <h1>Edit Inventory Item</h1>
                        <p>Update item information and details</p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="view.php?id=<?php echo $_GET['id']; ?>" class="date-picker-btn" style="margin-right: 10px;">
                        <i class="fas fa-eye"></i>
                        View Item
                    </a>
                    <a href="index.php" class="date-picker-btn">
                        <i class="fas fa-arrow-left"></i>
                        Back to List
                    </a>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Info Card -->
            <div class="info-card" data-aos="fade-up">
                <div class="info-card-icon">
                    <i class="fas fa-edit"></i>
                </div>
                <div class="info-card-content">
                    <h4>Update Item Information</h4>
                    <p>Modify the details of this inventory item. Changes will be tracked in the transaction history.</p>
                </div>
            </div>

            <!-- Form Card -->
            <div class="table-card" data-aos="fade-up" data-aos-delay="100">
                <div class="table-header">
                    <div class="table-title">
                        <h3>Item Information</h3>
                        <p>Item ID: <strong>#<?php echo str_pad($_GET['id'], 3, '0', STR_PAD_LEFT); ?></strong></p>
                    </div>
                </div>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $_GET['id']); ?>" method="post" class="form-container">
                    <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">

                    <!-- Basic Information Section -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-info-circle"></i>
                            <span>Basic Information</span>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Item Name <span class="required">*</span></label>
                                <input type="text" name="item_name" class="form-input <?php echo (!empty($item_name_err)) ? 'input-error' : ''; ?>" value="<?php echo $item_name; ?>" placeholder="Enter item name">
                                <?php if(!empty($item_name_err)): ?>
                                    <span class="error-message"><?php echo $item_name_err; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Category <span class="required">*</span></label>
                                <select name="category_id" class="form-input <?php echo (!empty($category_id_err)) ? 'input-error' : ''; ?>">
                                    <option value="">-- Select Category --</option>
                                    <?php foreach($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>" <?php echo ($category_id == $category['category_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if(!empty($category_id_err)): ?>
                                    <span class="error-message"><?php echo $category_id_err; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Supplier</label>
                                <select name="supplier_id" class="form-input">
                                    <option value="">-- Select Supplier (Optional) --</option>
                                    <?php foreach($suppliers as $supplier): ?>
                                        <option value="<?php echo $supplier['supplier_id']; ?>" <?php echo ($supplier_id == $supplier['supplier_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="field-hint">Leave blank if no supplier is associated</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status <span class="required">*</span></label>
                                <select name="status" class="form-input <?php echo (!empty($status_err)) ? 'input-error' : ''; ?>">
                                    <option value="">-- Select Status --</option>
                                    <option value="Available" <?php echo ($status == "Available") ? 'selected' : ''; ?>>Available</option>
                                    <option value="Assigned" <?php echo ($status == "Assigned") ? 'selected' : ''; ?>>Assigned</option>
                                    <option value="Maintenance" <?php echo ($status == "Maintenance") ? 'selected' : ''; ?>>Maintenance</option>
                                    <option value="Disposed" <?php echo ($status == "Disposed") ? 'selected' : ''; ?>>Disposed</option>
                                </select>
                                <?php if(!empty($status_err)): ?>
                                    <span class="error-message"><?php echo $status_err; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Item Details Section -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Item Details</span>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Serial Number</label>
                                <input type="text" name="serial_number" class="form-input" value="<?php echo $serial_number; ?>" placeholder="Enter serial number">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Model Number</label>
                                <input type="text" name="model_number" class="form-input" value="<?php echo $model_number; ?>" placeholder="Enter model number">
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Purchase Date <span class="required">*</span></label>
                                <input type="date" name="purchase_date" class="form-input <?php echo (!empty($purchase_date_err)) ? 'input-error' : ''; ?>" value="<?php echo $purchase_date; ?>">
                                <?php if(!empty($purchase_date_err)): ?>
                                    <span class="error-message"><?php echo $purchase_date_err; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Purchase Price (RM) <span class="required">*</span></label>
                                <div class="input-with-prefix">
                                    <span class="input-prefix">RM</span>
                                    <input type="text" name="purchase_price" class="form-input with-prefix <?php echo (!empty($purchase_price_err)) ? 'input-error' : ''; ?>" value="<?php echo $purchase_price; ?>" placeholder="0.00">
                                </div>
                                <?php if(!empty($purchase_price_err)): ?>
                                    <span class="error-message"><?php echo $purchase_price_err; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Warranty Expiry Date</label>
                                <input type="date" name="warranty_expiry" class="form-input" value="<?php echo $warranty_expiry; ?>">
                                <small class="field-hint">Leave blank if no warranty</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Location</label>
                                <input type="text" name="location" class="form-input" value="<?php echo $location; ?>" placeholder="e.g., Office, Storage Room">
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-input form-textarea" placeholder="Additional notes or remarks..."><?php echo $notes; ?></textarea>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="submit" class="btn-primary-large">
                            <i class="fas fa-save"></i>
                            Update Item
                        </button>
                        <a href="view.php?id=<?php echo $_GET['id']; ?>" class="btn-secondary-large">
                            <i class="fas fa-times"></i>
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="../../assets/js/modern-dashboard.js"></script>

    <script>
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize AOS animations
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true
            });

            // Initialize interactions
            initializeDashboard();
        });
    </script>
</body>
</html>
