<?php
session_start();
include_once 'connection/conn.php';
$conn = con();

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the school_id from the URL
$school_id = isset($_GET['school_id']) ? (int)$_GET['school_id'] : 0;

// Fetch departments for the school_id
$departments = [];
if ($school_id > 0) {
    $query = $conn->prepare("SELECT id, department_name FROM departments WHERE school_id = ?");
    $query->bind_param("i", $school_id);
    $query->execute();
    $result = $query->get_result();

    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }

    $query->close();
}

// Fetch existing parameters from the current URL
$current_params = $_GET;
$school_id = isset($current_params['school_id']) ? $current_params['school_id'] : 0;
$department_id = isset($current_params['department_id']) ? $current_params['department_id'] : null;
$grade_level = isset($current_params['grade_level']) ? $current_params['grade_level'] : null;

// Generate a query string with current parameters
$query_string = http_build_query(array_filter($current_params)); // Remove empty params
?>

<!-- Top Navbar -->
<nav class="navbar navbar-dark bg-dark py-1 fixed-top border-bottom border-secondary">
    <div class="container">
        <div class="w-100 justify-content-center" id="topNav">
            <ul class="navbar-nav d-flex flex-row flex-wrap gap-2 justify-content-center">
                <?php foreach ($departments as $department): ?>
                    <?php if ($department['department_name'] === 'College'): ?>
                        <li class="nav-item">
                            <a class="nav-link fw-semibold px-3 rounded-pill small <?php echo isset($active_department) && $active_department == $department['id'] ? 'active bg-primary' : ''; ?>"
                                href="#"
                                onclick="handleDepartmentClick(<?php echo $department['id']; ?>, '<?php echo $department['department_name']; ?>'); return false;">
                                <?php echo htmlspecialchars($department['department_name']); ?>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle fw-semibold px-3 rounded-pill small <?php echo isset($active_department) && $active_department == $department['id'] ? 'active bg-primary' : ''; ?>"
                                href="#"
                                id="navbarDropdown<?php echo $department['id']; ?>"
                                role="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false">
                                <?php echo htmlspecialchars($department['department_name']); ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-dark mt-2 py-2 shadow-lg border-0 rounded-3"
                                id="gradeLevelDropdown<?php echo $department['id']; ?>"
                                aria-labelledby="navbarDropdown<?php echo $department['id']; ?>">
                                <div class="px-3 py-2 d-flex align-items-center">
                                    <div class="spinner-border spinner-border-sm me-2" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span class="small">Loading...</span>
                                </div>
                            </div>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>


<!-- Spacer for fixed navbar -->
<div class="navbar-spacer"></div>

<!-- Main Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark py-2 shadow-sm main-navbar <?php echo basename($_SERVER['PHP_SELF']) === 'home.php' ? 'navbar-transparent' : ''; ?>" id="navbar">
    <div class="container">
        <a class="navbar-brand fw-bold fs-4" href="index.php?<?php echo $query_string; ?>">IMS</a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-1">
                <li class="nav-item">
                    <a class="nav-link text-light px-3 fw-bold" href="home.php?<?php echo $query_string; ?>">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-light px-3 fw-bold" href="events.php?<?php echo $query_string; ?>">Events</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-light px-3 fw-bold" href="games.php?<?php echo $query_string; ?>">Games</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-light px-3 fw-bold" href="teams.php?<?php echo $query_string; ?>">Teams</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-light px-3 fw-bold" href="results.php?<?php echo $query_string; ?>">Results</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-light px-3 fw-bold" href="rankings.php?<?php echo $query_string; ?>">Leaderboards</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-light px-3 fw-bold" href="livematches.php?<?php echo $query_string; ?>">Live Scores</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-light px-3 fw-bold" href="download-app.php?<?php echo $query_string; ?>">App</a>
                </li>
            </ul>
            <div class="d-flex align-items-center">
                <?php
                // Check if user is logged in
                if (isset($_SESSION['user_id'])) {
                    $role = $_SESSION['role'];
                    $dashboardLink = '';

                    // Determine dashboard link based on role
                    switch ($role) {
                        case 'superadmin':
                            $dashboardLink = 'super_admin/sa_dashboard.php';
                            break;
                        case 'School Admin':
                            $dashboardLink = 'school_admin/schooladmindashboard.php';
                            break;
                        case 'Department Admin':
                            $dashboardLink = 'department_admin/departmentadmindashboard.php';
                            break;
                        case 'Committee':
                            $dashboardLink = 'committee/committeedashboard.php';
                            break;
                    }
                ?>
                    <div class="dropdown">
                        <button class="btn d-flex align-items-center gap-2 border-0 px-2 py-1"
                            type="button"
                            id="userDropdown"
                            data-bs-toggle="dropdown"
                            data-bs-display="static"
                            aria-expanded="false">
                            <div class=" border-primary p-2" style="background-color: transparent;">
                                <i class="fas fa-user text-light fa-lg"></i>
                            </div>
                            <span class="d-none d-md-inline small text-light"><?php echo htmlspecialchars($_SESSION['firstname']); ?></span>
                            <i class="fas fa-chevron-down text-light ms-1"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end mt-1 shadow-lg border-0 rounded-3"
                            aria-labelledby="userDropdown"
                            style="min-width: 200px;">
                            <li>
                                <div class="px-4 py-3 border-bottom border-secondary">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="border-primary p-2" style="background-color: transparent;">
                                            <i class="fas fa-user fa-lg text-light"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold small text-light"><?php echo htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']); ?></div>
                                            <div class="small text-light"><?php echo ucfirst($role); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="<?php echo $dashboardLink; ?>">
                                    <span>Dashboard</span>
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider my-1">
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger" href="#" id="logoutBtn">
                                    <span>Logout</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php
                } else {
                    // Show login button for non-logged in users
                    echo '<a href="login.php?' . http_build_query(array_filter($current_params)) . '" 
                          class="btn btn-primary d-flex align-items-center gap-2 px-4 py-2 fw-bold rounded-pill"> 
                          <span class="text-white">Login</span>
                          </a>';
                }
                ?>
            </div>

            <!-- <style>
                /* Custom styles for the user section */
                .dropdown-menu {
                    margin-top: 10px !important;
                    border: 1px solid rgba(255, 255, 255, 0.1) !important;
                }

                .dropdown-item {
                    transition: all 0.2s ease;
                }

                .dropdown-item:hover {
                    background-color: rgba(255, 255, 255, 0.1);
                }

                .btn-outline-light {
                    background: rgba(255, 255, 255, 0.1);
                    transition: all 0.2s ease;
                }

                .btn-outline-light:hover {
                    background: rgba(255, 255, 255, 0.2);
                    transform: translateY(-1px);
                }

                .bg-primary.bg-opacity-25 {
                    background-color: rgba(13, 110, 253, 0.25) !important;
                }
            </style>-->
        </div>
    </div>

</nav>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Bootstrap dropdowns
        const dropdownElements = document.querySelectorAll('.dropdown-toggle');
        dropdownElements.forEach(dropdown => {
            new bootstrap.Dropdown(dropdown);

            // Load grade levels when dropdown is shown
            dropdown.addEventListener('show.bs.dropdown', function(e) {
                const departmentId = this.id.replace('navbarDropdown', '');
                loadGradeLevels(departmentId);
            });
        });

        // Handle navbar transparency
        const navbar = document.getElementById('navbar');
        if (navbar && navbar.classList.contains('navbar-transparent')) {
            function toggleNavbarColor() {
                if (window.scrollY > 50) {
                    navbar.classList.remove('navbar-transparent');
                    navbar.classList.add('bg-dark');
                } else {
                    navbar.classList.add('navbar-transparent');
                    navbar.classList.remove('bg-dark');
                }
            }
            toggleNavbarColor();
            window.addEventListener('scroll', toggleNavbarColor);
        }

        // Adjust spacing on mobile menu toggle
        const topNavToggler = document.querySelector('[data-bs-target="#topNav"]');
        const mainNavToggler = document.querySelector('[data-bs-target="#mainNav"]');
        const spacer = document.querySelector('.navbar-spacer');

        function updateSpacerHeight() {
            const topNavHeight = document.querySelector('.fixed-top').offsetHeight;
            const mainNavHeight = document.getElementById('navbar').offsetHeight;
            spacer.style.height = `${topNavHeight}px`;
            navbar.style.top = `${topNavHeight}px`;
        }

        // Update heights on toggle
        topNavToggler?.addEventListener('click', () => setTimeout(updateSpacerHeight, 350));
        mainNavToggler?.addEventListener('click', () => setTimeout(updateSpacerHeight, 350));
        window.addEventListener('resize', updateSpacerHeight);
        updateSpacerHeight();
    });

    function handleDepartmentClick(department_id, department_name) {
        if (department_name === 'College') {
            updateURL(department_id);
        }
    }

    function loadGradeLevels(department_id) {
        const dropdown = document.getElementById(`gradeLevelDropdown${department_id}`);
        if (!dropdown) return;

        dropdown.innerHTML = `
        <div class="px-3 py-2 d-flex align-items-center">
            <div class="spinner-border spinner-border-sm me-2" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <span class="small">Loading...</span>
        </div>
    `;

        fetch(`fetch_grade_levels.php?department_id=${department_id}`)
            .then(async response => {
                if (!response.ok) {
                    throw new Error('Failed to load grade levels');
                }
                return response.json();
            })
            .then(data => {
                dropdown.innerHTML = '';

                if (!Array.isArray(data) || data.length === 0) {
                    dropdown.innerHTML = '<div class="dropdown-item-text small text-muted px-3">No grade levels available</div>';
                    return;
                }

                data.forEach(grade => {
                    dropdown.innerHTML += `
                    <a class="dropdown-item px-3 py-2 small" href="#" onclick="updateURL(${department_id}, '${grade}'); return false;">
                        ${grade}
                    </a>
                `;
                });
            })
            .catch(error => {
                console.error('Error loading grade levels:', error);
                dropdown.innerHTML = `
                <div class="dropdown-item-text px-3 py-2 small text-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    ${error.message || 'Error loading grade levels'}
                </div>
            `;
            });
    }

    function updateURL(department_id, grade_level = null) {
        const url = new URL(window.location.href);
        url.searchParams.set('department_id', department_id);
        if (grade_level) {
            url.searchParams.set('grade_level', grade_level);
        } else {
            url.searchParams.delete('grade_level');
        }
        window.location.href = url.toString();
    }
</script>