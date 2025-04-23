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
                <form id="updateUserForm" method="POST">
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
                            <div id="updateSelectedDepartmentsContainer" class="mt-2 d-flex flex-wrap gap-2"></div>

                        </div>
                    </div>

                    <!-- <div class="row g-3 mt-2" id="update_gamesDiv" style="display: none;">
                        <div class="col-md-12">
                            <label for="update_game" class="form-label">Assigned Game</label>
                            <select class="form-select" id="update_game" name="game_id">
                                <option value="">Select School First</option>
                            </select>
                        </div>
                    </div> -->

                    <div class="row g-3 mt-2" id="update_gamesDiv" style="display: none;">
                        <div class="col-md-12">
                            <label for="update_game" class="form-label">Assigned Game</label>
                            <select class="form-select" id="update_game">
                                <option value="">Select a game to add</option>
                            </select>
                            <div id="updateSelectedGamesContainer" class="mt-2 d-flex flex-wrap gap-2"></div>
                        </div>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button id="updateUserSubmit" type="submit" form="updateUserForm" class="btn btn-primary">Update User</button>
            </div>
        </div>
    </div>
</div>

<script>
    let updateSelectedDepartmentIds = [];

    let updateSelectedGameIds = [];

    // Function to load departments for update form
    function loadDepartmentsForUpdate(schoolId, selectedDepartments = []) {
        const departmentSelect = document.getElementById('update_department');
        departmentSelect.innerHTML = '<option value="">Loading departments...</option>';
        departmentSelect.disabled = true;

        fetch(`get_departments.php?school_id=${schoolId}`)
            .then(response => response.json())
            .then(departments => {
                departmentSelect.innerHTML = '<option value="">Select Department</option>';

                departments.forEach(dept => {
                    const option = new Option(dept.department_name, dept.id);
                    departmentSelect.add(option);
                });

                departmentSelect.disabled = false;

                // 🔥 Wait until DOM has painted THEN call display
                requestAnimationFrame(() => {
                    updateSelectedDepartmentIds = selectedDepartments;
                    updateSelectedDepartmentsDisplayUpdate();
                });
            })

            .catch(error => {
                console.error('Error loading departments:', error);
                departmentSelect.innerHTML = '<option value="">Error loading departments</option>';
                departmentSelect.disabled = true;
            });
    }



    function updateSelectedDepartmentsDisplayUpdate() {
        const container = document.getElementById('updateSelectedDepartmentsContainer');
        container.innerHTML = "";

        document.querySelectorAll('input[name="department_ids[]"]').forEach(el => el.remove());

        updateSelectedDepartmentIds.forEach(id => {
            // ✅ Wait for the DOM to fully reflect the options
            const option = document.querySelector(`#update_department option[value="${id}"]`);
            const label = option ? option.textContent : `ID: ${id}`;

            const badge = document.createElement('span');
            badge.className = 'badge bg-secondary rounded-pill px-3 py-2 d-flex align-items-center';
            badge.innerHTML = `
            ${label}
            <button type="button" class="btn-close btn-close-white btn-sm ms-2" data-id="${id}" aria-label="Remove"></button>
        `;
            container.appendChild(badge);

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'department_ids[]';
            hiddenInput.value = id;
            document.getElementById('updateUserForm').appendChild(hiddenInput);
        });
    }




    // 🆕 Load games and restore selected
    function loadGamesForUpdate(schoolId) {
        const gameSelect = document.getElementById('update_game');
        gameSelect.innerHTML = '<option value="">Loading games...</option>';
        gameSelect.disabled = true;

        fetch(`get_games.php?school_id=${schoolId}`)
            .then(response => response.json())
            .then(games => {
                gameSelect.innerHTML = '<option value="">Select a game to add</option>';
                games.forEach(game => {
                    const option = new Option(game.game_name, game.game_id);
                    gameSelect.add(option);
                });
                gameSelect.disabled = false;
                updateGamesDisplay(); // render tags for selected games
            })
            .catch(error => {
                console.error('Error loading games:', error);
                gameSelect.innerHTML = '<option value="">Error loading games</option>';
                gameSelect.disabled = true;
            });
    }

    function handleUpdateRoleSelection(role) {
        const departmentDiv = document.getElementById('update_departmentDiv');
        const gamesDiv = document.getElementById('update_gamesDiv');
        const departmentSelect = document.getElementById('update_department');
        const gameSelect = document.getElementById('update_game');

        departmentSelect.required = false;
        gameSelect.required = false;

        departmentDiv.style.display = 'none';
        gamesDiv.style.display = 'none';

        if (role === 'Department Admin') {
            departmentDiv.style.display = 'block';
            // departmentSelect.required = true;
        } else if (role === 'Committee') {
            departmentDiv.style.display = 'block';
            gamesDiv.style.display = 'block';
            // departmentSelect.required = true;
            // gameSelect.required = true;
            gameSelect.required = false;
        }
    }

    function openUpdateModal(btn, userId, firstname, lastname, middleinitial, age, gender, email, role, schoolId, departmentIdCsv, gameIdsCsv) {
        const mainGameId = btn.getAttribute('data-main-game');


        document.getElementById('update_user_id').value = userId;
        document.getElementById('update_user_id').dataset.mainGame = mainGameId;
        document.getElementById('update_firstname').value = firstname;
        document.getElementById('update_lastname').value = lastname;
        document.getElementById('update_middleinitial').value = middleinitial;
        document.getElementById('update_age').value = age;
        document.getElementById('update_gender').value = gender;
        document.getElementById('update_email').value = email;
        document.getElementById('update_role').value = role;
        document.getElementById('update_school').value = schoolId;

        // updateSelectedGameIds = gameIdsCsv ? gameIdsCsv.split(',') : [];
        updateSelectedDepartmentIds = departmentIdCsv ? departmentIdCsv.split(',') : [];

        updateSelectedGameIds = [];

        if (gameIdsCsv && gameIdsCsv.trim() !== '') {
            updateSelectedGameIds = gameIdsCsv.split(',').filter(id => id.trim() !== '');
        }

        // Always include the main game ID if not already in the list
        if (mainGameId && !updateSelectedGameIds.includes(mainGameId)) {
            updateSelectedGameIds.unshift(mainGameId);
        }




        handleUpdateRoleSelection(role);

        // if (schoolId) {
        //     if (role === 'Department Admin' || role === 'Committee') {
        //         loadDepartmentsForUpdate(schoolId, departmentId);
        //     }
        //     if (role === 'Committee') {
        //         loadGamesForUpdate(schoolId);
        //     }
        //     if (role === 'Department Admin' || role === 'Committee') {
        //         loadDepartmentsForUpdate(schoolId, updateSelectedDepartmentIds);
        //     }
        // }

        if (schoolId) {
            if (role === 'Department Admin' || role === 'Committee') {
                loadDepartmentsForUpdate(schoolId, updateSelectedDepartmentIds);
            }
            if (role === 'Committee') {
                loadGamesForUpdate(schoolId);
            }
        }


        $('#updateUserModal').modal('show');
    }

    document.getElementById('update_school').addEventListener('change', function() {
        const selectedSchoolId = this.value;
        const currentRole = document.getElementById('update_role').value;

        if (selectedSchoolId) {
            if (currentRole === 'School Admin') return;
            if (currentRole === 'Department Admin') {
                loadDepartmentsForUpdate(selectedSchoolId);
            } else if (currentRole === 'Committee') {
                loadDepartmentsForUpdate(selectedSchoolId);
                loadGamesForUpdate(selectedSchoolId);
            }
        } else {
            document.getElementById('update_department').innerHTML = '<option value="">Select School First</option>';
            document.getElementById('update_game').innerHTML = '<option value="">Select School First</option>';
        }
    });

    // document.getElementById('update_department').addEventListener('change', function() {
    //     const selectedId = this.value;
    //     if (!selectedId || updateSelectedDepartmentIds.includes(selectedId)) return;

    //     updateSelectedDepartmentIds.push(selectedId);
    //     updateSelectedDepartmentsDisplayUpdate();
    //     this.value = "";
    // });

    document.getElementById('update_department').addEventListener('change', function() {
        const selectedId = this.value;
        const currentRole = document.getElementById('update_role').value;

        if (!selectedId) return;

        if (currentRole === 'Committee') {
            if (!updateSelectedDepartmentIds.includes(selectedId)) {
                updateSelectedDepartmentIds.push(selectedId);
            }
        } else if (currentRole === 'Department Admin') {
            updateSelectedDepartmentIds = [selectedId]; // only one
        }

        updateSelectedDepartmentsDisplayUpdate();
        this.value = "";
    });


    document.getElementById('updateSelectedDepartmentsContainer').addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-close')) {
            const idToRemove = e.target.getAttribute('data-id');
            updateSelectedDepartmentIds = updateSelectedDepartmentIds.filter(id => id !== idToRemove);
            updateSelectedDepartmentsDisplayUpdate();
        }
    });


    document.getElementById('update_role').addEventListener('change', function() {
        handleUpdateRoleSelection(this.value);
        const schoolSelect = document.getElementById('update_school');
        if (schoolSelect.value) {
            schoolSelect.dispatchEvent(new Event('change'));
        }
    });

    // 🆕 Game selection add
    document.getElementById('update_game').addEventListener('change', function() {
        const selectedGameId = this.value;
        if (!selectedGameId || updateSelectedGameIds.includes(selectedGameId)) return;

        updateSelectedGameIds.push(selectedGameId);
        updateGamesDisplay();
        this.value = '';
    });

    function updateDepartmentsDisplay() {
        const container = document.getElementById('updateSelectedDepartmentsContainer');
        container.innerHTML = '';

        // Clear existing hidden inputs
        document.querySelectorAll('input[name="department_ids[]"]').forEach(el => el.remove());

        updateSelectedDepartmentIds.forEach(id => {
            const label = document.querySelector(`#update_department option[value="${id}"]`)?.textContent || 'Department';

            const badge = document.createElement('span');
            badge.className = 'badge bg-secondary rounded-pill px-3 py-2 d-flex align-items-center';
            badge.innerHTML = `
            ${label}
            <button type="button" class="btn-close btn-close-white btn-sm ms-2" data-id="${id}" aria-label="Remove"></button>
        `;
            container.appendChild(badge);

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'department_ids[]';
            hiddenInput.value = id;
            document.getElementById('updateUserForm').appendChild(hiddenInput);
        });
    }

    document.getElementById('updateSelectedDepartmentsContainer').addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-close')) {
            const idToRemove = e.target.getAttribute('data-id');
            updateSelectedDepartmentIds = updateSelectedDepartmentIds.filter(id => id !== idToRemove);
            updateDepartmentsDisplay();
        }
    });


    // 🆕 Display selected games
    function updateGamesDisplay() {
        const container = document.getElementById('updateSelectedGamesContainer');
        container.innerHTML = '';

        // Remove previous hidden inputs
        document.querySelectorAll('input[name="game_ids[]"]').forEach(el => el.remove());

        updateSelectedGameIds.forEach(id => {
            const option = document.querySelector(`#update_game option[value="${id}"]`);
            const label = option ? option.textContent : 'Game';

            const badge = document.createElement('span');
            badge.className = 'badge bg-primary rounded-pill px-3 py-2 d-flex align-items-center';
            badge.innerHTML = `
                ${label}
                <button type="button" class="btn-close btn-close-white btn-sm ms-2" data-id="${id}" aria-label="Remove"></button>
            `;
            container.appendChild(badge);

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'game_ids[]';
            hiddenInput.value = id;
            document.getElementById('updateUserForm').appendChild(hiddenInput);
        });
    }

    // 🆕 Remove selected game
    document.getElementById('updateSelectedGamesContainer').addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-close')) {
            const idToRemove = e.target.getAttribute('data-id');
            updateSelectedGameIds = updateSelectedGameIds.filter(id => id !== idToRemove);
            updateGamesDisplay();
        }
    });

    document.getElementById('updateUserForm').addEventListener('submit', function(e) {
        e.preventDefault();

        // const updateBtn = document.querySelector('#updateUserForm button[type="submit"]');
        const updateBtn = document.getElementById('updateUserSubmit');

        updateBtn.disabled = true;
        updateBtn.innerHTML = 'Updating...';

        const age = parseInt(document.getElementById('update_age').value);
        if (age < 18) {
            alert('Age must be 18 or older!');
            updateBtn.disabled = false;
            updateBtn.innerHTML = 'Update User';
            return;
        }

        const email = document.getElementById('update_email').value;
        if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            alert('Please enter a valid email address!');
            updateBtn.disabled = false;
            updateBtn.innerHTML = 'Update User';
            return;
        }

        const role = document.getElementById('update_role').value;
        const department = document.getElementById('update_department').value;

        if (role === 'Department Admin') {
            if (updateSelectedDepartmentIds.length !== 1) {
                alert('Please select exactly one department for Department Admin!');
                updateBtn.disabled = false;
                updateBtn.innerHTML = 'Update User';
                return;
            }
        }

        if (role === 'Committee') {
            if (updateSelectedDepartmentIds.length === 0 || updateSelectedGameIds.length === 0) {
                alert('Please select both department and at least one game for Committee!');
                updateBtn.disabled = false;
                updateBtn.innerHTML = 'Update User';
                return;
            }
        }

        // if (role === 'Committee') {
        //     if (!department || updateSelectedGameIds.length === 0) {
        //         alert('Please select both department and at least one game for Committee!');
        //         updateBtn.disabled = false;
        //         updateBtn.innerHTML = 'Update User';
        //         return;
        //     }
        // }

        updateGamesDisplay(); // 🆙 update hidden inputs
        const formData = new FormData(this);

        fetch('admin_update_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        showConfirmButton: true
                    }).then(result => {
                        if (result.isConfirmed) {
                            $('#updateUserModal').modal('hide');
                            location.reload();
                        }
                    });
                } else {
                    updateBtn.disabled = false;
                    updateBtn.innerHTML = 'Update User';

                    Swal.fire({
                        icon: data.status === 'warning' ? 'warning' : 'error',
                        title: data.status === 'warning' ? 'Warning' : 'Error!',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                updateBtn.disabled = false;
                updateBtn.innerHTML = 'Update User';

                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while processing your request.'
                });
            });
    });
</script>