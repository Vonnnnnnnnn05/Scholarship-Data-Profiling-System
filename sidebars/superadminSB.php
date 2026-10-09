<style>
    :root {
        --sidebar-bg: #28a745;
        --sidebar-hover: #218838;
        --sidebar-active: #1e7e34;
        --sidebar-text: #ffffff;
        --sidebar-width: 260px;
    }

    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        height: 100vh;
        width: var(--sidebar-width);
        background: var(--sidebar-bg);
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        transition: all 0.3s ease;
    }

    .sidebar-header {
        padding: 20px;
        background: var(--sidebar-active);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .sidebar-logo {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--sidebar-text);
    }

    .sidebar-logo img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.9);
        padding: 5px;
    }

    .sidebar-logo h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }

    .sidebar-user {
        padding: 15px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .user-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: var(--sidebar-active);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: var(--sidebar-text);
        font-weight: 600;
    }

    .user-details h4 {
        margin: 0;
        font-size: 14px;
        color: var(--sidebar-text);
        font-weight: 600;
    }

    .user-details p {
        margin: 3px 0 0 0;
        font-size: 12px;
        color: rgba(255, 255, 255, 0.8);
    }

    .sidebar-menu {
        padding: 10px 0;
        overflow-y: auto;
        height: calc(100vh - 180px);
    }

    .sidebar-menu::-webkit-scrollbar {
        width: 5px;
    }

    .sidebar-menu::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 10px;
    }

    .menu-item {
        padding: 12px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--sidebar-text);
        text-decoration: none;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .menu-item:hover {
        background: var(--sidebar-hover);
        padding-left: 25px;
    }

    .menu-item.active {
        background: var(--sidebar-active);
        border-left: 4px solid var(--sidebar-text);
        padding-left: 21px;
    }

    .menu-item i {
        width: 20px;
        font-size: 16px;
    }

    .menu-item span {
        font-size: 14px;
        font-weight: 500;
    }

    .menu-section {
        padding: 15px 20px 8px;
        font-size: 11px;
        color: rgba(255, 255, 255, 0.6);
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 1px;
    }

    /* Mobile Topbar & Close Button Default Hidden on Desktop */
    .mobile-topbar {
        display: none;
    }

    .sidebar-close-btn {
        display: none;
    }

    .sidebar-overlay {
        display: none;
    }

    /* Mobile & Tablet Preview Responsiveness */
    @media (max-width: 1024px) {
        .mobile-topbar {
            display: flex;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            padding: 0 16px;
            align-items: center;
            justify-content: space-between;
            z-index: 1030;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
        }

        .mobile-menu-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border: none;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.18);
            color: var(--sidebar-text);
            font-size: 20px;
            cursor: pointer;
            transition: all 0.2s ease;
            -webkit-tap-highlight-color: transparent;
        }

        .mobile-menu-toggle:hover,
        .mobile-menu-toggle:focus {
            background: rgba(255, 255, 255, 0.28);
            outline: none;
        }

        .mobile-menu-toggle:active {
            transform: scale(0.95);
        }

        .mobile-topbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--sidebar-text);
            font-size: 17px;
            font-weight: 700;
            letter-spacing: 0.5px;
            user-select: none;
        }

        .mobile-topbar-brand img {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #ffffff;
            padding: 3px;
        }

        .mobile-topbar-user {
            display: flex;
            align-items: center;
        }

        .mobile-user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--sidebar-active);
            color: var(--sidebar-text);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 700;
            border: 2px solid rgba(255, 255, 255, 0.35);
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            height: 100dvh;
            width: 280px;
            max-width: 85vw;
            transform: translateX(-100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1050;
            box-shadow: 4px 0 25px rgba(0, 0, 0, 0.25);
            will-change: transform;
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sidebar-close-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.18);
            color: var(--sidebar-text);
            font-size: 18px;
            cursor: pointer;
            transition: background 0.2s ease;
            -webkit-tap-highlight-color: transparent;
        }

        .sidebar-close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .sidebar-overlay {
            display: block;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
            z-index: 1040;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        body.sidebar-open {
            overflow: hidden;
        }

        /* Content spacing for mobile preview with fixed topbar */
        .main-content {
            margin-left: 0 !important;
            padding-top: 80px !important;
            padding-left: 20px !important;
            padding-right: 20px !important;
            padding-bottom: 30px !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
    }

    @media (max-width: 768px) {
        .main-content {
            padding-top: 75px !important;
            padding-left: 14px !important;
            padding-right: 14px !important;
            padding-bottom: 25px !important;
        }

        .page-header {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 12px !important;
            margin-bottom: 20px !important;
        }

        .page-header h1 {
            font-size: 22px !important;
            line-height: 1.25 !important;
        }

        .page-header > div,
        .page-header-actions {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 8px !important;
            width: 100% !important;
        }

        .page-header .btn {
            flex: 1 1 auto !important;
            justify-content: center !important;
            font-size: 13px !important;
            padding: 9px 14px !important;
        }

        .stats-grid {
            grid-template-columns: 1fr !important;
            gap: 12px !important;
            margin-bottom: 20px !important;
        }

        .stat-card {
            padding: 16px !important;
            gap: 15px !important;
        }

        .stat-icon {
            width: 50px !important;
            height: 50px !important;
            font-size: 20px !important;
        }

        .stat-info h3 {
            font-size: 24px !important;
        }

        .dashboard-grid {
            grid-template-columns: 1fr !important;
            gap: 16px !important;
        }

        .card {
            padding: 16px !important;
            border-radius: 8px !important;
            margin-bottom: 16px !important;
        }

        .card-header {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 10px !important;
            margin-bottom: 15px !important;
            padding-bottom: 10px !important;
        }

        .card-header h2 {
            font-size: 17px !important;
        }

        .filters {
            grid-template-columns: 1fr !important;
            gap: 10px !important;
        }

        .filter-group input,
        .filter-group select {
            width: 100% !important;
            font-size: 15px !important;
            padding: 10px 12px !important;
            box-sizing: border-box !important;
        }

        .filters .btn {
            width: 100% !important;
            justify-content: center !important;
            padding: 10px 15px !important;
        }

        .table-wrapper {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
            width: 100% !important;
            margin-bottom: 15px !important;
            border: 1px solid #e0e0e0 !important;
            border-radius: 8px !important;
        }

        table {
            min-width: 650px !important;
            width: 100% !important;
        }

        table th, table td {
            padding: 10px 12px !important;
            font-size: 13px !important;
            white-space: nowrap !important;
        }

        .form-grid {
            grid-template-columns: 1fr !important;
            gap: 14px !important;
        }

        .form-actions {
            flex-direction: column !important;
            gap: 10px !important;
        }

        .form-actions .btn {
            width: 100% !important;
            justify-content: center !important;
        }

        .chart-container {
            height: 260px !important;
            position: relative !important;
        }

        .pagination {
            flex-wrap: wrap !important;
            justify-content: center !important;
            gap: 6px !important;
        }

        .pagination a, .pagination span {
            padding: 6px 12px !important;
            font-size: 13px !important;
        }
    }
</style>

<!-- Mobile Topbar with Hamburger Menu Button -->
<div class="mobile-topbar">
    <button type="button" class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle navigation">
        <i class="fas fa-bars"></i>
    </button>
    <div class="mobile-topbar-brand">
        <img src="../images/logo.png" alt="SKSU Logo">
        <span>SKSU SDP</span>
    </div>
    <div class="mobile-topbar-user">
        <div class="mobile-user-avatar" title="<?php echo htmlspecialchars($_SESSION['name'] ?? 'Super Admin'); ?>">
            <?php echo strtoupper(substr($_SESSION['name'] ?? 'S', 0, 1)); ?>
        </div>
    </div>
</div>

<!-- Mobile Sidebar Overlay Backdrop -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="../images/logo.png" alt="SKSU Logo">
            <h3>SKSU SDP</h3>
        </div>
        <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close sidebar">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="sidebar-user">
        <div class="user-info">
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
            </div>
            <div class="user-details">
                <h4><?php echo $_SESSION['name']; ?></h4>
                <p>Super Admin</p>
            </div>
        </div>
    </div>

    <nav class="sidebar-menu">
        <a href="dashboard.php" class="menu-item active">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>

        <div class="menu-section">Scholar Management</div>
        
        <a href="scholars.php" class="menu-item">
            <i class="fas fa-user-graduate"></i>
            <span>Scholars</span>
        </a>

        <a href="add-scholar.php" class="menu-item">
            <i class="fas fa-user-plus"></i>
            <span>Add Scholar</span>
        </a>

        <div class="menu-section">Scholarship Programs</div>

        <a href="scholarships.php" class="menu-item">
            <i class="fas fa-award"></i>
            <span>Scholarships</span>
        </a>

        <a href="scholarship-distribution.php" class="menu-item">
            <i class="fas fa-chart-pie"></i>
            <span>Distribution Report</span>
        </a>

        <div class="menu-section">Campus Management</div>

        <a href="campuses.php" class="menu-item">
            <i class="fas fa-building"></i>
            <span>Campuses</span>
        </a>

        <div class="menu-section">User Management (Full Control)</div>

        <a href="users.php" class="menu-item">
            <i class="fas fa-users"></i>
            <span>View Users</span>
        </a>

        <a href="add-user.php" class="menu-item">
            <i class="fas fa-user-plus"></i>
            <span>Add User</span>
        </a>

        <a href="manage-users.php" class="menu-item">
            <i class="fas fa-users-cog"></i>
            <span>Manage Users</span>
        </a>

        <div class="menu-section">System Management</div>

        <a href="audit-logs.php" class="menu-item">
            <i class="fas fa-history"></i>
            <span>Audit Logs</span>
        </a>

       

        <div class="menu-section">Reports & Analytics</div>

        <a href="reports.php" class="menu-item">
            <i class="fas fa-file-alt"></i>
            <span>Reports</span>
        </a>

        <a href="analytics.php" class="menu-item">
            <i class="fas fa-chart-bar"></i>
            <span>Analytics</span>
        </a>

        <div class="menu-section">Account</div>

        <a href="profile.php" class="menu-item">
            <i class="fas fa-user-circle"></i>
            <span>My Profile</span>
        </a>

        <a href="#" class="menu-item" onclick="confirmLogout(event)">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </nav>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function confirmLogout(event) {
        event.preventDefault();
        Swal.fire({
            title: 'Are you sure to logout?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#5f6368',
            confirmButtonText: 'Yes, logout',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../logout.php';
            }
        });
    }

    // Mobile Hamburger Menu & Sidebar Controller
    (function() {
        function initMobileSidebar() {
            const toggleBtn = document.getElementById('mobileMenuToggle');
            const closeBtn = document.getElementById('sidebarCloseBtn');
            const overlay = document.getElementById('sidebarOverlay');
            const sidebar = document.querySelector('.sidebar');

            if (!toggleBtn || !sidebar) return;

            function openSidebar() {
                sidebar.classList.add('active');
                if (overlay) overlay.classList.add('active');
                document.body.classList.add('sidebar-open');
                toggleBtn.setAttribute('aria-expanded', 'true');
            }

            function closeSidebar() {
                sidebar.classList.remove('active');
                if (overlay) overlay.classList.remove('active');
                document.body.classList.remove('sidebar-open');
                toggleBtn.setAttribute('aria-expanded', 'false');
            }

            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (sidebar.classList.contains('active')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });

            if (closeBtn) {
                closeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeSidebar();
                });
            }

            if (overlay) {
                overlay.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeSidebar();
                });
            }

            // Close on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && sidebar.classList.contains('active')) {
                    closeSidebar();
                }
            });

            // Close on navigation link click when on mobile screen
            document.querySelectorAll('.sidebar .menu-item').forEach(function(item) {
                item.addEventListener('click', function() {
                    if (window.innerWidth <= 1024) {
                        closeSidebar();
                    }
                });
            });

            // Auto-close on resize to desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth > 1024 && sidebar.classList.contains('active')) {
                    closeSidebar();
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initMobileSidebar);
        } else {
            initMobileSidebar();
        }
    })();
</script>
