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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Outstation Application - SOA Management System</title>

    <!-- Modern CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/modern-dashboard.css">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Animation -->
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
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
                        <h1>New Outstation Application</h1>
                        <p>Submit your outstation leave request</p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="index.php" class="date-picker-btn">
                        <i class="fas fa-arrow-left"></i>
                        Back to List
                    </a>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Application Info Card -->
            <div class="info-card" data-aos="fade-up">
                <div class="info-card-icon">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="info-card-content">
                    <h4>Application Guidelines</h4>
                    <p>Please complete this form before proceeding with your outstation trip. If your stay is for <strong>two nights or more</strong>, you will be eligible to claim outstation leave allowance upon return.</p>
                </div>
            </div>

            <!-- Application Form Card -->
            <div class="table-card" data-aos="fade-up" data-aos-delay="100">
                <div class="table-header">
                    <div class="table-title">
                        <h3>Outstation Leave Application</h3>
                        <p>Application No: <strong class="app-number-text"><?php echo $app_number; ?></strong></p>
                    </div>
                </div>

                <form id="outstationForm" method="POST" action="api/create_application.php" class="form-container">
                    <input type="hidden" name="application_number" value="<?php echo $app_number; ?>">
                    <input type="hidden" name="staff_id" value="<?php echo $staff_id; ?>">
                    <input type="hidden" name="total_nights" id="total_nights" value="0">
                    <input type="hidden" name="is_claimable" id="is_claimable" value="0">

                    <!-- Staff Information Section -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-user"></i>
                            <span>Staff Information</span>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Staff Name</label>
                                <input type="text" class="form-input" value="<?php echo htmlspecialchars($staff_name); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Department</label>
                                <input type="text" class="form-input" value="<?php echo htmlspecialchars($staff['department'] ?? 'N/A'); ?>" disabled>
                            </div>
                        </div>
                    </div>

                    <!-- Trip Details Section -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-plane-departure"></i>
                            <span>Trip Details</span>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Purpose of Trip <span class="required">*</span></label>
                                <select name="purpose" id="purpose" class="form-input" required>
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
                                <label class="form-label">Destination <span class="required">*</span></label>
                                <input type="text" name="destination" id="destination" class="form-input" placeholder="e.g., Kuala Lumpur, Johor Bahru" required>
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label class="form-label">Purpose Details / Activities <span class="required">*</span></label>
                            <textarea name="purpose_details" id="purpose_details" class="form-input form-textarea" placeholder="Please explain the purpose of your trip and describe the activities you will be doing..." required></textarea>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Departure Date <span class="required">*</span></label>
                                <input type="date" name="departure_date" id="departure_date" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Departure Time</label>
                                <input type="time" name="departure_time" id="departure_time" class="form-input">
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Return Date <span class="required">*</span></label>
                                <input type="date" name="return_date" id="return_date" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Return Time</label>
                                <input type="time" name="return_time" id="return_time" class="form-input">
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Transportation Mode <span class="required">*</span></label>
                                <select name="transportation_mode" id="transportation_mode" class="form-input" required>
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
                                <label class="form-label">Estimated Cost (RM)</label>
                                <input type="number" name="estimated_cost" id="estimated_cost" class="form-input" step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label class="form-label">Accommodation Details</label>
                            <textarea name="accommodation_details" id="accommodation_details" class="form-input form-textarea" placeholder="Enter hotel name, address, or accommodation details..."></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label class="form-label">Additional Remarks</label>
                            <textarea name="remarks" id="remarks" class="form-input form-textarea" placeholder="Any additional information..."></textarea>
                        </div>
                    </div>

                    <!-- Calculation Result -->
                    <div class="calculation-card" id="calculationResult" style="display: none;">
                        <div class="calculation-header">
                            <i class="fas fa-calculator"></i>
                            <span>Calculation Summary</span>
                        </div>

                        <div class="calculation-body">
                            <div class="calculation-row">
                                <span class="calculation-label">Departure Date:</span>
                                <span class="calculation-value" id="display_departure">-</span>
                            </div>
                            <div class="calculation-row">
                                <span class="calculation-label">Return Date:</span>
                                <span class="calculation-value" id="display_return">-</span>
                            </div>
                            <div class="calculation-row">
                                <span class="calculation-label">Total Nights:</span>
                                <span class="calculation-value" id="display_nights">0</span>
                            </div>
                            <div class="calculation-row highlight">
                                <span class="calculation-label">Claimable Status:</span>
                                <span class="calculation-value" id="display_claimable">
                                    <span class="claimable-badge no"><i class="fas fa-times"></i> Not Claimable</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Claimable Info Messages -->
                    <div id="claimableInfo" class="alert alert-success" style="display: none;" data-aos="fade-up">
                        <div class="alert-content">
                            <i class="fas fa-check-circle"></i>
                            <div>
                                <strong>Eligible for Outstation Leave Claim</strong>
                                <p>Your trip qualifies for outstation leave allowance. You can submit a claim after completing your trip.</p>
                            </div>
                        </div>
                    </div>

                    <div id="notClaimableInfo" class="alert alert-warning" style="display: none;" data-aos="fade-up">
                        <div class="alert-content">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>
                                <strong>Not Eligible for Claim</strong>
                                <p>Trips with less than 2 nights stay are not eligible for outstation leave claims. This application will be recorded for tracking purposes only.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="window.location.href='index.php'">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary" id="submitBtn">
                            <i class="fas fa-paper-plane"></i>
                            Submit Application
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="../../assets/js/modern-dashboard.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize AOS animations
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true
            });

            // Initialize dashboard
            initializeDashboard();

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
                document.getElementById('is_claimable').value = diffDays >= 2 ? '1' : '0';

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
                const isClaimable = nights >= 2;
                const claimableDisplay = document.getElementById('display_claimable');

                if (isClaimable) {
                    claimableDisplay.innerHTML = '<span class="claimable-badge yes"><i class="fas fa-check"></i> Claimable</span>';
                    claimableInfo.style.display = 'block';
                    notClaimableInfo.style.display = 'none';
                } else {
                    claimableDisplay.innerHTML = '<span class="claimable-badge no"><i class="fas fa-times"></i> Not Claimable</span>';
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
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

                const formData = new FormData(this);

                fetch(this.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Application submitted successfully!');
                        window.location.href = 'index.php?success=created';
                    } else {
                        alert('Error: ' + data.message);
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Application';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Application';
                });
            });
        });
    </script>

    <style>
        /* Application Form Specific Styles */
        .info-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: flex-start;
            border-left: 4px solid var(--primary-color);
        }

        .info-card-icon {
            width: 40px;
            height: 40px;
            background: rgba(59, 130, 246, 0.1);
            border-radius: var(--border-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            flex-shrink: 0;
        }

        .info-card-content h4 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }

        .info-card-content p {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin: 0;
            line-height: 1.5;
        }

        .app-number-text {
            font-family: 'Courier New', monospace;
            color: var(--primary-color);
        }

        .form-container {
            padding: 1.5rem;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--gray-100);
        }

        .form-section-title i {
            color: var(--primary-color);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.25rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        .form-label .required {
            color: var(--danger-color);
            margin-left: 2px;
        }

        .form-input {
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius-sm);
            font-size: 0.875rem;
            color: var(--gray-900);
            background: white;
            transition: var(--transition);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-input:disabled {
            background: var(--gray-100);
            color: var(--gray-600);
            cursor: not-allowed;
        }

        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }

        select.form-input {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        /* Calculation Card */
        .calculation-card {
            background: var(--gray-50);
            border: 2px solid var(--primary-color);
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .calculation-header {
            background: var(--primary-color);
            color: white;
            padding: 1rem 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .calculation-body {
            padding: 1.5rem;
        }

        .calculation-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .calculation-row:last-child {
            border-bottom: none;
        }

        .calculation-row.highlight {
            padding-top: 1rem;
            margin-top: 0.5rem;
        }

        .calculation-label {
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        .calculation-value {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--gray-900);
        }

        .claimable-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .claimable-badge.yes {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .claimable-badge.no {
            background: rgba(107, 114, 128, 0.1);
            color: var(--gray-500);
        }

        /* Alert Styles */
        .alert {
            display: flex;
            align-items: flex-start;
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .alert-content {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }

        .alert-content i {
            font-size: 1.25rem;
            flex-shrink: 0;
            margin-top: 0.125rem;
        }

        .alert-success .alert-content i {
            color: var(--success-color);
        }

        .alert-warning .alert-content i {
            color: var(--warning-color);
        }

        .alert-content strong {
            display: block;
            font-size: 0.875rem;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .alert-content p {
            font-size: 0.8125rem;
            color: var(--gray-600);
            margin: 0;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-200);
        }

        .btn-primary,
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius-sm);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: var(--gray-200);
            color: var(--gray-700);
        }

        .btn-secondary:hover {
            background: var(--gray-300);
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
                justify-content: center;
            }

            .info-card {
                flex-direction: column;
            }

            .alert-content {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</body>
</html>
