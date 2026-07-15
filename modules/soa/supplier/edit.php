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

try {
    $stmt = $pdo->prepare("
        SELECT s.*, po.po_number AS official_po_number 
        FROM supplier_soa s
        LEFT JOIN purchase_orders po ON s.po_id = po.po_id 
        WHERE s.soa_id = ?
    ");
    $stmt->execute([$soa_id]);
    $db_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$db_data) { header("location: index.php"); exit; }
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$errors = [];
$form_data = $db_data;

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $form_data = array_merge($form_data, $_POST);
    if(empty(trim($form_data["invoice_number"]))) $errors['invoice_number'] = "Please enter invoice number.";
    if(empty($form_data["issue_date"])) $errors['issue_date'] = "Please enter issue date.";
    if(empty($form_data["payment_due_date"])) $errors['payment_due_date'] = "Please enter payment due date.";
    if(empty(trim($form_data["purchase_description"]))) $errors['purchase_description'] = "Please enter purchase description.";
    if(empty(trim($form_data["amount"])) || !is_numeric(trim($form_data["amount"])) || floatval(trim($form_data["amount"])) <= 0) $errors['amount'] = "Please enter a valid positive amount.";
    if(empty($form_data["payment_status"])) $errors['payment_status'] = "Please select payment status.";
    if(empty($form_data["client_id"])) $errors['client_id'] = "Please select enduser.";
    // receipt_number is optional — no validation required
    if(empty(trim($form_data["payment_method"]))) $errors['payment_method'] = "Please enter payment method.";
    if(trim($form_data["amount_paid"]) === '') $errors['amount_paid'] = "Please enter amount paid.";
    // credit_duration is optional — no validation required

    $po_id_val = !empty($form_data['po_id']) ? intval($form_data['po_id']) : null;
    $remaining = null;
    $po_number = null;
    if($po_id_val){
        try {
            $check_stmt = $pdo->prepare("SELECT po_number, total_amount FROM purchase_orders WHERE po_id = :id AND status IN ('Approved', 'Partially Invoiced', 'Closed')");
            $check_stmt->bindParam(':id', $po_id_val, PDO::PARAM_INT);
            $check_stmt->execute();
            $po_row = $check_stmt->fetch(PDO::FETCH_ASSOC);

            if(!$po_row){
                $errors['po_id'] = "Selected PO is not available for invoicing.";
            } else {

                $po_number = $po_row['po_number'];
                $inv_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM supplier_soa WHERE po_id = :id AND soa_id != :soa_id");
                $inv_stmt->bindParam(':id', $po_id_val, PDO::PARAM_INT);
                $inv_stmt->bindParam(':soa_id', $soa_id, PDO::PARAM_INT);
                $inv_stmt->execute();
                $already_invoiced = floatval($inv_stmt->fetchColumn());
                $remaining = round(floatval($po_row['total_amount']) - $already_invoiced, 2);
                $this_amount = floatval(trim($form_data['amount']));

                if(isset($remaining) && $this_amount > $remaining + 0.01){
                    $errors['amount'] = "Amount exceeds remaining PO balance.";
                }
            }
        } catch(PDOException $e) {
            $errors['po_id'] = "Error validating PO.";
        }
    }
    
    if(empty($errors)){
        $sql = "UPDATE supplier_soa SET invoice_number=?,client_id=?, issue_date=?, payment_due_date=?, credit_duration=?, purchase_description=?, amount=?, receipt_number=?, amount_paid=?, payment_status=?, payment_method=?, payment_date=?, manual_po_number=? WHERE soa_id=?";
        if($stmt = $pdo->prepare($sql)){
            $stmt->execute([
                trim($form_data['invoice_number']), $form_data['client_id'], $form_data['issue_date'], 
                $form_data['payment_due_date'], trim($form_data['credit_duration']), trim($form_data['purchase_description']), trim($form_data['amount']), 
                trim($form_data['receipt_number']), trim($form_data['amount_paid']),
                $form_data['payment_status'], trim($form_data['payment_method']),
                ($form_data['payment_status'] == 'Paid' && !empty($form_data['payment_date'])) ? $form_data['payment_date'] : null,
                (empty($form_data['po_id']) && !empty($form_data['purchase_order'])) ? trim($form_data['purchase_order']) : null,
                $soa_id
            ]);
            header("location: view.php?id=" . $soa_id . "&success=updated");
            exit();
        }
    }
}

try {
    $suppliers = $pdo->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name")->fetchAll();
} catch(PDOException $e) {
    $suppliers = [];
}
try {
    $endusers = $pdo->query("SELECT client_id, client_name FROM clients ORDER BY client_name")->fetchAll();
} catch(PDOException $e) {
    $endusers = [];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Supplier SOA</title>
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
        .export-btn.info { background-color: var(--info-color); } .export-btn.info:hover { background-color: #0891b2; }
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
                        <h1>Edit Supplier SOA</h1>
                        <p>Update details for Invoice #<?php echo htmlspecialchars($form_data['invoice_number']); ?></p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="view.php?id=<?php echo $soa_id; ?>" class="export-btn info"><i class="fas fa-eye"></i> View SOA</a>
                    <a href="index.php" class="export-btn secondary"><i class="fas fa-arrow-left"></i> Back to List</a>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="form-card">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $soa_id); ?>" method="post" class="modern-form">
                    <div class="form-body">
                        <div class="form-section">
                            <div class="section-header"><h4><i class="fas fa-truck"></i> Supplier & Invoice Details</h4></div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label required">Invoice Supplier</label>
                                    <input type="text" name="invoice_number" class="form-input <?php echo isset($errors['invoice_number']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($form_data['invoice_number']); ?>">
                                    <?php if(isset($errors['invoice_number'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['invoice_number']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label required">Supplier</label>
                                    <select name="supplier_id" style="color: #000000ff;" class="form-select <?php echo isset($errors['supplier_id']) ? 'error' : ''; ?>" disabled>
                              readonly  <?php foreach($suppliers as $supplier): ?>
                                            <option value="<?php echo $supplier['supplier_id']; ?>" <?php echo ($supplier['supplier_id'] == $form_data['supplier_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if(isset($errors['supplier_id'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['supplier_id']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label required">Enduser</label>
                                    <select name="client_id" style="color: #000000ff;" class="form-select <?php echo isset($errors['client_id']) ? 'error' : ''; ?>">
                                        <?php foreach($endusers as $enduser): ?>
                                            <option value="<?php echo $enduser['client_id']; ?>" <?php echo ($enduser['client_id'] == $form_data['client_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($enduser['client_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if(isset($errors['client_id'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['client_id']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <?php if(!empty($form_data['po_id'])): ?>
                                        <label class="form-label required">Kyrol Purchase Order (PO)</label>
                                        <input type="text" name="purchase_order" class="form-input" value="<?php echo htmlspecialchars($form_data['official_po_number'] ?? 'N/A'); ?>" readonly>
                                    <?php else: ?>
                                        <label class="form-label">Kyrol Purchase Order (PO)</label>
                                        <input type="text" name="purchase_order" class="form-input" value="<?php echo htmlspecialchars($form_data['purchase_order'] ?? $form_data['manual_po_number'] ?? ''); ?>" placeholder="Enter manual PO number">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="section-header"><h4><i class="fas fa-calendar-alt"></i> Dates</h4></div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label required">Invoice Issue Date</label>
                                    <input type="date" name="issue_date" id="issueDateInput" class="form-input <?php echo isset($errors['issue_date']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($form_data['issue_date']); ?>">
                                    <?php if(isset($errors['issue_date'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['issue_date']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label required">Payment Due Date</label>
                                    <input type="date" name="payment_due_date" id="paymentDueDateInput" class="form-input <?php echo isset($errors['payment_due_date']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($form_data['payment_due_date']); ?>" readonly>
                                    <?php if(isset($errors['payment_due_date'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['payment_due_date']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Credit Duration (Months)</label>
                                    <input type="number" name="credit_duration" id="creditDurationInput" class="form-input <?php echo isset($errors['credit_duration']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($form_data['credit_duration'] ?? ''); ?>">
                                    <?php if(isset($errors['credit_duration'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['credit_duration']; ?></span><?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="section-header"><h4><i class="fas fa-dollar-sign"></i> Financial Details</h4></div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label required">Amount in Total (RM)</label>
                                    <input type="number" step="0.01" name="amount" id="amountInput" class="form-input <?php echo isset($errors['amount']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($form_data['amount']); ?>">
                                    <?php if(isset($errors['amount'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['amount']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Receipt Number</label>
                                    <input type="text" name="receipt_number" class="form-input <?php echo isset($errors['receipt_number']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($form_data['receipt_number'] ?? ''); ?>">
                                    <?php if(isset($errors['receipt_number'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['receipt_number']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label required">Amount Paid (RM)</label>
                                    <input type="number" step="0.01" name="amount_paid" id="amountPaidInput" class="form-input <?php echo isset($errors['amount_paid']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($form_data['amount_paid'] ?? ''); ?>">
                                    <?php if(isset($errors['amount_paid'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['amount_paid']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label required">Amount Pending (RM)</label>
                                    <input type="number" step="0.01" name="amount_pending" id="amountPendingInput" class="form-input <?php echo isset($errors['amount_pending']) ? 'error' : ''; ?>" value="<?php echo number_format($form_data['amount'] - $form_data['amount_paid'], 2, '.', ''); ?>" readonly>
                                    <?php if(isset($errors['amount_pending'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['amount_pending']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label required">Payment Status</label>
                                    <select name="payment_status" id="paymentStatusSelect" class="form-select <?php echo isset($errors['payment_status']) ? 'error' : ''; ?>">
                                        <option value="Pending" <?php echo ($form_data['payment_status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Paid" <?php echo ($form_data['payment_status'] == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                                        <option value="Overdue" <?php echo ($form_data['payment_status'] == 'Overdue') ? 'selected' : ''; ?>>Overdue</option>
                                    </select>
                                    <?php if(isset($errors['payment_status'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['payment_status']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Payment Date</label>
                                    <input type="date" name="payment_date" id="paymentDateInput" class="form-input <?php echo isset($errors['payment_date']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($form_data['payment_date'] ?? ''); ?>" readonly>
                                    <?php if(isset($errors['payment_date'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['payment_date']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Payment Method (if paid)</label>
                                    <select name="payment_method" class="form-select <?php echo isset($errors['payment_method']) ? 'error' : ''; ?>">
                                        <option value="Cheque" <?php echo ($form_data['payment_method'] == 'Cheque') ? 'selected' : ''; ?>>Cheque</option>
                                        <option value="Bank Transfer" <?php echo ($form_data['payment_method'] == 'Bank Transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                                        <option value="Cash" <?php echo ($form_data['payment_method'] == 'Cash') ? 'selected' : ''; ?>>Cash</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="section-header"><h4><i class="fas fa-align-left"></i> Purchase Description</h4></div>
                            <div class="form-group full-width">
                                <textarea name="purchase_description" class="form-textarea <?php echo isset($errors['purchase_description']) ? 'error' : ''; ?>" rows="4"><?php echo htmlspecialchars($form_data['purchase_description']); ?></textarea>
                                <?php if(isset($errors['purchase_description'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['purchase_description']; ?></span><?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update SOA</button>
                        <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        const amountInput         = document.getElementById('amountInput');
        const creditDurationInput = document.getElementById('creditDurationInput');
        const paymentDueDateInput = document.getElementById('paymentDueDateInput');
        const issueDateInput      = document.getElementById('issueDateInput');
        const amountPendingInput  = document.getElementById('amountPendingInput');
        const amountPaidInput     = document.getElementById('amountPaidInput');
        const paymentStatusSelect = document.getElementById('paymentStatusSelect');
        const paymentDateInput    = document.getElementById('paymentDateInput');

        function togglePaymentDate() {
            if (paymentStatusSelect.value === 'Paid') {
                paymentDateInput.removeAttribute('readonly');
                if (!paymentDateInput.value) {
                    paymentDateInput.value = new Date().toISOString().split('T')[0];
                }
            } else {
                paymentDateInput.setAttribute('readonly', 'true');
                paymentDateInput.value = '';
            }
        }

        paymentStatusSelect.addEventListener('change', togglePaymentDate);
        togglePaymentDate();


        function calculatePendingAmount() {
            const amountVal = amountInput.value;
            const amountPaidVal = amountPaidInput.value;

            if (amountVal !== '' && amountPaidVal !== '') {
                const amount = parseFloat(amountVal);
                let amountPaid = parseFloat(amountPaidVal);


                if (amountPaid > amount) {
                    amountPaid = amount;
                    amountPaidInput.value = amount; // Update the UI field
                }

                amountPendingInput.value = (amount - amountPaid).toFixed(2);
            } else {
        
                amountPendingInput.value = '';
            }
        }

        amountInput.addEventListener('change', calculatePendingAmount);
        amountPaidInput.addEventListener('change', calculatePendingAmount);


        function calculateEndDate() {
            if (issueDateInput.value!=='' && creditDurationInput.value!=='') {
                const creditDuration = creditDurationInput.value;
                const issueDate = issueDateInput.value;
                const paymentDueDate = new Date(issueDate);
                paymentDueDate.setMonth(paymentDueDate.getMonth() + parseInt(creditDuration));
                paymentDueDateInput.value = paymentDueDate.toISOString().split('T')[0];
            }
        }

        issueDateInput.addEventListener('change',calculateEndDate);
        creditDurationInput.addEventListener('change',calculateEndDate);

    </script>




</body>
</html>
<?php ob_end_flush(); ?>
