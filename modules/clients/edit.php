<?php
ob_start();
// Set the base path for includes
$basePath = '../../';

// Include database connection
require_once $basePath . "config/database.php";

// Check if user is logged in
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}

// Check if the user has admin privileges
if($_SESSION['position'] != 'Admin'){
    header("location: " . $basePath . "dashboard.php");
    exit;
}

// Check if id parameter is set
if(empty($_GET["id"])){
    header("location: index.php");
    exit();
}

// Define variables and initialize with empty values
$client_name = $address = $pic_name = $pic_contact = $pic_email = "";
$client_name_err = $address_err = $pic_name_err = $pic_contact_err = $pic_email_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Get hidden input value
    $id = $_POST["id"];
    
    // Validate client name
    if(empty(trim($_POST["client_name"]))){
        $client_name_err = "Please enter client name.";
    } else{
        $client_name = trim($_POST["client_name"]);
    }
    
    // Validate address
    if(empty(trim($_POST["address"]))){
        $address_err = "Please enter address.";
    } else{
        $address = trim($_POST["address"]);
    }
    
    // Validate PIC name
    if(empty(trim($_POST["pic_name"]))){
        $pic_name_err = "Please enter PIC name.";
    } else{
        $pic_name = trim($_POST["pic_name"]);
    }
    
    // Validate PIC contact
    if(empty(trim($_POST["pic_contact"]))){
        $pic_contact_err = "Please enter PIC contact.";
    } else{
        $pic_contact = trim($_POST["pic_contact"]);
    }
    
    // Validate PIC email
    if(empty(trim($_POST["pic_email"]))){
        $pic_email_err = "Please enter PIC email.";
    } elseif(!filter_var(trim($_POST["pic_email"]), FILTER_VALIDATE_EMAIL)){
        $pic_email_err = "Please enter a valid email address.";
    } else{
        // Check if email already exists for other clients
        try {
            $stmt = $pdo->prepare("SELECT client_id FROM clients WHERE pic_email = :email AND client_id != :id");
            $stmt->bindParam(":email", trim($_POST["pic_email"]), PDO::PARAM_STR);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if($stmt->rowCount() > 0){
                $pic_email_err = "This email is already registered with another client.";
            } else{
                $pic_email = trim($_POST["pic_email"]);
            }
        } catch(PDOException $e) {
            error_log("Email check error: " . $e->getMessage());
            $pic_email_err = "An error occurred while validating the email.";
        }
    }
    
    // Check input errors before updating the database
    if(empty($client_name_err) && empty($address_err) && empty($pic_name_err) && empty($pic_contact_err) && empty($pic_email_err)){
        try {
            // Prepare an update statement
            $sql = "UPDATE clients SET client_name = :client_name, address = :address, pic_name = :pic_name, pic_contact = :pic_contact, pic_email = :pic_email WHERE client_id = :id";
            
            if($stmt = $pdo->prepare($sql)){
                // Bind variables to the prepared statement as parameters
                $stmt->bindParam(":client_name", $param_client_name, PDO::PARAM_STR);
                $stmt->bindParam(":address", $param_address, PDO::PARAM_STR);
                $stmt->bindParam(":pic_name", $param_pic_name, PDO::PARAM_STR);
                $stmt->bindParam(":pic_contact", $param_pic_contact, PDO::PARAM_STR);
                $stmt->bindParam(":pic_email", $param_pic_email, PDO::PARAM_STR);
                $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
                
                // Set parameters
                $param_client_name = $client_name;
                $param_address = $address;
                $param_pic_name = $pic_name;
                $param_pic_contact = $pic_contact;
                $param_pic_email = $pic_email;
                $param_id = $id;
                
                // Attempt to execute the prepared statement
                if($stmt->execute()){
                    // Records updated successfully. Redirect to landing page
                    header("location: index.php?success=updated");
                    exit();
                } else{
                    $general_err = "Oops! Something went wrong. Please try again later.";
                }
            }
        } catch(PDOException $e) {
            error_log("Client update error: " . $e->getMessage());
            $general_err = "An error occurred while updating the client.";
        }
    }
} else {
    // Fetch client data
    try {
        $stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = :id");
        $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
        $stmt->execute();
        
        if($stmt->rowCount() != 1){
            header("location: index.php");
            exit();
        }
        
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Set values
        $client_name = $client['client_name'];
        $address = $client['address'];
        $pic_name = $client['pic_name'];
        $pic_contact = $client['pic_contact'];
        $pic_email = $client['pic_email'];
        
    } catch(PDOException $e) {
        error_log("Client fetch error: " . $e->getMessage());
        header("location: index.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Client - SOA Management System</title>
    
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
                        <h1>Edit Client</h1>
                        <p>Update client information and contact details</p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="view.php?id=<?php echo $_GET['id']; ?>" class="export-btn info">
                        <i class="fas fa-eye"></i>
                        View Details
                    </a>
                    <a href="index.php" class="export-btn secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to List
                    </a>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Error Message -->
            <?php if(isset($general_err)): ?>
                <div class="alert alert-error" data-aos="fade-down">
                    <div class="alert-content">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $general_err; ?></span>
                    </div>
                    <button class="alert-close" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Client Profile Header -->
            <div class="profile-header" data-aos="fade-down">
                <div class="profile-avatar">
                    <i class="fas fa-building"></i>
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($client_name); ?></h2>
                    <p class="profile-subtitle">Client ID: #<?php echo str_pad($_GET['id'], 3, '0', STR_PAD_LEFT); ?></p>
                    <div class="profile-meta">
                        <span class="meta-item">
                            <i class="fas fa-edit"></i>
                            Editing client information
                        </span>
                    </div>
                </div>
            </div>

            <!-- Edit Client Form -->
            <div class="form-card" data-aos="fade-up">
                <div class="form-header">
                    <div class="form-title">
                        <h3>
                            <i class="fas fa-building"></i>
                            Client Information
                        </h3>
                        <p>Update the details for this client</p>
                    </div>
                </div>
                
                <div class="form-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $_GET['id']); ?>" method="post" class="modern-form" id="clientForm">
                        <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
                        
                        <!-- Company Information Section -->
                        <div class="form-section">
                            <div class="section-header">
                                <h4>
                                    <i class="fas fa-building"></i>
                                    Company Information
                                </h4>
                                <span class="required-badge">Required</span>
                            </div>
                            
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label class="form-label required">
                                        <i class="fas fa-building"></i>
                                        Client Name
                                    </label>
                                    <input type="text" 
                                           name="client_name" 
                                           class="form-input <?php echo (!empty($client_name_err)) ? 'error' : ''; ?>" 
                                           value="<?php echo htmlspecialchars($client_name); ?>" 
                                           placeholder="Enter company name"
                                           required>
                                    <?php if(!empty($client_name_err)): ?>
                                        <span class="error-message">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <?php echo $client_name_err; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label class="form-label required">
                                        <i class="fas fa-map-marker-alt"></i>
                                        Address
                                    </label>
                                    <textarea name="address" 
                                              class="form-textarea <?php echo (!empty($address_err)) ? 'error' : ''; ?>" 
                                              rows="3" 
                                              placeholder="Enter complete address including city, state, and postal code"
                                              required><?php echo htmlspecialchars($address); ?></textarea>
                                    <?php if(!empty($address_err)): ?>
                                        <span class="error-message">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <?php echo $address_err; ?>
                                        </span>
                                    <?php endif; ?>
                                    <small class="form-help">Include street address, city, state/province, and postal code</small>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Person Section -->
                        <div class="form-section">
                            <div class="section-header">
                                <h4>
                                    <i class="fas fa-user"></i>
                                    Contact Person Information
                                </h4>
                                <span class="required-badge">Required</span>
                            </div>
                            
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label class="form-label required">
                                        <i class="fas fa-user"></i>
                                        Contact Person Name
                                    </label>
                                    <input type="text" 
                                           name="pic_name" 
                                           class="form-input <?php echo (!empty($pic_name_err)) ? 'error' : ''; ?>" 
                                           value="<?php echo htmlspecialchars($pic_name); ?>" 
                                           placeholder="Enter contact person's full name"
                                           required>
                                    <?php if(!empty($pic_name_err)): ?>
                                        <span class="error-message">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <?php echo $pic_name_err; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label required">
                                        <i class="fas fa-phone"></i>
                                        Contact Number
                                    </label>
                                    <input type="tel" 
                                           name="pic_contact" 
                                           class="form-input <?php echo (!empty($pic_contact_err)) ? 'error' : ''; ?>" 
                                           value="<?php echo htmlspecialchars($pic_contact); ?>" 
                                           placeholder="Enter phone number"
                                           required>
                                    <?php if(!empty($pic_contact_err)): ?>
                                        <span class="error-message">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <?php echo $pic_contact_err; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label required">
                                        <i class="fas fa-envelope"></i>
                                        Email Address
                                    </label>
                                    <input type="email" 
                                           name="pic_email" 
                                           class="form-input <?php echo (!empty($pic_email_err)) ? 'error' : ''; ?>" 
                                           value="<?php echo htmlspecialchars($pic_email); ?>" 
                                           placeholder="Enter email address"
                                           required>
                                    <?php if(!empty($pic_email_err)): ?>
                                        <span class="error-message">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <?php echo $pic_email_err; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Update Client
                            </button>
                            <a href="view.php?id=<?php echo $_GET['id']; ?>" class="btn btn-info">
                                <i class="fas fa-eye"></i>
                                View Details
                            </a>
                            <a href="index.php" class="btn btn-secondary">
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
            
            // Form validation
            initializeFormValidation();
        });

        function initializeFormValidation() {
            const form = document.getElementById('clientForm');
            const inputs = form.querySelectorAll('input, textarea');
            
            // Real-time validation
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    validateField(this);
                });
                
                input.addEventListener('input', function() {
                    if (this.classList.contains('error')) {
                        validateField(this);
                    }
                });
            });

            // Form submission validation
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                inputs.forEach(input => {
                    if (!validateField(input)) {
                        isValid = false;
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    showFormError('Please correct the errors above before submitting.');
                }
            });
        }

        function validateField(field) {
            const value = field.value.trim();
            const fieldName = field.name;
            let isValid = true;
            let errorMessage = '';

            // Remove existing error state
            field.classList.remove('error');
            const existingError = field.parentNode.querySelector('.error-message');
            if (existingError) {
                existingError.remove();
            }

            // Skip validation if field is not required and empty
            if (!field.hasAttribute('required') && value === '') {
                return true;
            }

            // Validate based on field type
            switch(fieldName) {
                case 'client_name':
                    if (value === '') {
                        isValid = false;
                        errorMessage = 'Client name is required.';
                    } else if (value.length < 2) {
                        isValid = false;
                        errorMessage = 'Client name must be at least 2 characters.';
                    }
                    break;
                case 'address':
                    if (value === '') {
                        isValid = false;
                        errorMessage = 'Address is required.';
                    } else if (value.length < 10) {
                        isValid = false;
                        errorMessage = 'Please enter a complete address.';
                    }
                    break;
                case 'pic_name':
                    if (value === '') {
                        isValid = false;
                        errorMessage = 'Contact person name is required.';
                    } else if (value.length < 2) {
                        isValid = false;
                        errorMessage = 'Name must be at least 2 characters.';
                    }
                    break;
                case 'pic_contact':
                    if (value === '') {
                        isValid = false;
                        errorMessage = 'Contact number is required.';
                    } else if (!/^[\d\s\-\+$$$$]+$/.test(value)) {
                        isValid = false;
                        errorMessage = 'Please enter a valid phone number.';
                    }
                    break;
                case 'pic_email':
                    if (value === '') {
                        isValid = false;
                        errorMessage = 'Email address is required.';
                    } else if (!isValidEmail(value)) {
                        isValid = false;
                        errorMessage = 'Please enter a valid email address.';
                    }
                    break;
            }

            if (!isValid) {
                field.classList.add('error');
                const errorSpan = document.createElement('span');
                errorSpan.className = 'error-message';
                errorSpan.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${errorMessage}`;
                field.parentNode.appendChild(errorSpan);
            }

            return isValid;
        }

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        function showFormError(message) {
            // Remove existing error alert
            const existingAlert = document.querySelector('.alert-error');
            if (existingAlert) {
                existingAlert.remove();
            }

            // Create new error alert
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-error';
            alertDiv.innerHTML = `
                <div class="alert-content">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>${message}</span>
                </div>
                <button class="alert-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;

            // Insert before form
            const form = document.querySelector('.form-card');
            form.parentNode.insertBefore(alertDiv, form);

            // Auto-hide after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
    </script>

    <style>
        /* Form Specific Styles */
        .form-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            overflow: hidden;
        }

        .form-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
        }

        .form-title h3 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-900);
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0 0 0.5rem 0;
        }

        .form-title p {
            color: var(--gray-600);
            margin: 0;
        }

        .form-body {
            padding: 2rem;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section:last-child {
            margin-bottom: 0;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .section-header h4 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-900);
            font-size: 1.125rem;
            font-weight: 600;
            margin: 0;
        }

        .required-badge {
            background: var(--danger-color);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--gray-700);
        }

        .form-label.required::after {
            content: '*';
            color: var(--danger-color);
            margin-left: 0.25rem;
        }

        .form-input,
        .form-textarea {
            padding: 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius-sm);
            font-size: 0.875rem;
            transition: var(--transition);
            background: white;
        }

        .form-input:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-input.error,
        .form-textarea.error {
            border-color: var(--danger-color);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-help {
            font-size: 0.75rem;
            color: var(--gray-500);
            margin-top: 0.25rem;
        }

        .error-message {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--danger-color);
            font-size: 0.75rem;
            font-weight: 500;
            margin-top: 0.25rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            padding-top: 2rem;
            border-top: 1px solid var(--gray-200);
            margin-top: 2rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius-sm);
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            color: white;
            text-decoration: none;
        }

        .btn-info {
            background: var(--info-color);
            color: white;
        }

        .btn-info:hover {
            background: #0891b2;
            color: white;
            text-decoration: none;
        }

        .btn-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
        }

        .btn-secondary:hover {
            background: var(--gray-200);
            color: var(--gray-900);
            text-decoration: none;
        }

        /* Profile Header */
        .profile-header {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            padding: 2rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: var(--warning-color);
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
        }

        .profile-info h2 {
            color: var(--gray-900);
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .profile-subtitle {
            color: var(--gray-600);
            margin-bottom: 1rem;
        }

        .profile-meta {
            display: flex;
            gap: 1.5rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--warning-color);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .meta-item i {
            color: var(--warning-color);
        }

        /* Alert Styles */
        .alert {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
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

        .alert-close {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: var(--border-radius-sm);
            transition: var(--transition);
        }

        .alert-close:hover {
            background: rgba(0, 0, 0, 0.1);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-body {
                padding: 1.5rem;
            }

            .form-actions {
                flex-direction: column;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .profile-meta {
                justify-content: center;
            }
        }
    </style>
</body>
</html>

<?php ob_end_flush(); ?>
