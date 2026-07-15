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

// Check permissions - strictly Admin and Manager only
if (!isset($_SESSION['position']) || !in_array($_SESSION['position'], ['Admin', 'Manager'])) {
    header("location: " . $basePath . "modules/dashboard/index.php?error=unauthorized");
    exit;
}

$is_admin_or_manager = true;


if(isset($_GET["action"]) && $_GET["action"] == "delete" && !empty($_GET["id"])){
    try {
        $application_id = intval($_GET["id"]);

        if($is_admin_or_manager){
            $pdo->beginTransaction();

            // Fetch the application details
            $fetch_sql = "SELECT staff_id, leave_reason, total_day FROM leave_application WHERE application_id = :id";
            $fetch_stmt = $pdo->prepare($fetch_sql);
            $fetch_stmt->execute([':id' => $application_id]);
            $application = $fetch_stmt->fetch(PDO::FETCH_ASSOC);

            if($application) {
                $staff_id = $application['staff_id'];
                $reason = $application['leave_reason'];
                $days_return = $application['total_day'];

                $column_map = [
                    'AL' => "annual_leave",
                    'EL' => "emergency_leave",
                    'ML' => "medical_leave",
                    'OL' => "outstation_leave",
                    'BL' => "birthday_leave",
                    'CL' => "carryforward_leave",
                    'CPL' => "paternal_leave",
                    'CML' => "maternal_leave",
                    'SML' => "marriage_leave",
                    'SHL' => "umrah_haji_leave",
                    'HL' => "hospitalization_leave",
                    'ILL' => "in_lieu_leave"
                ];

                // Validate mapping
                if(!isset($column_map[$reason])) {
                    throw new Exception("Mapping failed: Reason '$reason' not found in column map.");
                }

                $column_name = $column_map[$reason];

                // Update the balance
                $update_sql = "UPDATE leave_availability 
                               SET $column_name = $column_name + :days 
                               WHERE staff_id = :staff_id";
                
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute([
                    ':days'     => $days_return,
                    ':staff_id' => $staff_id
                ]);

                // Check if the staff_id actually existed in leave_availability
                if($update_stmt->rowCount() === 0) {
                    throw new Exception("No availability record found for Staff ID: $staff_id");
                }

                //Once, days has been sent back, this deletes it permanently.
                $delete_sql = "DELETE FROM leave_application WHERE application_id = :id";
                $delete_stmt = $pdo->prepare($delete_sql);
                $delete_stmt->execute([':id' => $application_id]);

                $pdo->commit();
                header("location: view.php?id=" . $staff_id . "&success=deleted");
                exit();
            } else {
                $delete_err = "Record not found.";
            }
        }
    } catch(Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Deletion Error in view.php: " . $e->getMessage());
        $delete_err = "System Error: A problem occurred during deletion.";
    }
}

// Handle Balance Adjustment
if(isset($_GET["action"]) && $_GET["action"] == "adjust" && !empty($_GET["id"]) && !empty($_GET["type"]) && isset($_GET["delta"]) && $is_admin_or_manager){
    try {
        $staff_id = intval($_GET["id"]);
        $type = $_GET["type"];
        $delta = intval($_GET["delta"]);

        // Whitelist the column names
        $allowed_types = ['annual_leave', 'carryforward_leave', 'in_lieu_leave'];
        
        if(in_array($type, $allowed_types)){
            // Check current balance if delta is negative
            if ($delta < 0) {
                $check_sql = "SELECT $type FROM leave_availability WHERE staff_id = :staff_id";
                $check_stmt = $pdo->prepare($check_sql);
                $check_stmt->execute([':staff_id' => $staff_id]);
                $current_balance = $check_stmt->fetchColumn();

                if ($current_balance + $delta < 0) {
                    header("location: view.php?id=" . $staff_id . "&error=insufficient_balance");
                    exit();
                }
            }

            $sql = "UPDATE leave_availability SET $type = $type + :delta WHERE staff_id = :staff_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':delta' => $delta, ':staff_id' => $staff_id]);
            
            header("location: view.php?id=" . $staff_id . "&success=updated");
            exit();
        }
    } catch(Exception $e) {
        error_log("Adjustment Error in view.php: " . $e->getMessage());
        $delete_err = "Adjustment Error: A problem occurred during balance adjustment.";
    }
}


// Fetch leave availability & application records for a particular staff
if (isset($_GET['id'])) {
    $staff_id = intval($_GET['id']);

    // Fetch Leave Balances
    $stmt = $pdo->prepare("SELECT la.*, s.full_name as staff_name, s.department
            FROM leave_availability la
            LEFT JOIN staff s ON la.staff_id = s.staff_id
            WHERE la.staff_id = ?");
    $stmt->execute([$staff_id]);
    $availability = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch ALL Leave History
    $stmt_history = $pdo->prepare("SELECT * FROM leave_application WHERE staff_id = ? ORDER BY start_date DESC");
    $stmt_history->execute([$staff_id]);
    $history_list = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management - SOA Management System</title>

    <!-- Modern CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/modern-dashboard.css">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                        <h1>Leave Management</h1>
                        <p>Track and manage leave applications</p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="application_form.php" class="export-btn">
                        <i class="fas fa-plus"></i>
                        Record New Leave
                    </a>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Success/Error Messages -->
            <?php if(isset($_GET["success"])): ?>
                <div class="alert alert-success" data-aos="fade-down">
                    <div class="alert-content">
                        <i class="fas fa-check-circle"></i>
                        <span>
                            <?php
                            if($_GET["success"] == "deleted") {
                                echo "Application has been deleted successfully.";
                            } elseif($_GET["success"] == "updated") {
                                echo "Application has been updated successfully.";
                            } elseif($_GET["success"] == "created") {
                                echo "Application has been submitted successfully.";
                            } else {
                                echo "Operation completed successfully.";
                            }
                            ?>
                        </span>
                    </div>
                    <button class="alert-close" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if(isset($delete_err) || isset($_GET['error'])): ?>
                <div class="alert alert-error" data-aos="fade-down">
                    <div class="alert-content">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>
                            <?php 
                                if (isset($delete_err)) {
                                    echo $delete_err; 
                                } elseif (isset($_GET['error'])) {
                                    switch($_GET['error']) {
                                        case 'insufficient_balance':
                                            echo "Adjustment Error: Insufficient leave balance.";
                                            break;
                                        case 'unauthorized':
                                            echo "Unauthorized access.";
                                            break;
                                        default:
                                            echo "An error occurred.";
                                    }
                                }
                            ?>
                        </span>
                    </div>
                    <button class="alert-close" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>
        <!-- Remaining leave balance. -->
        <div class="table-card" data-aos="fade-up">
            <div class="table-header">
                <div class="table-title">
                    <h3><i class="fas fa-user"></i> <?php echo htmlspecialchars($availability['staff_name'] ?? 'N/A') ?> ( <?php echo htmlspecialchars($availability['department'] ?? 'N/A') ?> )</h3>
                    <p>Remaining leaves for the year ( Last Updated: <i class="far fa-clock"></i> <?php echo date('d M Y', strtotime($availability['updated_at'])); ?> )</p>
                </div>
                <div class="table-actions">
                    <button class="table-action-btn" onclick="refreshTable()" title="Refresh">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button class="table-action-btn" onclick="exportTable()" title="Export to Excel">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>
            <div class="table-container">
                <table class="modern-table" id="leaveTable">
                    <thead>
                        <tr>
                            <th class="text-center">Annual</th>
                            <th class="text-center">Carryforward</th>
                            <th class="text-center">Emergency</th>
                            <th class="text-center">Medical</th>
                            <th class="text-center">Outstation</th>
                            <th class="text-center">Birthday</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($availability)): ?>

                            <tr>
                                <td class="text-center">
                                    <div class="leave-control">
                                        <span class="purpose-tag" style="background: rgba(59, 130, 246, 0.2); color: var(--primary-color);"><?php echo $availability['annual_leave'] . ($availability['annual_leave'] > 1 ? ' days' : ' day');?></span>
                                        <?php if($is_admin_or_manager): ?>
                                            <div class="adj-btns">
                                                <button type="button" class="adj-btn" onclick="adjustBalance('<?php echo $staff_id; ?>', 'annual_leave', 1)" title="Increase"><i class="fas fa-plus"></i></button>
                                                <button type="button" class="adj-btn" onclick="adjustBalance('<?php echo $staff_id; ?>', 'annual_leave', -1)" title="Decrease"><i class="fas fa-minus"></i></button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="leave-control">
                                        <span class="purpose-tag" style="background: rgba(59, 130, 246, 0.2); color: var(--primary-color);"><?php echo $availability['carryforward_leave'] . ($availability['carryforward_leave'] > 1 ? ' days' : ' day');?></span>
                                        <?php if($is_admin_or_manager): ?>
                                            <div class="adj-btns">
                                                <button type="button" class="adj-btn" onclick="adjustBalance('<?php echo $staff_id; ?>', 'carryforward_leave', 1)" title="Increase"><i class="fas fa-plus"></i></button>
                                                <button type="button" class="adj-btn" onclick="adjustBalance('<?php echo $staff_id; ?>', 'carryforward_leave', -1)" title="Decrease"><i class="fas fa-minus"></i></button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="leave-control">
                                        <span class="purpose-tag" style="background: rgba(245, 158, 11, 0.2); color: var(--warning-color);"><?php echo $availability['emergency_leave'] . ($availability['emergency_leave'] > 1 ? ' days' : ' day'); ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="leave-control">
                                        <span class="purpose-tag" style="background: rgba(16, 185, 129, 0.2); color: var(--success-color);"><?php echo $availability['medical_leave'] . ($availability['medical_leave'] > 1 ? ' days' : ' day'); ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="leave-control">
                                        <span class="purpose-tag" style="background: rgba(6, 182, 212, 0.2); color: var(--info-color);"><?php echo $availability['outstation_leave'] . ($availability['outstation_leave'] > 1 ? ' days' : ' day'); ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="leave-control">
                                        <?php if($availability['birthday_leave'] > 0): ?>
                                            <span class="purpose-tag" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6;"><i class="fas fa-birthday-cake"></i> Available</span>
                                        <?php else: ?>
                                            <span class="purpose-tag">Used</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <tr style="background: var(--gray-50);padding: 1rem;text-align: left;font-size: 0.875rem;font-weight: 600;text-transform: uppercase;letter-spacing: 0.05em;border-bottom: 1px solid var(--gray-200);">
                                <td style="color: #494949;">Paternal</td>
                                <td style="color: #494949;">Maternal</td>
                                <td style="color: #494949;">Marriage</td>
                                <td style="color: #494949;">Umrah/Haji</td>
                                <td style="color: #494949;">Hospitalization</td>
                                <td style="color: #494949;">Leave in lieu</td>
                            </tr>
                            <tr>
                                <td class="text-center">
                                    <div class="leave-control">
                                        <span class="purpose-tag" style="background: rgba(50, 156, 255, 0.2); color: rgba(58, 134, 255, 1);"><?php echo $availability['paternal_leave'] . ($availability['paternal_leave'] > 1 ? ' days' : ' day'); ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="leave-control">
                                        <span class="purpose-tag" style="background: rgba(255, 54, 131, 0.2); color: rgba(232, 93, 174, 1);"><?php echo $availability['maternal_leave'] . ($availability['maternal_leave'] > 1 ? ' days' : ' day'); ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="leave-control">
                                        <span class="purpose-tag" style="background: rgba(0, 0, 0, 0.2); color: rgb(30, 30, 30);"><?php echo $availability['marriage_leave'] . ($availability['marriage_leave'] > 1 ? ' days' : ' day'); ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="leave-control">
                                        <span class="purpose-tag" style="background: rgba(50, 255, 70, 0.2); color: rgb(38, 181, 117);"><?php echo $availability['umrah_haji_leave'] . ($availability['umrah_haji_leave'] > 1 ? ' days' : ' day'); ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="leave-control">
                                        <span class="purpose-tag" style="background: rgba(255, 55, 55, 0.2); color: rgba(214, 40, 40, 1);"><?php echo $availability['hospitalization_leave'] . ($availability['hospitalization_leave'] > 1 ? ' days' : ' day'); ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="leave-control">
                                        <span class="purpose-tag" style="background: rgba(39, 216, 110, 0.2); color: rgb(23, 74, 119);"><?php echo $availability['in_lieu_leave'] . ($availability['in_lieu_leave'] > 1 ? ' days' : ' day'); ?></span>
                                        <?php if($is_admin_or_manager): ?>
                                            <div class="adj-btns">
                                                <button type="button" class="adj-btn" onclick="adjustBalance('<?php echo $staff_id; ?>', 'in_lieu_leave', 1)" title="Increase"><i class="fas fa-plus"></i></button>
                                                <button type="button" class="adj-btn" onclick="adjustBalance('<?php echo $staff_id; ?>', 'in_lieu_leave', -1)" title="Decrease"><i class="fas fa-minus"></i></button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center no-data">
                                <div class="no-data-content">
                                    <i class="fas fa-calendar-times"></i>
                                    <h3>No Records Found</h3>
                                    <p>Leave data has not been initialized for this user.</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?> 
                    </tbody>

                </table>
            </div>
        </div>
                        <!--All leave applications/history for the viewed staff -->
        <div class="table-card" data-aos="fade-up" style="margin-top: 1.0rem;">
            <div class="table-header">
                <div class="table-title">
                    <h3>Leave History</h3>
                    <p>A list of every recorded leaves taken by the staff</p>
                </div>
                <div class="table-actions">
                    <button class="table-action-btn" onclick="refreshTable()" title="Refresh">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button class="table-action-btn" onclick="exportTable()" title="Export to Excel">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>
            <div class="table-container">
                <table class="modern-table" id="leaveTable">
                    <thead>
                        <tr>
                            <th class="text-center">ID No</th>
                            <th class="text-center">Leave Reason</th>
                            <th class="text-center">Total Days</th>
                            <th class="text-center">Date</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($history_list)): ?>
                            <?php foreach($history_list as $history): ?>
                            <tr>
                                <td class="text-center">
                                    <span><?php echo $history['application_id']; ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="purpose-tag" style="<?php 
                                        switch ($history['leave_reason']) {
                                            case 'AL':
                                                $style = "background: rgba(59, 130, 246, 0.2); color: var(--primary-color);";
                                                $text = "Annual Leave";
                                                break;
                                            case 'EL':
                                                $style = "background: rgba(245, 158, 11, 0.2); color: var(--warning-color);";
                                                $text = "Emergency Leave";
                                                break;
                                            case 'ML':
                                                $style = "background: rgba(16, 185, 129, 0.2); color: var(--success-color);";
                                                $text = "Medical Leave";
                                                break;
                                            case 'OL':
                                                $style = "background: rgba(6, 182, 212, 0.2); color: var(--info-color);";
                                                $text = "Outstation Leave";
                                                break;
                                            case 'BL':
                                                $style = "background: rgba(139, 92, 246, 0.15); color: #8b5cf6;";
                                                $text = "Birthday Leave";
                                                break;
                                            case 'CL':
                                                $style = "background: rgba(59, 130, 246, 0.2); color: var(--primary-color);";
                                                $text = "Carryforward Leave";
                                                break;
                                            case 'CPL':
                                                $style = "background: rgba(50, 156, 255, 0.2); color: rgba(58, 134, 255, 1);";
                                                $text = "Compassionate Paternal Leave";
                                                break;
                                            case 'CML':
                                                $style = "background: rgba(255, 54, 131, 0.2); color: rgba(232, 93, 174, 1);";
                                                $text = "Compassionate Maternal Leave";
                                                break;
                                            case 'SML':
                                                $style = "background: rgba(0, 0, 0, 0.2); color: rgb(30, 30, 30);";
                                                $text = "Special Marriage Leave";
                                                break;
                                            case 'SHL':
                                                $style = "background: rgba(50, 255, 70, 0.2); color: rgb(38, 181, 117);";
                                                $text = "Special Umrah/Haji Leave";
                                                break;
                                            case 'HL':
                                                $style = "background: rgba(255, 55, 55, 0.2); color: rgba(214, 40, 40, 1);";
                                                $text = "Hospitalization Leave";
                                                break;
                                            case 'ILL':
                                                $style = "background: rgba(39, 216, 110, 0.2); color: rgb(23, 74, 119);";
                                                $text = "Leave In Lieu";
                                                break;
                                            default:
                                                $style = "background: rgba(156, 163, 175, 0.2); color: #6b7280;";
                                                $text = "Undefined";
                                                break;
                                        }
                                        echo $style;
                                    ?>">
                                        <?php echo $text; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span><?php echo $history['total_day'] . (($history['total_day']>1) ? ' days': ' day') ?></span>
                                </td>
                                     <td>
                                        <div class="date-range-display">
                                            <div class="date-item">
                                                <i class="fa-solid fa-calendar-days"></i>
                                                <?php echo date('d M Y', strtotime($history['start_date'])); ?> (Start)
                                            </div>
                                            <div class="date-item">
                                                <i class="fa-solid fa-calendar-check"></i>
                                                <?php echo date('d M Y', strtotime($history['end_date'])); ?> (End)
                                            </div>
                                        </div>
                                    </td>
                                <td>
                                    <div class="staff-dept-small">
                                        <i class="far fa-clock"></i> 
                                        <?php echo date('d M Y', strtotime($history['updated_at'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">  
                                        <?php if($is_admin_or_manager): ?>
                                        <button class="action-btn action-btn-delete" 
                                                onclick="deleteRecord(<?= $history['application_id']; ?>, '<?= htmlspecialchars($availability['staff_name']); ?>', <?= $_GET['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center no-data">
                                <div class="no-data-content">
                                    <i class="fas fa-calendar-times"></i>
                                    <h3>No Records Found</h3>
                                    <p>Leave availability data has not been initialized for this user.</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    </div>
        
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="../../assets/js/modern-dashboard.js"></script>
    <script>
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize AOS animations
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true
            });

            // Initialize interactions
            if (typeof initializeDashboard === "function") { 
                initializeDashboard(); 
            }
        });


        function deleteRecord(id, staff_name, staff_id) {
            if (confirm(`Are you sure you want to delete the record for ${staff_name}?`)) {
                window.location.href = `view.php?action=delete&id=${id}&staff_id=${staff_id}`;
            }
        }

        function refreshTable() {
            location.reload();
        }

        function deleteWarn() {
            alert('Action Restricted: Leave availability records cannot be deleted individually. To remove this record, please delete the associated Staff Profile.');
        }

        function exportTable() {
            // Placeholder for CSV/Excel export logic
            console.log('Exporting leave availability data...');
            alert('Exporting leave balance report...');
        }

        function adjustBalance(id, type, delta) {
            window.location.href = `view.php?action=adjust&id=${id}&type=${type}&delta=${delta}`;
        }
    </script>

    <style>

        .staff-dept-small {
            font-size: 0.75rem;
            color: var(--gray-500);
        }

        .date-range-display {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .date-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: var(--gray-600);
        }

        .date-item i {
            width: 14px;
            color: var(--gray-400);
        }

        .purpose-tag {
            display: inline-block;
            font-size: 0.75rem;
            color: var(--gray-700);
            padding: 0.25rem 0.5rem;
            background: var(--gray-100);
            border-radius: 4px;
        }

        /* Adjustment Controls */
        .leave-control {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .adj-btns {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .adj-btn {
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 4px;
            cursor: pointer;
            color: var(--gray-600);
            font-size: 0.7rem;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .adj-btn:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-1px);
        }

        .adj-btn:active {
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .staff-info-display {
                flex-direction: column;
                align-items: flex-start;
            }

            .date-range-display {
                font-size: 0.7rem;
            }
        }
    </style>
</body>
</html>
