<!-- Update Committee Modal -->
<div class="modal fade" id="updateCommitteeModal" tabindex="-1" aria-labelledby="updateCommitteeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateCommitteeModalLabel">Update Committee Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="updateCommitteeForm" method="POST">
                    <input type="hidden" id="update_user_id" name="user_id">
                    <div class="row g-3">
                        <!-- Name Fields -->
                        <div class="col-md-4">
                            <label for="update_firstname" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="update_firstname" name="firstname" required>
                        </div>
                        <div class="col-md-4">
                            <label for="update_lastname" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="update_lastname" name="lastname" required>
                        </div>
                        <div class="col-md-4">
                            <label for="update_middleinitial" class="form-label">Middle Initial</label>
                            <input type="text" class="form-control" id="update_middleinitial" name="middleinitial" maxlength="2">
                        </div>
                    </div>
                    <div class="row g-3 mt-3">
                        <div class="col-md-6">
                            <label for="update_age" class="form-label">Age</label>
                            <input type="number" class="form-control" id="update_age" name="age" required>
                        </div>
                        <div class="col-md-6">
                            <label for="update_gender" class="form-label">Gender</label>
                            <select class="form-select" id="update_gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mt-3">
                        <div class="col-md-12">
                            <label for="update_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="update_email" name="email" required>
                        </div>
                    </div>
                    <div class="row g-3 mt-3">
                        <div class="col-md-4">
                            <label for="update_role" class="form-label">Role</label>
                            <select class="form-select" id="update_role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="Department Admin">Department Admin</option>
                                <option value="Committee">Committee</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="update_assignGameDiv" style="display: none;">
                            <label for="update_assign_game" class="form-label">Assign Game</label>
                            <select class="form-select" id="update_assign_game" name="assign_game[]" multiple>
                                <option value="">Select Game</option>
                                <?php foreach ($games as $game): ?>
                                    <option value="<?= htmlspecialchars($game['game_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= htmlspecialchars($game['game_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <!-- <div id="updateSelectedGamesContainer" class="mt-2 d-flex flex-wrap gap-2"></div> -->

                        </div>
                        <div class="col-md-4">
                            <label for="update_dept_level" class="form-label">Department</label>
                            <select class="form-select" id="update_dept_level" name="assign_department[]" multiple required>
                                <!-- <div id="updateSelectedDeptsContainer" class="mt-2 d-flex flex-wrap gap-2"></div> -->

                                <option value="">Select Department</option>
                                <?php
                                $query = "SELECT id, department_name FROM departments WHERE school_id = ? AND is_archived = 0";
                                $stmt = mysqli_prepare($conn, $query);
                                mysqli_stmt_bind_param($stmt, "i", $school_id);
                                mysqli_stmt_execute($stmt);
                                $result = mysqli_stmt_get_result($stmt);
                                if ($result) {
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo '<option value="' . htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') . '">'
                                            . htmlspecialchars($row['department_name'], ENT_QUOTES, 'UTF-8') . '</option>';
                                    }
                                } else {
                                    echo '<option value="">Error loading departments</option>';
                                }
                                ?>
                            </select>
                            <!-- <div id="updateSelectedDeptsContainer" class="mt-2 d-flex flex-wrap gap-2"></div> -->

                        </div>
                    </div>
                    <button type="button" id="confirmUpdateBtn" class="btn btn-primary mt-3">Update User</button> <!-- Changed type to button to prevent immediate submission -->
                </form>
            </div>
        </div>
    </div>
</div>