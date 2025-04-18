<!-- Navbar -->
<style>
    .navbar .dropdown-toggle img:hover {
        opacity: 0.85;
        transition: opacity 0.2s ease-in-out;
    }

    @media (max-width: 991.98px) {

        /* Fix Bootstrap collapse sidebar issues on mobile only */
        #sidebar.collapse {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            z-index: 1040;
            overflow-y: auto;
            background-color: #fff;
            transition: transform 0.3s ease-in-out;
        }

        #sidebar.collapse:not(.show) {
            transform: translateX(-100%);
        }

        #sidebar.collapse.show {
            transform: translateX(0);
        }

        #sidebar {
            margin-top: 0 !important;
        }
    }


    /* loader */

    .loader-container {
        position: fixed;
        inset: 0;
        z-index: 999999 !important;
        pointer-events: none;
    }

    .loader-in-transition {
        position: fixed;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        overflow: hidden;
        z-index: 999999 !important;
        pointer-events: auto;
        visibility: hidden;
        opacity: 0;
        transition: opacity 0.2s ease-in;
    }

    .loader-in-transition.show {
        visibility: visible;
        opacity: 1;
    }

    .loader-in-transition::after {
        content: "";
        position: absolute;
        top: 50%;
        left: 50%;
        width: 300vmax;
        height: 300vmax;
        background: #111;
        border-radius: 50%;
        transform: translate(-50%, -50%) scale(0);
        z-index: 999998 !important;
        opacity: 1;
    }

    .loader-in-transition.show::after {
        animation: expandCircleInward 1s ease-in-out forwards;
    }

    @keyframes expandCircleInward {
        0% {
            transform: translate(-50%, -50%) scale(0);
            opacity: 1;
        }

        100% {
            transform: translate(-50%, -50%) scale(1);
            opacity: 1;
        }
    }

    .basketball-svg {
        width: 200px;
        height: 200px;
        z-index: 999999 !important;
        /* make sure it stays above the shrinking black circle */
    }

    .seam {
        fill: none;
        stroke: orange;
        stroke-width: 2.5;
        stroke-dasharray: 100;
        stroke-dashoffset: 100;
        animation: seamFill 1.8s ease-in-out forwards infinite;
    }

    @keyframes seamFill {
        0% {
            stroke-dashoffset: 100;
        }

        100% {
            stroke-dashoffset: 0;
        }
    }

    .basketball-svg {
        width: 200px;
        height: 200px;
        animation: fillBasketball 2s ease-in-out forwards;
        stroke: orange;
        fill: transparent;
        stroke-width: 3px;
        z-index: 999999 !important;

    }

    @keyframes fillBasketball {
        0% {
            stroke-dasharray: 0, 100;
            fill: transparent;
        }

        50% {
            stroke-dasharray: 100, 0;
            fill: transparent;
        }

        100% {
            stroke-dasharray: 100, 0;
            fill: orange;
        }
    }

    .basketball-line {
        stroke-linecap: round;
        fill: none;
        stroke: orange;
        /* stroke-width: 2.5; */
        stroke-width: 4;
        stroke-dasharray: 1000;
        stroke-dashoffset: 1000;
    }

    .basketball-line.animate {
        animation: drawBasketball 3s cubic-bezier(0.7, 0.4, 0.1, 1) forwards;
    }

    @keyframes drawBasketball {
        to {
            stroke-dashoffset: 0;
        }
    }

    .loader-container {
        pointer-events: auto;
        /* allow blocking interaction */
    }

    .loader-container * {
        pointer-events: none;
        /* prevent inner SVG or content from being clickable */
    }

    .logout-text {
        position: relative;
        margin-top: 1rem;
        /* Adjust spacing as needed */
        font-size: 1.25rem;
        color: white;
        font-weight: 500;
        z-index: 999999 !important;
        font-family: 'Poppins', sans-serif;
        text-align: center;
        animation: fadeInText 0.5s ease-in-out forwards;
    }


    @keyframes fadeInText {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .dots::after {
        content: '';
        display: inline-block;
        animation: dotsAnimation 1.5s infinite steps(3);
        width: 1em;
        overflow: hidden;
        vertical-align: bottom;
    }

    @keyframes dotsAnimation {
        0% {
            content: '';
        }

        33% {
            content: '.';
        }

        66% {
            content: '..';
        }

        100% {
            content: '...';
        }
    }

    .loader-content {
        z-index: 999999 !important;
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
</style>

<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=home" />

<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm position-sticky top-0 z-5" style="z-index: 1050;">
    <div class="container-fluid">
        <!-- <div class="container-fluid d-flex align-items-center"> -->

        <!-- Toggle button -->
        <!-- <button
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
        </button> -->

        <button
            id="sidebarToggle"
            class="navbar-toggler"
            type="button"
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

        <!-- scan qr -->

        <!-- QR Scan Button -->
        <!-- QR Scan Icon (like Inbox/Notifications) -->
        <div class="dropdown me-3 position-relative">
            <a href="#" class="text-secondary position-relative" id="openQRScanner" title="Scan QR" style="text-decoration: none;">
                <i class="fas fa-qrcode fa-lg"></i>
            </a>
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

<div id="loader" class="loader-container loader-in-transition">
    <div class="loader-content">
        <svg class="basketball-svg" xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 400 400" fill="none">
            <path class="basketball-line"
                d="M386.546,128.301c-19.183-49.906-56.579-89.324-105.302-110.99C255.513,5.868,228.272,0.065,200.28,0.065 
      c-79.087,0-150.907,46.592-182.972,118.693c-21.668,48.723-23.036,103.041-3.854,152.944 
      c19.181,49.905,56.578,89.324,105.299,110.992c25.726,11.438,52.958,17.238,80.949,17.24c0.008,0,0.008,0,0.016,0 
      c64.187,0,124.602-30.795,162.104-82.505l3.967-5.719c6.559-9.743,12.238-19.979,16.9-30.469 
      C404.359,232.521,405.728,178.206,386.546,128.301z M306.656,67.229c29.342,23.576,50.05,56.346,58.89,93.178 
      c-26.182,14.254-115.898,58.574-227.678,63.936c-0.22-6.556-0.188-13.204,0.095-19.894c3.054,0.258,6.046,0.392,8.957,0.392 
      c48.011,0,72.144-34.739,95.479-68.341C258.911,112.729,277.523,85.931,306.656,67.229z M200.322,29.683 
      c23.826,0,47.004,4.939,68.891,14.682c3.611,1.607,7.234,3.381,10.836,5.309c-27.852,20.82-45.873,46.773-61.961,69.941 
      c-22.418,32.272-38.612,55.592-71.058,55.592c-2.009,0-4.09-0.088-6.231-0.264c10.624-71.404,45.938-128.484,57.204-145.242 
      C198.778,29.688,199.552,29.683,200.322,29.683z M83.571,75.701c21.39-19.967,48.144-34.277,76.704-41.215 
      c-16.465,28.652-38.163,74.389-47.548,128.982C90.537,147.617,65.38,118.793,83.571,75.701z M44.354,130.786 
      c1.519-3.414,3.15-6.779,4.895-10.094c0.915,4.799,2.234,9.52,3.96,14.139c12.088,32.377,40.379,52.406,55.591,61.219 
      c-0.654,9.672-0.84,19.303-0.548,28.762c-26.46-0.441-52.557-3.223-77.752-8.283C27.604,187.29,32.359,157.756,44.354,130.786z 
      M69.818,288.907c-2.943,3.579-5.339,7.495-7.178,11.717c-11.635-15.948-20.479-33.894-26.052-52.862 
      c24.227,4.182,49.111,6.424,74.187,6.678c0.554,3.955,1.199,7.906,1.931,11.828C99.568,268.702,81.578,274.605,69.818,288.907z 
      M130.784,355.646c-15.528-6.904-29.876-16.063-42.687-27.244c-1.059-8.738,0.472-15.68,4.558-20.658 
      c6.582-8.028,18.771-11.321,27.153-12.666c7.324,23.808,18.148,46.728,32.287,68.381 
      C144.818,361.331,137.693,358.722,130.784,355.646z M193.648,370.185c-19.319-23.783-33.777-49.438-43.082-76.426 
      c22.608,1.221,42.078,8.045,62.571,15.227c25.484,8.926,51.84,18.158,85.997,18.158c4.938,0,9.874-0.189,14.856-0.574 
      C281.376,355.896,238.354,371.788,193.648,370.185z M355.648,269.22c-3.43,7.703-7.519,15.278-12.173,22.555 
      c-15.463,3.785-29.923,5.625-44.119,5.625c-29.753,0-53.479-8.311-76.427-16.35c-23.997-8.41-48.813-17.107-79.65-17.107 
      c-0.267,0-0.534,0-0.802,0.002c-0.686-3.381-1.293-6.764-1.823-10.137c49.176-2.496,99.361-12.211,149.312-28.91 
      c35.29-11.799,62.965-24.643,80.103-33.42C371.438,218.101,366.516,244.771,355.648,269.22z" />
        </svg>
        <div class="logout-text" id="logoutText">Logging you out <span class="dots"></span></div>
    </div>
</div>

<!-- scanner modal -->

<!-- Hidden fullscreen overlay -->
<!-- Fullscreen QR Scanner Overlay -->
<div id="qrFullScreenScanner" style="display: none; position: fixed; inset: 0; background: #000; z-index: 9999;">
    <div id="html5qr-code" style="width: 100%; height: 100%; position: relative;"></div>
    <!-- Success/Error Message UI -->
    <div id="qrStatusMessage" style="
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 1.5rem;
    display: none;
    z-index: 10001;
    text-align: center;">
    </div>

    <div style="position: absolute; top: 50%; left: 50%; width: 250px; height: 250px; border: 2px solid #fff; transform: translate(-50%, -50%); z-index: 9999;"></div>
    <button id="closeQRScanner" style="position: absolute; top: 15px; right: 15px; z-index: 10000; background: rgba(0,0,0,0.7); color: white; border: none; padding: 10px 15px; border-radius: 5px;">Close</button>
</div>



<!-- QR Scanner Modal -->
<!-- <div class="modal fade" id="qrScannerModal" tabindex="-1" aria-labelledby="qrScannerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header">
                <h5 class="modal-title" id="qrScannerModalLabel">Scan QR Code</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <video id="qrVideo" width="100%" height="auto" autoplay></video>
            </div>
        </div>
    </div>
</div> -->

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
        const userRole = "<?php echo $_SESSION['role'] ?? ''; ?>";
        const togglerButton = document.getElementById('sidebarToggle');

        const isCommittee = userRole === 'Committee' || userRole === 'superadmin';
        const sidebarId = isCommittee ? '#csidebar' : '#sidebar';
        const sidebar = document.querySelector(sidebarId);

        if (togglerButton) {
            togglerButton.setAttribute('data-bs-target', sidebarId);
            togglerButton.setAttribute('aria-controls', sidebarId.substring(1));

            if (isCommittee) {
                // Committee uses custom manual toggling
                togglerButton.removeAttribute('data-bs-toggle');
            } else {
                // School/Dept Admin uses Bootstrap collapse
                togglerButton.setAttribute('data-bs-toggle', 'collapse');
            }
        }

        // Optional: Custom toggle logic if Committee role
        if (isCommittee && togglerButton && sidebar) {
            togglerButton.addEventListener('click', function(event) {
                event.preventDefault();
                sidebar.classList.toggle('active');
                document.body.classList.toggle('sidebar-active');
                togglerButton.setAttribute('aria-expanded', sidebar.classList.contains('active'));
            });

            // Close sidebar when clicking outside
            document.addEventListener('click', function(event) {
                if (!sidebar.contains(event.target) && !togglerButton.contains(event.target)) {
                    sidebar.classList.remove('active');
                    document.body.classList.remove('sidebar-active');
                    togglerButton.setAttribute('aria-expanded', 'false');
                }
            });
        }

        // scan fucntion

        // Fullscreen-style QR scanner
        const openQRScanner = document.getElementById('openQRScanner');
        const scannerOverlay = document.getElementById('qrFullScreenScanner');
        const closeScannerBtn = document.getElementById('closeQRScanner');
        let html5Qr;

        openQRScanner.addEventListener('click', function(e) {
            e.preventDefault();
            scannerOverlay.style.display = 'block';

            html5Qr = new Html5Qrcode("html5qr-code");

            Html5Qrcode.getCameras().then(cameras => {
                if (cameras && cameras.length) {
                    html5Qr.start({
                            facingMode: "environment"
                        }, {
                            fps: 10,
                            qrbox: {
                                width: 250,
                                height: 250
                            }
                        },
                        (qrCodeMessage) => {
                            html5Qr.stop().then(() => {
                                html5Qr.clear();

                                // UI update starts
                                const statusBox = document.getElementById('qrStatusMessage');
                                statusBox.style.display = 'block';
                                statusBox.innerHTML = `<div style="font-size: 3rem;">✅</div><div>QR Scanned Successfully</div>`;

                                // Make scanner frame dim
                                document.getElementById('html5qr-code').style.opacity = '0.2';

                                // Send scanned token
                                fetch(`../qr-code/use-qr-token.php`, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json'
                                        },
                                        body: JSON.stringify({
                                            token: qrCodeMessage
                                        })
                                    })
                                    .then(res => res.json())
                                    // .then(data => {
                                    //     if (data.success) {
                                    //         statusBox.innerHTML = `<div style="font-size: 3rem;">✅</div><div>User has been logged in!</div>`;
                                    //         setTimeout(() => {
                                    //             scannerOverlay.style.display = 'none';
                                    //             statusBox.style.display = 'none';
                                    //             document.getElementById('html5qr-code').style.opacity = '1';
                                    //         }, 2500);
                                    //     } else {
                                    //         statusBox.innerHTML = `<div style="font-size: 3rem;">❌</div><div>${data.message || 'Invalid or expired QR'}</div>`;
                                    //         setTimeout(() => {
                                    //             statusBox.style.display = 'none';
                                    //             scannerOverlay.style.display = 'none';
                                    //             document.getElementById('html5qr-code').style.opacity = '1';
                                    //         }, 2500);
                                    //     }
                                    // });

                                    .then(data => {
                                        const icon = data.success ? '✅' : '❌';
                                        const message = data.success ?
                                            'User has been logged in!' :
                                            (data.message || 'Invalid, used, or expired QR code.');

                                        statusBox.innerHTML = `<div style="font-size: 3rem;">${icon}</div><div>${message}</div>`;

                                        setTimeout(() => {
                                            statusBox.style.display = 'none';
                                            scannerOverlay.style.display = 'none';
                                            document.getElementById('html5qr-code').style.opacity = '1';
                                        }, 2500);
                                    });

                            });
                        },

                        (err) => {
                            const statusBox = document.getElementById('qrStatusMessage');
                            statusBox.style.display = 'block';
                            statusBox.innerHTML = `<div style="font-size: 3rem;">⚠️</div><div>Scanning failed. Try again.</div>`;
                        }

                    );
                }
            }).catch(err => {
                console.error("Camera error: ", err);
                Swal.fire({
                    icon: 'error',
                    title: 'Camera not available',
                    text: 'Please allow camera access or use a supported device.'
                });
            });
        });

        closeScannerBtn.addEventListener('click', () => {
            if (html5Qr) {
                html5Qr.stop().then(() => {
                    html5Qr.clear();
                    scannerOverlay.style.display = 'none';
                });
            }
        });

    });


    // Logout functionality
    document.getElementById('logoutBtn').addEventListener('click', function(event) {
        event.preventDefault();

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
                // Show the loader
                const loader = document.getElementById('loader');
                loader.classList.add('show');
                const line = loader.querySelector('.basketball-line');
                if (line) {
                    line.classList.add('animate');
                }

                // Send AJAX request to logout
                fetch('../logout.php', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1500);
                    })
                    .catch(error => {
                        loader.classList.remove('show');
                        console.error('Logout error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Logout failed!',
                            text: 'Please try again.',
                            toast: true,
                            timer: 2500,
                            position: 'top-end',
                            showConfirmButton: false
                        });
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
<script src="https://unpkg.com/html5-qrcode"></script>