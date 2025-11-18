<?php
// Set the base path for includes
$basePath = '../../';

// Include header and sidebar
include_once $basePath . "includes/header.php";
include_once $basePath . "includes/sidebar.php";

// Include database connection
require_once $basePath . "config/database.php";

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
    $total_km = 0;
    $total_parking = 0;
    $total_toll = 0;
    $total_meal = 0;
    
    foreach($entries as $entry){
        $total_km += floatval($entry['miles_traveled']);
        $total_parking += floatval($entry['parking_fee']);
        $total_toll += floatval($entry['toll_fee']);
    }
    
    foreach($meal_entries as $meal){
        $total_meal += floatval($meal['amount']);
    }
    
} catch(PDOException $e) {
    die("ERROR: Could not fetch claim. " . $e->getMessage());
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
    if(empty($date)) {
        return 'Not available';
    }
    return date("d M Y", strtotime($date));
}

// Get months
$months = [
    'January', 'February', 'March', 'April', 'May', 'June', 
    'July', 'August', 'September', 'October', 'November', 'December'
];
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Reimbursement Claim Details</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <?php if($claim['status'] == 'Pending' && ($claim['staff_id'] == $_SESSION['staff_id'] || $_SESSION['position'] == 'Admin' || $_SESSION['position'] == 'Manager')): ?>
                <a href="edit.php?id=<?php echo $_GET['id']; ?>" class="btn btn-sm btn-primary mr-2">
                    <i class="fas fa-edit"></i> Edit Claim
                </a>
            <?php endif; ?>
            
            <?php if($_SESSION['position'] == 'Admin' || $_SESSION['position'] == 'Manager'): ?>
                <div class="dropdown mr-2">
                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="statusDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-cog"></i> Change Status
                    </button>
                    <div class="dropdown-menu" aria-labelledby="statusDropdown">
                        <a class="dropdown-item" href="update_status.php?id=<?php echo $_GET['id']; ?>&status=Approved">Approve</a>
                        <a class="dropdown-item" href="update_status.php?id=<?php echo $_GET['id']; ?>&status=Rejected">Reject</a>
                        <a class="dropdown-item" href="update_status.php?id=<?php echo $_GET['id']; ?>&status=Paid">Mark as Paid</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <button class="btn btn-sm btn-info mr-2 btn-print">
                <i class="fas fa-print"></i> Print
            </button>
            
            <a href="index.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Claim #<?php echo $_GET['id']; ?></h6>
            <span class="badge <?php echo getStatusBadgeClass($claim['status']); ?> p-2">
                <?php echo $claim['status']; ?>
            </span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="font-weight-bold">REIMBURSEMENT CLAIM FORM</h5>
                </div>
                <div class="col-md-6 text-right">
                    <p><strong>Submission Date:</strong> <?php echo isset($claim['created_at']) && !empty($claim['created_at']) ? formatDate($claim['created_at']) : 'Not available'; ?></p>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <p><strong>Employee Name:</strong> <?php echo htmlspecialchars($claim['full_name']); ?></p>
                    <p><strong>Staff Number:</strong> <?php echo htmlspecialchars($claim['employee_id']); ?></p>
                    <p><strong>Department:</strong> <?php echo htmlspecialchars($claim['department']); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Month:</strong> <?php echo htmlspecialchars($claim['claim_month']); ?></p>
                    <p><strong>Vehicle Type:</strong> <?php echo htmlspecialchars($claim['vehicle_type']); ?></p>
                </div>
            </div>
            
            <hr>
            
            <h5 class="font-weight-bold mt-4">Travel Details</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th>Date</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Purpose of Traveling</th>
                            <th>Parking (RM)</th>
                            <th>Toll (RM)</th>
                            <th>Miles Traveled (KM)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($entries as $entry): ?>
                        <tr>
                            <td><?php echo formatDate($entry['travel_date']); ?></td>
                            <td><?php echo htmlspecialchars($entry['travel_from']); ?></td>
                            <td><?php echo htmlspecialchars($entry['travel_to']); ?></td>
                            <td><?php echo htmlspecialchars($entry['purpose']); ?></td>
                            <td class="text-right"><?php echo number_format($entry['parking_fee'], 2); ?></td>
                            <td class="text-right"><?php echo number_format($entry['toll_fee'], 2); ?></td>
                            <td class="text-right"><?php echo number_format($entry['miles_traveled'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <h5 class="font-weight-bold mt-4">Meal Expenses</h5>
            <?php if(empty($meal_entries)): ?>
                <div class="alert alert-info">No meal expenses claimed.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="bg-success text-white">
                        <tr>
                            <th>Date</th>
                            <th>Meal Type</th>
                            <th>Description</th>
                            <th>Amount (RM)</th>
                            <th>Receipt Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($meal_entries as $meal): ?>
                        <tr>
                            <td><?php echo formatDate($meal['meal_date']); ?></td>
                            <td><?php echo htmlspecialchars($meal['meal_type']); ?></td>
                            <td><?php echo htmlspecialchars($meal['description']); ?></td>
                            <td class="text-right"><?php echo number_format($meal['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($meal['receipt_reference']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <div class="row mt-4">
                <div class="col-md-6 offset-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h6 class="m-0 font-weight-bold">Summary</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-8">Total Miles Traveled:</div>
                                <div class="col-4 text-right"><?php echo number_format($total_km, 2); ?> KM</div>
                            </div>
                            <div class="row">
                                <div class="col-8">Total multiply KM rate:</div>
                                <div class="col-4 text-right">RM <?php echo number_format($claim['total_km_amount'], 2); ?></div>
                            </div>
                            <div class="row">
                                <div class="col-8">Total Parking:</div>
                                <div class="col-4 text-right">RM <?php echo number_format($total_parking, 2); ?></div>
                            </div>
                            <div class="row">
                                <div class="col-8">Total Toll:</div>
                                <div class="col-4 text-right">RM <?php echo number_format($total_toll, 2); ?></div>
                            </div>
                            <div class="row">
                                <div class="col-8">Total Meal Expenses:</div>
                                <div class="col-4 text-right">RM <?php echo number_format($claim['total_meal_amount'] ?? $total_meal, 2); ?></div>
                            </div>
                            <hr>
                            <div class="row font-weight-bold">
                                <div class="col-8">Total Reimbursement Amount:</div>
                                <div class="col-4 text-right">RM <?php echo number_format($claim['amount'], 2); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="alert alert-secondary">
                        <p class="mb-0"><strong>Certification:</strong> I HEREBY CERTIFY that the reimbursement claimed on this form are proper and actual expenses incurred during this period and in accordance with the company's Reimbursement Policy.</p>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <p><strong>Employee Signature:</strong> 
                        <?php if($claim['employee_signature']): ?>
                            <span class="text-success"><i class="fas fa-check-circle"></i> Signed</span>
                        <?php else: ?>
                            <span class="text-danger"><i class="fas fa-times-circle"></i> Not Signed</span>
                        <?php endif; ?>
                    </p>
                    <p><strong>Signature Date:</strong> 
                        <?php if($claim['signature_date']): ?>
                            <?php echo formatDate($claim['signature_date']); ?>
                        <?php else: ?>
                            <span class="text-muted">Not signed yet</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <p><strong>Approval Signature:</strong> 
                        <?php if($claim['approval_signature']): ?>
                            <span class="text-success"><i class="fas fa-check-circle"></i> Signed by <?php echo htmlspecialchars($claim['approved_by']); ?></span>
                        <?php else: ?>
                            <span class="text-danger"><i class="fas fa-times-circle"></i> Not Approved</span>
                        <?php endif; ?>
                    </p>
                    <p><strong>Approval Date:</strong> 
                        <?php if($claim['approval_date']): ?>
                            <?php echo formatDate($claim['approval_date']); ?>
                        <?php else: ?>
                            <span class="text-muted">Not approved yet</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            
            <?php if($claim['status'] == 'Rejected' && !empty($claim['rejection_reason'])): ?>
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="alert alert-danger">
                        <h6 class="font-weight-bold">Rejection Reason:</h6>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($claim['rejection_reason'])); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if($claim['status'] == 'Paid' && !empty($claim['payment_details'])): ?>
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="alert alert-success">
                        <h6 class="font-weight-bold">Payment Details:</h6>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($claim['payment_details'])); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Receipt Files Section -->
    <?php
    // Fetch receipt files for this claim
    $claim_id = $_GET["id"];
    $receiptSql = "SELECT * FROM claim_receipts WHERE claim_id = :claim_id ORDER BY upload_date DESC";
    $receiptStmt = $pdo->prepare($receiptSql);
    $receiptStmt->bindParam(":claim_id", $claim_id, PDO::PARAM_INT);
    $receiptStmt->execute();
    $receipts = $receiptStmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <?php if (!empty($receipts)): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Uploaded Receipts</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($receipts as $receipt): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <?php if (strpos($receipt['file_type'], 'image') !== false): ?>
                                    <i class="fas fa-file-image fa-3x text-primary"></i>
                                <?php else: ?>
                                    <i class="fas fa-file-pdf fa-3x text-danger"></i>
                                <?php endif; ?>
                                
                                <h6 class="mt-2 text-truncate" title="<?php echo htmlspecialchars($receipt['original_file_name']); ?>">
                                    <?php echo htmlspecialchars($receipt['original_file_name']); ?>
                                </h6>
                                <p class="small text-muted mb-2">
                                    <?php echo round($receipt['file_size'] / 1024, 2); ?> KB
                                </p>
                                <a href="<?php echo $basePath; ?>uploads/receipts/<?php echo $receipt['file_name']; ?>" 
                                   class="btn btn-sm btn-primary" target="_blank">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="<?php echo $basePath; ?>uploads/receipts/<?php echo $receipt['file_name']; ?>" 
                                   class="btn btn-sm btn-secondary" download="<?php echo $receipt['original_file_name']; ?>">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    // Print functionality
    $(".btn-print").click(function() {
        window.print();
    });
});
</script>

<style>
@media print {
    .sidebar, .navbar, .btn-toolbar, footer {
        display: none !important;
    }
    
    .col-md-10 {
        width: 100% !important;
        max-width: 100% !important;
        flex: 0 0 100% !important;
        margin-left: 0 !important;
    }
    
    .card {
        border: none !important;
    }
    
    .card-header {
        background-color: #f8f9fa !important;
        color: #000 !important;
    }
    
    .bg-primary, .bg-success {
        background-color: #f8f9fa !important;
        color: #000 !important;
    }
    
    .text-white {
        color: #000 !important;
    }
}
</style>

<?php
// Include footer
include_once $basePath . "includes/footer.php";
?>
