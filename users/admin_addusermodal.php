<?php
// Fetch all schools excluding id 0
$schools_query = "SELECT school_id, school_name FROM schools WHERE school_id != 0 ORDER BY school_name";
$schools_result = mysqli_query($conn, $schools_query);
$schools = mysqli_fetch_all($schools_result, MYSQLI_ASSOC);
?>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addUserForm" method="POST" action="admin_adduser.php">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="firstname" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstname" name="firstname" required>
                        </div>
                        <div class="col-md-4">
                            <label for="lastname" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lastname" name="lastname" required>
                        </div>
                        <div class="col-md-4">
                            <label for="middleinitial" class="form-label">Middle Initial</label>
                            <input type="text" class="form-control" id="middleinitial" name="middleinitial" maxlength="1">
                        </div>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <label for="age" class="form-label">Age</label>
                            <input type="number" class="form-control" id="age" name="age" min="18" required>
                        </div>
                        <div class="col-md-4">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="School Admin">School Admin</option>
                                <option value="Department Admin">Department Admin</option>
                                <option value="Committee">Committee</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="school" class="form-label">School</label>
                            <select class="form-select" id="school" name="school_id" required>
                                <option value="">Select School</option>
                                <?php foreach ($schools as $school): ?>
                                    <option value="<?php echo $school['school_id']; ?>">
                                        <?php echo htmlspecialchars($school['school_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4" id="departmentDiv">
                            <label for="department" class="form-label">Department</label>
                            <select class="form-select" id="department" name="department">
                                <option value="">Select School First</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mt-2" id="gamesDiv" style="display: none;">
                        <div class="col-md-12">
                            <label for="game" class="form-label">Assigned Game</label>
                            <select class="form-select" id="game" name="game_id">
                                <option value="">Select School First</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="addUserForm" class="btn btn-primary">Add User</button>
            </div>
        </div>
    </div>
</div>

<script>
// Function to load departments based on selected school
function loadDepartmentsForAdd(schoolId) {
    const departmentSelect = document.getElementById('department');
    departmentSelect.innerHTML = '<option value="">Loading departments...</option>';
    departmentSelect.disabled = true;

    fetch(`get_departments.php?school_id=${schoolId}`)
        .then(response => response.json())
        .then(departments => {
            departmentSelect.innerHTML = '<option value="">Select Department</option>';
            if (departments.length > 0) {
                departments.forEach(dept => {
                    const option = new Option(dept.department_name, dept.id);
                    departmentSelect.add(option);
                });
                departmentSelect.disabled = false;
            } else {
                departmentSelect.innerHTML = '<option value="">No departments found</option>';
                departmentSelect.disabled = true;
            }
        })
        .catch(error => {
            console.error('Error loading departments:', error);
            departmentSelect.innerHTML = '<option value="">Error loading departments</option>';
            departmentSelect.disabled = true;
        });
}

// Function to load games based on selected school
function loadGamesForAdd(schoolId) {
    const gameSelect = document.getElementById('game');
    gameSelect.innerHTML = '<option value="">Loading games...</option>';
    gameSelect.disabled = true;

    fetch(`get_games.php?school_id=${schoolId}`)
        .then(response => response.json())
        .then(games => {
            gameSelect.innerHTML = '<option value="">Select Game</option>';
            if (games.length > 0) {
                games.forEach(game => {
                    const option = new Option(game.game_name, game.game_id);
                    gameSelect.add(option);
                });
                gameSelect.disabled = false;
            } else {
                gameSelect.innerHTML = '<option value="">No games found</option>';
                gameSelect.disabled = true;
            }
        })
        .catch(error => {
            console.error('Error loading games:', error);
            gameSelect.innerHTML = '<option value="">Error loading games</option>';
            gameSelect.disabled = true;
        });
}

// Function to handle role selection
function handleRoleSelection(role) {
    const departmentDiv = document.getElementById('departmentDiv');
    const gamesDiv = document.getElementById('gamesDiv');
    const departmentSelect = document.getElementById('department');
    const gameSelect = document.getElementById('game');

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

// Add event listener for school selection change
document.getElementById('school').addEventListener('change', function() {
    const selectedSchoolId = this.value;
    const currentRole = document.getElementById('role').value;
    
    if (selectedSchoolId) {
        if (currentRole === 'School Admin') {
            // Don't load departments for School Admin
            loadGamesForAdd(selectedSchoolId);
        } else if (currentRole === 'Department Admin') {
            loadDepartmentsForAdd(selectedSchoolId);
        } else if (currentRole === 'Committee') {
            loadDepartmentsForAdd(selectedSchoolId);
            loadGamesForAdd(selectedSchoolId);
        }
    } else {
        const departmentSelect = document.getElementById('department');
        const gameSelect = document.getElementById('game');
        departmentSelect.innerHTML = '<option value="">Select School First</option>';
        gameSelect.innerHTML = '<option value="">Select School First</option>';
        departmentSelect.disabled = true;
        gameSelect.disabled = true;
    }
});

// Add event listener for role selection change
document.getElementById('role').addEventListener('change', function() {
    handleRoleSelection(this.value);
    
    // Reset and reload dropdowns based on role
    const schoolSelect = document.getElementById('school');
    if (schoolSelect.value) {
        schoolSelect.dispatchEvent(new Event('change'));
    }
});

// Form validation and submission
document.getElementById('addUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Age validation
    const age = parseInt(document.getElementById('age').value);
    if (age < 18) {
        alert('Age must be 18 or older!');
        return;
    }

    // Email validation
    const email = document.getElementById('email').value;
    if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
        alert('Please enter a valid email address!');
        return;
    }

    // Role-specific validation
    const role = document.getElementById('role').value;
    const department = document.getElementById('department').value;
    const game = document.getElementById('game').value;

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
    
    fetch('admin_adduser.php', {
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
                    $('#addUserModal').modal('hide');
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
