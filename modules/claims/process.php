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

// Check if user has admin or manager privileges
if($_SESSION['position'] != 'Admin' && $_SESSION['position'] != 'Manager'){
    header("location: index.php");
    exit;
}

// Check if id parameter is set
if(empty($_GET["id"])){
    header("location: index.php");
    exit();
}

// Define variables
$status = "";
$status_err = "";

// Fetch claim data
try {
    $stmt = $pdo->prepare("SELECT c.*, s.full_name as staff_name, s.department
                           FROM claims c
                           JOIN staff s ON c.staff_id = s.staff_id
                           WHERE c.claim_id = :id");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();

    if($stmt->rowCount() != 1){
        header("location: index.php");
        exit();
    }

    $claim = $stmt->fetch(PDO::FETCH_ASSOC);

    if($claim['status'] != 'Pending'){
        header("location: index.php");
        exit();
    }
} catch(PDOException $e) {
    die("ERROR: Could not fetch claim. " . $e->getMessage());
}

// Fetch related documents
try {
    $docStmt = $pdo->prepare("SELECT * FROM documents WHERE reference_type = 'Staff' AND reference_id = :staff_id AND document_type = 'Claim'");
    $docStmt->bindParam(":staff_id", $claim['staff_id'], PDO::PARAM_INT);
    $docStmt->execute();
    $documents = $docStmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $documents = [];
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty($_POST["status"])){
        $status_err = "Please select a status.";
    } else {
        $status = $_POST["status"];
    }

    if(empty($status_err)){
        try {
            $sql = "UPDATE claims SET status = :status, processed_date = NOW(), processed_by = :processed_by WHERE claim_id = :id";

            if($stmt = $pdo->prepare($sql)){
                $stmt->bindParam(":status", $param_status, PDO::PARAM_STR);
                $stmt->bindParam(":processed_by", $param_processed_by, PDO::PARAM_INT);
                $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);

                $param_status = $status;
                $param_processed_by = $_SESSION["staff_id"];
                $param_id = $claim["claim_id"];

                if($stmt->execute()){
                    header("location: index.php?processed=1");
                    exit();
                } else {
                    $form_error = "Something went wrong. Please try again later.";
                }
            }
        } catch(PDOException $e) {
            $form_error = "Error: " . $e->getMessage();
        }
    }
}

function formatDate($date) {
    if(empty($date)) return 'N/A';
    return date("d M Y, H:i", strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Claim #<?php echo htmlspecialchars($_GET['id']); ?> - SOA Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/modern-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include_once $basePath . "includes/modern-sidebar.php"; ?>

    <div class="main-content">
        <!-- Header -->
        <header class="dashboard-header">
            <div class="header-content">
                <div class="header-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="header-title">
                        <h1>Process Claim #<?php echo str_pad($_GET['id'], 4, '0', STR_PAD_LEFT); ?></h1>
                        <p>Review and approve or reject this claim</p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="view.php?id=<?php echo $_GET['id']; ?>" class="export-btn secondary">
                        <i class="fas fa-eye"></i>
                        View Claim
                    </a>
                    <a href="index.php" class="export-btn secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to List
                    </a>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="dashboard-content">

            <?php if(!empty($form_error)): ?>
            <div class="alert alert-error" data-aos="fade-down">
                <div class="alert-content"><i class="fas fa-exclamation-circle"></i> <span><?php echo htmlspecialchars($form_error); ?></span></div>
                <button class="alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            </div>
            <?php endif; ?>

            <div class="process-grid" data-aos="fade-up">

                <!-- Claim Information Card -->
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3><i class="fas fa-file-invoice-dollar"></i> Claim Information</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="info-row">
                            <span class="info-label">Claim ID</span>
                            <span class="info-value">#<?php echo str_pad($claim['claim_id'], 4, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Staff</span>
                            <span class="info-value">
                                <div class="employee-inline">
                                    <div class="employee-avatar-sm"><i class="fas fa-user"></i></div>
                                    <?php echo htmlspecialchars($claim['staff_name']); ?>
                                </div>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Department</span>
                            <span class="info-value"><?php echo htmlspecialchars($claim['department']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Claim Month</span>
                            <span class="info-value"><?php echo htmlspecialchars($claim['claim_month']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Vehicle Type</span>
                            <span class="info-value">
                                <span class="vehicle-badge">
                                    <i class="fas fa-<?php echo $claim['vehicle_type'] == 'Car' ? 'car' : 'motorcycle'; ?>"></i>
                                    <?php echo htmlspecialchars($claim['vehicle_type']); ?>
                                </span>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Description</span>
                            <span class="info-value"><?php echo nl2br(htmlspecialchars($claim['description'])); ?></span>
                        </div>
                        <div class="info-divider"></div>
                        <div class="info-row info-row-highlight">
                            <span class="info-label">Total Amount</span>
                            <span class="info-value amount-large">RM <?php echo number_format($claim['amount'], 2); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Current Status</span>
                            <span class="info-value">
                                <span class="status-badge status-pending">Pending</span>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Submitted</span>
                            <span class="info-value"><?php echo formatDate($claim['submitted_date']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Process Form + Documents Column -->
                <div class="right-column">

                    <!-- Process Form Card -->
                    <div class="detail-card" data-aos="fade-up">
                        <div class="detail-card-header">
                            <h3><i class="fas fa-tasks"></i> Process Claim</h3>
                        </div>
                        <div class="detail-card-body">
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $claim['claim_id']; ?>" method="post">

                                <div class="form-group">
                                    <label class="form-label">Decision <span class="required">*</span></label>
                                    <select name="status" class="form-input <?php echo (!empty($status_err)) ? 'input-error' : ''; ?>">
                                        <option value="">Select Decision</option>
                                        <option value="Approved" <?php echo ($status == "Approved") ? 'selected' : ''; ?>>
                                            Approve
                                        </option>
                                        <option value="Rejected" <?php echo ($status == "Rejected") ? 'selected' : ''; ?>>
                                            Reject
                                        </option>
                                    </select>
                                    <?php if(!empty($status_err)): ?>
                                        <span class="error-message"><?php echo $status_err; ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="process-info-note">
                                    <i class="fas fa-shield-alt"></i>
                                    <p>This action will update the claim record and notify the employee of your decision.</p>
                                </div>

                                <div class="form-actions" style="margin-top:1.5rem;">
                                    <button type="submit" class="btn-primary-large">
                                        <i class="fas fa-check-circle"></i> Confirm Decision
                                    </button>
                                    <a href="index.php" class="btn-secondary-large">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Related Documents Card -->
                    <div class="detail-card" data-aos="fade-up">
                        <div class="detail-card-header">
                            <h3><i class="fas fa-folder-open"></i> Related Documents</h3>
                        </div>
                        <div class="detail-card-body" style="padding:0;">
                            <?php if(!empty($documents)): ?>
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Upload Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($documents as $document): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($document['file_name']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($document['upload_date'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="<?php echo $basePath . $document['file_path']; ?>"
                                                   class="action-btn action-btn-view" target="_blank" title="View Document">
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
                                <div class="empty-icon"><i class="fas fa-folder-open"></i></div>
                                <p>No related documents found.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>

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
        /* Export btn secondary */
        .export-btn.secondary { background: white; border-color: var(--gray-300); color: var(--gray-700); }
        .export-btn.secondary:hover { background: var(--gray-50); border-color: var(--gray-400); color: var(--gray-900); }

        /* Alert */
        .alert { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.5rem; border-radius: var(--border-radius); margin-bottom: 1.5rem; border: 1px solid; }
        .alert-error { background: rgba(239,68,68,0.05); border-color: var(--danger-color); color: var(--danger-color); }
        .alert-content { display: flex; align-items: center; gap: 0.75rem; }
        .alert-close { background: none; border: none; color: inherit; cursor: pointer; }

        /* Two-column grid */
        .process-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            align-items: start;
        }

        /* Right column stacks two cards */
        .right-column { display: flex; flex-direction: column; gap: 1.5rem; }

        /* Detail card */
        .detail-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            overflow: hidden;
        }
        .detail-card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--gray-100);
        }
        .detail-card-header h3 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
        }
        .detail-card-header h3 i { color: var(--primary-color); }
        .detail-card-body { padding: 1.25rem 1.5rem; }

        /* Info rows */
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.625rem 0;
            border-bottom: 1px solid var(--gray-100);
            gap: 1rem;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-size: 0.8125rem; color: var(--gray-500); font-weight: 500; flex-shrink: 0; min-width: 120px; }
        .info-value { font-size: 0.875rem; color: var(--gray-900); font-weight: 500; text-align: right; }
        .info-divider { border-top: 2px solid var(--gray-200); margin: 0.75rem 0; }
        .info-row-highlight { background: var(--gray-50); margin: 0 -1.5rem; padding: 0.75rem 1.5rem; }
        .amount-large { font-size: 1.125rem; font-weight: 700; color: var(--success-color); }

        /* Employee inline display */
        .employee-inline { display: flex; align-items: center; gap: 0.5rem; }
        .employee-avatar-sm {
            width: 24px; height: 24px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 0.65rem; flex-shrink: 0;
        }

        /* Vehicle badge */
        .vehicle-badge {
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: 0.2rem 0.625rem;
            background: var(--gray-100); color: var(--gray-700);
            border-radius: 9999px; font-size: 0.75rem; font-weight: 500;
        }

        /* Status badge */
        .status-pending { background: rgba(245,158,11,0.1); color: var(--warning-color); }

        /* Form elements */
        .form-input {
            width: 100%;
            padding: 0.625rem 0.875rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius-sm);
            font-size: 0.875rem;
            color: var(--gray-900);
            background: white;
            transition: var(--transition);
        }
        .form-input:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .input-error { border-color: var(--danger-color); }
        .error-message { font-size: 0.8rem; color: var(--danger-color); margin-top: 0.375rem; display: block; }
        .required { color: var(--danger-color); }

        /* Process info note */
        .process-info-note {
            display: flex; gap: 0.75rem; align-items: flex-start;
            background: rgba(59,130,246,0.05);
            border: 1px solid rgba(59,130,246,0.15);
            border-radius: var(--border-radius-sm);
            padding: 0.875rem 1rem;
            margin-top: 1rem;
            font-size: 0.8125rem; color: var(--gray-600);
        }
        .process-info-note i { color: var(--primary-color); margin-top: 0.1rem; flex-shrink: 0; }
        .process-info-note p { margin: 0; }

        /* Action buttons */
        .action-buttons { display: flex; gap: 0.5rem; }
        .action-btn {
            width: 32px; height: 32px; border: none;
            border-radius: var(--border-radius-sm);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: var(--transition); font-size: 0.875rem;
            text-decoration: none;
        }
        .action-btn-view { background: rgba(59,130,246,0.1); color: var(--primary-color); }
        .action-btn-view:hover { background: var(--primary-color); color: white; }

        /* Empty state */
        .empty-state { text-align: center; padding: 2rem; }
        .empty-icon { font-size: 2rem; color: var(--gray-300); margin-bottom: 0.75rem; }
        .empty-state p { color: var(--gray-500); font-size: 0.875rem; }

        /* Responsive */
        @media (max-width: 900px) {
            .process-grid { grid-template-columns: 1fr; }
        }
    </style>
</body>
</html>
