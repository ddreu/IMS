<?php
session_start();
include_once '../connection/conn.php';

$conn = con();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header('Content-Type: application/json');
    
    try {
        // Retrieve and sanitize form data
        $school_id = intval($_POST['school_id']);
        $school_name = $conn->real_escape_string($_POST['school_name']);
        $school_code = $conn->real_escape_string($_POST['school_code']);
        $address = $conn->real_escape_string($_POST['address']);

        // Start transaction
        $conn->begin_transaction();

        // Handle logo upload if provided
        $logo_file = "";
        if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/logos/';
            $file_tmp = $_FILES['school_logo']['tmp_name'];
            $file_name = basename($_FILES['school_logo']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Validate file type
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($file_ext, $allowed_ext)) {
                throw new Exception("Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.");
            }

            $new_file_name = uniqid('school_', true) . '.' . $file_ext;
            $file_path = $upload_dir . $new_file_name;

            // Move uploaded file
            if (!move_uploaded_file($file_tmp, $file_path)) {
                throw new Exception("Failed to upload logo file.");
            }
            $logo_file = $new_file_name;

            // Delete old logo if exists
            $old_logo_query = "SELECT logo FROM schools WHERE school_id = ?";
            $stmt = $conn->prepare($old_logo_query);
            $stmt->bind_param("i", $school_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $old_logo = $row['logo'];
                if ($old_logo && file_exists($upload_dir . $old_logo)) {
                    unlink($upload_dir . $old_logo);
                }
            }
        }

        // Update school information
        $update_sql = "UPDATE schools SET school_name = ?, school_code = ?, address = ?";
        $params = [$school_name, $school_code, $address];
        $types = "sss";

        if ($logo_file) {
            $update_sql .= ", logo = ?";
            $params[] = $logo_file;
            $types .= "s";
        }

        $update_sql .= " WHERE school_id = ?";
        $params[] = $school_id;
        $types .= "i";

        $stmt = $conn->prepare($update_sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare update statement");
        }

        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update school information");
        }

        $conn->commit();
        echo json_encode([
            "status" => "success",
            "message" => "School information updated successfully!"
        ]);

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollback();
        }
        echo json_encode([
            "status" => "error",
            "message" => "Error: " . $e->getMessage()
        ]);
    }
    exit();
}

// Get school ID
$school_id = isset($_GET['school_id']) ? $_GET['school_id'] : null;

// Check if school_id is empty or invalid
if (empty($school_id)) {
    $_SESSION['error_message'] = "School ID is missing or invalid.";
    header("Location: schools.php");
    exit();
}

// Fetch school data
$sql = "SELECT * FROM schools WHERE school_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $school_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "School not found!";
    header("Location: schools.php");
    exit();
}

$school = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit School Information</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/ad90023682.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../super_admin/sastyles.css">
</head>

<body>
    <?php include '../super_admin/sa_sidebar.php'; ?>

    <div class="main-content">
        <div class="container mt-4">
            <h2>Edit School Information</h2>
            <form id="updateSchoolForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="school_id" value="<?php echo htmlspecialchars($school['school_id']); ?>">
                
                <div class="mb-3">
                    <label for="school_name" class="form-label">School Name</label>
                    <input type="text" class="form-control" id="school_name" name="school_name" value="<?php echo htmlspecialchars($school['school_name']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="school_code" class="form-label">School Code</label>
                    <input type="text" class="form-control" id="school_code" name="school_code" value="<?php echo htmlspecialchars($school['school_code']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($school['address']); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="school_logo" class="form-label">School Logo</label>
                    <div class="row">
                        <!-- Current Logo -->
                        <div class="col-md-6">
                            <label class="form-label">Current Logo:</label>
                            <div class="border p-3 text-center" style="min-height: 120px; display: flex; justify-content: center; align-items: center; background-color: white;">
                                <?php if ($school['logo']): ?>
                                    <img src="../uploads/logos/<?php echo htmlspecialchars($school['logo']); ?>" alt="Current Logo" style="max-height: 100px; max-width: 100%;">
                                <?php else: ?>
                                    <p class="text-muted mb-0">No logo uploaded</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- New Logo Preview -->
                        <div class="col-md-6">
                            <label class="form-label">New Logo Preview:</label>
                            <div class="border p-3 text-center" style="min-height: 120px; display: flex; justify-content: center; align-items: center; background-color: white;">
                                <img id="logo_preview" src="#" alt="Logo Preview" style="max-height: 100px; max-width: 100%; display: none;">
                                <p id="preview_text" class="text-muted mb-0">Logo preview will appear here</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <input type="file" class="form-control" id="school_logo" name="school_logo" accept="image/*" onchange="previewLogo()">
                        <small class="text-muted">Leave empty to keep current logo. Accepted formats: JPG, JPEG, PNG, GIF</small>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Update School</button>
                <a href="schools.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>

    <script>
    function previewLogo() {
        const file = document.getElementById("school_logo").files[0];
        const preview = document.getElementById("logo_preview");
        const previewText = document.getElementById("preview_text");

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = "block";
                previewText.style.display = "none";
            }
            reader.readAsDataURL(file);
        } else {
            preview.style.display = "none";
            previewText.style.display = "block";
        }
    }

    document.getElementById('updateSchoolForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to update this school's information?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, update it!'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData(this);
                
                fetch('update_school.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: data.message,
                            showConfirmButton: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'schools.php';
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.message
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred while updating the school.'
                    });
                });
            }
        });
    });
    </script>
</body>
</html>