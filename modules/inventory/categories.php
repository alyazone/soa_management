<?php
// At the top of the file, right after the opening <?php tag, add:
ob_start();

// Set the base path for includes
$basePath = '../../';

// Include header and sidebar
include_once $basePath . "includes/header.php";
include_once $basePath . "includes/sidebar.php";

// Include database connection
require_once $basePath . "config/database.php";

// Define variables and initialize with empty values
$category_name = $description = "";
$category_name_err = $description_err = "";
$edit_mode = false;
$edit_id = 0;

// Process delete operation
if(isset($_GET["action"]) && $_GET["action"] == "delete" && isset($_GET["id"]) && !empty($_GET["id"])){
    try {
        // Check if category has associated items
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM inventory_items WHERE category_id = :id");
        $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        
        if($result['count'] > 0) {
            $delete_err = "Cannot delete category because it has associated inventory items.";
        } else {
            // Prepare a delete statement
            $sql = "DELETE FROM inventory_categories WHERE category_id = :id";
            
            if($stmt = $pdo->prepare($sql)){
                // Bind variables to the prepared statement as parameters
                $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
                
                // Set parameters
                $param_id = trim($_GET["id"]);
                
                // Attempt to execute the prepared statement
                if($stmt->execute()){
                    // Records deleted successfully. Redirect to landing page
                    echo "<script>window.location.href = 'categories.php?success=1';</script>";
                    exit();
                } else{
                    $delete_err = "Oops! Something went wrong. Please try again later.";
                }
            }
        }
    } catch(PDOException $e) {
        $delete_err = "Error: " . $e->getMessage();
    }
}

// Check if we're in edit mode
if(isset($_GET["action"]) && $_GET["action"] == "edit" && isset($_GET["id"]) && !empty($_GET["id"])){
    $edit_mode = true;
    $edit_id = $_GET["id"];
    
    // Fetch category data
    try {
        $stmt = $pdo->prepare("SELECT * FROM inventory_categories WHERE category_id = :id");
        $stmt->bindParam(":id", $edit_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if($stmt->rowCount() == 1){
            $category = $stmt->fetch();
            $category_name = $category['category_name'];
            $description = $category['description'];
        } else {
            // Category not found
            echo "<script>window.location.href = 'categories.php';</script>";
            exit();
        }
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate category name
    if(empty(trim($_POST["category_name"]))){
        $category_name_err = "Please enter category name.";
    } else{
        $category_name = trim($_POST["category_name"]);
        
        // Check if category name already exists (excluding current record if in edit mode)
        $sql = "SELECT category_id FROM inventory_categories WHERE category_name = :category_name";
        if($edit_mode){
            $sql .= " AND category_id != :id";
        }
        
        if($stmt = $pdo->prepare($sql)){
            $stmt->bindParam(":category_name", $param_category_name, PDO::PARAM_STR);
            if($edit_mode){
                $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
                $param_id = $edit_id;
            }
            $param_category_name = $category_name;
            
            if($stmt->execute()){
                if($stmt->rowCount() > 0){
                    $category_name_err = "This category name already exists.";
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            
            unset($stmt);
        }
    }
    
    // Validate description (optional)
    $description = trim($_POST["description"]);
    
    // Check input errors before inserting/updating in database
    if(empty($category_name_err)){
        if($edit_mode){
            // Prepare an update statement
            $sql = "UPDATE inventory_categories SET category_name = :category_name, description = :description WHERE category_id = :id";
            
            if($stmt = $pdo->prepare($sql)){
                // Bind variables to the prepared statement as parameters
                $stmt->bindParam(":category_name", $param_category_name, PDO::PARAM_STR);
                $stmt->bindParam(":description", $param_description, PDO::PARAM_STR);
                $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
                
                // Set parameters
                $param_category_name = $category_name;
                $param_description = $description;
                $param_id = $edit_id;
                
                // Attempt to execute the prepared statement
                if($stmt->execute()){
                    // Records updated successfully. Redirect to landing page
                    echo "<script>window.location.href = 'categories.php?success=2';</script>";
                    exit();
                } else{
                    echo "Oops! Something went wrong. Please try again later.";
                }
            }
        } else {
            // Prepare an insert statement
            $sql = "INSERT INTO inventory_categories (category_name, description) VALUES (:category_name, :description)";
            
            if($stmt = $pdo->prepare($sql)){
                // Bind variables to the prepared statement as parameters
                $stmt->bindParam(":category_name", $param_category_name, PDO::PARAM_STR);
                $stmt->bindParam(":description", $param_description, PDO::PARAM_STR);
                
                // Set parameters
                $param_category_name = $category_name;
                $param_description = $description;
                
                // Attempt to execute the prepared statement
                if($stmt->execute()){
                    // Records created successfully. Redirect to landing page
                    echo "<script>window.location.href = 'categories.php?success=3';</script>";
                    exit();
                } else{
                    echo "Oops! Something went wrong. Please try again later.";
                }
            }
        }
        
        // Close statement
        unset($stmt);
    }
}

// Fetch all categories
try {
    $stmt = $pdo->query("SELECT c.*, 
                         (SELECT COUNT(*) FROM inventory_items WHERE category_id = c.category_id) as item_count 
                         FROM inventory_categories c 
                         ORDER BY c.category_name");
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Inventory Categories</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="index.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Inventory
            </a>
        </div>
    </div>
    
    <?php if(isset($_GET["success"])): ?>
        <div class="alert alert-success">
            <?php 
                if($_GET["success"] == 1) echo "Category has been deleted successfully.";
                elseif($_GET["success"] == 2) echo "Category has been updated successfully.";
                elseif($_GET["success"] == 3) echo "Category has been added successfully.";
            ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($delete_err)): ?>
        <div class="alert alert-danger">
            <?php echo $delete_err; ?>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Category Form -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <?php echo $edit_mode ? "Edit Category" : "Add New Category"; ?>
                    </h6>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($edit_mode ? "?action=edit&id=".$edit_id : "")); ?>" method="post">
                        <div class="form-group">
                            <label>Category Name</label>
                            <input type="text" name="category_name" class="form-control <?php echo (!empty($category_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $category_name; ?>">
                            <span class="invalid-feedback"><?php echo $category_name_err; ?></span>
                        </div>
                        
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo $description; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo $edit_mode ? "Update" : "Save"; ?>
                            </button>
                            <?php if($edit_mode): ?>
                                <a href="categories.php" class="btn btn-secondary">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Categories List -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Category List</h6>
                    <div class="input-group w-50">
                        <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search categories...">
                        <div class="input-group-append">
                            <button class="btn btn-sm btn-outline-secondary" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="categoryTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Category Name</th>
                                    <th>Description</th>
                                    <th>Items Count</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($categories as $category): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category['category_id']); ?></td>
                                    <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($category['description']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo ($category['item_count'] > 0) ? 'primary' : 'secondary'; ?>">
                                            <?php echo $category['item_count']; ?> items
                                        </span>
                                    </td>
                                    <td>
                                        <a href="categories.php?action=edit&id=<?php echo $category['category_id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if($category['item_count'] == 0): ?>
                                        <a href="categories.php?action=delete&id=<?php echo $category['category_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this category?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php else: ?>
                                        <button class="btn btn-danger btn-sm" disabled title="Cannot delete category with associated items">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($categories)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No categories found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Simple search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase();
    const table = document.getElementById('categoryTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        let found = false;
        const cells = rows[i].getElementsByTagName('td');
        
        for (let j = 0; j < cells.length; j++) {
            const cellText = cells[j].textContent || cells[j].innerText;
            
            if (cellText.toLowerCase().indexOf(searchValue) > -1) {
                found = true;
                break;
            }
        }
        
        rows[i].style.display = found ? '' : 'none';
    }
});
</script>

<?php
// Include footer
include_once $basePath . "includes/footer.php";

// Then at the very end of the file, after the include footer line, add:
ob_end_flush();
?>
