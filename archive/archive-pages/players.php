<?php
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

<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> -->
<!-- <link rel="stylesheet" href="../styles/committee.css">
<link rel="stylesheet" href="../styles/dashboard.css"> -->
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

    /* 
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    } */

    .card-header {
        padding: 1rem 1.25rem;
        background-color: white;
        border-bottom: 1px solid rgba(0, 0, 0, .125);
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

    .table> :not(caption)>*>* {
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

    .btn-bulk-upload {
        background-color: transparent;
        border: 2px solid #28a745;
        /* Change the color if needed */
        color: black;
        padding: 0.5rem 1.2rem;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }

    .btn-bulk-upload:hover {
        background-color: #28a745;
        /* Match the border color */
        color: white;
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

    #playerDetailsModal * {
        transition: none !important;
        animation: none !important;
    }



    /* .modal-body {
        max-height: 75vh;
        min-height: 400px;
        overflow-y: auto;
    }

    #playerModalContent {
        min-height: 500px;
    }

    .modal-content {
        min-height: 600px;
        overflow: hidden;
    } */



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






<!-- Main Content -->

<a href="javascript:history.back()" class="back-button">
    <i class="fas fa-arrow-left"></i> Back
</a>

<!-- Header with Team Name and Add Player Button -->
<div class="card">
    <div class="card-header">
        <h5>Player Roster for Team: <?= htmlspecialchars($team['team_name']) ?></h5>

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
                        WHERE p.team_id = ?
                        AND p.is_archived = 1";
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
                                    <a href="javascript:void(0);"
                                        class="btn btn-info btn-sm view-player-btn"
                                        data-player-id="<?= $row['player_id'] ?>"
                                        data-grade-section-id="<?= htmlspecialchars($grade_section_course_id) ?>">
                                        View
                                    </a>

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

<div class="modal" id="playerDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Player Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="playerModalContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2">Loading player info...</p>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modalElement = document.getElementById('playerDetailsModal');
        const modalInstance = new bootstrap.Modal(modalElement);
        const modalContent = document.getElementById('playerModalContent');

        document.querySelectorAll('.view-player-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const playerId = btn.getAttribute('data-player-id');
                const gradeSectionId = btn.getAttribute('data-grade-section-id');

                modalContent.innerHTML = `
                <div style="height: 400px;" class="d-flex align-items-center justify-content-center">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2">Loading player info...</p>
                    </div>
                </div>
            `;

                try {
                    const res = await fetch(`archive-pages/player_details.php?player_id=${playerId}&grade_section_course_id=${gradeSectionId}&modal=1`);
                    const html = await res.text();
                    modalContent.innerHTML = html;

                    // Slight delay to let DOM settle
                    setTimeout(() => {
                        modalInstance.show();
                    }, 50);
                } catch (err) {
                    console.error('Failed to load content:', err);
                    modalContent.innerHTML = `<div class="alert alert-danger">Failed to load player info.</div>`;
                    setTimeout(() => {
                        modalInstance.show();
                    }, 50);
                }
            });
        });
    });
</script>