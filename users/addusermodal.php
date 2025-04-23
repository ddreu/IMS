<!-- Bootstrap Modal for Add Committee -->
<div class="modal fade" id="addCommitteeModal" tabindex="-1" role="dialog" aria-labelledby="addCommitteeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCommitteeModalLabel">Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="adduser.php" id="addUserForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="firstname" id="firstname" placeholder="First Name" required>
                                <label for="firstname">First Name</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="lastname" id="lastname" placeholder="Last Name" required>
                                <label for="lastname">Last Name</label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="middleinitial" id="middleinitial" placeholder="MI" maxlength="2">
                                <label for="middleinitial">Middle Initial</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-floating">
                                <input type="number" class="form-control" name="age" id="age" placeholder="Age" required>
                                <label for="age">Age</label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-floating">
                                <select class="form-select" name="gender" id="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                                <label for="gender">Gender</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-floating">
                                <input type="email" class="form-control" name="email" id="email" placeholder="Email" required>
                                <label for="email">Email</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-floating">
                            <select class="form-select" name="role" id="role" required>
                                <option value="">Select Role</option>
                                <?php if ($role === 'School Admin'): ?>
                                    <option value="Department Admin">Department Admin</option>
                                    <option value="Committee">Committee</option>
                                <?php elseif ($role === 'Department Admin'): ?>
                                    <option value="Committee">Committee</option>
                                <?php endif; ?>
                            </select>
                            <label for="role">Select Role</label>
                        </div>
                    </div>

                    <div class="mb-3" id="add_assignGameDiv" style="display: none;">
                        <label for="assign_game">Assign Game</label>
                        <div class="form-floating">
                            <select class="form-select" id="assign_game" name="assign_game[]" multiple required>
                                <option value="">Select Game</option>
                                <?php foreach ($games as $game): ?>
                                    <option value="<?= htmlspecialchars($game['game_id']) ?>">
                                        <?= htmlspecialchars($game['game_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="addSelectedGamesContainer" class="mt-2 d-flex flex-wrap gap-2"></div>

                            <!-- <label for="assign_game">Assign Game</label> -->
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="dept_level">Select Department</label>
                        <div class="form-floating">
                            <select class="form-select" name="assign_department[]" id="dept_level" multiple required>
                                <option value="">Select Department</option>
                                <?php
                                // Fetch departments based on user's role and school_id
                                $query = "SELECT id, department_name FROM departments WHERE school_id = ?";
                                $stmt = mysqli_prepare($conn, $query);
                                mysqli_stmt_bind_param($stmt, "i", $school_id);
                                mysqli_stmt_execute($stmt);
                                $result = mysqli_stmt_get_result($stmt);

                                if ($result) {
                                    if ($role === 'Department Admin') {
                                        // Department Admin can only add users to their own department
                                        $dept_query = "SELECT d.id, d.department_name 
                                                     FROM departments d 
                                                     JOIN users u ON d.id = u.department 
                                                     WHERE u.id = ? AND d.school_id = ?";
                                        $dept_stmt = mysqli_prepare($conn, $dept_query);
                                        mysqli_stmt_bind_param($dept_stmt, "ii", $user_id, $school_id);
                                        mysqli_stmt_execute($dept_stmt);
                                        $dept_result = mysqli_stmt_get_result($dept_stmt);

                                        if ($dept_row = mysqli_fetch_assoc($dept_result)) {
                                            echo '<option value="' . htmlspecialchars($dept_row['id'], ENT_QUOTES, 'UTF-8') . '">'
                                                . htmlspecialchars($dept_row['department_name'], ENT_QUOTES, 'UTF-8') . '</option>';
                                        }
                                    } else {
                                        // School Admin can add users to any department in their school
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            echo '<option value="' . htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') . '">'
                                                . htmlspecialchars($row['department_name'], ENT_QUOTES, 'UTF-8') . '</option>';
                                        }
                                    }
                                } else {
                                    echo '<option value="">Error loading departments</option>';
                                }
                                ?>
                            </select>
                            <div id="addSelectedDeptsContainer" class="mt-2 d-flex flex-wrap gap-2"></div>

                            <!-- <label for="dept_level">Select Department</label> -->
                        </div>
                    </div>


                    <button type="submit" class="btn btn-primary w-100">Add User</button>
                </form>
            </div>
        </div>
    </div>
</div>