<?php
ob_start();
$basePath = '../../';
require_once $basePath . "config/database.php";

session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['position'] != 'Admin'){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}

$experience_id = $_GET['id'] ?? null;
if(!$experience_id) { header("location: index.php"); exit; }

$errors = [];

try {
    $stmt = $pdo->prepare("SELECT * FROM company_experiences WHERE experience_id = ?");
    $stmt->execute([$experience_id]);
    $exp = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$exp) { header("location: index.php"); exit; }

    $categories = $pdo->query("SELECT category_id, category_name FROM experience_categories ORDER BY category_name")->fetchAll();
} catch(PDOException $e) {
    die("ERROR: " . $e->getMessage());
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $category_id = $_POST['category_id'];
    $agency_name = trim($_POST['agency_name']);
    $contract_name = trim($_POST['contract_name']);
    $contract_year = $_POST['contract_year'];
    $amount = filter_var($_POST['amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    if(empty($category_id)) $errors['category_id'] = "Please select a category.";
    if(empty($agency_name)) $errors['agency_name'] = "Agency name is required.";
    if(empty($contract_name)) $errors['contract_name'] = "Contract name is required.";
    if(empty($contract_year)) $errors['contract_year'] = "Contract year is required.";
    if(empty($amount) || !is_numeric($amount)) $errors['amount'] = "A valid amount is required.";

    if(empty($errors)){
        $sql = "UPDATE company_experiences SET category_id = ?, agency_name = ?, contract_name = ?, contract_year = ?, amount = ? WHERE experience_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$category_id, $agency_name, $contract_name, $contract_year, $amount, $experience_id]);
        header("location: index.php?success=updated");
        exit();
    } else {
        $exp = array_merge($exp, $_POST);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Company Experience - SOA Management System</title>
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
                        <h1>Edit Company Experience</h1>
                        <p>Update experience record</p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="index.php" class="export-btn secondary"><i class="fas fa-arrow-left"></i> Back to List</a>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="form-card" data-aos="fade-up">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $experience_id); ?>" method="post" class="modern-form">
                    <div class="form-body">
                        <div class="form-section">
                            <div class="section-header"><h4><i class="fas fa-tag"></i> Category</h4></div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label required"><i class="fas fa-tag"></i> Experience Category</label>
                                    <select name="category_id" class="form-input <?php echo isset($errors['category_id']) ? 'error' : ''; ?>">
                                        <option value="">Select a category...</option>
                                        <?php foreach($categories as $cat): ?>
                                            <option value="<?php echo $cat['category_id']; ?>" <?php echo ($cat['category_id'] == $exp['category_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['category_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if(isset($errors['category_id'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['category_id']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label required"><i class="fas fa-calendar"></i> Contract Year</label>
                                    <input type="number" name="contract_year" class="form-input <?php echo isset($errors['contract_year']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($exp['contract_year']); ?>" min="2000" max="2099">
                                    <?php if(isset($errors['contract_year'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['contract_year']; ?></span><?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="section-header"><h4><i class="fas fa-building"></i> Contract Details</h4></div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label required"><i class="fas fa-building"></i> Agency Name</label>
                                    <input type="text" name="agency_name" class="form-input <?php echo isset($errors['agency_name']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($exp['agency_name']); ?>">
                                    <?php if(isset($errors['agency_name'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['agency_name']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label required"><i class="fas fa-dollar-sign"></i> Amount (RM)</label>
                                    <input type="number" step="0.01" name="amount" class="form-input <?php echo isset($errors['amount']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($exp['amount']); ?>">
                                    <?php if(isset($errors['amount'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['amount']; ?></span><?php endif; ?>
                                </div>
                            </div>
                            <div class="form-group full-width" style="margin-top:1rem;">
                                <label class="form-label required"><i class="fas fa-file-contract"></i> Contract Name</label>
                                <textarea name="contract_name" class="form-textarea <?php echo isset($errors['contract_name']) ? 'error' : ''; ?>" rows="3"><?php echo htmlspecialchars($exp['contract_name']); ?></textarea>
                                <?php if(isset($errors['contract_name'])): ?><span class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['contract_name']; ?></span><?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Experience</button>
                        <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                    </div>
                </form>
            </div>
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
        .form-card{background:white;border-radius:var(--border-radius);box-shadow:var(--shadow);border:1px solid var(--gray-200);overflow:hidden}.form-body{padding:2rem}.form-actions{padding:1.5rem 2rem;border-top:1px solid var(--gray-200);background:var(--gray-50);display:flex;gap:1rem}.form-section{margin-bottom:2.5rem}.form-section:last-child{margin-bottom:0}.section-header{margin-bottom:1.5rem;padding-bottom:.75rem;border-bottom:1px solid var(--gray-200)}.section-header h4{display:flex;align-items:center;gap:.5rem;color:var(--gray-900);font-size:1.125rem;font-weight:600;margin:0}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}.form-group{display:flex;flex-direction:column;gap:.5rem}.form-group.full-width{grid-column:1 / -1}.form-label{display:flex;align-items:center;gap:.5rem;font-size:.875rem;font-weight:500;color:var(--gray-700)}.form-label.required::after{content:'*';color:var(--danger-color);margin-left:.25rem}.form-input,.form-textarea{padding:.75rem;border:1px solid var(--gray-300);border-radius:var(--border-radius-sm);font-size:.875rem;transition:var(--transition);background:white}.form-input:focus,.form-textarea:focus{outline:0;border-color:var(--primary-color);box-shadow:0 0 0 3px rgba(59,130,246,.1)}.form-input.error,.form-textarea.error{border-color:var(--danger-color);box-shadow:0 0 0 3px rgba(239,68,68,.1)}.form-textarea{resize:vertical;min-height:80px}.error-message{display:flex;align-items:center;gap:.5rem;color:var(--danger-color);font-size:.75rem;font-weight:500}.btn{display:inline-flex;align-items:center;gap:.5rem;padding:.75rem 1.5rem;border:none;border-radius:var(--border-radius-sm);font-size:.875rem;font-weight:500;text-decoration:none;cursor:pointer;transition:var(--transition)}.btn-primary{background:var(--primary-color);color:white}.btn-primary:hover{background:var(--primary-dark)}.btn-secondary{background:var(--gray-200);color:var(--gray-800)}.btn-secondary:hover{background:var(--gray-300)}@media (max-width:768px){.form-grid{grid-template-columns:1fr}}
    </style>
</body>
</html>
<?php ob_end_flush(); ?>
