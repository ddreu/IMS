<?php
session_start();
include_once '../connection/conn.php';
include "../user_logs/logger.php"; // Include the logger at the top
$conn = con();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); // Redirect to login page if not logged in
    exit();
}
$role = $_SESSION['role'];
// Fetch the logged-in user's information
$user_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'] ?? null;

include '../navbar/navbar.php';

$success = false; // Initialize success variable
$error = ''; // Initialize error variable

// Fetch existing data
$conn = con();
$stmt = $conn->prepare("SELECT first_place_points, second_place_points, third_place_points FROM pointing_system WHERE school_id = ?");
$stmt->bind_param("i", $school_id);
$stmt->execute();
$stmt->bind_result($old_first_place_points, $old_second_place_points, $old_third_place_points);
$stmt->fetch();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get new values from the form
    $first_place_points = $_POST['first_place_points'];
    $second_place_points = $_POST['second_place_points'];
    $third_place_points = $_POST['third_place_points'];

    // Initialize description and change tracking
    $log_description = '';
    $changes = [];

    if ($first_place_points != $old_first_place_points) {
        $changes['first_place_points'] = ['old' => $old_first_place_points, 'new' => $first_place_points];
    }
    if ($second_place_points != $old_second_place_points) {
        $changes['second_place_points'] = ['old' => $old_second_place_points, 'new' => $second_place_points];
    }
    if ($third_place_points != $old_third_place_points) {
        $changes['third_place_points'] = ['old' => $old_third_place_points, 'new' => $third_place_points];
    }

    if (!empty($changes)) {
        foreach ($changes as $field => $value) {
            $log_description .= "Updated $field from {$value['old']} to {$value['new']}. ";
        }
    }

    // Check if a record already exists for the school_id
    $conn = con();
    $stmt = $conn->prepare("SELECT COUNT(*) FROM pointing_system WHERE school_id = ? AND is_archived = 0");
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        // Update the existing record
        $stmt = $conn->prepare("UPDATE pointing_system SET first_place_points = ?, second_place_points = ?, third_place_points = ? WHERE school_id = ? AND is_archived = 0");
        $stmt->bind_param("iiii", $first_place_points, $second_place_points, $third_place_points, $school_id);
        if ($stmt->execute()) {
            $success = true; // Set success to true
            // Log the update operation
            logUserAction(
                $conn,
                $user_id,
                'Pointing System',
                'UPDATE',
                $school_id,
                $log_description,
                json_encode([
                    'first_place_points' => $old_first_place_points,
                    'second_place_points' => $old_second_place_points,
                    'third_place_points' => $old_third_place_points
                ]),
                json_encode([
                    'first_place_points' => $first_place_points,
                    'second_place_points' => $second_place_points,
                    'third_place_points' => $third_place_points
                ])
            );
        } else {
            $error = $stmt->error; // Capture error message
        }
    } else {
        // Insert a new record
        $stmt = $conn->prepare("INSERT INTO pointing_system (school_id, first_place_points, second_place_points, third_place_points) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiii", $school_id, $first_place_points, $second_place_points, $third_place_points);
        if ($stmt->execute()) {
            $success = true; // Set success to true
            // Log the insert operation
            logUserAction(
                $conn,
                $user_id,
                'pointing_system',
                'INSERT',
                $school_id,
                "Inserted new pointing system values.",
                null,
                json_encode([
                    'first_place_points' => $first_place_points,
                    'second_place_points' => $second_place_points,
                    'third_place_points' => $third_place_points
                ])
            );
        } else {
            $error = $stmt->error; // Capture error message
        }
    }

    // Close connections
    $stmt->close();
    $conn->close();
}

// Fetch existing data after any changes
$conn = con();
$stmt = $conn->prepare("SELECT first_place_points, second_place_points, third_place_points FROM pointing_system WHERE school_id = ? AND is_archived = 0");
$stmt->bind_param("i", $school_id);
$stmt->execute();
$stmt->bind_result($first_place_points, $second_place_points, $third_place_points);
$stmt->fetch();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pointing System</title>

    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <!-- Font Awesome CDN for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../styles/dashboard.css">
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const success = <?php echo json_encode($success); ?>;
            const error = <?php echo json_encode($error); ?>;
            if (success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Points are set!',
                });
            }
            if (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: '<?php echo $error; ?>',
                });
            }
        });
    </script>
</head>

<body>
    <div class="wrapper">
        <?php
        $current_page = 'pointingsystem';
        if ($role == 'Committee') {
            include '../committee/csidebar.php';
        } else if ($role == 'superadmin') {
            include '../super_admin/sa_sidebar.php';
        } else {
            include '../department_admin/sidebar.php';
        }        ?>

        <!-- Page Content -->
        <div id="content">
            <!-- Page Header and Action Button -->
            <div class="container mt-4">
                <h2 class="mb-4">Pointing System</h2>
                <form action="" method="POST" class="bg-light border p-4 rounded shadow">
                    <div class="form-group mb-3">
                        <label for="first_place_points" class="form-label">First Place Points:</label>
                        <input type="number" name="first_place_points" id="first_place_points" value="<?php echo $first_place_points ?? ''; ?>" required class="form-control">
                    </div>
                    <div class="form-group mb-3">
                        <label for="second_place_points" class="form-label">Second Place Points:</label>
                        <input type="number" name="second_place_points" id="second_place_points" value="<?php echo $second_place_points ?? ''; ?>" required class="form-control">
                    </div>
                    <div class="form-group mb-3">
                        <label for="third_place_points" class="form-label">Third Place Points:</label>
                        <input type="number" name="third_place_points" id="third_place_points" value="<?php echo $third_place_points ?? ''; ?>" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Save</button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>