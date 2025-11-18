<?php
$basePath = '../../';
require_once $basePath . "config/database.php";

session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}

if(empty($_GET["id"])){
    header("location: index.php");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE supplier_id = :id");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    
    if($stmt->rowCount() != 1){
        header("location: index.php");
        exit();
    }
    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Supplier fetch error: " . $e->getMessage());
    header("location: index.php");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT s.*, c.client_name 
                           FROM soa s 
                           JOIN clients c ON s.client_id = c.client_id 
                           WHERE s.supplier_id = :id 
                           ORDER BY s.issue_date DESC");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    $soas = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("SOAs fetch error: " . $e->getMessage());
    $soas = [];
}

try {
    $stmt = $pdo->prepare("SELECT * FROM documents 
                           WHERE reference_type = 'Supplier' AND reference_id = :id 
                           ORDER BY upload_date DESC");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    $documents = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Documents fetch error: " . $e->getMessage());
    $documents = [];
}

$total_soas = count($soas);
$total_amount = 0;
foreach($soas as $soa) {
    $total_amount += $soa['balance_amount'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Details - SOA Management System</title>
    
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/modern-dashboard.css">
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
                        <h1><?php echo htmlspecialchars($supplier['supplier_name']); ?></h1>
                        <p>Supplier Details and Information</p>
                    </div>
                </div>
                <div class="header-right">
                    <?php if($_SESSION['position'] == 'Admin'): ?>
                    <a href="edit.php?id=<?php echo $supplier['supplier_id']; ?>" class="export-btn warning"><i class="fas fa-edit"></i> Edit Supplier</a>
                    <?php endif; ?>
                    <a href="index.php" class="export-btn secondary"><i class="fas fa-arrow-left"></i> Back to List</a>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="profile-header" data-aos="fade-down">
                <div class="profile-avatar" style="background-color: #6366f1;"><i class="fas fa-truck-fast"></i></div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($supplier['supplier_name']); ?></h2>
                    <p class="profile-subtitle">Supplier ID: #<?php echo str_pad($supplier['supplier_id'], 3, '0', STR_PAD_LEFT); ?></p>
                    <div class="profile-meta">
                        <span class="meta-item"><i class="fas fa-calendar-alt"></i> Registered: <?php echo date('M d, Y', strtotime($supplier['created_at'])); ?></span>
                        <span class="meta-item"><i class="fas fa-file-invoice-dollar"></i> <?php echo $total_soas; ?> SOAs</span>
                    </div>
                </div>
            </div>

            <div class="stats-grid" data-aos="fade-up">
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($total_soas); ?></h3>
                        <p>Total SOAs</p>
                    </div>
                </div>
                <div class="stat-card success">
                    <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="stat-content">
                        <h3>RM <?php echo number_format($total_amount, 2); ?></h3>
                        <p>Total Amount</p>
                    </div>
                </div>
            </div>

            <div class="content-grid" data-aos="fade-up" data-aos-delay="200">
                <div class="info-card">
                    <div class="info-header"><h3><i class="fas fa-info-circle"></i> Supplier Information</h3></div>
                    <div class="info-body">
                        <div class="info-grid">
                            <div class="info-item"><label>Supplier Name</label><value><?php echo htmlspecialchars($supplier['supplier_name']); ?></value></div>
                            <div class="info-item"><label>Supplier ID</label><value>#<?php echo str_pad($supplier['supplier_id'], 3, '0', STR_PAD_LEFT); ?></value></div>
                            <div class="info-item full-width"><label>Address</label><value><?php echo nl2br(htmlspecialchars($supplier['address'])); ?></value></div>
                            <div class="info-item"><label>PIC Name</label><value><?php echo htmlspecialchars($supplier['pic_name']); ?></value></div>
                            <div class="info-item"><label>PIC Contact</label><value><a href="tel:<?php echo htmlspecialchars($supplier['pic_contact']); ?>" class="contact-link"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($supplier['pic_contact']); ?></a></value></div>
                            <div class="info-item"><label>PIC Email</label><value><a href="mailto:<?php echo htmlspecialchars($supplier['pic_email']); ?>" class="contact-link"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($supplier['pic_email']); ?></a></value></div>
                            <div class="info-item"><label>Created At</label><value><?php echo date('M d, Y H:i', strtotime($supplier['created_at'])); ?></value></div>
                        </div>
                    </div>
                </div>

                <div class="table-card">
                    <div class="table-header">
                        <div class="table-title">
                            <h3><i class="fas fa-file-invoice-dollar"></i> Related SOAs</h3>
                            <p><?php echo count($soas); ?> SOA records</p>
                        </div>
                    </div>
                    <div class="table-container">
                        <?php if(!empty($soas)): ?>
                            <table class="modern-table">
                                <thead><tr><th>Account #</th><th>Client</th><th>Issue Date</th><th>Amount</th><th>Status</th><th>Actions</th></tr></thead>
                                <tbody>
                                    <?php foreach($soas as $soa): ?>
                                    <tr>
                                        <td><span class="account-number"><?php echo htmlspecialchars($soa['account_number']); ?></span></td>
                                        <td><?php echo htmlspecialchars($soa['client_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($soa['issue_date'])); ?></td>
                                        <td><span class="amount-display">RM <?php echo number_format($soa['balance_amount'], 2); ?></span></td>
                                        <td><span class="status-badge status-<?php echo strtolower($soa['status']); ?>"><?php echo htmlspecialchars($soa['status']); ?></span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="<?php echo $basePath; ?>modules/soa/view.php?id=<?php echo $soa['soa_id']; ?>" class="action-btn action-btn-view" title="View SOA"><i class="fas fa-eye"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                                <h3>No SOAs Found</h3>
                                <p>This supplier doesn't have any SOA records yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if(!empty($documents)): ?>
            <div class="table-card" data-aos="fade-up" data-aos-delay="400">
                <div class="table-header">
                    <div class="table-title">
                        <h3><i class="fas fa-folder-open"></i> Related Documents</h3>
                        <p><?php echo count($documents); ?> documents</p>
                    </div>
                </div>
                <div class="table-container">
                    <table class="modern-table">
                        <thead><tr><th>Document Type</th><th>File Name</th><th>Upload Date</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach($documents as $document): ?>
                            <tr>
                                <td><span class="document-type-badge"><?php echo htmlspecialchars($document['document_type']); ?></span></td>
                                <td><?php echo htmlspecialchars($document['file_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($document['upload_date'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?php echo $basePath . $document['file_path']; ?>" class="action-btn action-btn-view" target="_blank" title="View Document"><i class="fas fa-eye"></i></a>
                                        <a href="<?php echo $basePath . $document['file_path']; ?>" class="action-btn action-btn-download" download title="Download Document"><i class="fas fa-download"></i></a>
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
    <script src="../../assets/js/modern-dashboard.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({ duration: 800, easing: 'ease-in-out', once: true });
            initializeDashboard();
        });
    </script>
    <style>
        .profile-header{background:white;border-radius:var(--border-radius);box-shadow:var(--shadow);border:1px solid var(--gray-200);padding:2rem;margin-bottom:2rem;display:flex;align-items:center;gap:1.5rem}.profile-avatar{width:80px;height:80px;background:var(--primary-color);border-radius:var(--border-radius);display:flex;align-items:center;justify-content:center;color:white;font-size:2rem}.profile-info h2{color:var(--gray-900);margin-bottom:.5rem;font-size:1.5rem;font-weight:600}.profile-subtitle{color:var(--gray-600);margin-bottom:1rem}.profile-meta{display:flex;gap:1.5rem}.meta-item{display:flex;align-items:center;gap:.5rem;color:var(--gray-600);font-size:.875rem}.meta-item i{color:var(--gray-400)}.content-grid{display:grid;grid-template-columns:1fr 2fr;gap:2rem;margin-bottom:2rem}.info-card{background:white;border-radius:var(--border-radius);box-shadow:var(--shadow);border:1px solid var(--gray-200)}.info-header{padding:1.5rem;border-bottom:1px solid var(--gray-200)}.info-header h3{display:flex;align-items:center;gap:.5rem;color:var(--gray-900);font-size:1.125rem;font-weight:600;margin:0}.info-body{padding:1.5rem}.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}.info-item{display:flex;flex-direction:column;gap:.5rem}.info-item.full-width{grid-column:1 / -1}.info-item label{font-size:.875rem;font-weight:500;color:var(--gray-600)}.info-item value{font-size:.875rem;color:var(--gray-900);font-weight:500}.contact-link{display:inline-flex;align-items:center;gap:.5rem;color:var(--primary-color);text-decoration:none;transition:var(--transition)}.contact-link:hover{color:var(--primary-dark);text-decoration:none}.account-number{font-family:monospace;font-weight:600;color:var(--primary-color)}.document-type-badge{display:inline-flex;align-items:center;padding:.375rem .75rem;background:var(--gray-100);color:var(--gray-700);border-radius:9999px;font-size:.75rem;font-weight:500}.status-badge{display:inline-flex;align-items:center;padding:.375rem .75rem;border-radius:9999px;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em}.status-paid{background:rgba(16,185,129,.1);color:var(--success-color)}.status-pending{background:rgba(245,158,11,.1);color:var(--warning-color)}.status-overdue{background:rgba(239,68,68,.1);color:var(--danger-color)}.action-btn-download{background:rgba(16,185,129,.1);color:var(--success-color)}.action-btn-download:hover{background:var(--success-color);color:white}@media (max-width:768px){.profile-header{flex-direction:column;text-align:center}.content-grid{grid-template-columns:1fr}.info-grid{grid-template-columns:1fr}.profile-meta{justify-content:center}}
    </style>
</body>
</html>
