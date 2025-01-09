<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

// Check if team_id is set in the URL
if (!isset($_GET['team_id'])) {
    header("Location: teams.php");
    exit();
}
$role = $_SESSION['role'];
$team_id = intval($_GET['team_id']);
$grade_section_course_id = isset($_GET['grade_section_course_id']) ? intval($_GET['grade_section_course_id']) : null;

// Fetch the team name, game category, and game name using the team_id
$sql = "SELECT t.team_name, g.category AS game_category, g.game_name
        FROM teams t
        JOIN games g ON t.game_id = g.game_id
        WHERE t.team_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $team_id);
$stmt->execute();
$result = $stmt->get_result();
$team = $result->fetch_assoc();
$stmt->close();

// Access the game_name for use in the title
$game_name = $team['game_name'] ?? '';


if (!$team) {
    echo "Team not found.";
    exit();
}

$game_category = $team['game_category']; // Game category (e.g., "individual" or "team")
include '../navbar/navbar.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teams for <?= htmlspecialchars($game_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../styles/committee.css">
    <link rel="stylesheet" href="../styles/dashboard.css">

</head>

<body>
    <!-- Sidebar -->
    <nav>
        <?php if ($role == 'Committee') {
                include '../committee/csidebar.php';
            } else {
                include '../department_admin/sidebar.php';
            } ?>
    </nav>

    <section class="main">
    <div class="container mt-3">
        <!-- Back Button and Form Heading -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="view_roster.php?team_id=<?= htmlspecialchars($team_id) ?>&grade_section_course_id=<?= htmlspecialchars($grade_section_course_id) ?>" class="btn btn-outline-secondary btn-sm">
                &larr; Back to Roster
            </a>
            <h2 class="mb-0 mx-auto">Register New Player</h2>
        </div>

        <div class="d-flex justify-content-between mb-3">
            <h5 class="text-muted mt-4">
                Team: <?= htmlspecialchars($team['team_name']) ?>
            </h5>
        </div>

        <form id="playerForm" method="POST" action="register_player_process.php?grade_section_course_id=<?= htmlspecialchars($grade_section_course_id) ?>" enctype="multipart/form-data">
            <div class="row mb-3 align-items-start">
                <div class="col-md-3">
                    <!-- Picture Preview Container -->
                    <div class="mt-3 text-center border border-secondary rounded p-2" style="min-height: 150px; background-color: #f8f9fa;">
                        <img id="picturePreview" src="" alt="Image Preview" style="display:none; margin-top: 10px; width: 100%; height: 100%; object-fit: cover;" />
                        <p class="mt-2 text-muted" id="previewText" style="display: block;">No picture chosen. Please upload.</p>
                    </div>
                    <label for="picture" class="form-label mt-2">Player Picture</label>
                    <input type="file" class="form-control" id="picture" name="picture" accept="image/*" onchange="previewImage(event)">
                </div>
                <div class="col-md-9">
                    <!-- Row for Last Name, First Name -->
                    <div class="row mb-3">
                        <div class="col">
                            <label for="player_lastname" class="form-label">Last Name:</label>
                            <input type="text" class="form-control" id="player_lastname" name="player_lastname" placeholder="Last Name" required>
                        </div>
                        <div class="col">
                            <label for="player_firstname" class="form-label">First Name:</label>
                            <input type="text" class="form-control" id="player_firstname" name="player_firstname" placeholder="First Name" required>
                        </div>
                    </div>

                    <!-- Row for Middle Name, Email -->
                    <div class="row mb-3">
                        <div class="col">
                            <label for="player_middlename" class="form-label">Middle Name:</label>
                            <input type="text" class="form-control" id="player_middlename" name="player_middlename" placeholder="Middle Name">
                        </div>
                        <div class="col">
                            <label for="email" class="form-label">Email:</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="example@gmail.com" required>
                        </div>
                    </div>

                    <!-- Row for Phone Number, Date of Birth -->
                    <div class="row mb-3">
                        <div class="col">
                            <label for="phone_number" class="form-label">Phone Number:</label>
                            <div class="input-group">
                                <span class="input-group-text">+63</span>
                                <input type="tel" class="form-control" id="phone_number" name="phone_number" required
                                    pattern="^[0-9]{11}$" maxlength="11" title="Enter 10 digits after +63"
                                    placeholder="Enter phone number (e.g., 09123456789)">
                            </div>
                        </div>
                        <div class="col">
                            <label for="date_of_birth" class="form-label">Date of Birth:</label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth">
                            <small id="dob_error" class="text-danger"></small>

                        </div>
                    </div>

                    <!-- Jersey Number (always visible) -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="jersey_number" class="form-label">Jersey Number:</label>
                            <input type="number" class="form-control" id="jersey_number" name="jersey_number" placeholder="Jersey Number" required min="1" max="99">
                        </div>
                        <div class="col-md-4" id="positionRow">
                            <label for="position" class="form-label">Position:</label>
                            <input type="text" class="form-control" id="position" name="position" placeholder="Position">
                        </div>
                    </div>

                    <!-- Physical Attributes (hidden for individual sports) -->
                    <div class="row mb-3" id="physicalAttributes">
                        <div class="col">
                            <label for="height" class="form-label">Height:</label>
                            <input type="text" class="form-control" id="height" name="height" placeholder="e.g., 5'6">
                        </div>
                        <div class="col">
                            <label for="weight" class="form-label">Weight:</label>
                            <input type="text" class="form-control" id="weight" name="weight" placeholder="e.g., 150 lbs">
                        </div>
                    </div>

                    <input type="hidden" name="team_id" value="<?= htmlspecialchars($team_id) ?>">

                    <button type="submit" class="btn btn-primary" style="position: fixed; bottom: 20px; right: 20px;">Register Player</button>
                </div>
            </form>

            <script>
                document.getElementById("playerForm").addEventListener("submit", function(event) {
                    const dobField = document.getElementById("date_of_birth");
                    const dobError = document.getElementById("dob_error");
                    const dobValue = dobField.value;

                    const validation = validateBirthdate(dobValue);
                    if (!validation.valid) {
                        event.preventDefault(); // Prevent form submission
                        dobError.textContent = validation.message; // Show the error message dynamically
                    }
                });

                function validateBirthdate(birthdate) {
                    const today = new Date();
                    const minAge = 7; // Minimum age (e.g., for school entry)
                    const maxAge = 50; // Maximum realistic age
                    const birthDate = new Date(birthdate);

                    if (!birthdate || isNaN(birthDate.getTime())) {
                        return { valid: false, message: "Please enter a valid date." };
                    }

                    if (birthDate > today) {
                        return { valid: false, message: "Date of birth cannot be in the future." };
                    }

                    const age = today.getFullYear() - birthDate.getFullYear();
                    const monthDifference = today.getMonth() - birthDate.getMonth();
                    const dayDifference = today.getDate() - birthDate.getDate();

                    const correctedAge = (monthDifference < 0 || (monthDifference === 0 && dayDifference < 0)) ? age - 1 : age;

                    if (correctedAge < minAge) {
                        return { valid: false, message: `Age must be at least ${minAge} years old.` };
                    }
                    if (correctedAge > maxAge) {
                        return { valid: false, message: `Age cannot exceed ${maxAge} years.` };
                    }

                    return { valid: true, message: "Date of birth is valid." };
                }

                function previewImage(event) {
                    const preview = document.getElementById('picturePreview');
                    const previewText = document.getElementById('previewText');
                    const file = event.target.files[0];

                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                            previewText.style.display = 'none';
                        }
                        reader.readAsDataURL(file);
                    } else {
                        preview.src = "";
                        preview.style.display = 'none';
                        previewText.style.display = 'block';
                    }
                }

                var gameCategory = "<?php echo $game_category; ?>";
                if (gameCategory === "Individual Sports") {
                    document.getElementById("physicalAttributes").style.display = "none";
                    document.getElementById("positionRow").style.display = "none";
                }

                <?php if (isset($_SESSION['success_message'])): ?>
                    Swal.fire({
                        icon: 'success',
                        title: 'Player Added!',
                        text: 'The player has been successfully registered.',
                    });
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'An error occurred while registering the player. Please try again.',
                    });
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>
            </script>
        </div>
    </section>
</body>


</html>