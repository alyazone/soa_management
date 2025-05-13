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

// Process delete operation after confirmation
if(isset($_POST["soa_id"]) && !empty($_POST["soa_id"])){
    // Prepare a delete statement
    $sql = "DELETE FROM supplier_soa WHERE soa_id = :soa_id";
    
    if($stmt = $pdo->prepare($sql)){
        // Bind variables to the prepared statement as parameters
        $stmt->bindParam(":soa_id", $param_soa_id);
        
        // Set parameters
        $param_soa_id = trim($_POST["soa_id"]);
        
        // Attempt to execute the prepared statement
        if($stmt->execute()){
            // Records deleted successfully. Redirect to landing page
            header("location: index.php?success=1");
            exit();
        } else{
            echo "Oops! Something went wrong. Please try again later.";
        }
    }
     
    // Close statement
    unset($stmt);
    
    // Close connection
    unset($pdo);
} else{
    // Check existence of id parameter
    if(empty(trim($_GET["id"]))){
        // URL doesn't contain id parameter. Redirect to error page
        header("location: error.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Record</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .wrapper{
            width: 600px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <h2 class="mt-5 mb-3">Delete Supplier SOA</h2>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="alert alert-danger">
                            <input type="hidden" name="soa_id" value="<?php echo trim($_GET["id"]); ?>"/>
                            <p>Are you sure you want to delete this supplier SOA record?</p>
                            <p>
                                <input type="submit" value="Yes" class="btn btn-danger">
                                <a href="index.php" class="btn btn-secondary">No</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>        
        </div>
    </div>
</body>
</html>
