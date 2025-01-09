<!-- Add Team Modal -->
<div class="modal fade" id="addTeamModal" tabindex="-1" aria-labelledby="addTeamModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="addteam.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTeamModalLabel">Add New Team</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="team_name" class="form-label">Team Name</label>
                        <input type="text" class="form-control" id="team_name" name="team_name" required>
                    </div>

                    <?php if ($department_level == 'College'): ?>
                        <div class="mb-3">
                            <label for="course" class="form-label">Course</label>
                            <select class="form-select" id="course" name="course" required>
                                <option value="" disabled selected>Select a course</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?php echo htmlspecialchars($team['course']); ?>"><?php echo htmlspecialchars($team['course']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <label for="grade_level" class="form-label">Grade Level</label>
                            <select class="form-select" id="grade_level" name="grade_level" required>
                                <option value="" disabled selected>Select a grade level</option>
                                <?php 
                                    // Define grade levels based on department level
                                    $grade_levels = [];
                                    if ($department_level == 'Elementary') {
                                        $grade_levels = ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];
                                    } elseif ($department_level == 'JHS') {
                                        $grade_levels = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'];
                                    } elseif ($department_level == 'SHS') {
                                        $grade_levels = ['Grade 11', 'Grade 12'];
                                    }
                                    // Display grade level options
                                    foreach ($grade_levels as $grade) {
                                        echo "<option value='" . htmlspecialchars($grade) . "'>" . htmlspecialchars($grade) . "</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="section" class="form-label">Section</label>
                            <select class="form-select" id="section" name="section" required>
                                <option value="" disabled selected>Select a section</option>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Hidden inputs for game_id and department_level -->
                    <input type="hidden" name="game_id" value="<?php echo $game_id; ?>">
                    <input type="hidden" name="department_level" value="<?php echo htmlspecialchars($department_level); ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Team</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // AJAX to fetch sections based on selected grade level
    document.getElementById('grade_level').addEventListener('change', function() {
        var gradeLevel = this.value;
        var sectionSelect = document.getElementById('section');
        
        // Clear previous options
        sectionSelect.innerHTML = "<option value='' disabled selected>Select a section</option>";
        
        // AJAX request to fetch sections
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'fetch_sections.php?grade_level=' + gradeLevel, true);
        xhr.onload = function() {
            if (this.status === 200) {
                var sections = JSON.parse(this.responseText);
                sections.forEach(function(section) {
                    var option = document.createElement('option');
                    option.value = section.section_name;
                    option.textContent = section.section_name;
                    sectionSelect.appendChild(option);
                });
            }
        };
        xhr.send();
    });
</script>




 <!-- View Players Modal -->
 <div class="modal fade" id="viewPlayersModal<?php echo $team['team_id']; ?>" tabindex="-1" aria-labelledby="viewPlayersModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="viewPlayersModalLabel">Players in <?php echo htmlspecialchars($team['team_name']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <!-- Fetch and display players for this team -->
                                <?php
                                // Fetch players for this team
                                $players_sql = "SELECT * FROM players WHERE team_id = ?";
                                $players_stmt = mysqli_prepare($conn, $players_sql);
                                mysqli_stmt_bind_param($players_stmt, "i", $team['team_id']);
                                mysqli_stmt_execute($players_stmt);
                                $players_result = mysqli_stmt_get_result($players_stmt);

                                if ($players_result->num_rows > 0): ?>
                                    <ul>
                                        <?php while ($player = mysqli_fetch_assoc($players_result)): ?>
                                            <li><?php echo htmlspecialchars($player['player_name']); ?> - Jersey: <?php echo htmlspecialchars($player['jersey_number']); ?></li>
                                        <?php endwhile; ?>
                                    </ul>
                                <?php else: ?>
                                    <p>No players found for this team.</p>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                 <!-- Edit Team Modal -->
 <div class="modal fade" id="editTeamModal<?php echo $team['team_id']; ?>" tabindex="-1" aria-labelledby="editTeamModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editTeamModalLabel">Edit Team</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <!-- Form for editing the team -->
                                <form action="update_team.php" method="POST">
                                    <input type="hidden" name="team_id" value="<?php echo $team['team_id']; ?>">
                                    <div class="mb-3">
                                        <label for="team_name" class="form-label">Team Name</label>
                                        <input type="text" class="form-control" name="team_name" value="<?php echo htmlspecialchars($team['team_name']); ?>" required>
                                    </div>
                                    <?php if ($department_level !== 'College'): ?>
                                        <div class="mb-3">
                                            <label for="year_level" class="form-label">Year Level</label>
                                            <input type="text" class="form-control" name="year_level" value="<?php echo htmlspecialchars($team['year_level']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="section" class="form-label">Section</label>
                                            <input type="text" class="form-control" name="section" value="<?php echo htmlspecialchars($team['section']); ?>" required>
                                        </div>
                                    <?php else: ?>
                                        <div class="mb-3">
                                            <label for="course" class="form-label">Course</label>
                                            <input type="text" class="form-control" name="course" value="<?php echo htmlspecialchars($team['course']); ?>" required>
                                        </div>
                                    <?php endif; ?>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>