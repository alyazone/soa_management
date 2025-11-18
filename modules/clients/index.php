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
        // Check if client has associated SOAs
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM soa WHERE client_id = :id");
        $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        
        if($result['count'] > 0) {
            $delete_err = "Cannot delete client because they have associated SOA records.";
        } else {
            // Prepare a delete statement
            $sql = "DELETE FROM clients WHERE client_id = :id";
            
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

// Fetch all clients with SOA statistics
if(!$access_denied) {
    try {
        $stmt = $pdo->query("
            SELECT c.*, 
                   COUNT(s.soa_id) as total_soas,
                   COALESCE(SUM(CASE WHEN s.status = 'Pending' THEN s.balance_amount ELSE 0 END), 0) as pending_amount,
                   COALESCE(SUM(s.balance_amount), 0) as total_amount
            FROM clients c 
            LEFT JOIN soa s ON c.client_id = s.client_id 
            GROUP BY c.client_id 
            ORDER BY c.client_name
        ");
        $clients = $stmt->fetchAll();
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
    <title>Client Management - SOA Management System</title>
    
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
                        <h1>Client Management</h1>
                        <p>Manage client information and relationships</p>
                    </div>
                </div>
                <div class="header-right">
                    <?php if(!$access_denied): ?>
                    <a href="add.php" class="export-btn">
                        <i class="fas fa-user-plus"></i>
                        Add New Client
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
                    <p>You do not have permission to access this page. Only administrators can manage client records.</p>
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
                                echo "Client has been deleted successfully.";
                            } else {
                                echo "Client record has been updated successfully.";
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

            <!-- Clients Table -->
            <div class="table-card" data-aos="fade-up">
                <div class="table-header">
                    <div class="table-title">
                        <h3>Client Directory</h3>
                        <p>All registered clients in the system</p>
                    </div>
                    <div class="table-actions">
                        <button class="table-action-btn" onclick="refreshTable('clientsTable')">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button class="table-action-btn" onclick="exportTable()">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                </div>
                <div class="table-container">
                    <table class="modern-table" id="clientsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client Info</th>
                                <th>Contact Person</th>
                                <th>Contact Details</th>
                                <th>SOA Statistics</th>
                                <th>Total Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($clients)): ?>
                                <?php foreach($clients as $client): ?>
                                <tr class="table-row-clickable" data-href="view.php?id=<?php echo $client['client_id']; ?>">
                                    <td class="font-medium">#<?php echo str_pad($client['client_id'], 3, '0', STR_PAD_LEFT); ?></td>
                                    <td>
                                        <div class="client-info">
                                            <div class="client-avatar">
                                                <i class="fas fa-building"></i>
                                            </div>
                                            <div class="client-details">
                                                <div class="client-name"><?php echo htmlspecialchars($client['client_name']); ?></div>
                                                <div class="client-address"><?php echo htmlspecialchars(substr($client['address'], 0, 50)) . (strlen($client['address']) > 50 ? '...' : ''); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="contact-person">
                                            <div class="person-name">
                                                <i class="fas fa-user"></i>
                                                <?php echo htmlspecialchars($client['pic_name']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="contact-info">
                                            <div class="contact-item">
                                                <i class="fas fa-phone"></i>
                                                <?php echo htmlspecialchars($client['pic_contact']); ?>
                                            </div>
                                            <div class="contact-item">
                                                <i class="fas fa-envelope"></i>
                                                <?php echo htmlspecialchars($client['pic_email']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="soa-stats">
                                            <span class="soa-count-badge">
                                                <?php echo $client['total_soas']; ?> SOAs
                                            </span>
                                            <?php if($client['pending_amount'] > 0): ?>
                                            <span class="pending-badge">
                                                RM <?php echo number_format($client['pending_amount'], 2); ?> Pending
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="amount-display <?php echo $client['total_amount'] > 0 ? 'has-amount' : 'no-amount'; ?>">
                                            RM <?php echo number_format($client['total_amount'], 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn action-btn-view" onclick="viewClient(<?php echo $client['client_id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn action-btn-edit" onclick="editClient(<?php echo $client['client_id']; ?>)" title="Edit Client">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn action-btn-delete" onclick="deleteClient(<?php echo $client['client_id']; ?>, '<?php echo htmlspecialchars($client['client_name']); ?>')" title="Delete Client">
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
                                        <i class="fas fa-building"></i>
                                        <h3>No Clients Found</h3>
                                        <p>There are no clients registered in the system yet.</p>
                                        <a href="add.php" class="btn-primary">
                                            <i class="fas fa-user-plus"></i>
                                            Add First Client
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

        // Client management functions
        function viewClient(id) {
            window.location.href = `view.php?id=${id}`;
        }

        function editClient(id) {
            window.location.href = `edit.php?id=${id}`;
        }

        function deleteClient(id, name) {
            if (confirm(`Are you sure you want to delete ${name}? This action cannot be undone.`)) {
                window.location.href = `index.php?action=delete&id=${id}`;
            }
        }

        function refreshTable(tableId) {
            location.reload();
        }

        function exportTable() {
            // Implement export functionality
            console.log('Exporting client data...');
        }
    </script>

    <style>
        /* Client Management Specific Styles */
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

        /* Client Info Styles */
        .client-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .client-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .client-name {
            font-weight: 600;
            color: var(--gray-900);
            font-size: 0.875rem;
        }

        .client-address {
            font-size: 0.75rem;
            color: var(--gray-500);
        }

        .contact-person .person-name {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--gray-700);
        }

        .contact-person .person-name i {
            color: var(--gray-400);
            font-size: 0.75rem;
        }

        .contact-info .contact-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: var(--gray-600);
            margin-bottom: 0.25rem;
        }

        .contact-info .contact-item i {
            color: var(--gray-400);
            width: 12px;
        }

        /* SOA Statistics Styles */
        .soa-stats {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .soa-count-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.5rem;
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary-color);
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .pending-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.5rem;
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        /* Amount Display */
        .amount-display {
            font-weight: 600;
            font-size: 0.875rem;
        }

        .amount-display.has-amount {
            color: var(--success-color);
        }

        .amount-display.no-amount {
            color: var(--gray-400);
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
            .client-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .contact-info .contact-item {
                font-size: 0.75rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .soa-stats {
                align-items: flex-start;
            }
        }
    </style>
</body>
</html>
