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

// Get application ID
$application_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($application_id <= 0){
    header("location: index.php");
    exit;
}

// Check permissions
$is_admin_or_manager = ($_SESSION['position'] == 'Admin' || $_SESSION['position'] == 'Manager');

// Fetch application details
try {
    $sql = "SELECT oa.*,
                   s.full_name as staff_name,
                   s.email as staff_email,
                   s.department,
                   s.position,
                   approver.full_name as approver_name
            FROM outstation_applications oa
            LEFT JOIN staff s ON oa.staff_id = s.staff_id
            LEFT JOIN staff approver ON oa.approved_by = approver.staff_id
            WHERE oa.application_id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $application_id, PDO::PARAM_INT);
    $stmt->execute();
    $application = $stmt->fetch();

    if(!$application){
        header("location: index.php");
        exit;
    }

    // Check if user has permission to view this application
    if(!$is_admin_or_manager && $application['staff_id'] != $_SESSION['staff_id']){
        header("location: index.php");
        exit;
    }

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Application - Outstation Leave Management</title>

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
                        <h1>Application Details</h1>
                        <p><?php echo htmlspecialchars($application['application_number']); ?></p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="index.php" class="export-btn">
                        <i class="fas fa-arrow-left"></i>
                        Back to List
                    </a>
                    <?php if($application['status'] == 'Pending' && ($application['staff_id'] == $_SESSION['staff_id'] || $is_admin_or_manager)): ?>
                    <a href="edit.php?id=<?php echo $application_id; ?>" class="export-btn" style="margin-left: 10px;">
                        <i class="fas fa-edit"></i>
                        Edit
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <div class="view-container" data-aos="fade-up">
                <!-- Status Banner -->
                <div class="status-banner status-banner-<?php echo strtolower($application['status']); ?>">
                    <div class="status-banner-content">
                        <div class="status-banner-icon">
                            <?php
                            switch($application['status']) {
                                case 'Pending':
                                    echo '<i class="fas fa-clock"></i>';
                                    break;
                                case 'Approved':
                                    echo '<i class="fas fa-check-circle"></i>';
                                    break;
                                case 'Rejected':
                                    echo '<i class="fas fa-times-circle"></i>';
                                    break;
                                case 'Cancelled':
                                    echo '<i class="fas fa-ban"></i>';
                                    break;
                                case 'Completed':
                                    echo '<i class="fas fa-flag-checkered"></i>';
                                    break;
                            }
                            ?>
                        </div>
                        <div>
                            <div class="status-banner-title">Application Status: <?php echo $application['status']; ?></div>
                            <div class="status-banner-subtitle">
                                <?php
                                switch($application['status']) {
                                    case 'Pending':
                                        echo 'This application is awaiting approval from management';
                                        break;
                                    case 'Approved':
                                        echo 'This application has been approved' . ($application['approver_name'] ? ' by ' . htmlspecialchars($application['approver_name']) : '');
                                        break;
                                    case 'Rejected':
                                        echo 'This application has been rejected';
                                        break;
                                    case 'Cancelled':
                                        echo 'This application has been cancelled';
                                        break;
                                    case 'Completed':
                                        echo 'This trip has been completed';
                                        break;
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php if($is_admin_or_manager && $application['status'] == 'Pending'): ?>
                    <div class="status-banner-actions">
                        <button onclick="approveApplication()" class="status-action-btn approve-btn">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button onclick="rejectApplication()" class="status-action-btn reject-btn">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="view-grid">
                    <!-- Staff Information -->
                    <div class="view-card">
                        <div class="view-card-header">
                            <i class="fas fa-user"></i>
                            <h3>Staff Information</h3>
                        </div>
                        <div class="view-card-body">
                            <div class="info-row">
                                <span class="info-label">Staff Name:</span>
                                <span class="info-value"><?php echo htmlspecialchars($application['staff_name']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Email:</span>
                                <span class="info-value"><?php echo htmlspecialchars($application['staff_email']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Department:</span>
                                <span class="info-value"><?php echo htmlspecialchars($application['department']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Position:</span>
                                <span class="info-value"><?php echo htmlspecialchars($application['position']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Trip Details -->
                    <div class="view-card">
                        <div class="view-card-header">
                            <i class="fas fa-plane"></i>
                            <h3>Trip Details</h3>
                        </div>
                        <div class="view-card-body">
                            <div class="info-row">
                                <span class="info-label">Purpose:</span>
                                <span class="info-value">
                                    <span class="purpose-badge"><?php echo htmlspecialchars($application['purpose']); ?></span>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Destination:</span>
                                <span class="info-value">
                                    <i class="fas fa-map-marker-alt text-primary"></i>
                                    <?php echo htmlspecialchars($application['destination']); ?>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Transportation:</span>
                                <span class="info-value"><?php echo htmlspecialchars($application['transportation_mode']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Estimated Cost:</span>
                                <span class="info-value">RM <?php echo number_format($application['estimated_cost'], 2); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Travel Timeline -->
                    <div class="view-card full-width">
                        <div class="view-card-header">
                            <i class="fas fa-calendar-alt"></i>
                            <h3>Travel Timeline</h3>
                        </div>
                        <div class="view-card-body">
                            <div class="timeline-container">
                                <div class="timeline-item">
                                    <div class="timeline-icon departure">
                                        <i class="fas fa-plane-departure"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="timeline-title">Departure</div>
                                        <div class="timeline-date"><?php echo date('l, F j, Y', strtotime($application['departure_date'])); ?></div>
                                        <?php if($application['departure_time']): ?>
                                        <div class="timeline-time"><?php echo date('g:i A', strtotime($application['departure_time'])); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="timeline-divider">
                                    <div class="timeline-line"></div>
                                    <div class="timeline-duration">
                                        <i class="fas fa-moon"></i>
                                        <?php echo $application['total_nights']; ?> night<?php echo $application['total_nights'] != 1 ? 's' : ''; ?>
                                    </div>
                                </div>

                                <div class="timeline-item">
                                    <div class="timeline-icon arrival">
                                        <i class="fas fa-plane-arrival"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="timeline-title">Return</div>
                                        <div class="timeline-date"><?php echo date('l, F j, Y', strtotime($application['return_date'])); ?></div>
                                        <?php if($application['return_time']): ?>
                                        <div class="timeline-time"><?php echo date('g:i A', strtotime($application['return_time'])); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Claimability Status -->
                    <div class="view-card">
                        <div class="view-card-header">
                            <i class="fas fa-hand-holding-usd"></i>
                            <h3>Claimability Status</h3>
                        </div>
                        <div class="view-card-body">
                            <div class="claimability-display">
                                <?php if($application['is_claimable']): ?>
                                    <div class="claimability-badge claimable">
                                        <i class="fas fa-check-circle"></i>
                                        <div>
                                            <div class="claimability-title">Eligible for Claim</div>
                                            <div class="claimability-subtitle">This trip qualifies for outstation leave allowance</div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="claimability-badge not-claimable">
                                        <i class="fas fa-times-circle"></i>
                                        <div>
                                            <div class="claimability-title">Not Eligible</div>
                                            <div class="claimability-subtitle">Trip duration is less than the minimum required</div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="view-card">
                        <div class="view-card-header">
                            <i class="fas fa-info-circle"></i>
                            <h3>Additional Information</h3>
                        </div>
                        <div class="view-card-body">
                            <?php if($application['accommodation_details']): ?>
                            <div class="info-row">
                                <span class="info-label">Accommodation:</span>
                                <span class="info-value"><?php echo nl2br(htmlspecialchars($application['accommodation_details'])); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if($application['remarks']): ?>
                            <div class="info-row">
                                <span class="info-label">Remarks:</span>
                                <span class="info-value"><?php echo nl2br(htmlspecialchars($application['remarks'])); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if($application['rejection_reason']): ?>
                            <div class="info-row">
                                <span class="info-label">Rejection Reason:</span>
                                <span class="info-value rejection-reason"><?php echo nl2br(htmlspecialchars($application['rejection_reason'])); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="info-row">
                                <span class="info-label">Submitted On:</span>
                                <span class="info-value"><?php echo date('F j, Y \a\t g:i A', strtotime($application['created_at'])); ?></span>
                            </div>
                            <?php if($application['approved_at']): ?>
                            <div class="info-row">
                                <span class="info-label">Approved On:</span>
                                <span class="info-value"><?php echo date('F j, Y \a\t g:i A', strtotime($application['approved_at'])); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Approved By:</span>
                                <span class="info-value"><?php echo htmlspecialchars($application['approver_name']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Approval Modal -->
    <div id="approvalModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Approve Application</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to approve this outstation leave application?</p>
                <form id="approvalForm" method="POST" action="api/approve_application.php">
                    <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
                    <input type="hidden" name="action" value="approve">
                    <div class="form-group">
                        <label>Approval Notes (Optional):</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="btn-success">Approve Application</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Rejection Modal -->
    <div id="rejectionModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reject Application</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="rejectionForm" method="POST" action="api/approve_application.php">
                    <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
                    <input type="hidden" name="action" value="reject">
                    <div class="form-group">
                        <label>Rejection Reason <span class="required">*</span>:</label>
                        <textarea name="rejection_reason" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="btn-danger">Reject Application</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="../../assets/js/modern-dashboard.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true
            });

            initializeDashboard();
        });

        function approveApplication() {
            document.getElementById('approvalModal').style.display = 'flex';
        }

        function rejectApplication() {
            document.getElementById('rejectionModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('approvalModal').style.display = 'none';
            document.getElementById('rejectionModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const approvalModal = document.getElementById('approvalModal');
            const rejectionModal = document.getElementById('rejectionModal');
            if (event.target == approvalModal) {
                closeModal();
            }
            if (event.target == rejectionModal) {
                closeModal();
            }
        }
    </script>

    <style>
        /* View Container Styles */
        .view-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .status-banner {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
        }

        .status-banner-pending { border-left-color: var(--warning-color); }
        .status-banner-approved { border-left-color: var(--success-color); }
        .status-banner-rejected { border-left-color: var(--danger-color); }
        .status-banner-cancelled { border-left-color: var(--gray-400); }
        .status-banner-completed { border-left-color: var(--info-color); }

        .status-banner-content {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .status-banner-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .status-banner-pending .status-banner-icon {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .status-banner-approved .status-banner-icon {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .status-banner-rejected .status-banner-icon {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .status-banner-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
        }

        .status-banner-subtitle {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin-top: 0.25rem;
        }

        .status-banner-actions {
            display: flex;
            gap: 0.5rem;
        }

        .status-action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: var(--border-radius-sm);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .approve-btn {
            background: var(--success-color);
            color: white;
        }

        .approve-btn:hover {
            background: #059669;
        }

        .reject-btn {
            background: var(--danger-color);
            color: white;
        }

        .reject-btn:hover {
            background: #dc2626;
        }

        .view-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .view-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .view-card.full-width {
            grid-column: 1 / -1;
        }

        .view-card-header {
            background: var(--gray-50);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .view-card-header i {
            color: var(--primary-color);
            font-size: 1.25rem;
        }

        .view-card-header h3 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
        }

        .view-card-body {
            padding: 1.5rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-100);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 500;
            color: var(--gray-600);
            flex: 0 0 40%;
        }

        .info-value {
            color: var(--gray-900);
            flex: 1;
            text-align: right;
        }

        .purpose-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: var(--primary-color);
            color: white;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .timeline-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
        }

        .timeline-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .timeline-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .timeline-icon.departure {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }

        .timeline-icon.arrival {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .timeline-content {
            text-align: center;
        }

        .timeline-title {
            font-weight: 600;
            color: var(--gray-900);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .timeline-date {
            color: var(--gray-700);
            margin-top: 0.25rem;
        }

        .timeline-time {
            color: var(--gray-500);
            font-size: 0.875rem;
            margin-top: 0.125rem;
        }

        .timeline-divider {
            flex: 0 0 200px;
            position: relative;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .timeline-line {
            position: absolute;
            top: 30px;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #3b82f6, #10b981);
        }

        .timeline-duration {
            position: relative;
            z-index: 1;
            background: white;
            padding: 0.5rem 1rem;
            border: 2px solid var(--primary-color);
            border-radius: 9999px;
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .claimability-display {
            padding: 1rem 0;
        }

        .claimability-badge {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            border-radius: var(--border-radius);
        }

        .claimability-badge.claimable {
            background: rgba(16, 185, 129, 0.1);
            border: 2px solid var(--success-color);
        }

        .claimability-badge.not-claimable {
            background: rgba(107, 114, 128, 0.1);
            border: 2px solid var(--gray-400);
        }

        .claimability-badge i {
            font-size: 2rem;
        }

        .claimability-badge.claimable i {
            color: var(--success-color);
        }

        .claimability-badge.not-claimable i {
            color: var(--gray-400);
        }

        .claimability-title {
            font-weight: 600;
            font-size: 1.125rem;
            color: var(--gray-900);
        }

        .claimability-subtitle {
            color: var(--gray-600);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .rejection-reason {
            color: var(--danger-color);
            font-weight: 500;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            border-radius: var(--border-radius);
            max-width: 500px;
            width: 90%;
            box-shadow: var(--shadow-lg);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .modal-header h3 {
            margin: 0;
            color: var(--gray-900);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray-400);
            line-height: 1;
        }

        .modal-close:hover {
            color: var(--gray-700);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--gray-700);
        }

        .form-control {
            width: 100%;
            padding: 0.625rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius-sm);
            font-size: 0.875rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn-secondary {
            padding: 0.625rem 1.25rem;
            background: var(--gray-200);
            color: var(--gray-700);
            border: none;
            border-radius: var(--border-radius-sm);
            font-weight: 500;
            cursor: pointer;
        }

        .btn-secondary:hover {
            background: var(--gray-300);
        }

        .btn-success {
            padding: 0.625rem 1.25rem;
            background: var(--success-color);
            color: white;
            border: none;
            border-radius: var(--border-radius-sm);
            font-weight: 500;
            cursor: pointer;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-danger {
            padding: 0.625rem 1.25rem;
            background: var(--danger-color);
            color: white;
            border: none;
            border-radius: var(--border-radius-sm);
            font-weight: 500;
            cursor: pointer;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .required {
            color: var(--danger-color);
        }

        @media (max-width: 768px) {
            .view-grid {
                grid-template-columns: 1fr;
            }

            .timeline-container {
                flex-direction: column;
                gap: 2rem;
            }

            .timeline-divider {
                flex: unset;
                width: 60px;
                height: 100px;
            }

            .timeline-line {
                top: 0;
                bottom: 0;
                left: 30px;
                width: 2px;
                height: 100%;
            }

            .timeline-duration {
                transform: rotate(-90deg);
            }

            .status-banner {
                flex-direction: column;
                gap: 1rem;
            }

            .status-banner-actions {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</body>
</html>
