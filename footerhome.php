<div class="mt-5 pt-5">
    <footer class="mt-5 pt-5">
        <div class="footer__content">
            <a class="logo" href="index.php">IMS</a>
            <nav>
                <ul class="footer__links">
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Contact</a></li>
                    <li><a href="#">Support</a></li>
                </ul>
            </nav>
            <a class="cta" href="#"><i class='fas fa-angle-up'></i></a>
        </div>
        <div class="footer__bottom">
            <p>&copy; 2024 IMS. All rights reserved.</p>
        </div>
    </footer>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Logout confirmation
        document.getElementById('logoutBtn').addEventListener('click', function(event) {
            event.preventDefault(); // Prevent default anchor action
            Swal.fire({
                title: 'Are you sure?',
                text: 'You will be logged out of your account.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, logout!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirect to logout.php
                    window.location.href = 'logout.php';
                }
            });
        });
    }); // Close the event listener here
</script>