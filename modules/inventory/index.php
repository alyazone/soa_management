<?php
ob_start();
// Set the base path for includes
$basePath = '../../';

// Include header and sidebar
include_once $basePath . "includes/header.php";
include_once $basePath . "includes/sidebar.php";

// Check if the user has admin privileges
if($_SESSION['position'] != 'Admin'){
    echo '<div class="col-md-10 ml-sm-auto px-4"><div class="alert alert-danger mt-3">You do not have permission to access this page.</div></div>';
    include_once $basePath . "includes/footer.php";
    exit;
}


// Include database connection
require_once $basePath . "config/database.php";

// Fetch inventory items with category and supplier details
try {
    $stmt = $pdo->query("SELECT i.*, c.category_name, s.supplier_name 
                         FROM inventory_items i 
                         LEFT JOIN inventory_categories c ON i.category_id = c.category_id 
                         LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id 
                         ORDER BY i.item_name");
    $items = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Process delete operation
if(isset($_GET["action"]) && $_GET["action"] == "delete" && isset($_GET["id"]) && !empty($_GET["id"])){
    try {
        // Check if item has associated transactions
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM inventory_transactions WHERE item_id = :id");
        $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        
        if($result['count'] > 0) {
            $delete_err = "Cannot delete item because it has associated transactions.";
        } else {
            // Prepare a delete statement
            $sql = "DELETE FROM inventory_items WHERE item_id = :id";
            
            if($stmt = $pdo->prepare($sql)){
                // Bind variables to the prepared statement as parameters
                $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
                
                // Set parameters
                $param_id = trim($_GET["id"]);
                
                // Attempt to execute the prepared statement
                if($stmt->execute()){
                    // Records deleted successfully. Redirect to landing page
                    header("location: index.php?success=1");
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
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Inventory Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group mr-2">
                <a href="add.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> Add New Item
                </a>
                <a href="categories.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-tags"></i> Manage Categories
                </a>
                <a href="export.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-file-export"></i> Export
                </a>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
                <i class="fas fa-filter"></i> Filter
            </button>
            <div class="dropdown-menu">
                <a class="dropdown-item" href="index.php?status=Available">Available Items</a>
                <a class="dropdown-item" href="index.php?status=Assigned">Assigned Items</a>
                <a class="dropdown-item" href="index.php?status=Maintenance">Items in Maintenance</a>
                <a class="dropdown-item" href="index.php?status=Disposed">Disposed Items</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="index.php">Show All</a>
            </div>
        </div>
    </div>
    
    <!-- for display message -->
    <?php if(isset($_GET["success"])): ?>
            <div class="alert alert-success">
                <?php 
                    if($_GET["success"] == "1") {
                        echo "Inventory item has been deleted successfully.";
                    } elseif($_GET["success"] == "2") {
                        echo "Inventory item has been updated successfully.";
                    } elseif($_GET["success"] == "3") {
                        echo "Inventory item has been added successfully.";
                    }
                ?>
            </div>
    <?php endif; ?>
    
    <?php if(isset($delete_err)): ?>
        <div class="alert alert-danger">
            <?php echo $delete_err; ?>
        </div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Inventory Items</h6>
            <div class="input-group w-25">
                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search items...">
                <div class="input-group-append">
                    <button class="btn btn-sm btn-outline-secondary" type="button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="inventoryTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Supplier</th>
                            <th>Serial Number</th>
                            <th>Purchase Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['item_id']); ?></td>
                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['supplier_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['serial_number']); ?></td>
                            <td><?php echo htmlspecialchars($item['purchase_date']); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo ($item['status'] == 'Available') ? 'success' : 
                                        (($item['status'] == 'Assigned') ? 'primary' : 
                                        (($item['status'] == 'Maintenance') ? 'warning' : 'secondary')); 
                                ?>">
                                    <?php echo htmlspecialchars($item['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="view.php?id=<?php echo $item['item_id']; ?>" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit.php?id=<?php echo $item['item_id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="transactions.php?id=<?php echo $item['item_id']; ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-exchange-alt"></i>
                                </a>
                                <a href="index.php?action=delete&id=<?php echo $item['item_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this item?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($items)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No inventory items found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Dashboard Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Items</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($items); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Available Items</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                    $available = array_filter($items, function($item) {
                                        return $item['status'] == 'Available';
                                    });
                                    echo count($available);
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Assigned Items</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                    $assigned = array_filter($items, function($item) {
                                        return $item['status'] == 'Assigned';
                                    });
                                    echo count($assigned);
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Maintenance Items</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                    $maintenance = array_filter($items, function($item) {
                                        return $item['status'] == 'Maintenance';
                                    });
                                    echo count($maintenance);
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tools fa-2x text-gray-300"></i>
                        </div>
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
    const table = document.getElementById('inventoryTable');
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
ob_end_flush();
?>
