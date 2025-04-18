<?php
// Include database connection
require_once "config/database.php";

// Check if the setup has already been done
$stmt = $pdo->query("SELECT COUNT(*) as count FROM staff");
$result = $stmt->fetch();

if($result['count'] > 0) {
    die("Setup has already been completed. For security reasons, this script can only be run once.");
}

// Create admin account
$username = "admin";
$password = password_hash("password", PASSWORD_DEFAULT); // Password: password
$full_name = "System Administrator";
$email = "admin@example.com";
$department = "IT";
$position = "Admin";

$sql = "INSERT INTO staff (username, password, full_name, email, department, position) 
        VALUES (:username, :password, :full_name, :email, :department, :position)";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(":username", $username, PDO::PARAM_STR);
$stmt->bindParam(":password", $password, PDO::PARAM_STR);
$stmt->bindParam(":full_name", $full_name, PDO::PARAM_STR);
$stmt->bindParam(":email", $email, PDO::PARAM_STR);
$stmt->bindParam(":department", $department, PDO::PARAM_STR);
$stmt->bindParam(":position", $position, PDO::PARAM_STR);

if($stmt->execute()) {
    echo "Setup completed successfully! An admin account has been created.<br>";
    echo "Username: admin<br>";
    echo "Password: password<br>";
    echo "<a href='modules/auth/login.php'>Go to Login Page</a>";
} else {
    echo "Error during setup.";
}

// Delete this file for security
// Comment out the line below if you want to keep this file
// unlink(__FILE__);
?>