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
    $access_denied = true;
} else {
    $access_denied = false;
}

// Process delete operation
if(!$access_denied && isset($_GET["action"]) && $_GET["action"] == "delete" && isset($_GET["id"]) && !empty($_GET["id"])){
    try {
        // Check if item has associated transactions
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM inventory_transactions WHERE item_id = :id");
        $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();

        if($result['count'] > 0) {
            $delete_err = "Cannot delete item because it has associated transactions.";
        } else {
            // Prepare a delete statement
            $sql = "DELETE FROM inventory_items WHERE item_id = :id";

            if($stmt = $pdo->prepare($sql)){
                // Bind variables to the prepared statement as parameters
                $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);

                // Set parameters
                $param_id = trim($_GET["id"]);

                // Attempt to execute the prepared statement
                if($stmt->execute()){
                    // Records deleted successfully. Redirect to landing page
                    header("location: index.php?success=deleted");
                    exit();
                } else{
                    $delete_err = "Oops! Something went wrong. Please try again later.";
                }
            }
        }
    } catch(PDOException $e) {
        $delete_err = "Error: " . $e->getMessage();
    }
}

// Fetch inventory items with category and supplier details
if(!$access_denied) {
    try {
        // Check if status filter is applied
        $status_filter = isset($_GET['status']) ? $_GET['status'] : null;

        if($status_filter) {
            $stmt = $pdo->prepare("SELECT i.*, c.category_name, s.supplier_name
                                 FROM inventory_items i
                                 LEFT JOIN inventory_categories c ON i.category_id = c.category_id
                                 LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id
                                 WHERE i.status = :status
                                 ORDER BY i.item_name");
            $stmt->bindParam(':status', $status_filter);
            $stmt->execute();
        } else {
            $stmt = $pdo->query("SELECT i.*, c.category_name, s.supplier_name
                                 FROM inventory_items i
                                 LEFT JOIN inventory_categories c ON i.category_id = c.category_id
                                 LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id
                                 ORDER BY i.item_name");
        }
        $items = $stmt->fetchAll();

        // Calculate statistics
        $all_items = $pdo->query("SELECT status FROM inventory_items")->fetchAll();
        $total = count($all_items);
        $available = count(array_filter($all_items, fn($item) => $item['status'] === 'Available'));
        $assigned = count(array_filter($all_items, fn($item) => $item['status'] === 'Assigned'));
        $maintenance = count(array_filter($all_items, fn($item) => $item['status'] === 'Maintenance'));
        $disposed = count(array_filter($all_items, fn($item) => $item['status'] === 'Disposed'));

    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - SOA Management System</title>

    <!-- Modern CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/modern-dashboard.css">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                        <h1>Inventory Management</h1>
                        <p>Track and manage inventory items and assets</p>
                    </div>
                </div>
                <div class="header-right">
                    <?php if(!$access_denied): ?>
                    <a href="add.php" class="export-btn">
                        <i class="fas fa-plus"></i>
                        Add New Item
                    </a>
                    <a href="categories.php" class="export-btn" style="margin-left: 10px;">
                        <i class="fas fa-tags"></i>
                        Manage Categories
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <?php if($access_denied): ?>
            <!-- Access Denied -->
            <div class="access-denied-card">
                <div class="access-denied-content">
                    <div class="access-denied-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h2>Access Denied</h2>
                    <p>You do not have permission to access this page. Only administrators can manage inventory items.</p>
                    <a href="<?php echo $basePath; ?>dashboard.php" class="btn-primary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
            <?php else: ?>

            <!-- Success/Error Messages -->
            <?php if(isset($_GET["success"])): ?>
                <div class="alert alert-success" data-aos="fade-down">
                    <div class="alert-content">
                        <i class="fas fa-check-circle"></i>
                        <span>
                            <?php
                            if($_GET["success"] == "deleted") {
                                echo "Inventory item has been deleted successfully.";
                            } elseif($_GET["success"] == "2") {
                                echo "Inventory item has been updated successfully.";
                            } elseif($_GET["success"] == "3") {
                                echo "Inventory item has been added successfully.";
                            } else {
                                echo "Operation completed successfully.";
                            }
                            ?>
                        </span>
                    </div>
                    <button class="alert-close" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if(isset($delete_err)): ?>
                <div class="alert alert-error" data-aos="fade-down">
                    <div class="alert-content">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $delete_err; ?></span>
                    </div>
                    <button class="alert-close" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid" data-aos="fade-up">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: var(--primary-color);">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-label">Total Items</div>
                        <div class="stat-value"><?php echo $total; ?></div>
                        <div class="stat-change positive">All inventory</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success-color);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-label">Available</div>
                        <div class="stat-value"><?php echo $available; ?></div>
                        <div class="stat-change positive"><?php echo $total > 0 ? round(($available/$total)*100, 1) : 0; ?>% available</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-label">Assigned</div>
                        <div class="stat-value"><?php echo $assigned; ?></div>
                        <div class="stat-change"><?php echo $total > 0 ? round(($assigned/$total)*100, 1) : 0; ?>% in use</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--warning-color);">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-label">Maintenance</div>
                        <div class="stat-value"><?php echo $maintenance; ?></div>
                        <div class="stat-change">Under repair</div>
                    </div>
                </div>
            </div>

            <!-- Inventory Table -->
            <div class="table-card" data-aos="fade-up">
                <div class="table-header">
                    <div class="table-title">
                        <h3>Inventory Items</h3>
                        <p>All inventory items and their current status</p>
                    </div>
                    <div class="table-actions">
                        <div class="filter-dropdown" style="display: inline-block; margin-right: 10px;">
                            <select onchange="filterByStatus(this.value)" class="filter-select">
                                <option value="">All Status</option>
                                <option value="Available" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Available') ? 'selected' : ''; ?>>Available</option>
                                <option value="Assigned" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Assigned') ? 'selected' : ''; ?>>Assigned</option>
                                <option value="Maintenance" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                                <option value="Disposed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Disposed') ? 'selected' : ''; ?>>Disposed</option>
                            </select>
                        </div>
                        <button class="table-action-btn" onclick="refreshTable()">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button class="table-action-btn" onclick="exportTable()">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                </div>
                <div class="table-container">
                    <table class="modern-table" id="inventoryTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Item Info</th>
                                <th>Category</th>
                                <th>Supplier</th>
                                <th>Serial Number</th>
                                <th>Purchase Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($items)): ?>
                                <?php foreach($items as $item): ?>
                                <tr class="table-row-clickable" data-href="view.php?id=<?php echo $item['item_id']; ?>">
                                    <td class="font-medium">#<?php echo str_pad($item['item_id'], 3, '0', STR_PAD_LEFT); ?></td>
                                    <td>
                                        <div class="item-info">
                                            <div class="item-icon">
                                                <i class="fas fa-box"></i>
                                            </div>
                                            <div class="item-details">
                                                <div class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                                <div class="item-model"><?php echo htmlspecialchars($item['model_number'] ?: 'No model'); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="category-badge">
                                            <i class="fas fa-tag"></i>
                                            <?php echo htmlspecialchars($item['category_name'] ?: 'Uncategorized'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="supplier-display">
                                            <?php echo htmlspecialchars($item['supplier_name'] ?: 'No supplier'); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="serial-number">
                                            <?php echo htmlspecialchars($item['serial_number'] ?: 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="date-display">
                                            <i class="fas fa-calendar"></i>
                                            <?php echo date('d M Y', strtotime($item['purchase_date'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($item['status']); ?>">
                                            <?php echo $item['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn action-btn-view" onclick="event.stopPropagation(); viewItem(<?php echo $item['item_id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn action-btn-edit" onclick="event.stopPropagation(); editItem(<?php echo $item['item_id']; ?>)" title="Edit Item">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn action-btn-transaction" onclick="event.stopPropagation(); manageTransactions(<?php echo $item['item_id']; ?>)" title="Manage Status">
                                                <i class="fas fa-exchange-alt"></i>
                                            </button>
                                            <button class="action-btn action-btn-delete" onclick="event.stopPropagation(); deleteItem(<?php echo $item['item_id']; ?>, '<?php echo htmlspecialchars($item['item_name']); ?>')" title="Delete Item">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center no-data">
                                    <div class="no-data-content">
                                        <i class="fas fa-boxes"></i>
                                        <h3>No Inventory Items Found</h3>
                                        <p>There are no inventory items<?php echo isset($_GET['status']) ? ' with status "' . htmlspecialchars($_GET['status']) . '"' : ' in the system yet'; ?>.</p>
                                        <?php if(!isset($_GET['status'])): ?>
                                        <a href="add.php" class="btn-primary">
                                            <i class="fas fa-plus"></i>
                                            Add First Item
                                        </a>
                                        <?php else: ?>
                                        <button onclick="window.location.href='index.php'" class="btn-primary">
                                            <i class="fas fa-list"></i>
                                            View All Items
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
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

        // Inventory management functions
        function viewItem(id) {
            window.location.href = `view.php?id=${id}`;
        }

        function editItem(id) {
            window.location.href = `edit.php?id=${id}`;
        }

        function manageTransactions(id) {
            window.location.href = `transactions.php?id=${id}`;
        }

        function deleteItem(id, name) {
            if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone if there are no associated transactions.`)) {
                window.location.href = `index.php?action=delete&id=${id}`;
            }
        }

        function filterByStatus(status) {
            if(status === '') {
                window.location.href = 'index.php';
            } else {
                window.location.href = `index.php?status=${status}`;
            }
        }

        function refreshTable() {
            location.reload();
        }

        function exportTable() {
            // Implement export functionality
            console.log('Exporting inventory data...');
        }

        // Make table rows clickable
        document.querySelectorAll('.table-row-clickable').forEach(row => {
            row.style.cursor = 'pointer';
            row.addEventListener('click', function() {
                const href = this.dataset.href;
                if (href) {
                    window.location.href = href;
                }
            });
        });
    </script>

    <style>
        /* Inventory Management Specific Styles */
        .access-denied-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            padding: 3rem;
            text-align: center;
            max-width: 500px;
            margin: 2rem auto;
        }

        .access-denied-icon {
            width: 80px;
            height: 80px;
            background: rgba(239, 68, 68, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: var(--danger-color);
            font-size: 2rem;
        }

        .access-denied-card h2 {
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }

        .access-denied-card p {
            color: var(--gray-600);
            margin-bottom: 2rem;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius-sm);
            font-weight: 500;
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            color: white;
            text-decoration: none;
        }

        /* Alert Styles */
        .alert {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            border: 1px solid;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border-color: var(--success-color);
            color: var(--success-color);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border-color: var(--danger-color);
            color: var(--danger-color);
        }

        .alert-content {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-close {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: var(--border-radius-sm);
            transition: var(--transition);
        }

        .alert-close:hover {
            background: rgba(0, 0, 0, 0.1);
        }

        /* Item Info Styles */
        .item-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .item-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .item-name {
            font-weight: 600;
            color: var(--gray-900);
            font-size: 0.875rem;
        }

        .item-model {
            font-size: 0.75rem;
            color: var(--gray-500);
        }

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

        .supplier-display {
            font-size: 0.875rem;
            color: var(--gray-700);
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

        /* Status Badge Styles */
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
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

        /* Filter Dropdown */
        .filter-select {
            padding: 0.5rem 2rem 0.5rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius-sm);
            font-size: 0.875rem;
            color: var(--gray-700);
            background: white;
            cursor: pointer;
            transition: var(--transition);
        }

        .filter-select:hover {
            border-color: var(--primary-color);
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: var(--border-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.875rem;
        }

        .action-btn-view {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary-color);
        }

        .action-btn-view:hover {
            background: var(--primary-color);
            color: white;
        }

        .action-btn-edit {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .action-btn-edit:hover {
            background: var(--warning-color);
            color: white;
        }

        .action-btn-transaction {
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
        }

        .action-btn-transaction:hover {
            background: #8b5cf6;
            color: white;
        }

        .action-btn-delete {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .action-btn-delete:hover {
            background: var(--danger-color);
            color: white;
        }

        /* No Data Styles */
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
            margin-bottom: 1.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .item-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .action-buttons {
                flex-wrap: wrap;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</body>
</html>
