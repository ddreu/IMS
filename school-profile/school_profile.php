<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

$school_id = $_SESSION['school_id'];

$query = "SELECT * FROM school_profile WHERE school_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $school_id);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();

$title = $profile['title'] ?? 'Enter Title';
$description = $profile['description'] ?? 'Enter Description';
$cover_image = $profile['image'] ?? '../uploads/default.png';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../styles/dashboard.css">
</head>

<body>
    <?php
    $current_page = 'school_profile';
    include '../navbar/navbar.php';
    include '../department_admin/sidebar.php';
    ?>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">School Profile</h5>
            </div>
            <div class="card-body">
                <!-- Cover Image -->
                <div class="position-relative">
                    <img id="coverImage" src="<?= $cover_image ?>"
                        class="img-fluid rounded"
                        style="width: 100%; height: 400px; max-height: 600px; object-fit: cover;">
                    <input type="file" id="coverInput" class="d-none">
                    <div class="position-absolute top-0 end-0 m-2">
                        <button id="editCoverBtn" class="btn btn-secondary" onclick="editCover()">Edit Cover</button>
                        <button id="saveCoverBtn" class="btn btn-success d-none" onclick="saveCover()">Save</button>
                        <button id="cancelCoverBtn" class="btn btn-danger d-none" onclick="cancelCover()">Cancel</button>
                    </div>
                </div>


                <!-- Title and Description -->
                <div class="mt-3">
                    <label class="form-label">Title</label>
                    <input type="text" id="title" class="form-control" value="<?= $title ?>" disabled>
                </div>

                <div class="mt-3">
                    <label class="form-label">Description</label>
                    <textarea id="description" class="form-control" rows="3" disabled><?= $description ?></textarea>
                </div>


                <!-- Combined Edit and Save Buttons -->
                <div class="mt-3">
                    <button id="editProfileBtn" class="btn btn-primary" onclick="toggleEdit()">Edit</button>
                    <button id="saveProfileBtn" class="btn btn-success d-none" onclick="saveProfile()">Save</button>
                    <button id="cancelProfileBtn" class="btn btn-danger d-none" onclick="cancelEdit()">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let originalCover = '<?= $cover_image ?>';

        // Edit Cover
        function editCover() {
            document.getElementById('coverInput').click();

            document.getElementById('coverInput').onchange = (event) => {
                const file = event.target.files[0];
                if (file) {
                    document.getElementById('coverImage').src = URL.createObjectURL(file);
                    toggleCoverButtons(true);
                }
            };
        }

        function saveCover() {
            const file = document.getElementById('coverInput').files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('cover_image', file);

            fetch('save_school_profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Success!', data.message, 'success');
                        originalCover = document.getElementById('coverImage').src;
                        toggleCoverButtons(false);
                    } else {
                        Swal.fire('Error!', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error!', 'An error occurred while saving the cover image.', 'error');
                });
        }

        function cancelCover() {
            document.getElementById('coverImage').src = originalCover;
            toggleCoverButtons(false);
        }

        function toggleCoverButtons(editing) {
            document.getElementById('editCoverBtn').classList.toggle('d-none', editing);
            document.getElementById('saveCoverBtn').classList.toggle('d-none', !editing);
            document.getElementById('cancelCoverBtn').classList.toggle('d-none', !editing);
        }

        // Edit Title and Description
        let originalTitle = '<?= $title ?>';
        let originalDescription = '<?= $description ?>';

        // Toggle Edit Mode
        function toggleEdit() {
            const isEditing = document.getElementById('title').disabled;

            document.getElementById('title').disabled = !isEditing;
            document.getElementById('description').disabled = !isEditing;

            document.getElementById('editProfileBtn').classList.toggle('d-none');
            document.getElementById('saveProfileBtn').classList.toggle('d-none');
            document.getElementById('cancelProfileBtn').classList.toggle('d-none');
        }

        // Cancel Edit
        function cancelEdit() {
            document.getElementById('title').value = originalTitle;
            document.getElementById('description').value = originalDescription;
            toggleEdit();
        }

        // Save Profile
        function saveProfile() {
            const formData = new FormData();
            formData.append('title', document.getElementById('title').value);
            formData.append('description', document.getElementById('description').value);

            fetch('save_school_profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Success!', data.message, 'success');
                        originalTitle = document.getElementById('title').value;
                        originalDescription = document.getElementById('description').value;
                        toggleEdit();
                    } else {
                        Swal.fire('Error!', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error!', 'An error occurred while saving the profile.', 'error');
                });
        }

        function capitalize(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }
    </script>

</body>

</html>