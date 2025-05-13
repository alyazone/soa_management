<?php
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

// Check existence of id parameter before processing further
if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    // Get URL parameter
    $soa_id = trim($_GET["id"]);
    
    // Prepare a select statement
    $sql = "SELECT s.*, sup.supplier_name, sup.pic_name, sup.pic_contact, sup.pic_email, sup.address, 
            st.full_name as created_by_name
            FROM supplier_soa s 
            JOIN suppliers sup ON s.supplier_id = sup.supplier_id 
            JOIN staff st ON s.created_by = st.staff_id
            WHERE s.soa_id = :soa_id";
    
    if($stmt = $pdo->prepare($sql)){
        // Bind variables to the prepared statement as parameters
        $stmt->bindParam(":soa_id", $param_soa_id);
        
        // Set parameters
        $param_soa_id = $soa_id;
        
        // Attempt to execute the prepared statement
        if($stmt->execute()){
            if($stmt->rowCount() == 1){
                /* Fetch result row as an associative array. Since the result set
                contains only one row, we don't need to use while loop */
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Retrieve individual field value
                $invoice_number = $row["invoice_number"];
                $supplier_name = $row["supplier_name"];
                $issue_date = $row["issue_date"];
                $payment_due_date = $row["payment_due_date"];
                $purchase_description = $row["purchase_description"];
                $amount = $row["amount"];
                $payment_status = $row["payment_status"];
                $payment_method = $row["payment_method"];
                $created_at = $row["created_at"];
                $created_by_name = $row["created_by_name"];
                $pic_name = $row["pic_name"];
                $pic_contact = $row["pic_contact"];
                $pic_email = $row["pic_email"];
                $address = $row["address"];
            } else{
                // URL doesn't contain valid id parameter. Redirect to error page
                header("location: error.php");
                exit();
            }
            
        } else{
            echo "Oops! Something went wrong. Please try again later.";
        }
    }
    
    // Close statement
    unset($stmt);
    
    // Close connection
    unset($pdo);
} else{
    // URL doesn't contain id parameter. Redirect to error page
    header("location: error.php");
    exit();
}
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">View Supplier SOA</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="index.php" class="btn btn-sm btn-secondary mr-2">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <a href="edit.php?id=<?php echo $soa_id; ?>" class="btn btn-sm btn-primary mr-2">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="generate_pdf.php?id=<?php echo $soa_id; ?>" class="btn btn-sm btn-success" target="_blank">
                <i class="fas fa-file-pdf"></i> Generate PDF
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">SOA Details</h6>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th>Invoice Number</th>
                            <td><?php echo htmlspecialchars($invoice_number); ?></td>
                        </tr>
                        <tr>
                            <th>Issue Date</th>
                            <td><?php echo htmlspecialchars($issue_date); ?></td>
                        </tr>
                        <tr>
                            <th>Payment Due Date</th>
                            <td><?php echo htmlspecialchars($payment_due_date); ?></td>
                        </tr>
                        <tr>
                            <th>Amount</th>
                            <td>RM <?php echo number_format($amount, 2); ?></td>
                        </tr>
                        <tr>
                            <th>Payment Status</th>
                            <td>
                                <span class="badge badge-<?php 
                                    echo ($payment_status == 'Paid') ? 'success' : 
                                        (($payment_status == 'Overdue') ? 'danger' : 'warning'); 
                                ?>">
                                    <?php echo htmlspecialchars($payment_status); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Payment Method</th>
                            <td><?php echo htmlspecialchars($payment_method); ?></td>
                        </tr>
                        <tr>
                            <th>Created At</th>
                            <td><?php echo htmlspecialchars($created_at); ?></td>
                        </tr>
                        <tr>
                            <th>Created By</th>
                            <td><?php echo htmlspecialchars($created_by_name); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Supplier Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th>Supplier Name</th>
                            <td><?php echo htmlspecialchars($supplier_name); ?></td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td><?php echo htmlspecialchars($address); ?></td>
                        </tr>
                        <tr>
                            <th>Contact Person</th>
                            <td><?php echo htmlspecialchars($pic_name); ?></td>
                        </tr>
                        <tr>
                            <th>Contact Number</th>
                            <td><?php echo htmlspecialchars($pic_contact); ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo htmlspecialchars($pic_email); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Purchase Description</h6>
        </div>
        <div class="card-body">
            <p><?php echo nl2br(htmlspecialchars($purchase_description)); ?></p>
        </div>
    </div>
</div>

<?php
// Include footer
include_once $basePath . "includes/footer.php";
ob_end_flush();
?>
