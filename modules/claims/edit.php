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

// Define variables
$claim_month = "";
$vehicle_type = "";
$entries = [];
$meal_entries = [];
$vehicle_type_err = "";
$entries_err = "";
$total_amount = 0;
$access_error = "";

// Fetch claim data
try {
    $stmt = $pdo->prepare("SELECT * FROM claims WHERE claim_id = :id");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();

    if($stmt->rowCount() != 1){
        header("location: index.php");
        exit();
    }

    $claim = $stmt->fetch(PDO::FETCH_ASSOC);

    if($claim['staff_id'] != $_SESSION['staff_id'] && $_SESSION['position'] != 'Admin' && $_SESSION['position'] != 'Manager'){
        $access_error = "You do not have permission to edit this claim.";
    } elseif($claim['status'] != 'Pending'){
        $access_error = "This claim has already been processed and cannot be edited.";
    } else {
        $claim_month = $claim['claim_month'];
        $vehicle_type = $claim['vehicle_type'];

        $entry_stmt = $pdo->prepare("SELECT * FROM claim_travel_entries WHERE claim_id = :claim_id ORDER BY travel_date");
        $entry_stmt->bindParam(":claim_id", $_GET["id"], PDO::PARAM_INT);
        $entry_stmt->execute();
        $entries = $entry_stmt->fetchAll(PDO::FETCH_ASSOC);

        $meal_stmt = $pdo->prepare("SELECT * FROM claim_meal_entries WHERE claim_id = :claim_id ORDER BY meal_date");
        $meal_stmt->bindParam(":claim_id", $_GET["id"], PDO::PARAM_INT);
        $meal_stmt->execute();
        $meal_entries = $meal_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch(PDOException $e) {
    die("ERROR: Could not fetch claim. " . $e->getMessage());
}

// Get staff information
try {
    $stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = :staff_id");
    $stmt->bindParam(":staff_id", $claim['staff_id'], PDO::PARAM_INT);
    $stmt->execute();
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("ERROR: Could not fetch staff information. " . $e->getMessage());
}

// Get mileage rates
try {
    $stmt = $pdo->query("SELECT * FROM mileage_rates ORDER BY vehicle_type, km_threshold");
    $mileage_rates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $rates = [];
    foreach ($mileage_rates as $rate) {
        if (!isset($rates[$rate['vehicle_type']])) $rates[$rate['vehicle_type']] = [];
        $rates[$rate['vehicle_type']][] = ['threshold' => $rate['km_threshold'], 'rate' => $rate['rate_per_km']];
    }
} catch(PDOException $e) {
    die("ERROR: Could not fetch mileage rates. " . $e->getMessage());
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST" && empty($access_error)){
    $id = $_POST["id"];

    if(empty(trim($_POST["vehicle_type"]))){
        $vehicle_type_err = "Please select a vehicle type.";
    } else {
        $vehicle_type = trim($_POST["vehicle_type"]);
    }

    if(!empty(trim($_POST["claim_month"]))){
        $claim_month = trim($_POST["claim_month"]);
    }

    if(isset($_POST["travel_date"]) && is_array($_POST["travel_date"])){
        $count = count($_POST["travel_date"]);
        $entries = [];
        for($i = 0; $i < $count; $i++){
            if(!empty($_POST["travel_date"][$i]) && !empty($_POST["travel_from"][$i]) &&
               !empty($_POST["travel_to"][$i]) && !empty($_POST["purpose"][$i]) &&
               !empty($_POST["miles_traveled"][$i])){
                $entry = [
                    'travel_date'   => $_POST["travel_date"][$i],
                    'travel_from'   => $_POST["travel_from"][$i],
                    'travel_to'     => $_POST["travel_to"][$i],
                    'purpose'       => $_POST["purpose"][$i],
                    'parking_fee'   => !empty($_POST["parking_fee"][$i]) ? $_POST["parking_fee"][$i] : 0,
                    'toll_fee'      => !empty($_POST["toll_fee"][$i]) ? $_POST["toll_fee"][$i] : 0,
                    'miles_traveled'=> $_POST["miles_traveled"][$i]
                ];
                if(!empty($_POST["entry_id"][$i])) $entry['entry_id'] = $_POST["entry_id"][$i];
                $entries[] = $entry;
            }
        }
    }

    if(isset($_POST["meal_date"]) && is_array($_POST["meal_date"])){
        $count = count($_POST["meal_date"]);
        $meal_entries = [];
        for($i = 0; $i < $count; $i++){
            if(!empty($_POST["meal_date"][$i]) && !empty($_POST["meal_type"][$i]) &&
               !empty($_POST["meal_description"][$i]) && !empty($_POST["meal_amount"][$i])){
                $meal_entry = [
                    'meal_date'         => $_POST["meal_date"][$i],
                    'meal_type'         => $_POST["meal_type"][$i],
                    'description'       => $_POST["meal_description"][$i],
                    'amount'            => $_POST["meal_amount"][$i],
                    'receipt_reference' => !empty($_POST["receipt_reference"][$i]) ? $_POST["receipt_reference"][$i] : ''
                ];
                if(!empty($_POST["meal_id"][$i])) $meal_entry['meal_id'] = $_POST["meal_id"][$i];
                $meal_entries[] = $meal_entry;
            }
        }
    }

    if(empty($entries)) $entries_err = "Please add at least one travel entry.";

    $total_km = $total_parking = $total_toll = $total_meal = 0;
    foreach($entries as $entry){
        $total_km      += floatval($entry['miles_traveled']);
        $total_parking += floatval($entry['parking_fee']);
        $total_toll    += floatval($entry['toll_fee']);
    }
    foreach($meal_entries as $meal){
        $total_meal += floatval($meal['amount']);
    }

    $km_amount = 0; $km_rate = 0;
    $isManager = ($_SESSION['position'] === 'Manager');

    if($vehicle_type === "Car") {
        if($isManager) {
            $km_amount = $total_km <= 500 ? $total_km * 1.00 : (500 * 1.00) + (($total_km - 500) * 0.80);
            $km_rate = $total_km <= 500 ? 1.00 : 0.80;
        } else {
            $km_amount = $total_km <= 500 ? $total_km * 0.80 : (500 * 0.80) + (($total_km - 500) * 0.50);
            $km_rate = $total_km <= 500 ? 0.80 : 0.50;
        }
    } else if($vehicle_type === "Motorcycle") {
        if($isManager) {
            $km_amount = $total_km <= 500 ? $total_km * 0.80 : (500 * 0.80) + (($total_km - 500) * 1.00);
            $km_rate = $total_km <= 500 ? 0.80 : 1.00;
        } else {
            $km_amount = $total_km * 0.50;
            $km_rate = 0.50;
        }
    }

    $total_amount = $km_amount + $total_parking + $total_toll + $total_meal;

    if(empty($vehicle_type_err) && empty($entries_err)){
        try {
            $pdo->beginTransaction();

            $sql = "UPDATE claims SET claim_month = :claim_month, vehicle_type = :vehicle_type, description = :description,
                    amount = :amount, km_rate = :km_rate, total_km_amount = :total_km_amount, total_meal_amount = :total_meal_amount
                    WHERE claim_id = :id";

            if($stmt = $pdo->prepare($sql)){
                $description = "Mileage Reimbursement for " . $claim_month . " - Total KM: " . $total_km;
                $stmt->bindParam(":claim_month", $claim_month, PDO::PARAM_STR);
                $stmt->bindParam(":vehicle_type", $vehicle_type, PDO::PARAM_STR);
                $stmt->bindParam(":description", $description, PDO::PARAM_STR);
                $stmt->bindParam(":amount", $total_amount, PDO::PARAM_STR);
                $stmt->bindParam(":km_rate", $km_rate, PDO::PARAM_STR);
                $stmt->bindParam(":total_km_amount", $km_amount, PDO::PARAM_STR);
                $stmt->bindParam(":total_meal_amount", $total_meal, PDO::PARAM_STR);
                $stmt->bindParam(":id", $id, PDO::PARAM_INT);

                if($stmt->execute()){
                    $del = $pdo->prepare("DELETE FROM claim_travel_entries WHERE claim_id = :claim_id");
                    $del->bindParam(":claim_id", $id, PDO::PARAM_INT);
                    $del->execute();

                    $entry_sql = "INSERT INTO claim_travel_entries (claim_id, travel_date, travel_from, travel_to, purpose, parking_fee, toll_fee, miles_traveled)
                                  VALUES (:claim_id, :travel_date, :travel_from, :travel_to, :purpose, :parking_fee, :toll_fee, :miles_traveled)";
                    $entry_stmt = $pdo->prepare($entry_sql);
                    foreach($entries as $entry){
                        $entry_stmt->bindParam(":claim_id", $id, PDO::PARAM_INT);
                        $entry_stmt->bindParam(":travel_date", $entry['travel_date'], PDO::PARAM_STR);
                        $entry_stmt->bindParam(":travel_from", $entry['travel_from'], PDO::PARAM_STR);
                        $entry_stmt->bindParam(":travel_to", $entry['travel_to'], PDO::PARAM_STR);
                        $entry_stmt->bindParam(":purpose", $entry['purpose'], PDO::PARAM_STR);
                        $entry_stmt->bindParam(":parking_fee", $entry['parking_fee'], PDO::PARAM_STR);
                        $entry_stmt->bindParam(":toll_fee", $entry['toll_fee'], PDO::PARAM_STR);
                        $entry_stmt->bindParam(":miles_traveled", $entry['miles_traveled'], PDO::PARAM_STR);
                        $entry_stmt->execute();
                    }

                    $delMeal = $pdo->prepare("DELETE FROM claim_meal_entries WHERE claim_id = :claim_id");
                    $delMeal->bindParam(":claim_id", $id, PDO::PARAM_INT);
                    $delMeal->execute();

                    if(!empty($meal_entries)){
                        $meal_sql = "INSERT INTO claim_meal_entries (claim_id, meal_date, meal_type, description, amount, receipt_reference)
                                    VALUES (:claim_id, :meal_date, :meal_type, :description, :amount, :receipt_reference)";
                        $meal_stmt = $pdo->prepare($meal_sql);
                        foreach($meal_entries as $meal){
                            $meal_stmt->bindParam(":claim_id", $id, PDO::PARAM_INT);
                            $meal_stmt->bindParam(":meal_date", $meal['meal_date'], PDO::PARAM_STR);
                            $meal_stmt->bindParam(":meal_type", $meal['meal_type'], PDO::PARAM_STR);
                            $meal_stmt->bindParam(":description", $meal['description'], PDO::PARAM_STR);
                            $meal_stmt->bindParam(":amount", $meal['amount'], PDO::PARAM_STR);
                            $meal_stmt->bindParam(":receipt_reference", $meal['receipt_reference'], PDO::PARAM_STR);
                            $meal_stmt->execute();
                        }
                    }

                    $pdo->commit();
                    header("location: view.php?id=" . $id);
                    exit();
                } else {
                    $pdo->rollBack();
                    $form_error = "Something went wrong. Please try again later.";
                }
            }
        } catch(PDOException $e) {
            $pdo->rollBack();
            die("ERROR: Could not execute query. " . $e->getMessage());
        }
    }
}

$months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
$isManager = ($_SESSION['position'] === 'Manager');
$meal_types = ['Breakfast','Lunch','Dinner','Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Claim #<?php echo htmlspecialchars($_GET['id']); ?> - SOA Management System</title>
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
                        <h1>Edit Claim #<?php echo str_pad($_GET['id'], 4, '0', STR_PAD_LEFT); ?></h1>
                        <p>Update your reimbursement claim details</p>
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

            <?php if(!empty($access_error)): ?>
            <!-- Access Error -->
            <div class="access-denied-card" data-aos="fade-up">
                <div class="access-denied-icon"><i class="fas fa-lock"></i></div>
                <h2>Access Denied</h2>
                <p><?php echo htmlspecialchars($access_error); ?></p>
                <a href="index.php" class="export-btn" style="display:inline-flex;margin-top:1rem;">
                    <i class="fas fa-arrow-left"></i> Back to Claims
                </a>
            </div>
            <?php else: ?>

            <?php if(!empty($form_error)): ?>
            <div class="alert alert-error" data-aos="fade-down">
                <div class="alert-content"><i class="fas fa-exclamation-circle"></i> <span><?php echo $form_error; ?></span></div>
                <button class="alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            </div>
            <?php endif; ?>

            <!-- Info banner -->
            <div class="info-banner" data-aos="fade-up">
                <div class="info-banner-icon"><i class="fas fa-edit"></i></div>
                <div class="info-banner-content">
                    <h4>Editing Claim #<?php echo str_pad($_GET['id'], 4, '0', STR_PAD_LEFT); ?></h4>
                    <p>Only pending claims can be edited. Changes will replace the existing travel and meal entries.
                    <button type="button" class="rates-link" onclick="openRatesModal()">View mileage rates &rarr;</button></p>
                </div>
            </div>

            <!-- Form card -->
            <div class="table-card" data-aos="fade-up">
                <div class="table-header">
                    <div class="table-title">
                        <h3><i class="fas fa-file-invoice"></i> Reimbursement Form</h3>
                        <p>Update travel and expense details</p>
                    </div>
                </div>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $_GET['id']; ?>" method="post" id="claimForm" class="form-container">
                    <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">

                    <!-- Employee Info -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="fas fa-user"></i> Employee Information</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Employee Name</label>
                                <input type="text" class="form-input" value="<?php echo htmlspecialchars($staff['full_name']); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Staff Number</label>
                                <input type="text" class="form-input" value="<?php echo htmlspecialchars($staff['staff_id']); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Department</label>
                                <input type="text" class="form-input" value="<?php echo htmlspecialchars($staff['department']); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Position</label>
                                <input type="text" class="form-input" value="<?php echo htmlspecialchars($_SESSION['position']); ?>" readonly>
                                <input type="hidden" id="userPosition" value="<?php echo htmlspecialchars($_SESSION['position']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Claim Details -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="fas fa-car"></i> Claim Details</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Claim Month <span class="required">*</span></label>
                                <select name="claim_month" class="form-input">
                                    <?php foreach($months as $month): ?>
                                        <option value="<?php echo $month; ?>" <?php echo ($claim_month == $month) ? 'selected' : ''; ?>><?php echo $month; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Vehicle Type <span class="required">*</span></label>
                                <select name="vehicle_type" id="vehicleType" class="form-input <?php echo (!empty($vehicle_type_err)) ? 'input-error' : ''; ?>">
                                    <option value="">Select Vehicle Type</option>
                                    <option value="Car" <?php echo ($vehicle_type == "Car") ? 'selected' : ''; ?>>Car</option>
                                    <option value="Motorcycle" <?php echo ($vehicle_type == "Motorcycle") ? 'selected' : ''; ?>>Motorcycle</option>
                                </select>
                                <?php if(!empty($vehicle_type_err)): ?>
                                    <span class="error-message"><?php echo $vehicle_type_err; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Travel Details -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="fas fa-route"></i> Travel Details</div>
                        <?php if(!empty($entries_err)): ?>
                        <div class="alert alert-error" style="margin-bottom:1rem;">
                            <div class="alert-content"><i class="fas fa-exclamation-circle"></i> <span><?php echo $entries_err; ?></span></div>
                        </div>
                        <?php endif; ?>

                        <div class="entries-table-wrapper">
                            <div class="overflow-x-auto">
                                <table class="entries-table" id="travelEntriesTable">
                                    <thead>
                                        <tr>
                                            <th>Date</th><th>From</th><th>To</th><th>Purpose</th>
                                            <th>Parking (RM)</th><th>Toll (RM)</th><th>KM</th><th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="entriesBody">
                                        <?php if(empty($entries)): ?>
                                        <tr class="entry-row">
                                            <td><input type="date" name="travel_date[]" class="entry-input" required></td>
                                            <td><input type="text" name="travel_from[]" class="entry-input" placeholder="From" required></td>
                                            <td><input type="text" name="travel_to[]" class="entry-input" placeholder="To" required></td>
                                            <td><input type="text" name="purpose[]" class="entry-input" placeholder="Purpose" required></td>
                                            <td><input type="number" name="parking_fee[]" class="entry-input parking-fee" step="0.01" min="0" value="0"></td>
                                            <td><input type="number" name="toll_fee[]" class="entry-input toll-fee" step="0.01" min="0" value="0"></td>
                                            <td><input type="number" name="miles_traveled[]" class="entry-input miles-traveled" step="0.01" min="0" placeholder="0.00" required></td>
                                            <td><button type="button" class="entry-remove-btn remove-entry"><i class="fas fa-trash"></i></button></td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach($entries as $entry): ?>
                                            <tr class="entry-row">
                                                <input type="hidden" name="entry_id[]" value="<?php echo isset($entry['entry_id']) ? $entry['entry_id'] : ''; ?>">
                                                <td><input type="date" name="travel_date[]" class="entry-input" value="<?php echo $entry['travel_date']; ?>" required></td>
                                                <td><input type="text" name="travel_from[]" class="entry-input" value="<?php echo htmlspecialchars($entry['travel_from']); ?>" required></td>
                                                <td><input type="text" name="travel_to[]" class="entry-input" value="<?php echo htmlspecialchars($entry['travel_to']); ?>" required></td>
                                                <td><input type="text" name="purpose[]" class="entry-input" value="<?php echo htmlspecialchars($entry['purpose']); ?>" required></td>
                                                <td><input type="number" name="parking_fee[]" class="entry-input parking-fee" step="0.01" min="0" value="<?php echo $entry['parking_fee']; ?>"></td>
                                                <td><input type="number" name="toll_fee[]" class="entry-input toll-fee" step="0.01" min="0" value="<?php echo $entry['toll_fee']; ?>"></td>
                                                <td><input type="number" name="miles_traveled[]" class="entry-input miles-traveled" step="0.01" min="0" value="<?php echo $entry['miles_traveled']; ?>" required></td>
                                                <td><button type="button" class="entry-remove-btn remove-entry"><i class="fas fa-trash"></i></button></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="entries-footer">
                                <button type="button" class="add-entry-btn" id="addEntryBtn">
                                    <i class="fas fa-plus"></i> Add Travel Entry
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Meal Expenses -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="fas fa-utensils"></i> Meal Expenses</div>

                        <div class="entries-table-wrapper">
                            <div class="overflow-x-auto">
                                <table class="entries-table" id="mealEntriesTable">
                                    <thead>
                                        <tr>
                                            <th>Date</th><th>Meal Type</th><th>Description</th>
                                            <th>Amount (RM)</th><th>Receipt Ref</th><th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="mealEntriesBody">
                                        <?php if(empty($meal_entries)): ?>
                                        <tr class="meal-entry-row">
                                            <td><input type="date" name="meal_date[]" class="entry-input" required></td>
                                            <td>
                                                <select name="meal_type[]" class="entry-input" required>
                                                    <option value="">Select Type</option>
                                                    <?php foreach($meal_types as $type): ?>
                                                        <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td><input type="text" name="meal_description[]" class="entry-input" placeholder="e.g., Lunch with client" required></td>
                                            <td><input type="number" name="meal_amount[]" class="entry-input meal-amount" step="0.01" min="0" value="0" required></td>
                                            <td><input type="text" name="receipt_reference[]" class="entry-input" placeholder="Receipt #"></td>
                                            <td><button type="button" class="entry-remove-btn remove-meal-entry"><i class="fas fa-trash"></i></button></td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach($meal_entries as $meal): ?>
                                            <tr class="meal-entry-row">
                                                <input type="hidden" name="meal_id[]" value="<?php echo isset($meal['meal_id']) ? $meal['meal_id'] : ''; ?>">
                                                <td><input type="date" name="meal_date[]" class="entry-input" value="<?php echo $meal['meal_date']; ?>" required></td>
                                                <td>
                                                    <select name="meal_type[]" class="entry-input" required>
                                                        <option value="">Select Type</option>
                                                        <?php foreach($meal_types as $type): ?>
                                                            <option value="<?php echo $type; ?>" <?php echo ($meal['meal_type'] == $type) ? 'selected' : ''; ?>><?php echo $type; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td><input type="text" name="meal_description[]" class="entry-input" value="<?php echo htmlspecialchars($meal['description']); ?>" required></td>
                                                <td><input type="number" name="meal_amount[]" class="entry-input meal-amount" step="0.01" min="0" value="<?php echo $meal['amount']; ?>" required></td>
                                                <td><input type="text" name="receipt_reference[]" class="entry-input" value="<?php echo htmlspecialchars($meal['receipt_reference']); ?>"></td>
                                                <td><button type="button" class="entry-remove-btn remove-meal-entry"><i class="fas fa-trash"></i></button></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="entries-footer">
                                <button type="button" class="add-entry-btn add-entry-btn-meal" id="addMealEntryBtn">
                                    <i class="fas fa-plus"></i> Add Meal Expense
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="fas fa-calculator"></i> Live Summary</div>
                        <div class="summary-card">
                            <div class="summary-row"><span>Total Miles Traveled</span><strong><span id="totalKm">0.00</span> KM</strong></div>
                            <div class="summary-row"><span>Mileage Rate Amount</span><strong>RM <span id="totalKmAmount">0.00</span></strong></div>
                            <div class="summary-row"><span>Total Parking</span><strong>RM <span id="totalParking">0.00</span></strong></div>
                            <div class="summary-row"><span>Total Toll</span><strong>RM <span id="totalToll">0.00</span></strong></div>
                            <div class="summary-row"><span>Total Meal Expenses</span><strong>RM <span id="totalMeal">0.00</span></strong></div>
                            <div class="summary-divider"></div>
                            <div class="summary-row summary-total"><span>Total Reimbursement</span><strong>RM <span id="totalAmount">0.00</span></strong></div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="form-actions">
                        <button type="submit" class="btn-primary-large">
                            <i class="fas fa-save"></i> Update Claim
                        </button>
                        <a href="index.php" class="btn-secondary-large">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>

                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mileage Rates Modal -->
    <div id="ratesModal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fas fa-info-circle"></i> Mileage Reimbursement Rates</h3>
                <button class="modal-close" onclick="closeRatesModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div class="rates-tabs">
                    <button class="rates-tab active" onclick="switchRatesTab(this,'standardRates')">Standard Rates</button>
                    <button class="rates-tab" onclick="switchRatesTab(this,'managerRates')">Manager Rates</button>
                </div>
                <div id="standardRates" class="rates-content active">
                    <table class="modern-table">
                        <thead><tr><th>Mileage</th><th>Car</th><th>Motorcycle</th></tr></thead>
                        <tbody>
                            <tr><td>First 500 km</td><td>RM 0.80/km</td><td rowspan="2">RM 0.50/km (flat)</td></tr>
                            <tr><td>Subsequent km</td><td>RM 0.50/km</td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="managerRates" class="rates-content" style="display:none;">
                    <table class="modern-table">
                        <thead><tr><th>Mileage</th><th>Car</th><th>Motorcycle</th></tr></thead>
                        <tbody>
                            <tr><td>First 500 km</td><td>RM 1.00/km</td><td>RM 0.80/km</td></tr>
                            <tr><td>Subsequent km</td><td>RM 0.80/km</td><td>RM 1.00/km</td></tr>
                        </tbody>
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

        function updateTotals() {
            var totalKm = 0, totalParking = 0, totalToll = 0, totalMeal = 0;
            document.querySelectorAll('.entry-row').forEach(function(row) {
                totalKm      += parseFloat(row.querySelector('.miles-traveled').value) || 0;
                totalParking += parseFloat(row.querySelector('.parking-fee').value) || 0;
                totalToll    += parseFloat(row.querySelector('.toll-fee').value) || 0;
            });
            document.querySelectorAll('.meal-entry-row').forEach(function(row) {
                totalMeal += parseFloat(row.querySelector('.meal-amount').value) || 0;
            });

            var kmAmount = 0;
            var vehicleType = document.getElementById('vehicleType').value;
            var isManager = (document.getElementById('userPosition').value === 'Manager');

            if(vehicleType === "Car") {
                kmAmount = isManager
                    ? (totalKm <= 500 ? totalKm * 1.00 : 500 * 1.00 + (totalKm - 500) * 0.80)
                    : (totalKm <= 500 ? totalKm * 0.80 : 500 * 0.80 + (totalKm - 500) * 0.50);
            } else if(vehicleType === "Motorcycle") {
                kmAmount = isManager
                    ? (totalKm <= 500 ? totalKm * 0.80 : 500 * 0.80 + (totalKm - 500) * 1.00)
                    : totalKm * 0.50;
            }

            document.getElementById('totalKm').textContent       = totalKm.toFixed(2);
            document.getElementById('totalKmAmount').textContent  = kmAmount.toFixed(2);
            document.getElementById('totalParking').textContent   = totalParking.toFixed(2);
            document.getElementById('totalToll').textContent      = totalToll.toFixed(2);
            document.getElementById('totalMeal').textContent      = totalMeal.toFixed(2);
            document.getElementById('totalAmount').textContent    = (kmAmount + totalParking + totalToll + totalMeal).toFixed(2);
        }

        function makeTravelRow() {
            var tr = document.createElement('tr');
            tr.className = 'entry-row';
            tr.innerHTML = `
                <td><input type="date" name="travel_date[]" class="entry-input" required></td>
                <td><input type="text" name="travel_from[]" class="entry-input" placeholder="From" required></td>
                <td><input type="text" name="travel_to[]" class="entry-input" placeholder="To" required></td>
                <td><input type="text" name="purpose[]" class="entry-input" placeholder="Purpose" required></td>
                <td><input type="number" name="parking_fee[]" class="entry-input parking-fee" step="0.01" min="0" value="0"></td>
                <td><input type="number" name="toll_fee[]" class="entry-input toll-fee" step="0.01" min="0" value="0"></td>
                <td><input type="number" name="miles_traveled[]" class="entry-input miles-traveled" step="0.01" min="0" placeholder="0.00" required></td>
                <td><button type="button" class="entry-remove-btn remove-entry"><i class="fas fa-trash"></i></button></td>
            `;
            tr.querySelectorAll('input').forEach(function(i){ i.addEventListener('input', updateTotals); });
            return tr;
        }

        function makeMealRow() {
            var tr = document.createElement('tr');
            tr.className = 'meal-entry-row';
            tr.innerHTML = `
                <td><input type="date" name="meal_date[]" class="entry-input" required></td>
                <td>
                    <select name="meal_type[]" class="entry-input" required>
                        <option value="">Select Type</option>
                        <?php foreach($meal_types as $type): ?>
                            <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="text" name="meal_description[]" class="entry-input" placeholder="e.g., Lunch with client" required></td>
                <td><input type="number" name="meal_amount[]" class="entry-input meal-amount" step="0.01" min="0" value="0" required></td>
                <td><input type="text" name="receipt_reference[]" class="entry-input" placeholder="Receipt #"></td>
                <td><button type="button" class="entry-remove-btn remove-meal-entry"><i class="fas fa-trash"></i></button></td>
            `;
            tr.querySelectorAll('input').forEach(function(i){ i.addEventListener('input', updateTotals); });
            return tr;
        }

        document.getElementById('addEntryBtn').addEventListener('click', function() {
            document.getElementById('entriesBody').appendChild(makeTravelRow());
            updateTotals();
        });

        document.getElementById('addMealEntryBtn').addEventListener('click', function() {
            document.getElementById('mealEntriesBody').appendChild(makeMealRow());
            updateTotals();
        });

        document.getElementById('entriesBody').addEventListener('click', function(e) {
            var btn = e.target.closest('.remove-entry');
            if(!btn) return;
            var rows = document.querySelectorAll('.entry-row');
            if(rows.length > 1) { btn.closest('tr').remove(); updateTotals(); }
            else alert("You must have at least one travel entry.");
        });

        document.getElementById('mealEntriesBody').addEventListener('click', function(e) {
            var btn = e.target.closest('.remove-meal-entry');
            if(!btn) return;
            btn.closest('tr').remove();
            updateTotals();
        });

        document.querySelectorAll('.miles-traveled, .parking-fee, .toll-fee, .meal-amount').forEach(function(i){
            i.addEventListener('input', updateTotals);
        });
        document.getElementById('vehicleType').addEventListener('change', updateTotals);
        updateTotals();
    });

    function openRatesModal() { document.getElementById('ratesModal').style.display = 'flex'; }
    function closeRatesModal() { document.getElementById('ratesModal').style.display = 'none'; }
    function switchRatesTab(btn, id) {
        document.querySelectorAll('.rates-tab').forEach(function(t){ t.classList.remove('active'); });
        document.querySelectorAll('.rates-content').forEach(function(c){ c.style.display='none'; c.classList.remove('active'); });
        btn.classList.add('active');
        var el = document.getElementById(id);
        el.style.display='block'; el.classList.add('active');
    }
    document.addEventListener('keydown', function(e){ if(e.key === 'Escape') closeRatesModal(); });
    </script>

    <style>
        .export-btn.secondary { background: white; border-color: var(--gray-300); color: var(--gray-700); }
        .export-btn.secondary:hover { background: var(--gray-50); border-color: var(--gray-400); color: var(--gray-900); }

        .alert { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.5rem; border-radius: var(--border-radius); margin-bottom: 1.5rem; border: 1px solid; }
        .alert-error { background: rgba(239,68,68,0.05); border-color: var(--danger-color); color: var(--danger-color); }
        .alert-content { display: flex; align-items: center; gap: 0.75rem; }
        .alert-close { background: none; border: none; color: inherit; cursor: pointer; }

        /* Access denied */
        .access-denied-card { background: white; border-radius: var(--border-radius); box-shadow: var(--shadow); border: 1px solid var(--gray-200); padding: 3rem; text-align: center; max-width: 480px; margin: 2rem auto; }
        .access-denied-icon { width: 72px; height: 72px; background: rgba(239,68,68,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: var(--danger-color); font-size: 2rem; }
        .access-denied-card h2 { color: var(--gray-900); margin-bottom: 0.5rem; }
        .access-denied-card p { color: var(--gray-600); }

        /* Info banner */
        .info-banner { display: flex; gap: 1rem; align-items: flex-start; background: linear-gradient(135deg,rgba(245,158,11,0.08),rgba(59,130,246,0.08)); border: 1px solid rgba(245,158,11,0.25); border-radius: var(--border-radius); padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; }
        .info-banner-icon { width: 44px; height: 44px; background: var(--warning-color); border-radius: var(--border-radius-sm); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.25rem; flex-shrink: 0; }
        .info-banner-content h4 { font-size: 0.9375rem; font-weight: 600; color: var(--gray-900); margin-bottom: 0.25rem; }
        .info-banner-content p { font-size: 0.875rem; color: var(--gray-600); margin: 0; }
        .rates-link { background: none; border: none; color: var(--primary-color); cursor: pointer; font-size: 0.875rem; text-decoration: underline; padding: 0; }

        /* Table header icon */
        .table-header h3 { display: flex; align-items: center; gap: 0.5rem; }
        .table-header h3 i { color: var(--primary-color); }

        /* Entries table */
        .entries-table-wrapper { border: 1px solid var(--gray-200); border-radius: var(--border-radius-sm); overflow: hidden; margin-bottom: 0.75rem; }
        .entries-table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }
        .entries-table th { background: var(--gray-50); padding: 0.75rem 0.875rem; text-align: left; font-size: 0.75rem; font-weight: 600; color: var(--gray-600); text-transform: uppercase; letter-spacing: 0.04em; border-bottom: 1px solid var(--gray-200); white-space: nowrap; }
        .entries-table td { padding: 0.5rem 0.5rem; border-bottom: 1px solid var(--gray-100); vertical-align: middle; }
        .entries-table tbody tr:last-child td { border-bottom: none; }
        .entry-input { width: 100%; padding: 0.5rem 0.625rem !important; border: 1px solid var(--gray-200) !important; border-radius: 6px !important; font-size: 0.8125rem !important; color: var(--gray-900) !important; background: white !important; min-width: 80px; box-sizing: border-box !important; }
        .entry-input:focus { outline: none !important; border-color: var(--primary-color) !important; box-shadow: 0 0 0 2px rgba(59,130,246,0.1) !important; }
        .entry-remove-btn { width: 30px; height: 30px; border: none; background: rgba(239,68,68,0.1); color: var(--danger-color); border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; transition: var(--transition); }
        .entry-remove-btn:hover { background: var(--danger-color); color: white; }
        .entries-footer { padding: 0.75rem 1rem; background: var(--gray-50); border-top: 1px solid var(--gray-200); }
        .add-entry-btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 0.875rem; background: rgba(59,130,246,0.1); color: var(--primary-color); border: 1px solid rgba(59,130,246,0.2); border-radius: 6px; font-size: 0.8125rem; font-weight: 500; cursor: pointer; transition: var(--transition); }
        .add-entry-btn:hover { background: var(--primary-color); color: white; }
        .add-entry-btn-meal { background: rgba(16,185,129,0.1); color: var(--success-color); border-color: rgba(16,185,129,0.2); }
        .add-entry-btn-meal:hover { background: var(--success-color); color: white; }

        /* Summary card */
        .summary-card { background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: var(--border-radius-sm); padding: 1.5rem; max-width: 480px; margin-left: auto; }
        .summary-row { display: flex; justify-content: space-between; padding: 0.5rem 0; font-size: 0.875rem; color: var(--gray-700); }
        .summary-divider { border-top: 1px solid var(--gray-300); margin: 0.75rem 0; }
        .summary-total { font-size: 1rem; font-weight: 600; color: var(--gray-900); }
        .summary-total strong { color: var(--success-color); font-size: 1.125rem; }

        /* Modal */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .modal-box { background: white; border-radius: var(--border-radius); box-shadow: var(--shadow-xl); width: 100%; max-width: 520px; overflow: hidden; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--gray-200); }
        .modal-header h3 { display: flex; align-items: center; gap: 0.5rem; font-size: 1rem; font-weight: 600; color: var(--gray-900); margin: 0; }
        .modal-header h3 i { color: var(--primary-color); }
        .modal-close { background: none; border: none; cursor: pointer; width: 32px; height: 32px; border-radius: var(--border-radius-sm); display: flex; align-items: center; justify-content: center; color: var(--gray-500); transition: var(--transition); }
        .modal-close:hover { background: var(--gray-100); color: var(--gray-900); }
        .modal-body { padding: 1.5rem; }
        .rates-tabs { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
        .rates-tab { padding: 0.5rem 1rem; border: 1px solid var(--gray-300); border-radius: var(--border-radius-sm); background: white; font-size: 0.875rem; color: var(--gray-600); cursor: pointer; transition: var(--transition); }
        .rates-tab.active { background: var(--primary-color); color: white; border-color: var(--primary-color); }

        @media (max-width: 768px) {
            .summary-card { max-width: 100%; }
        }
    </style>
</body>
</html>
