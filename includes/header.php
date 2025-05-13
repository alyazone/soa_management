<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: modules/auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOA Management System - KYROL Security Labs</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo (isset($basePath) ? $basePath : ''); ?>assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary" style="color: #004085;">
        <a class="navbar-brand" href="<?php echo (isset($basePath) ? $basePath : ''); ?>dashboard.php">
            KYROL SOA Management
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION["full_name"]); ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/auth/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
