<?php
// Database configuration
$host = 'localhost';
$port = 3366;
$dbname = 'soa_management';
$username = 'superuser';
$password = '$Kyrol1133';

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password);
    //echo "✅ Connected!";
} catch (PDOException $e) {
    die("❌ ERROR: " . $e->getMessage());
}

/* Create connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}*/
?>



