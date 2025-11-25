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

// Fetch maintenance history
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Maintenance Record - SOA Management System</title>

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
                        <h1>Add Maintenance Record</h1>
                        <p>Record maintenance activities for inventory item</p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="view.php?id=<?php echo $item_id; ?>" class="date-picker-btn">
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
            <!-- Item Info Card -->
            <div class="info-card" data-aos="fade-up">
                <div class="info-card-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="info-card-content">
                    <h4><?php echo htmlspecialchars($item['item_name']); ?></h4>
                    <p>Item ID: <strong>#<?php echo str_pad($item['item_id'], 3, '0', STR_PAD_LEFT); ?></strong> | Category: <strong><?php echo htmlspecialchars($item['category_name']); ?></strong> | Current Status: <span class="status-badge status-<?php echo strtolower($item['status']); ?>"><?php echo htmlspecialchars($item['status']); ?></span></p>
                </div>
            </div>

            <div class="tables-grid" data-aos="fade-up" data-aos-delay="100">
                <!-- Left Column - Item Info -->
                <div>
                    <div class="table-card">
                        <div class="table-header">
                            <div class="table-title">
                                <h3>Item Information</h3>
                                <p>Quick overview</p>
                            </div>
                        </div>
                        <div class="table-container">
                            <table class="modern-table">
                                <tbody>
                                    <tr>
                                        <td style="width: 40%; font-weight: 600; color: var(--gray-700);">Item Name</td>
                                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 600; color: var(--gray-700);">Category</td>
                                        <td>
                                            <span class="category-badge">
                                                <i class="fas fa-tag"></i>
                                                <?php echo htmlspecialchars($item['category_name']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 600; color: var(--gray-700);">Serial Number</td>
                                        <td>
                                            <span class="serial-number">
                                                <?php echo htmlspecialchars($item['serial_number'] ?: 'N/A'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 600; color: var(--gray-700);">Current Status</td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($item['status']); ?>">
                                                <?php echo htmlspecialchars($item['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Maintenance Form -->
                <div>
                    <div class="table-card">
                        <div class="table-header">
                            <div class="table-title">
                                <h3>Maintenance Details</h3>
                                <p>Enter maintenance information</p>
                            </div>
                        </div>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $item_id); ?>" method="post" class="form-container">
                            <div class="form-section">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="form-label">Maintenance Date <span class="required">*</span></label>
                                        <input type="date" name="maintenance_date" class="form-input <?php echo (!empty($maintenance_date_err)) ? 'input-error' : ''; ?>" value="<?php echo $maintenance_date ?: date('Y-m-d'); ?>">
                                        <?php if(!empty($maintenance_date_err)): ?>
                                            <span class="error-message"><?php echo $maintenance_date_err; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Maintenance Type <span class="required">*</span></label>
                                        <input type="text" name="maintenance_type" class="form-input <?php echo (!empty($maintenance_type_err)) ? 'input-error' : ''; ?>" value="<?php echo $maintenance_type; ?>" placeholder="e.g., Repair, Service, Upgrade">
                                        <?php if(!empty($maintenance_type_err)): ?>
                                            <span class="error-message"><?php echo $maintenance_type_err; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="form-label">Performed By <span class="required">*</span></label>
                                        <input type="text" name="performed_by" class="form-input <?php echo (!empty($performed_by_err)) ? 'input-error' : ''; ?>" value="<?php echo $performed_by; ?>" placeholder="Person or company name">
                                        <?php if(!empty($performed_by_err)): ?>
                                            <span class="error-message"><?php echo $performed_by_err; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Cost (RM) <span class="required">*</span></label>
                                        <div class="input-with-prefix">
                                            <span class="input-prefix">RM</span>
                                            <input type="text" name="cost" class="form-input with-prefix <?php echo (!empty($cost_err)) ? 'input-error' : ''; ?>" value="<?php echo $cost; ?>" placeholder="0.00">
                                        </div>
                                        <?php if(!empty($cost_err)): ?>
                                            <span class="error-message"><?php echo $cost_err; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group full-width">
                                    <label class="form-label">Description <span class="required">*</span></label>
                                    <textarea name="description" class="form-input form-textarea <?php echo (!empty($description_err)) ? 'input-error' : ''; ?>" placeholder="Detailed description of maintenance performed"><?php echo $description; ?></textarea>
                                    <?php if(!empty($description_err)): ?>
                                        <span class="error-message"><?php echo $description_err; ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group full-width">
                                    <label class="form-label">Next Scheduled Maintenance Date</label>
                                    <input type="date" name="next_maintenance_date" class="form-input" value="<?php echo $next_maintenance_date; ?>">
                                    <small class="field-hint">Leave blank if no scheduled maintenance is planned</small>
                                </div>

                                <div class="form-group full-width">
                                    <div style="display: flex; align-items: center; padding: 1rem; background: var(--gray-50); border-radius: var(--border-radius-sm); border: 1px solid var(--gray-200);">
                                        <input type="checkbox" id="updateStatus" name="update_status" value="1" <?php echo ($item['status'] != 'Maintenance') ? 'checked' : ''; ?> style="margin-right: 0.75rem; width: 18px; height: 18px; cursor: pointer;">
                                        <label for="updateStatus" style="cursor: pointer; margin: 0; font-size: 0.875rem; color: var(--gray-700);">
                                            <strong>Update item status to "Maintenance"</strong>
                                            <br>
                                            <small style="color: var(--gray-500);">
                                                <?php if($item['status'] == 'Maintenance'): ?>
                                                    Item is already in Maintenance status
                                                <?php else: ?>
                                                    Check this to change the item status to "Maintenance"
                                                <?php endif; ?>
                                            </small>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-primary-large">
                                    <i class="fas fa-save"></i>
                                    Save Record
                                </button>
                                <a href="view.php?id=<?php echo $item_id; ?>" class="btn-secondary-large">
                                    <i class="fas fa-times"></i>
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Maintenance History -->
            <div class="table-card" data-aos="fade-up" data-aos-delay="200">
                <div class="table-header">
                    <div class="table-title">
                        <h3>Maintenance History</h3>
                        <p><?php echo count($maintenance_records); ?> previous records</p>
                    </div>
                </div>
                <div class="table-container">
                    <?php if(!empty($maintenance_records)): ?>
                        <table class="modern-table">
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
                                    <td>
                                        <div class="date-display">
                                            <i class="fas fa-calendar"></i>
                                            <?php echo date('d M Y', strtotime($record['maintenance_date'])); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['maintenance_type']); ?></td>
                                    <td><?php echo htmlspecialchars($record['performed_by']); ?></td>
                                    <td style="font-weight: 600; color: var(--danger-color);">RM <?php echo number_format($record['cost'], 2); ?></td>
                                    <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($record['description']); ?>">
                                        <?php echo htmlspecialchars(substr($record['description'], 0, 50)) . (strlen($record['description']) > 50 ? '...' : ''); ?>
                                    </td>
                                    <td>
                                        <?php
                                        if(!empty($record['next_maintenance_date'])) {
                                            echo date('d M Y', strtotime($record['next_maintenance_date']));

                                            // Check if next maintenance is due
                                            $today = new DateTime();
                                            $next_date = new DateTime($record['next_maintenance_date']);
                                            if($today > $next_date) {
                                                echo '<br><span class="status-badge status-overdue">Overdue</span>';
                                            } else {
                                                $interval = $today->diff($next_date);
                                                if($interval->days <= 30) {
                                                    echo '<br><span class="status-badge status-pending">Due soon</span>';
                                                }
                                            }
                                        } else {
                                            echo '<span style="color: var(--gray-400);">N/A</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['created_by_name']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-data">
                            <div class="no-data-content">
                                <i class="fas fa-tools"></i>
                                <h3>No Maintenance Records</h3>
                                <p>This will be the first maintenance record for this item.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
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

    <style>
        .category-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .category-badge i {
            font-size: 0.625rem;
        }

        .serial-number {
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
            color: var(--gray-600);
            background: var(--gray-100);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }

        .date-display {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        .date-display i {
            color: var(--gray-400);
            font-size: 0.75rem;
        }

        .status-available {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .status-assigned {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary-color);
        }

        .status-maintenance {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .status-disposed {
            background: rgba(107, 114, 128, 0.1);
            color: var(--gray-500);
        }

        .no-data {
            padding: 3rem !important;
        }

        .no-data-content {
            text-align: center;
        }

        .no-data-content i {
            font-size: 3rem;
            color: var(--gray-300);
            margin-bottom: 1rem;
        }

        .no-data-content h3 {
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        .no-data-content p {
            color: var(--gray-500);
        }
    </style>
</body>
</html>
