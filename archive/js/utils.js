// document.addEventListener('DOMContentLoaded', () => {
//     const schoolDropdown = document.getElementById('school');
//     const tableDropdown = document.getElementById('table');
//     const archiveYearDropdown = document.getElementById('archive_year');
//     const departmentDropdown = document.getElementById('department');
//     const courseDropdown = document.getElementById('course');
//     const gameDropdown = document.getElementById('game');
//     const archiveTableContainer = document.getElementById('archiveTableContainer');

//     let manualTrigger = false;

//     function updateURL(param, value) {
//         const url = new URL(window.location.href);
//         if (value) {
//             url.searchParams.set(param, value);
//         } else {
//             url.searchParams.delete(param);
//         }
//         window.history.pushState({}, '', url);
//     }

//     // Commented out the AJAX fetch for loading the archive table
//     /*
//     function loadArchiveTable() {
//         const params = new URLSearchParams({
//             table: tableDropdown.value,
//             school_id: schoolDropdown.value,
//             year: archiveYearDropdown.value,
//             department_id: departmentDropdown.value,
//             course_id: courseDropdown.value,
//             game_id: gameDropdown.value
//         });

//         fetch(`fetch_archive_table.php?${params.toString()}`)
//             .then(response => response.text())
//             .then(data => {
//                 archiveTableContainer.innerHTML = data;
//             })
//             .catch(error => {
//                 console.error("Error loading archive table:", error);
//                 archiveTableContainer.innerHTML = "<p class='text-danger'>Error loading table data.</p>";
//             });
//     }
//     */

//     schoolDropdown.addEventListener('change', () => {
//         updateURL('school_id', schoolDropdown.value);
//         loadDependentDropdowns();
//     });

//     archiveYearDropdown.addEventListener('change', () => {
//         updateURL('year', archiveYearDropdown.value);
//     });

//     tableDropdown.addEventListener('change', () => {
//         updateURL('table', tableDropdown.value);
//     });

//     departmentDropdown.addEventListener('change', () => {
//         updateURL('department_id', departmentDropdown.value);
//         handleGradeLevel();
//     });

//     courseDropdown.addEventListener('change', () => {
//         updateURL('course_id', courseDropdown.value);
//     });

//     gameDropdown.addEventListener('change', () => {
//         updateURL('game_id', gameDropdown.value);
//     });

//     function loadDependentDropdowns() {
//         if (schoolDropdown.value) {
//             fetch(`dropdown/fetch_archive_years.php?school_id=${schoolDropdown.value}`)
//                 .then(response => response.text())
//                 .then(data => {
//                     archiveYearDropdown.innerHTML = data;
//                     archiveYearDropdown.disabled = false;
//                     const savedYear = new URLSearchParams(window.location.search).get('year');
//                     if (savedYear) {
//                         archiveYearDropdown.value = savedYear;
//                     }
//                     loadDepartment();
//                     loadGames();
//                 })
//                 .catch(error => console.error('Error loading archive years:', error));
//         } else {
//             resetDropdowns();
//         }
//     }

//     function resetDropdowns() {
//         departmentDropdown.innerHTML = '<option value="">Select Department</option>';
//         departmentDropdown.disabled = true;
//         courseDropdown.innerHTML = '<option value="">Select Grade</option>';
//         courseDropdown.disabled = true;
//         archiveYearDropdown.innerHTML = '<option value="">Select Year</option>';
//         archiveYearDropdown.disabled = true;
//         gameDropdown.innerHTML = '<option value="">Select Game</option>';
//         gameDropdown.disabled = true;
//     }

//     setTimeout(() => {
//         manualTrigger = true;
//     }, 1000);

//     if (schoolDropdown.value) schoolDropdown.dispatchEvent(new Event('change'));
//     if (departmentDropdown.value) departmentDropdown.dispatchEvent(new Event('change'));
//     if (courseDropdown.value) updateURL('course_id', courseDropdown.value);
//     if (archiveYearDropdown.value) updateURL('year', archiveYearDropdown.value);
// });

document.addEventListener('DOMContentLoaded', () => {
    const schoolDropdown = document.getElementById('school');
    const tableDropdown = document.getElementById('table');
    const archiveYearDropdown = document.getElementById('archive_year');
    const departmentDropdown = document.getElementById('department');
    const courseDropdown = document.getElementById('course');
    const gameDropdown = document.getElementById('game');
    const searchInput = document.getElementById('search');
    const archiveTableContainer = document.getElementById('archiveTableContainer');

    function updateURL(param, value) {
        const url = new URL(window.location.href);
        if (value) {
            url.searchParams.set(param, value);
        } else {
            url.searchParams.delete(param);
        }
        window.history.pushState({}, '', url);
    }

    function resetURLParams() {
        const url = new URL(window.location.href);
        url.search = ''; // Remove all parameters
        window.history.pushState({}, '', url);
    }

    // Reload page when `table` dropdown changes
    tableDropdown.addEventListener('change', () => {
        updateURL('table', tableDropdown.value);
        location.reload();
    });

    // Reset all URL params when school changes and reload
    schoolDropdown.addEventListener('change', () => {
        resetURLParams();
        updateURL('school_id', schoolDropdown.value);
        loadDependentDropdowns();
        location.reload();
    });

    // ✅ Fetch dependent dropdowns
    function loadDependentDropdowns() {
        if (schoolDropdown.value) {
            fetch(`dropdown/fetch_archive_years.php?school_id=${schoolDropdown.value}`)
                .then(response => response.text())
                .then(data => {
                    archiveYearDropdown.innerHTML = data;
                    archiveYearDropdown.disabled = false;
                    archiveYearDropdown.value = '';
                    loadDepartment();
                    loadGames();
                })
                .catch(error => console.error('Error loading archive years:', error));
        } else {
            resetDropdowns();
        }
    }

    function loadDepartment() {
        if (schoolDropdown.value) {
            fetch(`dropdown/fetch_departments.php?school_id=${schoolDropdown.value}`)
                .then(response => response.text())
                .then(data => {
                    departmentDropdown.innerHTML = data;
                    departmentDropdown.disabled = false;
                    departmentDropdown.value = '';
                    handleGradeLevel(); // ✅ Auto-trigger when department loads
                })
                .catch(error => console.error('Error loading departments:', error));
        }
    }

    function loadGames() {
        if (schoolDropdown.value) {
            fetch(`dropdown/fetch_games.php?school_id=${schoolDropdown.value}`)
                .then(response => response.text())
                .then(data => {
                    gameDropdown.innerHTML = data;
                    gameDropdown.disabled = false;
                    gameDropdown.value = '';
                })
                .catch(error => console.error('Error loading games:', error));
        }
    }

    function handleGradeLevel() {
        const selectedDepartment = departmentDropdown.options[departmentDropdown.selectedIndex]?.text || '';

        if (departmentDropdown.value) {
            // Check if selected department is "College"
            if (selectedDepartment.toLowerCase() === "college") {
                courseDropdown.innerHTML = '<option value="">N/A</option>';
                courseDropdown.disabled = true;
                return;
            }

            fetch(`dropdown/fetch_grade_level.php?department_id=${departmentDropdown.value}`)
                .then(response => response.json()) // Parse JSON response
                .then(data => {
                    if (data.error) {
                        console.error('Error:', data.error);
                        courseDropdown.innerHTML = '<option value="">Error loading grades</option>';
                        courseDropdown.disabled = true;
                        return;
                    }

                    courseDropdown.innerHTML = '<option value="">Select Grade</option>'; // Default option

                    data.forEach(grade => {
                        const option = document.createElement('option');
                        option.value = grade;
                        option.textContent = grade;
                        courseDropdown.appendChild(option);
                    });

                    courseDropdown.disabled = false;
                })
                .catch(error => console.error('Error loading grades:', error));
        } else {
            courseDropdown.innerHTML = '<option value="">Select Grade</option>';
            courseDropdown.disabled = true;
        }
    }

    function resetDropdowns() {
        archiveYearDropdown.innerHTML = '<option value="">Select Year</option>';
        archiveYearDropdown.disabled = true;
        departmentDropdown.innerHTML = '<option value="">Select Department</option>';
        departmentDropdown.disabled = true;
        courseDropdown.innerHTML = '<option value="">Select Grade</option>';
        courseDropdown.disabled = true;
        gameDropdown.innerHTML = '<option value="">Select Game</option>';
        gameDropdown.disabled = true;
    }

    // 🔍 Live filter table rows
    function filterTable() {
        const searchText = searchInput.value.toLowerCase();
        const selectedYear = archiveYearDropdown.value;
        const selectedDepartment = departmentDropdown.value;
        const selectedGrade = courseDropdown.value;
        const selectedGame = gameDropdown.value;

        const rows = document.querySelectorAll("#archiveTableContainer table tbody tr");

        rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            const rowYear = row.getAttribute('data-year');
            const rowDepartment = row.getAttribute('data-department');
            const rowGrade = row.getAttribute('data-grade');
            const rowGame = row.getAttribute('data-game');

            // Filtering logic: Show rows that match ALL selected filters
            const matchesSearch = rowText.includes(searchText);
            const matchesYear = !selectedYear || rowYear === selectedYear;
            const matchesDepartment = !selectedDepartment || rowDepartment === selectedDepartment;
            const matchesGrade = !selectedGrade || rowGrade === selectedGrade;
            const matchesGame = !selectedGame || rowGame === selectedGame;

            row.style.display = (matchesSearch && matchesYear && matchesDepartment && matchesGrade && matchesGame) ? "" : "none";
        });
    }


// 🔍 Live filter elements with data attributes
// function filterTable() {
//     const searchText = searchInput.value.toLowerCase();
//     const selectedYear = archiveYearDropdown.value;
//     const selectedDepartment = departmentDropdown.value;
//     const selectedGrade = courseDropdown.value;
//     const selectedGame = gameDropdown.value;

//     // Select all department cards, grade sections, and table rows
//     const elements = document.querySelectorAll("#departmentsTable .department-card, #departmentsTable .grade-section, #departmentsTable .archiveTableContainer tbody tr");

//     elements.forEach(element => {
//         const elementText = element.textContent.toLowerCase();
//         const elementYear = element.getAttribute('data-year') || "";  // Default to empty string if not found
//         const elementDepartment = element.getAttribute('data-department') || "";
//         const elementGrade = element.getAttribute('data-grade') || "";
//         const elementGame = element.getAttribute('data-game') || "";

//         // Filtering logic: Show elements that match ALL selected filters
//         const matchesSearch = elementText.includes(searchText);
//         const matchesYear = !selectedYear || elementYear === selectedYear;
//         const matchesDepartment = !selectedDepartment || elementDepartment === selectedDepartment;
//         const matchesGrade = !selectedGrade || elementGrade === selectedGrade;
//         const matchesGame = !selectedGame || elementGame === selectedGame;

//         // Show/Hide elements
//         if (matchesSearch && matchesYear && matchesDepartment && matchesGrade && matchesGame) {
//             element.style.display = ""; // Show the element
//         } else {
//             element.style.display = "none"; // Hide the element
//         }
//     });
// }



    // Search input event listener
    if (searchInput) {
        searchInput.addEventListener('input', filterTable);
    }

    // URL updates for non-reloading filters
    archiveYearDropdown.addEventListener('change', () => {
        updateURL('year', archiveYearDropdown.value);
        filterTable();
    });
    departmentDropdown.addEventListener('change', () => {
        updateURL('department_id', departmentDropdown.value);
        handleGradeLevel();
        filterTable();
        
    });
    courseDropdown.addEventListener('change', () => {
        updateURL('course_id', courseDropdown.value);
        filterTable();
    });
    gameDropdown.addEventListener('change', () => {
        updateURL('game_id', gameDropdown.value);
        filterTable();
    });

    // Auto-load dependent dropdowns if school is pre-selected
    if (schoolDropdown.value) loadDependentDropdowns();
});

/* teams */
function showTeams(gradeSectionCourseId) {
    const url = new URL(window.location.href);

    // Update or set the 'table' parameter to 'department-teams'
    url.searchParams.set('table', 'department-teams');

    // Set the 'grade_section_course_id' parameter
    url.searchParams.set('grade_section_course_id', gradeSectionCourseId);

    // Reload the page with the updated URL
    window.location.href = url.toString();
}

/* Teams click handler */
document.addEventListener("click", function(event) {
    const target = event.target.closest(".view-teams-btn");
    if (target) {
        // Get the grade_section_course_id from the data attribute
        const gradeSectionCourseId = target.getAttribute("data-grade-section-course-id");

        // Call showTeams with the correct parameter
        showTeams(gradeSectionCourseId);
    }
});



document.addEventListener("click", function(event) {
    if (event.target.classList.contains("archive-btn")) {
        const button = event.target;
        const id = button.getAttribute("data-id");
        const table = button.getAttribute("data-table");
        const operation = button.getAttribute("data-operation"); // New: archive or unarchive

        if (!id || !table || !operation) return;

        const isArchive = operation === "archive";

        // SweetAlert confirmation
        Swal.fire({
            title: isArchive ?
                "Are you sure you want to archive this?" : "Are you sure you want to unarchive this?",
            text: `You are about to ${
isArchive ? "archive" : "unarchive"
} this record from ${table}.`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: isArchive ? "#3085d6" : "#28a745",
            cancelButtonColor: "#d33",
            confirmButtonText: isArchive ? "Yes, archive it!" : "Yes, unarchive it!",
        }).then((result) => {
            if (result.isConfirmed) {
                // Send AJAX request
                fetch("archive_handler.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: new URLSearchParams({
                            id: id,
                            action: table,
                            operation: operation, // Include operation
                        }),
                    })
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.success) {
                            Swal.fire({
                                title: isArchive ? "Archived!" : "Unarchived!",
                                text: `The record has been ${
          isArchive ? "archived" : "unarchived"
        } successfully.`,
                                icon: "success",
                                toast: true,
                                position: "top-end",
                                timer: 2000,
                                showConfirmButton: false,
                            });

                            // OPTIONAL: Reload or update the table
                            setTimeout(() => {
                                location.reload();
                            }, 1200);
                        } else {
                            Swal.fire({
                                title: "Error!",
                                text: data.message ||
                                    `Failed to ${
            isArchive ? "archive" : "unarchive"
          } the record.`,
                                icon: "error",
                            });
                        }
                    })
                    .catch((error) => {
                        console.error("Error:", error);
                        Swal.fire({
                            title: "Error!",
                            text: `An error occurred while trying to ${
        isArchive ? "archive" : "unarchive"
      } the record.`,
                            icon: "error",
                        });
                    });
            }
        });
    }
});