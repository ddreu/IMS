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
    const contentContainer = document.getElementById('archiveTableContent');

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
        url.search = '';
        window.history.pushState({}, '', url);
    }

    function loadArchivePage(extraParams = {}) {
        const table = tableDropdown.value;
        const year = archiveYearDropdown.value;

        if (!table || !year) {
            contentContainer.innerHTML = `<p class="text-muted">Select a table and year to view its content.</p>`;
            return;
        }

        let url = `archive-pages/${table}.php?year=${encodeURIComponent(year)}`;

        // Append extra query parameters
        Object.entries(extraParams).forEach(([key, val]) => {
            url += `&${encodeURIComponent(key)}=${encodeURIComponent(val)}`;
        });

        contentContainer.innerHTML = `<div class="text-muted py-3">Loading...</div>`;

        fetch(url)
            .then(res => {
                if (!res.ok) throw new Error("Failed to load content.");
                return res.text();
            })
            .then(html => {
                contentContainer.innerHTML = html;
                loadScript(`archive-page-js/${table}.js`);
                filterTable();
            })
            .catch(err => {
                contentContainer.innerHTML = `<p class="text-danger">Error: ${err.message}</p>`;
            });
    }

    function loadScript(src) {
        const existing = document.querySelector(`script[src="${src}"]`);
        if (existing) existing.remove();
        const script = document.createElement('script');
        script.src = src;
        script.defer = true;
        document.body.appendChild(script);
    }

    function loadDependentDropdowns() {
        if (!schoolDropdown.value) return resetDropdowns();

        fetch(`dropdown/fetch_archive_years.php?school_id=${schoolDropdown.value}`)
            .then(res => res.text())
            .then(data => {
                archiveYearDropdown.innerHTML = data;
                archiveYearDropdown.disabled = false;
                archiveYearDropdown.value = '';
                loadDepartment();
                loadGames();
            })
            .catch(err => console.error('Archive year fetch failed:', err));
    }

    function loadDepartment() {
        fetch(`dropdown/fetch_departments.php?school_id=${schoolDropdown.value}`)
            .then(res => res.text())
            .then(data => {
                departmentDropdown.innerHTML = data;
                departmentDropdown.disabled = false;
                departmentDropdown.value = '';
                handleGradeLevel();
            })
            .catch(err => console.error('Department fetch failed:', err));
    }

    function loadGames() {
        fetch(`dropdown/fetch_games.php?school_id=${schoolDropdown.value}`)
            .then(res => res.text())
            .then(data => {
                gameDropdown.innerHTML = data;
                gameDropdown.disabled = false;
                gameDropdown.value = '';
            })
            .catch(err => console.error('Games fetch failed:', err));
    }

    function handleGradeLevel() {
        const selectedText = departmentDropdown.options[departmentDropdown.selectedIndex]?.text?.toLowerCase() || '';

        if (selectedText === 'college') {
            courseDropdown.innerHTML = '<option value="">N/A</option>';
            courseDropdown.disabled = true;
            return;
        }

        fetch(`dropdown/fetch_grade_level.php?department_id=${departmentDropdown.value}`)
            .then(res => res.json())
            .then(data => {
                if (data.error) throw new Error(data.error);

                courseDropdown.innerHTML = '<option value="">Select Grade</option>';
                data.forEach(grade => {
                    const option = document.createElement('option');
                    option.value = grade;
                    option.textContent = grade;
                    courseDropdown.appendChild(option);
                });
                courseDropdown.disabled = false;
            })
            .catch(err => {
                console.error('Grade level fetch failed:', err);
                courseDropdown.innerHTML = '<option>Error loading grades</option>';
                courseDropdown.disabled = true;
            });
    }

    function resetDropdowns() {
        archiveYearDropdown.innerHTML = '<option value="">Select Year</option>';
        departmentDropdown.innerHTML = '<option value="">Select Department</option>';
        courseDropdown.innerHTML = '<option value="">Select Grade</option>';
        gameDropdown.innerHTML = '<option value="">Select Game</option>';

        archiveYearDropdown.disabled = true;
        departmentDropdown.disabled = true;
        courseDropdown.disabled = true;
        gameDropdown.disabled = true;
    }

    function filterTable() {
        const searchText = searchInput.value.toLowerCase();
        const year = archiveYearDropdown.value;
        const department = departmentDropdown.value;
        const grade = courseDropdown.value;
        const game = gameDropdown.value;

        const rows = document.querySelectorAll("#archiveTableContent table tbody tr");

        rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            const rowYear = row.getAttribute('data-year');
            const rowDept = row.getAttribute('data-department');
            const rowGrade = row.getAttribute('data-grade');
            const rowGame = row.getAttribute('data-game');

            const matchesSearch = rowText.includes(searchText);
            const matchesYear = !year || rowYear === year;
            const matchesDept = !department || rowDept === department;
            const matchesGrade = !grade || rowGrade === grade;
            const matchesGame = !game || rowGame === game;

            row.style.display = (matchesSearch && matchesYear && matchesDept && matchesGrade && matchesGame) ? "" : "none";
        });
    }

    // ✅ Event Listeners
    tableDropdown.addEventListener('change', () => {
        updateURL('table', tableDropdown.value);
        loadArchivePage();
    });

    schoolDropdown.addEventListener('change', () => {
        resetURLParams();
        updateURL('school_id', schoolDropdown.value);
        loadDependentDropdowns();
        contentContainer.innerHTML = `<p class="text-muted">Select a table and year to view content.</p>`;
    });

    archiveYearDropdown.addEventListener('change', () => {
        updateURL('year', archiveYearDropdown.value);
        loadArchivePage();
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

    searchInput.addEventListener('input', filterTable);

    // ✅ Auto load if pre-selected
    if (schoolDropdown.value) loadDependentDropdowns();
    if (schoolDropdown.value && tableDropdown.value && archiveYearDropdown.value) {
        loadArchivePage();
    }

    // ✅ REPLACE page reload for department teams
    document.addEventListener("click", function (event) {
        const target = event.target.closest(".view-teams-btn");
        if (!target) return;

        const gradeSectionCourseId = target.getAttribute("data-grade-section-course-id");
        updateURL('table', 'department-teams');
        updateURL('grade_section_course_id', gradeSectionCourseId);

        tableDropdown.value = 'department-teams';
        loadArchivePage({ grade_section_course_id: gradeSectionCourseId });
    });
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