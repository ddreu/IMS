<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">

<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar-custom">
            <!-- Header -->
            <div class="sidebar-custom-header align-items-center px-3">
                <!--<div class="sidebar-custom-logo-icon text-white text-center fw-bold"></div>-->
                <span class="sidebar-custom-logo-text ms-2"></span>
                <i class="bi bi-chevron-double-right ms-auto toggle-btn"></i>
            </div>
            <!-- Search Bar -->
            <!--<div class="sidebar-custom-search-bar px-3">
                <input type="text" class="form-control sidebar-custom-search-input" placeholder="Search">
            </div>-->
            <!-- Menu -->
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="../super_admin/sa_dashboard.php" class="sidebar-custom-nav-link <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
                        <i class="bi bi-house"></i>
                        <span class="sidebar-custom-link-text">Dashboard</span>
                    </a>
                </li>
                <div class="sidebar-custom-divider my-2"></div>
                <li class="nav-item">
                    <a href="../schools/schools.php" class="sidebar-custom-nav-link <?php echo ($current_page == 'schools') ? 'active' : ''; ?>">
                        <i class="bi bi-building"></i>
                        <span class="sidebar-custom-link-text">Schools</span>
                    </a>
                </li>
                <!--<li class="nav-item">
                    <a href="#analyticsMenu" class="sidebar-custom-nav-link" data-bs-toggle="collapse">
                        <i class="bi bi-pie-chart"></i>
                        <span class="sidebar-custom-link-text">Analytics</span>
                    </a>
                    <ul class="collapse sidebar-custom-sub-menu" id="analyticsMenu">
                        <li><a href="#" class="sidebar-custom-nav-link">Overview</a></li>
                        <li><a href="#" class="sidebar-custom-nav-link">Transaction</a></li>
                        <li><a href="#" class="sidebar-custom-nav-link">Viewers</a></li>
                    </ul>
                </li>-->
                <li class="nav-item">
                    <a href="../users/admin_userlist.php" class="sidebar-custom-nav-link <?php echo ($current_page == 'users') ? 'active' : ''; ?>">
                        <i class="bi bi-people"></i>
                        <span class="sidebar-custom-link-text">Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../announcements/sa_announcement.php" class="sidebar-custom-nav-link <?php echo ($current_page == 'announcements') ? 'active' : ''; ?>">
                        <i class="bi bi-megaphone"></i>
                        <span class="sidebar-custom-link-text">Announcements</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="sidebar-custom-nav-link active">
                        <i class="bi bi-people"></i>
                        <span class="sidebar-custom-link-text">Users</span>
                    </a>
                </li>
            </ul>
            <!-- Divider -->
            <div class="sidebar-custom-divider my-2"></div>
            <!-- Settings 
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="#" class="sidebar-custom-nav-link">
                        <i class="bi bi-gear"></i>
                        <span class="sidebar-custom-link-text">Settings</span>
                    </a>
                </li>
            </ul>-->
        </nav>


    </div>

</body>