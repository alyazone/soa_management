<?php
$basePath = '../../';
require_once $basePath . "config/database.php";

session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['position'] != 'Admin'){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}

$experience_id = $_GET['id'] ?? null;
if(!$experience_id) { header("location: index.php"); exit; }

try {
    $stmt = $pdo->prepare("DELETE FROM company_experiences WHERE experience_id = ?");
    $stmt->execute([$experience_id]);
    header("location: index.php?success=deleted");
    exit();
} catch(PDOException $e) {
    die("ERROR: Could not delete experience record. " . $e->getMessage());
}
?>
