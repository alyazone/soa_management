<?php
// Set the base path for includes
$basePath = '../../';
ob_start();

// Include header and sidebar
include_once $basePath . "includes/header.php";
include_once $basePath . "includes/sidebar.php";

// Include database connection
require_once $basePath . "config/database.php";

// Check if id parameter is set
if(empty($_GET["id"])){
    header("location: index.php");
    exit();
}

// Define variables and initialize with empty values
$claim_month = "";
$vehicle_type = "";
$entries = [];
$meal_entries = [];
$vehicle_type_err = "";
$entries_err = "";
$total_amount = 0;

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
    
    // Check if user has permission to edit this claim
    if($claim['staff_id'] != $_SESSION['staff_id'] && $_SESSION['position'] != 'Admin' && $_SESSION['position'] != 'Manager'){
        echo '<div class="col-md-10 ml-sm-auto px-4"><div class="alert alert-danger mt-3">You do not have permission to edit this claim.</div></div>';
        include_once $basePath . "includes/footer.php";
        exit;
    }
    
    // Check if claim is already processed
    if($claim['status'] != 'Pending'){
        echo '<div class="col-md-10 ml-sm-auto px-4"><div class="alert alert-danger mt-3">This claim has already been processed and cannot be edited.</div></div>';
        include_once $basePath . "includes/footer.php";
        exit;
    }
    
    // Set values
    $claim_month = $claim['claim_month'];
    $vehicle_type = $claim['vehicle_type'];
    
    // Fetch travel entries
    $entry_stmt = $pdo->prepare("SELECT * FROM claim_travel_entries WHERE claim_id = :claim_id ORDER BY travel_date");
    $entry_stmt->bindParam(":claim_id", $_GET["id"], PDO::PARAM_INT);
    $entry_stmt->execute();
    $entries = $entry_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch meal entries
    $meal_stmt = $pdo->prepare("SELECT * FROM claim_meal_entries WHERE claim_id = :claim_id ORDER BY meal_date");
    $meal_stmt->bindParam(":claim_id", $_GET["id"], PDO::PARAM_INT);
    $meal_stmt->execute();
    $meal_entries = $meal_stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
    
    // Organize rates by vehicle type
    $rates = [];
    foreach ($mileage_rates as $rate) {
        if (!isset($rates[$rate['vehicle_type']])) {
            $rates[$rate['vehicle_type']] = [];
        }
        $rates[$rate['vehicle_type']][] = [
            'threshold' => $rate['km_threshold'],
            'rate' => $rate['rate_per_km']
        ];
    }
} catch(PDOException $e) {
    die("ERROR: Could not fetch mileage rates. " . $e->getMessage());
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Get hidden input value
    $id = $_POST["id"];
    
    // Validate vehicle type
    if(empty(trim($_POST["vehicle_type"]))){
        $vehicle_type_err = "Please select a vehicle type.";
    } else{
        $vehicle_type = trim($_POST["vehicle_type"]);
    }
    
    // Validate claim month
    if(!empty(trim($_POST["claim_month"]))){
        $claim_month = trim($_POST["claim_month"]);
    }
    
    // Process travel entries
    if(isset($_POST["travel_date"]) && is_array($_POST["travel_date"])){
        $count = count($_POST["travel_date"]);
        $entries = [];
        
        for($i = 0; $i < $count; $i++){
            if(!empty($_POST["travel_date"][$i]) && !empty($_POST["travel_from"][$i]) && 
               !empty($_POST["travel_to"][$i]) && !empty($_POST["purpose"][$i]) && 
               !empty($_POST["miles_traveled"][$i])){
                
                $entry = [
                    'travel_date' => $_POST["travel_date"][$i],
                    'travel_from' => $_POST["travel_from"][$i],
                    'travel_to' => $_POST["travel_to"][$i],
                    'purpose' => $_POST["purpose"][$i],
                    'parking_fee' => !empty($_POST["parking_fee"][$i]) ? $_POST["parking_fee"][$i] : 0,
                    'toll_fee' => !empty($_POST["toll_fee"][$i]) ? $_POST["toll_fee"][$i] : 0,
                    'miles_traveled' => $_POST["miles_traveled"][$i]
                ];
                
                if(!empty($_POST["entry_id"][$i])){
                    $entry['entry_id'] = $_POST["entry_id"][$i];
                }
                
                $entries[] = $entry;
            }
        }
    }
    
    // Process meal entries
    if(isset($_POST["meal_date"]) && is_array($_POST["meal_date"])){
        $count = count($_POST["meal_date"]);
        $meal_entries = [];
        
        for($i = 0; $i < $count; $i++){
            if(!empty($_POST["meal_date"][$i]) && !empty($_POST["meal_type"][$i]) && 
               !empty($_POST["meal_description"][$i]) && !empty($_POST["meal_amount"][$i])){
                
                $meal_entry = [
                    'meal_date' => $_POST["meal_date"][$i],
                    'meal_type' => $_POST["meal_type"][$i],
                    'description' => $_POST["meal_description"][$i],
                    'amount' => $_POST["meal_amount"][$i],
                    'receipt_reference' => !empty($_POST["receipt_reference"][$i]) ? $_POST["receipt_reference"][$i] : ''
                ];
                
                if(!empty($_POST["meal_id"][$i])){
                    $meal_entry['meal_id'] = $_POST["meal_id"][$i];
                }
                
                $meal_entries[] = $meal_entry;
            }
        }
    }
    
    if(empty($entries)){
        $entries_err = "Please add at least one travel entry.";
    }
    
    // Calculate total amount
    $total_km = 0;
    $total_parking = 0;
    $total_toll = 0;
    $total_meal = 0;
    
    foreach($entries as $entry){
        $total_km += floatval($entry['miles_traveled']);
        $total_parking += floatval($entry['parking_fee']);
        $total_toll += floatval($entry['toll_fee']);
    }
    
    foreach($meal_entries as $meal){
        $total_meal += floatval($meal['amount']);
    }
    
    // Calculate mileage reimbursement based on rates and position
    $km_amount = 0;
    $km_rate = 0;
    
    // Check if user is a manager for special rates
    $isManager = ($_SESSION['position'] === 'Manager');
    
    if($vehicle_type === "Car") {
        if($isManager) {
            // Manager Car rates: First 500km - RM1.00, subsequent - RM0.80
            if($total_km <= 500) {
                $km_amount = $total_km * 1.00;
                $km_rate = 1.00;
            } else {
                $km_amount = (500 * 1.00) + (($total_km - 500) * 0.80);
                $km_rate = 0.80; // Use the last applicable rate
            }
        } else {
            // Regular Car rates: First 500km - RM0.80, subsequent - RM0.50
            if($total_km <= 500) {
                $km_amount = $total_km * 0.80;
                $km_rate = 0.80;
            } else {
                $km_amount = (500 * 0.80) + (($total_km - 500) * 0.50);
                $km_rate = 0.50; // Use the last applicable rate
            }
        }
    } else if($vehicle_type === "Motorcycle") {
        if($isManager) {
            // Manager Motorcycle rates: First 500km - RM0.80, subsequent - RM1.00
            if($total_km <= 500) {
                $km_amount = $total_km * 0.80;
                $km_rate = 0.80;
            } else {
                $km_amount = (500 * 0.80) + (($total_km - 500) * 1.00);
                $km_rate = 1.00; // Use the last applicable rate
            }
        } else {
            // Regular Motorcycle rate: Flat RM0.50
            $km_amount = $total_km * 0.50;
            $km_rate = 0.50;
        }
    }
    
    $total_amount = $km_amount + $total_parking + $total_toll + $total_meal;
    
    // Check input errors before updating the database
    if(empty($vehicle_type_err) && empty($entries_err)){
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Prepare an update statement
            $sql = "UPDATE claims SET claim_month = :claim_month, vehicle_type = :vehicle_type, description = :description, 
                    amount = :amount, km_rate = :km_rate, total_km_amount = :total_km_amount, total_meal_amount = :total_meal_amount 
                    WHERE claim_id = :id";
            
            if($stmt = $pdo->prepare($sql)){
                // Set parameters
                $description = "Mileage Reimbursement for " . $claim_month . " - Total KM: " . $total_km;
                
                // Bind variables
                $stmt->bindParam(":claim_month", $claim_month, PDO::PARAM_STR);
                $stmt->bindParam(":vehicle_type", $vehicle_type, PDO::PARAM_STR);
                $stmt->bindParam(":description", $description, PDO::PARAM_STR);
                $stmt->bindParam(":amount", $total_amount, PDO::PARAM_STR);
                $stmt->bindParam(":km_rate", $km_rate, PDO::PARAM_STR);
                $stmt->bindParam(":total_km_amount", $km_amount, PDO::PARAM_STR);
                $stmt->bindParam(":total_meal_amount", $total_meal, PDO::PARAM_STR);
                $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                
                // Execute the statement
                if($stmt->execute()){
                    // Delete existing entries
                    $delete_stmt = $pdo->prepare("DELETE FROM claim_travel_entries WHERE claim_id = :claim_id");
                    $delete_stmt->bindParam(":claim_id", $id, PDO::PARAM_INT);
                    $delete_stmt->execute();
                    
                    // Insert updated travel entries
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
                    
                    // Delete existing meal entries
                    $delete_meal_stmt = $pdo->prepare("DELETE FROM claim_meal_entries WHERE claim_id = :claim_id");
                    $delete_meal_stmt->bindParam(":claim_id", $id, PDO::PARAM_INT);
                    $delete_meal_stmt->execute();
                    
                    // Insert updated meal entries if any
                    if(!empty($meal_entries)) {
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
                    
                    // Commit transaction
                    $pdo->commit();
                    
                    // Redirect to view page
                    header("location: view.php?id=" . $id);
                    exit();
                } else {
                    $pdo->rollBack();
                    echo "Something went wrong. Please try again later.";
                }
            }
        } catch(PDOException $e) {
            $pdo->rollBack();
            die("ERROR: Could not execute query. " . $e->getMessage());
        }
    }
}

// Get list of months
$months = [
    'January', 'February', 'March', 'April', 'May', 'June', 
    'July', 'August', 'September', 'October', 'November', 'December'
];

// Check if user is a manager
$isManager = ($_SESSION['position'] === 'Manager');

// Meal types
$meal_types = ['Breakfast', 'Lunch', 'Dinner', 'Other'];
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Edit Reimbursement Claim</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="view.php?id=<?php echo $_GET['id']; ?>" class="btn btn-sm btn-info mr-2">
                <i class="fas fa-eye"></i> View Claim
            </a>
            <a href="index.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Reimbursement Form</h6>
            <div>
                <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#ratesModal">
                    <i class="fas fa-info-circle"></i> View Mileage Rates
                </button>
            </div>
        </div>
        <div class="card-body">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $_GET['id']; ?>" method="post" id="claimForm">
                <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Employee Name</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($staff['full_name']); ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Staff Number</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($staff['staff_id']); ?>" readonly>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Department</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($staff['department']); ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Month</label>
                            <select name="claim_month" class="form-control">
                                <?php foreach($months as $month): ?>
                                    <option value="<?php echo $month; ?>" <?php echo ($claim_month == $month) ? 'selected' : ''; ?>><?php echo $month; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Vehicle Type</label>
                            <select name="vehicle_type" id="vehicleType" class="form-control <?php echo (!empty($vehicle_type_err)) ? 'is-invalid' : ''; ?>">
                                <option value="">Select Vehicle Type</option>
                                <option value="Car" <?php echo ($vehicle_type == "Car") ? 'selected' : ''; ?>>Car</option>
                                <option value="Motorcycle" <?php echo ($vehicle_type == "Motorcycle") ? 'selected' : ''; ?>>Motorcycle</option>
                            </select>
                            <span class="invalid-feedback"><?php echo $vehicle_type_err; ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Position</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['position']); ?>" readonly>
                            <input type="hidden" id="userPosition" value="<?php echo htmlspecialchars($_SESSION['position']); ?>">
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <!-- Travel Details Section -->
                <h5 class="font-weight-bold">Travel Details</h5>
                <?php if(!empty($entries_err)): ?>
                    <div class="alert alert-danger"><?php echo $entries_err; ?></div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-bordered" id="travelEntriesTable">
                        <thead class="bg-primary" style="color:rgb(59, 114, 233);">
                            <tr>
                                <th>Date</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Purpose of Traveling</th>
                                <th>Parking (RM)</th>
                                <th>Toll (RM)</th>
                                <th>Miles Traveled (KM)</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="entriesBody">
                            <?php if(empty($entries)): ?>
                            <tr class="entry-row">
                                <td><input type="date" name="travel_date[]" class="form-control" required></td>
                                <td><input type="text" name="travel_from[]" class="form-control" required></td>
                                <td><input type="text" name="travel_to[]" class="form-control" required></td>
                                <td><input type="text" name="purpose[]" class="form-control" required></td>
                                <td><input type="number" name="parking_fee[]" class="form-control parking-fee" step="0.01" min="0" value="0"></td>
                                <td><input type="number" name="toll_fee[]" class="form-control toll-fee" step="0.01" min="0" value="0"></td>
                                <td><input type="number" name="miles_traveled[]" class="form-control miles-traveled" step="0.01" min="0" required></td>
                                <td><button type="button" class="btn btn-danger btn-sm remove-entry"><i class="fas fa-trash"></i></button></td>
                            </tr>
                            <?php else: ?>
                                <?php foreach($entries as $entry): ?>
                                <tr class="entry-row">
                                    <input type="hidden" name="entry_id[]" value="<?php echo $entry['entry_id']; ?>">
                                    <td><input type="date" name="travel_date[]" class="form-control" value="<?php echo $entry['travel_date']; ?>" required></td>
                                    <td><input type="text" name="travel_from[]" class="form-control" value="<?php echo htmlspecialchars($entry['travel_from']); ?>" required></td>
                                    <td><input type="text" name="travel_to[]" class="form-control" value="<?php echo htmlspecialchars($entry['travel_to']); ?>" required></td>
                                    <td><input type="text" name="purpose[]" class="form-control" value="<?php echo htmlspecialchars($entry['purpose']); ?>" required></td>
                                    <td><input type="number" name="parking_fee[]" class="form-control parking-fee" step="0.01" min="0" value="<?php echo $entry['parking_fee']; ?>"></td>
                                    <td><input type="number" name="toll_fee[]" class="form-control toll-fee" step="0.01" min="0" value="<?php echo $entry['toll_fee']; ?>"></td>
                                    <td><input type="number" name="miles_traveled[]" class="form-control miles-traveled" step="0.01" min="0" value="<?php echo $entry['miles_traveled']; ?>" required></td>
                                    <td><button type="button" class="btn btn-danger btn-sm remove-entry"><i class="fas fa-trash"></i></button></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="8">
                                    <button type="button" class="btn btn-success btn-sm" id="addEntryBtn">
                                        <i class="fas fa-plus"></i> Add Travel Entry
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <hr>
                
                <!-- Meal Expenses Section -->
                <h5 class="font-weight-bold mt-4">Meal Expenses</h5>
                
                <div class="table-responsive">
                    <table class="table table-bordered" id="mealEntriesTable">
                        <thead class="bg-success" style="color:rgb(59, 114, 233);">
                            <tr>
                                <th>Date</th>
                                <th>Meal Type</th>
                                <th>Description</th>
                                <th>Amount (RM)</th>
                                <th>Receipt Reference</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="mealEntriesBody">
                            <?php if(empty($meal_entries)): ?>
                            <tr class="meal-entry-row">
                                <td><input type="date" name="meal_date[]" class="form-control" required></td>
                                <td>
                                    <select name="meal_type[]" class="form-control" required>
                                        <option value="">Select Meal Type</option>
                                        <?php foreach($meal_types as $type): ?>
                                            <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="text" name="meal_description[]" class="form-control" placeholder="e.g., Lunch with client" required></td>
                                <td><input type="number" name="meal_amount[]" class="form-control meal-amount" step="0.01" min="0" value="0" required></td>
                                <td><input type="text" name="receipt_reference[]" class="form-control" placeholder="Receipt #"></td>
                                <td><button type="button" class="btn btn-danger btn-sm remove-meal-entry"><i class="fas fa-trash"></i></button></td>
                            </tr>
                            <?php else: ?>
                                <?php foreach($meal_entries as $meal): ?>
                                <tr class="meal-entry-row">
                                    <input type="hidden" name="meal_id[]" value="<?php echo $meal['meal_id']; ?>">
                                    <td><input type="date" name="meal_date[]" class="form-control" value="<?php echo $meal['meal_date']; ?>" required></td>
                                    <td>
                                        <select name="meal_type[]" class="form-control" required>
                                            <option value="">Select Meal Type</option>
                                            <?php foreach($meal_types as $type): ?>
                                                <option value="<?php echo $type; ?>" <?php echo ($meal['meal_type'] == $type) ? 'selected' : ''; ?>><?php echo $type; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input type="text" name="meal_description[]" class="form-control" value="<?php echo htmlspecialchars($meal['description']); ?>" required></td>
                                    <td><input type="number" name="meal_amount[]" class="form-control meal-amount" step="0.01" min="0" value="<?php echo $meal['amount']; ?>" required></td>
                                    <td><input type="text" name="receipt_reference[]" class="form-control" value="<?php echo htmlspecialchars($meal['receipt_reference']); ?>"></td>
                                    <td><button type="button" class="btn btn-danger btn-sm remove-meal-entry"><i class="fas fa-trash"></i></button></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6">
                                    <button type="button" class="btn btn-success btn-sm" id="addMealEntryBtn">
                                        <i class="fas fa-plus"></i> Add Meal Expense
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6 offset-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h6 class="m-0 font-weight-bold">Summary</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-8">Total Miles Traveled:</div>
                                    <div class="col-4 text-right"><span id="totalKm">0</span> KM</div>
                                </div>
                                <div class="row">
                                    <div class="col-8">Total multiply KM rate:</div>
                                    <div class="col-4 text-right">RM <span id="totalKmAmount">0.00</span></div>
                                </div>
                                <div class="row">
                                    <div class="col-8">Total Parking:</div>
                                    <div class="col-4 text-right">RM <span id="totalParking">0.00</span></div>
                                </div>
                                <div class="row">
                                    <div class="col-8">Total Toll:</div>
                                    <div class="col-4 text-right">RM <span id="totalToll">0.00</span></div>
                                </div>
                                <div class="row">
                                    <div class="col-8">Total Meal Expenses:</div>
                                    <div class="col-4 text-right">RM <span id="totalMeal">0.00</span></div>
                                </div>
                                <hr>
                                <div class="row font-weight-bold">
                                    <div class="col-8">Total Reimbursement Amount:</div>
                                    <div class="col-4 text-right">RM <span id="totalAmount">0.00</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info mt-4">
                    <i class="fas fa-info-circle"></i> By submitting this form, you certify that the reimbursement claimed are proper and actual expenses incurred during this period and in accordance with the company's Reimbursement Policy.
                </div>
                
                <div class="form-group mt-4">
                    <input type="submit" class="btn btn-primary" value="Update Claim">
                    <a href="index.php" class="btn btn-secondary ml-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Mileage Rates Modal -->
<div class="modal fade" id="ratesModal" tabindex="-1" role="dialog" aria-labelledby="ratesModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ratesModalLabel">Mileage Reimbursement Rates</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="ratesTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="standard-tab" data-toggle="tab" href="#standardRates" role="tab" aria-controls="standardRates" aria-selected="true">Standard Rates</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="manager-tab" data-toggle="tab" href="#managerRates" role="tab" aria-controls="managerRates" aria-selected="false">Manager Rates</a>
                    </li>
                </ul>
                <div class="tab-content mt-3" id="ratesTabContent">
                    <div class="tab-pane fade show active" id="standardRates" role="tabpanel" aria-labelledby="standard-tab">
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Mileage</th>
                                    <th>Car</th>
                                    <th>Motorcycle</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>First 500km</td>
                                    <td>RM0.80/km</td>
                                    <td rowspan="2">RM0.50/km</td>
                                </tr>
                                <tr>
                                    <td>Every subsequent km</td>
                                    <td>RM0.50/km</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="tab-pane fade" id="managerRates" role="tabpanel" aria-labelledby="manager-tab">
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Mileage</th>
                                    <th>Car</th>
                                    <th>Motorcycle</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>First 500km</td>
                                    <td>RM1.00/km</td>
                                    <td>RM0.80/km</td>
                                </tr>
                                <tr>
                                    <td>Every subsequent km</td>
                                    <td>RM0.80/km</td>
                                    <td>RM1.00/km</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Make sure we're using vanilla JS to avoid jQuery conflicts
    
    // Function to calculate and update totals
    function updateTotals() {
        var totalKm = 0;
        var totalParking = 0;
        var totalToll = 0;
        var totalMeal = 0;
        
        // Get all entry rows
        var entryRows = document.querySelectorAll('.entry-row');
        
        // Loop through each row and sum up the values
        entryRows.forEach(function(row) {
            var kmInput = row.querySelector('.miles-traveled');
            var parkingInput = row.querySelector('.parking-fee');
            var tollInput = row.querySelector('.toll-fee');
            
            var km = parseFloat(kmInput.value) || 0;
            var parking = parseFloat(parkingInput.value) || 0;
            var toll = parseFloat(tollInput.value) || 0;
            
            totalKm += km;
            totalParking += parking;
            totalToll += toll;
        });
        
        // Get all meal entry rows
        var mealRows = document.querySelectorAll('.meal-entry-row');
        
        // Loop through each meal row and sum up the values
        mealRows.forEach(function(row) {
            var mealAmountInput = row.querySelector('.meal-amount');
            var mealAmount = parseFloat(mealAmountInput.value) || 0;
            totalMeal += mealAmount;
        });
        
        // Calculate mileage amount based on rates and position
        var kmAmount = 0;
        var vehicleType = document.getElementById('vehicleType').value;
        var userPosition = document.getElementById('userPosition').value;
        var isManager = (userPosition === 'Manager');
        
        if(vehicleType === "Car") {
            if(isManager) {
                // Manager Car rates: First 500km - RM1.00, subsequent - RM0.80
                if(totalKm <= 500) {
                    kmAmount = totalKm * 1.00;
                } else {
                    kmAmount = (500 * 1.00) + ((totalKm - 500) * 0.80);
                }
            } else {
                // Regular Car rates: First 500km - RM0.80, subsequent - RM0.50
                if(totalKm <= 500) {
                    kmAmount = totalKm * 0.80;
                } else {
                    kmAmount = (500 * 0.80) + ((totalKm - 500) * 0.50);
                }
            }
        } else if(vehicleType === "Motorcycle") {
            if(isManager) {
                // Manager Motorcycle rates: First 500km - RM0.80, subsequent - RM1.00
                if(totalKm <= 500) {
                    kmAmount = totalKm * 0.80;
                } else {
                    kmAmount = (500 * 0.80) + ((totalKm - 500) * 1.00);
                }
            } else {
                // Regular Motorcycle rate: Flat RM0.50
                kmAmount = totalKm * 0.50;
            }
        }
        
        var totalAmount = kmAmount + totalParking + totalToll + totalMeal;
        
        // Update display
        document.getElementById('totalKm').textContent = totalKm.toFixed(2);
        document.getElementById('totalKmAmount').textContent = kmAmount.toFixed(2);
        document.getElementById('totalParking').textContent = totalParking.toFixed(2);
        document.getElementById('totalToll').textContent = totalToll.toFixed(2);
        document.getElementById('totalMeal').textContent = totalMeal.toFixed(2);
        document.getElementById('totalAmount').textContent = totalAmount.toFixed(2);
    }
    
    // Add new travel entry row
    document.getElementById('addEntryBtn').addEventListener('click', function() {
        var entriesBody = document.getElementById('entriesBody');
        var newRow = document.createElement('tr');
        newRow.className = 'entry-row';
        newRow.innerHTML = `
            <td><input type="date" name="travel_date[]" class="form-control" required></td>
            <td><input type="text" name="travel_from[]" class="form-control" required></td>
            <td><input type="text" name="travel_to[]" class="form-control" required></td>
            <td><input type="text" name="purpose[]" class="form-control" required></td>
            <td><input type="number" name="parking_fee[]" class="form-control parking-fee" step="0.01" min="0" value="0"></td>
            <td><input type="number" name="toll_fee[]" class="form-control toll-fee" step="0.01" min="0" value="0"></td>
            <td><input type="number" name="miles_traveled[]" class="form-control miles-traveled" step="0.01" min="0" required></td>
            <td><button type="button" class="btn btn-danger btn-sm remove-entry"><i class="fas fa-trash"></i></button></td>
        `;
        entriesBody.appendChild(newRow);
        
        // Add event listeners to the new inputs
        var newInputs = newRow.querySelectorAll('input');
        newInputs.forEach(function(input) {
            input.addEventListener('input', updateTotals);
        });
        
        // Add event listener to the new remove button
        var removeBtn = newRow.querySelector('.remove-entry');
        removeBtn.addEventListener('click', function() {
            var entryRows = document.querySelectorAll('.entry-row');
            if (entryRows.length > 1) {
                this.closest('tr').remove();
                updateTotals();
            } else {
                alert("You must have at least one travel entry.");
            }
        });
        
        updateTotals();
    });
    
    // Add new meal entry row
    document.getElementById('addMealEntryBtn').addEventListener('click', function() {
        var mealEntriesBody = document.getElementById('mealEntriesBody');
        var newRow = document.createElement('tr');
        newRow.className = 'meal-entry-row';
        newRow.innerHTML = `
            <td><input type="date" name="meal_date[]" class="form-control" required></td>
            <td>
                <select name="meal_type[]" class="form-control" required>
                    <option value="">Select Meal Type</option>
                    <?php foreach($meal_types as $type): ?>
                        <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td><input type="text" name="meal_description[]" class="form-control" placeholder="e.g., Lunch with client" required></td>
            <td><input type="number" name="meal_amount[]" class="form-control meal-amount" step="0.01" min="0" value="0" required></td>
            <td><input type="text" name="receipt_reference[]" class="form-control" placeholder="Receipt #"></td>
            <td><button type="button" class="btn btn-danger btn-sm remove-meal-entry"><i class="fas fa-trash"></i></button></td>
        `;
        mealEntriesBody.appendChild(newRow);
        
        // Add event listeners to the new inputs
        var newInputs = newRow.querySelectorAll('input');
        newInputs.forEach(function(input) {
            input.addEventListener('input', updateTotals);
        });
        
        // Add event listener to the new remove button
        var removeBtn = newRow.querySelector('.remove-meal-entry');
        removeBtn.addEventListener('click', function() {
            this.closest('tr').remove();
            updateTotals();
        });
        
        updateTotals();
    });
    
    // Remove travel entry row - delegate to parent since rows can be dynamically added
    document.getElementById('entriesBody').addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-entry') || e.target.parentElement.classList.contains('remove-entry')) {
            var button = e.target.classList.contains('remove-entry') ? e.target : e.target.parentElement;
            var entryRows = document.querySelectorAll('.entry-row');
            if (entryRows.length > 1) {
                button.closest('tr').remove();
                updateTotals();
            } else {
                alert("You must have at least one travel entry.");
            }
        }
    });
    
    // Remove meal entry row - delegate to parent since rows can be dynamically added
    document.getElementById('mealEntriesBody').addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-meal-entry') || e.target.parentElement.classList.contains('remove-meal-entry')) {
            var button = e.target.classList.contains('remove-meal-entry') ? e.target : e.target.parentElement;
            button.closest('tr').remove();
            updateTotals();
        }
    });
    
    // Add input event listeners to all existing inputs
    var allInputs = document.querySelectorAll('.miles-traveled, .parking-fee, .toll-fee, .meal-amount');
    allInputs.forEach(function(input) {
        input.addEventListener('input', updateTotals);
    });
    
    // Update when vehicle type changes
    document.getElementById('vehicleType').addEventListener('change', updateTotals);
    
    // Initialize totals on page load
    updateTotals();
    
    // Debug log to check if script is running
    console.log('Reimbursement calculator script initialized');
});
</script>

<?php
// Include footer
include_once $basePath . "includes/footer.php";
ob_end_flush();
?>
