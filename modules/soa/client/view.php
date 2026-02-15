<?php
ob_start();
$basePath = '../../../';
require_once $basePath . "config/database.php";

session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}

$soa_id = $_GET['id'] ?? null;
if(!$soa_id) { header("location: index.php"); exit; }

try {
    // Fetch main SOA data along with client and staff info
    $stmt = $pdo->prepare("
        SELECT s.*, c.client_name, c.address as client_address, 
               c.pic_name as client_pic, c.pic_contact as client_contact, 
               c.pic_email as client_email, 
               st.full_name as created_by_name
        FROM client_soa s 
        JOIN clients c ON s.client_id = c.client_id 
        LEFT JOIN staff st ON s.created_by = st.staff_id
        WHERE s.soa_id = :id
    ");
    $stmt->bindParam(":id", $soa_id, PDO::PARAM_INT);
    $stmt->execute();
    $soa = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$soa) { header("location: index.php"); exit(); }

    // Fetch related documents
    $doc_stmt = $pdo->prepare("SELECT * FROM documents WHERE reference_type = 'ClientSOA' AND reference_id = :id ORDER BY upload_date DESC");
    $doc_stmt->bindParam(":id", $soa_id, PDO::PARAM_INT);
    $doc_stmt->execute();
    $documents = $doc_stmt->fetchAll();

    // Fetch payment history
    $pay_stmt = $pdo->prepare("
        SELECT p.*, st.full_name as recorded_by_name
        FROM soa_payments p
        LEFT JOIN staff st ON p.recorded_by = st.staff_id
        WHERE p.soa_id = :id
        ORDER BY p.payment_date DESC, p.created_at DESC
        LIMIT 5
    ");
    $pay_stmt->bindParam(":id", $soa_id, PDO::PARAM_INT);
    $pay_stmt->execute();
    $recent_payments = $pay_stmt->fetchAll();

    // Count total payments
    $count_stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM soa_payments WHERE soa_id = :id");
    $count_stmt->bindParam(":id", $soa_id, PDO::PARAM_INT);
    $count_stmt->execute();
    $payment_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['cnt'];

} catch(PDOException $e) {
    die("ERROR: Could not fetch Client SOA details. " . $e->getMessage());
}

// Calculate balance
$balance = $soa['total_amount'] - $soa['paid_amount'];

// Calculate days until due / overdue
$due_date_obj = new DateTime($soa['due_date']);
$today_obj = new DateTime(date('Y-m-d'));
$days_diff = (int)$today_obj->diff($due_date_obj)->format('%r%a');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Client SOA - SOA Management System</title>
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
                        <h1>SOA Details</h1>
                        <p>Account #<?php echo htmlspecialchars($soa['account_number']); ?></p>
                    </div>
                </div>
                <div class="header-right">
                    <?php if($balance > 0 && $soa['status'] != 'Closed' && $_SESSION['position'] == 'Admin'): ?>
                        <a href="record_payment.php?id=<?php echo $soa_id; ?>" class="export-btn" style="background:var(--success-color);color:white;"><i class="fas fa-money-bill-wave"></i> Record Payment</a>
                    <?php endif; ?>
                    <a href="payments.php?id=<?php echo $soa_id; ?>" class="export-btn info"><i class="fas fa-history"></i> Payment History</a>
                    <a href="generate_pdf.php?id=<?php echo $soa_id; ?>" target="_blank" class="export-btn success"><i class="fas fa-file-pdf"></i> Generate PDF</a>
                    <?php if($soa['status'] != 'Closed' && $_SESSION['position'] == 'Admin'): ?>
                        <a href="edit.php?id=<?php echo $soa_id; ?>" class="export-btn warning"><i class="fas fa-edit"></i> Edit</a>
                    <?php endif; ?>
                    <a href="client_soas.php?client_id=<?php echo $soa['client_id']; ?>" class="export-btn secondary"><i class="fas fa-arrow-left"></i> Back to Client's SOAs</a>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <?php if(isset($_GET['success']) && $_GET['success'] == 'payment_recorded'): ?>
                <div class="alert alert-success" data-aos="fade-down">
                    <div class="alert-content"><i class="fas fa-check-circle"></i><span>Payment has been recorded successfully.</span></div>
                    <button class="alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>
            <div class="profile-header" data-aos="fade-down">
                <div class="profile-avatar" style="background-color: #4299e1;"><i class="fas fa-file-invoice-dollar"></i></div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($soa['client_name']); ?></h2>
                    <p class="profile-subtitle">Account #<?php echo htmlspecialchars($soa['account_number']); ?></p>
                    <div class="profile-meta">
                        <span class="meta-item"><i class="fas fa-calendar-alt"></i> Issued: <?php echo date('M d, Y', strtotime($soa['issue_date'])); ?></span>
                        <span class="meta-item"><i class="fas fa-exclamation-circle"></i> Due: <?php echo date('M d, Y', strtotime($soa['due_date'])); ?></span>
                        <span class="meta-item"><span class="status-badge status-<?php echo strtolower($soa['status']); ?>"><?php echo htmlspecialchars($soa['status']); ?></span></span>
                    </div>
                </div>
            </div>

            <div class="stats-grid" data-aos="fade-up">
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    <div class="stat-content">
                        <h3>RM <?php echo number_format($soa['total_amount'], 2); ?></h3>
                        <p>Total Amount</p>
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
                        <p>Balance Due</p>
                    </div>
                </div>
                <div class="stat-card <?php echo strtolower($soa['status']) == 'paid' ? 'success' : (strtolower($soa['status']) == 'overdue' ? 'danger' : 'warning'); ?>">
                    <div class="stat-icon"><i class="fas fa-info-circle"></i></div>
                    <div class="stat-content">
                        <h3><?php echo htmlspecialchars($soa['status']); ?></h3>
                        <p>Status</p>
                    </div>
                </div>
                <div class="stat-card <?php echo $days_diff < 0 ? 'danger' : 'info'; ?>">
                    <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                    <div class="stat-content">
                        <h3>
                            <?php
                            if ($days_diff < 0) {
                                echo abs($days_diff) . " Day(s)";
                            } else {
                                echo $days_diff . " Day(s)";
                            }
                            ?>
                        </h3>
                        <p><?php echo $days_diff < 0 ? 'Overdue' : 'Until Due'; ?></p>
                    </div>
                </div>
                <div class="stat-card secondary">
                    <div class="stat-icon"><i class="fas fa-user-edit"></i></div>
                    <div class="stat-content">
                        <h3><?php echo htmlspecialchars($soa['created_by_name'] ?? 'N/A'); ?></h3>
                        <p>Created By</p>
                    </div>
                </div>
            </div>

            <div class="content-grid" data-aos="fade-up" data-aos-delay="200">
                <div class="info-card">
                    <div class="info-header"><h3><i class="fas fa-file-alt"></i> SOA Information</h3></div>
                    <div class="info-body">
                        <div class="info-grid">
                            <div class="info-item"><label>PO Number</label><value><?php echo htmlspecialchars($soa['po_number'] ?: 'N/A'); ?></value></div>
                            <div class="info-item"><label>Invoice Number</label><value><?php echo htmlspecialchars($soa['invoice_number'] ?: 'N/A'); ?></value></div>
                            <div class="info-item"><label>Terms</label><value><?php echo htmlspecialchars($soa['terms']); ?></value></div>
                            <div class="info-item"><label>Purchase Date</label><value><?php echo date('M d, Y', strtotime($soa['purchase_date'])); ?></value></div>
                            <div class="info-item full-width"><label>Service Description</label><value><?php echo nl2br(htmlspecialchars($soa['service_description'])); ?></value></div>
                        </div>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-header"><h3><i class="fas fa-user-tie"></i> Client Details</h3></div>
                    <div class="info-body">
                        <div class="info-grid">
                            <div class="info-item full-width"><label>Address</label><value><?php echo nl2br(htmlspecialchars($soa['client_address'])); ?></value></div>
                            <div class="info-item"><label>Contact Person</label><value><?php echo htmlspecialchars($soa['client_pic']); ?></value></div>
                            <div class="info-item"><label>Contact Number</label><value><a href="tel:<?php echo htmlspecialchars($soa['client_contact']); ?>" class="contact-link"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($soa['client_contact']); ?></a></value></div>
                            <div class="info-item full-width"><label>Email</label><value><a href="mailto:<?php echo htmlspecialchars($soa['client_email']); ?>" class="contact-link"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($soa['client_email']); ?></a></value></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment History Section -->
            <div class="table-card" data-aos="fade-up" data-aos-delay="250">
                <div class="table-header">
                    <div class="table-title">
                        <h3><i class="fas fa-money-bill-wave"></i> Recent Payments</h3>
                        <p><?php echo $payment_count; ?> payment(s) recorded</p>
                    </div>
                    <?php if($payment_count > 0): ?>
                    <a href="payments.php?id=<?php echo $soa_id; ?>" class="export-btn info" style="font-size:.8rem;padding:.5rem 1rem;"><i class="fas fa-list"></i> View All</a>
                    <?php endif; ?>
                </div>
                <div class="table-container">
                    <table class="modern-table">
                        <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Reference</th><th>Recorded By</th></tr></thead>
                        <tbody>
                            <?php if(!empty($recent_payments)): ?>
                                <?php foreach($recent_payments as $pay): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($pay['payment_date'])); ?></td>
                                    <td><span style="color:var(--success-color);font-weight:600;">RM <?php echo number_format($pay['payment_amount'], 2); ?></span></td>
                                    <td><span class="document-type-badge"><?php echo htmlspecialchars($pay['payment_method']); ?></span></td>
                                    <td><?php echo htmlspecialchars($pay['payment_reference'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($pay['recorded_by_name'] ?? 'N/A'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--gray-500);">No payments recorded yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if(!empty($documents)): ?>
            <div class="table-card" data-aos="fade-up" data-aos-delay="300">
                <div class="table-header">
                    <div class="table-title">
                        <h3><i class="fas fa-folder-open"></i> Related Documents</h3>
                        <p><?php echo count($documents); ?> document(s) found</p>
                    </div>
                </div>
                <div class="table-container">
                    <table class="modern-table">
                        <thead><tr><th>Document Type</th><th>File Name</th><th>Upload Date</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach($documents as $doc): ?>
                            <tr>
                                <td><span class="document-type-badge"><?php echo htmlspecialchars($doc['document_type']); ?></span></td>
                                <td><?php echo htmlspecialchars($doc['file_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($doc['upload_date'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?php echo $basePath . $doc['file_path']; ?>" class="action-btn action-btn-view" target="_blank" title="View Document"><i class="fas fa-eye"></i></a>
                                        <a href="<?php echo $basePath . $doc['file_path']; ?>" class="action-btn action-btn-download" download title="Download Document"><i class="fas fa-download"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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
        .alert{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.5rem;border-radius:var(--border-radius);margin-bottom:1.5rem;border:1px solid}.alert-success{background:rgba(16,185,129,.1);border-color:var(--success-color);color:var(--success-color)}.alert-content{display:flex;align-items:center;gap:.75rem}.alert-close{background:0 0;border:none;color:inherit;cursor:pointer;padding:.25rem;border-radius:var(--border-radius-sm);transition:var(--transition)}.alert-close:hover{background:rgba(0,0,0,.1)}.profile-header{background:white;border-radius:var(--border-radius);box-shadow:var(--shadow);border:1px solid var(--gray-200);padding:2rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:1.5rem}.profile-avatar{width:80px;height:80px;background:var(--primary-color);border-radius:var(--border-radius);display:flex;align-items:center;justify-content:center;color:white;font-size:2rem;flex-shrink:0}.profile-info h2{color:var(--gray-900);margin-bottom:.25rem;font-size:1.5rem;font-weight:600}.profile-subtitle{color:var(--gray-600);margin-bottom:1rem}.profile-meta{display:flex;flex-wrap:wrap;gap:1.5rem}.meta-item{display:flex;align-items:center;gap:.5rem;color:var(--gray-600);font-size:.875rem}.meta-item i{color:var(--gray-400)}.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;margin-bottom:2rem}.stat-card{background:white;border-radius:var(--border-radius);padding:1.5rem;box-shadow:var(--shadow);border:1px solid var(--gray-200);display:flex;align-items:center;gap:1rem}.stat-card .stat-icon{width:48px;height:48px;border-radius:var(--border-radius-sm);display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:white}.stat-card.primary .stat-icon{background:var(--primary-color)}.stat-card.success .stat-icon{background:var(--success-color)}.stat-card.warning .stat-icon{background:var(--warning-color)}.stat-card.danger .stat-icon{background:var(--danger-color)}.stat-card.info .stat-icon{background:var(--info-color)}.stat-card.secondary .stat-icon{background:var(--gray-500)}.stat-card .stat-content h3{font-size:1.5rem;font-weight:700;color:var(--gray-900)}.stat-card .stat-content p{font-size:.875rem;color:var(--gray-600);margin:0}.content-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:2rem;margin-bottom:2rem}.info-card{background:white;border-radius:var(--border-radius);box-shadow:var(--shadow);border:1px solid var(--gray-200);overflow:hidden}.info-header{padding:1.5rem;border-bottom:1px solid var(--gray-200);background:var(--gray-50)}.info-header h3{display:flex;align-items:center;gap:.5rem;color:var(--gray-900);font-size:1.125rem;font-weight:600;margin:0}.info-body{padding:1.5rem}.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}.info-item{display:flex;flex-direction:column;gap:.25rem}.info-item.full-width{grid-column:1 / -1}.info-item label{font-size:.875rem;font-weight:500;color:var(--gray-600)}.info-item value{font-size:.875rem;color:var(--gray-900);font-weight:500;word-break:break-word}.contact-link{display:inline-flex;align-items:center;gap:.5rem;color:var(--primary-color);text-decoration:none;transition:var(--transition)}.contact-link:hover{color:var(--primary-dark);text-decoration:none}.status-badge{display:inline-flex;align-items:center;padding:.375rem .75rem;border-radius:9999px;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em}.status-badge.status-paid{background:rgba(16,185,129,.1);color:var(--success-color)}.status-badge.status-pending{background:rgba(245,158,11,.1);color:var(--warning-color)}.status-badge.status-overdue{background:rgba(239,68,68,.1);color:var(--danger-color)}.status-badge.status-closed{background:rgba(107,114,128,.1);color:var(--gray-600)}.document-type-badge{display:inline-flex;align-items:center;padding:.375rem .75rem;background:var(--gray-100);color:var(--gray-700);border-radius:9999px;font-size:.75rem;font-weight:500}.action-buttons{display:flex;gap:.5rem}.action-btn-download{background:rgba(16,185,129,.1);color:var(--success-color)}.action-btn-download:hover{background:var(--success-color);color:white}@media (max-width:768px){.profile-header{flex-direction:column;text-align:center}.profile-meta{justify-content:center}.info-grid{grid-template-columns:1fr}}
    </style>
</body>
</html>
<?php ob_end_flush(); ?>
