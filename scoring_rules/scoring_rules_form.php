<?php
session_start();
include_once '../connection/conn.php';
include '../user_logs/logger.php';
$conn = con();

$assigned_game_id = $_SESSION['game_id'];
$assigned_department_id = $_SESSION['department_id'];
$game_name = $_SESSION['game_name'];
$department_name = $_SESSION['department_name'];



// Fetch the school_id linked to the assigned game
$query = "SELECT school_id FROM games WHERE game_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $assigned_game_id);
$stmt->execute();
$stmt->bind_result($assigned_school_id);
$stmt->fetch();
$stmt->close(); // Close this statement


// Separate handling for Scoring Rules
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scoring_unit'])) {
    $scoring_unit = $_POST['scoring_unit'];
    $score_increment_options = $_POST['score_increment_options'];
    $period_type = $_POST['period_type'];
    $number_of_periods = $_POST['number_of_periods'];
    $duration_per_period = $_POST['duration_per_period'];
    $timeouts_per_period = $_POST['timeouts_per_period'];
    $time_limit = isset($_POST['time_limit']) ? 1 : 0;
    $point_cap = $_POST['point_cap'];
    $max_fouls = $_POST['max_fouls']; // Add the missing semicolon here

    $query = "
    INSERT INTO game_scoring_rules 
    (game_id, department_id, school_id, scoring_unit, score_increment_options, period_type, number_of_periods, duration_per_period, timeouts_per_period, time_limit, point_cap, max_fouls)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        scoring_unit = VALUES(scoring_unit),
        score_increment_options = VALUES(score_increment_options),
        period_type = VALUES(period_type),
        number_of_periods = VALUES(number_of_periods),
        duration_per_period = VALUES(duration_per_period),
        timeouts_per_period = VALUES(timeouts_per_period),
        time_limit = VALUES(time_limit),
        point_cap = VALUES(point_cap),
        max_fouls = VALUES(max_fouls)";

    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "iiisssiiiiii",
        $assigned_game_id,
        $assigned_department_id,
        $assigned_school_id,
        $scoring_unit,
        $score_increment_options,
        $period_type,
        $number_of_periods,
        $duration_per_period,
        $timeouts_per_period,
        $time_limit,
        $point_cap,
        $max_fouls
    );

    if ($stmt->execute()) {
        // Log user action before returning
        $description = "Updated the game scoring rules for $game_name under the $department_name department.";
        logUserAction($conn, $_SESSION['user_id'], 'Game Rules', 'CREATE/UPDATE', null, $description);
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Scoring rules saved successfully.'];
    } else {
        error_log("Error saving scoring rules: " . $stmt->error); // Debug SQL error
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to save scoring rules. Please try again.'];
    }
    $stmt->close();
}

// Retrieve existing scoring rules
$query = "SELECT * FROM game_scoring_rules WHERE game_id = ? AND department_id = ? AND school_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $assigned_game_id, $assigned_department_id, $assigned_school_id);
$stmt->execute();
$scoring_rules = $stmt->get_result()->fetch_assoc();
$stmt->close(); // Close this statement

include '../navbar/navbar.php';

// Fetch existing game stats
$existing_stats_query = "SELECT * FROM game_stats_config WHERE game_id = ?";
$existing_stats_stmt = $conn->prepare($existing_stats_query);
$existing_stats_stmt->bind_param("i", $assigned_game_id);
$existing_stats_stmt->execute();
$existing_stats_result = $existing_stats_stmt->get_result();
// No need to close here as it will be used in the display section


$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Rules</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script> <!-- Bootstrap JS -->
    <link rel="stylesheet" href="../styles/committee.css">
    <link rel="stylesheet" href="../styles/brackets.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <nav>
        <?php
        $current_page = 'gamerules';

        include '../committee/csidebar.php';
        ?>
    </nav>

    <script>
        <?php
        if (isset($_SESSION['message'])) {
            // Debug line
            error_log('Displaying message: ' . print_r($_SESSION['message'], true));
        ?>
            Swal.fire({
                icon: '<?php echo $_SESSION['message']['type'] ?? 'info'; ?>',
                title: '<?php echo htmlspecialchars($_SESSION['message']['text'] ?? 'No message'); ?>',
                showConfirmButton: true
            });
        <?php
            // Unset the message after displaying
            unset($_SESSION['message']);
        } ?>
    </script>


    <div class="container-fluid">
        <div class="main">
            <h4>Game Configuration</h4>
            <div class="row">
                <div class="col-md-6">
                    <div class="card mt-4">
                        <div class="card-header bg-primary text-white">
                            <h4>Scoring Rules Configuration</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Scoring Unit</label>
                                        <input type="text" name="scoring_unit" class="form-control" value="<?php echo htmlspecialchars($scoring_rules['scoring_unit'] ?? ''); ?>" placeholder="Enter scoring unit">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Score Increment Options</label>
                                        <input type="text" name="score_increment_options" class="form-control" value="<?php echo htmlspecialchars($scoring_rules['score_increment_options'] ?? ''); ?>" placeholder="Enter score increment options">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Period Type</label>
                                        <input type="text" name="period_type" class="form-control" value="<?php echo htmlspecialchars($scoring_rules['period_type'] ?? ''); ?>" placeholder="Enter period type">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Number of Periods</label>
                                        <input type="number" name="number_of_periods" class="form-control" value="<?php echo htmlspecialchars($scoring_rules['number_of_periods'] ?? ''); ?>" placeholder="Enter number of periods">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Duration per Period (minutes)</label>
                                        <input type="number" name="duration_per_period" class="form-control" value="<?php echo htmlspecialchars($scoring_rules['duration_per_period'] ?? ''); ?>" placeholder="Enter duration">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Timeouts per Period</label>
                                        <input type="number" name="timeouts_per_period" class="form-control" value="<?php echo htmlspecialchars($scoring_rules['timeouts_per_period'] ?? ''); ?>" placeholder="Enter timeouts">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Point Cap</label>
                                        <input type="number" name="point_cap" class="form-control" value="<?php echo htmlspecialchars($scoring_rules['point_cap'] ?? ''); ?>" placeholder="Enter point cap">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Max Fouls</label>
                                        <input type="number" name="max_fouls" class="form-control" value="<?php echo htmlspecialchars($scoring_rules['max_fouls'] ?? ''); ?>" placeholder="Enter max fouls">
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="time_limit" id="time_limit" <?php echo (!empty($scoring_rules['time_limit'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="time_limit">
                                                Enable Time Limit
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Save Scoring Rules</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card mb-4 mt-4">
                        <div class="card-header bg-primary text-white">
                            <h4>Game Stats Configuration</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="game_stats.php">
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label">Stat Name *</label>
                                        <input type="text" name="stat_name" class="form-control" required
                                            placeholder="e.g., Scores, Fouls, Assists, Rebounds">
                                        <!-- Add hidden inputs to pass game context -->
                                        <input type="hidden" name="game_id" value="<?php echo $assigned_game_id; ?>">
                                        <input type="hidden" name="department_id" value="<?php echo $assigned_department_id; ?>">
                                        <input type="hidden" name="school_id" value="<?php echo $assigned_school_id; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3 align-self-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-plus-circle"></i> Add Stat
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>


                    <!-- Existing Game Stats Display -->
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h4>Existing Game Stats</h4>
                        </div>
                        <div class="card-body">
                            <?php
                            if ($existing_stats_result->num_rows > 0) {
                                echo '<table class="table table-striped">';
                                echo '<thead><tr>
                    <th>Stat Name</th>
                    <th>Action</th>
                  </tr></thead><tbody>';

                                while ($stat = $existing_stats_result->fetch_assoc()) {
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($stat['stat_name']) . '</td>';
                                    echo '<td>
                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteStat(' . $stat['config_id'] . ')">Delete</button>
                      </td>';
                                    echo '</tr>';
                                }

                                echo '</tbody></table>';
                            } else {
                                echo '<div class="alert alert-info">No stats configured for this game yet.</div>';
                            }
                            ?>
                        </div>
                    </div>


                </div>
            </div>
        </div>
        <script>
            function deleteStat(statId) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'game_stats.php';

                        const statInput = document.createElement('input');
                        statInput.type = 'hidden';
                        statInput.name = 'stat_id';
                        statInput.value = statId;

                        const deleteInput = document.createElement('input');
                        deleteInput.type = 'hidden';
                        deleteInput.name = 'delete_stat';
                        deleteInput.value = '1';

                        form.appendChild(statInput);
                        form.appendChild(deleteInput);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }
        </script>
</body>

</html>