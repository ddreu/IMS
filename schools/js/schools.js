document.addEventListener("DOMContentLoaded", () => {
  const openSchoolModal = document.getElementById("openSchoolModal");
  const dashboardType = document.getElementById("dashboardType");
  const departmentSelection = document.getElementById("departmentSelection");
  const gameSelection = document.getElementById("gameSelection");
  const departmentDropdown = document.getElementById("department");
  const gameDropdown = document.getElementById("game");

  // ✅ Trigger when modal is shown
  openSchoolModal.addEventListener("show.bs.modal", (event) => {
    const button = event.relatedTarget;
    const schoolId = button.getAttribute("data-school-id");
    document.getElementById("selectedSchoolId").value = schoolId;

    // Clear dropdowns
    departmentDropdown.innerHTML =
      '<option value="">Select Department</option>';
    gameDropdown.innerHTML = '<option value="">Select Game</option>';

    // ✅ Load departments
    departments.forEach((dept) => {
      if (dept.school_id == schoolId) {
        departmentDropdown.innerHTML += `<option value="${dept.id}">${dept.department_name}</option>`;
      }
    });

    // ✅ Load games
    games.forEach((game) => {
      if (game.school_id == schoolId) {
        gameDropdown.innerHTML += `<option value="${game.game_id}">${game.game_name}</option>`;
      }
    });
  });

  // ✅ Show/Hide fields based on dashboard type
  dashboardType.addEventListener("change", () => {
    departmentSelection.classList.add("d-none");
    gameSelection.classList.add("d-none");

    if (dashboardType.value === "dept_admin") {
      departmentSelection.classList.remove("d-none");
    } else if (dashboardType.value === "committee") {
      departmentSelection.classList.remove("d-none"); // ✅ Show department for committee
      gameSelection.classList.remove("d-none");
    }
  });

  // ✅ Handle form submission
  document.getElementById("openDashboardBtn").addEventListener("click", () => {
    const schoolId = document.getElementById("selectedSchoolId").value;
    const departmentId = departmentDropdown.value || null;
    const gameId = gameDropdown.value || null;
    const selectedDashboardType = dashboardType.value;

    if (!schoolId) {
      alert("Please select a school.");
      return;
    }

    if (selectedDashboardType === "dept_admin" && !departmentId) {
      alert("Please select a department.");
      return;
    }

    if (selectedDashboardType === "committee" && (!departmentId || !gameId)) {
      alert("Please select both department and game.");
      return;
    }

    // ✅ Send data to PHP using AJAX
    fetch("set_dashboard_session.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        schoolId,
        departmentId,
        gameId,
        dashboardType: selectedDashboardType,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          window.location.href = data.redirect; // ✅ Redirect to dashboard
        } else {
          alert(data.message);
        }
      })
      .catch((error) => console.error("Error:", error));
  });
});
