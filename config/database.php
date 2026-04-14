<?php
// Database configuration
$host = '127.0.0.1';
$port = '3306';
$dbname = 'soa_management';
$username = 'root';
$password = '';

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password);
    //echo "✅ Connected!";

    // Auto-create client_contacts table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS client_contacts (
        contact_id INT PRIMARY KEY AUTO_INCREMENT,
        client_id INT NOT NULL,
        contact_name VARCHAR(255) NOT NULL,
        contact_number VARCHAR(100) NOT NULL,
        contact_email VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE CASCADE
    )");
} catch (PDOException $e) {
    die("❌ ERROR: " . $e->getMessage());
}

/*Create connection
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

