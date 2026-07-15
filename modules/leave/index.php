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

// Check permissions - strictly Admin and Manager only
if (!isset($_SESSION['position']) || !in_array($_SESSION['position'], ['Admin', 'Manager'])) {
    header("location: " . $basePath . "modules/leave/view_me.php?id=" . $_SESSION['staff_id']);
    exit;
}

try {
    // 1. Fetch Leave Availability (for the table display)
    $stmt = $pdo->query("
        SELECT la.*, s.full_name as staff_name, s.department
        FROM leave_availability la
        LEFT JOIN staff s ON la.staff_id = s.staff_id
        ORDER BY la.updated_at DESC
    ");
    $availabilities = $stmt->fetchAll();

    try {
    // 1. Initialize stats for total days
    $stats = [
        'AL' => 0, // Annual Leave
        'EL' => 0, // Emergency Leave
        'ML' => 0, // Medical Leave
        'OL' => 0, // Outstation Leave
        'BL' => 0, // Birthday Leave
        'CL' => 0  // Carryforward Leave
    ];

    // 2. Fetch SUM of total_day
    $sum_sql = "SELECT leave_reason, SUM(total_day) as total_sum 
                FROM leave_application 
                GROUP BY leave_reason";
    $sum_stmt = $pdo->query($sum_sql);

    // 3. Map the SUM results into our stats array
    while ($row = $sum_stmt->fetch(PDO::FETCH_ASSOC)) {
        $reason = $row['leave_reason'];
        if (array_key_exists($reason, $stats)) {
            // Ensure we handle potential nulls from SUM()
            $stats[$reason] = $row['total_sum'] ?? 0;
        }
    }

    // 4. Assign to variables for the UI
    $days_annual       = $stats["AL"];
    $days_emergency    = $stats["EL"];
    $days_medical      = $stats["ML"];
    $days_outstation   = $stats["OL"];
    $days_birthday     = $stats["BL"];
    $days_carryforward = $stats["CL"];

    } catch(PDOException $e) {
        echo "Error fetching leave statistics: " . $e->getMessage();
        error_log("Error fetching leave statistics in index.php: " . $e->getMessage());
    }

} catch(PDOException $e) {
    echo "Error fetching leave availabilities: " . $e->getMessage();
    error_log("Error fetching leave availabilities in index.php: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management - SOA Management System</title>

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
                        <h1>Leave Management</h1>
                        <p>Track and manage leave applications</p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="application_form.php" class="export-btn">
                        <i class="fas fa-plus"></i>
                        Record New Leave
                    </a>
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
                    <div class="stat-icon" style="background: rgba(59, 130, 246, 0.2); color: var(--primary-color);">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-label">Annual Leaves Taken</div>
                        <div class="stat-value"><?php echo $days_annual ?></div>
                        <div class="stat-change positive">Total days used</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(245, 158, 11, 0.2); color: var(--warning-color);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-label">Emergency Leaves Taken</div>
                        <div class="stat-value"><?php echo $days_emergency ?></div>
                        <div class="stat-change">Total days used</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.2); color: var(--success-color);">
                        <i class="fas fa-notes-medical"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-label">Medical Leaves Taken</div>
                        <div class="stat-value"><?php echo $days_medical ?></div>
                        <div class="stat-change positive">Total days used</div>
                    </div>
                </div>


                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(6, 182, 212, 0.2); color: var(--info-color);">
                        <i class="fas fa-suitcase"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-label">Outstation Leaves Taken</div>
                        <div class="stat-value"><?php echo $days_outstation ?></div>
                        <div class="stat-change">Total days used</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6;">
                        <i class="fas fa-cake-candles"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-label">Birthday Leaves Taken</div>
                        <div class="stat-value"><?php echo $days_birthday ?></div>
                        <div class="stat-change positive">Total days used</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(59, 130, 246, 0.2); color: var(--primary-color);">
                        <i class="fa-solid fa-calendar-week"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-label">Carryforward Leaves Taken</div>
                        <div class="stat-value"><?php echo $days_carryforward ?></div>
                        <div class="stat-change">Total days used</div>
                    </div>
                </div>
            </div>
            <!-- Staff's Leave_Availability -->
        <div class="table-card" data-aos="fade-up">
            <div class="table-header">
                <div class="table-title">
                    <h3>All Staff Leave Balances</h3>
                    <p>Current remaining leave balances for the calendar year</p>
                </div>
                <div class="table-actions">
                    <button class="table-action-btn" onclick="refreshTable()" title="Refresh">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button class="table-action-btn" onclick="exportTable()" title="Export to Excel">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>
            <div class="table-container">
                <table class="modern-table" id="leaveTable">
                    <thead>
                        <tr>
                            <th>Staff Info</th>
                            <th class="text-center">Annual</th>
                            <th class="text-center">Outstation</th>
                            <th class="text-center" style="font-weight:bold">Total</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($availabilities)): ?>
                            <?php foreach($availabilities as $app): ?>
                            <?php 
                            $total_leave = ($app['annual_leave'] + $app['carryforward_leave'] + $app['outstation_leave']);
                            $total_annual = ($app['annual_leave'] + $app['carryforward_leave']);
                            ?>
                            
                            <tr>
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
                                <td class="text-center">
                                    <span class="purpose-tag" style="background: rgba(59, 130, 246, 0.2); color: var(--primary-color);"><?php echo $total_annual . ($total_annual > 1 ? ' days' : ' day');?></span>
                                </td>

                                <td class="text-center">
                                    <span class="purpose-tag" style="background: rgba(6, 182, 212, 0.2); color: var(--info-color);"><?php echo $app['outstation_leave'] . ($app['outstation_leave'] > 1 ? ' days' : ' day'); ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="purpose-tag" style="background: rgba(79, 79, 79, 0.2); color:var(--gray-900); font-weight: bold;"> <?php echo $total_leave . ($total_leave > 1 ? ' days' : ' day'); ?> </span>
                                </td>
                                <td>
                                    <div class="staff-dept-small">
                                        <i class="far fa-clock"></i> 
                                        <?php echo date('d M Y', strtotime($app['updated_at'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn action-btn-view" onclick="viewRecord(<?php echo $app['staff_id']; ?>)" title="View History">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center no-data">
                                <div class="no-data-content">
                                    <i class="fas fa-calendar-times"></i>
                                    <h3>No Records Found</h3>
                                    <p>Leave availability data has not been initialized for this user.</p>
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
            if (typeof initializeDashboard === "function") { 
                initializeDashboard(); 
            }
        });

        // Leave Availability management functions
        function viewRecord(id) {
            window.location.href = `view.php?id=${id}`;
        }


        function refreshTable() {
            location.reload();
        }

        function exportTable() {
            // Placeholder for CSV/Excel export logic
            console.log('Exporting leave availability data...');
            alert('Exporting leave balance report...');
        }
    </script>

    <style>
        /* Outstation Management Specific Styles */

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

        .purpose-tag {
            display: inline-block;
            font-size: 0.75rem;
            color: var(--gray-700);
            padding: 0.25rem 0.5rem;
            background: var(--gray-100);
            border-radius: 4px;
        }

    </style>
</body>
</html>
