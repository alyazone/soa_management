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
$form_data = [
    'payment_date' => date('Y-m-d'),
    'payment_amount' => '',
    'payment_method' => 'Bank Transfer',
    'payment_reference' => '',
    'notes' => ''
];

// Fetch SOA details
try {
    $stmt = $pdo->prepare("
        SELECT s.*, c.client_name
        FROM client_soa s
        JOIN clients c ON s.client_id = c.client_id
        WHERE s.soa_id = :id
    ");
    $stmt->bindParam(':id', $soa_id, PDO::PARAM_INT);
    $stmt->execute();
    $soa = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$soa) { header("location: index.php"); exit; }

    $balance = $soa['total_amount'] - $soa['paid_amount'];
} catch(PDOException $e) {
    die("ERROR: Could not fetch SOA details. " . $e->getMessage());
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $form_data = array_merge($form_data, $_POST);

    $payment_date = trim($form_data['payment_date']);
    $payment_amount = filter_var($form_data['payment_amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $payment_method = $form_data['payment_method'];
    $payment_reference = trim($form_data['payment_reference']);
    $notes = trim($form_data['notes']);

    if(empty($payment_date)) $errors['payment_date'] = "Payment date is required.";
    if(empty($payment_amount) || !is_numeric($payment_amount) || $payment_amount <= 0) {
        $errors['payment_amount'] = "A valid payment amount is required.";
    } elseif($payment_amount > $balance) {
        $errors['payment_amount'] = "Payment amount (RM " . number_format($payment_amount, 2) . ") exceeds outstanding balance (RM " . number_format($balance, 2) . ").";
    }
    if(empty($payment_method)) $errors['payment_method'] = "Payment method is required.";

    if(empty($errors)){
        try {
            $pdo->beginTransaction();

            // Insert payment record
            $sql = "INSERT INTO soa_payments (soa_id, payment_date, payment_amount, payment_method, payment_reference, notes, recorded_by) VALUES (:soa_id, :payment_date, :payment_amount, :payment_method, :payment_reference, :notes, :recorded_by)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':soa_id' => $soa_id,
                ':payment_date' => $payment_date,
                ':payment_amount' => $payment_amount,
                ':payment_method' => $payment_method,
                ':payment_reference' => $payment_reference,
                ':notes' => $notes,
                ':recorded_by' => $_SESSION['staff_id']
            ]);

            // Update paid_amount on client_soa
            $new_paid = $soa['paid_amount'] + $payment_amount;
            $update_sql = "UPDATE client_soa SET paid_amount = :paid_amount WHERE soa_id = :soa_id";
            $stmt = $pdo->prepare($update_sql);
            $stmt->execute([':paid_amount' => $new_paid, ':soa_id' => $soa_id]);

            // Auto-update status if fully paid
            $new_balance = $soa['total_amount'] - $new_paid;
            if($new_balance <= 0) {
                $status_sql = "UPDATE client_soa SET status = 'Paid' WHERE soa_id = :soa_id";
                $stmt = $pdo->prepare($status_sql);
                $stmt->execute([':soa_id' => $soa_id]);
            }

            $pdo->commit();
            header("location: view.php?id=" . $soa_id . "&success=payment_recorded");
            exit();
        } catch(PDOException $e) {
            $pdo->rollBack();
            $general_err = "Failed to record payment: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Payment - SOA Management System</title>
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
                        <h1>Record Payment</h1>
                        <p>Account #<?php echo htmlspecialchars($soa['account_number']); ?> - <?php echo htmlspecialchars($soa['client_name']); ?></p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="view.php?id=<?php echo $soa_id; ?>" class="export-btn secondary"><i class="fas fa-arrow-left"></i> Back to SOA</a>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <?php if(isset($general_err)): ?>
                <div class="alert alert-error" data-aos="fade-down">
                    <div class="alert-content"><i class="fas fa-exclamation-circle"></i><span><?php echo htmlspecialchars($general_err); ?></span></div>
                </div>
            <?php endif; ?>

            <div class="stats-grid" data-aos="fade-up">
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    <div class="stat-content">
                        <h3>RM <?php echo number_format($soa['total_amount'], 2); ?></h3>
                        <p>Total Invoice</p>
                    </div>
                </div>
                <div class="stat-card success">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-content">
                        <h3>RM <?php echo number_format($soa['paid_amount'], 2); ?></h3>
                        <p>Total Paid</p>
                    </div>
                </div>
                <div class="stat-card <?php echo $balance > 0 ? 'danger' : 'success'; ?>">
                    <div class="stat-icon"><i class="fas fa-balance-scale"></i></div>
                    <div class="stat-content">
                        <h3>RM <?php echo number_format($balance, 2); ?></h3>
                        <p>Outstanding Balance</p>
                    </div>
                </div>
            </div>

            <?php if($balance <= 0): ?>
                <div class="alert alert-success" data-aos="fade-down">
                    <div class="alert-content"><i class="fas fa-check-circle"></i><span>This invoice has been fully paid. No further payments can be recorded.</span></div>
                </div>
            <?php else: ?>
            <div class="form-card" data-aos="fade-up">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $soa_id; ?>" method="post" class="modern-form">
                    <div class="form-body">
                        <div class="form-section">
                            <div class="section-header"><h4><i class="fas fa-money-bill-wave"></i> Payment Details</h4></div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label required">Payment Date</label>
                                    <input type="date" name="payment_date" class="form-input <?php echo isset($errors['payment_date']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($form_data['payment_date']); ?>">
                                    <?php if(isset($errors['payment_date'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['payment_date']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label required">Payment Amount (RM)</label>
                                    <input type="number" step="0.01" min="0.01" max="<?php echo $balance; ?>" name="payment_amount" class="form-input <?php echo isset($errors['payment_amount']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($form_data['payment_amount']); ?>" placeholder="Max: RM <?php echo number_format($balance, 2); ?>">
                                    <?php if(isset($errors['payment_amount'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['payment_amount']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label required">Payment Method</label>
                                    <select name="payment_method" class="form-input <?php echo isset($errors['payment_method']) ? 'error' : ''; ?>">
                                        <option value="Bank Transfer" <?php echo ($form_data['payment_method'] == 'Bank Transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                                        <option value="Cash" <?php echo ($form_data['payment_method'] == 'Cash') ? 'selected' : ''; ?>>Cash</option>
                                        <option value="Cheque" <?php echo ($form_data['payment_method'] == 'Cheque') ? 'selected' : ''; ?>>Cheque</option>
                                        <option value="Online Payment" <?php echo ($form_data['payment_method'] == 'Online Payment') ? 'selected' : ''; ?>>Online Payment</option>
                                        <option value="Credit Card" <?php echo ($form_data['payment_method'] == 'Credit Card') ? 'selected' : ''; ?>>Credit Card</option>
                                        <option value="Other" <?php echo ($form_data['payment_method'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                    <?php if(isset($errors['payment_method'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['payment_method']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Payment Reference</label>
                                    <input type="text" name="payment_reference" class="form-input" value="<?php echo htmlspecialchars($form_data['payment_reference']); ?>" placeholder="e.g., Transaction ID, Cheque No.">
                                </div>
                            </div>
                            <div class="form-group full-width" style="margin-top: 1.5rem;">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-textarea" rows="3" placeholder="Additional notes about this payment..."><?php echo htmlspecialchars($form_data['notes']); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Record Payment</button>
                        <a href="view.php?id=<?php echo $soa_id; ?>" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                    </div>
                </form>
            </div>
            <?php endif; ?>
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
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;margin-bottom:2rem}.stat-card{background:white;border-radius:var(--border-radius);padding:1.5rem;box-shadow:var(--shadow);border:1px solid var(--gray-200);display:flex;align-items:center;gap:1rem}.stat-card .stat-icon{width:48px;height:48px;border-radius:var(--border-radius-sm);display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:white}.stat-card.primary .stat-icon{background:var(--primary-color)}.stat-card.success .stat-icon{background:var(--success-color)}.stat-card.danger .stat-icon{background:var(--danger-color)}.stat-card .stat-content h3{font-size:1.5rem;font-weight:700;color:var(--gray-900)}.stat-card .stat-content p{font-size:.875rem;color:var(--gray-600);margin:0}.form-card{background:white;border-radius:var(--border-radius);box-shadow:var(--shadow);border:1px solid var(--gray-200);overflow:hidden}.form-body{padding:2rem}.form-actions{padding:1.5rem 2rem;border-top:1px solid var(--gray-200);background:var(--gray-50);display:flex;gap:1rem}.form-section{margin-bottom:2.5rem}.form-section:last-child{margin-bottom:0}.section-header{margin-bottom:1.5rem;padding-bottom:.75rem;border-bottom:1px solid var(--gray-200)}.section-header h4{display:flex;align-items:center;gap:.5rem;color:var(--gray-900);font-size:1.125rem;font-weight:600;margin:0}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}.form-group{display:flex;flex-direction:column;gap:.5rem}.form-group.full-width{grid-column:1 / -1}.form-label{display:flex;align-items:center;gap:.5rem;font-size:.875rem;font-weight:500;color:var(--gray-700)}.form-label.required::after{content:'*';color:var(--danger-color);margin-left:.25rem}.form-input,.form-textarea{padding:.75rem;border:1px solid var(--gray-300);border-radius:var(--border-radius-sm);font-size:.875rem;transition:var(--transition);background:white}.form-input:focus,.form-textarea:focus{outline:0;border-color:var(--primary-color);box-shadow:0 0 0 3px rgba(59,130,246,.1)}.form-input.error{border-color:var(--danger-color);box-shadow:0 0 0 3px rgba(239,68,68,.1)}.form-textarea{resize:vertical;min-height:80px}.error-message{display:flex;align-items:center;gap:.5rem;color:var(--danger-color);font-size:.75rem;font-weight:500}.btn{display:inline-flex;align-items:center;gap:.5rem;padding:.75rem 1.5rem;border:none;border-radius:var(--border-radius-sm);font-size:.875rem;font-weight:500;text-decoration:none;cursor:pointer;transition:var(--transition)}.btn-primary{background:var(--primary-color);color:white}.btn-primary:hover{background:var(--primary-dark)}.btn-secondary{background:var(--gray-200);color:var(--gray-800)}.btn-secondary:hover{background:var(--gray-300)}.alert{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.5rem;border-radius:var(--border-radius);margin-bottom:1.5rem;border:1px solid}.alert-error{background:rgba(239,68,68,.1);border-color:var(--danger-color);color:var(--danger-color)}.alert-success{background:rgba(16,185,129,.1);border-color:var(--success-color);color:var(--success-color)}.alert-content{display:flex;align-items:center;gap:.75rem}@media(max-width:768px){.form-grid{grid-template-columns:1fr}}
    </style>
</body>
</html>
<?php ob_end_flush(); ?>
