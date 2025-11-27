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

// Fetch dynamic dashboard data
try {
    // Client SOA Metrics
    $stmt = $pdo->query("SELECT
        COUNT(*) as total_count,
        SUM(CASE WHEN status = 'Paid' THEN 1 ELSE 0 END) as paid_count,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'Overdue' THEN 1 ELSE 0 END) as overdue_count,
        SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_count,
        SUM(total_amount) as total_revenue,
        SUM(CASE WHEN status = 'Paid' THEN total_amount ELSE 0 END) as paid_amount,
        SUM(CASE WHEN status = 'Pending' THEN total_amount ELSE 0 END) as pending_amount,
        SUM(CASE WHEN status = 'Overdue' THEN total_amount ELSE 0 END) as overdue_amount
        FROM client_soa");
    $client_soa_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Supplier SOA Metrics
    $stmt = $pdo->query("SELECT
        COUNT(*) as total_count,
        SUM(CASE WHEN payment_status = 'Paid' THEN 1 ELSE 0 END) as paid_count,
        SUM(CASE WHEN payment_status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN payment_status = 'Overdue' THEN 1 ELSE 0 END) as overdue_count,
        SUM(amount) as total_expenses,
        SUM(CASE WHEN payment_status = 'Paid' THEN amount ELSE 0 END) as paid_amount,
        SUM(CASE WHEN payment_status = 'Pending' THEN amount ELSE 0 END) as pending_amount,
        SUM(CASE WHEN payment_status = 'Overdue' THEN amount ELSE 0 END) as overdue_amount
        FROM supplier_soa");
    $supplier_soa_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Claims Metrics
    $stmt = $pdo->query("SELECT
        COUNT(*) as total_count,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected_count,
        SUM(amount) as total_claims,
        SUM(CASE WHEN status = 'Pending' THEN amount ELSE 0 END) as pending_amount,
        SUM(CASE WHEN status = 'Approved' THEN amount ELSE 0 END) as approved_amount,
        SUM(CASE WHEN status = 'Rejected' THEN amount ELSE 0 END) as rejected_amount
        FROM claims");
    $claims_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Monthly trends for Client SOAs (last 6 months)
    $stmt = $pdo->query("SELECT
        DATE_FORMAT(issue_date, '%Y-%m') as month,
        DATE_FORMAT(issue_date, '%b') as month_name,
        COUNT(*) as count,
        SUM(total_amount) as total
        FROM client_soa
        WHERE issue_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(issue_date, '%Y-%m')
        ORDER BY month ASC");
    $client_monthly = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Monthly trends for Claims (last 6 months)
    $stmt = $pdo->query("SELECT
        DATE_FORMAT(submitted_date, '%Y-%m') as month,
        DATE_FORMAT(submitted_date, '%b') as month_name,
        COUNT(*) as count,
        SUM(amount) as total
        FROM claims
        WHERE submitted_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(submitted_date, '%Y-%m')
        ORDER BY month ASC");
    $claims_monthly = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get counts for dashboard cards
    $stmt = $pdo->query("SELECT COUNT(*) as client_count FROM clients");
    $client_count = $stmt->fetch()['client_count'];

    $stmt = $pdo->query("SELECT COUNT(*) as supplier_count FROM suppliers");
    $supplier_count = $stmt->fetch()['supplier_count'];

    // Recent SOAs
    $stmt = $pdo->query("SELECT cs.soa_id, cs.account_number, c.client_name, cs.issue_date, cs.total_amount, cs.status
                         FROM client_soa cs
                         JOIN clients c ON cs.client_id = c.client_id
                         ORDER BY cs.created_at DESC LIMIT 5");
    $recent_soas = $stmt->fetchAll();

    // Recent Claims
    $stmt = $pdo->query("SELECT cl.claim_id, s.full_name, cl.amount, cl.status, cl.submitted_date
                         FROM claims cl
                         JOIN staff s ON cl.staff_id = s.staff_id
                         ORDER BY cl.submitted_date DESC LIMIT 5");
    $recent_claims = $stmt->fetchAll();

    // Calculate financial health metrics
    $total_revenue = $client_soa_data['total_revenue'] ?? 0;
    $total_expenses = ($supplier_soa_data['total_expenses'] ?? 0) + ($claims_data['approved_amount'] ?? 0);
    $net_balance = $total_revenue - $total_expenses;
    $expense_efficiency = $total_revenue > 0 ? round((($total_revenue - $total_expenses) / $total_revenue) * 100, 1) : 0;

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Prepare chart data for JavaScript
$client_monthly_labels = array_column($client_monthly, 'month_name');
$client_monthly_values = array_column($client_monthly, 'count');
$claims_monthly_labels = array_column($claims_monthly, 'month_name');
$claims_monthly_values = array_column($claims_monthly, 'count');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SOA Management System</title>

    <!-- Bootstrap 4.5.2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f7;
            color: #1d1d1f;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: #ffffff;
            border-right: 1px solid #e5e5e7;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar .logo {
            padding: 0 20px 20px;
            border-bottom: 1px solid #e5e5e7;
            margin-bottom: 20px;
        }

        .sidebar .logo h4 {
            color: #1d1d1f;
            font-weight: 600;
            font-size: 18px;
        }

        .sidebar .nav-item {
            padding: 12px 20px;
            color: #6e6e73;
            text-decoration: none;
            display: block;
            transition: all 0.3s;
            font-size: 14px;
        }

        .sidebar .nav-item:hover,
        .sidebar .nav-item.active {
            background: #f5f5f7;
            color: #0071e3;
            text-decoration: none;
        }

        .sidebar .nav-item i {
            width: 20px;
            margin-right: 10px;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 30px;
            width: calc(100% - 250px);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 32px;
            font-weight: 600;
            color: #1d1d1f;
        }

        .header .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        /* Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .metric-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }

        .metric-card .card-header-custom {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .metric-card .card-title {
            font-size: 13px;
            color: #6e6e73;
            font-weight: 500;
            margin-bottom: 4px;
        }

        .metric-card .card-value {
            font-size: 28px;
            font-weight: 600;
            color: #1d1d1f;
        }

        .metric-card .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .metric-card .card-footer-custom {
            display: flex;
            gap: 16px;
            padding-top: 16px;
            border-top: 1px solid #f5f5f7;
            margin-top: 16px;
        }

        .metric-card .sub-metric {
            flex: 1;
        }

        .metric-card .sub-label {
            font-size: 11px;
            color: #86868b;
            margin-bottom: 4px;
        }

        .metric-card .sub-value {
            font-size: 14px;
            font-weight: 600;
            color: #1d1d1f;
        }

        /* Color variations */
        .card-purple .card-icon { background: #f3e5f5; color: #9c27b0; }
        .card-orange .card-icon { background: #fff3e0; color: #f57c00; }
        .card-green .card-icon { background: #e8f5e9; color: #4caf50; }
        .card-blue .card-icon { background: #e3f2fd; color: #2196f3; }

        /* Progress bars */
        .progress-bars {
            margin-top: 12px;
        }

        .progress-item {
            margin-bottom: 8px;
        }

        .progress-item:last-child {
            margin-bottom: 0;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 4px;
        }

        .progress-label-text {
            color: #6e6e73;
        }

        .progress-label-value {
            color: #1d1d1f;
            font-weight: 600;
        }

        .progress-bar-custom {
            height: 6px;
            background: #f5f5f7;
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.6s ease;
        }

        /* Chart Section */
        .chart-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .chart-card h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1d1d1f;
        }

        .chart-card p {
            font-size: 13px;
            color: #6e6e73;
            margin-bottom: 20px;
        }

        /* Tables */
        .table-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
        }

        .table-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .table-card h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1d1d1f;
        }

        .table-card p {
            font-size: 13px;
            color: #6e6e73;
            margin-bottom: 20px;
        }

        .custom-table {
            width: 100%;
        }

        .custom-table thead th {
            font-size: 12px;
            color: #6e6e73;
            font-weight: 500;
            text-transform: uppercase;
            padding: 12px 8px;
            border-bottom: 1px solid #f5f5f7;
        }

        .custom-table tbody td {
            padding: 16px 8px;
            font-size: 14px;
            border-bottom: 1px solid #f5f5f7;
            color: #1d1d1f;
        }

        .custom-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Status badges */
        .badge-custom {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            display: inline-block;
        }

        .badge-paid { background: #d4edda; color: #155724; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-overdue { background: #f8d7da; color: #721c24; }
        .badge-approved { background: #d4edda; color: #155724; }
        .badge-rejected { background: #f8d7da; color: #721c24; }
        .badge-closed { background: #e2e3e5; color: #383d41; }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .cards-grid,
            .chart-section,
            .table-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <h4><i class="fas fa-shield-alt"></i> KYROL Security</h4>
            </div>

            <a href="dashboard.php" class="nav-item active">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            <a href="modules/clients/index.php" class="nav-item">
                <i class="fas fa-building"></i> Clients
            </a>
            <a href="modules/suppliers/index.php" class="nav-item">
                <i class="fas fa-truck"></i> Suppliers
            </a>
            <a href="modules/soa/client/index.php" class="nav-item">
                <i class="fas fa-file-invoice"></i> Client SOA
            </a>
            <a href="modules/soa/supplier/index.php" class="nav-item">
                <i class="fas fa-file-invoice-dollar"></i> Supplier SOA
            </a>
            <a href="modules/claims/index.php" class="nav-item">
                <i class="fas fa-receipt"></i> Claims
            </a>
            <?php if($_SESSION["position"] == "Admin"): ?>
            <a href="modules/staff/index.php" class="nav-item">
                <i class="fas fa-users"></i> Staff
            </a>
            <?php endif; ?>
            <a href="modules/auth/logout.php" class="nav-item">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div>
                    <h1>Dashboard</h1>
                    <p style="color: #6e6e73; font-size: 14px;">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                </div>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                        <div style="color: #6e6e73; font-size: 12px;"><?php echo htmlspecialchars($_SESSION['position']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Expense Overview -->
            <h2 style="font-size: 20px; font-weight: 600; margin-bottom: 20px;">Financial Overview</h2>

            <div class="cards-grid">
                <!-- Client SOA Card -->
                <div class="metric-card card-purple">
                    <div class="card-header-custom">
                        <div>
                            <div class="card-title">Client SOAs</div>
                            <div class="card-value">RM <?php echo number_format($client_soa_data['total_revenue'] ?? 0, 2); ?></div>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                    </div>
                    <div class="card-footer-custom">
                        <div class="sub-metric">
                            <div class="sub-label">Paid</div>
                            <div class="sub-value">RM <?php echo number_format($client_soa_data['paid_amount'] ?? 0, 0); ?></div>
                        </div>
                        <div class="sub-metric">
                            <div class="sub-label">Pending</div>
                            <div class="sub-value">RM <?php echo number_format($client_soa_data['pending_amount'] ?? 0, 0); ?></div>
                        </div>
                    </div>
                    <div class="progress-bars">
                        <div class="progress-item">
                            <div class="progress-label">
                                <span class="progress-label-text">Paid: <?php echo $client_soa_data['paid_count'] ?? 0; ?></span>
                            </div>
                            <div class="progress-bar-custom">
                                <div class="progress-bar-fill" style="width: <?php echo $client_soa_data['total_count'] > 0 ? ($client_soa_data['paid_count'] / $client_soa_data['total_count'] * 100) : 0; ?>%; background: #9c27b0;"></div>
                            </div>
                        </div>
                        <div class="progress-item">
                            <div class="progress-label">
                                <span class="progress-label-text">Pending: <?php echo $client_soa_data['pending_count'] ?? 0; ?></span>
                            </div>
                            <div class="progress-bar-custom">
                                <div class="progress-bar-fill" style="width: <?php echo $client_soa_data['total_count'] > 0 ? ($client_soa_data['pending_count'] / $client_soa_data['total_count'] * 100) : 0; ?>%; background: #f57c00;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Supplier SOA Card -->
                <div class="metric-card card-orange">
                    <div class="card-header-custom">
                        <div>
                            <div class="card-title">Supplier Expenses</div>
                            <div class="card-value">RM <?php echo number_format($supplier_soa_data['total_expenses'] ?? 0, 2); ?></div>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                    </div>
                    <div class="card-footer-custom">
                        <div class="sub-metric">
                            <div class="sub-label">Paid</div>
                            <div class="sub-value">RM <?php echo number_format($supplier_soa_data['paid_amount'] ?? 0, 0); ?></div>
                        </div>
                        <div class="sub-metric">
                            <div class="sub-label">Pending</div>
                            <div class="sub-value">RM <?php echo number_format($supplier_soa_data['pending_amount'] ?? 0, 0); ?></div>
                        </div>
                    </div>
                    <div class="progress-bars">
                        <div class="progress-item">
                            <div class="progress-label">
                                <span class="progress-label-text">Paid: <?php echo $supplier_soa_data['paid_count'] ?? 0; ?></span>
                            </div>
                            <div class="progress-bar-custom">
                                <div class="progress-bar-fill" style="width: <?php echo $supplier_soa_data['total_count'] > 0 ? ($supplier_soa_data['paid_count'] / $supplier_soa_data['total_count'] * 100) : 0; ?>%; background: #f57c00;"></div>
                            </div>
                        </div>
                        <div class="progress-item">
                            <div class="progress-label">
                                <span class="progress-label-text">Pending: <?php echo $supplier_soa_data['pending_count'] ?? 0; ?></span>
                            </div>
                            <div class="progress-bar-custom">
                                <div class="progress-bar-fill" style="width: <?php echo $supplier_soa_data['total_count'] > 0 ? ($supplier_soa_data['pending_count'] / $supplier_soa_data['total_count'] * 100) : 0; ?>%; background: #9c27b0;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Claims Card -->
                <div class="metric-card card-green">
                    <div class="card-header-custom">
                        <div>
                            <div class="card-title">Staff Claims</div>
                            <div class="card-value">RM <?php echo number_format($claims_data['approved_amount'] ?? 0, 2); ?></div>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-receipt"></i>
                        </div>
                    </div>
                    <div class="card-footer-custom">
                        <div class="sub-metric">
                            <div class="sub-label">Approved</div>
                            <div class="sub-value"><?php echo $claims_data['approved_count'] ?? 0; ?></div>
                        </div>
                        <div class="sub-metric">
                            <div class="sub-label">Pending</div>
                            <div class="sub-value"><?php echo $claims_data['pending_count'] ?? 0; ?></div>
                        </div>
                    </div>
                    <div class="progress-bars">
                        <div class="progress-item">
                            <div class="progress-label">
                                <span class="progress-label-text">Pending: RM <?php echo number_format($claims_data['pending_amount'] ?? 0, 0); ?></span>
                            </div>
                            <div class="progress-bar-custom">
                                <div class="progress-bar-fill" style="width: <?php echo $claims_data['total_claims'] > 0 ? ($claims_data['pending_amount'] / $claims_data['total_claims'] * 100) : 0; ?>%; background: #f57c00;"></div>
                            </div>
                        </div>
                        <div class="progress-item">
                            <div class="progress-label">
                                <span class="progress-label-text">Rejected: <?php echo $claims_data['rejected_count'] ?? 0; ?></span>
                            </div>
                            <div class="progress-bar-custom">
                                <div class="progress-bar-fill" style="width: <?php echo $claims_data['total_count'] > 0 ? ($claims_data['rejected_count'] / $claims_data['total_count'] * 100) : 0; ?>%; background: #f44336;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Net Balance Card -->
                <div class="metric-card card-blue">
                    <div class="card-header-custom">
                        <div>
                            <div class="card-title">Net Balance</div>
                            <div class="card-value">RM <?php echo number_format($net_balance, 2); ?></div>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="card-footer-custom">
                        <div class="sub-metric">
                            <div class="sub-label">Efficiency</div>
                            <div class="sub-value"><?php echo $expense_efficiency; ?>%</div>
                        </div>
                        <div class="sub-metric">
                            <div class="sub-label">Total Clients</div>
                            <div class="sub-value"><?php echo $client_count; ?></div>
                        </div>
                    </div>
                    <div class="progress-bars">
                        <div class="progress-item">
                            <div class="progress-label">
                                <span class="progress-label-text">Revenue vs Expenses</span>
                            </div>
                            <div class="progress-bar-custom">
                                <div class="progress-bar-fill" style="width: <?php echo min(100, $expense_efficiency); ?>%; background: #2196f3;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="chart-section">
                <!-- SOA Status Distribution -->
                <div class="chart-card">
                    <h3>SOA Status Distribution</h3>
                    <p>Breakdown of client SOA statuses</p>
                    <canvas id="soaStatusChart" style="max-height: 300px;"></canvas>
                </div>

                <!-- Claims Status Distribution -->
                <div class="chart-card">
                    <h3>Claims Status Distribution</h3>
                    <p>Breakdown of staff claims by status</p>
                    <canvas id="claimsStatusChart" style="max-height: 300px;"></canvas>
                </div>
            </div>

            <!-- Monthly Trends -->
            <div class="chart-card" style="margin-bottom: 30px;">
                <h3>Monthly Trends</h3>
                <p>SOAs and Claims activity over the last 6 months</p>
                <canvas id="monthlyTrendsChart" style="max-height: 350px;"></canvas>
            </div>

            <!-- Recent Activity Tables -->
            <h2 style="font-size: 20px; font-weight: 600; margin-bottom: 20px;">Recent Activity</h2>

            <div class="table-section">
                <!-- Recent SOAs -->
                <div class="table-card">
                    <h3>Recent Client SOAs</h3>
                    <p>Latest statement of accounts</p>
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Account #</th>
                                <th>Client</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($recent_soas) > 0): ?>
                                <?php foreach($recent_soas as $soa): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($soa['account_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($soa['client_name']); ?></td>
                                    <td>RM <?php echo number_format($soa['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="badge-custom badge-<?php echo strtolower($soa['status']); ?>">
                                            <?php echo htmlspecialchars($soa['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #6e6e73;">No recent SOAs</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Recent Claims -->
                <div class="table-card">
                    <h3>Recent Claims</h3>
                    <p>Latest staff expense claims</p>
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Staff</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($recent_claims) > 0): ?>
                                <?php foreach($recent_claims as $claim): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($claim['full_name']); ?></strong></td>
                                    <td>RM <?php echo number_format($claim['amount'], 2); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($claim['submitted_date'])); ?></td>
                                    <td>
                                        <span class="badge-custom badge-<?php echo strtolower($claim['status']); ?>">
                                            <?php echo htmlspecialchars($claim['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #6e6e73;">No recent claims</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // SOA Status Chart
        const soaStatusCtx = document.getElementById('soaStatusChart').getContext('2d');
        new Chart(soaStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Paid', 'Pending', 'Overdue', 'Closed'],
                datasets: [{
                    data: [
                        <?php echo $client_soa_data['paid_count'] ?? 0; ?>,
                        <?php echo $client_soa_data['pending_count'] ?? 0; ?>,
                        <?php echo $client_soa_data['overdue_count'] ?? 0; ?>,
                        <?php echo $client_soa_data['closed_count'] ?? 0; ?>
                    ],
                    backgroundColor: ['#4caf50', '#f57c00', '#f44336', '#9e9e9e'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                family: 'Poppins',
                                size: 12
                            }
                        }
                    }
                }
            }
        });

        // Claims Status Chart
        const claimsStatusCtx = document.getElementById('claimsStatusChart').getContext('2d');
        new Chart(claimsStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Approved', 'Pending', 'Rejected'],
                datasets: [{
                    data: [
                        <?php echo $claims_data['approved_count'] ?? 0; ?>,
                        <?php echo $claims_data['pending_count'] ?? 0; ?>,
                        <?php echo $claims_data['rejected_count'] ?? 0; ?>
                    ],
                    backgroundColor: ['#4caf50', '#f57c00', '#f44336'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                family: 'Poppins',
                                size: 12
                            }
                        }
                    }
                }
            }
        });

        // Monthly Trends Chart
        const monthlyTrendsCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
        new Chart(monthlyTrendsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_merge($client_monthly_labels, array_diff($claims_monthly_labels, $client_monthly_labels))); ?>,
                datasets: [
                    {
                        label: 'Client SOAs',
                        data: <?php echo json_encode($client_monthly_values); ?>,
                        borderColor: '#9c27b0',
                        backgroundColor: 'rgba(156, 39, 176, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2
                    },
                    {
                        label: 'Claims',
                        data: <?php echo json_encode($claims_monthly_values); ?>,
                        borderColor: '#4caf50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                family: 'Poppins',
                                size: 12
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: {
                                family: 'Poppins'
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: 'Poppins'
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
