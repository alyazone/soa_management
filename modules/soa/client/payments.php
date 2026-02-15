<?php
ob_start();
$basePath = '../../../';
require_once $basePath . "config/database.php";

session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['position'] != 'Admin'){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}

$soa_id = $_GET['id'] ?? null;
if(!$soa_id) { header("location: index.php"); exit; }

// Handle delete payment
if(isset($_GET['action']) && $_GET['action'] == 'delete_payment' && isset($_GET['payment_id'])){
    try {
        $pdo->beginTransaction();

        // Get payment amount before deleting
        $stmt = $pdo->prepare("SELECT payment_amount FROM soa_payments WHERE payment_id = :pid AND soa_id = :sid");
        $stmt->execute([':pid' => $_GET['payment_id'], ':sid' => $soa_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if($payment){
            // Delete the payment
            $stmt = $pdo->prepare("DELETE FROM soa_payments WHERE payment_id = :pid AND soa_id = :sid");
            $stmt->execute([':pid' => $_GET['payment_id'], ':sid' => $soa_id]);

            // Update paid_amount on client_soa
            $stmt = $pdo->prepare("UPDATE client_soa SET paid_amount = paid_amount - :amount WHERE soa_id = :sid");
            $stmt->execute([':amount' => $payment['payment_amount'], ':sid' => $soa_id]);

            // Recheck status - if balance > 0, set back to Pending
            $stmt = $pdo->prepare("SELECT total_amount, paid_amount, due_date FROM client_soa WHERE soa_id = :sid");
            $stmt->execute([':sid' => $soa_id]);
            $soa_check = $stmt->fetch(PDO::FETCH_ASSOC);
            if($soa_check){
                $remaining = $soa_check['total_amount'] - $soa_check['paid_amount'];
                if($remaining > 0){
                    $new_status = (strtotime($soa_check['due_date']) < time()) ? 'Overdue' : 'Pending';
                    $stmt = $pdo->prepare("UPDATE client_soa SET status = :status WHERE soa_id = :sid AND status = 'Paid'");
                    $stmt->execute([':status' => $new_status, ':sid' => $soa_id]);
                }
            }
        }

        $pdo->commit();
        header("location: payments.php?id=" . $soa_id . "&success=deleted");
        exit();
    } catch(PDOException $e) {
        $pdo->rollBack();
        $db_error = "Failed to delete payment: " . $e->getMessage();
    }
}

// Fetch SOA and payment data
try {
    $stmt = $pdo->prepare("
        SELECT s.*, c.client_name
        FROM client_soa s
        JOIN clients c ON s.client_id = c.client_id
        WHERE s.soa_id = :id
    ");
    $stmt->bindParam(':id', $soa_id, PDO::PARAM_INT);
    $stmt->execute();
    $soa = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$soa) { header("location: index.php"); exit; }

    $balance = $soa['total_amount'] - $soa['paid_amount'];

    // Fetch all payments for this SOA
    $stmt = $pdo->prepare("
        SELECT p.*, st.full_name as recorded_by_name
        FROM soa_payments p
        LEFT JOIN staff st ON p.recorded_by = st.staff_id
        WHERE p.soa_id = :id
        ORDER BY p.payment_date DESC, p.created_at DESC
    ");
    $stmt->bindParam(':id', $soa_id, PDO::PARAM_INT);
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("ERROR: Could not fetch data. " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History - SOA Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/modern-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include_once $basePath . "includes/modern-sidebar.php"; ?>

    <div class="main-content">
        <header class="dashboard-header">
            <div class="header-content">
                <div class="header-left">
                    <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                    <div class="header-title">
                        <h1>Payment History</h1>
                        <p>Account #<?php echo htmlspecialchars($soa['account_number']); ?> - <?php echo htmlspecialchars($soa['client_name']); ?></p>
                    </div>
                </div>
                <div class="header-right">
                    <?php if($balance > 0 && $soa['status'] != 'Closed'): ?>
                    <a href="record_payment.php?id=<?php echo $soa_id; ?>" class="export-btn success"><i class="fas fa-plus"></i> Record Payment</a>
                    <?php endif; ?>
                    <a href="view.php?id=<?php echo $soa_id; ?>" class="export-btn secondary"><i class="fas fa-arrow-left"></i> Back to SOA</a>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success" data-aos="fade-down">
                    <div class="alert-content"><i class="fas fa-check-circle"></i>
                        <span><?php echo ($_GET['success'] == 'deleted') ? 'Payment record deleted successfully.' : 'Operation completed.'; ?></span>
                    </div>
                    <button class="alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>
            <?php if(isset($db_error)): ?>
                <div class="alert alert-error" data-aos="fade-down">
                    <div class="alert-content"><i class="fas fa-exclamation-circle"></i><span><?php echo htmlspecialchars($db_error); ?></span></div>
                </div>
            <?php endif; ?>

            <div class="stats-grid" data-aos="fade-up">
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    <div class="stat-content">
                        <h3>RM <?php echo number_format($soa['total_amount'], 2); ?></h3>
                        <p>Total Invoice</p>
                    </div>
                </div>
                <div class="stat-card success">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-content">
                        <h3>RM <?php echo number_format($soa['paid_amount'], 2); ?></h3>
                        <p>Total Paid</p>
                    </div>
                </div>
                <div class="stat-card <?php echo $balance > 0 ? 'danger' : 'success'; ?>">
                    <div class="stat-icon"><i class="fas fa-balance-scale"></i></div>
                    <div class="stat-content">
                        <h3>RM <?php echo number_format($balance, 2); ?></h3>
                        <p>Outstanding Balance</p>
                    </div>
                </div>
                <div class="stat-card secondary">
                    <div class="stat-icon"><i class="fas fa-receipt"></i></div>
                    <div class="stat-content">
                        <h3><?php echo count($payments); ?></h3>
                        <p>Total Payments</p>
                    </div>
                </div>
            </div>

            <div class="table-card" data-aos="fade-up" data-aos-delay="200">
                <div class="table-header">
                    <div class="table-title">
                        <h3><i class="fas fa-history"></i> Payment Records</h3>
                        <p>All payments recorded for this invoice</p>
                    </div>
                </div>
                <div class="table-container">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Payment Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Recorded By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($payments)): ?>
                                <?php $running_paid = 0; foreach($payments as $i => $payment): $running_paid += $payment['payment_amount']; ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                    <td><span class="amount-display">RM <?php echo number_format($payment['payment_amount'], 2); ?></span></td>
                                    <td><span class="method-badge"><?php echo htmlspecialchars($payment['payment_method']); ?></span></td>
                                    <td><?php echo htmlspecialchars($payment['payment_reference'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($payment['recorded_by_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if(!empty($payment['notes'])): ?>
                                            <button class="action-btn action-btn-view" title="<?php echo htmlspecialchars($payment['notes']); ?>"><i class="fas fa-sticky-note"></i></button>
                                            <?php endif; ?>
                                            <a href="javascript:void(0);" onclick="confirmDeletePayment(<?php echo $payment['payment_id']; ?>)" class="action-btn action-btn-delete" title="Delete"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center no-data"><div class="no-data-content"><i class="fas fa-money-bill-wave"></i><h3>No Payments Recorded</h3><p>No payment records found for this invoice.</p></div></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="<?php echo $basePath; ?>assets/js/modern-dashboard.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({ duration: 800, easing: 'ease-in-out', once: true });
            initializeDashboard();
        });

        function confirmDeletePayment(paymentId) {
            if(confirm('Are you sure you want to delete this payment record? The SOA balance will be recalculated.')) {
                window.location.href = 'payments.php?id=<?php echo $soa_id; ?>&action=delete_payment&payment_id=' + paymentId;
            }
        }
    </script>
    <style>
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;margin-bottom:2rem}.stat-card{background:white;border-radius:var(--border-radius);padding:1.5rem;box-shadow:var(--shadow);border:1px solid var(--gray-200);display:flex;align-items:center;gap:1rem}.stat-card .stat-icon{width:48px;height:48px;border-radius:var(--border-radius-sm);display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:white}.stat-card.primary .stat-icon{background:var(--primary-color)}.stat-card.success .stat-icon{background:var(--success-color)}.stat-card.warning .stat-icon{background:var(--warning-color)}.stat-card.danger .stat-icon{background:var(--danger-color)}.stat-card.secondary .stat-icon{background:var(--gray-500)}.stat-card .stat-content h3{font-size:1.5rem;font-weight:700;color:var(--gray-900)}.stat-card .stat-content p{font-size:.875rem;color:var(--gray-600);margin:0}.method-badge{display:inline-flex;align-items:center;padding:.375rem .75rem;background:var(--gray-100);color:var(--gray-700);border-radius:9999px;font-size:.75rem;font-weight:500}.amount-display{font-weight:600;color:var(--success-color)}.action-buttons{display:flex;gap:.5rem}.action-btn{width:32px;height:32px;border:none;border-radius:var(--border-radius-sm);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:var(--transition);font-size:.875rem}.action-btn-view{background:rgba(59,130,246,.1);color:var(--primary-color)}.action-btn-delete{background:rgba(239,68,68,.1);color:var(--danger-color)}.action-btn-delete:hover{background:var(--danger-color);color:white}.no-data{padding:3rem!important}.no-data-content{text-align:center}.no-data-content i{font-size:3rem;color:var(--gray-300);margin-bottom:1rem}.no-data-content h3{color:var(--gray-700);margin-bottom:.5rem}.no-data-content p{color:var(--gray-500)}.alert{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.5rem;border-radius:var(--border-radius);margin-bottom:1.5rem;border:1px solid}.alert-success{background:rgba(16,185,129,.1);border-color:var(--success-color);color:var(--success-color)}.alert-error{background:rgba(239,68,68,.1);border-color:var(--danger-color);color:var(--danger-color)}.alert-content{display:flex;align-items:center;gap:.75rem}.alert-close{background:0 0;border:none;color:inherit;cursor:pointer;padding:.25rem;border-radius:var(--border-radius-sm);transition:var(--transition)}.alert-close:hover{background:rgba(0,0,0,.1)}@media(max-width:768px){.stats-grid{grid-template-columns:repeat(auto-fit,minmax(150px,1fr))}}
    </style>
</body>
</html>
<?php ob_end_flush(); ?>
