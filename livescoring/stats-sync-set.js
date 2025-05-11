function getUrlParams() {
  const params = {};
  window.location.search
    .substring(1)
    .split("&")
    .forEach((param) => {
      const [key, value] = param.split("=");
      params[key] = decodeURIComponent(value);
    });
  return params;
}

document.addEventListener("DOMContentLoaded", () => {
  const params = getUrlParams();
  const scheduleId = params.schedule_id;

  if (!scheduleId) {
    console.warn("❌ No schedule_id found in URL.");
    return;
  }

  //   const statKey = `team_${teamId}_player_${playerId}_stat_${statConfigId}`;
  const statsKey = `playerStats_${scheduleId}`;

  const metaKey = `playerStatsMeta_${scheduleId}`;

  const stats = JSON.parse(localStorage.getItem(statsKey) || "{}");
  const meta = JSON.parse(localStorage.getItem(metaKey) || "{}");

  console.log(`📦 *Loaded stats from:* ${statsKey}`);
  console.table(stats);

  console.log(`📦 *Loaded meta from:* ${metaKey}`);
  console.table(meta);

  const groupedByTeam = {};
  Object.entries(stats).forEach(([key, value]) => {
    const match = key.match(/team_(\d+)_player_(\d+)_stat_(\d+)/); // ✅ Correct pattern
    if (!match) return;

    const [, teamId, playerId, statId] = match;

    const statName = meta[statId]?.stat_name || "Unknown Stat";

    if (!groupedByTeam[teamId]) {
      groupedByTeam[teamId] = [];
    }

    groupedByTeam[teamId].push({
      team_id: teamId,
      player_id: playerId,
      stat_id: statId,
      stat_name: statName,
      value: value,
    });
  });

  console.log("📊 *Grouped stats by team:*");
  console.table(groupedByTeam);

  // Optional: log each team's stats
  Object.entries(groupedByTeam).forEach(([teamId, stats]) => {
    console.log(`📁 Team ${teamId} Stats:`);
    stats.forEach((s) => {
      console.log(
        `🔹 Player ${s.player_id} - ${s.stat_name} (ID: ${s.stat_id}): ${s.value}`
      );
    });
  });

  const rawScores = aggregateTeamScores(groupedByTeam);

  // Map by labels for scoreboard use
  const teamScores = {
    teamA: rawScores[window.gameData.teamA_id] || 0,
    teamB: rawScores[window.gameData.teamB_id] || 0,
  };

  // ⬇️ Insert this block here
  console.log("📊 *Raw Scores by Team ID:*");
  console.table(rawScores);

  console.log("📊 *Synced Scores by Team Label:*");
  console.log(
    `➡️ *Team A ID: ${window.gameData.teamA_id}* → Score: ${teamScores.teamA}`
  );
  console.log(
    `➡️ *Team B ID: ${window.gameData.teamB_id}* → Score: ${teamScores.teamB}`
  );

  // Continue with original code
  console.log("🎯 *Mapped Scores for UI sync:*", teamScores);
  window.syncedTeamScores = teamScores;
  console.log("🎯 *Aggregated Team Scores:*");
  console.table(teamScores);

  console.log("🎯 *Mapped Scores for UI sync:*", teamScores);

  // Optional: expose globally for scoreboard to pick up
  window.syncedTeamScores = teamScores;
  console.log("🎯 *Aggregated Team Scores:*");
  console.table(teamScores);

  // Log each team's score clearly
  console.log("📊 *Synced Total Scores by Team:*");
  Object.entries(teamScores).forEach(([teamId, score]) => {
    console.log(`➡️ Team ID: ${teamId} | Synced Total Score by Stat: ${score}`);
  });

  window.globalPlayerStats = Object.entries(stats)
    .filter(([_, value]) => value > 0)
    .map(([key, value]) => {
      const match = key.match(/team_(\d+)_player_(\d+)_stat_(\d+)/);
      if (!match) return null;
      const [, , playerId, statConfigId] = match;

      return {
        player_id: parseInt(playerId),
        stat_config_id: parseInt(statConfigId),
        stat_value: parseInt(value),
      };
    })
    .filter(Boolean);

  console.log("🌐 window.globalPlayerStats set:", window.globalPlayerStats);
});

function aggregateTeamScores(groupedStats) {
  const totals = {};

  Object.entries(groupedStats).forEach(([teamId, statEntries]) => {
    totals[teamId] = 0;

    statEntries.forEach(({ stat_name, value }) => {
      const formatted = stat_name
        .trim()
        .toLowerCase()
        .replace(/^['"]|['"]$/g, "")
        .replace(/s$/, "");
      console.log(
        `🔍 Checking stat: "${stat_name}" → formatted: "${formatted}"`
      );

      if (isScoringStat(formatted)) {
        totals[teamId] += parseInt(value) || 0;
        console.log(`✅ Included in score: ${formatted} → ${value}`);
      } else {
        console.log(`❌ Not a scoring stat: ${formatted}`);
      }
    });
  });

  console.log("📊 *Team Scores Computed Internally:*");
  Object.entries(totals).forEach(([teamId, score]) => {
    console.log(`➡️ Team ID: ${teamId} | Synced Total Score by Stat: ${score}`);
  });

  return totals;
}

function isScoringStat(formattedName) {
  const scoringKeywords = [
    "point",
    "score",
    "goal",
    "basket",
    "touchdown",
    "kill",
    "spike",
    "ace",
    "made",
    "run",
  ];

  return scoringKeywords.some((keyword) => formattedName.includes(keyword));
}
let lastStatsSnapshot = localStorage.getItem(
  `playerStats_${getUrlParams().schedule_id}`
);

// Run check every second
setInterval(() => {
  const currentStats = localStorage.getItem(
    `playerStats_${getUrlParams().schedule_id}`
  );

  if (currentStats !== lastStatsSnapshot) {
    console.log("🔄 Detected change in player stats, re-syncing scores...");
    lastStatsSnapshot = currentStats;

    const stats = JSON.parse(currentStats || "{}");
    const meta = JSON.parse(
      localStorage.getItem(`playerStatsMeta_${getUrlParams().schedule_id}`) ||
        "{}"
    );

    const groupedByTeam = {};
    Object.entries(stats).forEach(([key, value]) => {
      const match = key.match(/team_(\d+)_player_(\d+)_stat_(\d+)/);
      if (!match) return;
      const [, teamId, playerId, statId] = match;
      const statName = meta[statId]?.stat_name || "Unknown Stat";

      if (!groupedByTeam[teamId]) groupedByTeam[teamId] = [];

      groupedByTeam[teamId].push({
        team_id: teamId,
        player_id: playerId,
        stat_id: statId,
        stat_name: statName,
        value,
      });
    });

    const rawScores = aggregateTeamScores(groupedByTeam);

    window.syncedTeamScores = {
      teamA: rawScores[window.gameData.teamA_id] || 0,
      teamB: rawScores[window.gameData.teamB_id] || 0,
    };

    console.log("📢 *Auto-updated syncedTeamScores:*", window.syncedTeamScores);

    // Push to scoreboard immediately
    if (typeof syncTeamScoresIfEnabled === "function") {
      syncTeamScoresIfEnabled();
    }
  }
}, 1000);
