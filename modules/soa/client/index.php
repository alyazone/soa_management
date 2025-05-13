<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start();
// Set the base path for includes
$basePath = '../../../';

// Include header and sidebar
include_once $basePath . "includes/header.php";
include_once $basePath . "includes/sidebar.php";

// Check if the user has admin privileges
if($_SESSION['position'] != 'Admin'){
    echo '<div class="col-md-10 ml-sm-auto px-4"><div class="alert alert-danger mt-3">You do not have permission to access this page.</div></div>';
    include_once $basePath . "includes/footer.php";
    exit;
}

// Include database connection
require_once $basePath . "config/database.php";

// Fetch all clients
try {
    $stmt = $pdo->query("SELECT c.*, 
                         (SELECT COUNT(*) FROM client_soa WHERE client_id = c.client_id) as soa_count,
                         (SELECT IFNULL(SUM(total_amount), 0) FROM client_soa WHERE client_id = c.client_id AND status = 'Pending') as pending_amount
                         FROM clients c 
                         ORDER BY c.client_name");
    $clients = $stmt->fetchAll();
} catch(PDOException $e) {
    echo '<div class="alert alert-danger">Database Error: ' . $e->getMessage() . '</div>';
}
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Client SOA Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="add.php" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> Create New Client SOA
            </a>
        </div>
    </div>
    
    <?php if(isset($_GET["success"])): ?>
        <div class="alert alert-success">
            <?php 
            if($_GET["success"] == "1") {
                echo "Client SOA record has been deleted successfully.";
            } elseif($_GET["success"] == "2") {
                echo "Client SOA record has been added successfully.";
            } elseif($_GET["success"] == "3") {
                echo "Client SOA record has been updated successfully.";
            }
            ?>
        </div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Select a Client to Manage SOAs</h6>
            <div>
                <a href="all_soas.php" class="btn btn-sm btn-info">
                    <i class="fas fa-list"></i> View All SOAs
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Client Name</th>
                            <th>Contact Person</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>SOA Count</th>
                            <th>Pending Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(isset($clients) && !empty($clients)): ?>
                            <?php foreach($clients as $client): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($client['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($client['pic_name']); ?></td>
                                <td><?php echo htmlspecialchars($client['pic_email']); ?></td>
                                <td><?php echo htmlspecialchars($client['pic_contact']); ?></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo $client['soa_count']; ?> SOA(s)
                                    </span>
                                </td>
                                <td>
                                    <?php if($client['pending_amount'] > 0): ?>
                                        <span class="badge badge-warning">
                                            RM <?php echo number_format($client['pending_amount'], 2); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-success">RM 0.00</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="client_soas.php?client_id=<?php echo $client['client_id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-file-invoice"></i> View SOAs
                                    </a>
                                    <a href="add.php?client_id=<?php echo $client['client_id']; ?>" class="btn btn-success btn-sm">
                                        <i class="fas fa-plus"></i> Add SOA
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No clients found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once $basePath . "includes/footer.php";
ob_end_flush();
?>
