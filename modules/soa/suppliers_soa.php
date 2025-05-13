<?php
// Set the base path for includes
$basePath = '../../';

// Check if the user has admin privileges
if($_SESSION['position'] != 'Admin'){
    echo '<div class="col-md-10 ml-sm-auto px-4"><div class="alert alert-danger mt-3">You do not have permission to access this page.</div></div>';
    include_once $basePath . "includes/footer.php";
    exit;
}


// Include header and sidebar
include_once $basePath . "includes/header.php";
include_once $basePath . "includes/sidebar.php";

// Include database connection
require_once $basePath . "config/database.php";

// Fetch all suppliers with their total outstanding balance
try {
    $stmt = $pdo->query("SELECT 
                            sup.supplier_id, 
                            sup.supplier_name,
                            SUM(CASE WHEN s.status != 'Paid' THEN s.balance_amount ELSE 0 END) as outstanding_balance,
                            COUNT(s.soa_id) as total_soas
                         FROM suppliers sup
                         LEFT JOIN soa s ON sup.supplier_id = s.supplier_id
                         GROUP BY sup.supplier_id
                         ORDER BY sup.supplier_name");
    $suppliers = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Supplier SOAs</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="add.php" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> Create New SOA
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Supplier Payment Tracker</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Supplier</th>
                            <th>Total SOAs</th>
                            <th>Outstanding Balance (RM)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($suppliers as $supplier): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($supplier['supplier_name']); ?></td>
                            <td><?php echo htmlspecialchars($supplier['total_soas']); ?></td>
                            <td><?php echo number_format($supplier['outstanding_balance'], 2); ?></td>
                            <td>
                                <a href="generate_pdf.php?supplier_id=<?php echo $supplier['supplier_id']; ?>" class="btn btn-success btn-sm" target="_blank">
                                    <i class="fas fa-file-pdf"></i> Generate SOA
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($suppliers)): ?>
                        <tr>
                            <td colspan="4" class="text-center">No supplier records found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once $basePath . "includes/footer.php";
?>
