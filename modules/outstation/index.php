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

// Check permissions - All staff can view their own applications
// Admin and Manager can view all applications
$is_admin_or_manager = ($_SESSION['position'] == 'Admin' || $_SESSION['position'] == 'Manager');

// Process delete operation (only for own applications or admin/manager)
if(isset($_GET["action"]) && $_GET["action"] == "delete" && isset($_GET["id"]) && !empty($_GET["id"])){
    try {
        $application_id = intval($_GET["id"]);

        // Check ownership or admin/manager
        $check_sql = "SELECT staff_id, status FROM outstation_applications WHERE application_id = :id";
        $stmt = $pdo->prepare($check_sql);
        $stmt->bindParam(":id", $application_id, PDO::PARAM_INT);
        $stmt->execute();
        $app = $stmt->fetch();

        if($app && ($app['staff_id'] == $_SESSION['staff_id'] || $is_admin_or_manager)){
            // Only allow deletion of pending applications
            if($app['status'] == 'Pending'){
                $delete_sql = "DELETE FROM outstation_applications WHERE application_id = :id";
                $stmt = $pdo->prepare($delete_sql);
                $stmt->bindParam(":id", $application_id, PDO::PARAM_INT);

                if($stmt->execute()){
                    header("location: index.php?success=deleted");
                    exit();
                } else {
                    $delete_err = "Failed to delete application.";
                }
            } else {
                $delete_err = "Cannot delete applications that are not pending.";
            }
        } else {
            $delete_err = "Unauthorized access.";
        }
    } catch(PDOException $e) {
        $delete_err = "Error: " . $e->getMessage();
    }
}

// Fetch applications based on user role
try {
    if($is_admin_or_manager){
        // Admin/Manager can see all applications
        $stmt = $pdo->query("
            SELECT oa.*,
                   s.full_name as staff_name,
                   s.department,
                   approver.full_name as approver_name
            FROM outstation_applications oa
            LEFT JOIN staff s ON oa.staff_id = s.staff_id
            LEFT JOIN staff approver ON oa.approved_by = approver.staff_id
            ORDER BY oa.created_at DESC
        ");
    } else {
        // Regular staff can only see their own applications
        $stmt = $pdo->prepare("
            SELECT oa.*,
                   s.full_name as staff_name,
                   s.department,
                   approver.full_name as approver_name
            FROM outstation_applications oa
            LEFT JOIN staff s ON oa.staff_id = s.staff_id
            LEFT JOIN staff approver ON oa.approved_by = approver.staff_id
            WHERE oa.staff_id = :staff_id
            ORDER BY oa.created_at DESC
        ");
        $stmt->bindParam(':staff_id', $_SESSION['staff_id'], PDO::PARAM_INT);
        $stmt->execute();
    }
    $applications = $stmt->fetchAll();

    // Calculate statistics
    $total = count($applications);
    $pending = count(array_filter($applications, fn($app) => $app['status'] === 'Pending'));
    $approved = count(array_filter($applications, fn($app) => $app['status'] === 'Approved'));
    $rejected = count(array_filter($applications, fn($app) => $app['status'] === 'Rejected'));
    $claimable = count(array_filter($applications, fn($app) => $app['is_claimable'] == 1 && $app['status'] === 'Approved'));

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outstation Leave Management - SOA Management System</title>

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
                        <h1>Outstation Leave Management</h1>
                        <p>Track and manage outstation leave applications</p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="application_form.php" class="export-btn">
                        <i class="fas fa-plus"></i>
                        New Application
                    </a>
                    <?php if($is_admin_or_manager): ?>
                    <a href="dashboard.php" class="export-btn" style="margin-left: 10px;">
                        <i class="fas fa-chart-bar"></i>
                        Dashboard
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Success/Error Messages -->
            <?php if(isset($_GET["success"])): ?>
                <div class="alert alert-success" data-aos="fade-down">
                    <div class="alert-content">
                        <i class="fas fa-check-circle"></i>
                        <span>
                            <?php
                            if($_GET["success"] == "deleted") {
                                echo "Application has been deleted successfully.";
                            } elseif($_GET["success"] == "updated") {
                                echo "Application has been updated successfully.";
                            } elseif($_GET["success"] == "created") {
                                echo "Application has been submitted successfully.";
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
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-label">Total Applications</div>
                        <div class="stat-value"><?php echo $total; ?></div>
                        <div class="stat-change positive">All time</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--warning-color);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-label">Pending Approval</div>
                        <div class="stat-value"><?php echo $pending; ?></div>
                        <div class="stat-change"><?php echo $total > 0 ? round(($pending/$total)*100, 1) : 0; ?>% of total</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success-color);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-label">Approved</div>
                        <div class="stat-value"><?php echo $approved; ?></div>
                        <div class="stat-change positive"><?php echo $total > 0 ? round(($approved/$total)*100, 1) : 0; ?>% approved</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(6, 182, 212, 0.1); color: var(--info-color);">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-label">Claimable</div>
                        <div class="stat-value"><?php echo $claimable; ?></div>
                        <div class="stat-change">Eligible for claims</div>
                    </div>
                </div>
            </div>

            <!-- Applications Table -->
            <div class="table-card" data-aos="fade-up">
                <div class="table-header">
                    <div class="table-title">
                        <h3><?php echo $is_admin_or_manager ? 'All Applications' : 'My Applications'; ?></h3>
                        <p>Complete list of outstation leave applications</p>
                    </div>
                    <div class="table-actions">
                        <button class="table-action-btn" onclick="refreshTable()">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button class="table-action-btn" onclick="exportTable()">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                </div>
                <div class="table-container">
                    <table class="modern-table" id="applicationsTable">
                        <thead>
                            <tr>
                                <th>Application No.</th>
                                <?php if($is_admin_or_manager): ?>
                                <th>Staff Info</th>
                                <?php endif; ?>
                                <th>Destination</th>
                                <th>Travel Dates</th>
                                <th>Nights</th>
                                <th>Purpose</th>
                                <th>Claimable</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($applications)): ?>
                                <?php foreach($applications as $app): ?>
                                <tr class="table-row-clickable" data-href="view.php?id=<?php echo $app['application_id']; ?>">
                                    <td class="font-medium">
                                        <div class="app-number-display">
                                            <?php echo htmlspecialchars($app['application_number']); ?>
                                            <div class="app-date-small"><?php echo date('d M Y', strtotime($app['created_at'])); ?></div>
                                        </div>
                                    </td>
                                    <?php if($is_admin_or_manager): ?>
                                    <td>
                                        <div class="staff-info-display">
                                            <div class="staff-avatar-small">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <div class="staff-name-small"><?php echo htmlspecialchars($app['staff_name']); ?></div>
                                                <div class="staff-dept-small"><?php echo htmlspecialchars($app['department']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                    <td>
                                        <div class="destination-display">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo htmlspecialchars($app['destination']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="date-range-display">
                                            <div class="date-item">
                                                <i class="fas fa-plane-departure"></i>
                                                <?php echo date('d M Y', strtotime($app['departure_date'])); ?>
                                            </div>
                                            <div class="date-item">
                                                <i class="fas fa-plane-arrival"></i>
                                                <?php echo date('d M Y', strtotime($app['return_date'])); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="nights-badge"><?php echo $app['total_nights']; ?> night<?php echo $app['total_nights'] != 1 ? 's' : ''; ?></span>
                                    </td>
                                    <td>
                                        <span class="purpose-tag"><?php echo htmlspecialchars($app['purpose']); ?></span>
                                    </td>
                                    <td>
                                        <?php if($app['is_claimable']): ?>
                                            <span class="claimable-badge yes">
                                                <i class="fas fa-check"></i> Yes
                                            </span>
                                        <?php else: ?>
                                            <span class="claimable-badge no">
                                                <i class="fas fa-times"></i> No
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($app['status']); ?>">
                                            <?php echo $app['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn action-btn-view" onclick="event.stopPropagation(); viewApplication(<?php echo $app['application_id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if($app['status'] == 'Pending' && ($app['staff_id'] == $_SESSION['staff_id'] || $is_admin_or_manager)): ?>
                                            <button class="action-btn action-btn-edit" onclick="event.stopPropagation(); editApplication(<?php echo $app['application_id']; ?>)" title="Edit Application">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if($is_admin_or_manager && $app['status'] == 'Pending'): ?>
                                            <button class="action-btn action-btn-success" onclick="event.stopPropagation(); approveApplication(<?php echo $app['application_id']; ?>)" title="Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if($app['status'] == 'Pending' && ($app['staff_id'] == $_SESSION['staff_id'] || $is_admin_or_manager)): ?>
                                            <button class="action-btn action-btn-delete" onclick="event.stopPropagation(); deleteApplication(<?php echo $app['application_id']; ?>, '<?php echo htmlspecialchars($app['application_number']); ?>')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="<?php echo $is_admin_or_manager ? '9' : '8'; ?>" class="text-center no-data">
                                    <div class="no-data-content">
                                        <i class="fas fa-plane"></i>
                                        <h3>No Applications Found</h3>
                                        <p>There are no outstation leave applications yet.</p>
                                        <a href="application_form.php" class="btn-primary">
                                            <i class="fas fa-plus"></i>
                                            Create First Application
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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

        // Application management functions
        function viewApplication(id) {
            window.location.href = `view.php?id=${id}`;
        }

        function editApplication(id) {
            window.location.href = `edit.php?id=${id}`;
        }

        function approveApplication(id) {
            if (confirm('Are you sure you want to approve this application?')) {
                window.location.href = `api/approve_application.php?id=${id}&action=approve`;
            }
        }

        function deleteApplication(id, appNumber) {
            if (confirm(`Are you sure you want to delete application ${appNumber}? This action cannot be undone.`)) {
                window.location.href = `index.php?action=delete&id=${id}`;
            }
        }

        function refreshTable() {
            location.reload();
        }

        function exportTable() {
            // Implement export functionality
            console.log('Exporting outstation data...');
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
        /* Outstation Management Specific Styles */
        .app-number-display {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: var(--gray-900);
            font-size: 0.875rem;
        }

        .app-date-small {
            font-size: 0.75rem;
            color: var(--gray-500);
            margin-top: 0.125rem;
        }

        .staff-info-display {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .staff-avatar-small {
            width: 32px;
            height: 32px;
            background: var(--gray-200);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-600);
            font-size: 0.75rem;
        }

        .staff-name-small {
            font-weight: 600;
            color: var(--gray-900);
            font-size: 0.875rem;
        }

        .staff-dept-small {
            font-size: 0.75rem;
            color: var(--gray-500);
        }

        .destination-display {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-700);
        }

        .destination-display i {
            color: var(--primary-color);
        }

        .date-range-display {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .date-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: var(--gray-600);
        }

        .date-item i {
            width: 14px;
            color: var(--gray-400);
        }

        .nights-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .purpose-tag {
            display: inline-block;
            font-size: 0.75rem;
            color: var(--gray-700);
            padding: 0.25rem 0.5rem;
            background: var(--gray-100);
            border-radius: 4px;
        }

        .claimable-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .claimable-badge.yes {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .claimable-badge.no {
            background: rgba(107, 114, 128, 0.1);
            color: var(--gray-500);
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .status-approved {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .status-rejected {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .status-cancelled {
            background: rgba(107, 114, 128, 0.1);
            color: var(--gray-500);
        }

        .status-completed {
            background: rgba(6, 182, 212, 0.1);
            color: var(--info-color);
        }

        .action-btn-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .action-btn-success:hover {
            background: var(--success-color);
            color: white;
        }

        @media (max-width: 768px) {
            .staff-info-display {
                flex-direction: column;
                align-items: flex-start;
            }

            .date-range-display {
                font-size: 0.7rem;
            }
        }
    </style>
</body>
</html>
