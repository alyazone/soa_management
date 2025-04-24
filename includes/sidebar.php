<div class="col-md-2 bg-light sidebar">
    <div class="sidebar-sticky pt-3">
        <!-- Logo at the top of sidebar -->
        <div class="text-center mb-4">
            <img src="<?php echo (isset($basePath) ? $basePath : ''); ?>assets/images/logo.png" alt="KYROL Logo" class="sidebar-logo" width="120">
        </div>
        
        <ul class="nav flex-column sidebar-nav">
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>" href="<?php echo (isset($basePath) ? $basePath : ''); ?>dashboard.php">
                    <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                </a>
            </li>
            
            <?php if($_SESSION["position"] == "Admin"): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/staff/') !== false) ? 'active' : ''; ?>" href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/staff/index.php">
                    <i class="fas fa-users mr-2"></i> Staff Management
                </a>
            </li>
            <?php endif; ?>
            
            <?php if($_SESSION["position"] == "Admin" || $_SESSION["position"] == "Manager"): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/clients/') !== false) ? 'active' : ''; ?>" href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/clients/index.php">
                    <i class="fas fa-building mr-2"></i> Client Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/suppliers/') !== false) ? 'active' : ''; ?>" href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/suppliers/index.php">
                    <i class="fas fa-truck mr-2"></i> Supplier Management
                </a>
            </li>
            
            <!-- SOA Management Submenu -->
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/soa/') !== false) ? 'active' : ''; ?>" href="#" data-toggle="collapse" data-target="#soaSubmenu" aria-expanded="false">
                    <i class="fas fa-file-invoice-dollar mr-2"></i> SOA Management
                </a>
                <div class="collapse" id="soaSubmenu">
                    <ul class="nav flex-column ml-3">
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'suppliers_soa.php') ? 'active' : ''; ?>" href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/soa/suppliers_soa.php">Supplier SOAs</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'clients_soa.php') ? 'active' : ''; ?>" href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/soa/clients_soa.php">Client SOAs</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/soa/index.php">All SOAs</a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/inventory/') !== false) ? 'active' : ''; ?>" href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/inventory/index.php">
                    <i class="fas fa-boxes mr-2"></i> Inventory Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/documents/') !== false) ? 'active' : ''; ?>" href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/documents/index.php">
                    <i class="fas fa-file-upload mr-2"></i> Document Upload
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/claims/') !== false) ? 'active' : ''; ?>" href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/claims/index.php">
                    <i class="fas fa-receipt mr-2"></i> Claims Management
                </a>
            </li>
            
            <?php if($_SESSION["position"] == "Admin" || $_SESSION["position"] == "Manager"): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/excel/') !== false) ? 'active' : ''; ?>" href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/excel/index.php">
                    <i class="fas fa-file-excel mr-2"></i> Excel Integration
                </a>
            </li>
            <?php endif; ?>
        </ul>
        
        <!-- Version info at bottom of sidebar -->
        <div class="mt-5 pt-5 small text-center text-muted">
            <p>SOA Management v1.0</p>
        </div>
    </div>
</div>
<?php // No closing PHP tag to prevent whitespace issues ?>
