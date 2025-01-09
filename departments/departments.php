<?php
session_start();
include_once '../connection/conn.php';
$conn = con(); // Ensure the connection is successful

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Retrieve user information from the session
$role = $_SESSION['role'];
$department_id = $_SESSION['department_id']; // Department ID from session
$school_id = $_SESSION['school_id'];
$department_name = $_SESSION['department_name'];

// Check if a department filter is selected from the sidebar (via URL parameter)
$selected_department_id = isset($_GET['selected_department_id']) ? intval($_GET['selected_department_id']) : null;



// Ensure we are dealing with valid school_id and department_id
if (!$school_id) {
    die('Error: School ID is missing from session');
}

// Adjust query based on the role and selected department
$sql = "
    SELECT gsc.id, gsc.grade_level, gsc.section_name, gsc.course_name, d.department_name, gsc.strand, d.id as department_id
    FROM grade_section_course AS gsc
    JOIN departments AS d ON gsc.department_id = d.id
    WHERE d.school_id = ?";

// Use selected_department_id for filtering if it's set
if ($selected_department_id) {
    $sql .= " AND d.id = ?";
    $dept_id_to_use = $selected_department_id;
}


// Order results by department, grade level, section, and course
$sql .= " ORDER BY d.department_name, gsc.grade_level, gsc.section_name, gsc.course_name";

// Prepare and execute the SQL statement
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die('Prepare failed: ' . $conn->error);
}

// Bind parameters
if (isset($dept_id_to_use)) {
    $stmt->bind_param("ii", $school_id, $dept_id_to_use);
} else {
    $stmt->bind_param("i", $school_id);
}

// Execute the query
$stmt->execute();
$result = $stmt->get_result();

$sections_by_department = [];

// Organize sections by department and grade
while ($row = $result->fetch_assoc()) {
    $department = $row['department_name'] ?? 'Unknown Department';
    $grade = $row['grade_level'];
    $name = ($department == 'College') ? $row['course_name'] : $row['section_name'];

    // Group sections by department and grade
    $sections_by_department[$department][$grade][] = [
        'id' => $row['id'],
        'name' => $name,
        'strand' => $row['strand'] ?? '' // Include strand for SHS
    ];
}

// Fetch departments for sidebar filtering if the user is a School Admin
if ($role === 'School Admin') {
    $sql = "SELECT id, department_name FROM departments WHERE school_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $departments_result = $stmt->get_result();

    $departments = [];
    while ($row = $departments_result->fetch_assoc()) {
        $departments[] = $row;
    }
} else {
    $departments = [];
}

include '../navbar/navbar.php';

// Clean up
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../styles/dashboard.css">
    <style>
        .section-header {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .department-info {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.75rem 1.25rem;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            display: inline-block;
        }

        .card {
            border: none;
            transition: all 0.3s ease;
            border-radius: 10px;
            margin-bottom: 1rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .card-header {
            padding: 1rem 1.25rem;
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,.125);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h5 {
            margin: 0;
            color: #4e73df;
            font-weight: 600;
        }

        .card-body {
            padding: 1.25rem;
        }

        .table {
            font-size: 0.95rem;
            margin-bottom: 0;
        }
        
        .table > :not(caption) > * > * {
            padding: 0.85rem;
        }

        .table th {
            font-weight: 600;
            color: #4e73df;
            border-top: none;
            background-color: #f8f9fc;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table td {
            vertical-align: middle;
            font-size: 0.95rem;
            color: black;
        }

        .btn-sm {
            padding: 0.4rem 1rem;
            font-size: 0.85rem;
            border-radius: 15px;
            margin: 0 0.2rem;
            border-width: 2px;
        }

        .btn-danger {
            background-color: transparent;
            border: 2px solid #e74a3b;
            color: black;
        }

        .btn-danger:hover {
            background-color: #e74a3b;
            color: white;
        }

        .btn-warning {
            background-color: transparent;
            border: 2px solid #f6c23e;
            color: black;
        }

        .btn-warning:hover {
            background-color: #f6c23e;
            color: white;
        }

        .btn-info {
            background-color: transparent;
            border: 2px solid #36b9cc;
            color: black;
        }

        .btn-info:hover {
            background-color: #36b9cc;
            color: white;
        }

        .btn-primary {
            background-color: transparent;
            border: 2px solid #4e73df;
            color: black;
            padding: 0.5rem 1.2rem;
            font-size: 0.9rem;
        }

        .btn-primary:hover {
            background-color: #4e73df;
            color: white;
        }

        .department-icon {
            background: rgba(78, 115, 223, 0.1);
            color: #4e73df;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
        }

        .modal-content {
            border: none;
            border-radius: 10px;
        }

        .modal-header {
            background-color: #4e73df;
            color: white;
            border-radius: 10px 10px 0 0;
            padding: 1rem 1.25rem;
        }

        .modal-header .btn-close {
            color: white;
            opacity: 1;
        }

        .modal-body {
            padding: 1.25rem;
        }

        .modal-footer {
            padding: 1rem;
            border-top: 1px solid #e3e6f0;
        }

        .form-control {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            border-radius: 10px;
            border: 1px solid #d1d3e2;
        }

        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        .form-label {
            font-weight: 500;
            color: #5a5c69;
            margin-bottom: 0.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #858796;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #4e73df;
            opacity: 0.5;
        }

        .empty-state h5 {
            color: #5a5c69;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #858796;
            margin-bottom: 0;
        }

        .status-badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 500;
            border-radius: 10px;
            text-transform: capitalize;
        }

        @media (max-width: 768px) {
            .btn-sm {
                padding: 0.4rem 0.8rem;
                margin: 0.2rem;
                display: inline-block;
            }

            .card-header {
                flex-direction: column;
                align-items: stretch;
            }

            .card-header .btn {
                margin-top: 1rem;
            }

            .table-responsive {
                margin: 0 -1.25rem;
            }
        }
        
        .editable {
            background-color: #fff3cd;
            padding: 5px;
            border: 1px solid #ffeeba;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php
        $current_page = 'departments';
        include '../department_admin/sidebar.php';
        ?>

        <div id="content">
            <div class="container mt-5">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="mb-0">Departments</h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSectionModal">
                        <i class="fas fa-plus"></i>
                        <?php echo ($role == 'School Admin') ? 'Add' : (($department_name == 'College') ? 'Add Course' : 'Add Section'); ?>
                    </button>
                </div>
            </div>

            <div class="container mt-4">
                <?php foreach ($sections_by_department as $department_name => $grades): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5><?php echo htmlspecialchars($department_name); ?> Department</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($grades as $grade_level => $items): ?>
                                <div class="mt-3">
                                    <h5><?php echo htmlspecialchars($department_name === 'College' ? $grade_level : $grade_level); ?></h5>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <?php if ($department_name === 'SHS'): ?>
                                                        <th>Strand</th>
                                                    <?php endif; ?>
                                                    <th><?php echo ($department_name === 'College') ? 'Courses' : 'Sections'; ?></th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($items as $item): ?>
                                                    <tr>
                                                        <?php if ($department_name === 'SHS'): ?>
                                                            <td><?php echo htmlspecialchars($item['strand']); ?></td>
                                                        <?php endif; ?>
                                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                        <td>
                                                            <a href="../teams/adminteams.php?grade_section_course_id=<?php echo $item['id']; ?>" class="btn btn-primary btn-sm">
                                                                <i class="fas fa-users"></i> View Teams
                                                            </a>
                                                            <button class="btn btn-info btn-sm edit-btn" 
                                                                    data-id="<?php echo $item['id']; ?>"
                                                                    data-department="<?php echo htmlspecialchars($department_name); ?>"
                                                                    data-grade="<?php echo htmlspecialchars($grade_level); ?>">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </button>
                                                            <button class="btn btn-success btn-sm save-btn" style="display: none;">
                                                                <i class="fas fa-save"></i> Save
                                                            </button>
                                                            <button class="btn btn-danger btn-sm delete-section" data-bs-toggle="modal" data-bs-target="#deleteSectionModal" data-id="<?php echo $item['id']; ?>">
                                                                <i class="fas fa-trash"></i> Delete
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php include 'departmentmodals.php'; ?>

            <?php if (isset($_SESSION['success_message'])): ?>
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: "<?php echo $_SESSION['success_message']; ?>",
                        showConfirmButton: true
                    });
                </script>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: "<?php echo $_SESSION['error_message']; ?>",
                        showConfirmButton: true
                    });
                </script>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const departmentSelect = document.getElementById('department');
                    const conditionalFieldsContainer = document.getElementById('conditionalFields');
                    const departmentName = "<?php echo $department_name; ?>";

                    // Initial fields generation based on the department_name
                    if (conditionalFieldsContainer) {
                        conditionalFieldsContainer.innerHTML = generateFieldsHTML(departmentName);
                    }

                    if (departmentSelect) {
                        departmentSelect.addEventListener('change', function() {
                            const selectedOption = departmentSelect.options[departmentSelect.selectedIndex];
                            const selectedDepartment = selectedOption.getAttribute('data-name');
                            conditionalFieldsContainer.innerHTML = ''; // Clear previous fields
                            conditionalFieldsContainer.innerHTML = generateFieldsHTML(selectedDepartment);
                        });
                    }

                    function generateFieldsHTML(departmentName) {
                        let fieldsHTML = '';

                        if (departmentName === 'College') {
                            fieldsHTML += `
                <div class="mb-3">
                    <label for="courseName" class="form-label">Course Name</label>
                    <input type="text" class="form-control" id="courseName" name="course_name" required>
                </div>`;
                        } else if (departmentName === 'SHS') {
                            fieldsHTML += `
                <div class="mb-3">
                    <label for="strand" class="form-label">Strand</label>
                    <input type="text" class="form-control" id="strand" name="strand" required>
                </div>
                <div class="mb-3">
                    <label for="gradeLevel" class="form-label">Grade Level</label>
                    <select class="form-select" id="gradeLevel" name="grade_level" required>
                        <option value="" disabled selected>Select Grade Level</option>
                        <option value="Grade 11">Grade 11</option>
                        <option value="Grade 12">Grade 12</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="sectionName" class="form-label">Section Name</label>
                    <input type="text" class="form-control" id="sectionName" name="section_name" required>
                </div>`;
                        } else if (departmentName === 'JHS' || departmentName === 'Elementary') {
                            const grades = departmentName === 'JHS' ? ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'] : ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];
                            fieldsHTML += `
                <div class="mb-3">
                    <label for="gradeLevel" class="form-label">Grade Level</label>
                    <select class="form-select" id="gradeLevel" name="grade_level" required>
                        <option value="" disabled selected>Select Grade Level</option>
                        ${grades.map(grade => `<option value="${grade}">${grade}</option>`).join('')}
                    </select>
                </div>
                <div class="mb-3">
                    <label for="sectionName" class="form-label">Section Name</label>
                    <input type="text" class="form-control" id="sectionName" name="section_name" required>
                </div>`;
                        }

                        return fieldsHTML;
                    }

                    // Delete functionality
                    const deleteButtons = document.querySelectorAll('.delete-section');
                    deleteButtons.forEach(button => {
                        button.addEventListener('click', function() {
                            const courseId = this.getAttribute('data-id');
                            document.getElementById('deleteCourseId').value = courseId;
                        });
                    });

                    // Confirm delete button click handler
                    document.getElementById('confirmDelete').addEventListener('click', function() {
                        const courseId = document.getElementById('deleteCourseId').value;
                        
                        if (!courseId) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Course/Section ID is missing.',
                                showConfirmButton: true
                            });
                            return;
                        }

                        // Create form data
                        const formData = new FormData();
                        formData.append('id', courseId);

                        // Send request using fetch instead of jQuery
                        fetch('deletesection_course.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: 'Course/Section deleted successfully.'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: data.message || 'Failed to delete course/section.'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'An error occurred while trying to delete the course/section.'
                            });
                        });
                    });

                    // Edit functionality
                    document.querySelectorAll('.edit-btn').forEach(button => {
                        button.addEventListener('click', function() {
                            const row = this.closest('tr');
                            const department = this.getAttribute('data-department');
                            
                            // Store original values for cancellation
                            row.dataset.originalValues = JSON.stringify({
                                name: row.querySelector('td:nth-child(' + (department === 'SHS' ? '2' : '1') + ')').textContent.trim(),
                                strand: department === 'SHS' ? row.querySelector('td:nth-child(1)').textContent.trim() : null
                            });
                            
                            // Hide edit button and show save button
                            this.style.display = 'none';
                            row.querySelector('.save-btn').style.display = 'inline-block';
                            
                            // Make cells editable based on department
                            if (department === 'College') {
                                const nameCell = row.querySelector('td:nth-child(1)');
                                nameCell.contentEditable = true;
                                nameCell.classList.add('editable');
                            } else if (department === 'SHS') {
                                const strandCell = row.querySelector('td:nth-child(1)');
                                const nameCell = row.querySelector('td:nth-child(2)');
                                strandCell.contentEditable = true;
                                nameCell.contentEditable = true;
                                strandCell.classList.add('editable');
                                nameCell.classList.add('editable');
                            } else {
                                const nameCell = row.querySelector('td:nth-child(1)');
                                nameCell.contentEditable = true;
                                nameCell.classList.add('editable');
                            }

                            // Add click outside listener
                            function handleClickOutside(event) {
                                const editableCells = row.querySelectorAll('.editable');
                                let clickedInside = false;
                                
                                editableCells.forEach(cell => {
                                    if (cell.contains(event.target)) {
                                        clickedInside = true;
                                    }
                                });
                                
                                // Also check if clicked on save button
                                const saveBtn = row.querySelector('.save-btn');
                                if (saveBtn && saveBtn.contains(event.target)) {
                                    clickedInside = true;
                                }
                                
                                if (!clickedInside) {
                                    cancelEdit(row);
                                    document.removeEventListener('click', handleClickOutside);
                                }
                            }
                            
                            // Delay adding the click outside listener to prevent immediate triggering
                            setTimeout(() => {
                                document.addEventListener('click', handleClickOutside);
                            }, 0);
                        });
                    });

                    // Function to cancel edit
                    function cancelEdit(row) {
                        const originalValues = JSON.parse(row.dataset.originalValues || '{}');
                        const department = row.querySelector('.edit-btn').getAttribute('data-department');
                        
                        // Restore original values
                        if (department === 'SHS') {
                            row.querySelector('td:nth-child(1)').textContent = originalValues.strand;
                            row.querySelector('td:nth-child(2)').textContent = originalValues.name;
                        } else {
                            row.querySelector('td:nth-child(' + (department === 'SHS' ? '2' : '1') + ')').textContent = originalValues.name;
                        }
                        
                        // Reset editable state
                        const editableCells = row.querySelectorAll('.editable');
                        editableCells.forEach(cell => {
                            cell.contentEditable = false;
                            cell.classList.remove('editable');
                        });
                        
                        // Reset buttons
                        row.querySelector('.edit-btn').style.display = 'inline-block';
                        row.querySelector('.save-btn').style.display = 'none';
                    }

                    // Save functionality
                    document.querySelectorAll('.save-btn').forEach(button => {
                        button.addEventListener('click', function() {
                            const row = this.closest('tr');
                            const editBtn = row.querySelector('.edit-btn');
                            const id = editBtn.getAttribute('data-id');
                            const department = editBtn.getAttribute('data-department');
                            const grade = editBtn.getAttribute('data-grade');
                            
                            let data = {
                                id: id,
                                department: department,
                                grade_level: grade
                            };

                            if (department === 'College') {
                                data.course_name = row.querySelector('td:nth-child(1)').textContent.trim();
                            } else if (department === 'SHS') {
                                data.strand = row.querySelector('td:nth-child(1)').textContent.trim();
                                data.section_name = row.querySelector('td:nth-child(2)').textContent.trim();
                            } else {
                                data.section_name = row.querySelector('td:nth-child(1)').textContent.trim();
                            }

                            Swal.fire({
                                title: 'Are you sure?',
                                text: "Do you want to save these changes?",
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#3085d6',
                                cancelButtonColor: '#d33',
                                confirmButtonText: 'Yes, save it!'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    fetch('update_section.php', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                        },
                                        body: JSON.stringify(data)
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'Updated!',
                                                text: 'The section has been updated successfully.',
                                                showConfirmButton: true
                                            }).then((result) => {
                                                location.reload();
                                            });
                                        } else {
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Error!',
                                                text: data.message || 'Failed to update the section.',
                                                showConfirmButton: true
                                            }).then(() => {
                                                // If error, cancel the edit and restore original values
                                                cancelEdit(row);
                                            });
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error:', error);
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error!',
                                            text: 'An error occurred while updating the section.',
                                            showConfirmButton: true
                                        }).then(() => {
                                            // If error, cancel the edit and restore original values
                                            cancelEdit(row);
                                        });
                                    });
                                } else {
                                    // If user clicks Cancel on the confirmation dialog
                                    cancelEdit(row);
                                }
                            });
                        });
                    });
                });
            </script>
        </div>
    </div>
</body>
</html>