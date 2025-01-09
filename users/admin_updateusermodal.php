<?php
// Fetch all schools excluding id 0
$schools_query = "SELECT school_id, school_name FROM schools WHERE school_id != 0 ORDER BY school_name";
$schools_result = mysqli_query($conn, $schools_query);
$schools = mysqli_fetch_all($schools_result, MYSQLI_ASSOC);
?>

<!-- Update User Modal -->
<div class="modal fade" id="updateUserModal" tabindex="-1" aria-labelledby="updateUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateUserModalLabel">Update User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="updateUserForm" method="POST" action="update_user.php">
                    <input type="hidden" id="update_user_id" name="user_id">
                    
                    <div class="row g-3">
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
                            <input type="text" class="form-control" id="update_middleinitial" name="middleinitial" maxlength="1">
                        </div>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <label for="update_age" class="form-label">Age</label>
                            <input type="number" class="form-control" id="update_age" name="age" min="18" required>
                        </div>
                        <div class="col-md-4">
                            <label for="update_gender" class="form-label">Gender</label>
                            <select class="form-select" id="update_gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="update_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="update_email" name="email" required>
                        </div>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <label for="update_role" class="form-label">Role</label>
                            <select class="form-select" id="update_role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="School Admin">School Admin</option>
                                <option value="Department Admin">Department Admin</option>
                                <option value="Committee">Committee</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="update_school" class="form-label">School</label>
                            <select class="form-select" id="update_school" name="school_id" required>
                                <option value="">Select School</option>
                                <?php foreach ($schools as $school): ?>
                                    <option value="<?php echo $school['school_id']; ?>">
                                        <?php echo htmlspecialchars($school['school_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4" id="update_departmentDiv">
                            <label for="update_department" class="form-label">Department</label>
                            <select class="form-select" id="update_department" name="department">
                                <option value="">Select School First</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mt-2" id="update_gamesDiv" style="display: none;">
                        <div class="col-md-12">
                            <label for="update_game" class="form-label">Assigned Game</label>
                            <select class="form-select" id="update_game" name="game_id">
                                <option value="">Select School First</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="updateUserForm" class="btn btn-primary">Update User</button>
            </div>
        </div>
    </div>
</div>

<script>
// Function to load departments for update form
function loadDepartmentsForUpdate(schoolId, selectedDepartment = '') {
    const departmentSelect = document.getElementById('update_department');
    departmentSelect.innerHTML = '<option value="">Loading departments...</option>';
    departmentSelect.disabled = true;

    fetch(`get_departments.php?school_id=${schoolId}`)
        .then(response => response.json())
        .then(departments => {
            departmentSelect.innerHTML = '<option value="">Select Department</option>';
            if (departments.length > 0) {
                departments.forEach(dept => {
                    const option = new Option(dept.department_name, dept.id);
                    if (dept.id == selectedDepartment) {
                        option.selected = true;
                    }
                    departmentSelect.add(option);
                });
                departmentSelect.disabled = false;
            } else {
                departmentSelect.innerHTML = '<option value="">No departments found</option>';
                departmentSelect.disabled = true;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            departmentSelect.innerHTML = '<option value="">Error loading departments</option>';
            departmentSelect.disabled = true;
        });
}

function loadGamesForUpdate(schoolId, selectedGame = '') {
    const gameSelect = document.getElementById('update_game');
    gameSelect.innerHTML = '<option value="">Loading games...</option>';
    gameSelect.disabled = true;

    // Fetch games from the server
    fetch(`get_games.php?school_id=${schoolId}`)
        .then(response => response.json())
        .then(games => {
            // Clear the dropdown and add a default option
            gameSelect.innerHTML = '<option value="">Select Game</option>';
            if (games.length > 0) {
                // Loop through the games and create option elements
                games.forEach(game => {
                    const option = new Option(game.game_name, game.game_id);
                    // Check if this game is the one currently selected for update
                    if (game.game_id == selectedGame) {
                        option.selected = true; // Mark as selected if it matches
                    }
                    gameSelect.add(option); // Add the option to the dropdown
                });
                gameSelect.disabled = false; // Enable the dropdown
            } else {
                // If no games are found, show a message
                gameSelect.innerHTML = '<option value="">No games found</option>';
                gameSelect.disabled = true; // Keep the dropdown disabled
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Handle errors by showing an error message
            gameSelect.innerHTML = '<option value="">Error loading games</option>';
            gameSelect.disabled = true; // Keep the dropdown disabled
        });
}
// Function to handle role selection in update form
function handleUpdateRoleSelection(role) {
    const departmentDiv = document.getElementById('update_departmentDiv');
    const gamesDiv = document.getElementById('update_gamesDiv');
    const departmentSelect = document.getElementById('update_department');
    const gameSelect = document.getElementById('update_game');

    // Reset required attributes
    departmentSelect.required = false;
    gameSelect.required = false;

    // First hide all optional divs
    departmentDiv.style.display = 'none';
    gamesDiv.style.display = 'none';

    switch(role) {
        case 'School Admin':
            // School Admin doesn't need department or game
            break;
        case 'Department Admin':
            departmentDiv.style.display = 'block';
            departmentSelect.required = true;
            break;
        case 'Committee':
            departmentDiv.style.display = 'block';
            gamesDiv.style.display = 'block';
            departmentSelect.required = true;
            gameSelect.required = true;
            break;
    }
}

// Function to open update modal
function openUpdateModal(userId, firstname, lastname, middleinitial, age, gender, email, role, schoolId, departmentId, gameId) {
    // Set values in the form
    document.getElementById('update_user_id').value = userId;
    document.getElementById('update_firstname').value = firstname;
    document.getElementById('update_lastname').value = lastname;
    document.getElementById('update_middleinitial').value = middleinitial;
    document.getElementById('update_age').value = age;
    document.getElementById('update_gender').value = gender;
    document.getElementById('update_email').value = email;
    document.getElementById('update_role').value = role;
    document.getElementById('update_school').value = schoolId;

    // Handle role-specific fields
    handleUpdateRoleSelection(role);

    // Load departments and games if needed
    if (schoolId) {
        if (role === 'Department Admin' || role === 'Committee') {
            loadDepartmentsForUpdate(schoolId, departmentId);
        }
        if (role === 'Committee') {
            loadGamesForUpdate(schoolId, gameId);
        }
    }

    // Show the modal
    $('#updateUserModal').modal('show');
}

// Add event listeners for the update form
document.getElementById('update_school').addEventListener('change', function() {
    const selectedSchoolId = this.value;
    const currentRole = document.getElementById('update_role').value;
    
    if (selectedSchoolId) {
        if (currentRole === 'School Admin') {
            // Don't load departments for School Admin
            loadGamesForUpdate(selectedSchoolId);
        } else if (currentRole === 'Department Admin') {
            loadDepartmentsForUpdate(selectedSchoolId);
        } else if (currentRole === 'Committee') {
            loadDepartmentsForUpdate(selectedSchoolId);
            loadGamesForUpdate(selectedSchoolId);
        }
    } else {
        const departmentSelect = document.getElementById('update_department');
        const gameSelect = document.getElementById('update_game');
        departmentSelect.innerHTML = '<option value="">Select School First</option>';
        gameSelect.innerHTML = '<option value="">Select School First</option>';
        departmentSelect.disabled = true;
        gameSelect.disabled = true;
    }
});

document.getElementById('update_role').addEventListener('change', function() {
    handleUpdateRoleSelection(this.value);
    
    // Reset and reload dropdowns based on role
    const schoolSelect = document.getElementById('update_school');
    if (schoolSelect.value) {
        schoolSelect.dispatchEvent(new Event('change'));
    }
});

// Form validation and submission
document.getElementById('updateUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Age validation
    const age = parseInt(document.getElementById('update_age').value);
    if (age < 18) {
        alert('Age must be 18 or older!');
        return;
    }

    // Email validation
    const email = document.getElementById('update_email').value;
    if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
        alert('Please enter a valid email address!');
        return;
    }

    // Role-specific validation
    const role = document.getElementById('update_role').value;
    const department = document.getElementById('update_department').value;
    const game = document.getElementById('update_game').value;

    if (role === 'Department Admin' && !department) {
        alert('Please select a department for Department Admin!');
        return;
    }

    if (role === 'Committee' && (!department || !game)) {
        alert('Please select both department and game for Committee member!');
        return;
    }

    // Submit form via AJAX
    const formData = new FormData(this);
    
    fetch('admin_update_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message,
                showConfirmButton: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Close modal and refresh page
                    $('#updateUserModal').modal('hide');
                    location.reload();
                }
            });
        } else {
            // Show error message
            Swal.fire({
                icon: data.status === 'warning' ? 'warning' : 'error',
                title: data.status === 'warning' ? 'Warning' : 'Error!',
                text: data.message
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'An error occurred while processing your request.'
        });
    });
});
</script>
