<?php
// Initialize the session
session_start();

// Check if user came from forgot_password.php
if(!isset($_SESSION["reset_staff_id"]) || !isset($_SESSION["reset_username"])){
    header("location: forgot_password.php");
    exit;
}

// Check if user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: ../../dashboard.php");
    exit;
}

// Include database connection
require_once "../../config/database.php";

// Define variables and initialize with empty values
$new_password = $confirm_password = "";
$new_password_err = $confirm_password_err = $success_msg = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Validate new password
    if(empty(trim($_POST["new_password"]))){
        $new_password_err = "Please enter a new password.";
    } elseif(strlen(trim($_POST["new_password"])) < 6){
        $new_password_err = "Password must have at least 6 characters.";
    } else{
        $new_password = trim($_POST["new_password"]);
    }

    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Please confirm your password.";
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($new_password_err) && ($new_password != $confirm_password)){
            $confirm_password_err = "Passwords do not match.";
        }
    }

    // Check input errors before updating database
    if(empty($new_password_err) && empty($confirm_password_err)){
        // Prepare an update statement
        $sql = "UPDATE staff SET password = :password WHERE staff_id = :staff_id";

        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement
            $stmt->bindParam(":password", $param_password, PDO::PARAM_STR);
            $stmt->bindParam(":staff_id", $param_staff_id, PDO::PARAM_INT);

            // Set parameters
            $param_password = password_hash($new_password, PASSWORD_DEFAULT);
            $param_staff_id = $_SESSION["reset_staff_id"];

            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Password updated successfully
                // Clear reset session variables
                unset($_SESSION["reset_staff_id"]);
                unset($_SESSION["reset_username"]);

                // Set success message in session for login page
                session_start();
                $_SESSION["reset_success"] = "Password reset successful! You can now login with your new password.";

                // Redirect to login page
                header("location: login.php");
                exit();
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
    <title>Reset Password - SOA Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #ffffff;
            overflow-x: hidden;
            min-height: 100vh;
        }

        .login-container {
            display: flex;
            min-height: 100vh;
        }

        .left-section {
            width: 45%;
            background-color: #171739;
            position: relative;
            clip-path: polygon(0 0, 100% 0, 85% 100%, 0 100%);
            display: flex;
            flex-direction: column;
            padding: 40px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
        }

        .logo-container {
            margin-bottom: 80px;
            display: flex;
            justify-content: flex-start;
        }

        .logo {
            max-width: 350px;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
            transition: all 0.3s ease;
        }

        .management-title {
            color: #ffffff;
            font-size: 32px;
            font-weight: 700;
            margin-top: 40px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            letter-spacing: 0.5px;
        }

        .right-section {
            width: 55%;
            display: flex;
            flex-direction: column;
            padding: 40px 80px;
            background-color: #ffffff;
        }

        .nav-menu {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 80px;
        }

        .nav-link {
            color: #171739;
            text-decoration: none;
            font-weight: 500;
            margin-left: 30px;
            transition: all 0.3s;
            position: relative;
            padding-bottom: 5px;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 0;
            background-color: #171739;
            transition: width 0.3s ease;
        }

        .nav-link:hover {
            color: #171739;
            text-decoration: none;
        }

        .nav-link:hover::after {
            width: 100%;
        }

        .sign-up-btn {
            border: 1px solid #171739;
            border-radius: 50px;
            padding: 8px 25px;
            color: #171739;
            text-decoration: none;
            margin-left: 30px;
            transition: all 0.3s;
            font-weight: 500;
        }

        .sign-up-btn:hover {
            background-color: #171739;
            color: #ffffff;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .login-form {
            max-width: 450px;
            margin-top: 20px;
        }

        .login-title {
            font-size: 48px;
            font-weight: 800;
            color: #171739;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .login-subtitle {
            font-size: 18px;
            color: #6c757d;
            margin-bottom: 40px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 10px;
            color: #171739;
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        .form-control {
            width: 100%;
            padding: 15px 45px 15px 15px;
            border: none;
            background-color: #f0f0f0;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(23, 23, 57, 0.1);
            background-color: #f8f8f8;
        }

        .password-field {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 10;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #171739;
        }

        .login-btn {
            background-color: #171739;
            color: #fff;
            border: none;
            border-radius: 50px;
            padding: 15px 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            max-width: 220px;
            transition: all 0.3s;
            margin-top: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .login-btn:hover {
            background-color: #232347;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .login-btn i {
            font-size: 20px;
            margin-left: 10px;
            transition: transform 0.3s ease;
        }

        .login-btn:hover i {
            transform: translateX(3px);
        }

        .alert {
            margin-bottom: 20px;
            border-radius: 8px;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .alert-danger {
            background-color: #fff2f2;
            color: #dc3545;
        }

        .alert-success {
            background-color: #f2fff2;
            color: #28a745;
        }

        .alert-info {
            background-color: #f2f8ff;
            color: #0066cc;
        }

        .invalid-feedback {
            color: #dc3545;
            font-size: 14px;
            margin-top: -20px;
            margin-bottom: 20px;
            display: block;
        }

        .back-link {
            margin-top: 20px;
            text-align: center;
        }

        .back-link a {
            color: #171739;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .back-link a:hover {
            color: #232347;
            text-decoration: underline;
        }

        .password-requirements {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .password-requirements ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
        }

        .password-requirements li {
            color: #6c757d;
            margin-bottom: 5px;
        }

        /* Add subtle pattern to left section */
        .left-section::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image:
                radial-gradient(circle at 25px 25px, rgba(255, 255, 255, 0.1) 2px, transparent 0),
                linear-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 0),
                linear-gradient(90deg, rgba(255, 255, 255, 0.05) 1px, transparent 0);
            background-size: 50px 50px, 25px 25px, 25px 25px;
            opacity: 0.5;
            z-index: 0;
        }

        .logo-container, .management-title {
            position: relative;
            z-index: 1;
        }

        @media (max-width: 992px) {
            .login-container {
                flex-direction: column;
            }

            .left-section {
                width: 100%;
                clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
                padding: 30px;
                min-height: 250px;
            }

            .right-section {
                width: 100%;
                padding: 30px;
            }

            .logo-container {
                margin-bottom: 30px;
            }

            .management-title {
                font-size: 24px;
                margin-top: 0;
            }

            .nav-menu {
                margin-bottom: 40px;
            }

            .login-form {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left section with logo and management console text -->
        <div class="left-section">
            <div class="logo-container">
                <img src="../../assets/images/logo.png" alt="KYROL Security Labs Logo" class="logo">
            </div>
            <h2 class="management-title">FINANCE MANAGEMENT CONSOLE</h2>
        </div>

        <!-- Right section with reset password form -->
        <div class="right-section">
            <div class="nav-menu">
                <a href="#" class="nav-link">Home</a>
                <a href="#" class="nav-link">About</a>
                <a href="login.php" class="sign-up-btn">Log In</a>
                <a href="#" class="nav-link">Support</a>
            </div>

            <div class="login-form">
                <h1 class="login-title">Reset Password</h1>
                <p class="login-subtitle">Enter your new password for <strong><?php echo htmlspecialchars($_SESSION["reset_username"]); ?></strong></p>

                <?php
                if(!empty($success_msg)){
                    echo '<div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle mr-2"></i>' . $success_msg . '
                          </div>';
                }
                ?>

                <div class="password-requirements">
                    <strong><i class="fas fa-info-circle mr-1"></i> Password Requirements:</strong>
                    <ul>
                        <li>At least 6 characters long</li>
                        <li>Passwords must match</li>
                    </ul>
                </div>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <label for="new_password" class="form-label">NEW PASSWORD</label>
                    <div class="password-field">
                        <input type="password" name="new_password" id="new_password" class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>" placeholder="Enter new password" required>
                        <span class="password-toggle" onclick="togglePassword('new_password', this)">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    <?php if(!empty($new_password_err)): ?>
                        <span class="invalid-feedback"><?php echo $new_password_err; ?></span>
                    <?php endif; ?>

                    <label for="confirm_password" class="form-label">CONFIRM PASSWORD</label>
                    <div class="password-field">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" placeholder="Confirm new password" required>
                        <span class="password-toggle" onclick="togglePassword('confirm_password', this)">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    <?php if(!empty($confirm_password_err)): ?>
                        <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                    <?php endif; ?>

                    <button type="submit" class="login-btn">
                        RESET PASSWORD
                        <i class="fas fa-check"></i>
                    </button>

                    <div class="back-link">
                        <a href="login.php">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Login
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function togglePassword(fieldId, toggleIcon) {
            const passwordField = document.getElementById(fieldId);
            const icon = toggleIcon.querySelector('i');

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

        // Add subtle animation to logo on page load
        document.addEventListener('DOMContentLoaded', function() {
            const logo = document.querySelector('.logo');
            logo.style.opacity = '0';
            logo.style.transform = 'translateY(20px)';

            setTimeout(function() {
                logo.style.opacity = '1';
                logo.style.transform = 'translateY(0)';
            }, 300);
        });
    </script>
</body>
</html>
