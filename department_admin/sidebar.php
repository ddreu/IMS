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

$stmt = $conn->prepare("SELECT id, department_name FROM departments WHERE school_id = ?");
$stmt->bind_param("i", $school_id);
$stmt->execute();
$result = $stmt->get_result();
$departments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!-- Sidebar -->
<nav id="sidebar" class="mt-0 mt-sm-2 mt-md-3 mt-lg-4 mt-xl-5 overflow-auto">
    <div class="sidebar-header-logo text-center p-3 bg-light bg-gradient rounded-3 mx-2">
        <!-- Logo section always visible -->
        <div class="d-flex justify-content-center align-items-center logo-container">
            <div class="position-relative logo-wrapper">
                <img src="../uploads/logos/<?php echo htmlspecialchars($school['logo']); ?>"
                    alt="<?php echo htmlspecialchars($school['school_name']); ?>"
                    class="img-fluid rounded-circle shadow-lg border border-2 border-primary p-2 logo-image"
                    style="max-width: 110px; height: auto; object-fit: contain;">
                <div class="position-absolute bottom-0 start-50 translate-middle-x mb-n2 badge-container">
                    <span class="badge bg-primary px-3 py-2 rounded-pill shadow-sm">
                        <i class="fas fa-school me-1"></i>School
                    </span>
                </div>
            </div>
        </div>



    </div>

    <ul class="list-unstyled components">
        <li class="<?php echo ($current_page == 'admindashboard') ? 'active' : ''; ?>">
            <a href="<?php echo ($role === 'School Admin') ? '../school_admin/schooladmindashboard.php' : '../department_admin/departmentadmindashboard.php'; ?>">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
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
        <!--<li class="nav-item">
            <a href="#leaderboardsSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle <?php echo ($current_page == 'leaderboards') ? 'active' : ''; ?>">
                <i class="fas fa-trophy"></i>
                <span>Leaderboards</span>
            </a>
            <ul class="collapse list-unstyled" id="leaderboardsSubmenu">
                <?php foreach ($departments as $department) { ?>
                    <li>
                        <a class="submenu-item <?php echo (isset($_GET['selected_department_id']) && $_GET['selected_department_id'] == $department['id']) ? 'active' : ''; ?>"
                            href="../rankings/leaderboards.php?selected_department_id=<?php echo $department['id']; ?>">
                            <i class="fas fa-building fa-fw"></i>
                            <span><?php echo htmlspecialchars($department['department_name']); ?></span>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </li>-->
        <li class="<?php echo ($current_page == 'games') ? 'active' : ''; ?>">
            <a href="../games/games.php">
                <i class="fas fa-basketball-ball"></i>
                <span>Games</span>
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