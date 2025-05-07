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
            <div class="menu-category">
                <span>Access</span>
            </div>


            <?php
            $conn = con();
            $schoolQuery = "SELECT school_id, school_name FROM schools WHERE is_archived = 0 AND school_id != 0"; // Modify as necessary
            $schoolResult = $conn->query($schoolQuery); // Renamed result to schoolResult for clarity

            // Initialize the options array
            $options = "";
            $selectedSchoolId = isset($_SESSION['school_id']) ? $_SESSION['school_id'] : null;

            // Fetch and generate options for the dropdown
            while ($schoolRow = $schoolResult->fetch_assoc()) { // Renamed row to schoolRow for clarity
                $selected = ($schoolRow['school_id'] == $selectedSchoolId) ? 'selected' : ''; // Preselect if it matches session school_id
                $options .= '<option value="' . $schoolRow['school_id'] . '" ' . $selected . '>' . htmlspecialchars($schoolRow['school_name']) . '</option>';
            }
            ?>


            <li class="nav-item sidebar-filter-item">
                <label for="schoolSelect" class="sidebar-filter-label">School</label>
                <form method="GET" action="../super_admin/set_school.php"> <!-- Form to set school -->
                    <select id="schoolSelect" class="sidebar-dropdown" name="school_id" onchange="this.form.submit();">
                        <option value="">Select School</option>
                        <?php echo $options; ?> <!-- Insert dynamic options here -->
                    </select>
                </form>
            </li>

            <!-- Sidebar -->

            <?php
            if (!empty($_SESSION['school_id'])) {
                // Directly use $_SESSION['school_id'] in the query
                $departmentStmt = $conn->prepare("SELECT id, department_name FROM departments WHERE school_id = ? AND is_archived = 0"); // Renamed stmt to departmentStmt
                $departmentStmt->bind_param("i", $_SESSION['school_id']);  // Use session value directly
                $departmentStmt->execute();
                $departmentResult = $departmentStmt->get_result(); // Renamed result to departmentResult for clarity
                $departments = $departmentResult->fetch_all(MYSQLI_ASSOC); // Renamed result to departmentResult and used departments variable
                $departmentStmt->close();

                // Directly check if session variables for game and department are set
                $showGameLinks = isset($_SESSION['game_id'], $_SESSION['game_name'], $_SESSION['department_id'], $_SESSION['department_name']);
            }
            ?>


            <!-- School Access Section with Collapsible -->
            <?php if (!empty($_SESSION['school_id'])): ?>

                <div class="menu-category school-access">
                    <a href="#schoolAccessSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="nav-link dropdown-toggle">
                        <span>School Access</span>
                    </a>
                </div>

                <ul class="list-unstyled components collapse" id="schoolAccessSubmenu">
                    <!-- School Dashboard -->
                    <li class="nav-item">
                        <a href="../school_admin/schooladmindashboard.php" class="nav-link <?php echo ($current_page == 'admindashboard') ? 'active' : ''; ?>">
                            <i class="fas fa-th-large"></i><span>School Dashboard</span>
                        </a>
                    </li>

                    <!-- Departments List (Dropdown) -->
                    <li class="nav-item">
                        <a href="#departmentsSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="nav-link dropdown-toggle <?php echo ($current_page == 'departments') ? 'active' : ''; ?>">
                            <i class="fas fa-school"></i><span>School Departments</span>
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

                    <!-- Leaderboards -->
                    <!-- <li class="nav-item">
                        <a href="../rankings/leaderboards.php" class="nav-link <?php echo ($current_page == 'leaderboards') ? 'active' : ''; ?>">
                            <i class="fas fa-trophy"></i><span>School Leaderboards</span>
                        </a>
                    </li> -->
                    <!-- Games -->
                    <li class="nav-item">
                        <a href="../games/games.php" class="nav-link <?php echo ($current_page == 'Games') ? 'active' : ''; ?>">
                            <i class="fas fa-basketball-ball"></i><span>School Games</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="../pointing_system/pointing_system.php" class="nav-link <?php echo ($current_page == 'pointingsystem') ? 'active' : ''; ?>">
                            <i class="fas fa-medal"></i><span>Pointing System</span>
                        </a>
                    </li>

                    <!-- Brackets -->
                    <li class="nav-item">
                        <a href="../brackets/admin_brackets.php" class="nav-link <?php echo ($current_page == 'Brackets') ? 'active' : ''; ?>">
                            <i class="fas fa-sitemap"></i><span>Brackets</span>
                        </a>
                    </li>

                    <!-- Schedules -->
                    <li class="nav-item">
                        <a href="../schedule/schedule.php" class="nav-link <?php echo ($current_page == 'schedule') ? 'active' : ''; ?>">
                            <i class="fas fa-calendar"></i><span>Schedules</span>
                        </a>
                    </li>

                    <!-- Match List (Dropdown) -->
                    <li class="nav-item">
                        <a href="#matchlistSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="nav-link dropdown-toggle <?php echo ($current_page == 'matchlist') ? 'active' : ''; ?>">
                            <i class="fas fa-clipboard-list"></i><span>Match List</span>
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
                    <!-- Rankings -->
                    <li class="nav-item">
                        <a href="../rankings/admin-leaderboards.php" class="nav-link <?php echo ($current_page == 'rankings') ? 'active' : ''; ?>">
                            <i class="fas fa-chart-bar"></i><span>Rankings</span>
                        </a>
                    </li>
                </ul>

            <?php endif; ?>
            <?php
            // Fetching games based on selected school_id
            $game_dropdown_options = "";

            // Fetch games for the selected school
            if (isset($_SESSION['school_id']) && $_SESSION['school_id']) {
                $game_query = "SELECT game_id, game_name FROM games WHERE school_id = ? AND is_archived = 0";
                $game_stmt = $conn->prepare($game_query);
                $game_stmt->bind_param("i", $_SESSION['school_id']);  // Directly use the session value
                $game_stmt->execute();
                $game_fetch_result = $game_stmt->get_result();

                // Fetch and generate options for the dropdown
                while ($game_row = $game_fetch_result->fetch_assoc()) {
                    $selected_option = ($game_row['game_id'] == $_SESSION['game_id']) ? 'selected' : ''; // Directly use the session value
                    $game_dropdown_options .= '<option value="' . $game_row['game_id'] . '" ' . $selected_option . '>' . htmlspecialchars($game_row['game_name']) . '</option>';
                }

                // Close the prepared statement after usage
                $game_stmt->close();
            }
            ?>


            <!-- Game Dropdown -->
            <li class="nav-item sidebar-filter-item">
                <label for="gameSelect" class="sidebar-filter-label">Game</label>
                <form method="GET" action="../super_admin/set_game.php"> <!-- Form to set game -->
                    <select id="gameSelect" class="sidebar-dropdown" name="game_id" onchange="this.form.submit();">
                        <option value="">Select Game</option>
                        <?php echo $game_dropdown_options; ?> <!-- Insert dynamic options here -->
                    </select>
                </form>
            </li>


            <?php
            // Game Access Section with Collapsible
            if (isset($_SESSION['game_id']) && !empty($_SESSION['game_id'])):
            ?>

                <!-- Game Access Section -->
                <div class="menu-category game-access">
                    <a href="#gameAccessSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="nav-link dropdown-toggle">
                        <span>Game Access</span>
                    </a>
                </div>

                <ul class="list-unstyled components collapse" id="gameAccessSubmenu">
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a href="../committee/committeedashboard.php" class="nav-link <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
                            <i class="fas fa-th-large"></i><span>Game Dashboard</span>
                        </a>
                    </li>

                    <!-- Teams -->
                    <li class="nav-item">
                        <a href="../teams/teams.php" class="nav-link <?php echo ($current_page == 'teams') ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i><span>Teams</span>
                        </a>
                    </li>

                    <!-- Scoring Rules -->
                    <li class="nav-item">
                        <a href="../scoring_rules/scoring_rules_form.php" class="nav-link <?php echo ($current_page == 'scoring_rules') ? 'active' : ''; ?>">
                            <i class="fas fa-book-open"></i><span>Scoring Rules</span>
                        </a>
                    </li>

                    <!-- Brackets -->
                    <li class="nav-item">
                        <a href="../brackets/brackets.php" class="nav-link <?php echo ($current_page == 'brackets') ? 'active' : ''; ?>">
                            <i class="fas fa-play-circle"></i><span>Brackets</span>
                        </a>
                    </li>

                    <!-- Schedules -->
                    <li class="nav-item">
                        <a href="../schedule/schedule.php" class="nav-link <?php echo ($current_page == 'schedule') ? 'active' : ''; ?>">
                            <i class="fas fa-calendar"></i><span>Schedules</span>
                        </a>
                    </li>

                    <!-- Match List -->
                    <li class="nav-item">
                        <a href="../livescoring/match_list.php" class="nav-link <?php echo ($current_page == 'match_list') ? 'active' : ''; ?>">
                            <i class="fas fa-air-freshener"></i><span>Match</span>
                        </a>
                    </li>



                    <!-- User Logs -->
                    <!-- <li class="nav-item">
                        <a href="../user_logs/user_logs.php" class="nav-link <?php echo ($current_page == 'user_logs') ? 'active' : ''; ?>">
                            <i class="fas fa-clock"></i><span>User Logs</span>
                        </a>
                    </li> -->
                </ul>

            <?php
            endif; // End of if checking if game_id exists in the session
            ?>




            <!--  <label for="roleSelect" class="sidebar-filter-label">Role</label>
                <select id="roleSelect" class="sidebar-dropdown" disabled>
                    <option value="">Select Role</option>
                </select>
            </li> -->
            <!-- <li class="nav-item">
                <a href="../schools/schools.php" class="nav-link <?php echo ($current_page == 'schools') ? 'active' : ''; ?>">
                    <i class="fas fa-school"></i><span>School Leaderboards</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../users/admin_userlist.php" class="nav-link">
                    <i class="fas fa-users"></i><span>Matches</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../announcements/sa_announcement.php" class="nav-link">
                    <i class="fas fa-play-circle"></i><span>Ongoing Matches</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../user_logs/admin_user_logs.php" class="nav-link">
                    <i class="fas fa-award"></i><span>School Points</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../archive/archives.php" class="nav-link">
                    <i class="fas fa-trophy"></i><span>Brackets</span>
                </a>
            </li>
 -->


            <div class="menu-category">
                <span>Management</span>
            </div>
            <li class="nav-item">
                <a href="../schools/schools.php" class="nav-link <?php echo ($current_page == 'schools') ? 'active' : ''; ?>">
                    <i class="fas fa-school"></i><span>Schools</span>
                </a>
            </li>
            <!-- <li class="nav-item">
                <a href="../archive/archives.php" class="nav-link">
                    <i class="fas fa-gamepad"></i><span>Games</span>
                </a>
            </li> -->

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
            <!-- <li class="nav-item">
                <a href="../admin-pages/leaderboards.php" class="nav-link">
                    <i class="fas fa-trophy"></i><span>Leaderbooards</span>
                </a>
            </li> -->

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
</div>