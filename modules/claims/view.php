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

// Fetch claim data with staff details
try {
    $stmt = $pdo->prepare("SELECT c.*, s.full_name as staff_name, s.email as staff_email, s.department as staff_department,
                           p.full_name as processor_name, p.email as processor_email
                           FROM claims c 
                           JOIN staff s ON c.staff_id = s.staff_id 
                           LEFT JOIN staff p ON c.processed_by = p.staff_id 
                           WHERE c.claim_id = :id");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    
    if($stmt->rowCount() != 1){
        header("location: index.php");
        exit();
    }
    
    $claim = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if user has permission to view this claim
    if($claim['staff_id'] != $_SESSION['staff_id'] && $_SESSION['position'] != 'Admin' && $_SESSION['position'] != 'Manager'){
        echo '<div class="col-md-10 ml-sm-auto px-4"><div class="alert alert-danger mt-3">You do not have permission to view this claim.</div></div>';
        include_once $basePath . "includes/footer.php";
        exit;
    }
    
} catch(PDOException $e) {
    die("ERROR: Could not fetch claim. " . $e->getMessage());
}

// Fetch documents related to this claim
try {
    $stmt = $pdo->prepare("SELECT * FROM documents 
                           WHERE reference_type = 'Staff' AND reference_id = :staff_id AND document_type = 'Claim' 
                           ORDER BY upload_date DESC");
    $stmt->bindParam(":staff_id", $claim['staff_id'], PDO::PARAM_INT);
    $stmt->execute();
    $documents = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Claim Details</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <?php if($claim['status'] == 'Pending' && ($claim['staff_id'] == $_SESSION['staff_id'] || $_SESSION['position'] == 'Admin' || $_SESSION['position'] == 'Manager')): ?>
                <a href="edit.php?id=<?php echo $claim['claim_id']; ?>" class="btn btn-sm btn-primary mr-2">
                    <i class="fas fa-edit"></i> Edit
                </a>
            <?php endif; ?>
            
            <?php if($_SESSION['position'] == 'Admin' || $_SESSION['position'] == 'Manager'): ?>
                <?php if($claim['status'] == 'Pending'): ?>
                    <a href="process.php?id=<?php echo $claim['claim_id']; ?>" class="btn btn-sm btn-success mr-2">
                        <i class="fas fa-check"></i> Process
                    </a>
                <?php endif; ?>
            <?php endif; ?>
            
            <a href="index.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Claim Information</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="font-weight-bold">Claim #<?php echo htmlspecialchars($claim['claim_id']); ?></h5>
                            <p>
                                <strong>Submitted By:</strong> <?php echo htmlspecialchars($claim['staff_name']); ?><br>
                                <strong>Department:</strong> <?php echo htmlspecialchars($claim['staff_department']); ?><br>
                                <strong>Email:</strong> <?php echo htmlspecialchars($claim['staff_email']); ?><br>
                                <strong>Submitted Date:</strong> <?php echo date('Y-m-d H:i', strtotime($claim['submitted_date'])); ?>
                            </p>
                        </div>
                        <div class="col-md-6 text-right">
                            <h5 class="font-weight-bold">Status: 
                                <span class="badge badge-<?php 
                                    echo ($claim['status'] == 'Approved') ? 'success' : 
                                        (($claim['status'] == 'Rejected') ? 'danger' : 'warning'); 
                                ?>">
                                    <?php echo htmlspecialchars($claim['status']); ?>
                                </span>
                            </h5>
                            <p>
                                <strong>Amount:</strong> RM <?php echo number_format($claim['amount'], 2); ?><br>
                                <?php if($claim['status'] != 'Pending'): ?>
                                    <strong>Processed By:</strong> <?php echo htmlspecialchars($claim['processor_name']); ?><br>
                                    <strong>Processed Date:</strong> <?php echo date('Y-m-d H:i', strtotime($claim['processed_date'])); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5 class="font-weight-bold">Description</h5>
                            <div class="p-3 bg-light rounded">
                                <?php echo nl2br(htmlspecialchars($claim['description'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Supporting Documents</h6>
                    <a href="<?php echo $basePath; ?>modules/documents/upload.php?reference_type=Staff&reference_id=<?php echo $claim['staff_id']; ?>&document_type=Claim" class="btn btn-sm btn-primary">
                        <i class="fas fa-upload"></i> Upload
                    </a>
                </div>
                <div class="card-body">
                    <?php if(!empty($documents)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Upload Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($documents as $document): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($document['file_name']); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($document['upload_date'])); ?></td>
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
                        <p>No supporting documents found for this claim.</p>
                        <p>Click the "Upload" button to add supporting documents such as receipts or invoices.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Actions</h6>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php if($claim['status'] == 'Pending' && ($claim['staff_id'] == $_SESSION['staff_id'] || $_SESSION['position'] == 'Admin' || $_SESSION['position'] == 'Manager')): ?>
                            <a href="edit.php?id=<?php echo $claim['claim_id']; ?>" class="list-group-item list-group-item-action">
                                <i class="fas fa-edit mr-2"></i> Edit Claim
                            </a>
                        <?php endif; ?>
                        
                        <a href="<?php echo $basePath; ?>modules/documents/upload.php?reference_type=Staff&reference_id=<?php echo $claim['staff_id']; ?>&document_type=Claim" class="list-group-item list-group-item-action">
                            <i class="fas fa-upload mr-2"></i> Upload Document
                        </a>
                        
                        <?php if($_SESSION['position'] == 'Admin' || $_SESSION['position'] == 'Manager'): ?>
                            <?php if($claim['status'] == 'Pending'): ?>
                                <a href="process.php?id=<?php echo $claim['claim_id']; ?>" class="list-group-item list-group-item-action">
                                    <i class="fas fa-check mr-2"></i> Process Claim
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if($claim['staff_id'] == $_SESSION['staff_id'] || $_SESSION['position'] == 'Admin'): ?>
                            <a href="index.php?action=delete&id=<?php echo $claim['claim_id']; ?>" class="list-group-item list-group-item-action text-danger" onclick="return confirm('Are you sure you want to delete this claim?');">
                                <i class="fas fa-trash mr-2"></i> Delete Claim
                            </a>
                        <?php endif; ?>
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
