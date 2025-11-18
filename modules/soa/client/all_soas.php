<?php
ob_start();
$basePath = '../../../';
require_once $basePath . "config/database.php";

session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['position'] != 'Admin'){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}

// Delete/Close logic
if(isset($_GET["action"]) && isset($_GET["id"])){
    $action = $_GET["action"];
    $soa_id = $_GET["id"];
    $redirect_url = "all_soas.php";

    if($action == "delete"){
        $sql = "DELETE FROM client_soa WHERE soa_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $soa_id]);
        header("location: $redirect_url?success=deleted");
        exit();
    } elseif($action == "close"){
        $sql = "UPDATE client_soa SET status = 'Closed' WHERE soa_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $soa_id]);
        header("location: $redirect_url?success=closed");
        exit();
    }
}

try {
    $stmt = $pdo->query("
        SELECT s.*, c.client_name 
        FROM client_soa s 
        JOIN clients c ON s.client_id = c.client_id 
        ORDER BY s.issue_date DESC
    ");
    $soas = $stmt->fetchAll();
} catch(PDOException $e) {
    $db_error = "Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Client SOAs - SOA Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/modern-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include_once $basePath . "includes/modern-sidebar.php"; ?>

    <div class="main-content">
        <header class="dashboard-header">
            <div class="header-content">
                <div class="header-left">
                    <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                    <div class="header-title">
                        <h1>All Client SOAs</h1>
                        <p>A complete list of all Statements of Account</p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="index.php" class="export-btn secondary"><i class="fas fa-arrow-left"></i> Back to Client List</a>
                    <a href="add.php" class="export-btn"><i class="fas fa-plus"></i> Create New SOA</a>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success" data-aos="fade-down">
                    <div class="alert-content"><i class="fas fa-check-circle"></i>
                        <span>
                            <?php
                            if ($_GET['success'] == 'deleted') echo 'SOA record deleted successfully.';
                            if ($_GET['success'] == 'closed') echo 'SOA has been marked as Closed.';
                            if ($_GET['success'] == 'updated') echo 'SOA record has been updated successfully.';
                            if ($_GET['success'] == 'added') echo 'SOA record has been added successfully.';
                            ?>
                        </span>
                    </div>
                    <button class="alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>
            <?php if(isset($db_error)): ?>
                <div class="alert alert-error" data-aos="fade-down"><div class="alert-content"><i class="fas fa-exclamation-circle"></i><span><?php echo $db_error; ?></span></div></div>
            <?php endif; ?>

            <div class="table-card" data-aos="fade-up">
                <div class="table-header">
                    <div class="table-title">
                        <h3>SOA Ledger</h3>
                        <p>All client SOAs recorded in the system</p>
                    </div>
                </div>
                <div class="table-container">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Account #</th>
                                <th>Client</th>
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
                                    <td><span class="account-number"><?php echo htmlspecialchars($soa['account_number']); ?></span></td>
                                    <td><?php echo htmlspecialchars($soa['client_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($soa['issue_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($soa['due_date'])); ?></td>
                                    <td><span class="amount-display">RM <?php echo number_format($soa['total_amount'], 2); ?></span></td>
                                    <td><span class="status-badge status-<?php echo strtolower($soa['status']); ?>"><?php echo htmlspecialchars($soa['status']); ?></span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="view.php?id=<?php echo $soa['soa_id']; ?>" class="action-btn action-btn-view" title="View"><i class="fas fa-eye"></i></a>
                                            <?php if($soa['status'] != 'Closed' && $soa['status'] != 'Paid'): ?>
                                                <a href="edit.php?id=<?php echo $soa['soa_id']; ?>" class="action-btn action-btn-edit" title="Edit"><i class="fas fa-edit"></i></a>
                                                <a href="all_soas.php?action=close&id=<?php echo $soa['soa_id']; ?>" onclick="return confirm('Are you sure you want to close this account? This cannot be undone.');" class="action-btn action-btn-close" title="Close"><i class="fas fa-lock"></i></a>
                                            <?php endif; ?>
                                             <a href="all_soas.php?action=delete&id=<?php echo $soa['soa_id']; ?>" onclick="return confirm('Are you sure you want to delete this SOA?');" class="action-btn action-btn-delete" title="Delete"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center no-data"><div class="no-data-content"><i class="fas fa-file-invoice-dollar"></i><h3>No SOAs Found</h3><p>There are no SOAs recorded in the system yet.</p></div></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="<?php echo $basePath; ?>assets/js/modern-dashboard.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({ duration: 800, easing: 'ease-in-out', once: true });
            initializeDashboard();
        });
    </script>
    <style>
        .alert{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.5rem;border-radius:var(--border-radius);margin-bottom:1.5rem;border:1px solid}.alert-success{background:rgba(16,185,129,.1);border-color:var(--success-color);color:var(--success-color)}.alert-error{background:rgba(239,68,68,.1);border-color:var(--danger-color);color:var(--danger-color)}.alert-content{display:flex;align-items:center;gap:.75rem}.alert-close{background:0 0;border:none;color:inherit;cursor:pointer;padding:.25rem;border-radius:var(--border-radius-sm);transition:var(--transition)}.alert-close:hover{background:rgba(0,0,0,.1)}.account-number{font-family:monospace;font-weight:600;color:var(--primary-color)}.amount-display{font-weight:600;font-size:.875rem;color:var(--gray-800)}.status-badge.status-closed{background:rgba(107,114,128,.1);color:var(--gray-600)}.action-buttons{display:flex;gap:.5rem}.action-btn-view{background:rgba(59,130,246,.1);color:var(--primary-color)}.action-btn-view:hover{background:var(--primary-color);color:white}.action-btn-edit{background:rgba(245,158,11,.1);color:var(--warning-color)}.action-btn-edit:hover{background:var(--warning-color);color:white}.action-btn-close{background:rgba(107,114,128,.1);color:var(--gray-600)}.action-btn-close:hover{background:var(--gray-600);color:white}.action-btn-delete{background:rgba(239,68,68,.1);color:var(--danger-color)}.action-btn-delete:hover{background:var(--danger-color);color:white}.no-data{padding:3rem!important}.no-data-content{text-align:center}.no-data-content i{font-size:3rem;color:var(--gray-300);margin-bottom:1rem}.no-data-content h3{color:var(--gray-700);margin-bottom:.5rem}.no-data-content p{color:var(--gray-500);margin-bottom:1.5rem}
    </style>
</body>
</html>
<?php ob_end_flush(); ?>
