<?php
$school_id = $_SESSION['school_id'];
$role = $_SESSION['role'];

// Fetch departments for the school
$conn = con();

// Fetch school logo
$stmt = $conn->prepare("SELECT logo, school_name FROM schools WHERE school_id = ?");
$stmt->bind_param("i", $school_id);
$stmt->execute();
$result = $stmt->get_result();
$school = $result->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("SELECT id, department_name FROM departments WHERE school_id = ? AND is_archived = 0");
$stmt->bind_param("i", $school_id);
$stmt->execute();
$result = $stmt->get_result();
$departments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();


$showGameLinks = isset($_SESSION['game_id'], $_SESSION['game_name'], $_SESSION['department_id'], $_SESSION['department_name']);

?>
<!-- Sidebar -->
<nav id="sidebar" class="mt-0 mt-sm-2 mt-md-3 mt-lg-4 mt-xl-5 overflow-auto">
    <div class="sidebar-header-logo text-center p-3 bg-light bg-gradient rounded-3 mx-2 mt-4">
        <!-- Logo section always visible -->
        <div class="d-flex justify-content-center align-items-center logo-container">
            <div class="position-relative logo-wrapper">
                <img src="../uploads/logos/<?php echo htmlspecialchars($school['logo']); ?>"
                    alt="<?php echo htmlspecialchars($school['school_name']); ?>"
                    class="img-fluid rounded-circle shadow-lg border border-2 border-primary p-2 logo-image"
                    style="max-width: 110px; height: auto; object-fit: contain;">
                <div class="position-absolute bottom-0 start-50 translate-middle-x mb-n2 badge-container">
                    <!-- <span class="badge bg-primary px-3 py-2 rounded-pill shadow-sm">
                        <i class="fas fa-school me-1"></i>School
                    </span> -->
                </div>
            </div>
        </div>



    </div>

    <ul class="list-unstyled components">


        <?php if ($showGameLinks): ?>
            <li class="nav-item">
                <a href="#gameSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle <?php echo ($current_page == 'Dashboard') ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span>
                        <?php echo htmlspecialchars($_SESSION['game_name']); ?><br>
                        <?php echo htmlspecialchars($_SESSION['department_name']); ?>
                    </span>
                </a>
                <ul class="collapse list-unstyled" id="gameSubmenu">
                    <li>
                        <a class="submenu-item <?php echo ($current_page == 'Dashboard') ? 'active' : ''; ?>" href="../committee/committeedashboard.php">
                            <i class="fas fa-th-large"></i>
                            Game <br>Dashboard
                        </a>
                    </li>
                    <li>
                        <a class="submenu-item <?php echo ($current_page == 'Teams') ? 'active' : ''; ?>" href="../teams/teams.php">
                            <i class="fas fa-users"></i>
                            Teams
                        </a>
                    </li>
                    <li>
                        <a class="submenu-item <?php echo ($current_page == 'Scoring Rules') ? 'active' : ''; ?>" href="../scoring_rules/scoring_rules_form.php">
                            <i class="fas fa-book-open"></i>
                            Scoring Rules
                        </a>
                    </li>
                    <li>
                        <a class="submenu-item <?php echo ($current_page == 'Brackets') ? 'active' : ''; ?>" href="../brackets/brackets.php">
                            <i class="fas fa-sitemap"></i>
                            Brackets
                        </a>
                    </li>
                    <!-- <li>
                        <a class="submenu-item <?php echo ($current_page == 'Schedules') ? 'active' : ''; ?>" href="../schedule/schedule.php">
                            <i class="fas fa-calendar-alt"></i>
                            Schedules
                        </a>
                    </li> -->
                    <li>
                        <a class="submenu-item <?php echo ($current_page == 'Match') ? 'active' : ''; ?>" href="../livescoring/match_list.php">
                            <i class="fas fa-play-circle"></i>
                            Match
                        </a>
                    </li>
                    <li>
                        <a class="submenu-item <?php echo ($current_page == 'Rankings') ? 'active' : ''; ?>" href="../rankings/leaderboards.php">
                            <i class="fas fa-chart-bar"></i>
                            Rankings
                        </a>
                    </li>
                </ul>
            </li>
        <?php endif; ?>



        <li class="nav-item">
            <a class="fw-bold small text-muted px-3 mt-3">
                <i class="fas fa-user-tie"></i>
                <span> <?php echo $role; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <hr class="my-2 mx-3 border-top border-secondary">
        </li>
        <li class="<?php echo ($current_page == 'admindashboard') ? 'active' : ''; ?>">
            <a href="<?php echo ($role === 'School Admin') ? '../school_admin/schooladmindashboard.php' : '../department_admin/departmentadmindashboard.php'; ?>">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>
        </li>


        <li class="<?php echo ($current_page == 'games') ? 'active' : ''; ?>">
            <a href="../games/games.php">
                <i class="fas fa-basketball-ball"></i>
                <span>Games</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="../archive/archives.php" class="<?php echo ($current_page == 'archives') ? 'active' : ''; ?>">
                <i class="fas fa-archive"></i>
                <span>Archives</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="../school-profile/school_profile.php" class="<?php echo ($current_page == 'school_profile') ? 'active' : ''; ?>">
                <i class="fas fa-image"></i>
                <span>School Profile</span>
            </a>
        </li>
        <li class="<?php echo ($current_page == 'Logs') ? 'active' : ''; ?>">
            <a href="../user_logs/user_logs.php">
                <i class="fas fa-history"></i>
                <span>Logs</span>
            </a>
        </li>
        <li class="<?php echo ($current_page == 'adminannouncement') ? 'active' : ''; ?>">
            <a href="../announcements/adminannouncement.php">
                <i class="fas fa-bullhorn"></i>
                <span>Announcement</span>
            </a>
        </li>
        <li class="<?php echo ($current_page == 'pointingsystem') ? 'active' : ''; ?>">
            <a href="../pointing_system/pointing_system.php">
                <i class="fas fa-medal"></i>
                <span>Pointing System</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="#usersSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle <?php echo ($current_page == 'committeelist') ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
            <ul class="collapse list-unstyled" id="usersSubmenu">
                <?php foreach ($departments as $department) { ?>
                    <li>
                        <a class="submenu-item <?php echo (isset($_GET['selected_department_id']) && $_GET['selected_department_id'] == $department['id']) ? 'active' : ''; ?>"
                            href="../users/userlist.php?selected_department_id=<?php echo $department['id']; ?>">
                            <i class="fas fa-building fa-fw"></i>
                            <span><?php echo htmlspecialchars($department['department_name']); ?></span>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </li>
        <li class="nav-item">
            <a href="#departmentsSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle <?php echo ($current_page == 'departments') ? 'active' : ''; ?>">
                <i class="fas fa-school"></i>
                <span>Departments</span>
            </a>
            <ul class="collapse list-unstyled" id="departmentsSubmenu">
                <?php foreach ($departments as $department) { ?>
                    <li>
                        <a class="submenu-item <?php echo (isset($_GET['selected_department_id']) && $_GET['selected_department_id'] == $department['id']) ? 'active' : ''; ?>"
                            href="../departments/departments.php?selected_department_id=<?php echo $department['id']; ?>">
                            <i class="fas fa-building fa-fw"></i>
                            <span><?php echo htmlspecialchars($department['department_name']); ?></span>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </li>
        <li class="nav-item">
            <a href="../rankings/leaderboards.php" class="<?php echo ($current_page == 'leaderboards') ? 'active' : ''; ?>">
                <i class="fas fa-trophy"></i>
                <span>Leaderboards</span>
            </a>
        </li>


        <li class="nav-item">
            <a href="../brackets/admin_brackets.php" class="<?php echo ($current_page == 'Brackets') ? 'active' : ''; ?>">
                <i class="fas fa-sitemap"></i>
                <span>Brackets</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="../schedule/schedule.php" class="<?php echo ($current_page == 'schedule') ? 'active' : ''; ?>">
                <i class="fas fa-calendar"></i>
                <span>Schedules</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="#matchlistSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle <?php echo ($current_page == 'matchlist') ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-list"></i>
                <span>Match List</span>
            </a>
            <ul class="collapse list-unstyled" id="matchlistSubmenu">
                <?php foreach ($departments as $department) { ?>
                    <li>
                        <a class="submenu-item <?php echo (isset($_GET['selected_department_id']) && $_GET['selected_department_id'] == $department['id']) ? 'active' : ''; ?>"
                            href="../livescoring/admin_match_list.php?selected_department_id=<?php echo $department['id']; ?>">
                            <i class="fas fa-building fa-fw"></i>
                            <span><?php echo htmlspecialchars($department['department_name']); ?></span>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </li>

    </ul>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const toggleButton = document.querySelector('.navbar-toggler');

        // Toggle sidebar visibility
        toggleButton.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    });
</script>