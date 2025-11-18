<?php
ob_start();
$basePath = '../../';
require_once $basePath . "config/database.php";

session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['position'] != 'Admin'){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}

$supplier_name = $address = $pic_name = $pic_contact = $pic_email = "";
$supplier_name_err = $address_err = $pic_name_err = $pic_contact_err = $pic_email_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(trim($_POST["supplier_name"]))){
        $supplier_name_err = "Please enter supplier name.";
    } else{
        $supplier_name = trim($_POST["supplier_name"]);
    }
    
    if(empty(trim($_POST["address"]))){
        $address_err = "Please enter address.";
    } else{
        $address = trim($_POST["address"]);
    }
    
    if(empty(trim($_POST["pic_name"]))){
        $pic_name_err = "Please enter PIC name.";
    } else{
        $pic_name = trim($_POST["pic_name"]);
    }
    
    if(empty(trim($_POST["pic_contact"]))){
        $pic_contact_err = "Please enter PIC contact.";
    } else{
        $pic_contact = trim($_POST["pic_contact"]);
    }
    
    if(empty(trim($_POST["pic_email"]))){
        $pic_email_err = "Please enter PIC email.";
    } elseif(!filter_var(trim($_POST["pic_email"]), FILTER_VALIDATE_EMAIL)){
        $pic_email_err = "Please enter a valid email address.";
    } else{
        try {
            $stmt = $pdo->prepare("SELECT supplier_id FROM suppliers WHERE pic_email = :email");
            $stmt->bindParam(":email", trim($_POST["pic_email"]), PDO::PARAM_STR);
            $stmt->execute();
            
            if($stmt->rowCount() > 0){
                $pic_email_err = "This email is already registered with another supplier.";
            } else{
                $pic_email = trim($_POST["pic_email"]);
            }
        } catch(PDOException $e) {
            error_log("Email check error: " . $e->getMessage());
            $pic_email_err = "An error occurred while validating the email.";
        }
    }
    
    if(empty($supplier_name_err) && empty($address_err) && empty($pic_name_err) && empty($pic_contact_err) && empty($pic_email_err)){
        try {
            $sql = "INSERT INTO suppliers (supplier_name, address, pic_name, pic_contact, pic_email) VALUES (:supplier_name, :address, :pic_name, :pic_contact, :pic_email)";
            
            if($stmt = $pdo->prepare($sql)){
                $stmt->bindParam(":supplier_name", $param_supplier_name, PDO::PARAM_STR);
                $stmt->bindParam(":address", $param_address, PDO::PARAM_STR);
                $stmt->bindParam(":pic_name", $param_pic_name, PDO::PARAM_STR);
                $stmt->bindParam(":pic_contact", $param_pic_contact, PDO::PARAM_STR);
                $stmt->bindParam(":pic_email", $param_pic_email, PDO::PARAM_STR);
                
                $param_supplier_name = $supplier_name;
                $param_address = $address;
                $param_pic_name = $pic_name;
                $param_pic_contact = $pic_contact;
                $param_pic_email = $pic_email;
                
                if($stmt->execute()){
                    header("location: index.php?success=added");
                    exit();
                } else{
                    $general_err = "Oops! Something went wrong. Please try again later.";
                }
            }
        } catch(PDOException $e) {
            error_log("Supplier creation error: " . $e->getMessage());
            $general_err = "An error occurred while creating the supplier.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Supplier - SOA Management System</title>
    
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/modern-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include_once $basePath . "includes/modern-sidebar.php"; ?>
    
    <div class="main-content">
        <header class="dashboard-header">
            <div class="header-content">
                <div class="header-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="header-title">
                        <h1>Add New Supplier</h1>
                        <p>Register a new supplier in the system</p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="index.php" class="export-btn secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to List
                    </a>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
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

            <div class="form-card" data-aos="fade-up">
                <div class="form-header">
                    <div class="form-title">
                        <h3>
                            <i class="fas fa-truck-fast"></i>
                            Supplier Information
                        </h3>
                        <p>Enter the details for the new supplier</p>
                    </div>
                </div>
                
                <div class="form-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="modern-form" id="supplierForm">
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
                                        <i class="fas fa-truck-fast"></i>
                                        Supplier Name
                                    </label>
                                    <input type="text" name="supplier_name" class="form-input <?php echo (!empty($supplier_name_err)) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($supplier_name); ?>" placeholder="Enter company name" required>
                                    <?php if(!empty($supplier_name_err)): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $supplier_name_err; ?></span><?php endif; ?>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label class="form-label required">
                                        <i class="fas fa-map-marker-alt"></i>
                                        Address
                                    </label>
                                    <textarea name="address" class="form-textarea <?php echo (!empty($address_err)) ? 'error' : ''; ?>" rows="3" placeholder="Enter complete address" required><?php echo htmlspecialchars($address); ?></textarea>
                                    <?php if(!empty($address_err)): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $address_err; ?></span><?php endif; ?>
                                    <small class="form-help">Include street address, city, state/province, and postal code</small>
                                </div>
                            </div>
                        </div>

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
                                    <input type="text" name="pic_name" class="form-input <?php echo (!empty($pic_name_err)) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($pic_name); ?>" placeholder="Enter contact person's full name" required>
                                    <?php if(!empty($pic_name_err)): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $pic_name_err; ?></span><?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label required">
                                        <i class="fas fa-phone"></i>
                                        Contact Number
                                    </label>
                                    <input type="tel" name="pic_contact" class="form-input <?php echo (!empty($pic_contact_err)) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($pic_contact); ?>" placeholder="Enter phone number" required>
                                    <?php if(!empty($pic_contact_err)): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $pic_contact_err; ?></span><?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label required">
                                        <i class="fas fa-envelope"></i>
                                        Email Address
                                    </label>
                                    <input type="email" name="pic_email" class="form-input <?php echo (!empty($pic_email_err)) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($pic_email); ?>" placeholder="Enter email address" required>
                                    <?php if(!empty($pic_email_err)): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $pic_email_err; ?></span><?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Create Supplier
                            </button>
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

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="../../assets/js/modern-dashboard.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({ duration: 800, easing: 'ease-in-out', once: true });
            initializeDashboard();
            initializeFormValidation();
        });

        function initializeFormValidation() {
            const form = document.getElementById('supplierForm');
            const inputs = form.querySelectorAll('input, textarea');
            
            inputs.forEach(input => {
                input.addEventListener('blur', function() { validateField(this); });
                input.addEventListener('input', function() { if (this.classList.contains('error')) { validateField(this); } });
            });

            form.addEventListener('submit', function(e) {
                let isValid = true;
                inputs.forEach(input => { if (!validateField(input)) { isValid = false; } });
                if (!isValid) { e.preventDefault(); showFormError('Please correct the errors above before submitting.'); }
            });
        }

        function validateField(field) {
            const value = field.value.trim();
            const fieldName = field.name;
            let isValid = true;
            let errorMessage = '';

            field.classList.remove('error');
            const existingError = field.parentNode.querySelector('.error-message');
            if (existingError) { existingError.remove(); }

            if (!field.hasAttribute('required') && value === '') return true;

            switch(fieldName) {
                case 'supplier_name':
                    if (value === '') { isValid = false; errorMessage = 'Supplier name is required.'; }
                    else if (value.length < 2) { isValid = false; errorMessage = 'Supplier name must be at least 2 characters.'; }
                    break;
                case 'address':
                    if (value === '') { isValid = false; errorMessage = 'Address is required.'; }
                    else if (value.length < 10) { isValid = false; errorMessage = 'Please enter a complete address.'; }
                    break;
                case 'pic_name':
                    if (value === '') { isValid = false; errorMessage = 'Contact person name is required.'; }
                    else if (value.length < 2) { isValid = false; errorMessage = 'Name must be at least 2 characters.'; }
                    break;
                case 'pic_contact':
                    if (value === '') { isValid = false; errorMessage = 'Contact number is required.'; }
                    else if (!/^[\d\s\-\+()]+$/.test(value)) { isValid = false; errorMessage = 'Please enter a valid phone number.'; }
                    break;
                case 'pic_email':
                    if (value === '') { isValid = false; errorMessage = 'Email address is required.'; }
                    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) { isValid = false; errorMessage = 'Please enter a valid email address.'; }
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

        function showFormError(message) {
            const existingAlert = document.querySelector('.alert-error');
            if (existingAlert) existingAlert.remove();
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-error';
            alertDiv.innerHTML = `<div class="alert-content"><i class="fas fa-exclamation-circle"></i><span>${message}</span></div><button class="alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>`;
            const form = document.querySelector('.form-card');
            form.parentNode.insertBefore(alertDiv, form);
            setTimeout(() => { if (alertDiv.parentNode) alertDiv.remove(); }, 5000);
        }
    </script>
    <style>
        .form-card{background:white;border-radius:var(--border-radius);box-shadow:var(--shadow);border:1px solid var(--gray-200);overflow:hidden}.form-header{padding:1.5rem;border-bottom:1px solid var(--gray-200);background:var(--gray-50)}.form-title h3{display:flex;align-items:center;gap:.5rem;color:var(--gray-900);font-size:1.25rem;font-weight:600;margin:0 0 .5rem}.form-title p{color:var(--gray-600);margin:0}.form-body{padding:2rem}.form-section{margin-bottom:2rem}.form-section:last-child{margin-bottom:0}.section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;padding-bottom:.75rem;border-bottom:1px solid var(--gray-200)}.section-header h4{display:flex;align-items:center;gap:.5rem;color:var(--gray-900);font-size:1.125rem;font-weight:600;margin:0}.required-badge{background:var(--danger-color);color:white;padding:.25rem .5rem;border-radius:9999px;font-size:.75rem;font-weight:500}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}.form-group{display:flex;flex-direction:column;gap:.5rem}.form-group.full-width{grid-column:1 / -1}.form-label{display:flex;align-items:center;gap:.5rem;font-size:.875rem;font-weight:500;color:var(--gray-700)}.form-label.required::after{content:'*';color:var(--danger-color);margin-left:.25rem}.form-input,.form-textarea{padding:.75rem;border:1px solid var(--gray-300);border-radius:var(--border-radius-sm);font-size:.875rem;transition:var(--transition);background:white}.form-input:focus,.form-textarea:focus{outline:0;border-color:var(--primary-color);box-shadow:0 0 0 3px rgba(59,130,246,.1)}.form-input.error,.form-textarea.error{border-color:var(--danger-color);box-shadow:0 0 0 3px rgba(239,68,68,.1)}.form-textarea{resize:vertical;min-height:80px}.form-help{font-size:.75rem;color:var(--gray-500);margin-top:.25rem}.error-message{display:flex;align-items:center;gap:.5rem;color:var(--danger-color);font-size:.75rem;font-weight:500;margin-top:.25rem}.form-actions{display:flex;gap:1rem;padding-top:2rem;border-top:1px solid var(--gray-200);margin-top:2rem}.btn{display:inline-flex;align-items:center;gap:.5rem;padding:.75rem 1.5rem;border:none;border-radius:var(--border-radius-sm);font-size:.875rem;font-weight:500;text-decoration:none;cursor:pointer;transition:var(--transition)}.btn-primary{background:var(--primary-color);color:white}.btn-primary:hover{background:var(--primary-dark);color:white;text-decoration:none}.btn-secondary{background:var(--gray-100);color:var(--gray-700);border:1px solid var(--gray-300)}.btn-secondary:hover{background:var(--gray-200);color:var(--gray-900);text-decoration:none}.alert{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.5rem;border-radius:var(--border-radius);margin-bottom:1.5rem;border:1px solid}.alert-error{background:rgba(239,68,68,.1);border-color:var(--danger-color);color:var(--danger-color)}.alert-content{display:flex;align-items:center;gap:.75rem}.alert-close{background:0 0;border:none;color:inherit;cursor:pointer;padding:.25rem;border-radius:var(--border-radius-sm);transition:var(--transition)}.alert-close:hover{background:rgba(0,0,0,.1)}@media (max-width:768px){.form-grid{grid-template-columns:1fr}.form-body{padding:1.5rem}.form-actions{flex-direction:column}}
    </style>
</body>
</html>
<?php ob_end_flush(); ?>
