<?php
// Set the base path for includes
$basePath = '../../';

// Include header and sidebar
include_once $basePath . "includes/header.php";
include_once $basePath . "includes/sidebar.php";

// Check if the user has admin privileges or is viewing their own profile
if($_SESSION['position'] != 'Admin' && $_SESSION['staff_id'] != $_GET['id']){
    echo '<div class="col-md-10 ml-sm-auto px-4"><div class="alert alert-danger mt-3">You do not have permission to access this page.</div></div>';
    include_once $basePath . "includes/footer.php";
    exit;
}

// Include database connection
require_once $basePath . "config/database.php";

// Check if id parameter is set
if(empty($_GET["id"])){
    header("location: index.php");
    exit();
}

// Fetch staff data
try {
    $stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = :id");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    
    if($stmt->rowCount() != 1){
        header("location: index.php");
        exit();
    }
    
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("ERROR: Could not fetch staff. " . $e->getMessage());
}

// Fetch staff claims
try {
    $stmt = $pdo->prepare("SELECT * FROM claims WHERE staff_id = :id ORDER BY submitted_date DESC");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    $claims = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Fetch staff documents
try {
    $stmt = $pdo->prepare("SELECT * FROM documents WHERE uploaded_by = :id ORDER BY upload_date DESC");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    $documents = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Staff Profile</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="edit.php?id=<?php echo $staff['staff_id']; ?>" class="btn btn-sm btn-primary mr-2">
                <i class="fas fa-edit"></i> Edit Profile
            </a>
            <a href="index.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Staff Information</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr>
                                <th>Staff ID</th>
                                <td><?php echo htmlspecialchars($staff['staff_id']); ?></td>
                            </tr>
                            <tr>
                                <th>Username</th>
                                <td><?php echo htmlspecialchars($staff['username']); ?></td>
                            </tr>
                            <tr>
                                <th>Full Name</th>
                                <td><?php echo htmlspecialchars($staff['full_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td><?php echo htmlspecialchars($staff['email']); ?></td>
                            </tr>
                            <tr>
                                <th>Department</th>
                                <td><?php echo htmlspecialchars($staff['department']); ?></td>
                            </tr>
                            <tr>
                                <th>Position</th>
                                <td><?php echo htmlspecialchars($staff['position']); ?></td>
                            </tr>
                            <tr>
                                <th>Created At</th>
                                <td><?php echo date('Y-m-d H:i', strtotime($staff['created_at'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Claims History</h6>
                </div>
                <div class="card-body">
                    <?php if(!empty($claims)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($claims as $claim): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d', strtotime($claim['submitted_date'])); ?></td>
                                        <td>RM <?php echo number_format($claim['amount'], 2); ?></td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo ($claim['status'] == 'Approved') ? 'success' : 
                                                    (($claim['status'] == 'Rejected') ? 'danger' : 'warning'); 
                                            ?>">
                                                <?php echo htmlspecialchars($claim['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No claims found for this staff member.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Documents</h6>
                </div>
                <div class="card-body">
                    <?php if(!empty($documents)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>File Name</th>
                                        <th>Upload Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach(array_slice($documents, 0, 5) as $document): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($document['document_type']); ?></td>
                                        <td>
                                            <a href="<?php echo $basePath . $document['file_path']; ?>" target="_blank">
                                                <?php echo htmlspecialchars($document['file_name']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo date('Y-m-d', strtotime($document['upload_date'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php if(count($documents) > 5): ?>
                                <div class="text-center mt-3">
                                    <a href="<?php echo $basePath; ?>modules/documents/index.php" class="btn btn-sm btn-primary">
                                        View All Documents
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p>No documents uploaded by this staff member.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once $basePath . "includes/footer.php";
?>
