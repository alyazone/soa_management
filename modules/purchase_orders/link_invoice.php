<?php
ob_start();
$basePath = '../../';
require_once $basePath . "config/database.php";

session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}

if($_SESSION['position'] != 'Admin' && $_SESSION['position'] != 'Manager'){
    header("location: " . $basePath . "dashboard.php");
    exit;
}

if(empty($_GET["id"])){
    header("location: index.php");
    exit();
}

$po_id = intval($_GET["id"]);

// Fetch PO
try {
    $stmt = $pdo->prepare("SELECT po.*, s.supplier_name FROM purchase_orders po JOIN suppliers s ON po.supplier_id = s.supplier_id WHERE po.po_id = :id");
    $stmt->bindParam(':id', $po_id, PDO::PARAM_INT);
    $stmt->execute();

    if($stmt->rowCount() != 1){
        header("location: index.php");
        exit();
    }

    $po = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    header("location: index.php");
    exit();
}

$supplier_invoice_number = $po['supplier_invoice_number'] ?: "";
$supplier_invoice_date = $po['supplier_invoice_date'] ?: "";
$invoice_number_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(trim($_POST["supplier_invoice_number"]))){
        $invoice_number_err = "Please enter the supplier invoice number.";
    } else {
        $supplier_invoice_number = trim($_POST["supplier_invoice_number"]);
    }

    $supplier_invoice_date = !empty(trim($_POST["supplier_invoice_date"])) ? trim($_POST["supplier_invoice_date"]) : null;

    if(empty($invoice_number_err)){
        try {
            $sql = "UPDATE purchase_orders SET supplier_invoice_number = :invoice_number, supplier_invoice_date = :invoice_date WHERE po_id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':invoice_number', $supplier_invoice_number);
            $stmt->bindParam(':invoice_date', $supplier_invoice_date);
            $stmt->bindParam(':id', $po_id, PDO::PARAM_INT);

            if($stmt->execute()){
                header("location: view.php?id=" . $po_id . "&success=linked");
                exit();
            } else {
                $general_err = "Failed to link supplier invoice.";
            }
        } catch(PDOException $e) {
            $general_err = "Error linking invoice: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Supplier Invoice - SOA Management System</title>

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
                        <h1>Link Supplier Invoice</h1>
                        <p>Link a supplier invoice to <?php echo htmlspecialchars($po['po_number']); ?></p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="view.php?id=<?php echo $po_id; ?>" class="export-btn secondary"><i class="fas fa-arrow-left"></i> Back to PO</a>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <?php if(isset($general_err)): ?>
                <div class="alert alert-error" data-aos="fade-down">
                    <div class="alert-content"><i class="fas fa-exclamation-circle"></i><span><?php echo htmlspecialchars($general_err); ?></span></div>
                    <button class="alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>

            <!-- PO Summary -->
            <div class="profile-header" data-aos="fade-down">
                <div class="profile-avatar" style="background-color: #3b82f6;"><i class="fas fa-link"></i></div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($po['po_number']); ?></h2>
                    <p class="profile-subtitle"><?php echo htmlspecialchars($po['supplier_name']); ?></p>
                    <div class="profile-meta">
                        <span class="meta-item"><i class="fas fa-dollar-sign"></i> RM <?php echo number_format($po['total_amount'], 2); ?></span>
                        <span class="meta-item">
                            <span class="status-badge status-<?php echo strtolower($po['status']); ?>"><?php echo htmlspecialchars($po['status']); ?></span>
                        </span>
                    </div>
                </div>
            </div>

            <div class="form-card" data-aos="fade-up">
                <div class="form-header">
                    <div class="form-title">
                        <h3><i class="fas fa-file-invoice"></i> Supplier Invoice Details</h3>
                        <p>Enter the supplier invoice information to link with this PO</p>
                    </div>
                </div>
                <div class="form-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $po_id); ?>" method="post" class="modern-form">
                        <div class="form-section">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label required"><i class="fas fa-hashtag"></i> Supplier Invoice Number</label>
                                    <input type="text" name="supplier_invoice_number" class="form-input <?php echo (!empty($invoice_number_err)) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($supplier_invoice_number); ?>" placeholder="e.g., INV-2026-001" required>
                                    <?php if(!empty($invoice_number_err)): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $invoice_number_err; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-calendar"></i> Invoice Date</label>
                                    <input type="date" name="supplier_invoice_date" class="form-input" value="<?php echo htmlspecialchars($supplier_invoice_date); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-link"></i> Link Invoice</button>
                            <a href="view.php?id=<?php echo $po_id; ?>" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                        </div>
                    </form>
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
        .profile-header{background:white;border-radius:var(--border-radius);box-shadow:var(--shadow);border:1px solid var(--gray-200);padding:2rem;margin-bottom:2rem;display:flex;align-items:center;gap:1.5rem}
        .profile-avatar{width:80px;height:80px;border-radius:var(--border-radius);display:flex;align-items:center;justify-content:center;color:white;font-size:2rem}
        .profile-info h2{color:var(--gray-900);margin-bottom:.5rem;font-size:1.5rem;font-weight:600}
        .profile-subtitle{color:var(--gray-600);margin-bottom:1rem}
        .profile-meta{display:flex;gap:1.5rem;align-items:center}
        .meta-item{display:flex;align-items:center;gap:.5rem;color:var(--gray-600);font-size:.875rem}
        .meta-item i{color:var(--gray-400)}
        .status-badge{display:inline-flex;align-items:center;padding:.375rem .75rem;border-radius:9999px;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em}
        .status-draft{background:rgba(245,158,11,.1);color:var(--warning-color)}
        .status-approved{background:rgba(16,185,129,.1);color:var(--success-color)}
        .status-received{background:rgba(59,130,246,.1);color:var(--primary-color)}
        .status-cancelled{background:rgba(239,68,68,.1);color:var(--danger-color)}
        .form-card{background:white;border-radius:var(--border-radius);box-shadow:var(--shadow);border:1px solid var(--gray-200);overflow:hidden}
        .form-header{padding:1.5rem;border-bottom:1px solid var(--gray-200);background:var(--gray-50)}
        .form-title h3{display:flex;align-items:center;gap:.5rem;color:var(--gray-900);font-size:1.25rem;font-weight:600;margin:0 0 .5rem}
        .form-title p{color:var(--gray-600);margin:0}
        .form-body{padding:2rem}
        .form-section{margin-bottom:0}
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}
        .form-group{display:flex;flex-direction:column;gap:.5rem}
        .form-label{display:flex;align-items:center;gap:.5rem;font-size:.875rem;font-weight:500;color:var(--gray-700)}
        .form-label.required::after{content:'*';color:var(--danger-color);margin-left:.25rem}
        .form-input{padding:.75rem;border:1px solid var(--gray-300);border-radius:var(--border-radius-sm);font-size:.875rem;transition:var(--transition);background:white}
        .form-input:focus{outline:0;border-color:var(--primary-color);box-shadow:0 0 0 3px rgba(59,130,246,.1)}
        .form-input.error{border-color:var(--danger-color);box-shadow:0 0 0 3px rgba(239,68,68,.1)}
        .error-message{display:flex;align-items:center;gap:.5rem;color:var(--danger-color);font-size:.75rem;font-weight:500;margin-top:.25rem}
        .form-actions{display:flex;gap:1rem;padding-top:2rem;border-top:1px solid var(--gray-200);margin-top:2rem}
        .btn{display:inline-flex;align-items:center;gap:.5rem;padding:.75rem 1.5rem;border:none;border-radius:var(--border-radius-sm);font-size:.875rem;font-weight:500;text-decoration:none;cursor:pointer;transition:var(--transition)}
        .btn-primary{background:var(--primary-color);color:white}
        .btn-primary:hover{background:var(--primary-dark);color:white;text-decoration:none}
        .btn-secondary{background:var(--gray-100);color:var(--gray-700);border:1px solid var(--gray-300)}
        .btn-secondary:hover{background:var(--gray-200);color:var(--gray-900);text-decoration:none}
        .alert{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.5rem;border-radius:var(--border-radius);margin-bottom:1.5rem;border:1px solid}
        .alert-error{background:rgba(239,68,68,.1);border-color:var(--danger-color);color:var(--danger-color)}
        .alert-content{display:flex;align-items:center;gap:.75rem}
        .alert-close{background:0 0;border:none;color:inherit;cursor:pointer;padding:.25rem;border-radius:var(--border-radius-sm)}
        @media (max-width:768px){.form-grid{grid-template-columns:1fr}.profile-header{flex-direction:column;text-align:center}.form-actions{flex-direction:column}}
    </style>
</body>
</html>
<?php ob_end_flush(); ?>
