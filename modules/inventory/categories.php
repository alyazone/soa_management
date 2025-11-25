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
$category_name_err = "";
$edit_mode = false;
$edit_category_id = null;

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Check if this is an edit or add action
    if(isset($_POST["action"])){
        if($_POST["action"] == "add" || $_POST["action"] == "edit"){
            // Validate category name
            if(empty(trim($_POST["category_name"]))){
                $category_name_err = "Please enter category name.";
            } else{
                // Check if category name already exists (exclude current category in edit mode)
                $check_sql = "SELECT category_id FROM inventory_categories WHERE category_name = :category_name";
                if($_POST["action"] == "edit" && !empty($_POST["category_id"])){
                    $check_sql .= " AND category_id != :category_id";
                }

                if($stmt = $pdo->prepare($check_sql)){
                    $stmt->bindParam(":category_name", $param_category_name, PDO::PARAM_STR);
                    $param_category_name = trim($_POST["category_name"]);

                    if($_POST["action"] == "edit" && !empty($_POST["category_id"])){
                        $stmt->bindParam(":category_id", $_POST["category_id"], PDO::PARAM_INT);
                    }

                    if($stmt->execute()){
                        if($stmt->rowCount() > 0){
                            $category_name_err = "This category name already exists.";
                        } else{
                            $category_name = trim($_POST["category_name"]);
                        }
                    }
                    unset($stmt);
                }
            }

            // Get description (optional)
            $description = trim($_POST["description"]);

            // Check input errors before inserting/updating in database
            if(empty($category_name_err)){
                if($_POST["action"] == "add"){
                    // Prepare an insert statement
                    $sql = "INSERT INTO inventory_categories (category_name, description) VALUES (:category_name, :description)";

                    if($stmt = $pdo->prepare($sql)){
                        $stmt->bindParam(":category_name", $param_category_name, PDO::PARAM_STR);
                        $stmt->bindParam(":description", $param_description, PDO::PARAM_STR);

                        $param_category_name = $category_name;
                        $param_description = $description;

                        if($stmt->execute()){
                            header("location: categories.php?success=1");
                            exit();
                        } else{
                            echo "Oops! Something went wrong. Please try again later.";
                        }
                        unset($stmt);
                    }
                } else if($_POST["action"] == "edit" && !empty($_POST["category_id"])){
                    // Prepare an update statement
                    $sql = "UPDATE inventory_categories SET category_name = :category_name, description = :description WHERE category_id = :category_id";

                    if($stmt = $pdo->prepare($sql)){
                        $stmt->bindParam(":category_name", $param_category_name, PDO::PARAM_STR);
                        $stmt->bindParam(":description", $param_description, PDO::PARAM_STR);
                        $stmt->bindParam(":category_id", $param_category_id, PDO::PARAM_INT);

                        $param_category_name = $category_name;
                        $param_description = $description;
                        $param_category_id = $_POST["category_id"];

                        if($stmt->execute()){
                            header("location: categories.php?success=2");
                            exit();
                        } else{
                            echo "Oops! Something went wrong. Please try again later.";
                        }
                        unset($stmt);
                    }
                }
            }
        } else if($_POST["action"] == "delete" && !empty($_POST["category_id"])){
            // Check if category is being used
            $check_sql = "SELECT COUNT(*) as count FROM inventory_items WHERE category_id = :category_id";
            if($stmt = $pdo->prepare($check_sql)){
                $stmt->bindParam(":category_id", $_POST["category_id"], PDO::PARAM_INT);
                if($stmt->execute()){
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if($row['count'] > 0){
                        header("location: categories.php?error=1");
                        exit();
                    }
                }
                unset($stmt);
            }

            // Delete category
            $sql = "DELETE FROM inventory_categories WHERE category_id = :category_id";
            if($stmt = $pdo->prepare($sql)){
                $stmt->bindParam(":category_id", $_POST["category_id"], PDO::PARAM_INT);
                if($stmt->execute()){
                    header("location: categories.php?success=3");
                    exit();
                }
                unset($stmt);
            }
        }
    }
}

// Check if editing
if(isset($_GET["edit"]) && !empty($_GET["edit"])){
    $edit_mode = true;
    $edit_category_id = $_GET["edit"];

    // Fetch category data
    $sql = "SELECT * FROM inventory_categories WHERE category_id = :category_id";
    if($stmt = $pdo->prepare($sql)){
        $stmt->bindParam(":category_id", $edit_category_id, PDO::PARAM_INT);
        if($stmt->execute()){
            if($stmt->rowCount() == 1){
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $category_name = $row["category_name"];
                $description = $row["description"];
            }
        }
        unset($stmt);
    }
}

// Fetch all categories
try {
    $stmt = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM inventory_items WHERE category_id = c.category_id) as item_count FROM inventory_categories c ORDER BY c.category_name");
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
    <title>Category Management - SOA Management System</title>

    <!-- Modern CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/modern-dashboard.css">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Animation -->
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    
    <!-- Added inline critical styles to override Tailwind's Preflight reset -->
    <style>
        /* Form Input Styles - Override Tailwind Preflight */
        .form-input,
        input.form-input,
        select.form-input,
        textarea.form-input,
        .form-container input[type="text"],
        .form-container input[type="date"],
        .form-container input[type="number"],
        .form-container input[type="email"],
        .form-container input[type="password"],
        .form-container input[type="tel"],
        .form-container select,
        .form-container textarea {
            display: block !important;
            width: 100% !important;
            padding: 0.75rem 1rem !important;
            font-size: 0.95rem !important;
            font-weight: 400 !important;
            line-height: 1.5 !important;
            color: #1e293b !important;
            background-color: #ffffff !important;
            background-clip: padding-box !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
        }

        .form-input:focus,
        input.form-input:focus,
        select.form-input:focus,
        textarea.form-input:focus,
        .form-container input:focus,
        .form-container select:focus,
        .form-container textarea:focus {
            border-color: #3b82f6 !important;
            outline: 0 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
        }

        .form-container select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") !important;
            background-position: right 0.75rem center !important;
            background-repeat: no-repeat !important;
            background-size: 1.25rem !important;
            padding-right: 2.5rem !important;
        }

        .form-container textarea {
            min-height: 120px !important;
            resize: vertical !important;
        }

        .form-container {
            padding: 1.5rem !important;
        }

        .form-section {
            background: #f8fafc !important;
            border-radius: 0.75rem !important;
            padding: 1.5rem !important;
            margin-bottom: 1.5rem !important;
            border: 1px solid #e2e8f0 !important;
        }

        .form-section-title {
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            font-weight: 600 !important;
            font-size: 1rem !important;
            color: #1e293b !important;
            margin-bottom: 1.25rem !important;
            padding-bottom: 0.75rem !important;
            border-bottom: 1px solid #e2e8f0 !important;
        }

        .form-section-title i {
            color: #3b82f6 !important;
        }

        .form-grid {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 1.25rem !important;
            margin-bottom: 1.25rem !important;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr !important;
            }
        }

        .form-group {
            display: flex !important;
            flex-direction: column !important;
        }

        .form-group.full-width {
            grid-column: span 2 !important;
        }

        @media (max-width: 768px) {
            .form-group.full-width {
                grid-column: span 1 !important;
            }
        }

        .form-label {
            display: block !important;
            font-weight: 500 !important;
            font-size: 0.875rem !important;
            color: #475569 !important;
            margin-bottom: 0.5rem !important;
        }

        .form-label .required {
            color: #ef4444 !important;
            margin-left: 0.25rem !important;
        }

        .input-error {
            border-color: #ef4444 !important;
        }

        .error-message {
            color: #ef4444 !important;
            font-size: 0.8rem !important;
            margin-top: 0.375rem !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.25rem !important;
        }

        .field-hint {
            color: #64748b !important;
            font-size: 0.8rem !important;
            margin-top: 0.375rem !important;
        }

        .form-actions {
            display: flex !important;
            gap: 1rem !important;
            padding-top: 1.5rem !important;
            border-top: 1px solid #e2e8f0 !important;
            margin-top: 0.5rem !important;
        }

        .btn-primary-large {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.5rem !important;
            padding: 0.875rem 2rem !important;
            font-size: 0.95rem !important;
            font-weight: 600 !important;
            color: #ffffff !important;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
            border: none !important;
            border-radius: 0.5rem !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3) !important;
        }

        .btn-primary-large:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 10px -1px rgba(59, 130, 246, 0.4) !important;
        }

        .btn-secondary-large {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.5rem !important;
            padding: 0.875rem 2rem !important;
            font-size: 0.95rem !important;
            font-weight: 600 !important;
            color: #475569 !important;
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.5rem !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            text-decoration: none !important;
        }

        .btn-secondary-large:hover {
            background: #f8fafc !important;
            border-color: #cbd5e1 !important;
            color: #1e293b !important;
        }

        @media (max-width: 640px) {
            .form-actions {
                flex-direction: column !important;
            }
            
            .btn-primary-large,
            .btn-secondary-large {
                width: 100% !important;
            }
        }
    </style>
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
                        <h1>Category Management</h1>
                        <p>Manage inventory categories</p>
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
            <!-- Alert Messages -->
            <?php if(isset($_GET["success"])): ?>
                <div class="alert alert-success" data-aos="fade-up">
                    <i class="fas fa-check-circle"></i>
                    <?php
                    if($_GET["success"] == 1) echo "Category added successfully!";
                    else if($_GET["success"] == 2) echo "Category updated successfully!";
                    else if($_GET["success"] == 3) echo "Category deleted successfully!";
                    ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET["error"])): ?>
                <div class="alert alert-danger" data-aos="fade-up">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php
                    if($_GET["error"] == 1) echo "Cannot delete category. It is being used by inventory items.";
                    ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Add/Edit Category Form -->
                <div class="lg:col-span-1">
                    <div class="table-card" data-aos="fade-up">
                        <div class="table-header">
                            <div class="table-title">
                                <h3><?php echo $edit_mode ? 'Edit Category' : 'Add New Category'; ?></h3>
                                <p><?php echo $edit_mode ? 'Update category information' : 'Create a new inventory category'; ?></p>
                            </div>
                        </div>

                        <form action="categories.php" method="post" class="form-container">
                            <input type="hidden" name="action" value="<?php echo $edit_mode ? 'edit' : 'add'; ?>">
                            <?php if($edit_mode): ?>
                                <input type="hidden" name="category_id" value="<?php echo $edit_category_id; ?>">
                            <?php endif; ?>

                            <div class="form-group" style="margin-bottom: 1.25rem;">
                                <label class="form-label">Category Name <span class="required">*</span></label>
                                <input type="text" name="category_name" class="form-input <?php echo (!empty($category_name_err)) ? 'input-error' : ''; ?>" value="<?php echo htmlspecialchars($category_name); ?>" placeholder="Enter category name">
                                <?php if(!empty($category_name_err)): ?>
                                    <span class="error-message"><?php echo $category_name_err; ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group" style="margin-bottom: 1.25rem;">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-input form-textarea" placeholder="Category description (optional)"><?php echo htmlspecialchars($description); ?></textarea>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-primary-large">
                                    <i class="fas fa-<?php echo $edit_mode ? 'save' : 'plus'; ?>"></i>
                                    <?php echo $edit_mode ? 'Update' : 'Add Category'; ?>
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

                <!-- Categories List -->
                <div class="lg:col-span-2">
                    <div class="table-card" data-aos="fade-up" data-aos-delay="100">
                        <div class="table-header">
                            <div class="table-title">
                                <h3>Categories List</h3>
                                <p>All inventory categories</p>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Category Name</th>
                                        <th>Description</th>
                                        <th>Items</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($categories) > 0): ?>
                                        <?php foreach($categories as $cat): ?>
                                            <tr>
                                                <td>
                                                    <span class="font-medium"><?php echo htmlspecialchars($cat['category_name']); ?></span>
                                                </td>
                                                <td>
                                                    <span class="text-gray-600"><?php echo !empty($cat['description']) ? htmlspecialchars($cat['description']) : '-'; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-info"><?php echo $cat['item_count']; ?> items</span>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="categories.php?edit=<?php echo $cat['category_id']; ?>" class="btn-action btn-edit" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if($cat['item_count'] == 0): ?>
                                                            <form action="categories.php" method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="category_id" value="<?php echo $cat['category_id']; ?>">
                                                                <button type="submit" class="btn-action btn-delete" title="Delete">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <button class="btn-action btn-delete" disabled title="Cannot delete - has items" style="opacity: 0.5; cursor: not-allowed;">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-8">
                                                <div class="empty-state">
                                                    <i class="fas fa-folder-open text-4xl text-gray-300 mb-2"></i>
                                                    <p class="text-gray-500">No categories found</p>
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
    </script>
</body>
</html>
