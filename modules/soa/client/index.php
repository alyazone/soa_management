<?php
ob_start();
$basePath = '../../../';
require_once $basePath . "config/database.php";

session_start();
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
            SELECT c.client_id, c.client_name, c.address, c.pic_name, c.pic_contact, c.pic_email,
                   COUNT(s.soa_id) as soa_count,
                   COALESCE(SUM(CASE WHEN s.status = 'Pending' OR s.status = 'Overdue' THEN s.total_amount ELSE 0 END), 0) as pending_amount
            FROM clients c
            LEFT JOIN client_soa s ON c.client_id = s.client_id
            GROUP BY c.client_id
            ORDER BY c.client_name
        ");
        $clients = $stmt->fetchAll();
    } catch(PDOException $e) {
        $db_error = "Database Error: " . $e->getMessage();
    }
}
?>

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
    <?php include_once $basePath . "includes/modern-sidebar.php"; ?>

    <div class="main-content">
        <header class="dashboard-header">
            <div class="header-content">
                <div class="header-left">
                    <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                    <div class="header-title">
                        <h1>Client SOA Management</h1>
                        <p>Select a client to manage their Statements of Account</p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="all_soas.php" class="export-btn info"><i class="fas fa-list-ul"></i> View All SOAs</a>
                    <a href="add.php" class="export-btn"><i class="fas fa-plus"></i> Create New SOA</a>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <?php if($access_denied): ?>
                <div class="access-denied-card">
                    <div class="access-denied-content">
                        <div class="access-denied-icon"><i class="fas fa-lock"></i></div>
                        <h2>Access Denied</h2>
                        <p>You do not have permission to access this page.</p>
                        <a href="<?php echo $basePath; ?>dashboard.php" class="btn-primary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                    </div>
                </div>
            <?php else: ?>
                <?php if(isset($db_error)): ?>
                    <div class="alert alert-error"><div class="alert-content"><i class="fas fa-exclamation-circle"></i><span><?php echo $db_error; ?></span></div></div>
                <?php endif; ?>

                <div class="table-card" data-aos="fade-up">
                    <div class="table-header">
                        <div class="table-title">
                            <h3>Client Directory</h3>
                            <p>List of all clients with their SOA summaries</p>
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Client Info</th>
                                    <th>Contact Person</th>
                                    <th>SOA Count</th>
                                    <th>Pending Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($clients)): ?>
                                    <?php foreach($clients as $client): ?>
                                    <tr>
                                        <td>
                                            <div class="client-info">
                                                <div class="client-avatar" style="background-color: #4a5568;"><i class="fas fa-user-tie"></i></div>
                                                <div class="client-details">
                                                    <div class="client-name"><?php echo htmlspecialchars($client['client_name']); ?></div>
                                                    <div class="client-address"><?php echo htmlspecialchars(substr($client['address'], 0, 40)) . (strlen($client['address']) > 40 ? '...' : ''); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="contact-person">
                                                <div class="person-name"><i class="fas fa-user"></i> <?php echo htmlspecialchars($client['pic_name']); ?></div>
                                                <div class="contact-item"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($client['pic_email']); ?></div>
                                            </div>
                                        </td>
                                        <td><span class="soa-count-badge"><?php echo $client['soa_count']; ?> SOAs</span></td>
                                        <td>
                                            <span class="amount-display <?php echo $client['pending_amount'] > 0 ? 'has-amount' : 'no-amount'; ?>">
                                                RM <?php echo number_format($client['pending_amount'], 2); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="client_soas.php?client_id=<?php echo $client['client_id']; ?>" class="action-btn action-btn-view" title="View SOAs"><i class="fas fa-file-invoice"></i></a>
                                                <a href="add.php?client_id=<?php echo $client['client_id']; ?>" class="action-btn action-btn-add" title="Add SOA"><i class="fas fa-plus"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center no-data"><div class="no-data-content"><h3>No Clients Found</h3></div></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
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
        .client-info{display:flex;align-items:center;gap:.75rem}.client-avatar{width:40px;height:40px;background:var(--gray-700);border-radius:8px;display:flex;align-items:center;justify-content:center;color:white;flex-shrink:0}.client-name{font-weight:600;color:var(--gray-900);font-size:.875rem}.client-address{font-size:.75rem;color:var(--gray-500)}.contact-person{display:flex;flex-direction:column;gap:.25rem}.contact-person .person-name,.contact-person .contact-item{display:flex;align-items:center;gap:.5rem;font-size:.875rem;color:var(--gray-700)}.contact-person .contact-item{font-size:.8rem;color:var(--gray-600)}.contact-person i{color:var(--gray-400);width:14px;text-align:center}.soa-count-badge{display:inline-flex;align-items:center;padding:.25rem .75rem;background:rgba(59,130,246,.1);color:var(--primary-color);border-radius:9999px;font-size:.75rem;font-weight:500}.amount-display{font-weight:600;font-size:.875rem}.amount-display.has-amount{color:var(--danger-color)}.amount-display.no-amount{color:var(--gray-400)}.action-buttons{display:flex;gap:.5rem}.action-btn{width:32px;height:32px;border:none;border-radius:var(--border-radius-sm);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:var(--transition);font-size:.875rem}.action-btn-view{background:rgba(59,130,246,.1);color:var(--primary-color)}.action-btn-view:hover{background:var(--primary-color);color:white}.action-btn-add{background:rgba(16,185,129,.1);color:var(--success-color)}.action-btn-add:hover{background:var(--success-color);color:white}.no-data{padding:3rem!important}.no-data-content{text-align:center}.no-data-content h3{color:var(--gray-700);margin-bottom:.5rem}@media (max-width:768px){.client-info{flex-direction:column;align-items:flex-start;gap:.5rem}.action-buttons{flex-direction:column}}
    </style>
</body>
</html>
<?php ob_end_flush(); ?>
