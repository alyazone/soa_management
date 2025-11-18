<?php
// Set the base path for includes
$basePath = '../../';
ob_start();

// Initialize the session
session_start();

// Check if the user is logged in and is an admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["position"] !== "Admin"){
    header("location: login.php");
    exit;
}

// Include database connection
require_once $basePath . "config/database.php";

// Define variables and initialize with empty values
$username = $password = $confirm_password = $full_name = $email = $department = $position = "";
$username_err = $password_err = $confirm_password_err = $full_name_err = $email_err = $department_err = $position_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Debug information
    error_log("POST data received: " . print_r($_POST, true));
    
    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } else{
        // Prepare a select statement
        $sql = "SELECT staff_id FROM staff WHERE username = :username";
        
        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            
            // Set parameters
            $param_username = trim($_POST["username"]);
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                if($stmt->rowCount() == 1){
                    $username_err = "This username is already taken.";
                } else{
                    $username = trim($_POST["username"]);
                }
            } else{
                $username_err = "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            unset($stmt);
        }
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "Password must have at least 6 characters.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Please confirm password.";     
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Validate full name
    if(empty(trim($_POST["full_name"]))){
        $full_name_err = "Please enter full name.";
    } else{
        $full_name = trim($_POST["full_name"]);
    }
    
    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter email.";
    } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)){
        $email_err = "Please enter a valid email address.";
    } else{
        // Check if email already exists
        $sql = "SELECT staff_id FROM staff WHERE email = :email";
        
        if($stmt = $pdo->prepare($sql)){
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
            $param_email = trim($_POST["email"]);
            
            if($stmt->execute()){
                if($stmt->rowCount() == 1){
                    $email_err = "This email is already registered.";
                } else{
                    $email = trim($_POST["email"]);
                }
            } else{
                $email_err = "Oops! Something went wrong. Please try again later.";
            }
            
            unset($stmt);
        }
    }
    
    // Validate department
    if(empty(trim($_POST["department"]))){
        $department_err = "Please enter department.";
    } else{
        $department = trim($_POST["department"]);
    }
    
    // Validate position
    if(empty(trim($_POST["position"]))){
        $position_err = "Please select position.";
    } else{
        $position = trim($_POST["position"]);
    }
    
    // Check input errors before inserting in database
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($full_name_err) && empty($email_err) && empty($department_err) && empty($position_err)){
        
        try {
            // Prepare an insert statement
            $sql = "INSERT INTO staff (username, password, full_name, email, department, position) VALUES (:username, :password, :full_name, :email, :department, :position)";
             
            if($stmt = $pdo->prepare($sql)){
                // Bind variables to the prepared statement as parameters
                $stmt->bindParam(":username", $username, PDO::PARAM_STR);
                $stmt->bindParam(":password", $hashed_password, PDO::PARAM_STR);
                $stmt->bindParam(":full_name", $full_name, PDO::PARAM_STR);
                $stmt->bindParam(":email", $email, PDO::PARAM_STR);
                $stmt->bindParam(":department", $department, PDO::PARAM_STR);
                $stmt->bindParam(":position", $position, PDO::PARAM_STR);
                
                // Set parameters
                $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
                
                // Attempt to execute the prepared statement
                if($stmt->execute()){
                    // Redirect to staff list page
                    header("location: ../staff/index.php?success=created");
                    exit();
                } else{
                    $register_err = "Oops! Something went wrong. Please try again later.";
                    error_log("SQL execution failed: " . print_r($stmt->errorInfo(), true));
                }

                // Close statement
                unset($stmt);
            } else {
                $register_err = "Failed to prepare SQL statement.";
                error_log("SQL preparation failed: " . print_r($pdo->errorInfo(), true));
            }
        } catch(PDOException $e) {
            $register_err = "Database error: " . $e->getMessage();
            error_log("PDO Exception: " . $e->getMessage());
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
    <title>Register Staff - SOA Management System</title>
    
    <!-- Modern CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/modern-dashboard.css">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Sidebar -->
    <?php include_once $basePath . "includes/modern-sidebar.php"; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="dashboard-header">
            <div class="header-content">
                <div class="header-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="header-title">
                        <h1>Register New Staff</h1>
                        <p>Add a new staff member to the system</p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="../staff/index.php" class="date-picker-btn">
                        <i class="fas fa-arrow-left"></i>
                        Back to Staff List
                    </a>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Registration Form -->
            <div class="form-card" data-aos="fade-up">
                <div class="form-header">
                    <div class="form-title">
                        <h3>Staff Registration</h3>
                        <p>Fill in the details below to create a new staff account</p>
                    </div>
                </div>
                <div class="form-content">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="modern-form">
                        <?php if(isset($register_err)): ?>
                            <div class="alert alert-error" style="margin-bottom: 1.5rem;">
                                <div class="alert-content">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <span><?php echo $register_err; ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-grid">
                            <!-- Username -->
                            <div class="form-group">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user"></i>
                                    Username
                                </label>
                                <input 
                                    type="text" 
                                    id="username"
                                    name="username" 
                                    class="form-input <?php echo (!empty($username_err)) ? 'error' : ''; ?>" 
                                    value="<?php echo $username; ?>"
                                    placeholder="Enter username"
                                    required
                                >
                                <?php if(!empty($username_err)): ?>
                                    <div class="form-error">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo $username_err; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Full Name -->
                            <div class="form-group">
                                <label for="full_name" class="form-label">
                                    <i class="fas fa-id-card"></i>
                                    Full Name
                                </label>
                                <input 
                                    type="text" 
                                    id="full_name"
                                    name="full_name" 
                                    class="form-input <?php echo (!empty($full_name_err)) ? 'error' : ''; ?>" 
                                    value="<?php echo $full_name; ?>"
                                    placeholder="Enter full name"
                                    required
                                >
                                <?php if(!empty($full_name_err)): ?>
                                    <div class="form-error">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo $full_name_err; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Email -->
                            <div class="form-group">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope"></i>
                                    Email Address
                                </label>
                                <input 
                                    type="email" 
                                    id="email"
                                    name="email" 
                                    class="form-input <?php echo (!empty($email_err)) ? 'error' : ''; ?>" 
                                    value="<?php echo $email; ?>"
                                    placeholder="Enter email address"
                                    required
                                >
                                <?php if(!empty($email_err)): ?>
                                    <div class="form-error">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo $email_err; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Department -->
                            <div class="form-group">
                                <label for="department" class="form-label">
                                    <i class="fas fa-building"></i>
                                    Department
                                </label>
                                <input 
                                    type="text" 
                                    id="department"
                                    name="department" 
                                    class="form-input <?php echo (!empty($department_err)) ? 'error' : ''; ?>" 
                                    value="<?php echo $department; ?>"
                                    placeholder="Enter department"
                                    required
                                >
                                <?php if(!empty($department_err)): ?>
                                    <div class="form-error">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo $department_err; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Position -->
                            <div class="form-group">
                                <label for="position" class="form-label">
                                    <i class="fas fa-user-tie"></i>
                                    Position
                                </label>
                                <select 
                                    id="position"
                                    name="position" 
                                    class="form-select <?php echo (!empty($position_err)) ? 'error' : ''; ?>"
                                    required
                                >
                                    <option value="">Select Position</option>
                                    <option value="Admin" <?php echo ($position == "Admin") ? 'selected' : ''; ?>>Admin</option>
                                    <option value="Manager" <?php echo ($position == "Manager") ? 'selected' : ''; ?>>Manager</option>
                                    <option value="Staff" <?php echo ($position == "Staff") ? 'selected' : ''; ?>>Staff</option>
                                </select>
                                <?php if(!empty($position_err)): ?>
                                    <div class="form-error">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo $position_err; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Password -->
                            <div class="form-group">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock"></i>
                                    Password
                                </label>
                                <div class="password-input-container">
                                    <input 
                                        type="password" 
                                        id="password"
                                        name="password" 
                                        class="form-input <?php echo (!empty($password_err)) ? 'error' : ''; ?>"
                                        value="<?php echo $password; ?>"
                                        placeholder="Enter password"
                                        required
                                    >
                                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-help">
                                    <i class="fas fa-info-circle"></i>
                                    Password must be at least 6 characters long
                                </div>
                                <?php if(!empty($password_err)): ?>
                                    <div class="form-error">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo $password_err; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Confirm Password -->
                            <div class="form-group">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-lock"></i>
                                    Confirm Password
                                </label>
                                <div class="password-input-container">
                                    <input 
                                        type="password" 
                                        id="confirm_password"
                                        name="confirm_password" 
                                        class="form-input <?php echo (!empty($confirm_password_err)) ? 'error' : ''; ?>"
                                        value="<?php echo $confirm_password; ?>"
                                        placeholder="Confirm password"
                                        required
                                    >
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <?php if(!empty($confirm_password_err)): ?>
                                    <div class="form-error">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo $confirm_password_err; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-user-plus"></i>
                                Register Staff
                            </button>
                            <a href="../staff/index.php" class="btn-secondary">
                                <i class="fas fa-times"></i>
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="../../assets/js/modern-dashboard.js"></script>
    
    <script>
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize AOS animations
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true
            });

            // Initialize interactions
            initializeDashboard();
        });

        // Password toggle functionality
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const toggle = input.nextElementSibling;
            const icon = toggle.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Form validation
        document.querySelector('.modern-form').addEventListener('submit', function(e) {
            const requiredFields = ['username', 'full_name', 'email', 'department', 'position', 'password', 'confirm_password'];
            let hasErrors = false;

            requiredFields.forEach(fieldName => {
                const field = document.querySelector(`[name="${fieldName}"]`);
                if (!field.value.trim()) {
                    field.classList.add('error');
                    hasErrors = true;
                } else {
                    field.classList.remove('error');
                }
            });

            // Check password match
            const password = document.querySelector('[name="password"]').value;
            const confirmPassword = document.querySelector('[name="confirm_password"]').value;
            
            if (password !== confirmPassword) {
                document.querySelector('[name="confirm_password"]').classList.add('error');
                hasErrors = true;
                alert('Passwords do not match.');
            }

            if (hasErrors) {
                e.preventDefault();
                alert('Please fill in all required fields correctly.');
            }
        });

        // Real-time password match validation
        document.querySelector('[name="confirm_password"]').addEventListener('input', function() {
            const password = document.querySelector('[name="password"]').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.classList.add('error');
            } else {
                this.classList.remove('error');
            }
        });
    </script>

    <style>
        /* Form Specific Styles */
        .form-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            overflow: hidden;
            max-width: 900px;
            margin: 0 auto;
        }

        .form-header {
            padding: 1.5rem 2rem 1rem;
            border-bottom: 1px solid var(--gray-100);
        }

        .form-title h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .form-title p {
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        .form-content {
            padding: 2rem;
        }

        .modern-form {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--gray-700);
        }

        .form-label i {
            color: var(--primary-color);
            width: 16px;
        }

        .form-input,
        .form-select {
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius-sm);
            font-size: 0.875rem;
            transition: var(--transition);
            background: white;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-input.error,
        .form-select.error {
            border-color: var(--danger-color);
        }

        .form-input.error:focus,
        .form-select.error:focus {
            border-color: var(--danger-color);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }

        .password-input-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            padding: 0.25rem;
            border-radius: var(--border-radius-sm);
            transition: var(--transition);
        }

        .password-toggle:hover {
            color: var(--gray-600);
            background: var(--gray-100);
        }

        .form-help {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: var(--gray-500);
        }

        .form-help i {
            color: var(--info-color);
        }

        .form-error {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: var(--danger-color);
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding-top: 1rem;
            border-top: 1px solid var(--gray-100);
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius-sm);
            font-weight: 500;
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            color: white;
            text-decoration: none;
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--gray-100);
            color: var(--gray-700);
            text-decoration: none;
            border-radius: var(--border-radius-sm);
            font-weight: 500;
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }

        .btn-secondary:hover {
            background: var(--gray-200);
            color: var(--gray-800);
            text-decoration: none;
        }

        /* Alert Styles */
        .alert {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            border: 1px solid;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border-color: var(--danger-color);
            color: var(--danger-color);
        }

        .alert-content {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-content {
                padding: 1.5rem;
            }

            .form-actions {
                flex-direction: column-reverse;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</body>
</html>

<?php
ob_end_flush();
?>
