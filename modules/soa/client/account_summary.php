<?php
ob_start();
$basePath = '../../../';
require_once $basePath . "config/database.php";

session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['position'] != 'Admin'){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}

$client_id = $_GET['client_id'] ?? null;
if(!$client_id) { header("location: index.php"); exit; }

// Period filter defaults
$period_from = $_GET['from'] ?? date('Y-m-01', strtotime('-3 months'));
$period_to = $_GET['to'] ?? date('Y-m-d');

try {
    // Fetch client info
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = :id");
    $stmt->execute([':id' => $client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$client) { header("location: index.php"); exit; }

    // Fetch all SOAs for this client within the period
    $stmt = $pdo->prepare("
        SELECT s.*,
            (s.total_amount - s.paid_amount) as balance
        FROM client_soa s
        WHERE s.client_id = :client_id
        AND s.issue_date BETWEEN :from_date AND :to_date
        ORDER BY s.issue_date ASC
    ");
    $stmt->execute([':client_id' => $client_id, ':from_date' => $period_from, ':to_date' => $period_to]);
    $soas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch payments within period for this client's SOAs
    $stmt = $pdo->prepare("
        SELECT p.*, s.account_number, s.invoice_number
        FROM soa_payments p
        JOIN client_soa s ON p.soa_id = s.soa_id
        WHERE s.client_id = :client_id
        AND p.payment_date BETWEEN :from_date AND :to_date
        ORDER BY p.payment_date ASC
    ");
    $stmt->execute([':client_id' => $client_id, ':from_date' => $period_from, ':to_date' => $period_to]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate totals
    $total_invoiced = 0;
    $total_paid = 0;
    foreach($soas as $soa) {
        $total_invoiced += $soa['total_amount'];
        $total_paid += $soa['paid_amount'];
    }
    $total_balance = $total_invoiced - $total_paid;

    // Overall client totals (all time, not filtered)
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(total_amount), 0) as all_time_invoiced,
            COALESCE(SUM(paid_amount), 0) as all_time_paid,
            COALESCE(SUM(total_amount - paid_amount), 0) as all_time_balance,
            COUNT(*) as total_soas
        FROM client_soa WHERE client_id = :client_id
    ");
    $stmt->execute([':client_id' => $client_id]);
    $overall = $stmt->fetch(PDO::FETCH_ASSOC);

    // Build combined ledger (invoices + payments sorted by date)
    $ledger = [];
    foreach($soas as $soa) {
        $ledger[] = [
            'date' => $soa['issue_date'],
            'type' => 'invoice',
            'reference' => $soa['invoice_number'] ?: $soa['account_number'],
            'description' => $soa['service_description'],
            'amount' => $soa['total_amount'],
            'payment' => 0,
            'status' => $soa['status'],
            'soa_id' => $soa['soa_id']
        ];
    }
    foreach($payments as $p) {
        $ledger[] = [
            'date' => $p['payment_date'],
            'type' => 'payment',
            'reference' => $p['payment_reference'] ?: ('PMT-' . $p['payment_id']),
            'description' => 'Payment - ' . $p['payment_method'] . ' (' . ($p['invoice_number'] ?: $p['account_number']) . ')',
            'amount' => 0,
            'payment' => $p['payment_amount'],
            'status' => 'Payment',
            'soa_id' => null
        ];
    }
    // Sort by date
    usort($ledger, function($a, $b) {
        $cmp = strcmp($a['date'], $b['date']);
        if($cmp === 0) {
            // Invoices before payments on same date
            return ($a['type'] === 'invoice') ? -1 : 1;
        }
        return $cmp;
    });

    // Previous balance (before period)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_amount - paid_amount), 0) as prev_balance
        FROM client_soa
        WHERE client_id = :client_id AND issue_date < :from_date
    ");
    $stmt->execute([':client_id' => $client_id, ':from_date' => $period_from]);
    $prev_balance = $stmt->fetch(PDO::FETCH_ASSOC)['prev_balance'];

    // Get available years for this client
    $stmt = $pdo->prepare("SELECT DISTINCT YEAR(issue_date) as year FROM client_soa WHERE client_id = :id ORDER BY year DESC");
    $stmt->execute([':id' => $client_id]);
    $available_years = $stmt->fetchAll(PDO::FETCH_COLUMN);

} catch(PDOException $e) {
    die("ERROR: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Summary - <?php echo htmlspecialchars($client['client_name']); ?></title>
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
                        <h1>Account Summary</h1>
                        <p><?php echo htmlspecialchars($client['client_name']); ?></p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="generate_statement.php?client_id=<?php echo $client_id; ?>&from=<?php echo $period_from; ?>&to=<?php echo $period_to; ?>" target="_blank" class="export-btn success"><i class="fas fa-file-pdf"></i> Generate PDF Statement</a>
                    <a href="client_soas.php?client_id=<?php echo $client_id; ?>" class="export-btn secondary"><i class="fas fa-arrow-left"></i> Back to SOAs</a>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <!-- Period Filter -->
            <div class="filter-card" data-aos="fade-down">
                <form method="get" class="filter-form">
                    <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>Period From</label>
                            <input type="date" name="from" value="<?php echo htmlspecialchars($period_from); ?>" class="form-input">
                        </div>
                        <div class="filter-group">
                            <label>Period To</label>
                            <input type="date" name="to" value="<?php echo htmlspecialchars($period_to); ?>" class="form-input">
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Overall Account Stats -->
            <div class="stats-grid" data-aos="fade-up">
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="fas fa-file-invoice"></i></div>
                    <div class="stat-content">
                        <h3>RM <?php echo number_format($overall['all_time_invoiced'], 2); ?></h3>
                        <p>All-Time Invoiced</p>
                    </div>
                </div>
                <div class="stat-card success">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-content">
                        <h3>RM <?php echo number_format($overall['all_time_paid'], 2); ?></h3>
                        <p>All-Time Paid</p>
                    </div>
                </div>
                <div class="stat-card <?php echo $overall['all_time_balance'] > 0 ? 'danger' : 'success'; ?>">
                    <div class="stat-icon"><i class="fas fa-balance-scale"></i></div>
                    <div class="stat-content">
                        <h3>RM <?php echo number_format($overall['all_time_balance'], 2); ?></h3>
                        <p>Outstanding Balance</p>
                    </div>
                </div>
                <div class="stat-card secondary">
                    <div class="stat-icon"><i class="fas fa-hashtag"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $overall['total_soas']; ?></h3>
                        <p>Total Invoices</p>
                    </div>
                </div>
            </div>

            <!-- Period Summary -->
            <div class="summary-card" data-aos="fade-up" data-aos-delay="100">
                <div class="summary-header"><h3><i class="fas fa-calculator"></i> Period Summary (<?php echo date('M d, Y', strtotime($period_from)); ?> - <?php echo date('M d, Y', strtotime($period_to)); ?>)</h3></div>
                <div class="summary-body">
                    <div class="summary-row">
                        <span>Previous Balance</span>
                        <span class="summary-value">RM <?php echo number_format($prev_balance, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>New Charges</span>
                        <span class="summary-value">RM <?php echo number_format($total_invoiced, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Payments / Credits</span>
                        <span class="summary-value text-success">- RM <?php echo number_format($total_paid, 2); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Balance Due</span>
                        <span class="summary-value">RM <?php echo number_format($prev_balance + $total_invoiced - $total_paid, 2); ?></span>
                    </div>
                </div>
            </div>

            <!-- Ledger Table -->
            <div class="table-card" data-aos="fade-up" data-aos-delay="200">
                <div class="table-header">
                    <div class="table-title">
                        <h3><i class="fas fa-book"></i> Account Ledger</h3>
                        <p>All invoices and payments within the selected period</p>
                    </div>
                </div>
                <div class="table-container">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Description</th>
                                <th class="text-right">Charges (RM)</th>
                                <th class="text-right">Payments (RM)</th>
                                <th class="text-right">Running Balance (RM)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($prev_balance > 0): ?>
                            <tr class="row-balance-fwd">
                                <td><?php echo date('M d, Y', strtotime($period_from)); ?></td>
                                <td>-</td>
                                <td><em>Balance Brought Forward</em></td>
                                <td class="text-right">-</td>
                                <td class="text-right">-</td>
                                <td class="text-right"><strong>RM <?php echo number_format($prev_balance, 2); ?></strong></td>
                            </tr>
                            <?php endif; ?>
                            <?php
                            $running_balance = $prev_balance;
                            if(!empty($ledger)):
                                foreach($ledger as $entry):
                                    $running_balance += $entry['amount'] - $entry['payment'];
                            ?>
                            <tr class="<?php echo $entry['type'] === 'payment' ? 'row-payment' : 'row-invoice'; ?>">
                                <td><?php echo date('M d, Y', strtotime($entry['date'])); ?></td>
                                <td>
                                    <?php if($entry['soa_id']): ?>
                                        <a href="view.php?id=<?php echo $entry['soa_id']; ?>" class="ref-link"><?php echo htmlspecialchars($entry['reference']); ?></a>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($entry['reference']); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars(mb_strimwidth($entry['description'], 0, 60, '...')); ?></td>
                                <td class="text-right"><?php echo $entry['amount'] > 0 ? number_format($entry['amount'], 2) : '-'; ?></td>
                                <td class="text-right text-success"><?php echo $entry['payment'] > 0 ? number_format($entry['payment'], 2) : '-'; ?></td>
                                <td class="text-right"><strong>RM <?php echo number_format($running_balance, 2); ?></strong></td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="6" class="text-center no-data"><div class="no-data-content"><h3>No Records Found</h3><p>No invoices or payments within the selected period.</p></div></td></tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if(!empty($ledger)): ?>
                        <tfoot>
                            <tr class="totals-row">
                                <td colspan="3"><strong>Period Totals</strong></td>
                                <td class="text-right"><strong>RM <?php echo number_format($total_invoiced, 2); ?></strong></td>
                                <td class="text-right text-success"><strong>RM <?php echo number_format($total_paid, 2); ?></strong></td>
                                <td class="text-right"><strong>RM <?php echo number_format($running_balance, 2); ?></strong></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- Invoice Breakdown -->
            <div class="table-card" data-aos="fade-up" data-aos-delay="300">
                <div class="table-header">
                    <div class="table-title">
                        <h3><i class="fas fa-file-invoice-dollar"></i> Invoice Breakdown</h3>
                        <p>Individual invoice status within the period</p>
                    </div>
                </div>
                <div class="table-container">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Account #</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th class="text-right">Amount</th>
                                <th class="text-right">Paid</th>
                                <th class="text-right">Balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($soas as $soa): ?>
                            <tr>
                                <td><a href="view.php?id=<?php echo $soa['soa_id']; ?>" class="ref-link"><?php echo htmlspecialchars($soa['invoice_number'] ?: '-'); ?></a></td>
                                <td><span class="account-number"><?php echo htmlspecialchars($soa['account_number']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($soa['issue_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($soa['due_date'])); ?></td>
                                <td class="text-right">RM <?php echo number_format($soa['total_amount'], 2); ?></td>
                                <td class="text-right text-success">RM <?php echo number_format($soa['paid_amount'], 2); ?></td>
                                <td class="text-right <?php echo $soa['balance'] > 0 ? 'text-danger' : ''; ?>">RM <?php echo number_format($soa['balance'], 2); ?></td>
                                <td><span class="status-badge status-<?php echo strtolower($soa['status']); ?>"><?php echo htmlspecialchars($soa['status']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
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
    </script>
    <style>
        .filter-card{background:white;border-radius:var(--border-radius);box-shadow:var(--shadow);border:1px solid var(--gray-200);padding:1.5rem;margin-bottom:1.5rem}.filter-form .filter-row{display:flex;align-items:flex-end;gap:1.5rem;flex-wrap:wrap}.filter-group{display:flex;flex-direction:column;gap:.5rem}.filter-group label{font-size:.875rem;font-weight:500;color:var(--gray-700)}.filter-actions{display:flex;align-items:flex-end}.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;margin-bottom:2rem}.stat-card{background:white;border-radius:var(--border-radius);padding:1.5rem;box-shadow:var(--shadow);border:1px solid var(--gray-200);display:flex;align-items:center;gap:1rem}.stat-card .stat-icon{width:48px;height:48px;border-radius:var(--border-radius-sm);display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:white}.stat-card.primary .stat-icon{background:var(--primary-color)}.stat-card.success .stat-icon{background:var(--success-color)}.stat-card.danger .stat-icon{background:var(--danger-color)}.stat-card.secondary .stat-icon{background:var(--gray-500)}.stat-card .stat-content h3{font-size:1.5rem;font-weight:700;color:var(--gray-900)}.stat-card .stat-content p{font-size:.875rem;color:var(--gray-600);margin:0}.summary-card{background:white;border-radius:var(--border-radius);box-shadow:var(--shadow);border:1px solid var(--gray-200);margin-bottom:2rem;overflow:hidden}.summary-header{padding:1.5rem;border-bottom:1px solid var(--gray-200);background:var(--gray-50)}.summary-header h3{display:flex;align-items:center;gap:.5rem;color:var(--gray-900);font-size:1.125rem;font-weight:600;margin:0}.summary-body{padding:1.5rem}.summary-row{display:flex;justify-content:space-between;align-items:center;padding:.75rem 0;border-bottom:1px solid var(--gray-100)}.summary-row:last-child{border-bottom:none}.summary-row.total{border-top:2px solid var(--gray-300);margin-top:.5rem;padding-top:1rem;font-weight:700;font-size:1.125rem}.summary-value{font-weight:600;font-size:.875rem}.text-success{color:var(--success-color)!important}.text-danger{color:var(--danger-color)!important}.text-right{text-align:right}.ref-link{color:var(--primary-color);text-decoration:none;font-weight:600}.ref-link:hover{text-decoration:underline}.row-payment{background:rgba(16,185,129,.03)}.row-payment td{color:var(--gray-700)}.row-balance-fwd{background:var(--gray-50)}.row-balance-fwd td{font-style:italic;color:var(--gray-600)}.totals-row{background:var(--gray-50);border-top:2px solid var(--gray-300)}.account-number{font-family:monospace;font-weight:600;color:var(--primary-color)}.status-badge{display:inline-flex;align-items:center;padding:.375rem .75rem;border-radius:9999px;font-size:.75rem;font-weight:600;text-transform:uppercase}.status-badge.status-paid{background:rgba(16,185,129,.1);color:var(--success-color)}.status-badge.status-pending{background:rgba(245,158,11,.1);color:var(--warning-color)}.status-badge.status-overdue{background:rgba(239,68,68,.1);color:var(--danger-color)}.status-badge.status-closed{background:rgba(107,114,128,.1);color:var(--gray-600)}.form-input{padding:.75rem;border:1px solid var(--gray-300);border-radius:var(--border-radius-sm);font-size:.875rem;transition:var(--transition);background:white}.form-input:focus{outline:0;border-color:var(--primary-color);box-shadow:0 0 0 3px rgba(59,130,246,.1)}.btn{display:inline-flex;align-items:center;gap:.5rem;padding:.75rem 1.5rem;border:none;border-radius:var(--border-radius-sm);font-size:.875rem;font-weight:500;text-decoration:none;cursor:pointer;transition:var(--transition)}.btn-primary{background:var(--primary-color);color:white}.btn-primary:hover{background:var(--primary-dark)}.no-data{padding:3rem!important}.no-data-content{text-align:center}.no-data-content h3{color:var(--gray-700);margin-bottom:.5rem}.no-data-content p{color:var(--gray-500)}@media(max-width:768px){.filter-form .filter-row{flex-direction:column;align-items:stretch}.stats-grid{grid-template-columns:repeat(auto-fit,minmax(150px,1fr))}}
    </style>
</body>
</html>
<?php ob_end_flush(); ?>
