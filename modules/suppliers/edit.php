<?php
ob_start();
$basePath = '../../';
require_once $basePath . "config/database.php";

session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['position'] != 'Admin'){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}

if(empty($_GET["id"])){
    header("location: index.php");
    exit();
}

$supplier_name = $address = $pic_name = $pic_contact = $pic_email = "";
$supplier_name_err = $address_err = $pic_name_err = $pic_contact_err = $pic_email_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $id = $_POST["id"];
    
    if(empty(trim($_POST["supplier_name"]))){ $supplier_name_err = "Please enter supplier name."; } else { $supplier_name = trim($_POST["supplier_name"]); }
    if(empty(trim($_POST["address"]))){ $address_err = "Please enter address."; } else { $address = trim($_POST["address"]); }
    if(empty(trim($_POST["pic_name"]))){ $pic_name_err = "Please enter PIC name."; } else { $pic_name = trim($_POST["pic_name"]); }
    if(empty(trim($_POST["pic_contact"]))){ $pic_contact_err = "Please enter PIC contact."; } else { $pic_contact = trim($_POST["pic_contact"]); }
    
    if(empty(trim($_POST["pic_email"]))){
        $pic_email_err = "Please enter PIC email.";
    } elseif(!filter_var(trim($_POST["pic_email"]), FILTER_VALIDATE_EMAIL)){
        $pic_email_err = "Please enter a valid email address.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT supplier_id FROM suppliers WHERE pic_email = :email AND supplier_id != :id");
            $stmt->bindParam(":email", trim($_POST["pic_email"]), PDO::PARAM_STR);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
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
            $sql = "UPDATE suppliers SET supplier_name = :supplier_name, address = :address, pic_name = :pic_name, pic_contact = :pic_contact, pic_email = :pic_email WHERE supplier_id = :id";
            
            if($stmt = $pdo->prepare($sql)){
                $stmt->bindParam(":supplier_name", $param_supplier_name, PDO::PARAM_STR);
                $stmt->bindParam(":address", $param_address, PDO::PARAM_STR);
                $stmt->bindParam(":pic_name", $param_pic_name, PDO::PARAM_STR);
                $stmt->bindParam(":pic_contact", $param_pic_contact, PDO::PARAM_STR);
                $stmt->bindParam(":pic_email", $param_pic_email, PDO::PARAM_STR);
                $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
                
                $param_supplier_name = $supplier_name;
                $param_address = $address;
                $param_pic_name = $pic_name;
                $param_pic_contact = $pic_contact;
                $param_pic_email = $pic_email;
                $param_id = $id;
                
                if($stmt->execute()){
                    header("location: index.php?success=updated");
                    exit();
                } else{
                    $general_err = "Oops! Something went wrong. Please try again later.";
                }
            }
        } catch(PDOException $e) {
            error_log("Supplier update error: " . $e->getMessage());
            $general_err = "An error occurred while updating the supplier.";
        }
    }
} else {
    try {
        $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE supplier_id = :id");
        $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
        $stmt->execute();
        
        if($stmt->rowCount() != 1){
            header("location: index.php");
            exit();
        }
        
        $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
        $supplier_name = $supplier['supplier_name'];
        $address = $supplier['address'];
        $pic_name = $supplier['pic_name'];
        $pic_contact = $supplier['pic_contact'];
        $pic_email = $supplier['pic_email'];
        
    } catch(PDOException $e) {
        error_log("Supplier fetch error: " . $e->getMessage());
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
    <title>Edit Supplier - SOA Management System</title>
    
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
                    <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                    <div class="header-title">
                        <h1>Edit Supplier</h1>
                        <p>Update supplier information and contact details</p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="view.php?id=<?php echo $_GET['id']; ?>" class="export-btn info"><i class="fas fa-eye"></i> View Details</a>
                    <a href="index.php" class="export-btn secondary"><i class="fas fa-arrow-left"></i> Back to List</a>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <?php if(isset($general_err)): ?>
                <div class="alert alert-error" data-aos="fade-down">
                    <div class="alert-content"><i class="fas fa-exclamation-circle"></i><span><?php echo $general_err; ?></span></div>
                    <button class="alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>

            <div class="profile-header" data-aos="fade-down">
                <div class="profile-avatar" style="background-color: #fb923c;"><i class="fas fa-truck-fast"></i></div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($supplier_name); ?></h2>
                    <p class="profile-subtitle">Supplier ID: #<?php echo str_pad($_GET['id'], 3, '0', STR_PAD_LEFT); ?></p>
                    <div class="profile-meta">
                        <span class="meta-item" style="color: var(--warning-color);"><i class="fas fa-edit"></i> Editing supplier information</span>
                    </div>
                </div>
            </div>

            <div class="form-card" data-aos="fade-up">
                <div class="form-header">
                    <div class="form-title">
                        <h3><i class="fas fa-truck-fast"></i> Supplier Information</h3>
                        <p>Update the details for this supplier</p>
                    </div>
                </div>
                
                <div class="form-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $_GET['id']); ?>" method="post" class="modern-form" id="supplierForm">
                        <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
                        
                        <div class="form-section">
                            <div class="section-header">
                                <h4><i class="fas fa-building"></i> Company Information</h4>
                                <span class="required-badge">Required</span>
                            </div>
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label class="form-label required"><i class="fas fa-truck-fast"></i> Supplier Name</label>
                                    <input type="text" name="supplier_name" class="form-input <?php echo (!empty($supplier_name_err)) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($supplier_name); ?>" required>
                                    <?php if(!empty($supplier_name_err)): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $supplier_name_err; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group full-width">
                                    <label class="form-label required"><i class="fas fa-map-marker-alt"></i> Address</label>
                                    <textarea name="address" class="form-textarea <?php echo (!empty($address_err)) ? 'error' : ''; ?>" rows="3" required><?php echo htmlspecialchars($address); ?></textarea>
                                    <?php if(!empty($address_err)): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $address_err; ?></span><?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="section-header">
                                <h4><i class="fas fa-user"></i> Contact Person Information</h4>
                                <span class="required-badge">Required</span>
                            </div>
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label class="form-label required"><i class="fas fa-user"></i> Contact Person Name</label>
                                    <input type="text" name="pic_name" class="form-input <?php echo (!empty($pic_name_err)) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($pic_name); ?>" required>
                                    <?php if(!empty($pic_name_err)): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $pic_name_err; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label required"><i class="fas fa-phone"></i> Contact Number</label>
                                    <input type="tel" name="pic_contact" class="form-input <?php echo (!empty($pic_contact_err)) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($pic_contact); ?>" required>
                                    <?php if(!empty($pic_contact_err)): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $pic_contact_err; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label required"><i class="fas fa-envelope"></i> Email Address</label>
                                    <input type="email" name="pic_email" class="form-input <?php echo (!empty($pic_email_err)) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($pic_email); ?>" required>
                                    <?php if(!empty($pic_email_err)): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $pic_email_err; ?></span><?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Supplier</button>
                            <a href="view.php?id=<?php echo $_GET['id']; ?>" class="btn btn-info"><i class="fas fa-eye"></i> View Details</a>
                            <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
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
        function initializeFormValidation(){const e=document.getElementById("supplierForm"),t=e.querySelectorAll("input, textarea");t.forEach(e=>{e.addEventListener("blur",function(){validateField(this)}),e.addEventListener("input",function(){this.classList.contains("error")&&validateField(this)})}),e.addEventListener("submit",function(o){let i=!0;t.forEach(e=>{validateField(e)||i||(i=!1)}),i||(o.preventDefault(),showFormError("Please correct the errors above before submitting."))})}function validateField(e){const t=e.value.trim(),o=e.name;let i=!0,a="";e.classList.remove("error");const r=e.parentNode.querySelector(".error-message");if(r&&r.remove(),!e.hasAttribute("required")&&""===t)return!0;switch(o){case"supplier_name":""===t?(i=!1,a="Supplier name is required."):t.length<2&&(i=!1,a="Supplier name must be at least 2 characters.");break;case"address":""===t?(i=!1,a="Address is required."):t.length<10&&(i=!1,a="Please enter a complete address.");break;case"pic_name":""===t?(i=!1,a="Contact person name is required."):t.length<2&&(i=!1,a="Name must be at least 2 characters.");break;case"pic_contact":""===t?(i=!1,a="Contact number is required."):/^[\d\s\-+()]+$/.test(t)||(i=!1,a="Please enter a valid phone number.");break;case"pic_email":""===t?(i=!1,a="Email address is required."):/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(t)||(i=!1,a="Please enter a valid email address.")}if(!i){e.classList.add("error");const t=document.createElement("span");t.className="error-message",t.innerHTML=`<i class="fas fa-exclamation-circle"></i> ${a}`,e.parentNode.appendChild(t)}return i}function showFormError(e){const t=document.querySelector(".alert-error");t&&t.remove();const o=document.createElement("div");o.className="alert alert-error",o.innerHTML=`<div class="alert-content"><i class="fas fa-exclamation-circle"></i><span>${e}</span></div><button class="alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>`;const i=document.querySelector(".form-card");i.parentNode.insertBefore(o,i),setTimeout(()=>{o.parentNode&&o.remove()},5e3)}
    </script>
    <style>
        .form-card{background:white;border-radius:var(--border-radius);box-shadow:var(--shadow);border:1px solid var(--gray-200);overflow:hidden}.form-header{padding:1.5rem;border-bottom:1px solid var(--gray-200);background:var(--gray-50)}.form-title h3{display:flex;align-items:center;gap:.5rem;color:var(--gray-900);font-size:1.25rem;font-weight:600;margin:0 0 .5rem}.form-title p{color:var(--gray-600);margin:0}.form-body{padding:2rem}.form-section{margin-bottom:2rem}.form-section:last-child{margin-bottom:0}.section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;padding-bottom:.75rem;border-bottom:1px solid var(--gray-200)}.section-header h4{display:flex;align-items:center;gap:.5rem;color:var(--gray-900);font-size:1.125rem;font-weight:600;margin:0}.required-badge{background:var(--danger-color);color:white;padding:.25rem .5rem;border-radius:9999px;font-size:.75rem;font-weight:500}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}.form-group{display:flex;flex-direction:column;gap:.5rem}.form-group.full-width{grid-column:1 / -1}.form-label{display:flex;align-items:center;gap:.5rem;font-size:.875rem;font-weight:500;color:var(--gray-700)}.form-label.required::after{content:'*';color:var(--danger-color);margin-left:.25rem}.form-input,.form-textarea{padding:.75rem;border:1px solid var(--gray-300);border-radius:var(--border-radius-sm);font-size:.875rem;transition:var(--transition);background:white}.form-input:focus,.form-textarea:focus{outline:0;border-color:var(--primary-color);box-shadow:0 0 0 3px rgba(59,130,246,.1)}.form-input.error,.form-textarea.error{border-color:var(--danger-color);box-shadow:0 0 0 3px rgba(239,68,68,.1)}.form-textarea{resize:vertical;min-height:80px}.form-help{font-size:.75rem;color:var(--gray-500);margin-top:.25rem}.error-message{display:flex;align-items:center;gap:.5rem;color:var(--danger-color);font-size:.75rem;font-weight:500;margin-top:.25rem}.form-actions{display:flex;gap:1rem;padding-top:2rem;border-top:1px solid var(--gray-200);margin-top:2rem}.btn{display:inline-flex;align-items:center;gap:.5rem;padding:.75rem 1.5rem;border:none;border-radius:var(--border-radius-sm);font-size:.875rem;font-weight:500;text-decoration:none;cursor:pointer;transition:var(--transition)}.btn-primary{background:var(--primary-color);color:white}.btn-primary:hover{background:var(--primary-dark);color:white;text-decoration:none}.btn-info{background:var(--info-color);color:white}.btn-info:hover{background:#0891b2;color:white;text-decoration:none}.btn-secondary{background:var(--gray-100);color:var(--gray-700);border:1px solid var(--gray-300)}.btn-secondary:hover{background:var(--gray-200);color:var(--gray-900);text-decoration:none}.profile-header{background:white;border-radius:var(--border-radius);box-shadow:var(--shadow);border:1px solid var(--gray-200);padding:2rem;margin-bottom:2rem;display:flex;align-items:center;gap:1.5rem}.profile-avatar{width:80px;height:80px;background:var(--warning-color);border-radius:var(--border-radius);display:flex;align-items:center;justify-content:center;color:white;font-size:2rem}.profile-info h2{color:var(--gray-900);margin-bottom:.5rem;font-size:1.5rem;font-weight:600}.profile-subtitle{color:var(--gray-600);margin-bottom:1rem}.profile-meta{display:flex;gap:1.5rem}.meta-item{display:flex;align-items:center;gap:.5rem;color:var(--warning-color);font-size:.875rem;font-weight:500}.meta-item i{color:var(--warning-color)}.alert{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.5rem;border-radius:var(--border-radius);margin-bottom:1.5rem;border:1px solid}.alert-error{background:rgba(239,68,68,.1);border-color:var(--danger-color);color:var(--danger-color)}.alert-content{display:flex;align-items:center;gap:.75rem}.alert-close{background:0 0;border:none;color:inherit;cursor:pointer;padding:.25rem;border-radius:var(--border-radius-sm);transition:var(--transition)}.alert-close:hover{background:rgba(0,0,0,.1)}@media (max-width:768px){.form-grid{grid-template-columns:1fr}.form-body{padding:1.5rem}.form-actions{flex-direction:column}.profile-header{flex-direction:column;text-align:center}.profile-meta{justify-content:center}}
    </style>
</body>
</html>
<?php ob_end_flush(); ?>
