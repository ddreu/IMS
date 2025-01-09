<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Page</title>
    <!-- Include SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">

            <!-- Sidebar Toggle Button -->
            <button type="button" id="sidebarCollapse" class="btn btn-info">
                <i class="fas fa-align-left"></i>
            </button>

            <!-- Search bar -->
            <!-- <form class="form-inline ml-auto search-bar">
            <input class="form-control search-input" type="search" placeholder="Search..." aria-label="Search">
            <button class="btn search-btn" type="submit"><i class="fas fa-search"></i></button>
        </form>-->

            <!-- User Profile Section -->
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link profile" href="#">

                </li>
                <li class="nav-item">
                    <a class="nav-link logout" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </div>
    </nav>


    <!-- SweetAlert Script for Logout Confirmation -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.logout').addEventListener('click', function(event) {
                event.preventDefault(); // Prevent the default link action

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
                        window.location.href = this.href; // Redirect to the logout page
                    }
                });
            });
        });
    </script>

</body>

</html>