/*


function doubleBracket() {
        let bracketManager;
        let generatedStructure;

        try {
            // Initialize the bracket manager using the static method
            bracketManager = DoubleBracketManager.initialize({
                gameId: <?php echo $game_id; ?>,
                departmentId: <?php echo $department_id; ?>,
                gradeLevel: $('#gradeLevelSelect').val()
            });

            $('#gradeLevelSelect').on('change', function() {
                const selectedGrade = $(this).val();
                bracketManager.gradeLevel = selectedGrade;
                $('#bracket-container').empty();
                $('#save-bracket').prop('disabled', true);
                $('#generate-bracket').prop('disabled', false);
            });

            // Add error handling for team count
            document.addEventListener('generate', async function() {
                if ($('#bracketTypeSelect').val() !== 'double') return;

                try {
                    $(this).prop('disabled', true);
                    $('#bracket-container').html('<div class="text-center"><div class="spinner-border" role="status"></div><div>Generating bracket...</div></div>');

                    const teams = await bracketManager.fetchTeams();

                    // Validate team count
                    try {
                        bracketManager.validateTeamCount(teams.length);
                    } catch (error) {
                        throw new Error(`Invalid team count: ${error.message}`);
                    }

                    const structure = bracketManager.generateDoubleBracketStructure();
                    bracketManager.initializeBracketDisplay(structure.bracketData);

                    // Handle BYE matches automatically
                    structure.matches.forEach(match => {
                        if (match.teamA_id === -1 || match.teamB_id === -1) {
                            bracketManager.handleByeMatch(match);
                        }
                    });

                    generatedStructure = structure;
                    $('#save-bracket').prop('disabled', false);

                } catch (error) {
                    console.error('Error generating bracket:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message
                    });
                    $('#bracket-container').empty();
                } finally {
                    $(this).prop('disabled', false);
                }
            });

            // Add save handler
            $('#save-bracket').off('click').on('click', async function() {
                try {
                    if (!generatedStructure) {
                        throw new Error('No bracket structure available');
                    }
                    const result = await bracketManager.saveBracket(generatedStructure);
                    if (result.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Bracket has been saved successfully.',
                            icon: 'success'
                        }).then(() => {
                            location.reload();
                        });
                    }
                } catch (error) {
                    console.error('Error saving bracket:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: error.message,
                        icon: 'error'
                    });
                }
            });

        } catch (error) {
            console.error('Error initializing double bracket:', error);
            throw error;
        }
    }


*/



class DoubleBracketManager {
    constructor(options) {
        if (!options || !options.gameId || !options.departmentId) {
            throw new Error('Missing required options');
        }

        this.gameId = options.gameId;
        this.departmentId = options.departmentId;
        this.gradeLevel = options.gradeLevel;
        this.teams = [];
        this.matches = [];
        this.bracketData = null;
        this.rounds = 0;
        this.bracketType = 'double';
        this.MAX_TEAMS = 64;
        this.MIN_TEAMS = 2;

        // Initialize state management
        this.state = new BracketState();
        
        // Bind methods
        this.handleBracketUpdate = this.handleBracketUpdate.bind(this);
        this.handleTeamEdit = this.handleTeamEdit.bind(this);
        this.handleTeamRender = this.handleTeamRender.bind(this);
        
        // Subscribe to state changes
        this.state.subscribe((changeType, data) => {
            switch (changeType) {
                case 'matches':
                    this.matches = data;
                    $('#save-bracket').prop('disabled', false);
                    break;
                case 'teams':
                    this.teams = data;
                    break;
                case 'bracketData':
                    this.bracketData = data;
                    this.updateBracketDisplay();
                    break;
            }
        });
    }

    // Handle bracket updates
    handleBracketUpdate(data) {
        console.log('Bracket updated:', data);
        this.bracketData = data;
        this.state.updateBracketData(data);
    }

    // Handle team editing
    handleTeamEdit(container, data, doneCb) {
        const input = $('<select>').addClass('form-control form-control-sm');
        input.append($('<option>').val('').text('Select Team'));
        input.append($('<option>').val('BYE').text('BYE').prop('selected', data === null));

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

    // Handle team rendering
    handleTeamRender(container, team, score) {
        container.empty();
        if (team === null) {
            container.addClass('bye-team bye');
            container.append('BYE');
        } else {
            container.append(team);
        }
    }

    // Static initialization method
    static initialize(options) {
        return new DoubleBracketManager(options);
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
        return Math.random().toString(36).substr(2, 12);
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
        console.log('Generating bracket structure...');
        if (!this.teams || !this.teams.length) {
            throw new Error('No teams available');
        }

        // Calculate structure
        const totalSlots = Math.pow(2, Math.ceil(Math.log2(this.teams.length)));
        const winnersRounds = Math.ceil(Math.log2(totalSlots));
        const firstRoundMatches = totalSlots / 2;
        const losersRounds = 2 * (winnersRounds - 1);

        console.log('Structure calculations:', {
            totalSlots,
            winnersRounds,
            firstRoundMatches,
            losersRounds
        });

        // Distribute teams and BYEs
        const teamPairs = this.distributeByes(this.teams);
        console.log('Distributed team pairs:', teamPairs);

        // Generate matches
        const matches = this.generateAllMatches(teamPairs, winnersRounds, losersRounds);
        console.log('Generated matches:', matches);

        // Create the bracket data structure
        const bracketData = {
            teams: teamPairs,
            results: [
                [], // Winners bracket
                [], // Losers bracket
                [[[0, 0]], []] // Finals
            ]
        };

        // Initialize results arrays
        for (let i = 0; i < winnersRounds; i++) {
            bracketData.results[0].push(
                new Array(Math.pow(2, winnersRounds - i - 1)).fill([0, 0])
            );
        }

        for (let i = 0; i < losersRounds; i++) {
            bracketData.results[1].push(
                new Array(Math.pow(2, Math.floor((losersRounds - i - 1) / 2))).fill([0, 0])
            );
        }

        console.log('Final bracket data:', bracketData);

        return {
            teams: this.teams,
            matches: matches,
            rounds: {
                winners: winnersRounds,
                losers: losersRounds,
                total: winnersRounds + losersRounds + 2
            },
            bracketData: bracketData
        };
    }

    calculateNextWinnerMatch(round, matchNumber) {
        return Math.floor(matchNumber / 2) + Math.pow(2, 2 - round);
    }

    calculateNextLoserMatch(round, matchNumber) {
        // Calculate the next loser match based on the current round and match number
        const losersStartAt = 8; // First loser match number
        return losersStartAt + Math.floor((matchNumber - 1) / 2);
    }

    distributeByes(teams) {
        console.log('distributeByes called with teams:', teams);
        
        // Convert team objects to names
        const teamNames = teams.map(team => team.team_name);
        const teamCount = teamNames.length;
        const nextPowerOfTwo = Math.pow(2, Math.ceil(Math.log2(teamCount)));
        const byeCount = nextPowerOfTwo - teamCount;
        
        console.log('Distribution calculation:', {
            teamCount,
            nextPowerOfTwo,
            byeCount
        });

        // Create array for seeded positions
        let positions = [...teamNames];
        
        // Add BYEs in optimal positions
        for (let i = 0; i < byeCount; i++) {
            const insertPosition = i * 2 + 1;
            positions.splice(insertPosition, 0, 'BYE');
        }
        
        // Create pairs
        const pairs = [];
        for (let i = 0; i < positions.length; i += 2) {
            pairs.push([positions[i], positions[i + 1]]);
        }
        
        console.log('Final distribution:', {
            positions,
            pairs
        });
        
        return pairs;
    }

    generateAllMatches(teamPairs, winnersRounds, losersRounds) {
        const matches = [];
        let matchNumber = 1;
    
        console.log('Generating matches from team pairs:', teamPairs);
    
        // 1. Generate Winners Bracket Matches
        for (let round = 1; round <= winnersRounds; round++) {
            const matchesInRound = Math.pow(2, winnersRounds - round);
            
            for (let i = 0; i < matchesInRound; i++) {
                if (round === 1) {
                    // First round matches with actual teams
                    const [teamA, teamB] = teamPairs[i];
                    const hasBye = teamA === 'BYE' || teamB === 'BYE';
                    const teamAId = teamA === 'BYE' ? -1 : this.teams.find(t => t.team_name === teamA)?.team_id || -2;
                    const teamBId = teamB === 'BYE' ? -1 : this.teams.find(t => t.team_name === teamB)?.team_id || -2;
    
                    matches.push({
                        match_identifier: `M${this.gameId}-D${this.departmentId}-W-R${round}-${matchNumber}-${Date.now()}-${this.generateUniqueId()}`,
                        round: round,
                        match_number: matchNumber,
                        bracket: 'winners',
                        next_winner_match: this.calculateNextWinnerMatch(round, matchNumber),
                        next_loser_match: this.calculateNextLoserMatch(round, matchNumber),
                        teamA_id: teamAId,
                        teamB_id: teamBId,
                        status: hasBye ? 'Finished' : 'Pending',
                        match_type: round === winnersRounds ? 'winners_final' : 'winners_regular',
                        score_teamA: hasBye ? (teamA === 'BYE' ? 0 : 1) : 0,
                        score_teamB: hasBye ? (teamB === 'BYE' ? 0 : 1) : 0
                    });
                } else {
                    // Subsequent rounds with TBD teams
                    matches.push({
                        match_identifier: `M${this.gameId}-D${this.departmentId}-W-R${round}-${matchNumber}-${Date.now()}-${this.generateUniqueId()}`,
                        round: round,
                        match_number: matchNumber,
                        bracket: 'winners',
                        next_winner_match: round < winnersRounds ? this.calculateNextWinnerMatch(round, matchNumber) : 0,
                        next_loser_match: this.calculateNextLoserMatch(round, matchNumber),
                        teamA_id: -2,
                        teamB_id: -2,
                        status: 'Pending',
                        match_type: round === winnersRounds ? 'winners_final' : 'winners_regular',
                        score_teamA: 0,
                        score_teamB: 0
                    });
                }
                matchNumber++;
            }
        }
    
        // 2. Generate Losers Bracket Matches
        for (let round = 1; round <= losersRounds; round++) {
            const matchesInRound = Math.pow(2, Math.floor((losersRounds - round) / 2));
            
            for (let i = 0; i < matchesInRound; i++) {
                matches.push({
                    match_identifier: `M${this.gameId}-D${this.departmentId}-L-R${round}-${matchNumber}-${Date.now()}-${this.generateUniqueId()}`,
                    round: round,
                    match_number: matchNumber,
                    bracket: 'losers',
                    next_winner_match: round < losersRounds ? matchNumber + matchesInRound : 14, // Finals match
                    next_loser_match: 0,
                    teamA_id: -2,
                    teamB_id: -2,
                    status: 'Pending',
                    match_type: round === losersRounds ? 'losers_final' : 'losers_regular',
                    score_teamA: 0,
                    score_teamB: 0
                });
                matchNumber++;
            }
        }
    
        // 3. Generate Finals Match
        matches.push({
            match_identifier: `M${this.gameId}-D${this.departmentId}-F-R1-${matchNumber}-${Date.now()}-${this.generateUniqueId()}`,
            round: 1,
            match_number: matchNumber,
            bracket: 'finals',
            next_winner_match: 0,
            next_loser_match: 0,
            teamA_id: -2,
            teamB_id: -2,
            status: 'Pending',
            match_type: 'finals',
            score_teamA: 0,
            score_teamB: 0
        });
    
        console.log('Generated all matches:', matches);
        return matches;
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
                    edit: this.handleTeamEdit.bind(this),
                    render: this.handleTeamRender.bind(this)
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

    // Add this method to process BYE matches and handle advancements
    processByeMatches() {
        // Process first round winner's bracket BYE matches first
        const firstRoundMatches = this.matches.filter(m => m.round === 1 && m.bracket === 'winners');
        
        firstRoundMatches.forEach(match => {
            if (match.teamA_id === -1 || match.teamB_id === -1) {
                // Set match as finished with proper scores
                match.status = 'Finished';
                match.score_teamA = match.teamA_id === -1 ? 0 : 1;
                match.score_teamB = match.teamB_id === -1 ? 0 : 1;

                // Get the advancing team ID
                const winningTeamId = match.teamA_id === -1 ? match.teamB_id : match.teamA_id;

                // Find and update next winner's match
                const nextMatch = this.matches.find(m => m.match_number === match.next_winner_match);
                if (nextMatch) {
                    // Place advancing team based on current match number
                    if (match.match_number % 2 === 1) {
                        nextMatch.teamA_id = winningTeamId;
                    } else {
                        nextMatch.teamB_id = winningTeamId;
                    }
                }
            }
        });

        return this.matches;
    }
}