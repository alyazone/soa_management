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

// Determine if user is admin/manager
$isAdmin = ($_SESSION['position'] == 'Admin' || $_SESSION['position'] == 'Manager');

// Fetch claims data
try {
    if($isAdmin) {
        $stmt = $pdo->prepare("SELECT c.*, s.full_name
                              FROM claims c
                              JOIN staff s ON c.staff_id = s.staff_id
                              ORDER BY c.submitted_date DESC");
    } else {
        $stmt = $pdo->prepare("SELECT c.*, s.full_name
                              FROM claims c
                              JOIN staff s ON c.staff_id = s.staff_id
                              WHERE c.staff_id = :staff_id
                              ORDER BY c.submitted_date DESC");
        $stmt->bindParam(":staff_id", $_SESSION["staff_id"], PDO::PARAM_INT);
    }
    $stmt->execute();
    $claims = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("ERROR: Could not fetch claims. " . $e->getMessage());
}

// Get status badge class for modern design
function getStatusBadgeClass($status) {
    switch($status) {
        case 'Approved': return 'status-approved';
        case 'Rejected': return 'status-rejected';
        case 'Pending':  return 'status-pending';
        case 'Paid':     return 'status-paid';
        default:         return 'status-pending';
    }
}

function formatDate($date) {
    if(empty($date)) return 'N/A';
    return date("d M Y", strtotime($date));
}

// Summary counts for stat cards
$total     = count($claims);
$pending   = count(array_filter($claims, fn($c) => $c['status'] == 'Pending'));
$approved  = count(array_filter($claims, fn($c) => $c['status'] == 'Approved'));
$paid      = count(array_filter($claims, fn($c) => $c['status'] == 'Paid'));
$totalAmt  = array_sum(array_column($claims, 'amount'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claims Management - SOA Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/modern-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include_once $basePath . "includes/modern-sidebar.php"; ?>

    <div class="main-content">
        <!-- Header -->
        <header class="dashboard-header">
            <div class="header-content">
                <div class="header-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="header-title">
                        <h1>Mileage Reimbursement Claims</h1>
                        <p>Manage and track staff reimbursement claims</p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="add.php" class="export-btn">
                        <i class="fas fa-plus"></i>
                        New Claim
                    </a>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="dashboard-content">

            <!-- Success / Error messages -->
            <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success" data-aos="fade-down">
                <div class="alert-content">
                    <i class="fas fa-check-circle"></i>
                    <span>Claim status has been updated successfully.</span>
                </div>
                <button class="alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            </div>
            <?php endif; ?>

            <!-- Stat Cards -->
            <div class="stats-grid" data-aos="fade-up">
                <div class="stat-card stat-card-primary">
                    <div class="stat-card-content">
                        <div>
                            <div class="stat-label">Total Claims</div>
                            <div class="stat-value"><?php echo $total; ?></div>
                        </div>
                        <div class="stat-icon"><i class="fas fa-file-invoice"></i></div>
                    </div>
                </div>
                <div class="stat-card stat-card-warning">
                    <div class="stat-card-content">
                        <div>
                            <div class="stat-label">Pending</div>
                            <div class="stat-value"><?php echo $pending; ?></div>
                        </div>
                        <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                    </div>
                </div>
                <div class="stat-card stat-card-success">
                    <div class="stat-card-content">
                        <div>
                            <div class="stat-label">Approved</div>
                            <div class="stat-value"><?php echo $approved; ?></div>
                        </div>
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
                <div class="stat-card stat-card-info">
                    <div class="stat-card-content">
                        <div>
                            <div class="stat-label">Total Amount (RM)</div>
                            <div class="stat-value"><?php echo number_format($totalAmt, 0); ?></div>
                        </div>
                        <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                    </div>
                </div>
            </div>

            <!-- Claims Table -->
            <div class="table-card" data-aos="fade-up">
                <div class="table-header">
                    <div class="table-title">
                        <h3>Claims List</h3>
                        <p><?php echo $isAdmin ? 'All staff claims' : 'Your submitted claims'; ?></p>
                    </div>
                    <div class="table-actions">
                        <button class="table-action-btn" onclick="location.reload()" title="Refresh">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="table-container">
                    <?php if(empty($claims)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-file-invoice"></i></div>
                        <h3>No Claims Found</h3>
                        <p>No claims have been submitted yet.</p>
                        <a href="add.php" class="export-btn" style="display:inline-flex;margin-top:1rem;">
                            <i class="fas fa-plus"></i> New Claim
                        </a>
                    </div>
                    <?php else: ?>
                    <table class="modern-table" id="claimsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <?php if($isAdmin): ?><th>Employee</th><?php endif; ?>
                                <th>Month</th>
                                <th>Vehicle</th>
                                <th>Amount (RM)</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($claims as $claim): ?>
                            <tr class="table-row-clickable" data-href="view.php?id=<?php echo $claim['claim_id']; ?>">
                                <td class="font-medium">#<?php echo str_pad($claim['claim_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <?php if($isAdmin): ?>
                                <td>
                                    <div class="employee-info">
                                        <div class="employee-avatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <span><?php echo htmlspecialchars($claim['full_name']); ?></span>
                                    </div>
                                </td>
                                <?php endif; ?>
                                <td><?php echo htmlspecialchars($claim['claim_month']); ?></td>
                                <td>
                                    <span class="vehicle-badge">
                                        <i class="fas fa-<?php echo $claim['vehicle_type'] == 'Car' ? 'car' : 'motorcycle'; ?>"></i>
                                        <?php echo htmlspecialchars($claim['vehicle_type']); ?>
                                    </span>
                                </td>
                                <td class="font-medium">RM <?php echo number_format($claim['amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge <?php echo getStatusBadgeClass($claim['status']); ?>">
                                        <?php echo $claim['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($claim['submitted_date']); ?></td>
                                <td>
                                    <div class="action-buttons" onclick="event.stopPropagation()">
                                        <a href="view.php?id=<?php echo $claim['claim_id']; ?>" class="action-btn action-btn-view" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if($claim['status'] == 'Pending' && ($claim['staff_id'] == $_SESSION['staff_id'] || $isAdmin)): ?>
                                        <a href="edit.php?id=<?php echo $claim['claim_id']; ?>" class="action-btn action-btn-edit" title="Edit Claim">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if($isAdmin): ?>
                                        <div class="dropdown-wrapper">
                                            <button class="action-btn action-btn-status" title="Change Status" onclick="toggleDropdown(this)">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                            <div class="dropdown-menu-custom">
                                                <a href="update_status.php?id=<?php echo $claim['claim_id']; ?>&status=Approved" class="dropdown-item-custom">
                                                    <i class="fas fa-check text-green-500"></i> Approve
                                                </a>
                                                <a href="update_status.php?id=<?php echo $claim['claim_id']; ?>&status=Rejected" class="dropdown-item-custom">
                                                    <i class="fas fa-times text-red-500"></i> Reject
                                                </a>
                                                <a href="update_status.php?id=<?php echo $claim['claim_id']; ?>&status=Paid" class="dropdown-item-custom">
                                                    <i class="fas fa-dollar-sign text-blue-500"></i> Mark as Paid
                                                </a>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="../../assets/js/modern-dashboard.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({ duration: 800, easing: 'ease-in-out', once: true });
            initializeDashboard();
        });

        function toggleDropdown(btn) {
            const menu = btn.nextElementSibling;
            document.querySelectorAll('.dropdown-menu-custom.open').forEach(function(m) {
                if(m !== menu) m.classList.remove('open');
            });
            menu.classList.toggle('open');
        }

        document.addEventListener('click', function(e) {
            if(!e.target.closest('.dropdown-wrapper')) {
                document.querySelectorAll('.dropdown-menu-custom.open').forEach(function(m) {
                    m.classList.remove('open');
                });
            }
        });
    </script>

    <style>
        /* Employee info cell */
        .employee-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .employee-avatar {
            width: 32px;
            height: 32px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.75rem;
            flex-shrink: 0;
        }

        /* Vehicle badge */
        .vehicle-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.25rem 0.625rem;
            background: var(--gray-100);
            color: var(--gray-700);
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        /* Status badges */
        .status-approved {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }
        .status-rejected {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }
        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }
        .status-paid {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary-color);
        }

        /* Action buttons */
        .action-btn-view  { background: rgba(59,130,246,0.1); color: var(--primary-color); }
        .action-btn-view:hover  { background: var(--primary-color); color: white; }
        .action-btn-edit  { background: rgba(245,158,11,0.1); color: var(--warning-color); }
        .action-btn-edit:hover  { background: var(--warning-color); color: white; }
        .action-btn-status { background: var(--gray-100); color: var(--gray-600); }
        .action-btn-status:hover { background: var(--gray-600); color: white; }

        /* Dropdown */
        .dropdown-wrapper { position: relative; display: inline-block; }
        .dropdown-menu-custom {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + 4px);
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius-sm);
            box-shadow: var(--shadow-lg);
            z-index: 100;
            min-width: 160px;
            overflow: hidden;
        }
        .dropdown-menu-custom.open { display: block; }
        .dropdown-item-custom {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1rem;
            font-size: 0.875rem;
            color: var(--gray-700);
            text-decoration: none;
            transition: var(--transition);
        }
        .dropdown-item-custom:hover { background: var(--gray-50); color: var(--gray-900); }

        /* Alert */
        .alert {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            border: 1px solid;
        }
        .alert-success { background: rgba(16,185,129,0.1); border-color: var(--success-color); color: var(--success-color); }
        .alert-content { display: flex; align-items: center; gap: 0.75rem; }
        .alert-close { background: none; border: none; color: inherit; cursor: pointer; padding: 0.25rem; border-radius: var(--border-radius-sm); }

        /* Empty state */
        .empty-state { text-align: center; padding: 3rem 2rem; }
        .empty-icon { font-size: 3rem; color: var(--gray-300); margin-bottom: 1rem; }
        .empty-state h3 { color: var(--gray-700); margin-bottom: 0.5rem; }
        .empty-state p { color: var(--gray-500); margin-bottom: 1rem; }
    </style>
</body>
</html>
