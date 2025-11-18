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
    $sql = "SELECT s.*, sup.supplier_name, sup.pic_name, sup.pic_contact, sup.pic_email, sup.address, 
            st.full_name as created_by_name
            FROM supplier_soa s 
            JOIN suppliers sup ON s.supplier_id = sup.supplier_id 
            LEFT JOIN staff st ON s.created_by = st.staff_id
            WHERE s.soa_id = :soa_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':soa_id' => $soa_id]);
    $soa = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$soa) { header("location: index.php"); exit(); }
} catch(PDOException $e) {
    die("ERROR: Could not fetch Supplier SOA details. " . $e->getMessage());
}

$due_date_obj = new DateTime($soa['payment_due_date']);
$today_obj = new DateTime(date('Y-m-d'));
$days_diff = (int)$today_obj->diff($due_date_obj)->format('%r%a');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Supplier SOA</title>
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
        .export-btn.success { background-color: var(--success-color); } .export-btn.success:hover { background-color: #059669; }
        .export-btn.warning { background-color: var(--warning-color); } .export-btn.warning:hover { background-color: #d97706; }
        .dashboard-content { padding: 2rem; max-width: 1600px; margin: 0 auto; }
        .profile-header { background: white; border-radius: var(--border-radius); box-shadow: var(--shadow); border: 1px solid var(--gray-200); padding: 2rem; display: flex; align-items: center; gap: 2rem; margin-bottom: 2rem; }
        .profile-avatar { width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: white; flex-shrink: 0; }
        .profile-info h2 { font-size: 2rem; font-weight: 700; color: var(--gray-900); }
        .profile-info .profile-subtitle { font-size: 1rem; color: var(--gray-500); margin-top: 0.25rem; }
        .profile-meta { display: flex; gap: 1.5rem; margin-top: 1rem; color: var(--gray-600); font-size: 0.875rem; }
        .meta-item { display: flex; align-items: center; gap: 0.5rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; border-radius: var(--border-radius); padding: 1.5rem; box-shadow: var(--shadow); border: 1px solid var(--gray-200); display: flex; align-items: center; gap: 1.5rem; transition: var(--transition); }
        .stat-icon { width: 50px; height: 50px; border-radius: var(--border-radius-sm); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: white; flex-shrink: 0; }
        .stat-card.success .stat-icon { background: var(--success-color); } .stat-card.warning .stat-icon { background: var(--warning-color); } .stat-card.danger .stat-icon { background: var(--danger-color); } .stat-card.info .stat-icon { background: var(--info-color); } .stat-card.secondary .stat-icon { background: var(--secondary-color); }
        .stat-content h3 { font-size: 1.75rem; font-weight: 700; color: var(--gray-900); line-height: 1.2; }
        .stat-content p { font-size: 0.875rem; color: var(--gray-600); }
        .content-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .info-card { background: white; border-radius: var(--border-radius); box-shadow: var(--shadow); border: 1px solid var(--gray-200); }
        .info-header { padding: 1rem 1.5rem; border-bottom: 1px solid var(--gray-200); }
        .info-header h3 { font-size: 1.125rem; font-weight: 600; display: flex; align-items: center; gap: 0.75rem; }
        .info-body { padding: 1.5rem; }
        .info-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; }
        .info-item { display: flex; flex-direction: column; }
        .info-item label { font-size: 0.75rem; color: var(--gray-500); margin-bottom: 0.25rem; text-transform: uppercase; font-weight: 500; }
        .info-item value { font-size: 0.9rem; color: var(--gray-800); }
        .contact-link { color: var(--primary-color); text-decoration: none; } .contact-link:hover { text-decoration: underline; }
        .status-badge { display: inline-flex; align-items: center; padding: 0.375rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .status-paid { background: rgba(16, 185, 129, 0.1); color: var(--success-color); }
        .status-pending { background: rgba(245, 158, 11, 0.1); color: var(--warning-color); }
        .status-overdue { background: rgba(239, 68, 68, 0.1); color: var(--danger-color); }
    </style>
</head>
<body class="bg-gray-50">
    <?php include_once $basePath . "includes/modern-sidebar.php"; ?>

    <div class="main-content">
        <header class="dashboard-header">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-title">
                        <h1>Supplier SOA Details</h1>
                        <p>Invoice #<?php echo htmlspecialchars($soa['invoice_number']); ?></p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="generate_pdf.php?id=<?php echo $soa_id; ?>" target="_blank" class="export-btn success"><i class="fas fa-file-pdf"></i> Generate PDF</a>
                    <a href="edit.php?id=<?php echo $soa_id; ?>" class="export-btn warning"><i class="fas fa-edit"></i> Edit</a>
                    <a href="index.php" class="export-btn secondary"><i class="fas fa-arrow-left"></i> Back to List</a>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="profile-header">
                <div class="profile-avatar" style="background-color: #4299e1;"><i class="fas fa-truck"></i></div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($soa['supplier_name']); ?></h2>
                    <p class="profile-subtitle">Invoice #<?php echo htmlspecialchars($soa['invoice_number']); ?></p>
                    <div class="profile-meta">
                        <span class="meta-item"><i class="fas fa-calendar-alt"></i> Issued: <?php echo date('M d, Y', strtotime($soa['issue_date'])); ?></span>
                        <span class="meta-item"><i class="fas fa-exclamation-circle"></i> Due: <?php echo date('M d, Y', strtotime($soa['payment_due_date'])); ?></span>
                        <span class="meta-item"><span class="status-badge status-<?php echo strtolower($soa['payment_status']); ?>"><?php echo htmlspecialchars($soa['payment_status']); ?></span></span>
                    </div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card success"><div class="stat-icon"><i class="fas fa-dollar-sign"></i></div><div class="stat-content"><h3>RM <?php echo number_format($soa['amount'], 2); ?></h3><p>Total Amount</p></div></div>
                <div class="stat-card <?php echo strtolower($soa['payment_status']) == 'paid' ? 'success' : (strtolower($soa['payment_status']) == 'overdue' ? 'danger' : 'warning'); ?>"><div class="stat-icon"><i class="fas fa-info-circle"></i></div><div class="stat-content"><h3><?php echo htmlspecialchars($soa['payment_status']); ?></h3><p>Status</p></div></div>
                <div class="stat-card <?php echo $days_diff < 0 ? 'danger' : 'info'; ?>"><div class="stat-icon"><i class="fas fa-hourglass-half"></i></div><div class="stat-content"><h3><?php echo $days_diff < 0 ? abs($days_diff) . " Day(s)" : $days_diff . " Day(s)"; ?></h3><p><?php echo $days_diff < 0 ? 'Overdue' : 'Until Due'; ?></p></div></div>
                <div class="stat-card secondary"><div class="stat-icon"><i class="fas fa-user-edit"></i></div><div class="stat-content"><h3><?php echo htmlspecialchars($soa['created_by_name'] ?? 'N/A'); ?></h3><p>Created By</p></div></div>
            </div>

            <div class="content-grid">
                <div class="info-card">
                    <div class="info-header"><h3><i class="fas fa-file-alt"></i> SOA Information</h3></div>
                    <div class="info-body">
                        <div class="info-grid">
                            <div class="info-item"><label>Payment Method</label><value><?php echo htmlspecialchars($soa['payment_method'] ?: 'N/A'); ?></value></div>
                            <div class="info-item"><label>Created At</label><value><?php echo date('M d, Y H:i', strtotime($soa['created_at'])); ?></value></div>
                            <div class="info-item full-width"><label>Purchase Description</label><value><?php echo nl2br(htmlspecialchars($soa['purchase_description'])); ?></value></div>
                        </div>
                    </div>
                </div>
                <div class="info-card">
                    <div class="info-header"><h3><i class="fas fa-truck-fast"></i> Supplier Details</h3></div>
                    <div class="info-body">
                        <div class="info-grid">
                            <div class="info-item full-width"><label>Address</label><value><?php echo nl2br(htmlspecialchars($soa['address'])); ?></value></div>
                            <div class="info-item"><label>Contact Person</label><value><?php echo htmlspecialchars($soa['pic_name']); ?></value></div>
                            <div class="info-item"><label>Contact Number</label><value><a href="tel:<?php echo htmlspecialchars($soa['pic_contact']); ?>" class="contact-link"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($soa['pic_contact']); ?></a></value></div>
                            <div class="info-item full-width"><label>Email</label><value><a href="mailto:<?php echo htmlspecialchars($soa['pic_email']); ?>" class="contact-link"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($soa['pic_email']); ?></a></value></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php ob_end_flush(); ?>
