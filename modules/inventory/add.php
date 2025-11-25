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

    // Check input errors before inserting in database
    if(empty($item_name_err) && empty($category_id_err) &&
       empty($purchase_date_err) && empty($purchase_price_err) && empty($status_err)){

        // Prepare an insert statement
        $sql = "INSERT INTO inventory_items (item_name, category_id, supplier_id, serial_number, model_number,
                purchase_date, purchase_price, warranty_expiry, status, location, notes, created_by)
                VALUES (:item_name, :category_id, :supplier_id, :serial_number, :model_number,
                :purchase_date, :purchase_price, :warranty_expiry, :status, :location, :notes, :created_by)";

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
            $stmt->bindParam(":created_by", $param_created_by, PDO::PARAM_INT);

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
            $param_created_by = $_SESSION["staff_id"];

            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Get the last inserted ID
                $last_id = $pdo->lastInsertId();

                // Create a purchase transaction record
                $transaction_sql = "INSERT INTO inventory_transactions (item_id, transaction_type, to_status, transaction_date, notes, performed_by)
                                   VALUES (:item_id, 'Purchase', :status, NOW(), 'Initial purchase', :performed_by)";

                if($trans_stmt = $pdo->prepare($transaction_sql)){
                    $trans_stmt->bindParam(":item_id", $last_id, PDO::PARAM_INT);
                    $trans_stmt->bindParam(":status", $param_status, PDO::PARAM_STR);
                    $trans_stmt->bindParam(":performed_by", $param_created_by, PDO::PARAM_INT);
                    $trans_stmt->execute();
                }

                // Records created successfully. Redirect to landing page
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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Inventory Item - SOA Management System</title>

    <!-- Modern CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/modern-dashboard.css">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Animation -->
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    
    <!-- Added inline critical styles to override Tailwind's Preflight reset -->
    <style>
        /* Form Input Styles - Override Tailwind Preflight */
        .form-input,
        input.form-input,
        select.form-input,
        textarea.form-input,
        .form-container input[type="text"],
        .form-container input[type="date"],
        .form-container input[type="number"],
        .form-container input[type="email"],
        .form-container input[type="password"],
        .form-container input[type="tel"],
        .form-container select,
        .form-container textarea {
            display: block !important;
            width: 100% !important;
            padding: 0.75rem 1rem !important;
            font-size: 0.95rem !important;
            font-weight: 400 !important;
            line-height: 1.5 !important;
            color: #1e293b !important;
            background-color: #ffffff !important;
            background-clip: padding-box !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
        }

        .form-input:focus,
        input.form-input:focus,
        select.form-input:focus,
        textarea.form-input:focus,
        .form-container input:focus,
        .form-container select:focus,
        .form-container textarea:focus {
            border-color: #3b82f6 !important;
            outline: 0 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
        }

        .form-container select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") !important;
            background-position: right 0.75rem center !important;
            background-repeat: no-repeat !important;
            background-size: 1.25rem !important;
            padding-right: 2.5rem !important;
        }

        .form-container textarea {
            min-height: 120px !important;
            resize: vertical !important;
        }

        .form-container {
            padding: 1.5rem !important;
        }

        .form-section {
            background: #f8fafc !important;
            border-radius: 0.75rem !important;
            padding: 1.5rem !important;
            margin-bottom: 1.5rem !important;
            border: 1px solid #e2e8f0 !important;
        }

        .form-section-title {
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            font-weight: 600 !important;
            font-size: 1rem !important;
            color: #1e293b !important;
            margin-bottom: 1.25rem !important;
            padding-bottom: 0.75rem !important;
            border-bottom: 1px solid #e2e8f0 !important;
        }

        .form-section-title i {
            color: #3b82f6 !important;
        }

        .form-grid {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 1.25rem !important;
            margin-bottom: 1.25rem !important;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr !important;
            }
        }

        .form-group {
            display: flex !important;
            flex-direction: column !important;
        }

        .form-group.full-width {
            grid-column: span 2 !important;
        }

        @media (max-width: 768px) {
            .form-group.full-width {
                grid-column: span 1 !important;
            }
        }

        .form-label {
            display: block !important;
            font-weight: 500 !important;
            font-size: 0.875rem !important;
            color: #475569 !important;
            margin-bottom: 0.5rem !important;
        }

        .form-label .required {
            color: #ef4444 !important;
            margin-left: 0.25rem !important;
        }

        .input-error {
            border-color: #ef4444 !important;
        }

        .error-message {
            color: #ef4444 !important;
            font-size: 0.8rem !important;
            margin-top: 0.375rem !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.25rem !important;
        }

        .field-hint {
            color: #64748b !important;
            font-size: 0.8rem !important;
            margin-top: 0.375rem !important;
        }

        .input-with-prefix {
            display: flex !important;
            align-items: stretch !important;
        }

        .input-prefix {
            display: flex !important;
            align-items: center !important;
            padding: 0 1rem !important;
            background: #f1f5f9 !important;
            border: 1px solid #e2e8f0 !important;
            border-right: none !important;
            border-radius: 0.5rem 0 0 0.5rem !important;
            color: #64748b !important;
            font-weight: 500 !important;
            font-size: 0.95rem !important;
        }

        .input-with-prefix .form-input,
        .input-with-prefix input.form-input {
            border-radius: 0 0.5rem 0.5rem 0 !important;
            border-left: none !important;
        }

        .form-actions {
            display: flex !important;
            gap: 1rem !important;
            padding-top: 1.5rem !important;
            border-top: 1px solid #e2e8f0 !important;
            margin-top: 0.5rem !important;
        }

        .btn-primary-large {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.5rem !important;
            padding: 0.875rem 2rem !important;
            font-size: 0.95rem !important;
            font-weight: 600 !important;
            color: #ffffff !important;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
            border: none !important;
            border-radius: 0.5rem !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3) !important;
        }

        .btn-primary-large:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 10px -1px rgba(59, 130, 246, 0.4) !important;
        }

        .btn-secondary-large {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.5rem !important;
            padding: 0.875rem 2rem !important;
            font-size: 0.95rem !important;
            font-weight: 600 !important;
            color: #475569 !important;
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.5rem !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            text-decoration: none !important;
        }

        .btn-secondary-large:hover {
            background: #f8fafc !important;
            border-color: #cbd5e1 !important;
            color: #1e293b !important;
        }

        @media (max-width: 640px) {
            .form-actions {
                flex-direction: column !important;
            }
            
            .btn-primary-large,
            .btn-secondary-large {
                width: 100% !important;
            }
        }
    </style>
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
                        <h1>Add New Inventory Item</h1>
                        <p>Register a new item in the inventory system</p>
                    </div>
                </div>
                <div class="header-right">
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
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="info-card-content">
                    <h4>Inventory Item Guidelines</h4>
                    <p>Please fill in all required fields marked with <span class="required">*</span>. Ensure accurate information for proper tracking and management of inventory assets.</p>
                </div>
            </div>

            <!-- Form Card -->
            <div class="table-card" data-aos="fade-up" data-aos-delay="100">
                <div class="table-header">
                    <div class="table-title">
                        <h3>Item Information</h3>
                        <p>Complete the form below to add a new inventory item</p>
                    </div>
                </div>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="form-container">
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
                            Save Item
                        </button>
                        <a href="index.php" class="btn-secondary-large">
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
