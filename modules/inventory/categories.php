<?php
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
    header("location: index.php");
    exit;
}

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
                    header("location: categories.php?success=deleted");
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
            header("location: categories.php");
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
                    header("location: categories.php?success=updated");
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
                    header("location: categories.php?success=added");
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Categories - SOA Management System</title>

    <!-- Modern CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/modern-dashboard.css">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Animation -->
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
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
                        <h1>Inventory Categories</h1>
                        <p>Manage inventory item categories and classifications</p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="index.php" class="date-picker-btn">
                        <i class="fas fa-arrow-left"></i>
                        Back to Inventory
                    </a>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Success/Error Messages -->
            <?php if(isset($_GET["success"])): ?>
                <div class="alert alert-success" data-aos="fade-down">
                    <div class="alert-content">
                        <i class="fas fa-check-circle"></i>
                        <span>
                            <?php
                            if($_GET["success"] == "deleted") {
                                echo "Category has been deleted successfully.";
                            } elseif($_GET["success"] == "updated") {
                                echo "Category has been updated successfully.";
                            } elseif($_GET["success"] == "added") {
                                echo "Category has been added successfully.";
                            }
                            ?>
                        </span>
                    </div>
                    <button class="alert-close" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if(isset($delete_err)): ?>
                <div class="alert alert-error" data-aos="fade-down">
                    <div class="alert-content">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $delete_err; ?></span>
                    </div>
                    <button class="alert-close" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Main Content Grid -->
            <div class="tables-grid" data-aos="fade-up">
                <!-- Category Form (Left Side) -->
                <div>
                    <div class="table-card">
                        <div class="table-header">
                            <div class="table-title">
                                <h3><?php echo $edit_mode ? '<i class="fas fa-edit"></i> Edit Category' : '<i class="fas fa-plus"></i> Add New Category'; ?></h3>
                                <p><?php echo $edit_mode ? 'Update category information' : 'Create a new inventory category'; ?></p>
                            </div>
                        </div>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($edit_mode ? "?action=edit&id=".$edit_id : "")); ?>" method="post" class="form-container">
                            <div class="form-section">
                                <div class="form-group full-width">
                                    <label class="form-label">Category Name <span class="required">*</span></label>
                                    <input type="text" name="category_name" class="form-input <?php echo (!empty($category_name_err)) ? 'input-error' : ''; ?>" value="<?php echo $category_name; ?>" placeholder="Enter category name">
                                    <?php if(!empty($category_name_err)): ?>
                                        <span class="error-message"><?php echo $category_name_err; ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group full-width">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-input form-textarea" placeholder="Optional category description" style="min-height: 100px;"><?php echo $description; ?></textarea>
                                    <small class="field-hint">Provide additional details about this category</small>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-primary-large">
                                    <i class="fas fa-save"></i>
                                    <?php echo $edit_mode ? "Update Category" : "Add Category"; ?>
                                </button>
                                <?php if($edit_mode): ?>
                                    <a href="categories.php" class="btn-secondary-large">
                                        <i class="fas fa-times"></i>
                                        Cancel
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Categories List (Right Side) -->
                <div>
                    <div class="table-card">
                        <div class="table-header">
                            <div class="table-title">
                                <h3>Category List</h3>
                                <p><?php echo count($categories); ?> categories in total</p>
                            </div>
                            <div class="table-actions">
                                <div class="filter-dropdown" style="display: inline-block; margin-right: 10px;">
                                    <input type="text" id="searchInput" class="filter-select" placeholder="Search categories..." style="width: 200px;">
                                </div>
                                <button class="table-action-btn" onclick="location.reload()" title="Refresh">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        <div class="table-container">
                            <table class="modern-table" id="categoryTable">
                                <thead>
                                    <tr>
                                        <th style="width: 10%;">ID</th>
                                        <th style="width: 30%;">Category Name</th>
                                        <th style="width: 35%;">Description</th>
                                        <th style="width: 10%;">Items</th>
                                        <th style="width: 15%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($categories)): ?>
                                        <?php foreach($categories as $category): ?>
                                        <tr>
                                            <td class="font-medium">#<?php echo $category['category_id']; ?></td>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                    <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white;">
                                                        <i class="fas fa-tag"></i>
                                                    </div>
                                                    <span style="font-weight: 600;"><?php echo htmlspecialchars($category['category_name']); ?></span>
                                                </div>
                                            </td>
                                            <td style="color: var(--gray-600); font-size: 0.875rem;">
                                                <?php echo htmlspecialchars($category['description'] ?: 'No description'); ?>
                                            </td>
                                            <td>
                                                <span class="status-badge <?php echo ($category['item_count'] > 0) ? 'status-assigned' : 'status-disposed'; ?>">
                                                    <?php echo $category['item_count']; ?> items
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="categories.php?action=edit&id=<?php echo $category['category_id']; ?>" class="action-btn action-btn-edit" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if($category['item_count'] == 0): ?>
                                                    <button onclick="deleteCategory(<?php echo $category['category_id']; ?>, '<?php echo htmlspecialchars($category['category_name']); ?>')" class="action-btn action-btn-delete" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php else: ?>
                                                    <button class="action-btn" disabled title="Cannot delete category with items" style="background: var(--gray-100); color: var(--gray-400); cursor: not-allowed;">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center no-data">
                                            <div class="no-data-content">
                                                <i class="fas fa-tags"></i>
                                                <h3>No Categories Found</h3>
                                                <p>Start by adding your first category using the form on the left.</p>
                                            </div>
                                        </td>
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

        // Simple search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const table = document.getElementById('categoryTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
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

        // Delete category function
        function deleteCategory(id, name) {
            if (confirm(`Are you sure you want to delete the category "${name}"? This action cannot be undone.`)) {
                window.location.href = `categories.php?action=delete&id=${id}`;
            }
        }
    </script>

    <style>
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

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border-color: var(--success-color);
            color: var(--success-color);
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

        /* Filter Select */
        .filter-select {
            padding: 0.5rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius-sm);
            font-size: 0.875rem;
            color: var(--gray-700);
            background: white;
            transition: var(--transition);
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: var(--border-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.875rem;
            text-decoration: none;
        }

        .action-btn-edit {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .action-btn-edit:hover {
            background: var(--warning-color);
            color: white;
        }

        .action-btn-delete {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .action-btn-delete:hover {
            background: var(--danger-color);
            color: white;
        }

        /* No Data Styles */
        .no-data {
            padding: 3rem !important;
        }

        .no-data-content {
            text-align: center;
        }

        .no-data-content i {
            font-size: 3rem;
            color: var(--gray-300);
            margin-bottom: 1rem;
        }

        .no-data-content h3 {
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        .no-data-content p {
            color: var(--gray-500);
        }
    </style>
</body>
</html>
