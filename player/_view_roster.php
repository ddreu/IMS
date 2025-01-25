<?php
session_start();
include_once '../connection/conn.php';
$conn = con();
$role = $_SESSION['role'];
$game_name = ($role == 'Committee') ? $_SESSION['game_name'] : null; // or '' if you prefer an empty string
$department_name = $_SESSION['department_name'];

// Check if team_id is set in the URL
if (!isset($_GET['team_id'])) {
    header("Location: teams.php");
    exit();
}

$team_id = intval($_GET['team_id']);
$grade_section_course_id = isset($_GET['grade_section_course_id']) ? intval($_GET['grade_section_course_id']) : null;

// First verify if the team exists and get team details
$team_sql = "SELECT team_name FROM teams WHERE team_id = ?";
$team_stmt = $conn->prepare($team_sql);
$team_stmt->bind_param("i", $team_id);
$team_stmt->execute();
$team_result = $team_stmt->get_result();
$team = $team_result->fetch_assoc();
$team_stmt->close();

// Debug: Print team info
echo "<!-- Team Info: " . print_r($team, true) . " -->";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Roster</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../styles/committee.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
    <style>
        .section-header {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .team-info {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.75rem 1.25rem;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            display: inline-block;
        }

        .card {
            border: none;
            transition: all 0.3s ease;
            border-radius: 10px;
            margin-bottom: 1rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .card-header {
            padding: 1rem 1.25rem;
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,.125);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h5 {
            margin: 0;
            color: black;
            font-weight: 600;
        }

        .card-body {
            padding: 1.25rem;
        }

        .table {
            font-size: 0.875rem;
            margin-bottom: 0;
        }
        
        .table > :not(caption) > * > * {
            padding: 0.75rem;
        }

        .table th {
            font-weight: 600;
            color: #4e73df;
            border-top: none;
            background-color: #f8f9fc;
        }

        .table td {
            vertical-align: middle;
        }

        .player-icon {
            background: rgba(78, 115, 223, 0.1);
            color: #4e73df;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
        }

        .btn-sm {
            padding: 0.4rem 1rem;
            font-size: 0.85rem;
            border-radius: 15px;
            margin: 0 0.2rem;
        }

        .btn-danger {
            background-color: transparent;
            border: 2px solid #e74a3b;
            color: black;
        }

        .btn-danger:hover {
            background-color: #e74a3b;
            color: white;
        }

        .btn-warning {
            background-color: transparent;
            border: 2px solid #f6c23e;
            color: black;
        }

        .btn-warning:hover {
            background-color: #f6c23e;
            color: white;
        }

        .btn-info {
            background-color: transparent;
            border: 2px solid #36b9cc;
            color: black;
        }

        .btn-info:hover {
            background-color: #36b9cc;
            color: white;
        }

        .btn-primary {
            background-color: transparent;
            border: 2px solid #4e73df;
            color: black;
            padding: 0.5rem 1.2rem;
            font-size: 0.9rem;
        }

        .btn-primary:hover {
            background-color: #4e73df;
            color: white;
        }

        .back-button {
            color: #4e73df;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-bottom: 1rem;
            transition: all 0.2s ease;
        }

        .back-button:hover {
            transform: translateX(-3px);
            text-decoration: none;
            color: #224abe;
        }

        .back-button i {
            margin-right: 0.5rem;
        }

        .empty-roster {
            text-align: center;
            padding: 3rem 1rem;
            color: #858796;
        }

        .empty-roster i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #4e73df;
            opacity: 0.5;
        }

        .empty-roster h5 {
            color: #5a5c69;
            margin-bottom: 0.5rem;
        }

        .empty-roster p {
            color: #858796;
            margin-bottom: 0;
        }

        .status-badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 500;
            border-radius: 10px;
            text-transform: capitalize;
        }

        .status-active {
            background-color: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
        }

        .status-inactive {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }

        .player-name {
            font-weight: 500;
            color: #5a5c69;
        }

        .player-info {
            font-size: 0.8125rem;
            color: #858796;
        }

        .modal-content {
            border: none;
            border-radius: 10px;
        }

        .modal-header {
            background-color: #4e73df;
            color: white;
            border-radius: 10px 10px 0 0;
        }

        .modal-footer {
            border-top: 1px solid #e3e6f0;
            padding: 1rem;
        }

        @media (max-width: 768px) {
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .card-header .btn {
                margin-top: 1rem;
            }

            .table-responsive {
                margin: 0 -1.25rem;
            }

            .btn-sm {
                padding: 0.4rem 0.8rem;
                margin: 0.2rem;
                display: inline-block;
            }
        }
    </style>
</head>

<body>
    <?php 
    $current_page = 'teams'; 
    include '../navbar/navbar.php';
    ?>
    
    <!-- Sidebar -->
    <div class="d-flex">
        <!-- Sidebar Navigation -->
        <nav class="sidebar">
            <?php 
            if ($role == 'Committee') {
                include '../committee/csidebar.php';
            } else {
                include '../department_admin/sidebar.php';
            }
            ?>
        </nav>
        
        <!-- Main Content -->
        <div class="main-content flex-grow-1 p-3">
            <div class="container-fluid">
                <section class="main">
                <a href="<?= $role === 'Committee' ? '../teams/teams.php' : '../teams/adminteams.php' ?>?grade_section_course_id=<?= htmlspecialchars($grade_section_course_id) ?>" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Teams
                </a>

                    <!-- Header with Team Name and Add Player Button -->
                    <div class="card">
                        <div class="card-header">
                            <h5>Player Roster for Team: <?= htmlspecialchars($team['team_name']) ?></h5>
                            <a href="player_registration.php?team_id=<?= $team_id ?>&grade_section_course_id=<?= htmlspecialchars($grade_section_course_id) ?>" class="btn btn-primary btn-sm">Add Player</a>
                        </div>
                    </div>

                    <?php 
                    // Fetch players
                    $player_sql = "
                        SELECT p.player_id, 
                               CONCAT(p.player_lastname, ', ', p.player_firstname, ' ', COALESCE(p.player_middlename, '')) AS player_name, 
                               p.jersey_number,
                               pi.picture
                        FROM players AS p
                        LEFT JOIN players_info AS pi ON p.player_id = pi.player_id
                        WHERE p.team_id = ?";
                    $player_stmt = $conn->prepare($player_sql);
                    $player_stmt->bind_param("i", $team_id);
                    $player_stmt->execute();
                    $result = $player_stmt->get_result();
                    ?>
                    
                    <?php if ($result && $result->num_rows > 0): ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Picture</th>
                                            <th>Player Name</th>
                                            <th>Jersey Number</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Debug: Print before loop
                                        echo "<!-- Starting loop -->";
                                        while ($row = $result->fetch_assoc()) {
                                            // Debug: Print each row
                                            echo "<!-- Processing row: " . print_r($row, true) . " -->";
                                        ?>
                                            <tr>
                                                <td>
                                                    <?php if (!empty($row['picture'])): ?>
                                                        <img src="<?= htmlspecialchars($row['picture']) ?>" alt="Player Picture" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <img src="../uploads/players/default.png" alt="Default Picture" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($row['player_name']) ?></td>
                                                <td><?= htmlspecialchars($row['jersey_number']) ?></td>
                                                <td>
                                                <a href="player_details.php?player_id=<?= $row['player_id'] ?>&grade_section_course_id=<?= htmlspecialchars($grade_section_course_id) ?>" class="btn btn-info btn-sm">View</a>                                           
                                                 <a href="#" class="btn btn-warning btn-sm edit-player-btn" data-player-id="<?= $row['player_id'] ?>">Edit</a>
                                                    <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete(<?= htmlspecialchars($row['player_id']) ?>, <?= htmlspecialchars($team_id) ?>, <?= htmlspecialchars($grade_section_course_id) ?>)">Delete</button>
                                                    </td>
                                            </tr>
                                        <?php 
                                            // Debug: Print after row
                                            echo "<!-- Finished processing row -->";
                                        } 
                                        // Debug: Print after loop
                                        echo "<!-- Loop finished -->";
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                        <div class="empty-roster">
                            <i class="fas fa-users"></i>
                            <h5>No Players Found</h5>
                            <p>This team has no players yet.</p>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
         <!-- Edit Player Modal -->
    <div class="modal fade" id="editPlayerModal" tabindex="-1" aria-labelledby="editPlayerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPlayerModalLabel">Edit Player Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editPlayerForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="editPlayerId" name="player_id">
                        <div class="d-flex">
                            <!-- Player Image and Image Upload -->
                            <div class="me-3">
                                <img id="editPlayerImage" src="" alt="Player Picture" style="width: 150px; height: 150px; object-fit: cover; margin-bottom: 10px;">
                                <div>
                                    <label for="editPicture" class="form-label">Update Picture</label>
                                    <input type="file" class="form-control" id="editPicture" name="picture" accept="image/*" onchange="previewEditImage()">
                                </div>
                            </div>

                            <!-- Player Information Fields -->
                            <div class="container">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="editPlayerLastname" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="editPlayerLastname" name="player_lastname" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="editPlayerFirstname" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="editPlayerFirstname" name="player_firstname" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="editPlayerMiddlename" class="form-label">Middle Name</label>
                                        <input type="text" class="form-control" id="editPlayerMiddlename" name="player_middlename">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="editJerseyNumber" class="form-label">Jersey Number</label>
                                        <input type="number" class="form-control" id="editJerseyNumber" name="jersey_number" required min="1" max="99">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="editEmail" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="editEmail" name="email" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="editPhoneNumber" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="editPhoneNumber" name="phone_number" maxlength="13">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="editDateOfBirth" class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" id="editDateOfBirth" name="date_of_birth">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="editHeight" class="form-label">Height</label>
                                        <input type="text" class="form-control" id="editHeight" name="height">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="editWeight" class="form-label">Weight</label>
                                        <input type="text" class="form-control" id="editWeight" name="weight">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="editPosition" class="form-label">Position</label>
                                        <input type="text" class="form-control" id="editPosition" name="position">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Edit player details
            document.querySelectorAll(".edit-player-btn").forEach(button => {
                button.addEventListener("click", function() {
                    const playerId = this.getAttribute("data-player-id");

                    // Fetch player data using AJAX
                    fetch(`get_player_info.php?player_id=${playerId}`)
                        .then(response => response.json())
                        .then(data => {
                            // Decode HTML entities in the data
                            const decodeHtml = (html) => {
                                const txt = document.createElement("textarea");
                                txt.innerHTML = html;
                                return txt.value;
                            };

                            // Populate modal with player data
                            document.getElementById("editPlayerId").value = playerId;
                            document.getElementById("editPlayerImage").src = data.picture || "../uploads/players/default.png";
                            document.getElementById("editPlayerLastname").value = decodeHtml(data.lastname || '');
                            document.getElementById("editPlayerFirstname").value = decodeHtml(data.firstname || '');
                            document.getElementById("editPlayerMiddlename").value = decodeHtml(data.middlename || '');
                            document.getElementById("editJerseyNumber").value = decodeHtml(data.jersey_number || '');
                            document.getElementById("editEmail").value = decodeHtml(data.email || '');
                            document.getElementById("editPhoneNumber").value = decodeHtml(data.phone_number || '');
                            document.getElementById("editDateOfBirth").value = decodeHtml(data.date_of_birth || '');
                            document.getElementById("editHeight").value = decodeHtml(data.height || '');
                            document.getElementById("editWeight").value = decodeHtml(data.weight || '');
                            document.getElementById("editPosition").value = decodeHtml(data.position || '');

                            // Show the modal
                            const modal = new bootstrap.Modal(document.getElementById("editPlayerModal"));
                            modal.show();
                        })
                        .catch(error => {
                            console.error('Error fetching player data:', error);
                            Swal.fire({
                                icon: "error",
                                title: "Error",
                                text: "Failed to load player data. Please try again.",
                                confirmButtonColor: "#d33"
                            });
                        });
                });
            });

            // Preview function for the image
            document.getElementById('editPicture').addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('editPlayerImage').src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Handle form submission
            document.getElementById("editPlayerForm").addEventListener("submit", function(event) {
                event.preventDefault();
                const formData = new FormData(this);

                fetch("update_player_info.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Success",
                            text: data.message || "Player details updated successfully!",
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: data.message || "An error occurred while updating player details.",
                            confirmButtonColor: "#d33"
                        });
                    }
                })
                .catch(error => {
                    console.error("Error updating player details:", error);
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "An error occurred while updating player details. Please try again.",
                        confirmButtonColor: "#d33"
                    });
                });
            });
        });

        function previewEditImage() {
            const fileInput = document.getElementById("editPicture");
            const imagePreview = document.getElementById("editPlayerImage");

            if (fileInput.files && fileInput.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                };
                reader.readAsDataURL(fileInput.files[0]);
            }
        }

        function confirmDelete(playerId, teamId, gradeSectionCourseId) {
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
            fetch('delete_player.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ 
                    player_id: playerId, 
                    team_id: teamId, 
                    grade_section_course_id: gradeSectionCourseId 
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: data.message || 'The player has been deleted.',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message || 'Failed to delete the player. Please try again.',
                        confirmButtonColor: '#d33'
                    });
                }
            })
            .catch(error => {
                console.error('Error deleting player:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while deleting the player. Please try again later.',
                    confirmButtonColor: '#d33'
                });
            });
        }
    });
}

    </script>

</body>

</html>