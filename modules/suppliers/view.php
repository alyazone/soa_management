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
    $stmt = $pdo->prepare("SELECT * FROM supplier_contacts WHERE supplier_id = :id ORDER BY created_at ASC");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    $additional_contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Additional contacts fetch error: " . $e->getMessage());
    $additional_contacts = [];
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

// Calculate statistics
$total_soas = count($soas);
$total_amount = 0;
$pending_amount = 0;
$paid_amount = 0;
$overdue_amount = 0;

foreach($soas as $soa) {
    $total_amount += $soa['balance_amount'];
    switch($soa['status']) {
        case 'Paid':
            $paid_amount += $soa['balance_amount'];
            break;
        case 'Overdue':
            $overdue_amount += $soa['balance_amount'];
            break;
        default:
            $pending_amount += $soa['balance_amount'];
    }
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
        <!-- Page Header -->
        <header class="dashboard-header">
            <div class="header-content">
                <div class="header-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="header-title">
                        <h1><?php echo htmlspecialchars($supplier['supplier_name']); ?></h1>
                        <p>Supplier Details and Information</p>
                    </div>
                </div>
                <div class="header-right">
                    <?php if($_SESSION['position'] == 'Admin'): ?>
                    <a href="edit.php?id=<?php echo $supplier['supplier_id']; ?>" class="export-btn warning">
                        <i class="fas fa-edit"></i>
                        Edit Supplier
                    </a>
                    <?php endif; ?>
                    <a href="index.php" class="export-btn secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to List
                    </a>
                </div>
            </div>
        </header>

        <div class="dashboard-content">

            <!-- Supplier Profile Banner -->
            <div class="supplier-profile-card" data-aos="fade-down">
                <div class="supplier-banner"></div>
                <div class="supplier-profile-body">
                    <div class="supplier-avatar">
                        <i class="fas fa-industry"></i>
                    </div>
                    <div class="supplier-profile-info">
                        <h2><?php echo htmlspecialchars($supplier['supplier_name']); ?></h2>
                        <p class="supplier-id-badge">
                            <i class="fas fa-hashtag"></i>
                            SUP-<?php echo str_pad($supplier['supplier_id'], 3, '0', STR_PAD_LEFT); ?>
                        </p>
                        <div class="supplier-meta-row">
                            <span class="meta-chip">
                                <i class="fas fa-calendar-plus"></i>
                                Registered <?php echo date('M d, Y', strtotime($supplier['created_at'])); ?>
                            </span>
                            <span class="meta-chip">
                                <i class="fas fa-file-invoice-dollar"></i>
                                <?php echo $total_soas; ?> SOA<?php echo $total_soas !== 1 ? 's' : ''; ?>
                            </span>
                            <?php if(!empty($supplier['address'])): ?>
                            <span class="meta-chip">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars(explode(',', $supplier['address'])[0]); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid" data-aos="fade-up">
                <div class="stat-card primary">
                    <div class="stat-icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($total_soas); ?></h3>
                        <p>Total SOAs</p>
                    </div>
                </div>

                <div class="stat-card success">
                    <div class="stat-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stat-content">
                        <h3>RM <?php echo number_format($total_amount, 2); ?></h3>
                        <p>Total Amount</p>
                    </div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3>RM <?php echo number_format($pending_amount, 2); ?></h3>
                        <p>Pending Amount</p>
                    </div>
                </div>

                <div class="stat-card danger">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-content">
                        <h3>RM <?php echo number_format($overdue_amount, 2); ?></h3>
                        <p>Overdue Amount</p>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="content-grid" data-aos="fade-up" data-aos-delay="200">

                <!-- Supplier Information Card -->
                <div class="info-card">
                    <div class="info-header">
                        <h3>
                            <i class="fas fa-info-circle"></i>
                            Supplier Information
                        </h3>
                    </div>
                    <div class="info-body">

                        <!-- Basic Details -->
                        <div class="detail-row">
                            <span class="detail-label">
                                <i class="fas fa-industry"></i>
                                Supplier Name
                            </span>
                            <span class="detail-value"><?php echo htmlspecialchars($supplier['supplier_name']); ?></span>
                        </div>

                        <div class="detail-row">
                            <span class="detail-label">
                                <i class="fas fa-id-badge"></i>
                                Supplier ID
                            </span>
                            <span class="detail-value">
                                <span class="id-chip">SUP-<?php echo str_pad($supplier['supplier_id'], 3, '0', STR_PAD_LEFT); ?></span>
                            </span>
                        </div>

                        <div class="detail-row">
                            <span class="detail-label">
                                <i class="fas fa-map-marker-alt"></i>
                                Address
                            </span>
                            <span class="detail-value address-value">
                                <?php echo nl2br(htmlspecialchars($supplier['address'])); ?>
                            </span>
                        </div>

                        <div class="detail-row">
                            <span class="detail-label">
                                <i class="fas fa-calendar-alt"></i>
                                Registered
                            </span>
                            <span class="detail-value"><?php echo date('M d, Y \a\t H:i', strtotime($supplier['created_at'])); ?></span>
                        </div>

                        <!-- Primary Contact -->
                        <div class="contact-section">
                            <div class="contact-section-title">
                                <i class="fas fa-address-card"></i>
                                Primary Contact
                            </div>
                            <div class="contact-card primary-contact">
                                <div class="contact-name">
                                    <i class="fas fa-user-circle"></i>
                                    <?php echo htmlspecialchars($supplier['pic_name']); ?>
                                </div>
                                <div class="contact-links">
                                    <a href="tel:<?php echo htmlspecialchars($supplier['pic_contact']); ?>" class="contact-link phone">
                                        <i class="fas fa-phone"></i>
                                        <?php echo htmlspecialchars($supplier['pic_contact']); ?>
                                    </a>
                                    <a href="mailto:<?php echo htmlspecialchars($supplier['pic_email']); ?>" class="contact-link email">
                                        <i class="fas fa-envelope"></i>
                                        <?php echo htmlspecialchars($supplier['pic_email']); ?>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Contacts -->
                        <?php if(!empty($additional_contacts)): ?>
                        <div class="contact-section">
                            <div class="contact-section-title">
                                <i class="fas fa-users"></i>
                                Additional Contacts
                            </div>
                            <?php foreach($additional_contacts as $idx => $contact): ?>
                            <div class="contact-card additional-contact">
                                <div class="contact-card-header">
                                    <span class="contact-number-badge">Contact <?php echo $idx + 2; ?></span>
                                </div>
                                <div class="contact-name">
                                    <i class="fas fa-user-circle"></i>
                                    <?php echo htmlspecialchars($contact['contact_name']); ?>
                                </div>
                                <div class="contact-links">
                                    <a href="tel:<?php echo htmlspecialchars($contact['contact_number']); ?>" class="contact-link phone">
                                        <i class="fas fa-phone"></i>
                                        <?php echo htmlspecialchars($contact['contact_number']); ?>
                                    </a>
                                    <a href="mailto:<?php echo htmlspecialchars($contact['contact_email']); ?>" class="contact-link email">
                                        <i class="fas fa-envelope"></i>
                                        <?php echo htmlspecialchars($contact['contact_email']); ?>
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>

                <!-- Related SOAs Table -->
                <div class="table-card">
                    <div class="table-header">
                        <div class="table-title">
                            <h3>
                                <i class="fas fa-file-invoice-dollar"></i>
                                Related SOAs
                            </h3>
                            <p><?php echo $total_soas; ?> SOA record<?php echo $total_soas !== 1 ? 's' : ''; ?></p>
                        </div>
                        <div class="table-actions">
                            <a href="<?php echo $basePath; ?>modules/soa/supplier/add.php?supplier_id=<?php echo $supplier['supplier_id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i>
                                Create SOA
                            </a>
                        </div>
                    </div>
                    <div class="table-container">
                        <?php if(!empty($soas)): ?>
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Account #</th>
                                    <th>Client</th>
                                    <th>Issue Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($soas as $soa): ?>
                                <tr>
                                    <td>
                                        <span class="account-number">
                                            <?php echo htmlspecialchars($soa['account_number']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($soa['client_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($soa['issue_date'])); ?></td>
                                    <td>
                                        <span class="amount-display">
                                            RM <?php echo number_format($soa['balance_amount'], 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($soa['status']); ?>">
                                            <?php echo htmlspecialchars($soa['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="<?php echo $basePath; ?>modules/soa/supplier/view.php?id=<?php echo $soa['soa_id']; ?>"
                                               class="action-btn action-btn-view"
                                               title="View SOA">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                            <h3>No SOAs Found</h3>
                            <p>This supplier doesn't have any SOA records yet.</p>
                            <a href="<?php echo $basePath; ?>modules/soa/supplier/add.php?supplier_id=<?php echo $supplier['supplier_id']; ?>" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                Create First SOA
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Documents Section -->
            <?php if(!empty($documents)): ?>
            <div class="table-card" data-aos="fade-up" data-aos-delay="400">
                <div class="table-header">
                    <div class="table-title">
                        <h3>
                            <i class="fas fa-folder-open"></i>
                            Related Documents
                        </h3>
                        <p><?php echo count($documents); ?> document<?php echo count($documents) !== 1 ? 's' : ''; ?></p>
                    </div>
                </div>
                <div class="table-container">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Document Type</th>
                                <th>File Name</th>
                                <th>Upload Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($documents as $document): ?>
                            <tr>
                                <td>
                                    <span class="document-type-badge">
                                        <?php echo htmlspecialchars($document['document_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($document['file_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($document['upload_date'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?php echo $basePath . $document['file_path']; ?>"
                                           class="action-btn action-btn-view"
                                           target="_blank"
                                           title="View Document">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo $basePath . $document['file_path']; ?>"
                                           class="action-btn action-btn-download"
                                           download
                                           title="Download Document">
                                            <i class="fas fa-download"></i>
                                        </a>
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
        /* ============================================
           SUPPLIER PROFILE BANNER
           ============================================ */
        .supplier-profile-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .supplier-banner {
            height: 80px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #6366f1 100%);
            position: relative;
        }

        .supplier-banner::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.15) 0%, transparent 60%),
                              radial-gradient(circle at 80% 20%, rgba(255,255,255,0.1) 0%, transparent 40%);
        }

        .supplier-profile-body {
            display: flex;
            align-items: flex-end;
            gap: 1.5rem;
            padding: 0 2rem 1.75rem;
        }

        .supplier-avatar {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-radius: var(--border-radius);
            border: 3px solid white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.75rem;
            margin-top: -36px;
            flex-shrink: 0;
        }

        .supplier-profile-info {
            padding-top: 0.75rem;
            flex: 1;
            min-width: 0;
        }

        .supplier-profile-info h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0 0 0.375rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .supplier-id-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--gray-500);
            margin: 0 0 0.875rem;
            letter-spacing: 0.03em;
        }

        .supplier-meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.625rem;
        }

        .meta-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.3rem 0.75rem;
            background: var(--gray-100);
            color: var(--gray-600);
            border-radius: 9999px;
            font-size: 0.8125rem;
            font-weight: 500;
        }

        .meta-chip i {
            font-size: 0.75rem;
            color: var(--gray-400);
        }

        /* ============================================
           CONTENT GRID
           ============================================ */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        /* ============================================
           INFO CARD
           ============================================ */
        .info-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
        }

        .info-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .info-header h3 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-900);
            font-size: 1rem;
            font-weight: 600;
            margin: 0;
        }

        .info-header h3 i {
            color: var(--primary-color);
        }

        .info-body {
            padding: 0.5rem 0;
        }

        /* Detail rows */
        .detail-row {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 0.875rem 1.5rem;
            border-bottom: 1px solid var(--gray-100);
        }

        .detail-row:last-of-type {
            border-bottom: none;
        }

        .detail-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--gray-500);
            min-width: 110px;
            flex-shrink: 0;
        }

        .detail-label i {
            font-size: 0.75rem;
            width: 14px;
            text-align: center;
        }

        .detail-value {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--gray-800);
            flex: 1;
            min-width: 0;
        }

        .address-value {
            line-height: 1.6;
        }

        .id-chip {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.625rem;
            background: rgba(79, 70, 229, 0.08);
            color: #4f46e5;
            border-radius: 6px;
            font-size: 0.8125rem;
            font-weight: 600;
            font-family: monospace;
            letter-spacing: 0.05em;
        }

        /* ============================================
           CONTACT SECTIONS
           ============================================ */
        .contact-section {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--gray-100);
        }

        .contact-section-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 0.75rem;
        }

        .contact-section-title i {
            color: var(--gray-400);
        }

        .contact-card {
            border-radius: var(--border-radius-sm);
            padding: 0.875rem 1rem;
            border: 1px solid var(--gray-200);
        }

        .contact-card.primary-contact {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.04), rgba(124, 58, 237, 0.04));
            border-color: rgba(79, 70, 229, 0.2);
        }

        .contact-card.additional-contact {
            background: var(--gray-50);
            margin-bottom: 0.625rem;
        }

        .contact-card.additional-contact:last-child {
            margin-bottom: 0;
        }

        .contact-card-header {
            margin-bottom: 0.5rem;
        }

        .contact-number-badge {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            background: var(--gray-200);
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
        }

        .contact-name {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 0.625rem;
        }

        .contact-name i {
            color: #4f46e5;
            font-size: 1.1rem;
        }

        .contact-links {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
        }

        .contact-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 500;
            text-decoration: none;
            padding: 0.3rem 0.625rem;
            border-radius: 6px;
            transition: var(--transition);
            width: fit-content;
        }

        .contact-link.phone {
            color: var(--success-color);
            background: rgba(16, 185, 129, 0.07);
        }

        .contact-link.phone:hover {
            background: rgba(16, 185, 129, 0.15);
            text-decoration: none;
        }

        .contact-link.email {
            color: var(--primary-color);
            background: rgba(59, 130, 246, 0.07);
        }

        .contact-link.email:hover {
            background: rgba(59, 130, 246, 0.15);
            text-decoration: none;
        }

        .contact-link i {
            font-size: 0.75rem;
        }

        /* ============================================
           SOA TABLE
           ============================================ */
        .account-number {
            font-family: monospace;
            font-weight: 600;
            color: var(--primary-color);
        }

        .amount-display {
            font-weight: 600;
            color: var(--gray-800);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.3rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-paid {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .status-overdue {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        /* ============================================
           DOCUMENTS
           ============================================ */
        .document-type-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.3rem 0.75rem;
            background: var(--gray-100);
            color: var(--gray-700);
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .action-btn-download {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .action-btn-download:hover {
            background: var(--success-color);
            color: white;
        }

        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .supplier-profile-body {
                flex-direction: column;
                align-items: flex-start;
                padding: 0 1.25rem 1.25rem;
            }

            .supplier-avatar {
                margin-top: -28px;
            }

            .supplier-profile-info h2 {
                font-size: 1.25rem;
                white-space: normal;
            }

            .detail-row {
                flex-direction: column;
                gap: 0.375rem;
            }

            .detail-label {
                min-width: auto;
            }
        }
    </style>
</body>
</html>
