function singleBracket() {
    // ... existing single bracket code ...
}

function doubleBracket(gameId, departmentId) {
    let bracketManager;
    let generatedStructure;

    $(document).ready(async function() {
        try {
            // Initialize double bracket manager
            const module = await import('./bracket-init.js');
            bracketManager = module.initDoubleBracket(
                gameId,
                departmentId,
                $('#gradeLevelSelect').val()
            );

            // Add grade level change handler
            $('#gradeLevelSelect').on('change', function() {
                const selectedGrade = $(this).val();
                console.log('Grade level changed to:', selectedGrade);
                bracketManager.gradeLevel = selectedGrade;
                $('#bracket-container').empty();
                $('#save-bracket').prop('disabled', true);
                $('#generate-bracket').prop('disabled', false);
            });

            // Generate bracket button click handler
            $('#generate-bracket').click(async function() {
                try {
                    $(this).prop('disabled', true);
                    $('#bracket-container').html('<div class="text-center"><div class="spinner-border" role="status"></div><div>Generating bracket...</div></div>');

                    const teams = await bracketManager.fetchTeams();
                    const structure = bracketManager.generateDoubleBracketStructure();
                    bracketManager.initializeBracketDisplay();
                    
                    $('#save-bracket').prop('disabled', false);
                    generatedStructure = structure;

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
        } catch (error) {
            console.error('Error initializing double bracket:', error);
        }
    });
}

function initializeSingleBracket(bracketManager) {
    let generatedStructure;

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
        for (let i = 0; i < totalSlots/2; i++) {
            pairs[i] = [null, null];
        }

        // Place teams according to seeding
        seeds.forEach((seedIndex, seed) => {
            if (seedIndex === -1) return; // Skip BYE positions
            const position = getSeedPosition(seed, totalSlots/2);
            const isTop = seed % 2 === 0;
            
            if (position >= 0 && position < pairs.length) {
                const team = teams[seedIndex];
                pairs[position][isTop ? 0 : 1] = team ? team.team_name : null;
            }
        });

        return pairs;
    }

    return async function() {
        try {
            $('#bracket-container').html('<div class="text-center"><div class="spinner-border" role="status"></div><div>Generating bracket...</div></div>');

            const teams = await bracketManager.fetchTeams();
            
            // Calculate rounds needed based on team count
            const totalTeams = teams.length;
            const rounds = Math.ceil(Math.log2(totalTeams));
            const totalSlots = Math.pow(2, rounds);
            
            // Create properly seeded team pairings
            const bracketTeams = createBracketPairings(teams, totalSlots);
            console.log('Seeded teams:', bracketTeams);

            // Initialize empty results array
            const results = [];
            for (let i = 0; i < rounds; i++) {
                results.push([]);
            }

            // Create initial bracket data
            const initialBracketData = {
                teams: bracketTeams,
                results: results
            };

            // Generate initial structure
            generatedStructure = bracketManager.convertBracketData(initialBracketData, teams, rounds);
            console.log('Initial bracket structure:', generatedStructure);

            // Initialize the bracket with jQuery Bracket
            $('#bracket-container').bracket({
                teamWidth: 150,
                scoreWidth: 40,
                matchMargin: 50,
                roundMargin: 50,
                init: initialBracketData,
                save: async function(data, userData) {
                    // Update structure when bracket changes
                    generatedStructure = bracketManager.convertBracketData(data, teams, rounds);
                    console.log('Updated bracket structure:', generatedStructure);
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
            return generatedStructure;
            
        } catch (error) {
            console.error('Error generating bracket:', error);
            throw error;
        }
    };
} 