let currentView = "team"; // default view is team

document.addEventListener("DOMContentLoaded", function () {
  // Function to load grade levels based on department

  // Function to load rankings
  function loadRankings() {
    const urlParams = new URLSearchParams(window.location.search);
    const school = urlParams.get("school_id") || "";
    const department = urlParams.get("department_id") || "";
    // const gradeLevel = urlParams.get("course_id") || "";
    const gradeLevel =
      urlParams.get("grade_level") || urlParams.get("course_id") || "";

    const game = urlParams.get("game_id") || "";

    const rankingsDiv = document.getElementById("rankingsTable");

    if (!rankingsDiv) return;

    if (!department) {
      rankingsDiv.innerHTML =
        '<p class="text-center text-muted">Please select a department to view rankings.</p>';
      return;
    }

    if (currentView === "player") {
      fetch(
        `process/fetch_archived_player_rankings.php?department_id=${department}&grade_level=${gradeLevel}&game_id=${game}&school_id=${school}`
      )
        .then((response) => response.json())
        .then((data) => {
          if (data.error) {
            rankingsDiv.innerHTML = `<p class="text-center text-muted">${data.error}</p>`;
            return;
          }

          if (
            !data.players ||
            data.players.length === 0 ||
            !data.stat_columns ||
            data.stat_columns.length === 0
          ) {
            rankingsDiv.innerHTML = `
<div class="text-center py-5">
<i class="fas fa-frown fa-3x mb-3" style="color: #ccc;"></i>
<h5 class="text-muted">No player stats available</h5>
<p class="text-muted">No stats were found for the selected filters. Please try again later.</p>
</div>`;
            return;
          }

          let tableHtml = `
        <table id="rankTable" class="table table-striped">
            <thead>
                <tr>
                    <th class="text-center">Rank</th>
                    <th>Player</th>`;

          // Dynamically add stat columns
          data.stat_columns.forEach((stat) => {
            tableHtml += `<th>${stat}</th>`;
          });

          tableHtml += `</tr></thead><tbody>`;

          data.players.forEach((player, index) => {
            const rowClass =
              index === 0
                ? "table-gold"
                : index === 1
                ? "table-silver"
                : index === 2
                ? "table-bronze"
                : "";

            let rankDisplay =
              index === 0
                ? '<i class="fas fa-trophy" style="color: #FFD700;"></i>'
                : index === 1
                ? '<i class="fas fa-medal" style="color: #C0C0C0;"></i>'
                : index === 2
                ? '<i class="fas fa-medal" style="color: #CD7F32;"></i>'
                : index + 1;

            tableHtml += `<tr class="${rowClass}">
            <td class="text-center">${rankDisplay}</td>
            <td>${player.player_name}</td>`;

            // Dynamically fill stat values
            data.stat_columns.forEach((stat) => {
              tableHtml += `<td>${player.stats[stat] ?? 0}</td>`;
            });

            tableHtml += `</tr>`;
          });

          tableHtml += `</tbody></table>`;
          rankingsDiv.innerHTML = tableHtml;
        })
        .catch((error) => {
          console.error("Error loading player rankings:", error);
          rankingsDiv.innerHTML =
            '<p class="text-center text-danger">Error loading player rankings. Please try again.</p>';
        });
    } else {
      // ORIGINAL TEAM RANKINGS (your full working code stays here)
      fetch(
        `process/fetch_archived_rankings.php?department_id=${department}&grade_level=${gradeLevel}&game_id=${game}&school_id=${school}`
      )
        .then((response) => response.json())
        .then((data) => {
          if (data.error) {
            rankingsDiv.innerHTML = `<p class="text-center text-muted">${data.error}</p>`;
            return;
          }
          if (data.length === 0) {
            rankingsDiv.innerHTML =
              '<p class="text-center text-muted">No rankings available for the selected filters.</p>';
            return;
          }

          let tableHtml = `
            <table id="rankTable" class="table table-striped">
                <thead>
                    <tr>
                        <th class="text-center">Rank</th>
                        <th>Team</th>`;

          if (data[0].is_points) {
            tableHtml += `
                <th>Points</th>
                <th><i class="fas fa-medal" style="color: #FFD700;"></i> Gold</th>
                <th><i class="fas fa-medal" style="color: #C0C0C0;"></i> Silver</th>
                <th><i class="fas fa-medal" style="color: #CD7F32;"></i> Bronze</th>`;
          } else {
            tableHtml += `
                <th>Wins</th>
                <th>Losses</th>
                <th>Win Rate</th>`;
          }

          tableHtml += `</tr></thead><tbody>`;

          data.forEach((team, index) => {
            const rowClass =
              index === 0
                ? "table-gold"
                : index === 1
                ? "table-silver"
                : index === 2
                ? "table-bronze"
                : "";

            let rankDisplay =
              index === 0
                ? '<i class="fas fa-trophy" style="color: #FFD700;"></i>'
                : index === 1
                ? '<i class="fas fa-medal" style="color: #C0C0C0;"></i>'
                : index === 2
                ? '<i class="fas fa-medal" style="color: #CD7F32;"></i>'
                : index + 1;

            tableHtml += `
                <tr class="${rowClass}">
                    <td class="text-center">${rankDisplay}</td>
                    <td>${team.team_name}</td>`;

            if (team.is_points) {
              tableHtml += `
                    <td>${team.wins}</td>
                    <td>${team.gold}</td>
                    <td>${team.silver}</td>
                    <td>${team.bronze}</td>`;
            } else {
              const winRate =
                team.total_matches > 0
                  ? ((team.wins / team.total_matches) * 100).toFixed(1)
                  : "0.0";
              tableHtml += `
                    <td>${team.wins}</td>
                    <td>${team.losses}</td>
                    <td>${winRate}%</td>`;
            }

            tableHtml += `</tr>`;
          });

          tableHtml += `</tbody></table>`;
          rankingsDiv.innerHTML = tableHtml;
        })
        .catch((error) => {
          console.error("Error loading team rankings:", error);
          rankingsDiv.innerHTML =
            '<p class="text-center text-danger">Error loading rankings. Please try again.</p>';
        });
    }
  }

  // Add event listeners with null checks

  const toggleViewBtn = document.getElementById("toggleViewBtn");
  const toggleViewLabel = document.getElementById("toggleViewLabel");

  if (toggleViewBtn) {
    toggleViewBtn.addEventListener("change", function () {
      currentView = this.checked ? "player" : "team";
      toggleViewLabel.textContent = this.checked
        ? "Show Team Rankings"
        : "Show Player Rankings";
      loadRankings();
    });
  }

  loadRankings();
});
