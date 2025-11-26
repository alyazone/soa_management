<?php
// Initialize the session
session_start();

// Check if user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: ../../dashboard.php");
    exit;
}

// Include database connection
require_once "../../config/database.php";

// Define variables and initialize with empty values
$username = $email = "";
$username_err = $email_err = $verify_err = $success_msg = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter your username.";
    } else{
        $username = trim($_POST["username"]);
    }

    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter your email.";
    } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)){
        $email_err = "Please enter a valid email address.";
    } else{
        $email = trim($_POST["email"]);
    }

    // Check if inputs are valid before querying database
    if(empty($username_err) && empty($email_err)){
        // Prepare a select statement to verify username and email
        $sql = "SELECT staff_id, username, email FROM staff WHERE username = :username AND email = :email";

        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);

            // Set parameters
            $param_username = $username;
            $param_email = $email;

            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Check if username and email match
                if($stmt->rowCount() == 1){
                    if($row = $stmt->fetch()){
                        // Username and email verified, store staff_id in session
                        $_SESSION["reset_staff_id"] = $row["staff_id"];
                        $_SESSION["reset_username"] = $row["username"];

                        // Redirect to reset password page
                        header("location: reset_password.php");
                        exit();
                    }
                } else{
                    // Username and email don't match
                    $verify_err = "No account found with that username and email combination.";
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
    <title>Forgot Password - SOA Management System</title>
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
            padding: 15px;
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
            max-width: 180px;
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

        <!-- Right section with forgot password form -->
        <div class="right-section">
            <div class="nav-menu">
                <a href="#" class="nav-link">Home</a>
                <a href="#" class="nav-link">About</a>
                <a href="login.php" class="sign-up-btn">Log In</a>
                <a href="#" class="nav-link">Support</a>
            </div>

            <div class="login-form">
                <h1 class="login-title">Forgot Password</h1>
                <p class="login-subtitle">Enter your username and email to reset your password</p>

                <?php
                if(!empty($verify_err)){
                    echo '<div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle mr-2"></i>' . $verify_err . '
                          </div>';
                }
                if(!empty($success_msg)){
                    echo '<div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle mr-2"></i>' . $success_msg . '
                          </div>';
                }
                ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <label for="username" class="form-label">USERNAME</label>
                    <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>" placeholder="Enter your username" required>
                    <?php if(!empty($username_err)): ?>
                        <span class="invalid-feedback"><?php echo $username_err; ?></span>
                    <?php endif; ?>

                    <label for="email" class="form-label">EMAIL</label>
                    <input type="email" name="email" id="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter your email" required>
                    <?php if(!empty($email_err)): ?>
                        <span class="invalid-feedback"><?php echo $email_err; ?></span>
                    <?php endif; ?>

                    <button type="submit" class="login-btn">
                        VERIFY
                        <i class="fas fa-arrow-right"></i>
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
