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

// Fetch claim data
try {
    $stmt = $pdo->prepare("SELECT c.*, c.created_at, s.full_name, s.staff_id as employee_id, s.department
                          FROM claims c
                          JOIN staff s ON c.staff_id = s.staff_id
                          WHERE c.claim_id = :id");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();

    if($stmt->rowCount() != 1){
        header("location: index.php");
        exit();
    }

    $claim = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch travel entries
    $entry_stmt = $pdo->prepare("SELECT * FROM claim_travel_entries WHERE claim_id = :claim_id ORDER BY travel_date");
    $entry_stmt->bindParam(":claim_id", $_GET["id"], PDO::PARAM_INT);
    $entry_stmt->execute();
    $entries = $entry_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch meal entries
    $meal_stmt = $pdo->prepare("SELECT * FROM claim_meal_entries WHERE claim_id = :claim_id ORDER BY meal_date");
    $meal_stmt->bindParam(":claim_id", $_GET["id"], PDO::PARAM_INT);
    $meal_stmt->execute();
    $meal_entries = $meal_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate totals
    $total_km      = 0;
    $total_parking = 0;
    $total_toll    = 0;
    $total_meal    = 0;

    foreach($entries as $entry){
        $total_km      += floatval($entry['miles_traveled']);
        $total_parking += floatval($entry['parking_fee']);
        $total_toll    += floatval($entry['toll_fee']);
    }
    foreach($meal_entries as $meal){
        $total_meal += floatval($meal['amount']);
    }

} catch(PDOException $e) {
    die("ERROR: Could not fetch claim. " . $e->getMessage());
}

// Fetch receipt files
try {
    $claim_id = $_GET["id"];
    $receiptStmt = $pdo->prepare("SELECT * FROM claim_receipts WHERE claim_id = :claim_id ORDER BY upload_date DESC");
    $receiptStmt->bindParam(":claim_id", $claim_id, PDO::PARAM_INT);
    $receiptStmt->execute();
    $receipts = $receiptStmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $receipts = [];
}

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
    if(empty($date)) return 'Not available';
    return date("d M Y", strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim #<?php echo htmlspecialchars($_GET['id']); ?> - SOA Management System</title>
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
                        <h1>Claim #<?php echo str_pad($_GET['id'], 4, '0', STR_PAD_LEFT); ?></h1>
                        <p>Reimbursement claim details</p>
                    </div>
                </div>
                <div class="header-right">
                    <?php if($claim['status'] == 'Pending' && ($claim['staff_id'] == $_SESSION['staff_id'] || $_SESSION['position'] == 'Admin' || $_SESSION['position'] == 'Manager')): ?>
                    <a href="edit.php?id=<?php echo $_GET['id']; ?>" class="export-btn warning">
                        <i class="fas fa-edit"></i>
                        Edit Claim
                    </a>
                    <?php endif; ?>
                    <?php if($_SESSION['position'] == 'Admin' || $_SESSION['position'] == 'Manager'): ?>
                    <div class="header-dropdown-wrapper">
                        <button class="export-btn secondary" onclick="toggleHeaderDropdown(this)">
                            <i class="fas fa-cog"></i>
                            Change Status
                            <i class="fas fa-chevron-down" style="font-size:0.7rem;margin-left:0.25rem;"></i>
                        </button>
                        <div class="header-dropdown-menu">
                            <a href="update_status.php?id=<?php echo $_GET['id']; ?>&status=Approved" class="header-dropdown-item">
                                <i class="fas fa-check"></i> Approve
                            </a>
                            <a href="update_status.php?id=<?php echo $_GET['id']; ?>&status=Rejected" class="header-dropdown-item danger">
                                <i class="fas fa-times"></i> Reject
                            </a>
                            <a href="update_status.php?id=<?php echo $_GET['id']; ?>&status=Paid" class="header-dropdown-item success">
                                <i class="fas fa-dollar-sign"></i> Mark as Paid
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    <button class="export-btn secondary" onclick="window.print()">
                        <i class="fas fa-print"></i>
                        Print
                    </button>
                    <a href="index.php" class="export-btn secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to List
                    </a>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="dashboard-content">

            <!-- Profile / summary header -->
            <div class="profile-header" data-aos="fade-down">
                <div class="claim-avatar">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($claim['full_name']); ?></h2>
                    <p class="profile-subtitle"><?php echo htmlspecialchars($claim['department']); ?> &mdash; Staff #<?php echo htmlspecialchars($claim['employee_id']); ?></p>
                    <div class="profile-meta">
                        <span class="meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            <?php echo htmlspecialchars($claim['claim_month']); ?>
                        </span>
                        <span class="meta-item">
                            <i class="fas fa-<?php echo $claim['vehicle_type'] == 'Car' ? 'car' : 'motorcycle'; ?>"></i>
                            <?php echo htmlspecialchars($claim['vehicle_type']); ?>
                        </span>
                        <span class="meta-item">
                            <i class="fas fa-clock"></i>
                            Submitted: <?php echo formatDate($claim['created_at']); ?>
                        </span>
                    </div>
                </div>
                <div style="margin-left:auto;">
                    <span class="status-badge <?php echo getStatusBadgeClass($claim['status']); ?>" style="font-size:0.875rem;padding:0.5rem 1rem;">
                        <?php echo $claim['status']; ?>
                    </span>
                </div>
            </div>

            <!-- Summary stat cards -->
            <div class="stats-grid" data-aos="fade-up">
                <div class="stat-card stat-card-primary">
                    <div class="stat-card-content">
                        <div>
                            <div class="stat-label">Total KM</div>
                            <div class="stat-value"><?php echo number_format($total_km, 1); ?></div>
                        </div>
                        <div class="stat-icon"><i class="fas fa-road"></i></div>
                    </div>
                </div>
                <div class="stat-card stat-card-success">
                    <div class="stat-card-content">
                        <div>
                            <div class="stat-label">Mileage Amount (RM)</div>
                            <div class="stat-value"><?php echo number_format($claim['total_km_amount'], 2); ?></div>
                        </div>
                        <div class="stat-icon"><i class="fas fa-tachometer-alt"></i></div>
                    </div>
                </div>
                <div class="stat-card stat-card-warning">
                    <div class="stat-card-content">
                        <div>
                            <div class="stat-label">Parking + Toll (RM)</div>
                            <div class="stat-value"><?php echo number_format($total_parking + $total_toll, 2); ?></div>
                        </div>
                        <div class="stat-icon"><i class="fas fa-parking"></i></div>
                    </div>
                </div>
                <div class="stat-card stat-card-info">
                    <div class="stat-card-content">
                        <div>
                            <div class="stat-label">Total Reimbursement (RM)</div>
                            <div class="stat-value"><?php echo number_format($claim['amount'], 2); ?></div>
                        </div>
                        <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                    </div>
                </div>
            </div>

            <!-- Travel Details Table -->
            <div class="table-card" data-aos="fade-up">
                <div class="table-header">
                    <div class="table-title">
                        <h3><i class="fas fa-route"></i> Travel Details</h3>
                        <p><?php echo count($entries); ?> travel entries</p>
                    </div>
                </div>
                <div class="table-container">
                    <?php if(empty($entries)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-route"></i></div>
                        <p>No travel entries found.</p>
                    </div>
                    <?php else: ?>
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Purpose</th>
                                <th>Parking (RM)</th>
                                <th>Toll (RM)</th>
                                <th>KM</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($entries as $entry): ?>
                            <tr>
                                <td><?php echo formatDate($entry['travel_date']); ?></td>
                                <td><?php echo htmlspecialchars($entry['travel_from']); ?></td>
                                <td><?php echo htmlspecialchars($entry['travel_to']); ?></td>
                                <td><?php echo htmlspecialchars($entry['purpose']); ?></td>
                                <td class="font-medium"><?php echo number_format($entry['parking_fee'], 2); ?></td>
                                <td class="font-medium"><?php echo number_format($entry['toll_fee'], 2); ?></td>
                                <td class="font-medium"><?php echo number_format($entry['miles_traveled'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="totals-row">
                                <td colspan="4" class="font-medium">Totals</td>
                                <td class="font-medium">RM <?php echo number_format($total_parking, 2); ?></td>
                                <td class="font-medium">RM <?php echo number_format($total_toll, 2); ?></td>
                                <td class="font-medium"><?php echo number_format($total_km, 2); ?> km</td>
                            </tr>
                        </tfoot>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Meal Expenses Table -->
            <div class="table-card" data-aos="fade-up">
                <div class="table-header">
                    <div class="table-title">
                        <h3><i class="fas fa-utensils"></i> Meal Expenses</h3>
                        <p><?php echo count($meal_entries); ?> meal entries</p>
                    </div>
                </div>
                <div class="table-container">
                    <?php if(empty($meal_entries)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-utensils"></i></div>
                        <p>No meal expenses claimed.</p>
                    </div>
                    <?php else: ?>
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Meal Type</th>
                                <th>Description</th>
                                <th>Amount (RM)</th>
                                <th>Receipt Ref</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($meal_entries as $meal): ?>
                            <tr>
                                <td><?php echo formatDate($meal['meal_date']); ?></td>
                                <td>
                                    <span class="meal-type-badge"><?php echo htmlspecialchars($meal['meal_type']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($meal['description']); ?></td>
                                <td class="font-medium"><?php echo number_format($meal['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($meal['receipt_reference']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Reimbursement Summary + Signatures -->
            <div class="content-grid-2" data-aos="fade-up">

                <!-- Summary card -->
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3><i class="fas fa-calculator"></i> Reimbursement Summary</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="summary-row">
                            <span>Total Miles Traveled</span>
                            <strong><?php echo number_format($total_km, 2); ?> KM</strong>
                        </div>
                        <div class="summary-row">
                            <span>KM Rate Amount</span>
                            <strong>RM <?php echo number_format($claim['total_km_amount'], 2); ?></strong>
                        </div>
                        <div class="summary-row">
                            <span>Total Parking</span>
                            <strong>RM <?php echo number_format($total_parking, 2); ?></strong>
                        </div>
                        <div class="summary-row">
                            <span>Total Toll</span>
                            <strong>RM <?php echo number_format($total_toll, 2); ?></strong>
                        </div>
                        <div class="summary-row">
                            <span>Total Meal Expenses</span>
                            <strong>RM <?php echo number_format($claim['total_meal_amount'] ?? $total_meal, 2); ?></strong>
                        </div>
                        <div class="summary-divider"></div>
                        <div class="summary-row summary-total">
                            <span>Total Reimbursement</span>
                            <strong>RM <?php echo number_format($claim['amount'], 2); ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Signatures card -->
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3><i class="fas fa-signature"></i> Signatures & Approval</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="signature-item">
                            <div class="signature-label">Employee Signature</div>
                            <?php if($claim['employee_signature']): ?>
                                <span class="sig-status sig-signed"><i class="fas fa-check-circle"></i> Signed</span>
                            <?php else: ?>
                                <span class="sig-status sig-unsigned"><i class="fas fa-times-circle"></i> Not Signed</span>
                            <?php endif; ?>
                            <?php if($claim['signature_date']): ?>
                                <div class="sig-date"><?php echo formatDate($claim['signature_date']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="signature-divider"></div>
                        <div class="signature-item">
                            <div class="signature-label">Approval Signature</div>
                            <?php if($claim['approval_signature']): ?>
                                <span class="sig-status sig-signed"><i class="fas fa-check-circle"></i> Signed by <?php echo htmlspecialchars($claim['approved_by']); ?></span>
                            <?php else: ?>
                                <span class="sig-status sig-unsigned"><i class="fas fa-times-circle"></i> Not Approved</span>
                            <?php endif; ?>
                            <?php if($claim['approval_date']): ?>
                                <div class="sig-date"><?php echo formatDate($claim['approval_date']); ?></div>
                            <?php endif; ?>
                        </div>

                        <?php if($claim['status'] == 'Rejected' && !empty($claim['rejection_reason'])): ?>
                        <div class="signature-divider"></div>
                        <div class="rejection-note">
                            <div class="rejection-label"><i class="fas fa-exclamation-triangle"></i> Rejection Reason</div>
                            <p><?php echo nl2br(htmlspecialchars($claim['rejection_reason'])); ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if($claim['status'] == 'Paid' && !empty($claim['payment_details'])): ?>
                        <div class="signature-divider"></div>
                        <div class="payment-note">
                            <div class="payment-label"><i class="fas fa-dollar-sign"></i> Payment Details</div>
                            <p><?php echo nl2br(htmlspecialchars($claim['payment_details'])); ?></p>
                        </div>
                        <?php endif; ?>

                        <div class="signature-divider"></div>
                        <div class="certification-note">
                            <i class="fas fa-shield-alt"></i>
                            <p>I HEREBY CERTIFY that the reimbursement claimed on this form are proper and actual expenses incurred during this period and in accordance with the company's Reimbursement Policy.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Receipts -->
            <?php if(!empty($receipts)): ?>
            <div class="table-card" data-aos="fade-up">
                <div class="table-header">
                    <div class="table-title">
                        <h3><i class="fas fa-paperclip"></i> Uploaded Receipts</h3>
                        <p><?php echo count($receipts); ?> file(s) attached</p>
                    </div>
                </div>
                <div class="receipts-grid">
                    <?php foreach($receipts as $receipt): ?>
                    <div class="receipt-card">
                        <div class="receipt-icon">
                            <?php if(strpos($receipt['file_type'], 'image') !== false): ?>
                                <i class="fas fa-file-image"></i>
                            <?php else: ?>
                                <i class="fas fa-file-pdf"></i>
                            <?php endif; ?>
                        </div>
                        <div class="receipt-name" title="<?php echo htmlspecialchars($receipt['original_file_name']); ?>">
                            <?php echo htmlspecialchars($receipt['original_file_name']); ?>
                        </div>
                        <div class="receipt-size"><?php echo round($receipt['file_size'] / 1024, 1); ?> KB</div>
                        <div class="receipt-actions">
                            <a href="<?php echo $basePath; ?>uploads/receipts/<?php echo $receipt['file_name']; ?>"
                               class="action-btn action-btn-view" target="_blank" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="<?php echo $basePath; ?>uploads/receipts/<?php echo $receipt['file_name']; ?>"
                               class="action-btn action-btn-download" download="<?php echo htmlspecialchars($receipt['original_file_name']); ?>" title="Download">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
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

        function toggleHeaderDropdown(btn) {
            const menu = btn.nextElementSibling;
            menu.classList.toggle('open');
        }

        document.addEventListener('click', function(e) {
            if(!e.target.closest('.header-dropdown-wrapper')) {
                document.querySelectorAll('.header-dropdown-menu.open').forEach(function(m) {
                    m.classList.remove('open');
                });
            }
        });
    </script>

    <style>
        /* Status badges */
        .status-approved { background: rgba(16,185,129,0.1); color: var(--success-color); }
        .status-rejected  { background: rgba(239,68,68,0.1);  color: var(--danger-color); }
        .status-pending   { background: rgba(245,158,11,0.1); color: var(--warning-color); }
        .status-paid      { background: rgba(59,130,246,0.1); color: var(--primary-color); }

        /* Export btn variants */
        .export-btn.warning  { background: var(--warning-color); border-color: var(--warning-color); color: white; }
        .export-btn.warning:hover { background: #d97706; border-color: #d97706; }
        .export-btn.secondary { background: white; border-color: var(--gray-300); color: var(--gray-700); }
        .export-btn.secondary:hover { background: var(--gray-50); border-color: var(--gray-400); color: var(--gray-900); }

        /* Header dropdown */
        .header-dropdown-wrapper { position: relative; }
        .header-dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + 4px);
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius-sm);
            box-shadow: var(--shadow-lg);
            z-index: 200;
            min-width: 160px;
            overflow: hidden;
        }
        .header-dropdown-menu.open { display: block; }
        .header-dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1rem;
            font-size: 0.875rem;
            color: var(--gray-700);
            text-decoration: none;
            transition: var(--transition);
        }
        .header-dropdown-item:hover { background: var(--gray-50); }
        .header-dropdown-item.danger { color: var(--danger-color); }
        .header-dropdown-item.success { color: var(--success-color); }

        /* Profile header */
        .profile-header {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            padding: 2rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }
        .claim-avatar {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.75rem;
            flex-shrink: 0;
        }
        .profile-info h2 { color: var(--gray-900); margin-bottom: 0.35rem; font-size: 1.375rem; font-weight: 600; }
        .profile-subtitle { color: var(--gray-500); margin-bottom: 0.75rem; font-size: 0.875rem; }
        .profile-meta { display: flex; gap: 1.25rem; flex-wrap: wrap; }
        .meta-item { display: flex; align-items: center; gap: 0.4rem; color: var(--gray-600); font-size: 0.8rem; }
        .meta-item i { color: var(--gray-400); }

        /* Content grid */
        .content-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        /* Detail card */
        .detail-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            overflow: hidden;
        }
        .detail-card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--gray-100);
        }
        .detail-card-header h3 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
        }
        .detail-card-header h3 i { color: var(--primary-color); }
        .detail-card-body { padding: 1.5rem; }

        /* Summary rows */
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            font-size: 0.875rem;
            color: var(--gray-700);
        }
        .summary-divider { border-top: 1px solid var(--gray-200); margin: 0.75rem 0; }
        .summary-total { font-size: 1rem; color: var(--gray-900); }
        .summary-total strong { color: var(--success-color); font-size: 1.125rem; }

        /* Signatures */
        .signature-item { padding: 0.75rem 0; }
        .signature-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--gray-500); margin-bottom: 0.4rem; }
        .sig-status { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.875rem; font-weight: 500; }
        .sig-signed { color: var(--success-color); }
        .sig-unsigned { color: var(--danger-color); }
        .sig-date { font-size: 0.75rem; color: var(--gray-500); margin-top: 0.25rem; }
        .signature-divider { border-top: 1px solid var(--gray-100); margin: 0.75rem 0; }

        /* Rejection / payment notes */
        .rejection-note, .payment-note {
            padding: 0.75rem;
            border-radius: var(--border-radius-sm);
            font-size: 0.875rem;
        }
        .rejection-note { background: rgba(239,68,68,0.05); }
        .rejection-label { font-weight: 600; color: var(--danger-color); margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.4rem; }
        .payment-note { background: rgba(16,185,129,0.05); }
        .payment-label { font-weight: 600; color: var(--success-color); margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.4rem; }

        /* Certification */
        .certification-note {
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
            background: var(--gray-50);
            padding: 0.75rem;
            border-radius: var(--border-radius-sm);
            font-size: 0.8rem;
            color: var(--gray-600);
        }
        .certification-note i { color: var(--primary-color); margin-top: 0.1rem; flex-shrink: 0; }
        .certification-note p { margin: 0; line-height: 1.5; }

        /* Meal type badge */
        .meal-type-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.6rem;
            background: rgba(6,182,212,0.1);
            color: var(--info-color);
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        /* Table footer totals */
        .totals-row td {
            background: var(--gray-50);
            font-weight: 600;
            color: var(--gray-900);
            border-top: 2px solid var(--gray-200);
        }

        /* Table header with icon */
        .table-header h3 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .table-header h3 i { color: var(--primary-color); }

        /* Receipts grid */
        .receipts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            padding: 1.5rem;
        }
        .receipt-card {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius-sm);
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            text-align: center;
        }
        .receipt-icon { font-size: 2.5rem; color: var(--primary-color); }
        .receipt-icon .fa-file-pdf { color: var(--danger-color); }
        .receipt-name { font-size: 0.8rem; font-weight: 500; color: var(--gray-800); word-break: break-all; }
        .receipt-size { font-size: 0.75rem; color: var(--gray-500); }
        .receipt-actions { display: flex; gap: 0.5rem; margin-top: 0.25rem; }
        .action-btn-view  { background: rgba(59,130,246,0.1); color: var(--primary-color); }
        .action-btn-view:hover  { background: var(--primary-color); color: white; }
        .action-btn-download { background: rgba(16,185,129,0.1); color: var(--success-color); }
        .action-btn-download:hover { background: var(--success-color); color: white; }

        /* Empty state */
        .empty-state { text-align: center; padding: 2.5rem 2rem; }
        .empty-icon { font-size: 2.5rem; color: var(--gray-300); margin-bottom: 0.75rem; }
        .empty-state p { color: var(--gray-500); }

        /* Responsive */
        @media (max-width: 768px) {
            .profile-header { flex-direction: column; text-align: center; }
            .content-grid-2 { grid-template-columns: 1fr; }
            .profile-meta { justify-content: center; }
        }

        /* Print */
        @media print {
            .modern-sidebar, .dashboard-header, .header-right, .header-dropdown-wrapper { display: none !important; }
            .main-content { margin-left: 0 !important; }
            .dashboard-content { padding: 0 !important; }
            .table-card, .detail-card, .profile-header { box-shadow: none !important; border: 1px solid #ccc !important; }
        }
    </style>
</body>
</html>
