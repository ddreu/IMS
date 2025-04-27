<!-- <div class="mt-5 pt-5">
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
</div> -->
<!-- ==== FOOTER SECTION ==== -->
<footer class="footer-section position-relative">
    <!-- Back to Top Button -->
    <!-- <a href="#" class="back-to-top-btn">
        <i class="fas fa-chevron-up"></i>
    </a> -->

    <div class="container py-5">
        <div class="row text-white">
            <!-- About -->
            <div class="col-md-3 mb-4">
                <h5 class="fw-bold mb-3">ABOUT IMS</h5>
                <p>Intramurals Management System (IMS) simplifies sports events, team coordination, and score tracking for schools and organizations. Streamline your intramurals with ease and efficiency.</p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" class="text-white"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-white"><i class="fas fa-envelope"></i></a>
                </div>
            </div>


            <!-- Legal -->
            <div class="col-md-3 mb-4">
                <h5 class="fw-bold mb-3">LEGAL</h5>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-white text-decoration-none">Privacy</a></li>
                    <li><a href="#" class="text-white text-decoration-none">Terms</a></li>
                    <!-- <li><a href="#" class="text-white text-decoration-none">Refund policy</a></li> -->
                </ul>
            </div>

            <!-- Partner -->
            <div class="col-md-3 mb-4">
                <h5 class="fw-bold mb-3">PARTNER</h5>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-white text-decoration-none">Refer a friend</a></li>
                    <li><a href="#" class="text-white text-decoration-none">Affiliates</a></li>
                </ul>
            </div>

            <!-- Help -->
            <div class="col-md-3 mb-4">
                <h5 class="fw-bold mb-3">HELP</h5>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-white text-decoration-none">Support</a></li>
                </ul>
            </div>
        </div>

        <div class="text-center text-white mt-5 small">
            © 2025 IMS — All Rights Reserved
        </div>
    </div>
</footer>

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