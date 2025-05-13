<?php
// Set the base path for includes
$basePath = '../../../';

// Check if the user has admin privileges
if($_SESSION['position'] != 'Admin'){
    echo '<div class="col-md-10 ml-sm-auto px-4"><div class="alert alert-danger mt-3">You do not have permission to access this page.</div></div>';
    include_once $basePath . "includes/footer.php";
    exit;
}


// Include database connection
require_once $basePath . "config/database.php";

// Process delete operation
if(isset($_GET["id"]) && !empty($_GET["id"])){
    try {
        // Prepare a delete statement
        $sql = "DELETE FROM client_soa WHERE soa_id = :id";
        
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
                // Redirect with error
                header("location: index.php?error=1");
                exit();
            }
        }
    } catch(PDOException $e) {
        // Redirect with error
        header("location: index.php?error=2");
        exit();
    }
} else {
    // Redirect if no id parameter
    header("location: index.php");
    exit();
}
?>
