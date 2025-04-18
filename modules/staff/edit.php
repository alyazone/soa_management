<?php
// Set the base path for includes
$basePath = '../../';

// Include header and sidebar
include_once $basePath . "includes/header.php";
include_once $basePath . "includes/sidebar.php";

// Check if the user has admin privileges or is editing their own profile
if($_SESSION['position'] != 'Admin' && $_SESSION['staff_id'] != $_GET['id']){
    echo '<div class="col-md-10 ml-sm-auto px-4"><div class="alert alert-danger mt-3">You do not have permission to access this page.</div></div>';
    include_once $basePath . "includes/footer.php";
    exit;
}

// Include database connection
require_once $basePath . "config/database.php";

// Check if id parameter is set
if(empty($_GET["id"])){
    header("location: index.php");
    exit();
}

// Define variables and initialize with empty values
$username = $full_name = $email = $department = $position = "";
$username_err = $full_name_err = $email_err = $department_err = $position_err = $password_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
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
        if(empty(trim($_POST["position"]))){
            $position_err = "Please select position.";
        } else{
            $position = trim($_POST["position"]);
        }
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
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            if(!empty($password)){
                $stmt->bindParam(":password", $param_password, PDO::PARAM_STR);
            }
            $stmt->bindParam(":full_name", $param_full_name, PDO::PARAM_STR);
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
            $stmt->bindParam(":department", $param_department, PDO::PARAM_STR);
            if($_SESSION['position'] == 'Admin'){
                $stmt->bindParam(":position", $param_position, PDO::PARAM_STR);
            }
            $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
            
            // Set parameters
            $param_username = $username;
            if(!empty($password)){
                $param_password = password_hash($password, PASSWORD_DEFAULT);
            }
            $param_full_name = $full_name;
            $param_email = $email;
            $param_department = $department;
            if($_SESSION['position'] == 'Admin'){
                $param_position = $position;
            }
            $param_id = $id;
            
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
                header("location: index.php?success=1");
                exit();
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
        }
        
        // Close statement
        unset($stmt);
    }
} else {
    // Fetch staff data
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
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Edit Staff</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="index.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Staff Information</h6>
        </div>
        <div class="card-body">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                        <span class="invalid-feedback"><?php echo $username_err; ?></span>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Full Name</label>
                        <input type="text" name="full_name" class="form-control <?php echo (!empty($full_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $full_name; ?>">
                        <span class="invalid-feedback"><?php echo $full_name_err; ?></span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                        <span class="invalid-feedback"><?php echo $email_err; ?></span>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Department</label>
                        <input type="text" name="department" class="form-control <?php echo (!empty($department_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $department; ?>">
                        <span class="invalid-feedback"><?php echo $department_err; ?></span>
                    </div>
                </div>
                <?php if($_SESSION['position'] == 'Admin'): ?>
                <div class="form-group">
                    <label>Position</label>
                    <select name="position" class="form-control <?php echo (!empty($position_err)) ? 'is-invalid' : ''; ?>">
                        <option value="Admin" <?php echo ($position == "Admin") ? 'selected' : ''; ?>>Admin</option>
                        <option value="Manager" <?php echo ($position == "Manager") ? 'selected' : ''; ?>>Manager</option>
                        <option value="Staff" <?php echo ($position == "Staff") ? 'selected' : ''; ?>>Staff</option>
                    </select>
                    <span class="invalid-feedback"><?php echo $position_err; ?></span>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                    <small class="form-text text-muted">Leave blank to keep current password.</small>
                    <span class="invalid-feedback"><?php echo $password_err; ?></span>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Update">
                    <a href="index.php" class="btn btn-secondary ml-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Include footer
include_once $basePath . "includes/footer.php";
?>
