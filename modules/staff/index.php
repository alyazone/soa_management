<?php
// Set the base path for includes
$basePath = '../../';

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

// Process delete operation
if(isset($_GET["action"]) && $_GET["action"] == "delete" && isset($_GET["id"]) && !empty($_GET["id"])){
    try {
        // Check if trying to delete own account
        if($_GET["id"] == $_SESSION["staff_id"]){
            $delete_err = "You cannot delete your own account.";
        } else {
            // Check if staff has associated claims or documents
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM claims WHERE staff_id = :id");
            $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch();
            
            if($result['count'] > 0) {
                $delete_err = "Cannot delete staff because they have associated claims.";
            } else {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM documents WHERE uploaded_by = :id");
                $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
                $stmt->execute();
                $result = $stmt->fetch();
                
                if($result['count'] > 0) {
                    $delete_err = "Cannot delete staff because they have associated documents.";
                } else {
                    // Prepare a delete statement
                    $sql = "DELETE FROM staff WHERE staff_id = :id";
                    
                    if($stmt = $pdo->prepare($sql)){
                        // Bind variables to the prepared statement as parameters
                        $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
                        
                        // Set parameters
                        $param_id = trim($_GET["id"]);
                        
                        // Attempt to execute the prepared statement
                        if($stmt->execute()){
                            // Records deleted successfully. Redirect to landing page
                            header("location: index.php?success=1");
                            exit();
                        } else{
                            $delete_err = "Oops! Something went wrong. Please try again later.";
                        }
                    }
                }
            }
        }
    } catch(PDOException $e) {
        $delete_err = "Error: " . $e->getMessage();
    }
}

// Fetch all staff members
try {
    $stmt = $pdo->query("SELECT * FROM staff ORDER BY full_name");
    $staff_members = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Staff Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo $basePath; ?>modules/auth/register.php" class="btn btn-sm btn-primary">
                <i class="fas fa-user-plus"></i> Add New Staff
            </a>
        </div>
    </div>
    
    <?php if(isset($_GET["success"]) && $_GET["success"] == "1"): ?>
        <div class="alert alert-success">
            Staff record has been updated successfully.
        </div>
    <?php endif; ?>
    
    <?php if(isset($delete_err)): ?>
        <div class="alert alert-danger">
            <?php echo $delete_err; ?>
        </div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Staff List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($staff_members as $staff): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($staff['staff_id']); ?></td>
                            <td><?php echo htmlspecialchars($staff['username']); ?></td>
                            <td><?php echo htmlspecialchars($staff['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($staff['email']); ?></td>
                            <td><?php echo htmlspecialchars($staff['department']); ?></td>
                            <td><?php echo htmlspecialchars($staff['position']); ?></td>
                            <td>
                                <a href="view.php?id=<?php echo $staff['staff_id']; ?>" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit.php?id=<?php echo $staff['staff_id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if($staff['staff_id'] != $_SESSION['staff_id']): ?>
                                <a href="index.php?action=delete&id=<?php echo $staff['staff_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this staff member?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($staff_members)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No staff records found</td>
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
