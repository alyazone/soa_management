<?php
ob_start();
$basePath = '../../../';
require_once $basePath . "config/database.php";

session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['position'] != 'Admin'){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}

try {
    $stmt = $pdo->query("
        SELECT s.*, sup.supplier_name 
        FROM supplier_soa s 
        JOIN suppliers sup ON s.supplier_id = sup.supplier_id 
        ORDER BY s.issue_date DESC
    ");
    $soas = $stmt->fetchAll();

    $summary_stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_soas,
            SUM(CASE WHEN payment_status = 'Paid' THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN payment_status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN payment_status = 'Overdue' THEN 1 ELSE 0 END) as overdue_count,
            SUM(amount) as total_amount,
            SUM(CASE WHEN payment_status != 'Paid' THEN amount ELSE 0 END) as outstanding_amount
        FROM supplier_soa
    ");
    $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $db_error = "Database Error: " . $e->getMessage();
    $soas = [];
    $summary = array_fill_keys(['total_soas', 'paid_count', 'pending_count', 'overdue_count', 'total_amount', 'outstanding_amount'], 0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier SOA Management - SOA Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/modern-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3b82f6; --primary-dark: #2563eb; --success-color: #10b981; --warning-color: #f59e0b; --danger-color: #ef4444; --info-color: #06b6d4; --secondary-color: #6b7280;
            --gray-50: #f9fafb; --gray-100: #f3f4f6; --gray-200: #e5e7eb; --gray-300: #d1d5db; --gray-400: #9ca3af; --gray-500: #6b7280; --gray-600: #4b5563; --gray-700: #374151; --gray-800: #1f2937; --gray-900: #111827;
            --sidebar-width: 280px; --header-height: 80px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05); --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --border-radius: 12px; --border-radius-sm: 8px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Inter", sans-serif; background-color: var(--gray-50); color: var(--gray-900); line-height: 1.6; }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; transition: var(--transition); }
        .dashboard-header { background: white; border-bottom: 1px solid var(--gray-200); height: var(--header-height); position: sticky; top: 0; z-index: 40; }
        .header-content { display: flex; align-items: center; justify-content: space-between; height: 100%; padding: 0 2rem; }
        .header-left { display: flex; align-items: center; gap: 1rem; }
        .header-title h1 { font-size: 1.875rem; font-weight: 700; color: var(--gray-900); margin-bottom: 0.25rem; }
        .header-title p { color: var(--gray-600); font-size: 0.875rem; }
        .header-right { display: flex; align-items: center; gap: 1rem; }
        .export-btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.25rem; border: 1px solid transparent; background: var(--primary-color); color: white; border-radius: var(--border-radius-sm); font-size: 0.875rem; font-weight: 500; cursor: pointer; transition: var(--transition); text-decoration: none; }
        .export-btn:hover { background: var(--primary-dark); box-shadow: var(--shadow-md); }
        .dashboard-content { padding: 2rem; max-width: 1600px; margin: 0 auto; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; border-radius: var(--border-radius); padding: 1.5rem; box-shadow: var(--shadow); border: 1px solid var(--gray-200); display: flex; align-items: center; gap: 1.5rem; transition: var(--transition); }
        .stat-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }
        .stat-icon { width: 50px; height: 50px; border-radius: var(--border-radius-sm); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: white; flex-shrink: 0; }
        .stat-card.primary .stat-icon { background: var(--primary-color); } .stat-card.success .stat-icon { background: var(--success-color); } .stat-card.warning .stat-icon { background: var(--warning-color); } .stat-card.danger .stat-icon { background: var(--danger-color); } .stat-card.info .stat-icon { background: var(--info-color); }
        .stat-content h3 { font-size: 1.75rem; font-weight: 700; color: var(--gray-900); line-height: 1.2; }
        .stat-content p { font-size: 0.875rem; color: var(--gray-600); }
        .table-card { background: white; border-radius: var(--border-radius); box-shadow: var(--shadow); border: 1px solid var(--gray-200); overflow: hidden; }
        .table-header { display: flex; justify-content: space-between; align-items: flex-start; padding: 1.5rem; border-bottom: 1px solid var(--gray-200); }
        .table-title h3 { font-size: 1.25rem; font-weight: 600; color: var(--gray-900); margin-bottom: 0.25rem; }
        .table-title p { font-size: 0.875rem; color: var(--gray-600); }
        .table-container { overflow-x: auto; }
        .modern-table { width: 100%; border-collapse: collapse; }
        .modern-table th { background: var(--gray-50); padding: 1rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 600; color: var(--gray-600); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid var(--gray-200); }
        .modern-table td { padding: 1rem 1.5rem; border-bottom: 1px solid var(--gray-100); font-size: 0.875rem; color: var(--gray-800); vertical-align: middle; }
        .modern-table tr:last-child td { border-bottom: none; }
        .modern-table tr:hover { background: var(--gray-50); }
        .status-badge { display: inline-flex; align-items: center; padding: 0.375rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .status-paid { background: rgba(16, 185, 129, 0.1); color: var(--success-color); }
        .status-pending { background: rgba(245, 158, 11, 0.1); color: var(--warning-color); }
        .status-overdue { background: rgba(239, 68, 68, 0.1); color: var(--danger-color); }
        .action-buttons { display: flex; gap: 0.5rem; }
        .action-btn { width: 32px; height: 32px; border: none; border-radius: var(--border-radius-sm); display: inline-flex; align-items: center; justify-content: center; color: white; cursor: pointer; transition: var(--transition); text-decoration: none; }
        .action-btn-view { background-color: var(--info-color); } .action-btn-view:hover { background-color: #0891b2; }
        .action-btn-edit { background-color: var(--warning-color); } .action-btn-edit:hover { background-color: #d97706; }
        .action-btn-pdf { background-color: var(--success-color); } .action-btn-pdf:hover { background-color: #059669; }
        .action-btn-delete { background-color: var(--danger-color); } .action-btn-delete:hover { background-color: #dc2626; }
        .no-data { padding: 4rem !important; } .no-data-content { text-align: center; } .no-data-content i { font-size: 3rem; color: var(--gray-300); margin-bottom: 1rem; } .no-data-content h3 { font-size: 1.25rem; color: var(--gray-700); margin-bottom: 0.5rem; } .no-data-content p { color: var(--gray-500); margin-bottom: 1.5rem; }
        .no-data-content .btn-primary { background: var(--primary-color); color: white; padding: 0.75rem 1.5rem; border-radius: var(--border-radius-sm); text-decoration: none; font-weight: 500; transition: var(--transition); }
        .no-data-content .btn-primary:hover { background: var(--primary-dark); }
        .alert { padding: 1rem 1.5rem; margin-bottom: 1.5rem; border-radius: var(--border-radius-sm); display: flex; justify-content: space-between; align-items: center; box-shadow: var(--shadow); }
        .alert-content { display: flex; align-items: center; gap: 0.75rem; font-weight: 500; }
        .alert-close { background: none; border: none; cursor: pointer; opacity: 0.7; } .alert-close:hover { opacity: 1; }
        .alert-success { background-color: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    </style>
</head>
<body class="bg-gray-50">
    <?php include_once $basePath . "includes/modern-sidebar.php"; ?>

    <div class="main-content">
        <header class="dashboard-header">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-title">
                        <h1>Supplier SOA Management</h1>
                        <p>Manage all incoming invoices and statements from suppliers</p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="add.php" class="export-btn"><i class="fas fa-plus"></i> Add New SOA</a>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <div class="alert-content"><i class="fas fa-check-circle"></i>
                        <span>
                            <?php
                            if ($_GET['success'] == 'deleted') echo 'Supplier SOA record deleted successfully.';
                            if ($_GET['success'] == 'added') echo 'Supplier SOA record added successfully.';
                            if ($_GET['success'] == 'updated') echo 'Supplier SOA record updated successfully.';
                            ?>
                        </span>
                    </div>
                    <button class="alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card primary"><div class="stat-icon"><i class="fas fa-file-invoice"></i></div><div class="stat-content"><h3><?php echo $summary['total_soas']; ?></h3><p>Total SOAs</p></div></div>
                <div class="stat-card success"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?php echo $summary['paid_count']; ?></h3><p>Paid</p></div></div>
                <div class="stat-card warning"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-content"><h3><?php echo $summary['pending_count']; ?></h3><p>Pending</p></div></div>
                <div class="stat-card danger"><div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div><div class="stat-content"><h3><?php echo $summary['overdue_count']; ?></h3><p>Overdue</p></div></div>
                <div class="stat-card info"><div class="stat-icon"><i class="fas fa-dollar-sign"></i></div><div class="stat-content"><h3>RM <?php echo number_format($summary['total_amount'], 2); ?></h3><p>Total Amount</p></div></div>
                <div class="stat-card danger"><div class="stat-icon"><i class="fas fa-hand-holding-usd"></i></div><div class="stat-content"><h3>RM <?php echo number_format($summary['outstanding_amount'], 2); ?></h3><p>Outstanding</p></div></div>
            </div>

            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">
                        <h3>Supplier SOA Ledger</h3>
                        <p>All supplier SOAs recorded in the system</p>
                    </div>
                </div>
                <div class="table-container">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Supplier</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($soas)): ?>
                                <?php foreach($soas as $soa): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($soa['invoice_number']); ?></td>
                                    <td><?php echo htmlspecialchars($soa['supplier_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($soa['issue_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($soa['payment_due_date'])); ?></td>
                                    <td>RM <?php echo number_format($soa['amount'], 2); ?></td>
                                    <td><span class="status-badge status-<?php echo strtolower($soa['payment_status']); ?>"><?php echo htmlspecialchars($soa['payment_status']); ?></span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="view.php?id=<?php echo $soa['soa_id']; ?>" class="action-btn action-btn-view" title="View"><i class="fas fa-eye"></i></a>
                                            <a href="edit.php?id=<?php echo $soa['soa_id']; ?>" class="action-btn action-btn-edit" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="generate_pdf.php?id=<?php echo $soa['soa_id']; ?>" class="action-btn action-btn-pdf" title="PDF" target="_blank"><i class="fas fa-file-pdf"></i></a>
                                            <a href="delete.php?id=<?php echo $soa['soa_id']; ?>" onclick="return confirm('Are you sure you want to delete this SOA? This action cannot be undone.');" class="action-btn action-btn-delete" title="Delete"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="no-data"><div class="no-data-content"><i class="fas fa-file-invoice-dollar"></i><h3>No SOAs Found</h3><p>There are no supplier SOAs recorded yet.</p><a href="add.php" class="btn-primary"><i class="fas fa-plus"></i> Add First SOA</a></div></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php ob_end_flush(); ?>
