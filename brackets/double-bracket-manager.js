class DoubleBracketManager {
    constructor(options) {
        this.teams = [];
        this.matches = [];
        this.rounds = 0;
        this.gameId = options.gameId;
        this.departmentId = options.departmentId;
        this.gradeLevel = options.gradeLevel;
        this.bracketData = null;
        this.bracketType = 'double';
        this.MAX_TEAMS = 64; // Maximum teams allowed
        this.MIN_TEAMS = 2;  // Minimum teams required
    }

    validateTeamCount(teamCount) {
        if (teamCount < this.MIN_TEAMS) {
            throw new Error(`At least ${this.MIN_TEAMS} teams are required for a tournament`);
        }
        if (teamCount > this.MAX_TEAMS) {
            throw new Error(`Maximum number of teams (${this.MAX_TEAMS}) exceeded`);
        }
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
        this.validateTeamCount(teamCount);
        
        this.rounds = Math.ceil(Math.log2(teamCount));
        const totalSlots = Math.pow(2, this.rounds);
        this.byes = totalSlots - teamCount;
        
        console.log('Round calculation:', {
            teamCount,
            rounds: this.rounds,
            totalSlots,
            byes: this.byes
        });
        
        return { 
            rounds: this.rounds, 
            byes: this.byes,
            totalSlots,
            matchesPerRound: totalSlots / 2
        };
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
                [], // Winners bracket
                [], // Losers bracket
                [  // Finals
                    [[0, 0]], // Final match
                    []        // Reset match (will be filled if needed)
                ]
            ]
        };

        // Generate winners bracket rounds
        let winnersMatchesInRound = firstRoundMatches; // Renamed to avoid redeclaration
        for (let round = 0; round < winnersRounds; round++) {
            const roundMatches = [];
            for (let match = 0; match < winnersMatchesInRound; match++) {
                roundMatches.push([0, 0]);
            }
            bracketData.results[0].push(roundMatches);
            winnersMatchesInRound = Math.ceil(winnersMatchesInRound / 2);
        }

        // Generate losers bracket rounds
        let losersMatchesInRound = Math.floor(firstRoundMatches / 2); // Renamed to avoid redeclaration
        for (let round = 0; round < losersRounds; round++) {
            const roundMatches = [];
            for (let match = 0; match < losersMatchesInRound; match++) {
                roundMatches.push([0, 0]);
            }
            bracketData.results[1].push(roundMatches);
            if (round % 2 === 1) {
                losersMatchesInRound = Math.ceil(losersMatchesInRound / 2);
            }
        }

        // Generate matches array for backend
        let matchesInRound = firstRoundMatches; // This is fine as it's in a new scope
        for (let round = 0; round < winnersRounds; round++) {
            for (let match = 0; match < matchesInRound; match++) {
                const teamA = round === 0 ? teams[match * 2] : null;
                const teamB = round === 0 ? teams[match * 2 + 1] : null;
                const hasBye = teamA?.team_name === 'BYE' || teamB?.team_name === 'BYE';
                const autoAdvance = hasBye && (teamA?.team_name !== 'BYE' || teamB?.team_name !== 'BYE');

                matches.push({
                    match_identifier: `M${this.gameId}-D${this.departmentId}-W-R${round + 1}-${currentMatchNumber}-${Date.now()}-${this.generateUniqueId()}`,
                    round: round + 1,
                    match_number: currentMatchNumber,
                    bracket: 'winners',
                    next_winner_match: this.calculateNextWinnerMatch(round + 1, currentMatchNumber),
                    next_loser_match: this.calculateNextLoserMatch(round + 1, currentMatchNumber),
                    teamA_id: round === 0 ? (teamA?.team_id || -1) : -2,
                    teamB_id: round === 0 ? (teamB?.team_id || -1) : -2,
                    status: autoAdvance ? 'Finished' : 'Pending',
                    match_type: round === winnersRounds - 1 ? 'winners_final' : 'winners_regular',
                    score_teamA: autoAdvance && teamA?.team_name !== 'BYE' ? 1 : 0,
                    score_teamB: autoAdvance && teamB?.team_name !== 'BYE' ? 1 : 0
                });
                currentMatchNumber++;
            }
            matchesInRound = Math.ceil(matchesInRound / 2);
        }

        // Generate losers bracket matches
        for (let round = 0; round < losersRounds; round++) {
            const matchesThisRound = round === 0 ? Math.ceil(firstRoundMatches/2) : 1;
            for (let match = 0; match < matchesThisRound; match++) {
                matches.push({
                    match_identifier: `M${this.gameId}-D${this.departmentId}-L-R${round + 1}-${currentMatchNumber}-${Date.now()}-${this.generateUniqueId()}`,
                    round: round + 1,
                    match_number: currentMatchNumber,
                    bracket: 'losers',
                    next_winner_match: round === losersRounds - 1 ? currentMatchNumber + 1 : currentMatchNumber + 1,
                    next_loser_match: 0,
                    teamA_id: -2,
                    teamB_id: -2,
                    status: 'Pending',
                    match_type: round === losersRounds - 1 ? 'losers_final' : 'losers_regular',
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

            // Important: Set container width and positioning
            container.css({
                'width': '100%',
                'position': 'relative',
                'overflow-x': 'auto',
                'padding': '20px 0'
            });

            $(container).bracket({
                init: data,
                // Adjust these values for better spacing
                teamWidth: 120,
                scoreWidth: 30,
                matchMargin: 15,
                roundMargin: 20,  // Reduced from 25
                centerConnectors: true,
                disableHighlight: false,
                disableToolbar: false,
                skipConsolationRound: true,
                skipGrandFinalComeback: false,
                skipSecondaryFinal: false,
                save: this.handleBracketUpdate.bind(this),
                decorator: {
                    edit: this.editDecorator.bind(this),
                    render: this.renderDecorator.bind(this)
                }
            });

            // Add these styles after initialization
            $('.jQBracket.doubleElimination').css({
                'display': 'flex',
                'flex-direction': 'row',
                'justify-content': 'flex-start',
                'align-items': 'flex-start',
                'gap': '15px',  // Reduced from 20px
                'margin': '0'
            });

            // Also adjust losers bracket specifically
            $('.jQBracket .loserBracket').css({
                'margin-left': '0',
                'position': 'relative'
            });

            // Force layout recalculation
            setTimeout(() => {
                $(window).trigger('resize');
                container.scrollLeft(0); // Reset scroll position
            }, 100);

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
        
        // Create array of all positions
        let positions = Array(nextPowerOfTwo).fill(null);
        
        // Distribute teams and BYEs optimally
        const seededPositions = this.calculateSeededPositions(teamCount, nextPowerOfTwo);
        const byePositions = this.calculateByePositions(byeCount, nextPowerOfTwo);
        
        // Place teams and BYEs
        let teamIndex = 0;
        for (let i = 0; i < nextPowerOfTwo; i++) {
            if (byePositions.includes(i)) {
                positions[i] = 'BYE';
            } else {
                positions[i] = teams[teamIndex++]?.team_name || 'BYE';
            }
        }
        
        // Convert to pairs for bracket display
        const pairs = [];
        for (let i = 0; i < positions.length; i += 2) {
            pairs.push([positions[i], positions[i + 1]]);
        }
        
        return pairs;
    }

    calculateSeededPositions(teamCount, totalSlots) {
        const positions = [];
        for (let i = 0; i < teamCount; i++) {
            let position = this.getSeedPosition(i + 1, totalSlots);
            positions.push(position - 1); // Convert to 0-based index
        }
        return positions;
    }

    calculateByePositions(byeCount, totalSlots) {
        // Place BYEs optimally to ensure fair progression
        const positions = [];
        const step = totalSlots / byeCount;
        for (let i = 0; i < byeCount; i++) {
            positions.push(Math.floor(i * step));
        }
        return positions;
    }

    handleMatchResult(matchId, scoreA, scoreB) {
        const match = this.matches.find(m => m.match_number === matchId);
        if (!match) return;

        match.score_teamA = scoreA;
        match.score_teamB = scoreB;
        match.status = 'Finished';

        const winningTeamId = scoreA > scoreB ? match.teamA_id : match.teamB_id;
        const losingTeamId = scoreA > scoreB ? match.teamB_id : match.teamA_id;

        // Progress winning team
        this.progressTeam(match, winningTeamId);

        // Handle loser progression for winners bracket matches
        if (match.bracket === 'winners' && match.next_loser_match) {
            const nextLoserMatch = this.matches.find(m => m.match_number === match.next_loser_match);
            if (nextLoserMatch) {
                if (nextLoserMatch.teamA_id === -2) {
                    nextLoserMatch.teamA_id = losingTeamId;
                } else {
                    nextLoserMatch.teamB_id = losingTeamId;
                }
            }
        }

        this.updateBracketDisplay();
    }

    progressTeam(match, winningTeamId) {
        if (match.next_winner_match) {
            const nextMatch = this.matches.find(m => m.match_number === match.next_winner_match);
            if (nextMatch) {
                if (nextMatch.teamA_id === -2) {
                    nextMatch.teamA_id = winningTeamId;
                } else {
                    nextMatch.teamB_id = winningTeamId;
                }
            }
        }
    }

    updateBracketDisplay() {
        try {
            const container = $('#bracket-container');
            const currentData = $(container).bracket('data');
            
            // Validate the current data
            this.validateBracketData(currentData);

            // Update matches display
            this.matches.forEach(match => {
                if (match.status === 'Finished') {
                    const scores = [match.score_teamA, match.score_teamB];
                    
                    // Update the appropriate bracket section
                    switch (match.bracket) {
                        case 'winners':
                            if (currentData.results[0][match.round - 1]) {
                                currentData.results[0][match.round - 1][match.match_number - 1] = scores;
                            }
                            break;
                        case 'losers':
                            if (currentData.results[1][match.round - 1]) {
                                currentData.results[1][match.round - 1][match.match_number - 1] = scores;
                            }
                            break;
                        case 'finals':
                            if (match.match_type === 'grand_final') {
                                currentData.results[2][0] = [scores];
                            } else if (match.match_type === 'grand_final_2') {
                                currentData.results[2][1] = [scores];
                            }
                            break;
                    }
                }
            });

            // Reinitialize the bracket with updated data
            this.initializeBracketDisplay({
                teams: currentData.teams,
                results: currentData.results
            });

            // Update save button state
            $('#save-bracket').prop('disabled', false);

        } catch (error) {
            console.error('Error updating bracket display:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to update bracket display: ' + error.message
            });
        }
    }

    handleByeMatch(match) {
        if (!match) return;

        // Check if this is a BYE match
        if (match.teamA_id === -1 || match.teamB_id === -1) {
            // Determine the winning team (the non-BYE team)
            const winningTeamId = match.teamA_id === -1 ? match.teamB_id : match.teamA_id;
            
            // Update match status and scores
            match.status = 'Finished';
            match.score_teamA = match.teamA_id === -1 ? 0 : 1;
            match.score_teamB = match.teamB_id === -1 ? 0 : 1;

            // Progress the winning team to the next match
            if (match.next_winner_match) {
                const nextMatch = this.matches.find(m => m.match_number === match.next_winner_match);
                if (nextMatch) {
                    if (nextMatch.teamA_id === -2) {
                        nextMatch.teamA_id = winningTeamId;
                    } else {
                        nextMatch.teamB_id = winningTeamId;
                    }
                }
            }

            // Update the bracket display
            const container = $('#bracket-container');
            const currentData = $(container).bracket('data');
            
            // Update the match result in the display data
            if (match.bracket === 'winners') {
                if (currentData.results[0][match.round - 1]) {
                    currentData.results[0][match.round - 1][match.match_number - 1] = [
                        match.score_teamA,
                        match.score_teamB
                    ];
                }
            } else if (match.bracket === 'losers') {
                if (currentData.results[1][match.round - 1]) {
                    currentData.results[1][match.round - 1][match.match_number - 1] = [
                        match.score_teamA,
                        match.score_teamB
                    ];
                }
            }

            // Reinitialize the bracket with updated data
            this.initializeBracketDisplay({
                teams: currentData.teams,
                results: currentData.results
            });
        }
    }

    validateBracketData(data) {
        if (!data || !data.teams || !data.results) {
            throw new Error('Invalid bracket data structure');
        }

        // Validate teams array
        if (!Array.isArray(data.teams) || data.teams.length === 0) {
            throw new Error('Teams data is invalid or empty');
        }

        // Validate results structure for double elimination
        if (!Array.isArray(data.results) || data.results.length !== 3) {
            throw new Error('Results must have winners, losers, and finals brackets');
        }

        // Validate winners bracket
        if (!Array.isArray(data.results[0])) {
            throw new Error('Invalid winners bracket structure');
        }

        // Validate losers bracket
        if (!Array.isArray(data.results[1])) {
            throw new Error('Invalid losers bracket structure');
        }

        // Validate finals
        if (!Array.isArray(data.results[2])) {
            throw new Error('Invalid finals structure');
        }

        return true;
    }
}



