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
$transaction_type = $to_status = $assigned_to = $notes = "";
$transaction_type_err = $to_status_err = $assigned_to_err = $notes_err = "";

// Preselect transaction type if provided in URL
$preselected_type = isset($_GET['type']) ? $_GET['type'] : '';

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

    // Validate assigned_to (only for Assignment transaction type)
    if($transaction_type == "Assignment"){
        if(empty($_POST["assigned_to"])){
            $assigned_to_err = "Please select staff member for assignment.";
        } else{
            $assigned_to = $_POST["assigned_to"];
        }
    }

    // Validate notes (optional)
    $notes = trim($_POST["notes"]);

    // Check input errors before inserting in database
    if(empty($transaction_type_err) && empty($to_status_err) && empty($assigned_to_err)){
        // Begin transaction
        $pdo->beginTransaction();

        try {
            // Get current status
            $from_status = $item['status'];

            // Update item status
            $update_sql = "UPDATE inventory_items SET status = :status";
            $params = [':status' => $to_status, ':item_id' => $item_id];

            // If assignment, update assigned_to field
            if($transaction_type == "Assignment" && !empty($assigned_to)){
                $update_sql .= ", assigned_to = :assigned_to";
                $params[':assigned_to'] = $assigned_to;
            } elseif($transaction_type == "Return"){
                // Clear assigned_to on return
                $update_sql .= ", assigned_to = NULL";
            }

            $update_sql .= " WHERE item_id = :item_id";

            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute($params);

            // Create transaction record
            $transaction_sql = "INSERT INTO inventory_transactions (item_id, transaction_type,
                                from_status, to_status, transaction_date, assigned_to, notes, performed_by)
                                VALUES (:item_id, :transaction_type, :from_status, :to_status,
                                NOW(), :assigned_to, :notes, :performed_by)";

            $trans_stmt = $pdo->prepare($transaction_sql);
            $trans_stmt->bindParam(":item_id", $item_id, PDO::PARAM_INT);
            $trans_stmt->bindParam(":transaction_type", $transaction_type, PDO::PARAM_STR);
            $trans_stmt->bindParam(":from_status", $from_status, PDO::PARAM_STR);
            $trans_stmt->bindParam(":to_status", $to_status, PDO::PARAM_STR);

            if($transaction_type == "Assignment" && !empty($assigned_to)){
                $trans_stmt->bindParam(":assigned_to", $assigned_to, PDO::PARAM_INT);
            } else {
                $null_value = null;
                $trans_stmt->bindParam(":assigned_to", $null_value, PDO::PARAM_NULL);
            }

            $trans_stmt->bindParam(":notes", $notes, PDO::PARAM_STR);
            $trans_stmt->bindParam(":performed_by", $_SESSION["staff_id"], PDO::PARAM_INT);
            $trans_stmt->execute();

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

// Fetch all staff members for assignment dropdown
try {
    $stmt = $pdo->query("SELECT staff_id, full_name, position FROM staff ORDER BY full_name");
    $staff_members = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Fetch transaction history
try {
    $stmt = $pdo->prepare("SELECT t.*, s.full_name as performed_by_name,
                          a.full_name as assigned_to_name
                          FROM inventory_transactions t
                          LEFT JOIN staff s ON t.performed_by = s.staff_id
                          LEFT JOIN staff a ON t.assigned_to = a.staff_id
                          WHERE t.item_id = :id
                          ORDER BY t.transaction_date DESC
                          LIMIT 10");
    $stmt->bindParam(":id", $item_id, PDO::PARAM_INT);
    $stmt->execute();
    $transactions = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Transaction - SOA Management System</title>

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
                        <h1>Inventory Transaction</h1>
                        <p>Manage inventory item status and assignments</p>
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
                    <i class="fas fa-exchange-alt"></i>
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
                                    <?php if(!empty($item['assigned_to'])): ?>
                                    <tr>
                                        <td style="font-weight: 600; color: var(--gray-700);">Currently Assigned To</td>
                                        <td>
                                            <i class="fas fa-user" style="color: var(--gray-400); margin-right: 0.5rem;"></i>
                                            <?php
                                            try {
                                                $stmt = $pdo->prepare("SELECT full_name FROM staff WHERE staff_id = :id");
                                                $stmt->bindParam(":id", $item['assigned_to']);
                                                $stmt->execute();
                                                $assigned_staff = $stmt->fetch();
                                                echo htmlspecialchars($assigned_staff['full_name']);
                                            } catch(PDOException $e) {
                                                echo "N/A";
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Transaction Form -->
                <div>
                    <div class="table-card">
                        <div class="table-header">
                            <div class="table-title">
                                <h3>Transaction Details</h3>
                                <p>Update item status or assignment</p>
                            </div>
                        </div>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $item_id); ?>" method="post" class="form-container" id="transactionForm">
                            <div class="form-section">
                                <div class="form-group full-width">
                                    <label class="form-label">Transaction Type <span class="required">*</span></label>
                                    <select name="transaction_type" id="transactionType" class="form-input <?php echo (!empty($transaction_type_err)) ? 'input-error' : ''; ?>" onchange="updateFormFields()">
                                        <option value="">Select transaction type</option>
                                        <option value="Assignment" <?php echo ($preselected_type == 'Assignment' || $transaction_type == 'Assignment') ? 'selected' : ''; ?>>Assignment (Assign to staff)</option>
                                        <option value="Return" <?php echo ($preselected_type == 'Return' || $transaction_type == 'Return') ? 'selected' : ''; ?>>Return (Mark as available)</option>
                                        <option value="Maintenance" <?php echo ($preselected_type == 'Maintenance' || $transaction_type == 'Maintenance') ? 'selected' : ''; ?>>Maintenance (Send for repair/service)</option>
                                        <option value="Disposal" <?php echo ($transaction_type == 'Disposal') ? 'selected' : ''; ?>>Disposal (Mark as disposed)</option>
                                        <option value="Status Change" <?php echo ($transaction_type == 'Status Change') ? 'selected' : ''; ?>>Status Change (Manual status update)</option>
                                    </select>
                                    <?php if(!empty($transaction_type_err)): ?>
                                        <span class="error-message"><?php echo $transaction_type_err; ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group full-width">
                                    <label class="form-label">New Status <span class="required">*</span></label>
                                    <select name="to_status" id="toStatus" class="form-input <?php echo (!empty($to_status_err)) ? 'input-error' : ''; ?>">
                                        <option value="">Select new status</option>
                                        <option value="Available" <?php echo ($to_status == 'Available') ? 'selected' : ''; ?>>Available</option>
                                        <option value="Assigned" <?php echo ($to_status == 'Assigned') ? 'selected' : ''; ?>>Assigned</option>
                                        <option value="Maintenance" <?php echo ($to_status == 'Maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                                        <option value="Disposed" <?php echo ($to_status == 'Disposed') ? 'selected' : ''; ?>>Disposed</option>
                                    </select>
                                    <?php if(!empty($to_status_err)): ?>
                                        <span class="error-message"><?php echo $to_status_err; ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group full-width" id="assignedToField" style="display: none;">
                                    <label class="form-label">Assign To Staff <span class="required">*</span></label>
                                    <select name="assigned_to" id="assignedTo" class="form-input <?php echo (!empty($assigned_to_err)) ? 'input-error' : ''; ?>">
                                        <option value="">Select staff member</option>
                                        <?php foreach($staff_members as $staff): ?>
                                            <option value="<?php echo $staff['staff_id']; ?>" <?php echo ($assigned_to == $staff['staff_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($staff['full_name']) . ' (' . $staff['position'] . ')'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if(!empty($assigned_to_err)): ?>
                                        <span class="error-message"><?php echo $assigned_to_err; ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group full-width">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-input form-textarea" placeholder="Optional notes about this transaction"><?php echo $notes; ?></textarea>
                                    <small class="field-hint">Provide additional details about this transaction</small>
                                </div>

                                <!-- Transaction Summary -->
                                <div class="form-group full-width">
                                    <div style="padding: 1rem; background: var(--gray-50); border-radius: var(--border-radius-sm); border: 1px solid var(--gray-200);">
                                        <h4 style="font-size: 0.875rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.75rem;">Transaction Summary</h4>
                                        <div style="display: flex; align-items: center; gap: 1rem; font-size: 0.875rem;">
                                            <div>
                                                <span style="color: var(--gray-500);">Current Status:</span>
                                                <strong style="color: var(--gray-700);"><?php echo $item['status']; ?></strong>
                                            </div>
                                            <i class="fas fa-arrow-right" style="color: var(--gray-400);"></i>
                                            <div>
                                                <span style="color: var(--gray-500);">New Status:</span>
                                                <strong id="summaryStatus" style="color: var(--primary-color);">-</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-primary-large">
                                    <i class="fas fa-check"></i>
                                    Complete Transaction
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

            <!-- Transaction History -->
            <div class="table-card" data-aos="fade-up" data-aos-delay="200">
                <div class="table-header">
                    <div class="table-title">
                        <h3>Recent Transactions</h3>
                        <p>Last 10 transactions for this item</p>
                    </div>
                    <div class="table-actions">
                        <a href="view.php?id=<?php echo $item_id; ?>" class="table-action-btn" title="View All">
                            <i class="fas fa-list"></i>
                        </a>
                    </div>
                </div>
                <div class="table-container">
                    <?php if(!empty($transactions)): ?>
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Status Change</th>
                                    <th>Assigned To</th>
                                    <th>Performed By</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($transactions as $transaction): ?>
                                <tr>
                                    <td>
                                        <div class="date-display">
                                            <i class="fas fa-calendar"></i>
                                            <?php echo date('d M Y H:i', strtotime($transaction['transaction_date'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php
                                            echo ($transaction['transaction_type'] == 'Purchase') ? 'paid' :
                                                (($transaction['transaction_type'] == 'Assignment') ? 'assigned' :
                                                (($transaction['transaction_type'] == 'Return') ? 'available' : 'pending'));
                                        ?>">
                                            <?php echo htmlspecialchars($transaction['transaction_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.875rem;">
                                            <?php
                                            if(!empty($transaction['from_status'])) {
                                                echo '<span style="color: var(--gray-500);">' . htmlspecialchars($transaction['from_status']) . '</span>';
                                                echo ' <i class="fas fa-arrow-right" style="color: var(--gray-400); font-size: 0.75rem; margin: 0 0.25rem;"></i> ';
                                            }
                                            echo '<span style="font-weight: 600;">' . htmlspecialchars($transaction['to_status'] ?: 'N/A') . '</span>';
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        if(!empty($transaction['assigned_to_name'])) {
                                            echo '<i class="fas fa-user" style="color: var(--gray-400); margin-right: 0.5rem;"></i>';
                                            echo htmlspecialchars($transaction['assigned_to_name']);
                                        } else {
                                            echo '<span style="color: var(--gray-400);">-</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['performed_by_name']); ?></td>
                                    <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($transaction['notes']); ?>">
                                        <?php echo htmlspecialchars($transaction['notes'] ?: '-'); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-data">
                            <div class="no-data-content">
                                <i class="fas fa-exchange-alt"></i>
                                <h3>No Transactions</h3>
                                <p>This will be the first transaction for this item.</p>
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

            // Initialize form on page load
            updateFormFields();
        });

        // Update form fields based on transaction type
        function updateFormFields() {
            const transactionType = document.getElementById('transactionType').value;
            const toStatus = document.getElementById('toStatus');
            const assignedToField = document.getElementById('assignedToField');
            const assignedTo = document.getElementById('assignedTo');

            // Reset status dropdown
            toStatus.value = '';

            // Show/hide assigned to field and auto-select status
            if (transactionType === 'Assignment') {
                assignedToField.style.display = 'block';
                assignedTo.required = true;
                toStatus.value = 'Assigned';
            } else if (transactionType === 'Return') {
                assignedToField.style.display = 'none';
                assignedTo.required = false;
                toStatus.value = 'Available';
            } else if (transactionType === 'Maintenance') {
                assignedToField.style.display = 'none';
                assignedTo.required = false;
                toStatus.value = 'Maintenance';
            } else if (transactionType === 'Disposal') {
                assignedToField.style.display = 'none';
                assignedTo.required = false;
                toStatus.value = 'Disposed';
            } else {
                assignedToField.style.display = 'none';
                assignedTo.required = false;
            }

            updateSummary();
        }

        // Update status in summary
        function updateSummary() {
            const toStatus = document.getElementById('toStatus').value;
            const summaryStatus = document.getElementById('summaryStatus');

            summaryStatus.textContent = toStatus || '-';
        }

        // Update summary when status changes
        document.getElementById('toStatus').addEventListener('change', updateSummary);
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

        .status-paid {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
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
