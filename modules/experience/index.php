<?php
ob_start();
$basePath = '../../';
require_once $basePath . "config/database.php";

session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}

// Only Admin/Manager can access
if($_SESSION['position'] != 'Admin' && $_SESSION['position'] != 'Manager'){
    header("location: " . $basePath . "dashboard.php");
    exit;
}

// Fetch filter parameter
$filter_category = isset($_GET['category_id']) ? $_GET['category_id'] : '';

try {
    $categories = $pdo->query("SELECT category_id, category_name FROM experience_categories ORDER BY category_name")->fetchAll();

    $sql = "SELECT ce.*, ec.category_name, c.client_name
            FROM company_experiences ce
            JOIN experience_categories ec ON ce.category_id = ec.category_id
            LEFT JOIN clients c ON ce.client_id = c.client_id";
    $params = [];
    if(!empty($filter_category)){
        $sql .= " WHERE ce.category_id = ?";
        $params[] = $filter_category;
    }
    $sql .= " ORDER BY ec.category_name ASC, ce.contract_year DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("ERROR: Could not fetch data. " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Experience - SOA Management System</title>
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
                        <h1>Pengalaman Syarikat</h1>
                        <p>Company Experience Records for Tender</p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="generate_pdf.php<?php echo !empty($filter_category) ? '?category_id='.$filter_category : ''; ?>" target="_blank" class="export-btn success"><i class="fas fa-file-pdf"></i> Generate PDF Report</a>
                    <a href="add.php" class="export-btn primary"><i class="fas fa-plus"></i> Add Experience</a>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success" data-aos="fade-down">
                    <div class="alert-content"><i class="fas fa-check-circle"></i>
                        <span>
                            <?php
                            if($_GET['success'] == 'added') echo "Experience record added successfully.";
                            elseif($_GET['success'] == 'updated') echo "Experience record updated successfully.";
                            elseif($_GET['success'] == 'deleted') echo "Experience record deleted successfully.";
                            ?>
                        </span>
                    </div>
                    <button class="alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>

            <!-- Filter Bar -->
            <div class="filter-bar" data-aos="fade-down">
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label><i class="fas fa-filter"></i> Filter by Category:</label>
                        <select name="category_id" onchange="this.form.submit()" class="filter-select">
                            <option value="">All Categories</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>" <?php echo ($filter_category == $cat['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
                <div class="filter-info">
                    <span class="badge"><?php echo count($experiences); ?> record(s)</span>
                </div>
            </div>

            <!-- Experience Table -->
            <div class="table-card" data-aos="fade-up">
                <div class="table-container">
                    <table class="modern-table" id="experienceTable">
                        <thead>
                            <tr>
                                <th style="width:50px;">BIL</th>
                                <th>Agency / Client</th>
                                <th>Contract Name</th>
                                <th style="width:80px;">Year</th>
                                <th style="width:140px;">Amount (RM)</th>
                                <th>Category</th>
                                <th style="width:120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($experiences)): ?>
                                <?php $bil = 1; foreach($experiences as $exp): ?>
                                <tr>
                                    <td style="text-align:center;"><?php echo $bil++; ?></td>
                                    <td><?php echo htmlspecialchars($exp['agency_name']); ?></td>
                                    <td><?php echo htmlspecialchars($exp['contract_name']); ?></td>
                                    <td style="text-align:center;"><?php echo htmlspecialchars($exp['contract_year']); ?></td>
                                    <td style="text-align:right;font-weight:600;">RM <?php echo number_format($exp['amount'], 2); ?></td>
                                    <td><span class="category-badge"><?php echo htmlspecialchars($exp['category_name']); ?></span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit.php?id=<?php echo $exp['experience_id']; ?>" class="action-btn action-btn-edit" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="delete.php?id=<?php echo $exp['experience_id']; ?>" class="action-btn action-btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this experience record?');"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--gray-500);">No experience records found.</td></tr>
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
        .filter-bar{background:white;border-radius:var(--border-radius);box-shadow:var(--shadow);border:1px solid var(--gray-200);padding:1rem 1.5rem;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.filter-form{display:flex;align-items:center;gap:1rem}.filter-group{display:flex;align-items:center;gap:.5rem;font-size:.875rem;color:var(--gray-700)}.filter-group label{font-weight:500;white-space:nowrap}.filter-select{padding:.5rem .75rem;border:1px solid var(--gray-300);border-radius:var(--border-radius-sm);font-size:.875rem;background:white;cursor:pointer}.filter-select:focus{outline:0;border-color:var(--primary-color);box-shadow:0 0 0 3px rgba(59,130,246,.1)}.filter-info .badge{background:var(--primary-color);color:white;padding:.375rem .75rem;border-radius:9999px;font-size:.75rem;font-weight:600}.category-badge{display:inline-flex;align-items:center;padding:.375rem .75rem;background:rgba(139,92,246,.1);color:#7c3aed;border-radius:9999px;font-size:.75rem;font-weight:600}.table-card{background:white;border-radius:var(--border-radius);box-shadow:var(--shadow);border:1px solid var(--gray-200);overflow:hidden}.table-container{overflow-x:auto}.modern-table{width:100%;border-collapse:collapse}.modern-table thead{background:var(--gray-50)}.modern-table th{padding:1rem;font-size:.75rem;font-weight:600;color:var(--gray-600);text-transform:uppercase;letter-spacing:.05em;border-bottom:2px solid var(--gray-200);text-align:left}.modern-table td{padding:.875rem 1rem;font-size:.875rem;color:var(--gray-800);border-bottom:1px solid var(--gray-100)}.modern-table tbody tr:hover{background:var(--gray-50)}.action-buttons{display:flex;gap:.5rem}.action-btn{width:32px;height:32px;border-radius:var(--border-radius-sm);display:flex;align-items:center;justify-content:center;text-decoration:none;transition:var(--transition);font-size:.875rem}.action-btn-edit{background:rgba(59,130,246,.1);color:var(--primary-color)}.action-btn-edit:hover{background:var(--primary-color);color:white}.action-btn-delete{background:rgba(239,68,68,.1);color:var(--danger-color)}.action-btn-delete:hover{background:var(--danger-color);color:white}.alert{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.5rem;border-radius:var(--border-radius);margin-bottom:1.5rem;border:1px solid}.alert-success{background:rgba(16,185,129,.1);border-color:var(--success-color);color:var(--success-color)}.alert-content{display:flex;align-items:center;gap:.75rem}.alert-close{background:0 0;border:none;color:inherit;cursor:pointer;padding:.25rem;border-radius:var(--border-radius-sm);transition:var(--transition)}.alert-close:hover{background:rgba(0,0,0,.1)}
    </style>
</body>
</html>
<?php ob_end_flush(); ?>
