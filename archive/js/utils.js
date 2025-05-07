document.addEventListener("DOMContentLoaded", () => {
  const schoolDropdown = document.getElementById("school");

  const tableDropdown = document.getElementById("table");
  const archiveYearDropdown = document.getElementById("archive_year");
  const departmentDropdown = document.getElementById("department");
  const courseDropdown = document.getElementById("course");
  const gameDropdown = document.getElementById("game");
  const searchInput = document.getElementById("search");
  // const contentContainer = document.getElementById("archiveTableContent");
  const selectedYear = new URLSearchParams(window.location.search).get("year");

  const isSuperadmin = window.userRole === "superadmin";

  // If not superadmin, auto-load using their school_id
  const urlParams = new URLSearchParams(window.location.search);
  const urlSchoolId = urlParams.get("school_id");

  if (isSuperadmin) {
    // If a school_id is in the URL, apply it to the dropdown
    if (schoolDropdown && urlSchoolId) {
      schoolDropdown.value = urlSchoolId;
      loadDependentDropdowns();
    }
  } else {
    // For regular users, use their assigned school
    if (schoolDropdown && window.userSchoolId) {
      schoolDropdown.value = window.userSchoolId;
      loadDependentDropdowns();
    }
  }

  function updateURL(param, value) {
    const url = new URL(window.location.href);
    if (value) {
      url.searchParams.set(param, value);
    } else {
      url.searchParams.delete(param);
    }
    window.history.pushState({}, "", url);
  }

  function resetURLParams() {
    const url = new URL(window.location.href);
    url.search = "";
    window.history.pushState({}, "", url);
  }

  // function loadArchivePage(extraParams = {}) {
  //   const table = tableDropdown.value;
  //   const year = archiveYearDropdown.value;

  //   if (!table || !year) {
  //     contentContainer.innerHTML = `<p class="text-muted">Select a table and year to view its content.</p>`;
  //     return;
  //   }

  //   let url = `archive-pages/${table}.php?year=${encodeURIComponent(year)}`;

  //   // Append extra query parameters
  //   Object.entries(extraParams).forEach(([key, val]) => {
  //     url += `&${encodeURIComponent(key)}=${encodeURIComponent(val)}`;
  //   });

  //   contentContainer.innerHTML = `<div class="text-muted py-3">Loading...</div>`;

  //   fetch(url)
  //     .then((res) => {
  //       if (!res.ok) throw new Error("Failed to load content.");
  //       return res.text();
  //     })
  //     .then((html) => {
  //       contentContainer.innerHTML = html;
  //       loadScript(`archive-page-js/${table}.js`);
  //       filterTable();
  //     })
  //     .catch((err) => {
  //       contentContainer.innerHTML = `<p class="text-danger">Error: ${err.message}</p>`;
  //     });
  // }

  // function loadScript(src) {
  //   const existing = document.querySelector(`script[src="${src}"]`);
  //   if (existing) existing.remove();
  //   const script = document.createElement("script");
  //   script.src = src;
  //   script.defer = true;
  //   document.body.appendChild(script);
  // }

  function loadDependentDropdowns() {
    if (!schoolDropdown.value) return resetDropdowns();

    fetch(`dropdown/fetch_archive_years.php?school_id=${schoolDropdown.value}`)
      .then((res) => res.text())
      .then((data) => {
        // archiveYearDropdown.innerHTML = data;
        // archiveYearDropdown.disabled = false;
        // archiveYearDropdown.value = "";
        archiveYearDropdown.innerHTML = data;
        archiveYearDropdown.disabled = false;

        // ✅ Preserve the selected year
        const savedYear = new URLSearchParams(window.location.search).get(
          "year"
        );
        if (savedYear) {
          archiveYearDropdown.value = savedYear;
        }

        loadDepartment();
        loadGames();
      })
      .catch((err) => console.error("Archive year fetch failed:", err));
  }

  function loadDepartment() {
    fetch(
      `dropdown/fetch_departments.php?school_id=${schoolDropdown.value}&year=${selectedYear}`
    )
      .then((res) => res.text())
      .then((data) => {
        departmentDropdown.innerHTML = data;
        departmentDropdown.disabled = false;
        departmentDropdown.value = "";

        const saved = new URLSearchParams(window.location.search).get(
          "department_id"
        );
        if (saved) {
          departmentDropdown.value = saved;
          handleGradeLevel(); // ✅ Persist + load grades
        }
      })
      .catch((err) => console.error("Department fetch failed:", err));
  }

  function loadGames() {
    fetch(
      `dropdown/fetch_games.php?school_id=${schoolDropdown.value}&year=${selectedYear}`
    )
      .then((res) => res.text())
      .then((data) => {
        // Start with "Select Game"
        gameDropdown.innerHTML = '<option value="">Select Game</option>';

        // Append the "All" option
        const allOption = document.createElement("option");
        allOption.value = "__all__";
        allOption.textContent = "All";
        gameDropdown.appendChild(allOption);

        // Append the rest of the game options
        const tempDiv = document.createElement("div");
        tempDiv.innerHTML = data;

        Array.from(tempDiv.children).forEach((opt) => {
          gameDropdown.appendChild(opt);
        });

        gameDropdown.disabled = false;
        gameDropdown.value = "";
      })
      .catch((err) => console.error("Games fetch failed:", err));
  }

  function handleGradeLevel() {
    const selectedText =
      departmentDropdown.options[
        departmentDropdown.selectedIndex
      ]?.text?.toLowerCase() || "";

    if (selectedText === "college") {
      courseDropdown.innerHTML = '<option value="">N/A</option>';
      courseDropdown.disabled = true;
      return;
    }

    fetch(
      `dropdown/fetch_grade_level.php?department_id=${departmentDropdown.value}`
    )
      .then((res) => res.json())
      .then((data) => {
        if (data.error) throw new Error(data.error);

        courseDropdown.innerHTML = '<option value="">Select Grade</option>';

        const allOption = document.createElement("option");
        allOption.value = "__all__";
        allOption.textContent = "All";
        courseDropdown.appendChild(allOption); // <-- append after default

        data.forEach((grade) => {
          const option = document.createElement("option");
          option.value = grade;
          option.textContent = grade;
          courseDropdown.appendChild(option);
        });

        courseDropdown.disabled = false;
      })
      .catch((err) => {
        console.error("Grade level fetch failed:", err);
        courseDropdown.innerHTML = "<option>Error loading grades</option>";
        courseDropdown.disabled = true;
      });
  }

  function resetDropdowns() {
    archiveYearDropdown.innerHTML = '<option value="">Select Year</option>';
    departmentDropdown.innerHTML =
      '<option value="">Select Department</option>';
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

    const rows = document.querySelectorAll(
      "#archiveTableContent table tbody tr"
    );

    rows.forEach((row) => {
      const rowText = row.textContent.toLowerCase();
      const rowYear = row.getAttribute("data-year");
      const rowDept = row.getAttribute("data-department");
      const rowGrade = row.getAttribute("data-grade");
      const rowGame = row.getAttribute("data-game");

      const matchesSearch = rowText.includes(searchText);
      const matchesYear = !year || rowYear === year;
      const matchesDept = !department || rowDept === department;
      const matchesGrade = !grade || rowGrade === grade;
      const matchesGame = !game || rowGame === game;

      row.style.display =
        matchesSearch &&
        matchesYear &&
        matchesDept &&
        matchesGrade &&
        matchesGame
          ? ""
          : "none";
    });
  }

  // ✅ Event Listeners
  tableDropdown.addEventListener("change", () => {
    updateURL("table", tableDropdown.value);
    // loadArchivePage();
    window.location.reload();
  });
  if (isSuperadmin) {
    schoolDropdown.addEventListener("change", () => {
      resetURLParams();
      updateURL("school_id", schoolDropdown.value);
      loadDependentDropdowns();
      document.getElementById(
        "archiveTableContent"
      ).innerHTML = `<p class="text-muted">Select a table and year to view content.</p>`;
    });
  }

  // schoolDropdown.addEventListener("change", () => {
  //   resetURLParams();
  //   updateURL("school_id", schoolDropdown.value);
  //   loadDependentDropdowns();
  //   contentContainer.innerHTML = `<p class="text-muted">Select a table and year to view content.</p>`;
  // });

  archiveYearDropdown.addEventListener("change", () => {
    updateURL("year", archiveYearDropdown.value);
    // loadArchivePage();
    window.location.reload();
  });

  // departmentDropdown.addEventListener("change", () => {
  //   updateURL("department_id", departmentDropdown.value);
  //   handleGradeLevel();
  //   filterTable();
  //   window.location.reload();
  // });

  departmentDropdown.addEventListener("change", () => {
    updateURL("department_id", departmentDropdown.value);
    updateURL("course_id", ""); // 🔥 Remove grade level when department changes
    handleGradeLevel(); // Refresh grade options
    setTimeout(() => {
      window.location.reload(); // Reload with updated URL
    }, 100);
  });

  // courseDropdown.addEventListener("change", () => {
  //   updateURL("course_id", courseDropdown.value);
  //   // filterTable();
  //   window.location.reload();
  // });
  courseDropdown.addEventListener("change", () => {
    if (courseDropdown.value === "__all__") {
      updateURL("course_id", ""); // Remove from URL
    } else {
      updateURL("course_id", courseDropdown.value);
    }
    window.location.reload();
  });

  // gameDropdown.addEventListener("change", () => {
  //   updateURL("game_id", gameDropdown.value);
  //   // filterTable();
  //   window.location.reload();
  // });
  gameDropdown.addEventListener("change", () => {
    if (gameDropdown.value === "__all__") {
      updateURL("game_id", ""); // Remove from URL
    } else {
      updateURL("game_id", gameDropdown.value);
    }
    window.location.reload();
  });

  searchInput.addEventListener("input", filterTable);

  // ✅ Auto load if pre-selected
  if (schoolDropdown.value) loadDependentDropdowns();
  if (
    schoolDropdown.value &&
    tableDropdown.value &&
    archiveYearDropdown.value
  ) {
    loadArchivePage();
  }

  // ✅ REPLACE page reload for department teams
  document.addEventListener("click", function (event) {
    const target = event.target.closest(".view-teams-btn");
    if (!target) return;

    const gradeSectionCourseId = target.getAttribute(
      "data-grade-section-course-id"
    );
    updateURL("table", "department-teams");
    updateURL("grade_section_course_id", gradeSectionCourseId);

    tableDropdown.value = "department-teams";
    loadArchivePage({ grade_section_course_id: gradeSectionCourseId });
  });
});

/* teams */
function showTeams(gradeSectionCourseId) {
  const url = new URL(window.location.href);

  // Update or set the 'table' parameter to 'department-teams'
  url.searchParams.set("table", "department-teams");

  // Set the 'grade_section_course_id' parameter
  url.searchParams.set("grade_section_course_id", gradeSectionCourseId);

  // Reload the page with the updated URL
  window.location.href = url.toString();
}

/* Teams click handler */
document.addEventListener("click", function (event) {
  const target = event.target.closest(".view-teams-btn");
  if (target) {
    // Get the grade_section_course_id from the data attribute
    const gradeSectionCourseId = target.getAttribute(
      "data-grade-section-course-id"
    );

    // Call showTeams with the correct parameter
    showTeams(gradeSectionCourseId);
  }
});

function viewPlayers(teamId) {
  const url = new URL(window.location.href);

  // Set the correct table and team_id
  url.searchParams.set("table", "players");
  url.searchParams.set("team_id", teamId);

  // Optional: remove unrelated params
  // url.searchParams.delete("grade_section_course_id");

  // Redirect to the correct page
  window.location.href = url.toString();
}

function viewMatchSummary(matchId) {
  const modal = document.getElementById("matchSummaryModal");
  const modalBody = modal.querySelector(".modal-body");

  modalBody.innerHTML = `<div class="text-center py-4">Loading...</div>`;

  fetch(`archive-pages/match_summary.php?match_id=${matchId}&ajax=1`)
    .then((res) => {
      if (!res.ok) throw new Error("Failed to fetch match summary");
      return res.text();
    })
    .then((html) => {
      modalBody.innerHTML = html;
      const bsModal = new bootstrap.Modal(modal);
      bsModal.show();
    })
    .catch((err) => {
      modalBody.innerHTML = `<div class="text-danger text-center py-4">Error: ${err.message}</div>`;
    });
}

document.addEventListener("click", function (event) {
  if (event.target.classList.contains("archive-btn")) {
    const button = event.target;
    const id = button.getAttribute("data-id");
    const table = button.getAttribute("data-table");
    const operation = button.getAttribute("data-operation"); // New: archive or unarchive

    if (!id || !table || !operation) return;

    const isArchive = operation === "archive";

    // SweetAlert confirmation
    Swal.fire({
      title: isArchive
        ? "Are you sure you want to archive this?"
        : "Are you sure you want to unarchive this?",
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
            "Content-Type": "application/x-www-form-urlencoded",
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
                text:
                  data.message ||
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
