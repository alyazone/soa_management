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

// Check if the user has admin privileges or is viewing their own profile
if($_SESSION['position'] != 'Admin' && $_SESSION['staff_id'] != $_GET['id']){
    $access_denied = true;
} else {
    $access_denied = false;
}

// Check if id parameter is set
if(empty($_GET["id"])){
    header("location: index.php");
    exit();
}

if(!$access_denied) {
    // Fetch staff data
    try {
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = :id");
        $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
        $stmt->execute();
        
        if($stmt->rowCount() != 1){
            header("location: index.php");
            exit();
        }
        
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        die("ERROR: Could not fetch staff. " . $e->getMessage());
    }

    // Fetch staff claims
    try {
        $stmt = $pdo->prepare("SELECT * FROM claims WHERE staff_id = :id ORDER BY submitted_date DESC LIMIT 10");
        $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
        $stmt->execute();
        $claims = $stmt->fetchAll();
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }

    // Fetch staff documents
    try {
        $stmt = $pdo->prepare("SELECT * FROM documents WHERE uploaded_by = :id ORDER BY upload_date DESC LIMIT 10");
        $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
        $stmt->execute();
        $documents = $stmt->fetchAll();
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }

    // Get statistics
    try {
        // Total claims
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_claims, SUM(amount) as total_amount FROM claims WHERE staff_id = :id");
        $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
        $stmt->execute();
        $claims_stats = $stmt->fetch();

        // Approved claims
        $stmt = $pdo->prepare("SELECT COUNT(*) as approved_claims, SUM(amount) as approved_amount FROM claims WHERE staff_id = :id AND status = 'Approved'");
        $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
        $stmt->execute();
        $approved_stats = $stmt->fetch();

        // Documents count
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_documents FROM documents WHERE uploaded_by = :id");
        $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
        $stmt->execute();
        $docs_stats = $stmt->fetch();
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Profile - SOA Management System</title>
    
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
                        <h1><?php echo $access_denied ? 'Access Denied' : 'Staff Profile'; ?></h1>
                        <p><?php echo $access_denied ? 'You do not have permission to view this profile' : 'View staff member details and activity'; ?></p>
                    </div>
                </div>
                <div class="header-right">
                    <?php if(!$access_denied): ?>
                    <a href="edit.php?id=<?php echo $staff['staff_id']; ?>" class="export-btn">
                        <i class="fas fa-edit"></i>
                        Edit Profile
                    </a>
                    <?php endif; ?>
                    <a href="index.php" class="date-picker-btn">
                        <i class="fas fa-arrow-left"></i>
                        Back to List
                    </a>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <?php if($access_denied): ?>
            <!-- Access Denied -->
            <div class="access-denied-card">
                <div class="access-denied-content">
                    <div class="access-denied-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h2>Access Denied</h2>
                    <p>You do not have permission to view this staff profile. You can only view your own profile or you need administrator privileges.</p>
                    <a href="<?php echo $basePath; ?>dashboard.php" class="btn-primary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
            <?php else: ?>

            <!-- Profile Header -->
            <div class="profile-header-card" data-aos="fade-up">
                <div class="profile-header-content">
                    <div class="profile-avatar-section">
                        <div class="profile-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="profile-info">
                            <h2><?php echo htmlspecialchars($staff['full_name']); ?></h2>
                            <p class="profile-username">@<?php echo htmlspecialchars($staff['username']); ?></p>
                            <div class="profile-badges">
                                <span class="position-badge position-<?php echo strtolower($staff['position']); ?>">
                                    <?php echo htmlspecialchars($staff['position']); ?>
                                </span>
                                <span class="department-badge">
                                    <?php echo htmlspecialchars($staff['department']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="profile-actions">
                        <div class="profile-status">
                            <span class="status-badge status-active">
                                <i class="fas fa-circle"></i>
                                Active
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-card stat-card-primary">
                    <div class="stat-card-content">
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $claims_stats['total_claims'] ?? 0; ?></div>
                            <div class="stat-label">Total Claims</div>
                            <div class="stat-change">
                                <span>RM <?php echo number_format($claims_stats['total_amount'] ?? 0, 2); ?></span>
                            </div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-receipt"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card stat-card-success">
                    <div class="stat-card-content">
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $approved_stats['approved_claims'] ?? 0; ?></div>
                            <div class="stat-label">Approved Claims</div>
                            <div class="stat-change">
                                <span>RM <?php echo number_format($approved_stats['approved_amount'] ?? 0, 2); ?></span>
                            </div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card stat-card-info">
                    <div class="stat-card-content">
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $docs_stats['total_documents'] ?? 0; ?></div>
                            <div class="stat-label">Documents</div>
                            <div class="stat-change">
                                <span>Uploaded</span>
                            </div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card stat-card-warning">
                    <div class="stat-card-content">
                        <div class="stat-info">
                            <div class="stat-value"><?php echo date('d', strtotime($staff['created_at'])); ?></div>
                            <div class="stat-label">Days Active</div>
                            <div class="stat-change">
                                <span>Since <?php echo date('M Y', strtotime($staff['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="profile-content-grid">
                <!-- Staff Information -->
                <div class="profile-info-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="card-header">
                        <div class="card-title">
                            <h3>Staff Information</h3>
                            <p>Personal and contact details</p>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Staff ID</div>
                                <div class="info-value">#<?php echo str_pad($staff['staff_id'], 3, '0', STR_PAD_LEFT); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Full Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($staff['full_name']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Username</div>
                                <div class="info-value">@<?php echo htmlspecialchars($staff['username']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Email Address</div>
                                <div class="info-value">
                                    <a href="mailto:<?php echo htmlspecialchars($staff['email']); ?>" class="email-link">
                                        <i class="fas fa-envelope"></i>
                                        <?php echo htmlspecialchars($staff['email']); ?>
                                    </a>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Department</div>
                                <div class="info-value"><?php echo htmlspecialchars($staff['department']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Position</div>
                                <div class="info-value">
                                    <span class="position-badge position-<?php echo strtolower($staff['position']); ?>">
                                        <?php echo htmlspecialchars($staff['position']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Member Since</div>
                                <div class="info-value"><?php echo date('F j, Y', strtotime($staff['created_at'])); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Last Updated</div>
                                <div class="info-value"><?php echo date('F j, Y \a\t g:i A', strtotime($staff['created_at'])); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Claims -->
                <div class="claims-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="card-header">
                        <div class="card-title">
                            <h3>Recent Claims</h3>
                            <p>Latest expense claims submitted</p>
                        </div>
                        <div class="card-actions">
                            <a href="<?php echo $basePath; ?>modules/claims/index.php" class="table-action-btn">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-content">
                        <?php if(!empty($claims)): ?>
                            <div class="claims-list">
                                <?php foreach($claims as $claim): ?>
                                <div class="claim-item">
                                    <div class="claim-info">
                                        <div class="claim-amount">RM <?php echo number_format($claim['amount'], 2); ?></div>
                                        <div class="claim-date"><?php echo date('M j, Y', strtotime($claim['submitted_date'])); ?></div>
                                    </div>
                                    <div class="claim-status">
                                        <span class="status-badge status-<?php echo strtolower($claim['status']); ?>">
                                            <?php echo htmlspecialchars($claim['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-receipt"></i>
                                <h4>No Claims Found</h4>
                                <p>This staff member hasn't submitted any claims yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Documents -->
                <div class="documents-card" data-aos="fade-up" data-aos-delay="400">
                    <div class="card-header">
                        <div class="card-title">
                            <h3>Recent Documents</h3>
                            <p>Latest uploaded documents</p>
                        </div>
                        <div class="card-actions">
                            <a href="<?php echo $basePath; ?>modules/documents/index.php" class="table-action-btn">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-content">
                        <?php if(!empty($documents)): ?>
                            <div class="documents-list">
                                <?php foreach($documents as $document): ?>
                                <div class="document-item">
                                    <div class="document-icon">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <div class="document-info">
                                        <div class="document-name">
                                            <a href="<?php echo $basePath . $document['file_path']; ?>" target="_blank">
                                                <?php echo htmlspecialchars($document['file_name']); ?>
                                            </a>
                                        </div>
                                        <div class="document-meta">
                                            <span class="document-type"><?php echo htmlspecialchars($document['document_type']); ?></span>
                                            <span class="document-date"><?php echo date('M j, Y', strtotime($document['upload_date'])); ?></span>
                                        </div>
                                    </div>
                                    <div class="document-actions">
                                        <a href="<?php echo $basePath . $document['file_path']; ?>" target="_blank" class="action-btn action-btn-view">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-file-alt"></i>
                                <h4>No Documents Found</h4>
                                <p>This staff member hasn't uploaded any documents yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
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
        /* Profile Specific Styles */
        .profile-header-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .profile-header-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .profile-avatar-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
        }

        .profile-info h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .profile-username {
            color: var(--gray-600);
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .profile-badges {
            display: flex;
            gap: 0.5rem;
        }

        .profile-status {
            text-align: right;
        }

        .status-badge i {
            font-size: 0.5rem;
            margin-right: 0.25rem;
        }

        /* Content Grid */
        .profile-content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .profile-info-card {
            grid-column: 1 / -1;
        }

        .profile-info-card,
        .claims-card,
        .documents-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            overflow: hidden;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 1.5rem 1.5rem 1rem;
            border-bottom: 1px solid var(--gray-100);
        }

        .card-title h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .card-title p {
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        .card-content {
            padding: 1.5rem;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .info-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .info-value {
            font-size: 0.875rem;
            color: var(--gray-900);
            font-weight: 500;
        }

        .email-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .email-link:hover {
            color: var(--primary-dark);
            text-decoration: none;
        }

        /* Claims List */
        .claims-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .claim-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: var(--gray-50);
            border-radius: var(--border-radius-sm);
            border: 1px solid var(--gray-200);
        }

        .claim-amount {
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-900);
        }

        .claim-date {
            font-size: 0.75rem;
            color: var(--gray-500);
        }

        /* Documents List */
        .documents-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .document-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--gray-50);
            border-radius: var(--border-radius-sm);
            border: 1px solid var(--gray-200);
        }

        .document-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: var(--border-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .document-info {
            flex: 1;
        }

        .document-name a {
            color: var(--gray-900);
            text-decoration: none;
            font-weight: 500;
        }

        .document-name a:hover {
            color: var(--primary-color);
            text-decoration: none;
        }

        .document-meta {
            display: flex;
            gap: 1rem;
            margin-top: 0.25rem;
        }

        .document-type,
        .document-date {
            font-size: 0.75rem;
            color: var(--gray-500);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 2rem;
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--gray-300);
            margin-bottom: 1rem;
        }

        .empty-state h4 {
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: var(--gray-500);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .profile-content-grid {
                grid-template-columns: 1fr;
            }

            .profile-info-card {
                grid-column: 1;
            }
        }

        @media (max-width: 768px) {
            .profile-header-content {
                flex-direction: column;
                gap: 1.5rem;
            }

            .profile-avatar-section {
                flex-direction: column;
                text-align: center;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .claim-item,
            .document-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }
        }
    </style>
</body>
</html>
