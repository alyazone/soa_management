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

// Check if id parameter is set
if(empty($_GET["id"])){
    header("location: index.php");
    exit();
}

// Fetch item data with category and supplier details
try {
    $stmt = $pdo->prepare("SELECT i.*, c.category_name, s.supplier_name, s.pic_name as supplier_contact_name,
                          s.pic_contact as supplier_contact, s.pic_email as supplier_email,
                          st.full_name as created_by_name
                          FROM inventory_items i
                          LEFT JOIN inventory_categories c ON i.category_id = c.category_id
                          LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id
                          LEFT JOIN staff st ON i.created_by = st.staff_id
                          WHERE i.item_id = :id");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();

    if($stmt->rowCount() != 1){
        header("location: index.php");
        exit();
    }

    $item = $stmt->fetch(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("ERROR: Could not fetch item. " . $e->getMessage());
}

// Fetch transaction history
try {
    $stmt = $pdo->prepare("SELECT t.*, s.full_name as performed_by_name,
                          a.full_name as assigned_to_name
                          FROM inventory_transactions t
                          LEFT JOIN staff s ON t.performed_by = s.staff_id
                          LEFT JOIN staff a ON t.assigned_to = a.staff_id
                          WHERE t.item_id = :id
                          ORDER BY t.transaction_date DESC");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    $transactions = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Fetch maintenance records
try {
    $stmt = $pdo->prepare("SELECT m.*, s.full_name as created_by_name
                          FROM inventory_maintenance m
                          LEFT JOIN staff s ON m.created_by = s.staff_id
                          WHERE m.item_id = :id
                          ORDER BY m.maintenance_date DESC");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    $maintenance_records = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Fetch documents related to this item
try {
    $stmt = $pdo->prepare("SELECT * FROM documents
                          WHERE reference_type = 'Inventory' AND reference_id = :id
                          ORDER BY upload_date DESC");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    $documents = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Inventory Item - SOA Management System</title>

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
                        <h1>Inventory Item Details</h1>
                        <p>View and manage inventory item information</p>
                    </div>
                </div>
                <div class="header-right">
                    <?php if($_SESSION['position'] == 'Admin'): ?>
                    <a href="edit.php?id=<?php echo $item['item_id']; ?>" class="date-picker-btn">
                        <i class="fas fa-edit"></i>
                        Edit Item
                    </a>
                    <?php endif; ?>
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
                    <i class="fas fa-box"></i>
                </div>
                <div class="info-card-content">
                    <h4><?php echo htmlspecialchars($item['item_name']); ?></h4>
                    <p>Item ID: <strong>#<?php echo str_pad($item['item_id'], 3, '0', STR_PAD_LEFT); ?></strong> | Category: <strong><?php echo htmlspecialchars($item['category_name']); ?></strong> | Status: <span class="status-badge status-<?php echo strtolower($item['status']); ?>"><?php echo htmlspecialchars($item['status']); ?></span></p>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="tables-grid" data-aos="fade-up" data-aos-delay="100">
                <!-- Left Column -->
                <div>
                    <!-- Item Information -->
                    <div class="table-card" style="margin-bottom: 1.5rem;">
                        <div class="table-header">
                            <div class="table-title">
                                <h3>Item Information</h3>
                                <p>Basic details and specifications</p>
                            </div>
                        </div>
                        <div class="table-container">
                            <table class="modern-table">
                                <tbody>
                                    <tr>
                                        <td style="width: 35%; font-weight: 600; color: var(--gray-700);">Item Name</td>
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
                                        <td style="font-weight: 600; color: var(--gray-700);">Model Number</td>
                                        <td><?php echo htmlspecialchars($item['model_number'] ?: 'N/A'); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 600; color: var(--gray-700);">Status</td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($item['status']); ?>">
                                                <?php echo htmlspecialchars($item['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 600; color: var(--gray-700);">Location</td>
                                        <td>
                                            <i class="fas fa-map-marker-alt" style="color: var(--gray-400); margin-right: 0.5rem;"></i>
                                            <?php echo htmlspecialchars($item['location'] ?: 'N/A'); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 600; color: var(--gray-700);">Purchase Date</td>
                                        <td>
                                            <i class="fas fa-calendar" style="color: var(--gray-400); margin-right: 0.5rem;"></i>
                                            <?php echo date('d M Y', strtotime($item['purchase_date'])); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 600; color: var(--gray-700);">Purchase Price</td>
                                        <td style="font-weight: 600; color: var(--primary-color);">
                                            RM <?php echo number_format($item['purchase_price'], 2); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 600; color: var(--gray-700);">Warranty Expiry</td>
                                        <td>
                                            <?php
                                            if(!empty($item['warranty_expiry'])) {
                                                echo date('d M Y', strtotime($item['warranty_expiry']));

                                                // Check if warranty is expired
                                                $today = new DateTime();
                                                $warranty_date = new DateTime($item['warranty_expiry']);
                                                if($today > $warranty_date) {
                                                    echo ' <span class="status-badge status-overdue">Expired</span>';
                                                } else {
                                                    $interval = $today->diff($warranty_date);
                                                    echo ' <span class="status-badge status-paid">' . $interval->format('%y years, %m months remaining') . '</span>';
                                                }
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 600; color: var(--gray-700);">Created By</td>
                                        <td>
                                            <i class="fas fa-user" style="color: var(--gray-400); margin-right: 0.5rem;"></i>
                                            <?php echo htmlspecialchars($item['created_by_name']); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 600; color: var(--gray-700);">Created At</td>
                                        <td><?php echo date('d M Y H:i', strtotime($item['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 600; color: var(--gray-700);">Notes</td>
                                        <td><?php echo nl2br(htmlspecialchars($item['notes'] ?: 'No notes available')); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Supplier Information -->
                    <?php if(!empty($item['supplier_id'])): ?>
                    <div class="table-card" style="margin-bottom: 1.5rem;">
                        <div class="table-header">
                            <div class="table-title">
                                <h3>Supplier Information</h3>
                                <p>Supplier details</p>
                            </div>
                            <div class="table-actions">
                                <a href="<?php echo $basePath; ?>modules/suppliers/view.php?id=<?php echo $item['supplier_id']; ?>" class="table-action-btn" title="View Supplier">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </div>
                        <div class="table-container">
                            <table class="modern-table">
                                <tbody>
                                    <tr>
                                        <td style="width: 35%; font-weight: 600; color: var(--gray-700);">Supplier Name</td>
                                        <td><?php echo htmlspecialchars($item['supplier_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 600; color: var(--gray-700);">Contact Person</td>
                                        <td><?php echo htmlspecialchars($item['supplier_contact_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 600; color: var(--gray-700);">Contact Number</td>
                                        <td>
                                            <i class="fas fa-phone" style="color: var(--gray-400); margin-right: 0.5rem;"></i>
                                            <?php echo htmlspecialchars($item['supplier_contact']); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 600; color: var(--gray-700);">Email</td>
                                        <td>
                                            <i class="fas fa-envelope" style="color: var(--gray-400); margin-right: 0.5rem;"></i>
                                            <?php echo htmlspecialchars($item['supplier_email']); ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Quick Actions -->
                    <?php if($_SESSION['position'] == 'Admin'): ?>
                    <div class="table-card">
                        <div class="table-header">
                            <div class="table-title">
                                <h3>Quick Actions</h3>
                                <p>Manage this inventory item</p>
                            </div>
                        </div>
                        <div class="form-container">
                            <div class="quick-actions-grid">
                                <a href="transactions.php?id=<?php echo $item['item_id']; ?>&type=Assignment" class="quick-action-btn">
                                    <i class="fas fa-user-plus"></i>
                                    <span>Assign Item</span>
                                </a>
                                <a href="transactions.php?id=<?php echo $item['item_id']; ?>&type=Return" class="quick-action-btn">
                                    <i class="fas fa-undo"></i>
                                    <span>Return Item</span>
                                </a>
                                <a href="transactions.php?id=<?php echo $item['item_id']; ?>&type=Maintenance" class="quick-action-btn">
                                    <i class="fas fa-tools"></i>
                                    <span>Maintenance</span>
                                </a>
                                <a href="<?php echo $basePath; ?>modules/documents/upload.php?reference_type=Inventory&reference_id=<?php echo $item['item_id']; ?>" class="quick-action-btn">
                                    <i class="fas fa-file-upload"></i>
                                    <span>Upload Document</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Right Column -->
                <div>
                    <!-- Transaction History -->
                    <div class="table-card" style="margin-bottom: 1.5rem;">
                        <div class="table-header">
                            <div class="table-title">
                                <h3>Transaction History</h3>
                                <p><?php echo count($transactions); ?> transactions</p>
                            </div>
                            <?php if($_SESSION['position'] == 'Admin'): ?>
                            <div class="table-actions">
                                <a href="transactions.php?id=<?php echo $item['item_id']; ?>" class="table-action-btn" title="New Transaction">
                                    <i class="fas fa-plus"></i>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="table-container">
                            <?php if(!empty($transactions)): ?>
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Status Change</th>
                                            <th>By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($transactions as $transaction): ?>
                                        <tr>
                                            <td>
                                                <div class="date-display">
                                                    <i class="fas fa-calendar"></i>
                                                    <?php echo date('d M Y', strtotime($transaction['transaction_date'])); ?>
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

                                                    if($transaction['transaction_type'] == 'Assignment' && !empty($transaction['assigned_to'])) {
                                                        echo '<br><small style="color: var(--gray-500);">To: ' . htmlspecialchars($transaction['assigned_to_name']) . '</small>';
                                                    }
                                                    ?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($transaction['performed_by_name']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="no-data">
                                    <div class="no-data-content">
                                        <i class="fas fa-exchange-alt"></i>
                                        <h3>No Transactions</h3>
                                        <p>No transaction history found for this item.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Maintenance Records -->
                    <div class="table-card" style="margin-bottom: 1.5rem;">
                        <div class="table-header">
                            <div class="table-title">
                                <h3>Maintenance Records</h3>
                                <p><?php echo count($maintenance_records); ?> records</p>
                            </div>
                            <?php if($_SESSION['position'] == 'Admin'): ?>
                            <div class="table-actions">
                                <a href="maintenance.php?id=<?php echo $item['item_id']; ?>" class="table-action-btn" title="Add Maintenance">
                                    <i class="fas fa-plus"></i>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="table-container">
                            <?php if(!empty($maintenance_records)): ?>
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Cost</th>
                                            <th>Next Service</th>
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
                                            <td style="font-weight: 600; color: var(--danger-color);">
                                                RM <?php echo number_format($record['cost'], 2); ?>
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
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="no-data">
                                    <div class="no-data-content">
                                        <i class="fas fa-tools"></i>
                                        <h3>No Maintenance Records</h3>
                                        <p>No maintenance history found for this item.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Related Documents -->
                    <div class="table-card">
                        <div class="table-header">
                            <div class="table-title">
                                <h3>Related Documents</h3>
                                <p><?php echo count($documents); ?> documents</p>
                            </div>
                            <div class="table-actions">
                                <a href="<?php echo $basePath; ?>modules/documents/upload.php?reference_type=Inventory&reference_id=<?php echo $item['item_id']; ?>" class="table-action-btn" title="Upload Document">
                                    <i class="fas fa-upload"></i>
                                </a>
                            </div>
                        </div>
                        <div class="table-container">
                            <?php if(!empty($documents)): ?>
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>File Name</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($documents as $document): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($document['document_type']); ?></td>
                                            <td>
                                                <i class="fas fa-file" style="color: var(--gray-400); margin-right: 0.5rem;"></i>
                                                <?php echo htmlspecialchars($document['file_name']); ?>
                                            </td>
                                            <td>
                                                <div class="date-display">
                                                    <i class="fas fa-calendar"></i>
                                                    <?php echo date('d M Y', strtotime($document['upload_date'])); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="<?php echo $basePath . $document['file_path']; ?>" class="action-btn action-btn-view" target="_blank" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?php echo $basePath . $document['file_path']; ?>" class="action-btn action-btn-edit" download title="Download">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="no-data">
                                    <div class="no-data-content">
                                        <i class="fas fa-file-alt"></i>
                                        <h3>No Documents</h3>
                                        <p>No documents found for this item. <a href="<?php echo $basePath; ?>modules/documents/upload.php?reference_type=Inventory&reference_id=<?php echo $item['item_id']; ?>" style="color: var(--primary-color); font-weight: 600;">Upload a document</a></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
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
        /* Additional styles for view page */
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
            text-decoration: none;
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
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .action-btn-edit:hover {
            background: var(--success-color);
            color: white;
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
