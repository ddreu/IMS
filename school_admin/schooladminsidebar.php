<!-- Sidebar -->
<nav id="sidebar-admin" class="sidebar-admin-container">
    <div class="sidebar-admin-header">
        <h1><span>IMS</span></h1>
    </div>

    <ul class="sidebar-admin-menu">
        <!-- Home Link -->
        <li class="sidebar-admin-item <?php echo ($current_page == 'admindashboard') ? 'active' : ''; ?>">
            <a href="../school_admin/schooladmindashboard.php" class="sidebar-admin-item-link">
                <i class="fas-fa-th-large sidebar-admin-item-link-icon"></i><span>Dashboard</span>
            </a>
        </li>

        <!-- Announcement Link -->
        <li class="sidebar-admin-item <?php echo ($current_page == 'adminannouncement') ? 'active' : ''; ?>">
            <a href="../announcements/adminannouncement.php" class="sidebar-admin-item-link">
                <i class="fas fa-bullhorn sidebar-admin-item-link-icon"></i><span> Announcement</span>
            </a>
        </li>

        <!-- User List Link -->
        <li class="sidebar-admin-item <?php echo ($current_page == 'committeelist') ? 'active' : ''; ?>">
            <a href="#" class="sidebar-admin-item-link">
                <i class="fas fa-users sidebar-admin-item-link-icon"></i><span>Users</span>
            </a>
            <div id="userlistDropdown" class="sidebar-admin-dropdown">
                <?php foreach ($departments as $department) { ?>
                    <a href="../users/userlist.php?selected_department_id=<?php echo $department['id']; ?>" class="sidebar-admin-item-link">
                        <?php echo htmlspecialchars($department['department_name']); ?>
                    </a>
                <?php } ?>
            </div>
        </li>

        <!-- Curriculum Dropdown -->
        <li class="sidebar-admin-item <?php echo ($current_page == 'departments') ? 'active' : ''; ?>">
            <a href="#" class="sidebar-admin-item-link">
                <i class="fas fa-building sidebar-admin-item-link-icon"></i> <span>Departments</span>
            </a>

            <!-- Department List (hidden by default) -->
            <div id="departmentDropdown" class="sidebar-admin-dropdown">
                <?php foreach ($departments as $department) { ?>
                    <a href="../departments/departments.php?selected_department_id=<?php echo $department['id']; ?>" class="sidebar-admin-item-link">
                        <?php echo htmlspecialchars($department['department_name']); ?>
                    </a>
                <?php } ?>
            </div>
        </li>
        <li class="sidebar-admin-item <?php echo ($current_page == '') ? 'active' : ''; ?>">
            <a href="../rankings/leaderboards.php" class="sidebar-admin-item-link">
                <i class="fas fa-trophy sidebar-admin-item-link-icon"></i><span> Leaderboards</span>
            </a>
        </li>

        <!-- Additional Links -->
        <li class="sidebar-admin-item <?php echo ($current_page == 'games') ? 'active' : ''; ?>">
            <a href="../games/games.php" class="sidebar-admin-item-link">
                <i class="fas fa-gamepad sidebar-admin-item-link-icon"></i><span> Games</span>
            </a>
        </li>
        <li class="sidebar-admin-item <?php echo ($current_page == 'brackets') ? 'active' : ''; ?>">
            <a href="../brackets/brackets1.php" class="sidebar-admin-item-link">
                <i class="fas fa-sitemap sidebar-admin-item-link-icon"></i><span> Brackets</span>
            </a>
        </li>
        <li class="sidebar-admin-item <?php echo ($current_page == 'schedule') ? 'active' : ''; ?>">
            <a href="#" class="sidebar-admin-item-link">
                <i class="fas fa-calendar sidebar-admin-item-link-icon"></i><span> Schedules</span>
            </a>
            <div id="schedulesDropdown" class="sidebar-admin-dropdown">
                <?php foreach ($departments as $department) { ?>
                    <a href="../schedule/schedule.php?selected_department_id=<?php echo $department['id']; ?>" class="sidebar-admin-item-link">
                        <?php echo htmlspecialchars($department['department_name']); ?>
                    </a>
                <?php } ?>
            </div>
        </li>
        <li class="sidebar-admin-item <?php echo ($current_page == 'matchlist') ? 'active' : ''; ?>">
            <a href="#" class="sidebar-admin-item-link">
                <i class="fas fa-clipboard-list sidebar-admin-item-link-icon"></i><span> Match List </span>
            </a>
            <div id="matchlistDropdown" class="sidebar-admin-dropdown">
                <?php foreach ($departments as $department) { ?>
                    <a href="../livescoring/admin_match_list.php?selected_department_id=<?php echo $department['id']; ?>" class="sidebar-admin-item-link">
                        <?php echo htmlspecialchars($department['department_name']); ?>
                    </a>
                <?php } ?>
            </div>
        </li>
        <li class="sidebar-admin-item <?php echo ($current_page == 'user_settings') ? 'active' : ''; ?>">
            <a href="../profile_settings/profile_settings.php" class="sidebar-admin-item-link">
                <i class="fas fa-cog sidebar-admin-item-link-icon"></i><span> Profile Settings </span>
            </a>
        </li>


    </ul>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        // Generic Dropdown toggle function
        function toggleDropdown(dropdownId) {
            const dropdown = document.getElementById(dropdownId);
            dropdown.classList.toggle('open'); // Toggle 'open' class for dropdown visibility
        }

        // Initially ensure that all dropdowns are closed by removing the 'open' class
        document.querySelectorAll('.sidebar-admin-dropdown').forEach(dropdown => {
            dropdown.classList.remove('open');
        });

        // Adding event listeners to all links with href="#"
        document.querySelectorAll('a[href="#"]').forEach(link => {
            link.addEventListener('click', function(event) {
                event.preventDefault();
                const parentLi = this.closest('li'); // Get the closest li
                const dropdownId = parentLi.querySelector('div.sidebar-admin-dropdown')?.id;
                if (dropdownId) {
                    toggleDropdown(dropdownId); // Toggle the respective dropdown
                }
            });
        });

        // Sidebar Scrollbar Control
        const sidebar = document.getElementById('sidebar-admin');
        let timeout;

        sidebar.addEventListener('scroll', function() {
            sidebar.classList.add('scrolling');
            clearTimeout(timeout);

            timeout = setTimeout(function() {
                sidebar.classList.remove('scrolling');
            }, 500); // Adjust this timeout as needed
        });
    });
</script>