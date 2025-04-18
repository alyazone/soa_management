<?php
// Set the base path for includes
$basePath = '';

// Include header and sidebar
include_once "includes/header.php";
include_once "includes/sidebar.php";

// Include database connection
require_once "config/database.php";

// Get counts for dashboard
try {
    // Count clients
    $stmt = $pdo->query("SELECT COUNT(*) as client_count FROM clients");
    $client_count = $stmt->fetch()['client_count'];
    
    // Count suppliers
    $stmt = $pdo->query("SELECT COUNT(*) as supplier_count FROM suppliers");
    $supplier_count = $stmt->fetch()['supplier_count'];
    
    // Count staff
    $stmt = $pdo->query("SELECT COUNT(*) as staff_count FROM staff");
    $staff_count = $stmt->fetch()['staff_count'];
    
    // Count SOAs
    $stmt = $pdo->query("SELECT COUNT(*) as soa_count FROM soa");
    $soa_count = $stmt->fetch()['soa_count'];
    
    // Count pending claims
    $stmt = $pdo->query("SELECT COUNT(*) as pending_claims FROM claims WHERE status = 'Pending'");
    $pending_claims = $stmt->fetch()['pending_claims'];
    
    // Get recent SOAs
    $stmt = $pdo->query("SELECT s.soa_id, s.account_number, c.client_name, s.issue_date, s.balance_amount, s.status 
                         FROM soa s 
                         JOIN clients c ON s.client_id = c.client_id 
                         ORDER BY s.issue_date DESC LIMIT 5");
    $recent_soas = $stmt->fetchAll();
    
    // Get recent claims
    $stmt = $pdo->query("SELECT cl.claim_id, s.full_name, cl.amount, cl.status, cl.submitted_date 
                         FROM claims cl 
                         JOIN staff s ON cl.staff_id = s.staff_id 
                         ORDER BY cl.submitted_date DESC LIMIT 5");
    $recent_claims = $stmt->fetchAll();
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group mr-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Clients</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $client_count; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-building fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Suppliers</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $supplier_count; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-truck fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                SOA Records</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $soa_count; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-invoice-dollar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent SOAs -->
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent SOAs</h6>
                    <a href="modules/soa/index.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Account #</th>
                                    <th>Client</th>
                                    <th>Issue Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_soas as $soa): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($soa['account_number']); ?></td>
                                    <td><?php echo htmlspecialchars($soa['client_name']); ?></td>
                                    <td><?php echo htmlspecialchars($soa['issue_date']); ?></td>
                                    <td>RM <?php echo number_format($soa['balance_amount'], 2); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo ($soa['status'] == 'Paid') ? 'success' : 
                                                (($soa['status'] == 'Overdue') ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo htmlspecialchars($soa['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($recent_soas)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No SOA records found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Claims -->
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Claims</h6>
                    <a href="modules/claims/index.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Staff</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_claims as $claim): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($claim['full_name']); ?></td>
                                    <td>RM <?php echo number_format($claim['amount'], 2); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($claim['submitted_date'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo ($claim['status'] == 'Approved') ? 'success' : 
                                                (($claim['status'] == 'Rejected') ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo htmlspecialchars($claim['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($recent_claims)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No claim records found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once "includes/footer.php";
?>
