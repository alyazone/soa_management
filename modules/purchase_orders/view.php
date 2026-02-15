<?php
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

// Fetch purchase order
try {
    $sql = "SELECT po.*, s.supplier_name, s.address as supplier_address, s.pic_name, s.pic_contact, s.pic_email,
                   st.full_name as created_by_name, appr.full_name as approved_by_name
            FROM purchase_orders po
            JOIN suppliers s ON po.supplier_id = s.supplier_id
            JOIN staff st ON po.created_by = st.staff_id
            LEFT JOIN staff appr ON po.approved_by = appr.staff_id
            WHERE po.po_id = :po_id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':po_id', $po_id, PDO::PARAM_INT);
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

// Fetch line items
try {
    $stmt = $pdo->prepare("SELECT * FROM purchase_order_items WHERE po_id = :po_id ORDER BY item_id");
    $stmt->bindParam(':po_id', $po_id, PDO::PARAM_INT);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $items = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PO <?php echo htmlspecialchars($po['po_number']); ?> - SOA Management System</title>

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
                        <h1>Purchase Order Details</h1>
                        <p><?php echo htmlspecialchars($po['po_number']); ?></p>
                    </div>
                </div>
                <div class="header-right">
                    <?php if($po['status'] == 'Draft'): ?>
                    <a href="edit.php?id=<?php echo $po_id; ?>" class="export-btn warning"><i class="fas fa-edit"></i> Edit</a>
                    <a href="approve.php?id=<?php echo $po_id; ?>" class="export-btn success" onclick="return confirm('Approve this purchase order?')"><i class="fas fa-check"></i> Approve</a>
                    <?php endif; ?>
                    <?php if($po['status'] == 'Approved' && empty($po['supplier_invoice_number'])): ?>
                    <a href="link_invoice.php?id=<?php echo $po_id; ?>" class="export-btn info"><i class="fas fa-link"></i> Link Invoice</a>
                    <?php endif; ?>
                    <a href="generate_pdf.php?id=<?php echo $po_id; ?>" class="export-btn" target="_blank"><i class="fas fa-file-pdf"></i> PDF</a>
                    <a href="index.php" class="export-btn secondary"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <?php if(isset($_GET["success"])): ?>
                <div class="alert alert-success" data-aos="fade-down">
                    <div class="alert-content">
                        <i class="fas fa-check-circle"></i>
                        <span>
                            <?php
                            if($_GET["success"] == "added") echo "Purchase order created successfully.";
                            elseif($_GET["success"] == "updated") echo "Purchase order updated successfully.";
                            elseif($_GET["success"] == "approved") echo "Purchase order approved successfully.";
                            elseif($_GET["success"] == "linked") echo "Supplier invoice linked successfully.";
                            ?>
                        </span>
                    </div>
                    <button class="alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>

            <!-- PO Header -->
            <div class="profile-header" data-aos="fade-down">
                <div class="profile-avatar" style="background-color: <?php
                    echo $po['status'] == 'Draft' ? '#f59e0b' :
                        ($po['status'] == 'Approved' ? '#10b981' :
                        ($po['status'] == 'Received' ? '#3b82f6' : '#ef4444'));
                ?>;">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($po['po_number']); ?></h2>
                    <p class="profile-subtitle"><?php echo htmlspecialchars($po['supplier_name']); ?></p>
                    <div class="profile-meta">
                        <span class="meta-item"><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($po['order_date'])); ?></span>
                        <span class="meta-item">
                            <span class="status-badge status-<?php echo strtolower($po['status']); ?>"><?php echo htmlspecialchars($po['status']); ?></span>
                        </span>
                        <span class="meta-item"><i class="fas fa-dollar-sign"></i> RM <?php echo number_format($po['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid" data-aos="fade-up">
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="fas fa-list"></i></div>
                    <div class="stat-content">
                        <h3><?php echo count($items); ?></h3>
                        <p>Line Items</p>
                    </div>
                </div>
                <div class="stat-card success">
                    <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="stat-content">
                        <h3>RM <?php echo number_format($po['subtotal'], 2); ?></h3>
                        <p>Subtotal</p>
                    </div>
                </div>
                <div class="stat-card info">
                    <div class="stat-icon"><i class="fas fa-coins"></i></div>
                    <div class="stat-content">
                        <h3>RM <?php echo number_format($po['total_amount'], 2); ?></h3>
                        <p>Total Amount</p>
                    </div>
                </div>
            </div>

            <div class="content-grid" data-aos="fade-up" data-aos-delay="100">
                <!-- Order Info -->
                <div class="info-card">
                    <div class="info-header"><h3><i class="fas fa-info-circle"></i> Order Information</h3></div>
                    <div class="info-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <label>PO Number</label>
                                <value style="font-family:monospace;font-weight:600;color:var(--primary-color)"><?php echo htmlspecialchars($po['po_number']); ?></value>
                            </div>
                            <div class="info-item">
                                <label>Status</label>
                                <value><span class="status-badge status-<?php echo strtolower($po['status']); ?>"><?php echo htmlspecialchars($po['status']); ?></span></value>
                            </div>
                            <div class="info-item">
                                <label>Order Date</label>
                                <value><?php echo date('M d, Y', strtotime($po['order_date'])); ?></value>
                            </div>
                            <div class="info-item">
                                <label>Expected Delivery</label>
                                <value><?php echo $po['expected_delivery_date'] ? date('M d, Y', strtotime($po['expected_delivery_date'])) : '-'; ?></value>
                            </div>
                            <div class="info-item">
                                <label>Created By</label>
                                <value><?php echo htmlspecialchars($po['created_by_name']); ?></value>
                            </div>
                            <div class="info-item">
                                <label>Created At</label>
                                <value><?php echo date('M d, Y H:i', strtotime($po['created_at'])); ?></value>
                            </div>
                            <?php if($po['approved_by']): ?>
                            <div class="info-item">
                                <label>Approved By</label>
                                <value><?php echo htmlspecialchars($po['approved_by_name']); ?></value>
                            </div>
                            <div class="info-item">
                                <label>Approved Date</label>
                                <value><?php echo date('M d, Y H:i', strtotime($po['approved_date'])); ?></value>
                            </div>
                            <?php endif; ?>
                            <?php if(!empty($po['supplier_invoice_number'])): ?>
                            <div class="info-item">
                                <label>Supplier Invoice #</label>
                                <value><span class="invoice-linked"><i class="fas fa-link"></i> <?php echo htmlspecialchars($po['supplier_invoice_number']); ?></span></value>
                            </div>
                            <div class="info-item">
                                <label>Invoice Date</label>
                                <value><?php echo $po['supplier_invoice_date'] ? date('M d, Y', strtotime($po['supplier_invoice_date'])) : '-'; ?></value>
                            </div>
                            <?php endif; ?>
                            <?php if(!empty($po['notes'])): ?>
                            <div class="info-item full-width">
                                <label>Notes</label>
                                <value><?php echo nl2br(htmlspecialchars($po['notes'])); ?></value>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Supplier Info -->
                <div class="info-card">
                    <div class="info-header"><h3><i class="fas fa-truck"></i> Supplier Information</h3></div>
                    <div class="info-body">
                        <div class="info-grid">
                            <div class="info-item full-width">
                                <label>Supplier Name</label>
                                <value><?php echo htmlspecialchars($po['supplier_name']); ?></value>
                            </div>
                            <div class="info-item full-width">
                                <label>Address</label>
                                <value><?php echo nl2br(htmlspecialchars($po['supplier_address'])); ?></value>
                            </div>
                            <div class="info-item">
                                <label>Contact Person</label>
                                <value><?php echo htmlspecialchars($po['pic_name']); ?></value>
                            </div>
                            <div class="info-item">
                                <label>Contact Number</label>
                                <value><a href="tel:<?php echo htmlspecialchars($po['pic_contact']); ?>" class="contact-link"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($po['pic_contact']); ?></a></value>
                            </div>
                            <div class="info-item full-width">
                                <label>Email</label>
                                <value><a href="mailto:<?php echo htmlspecialchars($po['pic_email']); ?>" class="contact-link"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($po['pic_email']); ?></a></value>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Line Items Table -->
            <div class="table-card" data-aos="fade-up" data-aos-delay="200">
                <div class="table-header">
                    <div class="table-title">
                        <h3><i class="fas fa-list"></i> Line Items</h3>
                        <p><?php echo count($items); ?> items</p>
                    </div>
                </div>
                <div class="table-container">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Description</th>
                                <th style="text-align:right;">Quantity</th>
                                <th style="text-align:right;">Unit Price</th>
                                <th style="text-align:right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $row_num = 1; foreach($items as $item): ?>
                            <tr>
                                <td><?php echo $row_num++; ?></td>
                                <td><?php echo htmlspecialchars($item['description']); ?></td>
                                <td style="text-align:right;"><?php echo number_format($item['quantity'], 2); ?></td>
                                <td style="text-align:right;">RM <?php echo number_format($item['unit_price'], 2); ?></td>
                                <td style="text-align:right;font-weight:600;">RM <?php echo number_format($item['total_price'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="subtotal-row">
                                <td colspan="4" style="text-align:right;font-weight:600;">Subtotal:</td>
                                <td style="text-align:right;font-weight:600;">RM <?php echo number_format($po['subtotal'], 2); ?></td>
                            </tr>
                            <?php if($po['tax_amount'] > 0): ?>
                            <tr>
                                <td colspan="4" style="text-align:right;font-weight:500;">Tax:</td>
                                <td style="text-align:right;">RM <?php echo number_format($po['tax_amount'], 2); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="grand-total-row">
                                <td colspan="4" style="text-align:right;font-weight:700;font-size:1.125rem;">Total:</td>
                                <td style="text-align:right;font-weight:700;font-size:1.125rem;color:var(--primary-color);">RM <?php echo number_format($po['total_amount'], 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
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
        .stats-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;margin-bottom:2rem}
        .stat-card{background:white;border-radius:var(--border-radius);box-shadow:var(--shadow);border:1px solid var(--gray-200);padding:1.5rem;display:flex;align-items:center;gap:1rem}
        .stat-icon{width:50px;height:50px;border-radius:var(--border-radius-sm);display:flex;align-items:center;justify-content:center;font-size:1.25rem}
        .stat-card.primary .stat-icon{background:rgba(59,130,246,.1);color:var(--primary-color)}
        .stat-card.success .stat-icon{background:rgba(16,185,129,.1);color:var(--success-color)}
        .stat-card.info .stat-icon{background:rgba(6,182,212,.1);color:var(--info-color)}
        .stat-content h3{font-size:1.5rem;font-weight:700;color:var(--gray-900);line-height:1}
        .stat-content p{font-size:.875rem;color:var(--gray-500);margin-top:.25rem}
        .content-grid{display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-bottom:2rem}
        .info-card{background:white;border-radius:var(--border-radius);box-shadow:var(--shadow);border:1px solid var(--gray-200)}
        .info-header{padding:1.5rem;border-bottom:1px solid var(--gray-200)}
        .info-header h3{display:flex;align-items:center;gap:.5rem;color:var(--gray-900);font-size:1.125rem;font-weight:600;margin:0}
        .info-body{padding:1.5rem}
        .info-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}
        .info-item{display:flex;flex-direction:column;gap:.5rem}
        .info-item.full-width{grid-column:1 / -1}
        .info-item label{font-size:.875rem;font-weight:500;color:var(--gray-600)}
        .info-item value{font-size:.875rem;color:var(--gray-900);font-weight:500}
        .contact-link{display:inline-flex;align-items:center;gap:.5rem;color:var(--primary-color);text-decoration:none}
        .contact-link:hover{color:var(--primary-dark)}
        .status-badge{display:inline-flex;align-items:center;padding:.375rem .75rem;border-radius:9999px;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em}
        .status-draft{background:rgba(245,158,11,.1);color:var(--warning-color)}
        .status-approved{background:rgba(16,185,129,.1);color:var(--success-color)}
        .status-received{background:rgba(59,130,246,.1);color:var(--primary-color)}
        .status-cancelled{background:rgba(239,68,68,.1);color:var(--danger-color)}
        .invoice-linked{display:inline-flex;align-items:center;gap:.375rem;color:var(--primary-color);font-weight:500}
        .subtotal-row td{border-top:2px solid var(--gray-200)!important}
        .grand-total-row td{border-top:2px solid var(--gray-300)!important}
        .alert{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.5rem;border-radius:var(--border-radius);margin-bottom:1.5rem;border:1px solid}
        .alert-success{background:rgba(16,185,129,.1);border-color:var(--success-color);color:var(--success-color)}
        .alert-content{display:flex;align-items:center;gap:.75rem}
        .alert-close{background:0 0;border:none;color:inherit;cursor:pointer;padding:.25rem;border-radius:var(--border-radius-sm)}
        .export-btn.success{background:var(--success-color);color:white}
        .export-btn.success:hover{opacity:.9}
        .export-btn.info{background:var(--info-color);color:white}
        .export-btn.info:hover{opacity:.9}
        @media (max-width:768px){.profile-header{flex-direction:column;text-align:center}.content-grid{grid-template-columns:1fr}.stats-grid{grid-template-columns:1fr}.info-grid{grid-template-columns:1fr}.profile-meta{justify-content:center;flex-wrap:wrap}}
    </style>
</body>
</html>
