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
    
    if(empty($entries)){
        $entries_err = "Please add at least one travel entry.";
    }
    
    // Calculate total amount
    $total_km = 0;
    $total_parking = 0;
    $total_toll = 0;
    
    foreach($entries as $entry){
        $total_km += floatval($entry['miles_traveled']);
        $total_parking += floatval($entry['parking_fee']);
        $total_toll += floatval($entry['toll_fee']);
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
    
    $total_amount = $km_amount + $total_parking + $total_toll;
    
    // Check input errors before updating the database
    if(empty($vehicle_type_err) && empty($entries_err)){
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Prepare an update statement
            $sql = "UPDATE claims SET claim_month = :claim_month, vehicle_type = :vehicle_type, description = :description, 
                    amount = :amount, km_rate = :km_rate, total_km_amount = :total_km_amount WHERE claim_id = :id";
            
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
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Edit Mileage Reimbursement Claim</h1>
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
            <h6 class="m-0 font-weight-bold text-primary">Mileage Reimbursement Form</h6>
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
                
                <h5 class="font-weight-bold">Travel Details</h5>
                <?php if(!empty($entries_err)): ?>
                    <div class="alert alert-danger"><?php echo $entries_err; ?></div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-bordered" id="travelEntriesTable">
                    <thead class="bg-primary" style="color: #004085;">

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
                                        <i class="fas fa-plus"></i> Add Entry
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
                    <i class="fas fa-info-circle"></i> By submitting this form, you certify that the mileage reimbursement claimed are proper and actual mileages and fees incurred during this period and in accordance with the company's Mileage Reimbursement Policy.
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
        
        var totalAmount = kmAmount + totalParking + totalToll;
        
        // Update display
        document.getElementById('totalKm').textContent = totalKm.toFixed(2);
        document.getElementById('totalKmAmount').textContent = kmAmount.toFixed(2);
        document.getElementById('totalParking').textContent = totalParking.toFixed(2);
        document.getElementById('totalToll').textContent = totalToll.toFixed(2);
        document.getElementById('totalAmount').textContent = totalAmount.toFixed(2);
    }
    
    // Add new entry row
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
    
    // Remove entry row - delegate to parent since rows can be dynamically added
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
    
    // Add input event listeners to all existing inputs
    var allInputs = document.querySelectorAll('.miles-traveled, .parking-fee, .toll-fee');
    allInputs.forEach(function(input) {
        input.addEventListener('input', updateTotals);
    });
    
    // Update when vehicle type changes
    document.getElementById('vehicleType').addEventListener('change', updateTotals);
    
    // Initialize totals on page load
    updateTotals();
    
    // Debug log to check if script is running
    console.log('Mileage calculator script initialized');
});
</script>

<?php
// Include footer
include_once $basePath . "includes/footer.php";
ob_end_flush();
?>
