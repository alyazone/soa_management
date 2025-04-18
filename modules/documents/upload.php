<?php
// Set the base path for includes
$basePath = '../../';

// Include header and sidebar
include_once $basePath . "includes/header.php";
include_once $basePath . "includes/sidebar.php";

// Include database connection
require_once $basePath . "config/database.php";

// Define variables and initialize with empty values
$document_type = $reference_type = $reference_id = $description = "";
$document_type_err = $reference_type_err = $reference_id_err = $file_err = $description_err = "";

// Fetch clients, suppliers, staff, and SOAs for dropdown
try {
    $stmt = $pdo->query("SELECT client_id, client_name FROM clients ORDER BY client_name");
    $clients = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name");
    $suppliers = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT staff_id, full_name FROM staff ORDER BY full_name");
    $staff_members = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT soa_id, account_number FROM soa ORDER BY issue_date DESC");
    $soas = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate document type
    if(empty($_POST["document_type"])){
        $document_type_err = "Please select document type.";
    } else{
        $document_type = $_POST["document_type"];
    }
    
    // Validate reference type
    if(empty($_POST["reference_type"])){
        $reference_type_err = "Please select reference type.";
    } else{
        $reference_type = $_POST["reference_type"];
    }
    
    // Validate reference ID
    if(empty($_POST["reference_id"])){
        $reference_id_err = "Please select reference.";
    } else{
        $reference_id = $_POST["reference_id"];
    }
    
    // Validate description
    if(empty($_POST["description"])){
        $description_err = "Please enter description.";
    } else{
        $description = $_POST["description"];
    }
    
    // Validate file upload
    if(empty($_FILES["document_file"]["name"])){
        $file_err = "Please select a file to upload.";
    } else {
        $allowed_ext = array("pdf", "doc", "docx", "jpg", "jpeg", "png", "gif");
        $file_name = $_FILES["document_file"]["name"];
        $file_size = $_FILES["document_file"]["size"];
        $file_tmp = $_FILES["document_file"]["tmp_name"];
        $file_type = $_FILES["document_file"]["type"];
        
        $file_ext_arr = explode('.', $file_name);
        $file_ext = strtolower(end($file_ext_arr));
        
        if(!in_array($file_ext, $allowed_ext)){
            $file_err = "Extension not allowed, please choose a PDF, DOC, DOCX, JPG, JPEG, PNG or GIF file.";
        }
        
        if($file_size > 5242880){ // 5MB
            $file_err = "File size must be less than 5 MB.";
        }
    }
    
    // Check input errors before inserting in database
    if(empty($document_type_err) && empty($reference_type_err) && empty($reference_id_err) && empty($description_err) && empty($file_err)){
        // Create upload directory if it doesn't exist
        $upload_dir = "uploads/" . strtolower($document_type) . "s/";
        if(!file_exists($basePath . $upload_dir)){
            mkdir($basePath . $upload_dir, 0777, true);
        }
        
        // Generate unique file name
        $new_file_name = uniqid() . '.' . $file_ext;
        $upload_path = $upload_dir . $new_file_name;
        
        if(move_uploaded_file($file_tmp, $basePath . $upload_path)){
            try {
                // Prepare an insert statement
                $sql = "INSERT INTO documents (document_type, reference_id, reference_type, file_name, file_path, description, uploaded_by) VALUES (:document_type, :reference_id, :reference_type, :file_name, :file_path, :description, :uploaded_by)";
                 
                if($stmt = $pdo->prepare($sql)){
                    // Bind variables to the prepared statement as parameters
                    $stmt->bindParam(":document_type", $param_document_type, PDO::PARAM_STR);
                    $stmt->bindParam(":reference_id", $param_reference_id, PDO::PARAM_INT);
                    $stmt->bindParam(":reference_type", $param_reference_type, PDO::PARAM_STR);
                    $stmt->bindParam(":file_name", $param_file_name, PDO::PARAM_STR);
                    $stmt->bindParam(":file_path", $param_file_path, PDO::PARAM_STR);
                    $stmt->bindParam(":description", $param_description, PDO::PARAM_STR);
                    $stmt->bindParam(":uploaded_by", $param_uploaded_by, PDO::PARAM_INT);
                    
                    // Set parameters
                    $param_document_type = $document_type;
                    $param_reference_id = $reference_id;
                    $param_reference_type = $reference_type;
                    $param_file_name = $file_name;
                    $param_file_path = $upload_path;
                    $param_description = $description;
                    $param_uploaded_by = $_SESSION["staff_id"];
                    
                    // Attempt to execute the prepared statement
                    if($stmt->execute()){
                        // Records created successfully. Redirect to landing page
                        header("location: index.php");
                        exit();
                    } else{
                        echo "Oops! Something went wrong. Please try again later.";
                    }
                }
            } catch(PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
        } else {
            $file_err = "Failed to upload file.";
        }
    }
}
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Upload Document</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="index.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Document Information</h6>
        </div>
        <div class="card-body">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Document Type</label>
                    <select name="document_type" class="form-control <?php echo (!empty($document_type_err)) ? 'is-invalid' : ''; ?>">
                        <option value="">Select Document Type</option>
                        <option value="Receipt" <?php echo ($document_type == "Receipt") ? 'selected' : ''; ?>>Receipt</option>
                        <option value="Invoice" <?php echo ($document_type == "Invoice") ? 'selected' : ''; ?>>Invoice</option>
                        <option value="Warranty" <?php echo ($document_type == "Warranty") ? 'selected' : ''; ?>>Warranty</option>
                        <option value="Claim" <?php echo ($document_type == "Claim") ? 'selected' : ''; ?>>Claim</option>
                    </select>
                    <span class="invalid-feedback"><?php echo $document_type_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Reference Type</label>
                    <select name="reference_type" id="reference_type" class="form-control <?php echo (!empty($reference_type_err)) ? 'is-invalid' : ''; ?>">
                        <option value="">Select Reference Type</option>
                        <option value="Client" <?php echo ($reference_type == "Client") ? 'selected' : ''; ?>>Client</option>
                        <option value="Supplier" <?php echo ($reference_type == "Supplier") ? 'selected' : ''; ?>>Supplier</option>
                        <option value="Staff" <?php echo ($reference_type == "Staff") ? 'selected' : ''; ?>>Staff</option>
                        <option value="SOA" <?php echo ($reference_type == "SOA") ? 'selected' : ''; ?>>SOA</option>
                    </select>
                    <span class="invalid-feedback"><?php echo $reference_type_err; ?></span>
                </div>
                
                <!-- Dynamic Reference ID dropdown based on Reference Type -->
                <div class="form-group" id="client_reference" style="display: <?php echo ($reference_type == 'Client') ? 'block' : 'none'; ?>">
                    <label>Client</label>
                    <select name="reference_id" class="form-control reference-select <?php echo (!empty($reference_id_err)) ? 'is-invalid' : ''; ?>">
                        <option value="">Select Client</option>
                        <?php foreach($clients as $client): ?>
                            <option value="<?php echo $client['client_id']; ?>" <?php echo ($reference_type == 'Client' && $reference_id == $client['client_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($client['client_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="invalid-feedback"><?php echo $reference_id_err; ?></span>
                </div>
                
                <div class="form-group" id="supplier_reference" style="display: <?php echo ($reference_type == 'Supplier') ? 'block' : 'none'; ?>">
                    <label>Supplier</label>
                    <select name="reference_id" class="form-control reference-select <?php echo (!empty($reference_id_err)) ? 'is-invalid' : ''; ?>">
                        <option value="">Select Supplier</option>
                        <?php foreach($suppliers as $supplier): ?>
                            <option value="<?php echo $supplier['supplier_id']; ?>" <?php echo ($reference_type == 'Supplier' && $reference_id == $supplier['supplier_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="invalid-feedback"><?php echo $reference_id_err; ?></span>
                </div>
                
                <div class="form-group" id="staff_reference" style="display: <?php echo ($reference_type == 'Staff') ? 'block' : 'none'; ?>">
                    <label>Staff</label>
                    <select name="reference_id" class="form-control reference-select <?php echo (!empty($reference_id_err)) ? 'is-invalid' : ''; ?>">
                        <option value="">Select Staff</option>
                        <?php foreach($staff_members as $staff_member): ?>
                            <option value="<?php echo $staff_member['staff_id']; ?>" <?php echo ($reference_type == 'Staff' && $reference_id == $staff_member['staff_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($staff_member['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="invalid-feedback"><?php echo $reference_id_err; ?></span>
                </div>
                
                <div class="form-group" id="soa_reference" style="display: <?php echo ($reference_type == 'SOA') ? 'block' : 'none'; ?>">
                    <label>SOA</label>
                    <select name="reference_id" class="form-control reference-select <?php echo (!empty($reference_id_err)) ? 'is-invalid' : ''; ?>">
                        <option value="">Select SOA</option>
                        <?php foreach($soas as $soa): ?>
                            <option value="<?php echo $soa['soa_id']; ?>" <?php echo ($reference_type == 'SOA' && $reference_id == $soa['soa_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($soa['account_number']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="invalid-feedback"><?php echo $reference_id_err; ?></span>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>" rows="3"><?php echo $description; ?></textarea>
                    <span class="invalid-feedback"><?php echo $description_err; ?></span>
                </div>
                
                <div class="form-group">
                    <label>File</label>
                    <input type="file" name="document_file" class="form-control-file <?php echo (!empty($file_err)) ? 'is-invalid' : ''; ?>">
                    <small class="form-text text-muted">Allowed file types: PDF, DOC, DOCX, JPG, JPEG, PNG, GIF. Maximum file size: 5MB.</small>
                    <span class="invalid-feedback"><?php echo $file_err; ?></span>
                </div>
                
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Upload">
                    <a href="index.php" class="btn btn-secondary ml-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle reference type change
    document.getElementById('reference_type').addEventListener('change', function() {
        // Hide all reference selects
        document.querySelectorAll('.reference-select').forEach(function(select) {
            select.name = '';
        });
        
        // Hide all reference divs
        document.getElementById('client_reference').style.display = 'none';
        document.getElementById('supplier_reference').style.display = 'none';
        document.getElementById('staff_reference').style.display = 'none';
        document.getElementById('soa_reference').style.display = 'none';
        
        // Show the selected reference div
        var selectedType = this.value;
        if(selectedType) {
            document.getElementById(selectedType.toLowerCase() + '_reference').style.display = 'block';
            document.querySelector('#' + selectedType.toLowerCase() + '_reference select').name = 'reference_id';
        }
    });
});
</script>

<?php
// Include footer
include_once $basePath . "includes/footer.php";
?>
