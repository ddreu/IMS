// Bracket generation and management functionality
$(document).ready(function() {
    let availableTeams = [];
    let matches = {};
    
    // Ensure config is available
    if (!window.BRACKET_CONFIG) {
        console.error('Bracket configuration is not available');
        return;
    }
    
    const config = window.BRACKET_CONFIG;
    console.log('Bracket config loaded:', config);

    // Handle generate bracket button click
    $('#generateBracket').on('click', function() {
        initializeBracket(config.departmentId, config.gradeLevel);
        $('#bracketActions').show();
    });

    // Handle grade level selection
    $('#gradeLevelSelect').on('change', function() {
        config.gradeLevel = $(this).val() || null;
    });

    function getNextPowerOfTwo(n) {
        return Math.pow(2, Math.ceil(Math.log2(n)));
    }

    function distributeByes(numTeams, totalPositions) {
        const byesNeeded = totalPositions - numTeams;
        if (byesNeeded <= 0) return [];

        // Create array of all possible positions for byes
        let possiblePositions = [];
        for(let i = 0; i < totalPositions/2; i++) {
            possiblePositions.push(i);
        }

        // Split positions into left and right sides of bracket
        const midPoint = Math.floor(possiblePositions.length / 2);
        let leftSide = possiblePositions.slice(0, midPoint);
        let rightSide = possiblePositions.slice(midPoint);

        // Shuffle both sides
        leftSide = leftSide.sort(() => Math.random() - 0.5);
        rightSide = rightSide.sort(() => Math.random() - 0.5);

        let byePositions = [];
        const byesPerSide = Math.ceil(byesNeeded / 2);

        // Distribute byes evenly between sides
        for(let i = 0; i < byesNeeded; i++) {
            if (i < byesPerSide && leftSide.length > 0) {
                byePositions.push(leftSide.pop());
            } else if (rightSide.length > 0) {
                byePositions.push(rightSide.pop());
            } else if (leftSide.length > 0) {
                byePositions.push(leftSide.pop());
            }
        }

        return byePositions;
    }

    function advanceTeamWithBye(match, nextRoundIndex, matches) {
        const nextRoundMatch = Math.floor(nextRoundIndex / 2);
        const isTopTeam = nextRoundIndex % 2 === 0;
        
        if (!matches[1][nextRoundMatch]) {
            matches[1][nextRoundMatch] = {
                match_id: `1-${nextRoundMatch}`,
                teamA_id: null,
                teamB_id: null,
                teamA_name: null,
                teamB_name: null,
                winner_id: null,
                loser_id: null
            };
        }

        if (isTopTeam) {
            matches[1][nextRoundMatch].teamA_id = match.teamA_id;
            matches[1][nextRoundMatch].teamA_name = match.teamA_name;
        } else {
            matches[1][nextRoundMatch].teamB_id = match.teamA_id;
            matches[1][nextRoundMatch].teamB_name = match.teamA_name;
        }
    }

    function initializeBracket(departmentId, gradeLevel = null) {
        $('#bracket-container').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading teams...</div>');
        
        $.ajax({
            url: 'fetch_teams.php',
            method: 'POST',
            data: {
                department_id: departmentId,
                grade_level: gradeLevel
            },
            success: function(response) {
                if (response.error) {
                    console.error('Error fetching teams:', response.error);
                    $('#bracket-container').html('<div class="alert alert-danger">' + response.error + '</div>');
                    return;
                }

                availableTeams = response.teams || [];
                
                const numTeams = availableTeams.length;
                if (numTeams === 0) {
                    $('#bracket-container').html('<div class="alert alert-warning">No teams available for this selection.</div>');
                    return;
                }

                if (numTeams === 1) {
                    $('#bracket-container').html('<div class="alert alert-warning">Cannot create a bracket with only one team.</div>');
                    return;
                }

                const totalPositions = getNextPowerOfTwo(numTeams);
                const numRounds = Math.log2(totalPositions);
                const firstRoundMatches = totalPositions / 2;

                // Get bye positions and shuffle teams
                const byeMatchPositions = distributeByes(numTeams, totalPositions);
                const shuffledTeams = [...availableTeams].sort(() => Math.random() - 0.5);
                
                // Initialize all rounds
                matches = {};
                for(let round = 0; round < numRounds; round++) {
                    matches[round] = [];
                    const matchesInRound = firstRoundMatches / Math.pow(2, round);
                    for(let i = 0; i < matchesInRound; i++) {
                        matches[round].push({
                            match_id: `${round}-${i}`,
                            teamA_id: null,
                            teamB_id: null,
                            teamA_name: null,
                            teamB_name: null,
                            winner_id: null,
                            loser_id: null
                        });
                    }
                }
                
                let teamIndex = 0;
                
                // Distribute teams in first round
                for(let i = 0; i < firstRoundMatches && teamIndex < shuffledTeams.length; i++) {
                    const match = matches[0][i];
                    
                    // Always assign team A first
                    if (teamIndex < shuffledTeams.length) {
                        match.teamA_id = shuffledTeams[teamIndex].team_id;
                        match.teamA_name = shuffledTeams[teamIndex].team_name;
                        teamIndex++;
                    }

                    // Check if this match should have a bye
                    if (byeMatchPositions.includes(i)) {
                        match.teamB_id = 0;
                        match.teamB_name = 'BYE';
                        match.winner_id = match.teamA_id;
                        // Advance the team to next round
                        if (matches[1]) {
                            advanceTeamWithBye(match, i, matches);
                        }
                    } else if (teamIndex < shuffledTeams.length) {
                        // Assign team B if not a bye match and we have teams left
                        match.teamB_id = shuffledTeams[teamIndex].team_id;
                        match.teamB_name = shuffledTeams[teamIndex].team_name;
                        teamIndex++;
                    }
                }

                // Clean up empty matches
                matches[0] = matches[0].filter(match => match.teamA_id || match.teamB_id);

                // Initialize third place match
                matches['third-place'] = {
                    match_id: 'third-place',
                    teamA_id: null,
                    teamA_name: '(Semifinal 1 Loser)',
                    teamB_id: null,
                    teamB_name: '(Semifinal 2 Loser)',
                    winner_id: null,
                    loser_id: null
                };

                // Generate and display the bracket
                const bracketHTML = createBracketHTML(matches);
                $('#bracket-container').html(bracketHTML);
                
                attachClickHandlers();
                updateSaveButtonState();
            },
            error: function(xhr, status, error) {
                console.error('Error fetching teams:', error);
                $('#bracket-container').html('<div class="alert alert-danger">Failed to fetch teams. Please try again.</div>');
            }
        });
    }

    function createBracketHTML(matches) {
        let html = '<div class="tournament-bracket">';
        
        // Calculate total rounds excluding third place
        const totalRounds = Object.keys(matches).filter(key => !isNaN(key)).length;
        const firstRoundMatches = matches[0].length;
        const matchHeight = 100;
        const totalHeight = firstRoundMatches * matchHeight;
        
        // Generate main bracket
        for (let round = 0; round < totalRounds; round++) {
            html += `<div class="round round-${round + 1}">
                <h4 class="round-header" data-round="${round + 1}">Round ${round + 1}</h4>
                <div class="matches-wrapper" style="height: ${totalHeight}px">`;
            
            const matchesInRound = matches[round].length;
            
            for (let i = 0; i < matchesInRound; i++) {
                const position = calculateMatchPosition(round, i, matchesInRound, totalHeight, matchHeight);
                html += createMatchHTML(matches[round][i], round, i, position);
            }
            
            html += '</div></div>';
        }

        // Add third place playoff match
        html += createMatchHTML(matches['third-place'], totalRounds - 1, 0, null, true);
        
        html += '</div>';
        return html;
    }

    function calculateMatchPosition(round, matchIndex, matchesInRound, totalHeight, matchHeight) {
        if (round === 0) {
            return matchIndex * matchHeight;
        } else {
            const parentIndexes = [matchIndex * 2, matchIndex * 2 + 1];
            const prevRoundMatches = matchesInRound * 2;
            const firstParentPos = parentIndexes[0] * (totalHeight / prevRoundMatches);
            const secondParentPos = parentIndexes[1] * (totalHeight / prevRoundMatches);
            return (firstParentPos + secondParentPos) / 2;
        }
    }

    function createMatchHTML(match, round, index, position, isThirdPlace = false) {
        let matchType = 'regular';
        if (isThirdPlace) {
            matchType = 'third_place';
        } else if (round === matches.length - 1) {
            matchType = 'final';
        } else if (round === matches.length - 2) {
            matchType = 'semifinal';
        }

        const teamADisplay = match.teamA_name || '---';
        const teamBDisplay = match.teamB_name || '---';
        
        const positionStyle = position !== undefined ? `style="top: ${position}px"` : '';
        const matchClass = isThirdPlace ? 'third-place-match' : 'match';
        
        return `
            <div class="${matchClass}" data-match-id="${match.match_id}" 
                 data-round="${round + 1}" data-position="${index + 1}" 
                 data-match-type="${matchType}" ${positionStyle}>
                <div class="team team-top ${match.teamA_id ? 'has-team' : ''} ${match.winner_id === match.teamA_id ? 'winner' : ''} ${match.loser_id === match.teamA_id ? 'loser' : ''} ${match.teamA_id === 0 ? 'bye-team' : ''}">
                    <span class="team-name">${teamADisplay}</span>
                </div>
                <div class="team team-bottom ${match.teamB_id ? 'has-team' : ''} ${match.winner_id === match.teamB_id ? 'winner' : ''} ${match.loser_id === match.teamB_id ? 'loser' : ''} ${match.teamB_id === 0 ? 'bye-team' : ''}">
                    <span class="team-name">${teamBDisplay}</span>
                </div>
                ${isThirdPlace ? '<div class="match-label">Battle for Third</div>' : ''}
            </div>
        `;
    }

    function attachClickHandlers() {
        updateSaveButtonState();
        
        // Remove click handler for match results since they come from live scoring
        $('.match').off('click');

        // Add collapsible rounds
        $('.round-header').on('click', function() {
            const round = $(this).data('round');
            $(this).siblings('.matches-wrapper').toggleClass('collapsed');
        });
    }

    function updateSaveButtonState() {
        // Check if all first round matches have at least one valid team
        const firstRoundComplete = matches[0].every(match => 
            match.teamA_id || match.teamB_id
        );

        $('#save-bracket').prop('disabled', !firstRoundComplete);
    }

    function saveBracket() {
        if (!window.BRACKET_CONFIG) return;
        
        // Prepare matches data with match types
        const matchesData = {};
        Object.keys(matches).forEach(round => {
            if (round === 'third-place') {
                matchesData[round] = {
                    ...matches[round],
                    match_type: 'third_place',
                    next_match_number: null
                };
                return;
            }
            
            matchesData[round] = matches[round].map((match, index) => {
                let matchType = 'regular';
                // Get total number of rounds (excluding third-place)
                const totalRounds = Object.keys(matches).filter(key => !isNaN(key)).length;
                // Convert round to number for comparison
                const roundNum = parseInt(round);
                if (roundNum === totalRounds - 1) {
                    matchType = 'final';
                } else if (roundNum === totalRounds - 2) {
                    matchType = 'semifinal';
                }

                // Calculate next_match_number
                let nextMatchNumber;
                if (matchType === 'final' || matchType === 'third_place') {
                    nextMatchNumber = null;
                } else if (matchType === 'semifinal') {
                    nextMatchNumber = 0; // Both semifinals feed into finals (match 0)
                } else {
                    // Regular matches: pairs of matches feed into next round
                    nextMatchNumber = Math.floor(index / 2);
                }

                return {
                    ...match,
                    match_type: matchType,
                    next_match_number: nextMatchNumber
                };
            });
        });

        const bracketData = {
            matches: matchesData,
            department_id: window.BRACKET_CONFIG.departmentId,
            game_id: window.BRACKET_CONFIG.gameId,
            bracket_type: 'single'
        };

        // Only add grade_level if it's selected and not empty
        const gradeLevel = window.BRACKET_CONFIG.gradeLevel;
        if (gradeLevel && gradeLevel !== '') {
            bracketData.grade_level = gradeLevel;
        }

        $.ajax({
            url: 'save_bracket.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(bracketData),
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Bracket has been saved successfully.',
                        icon: 'success'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: response.message || 'Failed to save bracket.',
                        icon: 'error'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to save bracket. Please try again.',
                    icon: 'error'
                });
            }
        });
    }

    // Handle save bracket button click
    $('#save-bracket').on('click', saveBracket);
});

function getTeamNameById(teamId) {
    const team = availableTeams.find(team => team.team_id === teamId);
    return team ? team.team_name : 'Unknown';
}
