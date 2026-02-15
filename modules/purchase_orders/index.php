<?php
$basePath = '../../';
require_once $basePath . "config/database.php";

session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}

// Only Admin and Manager can access
if($_SESSION['position'] != 'Admin' && $_SESSION['position'] != 'Manager'){
    header("location: " . $basePath . "dashboard.php");
    exit;
}

// Handle status filter
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$supplier_filter = isset($_GET['supplier_id']) ? intval($_GET['supplier_id']) : 0;

// Build query with optional filters
$where_clauses = [];
$params = [];

if(!empty($status_filter) && in_array($status_filter, ['Draft', 'Approved', 'Received', 'Cancelled'])){
    $where_clauses[] = "po.status = :status";
    $params[':status'] = $status_filter;
}

if($supplier_filter > 0){
    $where_clauses[] = "po.supplier_id = :supplier_id";
    $params[':supplier_id'] = $supplier_filter;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

try {
    $sql = "SELECT po.*, s.supplier_name, st.full_name as created_by_name,
                   appr.full_name as approved_by_name
            FROM purchase_orders po
            JOIN suppliers s ON po.supplier_id = s.supplier_id
            JOIN staff st ON po.created_by = st.staff_id
            LEFT JOIN staff appr ON po.approved_by = appr.staff_id
            $where_sql
            ORDER BY po.created_at DESC";

    $stmt = $pdo->prepare($sql);
    foreach($params as $key => $val){
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $purchase_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $purchase_orders = [];
    $query_err = "Error loading purchase orders: " . $e->getMessage();
}

// Get suppliers for filter dropdown
try {
    $suppliers = $pdo->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name")->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $suppliers = [];
}

// Calculate summary stats
$total_pos = count($purchase_orders);
$draft_count = 0;
$approved_count = 0;
$total_value = 0;
foreach($purchase_orders as $po){
    $total_value += $po['total_amount'];
    if($po['status'] == 'Draft') $draft_count++;
    if($po['status'] == 'Approved') $approved_count++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders - SOA Management System</title>

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
                        <h1>Purchase Orders</h1>
                        <p>Manage purchase orders and supplier transactions</p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="add.php" class="export-btn">
                        <i class="fas fa-plus"></i>
                        New Purchase Order
                    </a>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <?php if(isset($_GET["success"])): ?>
                <div class="alert alert-success" data-aos="fade-down">
                    <div class="alert-content">
                        <i class="fas fa-check-circle"></i>
                        <span>
                            <?php
                            if($_GET["success"] == "deleted") echo "Purchase order has been deleted successfully.";
                            elseif($_GET["success"] == "added") echo "Purchase order has been created successfully.";
                            elseif($_GET["success"] == "updated") echo "Purchase order has been updated successfully.";
                            elseif($_GET["success"] == "approved") echo "Purchase order has been approved successfully.";
                            elseif($_GET["success"] == "linked") echo "Supplier invoice has been linked successfully.";
                            ?>
                        </span>
                    </div>
                    <button class="alert-close" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET["error"])): ?>
                <div class="alert alert-error" data-aos="fade-down">
                    <div class="alert-content">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($_GET["error"]); ?></span>
                    </div>
                    <button class="alert-close" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="stats-grid" data-aos="fade-up">
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $total_pos; ?></h3>
                        <p>Total POs</p>
                    </div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="fas fa-pencil-alt"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $draft_count; ?></h3>
                        <p>Draft</p>
                    </div>
                </div>
                <div class="stat-card success">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $approved_count; ?></h3>
                        <p>Approved</p>
                    </div>
                </div>
                <div class="stat-card info">
                    <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="stat-content">
                        <h3>RM <?php echo number_format($total_value, 2); ?></h3>
                        <p>Total Value</p>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="filter-bar" data-aos="fade-up" data-aos-delay="100">
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label><i class="fas fa-filter"></i> Status</label>
                        <select name="status" class="filter-select" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="Draft" <?php echo $status_filter == 'Draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="Approved" <?php echo $status_filter == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="Received" <?php echo $status_filter == 'Received' ? 'selected' : ''; ?>>Received</option>
                            <option value="Cancelled" <?php echo $status_filter == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-truck"></i> Supplier</label>
                        <select name="supplier_id" class="filter-select" onchange="this.form.submit()">
                            <option value="">All Suppliers</option>
                            <?php foreach($suppliers as $sup): ?>
                            <option value="<?php echo $sup['supplier_id']; ?>" <?php echo $supplier_filter == $sup['supplier_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sup['supplier_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if(!empty($status_filter) || $supplier_filter > 0): ?>
                    <a href="index.php" class="filter-clear"><i class="fas fa-times"></i> Clear Filters</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- PO Table -->
            <div class="table-card" data-aos="fade-up" data-aos-delay="200">
                <div class="table-header">
                    <div class="table-title">
                        <h3>Purchase Order List</h3>
                        <p><?php echo $total_pos; ?> records found</p>
                    </div>
                </div>
                <div class="table-container">
                    <table class="modern-table" id="poTable">
                        <thead>
                            <tr>
                                <th>PO Number</th>
                                <th>Supplier</th>
                                <th>Order Date</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Invoice</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($purchase_orders)): ?>
                                <?php foreach($purchase_orders as $po): ?>
                                <tr class="table-row-clickable" data-href="view.php?id=<?php echo $po['po_id']; ?>">
                                    <td>
                                        <span class="po-number"><?php echo htmlspecialchars($po['po_number']); ?></span>
                                    </td>
                                    <td>
                                        <div class="supplier-info">
                                            <div class="supplier-avatar">
                                                <i class="fas fa-truck"></i>
                                            </div>
                                            <span><?php echo htmlspecialchars($po['supplier_name']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($po['order_date'])); ?></td>
                                    <td>
                                        <span class="amount-display has-amount">
                                            RM <?php echo number_format($po['total_amount'], 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($po['status']); ?>">
                                            <?php echo htmlspecialchars($po['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if(!empty($po['supplier_invoice_number'])): ?>
                                            <span class="invoice-linked"><i class="fas fa-link"></i> <?php echo htmlspecialchars($po['supplier_invoice_number']); ?></span>
                                        <?php else: ?>
                                            <span class="invoice-none">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="created-by"><?php echo htmlspecialchars($po['created_by_name']); ?></span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn action-btn-view" onclick="viewPO(event, <?php echo $po['po_id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if($po['status'] == 'Draft'): ?>
                                            <button class="action-btn action-btn-edit" onclick="editPO(event, <?php echo $po['po_id']; ?>)" title="Edit PO">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn action-btn-delete" onclick="deletePO(event, <?php echo $po['po_id']; ?>, '<?php echo htmlspecialchars(addslashes($po['po_number'])); ?>')" title="Delete PO">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                            <button class="action-btn action-btn-pdf" onclick="generatePDF(event, <?php echo $po['po_id']; ?>)" title="Generate PDF">
                                                <i class="fas fa-file-pdf"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center no-data">
                                    <div class="no-data-content">
                                        <i class="fas fa-file-alt"></i>
                                        <h3>No Purchase Orders Found</h3>
                                        <p>There are no purchase orders matching your criteria.</p>
                                        <a href="add.php" class="btn-primary">
                                            <i class="fas fa-plus"></i>
                                            Create First PO
                                        </a>
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

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="../../assets/js/modern-dashboard.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({ duration: 800, easing: 'ease-in-out', once: true });
            initializeDashboard();
        });

        function viewPO(event, id) { event.stopPropagation(); window.location.href = `view.php?id=${id}`; }
        function editPO(event, id) { event.stopPropagation(); window.location.href = `edit.php?id=${id}`; }
        function generatePDF(event, id) { event.stopPropagation(); window.open(`generate_pdf.php?id=${id}`, '_blank'); }
        function deletePO(event, id, number) {
            event.stopPropagation();
            if (confirm(`Are you sure you want to delete PO ${number}? This action cannot be undone.`)) {
                window.location.href = `delete.php?id=${id}`;
            }
        }
    </script>
    <style>
        .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1.5rem;margin-bottom:2rem}
        .stat-card{background:white;border-radius:var(--border-radius);box-shadow:var(--shadow);border:1px solid var(--gray-200);padding:1.5rem;display:flex;align-items:center;gap:1rem}
        .stat-icon{width:50px;height:50px;border-radius:var(--border-radius-sm);display:flex;align-items:center;justify-content:center;font-size:1.25rem}
        .stat-card.primary .stat-icon{background:rgba(59,130,246,.1);color:var(--primary-color)}
        .stat-card.warning .stat-icon{background:rgba(245,158,11,.1);color:var(--warning-color)}
        .stat-card.success .stat-icon{background:rgba(16,185,129,.1);color:var(--success-color)}
        .stat-card.info .stat-icon{background:rgba(6,182,212,.1);color:var(--info-color)}
        .stat-content h3{font-size:1.5rem;font-weight:700;color:var(--gray-900);line-height:1}
        .stat-content p{font-size:.875rem;color:var(--gray-500);margin-top:.25rem}
        .filter-bar{background:white;border-radius:var(--border-radius);box-shadow:var(--shadow);border:1px solid var(--gray-200);padding:1.25rem 1.5rem;margin-bottom:2rem}
        .filter-form{display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap}
        .filter-group{display:flex;align-items:center;gap:.5rem}
        .filter-group label{font-size:.875rem;font-weight:500;color:var(--gray-600);display:flex;align-items:center;gap:.375rem;white-space:nowrap}
        .filter-select{padding:.5rem .75rem;border:1px solid var(--gray-300);border-radius:var(--border-radius-sm);font-size:.875rem;background:white;color:var(--gray-700);min-width:160px}
        .filter-select:focus{outline:0;border-color:var(--primary-color);box-shadow:0 0 0 3px rgba(59,130,246,.1)}
        .filter-clear{display:inline-flex;align-items:center;gap:.375rem;padding:.5rem .75rem;background:rgba(239,68,68,.1);color:var(--danger-color);border-radius:var(--border-radius-sm);font-size:.875rem;font-weight:500;text-decoration:none;transition:var(--transition)}
        .filter-clear:hover{background:var(--danger-color);color:white;text-decoration:none}
        .po-number{font-family:monospace;font-weight:600;color:var(--primary-color);font-size:.875rem}
        .supplier-info{display:flex;align-items:center;gap:.5rem}
        .supplier-avatar{width:28px;height:28px;background:rgba(99,102,241,.1);color:#6366f1;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:.75rem}
        .amount-display{font-weight:600;font-size:.875rem}
        .amount-display.has-amount{color:var(--success-color)}
        .status-badge{display:inline-flex;align-items:center;padding:.375rem .75rem;border-radius:9999px;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em}
        .status-draft{background:rgba(245,158,11,.1);color:var(--warning-color)}
        .status-approved{background:rgba(16,185,129,.1);color:var(--success-color)}
        .status-received{background:rgba(59,130,246,.1);color:var(--primary-color)}
        .status-cancelled{background:rgba(239,68,68,.1);color:var(--danger-color)}
        .invoice-linked{display:inline-flex;align-items:center;gap:.375rem;font-size:.75rem;color:var(--primary-color);font-weight:500}
        .invoice-none{color:var(--gray-400);font-size:.875rem}
        .created-by{font-size:.875rem;color:var(--gray-600)}
        .action-buttons{display:flex;gap:.5rem}
        .action-btn{width:32px;height:32px;border:none;border-radius:var(--border-radius-sm);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:var(--transition);font-size:.875rem}
        .action-btn-view{background:rgba(59,130,246,.1);color:var(--primary-color)}
        .action-btn-view:hover{background:var(--primary-color);color:white}
        .action-btn-edit{background:rgba(245,158,11,.1);color:var(--warning-color)}
        .action-btn-edit:hover{background:var(--warning-color);color:white}
        .action-btn-delete{background:rgba(239,68,68,.1);color:var(--danger-color)}
        .action-btn-delete:hover{background:var(--danger-color);color:white}
        .action-btn-pdf{background:rgba(239,68,68,.1);color:#dc2626}
        .action-btn-pdf:hover{background:#dc2626;color:white}
        .no-data{padding:3rem!important}
        .no-data-content{text-align:center}
        .no-data-content i{font-size:3rem;color:var(--gray-300);margin-bottom:1rem}
        .no-data-content h3{color:var(--gray-700);margin-bottom:.5rem}
        .no-data-content p{color:var(--gray-500);margin-bottom:1.5rem}
        .btn-primary{display:inline-flex;align-items:center;gap:.5rem;padding:.75rem 1.5rem;background:var(--primary-color);color:white;text-decoration:none;border-radius:var(--border-radius-sm);font-weight:500;transition:var(--transition)}
        .btn-primary:hover{background:var(--primary-dark);color:white;text-decoration:none}
        .alert{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.5rem;border-radius:var(--border-radius);margin-bottom:1.5rem;border:1px solid}
        .alert-success{background:rgba(16,185,129,.1);border-color:var(--success-color);color:var(--success-color)}
        .alert-error{background:rgba(239,68,68,.1);border-color:var(--danger-color);color:var(--danger-color)}
        .alert-content{display:flex;align-items:center;gap:.75rem}
        .alert-close{background:0 0;border:none;color:inherit;cursor:pointer;padding:.25rem;border-radius:var(--border-radius-sm);transition:var(--transition)}
        .alert-close:hover{background:rgba(0,0,0,.1)}
        @media (max-width:768px){.stats-grid{grid-template-columns:repeat(2,1fr)}.filter-form{flex-direction:column;align-items:stretch}.action-buttons{flex-direction:column}}
    </style>
</body>
</html>
