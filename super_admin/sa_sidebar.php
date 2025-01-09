<link rel="stylesheet" href="../scss/sa.css">


<nav class="custom-navbar">
    <ul class="custom-navbar__menu">
        <li class="custom-navbar__item">
            <a href="../super_admin/sa_dashboard.php" class="custom-navbar__link <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i><span>Dashboard</span></a>
        </li>
        <li class="custom-navbar__item">
            <a href="../schools/schools.php" class="custom-navbar__link <?php echo ($current_page == 'schools') ? 'active' : ''; ?>">
                <i data-feather="layers"></i><span>Schools</span></a>
        </li>
        <li class="custom-navbar__item">
            <a href="../users/admin_userlist.php" class="custom-navbar__link">
                <i data-feather="users"></i><span>Users</span></a>
        </li>
        <li class="custom-navbar__item">
            <a href="../announcements/sa_announcement.php" class="custom-navbar__link"><i data-feather="message-square"></i><span>Announcements</span></a>
        </li>
       <!-- <li class="custom-navbar__item">
            <a href="#" class="custom-navbar__link"><i data-feather="folder"></i><span>Projects</span></a>
        </li>
        <li class="custom-navbar__item">
            <a href="#" class="custom-navbar__link"><i data-feather="archive"></i><span>Resources</span></a>
        </li>
        <li class="custom-navbar__item">
            <a href="#" class="custom-navbar__link"><i data-feather="help-circle"></i><span>Help</span></a>
        </li>
        <li class="custom-navbar__item">
            <a href="#" class="custom-navbar__link"><i data-feather="settings"></i><span>Settings</span></a>
        </li>-->
        
    </ul>
</nav>
<script src="https://unpkg.com/feather-icons"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        feather.replace();
    });
</script>