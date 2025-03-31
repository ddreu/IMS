document.addEventListener("DOMContentLoaded", () => {
  // Attach click event to all "Open as Committee" buttons
  document.querySelectorAll(".open-committee-btn").forEach((button) => {
    button.addEventListener("click", () => {
      const gameId = button.getAttribute("data-game-id");
      const role = button.getAttribute("data-role");

      console.log(`Game ID: ${gameId}, Role: ${role}`);

      if (role === "School Admin" || role === "Super Admin") {
        console.log("Opening department modal...");
        const modal = new bootstrap.Modal(
          document.getElementById("selectDepartmentModal")
        );
        modal.show();

        // Confirm button action inside the modal
        document.getElementById("confirmOpenCommittee").addEventListener(
          "click",
          () => {
            const departmentDropdown =
              document.getElementById("departmentDropdown");
            const selectedDepartment = departmentDropdown.value;
            const selectedDepartmentName =
              departmentDropdown.options[
                departmentDropdown.selectedIndex
              ]?.getAttribute("data-name");

            if (selectedDepartment) {
              console.log(
                `Selected Department ID: ${selectedDepartment}, Name: ${selectedDepartmentName}`
              );
              openCommitteeWithDepartment(
                gameId,
                selectedDepartment,
                selectedDepartmentName
              );
            } else {
              alert("Please select a department.");
            }
          },
          { once: true } // Prevent multiple click bindings
        );
      } else {
        console.log("Direct access (non-admin), opening directly...");
        openCommitteeWithDepartment(gameId);
      }
    });
  });
});

function openCommitteeWithDepartment(gameId, departmentId = null) {
  console.log(
    `Opening with Game ID: ${gameId}, Department ID: ${departmentId}`
  );
  fetch("open_as_committee.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `game_id=${encodeURIComponent(
      gameId
    )}&department_id=${encodeURIComponent(departmentId || "")}`,
  })
    .then((response) => response.json())
    .then((res) => {
      console.log("Response from server:", res);
      if (res.success) {
        window.location.href = "../committee/committeedashboard.php";
      } else {
        alert(res.message);
      }
    })
    .catch(() => {
      alert("An error occurred while opening as committee.");
    });
}

function openCommitteeWithDepartment(
  gameId,
  departmentId = null,
  departmentName = null
) {
  console.log(
    `Opening with Game ID: ${gameId}, Department ID: ${departmentId}, Department Name: ${departmentName}`
  );

  // Build request body
  const params = new URLSearchParams();
  params.append("game_id", gameId);
  if (departmentId) params.append("department_id", departmentId);
  if (departmentName) params.append("department_name", departmentName);

  fetch("open_as_committee.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: params.toString(),
  })
    .then((response) => response.json())
    .then((res) => {
      console.log("Response from server:", res);
      if (res.success) {
        window.location.href = "../committee/committeedashboard.php";
      } else {
        alert(res.message);
      }
    })
    .catch(() => {
      alert("An error occurred while opening as committee.");
    });
}
