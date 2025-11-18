<?php
ob_start();
$basePath = '../../../';
require_once $basePath . "config/database.php";

session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['position'] != 'Admin'){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}

$soa_id = $_GET['id'] ?? null;
if(!$soa_id) { header("location: index.php"); exit; }

$errors = [];
// Initial fetch for form population
try {
    $stmt = $pdo->prepare("SELECT cs.*, c.client_name FROM client_soa cs JOIN clients c ON cs.client_id = c.client_id WHERE cs.soa_id = ?");
    $stmt->execute([$soa_id]);
    $soa = $stmt->fetch();
    if(!$soa) { header("location: index.php"); exit; }
    
    $clients = $pdo->query("SELECT client_id, client_name FROM clients ORDER BY client_name")->fetchAll();
} catch(PDOException $e) {
    $db_error = "Database error during initial load.";
    $soa = []; // Prevent errors on form if fetch fails
    $clients = [];
}


if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Sanitize and validate inputs
    $account_number = trim($_POST['account_number']);
    $client_id = $_POST['client_id'];
    $terms = trim($_POST['terms']);
    $purchase_date = $_POST['purchase_date'];
    $issue_date = $_POST['issue_date'];
    $due_date = $_POST['due_date'];
    $po_number = trim($_POST['po_number']);
    $invoice_number = trim($_POST['invoice_number']);
    $service_description = trim($_POST['service_description']);
    $total_amount = filter_var($_POST['total_amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $status = $_POST['status'];

    // Basic validation
    if(empty($account_number)) $errors['account_number'] = "Account number is required.";
    if(empty($client_id)) $errors['client_id'] = "Please select a client.";
    if(empty($issue_date)) $errors['issue_date'] = "Issue date is required.";
    if(empty($due_date)) $errors['due_date'] = "Due date is required.";
    if(empty($total_amount) || !is_numeric($total_amount)) $errors['total_amount'] = "A valid total amount is required.";
    if(empty($status)) $errors['status'] = "Status is required.";

    if(empty($errors)){
        $sql = "UPDATE client_soa SET account_number = ?, client_id = ?, terms = ?, purchase_date = ?, issue_date = ?, due_date = ?, po_number = ?, invoice_number = ?, service_description = ?, total_amount = ?, status = ? WHERE soa_id = ?";
        if($stmt = $pdo->prepare($sql)){
            $stmt->execute([$account_number, $client_id, $terms, $purchase_date, $issue_date, $due_date, $po_number, $invoice_number, $service_description, $total_amount, $status, $soa_id]);
            header("location: view.php?id=" . $soa_id . "&success=updated");
            exit();
        } else {
            $general_err = "Failed to prepare the update statement.";
        }
    } else {
        // Repopulate form with submitted values if there are errors
        $soa = array_merge($soa, $_POST);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Client SOA - SOA Management System</title>
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
                        <h1>Edit Client SOA</h1>
                        <p>Update details for Account #<?php echo htmlspecialchars($soa['account_number']); ?></p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="view.php?id=<?php echo $soa_id; ?>" class="export-btn info"><i class="fas fa-eye"></i> View SOA</a>
                    <a href="client_soas.php?client_id=<?php echo $soa['client_id']; ?>" class="export-btn secondary"><i class="fas fa-arrow-left"></i> Back to List</a>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <?php if(isset($general_err)): ?>
                <div class="alert alert-error" data-aos="fade-down">
                    <div class="alert-content"><i class="fas fa-exclamation-circle"></i><span><?php echo $general_err; ?></span></div>
                </div>
            <?php endif; ?>

            <div class="profile-header" data-aos="fade-down">
                <div class="profile-avatar" style="background-color: var(--warning-color);"><i class="fas fa-edit"></i></div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($soa['client_name']); ?></h2>
                    <p class="profile-subtitle">Editing Account #<?php echo htmlspecialchars($soa['account_number']); ?></p>
                    <div class="profile-meta">
                        <span class="meta-item"><i class="fas fa-file-invoice-dollar"></i> RM <?php echo number_format($soa['total_amount'], 2); ?></span>
                        <span class="meta-item"><span class="status-badge status-<?php echo strtolower($soa['status']); ?>"><?php echo htmlspecialchars($soa['status']); ?></span></span>
                    </div>
                </div>
            </div>

            <div class="form-card" data-aos="fade-up">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $soa_id); ?>" method="post" class="modern-form">
                    <div class="form-body">
                        <div class="form-section">
                            <div class="section-header"><h4><i class="fas fa-user-tie"></i> Client & Account Details</h4></div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label required"><i class="fas fa-user-tie"></i> Client</label>
                                    <select name="client_id" class="form-input <?php echo isset($errors['client_id']) ? 'error' : ''; ?>">
                                        <?php foreach($clients as $client): ?>
                                            <option value="<?php echo $client['client_id']; ?>" <?php echo ($client['client_id'] == $soa['client_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($client['client_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if(isset($errors['client_id'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['client_id']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label required"><i class="fas fa-hashtag"></i> Account Number</label>
                                    <input type="text" name="account_number" class="form-input <?php echo isset($errors['account_number']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($soa['account_number']); ?>">
                                    <?php if(isset($errors['account_number'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['account_number']; ?></span><?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="section-header"><h4><i class="fas fa-calendar-alt"></i> Dates & Terms</h4></div>
                            <div class="form-grid three-cols">
                                <div class="form-group">
                                    <label class="form-label">Purchase Date</label>
                                    <input type="date" name="purchase_date" class="form-input" value="<?php echo htmlspecialchars($soa['purchase_date']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label required">Issue Date</label>
                                    <input type="date" name="issue_date" class="form-input <?php echo isset($errors['issue_date']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($soa['issue_date']); ?>">
                                    <?php if(isset($errors['issue_date'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['issue_date']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label required">Due Date</label>
                                    <input type="date" name="due_date" class="form-input <?php echo isset($errors['due_date']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($soa['due_date']); ?>">
                                    <?php if(isset($errors['due_date'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['due_date']; ?></span><?php endif; ?>
                                </div>
                            </div>
                             <div class="form-group">
                                <label class="form-label">Terms</label>
                                <input type="text" name="terms" class="form-input" value="<?php echo htmlspecialchars($soa['terms']); ?>" placeholder="e.g., Net 30 Days">
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="section-header"><h4><i class="fas fa-dollar-sign"></i> Financial Details</h4></div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">PO Number</label>
                                    <input type="text" name="po_number" class="form-input" value="<?php echo htmlspecialchars($soa['po_number']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Invoice Number</label>
                                    <input type="text" name="invoice_number" class="form-input" value="<?php echo htmlspecialchars($soa['invoice_number']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label required">Total Amount (RM)</label>
                                    <input type="number" step="0.01" name="total_amount" class="form-input <?php echo isset($errors['total_amount']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($soa['total_amount']); ?>">
                                    <?php if(isset($errors['total_amount'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['total_amount']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label required">Status</label>
                                    <select name="status" class="form-input <?php echo isset($errors['status']) ? 'error' : ''; ?>">
                                        <option value="Pending" <?php echo ($soa['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Paid" <?php echo ($soa['status'] == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                                        <option value="Overdue" <?php echo ($soa['status'] == 'Overdue') ? 'selected' : ''; ?>>Overdue</option>
                                        <option value="Closed" <?php echo ($soa['status'] == 'Closed') ? 'selected' : ''; ?>>Closed</option>
                                    </select>
                                    <?php if(isset($errors['status'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['status']; ?></span><?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="section-header"><h4><i class="fas fa-align-left"></i> Service Description</h4></div>
                            <div class="form-group full-width">
                                <textarea name="service_description" class="form-textarea" rows="4" placeholder="Enter service or product details..."><?php echo htmlspecialchars($soa['service_description']); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update SOA</button>
                        <a href="view.php?id=<?php echo $soa_id; ?>" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                    </div>
                </form>
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
        .profile-header{background:white;border-radius:var(--border-radius);box-shadow:var(--shadow);border:1px solid var(--gray-200);padding:2rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:1.5rem}.profile-avatar{width:80px;height:80px;background:var(--warning-color);border-radius:var(--border-radius);display:flex;align-items:center;justify-content:center;color:white;font-size:2rem;flex-shrink:0}.profile-info h2{color:var(--gray-900);margin-bottom:.25rem;font-size:1.5rem;font-weight:600}.profile-subtitle{color:var(--gray-600);margin-bottom:1rem}.profile-meta{display:flex;flex-wrap:wrap;gap:1.5rem}.meta-item{display:flex;align-items:center;gap:.5rem;color:var(--gray-600);font-size:.875rem}.meta-item i{color:var(--gray-400)}.status-badge{display:inline-flex;align-items:center;padding:.375rem .75rem;border-radius:9999px;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em}.status-badge.status-paid{background:rgba(16,185,129,.1);color:var(--success-color)}.status-badge.status-pending{background:rgba(245,158,11,.1);color:var(--warning-color)}.status-badge.status-overdue{background:rgba(239,68,68,.1);color:var(--danger-color)}.status-badge.status-closed{background:rgba(107,114,128,.1);color:var(--gray-600)}.form-card{background:white;border-radius:var(--border-radius);box-shadow:var(--shadow);border:1px solid var(--gray-200);overflow:hidden}.form-body{padding:2rem}.form-actions{padding:1.5rem 2rem;border-top:1px solid var(--gray-200);background:var(--gray-50);display:flex;gap:1rem}.form-section{margin-bottom:2.5rem}.form-section:last-child{margin-bottom:0}.section-header{margin-bottom:1.5rem;padding-bottom:.75rem;border-bottom:1px solid var(--gray-200)}.section-header h4{display:flex;align-items:center;gap:.5rem;color:var(--gray-900);font-size:1.125rem;font-weight:600;margin:0}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}.form-grid.three-cols{grid-template-columns:1fr 1fr 1fr}.form-group{display:flex;flex-direction:column;gap:.5rem}.form-group.full-width{grid-column:1 / -1}.form-label{display:flex;align-items:center;gap:.5rem;font-size:.875rem;font-weight:500;color:var(--gray-700)}.form-label.required::after{content:'*';color:var(--danger-color);margin-left:.25rem}.form-input,.form-textarea{padding:.75rem;border:1px solid var(--gray-300);border-radius:var(--border-radius-sm);font-size:.875rem;transition:var(--transition);background:white}.form-input:focus,.form-textarea:focus{outline:0;border-color:var(--primary-color);box-shadow:0 0 0 3px rgba(59,130,246,.1)}.form-input.error,.form-textarea.error{border-color:var(--danger-color);box-shadow:0 0 0 3px rgba(239,68,68,.1)}.form-textarea{resize:vertical;min-height:80px}.error-message{display:flex;align-items:center;gap:.5rem;color:var(--danger-color);font-size:.75rem;font-weight:500}.btn{display:inline-flex;align-items:center;gap:.5rem;padding:.75rem 1.5rem;border:none;border-radius:var(--border-radius-sm);font-size:.875rem;font-weight:500;text-decoration:none;cursor:pointer;transition:var(--transition)}.btn-primary{background:var(--primary-color);color:white}.btn-primary:hover{background:var(--primary-dark)}.btn-secondary{background:var(--gray-200);color:var(--gray-800)}.btn-secondary:hover{background:var(--gray-300)}@media (max-width:768px){.profile-header{flex-direction:column;text-align:center}.form-grid,.form-grid.three-cols{grid-template-columns:1fr}}
    </style>
</body>
</html>
<?php ob_end_flush(); ?>
