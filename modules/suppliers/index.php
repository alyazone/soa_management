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
        // Check if supplier has associated SOAs
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM soa WHERE supplier_id = :id");
        $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        
        if($result['count'] > 0) {
            $delete_err = "Cannot delete supplier because they have associated SOA records.";
        } else {
            // Prepare a delete statement
            $sql = "DELETE FROM suppliers WHERE supplier_id = :id";
            
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

// Fetch all suppliers with SOA statistics
if(!$access_denied) {
    try {
        $stmt = $pdo->query("
            SELECT sup.*, 
                   COUNT(s.soa_id) as total_soas,
                   COALESCE(SUM(s.balance_amount), 0) as total_amount
            FROM suppliers sup 
            LEFT JOIN soa s ON sup.supplier_id = s.supplier_id 
            GROUP BY sup.supplier_id 
            ORDER BY sup.supplier_name
        ");
        $suppliers = $stmt->fetchAll();
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
    <title>Supplier Management - SOA Management System</title>
    
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/modern-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include_once $basePath . "includes/modern-sidebar.php"; ?>
    
    <div class="main-content">
        <header class="dashboard-header">
            <div class="header-content">
                <div class="header-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="header-title">
                        <h1>Supplier Management</h1>
                        <p>Manage supplier information and relationships</p>
                    </div>
                </div>
                <div class="header-right">
                    <?php if(!$access_denied): ?>
                    <a href="add.php" class="export-btn">
                        <i class="fas fa-plus"></i>
                        Add New Supplier
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <?php if($access_denied): ?>
            <div class="access-denied-card">
                <div class="access-denied-content">
                    <div class="access-denied-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h2>Access Denied</h2>
                    <p>You do not have permission to access this page. Only administrators can manage supplier records.</p>
                    <a href="<?php echo $basePath; ?>dashboard.php" class="btn-primary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
            <?php else: ?>
            
            <?php if(isset($_GET["success"])): ?>
                <div class="alert alert-success" data-aos="fade-down">
                    <div class="alert-content">
                        <i class="fas fa-check-circle"></i>
                        <span>
                            <?php 
                            if($_GET["success"] == "deleted") {
                                echo "Supplier has been deleted successfully.";
                            } elseif($_GET["success"] == "updated") {
                                echo "Supplier record has been updated successfully.";
                            } elseif($_GET["success"] == "added") {
                                echo "Supplier record has been added successfully.";
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

            <div class="table-card" data-aos="fade-up">
                <div class="table-header">
                    <div class="table-title">
                        <h3>Supplier Directory</h3>
                        <p>All registered suppliers in the system</p>
                    </div>
                    <div class="table-actions">
                        <button class="table-action-btn" onclick="refreshTable('suppliersTable')">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="table-container">
                    <table class="modern-table" id="suppliersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Supplier Info</th>
                                <th>Contact Person</th>
                                <th>Contact Details</th>
                                <th>SOA Count</th>
                                <th>Total Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($suppliers)): ?>
                                <?php foreach($suppliers as $supplier): ?>
                                <tr class="table-row-clickable" data-href="view.php?id=<?php echo $supplier['supplier_id']; ?>">
                                    <td class="font-medium">#<?php echo str_pad($supplier['supplier_id'], 3, '0', STR_PAD_LEFT); ?></td>
                                    <td>
                                        <div class="client-info">
                                            <div class="client-avatar" style="background-color: #6366f1;">
                                                <i class="fas fa-truck-fast"></i>
                                            </div>
                                            <div class="client-details">
                                                <div class="client-name"><?php echo htmlspecialchars($supplier['supplier_name']); ?></div>
                                                <div class="client-address"><?php echo htmlspecialchars(substr($supplier['address'], 0, 50)) . (strlen($supplier['address']) > 50 ? '...' : ''); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="contact-person">
                                            <div class="person-name">
                                                <i class="fas fa-user"></i>
                                                <?php echo htmlspecialchars($supplier['pic_name']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="contact-info">
                                            <div class="contact-item">
                                                <i class="fas fa-phone"></i>
                                                <?php echo htmlspecialchars($supplier['pic_contact']); ?>
                                            </div>
                                            <div class="contact-item">
                                                <i class="fas fa-envelope"></i>
                                                <?php echo htmlspecialchars($supplier['pic_email']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="soa-count-badge">
                                            <?php echo $supplier['total_soas']; ?> SOAs
                                        </span>
                                    </td>
                                    <td>
                                        <span class="amount-display <?php echo $supplier['total_amount'] > 0 ? 'has-amount' : 'no-amount'; ?>">
                                            RM <?php echo number_format($supplier['total_amount'], 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn action-btn-view" onclick="viewSupplier(event, <?php echo $supplier['supplier_id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn action-btn-edit" onclick="editSupplier(event, <?php echo $supplier['supplier_id']; ?>)" title="Edit Supplier">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn action-btn-delete" onclick="deleteSupplier(event, <?php echo $supplier['supplier_id']; ?>, '<?php echo htmlspecialchars(addslashes($supplier['supplier_name'])); ?>')" title="Delete Supplier">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center no-data">
                                    <div class="no-data-content">
                                        <i class="fas fa-truck-fast"></i>
                                        <h3>No Suppliers Found</h3>
                                        <p>There are no suppliers registered in the system yet.</p>
                                        <a href="add.php" class="btn-primary">
                                            <i class="fas fa-plus"></i>
                                            Add First Supplier
                                        </a>
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

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="../../assets/js/modern-dashboard.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({ duration: 800, easing: 'ease-in-out', once: true });
            initializeDashboard();
        });

        function viewSupplier(event, id) { event.stopPropagation(); window.location.href = `view.php?id=${id}`; }
        function editSupplier(event, id) { event.stopPropagation(); window.location.href = `edit.php?id=${id}`; }
        function deleteSupplier(event, id, name) {
            event.stopPropagation();
            if (confirm(`Are you sure you want to delete ${name}? This action cannot be undone.`)) {
                window.location.href = `index.php?action=delete&id=${id}`;
            }
        }
        function refreshTable(tableId) { location.reload(); }
    </script>
    <style>
        .access-denied-card{background:white;border-radius:var(--border-radius);box-shadow:var(--shadow);border:1px solid var(--gray-200);padding:3rem;text-align:center;max-width:500px;margin:2rem auto}.access-denied-icon{width:80px;height:80px;background:rgba(239,68,68,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;color:var(--danger-color);font-size:2rem}.access-denied-card h2{color:var(--gray-900);margin-bottom:.5rem}.access-denied-card p{color:var(--gray-600);margin-bottom:2rem}.btn-primary{display:inline-flex;align-items:center;gap:.5rem;padding:.75rem 1.5rem;background:var(--primary-color);color:white;text-decoration:none;border-radius:var(--border-radius-sm);font-weight:500;transition:var(--transition)}.btn-primary:hover{background:var(--primary-dark);color:white;text-decoration:none}.alert{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.5rem;border-radius:var(--border-radius);margin-bottom:1.5rem;border:1px solid}.alert-success{background:rgba(16,185,129,.1);border-color:var(--success-color);color:var(--success-color)}.alert-error{background:rgba(239,68,68,.1);border-color:var(--danger-color);color:var(--danger-color)}.alert-content{display:flex;align-items:center;gap:.75rem}.alert-close{background:0 0;border:none;color:inherit;cursor:pointer;padding:.25rem;border-radius:var(--border-radius-sm);transition:var(--transition)}.alert-close:hover{background:rgba(0,0,0,.1)}.client-info{display:flex;align-items:center;gap:.75rem}.client-avatar{width:40px;height:40px;background:var(--primary-color);border-radius:8px;display:flex;align-items:center;justify-content:center;color:white}.client-name{font-weight:600;color:var(--gray-900);font-size:.875rem}.client-address{font-size:.75rem;color:var(--gray-500)}.contact-person .person-name{display:flex;align-items:center;gap:.5rem;font-size:.875rem;color:var(--gray-700)}.contact-person .person-name i{color:var(--gray-400);font-size:.75rem}.contact-info .contact-item{display:flex;align-items:center;gap:.5rem;font-size:.75rem;color:var(--gray-600);margin-bottom:.25rem}.contact-info .contact-item i{color:var(--gray-400);width:12px}.soa-count-badge{display:inline-flex;align-items:center;padding:.25rem .5rem;background:rgba(59,130,246,.1);color:var(--primary-color);border-radius:9999px;font-size:.75rem;font-weight:500}.amount-display{font-weight:600;font-size:.875rem}.amount-display.has-amount{color:var(--success-color)}.amount-display.no-amount{color:var(--gray-400)}.action-buttons{display:flex;gap:.5rem}.action-btn{width:32px;height:32px;border:none;border-radius:var(--border-radius-sm);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:var(--transition);font-size:.875rem}.action-btn-view{background:rgba(59,130,246,.1);color:var(--primary-color)}.action-btn-view:hover{background:var(--primary-color);color:white}.action-btn-edit{background:rgba(245,158,11,.1);color:var(--warning-color)}.action-btn-edit:hover{background:var(--warning-color);color:white}.action-btn-delete{background:rgba(239,68,68,.1);color:var(--danger-color)}.action-btn-delete:hover{background:var(--danger-color);color:white}.no-data{padding:3rem!important}.no-data-content{text-align:center}.no-data-content i{font-size:3rem;color:var(--gray-300);margin-bottom:1rem}.no-data-content h3{color:var(--gray-700);margin-bottom:.5rem}.no-data-content p{color:var(--gray-500);margin-bottom:1.5rem}@media (max-width:768px){.client-info{flex-direction:column;align-items:flex-start;gap:.5rem}.action-buttons{flex-direction:column}}
    </style>
</body>
</html>
