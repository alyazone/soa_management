<?php
// Set the base path for includes
$basePath = '../../';

// Include header and sidebar
include_once $basePath . "includes/header.php";
include_once $basePath . "includes/sidebar.php";

// Include database connection
require_once $basePath . "config/database.php";

// Process delete operation
if(isset($_GET["action"]) && $_GET["action"] == "delete" && isset($_GET["id"]) && !empty($_GET["id"])){
    try {
        // Get file path before deleting record
        $stmt = $pdo->prepare("SELECT file_path FROM documents WHERE document_id = :id");
        $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
        $stmt->execute();
        
        if($stmt->rowCount() == 1){
            $document = $stmt->fetch();
            $file_path = $basePath . $document['file_path'];
            
            // Prepare a delete statement
            $sql = "DELETE FROM documents WHERE document_id = :id";
            
            if($stmt = $pdo->prepare($sql)){
                // Bind variables to the prepared statement as parameters
                $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
                
                // Set parameters
                $param_id = trim($_GET["id"]);
                
                // Attempt to execute the prepared statement
                if($stmt->execute()){
                    // Delete the file if it exists
                    if(file_exists($file_path)){
                        unlink($file_path);
                    }
                    
                    // Records deleted successfully. Redirect to landing page
                    header("location: index.php?success=1");
                    exit();
                } else{
                    $delete_err = "Oops! Something went wrong. Please try again later.";
                }
            }
        } else {
            $delete_err = "Document not found.";
        }
    } catch(PDOException $e) {
        $delete_err = "Error: " . $e->getMessage();
    }
}

// Fetch all documents with reference details
try {
    $stmt = $pdo->query("SELECT d.*, s.full_name as staff_name,
                         CASE 
                            WHEN d.reference_type = 'Client' THEN (SELECT client_name FROM clients WHERE client_id = d.reference_id)
                            WHEN d.reference_type = 'Supplier' THEN (SELECT supplier_name FROM suppliers WHERE supplier_id = d.reference_id)
                            WHEN d.reference_type = 'Staff' THEN (SELECT full_name FROM staff WHERE staff_id = d.reference_id)
                            WHEN d.reference_type = 'SOA' THEN (SELECT account_number FROM soa WHERE soa_id = d.reference_id)
                            ELSE 'Unknown'
                         END as reference_name
                         FROM documents d
                         JOIN staff s ON d.uploaded_by = s.staff_id
                         ORDER BY d.upload_date DESC");
    $documents = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Document Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="upload.php" class="btn btn-sm btn-primary">
                <i class="fas fa-upload"></i> Upload New Document
            </a>
        </div>
    </div>
    
    <?php if(isset($_GET["success"]) && $_GET["success"] == "1"): ?>
        <div class="alert alert-success">
            Document has been deleted successfully.
        </div>
    <?php endif; ?>
    
    <?php if(isset($delete_err)): ?>
        <div class="alert alert-danger">
            <?php echo $delete_err; ?>
        </div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Document List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Reference</th>
                            <th>File Name</th>
                            <th>Upload Date</th>
                            <th>Uploaded By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($documents as $document): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($document['document_id']); ?></td>
                            <td><?php echo htmlspecialchars($document['document_type']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($document['reference_type']); ?>: 
                                <?php echo htmlspecialchars($document['reference_name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($document['file_name']); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($document['upload_date'])); ?></td>
                            <td><?php echo htmlspecialchars($document['staff_name']); ?></td>
                            <td>
                                <a href="view.php?id=<?php echo $document['document_id']; ?>" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?php echo $basePath . $document['file_path']; ?>" class="btn btn-success btn-sm" download>
                                    <i class="fas fa-download"></i>
                                </a>
                                <a href="index.php?action=delete&id=<?php echo $document['document_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this document?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($documents)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No documents found</td>
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
?>
