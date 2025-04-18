<?php
// Initialize the session
session_start();
 
// Check if the user is already logged in, if yes then redirect to dashboard
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: ../../dashboard.php");
    exit;
}
 
// Include database connection
require_once "../../config/database.php";
 
// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $login_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Check if username is empty
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($username_err) && empty($password_err)){
        // Prepare a select statement
        $sql = "SELECT staff_id, username, password, full_name, position FROM staff WHERE username = :username";
        
        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            
            // Set parameters
            $param_username = $username;
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Check if username exists, if yes then verify password
                if($stmt->rowCount() == 1){
                    if($row = $stmt->fetch()){
                        $id = $row["staff_id"];
                        $username = $row["username"];
                        $hashed_password = $row["password"];
                        $full_name = $row["full_name"];
                        $position = $row["position"];
                        if(password_verify($password, $hashed_password)){
                            // Password is correct, so start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["staff_id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["full_name"] = $full_name;
                            $_SESSION["position"] = $position;                            
                            
                            // Redirect user to dashboard page
                            header("location: ../../dashboard.php");
                        } else{
                            // Password is not valid, display a generic error message
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else{
                    // Username doesn't exist, display a generic error message
                    $login_err = "Invalid username or password.";
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            unset($stmt);
        }
    }
    
    // Close connection
    unset($pdo);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SOA Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f5ff;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            display: flex;
            max-width: 900px;
            background-color: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        
        .login-left {
            background-color: #1258e3;
            color: white;
            padding: 40px;
            width: 50%;
            position: relative;
            overflow: hidden;
        }
        
        .login-left::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 25px 25px, rgba(255, 255, 255, 0.2) 2px, transparent 0),
                linear-gradient(rgba(255, 255, 255, 0.1) 1px, transparent 0),
                linear-gradient(90deg, rgba(255, 255, 255, 0.1) 1px, transparent 0);
            background-size: 50px 50px, 25px 25px, 25px 25px;
            opacity: 0.5;
        }
        
        .login-right {
            background-color: rgb(207, 242, 254);
            padding: 40px;
            width: 50%;
        }
        
        .login-header {
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }
        
        .login-header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .login-feature {
            margin-top: 40px;
            position: relative;
            z-index: 1;
        }
        
        .login-feature p {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .login-feature i {
            margin-right: 10px;
            width: 20px;
        }
        
        .login-form-container {
            text-align: center;
        }
        
        .login-logo {
            max-width: 180px;
            margin-bottom: 20px;
        }
        
        .login-form-title {
            color: #1258e3;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .login-form-subtitle {
            color: #6c757d;
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-group label {
            font-weight: 500;
            color: #1258e3;
            margin-bottom: 8px;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #1258e3;
            z-index: 10;
        }
        
        .form-control {
            height: 45px;
            padding-left: 45px;
            border-radius: 8px;
            border: 1px solid #d1d3e2;
            background-color: white;
        }
        
        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(18, 88, 227, 0.15);
            border-color: #1258e3;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 10;
        }
        
        .btn-primary {
            background: #1258e3;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            padding: 12px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: #0d47a1;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(18, 88, 227, 0.3);
        }
        
        .login-footer {
            margin-top: 25px;
            text-align: center;
            color: #6c757d;
            font-size: 0.85rem;
        }
        
        .circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .circle-1 {
            width: 300px;
            height: 300px;
            bottom: -150px;
            right: -150px;
        }
        
        .circle-2 {
            width: 200px;
            height: 200px;
            top: -100px;
            right: 50px;
        }
        
        .circle-3 {
            width: 100px;
            height: 100px;
            bottom: 50px;
            left: -50px;
        }
        
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                max-width: 500px;
                margin: 15px;
            }
            
            .login-left, .login-right {
                width: 100%;
                padding: 30px;
            }
            
            .login-left {
                border-radius: 10px 10px 0 0;
            }
            
            .login-right {
                border-radius: 0 0 10px 10px;
            }
        }
        
        @media (max-width: 576px) {
            .login-left, .login-right {
                padding: 20px;
            }
            
            .login-header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left side - Brand info -->
        <div class="login-left">
            <div class="login-header">
                <h1>SOA Management System</h1>
                <p class="lead">Secure, efficient, and comprehensive management solution for your organization.</p>
            </div>
            <div class="login-feature">
                <p><i class="fas fa-shield-alt"></i> Advanced security protocols</p>
                <p><i class="fas fa-chart-line"></i> Real-time analytics</p>
                <p><i class="fas fa-tasks"></i> Streamlined workflow management</p>
            </div>
            <!-- Decorative circles -->
            <div class="circle circle-1"></div>
            <div class="circle circle-2"></div>
            <div class="circle circle-3"></div>
        </div>
        
        <!-- Right side - Login form -->
        <div class="login-right">
            <div class="login-form-container">
                <img src="../../assets/images/logo.png" alt="KYROL Security Labs Logo" class="login-logo">
                <h2 class="login-form-title">Welcome Back</h2>
                <p class="login-form-subtitle">Please login to your account</p>
                
                <?php 
                if(!empty($login_err)){
                    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle mr-2"></i>' . $login_err . '
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                          </div>';
                }        
                ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-group">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>" placeholder="Enter your username">
                            <div class="invalid-feedback"><?php echo $username_err; ?></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="d-flex justify-content-between align-items-center">
                            <label for="password">Password</label>
                            <a href="#" class="text-primary small">Forgot password?</a>
                        </div>
                        <div class="input-group">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" placeholder="Enter your password">
                            <span class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye"></i>
                            </span>
                            <div class="invalid-feedback"><?php echo $password_err; ?></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="remember-me">
                            <label class="custom-control-label" for="remember-me">Remember me</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-sign-in-alt mr-2"></i>Login
                        </button>
                    </div>
                </form>
                
                <div class="login-footer">
                    <p>© <?php echo date('Y'); ?> KYROL Security Labs. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const icon = document.querySelector('.password-toggle i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
