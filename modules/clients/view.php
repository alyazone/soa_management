<?php
// Set the base path for includes
$basePath = '../../';

// Include database connection
require_once $basePath . "config/database.php";

// Check if user is logged in
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}

// Check if id parameter is set
if(empty($_GET["id"])){
    header("location: index.php");
    exit();
}

// Fetch client data
try {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = :id");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    
    if($stmt->rowCount() != 1){
        header("location: index.php");
        exit();
    }
    
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    error_log("Client fetch error: " . $e->getMessage());
    header("location: index.php");
    exit();
}

// Fetch SOAs related to this client
try {
    $stmt = $pdo->prepare("SELECT s.*, sup.supplier_name 
                           FROM soa s 
                           JOIN suppliers sup ON s.supplier_id = sup.supplier_id 
                           WHERE s.client_id = :id 
                           ORDER BY s.issue_date DESC");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    $soas = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("SOAs fetch error: " . $e->getMessage());
    $soas = [];
}

// Fetch documents related to this client
try {
    $stmt = $pdo->prepare("SELECT * FROM documents 
                           WHERE reference_type = 'Client' AND reference_id = :id 
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
    <title>Client Details - SOA Management System</title>
    
    <!-- Modern CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/modern-dashboard.css">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Sidebar -->
    <?php include_once $basePath . "includes/modern-sidebar.php"; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="dashboard-header">
            <div class="header-content">
                <div class="header-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="header-title">
                        <h1><?php echo htmlspecialchars($client['client_name']); ?></h1>
                        <p>Client Details and Information</p>
                    </div>
                </div>
                <div class="header-right">
                    <?php if($_SESSION['position'] == 'Admin'): ?>
                    <a href="edit.php?id=<?php echo $client['client_id']; ?>" class="export-btn warning">
                        <i class="fas fa-edit"></i>
                        Edit Client
                    </a>
                    <?php endif; ?>
                    <a href="index.php" class="export-btn secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to List
                    </a>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Client Profile Header -->
            <div class="profile-header" data-aos="fade-down">
                <div class="profile-avatar">
                    <i class="fas fa-building"></i>
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($client['client_name']); ?></h2>
                    <p class="profile-subtitle">Client ID: #<?php echo str_pad($client['client_id'], 3, '0', STR_PAD_LEFT); ?></p>
                    <div class="profile-meta">
                        <span class="meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            Registered: <?php echo date('M d, Y', strtotime($client['created_at'])); ?>
                        </span>
                        <span class="meta-item">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <?php echo $total_soas; ?> SOAs
                        </span>
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
                        <i class="fas fa-dollar-sign"></i>
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

            <!-- Content Grid -->
            <div class="content-grid" data-aos="fade-up" data-aos-delay="200">
                <!-- Client Information -->
                <div class="info-card">
                    <div class="info-header">
                        <h3>
                            <i class="fas fa-info-circle"></i>
                            Client Information
                        </h3>
                    </div>
                    <div class="info-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Client Name</label>
                                <value><?php echo htmlspecialchars($client['client_name']); ?></value>
                            </div>
                            <div class="info-item">
                                <label>Client ID</label>
                                <value>#<?php echo str_pad($client['client_id'], 3, '0', STR_PAD_LEFT); ?></value>
                            </div>
                            <div class="info-item full-width">
                                <label>Address</label>
                                <value><?php echo nl2br(htmlspecialchars($client['address'])); ?></value>
                            </div>
                            <div class="info-item">
                                <label>PIC Name</label>
                                <value><?php echo htmlspecialchars($client['pic_name']); ?></value>
                            </div>
                            <div class="info-item">
                                <label>PIC Contact</label>
                                <value>
                                    <a href="tel:<?php echo htmlspecialchars($client['pic_contact']); ?>" class="contact-link">
                                        <i class="fas fa-phone"></i>
                                        <?php echo htmlspecialchars($client['pic_contact']); ?>
                                    </a>
                                </value>
                            </div>
                            <div class="info-item">
                                <label>PIC Email</label>
                                <value>
                                    <a href="mailto:<?php echo htmlspecialchars($client['pic_email']); ?>" class="contact-link">
                                        <i class="fas fa-envelope"></i>
                                        <?php echo htmlspecialchars($client['pic_email']); ?>
                                    </a>
                                </value>
                            </div>
                            <div class="info-item">
                                <label>Created At</label>
                                <value><?php echo date('M d, Y H:i', strtotime($client['created_at'])); ?></value>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Related SOAs -->
                <div class="table-card">
                    <div class="table-header">
                        <div class="table-title">
                            <h3>
                                <i class="fas fa-file-invoice-dollar"></i>
                                Related SOAs
                            </h3>
                            <p><?php echo count($soas); ?> SOA records</p>
                        </div>
                        <div class="table-actions">
                            <a href="<?php echo $basePath; ?>modules/soa/add.php?client_id=<?php echo $client['client_id']; ?>" class="btn btn-primary btn-sm">
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
                                        <th>Supplier</th>
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
                                        <td><?php echo htmlspecialchars($soa['supplier_name']); ?></td>
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
                                                <a href="<?php echo $basePath; ?>modules/soa/view.php?id=<?php echo $soa['soa_id']; ?>" 
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
                                <p>This client doesn't have any SOA records yet.</p>
                                <a href="<?php echo $basePath; ?>modules/soa/add.php?client_id=<?php echo $client['client_id']; ?>" class="btn btn-primary">
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
                        <p><?php echo count($documents); ?> documents</p>
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="../../assets/js/modern-dashboard.js"></script>
    
    <script>
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize AOS animations
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true
            });

            // Initialize interactions
            initializeDashboard();
        });
    </script>

    <style>
        /* Client View Specific Styles */
        .profile-header {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            padding: 2rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: var(--primary-color);
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
        }

        .profile-info h2 {
            color: var(--gray-900);
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .profile-subtitle {
            color: var(--gray-600);
            margin-bottom: 1rem;
        }

        .profile-meta {
            display: flex;
            gap: 1.5rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        .meta-item i {
            color: var(--gray-400);
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        /* Info Card */
        .info-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
        }

        .info-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .info-header h3 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-900);
            font-size: 1.125rem;
            font-weight: 600;
            margin: 0;
        }

        .info-body {
            padding: 1.5rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .info-item.full-width {
            grid-column: 1 / -1;
        }

        .info-item label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--gray-600);
        }

        .info-item value {
            font-size: 0.875rem;
            color: var(--gray-900);
            font-weight: 500;
        }

        .contact-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .contact-link:hover {
            color: var(--primary-dark);
            text-decoration: none;
        }

        /* Account Number */
        .account-number {
            font-family: monospace;
            font-weight: 600;
            color: var(--primary-color);
        }

        /* Document Type Badge */
        .document-type-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.375rem 0.75rem;
            background: var(--gray-100);
            color: var(--gray-700);
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.375rem 0.75rem;
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

        /* Action Button Download */
        .action-btn-download {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .action-btn-download:hover {
            background: var(--success-color);
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .profile-meta {
                justify-content: center;
            }
        }
    </style>
</body>
</html>
