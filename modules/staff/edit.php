<?php
// Set the base path for includes
$basePath = '../../';
ob_start();

// Include database connection
require_once $basePath . "config/database.php";

// Check if user is logged in
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}

// Check if the user has admin privileges or is editing their own profile
if($_SESSION['position'] != 'Admin' && $_SESSION['staff_id'] != $_GET['id']){
    $access_denied = true;
} else {
    $access_denied = false;
}

// Check if id parameter is set
if(empty($_GET["id"])){
    header("location: index.php");
    exit();
}

// Define variables and initialize with empty values
$username = $full_name = $email = $department = $position = "";
$username_err = $full_name_err = $email_err = $department_err = $position_err = $password_err = "";

// Processing form data when form is submitted
if(!$access_denied && $_SERVER["REQUEST_METHOD"] == "POST"){
    // Debug information
    if($_SERVER["REQUEST_METHOD"] == "POST"){
        error_log("POST data received: " . print_r($_POST, true));
        error_log("Session position: " . $_SESSION['position']);
        error_log("Access denied: " . ($access_denied ? 'true' : 'false'));
    }
    // Get hidden input value
    $id = $_POST["id"];
    
    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } else{
        // Prepare a select statement
        $sql = "SELECT staff_id FROM staff WHERE username = :username AND staff_id != :id";
        
        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
            
            // Set parameters
            $param_username = trim($_POST["username"]);
            $param_id = $id;
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                if($stmt->rowCount() == 1){
                    $username_err = "This username is already taken.";
                } else{
                    $username = trim($_POST["username"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            unset($stmt);
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
        $sql = "SELECT staff_id FROM staff WHERE email = :email AND staff_id != :id";
        
        if($stmt = $pdo->prepare($sql)){
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
            $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
            $param_email = trim($_POST["email"]);
            $param_id = $id;
            
            if($stmt->execute()){
                if($stmt->rowCount() == 1){
                    $email_err = "This email is already registered.";
                } else{
                    $email = trim($_POST["email"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
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
    
    // Validate position (only admin can change position)
    if($_SESSION['position'] == 'Admin'){
        if(isset($_POST["position"])){
            if(empty(trim($_POST["position"]))){
                $position_err = "Please select position.";
            } else{
                $position = trim($_POST["position"]);
            }
        } else {
            // If position field is not in POST, keep the current position
            $position = $staff['position'] ?? '';
        }
    } else {
        // Non-admin users keep their current position
        $position = $staff['position'] ?? '';
    }
    
    // Validate password if provided
    $password = trim($_POST["password"]);
    if(!empty($password)){
        if(strlen($password) < 6){
            $password_err = "Password must have at least 6 characters.";
        }
    }
    
    // Check input errors before updating the database
    if(empty($username_err) && empty($full_name_err) && empty($email_err) && empty($department_err) && empty($position_err) && empty($password_err)){
        try {
            // Prepare an update statement
            if($_SESSION['position'] == 'Admin'){
                // Admin can update all fields including position
                if(!empty($password)){
                    $sql = "UPDATE staff SET username = :username, password = :password, full_name = :full_name, email = :email, department = :department, position = :position WHERE staff_id = :id";
                } else {
                    $sql = "UPDATE staff SET username = :username, full_name = :full_name, email = :email, department = :department, position = :position WHERE staff_id = :id";
                }
            } else {
                // Regular staff can only update their own details except position
                if(!empty($password)){
                    $sql = "UPDATE staff SET username = :username, password = :password, full_name = :full_name, email = :email, department = :department WHERE staff_id = :id";
                } else {
                    $sql = "UPDATE staff SET username = :username, full_name = :full_name, email = :email, department = :department WHERE staff_id = :id";
                }
            }
            
            if($stmt = $pdo->prepare($sql)){
                // Bind variables to the prepared statement as parameters
                $stmt->bindParam(":username", $username, PDO::PARAM_STR);
                if(!empty($password)){
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt->bindParam(":password", $hashed_password, PDO::PARAM_STR);
                }
                $stmt->bindParam(":full_name", $full_name, PDO::PARAM_STR);
                $stmt->bindParam(":email", $email, PDO::PARAM_STR);
                $stmt->bindParam(":department", $department, PDO::PARAM_STR);
                if($_SESSION['position'] == 'Admin'){
                    $stmt->bindParam(":position", $position, PDO::PARAM_STR);
                }
                $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                
                // Attempt to execute the prepared statement
                if($stmt->execute()){
                    // Update session variables if updating own profile
                    if($_SESSION['staff_id'] == $id){
                        $_SESSION['username'] = $username;
                        $_SESSION['full_name'] = $full_name;
                        if($_SESSION['position'] == 'Admin'){
                            $_SESSION['position'] = $position;
                        }
                    }
                    
                    // Records updated successfully. Redirect to landing page
                    header("location: index.php?success=updated");
                    exit();
                } else{
                    $update_err = "Oops! Something went wrong. Please try again later.";
                    error_log("SQL execution failed: " . print_r($stmt->errorInfo(), true));
                }
            } else {
                $update_err = "Failed to prepare SQL statement.";
                error_log("SQL preparation failed: " . print_r($pdo->errorInfo(), true));
            }
            
            // Close statement
            unset($stmt);
        } catch(PDOException $e) {
            $update_err = "Database error: " . $e->getMessage();
            error_log("PDO Exception: " . $e->getMessage());
        }
    }
} else {
    // Fetch staff data
    if(!$access_denied) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = :id");
            $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
            $stmt->execute();
            
            if($stmt->rowCount() != 1){
                header("location: index.php");
                exit();
            }
            
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Set values
            $username = $staff['username'];
            $full_name = $staff['full_name'];
            $email = $staff['email'];
            $department = $staff['department'];
            $position = $staff['position'];
            
        } catch(PDOException $e) {
            die("ERROR: Could not fetch staff. " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Staff - SOA Management System</title>
    
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
                        <h1><?php echo $access_denied ? 'Access Denied' : 'Edit Staff'; ?></h1>
                        <p><?php echo $access_denied ? 'You do not have permission to edit this profile' : 'Update staff member information'; ?></p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="index.php" class="date-picker-btn">
                        <i class="fas fa-arrow-left"></i>
                        Back to List
                    </a>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <?php if($access_denied): ?>
            <!-- Access Denied -->
            <div class="access-denied-card">
                <div class="access-denied-content">
                    <div class="access-denied-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h2>Access Denied</h2>
                    <p>You do not have permission to edit this staff profile. You can only edit your own profile or you need administrator privileges.</p>
                    <a href="<?php echo $basePath; ?>dashboard.php" class="btn-primary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
            <?php else: ?>

            <!-- Edit Form -->
            <div class="form-card" data-aos="fade-up">
                <div class="form-header">
                    <div class="form-title">
                        <h3>Staff Information</h3>
                        <p>Update the staff member's details below</p>
                    </div>
                </div>
                <div class="form-content">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $_GET['id']; ?>" method="post" class="modern-form">
                        <?php if(isset($update_err)): ?>
                            <div class="alert alert-error" style="margin-bottom: 1.5rem;">
                                <div class="alert-content">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <span><?php echo $update_err; ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
                        
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
                                >
                                <?php if(!empty($department_err)): ?>
                                    <div class="form-error">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo $department_err; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Position (Admin Only) -->
                            <?php if($_SESSION['position'] == 'Admin'): ?>
                            <div class="form-group">
                                <label for="position" class="form-label">
                                    <i class="fas fa-user-tie"></i>
                                    Position
                                </label>
                                <select 
                                    id="position"
                                    name="position" 
                                    class="form-select <?php echo (!empty($position_err)) ? 'error' : ''; ?>"
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
                            <?php endif; ?>

                            <!-- Password -->
                            <div class="form-group form-group-full">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock"></i>
                                    New Password
                                </label>
                                <div class="password-input-container">
                                    <input 
                                        type="password" 
                                        id="password"
                                        name="password" 
                                        class="form-input <?php echo (!empty($password_err)) ? 'error' : ''; ?>"
                                        placeholder="Enter new password (leave blank to keep current)"
                                    >
                                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-help">
                                    <i class="fas fa-info-circle"></i>
                                    Leave blank to keep current password. Minimum 6 characters required.
                                </div>
                                <?php if(!empty($password_err)): ?>
                                    <div class="form-error">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo $password_err; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i>
                                Update Staff
                            </button>
                            <a href="index.php" class="btn-secondary">
                                <i class="fas fa-times"></i>
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
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
            const requiredFields = ['username', 'full_name', 'email', 'department'];
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

            if (hasErrors) {
                e.preventDefault();
                alert('Please fill in all required fields.');
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
            max-width: 800px;
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

        .form-group-full {
            grid-column: 1 / -1;
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
            background: var(--primary-color-dark);
            color: var(--white-color);
            text-decoration: none;
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
