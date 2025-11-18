<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: ../modules/auth/login.php");
    exit();
}

$staff_id = $_SESSION['staff_id'];
$staff_name = $_SESSION['full_name'] ?? 'User';

// Get staff details
try {
    $stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
    $stmt->execute([$staff_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Generate application number
$app_number = 'OSL-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

$page_title = "Outstation Leave Application";
include '../includes/header.php';
?>

<style>
.outstation-form-container {
    max-width: 1000px;
    margin: 20px auto;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 30px;
}

.form-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 3px solid #4e73df;
}

.form-header h2 {
    color: #2c3e50;
    margin-bottom: 5px;
}

.form-header .app-number {
    color: #6c757d;
    font-size: 14px;
}

.form-section {
    margin-bottom: 30px;
}

.form-section-title {
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e3e6f0;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 20px;
}

.form-row.single {
    grid-template-columns: 1fr;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 8px;
    font-size: 14px;
}

.form-group label .required {
    color: #e74a3b;
    margin-left: 3px;
}

.form-control {
    padding: 10px 15px;
    border: 1px solid #d1d3e2;
    border-radius: 5px;
    font-size: 14px;
    transition: border-color 0.15s ease-in-out;
}

.form-control:focus {
    outline: none;
    border-color: #4e73df;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

.form-control:disabled {
    background-color: #e9ecef;
}

select.form-control {
    cursor: pointer;
}

textarea.form-control {
    min-height: 100px;
    resize: vertical;
}

.info-box {
    background: #e7f3ff;
    border-left: 4px solid #4e73df;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.info-box.success {
    background: #d4edda;
    border-left-color: #28a745;
}

.info-box.warning {
    background: #fff3cd;
    border-left-color: #ffc107;
}

.info-box.error {
    background: #f8d7da;
    border-left-color: #dc3545;
}

.info-box .info-title {
    font-weight: 600;
    margin-bottom: 5px;
    color: #2c3e50;
}

.info-box .info-text {
    font-size: 14px;
    color: #6c757d;
    margin: 0;
}

.calculation-result {
    background: #f8f9fc;
    border: 2px solid #4e73df;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.calculation-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #e3e6f0;
}

.calculation-item:last-child {
    border-bottom: none;
    font-weight: 600;
    font-size: 16px;
}

.calculation-label {
    color: #5a5c69;
}

.calculation-value {
    color: #2c3e50;
    font-weight: 500;
}

.claimable-badge {
    display: inline-block;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}

.claimable-badge.yes {
    background: #d4edda;
    color: #155724;
}

.claimable-badge.no {
    background: #f8d7da;
    color: #721c24;
}

.btn-group {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 2px solid #e3e6f0;
}

.btn {
    padding: 12px 30px;
    border: none;
    border-radius: 5px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #4e73df;
    color: white;
}

.btn-primary:hover {
    background: #2e59d9;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(78, 115, 223, 0.3);
}

.btn-secondary {
    background: #858796;
    color: white;
}

.btn-secondary:hover {
    background: #6c757d;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .btn-group {
        flex-direction: column;
    }
}
</style>

<div class="outstation-form-container">
    <div class="form-header">
        <h2>Outstation Leave Application</h2>
        <div class="app-number">Application No: <strong id="applicationNumber"><?php echo $app_number; ?></strong></div>
    </div>

    <div class="info-box">
        <div class="info-title">📋 Application Guidelines</div>
        <p class="info-text">Please complete this form before proceeding with your outstation trip. If your stay is for <strong>one night or more</strong>, you will be eligible to claim outstation leave allowance upon return.</p>
    </div>

    <form id="outstationForm" method="POST" action="../modules/outstation/api/create_application.php">
        <input type="hidden" name="application_number" value="<?php echo $app_number; ?>">
        <input type="hidden" name="staff_id" value="<?php echo $staff_id; ?>">
        <input type="hidden" name="total_nights" id="total_nights" value="0">
        <input type="hidden" name="is_claimable" id="is_claimable" value="0">

        <!-- Staff Information -->
        <div class="form-section">
            <div class="form-section-title">📝 Staff Information</div>
            <div class="form-row">
                <div class="form-group">
                    <label>Staff Name</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($staff_name); ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($staff['department'] ?? 'N/A'); ?>" disabled>
                </div>
            </div>
        </div>

        <!-- Trip Details -->
        <div class="form-section">
            <div class="form-section-title">✈️ Trip Details</div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Purpose of Trip <span class="required">*</span></label>
                    <select name="purpose" id="purpose" class="form-control" required>
                        <option value="">-- Select Purpose --</option>
                        <option value="Client Meeting">Client Meeting</option>
                        <option value="Site Visit">Site Visit</option>
                        <option value="Training/Workshop">Training/Workshop</option>
                        <option value="Business Conference">Business Conference</option>
                        <option value="Project Work">Project Work</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Destination <span class="required">*</span></label>
                    <input type="text" name="destination" id="destination" class="form-control" placeholder="e.g., Kuala Lumpur, Johor Bahru" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Departure Date <span class="required">*</span></label>
                    <input type="date" name="departure_date" id="departure_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Departure Time</label>
                    <input type="time" name="departure_time" id="departure_time" class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Return Date <span class="required">*</span></label>
                    <input type="date" name="return_date" id="return_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Return Time</label>
                    <input type="time" name="return_time" id="return_time" class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Transportation Mode <span class="required">*</span></label>
                    <select name="transportation_mode" id="transportation_mode" class="form-control" required>
                        <option value="">-- Select Transportation --</option>
                        <option value="Company Vehicle">Company Vehicle</option>
                        <option value="Personal Vehicle">Personal Vehicle</option>
                        <option value="Flight">Flight</option>
                        <option value="Train">Train</option>
                        <option value="Bus">Bus</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estimated Cost (RM)</label>
                    <input type="number" name="estimated_cost" id="estimated_cost" class="form-control" step="0.01" min="0" placeholder="0.00">
                </div>
            </div>

            <div class="form-row single">
                <div class="form-group">
                    <label>Accommodation Details</label>
                    <textarea name="accommodation_details" id="accommodation_details" class="form-control" placeholder="Enter hotel name, address, or accommodation details..."></textarea>
                </div>
            </div>

            <div class="form-row single">
                <div class="form-group">
                    <label>Additional Remarks</label>
                    <textarea name="remarks" id="remarks" class="form-control" placeholder="Any additional information..."></textarea>
                </div>
            </div>
        </div>

        <!-- Calculation Result -->
        <div class="calculation-result" id="calculationResult" style="display: none;">
            <div class="form-section-title" style="margin-bottom: 15px;">📊 Calculation Summary</div>
            
            <div class="calculation-item">
                <span class="calculation-label">Departure Date:</span>
                <span class="calculation-value" id="display_departure">-</span>
            </div>
            <div class="calculation-item">
                <span class="calculation-label">Return Date:</span>
                <span class="calculation-value" id="display_return">-</span>
            </div>
            <div class="calculation-item">
                <span class="calculation-label">Total Nights:</span>
                <span class="calculation-value" id="display_nights">0</span>
            </div>
            <div class="calculation-item">
                <span class="calculation-label">Claimable Status:</span>
                <span class="calculation-value" id="display_claimable">
                    <span class="claimable-badge no">Not Claimable</span>
                </span>
            </div>
        </div>

        <div id="claimableInfo" class="info-box success" style="display: none;">
            <div class="info-title">✅ Eligible for Outstation Leave Claim</div>
            <p class="info-text">Your trip qualifies for outstation leave allowance. You can submit a claim after completing your trip.</p>
        </div>

        <div id="notClaimableInfo" class="info-box warning" style="display: none;">
            <div class="info-title">⚠️ Not Eligible for Claim</div>
            <p class="info-text">Trips with less than 1 night stay are not eligible for outstation leave claims. This application will be recorded for tracking purposes only.</p>
        </div>

        <!-- Submit Buttons -->
        <div class="btn-group">
            <button type="button" class="btn btn-secondary" onclick="window.location.href='view_applications.php'">Cancel</button>
            <button type="submit" class="btn btn-primary" id="submitBtn">Submit Application</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const departureDateInput = document.getElementById('departure_date');
    const returnDateInput = document.getElementById('return_date');
    
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    departureDateInput.setAttribute('min', today);
    
    // Update return date minimum when departure date changes
    departureDateInput.addEventListener('change', function() {
        returnDateInput.setAttribute('min', this.value);
        if (returnDateInput.value && returnDateInput.value < this.value) {
            returnDateInput.value = this.value;
        }
        calculateNights();
    });
    
    returnDateInput.addEventListener('change', calculateNights);
    
    function calculateNights() {
        const departureDate = departureDateInput.value;
        const returnDate = returnDateInput.value;
        
        if (!departureDate || !returnDate) {
            hideCalculation();
            return;
        }
        
        const departure = new Date(departureDate);
        const returnD = new Date(returnDate);
        
        // Calculate difference in days
        const diffTime = returnD - departure;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays < 0) {
            hideCalculation();
            alert('Return date cannot be earlier than departure date!');
            returnDateInput.value = '';
            return;
        }
        
        // Update hidden fields
        document.getElementById('total_nights').value = diffDays;
        document.getElementById('is_claimable').value = diffDays >= 1 ? '1' : '0';
        
        // Update display
        showCalculation(departureDate, returnDate, diffDays);
    }
    
    function showCalculation(departure, returnDate, nights) {
        const calculationResult = document.getElementById('calculationResult');
        const claimableInfo = document.getElementById('claimableInfo');
        const notClaimableInfo = document.getElementById('notClaimableInfo');
        
        // Format dates for display
        const departureFormatted = new Date(departure).toLocaleDateString('en-MY', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        const returnFormatted = new Date(returnDate).toLocaleDateString('en-MY', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        
        // Update display values
        document.getElementById('display_departure').textContent = departureFormatted;
        document.getElementById('display_return').textContent = returnFormatted;
        document.getElementById('display_nights').textContent = nights + ' night' + (nights !== 1 ? 's' : '');
        
        // Update claimable status
        const isClaimable = nights >= 1;
        const claimableDisplay = document.getElementById('display_claimable');
        
        if (isClaimable) {
            claimableDisplay.innerHTML = '<span class="claimable-badge yes">✓ Claimable</span>';
            claimableInfo.style.display = 'block';
            notClaimableInfo.style.display = 'none';
        } else {
            claimableDisplay.innerHTML = '<span class="claimable-badge no">✗ Not Claimable</span>';
            claimableInfo.style.display = 'none';
            notClaimableInfo.style.display = 'block';
        }
        
        calculationResult.style.display = 'block';
    }
    
    function hideCalculation() {
        document.getElementById('calculationResult').style.display = 'none';
        document.getElementById('claimableInfo').style.display = 'none';
        document.getElementById('notClaimableInfo').style.display = 'none';
    }
    
    // Form submission
    document.getElementById('outstationForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';
        
        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Application submitted successfully!');
                window.location.href = 'view_applications.php';
            } else {
                alert('Error: ' + data.message);
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Application';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Application';
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>