<script>
    function singleBracket() {
        let bracketManager;
        let generatedStructure;

        // Function to create bracket pairings
        function createBracketPairings(teams, totalSlots) {
            // Create array of team indices for seeding
            let seeds = [];
            for (let i = 0; i < totalSlots; i++) {
                seeds[i] = i < teams.length ? i : -1; // -1 represents BYE
            }

            // Function to get proper seed position
            function getSeedPosition(seed, roundSize) {
                if (seed < 0) return -1; // BYE position

                // Standard tournament seeding pattern
                let position;
                if (seed % 2 === 0) {
                    position = seed / 2;
                } else {
                    position = roundSize - 1 - Math.floor(seed / 2);
                }
                return position;
            }

            // Create properly seeded pairs
            const pairs = [];
            for (let i = 0; i < totalSlots / 2; i++) {
                pairs[i] = [null, null];
            }

            // Place teams according to seeding
            seeds.forEach((seedIndex, seed) => {
                if (seedIndex === -1) return; // Skip BYE positions
                const position = getSeedPosition(seed, totalSlots / 2);
                const isTop = seed % 2 === 0;

                if (position >= 0 && position < pairs.length) {
                    const team = teams[seedIndex];
                    pairs[position][isTop ? 0 : 1] = team ? team.team_name : null;
                }
            });

            return pairs;
        }

        // Initialize bracket manager with initial grade level
        bracketManager = new BracketManager({
            gameId: <?php echo $game_id; ?>,
            departmentId: <?php echo $department_id; ?>,
            gradeLevel: $('#gradeLevelSelect').val()
        });

        // Add grade level change handler
        $('#gradeLevelSelect').on('change', function() {
            const selectedGrade = $(this).val();
            console.log('Grade level changed to:', selectedGrade);
            bracketManager.gradeLevel = selectedGrade;
            $('#bracket-container').empty();
            $('#save-bracket').prop('disabled', true);
            $('#generate-bracket').prop('disabled', false);
        });

        // Listen for generate event instead of button click
        document.addEventListener('generate', async function() {
            if ($('#bracketTypeSelect').val() !== 'single') return;
            try {
                $(this).prop('disabled', true);
                $('#bracket-container').html('<div class="text-center"><div class="spinner-border" role="status"></div><div>Generating bracket...</div></div>');

                const teams = await bracketManager.fetchTeams();
                const rounds = Math.ceil(Math.log2(teams.length));
                const totalSlots = Math.pow(2, rounds);
                const bracketTeams = createBracketPairings(teams, totalSlots);

                const initialBracketData = {
                    teams: bracketTeams,
                    results: Array(rounds).fill([])
                };

                generatedStructure = bracketManager.convertBracketData(initialBracketData, teams, rounds);

                // Initialize the bracket with jQuery Bracket
                $('#bracket-container').bracket({
                    teamWidth: 150,
                    scoreWidth: 40,
                    matchMargin: 50,
                    roundMargin: 50,
                    init: initialBracketData,
                    save: async function(data, userData) {
                        generatedStructure = bracketManager.convertBracketData(data, teams, rounds);
                    },
                    decorator: {
                        edit: function(container, data, doneCb) {
                            const input = $('<select>').addClass('form-control form-control-sm');
                            input.append($('<option>').val('').text('Select Team'));
                            input.append($('<option>').val('BYE').text('BYE').prop('selected', data === null));

                            // Get all currently selected teams
                            const selectedTeams = [];
                            $('.jQBracket .label').each(function() {
                                const teamName = $(this).text().trim();
                                if (teamName && teamName !== 'BYE') {
                                    selectedTeams.push(teamName);
                                }
                            });

                            // Add available teams
                            teams.forEach(function(team) {
                                if (!selectedTeams.includes(team.team_name) || team.team_name === data) {
                                    const option = $('<option>')
                                        .val(team.team_name)
                                        .text(team.team_name)
                                        .prop('selected', team.team_name === data);

                                    input.append(option);
                                }
                            });

                            container.html(input);

                            input.on('change', function() {
                                const selectedValue = $(this).val();
                                doneCb(selectedValue === 'BYE' || selectedValue === '' ? null : selectedValue);
                            });
                        },
                        render: function(container, team, score) {
                            container.empty();
                            if (team === null) {
                                container.addClass('bye-team bye');
                                container.append('BYE');
                            } else {
                                container.append(team);
                            }
                        }
                    }
                });

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

        // Save bracket button click handler
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
                } else {
                    throw new Error(result.message);
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
    }

    function doubleBracket() {
        let bracketManager;
        let generatedStructure;

        try {
            bracketManager = new DoubleBracketManager({
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

            // Listen for generate event instead of button click
            document.addEventListener('generate', async function() {
                if ($('#bracketTypeSelect').val() !== 'double') return;
                try {
                    $(this).prop('disabled', true);
                    $('#bracket-container').html('<div class="text-center"><div class="spinner-border" role="status"></div><div>Generating bracket...</div></div>');

                    const teams = await bracketManager.fetchTeams();

                    // Generate structure with proper bracket data
                    const structure = bracketManager.generateDoubleBracketStructure();
                    console.log('Generated structure:', structure);

                    // Initialize with just the bracketData property
                    bracketManager.initializeBracketDisplay(structure.bracketData);

                    // Store the structure for saving later
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
        }
    }


    //ROUND ROBIN
    async function roundRobinBracket() {
        console.log('Fetching teams for Round Robin Tournament...');

        let options = {
            gameId: <?php echo $game_id; ?>,
            departmentId: <?php echo $department_id; ?>,
            gradeLevel: $('#gradeLevelSelect').val()
        };

        let tournament = new RoundRobinManager(options);
        let generatedStructure;

        // Listen for generate event
        document.addEventListener('generate', async function() {
            if ($('#bracketTypeSelect').val() !== 'single_round_robin') return;

            try {
                // Fetch teams dynamically
                let teams = await tournament.fetchTeams();

                if (teams.length < 2) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'You need at least two teams for a Round Robin tournament.'
                    });
                    return;
                }

                // Generate matches
                generatedStructure = tournament.generateMatches();
                console.log('Generated structure:', generatedStructure);

                // Populate table inside modal
                let tableBody = document.querySelector('#roundRobinTable tbody');
                tableBody.innerHTML = ''; // Clear existing matches

                // Create team options for dropdowns
                const teamOptions = teams.map(team =>
                    `<option value="${team.team_id}">${team.team_name}</option>`
                ).join('');

                generatedStructure.matches.forEach((match) => {
                    // Find team names from team IDs
                    const teamA = teams.find(t => t.team_id === match.teamA_id);
                    const teamB = teams.find(t => t.team_id === match.teamB_id);

                    let row = document.createElement('tr');
                    row.innerHTML = `
                        <td>Round ${match.round}</td>
                        <td>Match ${match.match_number}</td>
                        <td>
                            <select class="form-select team-select" data-team="A" data-match="${match.match_number}" data-round="${match.round}">
                                <option value="">Select Team A</option>
                                ${teamOptions}
                            </select>
                        </td>
                        <td>
                            <select class="form-select team-select" data-team="B" data-match="${match.match_number}" data-round="${match.round}">
                                <option value="">Select Team B</option>
                                ${teamOptions}
                            </select>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-secondary swap-teams" title="Swap Teams">
                                <i class="fas fa-exchange-alt"></i>
                            </button>
                        </td>
                    `;

                    // Set initial values
                    row.querySelector('select[data-team="A"]').value = teamA?.team_id || '';
                    row.querySelector('select[data-team="B"]').value = teamB?.team_id || '';

                    // Add change handlers
                    row.querySelectorAll('.team-select').forEach(select => {
                        select.addEventListener('change', function() {
                            const matchNumber = this.dataset.match;
                            const round = this.dataset.round;
                            const isTeamA = this.dataset.team === 'A';
                            const selectedTeamId = parseInt(this.value);

                            // Update the structure
                            const match = generatedStructure.matches.find(
                                m => m.round == round && m.match_number == matchNumber
                            );
                            if (match) {
                                if (isTeamA) {
                                    match.teamA_id = selectedTeamId;
                                } else {
                                    match.teamB_id = selectedTeamId;
                                }
                            }
                        });
                    });

                    // Add swap button handler
                    row.querySelector('.swap-teams').addEventListener('click', function() {
                        const selects = row.querySelectorAll('.team-select');
                        const teamASelect = selects[0];
                        const teamBSelect = selects[1];

                        // Swap values
                        const tempValue = teamASelect.value;
                        teamASelect.value = teamBSelect.value;
                        teamBSelect.value = tempValue;

                        // Trigger change events
                        teamASelect.dispatchEvent(new Event('change'));
                        teamBSelect.dispatchEvent(new Event('change'));
                    });

                    tableBody.appendChild(row);
                });

                // Setup save button in modal
                setupRoundRobinSave(options, generatedStructure);

                // Show the modal
                let modalElement = document.getElementById('roundRobinModal');
                let modal = new bootstrap.Modal(modalElement);
                modal.show();

                // Add reset button handler
                document.querySelector('#resetTeams').addEventListener('click', function() {
                    // Confirm reset
                    Swal.fire({
                        title: 'Reset Teams?',
                        text: 'This will clear all team selections. Are you sure?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, reset teams'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Reset all dropdowns to empty selection
                            document.querySelectorAll('#roundRobinTable .team-select').forEach(select => {
                                select.value = '';
                                // Trigger change event to update structure
                                select.dispatchEvent(new Event('change'));
                            });

                            // Reset the structure
                            generatedStructure.matches.forEach(match => {
                                match.teamA_id = null;
                                match.teamB_id = null;
                            });

                            Swal.fire(
                                'Reset Complete',
                                'All team selections have been cleared.',
                                'success'
                            );
                        }
                    });
                });

            } catch (error) {
                console.error("Error generating Round Robin bracket:", error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to generate Round Robin bracket: ' + error.message
                });
            }
        });
    }

    // Separate save function for round robin
    function setupRoundRobinSave(options, generatedStructure) {
        let saveButton = document.querySelector('#saveRoundRobin');
        saveButton.onclick = async () => {
            try {
                // First save the bracket and get the bracket_id
                const bracketResponse = await fetch('save_round_robin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        game_id: options.gameId,
                        department_id: options.departmentId,
                        grade_level: options.gradeLevel,
                        teams: generatedStructure.teams,
                        matches: generatedStructure.matches,
                        rounds: generatedStructure.rounds
                    })
                });

                const bracketResult = await bracketResponse.json();

                if (bracketResult.success) {
                    // Now save the scoring points
                    const scoringResponse = await fetch('save_round_robin_points.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            bracket_id: bracketResult.bracket_id,
                            win_points: parseInt(document.getElementById('winPoints').value) || 3,
                            draw_points: parseInt(document.getElementById('drawPoints').value) || 1,
                            loss_points: parseInt(document.getElementById('lossPoints').value) || 0,
                            bonus_points: parseInt(document.getElementById('bonusPoints').value) || 0
                        })
                    });

                    const scoringResult = await scoringResponse.json();

                    if (scoringResult.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Round Robin tournament and scoring settings have been saved successfully.',
                            icon: 'success'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        throw new Error(scoringResult.message || 'Failed to save scoring settings');
                    }
                } else {
                    throw new Error(bracketResult.message || 'Failed to save tournament');
                }
            } catch (error) {
                console.error('Error saving tournament:', error);
                Swal.fire({
                    title: 'Error!',
                    text: error.message,
                    icon: 'error'
                });
            }
        };
    }

    // Update the bracket type selection handler
    $(document).ready(function() {
        let currentBracketType = null;

        $('#bracketTypeSelect').on('change', function() {
            const selectedValue = $(this).val();
            console.log('Selected bracket type:', selectedValue);

            // Clear existing bracket
            $('#bracket-container').empty();
            $('#save-bracket').prop('disabled', true);

            // Update current type
            currentBracketType = selectedValue;

            // Initialize the selected bracket type
            if (selectedValue === 'single') {
                singleBracket();
            } else if (selectedValue === 'double') {
                doubleBracket();
            } else if (selectedValue === 'single_round_robin') {
                roundRobinBracket();
            }
        });

        // Handle Generate button click
        $('#generate-bracket').on('click', function() {
            // Only trigger generation for the currently selected type
            if (!currentBracketType) return;

            const event = new Event('generate');
            document.dispatchEvent(event);
        });

        $('#bracket-container').html(`
            <div class="text-center p-5">
                <h4>Welcome to Tournament Bracket Generator</h4>
                <p>Select a bracket type and click "Generate Bracket" to begin.</p>
            </div>
        `);

        // Set initial type
        currentBracketType = 'single';
        singleBracket();
    });
</script>