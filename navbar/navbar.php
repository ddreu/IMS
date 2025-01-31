<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm position-sticky top-0 z-5" style="z-index: 1050;">
    <div class="container-fluid">

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

        <!-- Collapsible wrapper -->
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <!-- Navbar brand with space on the left -->
            <a class="navbar-brand fw-bold text-primary ms-md-5">
                IMS
            </a>

            <!-- Navigation Links -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="../home.php?school_id=<?php echo isset($_SESSION['school_id']) ? $_SESSION['school_id'] : ''; ?>&department_id=<?php echo isset($_SESSION['department_id']) ? $_SESSION['department_id'] : ''; ?>">
                        <i class="fas fa-home me-1"></i>Homepage
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
                    <!-- Game items will be dynamically inserted here 
                    <li class="dropdown-item text-center text-muted">Loading...</li>
                </ul>
            </div>-->


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
                <i class="fas fa-user-circle text-secondary" style="font-size: 30px;"></i>
            </a>
            <ul
                class="dropdown-menu dropdown-menu-end shadow-sm"
                aria-labelledby="navbarDropdownMenuAvatar">
                <li>
                    <a class="dropdown-item" href="../profile_settings/profile_settings.php"><i class="fas fa-cog me-2 text-secondary"></i> Settings</a>
                </li>
                <li>
                    <a class="dropdown-item" id="logoutBtn" href="#"><i class="fas fa-sign-out-alt me-2 text-secondary"></i> Logout</a>
                </li>
            </ul>
        </div>

</nav>
<script>
    // Handle button visibility based on role
    document.addEventListener("DOMContentLoaded", function() {
        const userRole = "<?php echo isset($_SESSION['role']) ? $_SESSION['role'] : ''; ?>";
        const committeeSidebarToggle = document.getElementById('committeeSidebarToggle');
        const sidebarToggle = document.getElementById('sidebarToggle');

        // Initially hide both buttons
        if (committeeSidebarToggle) committeeSidebarToggle.style.display = 'none';
        if (sidebarToggle) sidebarToggle.style.display = 'none';

        // Show appropriate button based on role
        if (userRole === 'Committee') {
            if (committeeSidebarToggle) committeeSidebarToggle.style.display = 'block';
        } else {
            if (sidebarToggle) sidebarToggle.style.display = 'block';
        }
    });

    // Your existing toggle functionality
    document.addEventListener("DOMContentLoaded", function() {
        const userRole = "<?php echo isset($_SESSION['role']) ? $_SESSION['role'] : ''; ?>";
        console.log(userRole);

        // Determine the correct sidebar ID based on user role
        const sidebarId = (userRole === 'Committee') ? '#csidebar' : '#sidebar';
        const sidebar = document.querySelector(sidebarId);
        const togglerButton = document.querySelector('.navbar-toggler');

        // If the button is found, update the 'data-bs-target' to the correct sidebar ID
        if (togglerButton) {
            togglerButton.setAttribute('data-bs-target', sidebarId);
        }

        // Add click event listener to the toggler button to manually handle sidebar toggle
        if (togglerButton && sidebar) {
            togglerButton.addEventListener('click', function(event) {
                event.preventDefault(); // Prevent default Bootstrap behavior
                event.stopPropagation(); // Prevent event from bubbling
                
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