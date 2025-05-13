<?php
// Set the base path for includes
$basePath = '../../../';

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

// Fetch Client SOA data
try {
    $stmt = $pdo->prepare("SELECT s.*, c.client_name, c.address as client_address, 
                          c.pic_name as client_pic, c.pic_contact as client_contact, 
                          c.pic_email as client_email, 
                          st.full_name as created_by_name
                          FROM client_soa s 
                          JOIN clients c ON s.client_id = c.client_id 
                          JOIN staff st ON s.created_by = st.staff_id
                          WHERE s.soa_id = :id");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    
    if($stmt->rowCount() != 1){
        header("location: index.php");
        exit();
    }
    
    $soa = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("ERROR: Could not fetch Client SOA. " . $e->getMessage());
}
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">View Client SOA</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="edit.php?id=<?php echo $_GET['id']; ?>" class="btn btn-sm btn-primary mr-2">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="generate_pdf.php?id=<?php echo $_GET['id']; ?>" class="btn btn-sm btn-success mr-2" target="_blank">
                <i class="fas fa-file-pdf"></i> Generate PDF
            </a>
            <a href="index.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Client SOA Details</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-info-circle mr-2"></i>Basic Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Account Number:</th>
                                    <td><?php echo htmlspecialchars($soa['account_number']); ?></td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo ($soa['status'] == 'Paid') ? 'success' : 
                                                (($soa['status'] == 'Overdue') ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo htmlspecialchars($soa['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Issue Date:</th>
                                    <td><?php echo date('d M Y', strtotime($soa['issue_date'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Due Date:</th>
                                    <td><?php echo date('d M Y', strtotime($soa['due_date'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Total Amount:</th>
                                    <td>RM <?php echo number_format($soa['total_amount'], 2); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-building mr-2"></i>Client Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Client Name:</th>
                                    <td><?php echo htmlspecialchars($soa['client_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Address:</th>
                                    <td><?php echo nl2br(htmlspecialchars($soa['client_address'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Contact Person:</th>
                                    <td><?php echo htmlspecialchars($soa['client_pic']); ?></td>
                                </tr>
                                <tr>
                                    <th>Contact Number:</th>
                                    <td><?php echo htmlspecialchars($soa['client_contact']); ?></td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td><?php echo htmlspecialchars($soa['client_email']); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-file-invoice-dollar mr-2"></i>Service Details
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6 class="font-weight-bold">Service Description:</h6>
                        <p><?php echo nl2br(htmlspecialchars($soa['service_description'])); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history mr-2"></i>Record Information
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="20%">Created By:</th>
                            <td><?php echo htmlspecialchars($soa['created_by_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Created At:</th>
                            <td><?php echo date('d M Y H:i:s', strtotime($soa['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>Last Updated:</th>
                            <td><?php echo date('d M Y H:i:s', strtotime($soa['updated_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once $basePath . "includes/footer.php";
?>
