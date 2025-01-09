<!-- Modal for Adding Section -->
<div class="modal fade" id="addSectionModal" tabindex="-1" aria-labelledby="addSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSectionModalLabel">Add Section/Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addSectionForm" method="POST" action="adddepartment.php">
                    <?php
                    // Check if the logged-in user is a Department Admin
                    if ($role != 'Department Admin') {
                        // Department Dropdown visible only for School Admin or other roles
                    ?>
                        <div class="mb-3">
                            <label for="department" class="form-label">Department</label>
                            <select class="form-select" id="department" name="department" required>
                                <option value="" disabled selected>Select Department</option>
                                <?php
                                foreach ($departments as $dept) {
                                    echo "<option value='" . htmlspecialchars($dept['id']) . "' data-name='" . htmlspecialchars($dept['department_name']) . "'>" . htmlspecialchars($dept['department_name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    <?php } else { ?>
                        <!-- Optionally, you can hide the department field or provide a hidden field for Department Admin -->
                        <input type="hidden" name="department" value="<?php echo htmlspecialchars($department_id); ?>" />
                    <?php } ?>

                    <!-- Conditional Fields Container -->
                    <div id="conditionalFields"></div> <!-- Add this container -->

                    <button type="submit" class="btn btn-primary">Add</button>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Delete Section Modal -->
<div class="modal fade" id="deleteSectionModal" tabindex="-1" aria-labelledby="deleteSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteSectionModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this course/section?
                <input type="hidden" id="deleteCourseId" value="">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>