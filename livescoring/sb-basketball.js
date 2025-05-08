// Game settings
let gameSettings = {
  periodLength: 10,
  numberOfPeriods: 4,
  shotClockDuration: 24,
  maxFouls: 5,
  maxTimeouts: 5,
  scoringUnit: 1, // Added scoringUnit property
  timeoutsPerPeriod: 5, // Added timeoutsPerPeriod property
};

// Game settings and state
let timerRunning = false;
let timeLeft = 600; // 10 minutes in seconds

// Ensure game data is available
if (!window.gameData) {
  console.error("Game data not initialized!");
  window.gameData = {};
}

// Timer variables
let timerInterval;
let shotClockInterval;
let shotClockTime;
let isShotClockRunning = false;
let playerStats = JSON.parse(localStorage.getItem("playerStats") || "{}");

// Initialize the game
function initializeGame() {
  timeLeft = gameSettings.periodLength * 60;
  shotClockTime = gameSettings.shotClockDuration;
  updateDisplays();

  // Initialize settings inputs
  document.getElementById("periodLength").value = gameSettings.periodLength;
  document.getElementById("numberOfPeriods").value =
    gameSettings.numberOfPeriods;
  document.getElementById("shotClockDuration").value =
    gameSettings.shotClockDuration;

  // Initialize timeouts
  document.getElementById("teamATimeouts").textContent =
    gameSettings.maxTimeouts;
  document.getElementById("teamBTimeouts").textContent =
    gameSettings.maxTimeouts;
}

// Update all displays
function updateDisplays() {
  updateGameTimer();
  updateShotClock();
}

// Update game timer display
function updateGameTimer() {
  const minutes = Math.floor(timeLeft / 60);
  const seconds = timeLeft % 60;
  document.getElementById("gameTimer").textContent =
    minutes.toString().padStart(2, "0") +
    ":" +
    seconds.toString().padStart(2, "0");
}

// Update shot clock display
function updateShotClock() {
  document.getElementById("shotClock").textContent = shotClockTime
    .toString()
    .padStart(2, "0");
}

// Score update function
function updateScore(team, change) {
  console.log("Updating score for", team, "by", change);
  let scoreElement = document.getElementById(team + "Score");
  let score = parseInt(scoreElement.textContent);
  score = Math.max(0, score + change);
  scoreElement.textContent = String(score).padStart(2, "0");
  stateManager.save();
  sendUpdate();
}

// Update fouls
function updateFouls(team, change) {
  console.log("Updating fouls for", team, "by", change);
  let foulsElement = document.getElementById(team + "Fouls");
  let fouls = parseInt(foulsElement.textContent);
  fouls = Math.max(0, Math.min(gameSettings.maxFouls, fouls + change));
  foulsElement.textContent = fouls;
  stateManager.save();
  sendUpdate();
}

// Update timeouts
function updateTimeouts(team, change) {
  console.log("Updating timeouts for", team, "by", change);
  let timeoutsElement = document.getElementById(team + "Timeouts");
  let timeouts = parseInt(timeoutsElement.textContent);
  timeouts = Math.max(0, Math.min(gameSettings.maxTimeouts, timeouts + change));
  timeoutsElement.textContent = timeouts;
  stateManager.save();
  sendUpdate();
}

// Period controls
function updatePeriod(change) {
  let periodElement = document.getElementById("periodCounter");
  let period = parseInt(periodElement.textContent);
  period = Math.max(1, Math.min(gameSettings.numberOfPeriods, period + change));
  periodElement.textContent = period;
  stateManager.save();
  sendUpdate();

  if (change > 0) {
    // Reset timers for new period
    resetTimer();
    resetShotClock();
  }
}

// Timer controls
function startTimer() {
  if (!timerRunning && timeLeft > 0) {
    timerRunning = true;

    timerInterval = setInterval(() => {
      if (timeLeft > 0) {
        timeLeft--;
        updateGameTimer();
        stateManager.save();
      } else {
        pauseAll(); // Unified pause
        playBuzzer();
        resetTimer();
        updatePeriod(1);
      }
    }, 1000);

    timerUpdateInterval = setInterval(() => {
      if (timerRunning) updateServerTimer();
    }, 3000);

    if (!isShotClockRunning && shotClockTime > 0) startShotClock(); // Link start
  }
}

function pauseAll() {
  pauseTimer();
  pauseShotClock();
}

// This should be declared globally so you can clear it later
let timerUpdateInterval;

// Function to send current time to the backend
function updateServerTimer() {
  const schedule_id = window.gameData?.schedule_id;
  const period = parseInt(document.getElementById("periodCounter").textContent);
  const timer_status = timerRunning ? "running" : "paused";

  if (!schedule_id) {
    console.warn("Missing schedule_id for timer update");
    return;
  }

  fetch("timer/update_timer.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      schedule_id,
      time_remaining: timeLeft,
      period,
      timer_status,
    }),
  })
    .then((res) => (res.ok ? res.text() : Promise.reject(res.statusText)))
    .then((result) => {
      console.log("Timer update successful:", result);
    })
    .catch((err) => {
      console.error("Timer update failed:", err);
    });
}

// Define global toggle functions
function initPlayerStatsToggle() {
  // Comprehensive element finding
  const playerStatsPanel =
    document.querySelector(".player-stats") ||
    document.getElementById("playerStatsPanel");

  const overlay =
    document.querySelector(".overlay") ||
    document.getElementById("playerStatsOverlay");

  const playerStatsBar =
    document.querySelector(".player-stats-bar") ||
    document.getElementById("playerStatsBar");

  const mobilePlayerStatsButton =
    document.querySelector(".bottom-nav .nav-item:last-child") ||
    document.getElementById("mobilePlayerStatsButton");

  // Extensive logging
  console.group("Player Stats Toggle Initialization");
  console.log("Player Stats Panel:", playerStatsPanel);
  console.log("Overlay:", overlay);
  console.log("Player Stats Bar:", playerStatsBar);
  console.log("Mobile Player Stats Button:", mobilePlayerStatsButton);
  console.groupEnd();

  // Fallback debugging
  if (!playerStatsPanel || !overlay) {
    console.warn("Critical elements missing, running debug");
    findElementsDebug();
  }

  // Define toggle functions
  window.togglePlayerStats = function () {
    console.log("Toggle Player Stats called");

    // Re-find elements in case they were missed initially
    const panel =
      document.querySelector(".player-stats") ||
      document.getElementById("playerStatsPanel");
    const overlayEl =
      document.querySelector(".overlay") ||
      document.getElementById("playerStatsOverlay");

    if (panel && overlayEl) {
      panel.classList.toggle("active");
      overlayEl.classList.toggle("active");

      if (panel.classList.contains("active")) {
        console.log("Player stats panel activated");
        playerStatsManager.loadPlayerStats();
      }
    } else {
      console.error("Player stats panel or overlay not found", {
        panel,
        overlay: overlayEl,
      });
      // Last resort debugging
      findElementsDebug();
    }
  };

  window.closePlayerStats = function () {
    const panel =
      document.querySelector(".player-stats") ||
      document.getElementById("playerStatsPanel");
    const overlayEl =
      document.querySelector(".overlay") ||
      document.getElementById("playerStatsOverlay");

    if (panel && overlayEl) {
      panel.classList.remove("active");
      overlayEl.classList.remove("active");
    }
  };

  // Add event listeners
  function addEventListeners() {
    const statsBar =
      document.querySelector(".player-stats-bar") ||
      document.getElementById("playerStatsBar");
    const mobileButton =
      document.querySelector(".bottom-nav .nav-item:last-child") ||
      document.getElementById("mobilePlayerStatsButton");
    const overlayEl =
      document.querySelector(".overlay") ||
      document.getElementById("playerStatsOverlay");

    if (statsBar) {
      statsBar.addEventListener("click", window.togglePlayerStats);
      console.log("Added click listener to stats bar");
    }

    if (mobileButton) {
      mobileButton.addEventListener("click", window.togglePlayerStats);
      console.log("Added click listener to mobile button");
    }

    if (overlayEl) {
      overlayEl.addEventListener("click", window.closePlayerStats);
      console.log("Added click listener to overlay");
    }
  }

  // Retry mechanism
  function retryInitialization(maxAttempts = 5) {
    let attempts = 0;
    const interval = setInterval(() => {
      attempts++;
      console.log(`Initialization attempt ${attempts}`);

      const panel =
        document.querySelector(".player-stats") ||
        document.getElementById("playerStatsPanel");
      const overlayEl =
        document.querySelector(".overlay") ||
        document.getElementById("playerStatsOverlay");

      if (panel && overlayEl) {
        clearInterval(interval);
        addEventListeners();
      }

      if (attempts >= maxAttempts) {
        clearInterval(interval);
        console.error(
          "Failed to initialize player stats after multiple attempts"
        );
        findElementsDebug();
      }
    }, 500);
  }

  // Initial attempt to add listeners
  addEventListeners();

  // If initial attempt fails, use retry mechanism
  retryInitialization();
}

// Debugging function to log all potential selectors
function findElementsDebug() {
  const selectors = [
    ".player-stats",
    "#playerStatsPanel",
    ".overlay",
    "#playerStatsOverlay",
    ".player-stats-bar",
    "#playerStatsBar",
    ".bottom-nav .nav-item:last-child",
    "#mobilePlayerStatsButton",
  ];

  console.log("Starting comprehensive element search");

  selectors.forEach((selector) => {
    const elements = document.querySelectorAll(selector);
    console.log(`Selector "${selector}":`, {
      found: elements.length,
      elements: Array.from(elements).map((el) => ({
        tagName: el.tagName,
        classes: Array.from(el.classList),
        id: el.id,
        visible: el.offsetParent !== null,
      })),
    });
  });

  // Additional DOM structure logging
  console.log(
    "Full document body structure:",
    document.body.innerHTML.replace(/\s+/g, " ").substring(0, 1000) + "..."
  );
}

// Initialize on DOM load with additional safety
document.addEventListener("DOMContentLoaded", initPlayerStatsToggle);
window.addEventListener("load", initPlayerStatsToggle);

// Player Stats Manager
const playerStatsManager = {
  init: function () {
    // Handle clicking outside the panel
    document.addEventListener("click", function (event) {
      if (
        document.querySelector(".player-stats") &&
        document.querySelector(".player-stats").classList.contains("active") &&
        !document.querySelector(".player-stats").contains(event.target) &&
        !event.target.closest(".player-stats-bar") &&
        !event.target.closest(".nav-item")
      ) {
        window.closePlayerStats();
      }
    });

    this.loadPlayerStats();
  },

  loadPlayerStats: function () {
    const teamAList = utils.getElement("teamAList");
    const teamBList = utils.getElement("teamBList");
    const scheduleId = utils.getElement("schedule_id").value;
    const gameId = utils.getElement("game_id").value;
    const teamAId = utils.getElement("teamA_id").value;

    if (!teamAList || !teamBList) return;

    // Clear existing content and show loading
    teamAList.innerHTML =
      '<li class="list-group-item text-center text-muted">Loading players...</li>';
    teamBList.innerHTML =
      '<li class="list-group-item text-center text-muted">Loading players...</li>';

    // First fetch players
    fetch(`getPlayersByTeams.php?schedule_id=${scheduleId}`)
      .then((res) => {
        if (!res.ok) {
          throw new Error("Failed to fetch players");
        }
        return res.json();
      })
      .then((players) => {
        if (!Array.isArray(players)) {
          throw new Error("Invalid player data received");
        }

        // Clear loading messages
        teamAList.innerHTML = "";
        teamBList.innerHTML = "";

        // Then fetch game stats
        return fetch(`get_game_stats_config.php?game_id=${gameId}`)
          .then((res) => {
            if (!res.ok) {
              throw new Error("Failed to fetch game stats config");
            }
            return res.json();
          })
          .then((stats) => {
            if (!Array.isArray(stats)) {
              throw new Error("Invalid stats configuration received");
            }

            const template = document.getElementById("playerStatTemplate");

            players.forEach((player) => {
              const playerItem = document.createElement("li");
              playerItem.className = "list-group-item";

              // Create player header
              const playerHeader = document.createElement("div");
              playerHeader.className =
                "d-flex justify-content-between align-items-center mb-3";
              playerHeader.innerHTML = `
                                 <div class="player-name fw-bold">${
                                   player.player_name
                                 }</div>
                                 <span class="badge bg-secondary">#${
                                   player.jersey_number || "N/A"
                                 }</span>
                             `;
              playerItem.appendChild(playerHeader);

              // Create stats container
              const statsContainer = document.createElement("div");
              statsContainer.className = "player-stats-container";

              stats.forEach((stat) => {
                const statRow = template.content.cloneNode(true);
                const statName = statRow.querySelector(".stat-name");
                const statValue = statRow.querySelector(".stat-value");
                const incrementBtn = statRow.querySelector(".increment-stat");
                const decrementBtn = statRow.querySelector(".decrement-stat");

                statName.textContent = stat.stat_name;
                statValue.setAttribute("data-player-id", player.player_id);
                statValue.setAttribute("data-stat-id", stat.config_id);

                incrementBtn.onclick = () => {
                  const currentValue = parseInt(statValue.textContent);
                  statValue.textContent = currentValue + 1;
                  this.updatePlayerStat(
                    player.player_id,
                    stat.config_id,
                    currentValue + 1
                  );
                };

                decrementBtn.onclick = () => {
                  const currentValue = parseInt(statValue.textContent);
                  if (currentValue > 0) {
                    statValue.textContent = currentValue - 1;
                    this.updatePlayerStat(
                      player.player_id,
                      stat.config_id,
                      currentValue - 1
                    );
                  }
                };

                statsContainer.appendChild(statRow);
              });

              playerItem.appendChild(statsContainer);

              // Add to appropriate team list
              if (player.team_id === parseInt(teamAId)) {
                teamAList.appendChild(playerItem);
              } else {
                teamBList.appendChild(playerItem);
              }
            });
          });
      })
      .catch((error) => {
        console.error("Error:", error);
        const errorMessage =
          '<li class="list-group-item text-center text-danger">Failed to load players. Please try again.</li>';
        teamAList.innerHTML = errorMessage;
        teamBList.innerHTML = errorMessage;
        utils.showAlert({
          icon: "error",
          title: "Error",
          text: error.message || "Failed to load players",
        });
      });
  },
  updatePlayerStat: function (playerId, statId, value) {
    const scheduleId = utils.getElement("schedule_id").value;
    fetch(`update_player_stat.php?schedule_id=${scheduleId}`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        player_id: playerId,
        stat_id: statId,
        value: value,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.error) {
          console.error("Error updating stat:", data.error);
          utils.showAlert({
            icon: "error",
            title: "Error",
            text: data.error,
          });
        }
      })
      .catch((error) => {
        console.error("Error updating stat:", error);
        utils.showAlert({
          icon: "error",
          title: "Error",
          text: "Failed to update player statistic",
        });
      });
  },
};
function pauseTimer() {
  clearInterval(timerInterval);
  clearInterval(timerUpdateInterval);
  timerRunning = false;
  updateServerTimer();
  if (isShotClockRunning) pauseShotClock(); // Link pause
}

function resetTimer() {
  pauseTimer();
  timeLeft = gameSettings.periodLength * 60;
  updateGameTimer();
}

// function pauseTimer() {
//     clearInterval(timerInterval);
//     timerRunning = false;
// }

// function resetTimer() {
//     pauseTimer();
//     timeLeft = gameSettings.periodLength * 60;
//     updateGameTimer();
// }

// Shot clock controls
function startShotClock() {
  if (!isShotClockRunning && shotClockTime > 0) {
    isShotClockRunning = true;
    shotClockInterval = setInterval(() => {
      if (shotClockTime > 0) {
        shotClockTime--;
        updateShotClock();
      } else {
        pauseAll(); // Unified pause
        playBuzzer();
        resetShotClock();
      }
    }, 1000);

    if (!timerRunning && timeLeft > 0) startTimer(); // Link start
  }
}

// Function to update the shot clock manually (+ or -)
function updateShotClock(value) {
  shotClockTime += value;
  if (shotClockTime < 0) shotClockTime = 0; // Prevent negative values
  updateShotClock(); // Call updateShotClock instead of updateShotClockDisplay
}

// Function to update the shot clock display
function updateShotClock() {
  document.getElementById("shotClock").textContent = shotClockTime
    .toString()
    .padStart(2, "0");
}

function pauseShotClock() {
  clearInterval(shotClockInterval);
  isShotClockRunning = false;
  if (timerRunning) pauseTimer(); // Link pause
}

function resetShotClock() {
  pauseShotClock();
  shotClockTime = gameSettings.shotClockDuration;
  updateShotClock();
}

// Settings modal functions
function openSettings() {
  document.getElementById("settingsModal").style.display = "block";
}

function closeSettings() {
  document.getElementById("settingsModal").style.display = "none";
}

function saveSettings() {
  gameSettings.periodLength = parseInt(
    document.getElementById("periodLength").value
  );
  gameSettings.numberOfPeriods = parseInt(
    document.getElementById("numberOfPeriods").value
  );
  gameSettings.shotClockDuration = parseInt(
    document.getElementById("shotClockDuration").value
  );

  // Reset timers with new settings
  resetTimer();
  resetShotClock();
  closeSettings();
}

// Team name editing function
function editTeamName(team) {
  let teamNameElement = document.querySelector(
    `.team-name[onclick="editTeamName('${team}')"]`
  );
  teamNameElement.contentEditable = "true";
  teamNameElement.focus();
  teamNameElement.addEventListener("blur", function () {
    teamNameElement.contentEditable = "false";
  });
}

// Timer adjustment function
function adjustTimer(seconds) {
  timeLeft = Math.max(0, timeLeft + seconds);
  updateGameTimer();
}

// Buzzer function
function playBuzzer() {
  const buzzer = document.getElementById("buzzerSound");
  buzzer.currentTime = 0;
  buzzer.play();
}

// Live Stream functions
function openLiveStreamSettings() {
  document.getElementById("liveStreamModal").style.display = "block";
}

function closeLiveStreamSettings() {
  document.getElementById("liveStreamModal").style.display = "none";
}

function startStream() {
  const streamKey = document.getElementById("streamKey").value;
  const streamUrl = document.getElementById("streamUrl").value;

  if (!streamKey || !streamUrl) {
    alert("Please enter both Stream Key and Stream URL");
    return;
  }

  // Add your streaming logic here
  alert("Stream started!");
  closeLiveStreamSettings();
}

// Send update function
function sendUpdate() {
  console.log("Sending update to server...");

  // Use window.gameData directly
  const { schedule_id, game_id, teamA_id, teamB_id } = window.gameData;

  console.log("Game IDs from gameData:", {
    schedule_id,
    game_id,
    teamA_id,
    teamB_id,
  });

  // Validate required fields
  if (!schedule_id || !game_id || !teamA_id || !teamB_id) {
    console.error("Missing required IDs in gameData:", window.gameData);
    utils.showAlert({
      icon: "error",
      title: "Error",
      text: "Missing required game information. Please check the console for details.",
    });
    return;
  }

  // Capture all current values
  const teamAScore =
    parseInt(document.getElementById("teamAScore").textContent) || 0;
  const teamBScore =
    parseInt(document.getElementById("teamBScore").textContent) || 0;
  const foulTeamA =
    parseInt(document.getElementById("teamAFouls").textContent) || 0;
  const foulTeamB =
    parseInt(document.getElementById("teamBFouls").textContent) || 0;
  const timeoutTeamA =
    parseInt(document.getElementById("teamATimeouts").textContent) || 0;
  const timeoutTeamB =
    parseInt(document.getElementById("teamBTimeouts").textContent) || 0;
  const currentPeriod =
    parseInt(document.getElementById("periodCounter").textContent) || 1;

  const data = {
    schedule_id,
    game_id,
    teamA_id,
    teamB_id,
    teamA_score: teamAScore,
    teamB_score: teamBScore,
    current_period: currentPeriod,
    time_remaining: timeLeft, // Changed from timeRemaining to timeLeft (total seconds)
    timer_status: timerRunning ? "running" : "paused",
    teamA_fouls: foulTeamA,
    teamB_fouls: foulTeamB,
    teamA_timeouts: timeoutTeamA,
    teamB_timeouts: timeoutTeamB,
  };

  // Log detailed data for debugging
  console.log("Detailed update data:", {
    scores: { teamA: teamAScore, teamB: teamBScore },
    fouls: { teamA: foulTeamA, teamB: foulTeamB },
    timeouts: { teamA: timeoutTeamA, teamB: timeoutTeamB },
    period: currentPeriod,
    timeRemaining: timeLeft,
    timerStatus: data.timer_status,
  });

  fetch("update_score.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(data),
  })
    .then((response) => {
      if (!response.ok) {
        // Try to get error text
        return response.text().then((errorText) => {
          console.error("Server response:", errorText);
          throw new Error(
            `HTTP error! status: ${response.status}, message: ${errorText}`
          );
        });
      }
      return response.json();
    })
    .then((result) => {
      if (result.error) {
        console.error("Server error:", result.error);
        throw new Error(result.error);
      }
      console.log("Update successful:", result);
    })
    .catch((error) => {
      console.error("Error updating score:", error);
      utils.showAlert({
        icon: "error",
        title: "Error",
        text: "Failed to update score: " + error.message,
      });
    });
}

// State preservation functions
const stateManager = {
  save: function () {
    const state = {
      teamAScore: document.getElementById("teamAScore").textContent,
      teamBScore: document.getElementById("teamBScore").textContent,
      teamAFouls: document.getElementById("teamAFouls").textContent,
      teamBFouls: document.getElementById("teamBFouls").textContent,
      teamATimeouts: document.getElementById("teamATimeouts").textContent,
      teamBTimeouts: document.getElementById("teamBTimeouts").textContent,
      periodCounter: document.getElementById("periodCounter").textContent,
      timeLeft: timeLeft,
      timerRunning: timerRunning,
    };
    localStorage.setItem("basketballScoreboardState", JSON.stringify(state));
  },
  restore: function () {
    const savedState = localStorage.getItem("basketballScoreboardState");
    if (savedState) {
      const state = JSON.parse(savedState);

      // Restore scores
      document.getElementById("teamAScore").textContent = state.teamAScore;
      document.getElementById("teamBScore").textContent = state.teamBScore;

      // Restore fouls
      document.getElementById("teamAFouls").textContent = state.teamAFouls;
      document.getElementById("teamBFouls").textContent = state.teamBFouls;

      // Restore timeouts
      document.getElementById("teamATimeouts").textContent =
        state.teamATimeouts;
      document.getElementById("teamBTimeouts").textContent =
        state.teamBTimeouts;

      // Restore period
      document.getElementById("periodCounter").textContent =
        state.periodCounter;

      // Restore timer
      timeLeft = state.timeLeft;
      timerRunning = state.timerRunning;
      updateGameTimer();

      // Optionally, restart timer if it was running
      if (timerRunning) {
        startTimer();
      }
    }
  },
  clear: function () {
    // Clear all state
    localStorage.removeItem("basketballScoreboardState");
    localStorage.removeItem("point_based_teamA_score");
    localStorage.removeItem("point_based_teamB_score");
    localStorage.removeItem("point_based_teamA_timeouts");
    localStorage.removeItem("point_based_teamB_timeouts");
    localStorage.removeItem("point_based_currentSet");
  },
};

// Keyboard shortcuts
document.addEventListener("keydown", function (event) {
  switch (event.key) {
    case " ": // Space bar - Start/Pause main timer
      if (timerRunning) {
        pauseTimer();
      } else {
        startTimer();
      }
      break;
    case "s": // Start/Pause shot clock
      if (isShotClockRunning) {
        pauseShotClock();
      } else {
        startShotClock();
      }
      break;
    case "r": // Reset shot clock
      resetShotClock();
      break;
    case "p": // Next period
      updatePeriod(1);
      break;
    case "b": // Previous period
      updatePeriod(-1);
      break;
    case "1": // Team A +1
      updateScore("teamA", 1);
      break;
    case "2": // Team A -1
      updateScore("teamA", -1);
      break;
    case "3": // Team B +1
      updateScore("teamB", 1);
      break;
    case "4": // Team B -1
      updateScore("teamB", -1);
      break;
    case "f": // Team A foul
      updateFouls("teamA", 1);
      break;
    case "g": // Team B foul
      updateFouls("teamB", 1);
      break;
    case "t": // Team A timeout
      updateTimeouts("teamA", -1);
      break;
    case "y": // Team B timeout
      updateTimeouts("teamB", -1);
      break;
    case "b": // Buzzer
      playBuzzer();
      break;
    case "ArrowLeft": // -1s
      if (event.ctrlKey) {
        adjustTimer(-60); // -1m if Ctrl is pressed
      } else {
        adjustTimer(-1);
      }
      break;
    case "ArrowRight": // +1s
      if (event.ctrlKey) {
        adjustTimer(60); // +1m if Ctrl is pressed
      } else {
        adjustTimer(1);
      }
      break;
  }
});

// Initialize on page load
document.addEventListener("DOMContentLoaded", () => {
  console.log("Scoreboard initialized with game data:", window.gameData);
  initializeGame();
  stateManager.restore();
});

// Initialize end match functionality
document.addEventListener("DOMContentLoaded", function () {
  // Initialize end match button
  const endMatchButton = document.getElementById("end-match-button");
  if (endMatchButton) {
    endMatchButton.addEventListener("click", endMatch);
  }
});

function submitPlayerStats() {
  console.log("submitPlayerStats called");

  const scheduleId = window.gameData.schedule_id;

  const statsToSubmit = Object.entries(playerStats)
    .filter(([key, value]) => value > 0)
    .map(([key, value]) => {
      const [, playerId, statConfigId] = key.match(/player_(\d+)_stat_(\d+)/);
      return {
        player_id: playerId,
        stat_config_id: statConfigId,
        stat_value: value,
      };
    });

  if (statsToSubmit.length === 0) {
    return Promise.resolve(); // <- return resolved promise if nothing to do
  }

  console.log("Submitting stats to server:", statsToSubmit);

  return fetch("save_player_stats.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      schedule_id: scheduleId,
      stats: statsToSubmit,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        localStorage.removeItem("playerStats");
        console.log("Player stats submitted successfully");
      } else {
        console.error(
          "Submission failed:",
          data.message || "Unable to submit player statistics."
        );
      }
    })
    .catch((error) => {
      console.error("Error:", error);
    });
}

function endMatch() {
  // Comprehensive logging and error checking
  console.group("End Match Process");

  // Verify all required elements exist
  const scheduleIdEl = document.getElementById("schedule_id");
  const matchIdEl = document.getElementById("match-id");
  const gameIdEl = document.getElementById("game_id");
  const teamAIdEl = document.getElementById("teamA_id");
  const teamBIdEl = document.getElementById("teamB_id");

  // Log element existence
  console.log("Schedule ID Element:", scheduleIdEl);
  console.log("Match ID Element:", matchIdEl);
  console.log("Game ID Element:", gameIdEl);
  console.log("Team A ID Element:", teamAIdEl);
  console.log("Team B ID Element:", teamBIdEl);

  // Comprehensive null checks with detailed logging
  if (!scheduleIdEl || !matchIdEl || !gameIdEl || !teamAIdEl || !teamBIdEl) {
    console.error("Missing critical elements for end match", {
      scheduleId: !!scheduleIdEl,
      matchId: !!matchIdEl,
      gameId: !!gameIdEl,
      teamAId: !!teamAIdEl,
      teamBId: !!teamBIdEl,
    });

    // Fallback to global gameData if available
    if (window.gameData) {
      console.warn("Falling back to window.gameData", window.gameData);
      return processEndMatch(window.gameData);
    }

    utils.showAlert({
      icon: "error",
      title: "Error",
      text: "Unable to retrieve match information. Please refresh the page.",
    });
    return Promise.reject(new Error("Missing match information"));
  }

  // Collect match data
  const matchData = {
    schedule_id: scheduleIdEl.value,
    match_id: matchIdEl.value,
    game_id: gameIdEl.value,
    teamA_id: teamAIdEl.value,
    teamB_id: teamBIdEl.value,
    teamA_score: document.getElementById("teamAScore").textContent,
    teamB_score: document.getElementById("teamBScore").textContent,
    current_period: document.getElementById("periodCounter").textContent,
    time_remaining: document.getElementById("gameTimer").textContent,
  };

  const periodVal = document.getElementById("periodCounter").textContent;
  const teamAScore = parseInt(
    document.getElementById("teamAScore").textContent
  );
  const teamBScore = parseInt(
    document.getElementById("teamBScore").textContent
  );

  if (parseInt(periodVal) === 4 && teamAScore === teamBScore) {
    document.getElementById("periodCounter").textContent = "OT";
    matchData.current_period = 5; // For backend
    timeLeft = 5 * 60; // 5-minute OT
    pauseTimer();
    updateGameTimer();
    sendUpdate();

    Swal.fire({
      title: "Overtime Started",
      text: "Scores were tied. Overtime has begun.",
      icon: "info",
    });
    return; // Stop match from ending now
  }

  console.log("Match Data for End Match:", matchData);
  console.groupEnd();

  return processEndMatch(matchData);
}

function processEndMatch(matchData) {
  return new Promise((resolve, reject) => {
    // First fetch the bracket type
    fetch("helper/get_bracket_type.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ schedule_id: matchData.schedule_id }),
    })
      .then((response) => response.json())
      .then((bracketData) => {
        if (!bracketData.success) {
          throw new Error(bracketData.error || "Failed to get bracket type");
        }

        // Determine which endpoint to use based on bracket type
        const endpoint =
          bracketData.bracket_type === "round_robin"
            ? "process_round_robin/end_match_points.php"
            : "process_end_match.php";

        console.log("Using endpoint:", endpoint);

        // Continue with match end confirmation
        Swal.fire({
          title: "End Match?",
          text: "Are you sure you want to end the match?",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#3085d6",
          cancelButtonColor: "#d33",
          confirmButtonText: "Yes, End Match",
        }).then((result) => {
          if (result.isConfirmed) {
            // 🔁 Inline submitPlayerStats logic here
            const localStats = JSON.parse(
              localStorage.getItem("playerStats") || "{}"
            );
            const statsToSubmit = Object.entries(localStats)
              .filter(([key, value]) => value > 0)
              .map(([key, value]) => {
                const [, playerId, statConfigId] = key.match(
                  /player_(\d+)_stat_(\d+)/
                );
                return {
                  player_id: playerId,
                  stat_config_id: statConfigId,
                  stat_value: value,
                };
              });

            let submitStatsPromise = Promise.resolve();

            if (statsToSubmit.length > 0) {
              submitStatsPromise = fetch("save_player_stats.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                  schedule_id: matchData.schedule_id,
                  stats: statsToSubmit,
                }),
              })
                .then((res) => res.json())
                .then((data) => {
                  if (data.success) {
                    localStorage.removeItem("playerStats");
                    console.log("✅ Player stats submitted");
                  } else {
                    console.warn(
                      "⚠️ Player stats submission failed:",
                      data.message
                    );
                  }
                })
                .catch((err) => {
                  console.error("❌ Error submitting player stats:", err);
                });
            }

            // 📦 Proceed with match end after submitting stats
            submitStatsPromise.then(() => {
              fetch(endpoint, {
                method: "POST",
                headers: {
                  "Content-Type": "application/json",
                },
                body: JSON.stringify({
                  schedule_id: matchData.schedule_id,
                }),
              })
                .then((response) => {
                  if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                  }
                  return response.json();
                })
                .then((response) => {
                  console.log("End Match Response:", response);

                  if (response.success) {
                    Swal.fire({
                      title: "Match Ended!",
                      text: "The match has concluded successfully.",
                      icon: "success",
                      showCancelButton: true,
                      confirmButtonText: "View Summary",
                      cancelButtonText: "Back to Matches",
                    }).then((result) => {
                      // Clear state before redirecting
                      stateManager.clear();
                      localStorage.removeItem("point_based_teamA_score");
                      localStorage.removeItem("point_based_teamB_score");
                      localStorage.removeItem("point_based_teamA_timeouts");
                      localStorage.removeItem("point_based_teamB_timeouts");
                      localStorage.removeItem("point_based_currentSet");

                      if (result.isConfirmed) {
                        const matchId = response.match_id || matchData.match_id;
                        window.location.href = `match_summary.php?match_id=${matchId}&status=${response.status}`;
                      } else {
                        window.location.href = "match_list.php";
                      }
                    });
                    resolve(response);
                  } else {
                    if (response.overtime_required) {
                      Swal.fire({
                        title: "Overtime Required",
                        text: response.error,
                        icon: "info",
                        showCancelButton: true,
                        confirmButtonText: "Start Overtime",
                        cancelButtonText: "Cancel",
                      }).then((result) => {
                        if (result.isConfirmed) {
                          document.getElementById("periodCounter").textContent =
                            "OT";
                          timeLeft = 5 * 60; // 5 minutes
                          pauseTimer();
                          sendUpdate();
                        }
                      });
                    } else {
                      throw new Error(response.error || "Failed to end match");
                    }
                  }
                })
                .catch((error) => {
                  console.error("End Match Error:", error);
                  Swal.fire({
                    title: "Error",
                    text:
                      error.message || "Failed to end match. Please try again.",
                    icon: "error",
                  });
                  reject(error);
                });
            });
          } else {
            reject(new Error("Match end cancelled"));
          }
        });
      })
      .catch((error) => {
        console.error("Error getting bracket type:", error);
        Swal.fire({
          title: "Error",
          text: "Failed to determine bracket type. Please try again.",
          icon: "error",
        });
        reject(error);
      });
  });
}
