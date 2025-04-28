<link rel="stylesheet" href="../super_admin/sidebar.css">

<div class="sidebar">
    <div class="sidebar-brand">
        <a href="#" class="logo">
            <!-- <img src="assets/img/DZCM.png" alt="Dashboard Logo"> -->

        </a>
    </div>
    <?php
    $uploads_path = "../uploads/users/";
    $default_image = "../assets/defaults/default-profile.jpg";


    if (!empty($_SESSION['profile_image']) && file_exists($uploads_path . $_SESSION['profile_image'])) {
        $nav_image_path = $uploads_path . $_SESSION['profile_image'];
    } else {
        $nav_image_path = $default_image;
    }
    ?>
    <div class="user-profile">
        <div class="user-avatar">
            <img src="<?php echo $nav_image_path; ?>" alt="User Avatar">
        </div>
        <div class="user-info">
            <h6 class="user-name"><?php echo $_SESSION['firstname']; ?></h6>
            <span class="user-role"><?php echo $_SESSION['role']; ?></span>
        </div>
    </div>

    <nav class="sidebar-menu">
        <div class="menu-category">
            <span>Main</span>
        </div>
        <ul>
            <li class="nav-item">
                <a href="../super_admin/sa_dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i><span>Dashboard</span>
                </a>
            </li>
            <div class="menu-category">
                <span>Management</span>
            </div>
            <li class="nav-item">
                <a href="../schools/schools.php" class="nav-link <?php echo ($current_page == 'schools') ? 'active' : ''; ?>">
                    <i class="fas fa-school"></i><span>Schools</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../users/admin_userlist.php" class="nav-link">
                    <i class="fas fa-user-friends"></i><span>Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../announcements/sa_announcement.php" class="nav-link">
                    <i class="fas fa-bullhorn"></i><span>Announcements</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../user_logs/admin_user_logs.php" class="nav-link">
                    <i class="fas fa-history"></i><span>Activity Logs</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../archive/archives.php" class="nav-link">
                    <i class="fas fa-archive"></i><span>Archives</span>
                </a>
            </li>
            <div class="menu-category">
                <span>Support</span>
            </div>
            <li class="nav-item">
                <a href="../admin-chat/messages.php" class="nav-link">
                    <i class="fas fa-comments"></i><span>Messages</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../super_admin/active-users.php" class="nav-link <?php echo ($current_page == 'active_users') ? 'active' : ''; ?>">
                    <i class="fas fa-user-check"></i><span>Active Users</span>
                </a>
            </li>
        </ul>
    </nav>


    <div class="sidebar-footer">
        <a href="#" class="footer-link">
            <span>&copy; 2025 IMS</span>
        </a>
    </div>

    <!-- <div class="sidebar-footer">
        <a id="logoutBtn" href="#" class="logout-link">
            <i class="fas fa-power-off"></i><span>Logout</span>
        </a>
    </div> -->
    <!-- <div class="sidebar-footer">
        <span class="footer-text">&copy; 2025 IMS</span>
    </div> -->


</div>