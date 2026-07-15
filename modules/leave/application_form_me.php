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

// For staff's selection list.
$staff_id = $_SESSION['staff_id'];

try {
    $staff = $pdo->query("SELECT staff_id, full_name, department FROM staff WHERE staff_id = $staff_id")->fetch();
} catch(PDOException $e) {
    error_log("Error fetching staff list in application_form.php: " . $e->getMessage());
    $staff = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Leave Application - SOA Management System</title>

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
                        <h1>New Leave Application</h1>
                        <p>Submit a leave application</p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="view_me.php?id=<?= $_SESSION['staff_id'] ?>" class="date-picker-btn">
                        <i class="fas fa-arrow-left"></i>
                        Back
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
                    <p>Please enter a valid amount of leave days.</p>
                </div>
            </div>

            <!-- Application Form Card -->
            <div class="table-card" data-aos="fade-up" data-aos-delay="100">
                <div class="table-header">
                    <div class="table-title">
                        <h3>Leave Application</h3>
                    </div>
                </div>

                <form id="leaveForm" method="POST" action="api/create_application.php" class="form-container">

                    <!-- Staff Information Section -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-user"></i>
                            <span>Staff Information</span>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Staff Name<span class="required">*</span></label>
                                <select name="staff_id" id="staff_id" class="form-input" required>
                                    <option value="<?php echo $staff['staff_id']; ?>" <?php echo ($staff['staff_id']) ? 'selected' : ''; ?> department="<?php echo htmlspecialchars($staff['department']); ?>">
                                        <?php echo htmlspecialchars($staff['full_name']); ?>
                                    </option>
                            </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Department</label>
                                <input type="text" class="form-input" id="department_input" value="<?php echo htmlspecialchars($staff['department']); ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Leave Details Section -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <span>Leave Details</span>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Leave Reason<span class="required">*</span></label>
                                <select name="leave_reason" id="leave_reason" class="form-input" required>
                                    <option value="">-- Select Reason --</option>
                                    <option value="AL">Annual Leave</option>
                                    <option value="EL">Emergency Leave</option>
                                    <option value="ML">Medical Leave</option>
                                    <option value="OL">Outstation Leave</option>
                                    <option value="BL">Birthday Leave</option>
                                    <option value="CL">Carryforward Leave</option>
                                    <option value="CPL">Paternal Leave</option>
                                    <option value="CML">Maternal Leave</option>
                                    <option value="SML">Special Marriage Leave</option>
                                    <option value="SHL">Special Umrah/Haji Leave</option>
                                    <option value="HL">Hospitalization Leave</option>
                                    <option value="ILL">Leave In Lieu</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Leaves Available</label>
                                <span id="leave_amount" class="leave-amount-tag">N/A</span>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Start Date <span class="required">*</span></label>
                                <input type="date" name="start_date" id="start_date" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">End Date <span class="required">*</span></label>
                                <input type="date" name="end_date" id="end_date" class="form-input" required>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Total Days<span class="required">*</span></label>
                                <input type="number" name="total_day" id="total_day" class="form-input" min="0" placeholder="0">
                            </div>
                        </div>
                    </div>

                    <!-- Calculation Result -->
                    <div class="calculation-card" id="calculationResult" style="display:none">
                        <div class="calculation-header">
                            <i class="fas fa-calculator"></i>
                            <span>Calculation Summary</span>
                        </div>

                        <div class="calculation-body">
                            <div class="calculation-row">
                                <span class="calculation-label">Start Date:</span>
                                <span class="calculation-value" id="display_start">-</span>
                            </div>
                            <div class="calculation-row">
                                <span class="calculation-label">End Date:</span>
                                <span class="calculation-value" id="display_end">-</span>
                            </div>
                            <div class="calculation-row">
                                <span class="calculation-label">Leave Remaining:</span>
                                <span class="calculation-value" id="display_days">-</span>
                            </div>
                        </div>
                    </div>

                    <div id="display_info" class="alert alert-warning" style="display:none" data-aos="fade-up">
                        <div class="alert-content">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>
                                <strong>Insufficient Leave Balance</strong>
                                <p>Leave days requested exceed the amount of leave remaining. You must adjust your request to continue.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="window.location.href='view_me.php?id=<?php echo $_SESSION['staff_id'] ?>'">
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
            // Initialize AOS
            AOS.init({ duration: 800, easing: 'ease-in-out', once: true });

            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            const staffSelect = document.getElementById('staff_id');
            const infoDiv = document.getElementById('leave_amount');
            const leaveCalc = document.getElementById('display_days')
            const totalDayInput = document.getElementById('total_day');
            const leaveReasonSelect = document.getElementById('leave_reason');
            const startDateInfo = document.getElementById('display_start');
            const endDateInfo = document.getElementById('display_end');
            const insufficientInfo = document.getElementById('display_info');
            const calculationInfo = document.getElementById('calculationResult');
            // 1. Function to fetch data and update the balance display
            function updateLeaveBalance() {
                const staffId = staffSelect.value;
                const requestedDays = parseInt(totalDayInput.value) || 0;

                if (!staffId) {
                    infoDiv.innerHTML = "0";
                    return;
                }

                fetch(`api/get_availability_data.php?staff_id=${staffId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data) { 
                            
                            // 1. The Mapping Object (Easy to add more leave types here!)
                            const leaveMap = {
                                "AL": { key: "annual_leave", label: "Annual" },
                                "EL": { key: "emergency_leave", label: "Emergency" },
                                "ML": { key: "medical_leave", label: "Medical" },
                                "OL": { key: "outstation_leave", label: "Outstation" },
                                "BL": { key: "birthday_leave", label: "Birthday" },
                                "CL": { key: "carryforward_leave", label: "Carryforward" },
                                "CPL": { key: "paternal_leave", label: "Paternal" },
                                "CML": { key: "maternal_leave", label: "Maternal" },
                                "SML": { key: "marriage_leave", label: "Marriage" },
                                "SHL": { key: "umrah_haji_leave", label: "Umrah/Haji" },
                                "HL": { key: "hospitalization_leave", label: "Hospitalization" },
                                "ILL": { key: "in_lieu_leave", label: "In Lieu" }
                            };

                            // 2. Look up the selected leave type
                            const config = leaveMap[leaveReasonSelect.value];
                            let remaining = 0;

                            // 3. Calculate and update if a valid type was selected
                            if (config) {
                                // It uses the config.key to dynamically grab the right data property
                                remaining = data[config.key] - requestedDays;
                                leaveCalc.textContent = `${remaining} (${config.label})`;
                            }

                            // 4. Update the summary UI
                            infoDiv.innerHTML = 
                                `Annual: ${data.annual_leave} | 
                                Emergency: ${data.emergency_leave} | 
                                Medical: ${data.medical_leave} | 
                                Outstation: ${data.outstation_leave} |
                                Birthday: ${data.birthday_leave} |
                                Carryforward: ${data.carryforward_leave}<br>
                                Paternal: ${data.paternal_leave} | 
                                Maternal: ${data.maternal_leave} | 
                                Marriage: ${data.marriage_leave} |
                                Umrah/Haji: ${data.umrah_haji_leave} |
                                Hospitalization: ${data.hospitalization_leave} | 
                                In Lieu: ${data.in_lieu_leave}`;

                            // 5. Update Visibility
                            if (staffSelect) {
                                calculationInfo.style.display = "block";
                                
                                // We can now use the 'remaining' variable directly 
                                // instead of parsing 'leaveCalc.textContent' again!
                                if (remaining < 0) {
                                    insufficientInfo.style.display = "block";
                                } else {
                                    insufficientInfo.style.display = "none";
                                }
                            } else {
                                calculationInfo.style.display = "none";
                                insufficientInfo.style.display = "none";
                            }
                        }
                    })
                    .catch(err => console.error('Error fetching availability:', err));
            }

            // 2. Date Calculation Logic
            function calculateDays() {
                const startD = new Date(startDateInput.value);
                const endD = new Date(endDateInput.value);

                if (startDateInput.value && endDateInput.value) {
                    const diffTime = endD - startD;
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;

                    if (diffDays < 0) {
                        alert('End date cannot be earlier than start date!');
                        endDateInput.value = '';
                        return;
                    }
                    
                    totalDayInput.placeholder = diffDays;
                }
            }

            // 3. Event Listeners
            startDateInput.addEventListener('change', function() {
                endDateInput.setAttribute('min', this.value);
                if (endDateInput.value && endDateInput.value < this.value) {
                    endDateInput.value = this.value;
                }
                calculateDays();
                const selectedDate = this.value;
                if (selectedDate) {
                    const dateObj = new Date(selectedDate);
                    
                    const formattedDate = dateObj.toLocaleDateString('en-MY', {
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric'
                    });

                    startDateInfo.textContent = formattedDate;
                } else {
                    startDateInfo.textContent = "-";
                }
            });

            endDateInput.addEventListener('change', function() {
                calculateDays();                    
                const selectedDate = this.value;
                if (selectedDate) {
                    const dateObj = new Date(selectedDate);
                    
                    const formattedDate = dateObj.toLocaleDateString('en-MY', {
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric'
                    });

                    endDateInfo.textContent = formattedDate;
                } else {
                    endDateInfo.textContent = "-";
                }

            });
            totalDayInput.addEventListener('input', updateLeaveBalance);
            leaveReasonSelect.addEventListener('change',updateLeaveBalance);
            staffSelect.addEventListener('change', function() {
                // Update department immediately
                const selectedOption = this.options[this.selectedIndex];
                document.getElementById('department_input').value = selectedOption.getAttribute('department') || 'N/A';
                
                // Fetch and calculate balance
                updateLeaveBalance();
            });
            // Form submission
            document.getElementById('leaveForm').addEventListener('submit', function(e) {
                e.preventDefault();

                // 1. Check for insufficient balance before submitting
                const remainingText = leaveCalc.textContent;
                const remainingValue = parseInt(remainingText);

                if (remainingValue < 0) {
                    alert("Error: Insufficient leave balance. You cannot apply for more days than available.");
                    return;
                }

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
                        window.location.href = 'view_me.php?id=<?php echo $staff_id; ?>&success=created';
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
                });
            });

            // Initial fetch to show current balances immediately on load
            if (staffSelect && staffSelect.value) {
                updateLeaveBalance();
            }
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
        .leave-amount-tag {
            display: inline-block; 
            width: max-content;    
            min-width: 40px;       
            text-align: center;
            font-size: 0.75rem;
            color: var(--gray-700);
            padding: 0.25rem 0.75rem;
            background: var(--gray-100);
            border-radius: 4px;
            font-weight: 600;
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
