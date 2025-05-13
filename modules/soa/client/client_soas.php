<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start();
// Set the base path for includes
$basePath = '../../../';

// Include header and sidebar
include_once $basePath . "includes/header.php";
include_once $basePath . "includes/sidebar.php";

// Add custom CSS for statistics cards and filter
echo '<style>
/* Statistics Cards Styling */
.card[style*="border-left"] {
    transition: transform 0.2s, box-shadow 0.2s;
}

.card[style*="border-left"]:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.card[style*="border-left"] .text-xs {
    letter-spacing: 0.1em;
    font-size: 0.8rem;
}

.card[style*="border-left"] .h3 {
    font-size: 2rem;
    margin-top: 0.25rem;
}

.card[style*="border-left"] .fa-2x {
    opacity: 0.7;
}

/* Modern Filter Styling */
.filter-container {
    background-color: #f8f9fc;
    border-radius: 0.35rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    margin-bottom: 1.5rem;
}

.filter-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e3e6f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.filter-header h6 {
    margin: 0;
    font-weight: 700;
    color: #4e73df;
}

.filter-body {
    padding: 1.25rem;
}

.filter-footer {
    padding: 1rem 1.25rem;
    background-color: #f8f9fc;
    border-top: 1px solid #e3e6f0;
}

.month-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.75rem;
}

.month-checkbox {
    display: flex;
    align-items: center;
}

.month-checkbox input[type="checkbox"] {
    margin-right: 0.5rem;
}

.filter-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.35em 0.65em;
    font-size: 0.75em;
    font-weight: 700;
    line-height: 1;
    color: #fff;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.25rem;
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}

.filter-badge .badge-remove {
    margin-left: 0.5rem;
    cursor: pointer;
    opacity: 0.7;
}

.filter-badge .badge-remove:hover {
    opacity: 1;
}

.filter-badge-year {
    background-color: #4e73df;
}

.filter-badge-month {
    background-color: #36b9cc;
}

.active-filters {
    margin-bottom: 1rem;
    padding: 0.75rem;
    background-color: #f8f9fc;
    border-radius: 0.35rem;
    border: 1px solid #e3e6f0;
}

.filter-summary {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
}

.filter-count {
    margin-left: auto;
    font-size: 0.875rem;
    color: #6c757d;
}

.btn-filter {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-filter i {
    margin-right: 0.5rem;
}

.select-all-container {
    margin-bottom: 0.75rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e3e6f0;
}

.year-select {
    width: 100%;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
    color: #6e707e;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #d1d3e2;
    border-radius: 0.35rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.year-select:focus {
    border-color: #bac8f3;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}
</style>';

// Check if the user has admin privileges
if($_SESSION['position'] != 'Admin'){
    echo '<div class="col-md-10 ml-sm-auto px-4"><div class="alert alert-danger mt-3">You do not have permission to access this page.</div></div>';
    include_once $basePath . "includes/footer.php";
    exit;
}

// Include database connection
require_once $basePath . "config/database.php";

// Check if client_id is provided
if(!isset($_GET['client_id']) || empty($_GET['client_id'])) {
    header("location: index.php");
    exit;
}

$client_id = $_GET['client_id'];

// Initialize filter variables
$filter_months = [];
$filter_year = null; // Set to null initially to show all years
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

// Fetch client information
try {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch();
    
    if(!$client) {
        header("location: index.php");
        exit;
    }
    
    // Prepare the base query for SOAs
    $soa_query = "SELECT * FROM client_soa WHERE client_id = ?";
    $query_params = [$client_id];
    
    // Add filters only if filter is applied
    if($filter_applied) {
        // Add month filter if months are selected
        if(!empty($filter_months)) {
            $month_conditions = [];
            foreach($filter_months as $month) {
                $month_conditions[] = "MONTH(issue_date) = ?";
                $query_params[] = $month;
            }
            $soa_query .= " AND (" . implode(" OR ", $month_conditions) . ")";
        }
        
        // Add year filter if year is selected
        if($filter_year !== null) {
            $soa_query .= " AND YEAR(issue_date) = ?";
            $query_params[] = $filter_year;
        }
    }
    
    // Add order by
    $soa_query .= " ORDER BY issue_date DESC";
    
    // Fetch all SOAs for this client with filters
    $stmt = $pdo->prepare($soa_query);
    $stmt->execute($query_params);
    $soas = $stmt->fetchAll();
    
    // Get statistics (always show all stats regardless of filter)
    $stmt = $pdo->prepare("SELECT 
                          COUNT(*) as total_soas,
                          SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
                          SUM(CASE WHEN status = 'Paid' THEN 1 ELSE 0 END) as paid_count,
                          SUM(CASE WHEN status = 'Overdue' THEN 1 ELSE 0 END) as overdue_count,
                          SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_count,
                          SUM(total_amount) as total_amount,
                          SUM(CASE WHEN status = 'Pending' THEN total_amount ELSE 0 END) as pending_amount
                          FROM client_soa 
                          WHERE client_id = ?");
    $stmt->execute([$client_id]);
    $stats = $stmt->fetch();
    
    // Get available years for the filter dropdown
    $stmt = $pdo->prepare("SELECT DISTINCT YEAR(issue_date) as year FROM client_soa WHERE client_id = ? ORDER BY year DESC");
    $stmt->execute([$client_id]);
    $available_years = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // If no years found, use current year
    if(empty($available_years)) {
        $available_years = [date('Y')];
    }
    
} catch(PDOException $e) {
    echo '<div class="alert alert-danger">Database Error: ' . $e->getMessage() . '</div>';
}

// Process delete operation
if(isset($_GET["action"]) && $_GET["action"] == "delete" && isset($_GET["soa_id"]) && !empty($_GET["soa_id"])){
    try {
        // Prepare a delete statement
        $sql = "DELETE FROM client_soa WHERE soa_id = :id AND client_id = :client_id";
        
        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
            $stmt->bindParam(":client_id", $param_client_id, PDO::PARAM_INT);
            
            // Set parameters
            $param_id = trim($_GET["soa_id"]);
            $param_client_id = $client_id;
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Records deleted successfully. Redirect to landing page
                header("location: client_soas.php?client_id=$client_id&success=1");
                exit();
            } else{
                $delete_err = "Oops! Something went wrong. Please try again later.";
            }
        }
    } catch(PDOException $e) {
        $delete_err = "Error: " . $e->getMessage();
    }
}

// Process close account operation
if(isset($_GET["action"]) && $_GET["action"] == "close" && isset($_GET["soa_id"]) && !empty($_GET["soa_id"])){
    try {
        // First check the current status
        $check_stmt = $pdo->prepare("SELECT status FROM client_soa WHERE soa_id = :id");
        $check_stmt->bindParam(":id", $_GET["soa_id"], PDO::PARAM_INT);
        $check_stmt->execute();
        $current_status = $check_stmt->fetchColumn();
        
        // Prepare an update statement to close the account
        $sql = "UPDATE client_soa SET status = 'Closed' WHERE soa_id = :id AND client_id = :client_id";
        
        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
            $stmt->bindParam(":client_id", $param_client_id, PDO::PARAM_INT);
            
            // Set parameters
            $param_id = trim($_GET["soa_id"]);
            $param_client_id = $client_id;
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Account closed successfully. Redirect to landing page
                header("location: client_soas.php?client_id=$client_id&success=4");
                exit();
            } else{
                $close_err = "Oops! Something went wrong. Please try again later.";
            }
        }
    } catch(PDOException $e) {
        $close_err = "Error: " . $e->getMessage();
    }
}

// Define month names array
$month_names = [
    1 => 'January',
    2 => 'February',
    3 => 'March',
    4 => 'April',
    5 => 'May',
    6 => 'June',
    7 => 'July',
    8 => 'August',
    9 => 'September',
    10 => 'October',
    11 => 'November',
    12 => 'December'
];
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">SOAs for <?php echo htmlspecialchars($client['client_name']); ?></h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="add.php?client_id=<?php echo $client_id; ?>" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> Create New SOA
            </a>
            <a href="index.php" class="btn btn-sm btn-secondary ml-2">
                <i class="fas fa-arrow-left"></i> Back to Clients
            </a>
        </div>
    </div>
    
    <?php if(isset($_GET["success"])): ?>
        <div class="alert alert-success">
            <?php 
            if($_GET["success"] == "1") {
                echo "SOA record has been deleted successfully.";
            } elseif($_GET["success"] == "2") {
                echo "SOA record has been added successfully.";
            } elseif($_GET["success"] == "3") {
                echo "SOA record has been updated successfully.";
            } elseif($_GET["success"] == "4") {
                echo "Account has been closed successfully.";
            }
            ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($delete_err)): ?>
        <div class="alert alert-danger">
            <?php echo $delete_err; ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($close_err)): ?>
        <div class="alert alert-danger">
            <?php echo $close_err; ?>
        </div>
    <?php endif; ?>
    
    <!-- Client Information Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Client Information</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Client Name:</strong> <?php echo htmlspecialchars($client['client_name']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($client['address']); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Contact Person:</strong> <?php echo htmlspecialchars($client['pic_name']); ?></p>
                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($client['pic_contact']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($client['pic_email']); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col">
            <div class="card shadow-sm mb-4" style="border-left: 4px solid #4e73df;">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <div>
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            TOTAL SOAS</div>
                        <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_soas'] ?? 0; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-file-invoice fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col">
            <div class="card shadow-sm mb-4" style="border-left: 4px solid #1cc88a;">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <div>
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            PAID SOAS</div>
                        <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo $stats['paid_count'] ?? 0; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col">
            <div class="card shadow-sm mb-4" style="border-left: 4px solid #f6c23e;">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <div>
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            PENDING SOAS</div>
                        <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo $stats['pending_count'] ?? 0; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col">
            <div class="card shadow-sm mb-4" style="border-left: 4px solid #e74a3b;">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <div>
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                            OVERDUE SOAS</div>
                        <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo $stats['overdue_count'] ?? 0; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col">
            <div class="card shadow-sm mb-4" style="border-left: 4px solid #858796;">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <div>
                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                            CLOSED SOAS</div>
                        <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo $stats['closed_count'] ?? 0; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-lock fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- SOA List Card with Modern Filter -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">SOA List for <?php echo htmlspecialchars($client['client_name']); ?></h6>
            <button class="btn btn-sm btn-outline-primary" type="button" data-toggle="collapse" data-target="#filterCollapse" aria-expanded="<?php echo $filter_applied ? 'true' : 'false'; ?>">
                <i class="fas fa-filter"></i> Filter Options
            </button>
        </div>
        
        <!-- Modern Filter UI -->
        <div class="collapse <?php echo $filter_applied ? 'show' : ''; ?>" id="filterCollapse">
            <div class="card-body bg-light border-bottom">
                <form method="post" class="filter-form" id="filterForm">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="year" class="font-weight-bold text-primary">Year:</label>
                                <select class="form-control year-select" id="year" name="year">
                                    <option value="">All Years</option>
                                    <?php foreach($available_years as $year): ?>
                                    <option value="<?php echo $year; ?>" <?php echo ($year == $filter_year) ? 'selected' : ''; ?>>
                                        <?php echo $year; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <label class="font-weight-bold text-primary">Month(s):</label>
                            <div class="select-all-container">
                                <div class="month-checkbox">
                                    <input type="checkbox" id="select_all_months" class="select-all-checkbox">
                                    <label for="select_all_months" class="font-weight-bold">Select All Months</label>
                                </div>
                            </div>
                            
                            <div class="month-grid">
                                <?php foreach($month_names as $month_num => $month_name): ?>
                                <div class="month-checkbox">
                                    <input type="checkbox" id="month_<?php echo $month_num; ?>" name="months[]" value="<?php echo $month_num; ?>" 
                                        <?php echo (in_array($month_num, $filter_months)) ? 'checked' : ''; ?> class="month-checkbox-input">
                                    <label for="month_<?php echo $month_num; ?>"><?php echo $month_name; ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <button type="submit" name="filter_submit" class="btn btn-primary btn-filter">
                                <i class="fas fa-filter"></i> Apply Filter
                            </button>
                            <button type="button" id="clearFilter" class="btn btn-secondary btn-filter ml-2">
                                <i class="fas fa-times"></i> Clear Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if($filter_applied): ?>
        <div class="card-body border-bottom active-filters">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="font-weight-bold mb-2">Active Filters:</h6>
                    <div class="filter-summary">
                        <span class="filter-badge filter-badge-year">
                            Year: <?php echo ($filter_year !== null) ? $filter_year : 'All Years'; ?>
                        </span>
                        <?php 
                        if(!empty($filter_months)) {
                            foreach($filter_months as $month) {
                                echo '<span class="filter-badge filter-badge-month">' . $month_names[$month] . '</span>';
                            }
                        } else {
                            echo '<span class="filter-badge filter-badge-month">All Months</span>';
                        }
                        ?>
                    </div>
                </div>
                <div class="filter-count">
                    <span class="badge badge-pill badge-light">
                        <i class="fas fa-table mr-1"></i> <?php echo count($soas); ?> records found
                    </span>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Account #</th>
                            <th>Issue Date</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(isset($soas) && !empty($soas)): ?>
                            <?php foreach($soas as $soa): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($soa['account_number']); ?></td>
                                <td><?php echo htmlspecialchars($soa['issue_date']); ?></td>
                                <td><?php echo htmlspecialchars($soa['due_date']); ?></td>
                                <td>RM <?php echo number_format($soa['total_amount'], 2); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo ($soa['status'] == 'Paid') ? 'success' : 
                                            (($soa['status'] == 'Overdue') ? 'danger' : 
                                             (($soa['status'] == 'Closed') ? 'secondary' : 'warning')); 
                                    ?>">
                                        <?php echo htmlspecialchars($soa['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view.php?id=<?php echo $soa['soa_id']; ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if($soa['status'] != 'Closed'): ?>
                                    <a href="edit.php?id=<?php echo $soa['soa_id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="generate_pdf.php?id=<?php echo $soa['soa_id']; ?>" class="btn btn-success btn-sm" target="_blank">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                    <?php if($soa['status'] != 'Closed'): ?>
                                    <a href="client_soas.php?client_id=<?php echo $client_id; ?>&action=delete&soa_id=<?php echo $soa['soa_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this SOA?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="javascript:void(0);" class="btn btn-dark btn-sm" onclick="confirmCloseAccount(<?php echo $soa['soa_id']; ?>, '<?php echo $soa['status']; ?>', <?php echo $client_id; ?>)">
                                        <i class="fas fa-lock"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No SOA records found for this client<?php echo $filter_applied ? ' with the selected filters' : ''; ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function confirmCloseAccount(soaId, status, clientId) {
    let confirmMessage = '';
    
    if (status === 'Paid') {
        confirmMessage = 'Are you sure you want to close this account? This action cannot be undone.';
    } else if (status === 'Pending') {
        confirmMessage = 'WARNING: This account is still PENDING. Are you sure you want to close it? This action cannot be undone.';
    } else if (status === 'Overdue') {
        confirmMessage = 'WARNING: This account is OVERDUE. Are you sure you want to close it? This action cannot be undone.';
    }
    
    if (confirm(confirmMessage)) {
        window.location.href = `client_soas.php?client_id=${clientId}&action=close&soa_id=${soaId}`;
    }
}

// Select All / Deselect All functionality
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select_all_months');
    const monthCheckboxes = document.querySelectorAll('.month-checkbox-input');
    
    // Initialize "Select All" checkbox state
    function updateSelectAllCheckbox() {
        const checkedCount = document.querySelectorAll('.month-checkbox-input:checked').length;
        selectAllCheckbox.checked = checkedCount === monthCheckboxes.length;
        selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < monthCheckboxes.length;
    }
    
    // Set initial state
    updateSelectAllCheckbox();
    
    // Add event listener to "Select All" checkbox
    selectAllCheckbox.addEventListener('change', function() {
        monthCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });
    
    // Add event listeners to month checkboxes
    monthCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectAllCheckbox);
    });
    
    // clear filter function
    document.getElementById('clearFilter').addEventListener('click', function() {
        // uncheck all month checkboxes
        monthCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
    
        // Reset year to "All Years"
        const yearSelect = document.getElementById('year');
        yearSelect.value = '';
        
        // Update select all checkbox state
        updateSelectAllCheckbox();
        
        // Submit the form
        document.getElementById('filterForm').submit();
    });
});
</script>

<?php
// Include footer
include_once $basePath . "includes/footer.php";
ob_end_flush();
?>
