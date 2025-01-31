// Bracket generation and management functionality
$(document).ready(function() {
    if (!window.BRACKET_CONFIG) {
        console.error('Bracket configuration is not available');
        return;
    }

    // Initialize the bracket manager with config
    const bracketManager = new BracketManager(window.BRACKET_CONFIG);
});

// BracketManager class for handling tournament brackets
class BracketManager {
    constructor(config) {
        this.config = config;
        this.availableTeams = [];
        this.bracketData = null;
        this.domElements = {
            container: $('#bracket-container'),
            saveButton: $('#save-bracket'),
            generateButton: $('#generateBracket'),
            gradeLevelSelect: $('#gradeLevelSelect')
        };
        this.initializeEventListeners();
    }

    // Initialize all event listeners
    initializeEventListeners() {
        this.domElements.generateButton.on('click', () => this.handleGenerateBracket());
        this.domElements.saveButton.on('click', () => this.handleSaveBracket());
        if (!this.config.isCollege) {
            this.domElements.gradeLevelSelect.on('change', () => this.handleGradeLevelChange());
            this.loadGradeLevels();
        }
    }

    // Load grade levels for non-college brackets
    loadGradeLevels() {
        $.ajax({
            url: 'fetch_grade_levels.php',
            method: 'GET',
            success: (response) => {
                if (response.success && response.gradeLevels && Array.isArray(response.gradeLevels)) {
                    const gradeSelect = this.domElements.gradeLevelSelect.empty();
                    gradeSelect.append($('<option>', {
                        value: '',
                        text: 'Select Grade Level'
                    }));
                    response.gradeLevels.forEach(grade => {
                        gradeSelect.append($('<option>', {
                            value: grade.name,
                            text: grade.name
                        }));
                    });
                } else {
                    this.showError('Invalid grade levels response');
                }
            },
            error: (xhr, status, error) => {
                this.showError('Failed to load grade levels. Please refresh the page.');
            }
        });
    }

    // Handle generate bracket button click
    handleGenerateBracket() {
        let gradeLevel = null;
        
        if (!this.config.isCollege) {
            gradeLevel = this.domElements.gradeLevelSelect.val();
            if (!gradeLevel) {
                this.showWarning('Grade Level Required', 'Please select a grade level for non-college departments.');
                return;
            }
        }

        if (!this.config.departmentId) {
            this.showError('Department ID is missing. Please refresh the page and try again.');
            return;
        }

        this.initializeBracket(this.config.departmentId, gradeLevel);
    }

    // Initialize the bracket with teams
    initializeBracket(departmentId, gradeLevel = null) {
        this.showLoading();

        $.ajax({
            url: 'fetch_teams.php',
            method: 'GET',
            data: { department_id: departmentId, grade_level: gradeLevel },
            success: (response) => {
                if (response.success && response.teams && response.teams.length > 0) {
                    const teams = response.teams.map(team => ({
                        ...team,
                        team_name: this.cleanTeamName(team.team_name)
                    }));
                    
                    const totalTeams = teams.length;
                    const totalPositions = this.getNextPowerOfTwo(totalTeams);
                    const numByes = totalPositions - totalTeams;

                    const bracketTeams = this.generateFirstRound(teams, numByes, totalPositions);
                    const results = this.generateEmptyResults(totalPositions);

                    this.bracketData = { teams: bracketTeams, results: results };
                    this.renderBracket(departmentId, gradeLevel);
                } else {
                    const errorMessage = response.success ? 'No teams available for the selected criteria.' : (response.message || 'Failed to load teams.');
                    this.showError(errorMessage);
                }
            },
            error: (xhr, status, error) => {
                this.showError('Failed to load teams. Please try again.');
            }
        });
    }

    // Render the bracket with enhanced UI
    renderBracket(departmentId, gradeLevel) {
        this.domElements.container.empty().addClass('bracket-wrapper');
        
        const cleanedData = {
            teams: this.bracketData.teams.map(matchTeams => 
                matchTeams.map(team => this.renderTeam(team))
            ),
            results: this.bracketData.results
        };

        const mainBracketContainer = $('<div>', {
            class: 'main-bracket'
        }).appendTo(this.domElements.container);

        // Enhanced bracket options with full jQuery bracket functionality
        const bracketOptions = {
            init: cleanedData,
            save: (data) => {
                // Auto-save on bracket updates
                this.bracketData = data;
                this.handleSaveBracket();
            },
            userData: {
                departmentId: departmentId,
                gameId: this.config.gameId,
                gradeLevel: gradeLevel
            },
            decorator: {
                edit: () => {
                    // Allow score editing only if match has both teams
                    return true;
                },
                render: (container, data, score, state) => this.renderTeamSlot(container, data, score, state)
            },
            skipConsolationRound: true,
            centerConnectors: true,
            disableHighlight: false,
            teamWidth: 150,
            scoreWidth: 40,
            roundMargin: 50,
            matchMargin: 30,
            onMatchClick: (data) => this.handleMatchClick(data),
            onMatchHover: (data, hover) => {
                // Add hover effect to matches
                if (hover) {
                    $(data.elem).addClass('hover');
                } else {
                    $(data.elem).removeClass('hover');
                }
            },
            dir: 'lr', // Left to right direction
            skipSecondaryFinal: false,
            skipGrandFinalComeback: true,
            disableToolbar: false,
            disableTeamEdit: true, // Prevent team name editing
            validation: (oldData, newData) => {
                // Validate score updates
                if (newData.score && !isNaN(newData.score[0]) && !isNaN(newData.score[1])) {
                    // Ensure scores are non-negative
                    if (newData.score[0] < 0 || newData.score[1] < 0) {
                        return false;
                    }
                    // Ensure at least one team has a higher score (no ties)
                    if (newData.score[0] === newData.score[1]) {
                        this.showWarning('Invalid Score', 'Scores cannot be tied. One team must win.');
                        return false;
                    }
                    return true;
                }
                return false;
            }
        };

        // Initialize the bracket with enhanced options
        mainBracketContainer.bracket(bracketOptions);

        // Add match hover styles
        $('<style>')
            .text('.bracket .match.hover { background-color: #f0f0f0; cursor: pointer; }')
            .appendTo('head');

        // Add actions container
        const actionContainer = $('<div>', {
            class: 'bracket-actions'
        }).appendTo(this.domElements.container);

        this.domElements.saveButton.appendTo(actionContainer).prop('disabled', false);
        
        // Add third place bracket if needed
        if (this.bracketData.results[0].length > 2) {
            this.renderThirdPlaceMatch();
        }

        // Enable tooltips for long team names
        this.initializeTooltips();

        // Add bracket controls if needed
        this.addBracketControls(mainBracketContainer);
    }

    // Add additional bracket controls
    addBracketControls(bracketContainer) {
        const controls = $('<div>', {
            class: 'bracket-controls'
        }).insertBefore(bracketContainer);

        // Add zoom controls
        const zoomControls = $('<div>', {
            class: 'zoom-controls'
        }).appendTo(controls);

        $('<button>', {
            class: 'btn btn-sm btn-outline-secondary mr-2',
            html: '<i class="fas fa-search-plus"></i>',
            click: () => this.zoomBracket(bracketContainer, 1.1)
        }).appendTo(zoomControls);

        $('<button>', {
            class: 'btn btn-sm btn-outline-secondary',
            html: '<i class="fas fa-search-minus"></i>',
            click: () => this.zoomBracket(bracketContainer, 0.9)
        }).appendTo(zoomControls);
    }

    // Handle bracket zooming
    zoomBracket(container, factor) {
        const currentScale = parseFloat(container.css('transform').split(',')[3]) || 1;
        const newScale = currentScale * factor;
        if (newScale >= 0.5 && newScale <= 2) {
            container.css('transform', `scale(${newScale})`);
        }
    }

    // Render individual team slot with enhanced UI
    renderTeamSlot(container, data, score, state) {
        container.empty();
        
        const teamName = data && typeof data === 'object' ? data.team_name : 
                        typeof data === 'string' ? data : null;
        
        const teamElement = $('<div>', { class: 'team-slot' });
        
        switch (state) {
            case "empty-bye":
                teamElement.append("<span class='team-name bye'>BYE</span>");
                break;
            case "empty-tbd":
                teamElement.append("<span class='team-name tbd'>TBD</span>");
                break;
            default:
                if (teamName) {
                    const cleanedName = this.cleanTeamName(teamName);
                    const nameSpan = $('<span>', {
                        class: 'team-name',
                        text: this.truncateTeamName(cleanedName),
                        title: cleanedName
                    });
                    teamElement.append(nameSpan);
                    
                    if (score !== null) {
                        teamElement.append(`<span class="team-score">(${score})</span>`);
                    }
                } else {
                    teamElement.append("<span class='team-name tbd'>TBD</span>");
                }
        }
        
        container.append(teamElement);
    }

    // Handle match click for potential match details modal
    handleMatchClick(data) {
        const match = data.match;
        if (!match) return;

        // Get teams in the match
        const team1 = match.teams[0]?.team_name || 'TBD';
        const team2 = match.teams[1]?.team_name || 'TBD';
        const score1 = match.score?.[0] || 0;
        const score2 = match.score?.[1] || 0;

        // Show match details modal
        Swal.fire({
            title: 'Match Details',
            html: `
                <div class="match-details">
                    <div class="team-detail">
                        <h5>${team1}</h5>
                        <input type="number" class="form-control score-input" id="score1" value="${score1}" min="0">
                    </div>
                    <div class="vs">VS</div>
                    <div class="team-detail">
                        <h5>${team2}</h5>
                        <input type="number" class="form-control score-input" id="score2" value="${score2}" min="0">
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Update Score',
            cancelButtonText: 'Cancel',
            preConfirm: () => {
                const newScore1 = parseInt($('#score1').val());
                const newScore2 = parseInt($('#score2').val());
                
                if (isNaN(newScore1) || isNaN(newScore2) || newScore1 < 0 || newScore2 < 0) {
                    Swal.showValidationMessage('Please enter valid scores');
                    return false;
                }
                
                if (newScore1 === newScore2) {
                    Swal.showValidationMessage('Scores cannot be tied');
                    return false;
                }
                
                return [newScore1, newScore2];
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const [newScore1, newScore2] = result.value;
                match.score = [newScore1, newScore2];
                this.domElements.container.find('.main-bracket').bracket('refresh');
                this.handleSaveBracket();
            }
        });
    }

    // Initialize tooltips for long team names
    initializeTooltips() {
        $('.team-name').each(function() {
            const $this = $(this);
            if ($this.prop('scrollWidth') > $this.width()) {
                $this.tooltip({
                    title: $this.text(),
                    placement: 'top',
                    container: 'body'
                });
            }
        });
    }

    // Utility functions
    cleanTeamName(name) {
        if (!name) return null;
        
        // Handle special case of ( _ _ ) or (__ __)
        if (name.match(/\(\s*[_]{2}\s*\)$/)) {
            return name.replace(/\s*\(\s*[_]{2}\s*\)$/, '').trim();
        }
        
        // Handle other cases
        return name.replace(/\s*\([_-\s]+\)\s*$/, '')  // Remove (--) or (_ _)
                  .replace(/\s*\([^)]*[-_]{2}[^)]*\)\s*$/, '')  // Remove anything with -- or __
                  .replace(/[-_]{2,}\s*$/, '')  // Remove trailing -- or __
                  .replace(/\s*\([^)]*\)\s*$/, '')  // Remove any remaining parentheses at the end
                  .trim();
    }

    truncateTeamName(name, maxLength = 25) {
        return name.length > maxLength ? `${name.substring(0, maxLength)}...` : name;
    }

    getNextPowerOfTwo(n) {
        return Math.pow(2, Math.ceil(Math.log2(n)));
    }

    generateEmptyResults(totalPositions) {
        const rounds = Math.log2(totalPositions);
        const results = [];
        for (let i = 0; i < rounds; i++) {
            results.push(Array(Math.pow(2, rounds - i - 1)).fill([null, null]));
        }
        return results;
    }

    // UI feedback methods
    showLoading() {
        this.domElements.container.html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading bracket...</div>');
    }

    showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message
        });
    }

    showWarning(title, message) {
        Swal.fire({
            icon: 'warning',
            title: title,
            text: message
        });
    }

    showSuccess(message) {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: message
        });
    }

    // Handle save bracket click
    handleSaveBracket() {
        const bracketData = this.formatBracketForSave();
        
        $.ajax({
            url: 'save_bracket.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(bracketData),
            success: (response) => {
                if (response.success) {
                    this.showSuccess('Bracket saved successfully.');
                } else {
                    this.showError(response.message || 'Failed to save bracket.');
                }
            },
            error: (xhr, status, error) => {
                console.error('Save error:', error);
                console.error('Server response:', xhr.responseText);
                this.showError('Failed to save bracket. Please try again.');
            }
        });
    }

    // Format bracket data for saving
    formatBracketForSave() {
        const bracketData = this.domElements.container.find('.main-bracket').bracket('data');
        const matches = [];
        
        // Count actual teams (excluding nulls and BYEs)
        let actualTeamCount = 0;
        bracketData.teams.forEach(matchup => {
            if (matchup[0]?.team_id && matchup[0]?.team_id !== -1) actualTeamCount++;
            if (matchup[1]?.team_id && matchup[1]?.team_id !== -1) actualTeamCount++;
        });

        // Calculate total rounds needed
        const totalRounds = Math.ceil(Math.log2(actualTeamCount));
        
        // Calculate number of matches per round
        const matchesPerRound = [];
        for (let i = 0; i < totalRounds; i++) {
            matchesPerRound[i] = Math.pow(2, totalRounds - i - 1);
        }
        
        // Process first round matches
        bracketData.teams.forEach((matchup, matchIndex) => {
            matches[0] = matches[0] || [];
            const nextMatchNumber = Math.floor(matchIndex / 2);
            
            matches[0].push({
                teamA_id: matchup[0]?.team_id || null,
                teamB_id: matchup[1]?.team_id || null,
                teamA_name: matchup[0]?.team_name || null,
                teamB_name: matchup[1]?.team_name || null,
                match_type: totalRounds === 1 ? 'final' : 'regular',
                round: 1,
                match_number: matchIndex,
                next_match_number: nextMatchNumber
            });
        });

        // Generate subsequent rounds
        for (let round = 1; round < totalRounds; round++) {
            matches[round] = [];
            const numMatches = matchesPerRound[round];
            const isFinalRound = round === totalRounds - 1;
            const isSemiFinalRound = round === totalRounds - 2;
            
            for (let matchIndex = 0; matchIndex < numMatches; matchIndex++) {
                let matchType = 'regular';
                let nextMatchNumber = Math.floor(matchIndex / 2);

                // Determine match type and next match number
                if (isFinalRound) {
                    matchType = 'final';
                    nextMatchNumber = 0;
                } else if (isSemiFinalRound) {
                    matchType = 'semifinal';
                    nextMatchNumber = 0;
                }

                matches[round].push({
                    teamA_id: null,
                    teamB_id: null,
                    teamA_name: null,
                    teamB_name: null,
                    match_type: matchType,
                    round: round + 1,
                    match_number: matchIndex,
                    next_match_number: nextMatchNumber
                });
            }
        }

        // Add third place match if we have semifinals (3 or more rounds)
        if (totalRounds >= 3) {
            matches['third-place'] = {
                teamA_id: null,
                teamB_id: null,
                teamA_name: 'Semifinal Loser 1',
                teamB_name: 'Semifinal Loser 2',
                match_type: 'third_place',
                round: totalRounds,
                match_number: -1,
                next_match_number: 0
            };
        }

        return {
            matches: matches,
            department_id: this.config.departmentId,
            game_id: this.config.gameId,
            grade_level: this.config.gradeLevel,
            total_teams: actualTeamCount,
            total_rounds: totalRounds,
            bracket_type: 'single'
        };
    }

    // Render third place match
    renderThirdPlaceMatch() {
        const thirdPlaceContainer = $('<div>', {
            class: 'third-place-section'
        }).appendTo(this.domElements.container);
        
        const thirdPlaceMatch = {
            teams: [["Semifinal Loser 1", "Semifinal Loser 2"]],
            results: [[null, null]]
        };
        
        thirdPlaceContainer.bracket({
            init: thirdPlaceMatch,
            skipConsolationRound: true,
            centerConnectors: true,
            teamWidth: 150,
            scoreWidth: 40,
            decorator: {
                edit: () => {},
                render: (container, data, score, state) => {
                    container.empty();
                    if (state === "empty-tbd" || !data) {
                        container.append("<span class='team-name tbd'>Semifinal Loser</span>");
                    } else {
                        container.append(`<span class="team-name">${data}</span>`);
                        if (score !== null) {
                            container.append(`<span class="team-score">(${score})</span>`);
                        }
                    }
                }
            }
        });
    }

    // Handle grade level selection change
    handleGradeLevelChange() {
        const selectedGrade = this.domElements.gradeLevelSelect.val();
        if (this.config.isCollege) return; // Ignore for college department
        
        // Update the config
        this.config.gradeLevel = selectedGrade;
        
        // Clear existing bracket when grade level changes
        this.domElements.container.html(`
            <div class="bracket-empty">
                <i class="fas fa-trophy"></i>
                <p>Click "Generate New Bracket" to create a bracket for ${selectedGrade || 'selected grade level'}.</p>
            </div>
        `);
        this.domElements.saveButton.prop('disabled', true);
    }

    // Generate first round matches
    generateFirstRound(teams, numByes, totalPositions) {
        const bracketTeams = [];
        let teamIndex = 0;
        const matchesInRound = totalPositions / 2;

        // Create an array of positions for BYE distribution
        const positions = Array.from({ length: matchesInRound * 2 }, (_, i) => i);
        
        // Shuffle positions to randomly distribute BYEs
        for (let i = positions.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [positions[i], positions[j]] = [positions[j], positions[i]];
        }

        // Sort BYE positions to ensure they're evenly distributed
        const byePositions = positions.slice(0, numByes);
        byePositions.sort((a, b) => a - b);

        // Create matches with BYEs in their positions
        for (let i = 0; i < matchesInRound; i++) {
            const teamAPos = i * 2;
            const teamBPos = i * 2 + 1;
            
            const teamA = byePositions.includes(teamAPos) ? null :
                         (teamIndex < teams.length ? this.renderTeam(teams[teamIndex++]) : null);
            
            const teamB = byePositions.includes(teamBPos) ? null :
                         (teamIndex < teams.length ? this.renderTeam(teams[teamIndex++]) : null);
            
            bracketTeams.push([teamA, teamB]);
        }

        return bracketTeams;
    }

    // Render team data
    renderTeam(team) {
        if (!team) return null;
        const cleanedName = this.cleanTeamName(team.team_name || team);
        return typeof team === 'object' ? { ...team, team_name: cleanedName } : cleanedName;
    }
}