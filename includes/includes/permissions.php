<?php
// Define access permissions for different user positions
function checkPermission($module) {
    // If user is not logged in, redirect to login page
    if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
        header("location: " . (isset($GLOBALS['basePath']) ? $GLOBALS['basePath'] : '') . "modules/auth/login.php");
        exit;
    }
    
    // Get user position from session
    $position = $_SESSION["position"];
    
    // Define permissions for each position
    $permissions = [
        'Admin' => [
            'dashboard', 'staff', 'clients', 'suppliers', 'soa', 'inventory',
            'documents', 'claims', 'excel', 'purchase_orders'
        ],
        'Manager' => [
            'dashboard', 'clients', 'suppliers', 'soa', 'inventory',
            'documents', 'claims', 'excel', 'purchase_orders'
        ],
        'Staff' => [
            'dashboard', 'documents', 'claims'
        ]
    ];
    
    // Check if user has permission to access the module
    if (!in_array($module, $permissions[$position])) {
        // Redirect to dashboard with error message
        $_SESSION['access_denied'] = "You do not have permission to access the $module module.";
        header("location: " . (isset($GLOBALS['basePath']) ? $GLOBALS['basePath'] : '') . "dashboard.php");
        exit;
    }
    
    return true;
}
?>
