function getUrlParams() {
  const params = {};
  const queryString = window.location.search;
  const urlParams = new URLSearchParams(queryString);
  for (const [key, value] of urlParams.entries()) {
    params[key] = value;
  }
  return params;
}
function getUrlParams() {
  const params = {};
  const queryString = window.location.search;
  const urlParams = new URLSearchParams(queryString);
  for (const [key, value] of urlParams.entries()) {
    params[key] = value;
  }
  return params;
}

function loadBrackets(filters = {}) {
  const urlParams = getUrlParams();
  const finalFilters = { ...urlParams, ...filters };

  $.ajax({
    url: "archive-pages/fetch_admin_brackets.php",
    method: "GET",
    data: finalFilters,
    success: function (response) {
      if (response.success) {
        const brackets = response.data;
        const tableSelector = "#bracketsTable";
        const tbody = $("#bracketsTableBody");

        // Destroy DataTable instance if it exists
        if ($.fn.DataTable.isDataTable(tableSelector)) {
          $(tableSelector).DataTable().clear().destroy();
        }

        tbody.empty();

        if (brackets.length === 0) {
          tbody.append(
            '<tr><td colspan="8" class="text-center">No brackets found</td></tr>'
          );
        } else {
          brackets.forEach((bracket) => {
            const row = `
<tr data-category="${bracket.is_archived}">
  <td>${bracket.game_name}</td>
  <td>${bracket.department_name}</td>
  <td>${bracket.grade_level || "N/A"}</td>
  <td>${bracket.total_teams}</td>
  <td>${bracket.status}</td>
  <td>${bracket.bracket_type}</td>
  <td>${new Date(bracket.created_at).toLocaleDateString()}</td>
  <td>
    <div class="dropdown">
      <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        Actions
      </button>
      <ul class="dropdown-menu">
        ${
          bracket.bracket_type === "round_robin"
            ? `
              <li><button class="dropdown-item" onclick="viewRoundRobin(${
                bracket.bracket_id
              })">View Round Robin</button></li>
              ${
                bracket.is_archived == 0
                  ? `
                <li><button class="dropdown-item" onclick="downloadBracket(${bracket.bracket_id}, 'round_robin')">Download Round Robin</button></li>
                <li><button class="dropdown-item archive-btn" data-id="${bracket.bracket_id}" data-table="brackets" data-operation="archive">Archive</button></li>
                <li><button class="dropdown-item text-danger" onclick="deleteBracket(${bracket.bracket_id})">Delete</button></li>
              `
                  : ""
              }
            `
            : bracket.bracket_type === "double"
            ? `
              <li><button class="dropdown-item" onclick="viewDoubleElimination(${
                bracket.bracket_id
              })">View Double Elimination</button></li>
              ${
                bracket.is_archived == 0
                  ? `
                <li><button class="dropdown-item archive-btn" data-id="${bracket.bracket_id}" data-table="brackets" data-operation="archive">Archive</button></li>
                <li><button class="dropdown-item text-danger" onclick="deleteBracket(${bracket.bracket_id})">Delete</button></li>
              `
                  : ""
              }
            `
            : `
              <li><button class="dropdown-item" onclick="viewBracket(${
                bracket.bracket_id
              })">View Bracket</button></li>
              ${
                bracket.is_archived == 0
                  ? `
                <li><button class="dropdown-item archive-btn" data-id="${bracket.bracket_id}" data-table="brackets" data-operation="archive">Archive</button></li>
                <li><button class="dropdown-item text-danger" onclick="deleteBracket(${bracket.bracket_id})">Delete</button></li>
              `
                  : ""
              }
            `
        }
      </ul>
    </div>
  </td>
</tr>`;
            tbody.append(row);
          });
        }

        // Re-initialize DataTable
        $(tableSelector).DataTable({
          pageLength: 10,
          lengthMenu: [5, 10, 25, 50, 100],
          order: [[6, "desc"]],
          columnDefs: [{ orderable: false, targets: -1 }],
        });
      } else {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: response.message || "Failed to load brackets",
        });
      }
    },
    error: function (xhr, status, error) {
      console.error("Error loading brackets:", error);
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Failed to load brackets. Please try again.",
      });
    },
  });
}

function applyFilters() {
  const filters = {
    department_id: $("#departmentFilter").val(),
    game_id: $("#gameFilter").val(),
    grade_level: $("#gradeLevelFilter").val(),
  };
  loadBrackets(filters);
}

function viewBracket(bracketId) {
  console.log("View Bracket clicked:", bracketId);

  $("#bracketModalContainer").empty(); // Clear modal content

  fetch("archive-pages/fetch_bracket.php?bracket_id=" + bracketId)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const roundNumbers = Object.keys(data.matches)
          .filter((key) => key !== "third-place" && !isNaN(parseInt(key)))
          .sort((a, b) => parseInt(a) - parseInt(b));

        const totalTeams = data.matches["1"] ? data.matches["1"].length * 2 : 0;

        const teams = [];
        if (data.matches["1"]) {
          data.matches["1"].forEach((match) => {
            teams.push([match.teamA_name || "TBD", match.teamB_name || "TBD"]);
          });
        }

        const results = [];
        let matchesInRound = Math.floor(totalTeams / 2);

        roundNumbers.forEach((roundNum) => {
          const roundResults = [];
          const roundMatches = data.matches[roundNum] || [];

          for (let i = 0; i < matchesInRound; i++) {
            const match = roundMatches[i];
            if (match) {
              if (
                match.status === "Finished" &&
                match.score_teamA != null &&
                match.score_teamB != null
              ) {
                roundResults.push([
                  parseInt(match.score_teamA),
                  parseInt(match.score_teamB),
                ]);
              } else if (match.winning_team_id !== null) {
                roundResults.push(
                  match.winning_team_id === match.teamA_id ? [1, 0] : [0, 1]
                );
              } else if (match.teamA_id === -1 || match.teamB_id === -1) {
                roundResults.push(match.teamA_id === -1 ? [0, 1] : [1, 0]);
              } else {
                roundResults.push([0, 0]);
              }
            } else {
              roundResults.push([0, 0]);
            }
          }

          results.push(roundResults);
          matchesInRound = Math.floor(matchesInRound / 2);
        });

        if (teams.length === 0) {
          $("#bracketModalContainer").html(
            `<div class="alert alert-warning">No teams available for the bracket.</div>`
          );
          return;
        }

        const bracketData = {
          teams: teams,
          results: results,
        };

        $("#bracketModalContainer").bracket({
          teamWidth: 150,
          scoreWidth: 40,
          matchMargin: 50,
          roundMargin: 50,
          init: bracketData,
          decorator: {
            edit: function () {},
            render: function (container, data, score) {
              container.empty();
              if (data === null) container.append("BYE");
              else if (data === "TBD") container.append("TBD");
              else container.append(data);
            },
          },
        });
        const matchCount = bracketData.teams.length;
        const height = Math.max(700, matchCount * 60); // Adjust multiplier if needed
        $("#bracketModalContainer").css("height", `${height}px`);

        $("#bracketModalContainer .jQBracket").css({
          "min-height": "700px",
          "padding-bottom": "50px",
        });

        const modal = new bootstrap.Modal(
          document.getElementById("viewBracketModal")
        );
        modal.show();
        setTimeout(() => {
          $("#bracketModalContainer").trigger("resize");
          window.dispatchEvent(new Event("resize"));
        }, 300);
      } else {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Failed to load bracket: " + data.message,
        });
      }
    })
    .catch((error) => {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Failed to load bracket. Please try again.",
      });
    });
}

// //PDF BRACKET
// function exportBracketToPDF() {
//     Swal.fire({
//         title: 'Exporting...',
//         text: 'Please wait while the bracket is being exported.',
//         allowOutsideClick: false,
//         didOpen: () => {
//             Swal.showLoading(); // ✅ Show loading spinner
//         }
//     });

//     const element = document.getElementById('bracket-container');
//     html2canvas(element, {
//         scale: 2
//     }).then((canvas) => {
//         const imgData = canvas.toDataURL('image/png');
//         const {
//             jsPDF
//         } = window.jspdf;
//         const pdf = new jsPDF('landscape');
//         const imgWidth = 280;
//         const imgHeight = (canvas.height * imgWidth) / canvas.width;
//         pdf.addImage(imgData, 'PNG', 10, 10, imgWidth, imgHeight);
//         pdf.save('bracket.pdf');

//         // ✅ Close loading state and show success toast using SweetAlert
//         Swal.close();
//         Swal.fire({
//             icon: 'success',
//             title: 'Bracket exported successfully!',
//             toast: true,
//             position: 'top',
//             showConfirmButton: false,
//             timer: 3000
//         });
//     }).catch((error) => {
//         // ✅ Close loading state and show error toast using SweetAlert
//         Swal.close();
//         Swal.fire({
//             icon: 'error',
//             title: 'Failed to export bracket.',
//             text: 'Please try again.',
//             toast: true,
//             position: 'top',
//             showConfirmButton: false,
//             timer: 3000
//         });
//         console.error('Error exporting bracket:', error);
//     });
// }

//PDF BRACKET
async function exportBracketToPDF() {
  Swal.fire({
    title: "Exporting...",
    text: "Please wait while the bracket is being exported.",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });

  const element = document.getElementById("bracket-container");
  html2canvas(element, {
    scale: 2,
  })
    .then(async (canvas) => {
      const imgData = canvas.toDataURL("image/png");
      const { jsPDF } = window.jspdf;
      const pdf = new jsPDF("landscape");
      const imgWidth = 280;
      const imgHeight = (canvas.height * imgWidth) / canvas.width;
      pdf.addImage(imgData, "PNG", 10, 10, imgWidth, imgHeight);

      Swal.close();

      try {
        // ✅ Use File System Access API for file picker
        const fileHandle = await window.showSaveFilePicker({
          suggestedName: "bracket.pdf",
          types: [
            {
              description: "PDF Document",
              accept: {
                "application/pdf": [".pdf"],
              },
            },
          ],
        });

        const writable = await fileHandle.createWritable();
        const pdfBlob = pdf.output("blob");
        await writable.write(pdfBlob);
        await writable.close();

        toastr.success("Bracket exported successfully!");
      } catch (error) {
        console.error("Error saving file:", error);
        toastr.error("Failed to export bracket. Please try again.");
      }
    })
    .catch((error) => {
      Swal.close();
      toastr.error("Failed to export bracket. Please try again.");
      console.error("Error exporting bracket:", error);
    });
}

// Load brackets when page loads
$(document).ready(function () {
  loadBrackets();
  // Wrap bracket container with wrapper div
  $("#bracket-container").wrap('<div class="bracket-wrapper"></div>');
});

function loadRoundRobin(bracketId) {
  Swal.fire({
    title: "Loading...",
    text: "Fetching tournament schedule",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });

  fetch("fetch_round_robin.php?bracket_id=" + bracketId)
    .then((response) => response.json())
    .then((data) => {
      Swal.close();

      if (data.success) {
        // Populate tournament info
        $("#tournamentGame").text(data.tournament_info.game_name);
        $("#tournamentDept").text(data.tournament_info.department_name);
        $("#tournamentStatus").text(data.tournament_info.status);
        $("#tournamentTeams").text(data.tournament_info.total_teams);

        // Populate scoring rules inputs
        if (data.scoring_rules) {
          $("#viewWinPoints").val(data.scoring_rules.win_points);
          $("#viewDrawPoints").val(data.scoring_rules.draw_points);
          $("#viewLossPoints").val(data.scoring_rules.loss_points);
          $("#viewBonusPoints").val(data.scoring_rules.bonus_points);
        }

        // Populate standings table
        const standingsBody = $("#standingsTableBody");
        standingsBody.empty();

        let rank = 1;
        data.standings.forEach((team) => {
          const rankEmoji =
            rank === 1 ? "1️⃣" : rank === 2 ? "2️⃣" : rank === 3 ? "3️⃣" : rank;
          const rowClass =
            rank === 1
              ? "table-success"
              : rank === 2
              ? "table-info"
              : rank === 3
              ? "table-warning"
              : "";

          standingsBody.append(`
                        <tr class="${rowClass}">
                            <td>${rankEmoji}</td>
                            <td><strong>${team.team_name}</strong></td>
                            <td>${team.played}</td>
                            <td>${team.wins}</td>
                            <td>${team.draw}</td>
                            <td>${team.losses}</td>
                            <td>${team.bonus_points}</td>
                            <td><strong>${team.total_points}</strong></td>
                            <td>
                                <button class="btn btn-sm btn-success add-bonus" 
                                        data-team-id="${team.team_id}" 
                                        data-bracket-id="${bracketId}">
                                    <i class="fas fa-plus"></i> Add Bonus
                                </button>
                            </td>
                        </tr>
                    `);
          rank++;
        });

        // Add bonus points handler
        $("#standingsTableBody")
          .off("click", ".add-bonus")
          .on("click", ".add-bonus", async function () {
            const teamId = $(this).data("team-id");
            const bracketId = $(this).data("bracket-id");

            const { value: bonusPoints } = await Swal.fire({
              title: "Add Bonus Points",
              input: "number",
              inputLabel: "Enter bonus points",
              inputValue: 0,
              showCancelButton: true,
              inputValidator: (value) => {
                if (!value || isNaN(value) || value < 0) {
                  return "Please enter a valid positive number";
                }
              },
            });

            if (bonusPoints) {
              try {
                const response = await fetch("save_round_robin_points.php", {
                  method: "POST",
                  headers: {
                    "Content-Type": "application/json",
                  },
                  body: JSON.stringify({
                    bracket_id: bracketId,
                    team_id: teamId,
                    bonus_points: parseInt(bonusPoints),
                    action: "add_bonus",
                  }),
                });

                const result = await response.json();

                if (result.success) {
                  Swal.fire({
                    icon: "success",
                    title: "Success",
                    text: "Bonus points added successfully!",
                  }).then(() => {
                    loadRoundRobin(bracketId); // Refresh the standings
                  });
                } else {
                  throw new Error(result.message);
                }
              } catch (error) {
                Swal.fire({
                  icon: "error",
                  title: "Error",
                  text: "Failed to add bonus points: " + error.message,
                });
              }
            }
          });

        // Populate matches table
        const matchesBody = $("#matchesTableBody");
        matchesBody.empty();

        data.matches.forEach((match) => {
          const scores =
            match.status.toLowerCase() === "finished"
              ? `${match.score_teamA || "-"} - ${match.score_teamB || "-"}`
              : "-";

          matchesBody.append(`
                        <tr>
                            <td>Round ${match.round}</td>
                            <td>Match ${match.match_number}</td>
                            <td>${match.teamA_name || "TBD"}</td>
                            <td>${scores}</td>
                            <td>${match.teamB_name || "TBD"}</td>
                            <td>
                                <span class="badge bg-${
                                  match.status.toLowerCase() === "pending"
                                    ? "warning"
                                    : "success"
                                }">
                                    ${match.status}
                                </span>
                            </td>
                        </tr>
                    `);
        });

        // Save points handler
        $("#savePoints")
          .off("click")
          .on("click", async function () {
            try {
              const response = await fetch("save_round_robin_points.php", {
                method: "POST",
                headers: {
                  "Content-Type": "application/json",
                },
                body: JSON.stringify({
                  bracket_id: bracketId,
                  win_points: parseInt($("#viewWinPoints").val()) || 0,
                  draw_points: parseInt($("#viewDrawPoints").val()) || 0,
                  loss_points: parseInt($("#viewLossPoints").val()) || 0,
                  bonus_points: parseInt($("#viewBonusPoints").val()) || 0,
                }),
              });

              const result = await response.json();

              if (result.success) {
                Swal.fire({
                  icon: "success",
                  title: "Success",
                  text: "Scoring points updated successfully!",
                });
              } else {
                throw new Error(result.message);
              }
            } catch (error) {
              Swal.fire({
                icon: "error",
                title: "Error",
                text: "Failed to update scoring points: " + error.message,
              });
            }
          });

        // Show the modal
        const modal = new bootstrap.Modal(
          document.getElementById("viewRoundRobinModal")
        );
        modal.show();
      } else {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: data.message || "Failed to load tournament schedule",
        });
      }
    })
    .catch((error) => {
      Swal.close();
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Failed to load tournament schedule. Error: " + error.message,
      });
    });
}

// Bind the event
$(document).on("click", ".view-round-robin", function () {
  const bracketId = $(this).data("bracket-id");
  loadRoundRobin(bracketId);
});

function deleteBracket(bracketId) {
  Swal.fire({
    title: "Are you sure?",
    text: "This will permanently delete this bracket and all its matches. This action cannot be undone!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Yes, delete it!",
  }).then((result) => {
    if (result.isConfirmed) {
      $.ajax({
        url: "delete_bracket.php",
        method: "POST",
        data: {
          bracket_id: bracketId,
        },
        success: function (response) {
          if (response.success) {
            Swal.fire({
              title: "Success!",
              text: "Bracket has been deleted successfully.",
              icon: "success",
            }).then(() => {
              // Reload the page to show the saved bracket
              window.location.reload();
            });
          } else {
            Swal.fire({
              title: "Error!",
              text: response.message,
              icon: "error",
            });
          }
        },
        error: function () {
          Swal.fire({
            title: "Error!",
            text: "Failed to delete bracket. Please try again.",
            icon: "error",
          });
        },
      });
    }
  });
}

// function loadBrackets(filters = {}) {
//   const urlParams = getUrlParams();
//   const finalFilters = { ...urlParams, ...filters };

//   $.ajax({
//     url: "archive-pages/fetch_admin_brackets.php",
//     method: "GET",
//     data: finalFilters,
//     success: function (response) {
//       if (response.success) {
//         const brackets = response.data;
//         const tbody = $("#bracketsTableBody");
//         tbody.empty();

//         if (brackets.length === 0) {
//           tbody.append(
//             '<tr><td colspan="7" class="text-center">No brackets found</td></tr>'
//           );
//           return;
//         }

//         brackets.forEach((bracket) => {
//           const row = `
// <tr data-category="${bracket.is_archived}">
//             <td>${bracket.game_name}</td>
//             <td>${bracket.department_name}</td>
//             <td>${bracket.grade_level || "N/A"}</td>
//             <td>${bracket.total_teams}</td>
//             <td>${bracket.status}</td>
//             <td>${bracket.bracket_type}</td>
//             <td>${new Date(bracket.created_at).toLocaleDateString()}</td>
//             <td>
//                 <div class="dropdown">
//                     <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
//                         Actions
//                    </button>
// <ul class="dropdown-menu">
//   ${
//     bracket.bracket_type === "round_robin"
//       ? `
//         <li>
//           <button class="dropdown-item" onclick="viewRoundRobin(${
//             bracket.bracket_id
//           })">
//             View Round Robin
//           </button>
//         </li>
//         ${
//           bracket.is_archived == 0
//             ? `
//               <li>
//                 <button class="dropdown-item" onclick="downloadBracket(${bracket.bracket_id}, 'round_robin')">
//                   Download Round Robin
//                 </button>
//               </li>
//               <li>
//                 <button type="button"
//                   class="dropdown-item archive-btn"
//                   data-id="${bracket.bracket_id}"
//                   data-table="brackets"
//                   data-operation="archive"
//                   style="padding: 4px 12px; line-height: 1.2;">
//                   Archive
//                 </button>
//               </li>
//               <li>
//                 <button class="dropdown-item text-danger" onclick="deleteBracket(${bracket.bracket_id})">
//                   Delete
//                 </button>
//               </li>
//             `
//             : ""
//         }
//       `
//       : bracket.bracket_type === "double"
//       ? `
//         <li>
//           <button class="dropdown-item" onclick="viewDoubleElimination(${
//             bracket.bracket_id
//           })">
//             View Double Elimination
//           </button>
//         </li>
//         ${
//           bracket.is_archived == 0
//             ? `
//               <li>
//                 <button type="button"
//                   class="dropdown-item archive-btn"
//                   data-id="${bracket.bracket_id}"
//                   data-table="brackets"
//                   data-operation="archive"
//                   style="padding: 4px 12px; line-height: 1.2;">
//                   Archive
//                 </button>
//               </li>
//               <li>
//                 <button class="dropdown-item text-danger" onclick="deleteBracket(${bracket.bracket_id})">
//                   Delete
//                 </button>
//               </li>
//             `
//             : ""
//         }
//       `
//       : `
//         <li>
//           <button class="dropdown-item" onclick="viewBracket(${
//             bracket.bracket_id
//           })">
//             View Bracket
//           </button>
//         </li>
//         ${
//           bracket.is_archived == 0
//             ? `
//               <li>
//                 <button type="button"
//                   class="dropdown-item archive-btn"
//                   data-id="${bracket.bracket_id}"
//                   data-table="brackets"
//                   data-operation="archive"
//                   style="padding: 4px 12px; line-height: 1.2;">
//                   Archive
//                 </button>
//               </li>
//               <li>
//                 <button class="dropdown-item text-danger" onclick="deleteBracket(${bracket.bracket_id})">
//                   Delete
//                 </button>
//               </li>
//             `
//             : ""
//         }
//       `
//   }
// </ul>

//                 </div>
//             </td>
//         </tr>

//                             `;
//           tbody.append(row);
//         });
//       } else {
//         Swal.fire({
//           icon: "error",
//           title: "Error",
//           text: response.message || "Failed to load brackets",
//         });
//       }
//     },
//     error: function (xhr, status, error) {
//       console.error("Error loading brackets:", error);
//       Swal.fire({
//         icon: "error",
//         title: "Error",
//         text: "Failed to load brackets. Please try again.",
//       });
//     },
//   });
// }

function applyFilters() {
  const filters = {
    department_id: $("#departmentFilter").val(),
    game_id: $("#gameFilter").val(),
    grade_level: $("#gradeLevelFilter").val(),
  };
  loadBrackets(filters);
}

function viewBracket(bracketId) {
  console.log("View Bracket clicked:", bracketId);

  $("#bracketModalContainer").empty(); // Clear modal content

  fetch("archive-pages/fetch_bracket.php?bracket_id=" + bracketId)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const roundNumbers = Object.keys(data.matches)
          .filter((key) => key !== "third-place" && !isNaN(parseInt(key)))
          .sort((a, b) => parseInt(a) - parseInt(b));

        const totalTeams = data.matches["1"] ? data.matches["1"].length * 2 : 0;

        const teams = [];
        if (data.matches["1"]) {
          data.matches["1"].forEach((match) => {
            teams.push([match.teamA_name || "TBD", match.teamB_name || "TBD"]);
          });
        }

        const results = [];
        let matchesInRound = Math.floor(totalTeams / 2);

        roundNumbers.forEach((roundNum) => {
          const roundResults = [];
          const roundMatches = data.matches[roundNum] || [];

          for (let i = 0; i < matchesInRound; i++) {
            const match = roundMatches[i];
            if (match) {
              if (
                match.status === "Finished" &&
                match.score_teamA != null &&
                match.score_teamB != null
              ) {
                roundResults.push([
                  parseInt(match.score_teamA),
                  parseInt(match.score_teamB),
                ]);
              } else if (match.winning_team_id !== null) {
                roundResults.push(
                  match.winning_team_id === match.teamA_id ? [1, 0] : [0, 1]
                );
              } else if (match.teamA_id === -1 || match.teamB_id === -1) {
                roundResults.push(match.teamA_id === -1 ? [0, 1] : [1, 0]);
              } else {
                roundResults.push([0, 0]);
              }
            } else {
              roundResults.push([0, 0]);
            }
          }

          results.push(roundResults);
          matchesInRound = Math.floor(matchesInRound / 2);
        });

        if (teams.length === 0) {
          $("#bracketModalContainer").html(
            `<div class="alert alert-warning">No teams available for the bracket.</div>`
          );
          return;
        }

        const bracketData = {
          teams: teams,
          results: results,
        };

        $("#bracketModalContainer").bracket({
          teamWidth: 150,
          scoreWidth: 40,
          matchMargin: 50,
          roundMargin: 50,
          init: bracketData,
          decorator: {
            edit: function () {},
            render: function (container, data, score) {
              container.empty();
              if (data === null) container.append("BYE");
              else if (data === "TBD") container.append("TBD");
              else container.append(data);
            },
          },
        });
        const matchCount = bracketData.teams.length;
        const height = Math.max(700, matchCount * 60); // Adjust multiplier if needed
        $("#bracketModalContainer").css("height", `${height}px`);

        $("#bracketModalContainer .jQBracket").css({
          "min-height": "700px",
          "padding-bottom": "50px",
        });

        const modal = new bootstrap.Modal(
          document.getElementById("viewBracketModal")
        );
        modal.show();
        setTimeout(() => {
          $("#bracketModalContainer").trigger("resize");
          window.dispatchEvent(new Event("resize"));
        }, 300);
      } else {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Failed to load bracket: " + data.message,
        });
      }
    })
    .catch((error) => {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Failed to load bracket. Please try again.",
      });
    });
}

// //PDF BRACKET
// function exportBracketToPDF() {
//     Swal.fire({
//         title: 'Exporting...',
//         text: 'Please wait while the bracket is being exported.',
//         allowOutsideClick: false,
//         didOpen: () => {
//             Swal.showLoading(); // ✅ Show loading spinner
//         }
//     });

//     const element = document.getElementById('bracket-container');
//     html2canvas(element, {
//         scale: 2
//     }).then((canvas) => {
//         const imgData = canvas.toDataURL('image/png');
//         const {
//             jsPDF
//         } = window.jspdf;
//         const pdf = new jsPDF('landscape');
//         const imgWidth = 280;
//         const imgHeight = (canvas.height * imgWidth) / canvas.width;
//         pdf.addImage(imgData, 'PNG', 10, 10, imgWidth, imgHeight);
//         pdf.save('bracket.pdf');

//         // ✅ Close loading state and show success toast using SweetAlert
//         Swal.close();
//         Swal.fire({
//             icon: 'success',
//             title: 'Bracket exported successfully!',
//             toast: true,
//             position: 'top',
//             showConfirmButton: false,
//             timer: 3000
//         });
//     }).catch((error) => {
//         // ✅ Close loading state and show error toast using SweetAlert
//         Swal.close();
//         Swal.fire({
//             icon: 'error',
//             title: 'Failed to export bracket.',
//             text: 'Please try again.',
//             toast: true,
//             position: 'top',
//             showConfirmButton: false,
//             timer: 3000
//         });
//         console.error('Error exporting bracket:', error);
//     });
// }

//PDF BRACKET
async function exportBracketToPDF() {
  Swal.fire({
    title: "Exporting...",
    text: "Please wait while the bracket is being exported.",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });

  const element = document.getElementById("bracket-container");
  html2canvas(element, {
    scale: 2,
  })
    .then(async (canvas) => {
      const imgData = canvas.toDataURL("image/png");
      const { jsPDF } = window.jspdf;
      const pdf = new jsPDF("landscape");
      const imgWidth = 280;
      const imgHeight = (canvas.height * imgWidth) / canvas.width;
      pdf.addImage(imgData, "PNG", 10, 10, imgWidth, imgHeight);

      Swal.close();

      try {
        // ✅ Use File System Access API for file picker
        const fileHandle = await window.showSaveFilePicker({
          suggestedName: "bracket.pdf",
          types: [
            {
              description: "PDF Document",
              accept: {
                "application/pdf": [".pdf"],
              },
            },
          ],
        });

        const writable = await fileHandle.createWritable();
        const pdfBlob = pdf.output("blob");
        await writable.write(pdfBlob);
        await writable.close();

        toastr.success("Bracket exported successfully!");
      } catch (error) {
        console.error("Error saving file:", error);
        toastr.error("Failed to export bracket. Please try again.");
      }
    })
    .catch((error) => {
      Swal.close();
      toastr.error("Failed to export bracket. Please try again.");
      console.error("Error exporting bracket:", error);
    });
}

// Load brackets when page loads
$(document).ready(function () {
  loadBrackets();
  // Wrap bracket container with wrapper div
  $("#bracket-container").wrap('<div class="bracket-wrapper"></div>');
});

function loadRoundRobin(bracketId) {
  Swal.fire({
    title: "Loading...",
    text: "Fetching tournament schedule",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });

  fetch("fetch_round_robin.php?bracket_id=" + bracketId)
    .then((response) => response.json())
    .then((data) => {
      Swal.close();

      if (data.success) {
        // Populate tournament info
        $("#tournamentGame").text(data.tournament_info.game_name);
        $("#tournamentDept").text(data.tournament_info.department_name);
        $("#tournamentStatus").text(data.tournament_info.status);
        $("#tournamentTeams").text(data.tournament_info.total_teams);

        // Populate scoring rules inputs
        if (data.scoring_rules) {
          $("#viewWinPoints").val(data.scoring_rules.win_points);
          $("#viewDrawPoints").val(data.scoring_rules.draw_points);
          $("#viewLossPoints").val(data.scoring_rules.loss_points);
          $("#viewBonusPoints").val(data.scoring_rules.bonus_points);
        }

        // Populate standings table
        const standingsBody = $("#standingsTableBody");
        standingsBody.empty();

        let rank = 1;
        data.standings.forEach((team) => {
          const rankEmoji =
            rank === 1 ? "1️⃣" : rank === 2 ? "2️⃣" : rank === 3 ? "3️⃣" : rank;
          const rowClass =
            rank === 1
              ? "table-success"
              : rank === 2
              ? "table-info"
              : rank === 3
              ? "table-warning"
              : "";

          standingsBody.append(`
                        <tr class="${rowClass}">
                            <td>${rankEmoji}</td>
                            <td><strong>${team.team_name}</strong></td>
                            <td>${team.played}</td>
                            <td>${team.wins}</td>
                            <td>${team.draw}</td>
                            <td>${team.losses}</td>
                            <td>${team.bonus_points}</td>
                            <td><strong>${team.total_points}</strong></td>
                            <td>
                                <button class="btn btn-sm btn-success add-bonus" 
                                        data-team-id="${team.team_id}" 
                                        data-bracket-id="${bracketId}">
                                    <i class="fas fa-plus"></i> Add Bonus
                                </button>
                            </td>
                        </tr>
                    `);
          rank++;
        });

        // Add bonus points handler
        $("#standingsTableBody")
          .off("click", ".add-bonus")
          .on("click", ".add-bonus", async function () {
            const teamId = $(this).data("team-id");
            const bracketId = $(this).data("bracket-id");

            const { value: bonusPoints } = await Swal.fire({
              title: "Add Bonus Points",
              input: "number",
              inputLabel: "Enter bonus points",
              inputValue: 0,
              showCancelButton: true,
              inputValidator: (value) => {
                if (!value || isNaN(value) || value < 0) {
                  return "Please enter a valid positive number";
                }
              },
            });

            if (bonusPoints) {
              try {
                const response = await fetch("save_round_robin_points.php", {
                  method: "POST",
                  headers: {
                    "Content-Type": "application/json",
                  },
                  body: JSON.stringify({
                    bracket_id: bracketId,
                    team_id: teamId,
                    bonus_points: parseInt(bonusPoints),
                    action: "add_bonus",
                  }),
                });

                const result = await response.json();

                if (result.success) {
                  Swal.fire({
                    icon: "success",
                    title: "Success",
                    text: "Bonus points added successfully!",
                  }).then(() => {
                    loadRoundRobin(bracketId); // Refresh the standings
                  });
                } else {
                  throw new Error(result.message);
                }
              } catch (error) {
                Swal.fire({
                  icon: "error",
                  title: "Error",
                  text: "Failed to add bonus points: " + error.message,
                });
              }
            }
          });

        // Populate matches table
        const matchesBody = $("#matchesTableBody");
        matchesBody.empty();

        data.matches.forEach((match) => {
          const scores =
            match.status.toLowerCase() === "finished"
              ? `${match.score_teamA || "-"} - ${match.score_teamB || "-"}`
              : "-";

          matchesBody.append(`
                        <tr>
                            <td>Round ${match.round}</td>
                            <td>Match ${match.match_number}</td>
                            <td>${match.teamA_name || "TBD"}</td>
                            <td>${scores}</td>
                            <td>${match.teamB_name || "TBD"}</td>
                            <td>
                                <span class="badge bg-${
                                  match.status.toLowerCase() === "pending"
                                    ? "warning"
                                    : "success"
                                }">
                                    ${match.status}
                                </span>
                            </td>
                        </tr>
                    `);
        });

        // Save points handler
        $("#savePoints")
          .off("click")
          .on("click", async function () {
            try {
              const response = await fetch("save_round_robin_points.php", {
                method: "POST",
                headers: {
                  "Content-Type": "application/json",
                },
                body: JSON.stringify({
                  bracket_id: bracketId,
                  win_points: parseInt($("#viewWinPoints").val()) || 0,
                  draw_points: parseInt($("#viewDrawPoints").val()) || 0,
                  loss_points: parseInt($("#viewLossPoints").val()) || 0,
                  bonus_points: parseInt($("#viewBonusPoints").val()) || 0,
                }),
              });

              const result = await response.json();

              if (result.success) {
                Swal.fire({
                  icon: "success",
                  title: "Success",
                  text: "Scoring points updated successfully!",
                });
              } else {
                throw new Error(result.message);
              }
            } catch (error) {
              Swal.fire({
                icon: "error",
                title: "Error",
                text: "Failed to update scoring points: " + error.message,
              });
            }
          });

        // Show the modal
        const modal = new bootstrap.Modal(
          document.getElementById("viewRoundRobinModal")
        );
        modal.show();
      } else {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: data.message || "Failed to load tournament schedule",
        });
      }
    })
    .catch((error) => {
      Swal.close();
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Failed to load tournament schedule. Error: " + error.message,
      });
    });
}

// Bind the event
$(document).on("click", ".view-round-robin", function () {
  const bracketId = $(this).data("bracket-id");
  loadRoundRobin(bracketId);
});

function deleteBracket(bracketId) {
  Swal.fire({
    title: "Are you sure?",
    text: "This will permanently delete this bracket and all its matches. This action cannot be undone!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Yes, delete it!",
  }).then((result) => {
    if (result.isConfirmed) {
      $.ajax({
        url: "delete_bracket.php",
        method: "POST",
        data: {
          bracket_id: bracketId,
        },
        success: function (response) {
          if (response.success) {
            Swal.fire({
              title: "Success!",
              text: "Bracket has been deleted successfully.",
              icon: "success",
            }).then(() => {
              // Reload the page to show the saved bracket
              window.location.reload();
            });
          } else {
            Swal.fire({
              title: "Error!",
              text: response.message,
              icon: "error",
            });
          }
        },
        error: function () {
          Swal.fire({
            title: "Error!",
            text: "Failed to delete bracket. Please try again.",
            icon: "error",
          });
        },
      });
    }
  });
}
