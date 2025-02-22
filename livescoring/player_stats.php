<!-- Player Stats Panel -->
<div class="player-stats">
    <div class="player-stats-header">
        <div class="handle"></div>
        <h4 class="mb-0">Player Statistics</h4>
    </div>
    <div class="player-stats-content">
        <div class="row">
            <div class="col-md-12">
                <table class="table table-bordered" style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 50%;"><?= htmlspecialchars($match['teamA_name']); ?></th>
                            <th style="width: 50%;"><?= htmlspecialchars($match['teamB_name']); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <ul class="list-group" id="teamAList"></ul>
                            </td>
                            <td>
                                <ul class="list-group" id="teamBList"></ul>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<template id="playerStatTemplate">
    <div class="player-stat-row mb-2 d-flex justify-content-between align-items-center">
        <div class="stat-name fw-bold"></div>
        <div class="stat-controls d-flex align-items-center">
            <button class="btn btn-sm btn-outline-danger decrement-stat p-1">
                <i class="fas fa-minus"></i>
            </button>
            <span class="stat-value mx-2">0</span>
            <button class="btn btn-sm btn-outline-success increment-stat p-1">
                <i class="fas fa-plus"></i>
            </button>
        </div>
    </div>
</template>



<script>
    document.addEventListener('DOMContentLoaded', function() {
        const gameId = <?= $game_id = $_SESSION['game_id'] ?>;
        let gameStats = [];
        let playerStats = {};

        // Fetch game stats configuration
        fetch(`get_game_stats_config.php?game_id=${gameId}`)
            .then(response => response.json())
            .then(stats => {
                gameStats = stats;
                initializePlayerStats();
            })
            .catch(error => console.error('Error fetching game stats:', error));

        function initializePlayerStats() {
            const teamAList = document.getElementById('teamAList');
            const teamBList = document.getElementById('teamBList');

            // Clear existing content
            teamAList.innerHTML = '';
            teamBList.innerHTML = '';

            // Initialize stats for each player
            document.querySelectorAll('.player-item').forEach(playerItem => {
                const playerId = playerItem.dataset.playerId;
                const playerName = playerItem.dataset.playerName;
                const teamId = playerItem.dataset.teamId;

                const playerStatContainer = document.createElement('li');
                playerStatContainer.className = 'list-group-item';
                playerStatContainer.innerHTML = `<div class="player-name mb-2 fw-bold">${playerName}</div>`;

                // Create stat controls for each stat type
                gameStats.forEach(stat => {
                    const statKey = `player_${playerId}_stat_${stat.config_id}`;
                    if (!playerStats[statKey]) {
                        playerStats[statKey] = 0;
                    }

                    const template = document.getElementById('playerStatTemplate');
                    const statRow = template.content.cloneNode(true);

                    statRow.querySelector('.stat-name').textContent = stat.stat_name;
                    statRow.querySelector('.stat-value').textContent = playerStats[statKey];

                    // Add event listeners for increment/decrement
                    const incrementBtn = statRow.querySelector('.increment-stat');
                    const decrementBtn = statRow.querySelector('.decrement-stat');
                    const statValue = statRow.querySelector('.stat-value');

                    incrementBtn.addEventListener('click', () => {
                        playerStats[statKey]++;
                        statValue.textContent = playerStats[statKey];
                        updatePlayerStat(playerId, stat.config_id, playerStats[statKey]);
                    });

                    decrementBtn.addEventListener('click', () => {
                        if (playerStats[statKey] > 0) {
                            playerStats[statKey]--;
                            statValue.textContent = playerStats[statKey];
                            updatePlayerStat(playerId, stat.config_id, playerStats[statKey]);
                        }
                    });

                    playerStatContainer.appendChild(statRow);
                });

                // Add to appropriate team list
                if (teamId === <?= $teamA_id = $_GET['teamA_id'] ?>) {
                    teamAList.appendChild(playerStatContainer);
                } else {
                    teamBList.appendChild(playerStatContainer);
                }
            });
        }

        function updatePlayerStat(playerId, statId, value) {
            // TODO: Implement the API call to update the player's stat in the database
            console.log(`Updating stat for player ${playerId}, stat ${statId} to ${value}`);
        }

        // Handle panel sliding
        const playerStatsPanel = document.querySelector('.player-stats');
        const handle = playerStatsPanel.querySelector('.handle');
        let isDragging = false;
        let startY = 0;
        let startTransform = 0;

        handle.addEventListener('mousedown', startDragging);
        document.addEventListener('mousemove', drag);
        document.addEventListener('mouseup', stopDragging);

        handle.addEventListener('touchstart', e => {
            startDragging(e.touches[0]);
        });
        document.addEventListener('touchmove', e => {
            drag(e.touches[0]);
        });
        document.addEventListener('touchend', stopDragging);

        function startDragging(e) {
            isDragging = true;
            startY = e.clientY;
            startTransform = getCurrentTransform();
            playerStatsPanel.style.transition = 'none';
        }

        function drag(e) {
            if (!isDragging) return;

            const delta = startY - e.clientY;
            const newTransform = Math.min(0, Math.max(-window.innerHeight + 60, startTransform + delta));
            playerStatsPanel.style.transform = `translateY(${newTransform}px)`;
        }

        function stopDragging() {
            if (!isDragging) return;

            isDragging = false;
            playerStatsPanel.style.transition = 'transform 0.3s ease';
            const currentTransform = getCurrentTransform();

            if (currentTransform > -window.innerHeight / 2) {
                playerStatsPanel.style.transform = 'translateY(0)';
            } else {
                playerStatsPanel.style.transform = `translateY(${-window.innerHeight + 60}px)`;
            }
        }

        function getCurrentTransform() {
            const transform = window.getComputedStyle(playerStatsPanel).transform;
            const matrix = new DOMMatrix(transform);
            return matrix.m42;
        }
    });
</script>