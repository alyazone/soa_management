<?php
ob_start();
// Set the base path for includes
$basePath = '../../';

// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}

// Include database connection
require_once $basePath . "config/database.php";

// Check if id parameter is set
if(empty($_GET["id"])){
    header("location: index.php");
    exit();
}

try {
    // Check if client has associated SOAs
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM soa WHERE client_id = :id");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch();
    
    if($result['count'] > 0) {
        // Client has associated SOAs, cannot delete
        $_SESSION['error_message'] = "Cannot delete client because they have associated SOA records.";
        header("location: index.php");
        exit();
    } else {
        // Prepare a delete statement
        $sql = "DELETE FROM clients WHERE client_id = :id";
        
        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
            
            // Set parameters
            $param_id = trim($_GET["id"]);
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Records deleted successfully. Redirect to landing page
                $_SESSION['success_message'] = "Client has been deleted successfully.";
                header("location: index.php");
                exit();
            } else{
                $_SESSION['error_message'] = "Oops! Something went wrong. Please try again later.";
                header("location: index.php");
                exit();
            }
        }
    }
} catch(PDOException $e) {
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header("location: index.php");
    exit();
}
ob_end_flush();
?>
