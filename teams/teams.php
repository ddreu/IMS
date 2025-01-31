<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Get the logged-in committee details
$role = $_SESSION['role'];
$game_id = $_SESSION['game_id'];
$department_id = $_SESSION['department_id'];
$school_id = $_SESSION['school_id'];
$game_name = $_SESSION['game_name'];
$department_name = $_SESSION['department_name'];

// Get grade_section_course_id from URL for School Admin and Department Admin
$grade_section_course_id = isset($_GET['grade_section_course_id']) ? intval($_GET['grade_section_course_id']) : null;

// Fetch teams based on role
if ($role == 'Committee') {
    // Original query for Committee role
    $sql = "
        SELECT t.team_id, t.team_name, gsc.section_name, gsc.grade_level, gsc.course_name, gsc.strand
        FROM teams AS t
        JOIN grade_section_course AS gsc ON t.grade_section_course_id = gsc.id
        JOIN departments AS d ON gsc.department_id = d.id
        WHERE t.game_id = ? AND gsc.department_id = ? AND d.school_id = ?
        ORDER BY gsc.grade_level, gsc.section_name, gsc.course_name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $game_id, $department_id, $school_id);
} else {
    // Query for School Admin and Department Admin
    $sql = "
        SELECT t.team_id, t.team_name, gsc.section_name, gsc.grade_level, gsc.course_name, gsc.strand
        FROM teams AS t
        JOIN grade_section_course AS gsc ON t.grade_section_course_id = gsc.id
        WHERE t.grade_section_course_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $grade_section_course_id);
}

$stmt->execute();
$result = $stmt->get_result();

// Group results by grade level
$teams_by_grade = [];
while ($row = $result->fetch_assoc()) {
    $grade_level = $row['grade_level'];
    $teams_by_grade[$grade_level][] = $row;
}
include '../navbar/navbar.php';

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teams for <?= htmlspecialchars($game_name) ?> - <?= htmlspecialchars($department_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../styles/dashboard.css">
    <link rel="stylesheet" href="../styles/committee.css">
    <style>
        .section-header {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .department-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            display: inline-block;
        }

        .table {
            font-size: 0.95rem;
            margin-bottom: 0;
        }
        
        .table > :not(caption) > * > * {
            padding: 0.85rem;
        }

        .table th {
            font-weight: 600;
            color: #4e73df;
            border-top: none;
            background-color: #f8f9fc;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table td {
            vertical-align: middle;
            font-size: 0.95rem;
            color: black;
        }

        .team-icon {
            background: rgba(78, 115, 223, 0.1);
            color: #4e73df;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
            font-size: 0.875rem;
        }

        .btn-sm {
            padding: 0.4rem 1rem;
            font-size: 0.85rem;
            border-radius: 15px;
            margin: 0 0.2rem;
            border-width: 2px;
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

        @media (max-width: 768px) {
            .btn-sm {
                padding: 0.4rem 0.8rem;
                margin: 0.2rem;
                display: inline-block;
            }
        }

        .card {
            border: none;
            transition: all 0.3s ease;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .card-header {
            padding: 0.75rem;
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,.125);
        }

        .card-body {
            padding: 0.75rem;
        }

        .table-responsive {
            margin: -0.75rem;
        }

        .btn-sm i {
            font-size: 0.75rem;
            margin-right: 0.25rem;
        }

        .badge {
            font-weight: 500;
            padding: 0.35em 0.65em;
            font-size: 0.75rem;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <nav>
        <?php if ($role == 'Committee') {
        include '../committee/csidebar.php';
    } else {
        include '../department_admin/sidebar.php'; // fallback for other roles
    } ?>
    </nav>
    <!-- Main Content -->
    <div class="mt-4">
        <div class="container-fluid">
            <section class="main">
                <div class="main-top d-flex justify-content-between align-items-center">
                    <?php if ($role != 'Committee'): ?>
                        <h2 class="mb-4">
                            <?php 
                            if ($grade_section_course_id) {
                                $sql = "SELECT gsc.*, d.department_name 
                                        FROM grade_section_course gsc
                                        JOIN departments d ON gsc.department_id = d.id
                                        WHERE gsc.id = ?";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("i", $grade_section_course_id);
                                $stmt->execute();
                                $section_details = $stmt->get_result()->fetch_assoc();
                                if ($section_details['department_name'] === 'College') {
                                    echo htmlspecialchars($section_details['course_name']);
                                } else {
                                    echo htmlspecialchars($section_details['grade_level'] . ' - ' . $section_details['section_name']);
                                    if (!empty($section_details['strand'])) {
                                        echo ' (' . htmlspecialchars($section_details['strand']) . ')';
                                    }
                                }
                            }
                            ?> Teams
                        </h2>
                    <?php else: ?>
                        <h2 class="mb-4">Teams for <?= htmlspecialchars($game_name) ?> - <?= htmlspecialchars($department_name) ?></h2>
                    <?php endif; ?>
                </div>

                <?php if (!empty($teams_by_grade)): ?>
                    <?php foreach ($teams_by_grade as $grade_level => $teams): ?>
                        <div class="card shadow mt-3">
                            
                                <div class="row mt-3">
                                    <div class="col text-end">
                                        
                                    <button type="button" class="btn btn-success btn-sm me-3" data-bs-toggle="modal" data-bs-target="#addTeamModal">Add Team</button>                                    <!--<h4 class="m-0 font-weight-bold text-primary"><?= htmlspecialchars($grade_level) ?></h4>-->
                                    </div>
                                </div>
                            
                            <div class="card-body p-4">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <?php if ($department_name === 'College'): ?>
                                                <th>Team Name</th>
                                                <th>Course Name</th>
                                            <?php elseif ($department_name === 'SHS'): ?>
                                                <th>Team Name</th>
                                                <th>Strand</th>
                                                <th>Grade Level</th>
                                                <th>Section Name</th>
                                            <?php elseif ($department_name === 'JHS' || $department_name === 'Elementary'): ?>
                                                <th>Team Name</th>
                                                <th>Grade Level</th>
                                                <th>Section Name</th>
                                            <?php endif; ?>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($teams as $row): ?>
                                            <tr>
                                                <?php if ($department_name === 'College'): ?>
                                                    <td><?= htmlspecialchars($row['team_name']) ?></td>
                                                    <td><?= htmlspecialchars($row['course_name'] ?? '-') ?></td>
                                                <?php elseif ($department_name === 'SHS'): ?>
                                                    <td><?= htmlspecialchars($row['team_name']) ?></td>
                                                    <td><?= htmlspecialchars($row['strand'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars($row['grade_level']) ?></td>
                                                    <td><?= htmlspecialchars($row['section_name']) ?></td>
                                                <?php elseif ($department_name === 'JHS' || $department_name === 'Elementary'): ?>
                                                    <td><?= htmlspecialchars($row['team_name']) ?></td>
                                                    <td><?= htmlspecialchars($row['grade_level']) ?></td>
                                                    <td><?= htmlspecialchars($row['section_name']) ?></td>
                                                <?php endif; ?>
                                                <td>
                                                    <a href="../player/view_roster.php?team_id=<?= htmlspecialchars($row['team_id']) ?>&grade_section_course_id=<?= htmlspecialchars($grade_section_course_id) ?>" class="btn btn-info btn-sm">View Roster</a>
                                                    <a href="../player/player_registration.php?team_id=<?= htmlspecialchars($row['team_id']) ?>&grade_section_course_id=<?= htmlspecialchars($grade_section_course_id) ?>" class="btn btn-primary btn-sm">Register Player</a>
                                                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editTeamModal" 
                                                    data-team-id="<?= htmlspecialchars($row['team_id']) ?>" 
                                                    data-team-name="<?= htmlspecialchars($row['team_name']) ?>">
                                                        Edit
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeletion(<?= htmlspecialchars($row['team_id']) ?>)">Delete</button>
                                                    </td>

                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No teams registered yet for this game and department.</p>
                <?php endif; ?>
            </section>
        </div>
    </div>
   
    <?php include "addteam_modal.php"; ?>
    <?php include "edit_team_modal.php"; ?>
    <script>
        // Populate the modal with team ID when button is clicked
        document.querySelectorAll('[data-bs-target="#registerPlayerModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const teamId = this.getAttribute('data-team-id');
                document.getElementById('modalTeamId').value = teamId;
            });
        });

        document.addEventListener("DOMContentLoaded", () => {
        const departmentId = <?= $_SESSION['department_id']; ?>; // Replace with actual session variable
        const gscDropdown = document.getElementById("gscDropdown");

        // Fetch data from the server
        fetch(`fetch_gsc.php?department_id=${departmentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    data.forEach(item => {
                        const option = document.createElement("option");
                        option.value = item.id; // Use the appropriate ID field
                        option.textContent = `${item.grade_level || ''} ${item.strand || ''} ${item.section_name || ''} ${item.course_name || ''}`.trim();
                        gscDropdown.appendChild(option);
                    });
                } else {
                    const option = document.createElement("option");
                    option.value = "";
                    option.textContent = "No entries available";
                    gscDropdown.appendChild(option);
                }
            })
            .catch(error => console.error("Error fetching GSC data:", error));
    });

    document.getElementById("saveTeamBtn").addEventListener("click", function() {
    const formData = new FormData(document.getElementById("addTeamForm"));

    fetch('team_op.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: data.message,
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                // Optionally, reload the page or update the UI
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: data.message,
                showConfirmButton: true
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'An unexpected error occurred.',
            showConfirmButton: true
        });
    });
});

    </script>
    <script>
function confirmDeletion(teamId) {
    Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, delete it!"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `delete_team.php?team_id=${teamId}`;
        }
    });
}
</script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    <?php if (isset($_SESSION['success_message'])): ?>
        Swal.fire({
            icon: 'success',
            title: '<?= $_SESSION['success_message'] ?>',
            showConfirmButton: false,
            timer: 1500
        });
        <?php unset($_SESSION['success_message']); // Clear the session message ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        Swal.fire({
            icon: 'error',
            title: '<?= $_SESSION['error_message'] ?>',
            showConfirmButton: true
        });
        <?php unset($_SESSION['error_message']); // Clear the session message ?>
    <?php endif; ?>
});
</script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
    // Populate modal with the current team name
    document.querySelectorAll("[data-bs-target='#editTeamModal']").forEach(button => {
        button.addEventListener("click", function() {
            document.getElementById("editTeamId").value = this.getAttribute("data-team-id");
            document.getElementById("editTeamName").value = this.getAttribute("data-team-name");
        });
    });

    // Handle form submission for renaming the team
    document.getElementById("editTeamForm").addEventListener("submit", function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('edit_team.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: data.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: data.message,
                    showConfirmButton: true
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'An unexpected error occurred.',
                showConfirmButton: true
            });
        });
    });
});

</script>    
</body>

</html>