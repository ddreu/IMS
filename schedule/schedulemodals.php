<style>
    .modal-backdrop {
        z-index: 1040 !important;
    }

    .modal {
        z-index: 1050 !important;
    }

    .modal-content {
        margin: 2px auto;
        z-index: 1100 !important;
    }

    .modal-xl {
        max-width: 1000px !important;
        /* Custom width for extra large modal */
    }

    .modal-header {
        background-color: #0d6efd;
        color: white;
    }

    .btn-close-white {
        filter: brightness(0) invert(1);
    }

    /* Hide validation icons in date and time inputs */
    input[type="date"]::-webkit-calendar-picker-indicator,
    input[type="time"]::-webkit-calendar-picker-indicator {
        opacity: 1 !important;
    }

    input[type="date"].is-invalid::-webkit-validation-marker,
    input[type="time"].is-invalid::-webkit-validation-marker,
    input[type="date"]:invalid::-webkit-validation-marker,
    input[type="time"]:invalid::-webkit-validation-marker {
        display: none;
    }

    /* Hide validation icons and maintain normal input appearance */
    input[type="date"],
    input[type="time"] {
        background-image: none !important;
    }

    input[type="date"]:invalid,
    input[type="time"]:invalid {
        border-color: #ced4da;
        background-image: none !important;
        padding-right: 0.75rem !important;
    }

    .form-control:invalid {
        background-image: none !important;
        border-color: #ced4da !important;
        padding-right: 0.75rem !important;
    }
</style>

<!-- Modal Structure -->
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="scheduleModalLabel">Create New Schedule</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="scheduleForm" method="POST" action="create_schedule.php">
                    <?php if ($role === 'School Admin'): ?>
                        <div class="mb-3">
                            <label for="department" class="form-label">Department:</label>
                            <select id="department" name="department_id" class="form-select" required>
                                <option value="">Select Department</option>
                                <?php
                                $departments_query = "SELECT id, department_name FROM departments WHERE school_id = ? AND is_archived = 0 ORDER BY department_name";
                                $stmt = $conn->prepare($departments_query);
                                $stmt->bind_param("i", $school_id);
                                $stmt->execute();
                                $departments_result = $stmt->get_result();
                                while ($department = $departments_result->fetch_assoc()) {
                                    $department_name = htmlspecialchars($department['department_name'], ENT_QUOTES, 'UTF-8');
                                    echo "<option value='{$department['id']}'>{$department_name}</option>";
                                }
                                $stmt->close();
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="game" class="form-label">Game:</label>
                            <select id="game" name="game_id" class="form-select" required>
                                <option value="">Select Game</option>
                                <?php
                                $games_query = "SELECT game_id, game_name FROM games WHERE school_id = ? AND is_archived = 0 ORDER BY game_name";
                                $stmt = $conn->prepare($games_query);
                                $stmt->bind_param("i", $school_id);
                                $stmt->execute();
                                $games_result = $stmt->get_result();
                                while ($game = $games_result->fetch_assoc()) {
                                    $game_name = htmlspecialchars($game['game_name'], ENT_QUOTES, 'UTF-8');
                                    echo "<option value='{$game['game_id']}'>{$game_name}</option>";
                                }
                                $stmt->close();
                                ?>
                            </select>
                        </div>
                    <?php elseif ($role === 'Department Admin'): ?>
                        <div class="mb-3">
                            <label for="department" class="form-label">Department:</label>
                            <select id="department" name="department_id" class="form-select" disabled>
                                <option value="<?php echo htmlspecialchars($user_department_id, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($user_department_name, ENT_QUOTES, 'UTF-8'); ?></option>
                            </select>
                            <input type="hidden" name="department_id" value="<?php echo htmlspecialchars($user_department_id, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="game" class="form-label">Game:</label>
                            <select id="game" name="game_id" class="form-select" required>
                                <option value="">Select Game</option>
                                <?php
                                $games_query = "SELECT game_id, game_name FROM games WHERE school_id = ? AND is_archived = 0 ORDER BY game_name";
                                $stmt = $conn->prepare($games_query);
                                $stmt->bind_param("i", $school_id);
                                $stmt->execute();
                                $games_result = $stmt->get_result();
                                while ($game = $games_result->fetch_assoc()) {
                                    $game_name = htmlspecialchars($game['game_name'], ENT_QUOTES, 'UTF-8');
                                    echo "<option value='{$game['game_id']}'>{$game_name}</option>";
                                }
                                $stmt->close();
                                ?>
                            </select>
                        </div>
                    <?php elseif ($role === 'Committee'): ?>
                        <div class="mb-3">
                            <label for="department" class="form-label">Department:</label>
                            <select id="department" name="department_id" class="form-select" disabled>
                                <option value="<?php echo htmlspecialchars($user_department_id, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($user_department_name, ENT_QUOTES, 'UTF-8'); ?></option>
                            </select>
                            <input type="hidden" name="department_id" value="<?php echo htmlspecialchars($user_department_id, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="game" class="form-label">Game:</label>
                            <select id="game" name="game_id" class="form-select" disabled>
                                <option value="<?php echo htmlspecialchars($user_game_id, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($user_game_name, ENT_QUOTES, 'UTF-8'); ?></option>
                            </select>
                            <input type="hidden" name="game_id" value="<?php echo htmlspecialchars($user_game_id, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    <?php endif; ?>

                    <div id="gradeLevelContainer" class="mb-3">
                        <label for="grade_level" class="form-label">Grade Level:</label>
                        <select id="grade_level" name="grade_level" class="form-select">
                            <option value="">Select Grade Level</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="match" class="form-label">Select Match:</label>
                        <select id="match" name="match_id" class="form-select" required>
                            <option value="">Select a Match</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-0">
                                <label for="schedule_date" class="form-label">Schedule Date:</label>
                                <input type="date" id="schedule_date" name="schedule_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-0">
                                <label for="schedule_time" class="form-label">Schedule Time:</label>
                                <input type="time" id="schedule_time" name="schedule_time" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div id="dateError" class="text-danger small mt-2 mb-3" style="display: none; text-align: center; font-weight: 500;"></div>
                    <div id="timeError" class="text-danger small mt-2 mb-3" style="display: none; text-align: center; font-weight: 500;"></div>

                    <div class="mb-3">
                        <label for="venue" class="form-label">Venue:</label>
                        <input type="text" id="venue" name="venue" class="form-control" placeholder="Venue" required>
                    </div>

                    <div class="modal-footer px-0 pb-0">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Create Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Event Details Modal -->
<div class="modal fade" id="eventDetailsModal" tabindex="-1" aria-labelledby="eventDetailsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="detail-title">Event Title</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Department:</strong> <span id="detail-department"></span></p>
                <p><strong>Game:</strong> <span id="detail-game"></span></p>
                <p><strong>Match Type:</strong> <span id="detail-match-type"></span></p>
                <p><strong>Teams:</strong> <span id="detail-teams"></span></p>
                <p><strong>Date:</strong> <span id="detail-date"></span></p>
                <p><strong>Time:</strong> <span id="detail-time"></span></p>
                <p><strong>Venue:</strong> <span id="detail-venue"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="editButton">Edit</button>
                <button type="button" class="btn btn-danger" id="cancelButton">Cancel Schedule</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<!-- Edit Schedule Modal -->
<div class="modal fade" id="editScheduleModal" tabindex="-1" aria-labelledby="editScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editScheduleModalLabel">Edit Schedule</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editScheduleForm">
                    <input type="hidden" id="edit_schedule_id" name="schedule_id">
                    <input type="hidden" id="edit_match_id" name="match_id">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-0">
                                <label for="edit_schedule_date" class="form-label">Schedule Date:</label>
                                <input type="date" id="edit_schedule_date" name="schedule_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-0">
                                <label for="edit_schedule_time" class="form-label">Schedule Time:</label>
                                <input type="time" id="edit_schedule_time" name="schedule_time" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div id="editDateError" class="text-danger small mt-2 mb-3" style="display: none; text-align: center; font-weight: 500;"></div>
                    <div id="editTimeError" class="text-danger small mt-2 mb-3" style="display: none; text-align: center; font-weight: 500;"></div>

                    <div class="mb-3">
                        <label for="edit_venue" class="form-label">Venue:</label>
                        <input type="text" id="edit_venue" name="venue" class="form-control" required>
                    </div>

                    <div class="modal-footer px-0 pb-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>