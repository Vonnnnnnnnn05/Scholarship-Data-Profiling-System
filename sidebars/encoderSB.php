<style>
    :root {
        --sidebar-bg: #1a73e8;
        --sidebar-hover: #1557b0;
        --sidebar-active: #0d47a1;
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

    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.active {
            transform: translateX(0);
        }
    }
</style>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="../images/logo.png" alt="SKSU Logo">
            <h3>SKSU SDP</h3>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="user-info">
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
            </div>
            <div class="user-details">
                <h4><?php echo $_SESSION['name']; ?></h4>
                <p>Encoder</p>
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

        <div class="menu-section">Reports</div>

        <a href="reports.php" class="menu-item">
            <i class="fas fa-file-alt"></i>
            <span>My Reports</span>
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
            confirmButtonColor: '#1a73e8',
            cancelButtonColor: '#5f6368',
            confirmButtonText: 'Yes, logout',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../logout.php';
            }
        });
    }
</script>
