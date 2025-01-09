<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm position-sticky top-0 z-5" style="z-index: 1050;">
    <div class="container-fluid">

        <!-- Toggle button -->
        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent"
            aria-expanded="false"
            aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Collapsible wrapper -->
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <!-- Navbar brand with space on the left -->
            <a class="navbar-brand fw-bold text-primary ms-md-2">
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
    document.addEventListener('DOMContentLoaded', function() {
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

       /* // Games dropdown functionality
        const dropdown = document.getElementById('gamesDropdown');
        fetch('../rankings/fetch_games.php')
            .then(response => response.json())
            .then(games => {
                dropdown.innerHTML = ''; // Clear the "Loading..." message

                // Add a "Clear Selection" option
                const clearOption = document.createElement('li');
                clearOption.innerHTML = `<a class="dropdown-item" href="#" id="clearSelection">Clear Selection</a>`;
                dropdown.appendChild(clearOption);

                // Add event listener for the "Clear Selection" option
                document.getElementById('clearSelection').addEventListener('click', function(e) {
                    e.preventDefault();
                    const url = new URL(window.location.href);
                    url.searchParams.delete('game_id'); // Remove 'game_id' parameter
                    window.location.href = url.toString(); // Redirect with updated URL
                });

                if (games.length === 0) {
                    dropdown.innerHTML += '<li class="dropdown-item text-center text-muted">No games available</li>';
                    return;
                }

                // Populate the dropdown with games
                games.forEach(game => {
                    const listItem = document.createElement('li');
                    listItem.innerHTML = `<a class="dropdown-item" href="?game_id=${game.game_id}">${game.game_name}</a>`;
                    dropdown.appendChild(listItem);
                });
            })
            .catch(error => {
                console.error('Error fetching games:', error);
                dropdown.innerHTML = '<li class="dropdown-item text-center text-danger">Error loading games</li>';
            }); */
    });
</script>