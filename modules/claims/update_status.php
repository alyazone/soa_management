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

// Check if user has admin/manager privileges
if($_SESSION['position'] != 'Admin' && $_SESSION['position'] != 'Manager'){
    header("location: index.php");
    exit;
}

// Check if id and status parameters are set
if(empty($_GET["id"]) || empty($_GET["status"])){
    header("location: index.php");
    exit();
}

$claim_id   = $_GET["id"];
$new_status = $_GET["status"];
$rejection_reason = "";
$payment_details  = "";
$error_message    = "";

// Validate status
$valid_statuses = ['Pending', 'Approved', 'Rejected', 'Paid'];
if(!in_array($new_status, $valid_statuses)){
    header("location: index.php");
    exit();
}

// Fetch claim data
try {
    $stmt = $pdo->prepare("SELECT c.*, s.full_name, s.department
                           FROM claims c
                           JOIN staff s ON c.staff_id = s.staff_id
                           WHERE c.claim_id = :id");
    $stmt->bindParam(":id", $claim_id, PDO::PARAM_INT);
    $stmt->execute();

    if($stmt->rowCount() != 1){
        header("location: index.php");
        exit();
    }

    $claim = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("ERROR: Could not fetch claim. " . $e->getMessage());
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $new_status = $_POST["status"];

    if($new_status == "Rejected"){
        if(empty(trim($_POST["rejection_reason"]))){
            $error_message = "Please provide a reason for rejection.";
        } else {
            $rejection_reason = trim($_POST["rejection_reason"]);
        }
    }

    if($new_status == "Paid"){
        if(empty(trim($_POST["payment_details"]))){
            $error_message = "Please provide payment details.";
        } else {
            $payment_details = trim($_POST["payment_details"]);
        }
    }

    if(empty($error_message)){
        try {
            // Check if approval columns exist
            $columnCheckStmt = $pdo->prepare("SHOW COLUMNS FROM claims LIKE 'approved_by'");
            $columnCheckStmt->execute();
            $approvalColumnsExist = $columnCheckStmt->rowCount() > 0;

            if($approvalColumnsExist) {
                if($new_status == "Rejected"){
                    $sql = "UPDATE claims SET status = :status, rejection_reason = :rejection_reason,
                            approved_by = :approved_by, approval_signature = 1, approval_date = CURDATE()
                            WHERE claim_id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(":rejection_reason", $rejection_reason, PDO::PARAM_STR);
                    $stmt->bindParam(":approved_by", $_SESSION["username"], PDO::PARAM_STR);
                } else if($new_status == "Paid"){
                    $sql = "UPDATE claims SET status = :status, payment_details = :payment_details,
                            approved_by = :approved_by, approval_signature = 1, approval_date = CURDATE()
                            WHERE claim_id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(":payment_details", $payment_details, PDO::PARAM_STR);
                    $stmt->bindParam(":approved_by", $_SESSION["username"], PDO::PARAM_STR);
                } else {
                    $sql = "UPDATE claims SET status = :status, approved_by = :approved_by,
                            approval_signature = 1, approval_date = CURDATE()
                            WHERE claim_id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(":approved_by", $_SESSION["username"], PDO::PARAM_STR);
                }
            } else {
                $sql  = "UPDATE claims SET status = :status WHERE claim_id = :id";
                $stmt = $pdo->prepare($sql);
            }

            $stmt->bindParam(":status", $new_status, PDO::PARAM_STR);
            $stmt->bindParam(":id", $claim_id, PDO::PARAM_INT);

            if($stmt->execute()){
                header("location: view.php?id=" . $claim_id);
                exit();
            } else {
                $error_message = "Something went wrong. Please try again later.";
            }
        } catch(PDOException $e) {
            $error_message = "ERROR: Could not execute query. " . $e->getMessage();
        }
    }
}

// Status badge helper
function getStatusBadgeClass($status) {
    switch($status) {
        case 'Approved': return 'status-approved';
        case 'Rejected': return 'status-rejected';
        case 'Pending':  return 'status-pending';
        case 'Paid':     return 'status-paid';
        default:         return 'status-pending';
    }
}

// Status icon
function getStatusIcon($status) {
    switch($status) {
        case 'Approved': return 'fa-check-circle';
        case 'Rejected': return 'fa-times-circle';
        case 'Pending':  return 'fa-hourglass-half';
        case 'Paid':     return 'fa-dollar-sign';
        default:         return 'fa-question-circle';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Claim Status - SOA Management System</title>
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
                        <h1>Update Claim Status</h1>
                        <p>Claim #<?php echo str_pad($claim_id, 4, '0', STR_PAD_LEFT); ?> &mdash; <?php echo htmlspecialchars($claim['full_name']); ?></p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="view.php?id=<?php echo $claim_id; ?>" class="export-btn secondary">
                        <i class="fas fa-eye"></i>
                        View Claim
                    </a>
                    <a href="index.php" class="export-btn secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to List
                    </a>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="dashboard-content">

            <?php if(!empty($error_message)): ?>
            <div class="alert alert-error" data-aos="fade-down">
                <div class="alert-content"><i class="fas fa-exclamation-circle"></i> <span><?php echo htmlspecialchars($error_message); ?></span></div>
                <button class="alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            </div>
            <?php endif; ?>

            <!-- Status transition banner -->
            <div class="transition-banner" data-aos="fade-down">
                <div class="transition-from">
                    <div class="transition-label">Current Status</div>
                    <span class="status-badge <?php echo getStatusBadgeClass($claim['status']); ?> status-badge-lg">
                        <i class="fas <?php echo getStatusIcon($claim['status']); ?>"></i>
                        <?php echo $claim['status']; ?>
                    </span>
                </div>
                <div class="transition-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
                <div class="transition-to">
                    <div class="transition-label">New Status</div>
                    <span class="status-badge <?php echo getStatusBadgeClass($new_status); ?> status-badge-lg" id="newStatusBadge">
                        <i class="fas <?php echo getStatusIcon($new_status); ?>" id="newStatusIcon"></i>
                        <span id="newStatusText"><?php echo $new_status; ?></span>
                    </span>
                </div>
            </div>

            <div class="update-grid" data-aos="fade-up">

                <!-- Claim Summary -->
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3><i class="fas fa-file-invoice-dollar"></i> Claim Summary</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="info-row">
                            <span class="info-label">Claim ID</span>
                            <span class="info-value">#<?php echo str_pad($claim['claim_id'], 4, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Employee</span>
                            <span class="info-value">
                                <div class="employee-inline">
                                    <div class="employee-avatar-sm"><i class="fas fa-user"></i></div>
                                    <?php echo htmlspecialchars($claim['full_name']); ?>
                                </div>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Department</span>
                            <span class="info-value"><?php echo htmlspecialchars($claim['department']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Claim Month</span>
                            <span class="info-value"><?php echo htmlspecialchars($claim['claim_month']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Vehicle</span>
                            <span class="info-value">
                                <span class="vehicle-badge">
                                    <i class="fas fa-<?php echo $claim['vehicle_type'] == 'Car' ? 'car' : 'motorcycle'; ?>"></i>
                                    <?php echo htmlspecialchars($claim['vehicle_type']); ?>
                                </span>
                            </span>
                        </div>
                        <div class="info-divider"></div>
                        <div class="info-row info-row-highlight">
                            <span class="info-label">Total Amount</span>
                            <span class="info-value amount-large">RM <?php echo number_format($claim['amount'], 2); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">KM Rate Amount</span>
                            <span class="info-value">RM <?php echo number_format($claim['total_km_amount'], 2); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">KM Rate</span>
                            <span class="info-value">RM <?php echo number_format($claim['km_rate'], 2); ?>/km</span>
                        </div>
                    </div>
                </div>

                <!-- Status Update Form -->
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3><i class="fas fa-edit"></i> Update Status</h3>
                    </div>
                    <div class="detail-card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $claim_id . "&status=" . htmlspecialchars($new_status); ?>" method="post" id="statusForm">

                            <div class="form-group" style="margin-bottom:1.25rem;">
                                <label class="form-label">New Status <span class="required">*</span></label>
                                <select name="status" class="form-input" id="statusSelect">
                                    <option value="Pending"  <?php echo ($new_status == "Pending")  ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Approved" <?php echo ($new_status == "Approved") ? 'selected' : ''; ?>>Approved</option>
                                    <option value="Rejected" <?php echo ($new_status == "Rejected") ? 'selected' : ''; ?>>Rejected</option>
                                    <option value="Paid"     <?php echo ($new_status == "Paid")     ? 'selected' : ''; ?>>Paid</option>
                                </select>
                            </div>

                            <!-- Rejection reason (shown when Rejected) -->
                            <div id="rejectionReasonDiv" class="conditional-field" style="display: <?php echo ($new_status == 'Rejected') ? 'block' : 'none'; ?>;">
                                <div class="conditional-field-inner rejection">
                                    <label class="form-label"><i class="fas fa-exclamation-triangle"></i> Rejection Reason <span class="required">*</span></label>
                                    <textarea name="rejection_reason" class="form-input form-textarea" rows="4" placeholder="Explain why this claim is being rejected..."><?php echo htmlspecialchars($rejection_reason); ?></textarea>
                                    <p class="field-hint">This reason will be visible to the employee.</p>
                                </div>
                            </div>

                            <!-- Payment details (shown when Paid) -->
                            <div id="paymentDetailsDiv" class="conditional-field" style="display: <?php echo ($new_status == 'Paid') ? 'block' : 'none'; ?>;">
                                <div class="conditional-field-inner payment">
                                    <label class="form-label"><i class="fas fa-dollar-sign"></i> Payment Details <span class="required">*</span></label>
                                    <textarea name="payment_details" class="form-input form-textarea" rows="4" placeholder="e.g., Payment via bank transfer on 24 Feb 2026, Ref: TXN123456..."><?php echo htmlspecialchars($payment_details); ?></textarea>
                                    <p class="field-hint">Include the payment date, reference number, and method.</p>
                                </div>
                            </div>

                            <div class="policy-note">
                                <i class="fas fa-info-circle"></i>
                                <p>Changing the status will update the claim record. Approved and Paid statuses will record your approval signature and today's date.</p>
                            </div>

                            <div class="form-actions" style="margin-top:1.5rem;">
                                <button type="submit" class="btn-primary-large" id="submitBtn">
                                    <i class="fas fa-save"></i> Update Status
                                </button>
                                <a href="view.php?id=<?php echo $claim_id; ?>" class="btn-secondary-large">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
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

        var statusSelect    = document.getElementById('statusSelect');
        var rejectionDiv    = document.getElementById('rejectionReasonDiv');
        var paymentDiv      = document.getElementById('paymentDetailsDiv');
        var newStatusBadge  = document.getElementById('newStatusBadge');
        var newStatusIcon   = document.getElementById('newStatusIcon');
        var newStatusText   = document.getElementById('newStatusText');
        var submitBtn       = document.getElementById('submitBtn');

        var statusClasses = {
            'Approved': { badge: 'status-approved', icon: 'fa-check-circle',   label: 'Approved' },
            'Rejected': { badge: 'status-rejected',  icon: 'fa-times-circle',   label: 'Rejected' },
            'Pending':  { badge: 'status-pending',   icon: 'fa-hourglass-half', label: 'Pending'  },
            'Paid':     { badge: 'status-paid',      icon: 'fa-dollar-sign',    label: 'Paid'     }
        };

        var submitLabels = {
            'Approved': 'Approve Claim',
            'Rejected': 'Reject Claim',
            'Pending':  'Set as Pending',
            'Paid':     'Mark as Paid'
        };

        function onStatusChange() {
            var val = statusSelect.value;

            // Show/hide conditional fields
            rejectionDiv.style.display = (val === 'Rejected') ? 'block' : 'none';
            paymentDiv.style.display   = (val === 'Paid')     ? 'block' : 'none';

            // Update live badge
            var cfg = statusClasses[val] || statusClasses['Pending'];
            newStatusBadge.className = 'status-badge ' + cfg.badge + ' status-badge-lg';
            newStatusIcon.className  = 'fas ' + cfg.icon;
            newStatusText.textContent = cfg.label;

            // Update submit button label
            submitBtn.innerHTML = '<i class="fas fa-save"></i> ' + (submitLabels[val] || 'Update Status');
        }

        statusSelect.addEventListener('change', onStatusChange);
        onStatusChange(); // initialize on load
    });
    </script>

    <style>
        /* Export btn secondary */
        .export-btn.secondary { background: white; border-color: var(--gray-300); color: var(--gray-700); }
        .export-btn.secondary:hover { background: var(--gray-50); border-color: var(--gray-400); color: var(--gray-900); }

        /* Alert */
        .alert { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.5rem; border-radius: var(--border-radius); margin-bottom: 1.5rem; border: 1px solid; }
        .alert-error { background: rgba(239,68,68,0.05); border-color: var(--danger-color); color: var(--danger-color); }
        .alert-content { display: flex; align-items: center; gap: 0.75rem; }
        .alert-close { background: none; border: none; color: inherit; cursor: pointer; }

        /* Status transition banner */
        .transition-banner {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }
        .transition-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--gray-500); margin-bottom: 0.625rem; text-align: center; }
        .transition-arrow { color: var(--gray-400); font-size: 1.5rem; }
        .status-badge-lg { padding: 0.5rem 1.25rem !important; font-size: 0.9rem !important; display: inline-flex; align-items: center; gap: 0.5rem; }

        /* Status badges */
        .status-badge { display: inline-flex; align-items: center; padding: 0.375rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
        .status-approved { background: rgba(16,185,129,0.1); color: var(--success-color); }
        .status-rejected  { background: rgba(239,68,68,0.1);  color: var(--danger-color); }
        .status-pending   { background: rgba(245,158,11,0.1); color: var(--warning-color); }
        .status-paid      { background: rgba(59,130,246,0.1); color: var(--primary-color); }

        /* Two-column grid */
        .update-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            align-items: start;
        }

        /* Detail card */
        .detail-card { background: white; border-radius: var(--border-radius); box-shadow: var(--shadow); border: 1px solid var(--gray-200); overflow: hidden; }
        .detail-card-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--gray-100); }
        .detail-card-header h3 { display: flex; align-items: center; gap: 0.5rem; font-size: 1rem; font-weight: 600; color: var(--gray-900); margin: 0; }
        .detail-card-header h3 i { color: var(--primary-color); }
        .detail-card-body { padding: 1.25rem 1.5rem; }

        /* Info rows */
        .info-row { display: flex; justify-content: space-between; align-items: center; padding: 0.625rem 0; border-bottom: 1px solid var(--gray-100); gap: 1rem; }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-size: 0.8125rem; color: var(--gray-500); font-weight: 500; flex-shrink: 0; min-width: 130px; }
        .info-value { font-size: 0.875rem; color: var(--gray-900); font-weight: 500; text-align: right; }
        .info-divider { border-top: 2px solid var(--gray-200); margin: 0.75rem 0; }
        .info-row-highlight { background: var(--gray-50); margin: 0 -1.5rem; padding: 0.75rem 1.5rem; }
        .amount-large { font-size: 1.125rem; font-weight: 700; color: var(--success-color); }

        /* Employee inline */
        .employee-inline { display: flex; align-items: center; gap: 0.5rem; justify-content: flex-end; }
        .employee-avatar-sm { width: 24px; height: 24px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.65rem; flex-shrink: 0; }

        /* Vehicle badge */
        .vehicle-badge { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.2rem 0.625rem; background: var(--gray-100); color: var(--gray-700); border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }

        /* Form elements */
        .form-label { display: block; font-size: 0.875rem; font-weight: 500; color: var(--gray-700); margin-bottom: 0.5rem; }
        .form-input { width: 100%; padding: 0.625rem 0.875rem; border: 1px solid var(--gray-300); border-radius: var(--border-radius-sm); font-size: 0.875rem; color: var(--gray-900); background: white; transition: var(--transition); box-sizing: border-box; }
        .form-input:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .form-textarea { resize: vertical; min-height: 100px; font-family: inherit; }
        .required { color: var(--danger-color); }
        .field-hint { font-size: 0.75rem; color: var(--gray-500); margin-top: 0.375rem; }

        /* Conditional fields */
        .conditional-field { margin-bottom: 1.25rem; }
        .conditional-field-inner { padding: 1rem; border-radius: var(--border-radius-sm); border: 1px solid; }
        .conditional-field-inner.rejection { background: rgba(239,68,68,0.04); border-color: rgba(239,68,68,0.2); }
        .conditional-field-inner.rejection .form-label { color: var(--danger-color); }
        .conditional-field-inner.payment { background: rgba(16,185,129,0.04); border-color: rgba(16,185,129,0.2); }
        .conditional-field-inner.payment .form-label { color: var(--success-color); }

        /* Policy note */
        .policy-note { display: flex; gap: 0.75rem; align-items: flex-start; background: rgba(59,130,246,0.05); border: 1px solid rgba(59,130,246,0.15); border-radius: var(--border-radius-sm); padding: 0.875rem 1rem; font-size: 0.8125rem; color: var(--gray-600); margin-top: 1.25rem; }
        .policy-note i { color: var(--primary-color); margin-top: 0.1rem; flex-shrink: 0; }
        .policy-note p { margin: 0; }

        /* Responsive */
        @media (max-width: 900px) {
            .update-grid { grid-template-columns: 1fr; }
            .transition-banner { gap: 1rem; padding: 1.25rem; }
        }
    </style>
</body>
</html>
