<?php
// Set the base path for includes
$basePath = '../';

// Include database connection
require_once "../config/database.php";

// Check if user is logged in
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../modules/auth/login.php");
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

// Get requested data type
$data_type = isset($_GET['data']) ? $_GET['data'] : '';

// Get date range if provided
$start_date = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');

// Process request based on data type
try {
    switch($data_type) {
        case 'summary':
            getSummaryData($pdo, $start_date, $end_date);
            break;
        case 'soa_status':
            getSOAStatusData($pdo, $start_date, $end_date);
            break;
        case 'claims_status':
            getClaimsStatusData($pdo, $start_date, $end_date);
            break;
        case 'monthly_trends':
            getMonthlyTrendsData($pdo, $start_date, $end_date);
            break;
        case 'top_clients':
            getTopClientsData($pdo, $start_date, $end_date);
            break;
        case 'recent_soas':
            getRecentSOAsData($pdo, $start_date, $end_date);
            break;
        case 'recent_claims':
            getRecentClaimsData($pdo, $start_date, $end_date);
            break;
        default:
            echo json_encode(['error' => 'Invalid data type requested']);
            exit;
    }
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// Get summary data
function getSummaryData($pdo, $start_date, $end_date) {
    // Count clients
    $stmt = $pdo->query("SELECT COUNT(*) as client_count FROM clients");
    $client_count = $stmt->fetch()['client_count'];
    
    // Count suppliers
    $stmt = $pdo->query("SELECT COUNT(*) as supplier_count FROM suppliers");
    $supplier_count = $stmt->fetch()['supplier_count'];
    
    // Count SOAs within date range
    $stmt = $pdo->prepare("SELECT COUNT(*) as soa_count FROM soa WHERE issue_date BETWEEN ? AND ?");
    $stmt->execute([$start_date, $end_date]);
    $soa_count = $stmt->fetch()['soa_count'];
    
    // Count pending claims within date range
    $stmt = $pdo->prepare("SELECT COUNT(*) as pending_claims FROM claims WHERE status = 'Pending' AND submitted_date BETWEEN ? AND ?");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $pending_claims = $stmt->fetch()['pending_claims'];
    
    echo json_encode([
        'client_count' => $client_count,
        'supplier_count' => $supplier_count,
        'soa_count' => $soa_count,
        'pending_claims' => $pending_claims
    ]);
}

// Get SOA status distribution data
function getSOAStatusData($pdo, $start_date, $end_date) {
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) as count 
        FROM soa 
        WHERE issue_date BETWEEN ? AND ? 
        GROUP BY status
    ");
    $stmt->execute([$start_date, $end_date]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $labels = [];
    $values = [];
    
    foreach($results as $row) {
        $labels[] = $row['status'];
        $values[] = (int)$row['count'];
    }
    
    echo json_encode([
        'labels' => $labels,
        'values' => $values
    ]);
}

// Get claims status distribution data
function getClaimsStatusData($pdo, $start_date, $end_date) {
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) as count 
        FROM claims 
        WHERE submitted_date BETWEEN ? AND ? 
        GROUP BY status
    ");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $labels = [];
    $values = [];
    
    foreach($results as $row) {
        $labels[] = $row['status'];
        $values[] = (int)$row['count'];
    }
    
    echo json_encode([
        'labels' => $labels,
        'values' => $values
    ]);
}

// Get monthly trends data
function getMonthlyTrendsData($pdo, $start_date, $end_date) {
    // Get last 6 months
    $months = [];
    $soa_counts = [];
    $claim_counts = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $month_start = date('Y-m-01', strtotime("-$i months"));
        $month_end = date('Y-m-t', strtotime("-$i months"));
        $month_name = date('M Y', strtotime("-$i months"));
        
        // Count SOAs for this month
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM soa 
            WHERE issue_date BETWEEN ? AND ?
        ");
        $stmt->execute([$month_start, $month_end]);
        $soa_count = $stmt->fetch()['count'];
        
        // Count claims for this month
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM claims 
            WHERE submitted_date BETWEEN ? AND ?
        ");
        $stmt->execute([$month_start . ' 00:00:00', $month_end . ' 23:59:59']);
        $claim_count = $stmt->fetch()['count'];
        
        $months[] = $month_name;
        $soa_counts[] = (int)$soa_count;
        $claim_counts[] = (int)$claim_count;
    }
    
    echo json_encode([
        'months' => $months,
        'soa_counts' => $soa_counts,
        'claim_counts' => $claim_counts
    ]);
}

// Get top clients data
function getTopClientsData($pdo, $start_date, $end_date) {
    $stmt = $pdo->prepare("
        SELECT c.client_id, c.client_name, SUM(s.balance_amount) as total_amount
        FROM soa s
        JOIN clients c ON s.client_id = c.client_id
        WHERE s.issue_date BETWEEN ? AND ?
        GROUP BY c.client_id, c.client_name
        ORDER BY total_amount DESC
        LIMIT 5
    ");
    $stmt->execute([$start_date, $end_date]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $client_ids = [];
    $client_names = [];
    $amounts = [];
    
    foreach($results as $row) {
        $client_ids[] = $row['client_id'];
        $client_names[] = $row['client_name'];
        $amounts[] = (float)$row['total_amount'];
    }
    
    echo json_encode([
        'client_ids' => $client_ids,
        'client_names' => $client_names,
        'amounts' => $amounts
    ]);
}

// Get recent SOAs data
function getRecentSOAsData($pdo, $start_date, $end_date) {
    $stmt = $pdo->prepare("
        SELECT s.soa_id, s.account_number, c.client_name, s.issue_date, s.balance_amount, s.status 
        FROM soa s 
        JOIN clients c ON s.client_id = c.client_id 
        WHERE s.issue_date BETWEEN ? AND ?
        ORDER BY s.issue_date DESC 
        LIMIT 5
    ");
    $stmt->execute([$start_date, $end_date]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($results);
}

// Get recent claims data
function getRecentClaimsData($pdo, $start_date, $end_date) {
    $stmt = $pdo->prepare("
        SELECT cl.claim_id, s.full_name, cl.amount, cl.status, cl.submitted_date 
        FROM claims cl 
        JOIN staff s ON cl.staff_id = s.staff_id 
        WHERE cl.submitted_date BETWEEN ? AND ?
        ORDER BY cl.submitted_date DESC 
        LIMIT 5
    ");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($results);
}
