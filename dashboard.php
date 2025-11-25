<?php
// Set the base path for includes
$basePath = '';

// Include database connection
require_once "config/database.php";

// Check if user is logged in
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: modules/auth/login.php");
    exit;
}

// Check if user has permission to access dashboard (Admin or Manager only)
if($_SESSION["position"] != "Admin" && $_SESSION["position"] != "Manager"){
    header("location: modules/outstation/index.php");
    exit;
}

// Get counts for dashboard
try {
    // Count clients
    $stmt = $pdo->query("SELECT COUNT(*) as client_count FROM clients");
    $client_count = $stmt->fetch()['client_count'];
    
    // Count suppliers
    $stmt = $pdo->query("SELECT COUNT(*) as supplier_count FROM suppliers");
    $supplier_count = $stmt->fetch()['supplier_count'];
    
    // Count SOAs
    $stmt = $pdo->query("SELECT COUNT(*) as soa_count FROM soa");
    $soa_count = $stmt->fetch()['soa_count'];
    
    // Count pending claims
    $stmt = $pdo->query("SELECT COUNT(*) as pending_claims FROM claims WHERE status = 'Pending'");
    $pending_claims = $stmt->fetch()['pending_claims'];
    
    // Get recent SOAs
    $stmt = $pdo->query("SELECT s.soa_id, s.account_number, c.client_name, s.issue_date, s.balance_amount, s.status 
                         FROM soa s 
                         JOIN clients c ON s.client_id = c.client_id 
                         ORDER BY s.issue_date DESC LIMIT 5");
    $recent_soas = $stmt->fetchAll();
    
    // Get recent claims
    $stmt = $pdo->query("SELECT cl.claim_id, s.full_name, cl.amount, cl.status, cl.submitted_date 
                         FROM claims cl 
                         JOIN staff s ON cl.staff_id = s.staff_id 
                         ORDER BY cl.submitted_date DESC LIMIT 5");
    $recent_claims = $stmt->fetchAll();
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SOA Management System</title>
    
    <!-- Modern CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/modern-dashboard.css">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Charts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body class="bg-gray-50">
    <!-- Sidebar -->
    <?php include_once "includes/modern-sidebar.php"; ?>
    
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
                        <h1>Dashboard</h1>
                        <p>Welcome back! Here's what's happening with your SOA management system.</p>
                    </div>
                </div>
                <div class="header-right">
                    <div class="date-picker-container">
                        <button class="date-picker-btn" id="dateRangePicker">
                            <i class="fas fa-calendar-alt"></i>
                            <span id="dateRangeText">Last 30 Days</span>
                        </button>
                    </div>
                    <button class="export-btn">
                        <i class="fas fa-download"></i>
                        Export
                    </button>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card stat-card-primary" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-card-content">
                        <div class="stat-info">
                            <div class="stat-value"><?php echo number_format($client_count); ?></div>
                            <div class="stat-label">Total Clients</div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i>
                                <span>+12%</span>
                            </div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-building"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card stat-card-success" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-card-content">
                        <div class="stat-info">
                            <div class="stat-value"><?php echo number_format($supplier_count); ?></div>
                            <div class="stat-label">Total Suppliers</div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i>
                                <span>+8%</span>
                            </div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card stat-card-info" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-card-content">
                        <div class="stat-info">
                            <div class="stat-value"><?php echo number_format($soa_count); ?></div>
                            <div class="stat-label">SOA Records</div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i>
                                <span>+23%</span>
                            </div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card stat-card-warning" data-aos="fade-up" data-aos-delay="400">
                    <div class="stat-card-content">
                        <div class="stat-info">
                            <div class="stat-value"><?php echo number_format($pending_claims); ?></div>
                            <div class="stat-label">Pending Claims</div>
                            <div class="stat-change negative">
                                <i class="fas fa-arrow-down"></i>
                                <span>-5%</span>
                            </div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-section">
                <div class="charts-grid">
                    <!-- SOA Status Chart -->
                    <div class="chart-card" data-aos="fade-up" data-aos-delay="500">
                        <div class="chart-header">
                            <div class="chart-title">
                                <h3>SOA Status Distribution</h3>
                                <p>Current status of all SOA records</p>
                            </div>
                            <div class="chart-actions">
                                <button class="chart-action-btn" onclick="refreshChart('soaChart')">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                                <button class="chart-action-btn" onclick="exportChart('soaChart')">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <div id="soaChart"></div>
                        </div>
                    </div>

                    <!-- Claims Status Chart -->
                    <div class="chart-card" data-aos="fade-up" data-aos-delay="600">
                        <div class="chart-header">
                            <div class="chart-title">
                                <h3>Claims Status</h3>
                                <p>Distribution of claim statuses</p>
                            </div>
                            <div class="chart-actions">
                                <button class="chart-action-btn" onclick="refreshChart('claimsChart')">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                                <button class="chart-action-btn" onclick="exportChart('claimsChart')">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <div id="claimsChart"></div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Trends Chart -->
                <div class="chart-card chart-card-wide" data-aos="fade-up" data-aos-delay="700">
                    <div class="chart-header">
                        <div class="chart-title">
                            <h3>Monthly Trends</h3>
                            <p>SOAs and Claims over the last 6 months</p>
                        </div>
                        <div class="chart-filters">
                            <div class="filter-tabs">
                                <button class="filter-tab active" data-filter="all">All</button>
                                <button class="filter-tab" data-filter="soa">SOA</button>
                                <button class="filter-tab" data-filter="claims">Claims</button>
                            </div>
                            <div class="chart-actions">
                                <button class="chart-action-btn" onclick="refreshChart('trendsChart')">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                                <button class="chart-action-btn" onclick="exportChart('trendsChart')">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="chart-container">
                        <div id="trendsChart"></div>
                    </div>
                </div>
            </div>

            <!-- Data Tables Section -->
            <div class="tables-section">
                <div class="tables-grid">
                    <!-- Recent SOAs -->
                    <div class="table-card" data-aos="fade-up" data-aos-delay="800">
                        <div class="table-header">
                            <div class="table-title">
                                <h3>Recent SOAs</h3>
                                <p>Latest statement of accounts</p>
                            </div>
                            <div class="table-actions">
                                <button class="table-action-btn" onclick="refreshTable('soaTable')">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                                <a href="modules/soa/index.php" class="table-action-btn">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </div>
                        <div class="table-container">
                            <table class="modern-table" id="soaTable">
                                <thead>
                                    <tr>
                                        <th>Account #</th>
                                        <th>Client</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_soas as $soa): ?>
                                    <tr class="table-row-clickable" data-href="modules/soa/view.php?id=<?php echo $soa['soa_id']; ?>">
                                        <td class="font-medium"><?php echo htmlspecialchars($soa['account_number']); ?></td>
                                        <td><?php echo htmlspecialchars($soa['client_name']); ?></td>
                                        <td class="font-medium">RM <?php echo number_format($soa['balance_amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($soa['status']); ?>">
                                                <?php echo htmlspecialchars($soa['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="action-btn" onclick="viewSOA(<?php echo $soa['soa_id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Recent Claims -->
                    <div class="table-card" data-aos="fade-up" data-aos-delay="900">
                        <div class="table-header">
                            <div class="table-title">
                                <h3>Recent Claims</h3>
                                <p>Latest expense claims</p>
                            </div>
                            <div class="table-actions">
                                <button class="table-action-btn" onclick="refreshTable('claimsTable')">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                                <a href="modules/claims/index.php" class="table-action-btn">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </div>
                        <div class="table-container">
                            <table class="modern-table" id="claimsTable">
                                <thead>
                                    <tr>
                                        <th>Staff</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_claims as $claim): ?>
                                    <tr class="table-row-clickable" data-href="modules/claims/view.php?id=<?php echo $claim['claim_id']; ?>">
                                        <td class="font-medium"><?php echo htmlspecialchars($claim['full_name']); ?></td>
                                        <td class="font-medium">RM <?php echo number_format($claim['amount'], 2); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($claim['submitted_date'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($claim['status']); ?>">
                                                <?php echo htmlspecialchars($claim['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="action-btn" onclick="viewClaim(<?php echo $claim['claim_id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Panel -->
            <div class="quick-actions-panel" data-aos="fade-up" data-aos-delay="1000">
                <div class="quick-actions-header">
                    <h3>Quick Actions</h3>
                    <p>Frequently used actions</p>
                </div>
                <div class="quick-actions-grid">
                    <button class="quick-action-btn" onclick="location.href='modules/soa/create.php'">
                        <i class="fas fa-plus"></i>
                        <span>Create SOA</span>
                    </button>
                    <button class="quick-action-btn" onclick="location.href='modules/clients/create.php'">
                        <i class="fas fa-building"></i>
                        <span>Add Client</span>
                    </button>
                    <button class="quick-action-btn" onclick="location.href='modules/suppliers/create.php'">
                        <i class="fas fa-truck"></i>
                        <span>Add Supplier</span>
                    </button>
                    <button class="quick-action-btn" onclick="location.href='modules/claims/create.php'">
                        <i class="fas fa-receipt"></i>
                        <span>Submit Claim</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="assets/js/modern-dashboard.js"></script>
    
    <script>
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize AOS animations
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true
            });

            // Initialize charts with PHP data
            initializeCharts();
            
            // Initialize interactions
            initializeDashboard();
        });

        function initializeCharts() {
            // SOA Status Chart
            const soaChart = new ApexCharts(document.querySelector("#soaChart"), {
                series: [65, 25, 10],
                chart: {
                    type: 'donut',
                    height: 300,
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                labels: ['Paid', 'Pending', 'Overdue'],
                colors: ['#10B981', '#F59E0B', '#EF4444'],
                legend: {
                    position: 'bottom'
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '70%'
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val) {
                        return Math.round(val) + '%';
                    }
                }
            });
            soaChart.render();

            // Claims Status Chart
            const claimsChart = new ApexCharts(document.querySelector("#claimsChart"), {
                series: [{
                    name: 'Claims',
                    data: [44, 55, 13]
                }],
                chart: {
                    type: 'bar',
                    height: 300,
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 8,
                        horizontal: false,
                        columnWidth: '55%',
                        distributed: true
                    }
                },
                colors: ['#10B981', '#F59E0B', '#EF4444'],
                xaxis: {
                    categories: ['Approved', 'Pending', 'Rejected']
                },
                dataLabels: {
                    enabled: false
                }
            });
            claimsChart.render();

            // Monthly Trends Chart
            const trendsChart = new ApexCharts(document.querySelector("#trendsChart"), {
                series: [{
                    name: 'SOAs',
                    data: [31, 40, 28, 51, 42, 109]
                }, {
                    name: 'Claims',
                    data: [11, 32, 45, 32, 34, 52]
                }],
                chart: {
                    type: 'area',
                    height: 350,
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                colors: ['#3B82F6', '#10B981'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.3
                    }
                },
                xaxis: {
                    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']
                },
                markers: {
                    size: 6,
                    strokeWidth: 2,
                    fillOpacity: 1,
                    strokeOpacity: 1
                }
            });
            trendsChart.render();
        }
    </script>
</body>
</html>
