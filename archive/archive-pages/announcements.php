<?php
// session_start();
// require_once '../connection/conn.php';
$conn = con();
$user_id = $_SESSION['user_id'];
$email = $_SESSION['email'];
$role = $_SESSION['role'];

if ($role === 'superadmin') {
    $school_id = isset($_GET['school_id']) ? intval($_GET['school_id']) : null;
} else {
    $school_id = $_SESSION['school_id'];
}

if (!$school_id) {
    header('Location: ims/login.php');
    exit();
}

// Department filter from URL parameter (optional)
$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : null;

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total count of announcements
$count_query = "SELECT COUNT(*) as total 
               FROM announcement a 
               LEFT JOIN departments d ON a.department_id = d.id 
               WHERE a.school_id = ?
               AND ((d.is_archived = 1) OR (a.is_archived = 1))";

if ($department_id) {
    $count_query .= " AND a.department_id = ?";
}

$stmt_count = mysqli_prepare($conn, $count_query);
if ($department_id) {
    mysqli_stmt_bind_param($stmt_count, "ii", $school_id, $department_id);
} else {
    mysqli_stmt_bind_param($stmt_count, "i", $school_id);
}
mysqli_stmt_execute($stmt_count);
$result_count = mysqli_stmt_get_result($stmt_count);
$total_rows = mysqli_fetch_assoc($result_count)['total'];
$total_pages = ceil($total_rows / $limit);

// Base query for fetching announcements
$announcements_query = "SELECT a.*, d.department_name 
                       FROM announcement a 
                       LEFT JOIN departments d ON a.department_id = d.id 
                       WHERE a.school_id = ?
                       AND ((d.is_archived = 1) OR (a.is_archived = 1))";

if ($department_id) {
    $announcements_query .= " AND a.department_id = ?";
}

$announcements_query .= " ORDER BY a.id DESC LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($conn, $announcements_query);

if ($department_id) {
    mysqli_stmt_bind_param($stmt, "iiii", $school_id, $department_id, $limit, $offset);
} else {
    mysqli_stmt_bind_param($stmt, "iii", $school_id, $limit, $offset);
}

mysqli_stmt_execute($stmt);
$announcements_result = mysqli_stmt_get_result($stmt);

?>


<div class="table-responsive" style="overflow: visible;">
    <div class="container-fluid p-0">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4 py-3">Title</th>
                            <th class="px-4 py-3">Department</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($announcements_result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($announcements_result)): ?>
                                <tr data-category="<?= htmlspecialchars($row['is_archived']) ?>">
                                    <td class="px-4 text-break" data-label="Title">
                                        <div class="d-flex align-items-center gap-3">
                                            <?php if (!empty($row["image"])): ?>
                                                <div style="width: 40px; height: 40px; overflow: hidden;" class="rounded-circle bg-light d-flex align-items-center justify-content-center">
                                                    <img src="../uploads/announcements/<?php echo htmlspecialchars($row['image']); ?>" alt="Announcement Image" class="img-fluid" style="object-fit: cover; width: 100%; height: 100%;">
                                                </div>
                                            <?php else: ?>
                                                <div style="width: 40px; height: 40px;" class="rounded-circle bg-secondary d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-bullhorn text-white"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-medium"><?= htmlspecialchars($row["title"]) ?></div>
                                                <small class="text-muted"><?= date('M d, Y', strtotime($row["created_at"])) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4" data-label="Department">
                                        <?= htmlspecialchars($row["department_name"] ?? 'All Departments') ?>
                                    </td>
                                    <td class="px-4" data-label="Actions">
                                        <div class="d-flex gap-2 justify-content-center">
                                            <div class="dropdown">
                                                <button class="btn btn-secondary btn-sm dropdown-toggle shadow-sm"
                                                    type="button"
                                                    data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu" style="z-index: 1050; padding: 4px 8px; line-height: 1.2; min-width: 140px;">

                                                    <!-- View Button -->
                                                    <?php if ($row['is_archived'] == 0): ?>
                                                        <li>
                                                            <button class="dropdown-item view-btn"
                                                                data-id="<?= htmlspecialchars($row['id']) ?>"
                                                                title="View"
                                                                style="padding: 4px 12px; line-height: 1.2;">
                                                                View
                                                            </button>
                                                        </li>
                                                    <?php endif; ?>

                                                    <!-- Delete Button -->
                                                    <li>
                                                        <button class="dropdown-item delete-btn"
                                                            data-id="<?= htmlspecialchars($row['id']) ?>"
                                                            title="Delete"
                                                            style="padding: 4px 12px; line-height: 1.2;">
                                                            Delete
                                                        </button>
                                                    </li>

                                                    <!-- Archive/Unarchive Button -->
                                                    <li>
                                                        <button type="button"
                                                            class="dropdown-item archive-btn"
                                                            data-id="<?= htmlspecialchars($row['id']) ?>"
                                                            data-table="announcements"
                                                            data-operation="<?= $row['is_archived'] == 1 ? 'unarchive' : 'archive' ?>"
                                                            style="padding: 4px 12px; line-height: 1.2;">
                                                            <?= $row['is_archived'] == 1 ? 'Unarchive' : 'Archive' ?>
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>

                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-bullhorn fa-3x mb-3 d-block"></i>
                                        <p class="mb-0">No Announcements Found</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>

                </table>
            </div>
        </div>
    </div>

    <!-- Pagination Controls -->
    <div class="d-flex justify-content-center mt-3">
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>&department_id=<?= $department_id ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&department_id=<?= $department_id ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>

</div>

<script>
    function confirmEdit(event) {
        event.preventDefault(); // Prevent form from submitting immediately

        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to save these changes?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, save changes',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // If user confirms, submit the form
                document.getElementById('editAnnouncementForm').submit();
            }
        });

        return false; // Prevent form from submitting normally
    }

    // Add this to your existing JavaScript
    function editAnnouncement(id) {
        fetch('get_announcement.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                document.getElementById('edit_announcement_id').value = data.id;
                document.getElementById('edit_title').value = data.title;
                document.getElementById('edit_message').value = data.message;

                // Set department if it exists and user is School Admin
                const departmentSelect = document.getElementById('edit_department');
                if (departmentSelect && data.department_id) {
                    departmentSelect.value = data.department_id;
                }

                // Show current image preview if exists
                const imagePreview = document.getElementById('imagePreview');
                if (data.image) {
                    imagePreview.src = `../uploads/${data.image}`;
                    imagePreview.style.display = 'block';
                } else {
                    imagePreview.style.display = 'none';
                }

                // Show the modal
                new bootstrap.Modal(document.getElementById('editAnnouncementModal')).show();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading announcement details');
            });
    }

    // Add preview for new image upload
    document.getElementById('edit_image').addEventListener('change', function(e) {
        const imagePreview = document.getElementById('imagePreview');
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
            }
            reader.readAsDataURL(this.files[0]);
        }
    });
</script>

<script>
    // Handle success and error messages
    <?php if (isset($successMessage) && !empty($successMessage)): ?>
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '<?php echo $successMessage; ?>',
            showConfirmButton: false,
            timer: 1500
        });
    <?php endif; ?>

    <?php if (isset($errorMessage) && !empty($errorMessage)): ?>
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: '<?php echo $errorMessage; ?>'
        });
    <?php endif; ?>

    document.addEventListener('DOMContentLoaded', () => {
        // Handle view button click
        document.querySelectorAll('.view-btn').forEach(button => {
            button.addEventListener('click', () => {
                const announcementId = button.getAttribute('data-id');
                fetch('../../announcements/fetch_announcement.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `id=${announcementId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Update modal content
                        document.getElementById('announcementModalLabel').textContent = data.title || 'Announcement';
                        document.getElementById('modalMessage').textContent = data.message || 'No message available.';
                        document.getElementById('modalImage').src = data.image_url || 'default-image.jpg';

                        // Show the modal
                        const viewModal = new bootstrap.Modal(document.getElementById('announcementModal'));
                        viewModal.show();
                    })
                    .catch(error => console.error('Error fetching announcement:', error));
            });
        });

        // Handle edit button click
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.getAttribute('data-id');
                editAnnouncement(id);
            });
        });

        // Handle delete button click
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', () => {
                const announcementId = button.getAttribute('data-id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('../announcements/delete_announcement.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: `announcement_id=${announcementId}`
                            })
                            .then(response => response.text())
                            .then(() => {
                                Swal.fire('Deleted!', 'Your announcement has been deleted.', 'success')
                                    .then(() => location.reload());
                            })
                            .catch(() => {
                                Swal.fire('Error!', 'There was a problem deleting the announcement.', 'error');
                            });
                    }
                });
            });
        });
    });
</script>