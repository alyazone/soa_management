<div class="modern-sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <div class="logo-icon">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div class="logo-text">
                <h2>KYROL</h2>
                <p>SOA Management</p>
            </div>
        </div>
        <button class="sidebar-collapse-btn" id="sidebarCollapseBtn">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="<?php echo (isset($basePath) ? $basePath : ''); ?>dashboard.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <?php if($_SESSION["position"] == "Admin"): ?>
            <li class="nav-item">
                <a href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/staff/index.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/staff/') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Staff Management</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if($_SESSION["position"] == "Admin" || $_SESSION["position"] == "Manager"): ?>
            <li class="nav-item">
                <a href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/clients/index.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/clients/') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-building"></i>
                    <span>Client Management</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/suppliers/index.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/suppliers/') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-truck"></i>
                    <span>Supplier Management</span>
                </a>
            </li>
            <li class="nav-item has-submenu">
                <a href="#" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/soa/') !== false) ? 'active' : ''; ?>" data-toggle="submenu">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>SOA Management</span>
                    <i class="fas fa-chevron-right submenu-arrow"></i>
                </a>
                <ul class="submenu <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/soa/') !== false) ? 'show' : ''; ?>">
                    <li>
                        <a href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/soa/client/index.php" class="submenu-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/soa/client') !== false) ? 'active' : ''; ?>">
                            <i class="fas fa-building"></i>
                            <span>Client SOA</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/soa/supplier/index.php" class="submenu-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/soa/supplier') !== false) ? 'active' : ''; ?>">
                            <i class="fas fa-truck"></i>
                            <span>Supplier SOA</span>
                        </a>
                    </li>
                </ul>
            </li>
            <li class="nav-item">
                <a href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/inventory/index.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/inventory/') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-boxes"></i>
                    <span>Inventory Management</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/documents/index.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/documents/') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-file-upload"></i>
                    <span>Document Upload</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/claims/index.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/claims/') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-receipt"></i>
                    <span>Claims Management</span>
                </a>
            </li>
            <li class="nav-item has-submenu">
                <a href="#" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/outstation/') !== false) ? 'active' : ''; ?>" data-toggle="submenu">
                    <i class="fas fa-plane"></i>
                    <span>Outstation Leave</span>
                    <i class="fas fa-chevron-right submenu-arrow"></i>
                </a>
                <ul class="submenu <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/outstation/') !== false) ? 'show' : ''; ?>">
                    <li>
                        <a href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/outstation/index.php" class="submenu-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['PHP_SELF'], '/modules/outstation/') !== false) ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i>
                            <span>My Applications</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/outstation/application_form.php" class="submenu-link <?php echo (basename($_SERVER['PHP_SELF']) == 'application_form.php') ? 'active' : ''; ?>">
                            <i class="fas fa-plus-circle"></i>
                            <span>New Application</span>
                        </a>
                    </li>
                    <?php if($_SESSION["position"] == "Admin" || $_SESSION["position"] == "Manager"): ?>
                    <li>
                        <a href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/outstation/dashboard.php" class="submenu-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php' && strpos($_SERVER['PHP_SELF'], '/modules/outstation/') !== false) ? 'active' : ''; ?>">
                            <i class="fas fa-chart-pie"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php if($_SESSION["position"] == "Admin" || $_SESSION["position"] == "Manager"): ?>
            <li class="nav-item">
                <a href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/excel/index.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/excel/') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-file-excel"></i>
                    <span>Excel Integration</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <div class="user-info" id="userInfoDropdown" tabindex="0">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo $_SESSION["full_name"]; ?></div>
                <div class="user-role"><?php echo $_SESSION["position"]; ?></div>
            </div>
            <div class="user-dropdown-menu" id="userDropdownMenu">
               <a href="<?php echo (isset($basePath) ? $basePath : ''); ?>modules/auth/logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        <div class="version-info">
            <span>SOA Management v2.0</span>
        </div>
    </div>
</div>

<style>
.modern-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: var(--sidebar-width);
    height: 100vh;
    background: white;
    border-right: 1px solid var(--gray-200);
    display: flex;
    flex-direction: column;
    z-index: 50;
    transition: var(--transition);
    box-shadow: var(--shadow-lg);
}

.sidebar-collapsed .modern-sidebar {
    width: 80px;
}

.sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    border-bottom: 1px solid var(--gray-200);
    min-height: var(--header-height);
}

.sidebar-logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.logo-icon {
    width: 40px;
    height: 40px;
    background: var(--primary-color);
    border-radius: var(--border-radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
}

.logo-text h2 {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--gray-900);
    line-height: 1;
}

.logo-text p {
    font-size: 0.75rem;
    color: var(--gray-600);
    margin-top: 0.125rem;
}

.sidebar-collapsed .logo-text {
    display: none;
}

.sidebar-collapse-btn {
    width: 32px;
    height: 32px;
    border: none;
    background: var(--gray-100);
    border-radius: var(--border-radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gray-600);
    cursor: pointer;
    transition: var(--transition);
}

.sidebar-collapse-btn:hover {
    background: var(--primary-color);
    color: white;
}

.sidebar-collapsed .sidebar-collapse-btn i {
    transform: rotate(180deg);
}

.sidebar-nav {
    flex: 1;
    padding: 1rem 0;
    overflow-y: auto;
}

.nav-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-item {
    margin-bottom: 0.25rem;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1.5rem;
    color: var(--gray-700);
    text-decoration: none;
    font-weight: 500;
    font-size: 0.875rem;
    transition: var(--transition);
    position: relative;
}

.nav-link:hover {
    background: var(--gray-50);
    color: var(--primary-color);
}

.nav-link.active {
    background: rgba(59, 130, 246, 0.1);
    color: var(--primary-color);
    border-right: 3px solid var(--primary-color);
}

.nav-link i {
    width: 20px;
    text-align: center;
    font-size: 1rem;
}

.sidebar-collapsed .nav-link span {
    display: none;
}

.sidebar-collapsed .nav-link {
    justify-content: center;
    padding: 0.875rem;
}

.submenu-arrow {
    margin-left: auto !important;
    width: auto !important;
    font-size: 0.75rem !important;
    transition: var(--transition);
}

.has-submenu .nav-link.active .submenu-arrow,
.has-submenu .nav-link:hover .submenu-arrow {
    transform: rotate(90deg);
}

.submenu {
    list-style: none;
    padding: 0;
    margin: 0;
    max-height: 0;
    overflow: hidden;
    transition: var(--transition);
    background: var(--gray-50);
}

.submenu.show {
    max-height: 200px;
}

.sidebar-collapsed .submenu {
    display: none;
}

.submenu-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1.5rem 0.75rem 3rem;
    color: var(--gray-600);
    text-decoration: none;
    font-size: 0.875rem;
    transition: var(--transition);
}

.submenu-link:hover {
    background: var(--gray-100);
    color: var(--primary-color);
}

.submenu-link.active {
    background: var(--gray-100);
    color: var(--primary-color);
}

.submenu-link i {
    width: 16px;
    text-align: center;
    font-size: 0.875rem;
}

.sidebar-footer {
    padding: 1.5rem;
    border-top: 1px solid var(--gray-200);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.user-avatar {
    width: 40px;
    height: 40px;
    background: var(--gray-200);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gray-600);
}

.user-details {
    flex: 1;
}

.user-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--gray-900);
    line-height: 1;
}

.user-role {
    font-size: 0.75rem;
    color: var(--gray-600);
    margin-top: 0.125rem;
}

.sidebar-collapsed .user-details {
    display: none;
}

.version-info {
    text-align: center;
    font-size: 0.75rem;
    color: var(--gray-500);
}

.sidebar-collapsed .version-info {
    display: none;
}

/* Mobile Sidebar */
@media (max-width: 1024px) {
    .modern-sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar-open .modern-sidebar {
        transform: translateX(0);
    }
    
    .sidebar-open::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 40;
    }
}
</style>
