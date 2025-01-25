<div class="modal fade" id="addTeamModal" tabindex="-1" aria-labelledby="addTeamModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="addTeamModalLabel">Add Team</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- Modal Body -->
            <div class="modal-body">
                <form id="addTeamForm">
                    <div class="mb-3">
                        <label for="gscDropdown" class="form-label">Grade/Strand/Section:</label>
                        <select class="form-select" id="gscDropdown" name="grade_section_course_id" required>
                            <option value="" selected disabled>Select Grade/Strand/Section</option>
                            <!-- Options will be dynamically loaded -->
                        </select>
                    </div>
                    <!-- Team Name -->
                    <div class="mb-3">
                        <label for="teamName" class="form-label">Team Name:</label>
                        <input type="text" class="form-control" id="teamName" name="team_name" placeholder="Enter team name" required>
                    </div>
                </form>
            </div>
            <!-- Modal Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveTeamBtn">Save Team</button>
            </div>
        </div>
    </div>
</div>
