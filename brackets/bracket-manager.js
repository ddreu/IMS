class BracketManager {
  constructor(options) {
    this.teams = [];
    this.matches = [];
    this.rounds = 0;
    this.gameId = options.gameId;
    this.departmentId = options.departmentId;
    this.gradeLevel = options.gradeLevel;
    this.bracketData = null;
  }
  async fetchTeams() {
    try {
      console.log("Fetching teams with params:", {
        department_id: this.departmentId,
        game_id: this.gameId,
        grade_level: this.gradeLevel,
      });

      const response = await $.ajax({
        url: "fetch_teams.php",
        method: "GET",
        data: {
          department_id: this.departmentId,
          game_id: this.gameId,
          grade_level: this.gradeLevel,
        },
      });

      console.log("Fetch teams response:", response);

      if (response.success) {
        // If we have teams with valid average heights, use them for seeding
        if (
          response.valid_teams_with_height &&
          response.valid_teams_with_height.length > 0
        ) {
          // Sort teams by average height in descending order (highest first)
          const sortedTeams = response.valid_teams_with_height.sort((a, b) => b.avg_height - a.avg_height);
          console.log("Teams sorted by height:", sortedTeams);
          this.teams = sortedTeams;
        } else {
          // If no teams with average heights, fallback to random seeding
          console.log("Using random seeding due to missing or invalid height data.");
          this.teams = this.shuffleTeams(response.teams);
        }
        return this.teams;
      } else {
        console.log("Using random seeding due to error or missing data.");
        this.teams = this.shuffleTeams(response.teams);
        return this.teams;
      }
    } catch (error) {
      console.error("Error fetching teams:", error);
      throw new Error(`Failed to fetch teams: ${error.message}`);
    }
  }

  shuffleTeams(teams) {
    const shuffled = [...teams];
    for (let i = shuffled.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
    }
    return shuffled;
  }

  calculateRounds() {
    const teamCount = this.teams.length;
    if (teamCount < 2) {
      throw new Error("At least 2 teams are required to create a bracket");
    }

    this.rounds = Math.ceil(Math.log2(teamCount));
    const totalSlots = Math.pow(2, this.rounds);
    this.byes = totalSlots - teamCount;

    console.log("Round calculation:", {
      teamCount,
      rounds: this.rounds,
      totalSlots,
      byes: this.byes,
    });

    return { rounds: this.rounds, byes: this.byes };
  }

  getByePositions(totalTeams, totalSlots) {
    const byes = totalSlots - totalTeams;
    const positions = [];

    // Calculate positions where BYEs should be placed
    // BYEs are placed against the lowest seeds
    for (let i = totalSlots; i > totalSlots - byes; i--) {
      positions.push(i);
    }
    return positions;
  }

  getSeedPosition(seed, totalSlots) {
    // Standard tournament seeding pattern
    if (seed % 2 === 0) {
      return totalSlots - seed + 1;
    } else {
      return seed;
    }
  }

  generateUniqueId() {
    return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
  }

  generateBracketStructure() {
    const teams = [...this.teams];
    const matches = [];
    let currentMatchNumber = 1;

    // Calculate total slots needed (power of 2)
    const totalSlots = Math.pow(2, Math.ceil(Math.log2(teams.length)));
    const byes = totalSlots - teams.length;

    console.log("Round calculation:", {
      teamCount: teams.length,
      rounds: Math.ceil(Math.log2(teams.length)),
      totalSlots: totalSlots,
      byes: byes,
    });

    // Create seeded positions for a proper bracket
    let seededPositions = [];
    for (let i = 0; i < totalSlots; i++) {
      seededPositions[i] = null;
    }

    // Function to get proper seeded position in the bracket
    // This follows standard tournament seeding where highest seed faces lowest seed
    function getSeedPosition(seed, totalPositions) {
      // For a power of 2 bracket size N, if seed=k, position = 2k-1
      // For seeds above N/2, position = 2(N-k)+2
      const N = totalPositions;
      const k = seed;
      
      if (k <= N/2) {
        return 2*k - 2;
      } else {
        return 2*(N-k) + 1;
      }
    }

    // Place teams in their seeded positions
    teams.forEach((team, index) => {
      const seedNumber = index + 1; // Seed number is 1-based
      const position = getSeedPosition(seedNumber, totalSlots);
      seededPositions[position] = team;
    });

    // Generate first round matches with proper seeding
    for (let i = 0; i < totalSlots; i += 2) {
      const teamA = seededPositions[i];
      const teamB = seededPositions[i + 1];

      matches.push({
        match_identifier: `M${this.gameId}-D${this.departmentId}-${
          this.gradeLevel || "ALL"
        }-R1-${currentMatchNumber}-${this.generateUniqueId()}`,
        round: 1,
        match_number: currentMatchNumber,
        next_match_number: Math.floor(currentMatchNumber / 2) + totalSlots / 2,
        teamA_id: teamA ? teamA.team_id : -1, // -1 for BYE
        teamB_id: teamB ? teamB.team_id : -1,
        match_type: teams.length === 4 ? "semifinal" : "regular",
        status: !teamA || !teamB ? "Finished" : "Pending",
      });
      currentMatchNumber++;
    }

    // Generate subsequent round matches
    let currentRound = 2;
    let matchesInRound = totalSlots / 2;

    while (matchesInRound >= 1) {
      for (let i = 0; i < matchesInRound; i++) {
        const isLastRound = matchesInRound === 1;
        const isSecondToLastRound = matchesInRound === 2;

        let matchType = "regular";
        if (isLastRound) {
          matchType = "final";
        } else if (isSecondToLastRound) {
          matchType = "semifinal";
        }

        matches.push({
          match_identifier: `M${this.gameId}-D${this.departmentId}-${
            this.gradeLevel || "ALL"
          }-R${currentRound}-${currentMatchNumber}-${this.generateUniqueId()}`,
          round: currentRound,
          match_number: currentMatchNumber,
          next_match_number: isLastRound
            ? 0
            : Math.floor(currentMatchNumber / 2) + matchesInRound / 2,
          teamA_id: -2, // -2 for TBD
          teamB_id: -2,
          match_type: matchType,
          status: "Pending",
        });
        currentMatchNumber++;
      }

      matchesInRound = matchesInRound / 2;
      currentRound++;
    }

    // Add third place match
    matches.push({
      match_identifier: `M${this.gameId}-D${this.departmentId}-${
        this.gradeLevel || "ALL"
      }-R${currentRound}-${currentMatchNumber}-${this.generateUniqueId()}`,
      round: currentRound,
      match_number: currentMatchNumber,
      next_match_number: 0,
      teamA_id: -2,
      teamB_id: -2,
      match_type: "third_place",
      status: "Pending",
    });

    console.log("Generated bracket structure:", {
      teams: this.teams,
      matches: matches,
      rounds: currentRound,
    });

    return {
      teams: this.teams,
      matches: matches,
      rounds: currentRound,
    };
  }

  convertBracketData(bracketData, teams, totalRounds) {
    const matches = [];
    const totalTeams = teams.length;
    const totalSlots = Math.pow(2, Math.ceil(Math.log2(totalTeams)));

    // ✅ Always clear previous advancement state
    const advancedTeams = new Map(); // Reset completely on new save

    if (teams.length === 2 && totalRounds === 1) {
      return {
        teams: teams,
        matches: [
          {
            match_identifier: `M${this.gameId}-D${this.departmentId}-${
              this.gradeLevel || "ALL"
            }-R1-1-${this.generateUniqueId()}`,
            round: 1,
            match_number: 1,
            next_match_number: 0,
            teamA_id: teams[0].team_id,
            teamB_id: teams[1].team_id,
            match_type: "final",
            status: "Pending",
          },
        ],
        rounds: 1,
      };
    }
    // Calculate base match numbers for each round
    const baseMatchNumbers = {};
    let base = 1;
    for (let round = 1; round <= totalRounds; round++) {
      baseMatchNumbers[round] = base;
      const matchesInRound = Math.pow(2, totalRounds - round);
      base += matchesInRound;
    }

    // Helper function to get team ID from team name
    const getTeamId = (teamName) => {
      if (!teamName || teamName === "BYE") return -1;
      if (teamName === "TBD") return -2;
      const team = teams.find((t) => t.team_name === teamName);
      return team ? team.team_id : -2;
    };

    // Process first round matches and track advancements
    // const advancedTeams = new Map();
    const firstRoundMatches = [];

    // First round has totalSlots/2 matches
    const firstRoundMatchCount = totalSlots / 2;

    bracketData.teams.forEach((matchTeams, index) => {
      const teamA = matchTeams[0];
      const teamB = matchTeams[1];
      const teamA_id = getTeamId(teamA);
      const teamB_id = getTeamId(teamB);
      const currentMatchNumber = baseMatchNumbers[1] + index;

      // Calculate next match number for first round
      // For match pairs (0,1), (2,3), (4,5), (6,7) -> next matches are 9, 10, 11, 12
      const nextMatchNumber = baseMatchNumbers[2] + Math.floor(index / 2);

      // Determine if this is a BYE match and who advances
      let advancingTeamId = -2; // Default to TBD
      let status = "Pending";

      if (teamA_id === -1 && teamB_id === -1) {
        status = "Finished";
        advancingTeamId = -2; // Both BYE, next match gets TBD
      } else if (teamA_id === -1) {
        status = "Finished";
        advancingTeamId = teamB_id; // Team B advances
      } else if (teamB_id === -1) {
        status = "Finished";
        advancingTeamId = teamA_id; // Team A advances
      }

      // Store the match
      firstRoundMatches.push({
        match_identifier: `M${this.gameId}-D${this.departmentId}-${
          this.gradeLevel || "ALL"
        }-R1-${currentMatchNumber}-${this.generateUniqueId()}`,
        round: 1,
        match_number: currentMatchNumber,
        next_match_number: nextMatchNumber,
        teamA_id: teamA_id,
        teamB_id: teamB_id,
        // match_type: "regular",
        match_type: teams.length === 4 ? "semifinal" : "regular",

        status: status,
      });

      // If there's an advancing team, store it for the next round
      //   if (advancingTeamId !== -2) {
      //     advancedTeams.set(nextMatchNumber, advancingTeamId);
      //   }
    });

    // Add first round matches to the final array
    matches.push(...firstRoundMatches);

    // Process subsequent rounds
    for (let round = 2; round <= totalRounds; round++) {
      const matchesInRound = Math.pow(2, totalRounds - round);
      const isLastRound = round === totalRounds;

      for (let i = 0; i < matchesInRound; i++) {
        const currentMatchNumber = baseMatchNumbers[round] + i;

        // Calculate next match number
        let nextMatchNumber;
        if (isLastRound) {
          nextMatchNumber = 0; // Final match has no next match
        } else {
          // For match pairs (9,10), (11,12) -> next matches are 13, 14
          nextMatchNumber = baseMatchNumbers[round + 1] + Math.floor(i / 2);
        }

        const isSecondToLastRound = round === totalRounds - 1;

        let matchType = "regular";
        if (isLastRound) {
          matchType = "final";
        } else if (isSecondToLastRound) {
          matchType = "semifinal";
        }

        // Check if we have any advanced teams for this match
        const teamA_id = advancedTeams.get(currentMatchNumber) || -2;
        const teamB_id = advancedTeams.get(currentMatchNumber + 1) || -2;

        // Determine match status
        let status = "Pending";
        if (teamA_id === -1 && teamB_id === -1) {
          status = "Finished";
          advancedTeams.set(nextMatchNumber, -2);
        } else if (teamA_id === -1) {
          status = "Finished";
          advancedTeams.set(nextMatchNumber, teamB_id);
        } else if (teamB_id === -1) {
          status = "Finished";
          advancedTeams.set(nextMatchNumber, teamA_id);
        }

        matches.push({
          match_identifier: `M${this.gameId}-D${this.departmentId}-${
            this.gradeLevel || "ALL"
          }-R${round}-${currentMatchNumber}-${this.generateUniqueId()}`,
          round: round,
          match_number: currentMatchNumber,
          next_match_number: nextMatchNumber,
          teamA_id: teamA_id,
          teamB_id: teamB_id,
          match_type: matchType,
          status: status,
        });
      }
    }

    // Add third place match as the last match
    const thirdPlaceMatchNumber = baseMatchNumbers[totalRounds] + 1;
    matches.push({
      match_identifier: `M${this.gameId}-D${this.departmentId}-${
        this.gradeLevel || "ALL"
      }-R${totalRounds}-${thirdPlaceMatchNumber}-${this.generateUniqueId()}`,
      round: totalRounds,
      match_number: thirdPlaceMatchNumber,
      next_match_number: 0,
      teamA_id: -2,
      teamB_id: -2,
      match_type: "third_place",
      status: "Pending",
    });

    return {
      teams: this.teams,
      matches: matches,
      rounds: totalRounds,
    };
  }

  getCurrentBracketState() {
    // Instead of trying to read from jQuery bracket,
    // we'll use our generated structure
    const bracketStructure = this.generateBracketStructure();

    console.log("Current bracket state:", bracketStructure);

    if (!bracketStructure || !bracketStructure.matches) {
      console.error("No bracket structure found");
      return null;
    }

    return bracketStructure;
  }

  async saveBracket(bracketStructure) {
    console.log("Bracket state before saving:", bracketStructure);
    console.log(
      "Number of matches generated:",
      bracketStructure.matches.length
    );

    try {
      console.log("Saving bracket structure:", bracketStructure);

      const response = await fetch("save_bracket.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          game_id: this.gameId,
          department_id: this.departmentId,
          grade_level: this.gradeLevel || null,
          bracket_type: "single", // Make sure this is set
          teams: bracketStructure.teams,
          matches: bracketStructure.matches,
          rounds: bracketStructure.rounds,
        }),
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();
      if (!result.success) {
        throw new Error(result.message || "Failed to save bracket");
      }

      return result;
    } catch (error) {
      console.error("Error saving bracket:", error);
      throw error;
    }
  }
}

class RoundRobinManager {
  constructor(options) {
    this.teams = [];
    this.matches = [];
    this.rounds = 0;
    this.gameId = options.gameId;
    this.departmentId = options.departmentId;
    this.gradeLevel = options.gradeLevel;
  }

  async fetchTeams() {
    try {
      console.log("Fetching teams with params:", {
        department_id: this.departmentId,
        game_id: this.gameId,
        grade_level: this.gradeLevel,
      });

      const response = await $.ajax({
        url: "fetch_teams.php",
        method: "GET",
        data: {
          department_id: this.departmentId,
          game_id: this.gameId,
          grade_level: this.gradeLevel,
        },
      });

      console.log("Fetch teams response:", response);

      if (response.success) {
        if (!response.teams || response.teams.length < 2) {
          throw new Error(
            "At least two teams are required for a Round Robin tournament."
          );
        }
        this.teams = this.shuffleTeams(response.teams);
        return this.teams;
      } else {
        throw new Error(response.message || "Failed to fetch teams");
      }
    } catch (error) {
      console.error("Error fetching teams:", error);
      throw new Error(`Failed to fetch teams: ${error.message}`);
    }
  }

  shuffleTeams(teams) {
    const shuffled = [...teams];
    for (let i = shuffled.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
    }
    return shuffled;
  }

  generateMatches() {
    if (this.teams.length === 0) {
      console.error("No teams available to generate matches.");
      return [];
    }

    let teams = [...this.teams];

    // If teams are odd, add a "BYE" placeholder team
    if (teams.length % 2 !== 0) {
      teams.push({
        team_id: -1,
        team_name: "BYE",
      });
    }

    this.rounds = teams.length - 1;
    let halfSize = teams.length / 2;
    let generatedMatches = [];
    let matchNumber = 1;

    for (let round = 0; round < this.rounds; round++) {
      let roundMatches = [];

      for (let i = 0; i < halfSize; i++) {
        let teamA = teams[i];
        let teamB = teams[teams.length - 1 - i];

        if (teamA.team_id !== -1 && teamB.team_id !== -1) {
          roundMatches.push({
            match_identifier: `M${this.gameId}-D${this.departmentId}-${
              this.gradeLevel || "ALL"
            }-RR-R${round + 1}-${matchNumber}-${this.generateUniqueId()}`,
            round: round + 1,
            match_number: matchNumber,
            teamA_id: teamA.team_id,
            teamB_id: teamB.team_id,
            match_type: "round_robin",
            status: "Pending",
          });
          matchNumber++;
        }
      }

      generatedMatches.push(roundMatches);
      teams.splice(1, 0, teams.pop()); // Rotate teams (Keep first team fixed)
    }

    this.matches = generatedMatches;
    console.log("Generated Round Robin Matches:", this.matches);
    return {
      teams: this.teams,
      matches: generatedMatches.flat(), // Flatten the array of round matches
      rounds: this.rounds,
    };
  }

  generateUniqueId() {
    return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
  }
}
