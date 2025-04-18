<?php
// Set the base path for includes
$basePath = '../../';

// Include header and sidebar
include_once $basePath . "includes/header.php";
include_once $basePath . "includes/sidebar.php";

// Include database connection
require_once $basePath . "config/database.php";

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
        $pic_email = trim($_POST["pic_email"]);
    }
    
    // Check input errors before updating the database
    if(empty($client_name_err) && empty($address_err) && empty($pic_name_err) && empty($pic_contact_err) && empty($pic_email_err)){
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
        die("ERROR: Could not fetch client. " . $e->getMessage());
    }
}
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Edit Client</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="view.php?id=<?php echo $_GET['id']; ?>" class="btn btn-sm btn-info mr-2">
                <i class="fas fa-eye"></i> View Client
            </a>
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
                <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
                <div class="form-group">
                    <label>Client Name</label>
                    <input type="text" name="client_name" class="form-control <?php echo (!empty($client_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $client_name; ?>">
                    <span class="invalid-feedback"><?php echo $client_name_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" class="form-control <?php echo (!empty($address_err)) ? 'is-invalid' : ''; ?>" rows="3"><?php echo $address; ?></textarea>
                    <span class="invalid-feedback"><?php echo $address_err; ?></span>
                </div>
                <div class="form-group">
                    <label>PIC Name</label>
                    <input type="text" name="pic_name" class="form-control <?php echo (!empty($pic_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $pic_name; ?>">
                    <span class="invalid-feedback"><?php echo $pic_name_err; ?></span>
                </div>
                <div class="form-group">
                    <label>PIC Contact</label>
                    <input type="text" name="pic_contact" class="form-control <?php echo (!empty($pic_contact_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $pic_contact; ?>">
                    <span class="invalid-feedback"><?php echo $pic_contact_err; ?></span>
                </div>
                <div class="form-group">
                    <label>PIC Email</label>
                    <input type="email" name="pic_email" class="form-control <?php echo (!empty($pic_email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $pic_email; ?>">
                    <span class="invalid-feedback"><?php echo $pic_email_err; ?></span>
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
