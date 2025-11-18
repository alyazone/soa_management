<?php
ob_start();
$basePath = '../../';

session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['position'] != 'Admin'){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}

require_once $basePath . "config/database.php";

if(empty($_GET["id"])){
    header("location: index.php");
    exit();
}

try {
    // Check if supplier has associated SOAs
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM soa WHERE supplier_id = :id");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch();
    
    if($result['count'] > 0) {
        $_SESSION['error_message'] = "Cannot delete supplier because they have associated SOA records.";
        header("location: index.php");
        exit();
    } else {
        // Prepare a delete statement
        $sql = "DELETE FROM suppliers WHERE supplier_id = :id";
        
        if($stmt = $pdo->prepare($sql)){
            $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
            $param_id = trim($_GET["id"]);
            
            if($stmt->execute()){
                $_SESSION['success_message'] = "Supplier has been deleted successfully.";
                header("location: index.php?success=deleted");
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
<?php