<!-- Sidebar -->
<nav id="sidebar">
    <div class="sidebar-header">


        <h1>IMS</h1>
        </a>
    </div>

    <ul class="list-unstyled components">
        <li class="<?php echo ($current_page == 'admindashboard') ? 'active' : ''; ?>">
            <a href="../department_admin/departmentadmindashboard.php"><i class="fas fa-home"></i> Home</a>
        </li>
        <li class="<?php echo ($current_page == 'adminannouncement') ? 'active' : ''; ?>">
            <a href="../announcements/adminannouncement.php"><i class="fas fa-bullhorn"></i> Announcement</a>
        </li>
        <li class="<?php echo ($current_page == 'committeelist') ? 'active' : ''; ?>">
            <a href="../users/userlist.php"><i class="fas fa-users"></i> Users</a>
        </li>
        <li class="<?php echo ($current_page == 'departments') ? 'active' : ''; ?>">
            <a href="../departments/departments.php"><i class="fas fa-school"></i> Curriculum</a>
        </li>
        <li class="<?php echo ($current_page == 'games') ? 'active' : ''; ?>">
            <a href="../games/games.php"><i class="fas fa-basketball-ball"></i> Games</a>
        </li>
        <li class="<?php echo ($current_page == 'Brackets') ? 'active' : ''; ?>">
            <a href="../brackets/brackets1.php"><i class="fas fa-sitemap"></i></i> Brackets</a>
        </li>
        <li class="<?php echo ($current_page == 'schedule') ? 'active' : ''; ?>">
            <a href="../schedule/schedule.php"><i class="fas fa-calendar"></i> Schedules</a>
        </li>
        <li class="<?php echo ($current_page == 'matchlist') ? 'active' : ''; ?>">
            <a href="../livescoring/admin_match_list.php"><i class="fas fa-clipboard-list"></i> Match List</a>
        </li>
        <li class="<?php echo ($current_page == 'user settings') ? 'active' : ''; ?>">
            <a href="../profile_settings/profile_settings.php"><i class="fas fa-cog"></i> Profile Settings</a>
        </li>
        <li class="">
            <a class="nav-link logout" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </li>
    </ul>
</nav>

<!-- SweetAlert Script for Logout Confirmation -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelector('.logout').addEventListener('click', function(event) {
            event.preventDefault();

            Swal.fire({
                title: 'Are you sure?',
                text: 'You will be logged out!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, logout!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = this.href;
                }
            });
        });
    });
</script>

</body>

</html>