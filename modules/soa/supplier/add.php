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
    'invoice_number' => '', 'supplier_id' => '', 'issue_date' => date('Y-m-d'), 
    'payment_due_date' => date('Y-m-d', strtotime('+30 days')), 'purchase_description' => '', 
    'amount' => '', 'payment_status' => 'Pending', 'payment_method' => ''
];

$preselected_supplier = isset($_GET['supplier_id']) ? $_GET['supplier_id'] : '';
if(!empty($preselected_supplier)) {
    $form_data['supplier_id'] = $preselected_supplier;
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $form_data = array_merge($form_data, $_POST);

    if(empty(trim($form_data["invoice_number"]))) $errors['invoice_number'] = "Please enter invoice number.";
    if(empty($form_data["supplier_id"])) $errors['supplier_id'] = "Please select a supplier.";
    if(empty($form_data["issue_date"])) $errors['issue_date'] = "Please enter issue date.";
    if(empty($form_data["payment_due_date"])) $errors['payment_due_date'] = "Please enter payment due date.";
    if(empty(trim($form_data["purchase_description"]))) $errors['purchase_description'] = "Please enter purchase description.";
    if(empty(trim($form_data["amount"])) || !is_numeric(trim($form_data["amount"])) || floatval(trim($form_data["amount"])) <= 0) $errors['amount'] = "Please enter a valid positive amount.";
    if(empty($form_data["payment_status"])) $errors['payment_status'] = "Please select payment status.";

    if(empty($errors)){
        $sql = "INSERT INTO supplier_soa (invoice_number, supplier_id, issue_date, payment_due_date, purchase_description, amount, payment_status, payment_method, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        if($stmt = $pdo->prepare($sql)){
            $created_by = $_SESSION["staff_id"];
            $stmt->execute([
                trim($form_data['invoice_number']), $form_data['supplier_id'], $form_data['issue_date'], 
                $form_data['payment_due_date'], trim($form_data['purchase_description']), trim($form_data['amount']), 
                $form_data['payment_status'], trim($form_data['payment_method']), $created_by
            ]);
            header("location: index.php?success=added");
            exit();
        }
    }
}

try {
    $suppliers = $pdo->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name")->fetchAll();
} catch(PDOException $e) {
    $suppliers = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Supplier SOA - SOA Management System</title>
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
        .export-btn.secondary { background-color: var(--secondary-color); } .export-btn.secondary:hover { background-color: var(--gray-700); }
        .dashboard-content { padding: 2rem; max-width: 1600px; margin: 0 auto; }
        .form-card { background: white; border-radius: var(--border-radius); box-shadow: var(--shadow); border: 1px solid var(--gray-200); }
        .modern-form { padding: 2rem; }
        .form-section { margin-bottom: 2.5rem; }
        .section-header { border-bottom: 1px solid var(--gray-200); padding-bottom: 0.75rem; margin-bottom: 1.5rem; }
        .section-header h4 { font-size: 1.125rem; font-weight: 600; color: var(--gray-800); display: flex; align-items: center; gap: 0.75rem; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-label { display: block; font-weight: 500; color: var(--gray-700); margin-bottom: 0.5rem; font-size: 0.875rem; }
        .form-label.required::after { content: ' *'; color: var(--danger-color); }
        .form-input, .form-textarea, .form-select { width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--gray-300); border-radius: var(--border-radius-sm); background: var(--gray-50); transition: var(--transition); }
        .form-input:focus, .form-textarea:focus, .form-select:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2); background: white; }
        .form-input.error, .form-textarea.error, .form-select.error { border-color: var(--danger-color); }
        .error-message { color: var(--danger-color); font-size: 0.75rem; margin-top: 0.25rem; display: flex; align-items: center; gap: 0.25rem; }
        .form-actions { display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1rem; }
        .btn { padding: 0.75rem 1.5rem; border-radius: var(--border-radius-sm); font-weight: 500; text-decoration: none; transition: var(--transition); display: inline-flex; align-items: center; gap: 0.5rem; border: 1px solid transparent; cursor: pointer; }
        .btn-primary { background: var(--primary-color); color: white; } .btn-primary:hover { background: var(--primary-dark); }
        .btn-secondary { background: var(--gray-200); color: var(--gray-800); } .btn-secondary:hover { background: var(--gray-300); }
    </style>
</head>
<body class="bg-gray-50">
    <?php include_once $basePath . "includes/modern-sidebar.php"; ?>

    <div class="main-content">
        <header class="dashboard-header">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-title">
                        <h1>Add New Supplier SOA</h1>
                        <p>Record a new invoice or statement from a supplier</p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="index.php" class="export-btn secondary"><i class="fas fa-arrow-left"></i> Back to List</a>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="form-card">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?><?php if($preselected_supplier) echo '?supplier_id='.$preselected_supplier; ?>" method="post" class="modern-form">
                    <div class="form-body">
                        <div class="form-section">
                            <div class="section-header"><h4><i class="fas fa-truck"></i> Supplier & Invoice Details</h4></div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label required">Invoice Number</label>
                                    <input type="text" name="invoice_number" class="form-input <?php echo isset($errors['invoice_number']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($form_data['invoice_number']); ?>">
                                    <?php if(isset($errors['invoice_number'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['invoice_number']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label required">Supplier</label>
                                    <select name="supplier_id" class="form-select <?php echo isset($errors['supplier_id']) ? 'error' : ''; ?>">
                                        <option value="">Select a supplier...</option>
                                        <?php foreach($suppliers as $supplier): ?>
                                            <option value="<?php echo $supplier['supplier_id']; ?>" <?php echo ($supplier['supplier_id'] == $form_data['supplier_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if(isset($errors['supplier_id'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['supplier_id']; ?></span><?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="section-header"><h4><i class="fas fa-calendar-alt"></i> Dates</h4></div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label required">Issue Date</label>
                                    <input type="date" name="issue_date" class="form-input <?php echo isset($errors['issue_date']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($form_data['issue_date']); ?>">
                                    <?php if(isset($errors['issue_date'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['issue_date']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label required">Payment Due Date</label>
                                    <input type="date" name="payment_due_date" class="form-input <?php echo isset($errors['payment_due_date']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($form_data['payment_due_date']); ?>">
                                    <?php if(isset($errors['payment_due_date'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['payment_due_date']; ?></span><?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="section-header"><h4><i class="fas fa-dollar-sign"></i> Financial Details</h4></div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label required">Amount (RM)</label>
                                    <input type="number" step="0.01" name="amount" class="form-input <?php echo isset($errors['amount']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($form_data['amount']); ?>">
                                    <?php if(isset($errors['amount'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['amount']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label required">Payment Status</label>
                                    <select name="payment_status" class="form-select <?php echo isset($errors['payment_status']) ? 'error' : ''; ?>">
                                        <option value="Pending" <?php echo ($form_data['payment_status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Paid" <?php echo ($form_data['payment_status'] == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                                        <option value="Overdue" <?php echo ($form_data['payment_status'] == 'Overdue') ? 'selected' : ''; ?>>Overdue</option>
                                    </select>
                                    <?php if(isset($errors['payment_status'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['payment_status']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group full-width">
                                    <label class="form-label">Payment Method (if paid)</label>
                                    <input type="text" name="payment_method" class="form-input" value="<?php echo htmlspecialchars($form_data['payment_method']); ?>" placeholder="e.g., Bank Transfer, Cash">
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="section-header"><h4><i class="fas fa-align-left"></i> Purchase Description</h4></div>
                            <div class="form-group full-width">
                                <textarea name="purchase_description" class="form-textarea <?php echo isset($errors['purchase_description']) ? 'error' : ''; ?>" rows="4" placeholder="Enter service or product details..."><?php echo htmlspecialchars($form_data['purchase_description']); ?></textarea>
                                <?php if(isset($errors['purchase_description'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['purchase_description']; ?></span><?php endif; ?>
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
</body>
</html>
<?php ob_end_flush(); ?>
