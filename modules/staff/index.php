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
        // Check if trying to delete own account
        if($_GET["id"] == $_SESSION["staff_id"]){
            $delete_err = "You cannot delete your own account.";
        } else {
            // Check if staff has associated claims or documents
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM claims WHERE staff_id = :id");
            $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch();
            
            if($result['count'] > 0) {
                $delete_err = "Cannot delete staff because they have associated claims.";
            } else {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM documents WHERE uploaded_by = :id");
                $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
                $stmt->execute();
                $result = $stmt->fetch();
                
                if($result['count'] > 0) {
                    $delete_err = "Cannot delete staff because they have associated documents.";
                } else {
                    // Prepare a delete statement
                    $sql = "DELETE FROM staff WHERE staff_id = :id";
                    
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
            }
        }
    } catch(PDOException $e) {
        $delete_err = "Error: " . $e->getMessage();
    }
}

// Fetch all staff members
if(!$access_denied) {
    try {
        $stmt = $pdo->query("SELECT * FROM staff ORDER BY full_name");
        $staff_members = $stmt->fetchAll();
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
    <title>Staff Management - SOA Management System</title>
    
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
                        <h1>Staff Management</h1>
                        <p>Manage system users and their permissions</p>
                    </div>
                </div>
                <div class="header-right">
                    <?php if(!$access_denied): ?>
                    <a href="<?php echo $basePath; ?>modules/auth/register.php" class="export-btn">
                        <i class="fas fa-user-plus"></i>
                        Add New Staff
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
                    <p>You do not have permission to access this page. Only administrators can manage staff members.</p>
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
                                echo "Staff member has been deleted successfully.";
                            } else {
                                echo "Staff record has been updated successfully.";
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

            <!-- Staff Table -->
            <div class="table-card" data-aos="fade-up">
                <div class="table-header">
                    <div class="table-title">
                        <h3>Staff Members</h3>
                        <p>All registered staff members in the system</p>
                    </div>
                    <div class="table-actions">
                        <button class="table-action-btn" onclick="refreshTable('staffTable')">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button class="table-action-btn" onclick="exportTable()">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                </div>
                <div class="table-container">
                    <table class="modern-table" id="staffTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Staff Info</th>
                                <th>Contact</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($staff_members)): ?>
                                <?php foreach($staff_members as $staff): ?>
                                <tr class="table-row-clickable" data-href="view.php?id=<?php echo $staff['staff_id']; ?>">
                                    <td class="font-medium">#<?php echo str_pad($staff['staff_id'], 3, '0', STR_PAD_LEFT); ?></td>
                                    <td>
                                        <div class="staff-info">
                                            <div class="staff-avatar">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div class="staff-details">
                                                <div class="staff-name"><?php echo htmlspecialchars($staff['full_name']); ?></div>
                                                <div class="staff-username">@<?php echo htmlspecialchars($staff['username']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="contact-info">
                                            <div class="email">
                                                <i class="fas fa-envelope"></i>
                                                <?php echo htmlspecialchars($staff['email']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="department-badge">
                                            <?php echo htmlspecialchars($staff['department']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="position-badge position-<?php echo strtolower($staff['position']); ?>">
                                            <?php echo htmlspecialchars($staff['position']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-active">
                                            Active
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn action-btn-view" onclick="viewStaff(<?php echo $staff['staff_id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn action-btn-edit" onclick="editStaff(<?php echo $staff['staff_id']; ?>)" title="Edit Staff">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if($staff['staff_id'] != $_SESSION['staff_id']): ?>
                                            <button class="action-btn action-btn-delete" onclick="deleteStaff(<?php echo $staff['staff_id']; ?>, '<?php echo htmlspecialchars($staff['full_name']); ?>')" title="Delete Staff">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center no-data">
                                    <div class="no-data-content">
                                        <i class="fas fa-users"></i>
                                        <h3>No Staff Members Found</h3>
                                        <p>There are no staff members registered in the system yet.</p>
                                        <a href="<?php echo $basePath; ?>modules/auth/register.php" class="btn-primary">
                                            <i class="fas fa-user-plus"></i>
                                            Add First Staff Member
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

        // Staff management functions
        function viewStaff(id) {
            window.location.href = `view.php?id=${id}`;
        }

        function editStaff(id) {
            window.location.href = `edit.php?id=${id}`;
        }

        function deleteStaff(id, name) {
            if (confirm(`Are you sure you want to delete ${name}? This action cannot be undone.`)) {
                window.location.href = `index.php?action=delete&id=${id}`;
            }
        }

        function refreshTable(tableId) {
            location.reload();
        }

        function exportTable() {
            // Implement export functionality
            console.log('Exporting staff data...');
        }
    </script>

    <style>
        /* Staff Management Specific Styles */
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

        /* Staff Info Styles */
        .staff-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .staff-avatar {
            width: 40px;
            height: 40px;
            background: var(--gray-100);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-600);
        }

        .staff-name {
            font-weight: 600;
            color: var(--gray-900);
            font-size: 0.875rem;
        }

        .staff-username {
            font-size: 0.75rem;
            color: var(--gray-500);
        }

        .contact-info .email {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--gray-700);
        }

        .contact-info .email i {
            color: var(--gray-400);
            font-size: 0.75rem;
        }

        /* Badge Styles */
        .department-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.375rem 0.75rem;
            background: var(--gray-100);
            color: var(--gray-700);
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .position-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .position-admin {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .position-manager {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .position-staff {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary-color);
        }

        .status-active {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
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
            .staff-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .contact-info .email {
                font-size: 0.75rem;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</body>
</html>
