<div class="col-md-2 bg-light sidebar">
    <div class="sidebar-sticky pt-3">
        <!-- Logo at the top of sidebar -->
        <div class="text-center mb-4">
            <img src="<?php echo (isset($basePath) ? $basePath : ''); ?>assets/images/logo.png" alt="KYROL Logo" class="sidebar-logo" width="120">
        </div>

        <ul class="nav flex-column sidebar-nav">
            <!-- 1. Dashboard -->
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>" href="<?php echo (isset($basePath) ? $basePath : ''); ?>dashboard.php">
                    <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                </a>
            </li>

            <?php if($_SESSION["position"] == "Admin" || $_SESSION["position"] == "Manager"): ?>

            <!-- 2. Staff Management -->
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/staff/') !== false) ? 'active' : ''; ?>" href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/staff/index.php">
                    <i class="fas fa-users mr-2"></i> Staff Management
                </a>
            </li>

            <!-- 3. Client Management (dropdown) -->
            <?php
            $clientActive = (strpos($_SERVER['PHP_SELF'], '/modules/clients/') !== false || strpos($_SERVER['PHP_SELF'], '/modules/soa/client') !== false);
            ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $clientActive ? 'active' : ''; ?>" href="#" data-toggle="collapse" data-target="#clientSubmenu" aria-expanded="<?php echo $clientActive ? 'true' : 'false'; ?>">
                    <i class="fas fa-building mr-2"></i> Client Management <i class="fas fa-chevron-down ml-auto small"></i>
                </a>
                <div class="collapse <?php echo $clientActive ? 'show' : ''; ?>" id="clientSubmenu">
                    <ul class="nav flex-column ml-3">
                        <li class="nav-item">
                            <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/clients/') !== false) ? 'active' : ''; ?>" href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/clients/index.php">
                                <i class="fas fa-list mr-2"></i> Client List
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/soa/client') !== false) ? 'active' : ''; ?>" href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/soa/client/index.php">
                                <i class="fas fa-file-invoice-dollar mr-2"></i> Client SOA
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- 4. Supplier Management (dropdown) -->
            <?php
            $supplierActive = (strpos($_SERVER['PHP_SELF'], '/modules/suppliers/') !== false || strpos($_SERVER['PHP_SELF'], '/modules/soa/supplier') !== false || strpos($_SERVER['PHP_SELF'], '/modules/purchase_orders/') !== false);
            ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $supplierActive ? 'active' : ''; ?>" href="#" data-toggle="collapse" data-target="#supplierSubmenu" aria-expanded="<?php echo $supplierActive ? 'true' : 'false'; ?>">
                    <i class="fas fa-truck mr-2"></i> Supplier Management <i class="fas fa-chevron-down ml-auto small"></i>
                </a>
                <div class="collapse <?php echo $supplierActive ? 'show' : ''; ?>" id="supplierSubmenu">
                    <ul class="nav flex-column ml-3">
                        <li class="nav-item">
                            <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/suppliers/') !== false) ? 'active' : ''; ?>" href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/suppliers/index.php">
                                <i class="fas fa-list mr-2"></i> Supplier List
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/soa/supplier') !== false) ? 'active' : ''; ?>" href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/soa/supplier/index.php">
                                <i class="fas fa-file-invoice-dollar mr-2"></i> Supplier SOA
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/purchase_orders/') !== false) ? 'active' : ''; ?>" href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/purchase_orders/index.php">
                                <i class="fas fa-shopping-cart mr-2"></i> Purchase Order
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <?php endif; ?>

            <!-- 5. Outstation Leave -->
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/outstation_leave/') !== false) ? 'active' : ''; ?>" href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/outstation_leave/index.php">
                    <i class="fas fa-plane-departure mr-2"></i> Outstation Leave
                </a>
            </li>

            <!-- 6. Claim Management -->
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/claims/') !== false) ? 'active' : ''; ?>" href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/claims/index.php">
                    <i class="fas fa-receipt mr-2"></i> Claim Management
                </a>
            </li>

            <!-- 7. Inventory Management -->
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/inventory/') !== false) ? 'active' : ''; ?>" href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/inventory/index.php">
                    <i class="fas fa-boxes mr-2"></i> Inventory Management
                </a>
            </li>
        </ul>

        <!-- Version info at bottom of sidebar -->
        <div class="mt-5 pt-5 small text-center text-muted">
            <p>SOA Management v1.0</p>
        </div>
    </div>
</div>
<?php // No closing PHP tag to prevent whitespace issues ?>
