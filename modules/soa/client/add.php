<?php
ob_start();
$basePath = '../../../';
require_once $basePath . "config/database.php";

session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['position'] != 'Admin'){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}

$errors = [];
$form_data = [
    'account_number' => '', 'client_id' => '', 'terms' => 'Net 30 Days', 
    'purchase_date' => date('Y-m-d'), 'issue_date' => date('Y-m-d'), 'due_date' => date('Y-m-d', strtotime('+30 days')),
    'po_number' => '', 'invoice_number' => '', 'service_description' => '', 
    'total_amount' => '', 'status' => 'Pending'
];

$preselected_client = isset($_GET['client_id']) ? $_GET['client_id'] : '';
if(!empty($preselected_client)) {
    $form_data['client_id'] = $preselected_client;
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Overwrite defaults with posted data
    $form_data = array_merge($form_data, $_POST);

    // Sanitize and validate inputs
    $account_number = trim($form_data['account_number']);
    $client_id = $form_data['client_id'];
    $issue_date = $form_data['issue_date'];
    $due_date = $form_data['due_date'];
    $total_amount = filter_var($form_data['total_amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $status = $form_data['status'];

    // Basic validation
    if(empty($account_number)) $errors['account_number'] = "Account number is required.";
    if(empty($client_id)) $errors['client_id'] = "Please select a client.";
    if(empty($issue_date)) $errors['issue_date'] = "Issue date is required.";
    if(empty($due_date)) $errors['due_date'] = "Due date is required.";
    if(empty($total_amount) || !is_numeric($total_amount)) $errors['total_amount'] = "A valid total amount is required.";
    if(empty($status)) $errors['status'] = "Status is required.";

    if(empty($errors)){
        $sql = "INSERT INTO client_soa (account_number, client_id, terms, purchase_date, issue_date, due_date, po_number, invoice_number, service_description, total_amount, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        if($stmt = $pdo->prepare($sql)){
            $created_by = $_SESSION["staff_id"];
            $stmt->execute([
                $account_number, $client_id, trim($form_data['terms']), $form_data['purchase_date'], 
                $issue_date, $due_date, trim($form_data['po_number']), trim($form_data['invoice_number']), 
                trim($form_data['service_description']), $total_amount, $status, $created_by
            ]);
            $last_id = $pdo->lastInsertId();
            header("location: view.php?id=" . $last_id . "&success=added");
            exit();
        } else {
            $general_err = "Failed to prepare the insert statement.";
        }
    }
}

try {
    $clients = $pdo->query("SELECT client_id, client_name FROM clients ORDER BY client_name")->fetchAll();
} catch(PDOException $e) {
    $db_error = "Could not fetch clients.";
    $clients = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Client SOA - SOA Management System</title>
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
                        <h1>Create New Client SOA</h1>
                        <p>Fill in the details to generate a new Statement of Account</p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="index.php" class="export-btn secondary"><i class="fas fa-arrow-left"></i> Back to Client List</a>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <?php if(isset($general_err) || isset($db_error)): ?>
                <div class="alert alert-error" data-aos="fade-down">
                    <div class="alert-content"><i class="fas fa-exclamation-circle"></i><span><?php echo $general_err ?? $db_error; ?></span></div>
                </div>
            <?php endif; ?>

            <div class="form-card" data-aos="fade-up">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?><?php if($preselected_client) echo '?client_id='.$preselected_client; ?>" method="post" class="modern-form">
                    <div class="form-body">
                        <div class="form-section">
                            <div class="section-header"><h4><i class="fas fa-user-tie"></i> Client & Account Details</h4></div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label required"><i class="fas fa-user-tie"></i> Client</label>
                                    <select name="client_id" class="form-input <?php echo isset($errors['client_id']) ? 'error' : ''; ?>">
                                        <option value="">Select a client...</option>
                                        <?php foreach($clients as $client): ?>
                                            <option value="<?php echo $client['client_id']; ?>" <?php echo ($client['client_id'] == $form_data['client_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($client['client_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if(isset($errors['client_id'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['client_id']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label required"><i class="fas fa-hashtag"></i> Account Number</label>
                                    <input type="text" name="account_number" class="form-input <?php echo isset($errors['account_number']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($form_data['account_number']); ?>" placeholder="e.g., SOA-2024-001">
                                    <?php if(isset($errors['account_number'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['account_number']; ?></span><?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="section-header"><h4><i class="fas fa-calendar-alt"></i> Dates & Terms</h4></div>
                            <div class="form-grid three-cols">
                                <div class="form-group">
                                    <label class="form-label">Purchase Date</label>
                                    <input type="date" name="purchase_date" class="form-input" value="<?php echo htmlspecialchars($form_data['purchase_date']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label required">Issue Date</label>
                                    <input type="date" name="issue_date" class="form-input <?php echo isset($errors['issue_date']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($form_data['issue_date']); ?>">
                                    <?php if(isset($errors['issue_date'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['issue_date']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label required">Due Date</label>
                                    <input type="date" name="due_date" class="form-input <?php echo isset($errors['due_date']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($form_data['due_date']); ?>">
                                    <?php if(isset($errors['due_date'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['due_date']; ?></span><?php endif; ?>
                                </div>
                            </div>
                             <div class="form-group">
                                <label class="form-label">Terms</label>
                                <input type="text" name="terms" class="form-input" value="<?php echo htmlspecialchars($form_data['terms']); ?>" placeholder="e.g., Net 30 Days">
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="section-header"><h4><i class="fas fa-dollar-sign"></i> Financial Details</h4></div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">PO Number</label>
                                    <input type="text" name="po_number" class="form-input" value="<?php echo htmlspecialchars($form_data['po_number']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Invoice Number</label>
                                    <input type="text" name="invoice_number" class="form-input" value="<?php echo htmlspecialchars($form_data['invoice_number']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label required">Total Amount (RM)</label>
                                    <input type="number" step="0.01" name="total_amount" class="form-input <?php echo isset($errors['total_amount']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($form_data['total_amount']); ?>">
                                    <?php if(isset($errors['total_amount'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['total_amount']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label required">Status</label>
                                    <select name="status" class="form-input <?php echo isset($errors['status']) ? 'error' : ''; ?>">
                                        <option value="Pending" <?php echo ($form_data['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Paid" <?php echo ($form_data['status'] == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                                        <option value="Overdue" <?php echo ($form_data['status'] == 'Overdue') ? 'selected' : ''; ?>>Overdue</option>
                                    </select>
                                    <?php if(isset($errors['status'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['status']; ?></span><?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="section-header"><h4><i class="fas fa-align-left"></i> Service Description</h4></div>
                            <div class="form-group full-width">
                                <textarea name="service_description" class="form-textarea" rows="4" placeholder="Enter service or product details..."><?php echo htmlspecialchars($form_data['service_description']); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create SOA</button>
                        <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
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
        .form-card{background:white;border-radius:var(--border-radius);box-shadow:var(--shadow);border:1px solid var(--gray-200);overflow:hidden}.form-body{padding:2rem}.form-actions{padding:1.5rem 2rem;border-top:1px solid var(--gray-200);background:var(--gray-50);display:flex;gap:1rem}.form-section{margin-bottom:2.5rem}.form-section:last-child{margin-bottom:0}.section-header{margin-bottom:1.5rem;padding-bottom:.75rem;border-bottom:1px solid var(--gray-200)}.section-header h4{display:flex;align-items:center;gap:.5rem;color:var(--gray-900);font-size:1.125rem;font-weight:600;margin:0}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}.form-grid.three-cols{grid-template-columns:1fr 1fr 1fr}.form-group{display:flex;flex-direction:column;gap:.5rem}.form-group.full-width{grid-column:1 / -1}.form-label{display:flex;align-items:center;gap:.5rem;font-size:.875rem;font-weight:500;color:var(--gray-700)}.form-label.required::after{content:'*';color:var(--danger-color);margin-left:.25rem}.form-input,.form-textarea{padding:.75rem;border:1px solid var(--gray-300);border-radius:var(--border-radius-sm);font-size:.875rem;transition:var(--transition);background:white}.form-input:focus,.form-textarea:focus{outline:0;border-color:var(--primary-color);box-shadow:0 0 0 3px rgba(59,130,246,.1)}.form-input.error,.form-textarea.error{border-color:var(--danger-color);box-shadow:0 0 0 3px rgba(239,68,68,.1)}.form-textarea{resize:vertical;min-height:80px}.error-message{display:flex;align-items:center;gap:.5rem;color:var(--danger-color);font-size:.75rem;font-weight:500}.btn{display:inline-flex;align-items:center;gap:.5rem;padding:.75rem 1.5rem;border:none;border-radius:var(--border-radius-sm);font-size:.875rem;font-weight:500;text-decoration:none;cursor:pointer;transition:var(--transition)}.btn-primary{background:var(--primary-color);color:white}.btn-primary:hover{background:var(--primary-dark)}.btn-secondary{background:var(--gray-200);color:var(--gray-800)}.btn-secondary:hover{background:var(--gray-300)}.alert{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.5rem;border-radius:var(--border-radius);margin-bottom:1.5rem;border:1px solid}.alert-error{background:rgba(239,68,68,.1);border-color:var(--danger-color);color:var(--danger-color)}.alert-content{display:flex;align-items:center;gap:.75rem}@media (max-width:768px){.form-grid,.form-grid.three-cols{grid-template-columns:1fr}}
    </style>
</body>
</html>
<?php ob_end_flush(); ?>
