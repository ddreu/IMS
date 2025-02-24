class DoubleBracketManager {
    constructor(options) {
        this.teams = [];
        this.matches = [];
        this.rounds = 0;
        this.gameId = options.gameId;
        this.departmentId = options.departmentId;
        this.gradeLevel = options.gradeLevel;
        this.bracketData = null;
        this.bracketType = 'double'; // Add bracket type
    }

    async fetchTeams() {
        try {
            console.log('Fetching teams with params:', {
                department_id: this.departmentId,
                game_id: this.gameId,
                grade_level: this.gradeLevel
            });

            const response = await $.ajax({
                url: 'fetch_teams.php',
                method: 'GET',
                data: {
                    department_id: this.departmentId,
                    game_id: this.gameId,
                    grade_level: this.gradeLevel
                }
            });
            
            console.log('Fetch teams response:', response);
            
            if (response.success) {
                if (!response.teams || !response.teams.length) {
                    throw new Error('No teams found for the selected criteria');
                }
                this.teams = this.shuffleTeams(response.teams);
                return this.teams;
            } else {
                throw new Error(response.message || 'Failed to fetch teams');
            }
        } catch (error) {
            console.error('Error fetching teams:', error);
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
            throw new Error('At least 2 teams are required to create a bracket');
        }
        
        this.rounds = Math.ceil(Math.log2(teamCount));
        const totalSlots = Math.pow(2, this.rounds);
        this.byes = totalSlots - teamCount;
        
        console.log('Round calculation:', {
            teamCount,
            rounds: this.rounds,
            totalSlots,
            byes: this.byes
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
        return Math.random().toString(36).substring(2, 12);
    }

    generateBracketStructure() {
        const teams = [...this.teams];
        const matches = [];
        let currentMatchNumber = 1;

        // Calculate total slots needed (power of 2)
        const totalSlots = Math.pow(2, Math.ceil(Math.log2(teams.length)));
        const byes = totalSlots - teams.length;

        console.log('Round calculation:', {
            teamCount: teams.length,
            rounds: Math.ceil(Math.log2(teams.length)),
            totalSlots: totalSlots,
            byes: byes
        });

        // Create seeded positions for a proper bracket
        let seededPositions = [];
        for (let i = 0; i < totalSlots; i++) {
            seededPositions[i] = null;
        }

        // Function to get proper seeded position in the bracket
        function getSeedPosition(seed, totalPositions) {
            const rounds = Math.log2(totalPositions);
            let position = 0;
            let step = totalPositions;
            
            for (let i = 0; i < rounds; i++) {
                step = step / 2;
                if (seed % 2) {
                    position += step;
                }
                seed = Math.ceil(seed / 2);
            }
            return position;
        }

        // Place teams in their seeded positions
        teams.forEach((team, index) => {
            const seedNumber = index + 1;
            const position = getSeedPosition(seedNumber, totalSlots);
            seededPositions[position] = team;
        });

        // Generate first round matches with proper seeding
        for (let i = 0; i < totalSlots; i += 2) {
            const teamA = seededPositions[i];
            const teamB = seededPositions[i + 1];
            
            matches.push({
                match_identifier: `M${this.gameId}-D${this.departmentId}-${this.gradeLevel || 'ALL'}-R1-${currentMatchNumber}-${this.generateUniqueId()}`,
                round: 1,
                match_number: currentMatchNumber,
                next_match_number: Math.floor(currentMatchNumber / 2) + (totalSlots / 2),
                teamA_id: teamA ? teamA.team_id : -1, // -1 for BYE
                teamB_id: teamB ? teamB.team_id : -1,
                match_type: 'regular',
                status: (!teamA || !teamB) ? 'Finished' : 'Pending'
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
                
                let matchType = 'regular';
                if (isLastRound) {
                    matchType = 'final';
                } else if (isSecondToLastRound) {
                    matchType = 'semifinal';
                }

                matches.push({
                    match_identifier: `M${this.gameId}-D${this.departmentId}-${this.gradeLevel || 'ALL'}-R${currentRound}-${currentMatchNumber}-${this.generateUniqueId()}`,
                    round: currentRound,
                    match_number: currentMatchNumber,
                    next_match_number: isLastRound ? 0 : Math.floor(currentMatchNumber / 2) + matchesInRound/2,
                    teamA_id: -2, // -2 for TBD
                    teamB_id: -2,
                    match_type: matchType,
                    status: 'Pending'
                });
                currentMatchNumber++;
            }
            
            matchesInRound = matchesInRound / 2;
            currentRound++;
        }

        // Add third place match
        matches.push({
            match_identifier: `M${this.gameId}-D${this.departmentId}-${this.gradeLevel || 'ALL'}-R${currentRound}-${currentMatchNumber}-${this.generateUniqueId()}`,
            round: currentRound,
            match_number: currentMatchNumber,
            next_match_number: 0,
            teamA_id: -2,
            teamB_id: -2,
            match_type: 'third_place',
            status: 'Pending'
        });

        console.log('Generated bracket structure:', {
            teams: this.teams,
            matches: matches,
            rounds: currentRound
        });

        return {
            teams: this.teams,
            matches: matches,
            rounds: currentRound
        };
    }

    convertBracketData(bracketData, teams, totalRounds) {
        // Format data for jQuery bracket plugin's double elimination structure
        const doubleEliminationData = {
            teams: bracketData.teams,
            results: []
        };

        // Format winners bracket results
        const winnersResults = this.formatWinnersResults();
        doubleEliminationData.results.push(winnersResults);

        // Format losers bracket results
        const losersResults = this.formatLosersResults();
        doubleEliminationData.results.push(losersResults);

        // Format finals results
        const finalsResults = [
            [[null, null]], // First round
            []              // Second round (if needed)
        ];
        doubleEliminationData.results.push(finalsResults);

        // Update with actual scores if available
        bracketData.winners_bracket.forEach(match => {
            const roundIndex = match.round - 1;
            const matchIndex = match.match_number - 1;
            if (doubleEliminationData.results[0][roundIndex] && 
                doubleEliminationData.results[0][roundIndex][matchIndex]) {
                doubleEliminationData.results[0][roundIndex][matchIndex] = [
                    match.score_teamA || null,
                    match.score_teamB || null
                ];
            }
        });

        bracketData.losers_bracket.forEach(match => {
            const roundIndex = match.round - 1;
            const matchIndex = match.match_number - 1;
            if (doubleEliminationData.results[1][roundIndex] && 
                doubleEliminationData.results[1][roundIndex][matchIndex]) {
                doubleEliminationData.results[1][roundIndex][matchIndex] = [
                    match.score_teamA || null,
                    match.score_teamB || null
                ];
            }
        });

        if (bracketData.grand_finals.length > 0) {
            doubleEliminationData.results[2][0][0] = [
                bracketData.grand_finals[0].score_teamA || null,
                bracketData.grand_finals[0].score_teamB || null
            ];
        }

        console.log('Converted bracket data:', doubleEliminationData);
        return doubleEliminationData;
    }

    getCurrentBracketState() {
        if (!this.bracketData) {
            console.error('No bracket data available');
            return null;
        }

        // Get the current state from jQuery bracket
        const container = $('#bracket-container');
        const displayData = $(container).bracket('data');

        // Calculate rounds based on team count
        const winnersRounds = Math.ceil(Math.log2(this.teams.length));
        const losersRounds = 2 * (winnersRounds - 1);

        // Ensure all required fields are present
        const state = {
            teams: this.teams.map(team => ({
                team_id: team.team_id,
                team_name: team.team_name
            })),
            matches: this.matches.map(match => ({
                match_identifier: match.match_identifier,
                round: match.round,
                match_number: match.match_number,
                bracket: match.bracket,
                next_winner_match: match.next_winner_match,
                next_loser_match: match.next_loser_match,
                teamA_id: match.teamA_id,
                teamB_id: match.teamB_id,
                status: match.status,
                match_type: match.match_type,
                score_teamA: match.score_teamA || 0,
                score_teamB: match.score_teamB || 0
            })),
            rounds: {
                winners: winnersRounds,
                losers: losersRounds,
                total: winnersRounds + losersRounds + 2
            },
            bracket_data: {
                teams: displayData.teams,
                results: displayData.results
            }
        };

        console.log('Current bracket state:', state);
        return state;
    }

    async saveBracket(bracketState) {
        try {
            // Validate required data
            if (!bracketState || !bracketState.teams || !bracketState.matches || !bracketState.rounds) {
                throw new Error('Missing required bracket data');
            }

            // Get current display data
            const container = $('#bracket-container');
            const displayData = $(container).bracket('data');

            // Prepare save data
            const saveData = {
                game_id: this.gameId,
                department_id: this.departmentId,
                grade_level: this.gradeLevel,
                bracket_type: 'double',
                teams: bracketState.teams,
                matches: bracketState.matches,
                rounds: bracketState.rounds,
                bracket_data: {
                    teams: displayData.teams,
                    results: displayData.results
                }
            };

            console.log('Saving bracket data:', saveData);

            const response = await fetch('save_bracket.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(saveData)
            });

            const result = await response.json();
            if (!result.success) {
                throw new Error(result.message || 'Failed to save bracket');
            }

            return result;
        } catch (error) {
            console.error('Error saving bracket:', error);
            throw error;
        }
    }

    // Add new method to generate double elimination structure
    generateDoubleBracketStructure() {
        if (!this.teams || !this.teams.length) {
            throw new Error('No teams available. Please fetch teams first.');
        }

        const teams = [...this.teams];
        const matches = [];
        let currentMatchNumber = 1;

        // Calculate structure
        const totalSlots = Math.pow(2, Math.ceil(Math.log2(teams.length))); 
        const winnersRounds = Math.ceil(Math.log2(totalSlots)); 
        const firstRoundMatches = totalSlots/2; 
        const losersRounds = 2 * (winnersRounds - 1);

        console.log('Structure calculation:', {
            teamCount: teams.length,
            totalSlots,
            winnersRounds,
            firstRoundMatches,
            losersRounds
        });

        // Create the display data structure for jQuery bracket
        const bracketData = {
            teams: this.distributeByes(teams),
            results: [
                [ // Winners bracket
                    [ // First round
                        [0, 0],
                        [0, 0],
                        [0, 0],
                        [0, 0]
                    ],
                    [ // Second round
                        [0, 0],
                        [0, 0]
                    ],
                    [ // Third round
                        [0, 0]
                    ]
                ],
                [ // Losers bracket
                    [ // First round
                        [0, 0],
                        [0, 0]
                    ],
                    [ // Second round
                        [0, 0]
                    ],
                    [ // Third round
                        [0, 0]
                    ],
                    [ // Fourth round
                        [0, 0]
                    ]
                ],
                [ // Finals
                    [ // First final
                        [0, 0]
                    ],
                    [ // Second final
                        [0, 0]
                    ]
                ]
            ]
        };

        // Generate matches array for backend
        let matchesInRound = firstRoundMatches;
        for (let round = 1; round <= winnersRounds; round++) {
            for (let match = 0; match < matchesInRound; match++) {
                const teamA = round === 1 ? teams[match * 2] : null;
                const teamB = round === 1 ? teams[match * 2 + 1] : null;
                const hasBye = teamA?.team_name === 'BYE' || teamB?.team_name === 'BYE';
                const autoAdvance = hasBye && (teamA?.team_name !== 'BYE' || teamB?.team_name !== 'BYE');

                matches.push({
                    match_identifier: `M${this.gameId}-D${this.departmentId}-W-R${round}-${currentMatchNumber}-${Date.now()}-${this.generateUniqueId()}`,
                    round: round,
                    match_number: currentMatchNumber,
                    bracket: 'winners',
                    next_winner_match: this.calculateNextWinnerMatch(round, currentMatchNumber),
                    next_loser_match: this.calculateNextLoserMatch(round, currentMatchNumber),
                    teamA_id: round === 1 ? (teamA?.team_id || -1) : -2,
                    teamB_id: round === 1 ? (teamB?.team_id || -1) : -2,
                    status: autoAdvance ? 'Finished' : 'Pending',
                    match_type: round === winnersRounds ? 'winners_final' : 'winners_regular',
                    score_teamA: autoAdvance && teamA?.team_name !== 'BYE' ? 1 : 0,
                    score_teamB: autoAdvance && teamB?.team_name !== 'BYE' ? 1 : 0
                });
                currentMatchNumber++;
            }
            matchesInRound = Math.ceil(matchesInRound / 2);
        }

        // Generate losers bracket matches
        for (let round = 1; round <= losersRounds; round++) {
            const matchesThisRound = round === 1 ? Math.ceil(firstRoundMatches/2) : 1;
            for (let match = 0; match < matchesThisRound; match++) {
                matches.push({
                    match_identifier: `M${this.gameId}-D${this.departmentId}-L-R${round}-${currentMatchNumber}-${Date.now()}-${this.generateUniqueId()}`,
                    round: round,
                    match_number: currentMatchNumber,
                    bracket: 'losers',
                    next_winner_match: round === losersRounds ? currentMatchNumber + 1 : currentMatchNumber + 1,
                    next_loser_match: 0,
                    teamA_id: -2,
                    teamB_id: -2,
                    status: 'Pending',
                    match_type: round === losersRounds ? 'losers_final' : 'losers_regular',
                    score_teamA: 0,
                    score_teamB: 0
                });
                currentMatchNumber++;
            }
        }

        // Add finals matches
        for (let i = 0; i < 2; i++) {
            matches.push({
                match_identifier: `M${this.gameId}-D${this.departmentId}-F${i+1}-${currentMatchNumber}-${Date.now()}-${this.generateUniqueId()}`,
                round: i + 1,
                match_number: currentMatchNumber,
                bracket: 'finals',
                next_winner_match: i === 0 ? currentMatchNumber + 1 : 0,
                next_loser_match: 0,
                teamA_id: -2,
                teamB_id: -2,
                status: 'Pending',
                match_type: i === 0 ? 'grand_final' : 'grand_final_2',
                score_teamA: 0,
                score_teamB: 0
            });
            currentMatchNumber++;
        }

        return {
            teams,
            matches,
            rounds: {
                winners: winnersRounds,
                losers: losersRounds,
                total: winnersRounds + losersRounds + 2
            },
            bracketData
        };
    }

    calculateNextWinnerMatch(round, currentMatchNumber) {
        // For winners bracket progression
        const matchesInFirstRound = Math.pow(2, Math.ceil(Math.log2(this.teams.length))) / 2;

        if (round === 1) {
            // First round matches: Pair up winners to next round
            // Example: Matches 1,2 -> 5, Matches 3,4 -> 6
            const baseNextMatch = matchesInFirstRound + 1;
            return baseNextMatch + Math.floor((currentMatchNumber - 1) / 2);
        } else if (round === 2) {
            // Second round matches: Winners go to winners final
            // Example: Matches 5,6 -> 7
            return matchesInFirstRound + Math.floor(matchesInFirstRound/2) + 1;
        } else if (round === 3) {
            // Winners final: Winner goes to grand finals
            const losersMatches = 2 * (Math.ceil(Math.log2(matchesInFirstRound))) - 1;
            return currentMatchNumber + losersMatches + 1;
        }
        return 0;
    }

    calculateNextLoserMatch(round, currentMatchNumber) {
        // For losers bracket progression from winners bracket
        const matchesInFirstRound = Math.pow(2, Math.ceil(Math.log2(this.teams.length))) / 2;
        const firstLoserMatch = matchesInFirstRound + Math.floor(matchesInFirstRound/2) + 2;

        if (round === 1) {
            // First round losers: Pair up in losers bracket
            // Example: Matches 1,2 losers -> 8, Matches 3,4 losers -> 9
            return firstLoserMatch + Math.floor((currentMatchNumber - 1) / 2);
        } else if (round === 2) {
            // Second round losers: Go to appropriate losers match
            // Example: Match 5 loser -> 10, Match 6 loser -> 11
            return firstLoserMatch + (matchesInFirstRound/2) + (currentMatchNumber - (matchesInFirstRound + 1));
        } else if (round === 3) {
            // Winners final loser: Goes to losers final
            // Example: Match 7 loser -> 12
            return currentMatchNumber + 5;
        }
        return 0;
    }

    calculateNextLoserBracketMatch(match) {
        // For progression within losers bracket
        const matchesInFirstRound = Math.pow(2, Math.ceil(Math.log2(this.teams.length))) / 2;
        const totalLosersRounds = 2 * (Math.ceil(Math.log2(matchesInFirstRound))) - 1;

        if (match.round === totalLosersRounds) {
            // Losers final: Winner goes to grand finals
            return match.match_number + 1;
        }

        // All other losers matches: Winner progresses to next match
        return match.match_number + 1;
    }

    calculateLoserMatchNumber(winnersRound, matchIndex, currentMatchNumber) {
        // Calculate which match in the losers bracket this team will go to if they lose
        const totalTeams = this.teams.length;
        const totalSlots = Math.pow(2, Math.ceil(Math.log2(totalTeams)));
        const totalRounds = Math.log2(totalSlots);
        
        // First round losers go to first round of losers bracket
        if (winnersRound === 1) {
            return currentMatchNumber + totalSlots/2 + Math.floor(matchIndex/2);
        }
        
        // Later round losers go to appropriate round in losers bracket
        const losersStartMatch = currentMatchNumber + totalSlots/2;
        const roundOffset = (winnersRound - 1) * 2;
        return losersStartMatch + roundOffset + matchIndex;
    }

    initializeBracketDisplay(data) {
        console.log('Initializing bracket display with data:', data);
        const container = $('#bracket-container');
        container.empty();

        try {
            if (!$.fn.bracket) {
                throw new Error('jQuery bracket plugin not loaded');
            }

            // Create the bracket data structure that jQuery bracket expects
            const bracketData = {
                teams: data.teams,
                results: data.results
            };

            $(container).bracket({
                teamWidth: 150,
                scoreWidth: 40,
                matchMargin: 30,
                roundMargin: 40,
                centerConnectors: true,
                disableHighlight: false,
                disableToolbar: false,
                skipConsolationRound: true,
                skipGrandFinalComeback: false,
                skipSecondaryFinal: false,
                init: bracketData, // Pass the correctly formatted data
                save: this.handleBracketUpdate.bind(this),
                decorator: {
                    edit: this.editDecorator.bind(this),
                    render: this.renderDecorator.bind(this)
                }
            });

            console.log('Bracket initialized successfully');
        } catch (error) {
            console.error('Error initializing bracket:', error);
            console.log('Bracket data:', JSON.stringify(data, null, 2));
            throw error;
        }
    }

    formatWinnersResults() {
        const rounds = [];
        let matchesInRound = Math.floor(this.teams.length / 2);
        let roundNumber = 0;
        
        while (matchesInRound >= 1) {
            const roundMatches = [];
            for (let i = 0; i < matchesInRound; i++) {
                roundMatches.push([0, 0]);
            }
            rounds[roundNumber] = roundMatches;
            matchesInRound = Math.floor(matchesInRound / 2);
            roundNumber++;
        }
        
        return rounds;
    }

    formatLosersResults() {
        const rounds = [];
        const totalRounds = Math.ceil(Math.log2(this.teams.length));
        const loserRounds = (totalRounds - 1) * 2;
        let matchesInRound = Math.floor(this.teams.length / 4);
        
        for (let round = 0; round < loserRounds; round++) {
            const roundMatches = [];
            const currentRoundMatches = round % 2 === 0 ? matchesInRound : matchesInRound;
            
            for (let i = 0; i < currentRoundMatches; i++) {
                roundMatches.push([0, 0]);
            }
            rounds[round] = roundMatches;
            
            if (round % 2 === 1) {
                matchesInRound = Math.floor(matchesInRound / 2);
            }
        }
        
        return rounds;
    }

    handleBracketUpdate(data) {
        console.log('Bracket updated:', data);
        this.bracketData = data;

        try {
            // Convert the jQuery bracket data format back to our match format
            if (data.results.winners) {
                data.results.winners.forEach((round, roundIndex) => {
                    round.forEach((match, matchIndex) => {
                        const matchToUpdate = this.matches.find(m => 
                            m.bracket === 'winners' && 
                            m.round === roundIndex + 1 && 
                            matchIndex === (m.match_number - 1) % round.length
                        );
                        if (matchToUpdate) {
                            matchToUpdate.score_teamA = Number(match[0]) || 0;
                            matchToUpdate.score_teamB = Number(match[1]) || 0;
                            matchToUpdate.status = 'Finished';
                        }
                    });
                });
            }

            if (data.results.losers) {
                data.results.losers.forEach((round, roundIndex) => {
                    round.forEach((match, matchIndex) => {
                        const matchToUpdate = this.matches.find(m => 
                            m.bracket === 'losers' && 
                            m.round === roundIndex + 1 && 
                            matchIndex === (m.match_number - 1) % round.length
                        );
                        if (matchToUpdate) {
                            matchToUpdate.score_teamA = Number(match[0]) || 0;
                            matchToUpdate.score_teamB = Number(match[1]) || 0;
                            matchToUpdate.status = 'Finished';
                        }
                    });
                });
            }

            if (data.results.finals) {
                const finalMatch = this.matches.find(m => m.bracket === 'grand_final');
                if (finalMatch) {
                    finalMatch.score_teamA = Number(data.results.finals[0][0]) || 0;
                    finalMatch.score_teamB = Number(data.results.finals[0][1]) || 0;
                    finalMatch.status = 'Finished';
                }
            }
        } catch (error) {
            console.error('Error updating bracket:', error);
        }
    }

    editDecorator(container, data, doneCb) {
        // Similar to single elimination but for double bracket
        const input = $('<select>').addClass('form-control form-control-sm');
        input.append($('<option>').val('').text('Select Team'));
        input.append($('<option>').val('BYE').text('BYE').prop('selected', data === null));

        // Add teams
        this.teams.forEach(team => {
            input.append($('<option>')
                .val(team.team_name)
                .text(team.team_name)
                .prop('selected', team.team_name === data)
            );
        });

        container.html(input);
        input.on('change', function() {
            const selectedValue = $(this).val();
            doneCb(selectedValue === 'BYE' || selectedValue === '' ? null : selectedValue);
        });
    }

    renderDecorator(container, team, score) {
        container.empty();
        if (team === null) {
            container.addClass('bye-team bye');
            container.append('BYE');
        } else {
            container.append(team);
        }
    }

    distributeByes(teams) {
        const teamCount = teams.length;
        const nextPowerOfTwo = Math.pow(2, Math.ceil(Math.log2(teamCount)));
        const byeCount = nextPowerOfTwo - teamCount;
        
        // First shuffle the teams
        const shuffledTeams = [...teams].sort(() => Math.random() - 0.5);
        
        // Create array of all positions
        let positions = Array(nextPowerOfTwo).fill(null);
        
        // Create all possible positions and shuffle them
        let allPositions = Array.from({length: nextPowerOfTwo}, (_, i) => i);
        allPositions = allPositions.sort(() => Math.random() - 0.5);
        
        // Take first byeCount positions for BYEs
        const byePositions = allPositions.slice(0, byeCount);
        
        // Place teams and BYEs in shuffled positions
        let teamIndex = 0;
        for (let i = 0; i < nextPowerOfTwo; i++) {
            if (byePositions.includes(i)) {
                positions[i] = 'BYE';
            } else {
                positions[i] = shuffledTeams[teamIndex++];
            }
        }
        
        // Convert to pairs
        const pairs = [];
        for (let i = 0; i < positions.length; i += 2) {
            pairs.push([
                positions[i]?.team_name || 'BYE',
                positions[i + 1]?.team_name || 'BYE'
            ]);
        }
         
        // Store the shuffled teams for match generation
        this.teams = shuffledTeams;
        
        return pairs;
    }
}


