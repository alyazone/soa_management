<?php
// Set the base path for includes
$basePath = '../../';
ob_start();

// Include header and sidebar
include_once $basePath . "includes/header.php";
include_once $basePath . "includes/sidebar.php";

// Include database connection
require_once $basePath . "config/database.php";

// Determine if user is admin/manager
$isAdmin = ($_SESSION['position'] == 'Admin' || $_SESSION['position'] == 'Manager');

// Fetch claims data
try {
    if($isAdmin) {
        // Admin/Manager can see all claims
        $stmt = $pdo->prepare("SELECT c.*, s.full_name 
                              FROM claims c 
                              JOIN staff s ON c.staff_id = s.staff_id 
                              ORDER BY c.submitted_date DESC");
    } else {
        // Regular staff can only see their own claims
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

// Get status badge color
function getStatusBadgeClass($status) {
    switch($status) {
        case 'Approved':
            return 'badge-success';
        case 'Rejected':
            return 'badge-danger';
        case 'Pending':
            return 'badge-warning';
        case 'Paid':
            return 'badge-primary';
        default:
            return 'badge-secondary';
    }
}

// Format date
function formatDate($date) {
    return date("d M Y", strtotime($date));
}
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Mileage Reimbursement Claims</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="add.php" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> New Claim
            </a>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Claims List</h6>
        </div>
        <div class="card-body">
            <?php if(empty($claims)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No claims found. Click "New Claim" to submit a mileage reimbursement claim.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <?php if($isAdmin): ?>
                                    <th>Employee</th>
                                <?php endif; ?>
                                <th>Month</th>
                                <th>Vehicle Type</th>
                                <th>Amount (RM)</th>
                                <th>Status</th>
                                <th>Submission Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($claims as $claim): ?>
                                <tr>
                                    <td><?php echo $claim['claim_id']; ?></td>
                                    <?php if($isAdmin): ?>
                                        <td><?php echo htmlspecialchars($claim['full_name']); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo htmlspecialchars($claim['claim_month']); ?></td>
                                    <td><?php echo htmlspecialchars($claim['vehicle_type']); ?></td>
                                    <td class="text-right"><?php echo number_format($claim['amount'], 2); ?></td>
                                    <td>
                                        <span class="badge <?php echo getStatusBadgeClass($claim['status']); ?>">
                                            <?php echo $claim['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($claim['submitted_date']); ?></td>
                                    <td>
                                        <a href="view.php?id=<?php echo $claim['claim_id']; ?>" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if($claim['status'] == 'Pending' && ($claim['staff_id'] == $_SESSION['staff_id'] || $isAdmin)): ?>
                                            <a href="edit.php?id=<?php echo $claim['claim_id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if($isAdmin): ?>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-secondary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <i class="fas fa-cog"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item" href="update_status.php?id=<?php echo $claim['claim_id']; ?>&status=Approved">Approve</a>
                                                    <a class="dropdown-item" href="update_status.php?id=<?php echo $claim['claim_id']; ?>&status=Rejected">Reject</a>
                                                    <a class="dropdown-item" href="update_status.php?id=<?php echo $claim['claim_id']; ?>&status=Paid">Mark as Paid</a>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
include_once $basePath . "includes/footer.php";
ob_end_flush();
?>
