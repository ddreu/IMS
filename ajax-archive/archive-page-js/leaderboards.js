
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const department = urlParams.get('department_id');
        const gradeLevel = urlParams.get('grade_level') || '';
        const game = urlParams.get('game_id') || '';
        const rankingsDiv = document.getElementById('rankingsTable');

        if (!department) {
            rankingsDiv.innerHTML = '<p class="text-center text-muted">Missing department_id in URL.</p>';
            return;
        }

        fetch(`../rankings/fetch_rankings.php?department_id=${department}&grade_level=${gradeLevel}&game_id=${game}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    rankingsDiv.innerHTML = `<p class="text-center text-muted">${data.error}</p>`;
                    return;
                }

                if (data.length === 0) {
                    rankingsDiv.innerHTML = '<p class="text-center text-muted">No rankings available.</p>';
                    return;
                }

                let tableHtml = `
                        <table id="rankTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th class="text-center">Rank</th>
                                    <th>Team</th>`;

                if (data[0].is_points) {
                    tableHtml += `<th>Points</th>`;
                } else {
                    tableHtml += `
                            <th>Wins</th>
                            <th>Losses</th>
                            <th>Win Rate</th>`;
                }

                tableHtml += `</tr></thead><tbody>`;

                data.forEach((team, index) => {
                    const rowClass = index === 0 ? 'table-gold' :
                        index === 1 ? 'table-silver' :
                        index === 2 ? 'table-bronze' : '';
                    let rankDisplay;
                    if (index === 0) rankDisplay = '<i class="fas fa-trophy" style="color: #FFD700;"></i>';
                    else if (index === 1) rankDisplay = '<i class="fas fa-medal" style="color: #C0C0C0;"></i>';
                    else if (index === 2) rankDisplay = '<i class="fas fa-medal" style="color: #CD7F32;"></i>';
                    else rankDisplay = index + 1;

                    tableHtml += `<tr class="${rowClass}">
                            <td class="text-center">${rankDisplay}</td>
                            <td>${team.team_name}</td>`;

                    if (team.is_points) {
                        tableHtml += `<td>${team.wins}</td>`;
                    } else {
                        const winRate = team.total_matches > 0 ?
                            ((team.wins / team.total_matches) * 100).toFixed(1) : '0.0';
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
            .catch(error => {
                console.error('Error loading rankings:', error);
                rankingsDiv.innerHTML = '<p class="text-center text-danger">Error loading rankings. Please try again.</p>';
            });
    });
