<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start();
// Set the base path for includes
$basePath = '../../../';

// Include database connection
require_once $basePath . "config/database.php";

// Start session and check login
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['position'] != 'Admin'){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}

// Check if client_id is provided
if(!isset($_GET['client_id']) || empty($_GET['client_id'])) {
    header("location: index.php");
    exit;
}

$client_id = $_GET['client_id'];

// Initialize filter variables
$filter_months = [];
$filter_year = null;
$filter_applied = false;

// Process filter form submission
if(isset($_POST['filter_submit'])) {
    $filter_applied = true;
    if(isset($_POST['months']) && !empty($_POST['months'])) {
        $filter_months = $_POST['months'];
    }
    if(isset($_POST['year']) && !empty($_POST['year'])) {
        $filter_year = $_POST['year'];
    }
}

// Fetch client information and SOAs
try {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch();
    
    if(!$client) {
        header("location: index.php");
        exit;
    }
    
    $soa_query = "SELECT *, (total_amount - paid_amount) as balance FROM client_soa WHERE client_id = ?";
    $query_params = [$client_id];
    
    if($filter_applied) {
        if(!empty($filter_months)) {
            $month_conditions = array_fill(0, count($filter_months), "MONTH(issue_date) = ?");
            $soa_query .= " AND (" . implode(" OR ", $month_conditions) . ")";
            $query_params = array_merge($query_params, $filter_months);
        }
        if($filter_year !== null) {
            $soa_query .= " AND YEAR(issue_date) = ?";
            $query_params[] = $filter_year;
        }
    }
    
    $soa_query .= " ORDER BY issue_date DESC";
    $stmt = $pdo->prepare($soa_query);
    $stmt->execute($query_params);
    $soas = $stmt->fetchAll();
    
    // Get statistics (always show all stats regardless of filter)
    $stmt = $pdo->prepare("SELECT
                          COUNT(*) as total_soas,
                          SUM(CASE WHEN status = 'Paid' THEN 1 ELSE 0 END) as paid_count,
                          SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
                          SUM(CASE WHEN status = 'Overdue' THEN 1 ELSE 0 END) as overdue_count,
                          SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_count,
                          COALESCE(SUM(total_amount), 0) as total_invoiced,
                          COALESCE(SUM(paid_amount), 0) as total_paid,
                          COALESCE(SUM(total_amount - paid_amount), 0) as total_balance
                          FROM client_soa WHERE client_id = ?");
    $stmt->execute([$client_id]);
    $stats = $stmt->fetch();
    
    // Get available years for the filter dropdown
    $stmt = $pdo->prepare("SELECT DISTINCT YEAR(issue_date) as year FROM client_soa WHERE client_id = ? ORDER BY year DESC");
    $stmt->execute([$client_id]);
    $available_years = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if(empty($available_years)) {
        $available_years = [date('Y')];
    }
    
} catch(PDOException $e) {
    $db_error = "Database Error: " . $e->getMessage();
}

// Process delete/close operations
if(isset($_GET["action"]) && isset($_GET["soa_id"]) && !empty($_GET["soa_id"])){
    $action = $_GET["action"];
    $soa_id = $_GET["soa_id"];
    $redirect_url = "client_soas.php?client_id=$client_id";

    if($action == "delete"){
        $sql = "DELETE FROM client_soa WHERE soa_id = :id AND client_id = :client_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $soa_id, ':client_id' => $client_id]);
        header("location: $redirect_url&success=deleted");
        exit();
    } elseif($action == "close"){
        $sql = "UPDATE client_soa SET status = 'Closed' WHERE soa_id = :id AND client_id = :client_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $soa_id, ':client_id' => $client_id]);
        header("location: $redirect_url&success=closed");
        exit();
    }
}

$month_names = [1=>'Jan', 2=>'Feb', 3=>'Mar', 4=>'Apr', 5=>'May', 6=>'Jun', 7=>'Jul', 8=>'Aug', 9=>'Sep', 10=>'Oct', 11=>'Nov', 12=>'Dec'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOAs for <?php echo htmlspecialchars($client['client_name']); ?> - SOA Management System</title>
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
                        <h1>SOAs for <?php echo htmlspecialchars($client['client_name']); ?></h1>
                        <p>Manage all statements for this client</p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="account_summary.php?client_id=<?php echo $client_id; ?>" class="export-btn info"><i class="fas fa-chart-bar"></i> Account Summary</a>
                    <a href="index.php" class="export-btn secondary"><i class="fas fa-arrow-left"></i> Back to Client List</a>
                    <a href="add.php?client_id=<?php echo $client_id; ?>" class="export-btn"><i class="fas fa-plus"></i> Create New SOA</a>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <?php if(isset($_GET["success"])): ?>
                <div class="alert alert-success" data-aos="fade-down">
                    <div class="alert-content"><i class="fas fa-check-circle"></i>
                        <span>
                            <?php 
                            if($_GET["success"] == "deleted") echo "SOA record has been deleted successfully.";
                            elseif($_GET["success"] == "closed") echo "Account has been closed successfully.";
                            ?>
                        </span>
                    </div>
                    <button class="alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>

            <div class="profile-header" data-aos="fade-down">
                <div class="profile-avatar" style="background-color: #4a5568;"><i class="fas fa-user-tie"></i></div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($client['client_name']); ?></h2>
                    <p class="profile-subtitle"><?php echo htmlspecialchars($client['address']); ?></p>
                    <div class="profile-meta">
                        <span class="meta-item"><i class="fas fa-user"></i> <?php echo htmlspecialchars($client['pic_name']); ?></span>
                        <span class="meta-item"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($client['pic_contact']); ?></span>
                        <span class="meta-item"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($client['pic_email']); ?></span>
                    </div>
                </div>
            </div>

            <div class="stats-grid-detailed" data-aos="fade-up">
                <div class="stat-card-detailed primary"><div class="stat-icon"><i class="fas fa-file-invoice"></i></div><div class="stat-content"><h3><?php echo $stats['total_soas'] ?? 0; ?></h3><p>Total SOAs</p></div></div>
                <div class="stat-card-detailed success"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?php echo $stats['paid_count'] ?? 0; ?></h3><p>Paid</p></div></div>
                <div class="stat-card-detailed warning"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-content"><h3><?php echo $stats['pending_count'] ?? 0; ?></h3><p>Pending</p></div></div>
                <div class="stat-card-detailed danger"><div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div><div class="stat-content"><h3><?php echo $stats['overdue_count'] ?? 0; ?></h3><p>Overdue</p></div></div>
                <div class="stat-card-detailed secondary"><div class="stat-icon"><i class="fas fa-lock"></i></div><div class="stat-content"><h3><?php echo $stats['closed_count'] ?? 0; ?></h3><p>Closed</p></div></div>
            </div>

            <!-- Financial Summary -->
            <div class="financial-summary" data-aos="fade-up" data-aos-delay="100">
                <div class="fin-card">
                    <span class="fin-label">Total Invoiced</span>
                    <span class="fin-value">RM <?php echo number_format($stats['total_invoiced'] ?? 0, 2); ?></span>
                </div>
                <div class="fin-card">
                    <span class="fin-label">Total Paid</span>
                    <span class="fin-value fin-success">RM <?php echo number_format($stats['total_paid'] ?? 0, 2); ?></span>
                </div>
                <div class="fin-card">
                    <span class="fin-label">Outstanding Balance</span>
                    <span class="fin-value <?php echo ($stats['total_balance'] ?? 0) > 0 ? 'fin-danger' : 'fin-success'; ?>">RM <?php echo number_format($stats['total_balance'] ?? 0, 2); ?></span>
                </div>
            </div>

            <div class="table-card" data-aos="fade-up" data-aos-delay="200">
                <div class="table-header">
                    <div class="table-title">
                        <h3>SOA History</h3>
                        <p>All SOAs associated with <?php echo htmlspecialchars($client['client_name']); ?></p>
                    </div>
                    <button class="export-btn secondary" type="button" id="filterToggleBtn">
                        <i class="fas fa-filter"></i> Filter Options
                    </button>
                </div>

                <div class="filter-container <?php echo $filter_applied ? '' : 'hidden'; ?>" id="filterContainer">
                    <form method="post" id="filterForm">
                        <div class="filter-grid">
                            <div class="filter-group year-group">
                                <label for="year" class="filter-label">Year</label>
                                <select class="filter-select" id="year" name="year">
                                    <option value="">All Years</option>
                                    <?php foreach($available_years as $year): ?>
                                    <option value="<?php echo $year; ?>" <?php echo ($year == $filter_year) ? 'selected' : ''; ?>><?php echo $year; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group month-group">
                                <label class="filter-label">Months</label>
                                <div class="month-grid">
                                    <div class="month-checkbox select-all-container">
                                        <input type="checkbox" id="select_all_months">
                                        <label for="select_all_months">All</label>
                                    </div>
                                    <?php foreach($month_names as $num => $name): ?>
                                    <div class="month-checkbox">
                                        <input type="checkbox" id="month_<?php echo $num; ?>" name="months[]" value="<?php echo $num; ?>" <?php echo in_array($num, $filter_months) ? 'checked' : ''; ?>>
                                        <label for="month_<?php echo $num; ?>"><?php echo $name; ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="filter-actions">
                            <button type="submit" name="filter_submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply Filter</button>
                            <button type="button" id="clearFilter" class="btn btn-secondary"><i class="fas fa-times"></i> Clear Filter</button>
                        </div>
                    </form>
                </div>

                <?php if($filter_applied): ?>
                <div class="active-filters-bar">
                    <span class="bar-title">Active Filters:</span>
                    <span class="filter-tag year"><?php echo ($filter_year !== null) ? $filter_year : 'All Years'; ?></span>
                    <?php if(!empty($filter_months)): ?>
                        <?php foreach($filter_months as $month): ?>
                            <span class="filter-tag month"><?php echo $month_names[$month]; ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="filter-tag month">All Months</span>
                    <?php endif; ?>
                    <span class="filter-count"><?php echo count($soas); ?> records found</span>
                </div>
                <?php endif; ?>

                <div class="table-container">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Account #</th><th>Issue Date</th><th>Due Date</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Status</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($soas)): ?>
                                <?php foreach($soas as $soa): ?>
                                <tr>
                                    <td><span class="account-number"><?php echo htmlspecialchars($soa['account_number']); ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($soa['issue_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($soa['due_date'])); ?></td>
                                    <td><span class="amount-display">RM <?php echo number_format($soa['total_amount'], 2); ?></span></td>
                                    <td><span style="color:var(--success-color);font-weight:600;">RM <?php echo number_format($soa['paid_amount'], 2); ?></span></td>
                                    <td><span class="<?php echo $soa['balance'] > 0 ? 'balance-due' : 'balance-clear'; ?>">RM <?php echo number_format($soa['balance'], 2); ?></span></td>
                                    <td><span class="status-badge status-<?php echo strtolower($soa['status']); ?>"><?php echo htmlspecialchars($soa['status']); ?></span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="view.php?id=<?php echo $soa['soa_id']; ?>" class="action-btn action-btn-view" title="View"><i class="fas fa-eye"></i></a>
                                            <a href="generate_pdf.php?id=<?php echo $soa['soa_id']; ?>" class="action-btn action-btn-pdf" title="PDF" target="_blank"><i class="fas fa-file-pdf"></i></a>
                                            <?php if($soa['status'] != 'Closed' && $soa['status'] != 'Paid'): ?>
                                                <a href="edit.php?id=<?php echo $soa['soa_id']; ?>" class="action-btn action-btn-edit" title="Edit"><i class="fas fa-edit"></i></a>
                                                <a href="javascript:void(0);" onclick="confirmCloseAccount(<?php echo $soa['soa_id']; ?>, '<?php echo $soa['status']; ?>', <?php echo $client_id; ?>)" class="action-btn action-btn-close" title="Close"><i class="fas fa-lock"></i></a>
                                            <?php endif; ?>
                                            <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $soa['soa_id']; ?>, <?php echo $client_id; ?>)" class="action-btn action-btn-delete" title="Delete"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="text-center no-data"><div class="no-data-content"><i class="fas fa-file-invoice-dollar"></i><h3>No SOAs Found</h3><p>No records match your criteria. Try adjusting the filters.</p></div></td></tr>
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

            // Filter toggle
            const filterToggleBtn = document.getElementById('filterToggleBtn');
            const filterContainer = document.getElementById('filterContainer');
            filterToggleBtn.addEventListener('click', () => {
                filterContainer.classList.toggle('hidden');
            });

            // Filter form logic
            const selectAllCheckbox = document.getElementById('select_all_months');
            const monthCheckboxes = document.querySelectorAll('.month-grid input[type="checkbox"]:not(#select_all_months)');
            
            function updateSelectAllState() {
                const allChecked = Array.from(monthCheckboxes).every(cb => cb.checked);
                selectAllCheckbox.checked = allChecked;
            }

            selectAllCheckbox.addEventListener('change', function() {
                monthCheckboxes.forEach(checkbox => checkbox.checked = this.checked);
            });

            monthCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectAllState);
            });

            updateSelectAllState();

            document.getElementById('clearFilter').addEventListener('click', function() {
                document.getElementById('year').value = '';
                monthCheckboxes.forEach(checkbox => checkbox.checked = false);
                selectAllCheckbox.checked = false;
                document.getElementById('filterForm').submit();
            });
        });

        function confirmDelete(soaId, clientId) {
            if (confirm('Are you sure you want to delete this SOA? This action cannot be undone.')) {
                window.location.href = `client_soas.php?client_id=${clientId}&action=delete&soa_id=${soaId}`;
            }
        }

        function confirmCloseAccount(soaId, status, clientId) {
            let msg = 'Are you sure you want to close this account? This action cannot be undone.';
            if (status === 'Pending' || status === 'Overdue') {
                msg = `WARNING: This account is currently ${status.toUpperCase()}. Are you sure you want to close it? This action cannot be undone.`;
            }
            if (confirm(msg)) {
                window.location.href = `client_soas.php?client_id=${clientId}&action=close&soa_id=${soaId}`;
            }
        }
    </script>
    <style>
        .profile-header{background:white;border-radius:var(--border-radius);box-shadow:var(--shadow);border:1px solid var(--gray-200);padding:2rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:1.5rem}.profile-avatar{width:80px;height:80px;background:var(--gray-700);border-radius:var(--border-radius);display:flex;align-items:center;justify-content:center;color:white;font-size:2rem;flex-shrink:0}.profile-info h2{color:var(--gray-900);margin-bottom:.25rem;font-size:1.5rem;font-weight:600}.profile-subtitle{color:var(--gray-600);margin-bottom:1rem;max-width:60ch}.profile-meta{display:flex;flex-wrap:wrap;gap:1.5rem}.meta-item{display:flex;align-items:center;gap:.5rem;color:var(--gray-600);font-size:.875rem}.meta-item i{color:var(--gray-400)}.stats-grid-detailed{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:2rem}.stat-card-detailed{background:white;border-radius:var(--border-radius);padding:1rem;box-shadow:var(--shadow-sm);border:1px solid var(--gray-200);display:flex;align-items:center;gap:1rem}.stat-card-detailed .stat-icon{width:40px;height:40px;border-radius:var(--border-radius-sm);display:flex;align-items:center;justify-content:center;font-size:1.25rem;color:white}.stat-card-detailed.primary .stat-icon{background:var(--primary-color)}.stat-card-detailed.success .stat-icon{background:var(--success-color)}.stat-card-detailed.warning .stat-icon{background:var(--warning-color)}.stat-card-detailed.danger .stat-icon{background:var(--danger-color)}.stat-card-detailed.secondary .stat-icon{background:var(--gray-500)}.stat-card-detailed .stat-content h3{font-size:1.25rem;font-weight:700;color:var(--gray-900)}.stat-card-detailed .stat-content p{font-size:.75rem;color:var(--gray-600);margin:0;text-transform:uppercase}.filter-container{padding:1.5rem;border-top:1px solid var(--gray-200);background:var(--gray-50)}.filter-container.hidden{display:none}.filter-grid{display:grid;grid-template-columns:1fr 3fr;gap:2rem;margin-bottom:1.5rem}.filter-group .filter-label{display:block;font-weight:600;color:var(--gray-700);margin-bottom:.5rem}.filter-select{width:100%;padding:.75rem;border:1px solid var(--gray-300);border-radius:var(--border-radius-sm);font-size:.875rem}.month-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:.75rem}.month-checkbox{display:flex;align-items:center;gap:.5rem}.month-checkbox label{font-size:.875rem;color:var(--gray-700);cursor:pointer}.month-checkbox input{cursor:pointer}.select-all-container{font-weight:600}.filter-actions{display:flex;gap:1rem;padding-top:1rem;border-top:1px solid var(--gray-200);margin-top:1rem}.active-filters-bar{display:flex;align-items:center;gap:.75rem;padding:1rem 1.5rem;background:rgba(59,130,246,.05);border-top:1px solid var(--gray-200);border-bottom:1px solid var(--gray-200)}.bar-title{font-weight:600;color:var(--gray-700)}.filter-tag{padding:.25rem .75rem;border-radius:9999px;font-size:.75rem;font-weight:500}.filter-tag.year{background:var(--primary-color);color:white}.filter-tag.month{background:var(--info-color);color:white}.filter-count{margin-left:auto;font-size:.875rem;color:var(--gray-600)}.account-number{font-family:monospace;font-weight:600;color:var(--primary-color)}.amount-display{font-weight:600;font-size:.875rem;color:var(--gray-800)}.status-badge.status-closed{background:rgba(107,114,128,.1);color:var(--gray-600)}.action-buttons{display:flex;gap:.5rem}.action-btn-view{background:rgba(59,130,246,.1);color:var(--primary-color)}.action-btn-view:hover{background:var(--primary-color);color:white}.action-btn-pdf{background:rgba(16,185,129,.1);color:var(--success-color)}.action-btn-pdf:hover{background:var(--success-color);color:white}.action-btn-edit{background:rgba(245,158,11,.1);color:var(--warning-color)}.action-btn-edit:hover{background:var(--warning-color);color:white}.action-btn-close{background:rgba(107,114,128,.1);color:var(--gray-600)}.action-btn-close:hover{background:var(--gray-600);color:white}.action-btn-delete{background:rgba(239,68,68,.1);color:var(--danger-color)}.action-btn-delete:hover{background:var(--danger-color);color:white}.no-data{padding:3rem!important}.no-data-content i{font-size:3rem;color:var(--gray-300);margin-bottom:1rem}.no-data-content h3{color:var(--gray-700);margin-bottom:.5rem}.no-data-content p{color:var(--gray-500);margin-bottom:1.5rem}.btn{display:inline-flex;align-items:center;gap:.5rem;padding:.75rem 1.5rem;border:none;border-radius:var(--border-radius-sm);font-size:.875rem;font-weight:500;text-decoration:none;cursor:pointer;transition:var(--transition)}.btn-primary{background:var(--primary-color);color:white}.btn-primary:hover{background:var(--primary-dark)}.btn-secondary{background:var(--gray-200);color:var(--gray-800)}.btn-secondary:hover{background:var(--gray-300)}.financial-summary{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:2rem}.fin-card{background:white;border-radius:var(--border-radius);padding:1rem 1.5rem;box-shadow:var(--shadow-sm);border:1px solid var(--gray-200);display:flex;justify-content:space-between;align-items:center}.fin-label{font-size:.875rem;color:var(--gray-600);font-weight:500}.fin-value{font-size:1.125rem;font-weight:700;color:var(--gray-900)}.fin-success{color:var(--success-color)!important}.fin-danger{color:var(--danger-color)!important}.balance-due{font-weight:600;color:var(--danger-color)}.balance-clear{font-weight:600;color:var(--success-color)}@media(max-width:1024px){.filter-grid{grid-template-columns:1fr;gap:1.5rem}.month-grid{grid-template-columns:repeat(4,1fr)}.financial-summary{grid-template-columns:1fr}}@media(max-width:768px){.stats-grid-detailed{grid-template-columns:repeat(auto-fit,minmax(120px,1fr))}.month-grid{grid-template-columns:repeat(3,1fr)}}
    </style>
</body>
</html>
<?php ob_end_flush(); ?>
