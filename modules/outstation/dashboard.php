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

// Check permissions - Only Admin and Manager can access
if($_SESSION['position'] != 'Admin' && $_SESSION['position'] != 'Manager'){
    header("location: index.php");
    exit;
}

// Fetch dashboard statistics
try {
    // Overall statistics
    $stats_sql = "SELECT
        COUNT(*) as total_applications,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected_count,
        SUM(CASE WHEN is_claimable = 1 AND status = 'Approved' THEN 1 ELSE 0 END) as claimable_count,
        SUM(total_nights) as total_nights_sum,
        SUM(estimated_cost) as total_estimated_cost
        FROM outstation_applications";
    $stmt = $pdo->query($stats_sql);
    $stats = $stmt->fetch();

    // Monthly trend data (last 6 months)
    $monthly_sql = "SELECT
        DATE_FORMAT(created_at, '%Y-%m') as month,
        DATE_FORMAT(created_at, '%b %Y') as month_name,
        COUNT(*) as count,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN is_claimable = 1 AND status = 'Approved' THEN 1 ELSE 0 END) as claimable
        FROM outstation_applications
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month";
    $stmt = $pdo->query($monthly_sql);
    $monthly_data = $stmt->fetchAll();

    // Status distribution
    $status_sql = "SELECT status, COUNT(*) as count
                   FROM outstation_applications
                   GROUP BY status";
    $stmt = $pdo->query($status_sql);
    $status_distribution = $stmt->fetchAll();

    // Top destinations
    $destination_sql = "SELECT destination, COUNT(*) as count
                        FROM outstation_applications
                        GROUP BY destination
                        ORDER BY count DESC
                        LIMIT 10";
    $stmt = $pdo->query($destination_sql);
    $top_destinations = $stmt->fetchAll();

    // Staff with most applications
    $staff_sql = "SELECT s.full_name, s.department, COUNT(oa.application_id) as app_count,
                  SUM(CASE WHEN oa.is_claimable = 1 AND oa.status = 'Approved' THEN 1 ELSE 0 END) as claimable_count,
                  SUM(CASE WHEN oa.status = 'Approved' THEN 1 ELSE 0 END) as approved_count
                  FROM staff s
                  LEFT JOIN outstation_applications oa ON s.staff_id = oa.staff_id
                  GROUP BY s.staff_id
                  HAVING app_count > 0
                  ORDER BY app_count DESC
                  LIMIT 10";
    $stmt = $pdo->query($staff_sql);
    $staff_stats = $stmt->fetchAll();

    // Recent applications
    $recent_sql = "SELECT oa.*, s.full_name as staff_name, s.department
                   FROM outstation_applications oa
                   LEFT JOIN staff s ON oa.staff_id = s.staff_id
                   ORDER BY oa.created_at DESC
                   LIMIT 5";
    $stmt = $pdo->query($recent_sql);
    $recent_applications = $stmt->fetchAll();

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outstation Dashboard - SOA Management System</title>

    <!-- Modern CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/modern-dashboard.css">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
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
                        <h1>Outstation Leave Dashboard</h1>
                        <p>Overview and analytics of outstation leave applications</p>
                    </div>
                </div>
                <div class="header-right">
                    <a href="index.php" class="export-btn">
                        <i class="fas fa-list"></i>
                        View All Applications
                    </a>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Statistics Cards -->
            <div class="stats-grid" data-aos="fade-up">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: var(--primary-color);">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-label">Total Applications</div>
                        <div class="stat-value"><?php echo $stats['total_applications']; ?></div>
                        <div class="stat-change positive">All time submissions</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--warning-color);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-label">Pending</div>
                        <div class="stat-value"><?php echo $stats['pending_count']; ?></div>
                        <div class="stat-change">Awaiting approval</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success-color);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-label">Approved</div>
                        <div class="stat-value"><?php echo $stats['approved_count']; ?></div>
                        <div class="stat-change positive">
                            <?php echo $stats['total_applications'] > 0 ? round(($stats['approved_count']/$stats['total_applications'])*100, 1) : 0; ?>% approval rate
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(6, 182, 212, 0.1); color: var(--info-color);">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-label">Claimable</div>
                        <div class="stat-value"><?php echo $stats['claimable_count']; ?></div>
                        <div class="stat-change">Eligible for claims</div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-grid" data-aos="fade-up" data-aos-delay="100">
                <!-- Monthly Trend Chart -->
                <div class="chart-card full-width">
                    <div class="chart-header">
                        <div>
                            <h3>Monthly Application Trends</h3>
                            <p>Applications over the last 6 months</p>
                        </div>
                        <div class="chart-legend">
                            <span class="legend-item"><span class="legend-dot" style="background: #3b82f6;"></span> Total</span>
                            <span class="legend-item"><span class="legend-dot" style="background: #10b981;"></span> Approved</span>
                            <span class="legend-item"><span class="legend-dot" style="background: #06b6d4;"></span> Claimable</span>
                        </div>
                    </div>
                    <div class="chart-body">
                        <canvas id="monthlyTrendChart"></canvas>
                    </div>
                </div>

                <!-- Status Distribution Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <h3>Status Distribution</h3>
                            <p>Application status breakdown</p>
                        </div>
                    </div>
                    <div class="chart-body">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>

                <!-- Top Destinations Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <h3>Top Destinations</h3>
                            <p>Most visited locations</p>
                        </div>
                    </div>
                    <div class="chart-body">
                        <canvas id="destinationChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Data Tables -->
            <div class="table-grid" data-aos="fade-up" data-aos-delay="200">
                <!-- Staff Statistics Table -->
                <div class="table-card">
                    <div class="table-header">
                        <div class="table-title">
                            <h3>Staff Statistics</h3>
                            <p>Employee outstation activity overview</p>
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Staff Name</th>
                                    <th>Department</th>
                                    <th>Total Apps</th>
                                    <th>Approved</th>
                                    <th>Claimable</th>
                                    <th>Claim Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($staff_stats)): ?>
                                    <?php foreach($staff_stats as $staff): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($staff['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($staff['department']); ?></td>
                                        <td class="font-medium"><?php echo $staff['app_count']; ?></td>
                                        <td>
                                            <span class="mini-badge success"><?php echo $staff['approved_count']; ?></span>
                                        </td>
                                        <td>
                                            <span class="mini-badge info"><?php echo $staff['claimable_count']; ?></span>
                                        </td>
                                        <td>
                                            <?php if($staff['claimable_count'] > 0): ?>
                                                <span class="status-badge-small status-warning">
                                                    <i class="fas fa-exclamation-circle"></i> Has Claimable
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge-small status-success">
                                                    <i class="fas fa-check"></i> Up to date
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center no-data">No staff data available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Applications -->
                <div class="table-card">
                    <div class="table-header">
                        <div class="table-title">
                            <h3>Recent Applications</h3>
                            <p>Latest outstation leave submissions</p>
                        </div>
                        <a href="index.php" class="view-all-link">View All <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="table-container">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Staff</th>
                                    <th>Destination</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($recent_applications)): ?>
                                    <?php foreach($recent_applications as $app): ?>
                                    <tr onclick="window.location.href='view.php?id=<?php echo $app['application_id']; ?>'" style="cursor: pointer;">
                                        <td>
                                            <div class="staff-mini">
                                                <div class="staff-name-mini"><?php echo htmlspecialchars($app['staff_name']); ?></div>
                                                <div class="staff-dept-mini"><?php echo htmlspecialchars($app['department']); ?></div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($app['destination']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($app['departure_date'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($app['status']); ?>">
                                                <?php echo $app['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center no-data">No recent applications</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="../../assets/js/modern-dashboard.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize AOS
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true
            });

            initializeDashboard();

            // Prepare data for monthly trend chart
            const monthlyLabels = <?php echo json_encode(array_column($monthly_data, 'month_name')); ?>;
            const monthlyTotal = <?php echo json_encode(array_column($monthly_data, 'count')); ?>;
            const monthlyApproved = <?php echo json_encode(array_column($monthly_data, 'approved')); ?>;
            const monthlyClaimable = <?php echo json_encode(array_column($monthly_data, 'claimable')); ?>;

            // Monthly Trend Chart
            const monthlyCtx = document.getElementById('monthlyTrendChart').getContext('2d');
            new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: monthlyLabels,
                    datasets: [
                        {
                            label: 'Total Applications',
                            data: monthlyTotal,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Approved',
                            data: monthlyApproved,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Claimable',
                            data: monthlyClaimable,
                            borderColor: '#06b6d4',
                            backgroundColor: 'rgba(6, 182, 212, 0.1)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });

            // Status Distribution Chart
            const statusLabels = <?php echo json_encode(array_column($status_distribution, 'status')); ?>;
            const statusCounts = <?php echo json_encode(array_column($status_distribution, 'count')); ?>;

            const statusCtx = document.getElementById('statusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusCounts,
                        backgroundColor: [
                            '#f59e0b',  // Pending
                            '#10b981',  // Approved
                            '#ef4444',  // Rejected
                            '#6b7280',  // Cancelled
                            '#06b6d4'   // Completed
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Top Destinations Chart
            const destLabels = <?php echo json_encode(array_column($top_destinations, 'destination')); ?>;
            const destCounts = <?php echo json_encode(array_column($top_destinations, 'count')); ?>;

            const destCtx = document.getElementById('destinationChart').getContext('2d');
            new Chart(destCtx, {
                type: 'bar',
                data: {
                    labels: destLabels,
                    datasets: [{
                        label: 'Applications',
                        data: destCounts,
                        backgroundColor: '#3b82f6',
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        },
                        x: {
                            ticks: {
                                autoSkip: false,
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    }
                }
            });
        });
    </script>

    <style>
        /* Dashboard Specific Styles */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .chart-card.full-width {
            grid-column: 1 / -1;
        }

        .chart-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chart-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0 0 0.25rem 0;
        }

        .chart-header p {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin: 0;
        }

        .chart-legend {
            display: flex;
            gap: 1rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--gray-700);
        }

        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .chart-body {
            padding: 1.5rem;
            height: 300px;
        }

        .table-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .view-all-link {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .view-all-link:hover {
            color: var(--primary-dark);
        }

        .mini-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .mini-badge.success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .mini-badge.info {
            background: rgba(6, 182, 212, 0.1);
            color: var(--info-color);
        }

        .status-badge-small {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-badge-small.status-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .status-badge-small.status-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .staff-mini {
            display: flex;
            flex-direction: column;
            gap: 0.125rem;
        }

        .staff-name-mini {
            font-weight: 500;
            color: var(--gray-900);
            font-size: 0.875rem;
        }

        .staff-dept-mini {
            font-size: 0.75rem;
            color: var(--gray-500);
        }

        @media (max-width: 1024px) {
            .charts-grid,
            .table-grid {
                grid-template-columns: 1fr;
            }

            .chart-card.full-width {
                grid-column: 1;
            }
        }

        @media (max-width: 768px) {
            .chart-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .chart-legend {
                flex-wrap: wrap;
            }
        }
    </style>
</body>
</html>
