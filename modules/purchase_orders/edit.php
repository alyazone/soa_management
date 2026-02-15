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

// Fetch PO and verify it's a draft
try {
    $stmt = $pdo->prepare("SELECT * FROM purchase_orders WHERE po_id = :id");
    $stmt->bindParam(':id', $po_id, PDO::PARAM_INT);
    $stmt->execute();

    if($stmt->rowCount() != 1){
        header("location: index.php");
        exit();
    }

    $po = $stmt->fetch(PDO::FETCH_ASSOC);

    if($po['status'] != 'Draft'){
        header("location: index.php?error=" . urlencode("Only draft purchase orders can be edited."));
        exit();
    }
} catch(PDOException $e) {
    header("location: index.php");
    exit();
}

// Fetch existing line items
try {
    $stmt = $pdo->prepare("SELECT * FROM purchase_order_items WHERE po_id = :po_id ORDER BY item_id");
    $stmt->bindParam(':po_id', $po_id, PDO::PARAM_INT);
    $stmt->execute();
    $existing_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $existing_items = [];
}

// Get suppliers
try {
    $suppliers = $pdo->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name")->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $suppliers = [];
}

// Initialize from existing data
$supplier_id = $po['supplier_id'];
$order_date = $po['order_date'];
$expected_delivery_date = $po['expected_delivery_date'];
$notes = $po['notes'];
$po_number = $po['po_number'];
$supplier_id_err = $order_date_err = $items_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate
    if(empty(trim($_POST["supplier_id"]))){
        $supplier_id_err = "Please select a supplier.";
    } else {
        $supplier_id = intval($_POST["supplier_id"]);
    }

    if(empty(trim($_POST["order_date"]))){
        $order_date_err = "Please enter the order date.";
    } else {
        $order_date = trim($_POST["order_date"]);
    }

    $expected_delivery_date = !empty(trim($_POST["expected_delivery_date"])) ? trim($_POST["expected_delivery_date"]) : null;
    $notes = trim($_POST["notes"]);

    // Validate items
    $descriptions = isset($_POST["item_description"]) ? $_POST["item_description"] : [];
    $quantities = isset($_POST["item_quantity"]) ? $_POST["item_quantity"] : [];
    $unit_prices = isset($_POST["item_unit_price"]) ? $_POST["item_unit_price"] : [];

    $valid_items = [];
    foreach($descriptions as $i => $desc){
        $desc = trim($desc);
        $qty = floatval($quantities[$i]);
        $price = floatval($unit_prices[$i]);

        if(!empty($desc) && $qty > 0 && $price >= 0){
            $valid_items[] = [
                'description' => $desc,
                'quantity' => $qty,
                'unit_price' => $price,
                'total_price' => $qty * $price
            ];
        }
    }

    if(empty($valid_items)){
        $items_err = "Please add at least one line item.";
    }

    if(empty($supplier_id_err) && empty($order_date_err) && empty($items_err)){
        try {
            $pdo->beginTransaction();

            $subtotal = 0;
            foreach($valid_items as $item){
                $subtotal += $item['total_price'];
            }
            $tax_amount = 0;
            $total_amount = $subtotal + $tax_amount;

            // Update purchase order
            $sql = "UPDATE purchase_orders SET supplier_id = :supplier_id, order_date = :order_date, expected_delivery_date = :expected_delivery_date, subtotal = :subtotal, tax_amount = :tax_amount, total_amount = :total_amount, notes = :notes WHERE po_id = :po_id";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
            $stmt->bindParam(':order_date', $order_date);
            $stmt->bindParam(':expected_delivery_date', $expected_delivery_date);
            $stmt->bindParam(':subtotal', $subtotal);
            $stmt->bindParam(':tax_amount', $tax_amount);
            $stmt->bindParam(':total_amount', $total_amount);
            $stmt->bindParam(':notes', $notes);
            $stmt->bindParam(':po_id', $po_id, PDO::PARAM_INT);
            $stmt->execute();

            // Delete old items and re-insert
            $del_stmt = $pdo->prepare("DELETE FROM purchase_order_items WHERE po_id = :po_id");
            $del_stmt->bindParam(':po_id', $po_id, PDO::PARAM_INT);
            $del_stmt->execute();

            $item_sql = "INSERT INTO purchase_order_items (po_id, description, quantity, unit_price, total_price) VALUES (:po_id, :description, :quantity, :unit_price, :total_price)";
            $item_stmt = $pdo->prepare($item_sql);

            foreach($valid_items as $item){
                $item_stmt->bindParam(':po_id', $po_id, PDO::PARAM_INT);
                $item_stmt->bindParam(':description', $item['description']);
                $item_stmt->bindParam(':quantity', $item['quantity']);
                $item_stmt->bindParam(':unit_price', $item['unit_price']);
                $item_stmt->bindParam(':total_price', $item['total_price']);
                $item_stmt->execute();
            }

            $pdo->commit();
            header("location: view.php?id=" . $po_id . "&success=updated");
            exit();

        } catch(PDOException $e) {
            $pdo->rollBack();
            $general_err = "Error updating purchase order: " . $e->getMessage();
        }
    } else {
        // Rebuild existing_items from POST data for re-rendering
        $existing_items = $valid_items;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Purchase Order - SOA Management System</title>

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
                        <h1>Edit Purchase Order</h1>
                        <p>Update <?php echo htmlspecialchars($po_number); ?></p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="view.php?id=<?php echo $po_id; ?>" class="export-btn info"><i class="fas fa-eye"></i> View Details</a>
                    <a href="index.php" class="export-btn secondary"><i class="fas fa-arrow-left"></i> Back to List</a>
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

            <!-- Draft Notice -->
            <div class="alert alert-warning" data-aos="fade-down">
                <div class="alert-content">
                    <i class="fas fa-info-circle"></i>
                    <span>This purchase order is in <strong>Draft</strong> status. You can edit it until it is approved.</span>
                </div>
            </div>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $po_id); ?>" method="post" id="poForm">
                <div class="form-card" data-aos="fade-up">
                    <div class="form-header">
                        <div class="form-title">
                            <h3><i class="fas fa-file-alt"></i> Purchase Order Details</h3>
                            <p>Update the details for this purchase order</p>
                        </div>
                    </div>
                    <div class="form-body">
                        <div class="form-section">
                            <div class="section-header">
                                <h4><i class="fas fa-info-circle"></i> Order Information</h4>
                                <span class="required-badge">Required</span>
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-hashtag"></i> PO Number</label>
                                    <input type="text" class="form-input" value="<?php echo htmlspecialchars($po_number); ?>" readonly style="background:var(--gray-100);font-family:monospace;font-weight:600;">
                                </div>
                                <div class="form-group">
                                    <label class="form-label required"><i class="fas fa-truck"></i> Supplier</label>
                                    <select name="supplier_id" class="form-input <?php echo (!empty($supplier_id_err)) ? 'error' : ''; ?>" required>
                                        <option value="">Select Supplier</option>
                                        <?php foreach($suppliers as $sup): ?>
                                        <option value="<?php echo $sup['supplier_id']; ?>" <?php echo ($supplier_id == $sup['supplier_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($sup['supplier_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if(!empty($supplier_id_err)): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $supplier_id_err; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label required"><i class="fas fa-calendar"></i> Order Date</label>
                                    <input type="date" name="order_date" class="form-input <?php echo (!empty($order_date_err)) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($order_date); ?>" required>
                                    <?php if(!empty($order_date_err)): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $order_date_err; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-calendar-check"></i> Expected Delivery</label>
                                    <input type="date" name="expected_delivery_date" class="form-input" value="<?php echo htmlspecialchars($expected_delivery_date); ?>">
                                </div>
                                <div class="form-group full-width">
                                    <label class="form-label"><i class="fas fa-sticky-note"></i> Notes</label>
                                    <textarea name="notes" class="form-textarea" rows="3"><?php echo htmlspecialchars($notes); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Line Items -->
                <div class="form-card" data-aos="fade-up" data-aos-delay="100" style="margin-top:2rem;">
                    <div class="form-header">
                        <div class="form-title">
                            <h3><i class="fas fa-list"></i> Line Items</h3>
                            <p>Update items in this purchase order</p>
                        </div>
                    </div>
                    <div class="form-body">
                        <?php if(!empty($items_err)): ?>
                        <div class="alert alert-error" style="margin-bottom:1.5rem;">
                            <div class="alert-content"><i class="fas fa-exclamation-circle"></i><span><?php echo $items_err; ?></span></div>
                        </div>
                        <?php endif; ?>

                        <div id="items-container">
                            <?php foreach($existing_items as $idx => $item): ?>
                            <div class="line-item" data-index="<?php echo $idx; ?>">
                                <div class="item-row">
                                    <div class="item-field item-desc">
                                        <label>Description</label>
                                        <input type="text" name="item_description[]" class="form-input" value="<?php echo htmlspecialchars($item['description']); ?>" required>
                                    </div>
                                    <div class="item-field item-qty">
                                        <label>Quantity</label>
                                        <input type="number" name="item_quantity[]" class="form-input item-quantity" value="<?php echo htmlspecialchars($item['quantity']); ?>" min="0.01" step="0.01" required>
                                    </div>
                                    <div class="item-field item-price">
                                        <label>Unit Price (RM)</label>
                                        <input type="number" name="item_unit_price[]" class="form-input item-unit-price" value="<?php echo htmlspecialchars($item['unit_price']); ?>" min="0" step="0.01" required>
                                    </div>
                                    <div class="item-field item-total">
                                        <label>Total (RM)</label>
                                        <input type="text" class="form-input item-total-display" value="<?php echo number_format($item['total_price'], 2); ?>" readonly style="background:var(--gray-100);font-weight:600;">
                                    </div>
                                    <div class="item-field item-action">
                                        <label>&nbsp;</label>
                                        <button type="button" class="btn-remove-item" onclick="removeItem(this)" title="Remove Item" <?php echo count($existing_items) <= 1 ? 'style="visibility:hidden;"' : ''; ?>>
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="items-footer">
                            <button type="button" class="btn-add-item" onclick="addItem()">
                                <i class="fas fa-plus"></i> Add Line Item
                            </button>
                            <div class="order-totals">
                                <div class="total-row">
                                    <span>Subtotal:</span>
                                    <span id="subtotal-display">RM 0.00</span>
                                </div>
                                <div class="total-row grand-total">
                                    <span>Total:</span>
                                    <span id="grand-total-display">RM 0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions-bar" data-aos="fade-up" data-aos-delay="200">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Purchase Order</button>
                    <a href="view.php?id=<?php echo $po_id; ?>" class="btn btn-info"><i class="fas fa-eye"></i> View Details</a>
                    <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="../../assets/js/modern-dashboard.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({ duration: 800, easing: 'ease-in-out', once: true });
            initializeDashboard();
            updateTotals();
        });

        let itemIndex = <?php echo count($existing_items); ?>;

        function addItem() {
            const container = document.getElementById('items-container');
            const div = document.createElement('div');
            div.className = 'line-item';
            div.setAttribute('data-index', itemIndex);
            div.innerHTML = `
                <div class="item-row">
                    <div class="item-field item-desc">
                        <label>Description</label>
                        <input type="text" name="item_description[]" class="form-input" placeholder="Item description" required>
                    </div>
                    <div class="item-field item-qty">
                        <label>Quantity</label>
                        <input type="number" name="item_quantity[]" class="form-input item-quantity" value="1" min="0.01" step="0.01" required>
                    </div>
                    <div class="item-field item-price">
                        <label>Unit Price (RM)</label>
                        <input type="number" name="item_unit_price[]" class="form-input item-unit-price" value="0.00" min="0" step="0.01" required>
                    </div>
                    <div class="item-field item-total">
                        <label>Total (RM)</label>
                        <input type="text" class="form-input item-total-display" value="0.00" readonly style="background:var(--gray-100);font-weight:600;">
                    </div>
                    <div class="item-field item-action">
                        <label>&nbsp;</label>
                        <button type="button" class="btn-remove-item" onclick="removeItem(this)" title="Remove Item">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(div);
            itemIndex++;
            updateRemoveButtons();
            bindItemEvents(div);
        }

        function removeItem(btn) {
            const items = document.querySelectorAll('.line-item');
            if(items.length > 1){
                btn.closest('.line-item').remove();
                updateRemoveButtons();
                updateTotals();
            }
        }

        function updateRemoveButtons() {
            const items = document.querySelectorAll('.line-item');
            items.forEach(item => {
                const btn = item.querySelector('.btn-remove-item');
                btn.style.visibility = items.length > 1 ? 'visible' : 'hidden';
            });
        }

        function bindItemEvents(container) {
            container.querySelectorAll('.item-quantity').forEach(input => input.addEventListener('input', updateTotals));
            container.querySelectorAll('.item-unit-price').forEach(input => input.addEventListener('input', updateTotals));
        }

        function updateTotals() {
            let subtotal = 0;
            document.querySelectorAll('.line-item').forEach(item => {
                const qty = parseFloat(item.querySelector('.item-quantity').value) || 0;
                const price = parseFloat(item.querySelector('.item-unit-price').value) || 0;
                const total = qty * price;
                item.querySelector('.item-total-display').value = total.toFixed(2);
                subtotal += total;
            });
            document.getElementById('subtotal-display').textContent = 'RM ' + subtotal.toFixed(2);
            document.getElementById('grand-total-display').textContent = 'RM ' + subtotal.toFixed(2);
        }

        // Bind events to all existing item rows
        document.querySelectorAll('.line-item').forEach(item => bindItemEvents(item));
    </script>
    <style>
        .form-card{background:white;border-radius:var(--border-radius);box-shadow:var(--shadow);border:1px solid var(--gray-200);overflow:hidden}
        .form-header{padding:1.5rem;border-bottom:1px solid var(--gray-200);background:var(--gray-50)}
        .form-title h3{display:flex;align-items:center;gap:.5rem;color:var(--gray-900);font-size:1.25rem;font-weight:600;margin:0 0 .5rem}
        .form-title p{color:var(--gray-600);margin:0}
        .form-body{padding:2rem}
        .form-section{margin-bottom:0}
        .section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;padding-bottom:.75rem;border-bottom:1px solid var(--gray-200)}
        .section-header h4{display:flex;align-items:center;gap:.5rem;color:var(--gray-900);font-size:1.125rem;font-weight:600;margin:0}
        .required-badge{background:var(--danger-color);color:white;padding:.25rem .5rem;border-radius:9999px;font-size:.75rem;font-weight:500}
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}
        .form-group{display:flex;flex-direction:column;gap:.5rem}
        .form-group.full-width{grid-column:1 / -1}
        .form-label{display:flex;align-items:center;gap:.5rem;font-size:.875rem;font-weight:500;color:var(--gray-700)}
        .form-label.required::after{content:'*';color:var(--danger-color);margin-left:.25rem}
        .form-input,.form-textarea{padding:.75rem;border:1px solid var(--gray-300);border-radius:var(--border-radius-sm);font-size:.875rem;transition:var(--transition);background:white}
        .form-input:focus,.form-textarea:focus{outline:0;border-color:var(--primary-color);box-shadow:0 0 0 3px rgba(59,130,246,.1)}
        .form-input.error{border-color:var(--danger-color);box-shadow:0 0 0 3px rgba(239,68,68,.1)}
        .form-textarea{resize:vertical;min-height:80px}
        .error-message{display:flex;align-items:center;gap:.5rem;color:var(--danger-color);font-size:.75rem;font-weight:500;margin-top:.25rem}
        .line-item{border:1px solid var(--gray-200);border-radius:var(--border-radius-sm);padding:1rem;margin-bottom:1rem;background:var(--gray-50);transition:var(--transition)}
        .line-item:hover{border-color:var(--primary-color);background:rgba(59,130,246,.02)}
        .item-row{display:grid;grid-template-columns:3fr 1fr 1.5fr 1.5fr auto;gap:1rem;align-items:end}
        .item-field label{display:block;font-size:.75rem;font-weight:500;color:var(--gray-600);margin-bottom:.375rem}
        .btn-remove-item{width:36px;height:36px;border:none;background:rgba(239,68,68,.1);color:var(--danger-color);border-radius:var(--border-radius-sm);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:var(--transition)}
        .btn-remove-item:hover{background:var(--danger-color);color:white}
        .items-footer{display:flex;justify-content:space-between;align-items:flex-start;margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid var(--gray-200)}
        .btn-add-item{display:inline-flex;align-items:center;gap:.5rem;padding:.75rem 1.25rem;background:rgba(59,130,246,.1);color:var(--primary-color);border:1px dashed var(--primary-color);border-radius:var(--border-radius-sm);cursor:pointer;font-size:.875rem;font-weight:500;transition:var(--transition)}
        .btn-add-item:hover{background:var(--primary-color);color:white;border-style:solid}
        .order-totals{text-align:right;min-width:250px}
        .total-row{display:flex;justify-content:space-between;padding:.5rem 0;font-size:.875rem;color:var(--gray-700)}
        .total-row.grand-total{border-top:2px solid var(--gray-300);margin-top:.5rem;padding-top:.75rem;font-size:1.125rem;font-weight:700;color:var(--gray-900)}
        .form-actions-bar{display:flex;gap:1rem;margin-top:2rem;padding:1.5rem;background:white;border-radius:var(--border-radius);box-shadow:var(--shadow);border:1px solid var(--gray-200)}
        .btn{display:inline-flex;align-items:center;gap:.5rem;padding:.75rem 1.5rem;border:none;border-radius:var(--border-radius-sm);font-size:.875rem;font-weight:500;text-decoration:none;cursor:pointer;transition:var(--transition)}
        .btn-primary{background:var(--primary-color);color:white}
        .btn-primary:hover{background:var(--primary-dark);color:white;text-decoration:none}
        .btn-info{background:var(--info-color);color:white}
        .btn-info:hover{opacity:.9;color:white;text-decoration:none}
        .btn-secondary{background:var(--gray-100);color:var(--gray-700);border:1px solid var(--gray-300)}
        .btn-secondary:hover{background:var(--gray-200);color:var(--gray-900);text-decoration:none}
        .alert{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.5rem;border-radius:var(--border-radius);margin-bottom:1.5rem;border:1px solid}
        .alert-error{background:rgba(239,68,68,.1);border-color:var(--danger-color);color:var(--danger-color)}
        .alert-warning{background:rgba(245,158,11,.1);border-color:var(--warning-color);color:var(--warning-color)}
        .alert-content{display:flex;align-items:center;gap:.75rem}
        .alert-close{background:0 0;border:none;color:inherit;cursor:pointer;padding:.25rem;border-radius:var(--border-radius-sm)}
        .export-btn.info{background:var(--info-color);color:white}
        .export-btn.info:hover{opacity:.9}
        @media (max-width:768px){.form-grid{grid-template-columns:1fr}.item-row{grid-template-columns:1fr;gap:.75rem}.items-footer{flex-direction:column;gap:1rem}.order-totals{min-width:auto;width:100%}.form-actions-bar{flex-direction:column}}
    </style>
</body>
</html>
<?php ob_end_flush(); ?>
