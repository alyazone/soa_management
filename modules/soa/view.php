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

// Fetch SOA data with client and supplier details
try {
    $stmt = $pdo->prepare("SELECT s.*, c.client_name, c.address as client_address, c.pic_name as client_pic, 
                           c.pic_contact as client_contact, c.pic_email as client_email,
                           sup.supplier_name, sup.address as supplier_address, sup.pic_name as supplier_pic,
                           sup.pic_contact as supplier_contact, sup.pic_email as supplier_email,
                           st.full_name as created_by_name
                           FROM soa s 
                           JOIN clients c ON s.client_id = c.client_id 
                           JOIN suppliers sup ON s.supplier_id = sup.supplier_id 
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
    die("ERROR: Could not fetch SOA. " . $e->getMessage());
}

// Fetch documents related to this SOA
try {
    $stmt = $pdo->prepare("SELECT * FROM documents 
                           WHERE reference_type = 'SOA' AND reference_id = :id 
                           ORDER BY upload_date DESC");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    $documents = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">SOA Details</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="edit.php?id=<?php echo $soa['soa_id']; ?>" class="btn btn-sm btn-primary mr-2">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="generate_pdf.php?id=<?php echo $soa['soa_id']; ?>" class="btn btn-sm btn-success mr-2" target="_blank">
                <i class="fas fa-file-pdf"></i> Generate PDF
            </a>
            <a href="index.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">SOA Information</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="font-weight-bold">Account Number: <?php echo htmlspecialchars($soa['account_number']); ?></h5>
                            <p>
                                <strong>Terms:</strong> <?php echo htmlspecialchars($soa['terms']); ?><br>
                                <strong>Purchase Date:</strong> <?php echo htmlspecialchars($soa['purchase_date']); ?><br>
                                <strong>Issue Date:</strong> <?php echo htmlspecialchars($soa['issue_date']); ?><br>
                                <strong>PO Number:</strong> <?php echo htmlspecialchars($soa['po_number']); ?><br>
                                <strong>Invoice Number:</strong> <?php echo htmlspecialchars($soa['invoice_number']); ?>
                            </p>
                        </div>
                        <div class="col-md-6 text-right">
                            <h5 class="font-weight-bold">Status: 
                                <span class="badge badge-<?php 
                                    echo ($soa['status'] == 'Paid') ? 'success' : 
                                        (($soa['status'] == 'Overdue') ? 'danger' : 'warning'); 
                                ?>">
                                    <?php echo htmlspecialchars($soa['status']); ?>
                                </span>
                            </h5>
                            <p>
                                <strong>Balance Amount:</strong> RM <?php echo number_format($soa['balance_amount'], 2); ?><br>
                                <strong>Created By:</strong> <?php echo htmlspecialchars($soa['created_by_name']); ?><br>
                                <strong>Created At:</strong> <?php echo date('Y-m-d H:i', strtotime($soa['created_at'])); ?><br>
                                <strong>Last Updated:</strong> <?php echo date('Y-m-d H:i', strtotime($soa['updated_at'])); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="font-weight-bold">Client Information</h5>
                            <p>
                                <strong>Name:</strong> <?php echo htmlspecialchars($soa['client_name']); ?><br>
                                <strong>Address:</strong> <?php echo nl2br(htmlspecialchars($soa['client_address'])); ?><br>
                                <strong>Contact Person:</strong> <?php echo htmlspecialchars($soa['client_pic']); ?><br>
                                <strong>Contact:</strong> <?php echo htmlspecialchars($soa['client_contact']); ?><br>
                                <strong>Email:</strong> <?php echo htmlspecialchars($soa['client_email']); ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h5 class="font-weight-bold">Supplier Information</h5>
                            <p>
                                <strong>Name:</strong> <?php echo htmlspecialchars($soa['supplier_name']); ?><br>
                                <strong>Address:</strong> <?php echo nl2br(htmlspecialchars($soa['supplier_address'])); ?><br>
                                <strong>Contact Person:</strong> <?php echo htmlspecialchars($soa['supplier_pic']); ?><br>
                                <strong>Contact:</strong> <?php echo htmlspecialchars($soa['supplier_contact']); ?><br>
                                <strong>Email:</strong> <?php echo htmlspecialchars($soa['supplier_email']); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5 class="font-weight-bold">Description</h5>
                            <p><?php echo nl2br(htmlspecialchars($soa['description'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Related Documents</h6>
                    <a href="<?php echo $basePath; ?>modules/documents/upload.php?reference_type=SOA&reference_id=<?php echo $soa['soa_id']; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-upload"></i> Upload
                    </a>
                </div>
                <div class="card-body">
                    <?php if(!empty($documents)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>File Name</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($documents as $document): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($document['document_type']); ?></td>
                                        <td><?php echo htmlspecialchars($document['file_name']); ?></td>
                                        <td>
                                            <a href="<?php echo $basePath . $document['file_path']; ?>" class="btn btn-info btn-sm" target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo $basePath . $document['file_path']; ?>" class="btn btn-success btn-sm" download>
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No documents found for this SOA.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Actions</h6>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="edit.php?id=<?php echo $soa['soa_id']; ?>" class="list-group-item list-group-item-action">
                            <i class="fas fa-edit mr-2"></i> Edit SOA
                        </a>
                        <a href="generate_pdf.php?id=<?php echo $soa['soa_id']; ?>" class="list-group-item list-group-item-action" target="_blank">
                            <i class="fas fa-file-pdf mr-2"></i> Generate PDF
                        </a>
                        <a href="<?php echo $basePath; ?>modules/documents/upload.php?reference_type=SOA&reference_id=<?php echo $soa['soa_id']; ?>" class="list-group-item list-group-item-action">
                            <i class="fas fa-upload mr-2"></i> Upload Document
                        </a>
                        <a href="index.php?action=delete&id=<?php echo $soa['soa_id']; ?>" class="list-group-item list-group-item-action text-danger" onclick="return confirm('Are you sure you want to delete this SOA?');">
                            <i class="fas fa-trash mr-2"></i> Delete SOA
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once $basePath . "includes/footer.php";
?>
