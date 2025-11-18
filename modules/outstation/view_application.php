<?php
ob_start();
session_start();
//$basePath = '../../../';
require_once $_SERVER['DOCUMENT_ROOT'] . '/soa_management/config/database.php';


if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}

if($_SESSION['position'] != 'Admin'){
    $access_denied = true;
} else {
    $access_denied = false;
}

if(!$access_denied) {
    try {
        $stmt = $pdo->query("
            SELECT oa.*, 
            s.full_name as approver_name
            FROM outstation_applications oa
            LEFT JOIN staff s ON oa.approved_by = s.staff_id
            WHERE oa.staff_id = ?
            ORDER BY oa.created_at DESC
        ");
        $clients = $stmt->fetchAll();
    } catch(PDOException $e) {
        $db_error = "Database Error: " . $e->getMessage();
    }
}
?>

<style>
.applications-container {
    max-width: 1400px;
    margin: 20px auto;
    padding: 0 20px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.page-header h2 {
    color: #2c3e50;
    margin: 0;
}

.btn-new-application {
    background: #4e73df;
    color: white;
    padding: 12px 25px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-new-application:hover {
    background: #2e59d9;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(78, 115, 223, 0.3);
    color: white;
}

.stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-left: 4px solid #4e73df;
}

.stat-card.pending {
    border-left-color: #f6c23e;
}

.stat-card.approved {
    border-left-color: #1cc88a;
}

.stat-card.rejected {
    border-left-color: #e74a3b;
}

.stat-card.claimable {
    border-left-color: #36b9cc;
}

.stat-card h3 {
    font-size: 14px;
    color: #858796;
    margin: 0 0 10px 0;
    text-transform: uppercase;
}

.stat-card .stat-value {
    font-size: 32px;
    font-weight: 700;
    color: #2c3e50;
}

.applications-table-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.table-header {
    padding: 20px;
    background: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
}

.table-header h3 {
    margin: 0;
    color: #2c3e50;
}

.applications-table {
    width: 100%;
    border-collapse: collapse;
}

.applications-table thead {
    background: #4e73df;
    color: white;
}

.applications-table thead th {
    padding: 15px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
}

.applications-table tbody td {
    padding: 15px;
    border-bottom: 1px solid #e3e6f0;
    font-size: 14px;
    color: #5a5c69;
}

.applications-table tbody tr:hover {
    background: #f8f9fc;
}

.status-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.pending {
    background: #fff3cd;
    color: #856404;
}

.status-badge.approved {
    background: #d4edda;
    color: #155724;
}

.status-badge.rejected {
    background: #f8d7da;
    color: #721c24;
}

.status-badge.completed {
    background: #d1ecf1;
    color: #0c5460;
}

.status-badge.cancelled {
    background: #e2e3e5;
    color: #383d41;
}

.claimable-indicator {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.claimable-indicator.yes {
    color: #1cc88a;
    font-weight: 600;
}

.claimable-indicator.no {
    color: #858796;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-action {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.btn-view {
    background: #4e73df;
    color: white;
}

.btn-view:hover {
    background: #2e59d9;
    color: white;
}

.btn-edit {
    background: #f6c23e;
    color: #5a5c69;
}

.btn-edit:hover {
    background: #f4b619;
}

.btn-claim {
    background: #1cc88a;
    color: white;
}

.btn-claim:hover {
    background: #17a673;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #858796;
}

.empty-state-icon {
    font-size: 64px;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #5a5c69;
    margin-bottom: 10px;
}

.empty-state p {
    color: #858796;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .applications-table {
        font-size: 12px;
    }
    
    .applications-table thead th,
    .applications-table tbody td {
        padding: 10px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Client SOA Management - SOA Management System</title>
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/modern-dashboard.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>
    <body class="bg-gray-50">
        <?require_once $_SERVER['DOCUMENT_ROOT'] . '/soa_management/includes/modern-sidebar.php'; ?>

        <div class="applications-container">
            <div class="page-header">
                <h2>My Outstation Applications</h2>
                <a href="application_form.php" class="btn-new-application">
                    <span>➕</span> New Application
                </a>
            </div>

            <?php
            // Calculate statistics
            $total = count($applications);
            $pending = count(array_filter($applications, fn($app) => $app['status'] === 'Pending'));
            $approved = count(array_filter($applications, fn($app) => $app['status'] === 'Approved'));
            $rejected = count(array_filter($applications, fn($app) => $app['status'] === 'Rejected'));
            $claimable = count(array_filter($applications, fn($app) => $app['is_claimable'] == 1 && $app['status'] === 'Approved'));
            ?>

            <div class="stats-cards">
                <div class="stat-card">
                    <h3>Total Applications</h3>
                    <div class="stat-value"><?php echo $total; ?></div>
                </div>
                <div class="stat-card pending">
                    <h3>Pending</h3>
                    <div class="stat-value"><?php echo $pending; ?></div>
                </div>
                <div class="stat-card approved">
                    <h3>Approved</h3>
                    <div class="stat-value"><?php echo $approved; ?></div>
                </div>
                <div class="stat-card claimable">
                    <h3>Claimable</h3>
                    <div class="stat-value"><?php echo $claimable; ?></div>
                </div>
            </div>

            <div class="applications-table-container">
                <div class="table-header">
                    <h3>Application History</h3>
                </div>
                
                <?php if (empty($applications)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📋</div>
                        <h3>No Applications Yet</h3>
                        <p>You haven't submitted any outstation leave applications.</p>
                        <a href="application_form.php" class="btn-new-application">Create Your First Application</a>
                    </div>
                <?php else: ?>
                    <table class="applications-table">
                        <thead>
                            <tr>
                                <th>Application No.</th>
                                <th>Destination</th>
                                <th>Departure</th>
                                <th>Return</th>
                                <th>Nights</th>
                                <th>Claimable</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($app['application_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($app['destination']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($app['departure_date'])); ?></td>
                                    <td><?php echo date('d M Y', strtotime($app['return_date'])); ?></td>
                                    <td><?php echo $app['total_nights']; ?></td>
                                    <td>
                                        <?php if ($app['is_claimable']): ?>
                                            <span class="claimable-indicator yes">✓ Yes</span>
                                        <?php else: ?>
                                            <span class="claimable-indicator no">✗ No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($app['status']); ?>">
                                            <?php echo $app['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($app['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="view_details.php?id=<?php echo $app['application_id']; ?>" class="btn-action btn-view">View</a>
                                            
                                            <?php if ($app['status'] === 'Pending'): ?>
                                                <a href="modify_application.php?id=<?php echo $app['application_id']; ?>" class="btn-action btn-edit">Edit</a>
                                            <?php endif; ?>
                                            
                                            <?php if ($app['status'] === 'Approved' && $app['is_claimable']): ?>
                                                <a href="claim_form.php?id=<?php echo $app['application_id']; ?>" class="btn-action btn-claim">Claim</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
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
                .client-info{display:flex;align-items:center;gap:.75rem}.client-avatar{width:40px;height:40px;background:var(--gray-700);border-radius:8px;display:flex;align-items:center;justify-content:center;color:white;flex-shrink:0}.client-name{font-weight:600;color:var(--gray-900);font-size:.875rem}.client-address{font-size:.75rem;color:var(--gray-500)}.contact-person{display:flex;flex-direction:column;gap:.25rem}.contact-person .person-name,.contact-person .contact-item{display:flex;align-items:center;gap:.5rem;font-size:.875rem;color:var(--gray-700)}.contact-person .contact-item{font-size:.8rem;color:var(--gray-600)}.contact-person i{color:var(--gray-400);width:14px;text-align:center}.soa-count-badge{display:inline-flex;align-items:center;padding:.25rem .75rem;background:rgba(59,130,246,.1);color:var(--primary-color);border-radius:9999px;font-size:.75rem;font-weight:500}.amount-display{font-weight:600;font-size:.875rem}.amount-display.has-amount{color:var(--danger-color)}.amount-display.no-amount{color:var(--gray-400)}.action-buttons{display:flex;gap:.5rem}.action-btn{width:32px;height:32px;border:none;border-radius:var(--border-radius-sm);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:var(--transition);font-size:.875rem}.action-btn-view{background:rgba(59,130,246,.1);color:var(--primary-color)}.action-btn-view:hover{background:var(--primary-color);color:white}.action-btn-add{background:rgba(16,185,129,.1);color:var(--success-color)}.action-btn-add:hover{background:var(--success-color);color:white}.no-data{padding:3rem!important}.no-data-content{text-align:center}.no-data-content h3{color:var(--gray-700);margin-bottom:.5rem}@media (max-width:768px){.client-info{flex-direction:column;align-items:flex-start;gap:.5rem}.action-buttons{flex-direction:column}}
            </style>
        </div>
    </body>
</html>
<?php ob_end_flush(); ?>

<?php include '../includes/footer.php'; ?>
