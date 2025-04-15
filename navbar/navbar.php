<!-- Navbar -->
<style>
    .navbar .dropdown-toggle img:hover {
        opacity: 0.85;
        transition: opacity 0.2s ease-in-out;
    }
</style>

<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=home" />

<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm position-sticky top-0 z-5" style="z-index: 1050;">
    <div class="container-fluid">
        <!-- <div class="container-fluid d-flex align-items-center"> -->

        <!-- Toggle button -->
        <button
            id="committeeSidebarToggle"
            class="navbar-toggler"
            type="button"
            data-bs-target="#csidebar"
            aria-controls="navbarSupportedContent"
            aria-expanded="false"
            aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <button
            id="sidebarToggle"
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#sidebar"
            aria-controls="navbarSupportedContent"
            aria-expanded="false"
            aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Toggle button -->
        <!-- <button
            id="committeeSidebarToggle"
            class="navbar-toggler d-block d-md-none"
            type="button"
            data-bs-target="#csidebar"
            aria-controls="navbarSupportedContent"
            aria-expanded="false"
            aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <button
            id="sidebarToggle"
            class="navbar-toggler d-block d-md-none"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#sidebar"
            aria-controls="navbarSupportedContent"
            aria-expanded="false"
            aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button> -->


        <!-- Collapsible wrapper -->
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <!-- Navbar brand with space on the left -->
            <a class="navbar-brand fw-bold text-primary ms-md-5">
                IMS
            </a>

            <!-- Navigation Links -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-1" href="../home.php?school_id=<?php echo $_SESSION['school_id'] ?? ''; ?>&department_id=<?php echo $_SESSION['department_id'] ?? ''; ?>">
                        <!-- <i class="fas fa-home fa-sm text-muted"></i> -->
                        <span class="material-symbols-outlined">
                            home
                        </span>
                        <span class="text-muted small"><strong>Home</strong></span>
                    </a>
                </li>
            </ul>


        </div>

        <!-- Right elements 
        <div class="d-flex align-items-center">

            <div class="dropdown">
                <a
                    class="text-secondary me-3 dropdown-toggle hidden-arrow position-relative"

                    id="navbarDropdownMenuLink"
                    role="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    data-bs-toggle="tooltip"
                    data-bs-placement="bottom"
                    title="Select Game"
                    style="text-decoration: none;">
                    Game
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" id="gamesDropdown" aria-labelledby="navbarDropdownMenuLink">
                     Game items will be dynamically inserted here 
                    <li class="dropdown-item text-center text-muted">Loading...</li>
                </ul>
            </div>-->

        <!-- notification icon -->

        <!-- Notification Icon -->
        <!-- <div class="dropdown me-3 notification-dropdown">
            <a
                class="text-secondary me-3 dropdown-toggle position-relative"
                id="notificationDropdown"
                role="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
                title="Notifications"
                style="text-decoration: none;">
                <i class="fas fa-bell fa-lg"></i>
                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="notificationDropdown">
                <li class="notification-item">
                    <a class="dropdown-item" href="#">New message from Admin</a>
                </li>
                <li class="notification-item">
                    <a class="dropdown-item" href="#">Tournament schedule updated</a>
                </li>
                <li class="notification-item">
                    <a class="dropdown-item" href="#">New event posted</a>
                </li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item text-center text-muted" href="#">View all notifications</a></li>
            </ul>
        </div> -->

        <!-- Inbox Icon -->
        <div class="dropdown me-3 inbox-dropdown position-relative">
            <a
                class="text-secondary dropdown-toggle position-relative"
                id="inboxDropdown"
                role="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
                title="Inbox"
                style="text-decoration: none;">
                <i class="fas fa-inbox fa-lg"></i>
                <!-- Optional badge if you want to show unread messages -->
                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
            </a>
            <!-- Dropdown container with dynamic width and content wrapping -->
            <ul class="dropdown-menu dropdown-menu-end shadow-sm overflow-auto"
                style="max-height: 300px; overflow-x: hidden; width: 30vw;"
                aria-labelledby="inboxDropdown">

                <?php require_once '../messages/inbox.php'; ?>
            </ul>
        </div>

        <!-- Notification Icon -->
        <div class="dropdown me-3 notification-dropdown position-relative">
            <a
                class="text-secondary me-3 dropdown-toggle position-relative"
                id="notificationDropdown"
                role="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
                title="Notifications"
                style="text-decoration: none;">
                <i class="fas fa-bell fa-lg"></i>
                <!-- Badge for unread notifications -->
                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
            </a>
            <!-- Dropdown container with dynamic width and content wrapping -->
            <ul class="dropdown-menu dropdown-menu-end shadow-sm overflow-auto"
                style="max-height: 300px; overflow-x: hidden; width: 30vw;"
                aria-labelledby="notificationDropdown">
                <?php require_once '../notifications/notifications.php'; ?>
            </ul>
        </div>




        <?php
        include_once '../connection/conn.php';
        $conn = con();
        $user_id = $_SESSION['user_id'];

        $image_query = $conn->prepare("SELECT image FROM users WHERE id = ?");
        $image_query->bind_param("i", $user_id);
        $image_query->execute();
        $image_result = $image_query->get_result();
        $user_image_data = $image_result->fetch_assoc();

        $user_image = $user_image_data['image'] ?? '';
        $uploads_path = "../uploads/users/";
        $default_image = "../assets/defaults/default-profile.jpg";

        // Check if image exists and is a valid file
        if (!empty($user_image) && file_exists($uploads_path . $user_image)) {
            $nav_image_path = $uploads_path . $user_image;
        } else {
            $nav_image_path = $default_image;
        }

        $image_query->close();

        ?>

        <!-- User Avatar Dropdown -->
        <div class="dropdown me-md-5">
            <a
                class="dropdown-toggle d-flex align-items-center hidden-arrow"
                id="navbarDropdownMenuAvatar"
                role="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
                data-bs-toggle="tooltip"
                data-bs-placement="bottom"
                title="User Menu"
                style="text-decoration: none;">
                <img src="<?php echo $nav_image_path; ?>" alt="Profile" class="rounded-circle border shadow-sm" style="width: 35px; height: 35px; object-fit: cover;">
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="navbarDropdownMenuAvatar">
                <!-- User Info (Profile + Name/Email) -->
                <li class="px-3 py-2">
                    <div class="d-flex align-items-center">
                        <img src="<?php echo $nav_image_path; ?>" alt="Profile" class="rounded-circle me-2 border" style="width: 40px; height: 40px; object-fit: cover;">
                        <div class="d-flex flex-column">
                            <span class="fw-semibold text-dark">
                                <?php
                                $displayName = trim($_SESSION['firstname']);
                                echo !empty($displayName) ? htmlspecialchars($displayName) : htmlspecialchars($_SESSION['email']);
                                ?>
                            </span>
                            <a href="../profile_settings/profile_settings.php" class="text-decoration-none small text-secondary">View Profile</a>
                        </div>
                    </div>
                </li>
                <li>
                    <hr class="dropdown-divider">
                </li>

                <!-- Settings -->
                <li>
                    <a class="dropdown-item" href="../profile_settings/profile_settings.php">
                        <i class="fas fa-cog me-2 text-secondary"></i> Settings
                    </a>
                </li>

                <!-- Logout -->
                <li>
                    <a class="dropdown-item" id="logoutBtn" href="#">
                        <i class="fas fa-sign-out-alt me-2 text-secondary"></i> Logout
                    </a>
                </li>
            </ul>
        </div>


</nav>
<script>
    // // Handle button visibility based on role
    // document.addEventListener("DOMContentLoaded", function() {
    //     const userRole = "<?php echo isset($_SESSION['role']) ? $_SESSION['role'] : ''; ?>";
    //     const committeeSidebarToggle = document.getElementById('committeeSidebarToggle');
    //     const sidebarToggle = document.getElementById('sidebarToggle');

    //     // Initially hide both buttons
    //     if (committeeSidebarToggle) committeeSidebarToggle.style.display = 'none';
    //     if (sidebarToggle) sidebarToggle.style.display = 'none';

    //     // Show appropriate button based on role
    //     if (userRole === 'Committee' || userRole === 'superadmin') {
    //         if (committeeSidebarToggle) committeeSidebarToggle.style.display = 'block';
    //     } else {
    //         if (sidebarToggle) sidebarToggle.style.display = 'block';
    //     }
    // });

    // // Your existing toggle functionality
    // document.addEventListener("DOMContentLoaded", function() {
    //     const userRole = "<?php echo isset($_SESSION['role']) ? $_SESSION['role'] : ''; ?>";
    //     console.log(userRole);

    //     // Determine the correct sidebar ID based on user role
    //     const sidebarId = (userRole === 'Committee') ? '#csidebar' : '#sidebar';
    //     const sidebar = document.querySelector(sidebarId);
    //     const togglerButton = document.querySelector('.navbar-toggler');

    //     // If the button is found, update the 'data-bs-target' to the correct sidebar ID
    //     if (togglerButton) {
    //         togglerButton.setAttribute('data-bs-target', sidebarId);
    //     }

    //     // Add click event listener to the toggler button to manually handle sidebar toggle
    //     if (togglerButton && sidebar) {
    //         togglerButton.addEventListener('click', function(event) {
    //             event.preventDefault();
    //             event.stopPropagation();

    //             // Toggle the 'active' class on the sidebar
    //             sidebar.classList.toggle('active');

    //             // Add/remove 'sidebar-active' class to body for overlay
    //             document.body.classList.toggle('sidebar-active');

    //             // Update aria-expanded attribute
    //             const isExpanded = sidebar.classList.contains('active');
    //             togglerButton.setAttribute('aria-expanded', isExpanded);
    //         });
    //     }
    // });

    // Handle button visibility based on role and screen size
    document.addEventListener("DOMContentLoaded", function() {
        const userRole = "<?php echo isset($_SESSION['role']) ? $_SESSION['role'] : ''; ?>";
        const committeeSidebarToggle = document.getElementById('committeeSidebarToggle');
        const sidebarToggle = document.getElementById('sidebarToggle');

        // Function to check if it's a mobile screen
        function isMobileScreen() {
            return window.innerWidth < 992; // Mobile screens are less than 992px wide
        }

        // Initially hide both buttons if it's not a mobile screen
        if (!isMobileScreen()) {
            if (committeeSidebarToggle) committeeSidebarToggle.style.display = 'none';
            if (sidebarToggle) sidebarToggle.style.display = 'none';
        } else {
            // Show the appropriate button based on role on mobile screens
            if (userRole === 'Committee' || userRole === 'superadmin') {
                if (committeeSidebarToggle) committeeSidebarToggle.style.display = 'block';
            } else {
                if (sidebarToggle) sidebarToggle.style.display = 'block';
            }
        }

        // Adjust visibility on screen resize (in case user resizes window)
        window.addEventListener('resize', function() {
            if (!isMobileScreen()) {
                if (committeeSidebarToggle) committeeSidebarToggle.style.display = 'none';
                if (sidebarToggle) sidebarToggle.style.display = 'none';
            } else {
                if (userRole === 'Committee' || userRole === 'superadmin') {
                    if (committeeSidebarToggle) committeeSidebarToggle.style.display = 'block';
                } else {
                    if (sidebarToggle) sidebarToggle.style.display = 'block';
                }
            }
        });

        // Your existing toggle functionality
        const sidebarId = (userRole === 'Committee') ? '#csidebar' : '#sidebar';
        const sidebar = document.querySelector(sidebarId);
        const togglerButton = document.querySelector('.navbar-toggler');

        if (togglerButton) {
            togglerButton.setAttribute('data-bs-target', sidebarId);
        }

        if (togglerButton && sidebar) {
            togglerButton.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();

                // Toggle the 'active' class on the sidebar
                sidebar.classList.toggle('active');

                // Add/remove 'sidebar-active' class to body for overlay
                document.body.classList.toggle('sidebar-active');

                // Update aria-expanded attribute
                const isExpanded = sidebar.classList.contains('active');
                togglerButton.setAttribute('aria-expanded', isExpanded);
            });
        }
    });


    // Logout functionality
    document.getElementById('logoutBtn').addEventListener('click', function(event) {
        event.preventDefault(); // Prevent the default anchor action
        // Show SweetAlert2 confirmation dialog
        Swal.fire({
            title: "Are you sure?",
            text: "You will be logged out of your account.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, logout!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show success message
                Swal.fire({
                    icon: "success",
                    title: "Successfully logged out!",
                    showConfirmButton: false,
                    timer: 1000
                }).then(() => {
                    // After 1 second, redirect to logout.php
                    window.location.href = '../logout.php';
                });
            }
        });
    });

    // Add click event to close sidebar when clicking outside or on overlay
    document.addEventListener('click', function(event) {
        // Check if sidebar exists and is currently active
        if (sidebar && sidebar.classList.contains('active')) {
            // Check if the click is outside the sidebar and not on the toggler button
            if (!sidebar.contains(event.target) &&
                !togglerButton.contains(event.target)) {
                sidebar.classList.remove('active');
                document.body.classList.remove('sidebar-active');
                togglerButton.setAttribute('aria-expanded', 'false');
            }
        }
    });

    // Prevent sidebar links from closing the sidebar when clicked
    if (sidebar) {
        const sidebarLinks = sidebar.querySelectorAll('a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function(event) {
                event.stopPropagation(); // Prevent click from propagating to document
            });
        });
    }
</script>