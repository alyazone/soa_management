<?php
ob_start();
// Set the base path for includes
$basePath = '../../';

// Include header and sidebar
include_once $basePath . "includes/header.php";
include_once $basePath . "includes/sidebar.php";

// Include database connection
require_once $basePath . "config/database.php";

// Define variables and initialize with empty values
$client_name = $address = $pic_name = $pic_contact = $pic_email = "";
$client_name_err = $address_err = $pic_name_err = $pic_contact_err = $pic_email_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
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
       $pic_email = trim($_POST["pic_email"]);
   }
   
   // Check input errors before inserting in database
   if(empty($client_name_err) && empty($address_err) && empty($pic_name_err) && empty($pic_contact_err) && empty($pic_email_err)){
       // Prepare an insert statement
       $sql = "INSERT INTO clients (client_name, address, pic_name, pic_contact, pic_email) VALUES (:client_name, :address, :pic_name, :pic_contact, :pic_email)";
        
       if($stmt = $pdo->prepare($sql)){
           // Bind variables to the prepared statement as parameters
           $stmt->bindParam(":client_name", $param_client_name, PDO::PARAM_STR);
           $stmt->bindParam(":address", $param_address, PDO::PARAM_STR);
           $stmt->bindParam(":pic_name", $param_pic_name, PDO::PARAM_STR);
           $stmt->bindParam(":pic_contact", $param_pic_contact, PDO::PARAM_STR);
           $stmt->bindParam(":pic_email", $param_pic_email, PDO::PARAM_STR);
           
           // Set parameters
           $param_client_name = $client_name;
           $param_address = $address;
           $param_pic_name = $pic_name;
           $param_pic_contact = $pic_contact;
           $param_pic_email = $pic_email;
           
           // Attempt to execute the prepared statement
           if($stmt->execute()){
               // Records created successfully. Redirect to landing page
               header("location: index.php");
               exit();
           } else{
               echo "Oops! Something went wrong. Please try again later.";
           }
       }
        
       // Close statement
       unset($stmt);
   }
   
   // Close connection
   unset($pdo);
}
?>

<div class="col-md-10 ml-sm-auto px-4">
   <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
       <h1 class="h2">Add New Client</h1>
       <div class="btn-toolbar mb-2 mb-md-0">
           <a href="index.php" class="btn btn-sm btn-secondary">
               <i class="fas fa-arrow-left"></i> Back to List
           </a>
       </div>
   </div>
   
   <div class="card shadow mb-4">
       <div class="card-header py-3">
           <h6 class="m-0 font-weight-bold text-primary">Client Information</h6>
       </div>
       <div class="card-body">
           <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
               <!-- Company Information Section -->
               <div class="card mb-4">
                   <div class="card-header bg-light">
                       <h6 class="m-0 font-weight-bold text-primary">
                           <i class="fas fa-building mr-2"></i>Company Information
                           <span class="badge badge-warning ml-2">Required</span>
                       </h6>
                       <small class="text-muted">Basic information about the client company</small>
                   </div>
                   <div class="card-body">
                       <div class="form-group">
                           <label>
                               <span class="text-danger">*</span> Client Name
                           </label>
                           <div class="input-group">
                               <div class="input-group-prepend">
                                   <span class="input-group-text"><i class="fas fa-building"></i></span>
                               </div>
                               <input type="text" name="client_name" class="form-control <?php echo (!empty($client_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $client_name; ?>" placeholder="Enter company name">
                               <span class="invalid-feedback"><?php echo $client_name_err; ?></span>
                           </div>
                       </div>
                       
                       <div class="form-group">
                           <label>
                               <span class="text-danger">*</span> Address
                           </label>
                           <div class="input-group">
                               <div class="input-group-prepend">
                                   <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                               </div>
                               <textarea name="address" class="form-control <?php echo (!empty($address_err)) ? 'is-invalid' : ''; ?>" rows="3" placeholder="Enter complete address"><?php echo $address; ?></textarea>
                               <span class="invalid-feedback"><?php echo $address_err; ?></span>
                           </div>
                           <small class="form-text text-muted">Include street address, city, state/province, and postal code</small>
                       </div>
                   </div>
               </div>
               
               <!-- Contact Person Section -->
               <div class="card mb-4">
                   <div class="card-header bg-light">
                       <h6 class="m-0 font-weight-bold text-primary">
                           <i class="fas fa-user mr-2"></i>Contact Person Information
                           <span class="badge badge-warning ml-2">Required</span>
                       </h6>
                       <small class="text-muted">Details about the primary contact person</small>
                   </div>
                   <div class="card-body">
                       <div class="form-group">
                           <label>
                               <span class="text-danger">*</span> PIC Name
                           </label>
                           <div class="input-group">
                               <div class="input-group-prepend">
                                   <span class="input-group-text"><i class="fas fa-user"></i></span>
                               </div>
                               <input type="text" name="pic_name" class="form-control <?php echo (!empty($pic_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $pic_name; ?>" placeholder="Enter contact person's full name">
                               <span class="invalid-feedback"><?php echo $pic_name_err; ?></span>
                           </div>
                       </div>
                       
                       <div class="form-row">
                           <div class="form-group col-md-6">
                               <label>
                                   <span class="text-danger">*</span> PIC Contact
                               </label>
                               <div class="input-group">
                                   <div class="input-group-prepend">
                                       <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                   </div>
                                   <input type="text" name="pic_contact" class="form-control <?php echo (!empty($pic_contact_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $pic_contact; ?>" placeholder="Enter phone number">
                                   <span class="invalid-feedback"><?php echo $pic_contact_err; ?></span>
                               </div>
                           </div>
                           
                           <div class="form-group col-md-6">
                               <label>
                                   <span class="text-danger">*</span> PIC Email
                               </label>
                               <div class="input-group">
                                   <div class="input-group-prepend">
                                       <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                   </div>
                                   <input type="email" name="pic_email" class="form-control <?php echo (!empty($pic_email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $pic_email; ?>" placeholder="Enter email address">
                                   <span class="invalid-feedback"><?php echo $pic_email_err; ?></span>
                               </div>
                           </div>
                       </div>
                   </div>
               </div>
               
               <!-- Form Actions -->
               <div class="form-group text-center">
                   <button type="submit" class="btn btn-primary btn-lg px-5">
                       <i class="fas fa-save mr-2"></i>Save Client
                   </button>
                   <a href="index.php" class="btn btn-secondary btn-lg ml-2 px-5">
                       <i class="fas fa-times mr-2"></i>Cancel
                   </a>
               </div>
           </form>
       </div>
   </div>
</div>

<?php
// Include footer
include_once $basePath . "includes/footer.php";
ob_end_flush();
?>
