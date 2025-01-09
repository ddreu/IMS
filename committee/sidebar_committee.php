<!-- sidebar.php -->
<nav class="sidebar-nav">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <i class="fas fa-user"></i>
        <span class="sidebar-title">Committee</span>
    </div>

    <ul class="list-unstyled components">
        <li class="<?php echo ($current_page == 'committeedashboard') ? 'active' : ''; ?>">
            <a href="../committee/committeedashboard.php" class="sidebar-link">
                <i class="fas fa-home"></i>
                <span class="sidebar-item">Dashboard</span>
            </a>
        </li>
        <li class="<?php echo ($current_page == 'teams') ? 'active' : ''; ?>">
            <a href="../teams/teams.php" class="sidebar-link">
                <i class="fas fa-users"></i>
                <span class="sidebar-item">Teams</span>
            </a>
        </li>
        <li class="<?php echo ($current_page == 'gamerules') ? 'active' : ''; ?>">
            <a href="../scoring_rules/scoring_rules_form.php" class="sidebar-link">
                <i class="fas fa-calendar"></i>
                <span class="sidebar-item">Scoring Rules</span>
            </a>
        </li>
        <li class="<?php echo ($current_page == 'Brackets') ? 'active' : ''; ?>">
            <a href="../brackets/brackets.php" class="sidebar-link">
                <i class="fas fa-sitemap"></i></i> Brackets
            </a>
        </li>
        <li class="<?php echo ($current_page == 'schedules') ? 'active' : ''; ?>">
            <a href="../schedule/schedule.php" class="sidebar-link">
                <i class="fas fa-calendar"></i>
                <span class="sidebar-item">Schedules</span>
            </a>
        </li>
        <li class="<?php echo ($current_page == 'matchlist') ? 'active' : ''; ?>">
            <a href="../livescoring/match_list.php" class="sidebar-link">
                <i class="fas fa-clipboard-list"></i>
                <span class="sidebar-item">Match List</span>
            </a>
        </li>
        <li class="<?php echo ($current_page == 'profile') ? 'active' : ''; ?>">
            <a href="../profile_settings/profile_settings.php" class="sidebar-link">
                <i class="fas fa-cog"></i>
                <span class="sidebar-item">Profile Settings</span>
            </a>
        </li>
        <li>
            <a href="#" class="sidebar-link sidebar-logout" id="logoutBtn">
                <i class="fas fa-sign-out-alt"></i>
                <span class="sidebar-item">Log out</span>
            </a>
        </li>
    </ul>
</nav>


<!-- Include SweetAlert -->
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

<script>
    document.getElementById('logoutBtn').addEventListener('click', function(event) {
        event.preventDefault(); // Prevent the default anchor action
        // Show SweetAlert confirmation dialog
        swal({
                title: "Are you sure?",
                text: "You will be logged out of your account.",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
            .then((willLogout) => {
                if (willLogout) {
                    // Show success message
                    swal("Successfully logged out!", {
                        icon: "success",
                        buttons: false, // Disable button to prevent user interaction
                        timer: 1000, // Set a timer for 2 seconds
                    }).then(() => {
                        // After 2 seconds, redirect to logout.php
                        window.location.href = '../logout.php';
                    });
                } else {
                    // Optionally show a success message after canceling
                    swal("Your session is safe!", {
                        icon: "info",
                    });
                }
            });
    });
</script>