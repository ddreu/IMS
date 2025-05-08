// Ensure global ScoreManager is available even if not loaded immediately
(function() {
    // Create global ScoreManager if not exists
    if (!window.ScoreManager) {
        window.ScoreManager = {};
    }

    // Comprehensive AJAX method
    window.ScoreManager.sendAjax = function(url, data) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-Type', 'application/json');

            xhr.onload = function() {
                console.group('XHR Request Details');
                console.log('URL:', url);
                console.log('Status:', xhr.status);
                console.log('Response Text:', xhr.responseText);
                console.groupEnd();

                try {
                    const response = JSON.parse(xhr.responseText);
                    if (xhr.status >= 200 && xhr.status < 300) {
                        resolve(response);
                    } else {
                        reject(new Error(`HTTP error! status: ${xhr.status}, response: ${xhr.responseText}`));
                    }
                } catch (e) {
                    reject(new Error(`Parse error: ${e.message}, raw response: ${xhr.responseText}`));
                }
            };

            xhr.onerror = function() {
                console.group('XHR Error Details');
                console.error('Network Error Details:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText
                });
                console.groupEnd();

                reject(new Error('Network error occurred'));
            };

            xhr.ontimeout = function() {
                console.error('Request timed out');
                reject(new Error('Request timed out'));
            };

            // Set a timeout of 10 seconds
            xhr.timeout = 10000;

            // Convert data to JSON and send
            xhr.send(JSON.stringify(data));
        });
    };

    // Comprehensive score update method
    window.ScoreManager.sendScoreUpdate = function() {
        // Collect current sets won from display
        const teamA_sets_won = parseInt(document.getElementById('teamA-sets').textContent);
        const teamB_sets_won = parseInt(document.getElementById('teamB-sets').textContent);

        // Collect all required data
        const data = {
            schedule_id: document.getElementById('schedule_id').value,
            game_id: document.getElementById('game_id').value,
            teamA_id: document.getElementById('teamA_id').value,
            teamB_id: document.getElementById('teamB_id').value,
            teamA_score: parseInt(document.getElementById('scoreA').textContent),
            teamB_score: parseInt(document.getElementById('scoreB').textContent),
            teamA_sets_won: teamA_sets_won,
            teamB_sets_won: teamB_sets_won,
            current_set: parseInt(document.getElementById('currentSet').textContent),
            timeout_teamA: parseInt(document.getElementById('teamA-timeouts').textContent),
            timeout_teamB: parseInt(document.getElementById('teamB-timeouts').textContent)
        };

        console.group('Score Update Attempt');
        console.log('Sending Data:', data);
        console.groupEnd();

        // Send AJAX request
        this.sendAjax('update_set_score.php', data)
            .then(response => {
                console.group('Score Update Response');
                console.log('Full Response:', response);
                console.log('Success:', response.success);
                console.groupEnd();

                if (!response.success) {
                    console.error('Failed to update score', response);
                    Swal.fire({
                        icon: 'error',
                        title: 'Update Failed',
                        text: 'Could not update match score: ' + (response.error || 'Unknown error')
                    });
                }
            })
            .catch(error => {
                console.error('Error updating score:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Could not connect to server: ' + error.message
                });
            });
    };
})();

document.addEventListener('DOMContentLoaded', function() {
    // Expose ScoreManager globally
    window.ScoreManager = {
        scheduleId: document.getElementById('schedule_id').value,
        matchId: document.getElementById('match-id').value,
        teamAId: document.getElementById('teamA_id').value,
        teamBId: document.getElementById('teamB_id').value,

        saveState: function() {
            // Save only UI-specific state, excluding sets won
            localStorage.setItem('set_test_teamA_timeouts', document.getElementById('teamA-timeouts').innerText);
            localStorage.setItem('set_test_teamB_timeouts', document.getElementById('teamB-timeouts').innerText);
            localStorage.setItem('set_test_teamA_score', document.getElementById('scoreA').innerText);
            localStorage.setItem('set_test_teamB_score', document.getElementById('scoreB').innerText);
            localStorage.setItem('set_test_currentSet', document.getElementById('currentSet').innerText);
            
            console.log('State saved without sets won');
        },

        loadState: function() {
            // Restore other states from localStorage
            const teamATimeouts = localStorage.getItem('set_test_teamA_timeouts');
            const teamBTimeouts = localStorage.getItem('set_test_teamB_timeouts');
            const teamAScore = localStorage.getItem('set_test_teamA_score');
            const teamBScore = localStorage.getItem('set_test_teamB_score');
            const currentSet = localStorage.getItem('set_test_currentSet');

            // Restore UI elements
            if (teamATimeouts) document.getElementById('teamA-timeouts').innerText = teamATimeouts;
            if (teamBTimeouts) document.getElementById('teamB-timeouts').innerText = teamBTimeouts;
            if (teamAScore) document.getElementById('scoreA').innerText = teamAScore;
            if (teamBScore) document.getElementById('scoreB').innerText = teamBScore;
            if (currentSet) document.getElementById('currentSet').innerText = currentSet;

            // Always fetch sets won from database
            this.fetchSetsWon();
        },

        initializeScores: function() {
            // Add null checks
            const scoreAEl = document.getElementById('scoreA');
            const scoreBEl = document.getElementById('scoreB');
            const teamASetsEl = document.getElementById('teamA-sets');
            const teamBSetsEl = document.getElementById('teamB-sets');
            const currentSetEl = document.getElementById('currentSet');
            const teamATimeoutsEl = document.getElementById('teamA-timeouts');
            const teamBTimeoutsEl = document.getElementById('teamB-timeouts');
        
            if (!scoreAEl || !scoreBEl || !teamASetsEl || !teamBSetsEl || !currentSetEl || !teamATimeoutsEl || !teamBTimeoutsEl) {
                console.error('Critical elements missing for score initialization');
                return;
            }

            // Retrieve existing match context from localStorage
            const savedMatchId = localStorage.getItem('current_match_id');
            const currentMatchId = document.getElementById('match-id').value;

            // If match ID has changed, clear only specific match-related data
            if (savedMatchId !== currentMatchId) {
                const matchSpecificKeys = [
                    'set_test_teamA_score', 
                    'set_test_teamB_score', 
                    'set_test_teamA_sets', 
                    'set_test_teamB_sets', 
                    'set_test_currentSet',
                    'set_test_teamA_timeouts',
                    'set_test_teamB_timeouts'
                ];

                matchSpecificKeys.forEach(key => {
                    localStorage.removeItem(key);
                });

                // Save new match ID
                localStorage.setItem('current_match_id', currentMatchId);
            }

            // Retrieve or set initial values
            const teamAScore = localStorage.getItem('set_test_teamA_score') || '0';
            const teamBScore = localStorage.getItem('set_test_teamB_score') || '0';
            const teamASets = '0';
            const teamBSets = '0';
            const currentSet = localStorage.getItem('set_test_currentSet') || '1';
            const teamATimeouts = localStorage.getItem('set_test_teamA_timeouts') || '0';
            const teamBTimeouts = localStorage.getItem('set_test_teamB_timeouts') || '0';

            // Update DOM with retrieved or default values
            scoreAEl.textContent = teamAScore;
            scoreBEl.textContent = teamBScore;
            teamASetsEl.textContent = teamASets;
            teamBSetsEl.textContent = teamBSets;
            currentSetEl.textContent = currentSet;
            teamATimeoutsEl.textContent = teamATimeouts;
            teamBTimeoutsEl.textContent = teamBTimeouts;

            // Send initial update to server
            this.sendScoreUpdate();
        },

        updateScore: function(team, change) {
            const scoreElement = document.getElementById(`score${team}`);
            let currentScore = parseInt(scoreElement.textContent);
            const newScore = Math.max(0, currentScore + change);
            
            scoreElement.textContent = newScore;
            
            // Save to localStorage
            localStorage.setItem(`set_test_${team.toLowerCase()}_score`, newScore);
            
            // Always send update
            this.sendScoreUpdate();
        },

        updateSet: function(change) {
            const setElement = document.getElementById("currentSet");
            let currentSet = parseInt(setElement.textContent);
            const newSet = Math.max(1, currentSet + change);
            
            setElement.textContent = newSet;
            
            // Save to localStorage
            localStorage.setItem('set_test_currentSet', newSet);
            
            // Always send update
            this.sendScoreUpdate();
        },

        updateTimeouts: function(team, change) {
            const timeoutsElement = document.getElementById(`team${team}-timeouts`);
            let currentTimeouts = parseInt(timeoutsElement.textContent);
            const newTimeouts = Math.max(0, currentTimeouts + change);
            
            timeoutsElement.textContent = newTimeouts;
            
            // Save to localStorage
            localStorage.setItem(`set_test_team${team}_timeouts`, newTimeouts);
            
            // Always send update
            this.sendScoreUpdate();
        },

        fetchSetsWon: function() {
            // Get current match and schedule details
            const scheduleId = document.getElementById('schedule_id').value;
            const matchId = document.getElementById('match-id').value;

            console.group('Fetch Sets Won Debug');
            console.log('Schedule ID:', scheduleId);
            console.log('Match ID:', matchId);

            // Return a promise that always resolves
            return new Promise((resolve, reject) => {
                this.sendAjax('fetch_sets_won.php', { 
                    schedule_id: scheduleId,
                    match_id: matchId 
                })
                .then(response => {
                    console.log('Full Response:', response);

                    // Check if response is valid and has success status
                    if (response && response.success) {
                        // Update sets won for both teams
                        const teamASets = response.teamA_sets_won || 0;
                        const teamBSets = response.teamB_sets_won || 0;

                        document.getElementById('teamA-sets').textContent = teamASets;
                        document.getElementById('teamB-sets').textContent = teamBSets;

                        console.log('Sets Won Updated:', {
                            teamA: teamASets,
                            teamB: teamBSets
                        });

                        resolve(response);
                    } else {
                        // Log detailed error information
                        console.error('Failed to fetch sets won:', response);
                        
                        // Reject with an error
                        reject(new Error('Could not retrieve sets won: ' + (response ? response.error : 'Unknown error')));
                    }
                })
                .catch(error => {
                    console.error('Error in fetchSetsWon:', error);
                    
                    // Reject with the error
                    reject(error);
                })
                .finally(() => {
                    console.groupEnd();
                });
            });
        },

        endSet: function() {
            // Collect current set data
            const data = {
                schedule_id: document.getElementById('schedule_id').value,
                match_id: document.getElementById('match-id').value,
                teamA_id: document.getElementById('teamA_id').value,
                teamB_id: document.getElementById('teamB_id').value,
                teamA_score: parseInt(document.getElementById('scoreA').textContent),
                teamB_score: parseInt(document.getElementById('scoreB').textContent),
                current_set: parseInt(document.getElementById('currentSet').textContent),
                timeout_teamA: parseInt(document.getElementById('teamA-timeouts').textContent),
                timeout_teamB: parseInt(document.getElementById('teamB-timeouts').textContent)
            };

            console.group('End Set Data');
            console.log('Sending Data:', data);
            console.groupEnd();

            // Chain promises to handle set end and sets won fetch
            this.sendAjax('process_set_end.php', data)
                .then(response => {
                    console.group('End Set Response');
                    console.log('Full Response:', response);
                    console.groupEnd();

                    if (response.success) {
                        // Attempt to fetch sets won, but don't block if it fails
                        return this.fetchSetsWon()
                            .catch(fetchError => {
                                console.warn('Sets won fetch failed, continuing with UI update', fetchError);
                                return null; // Allow continuation even if fetch fails
                            });
                    } else {
                        throw new Error(response.error || 'Failed to end set');
                    }
                })
                .then(() => {
                    // Reset scores and increment current set
                    window.updateScore('A', -parseInt(document.getElementById('scoreA').textContent));
                    window.updateScore('B', -parseInt(document.getElementById('scoreB').textContent));
                    window.updateSet(1);

                    // Save the new state
                    this.saveState();

                    // Show success notification
                    Swal.fire({
                        icon: 'success',
                        title: 'Set Ended',
                        text: 'The set has been successfully saved and a new set has started.'
                    });
                })
                .catch(error => {
                    console.error('Error ending set:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'Could not end set: ' + error.message
                    });
                });
        },

        confirmEndSet: function() {
            Swal.fire({
                title: 'End Set Confirmation',
                text: 'Are you sure you want to end this set?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, end set!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Call the existing endSet method
                    this.endSet();
                }
            });
        },

        endMatch: function() {
            // Fetch team names
            const teamAName = document.querySelector('#teamA h2').textContent;
            const teamBName = document.querySelector('#teamB h2').textContent;

            // Confirmation dialog first
            Swal.fire({
                title: 'End Match Confirmation',
                html: `Are you sure you want to end the match between <strong>${teamAName}</strong> and <strong>${teamBName}</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, end match!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Validate match state after confirmation
                    const teamACurrentSets = parseInt(document.getElementById('teamA-sets').textContent);
                    const teamBCurrentSets = parseInt(document.getElementById('teamB-sets').textContent);

                    // Optional: Add a minimum set requirement
                    if (teamACurrentSets < 2 && teamBCurrentSets < 2) {
                        Swal.fire({
                            title: 'Cannot End Match',
                            text: 'Match cannot be ended before reaching minimum set requirements.',
                            icon: 'warning'
                        });
                        return;
                    }
                    const rawStats = localStorage.getItem('playerStats');
                    if (rawStats) {
                        try {
                            const parsed = JSON.parse(rawStats);
                    
                            const statsToSubmit = Object.entries(parsed)
                                .filter(([_, value]) => value > 0)
                                .map(([key, value]) => {
                                    const match = key.match(/player_(\d+)_stat_(\d+)/);
                                    if (!match) return null;
                                    const [, playerId, statConfigId] = match;
                                    return {
                                        player_id: playerId,
                                        stat_config_id: statConfigId,
                                        stat_value: value
                                    };
                                }).filter(Boolean);
                    
                            if (statsToSubmit.length > 0) {
                                console.log('*_Submitting player stats before ending match..._*');
                    
                                fetch('save_player_stats.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({
                                        schedule_id: this.scheduleId,
                                        stats: statsToSubmit
                                    })
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        console.log('*_Player stats submitted successfully_*');
                                        localStorage.removeItem('playerStats');
                                    } else {
                                        console.warn('*_Player stats submission failed_*:', data.message || 'Unknown reason');
                                    }
                                })
                                .catch(err => {
                                    console.error('*_Error submitting player stats_*:', err);
                                });
                            }
                        } catch (err) {
                            console.error('*_Invalid playerStats JSON_*:', err);
                        }
                    }
                    // First check bracket type
                    this.sendAjax('helper/get_bracket_type.php', { schedule_id: this.scheduleId })
                        .then(bracketData => {
                            if (!bracketData.success) {
                                throw new Error(bracketData.error || 'Failed to get bracket type');
                            }

                            // Determine endpoint based on bracket type
                            const endpoint = bracketData.bracket_type === 'round_robin' 
                                ? 'process_round_robin/end_match_set-based.php'
                                : 'end_set.php';

                            console.log('Using endpoint:', endpoint);

                            // Send end match request
                            const data = {
                                schedule_id: this.scheduleId,
                                match_id: this.matchId,
                                teamA_id: this.teamAId,
                                teamB_id: this.teamBId
                            };

                            return this.sendAjax(endpoint, data);
                        })
                        .then(response => {
                            if (response.success) {
                                // Clear all set-test states from localStorage
                                localStorage.removeItem('set_test_teamA_timeouts');
                                localStorage.removeItem('set_test_teamB_timeouts');
                                localStorage.removeItem('set_test_teamA_score');
                                localStorage.removeItem('set_test_teamB_score');
                                localStorage.removeItem('set_test_currentSet');
                                localStorage.removeItem('set_test_teamA_sets');
                                localStorage.removeItem('set_test_teamB_sets');

                                Swal.fire({
                                    title: 'Match Ended!',
                                    text: 'The match has concluded successfully.',
                                    icon: 'success',
                                    showCancelButton: true,
                                    confirmButtonText: 'View Summary',
                                    cancelButtonText: 'Back to Matches'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = `match_summary.php?match_id=${this.matchId}&status=${response.status}`;
                                    } else {
                                        window.location.href = 'match_list.php';
                                    }
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: response.error || 'Failed to end match',
                                    icon: 'error'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('End Match Error:', error);
                            Swal.fire({
                                title: 'Error',
                                text: 'Unable to end match. Please try again.',
                                icon: 'error'
                            });
                        });
                }
            });
        },

        debugLog: function(message) {
            console.log(`ScoreManager: ${message}`);
        },

        init: function() {
            this.debugLog('ScoreManager Initialization Started');

            // Extend global functions to trigger ScoreManager updates
            const originalUpdateScore = window.updateScore;
            window.updateScore = function(team, change) {
                // Call original function first
                originalUpdateScore(team, change);
                
                // Then trigger ScoreManager update
                if (window.ScoreManager && typeof ScoreManager.sendScoreUpdate === 'function') {
                    ScoreManager.sendScoreUpdate();
                }
            };

            const originalUpdateSet = window.updateSet;
            window.updateSet = function(change) {
                // Call original function first
                originalUpdateSet(change);
                
                // Then trigger ScoreManager update
                if (window.ScoreManager && typeof ScoreManager.sendScoreUpdate === 'function') {
                    ScoreManager.sendScoreUpdate();
                }
            };

            // Extend global updateTimeouts function
            const originalUpdateTimeouts = window.updateTimeouts;
            window.updateTimeouts = function(team, change) {
                // Call original function first
                originalUpdateTimeouts(team, change);
                
                // Then trigger ScoreManager update
                if (window.ScoreManager && typeof ScoreManager.sendScoreUpdate === 'function') {
                    ScoreManager.sendScoreUpdate();
                }
            };

            // Global ScoreManager setup
            window.ScoreManager.sendAjax = function(url, data) {
                return new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', url, true);
                    xhr.setRequestHeader('Content-Type', 'application/json');

                    xhr.onload = function() {
                        console.group('XHR Request Details');
                        console.log('URL:', url);
                        console.log('Status:', xhr.status);
                        console.log('Response Text:', xhr.responseText);
                        console.groupEnd();

                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (xhr.status >= 200 && xhr.status < 300) {
                                resolve(response);
                            } else {
                                reject(new Error(`HTTP error! status: ${xhr.status}, response: ${xhr.responseText}`));
                            }
                        } catch (e) {
                            reject(new Error(`Parse error: ${e.message}, raw response: ${xhr.responseText}`));
                        }
                    };

                    xhr.onerror = function() {
                        console.error('Network Error:', xhr.status, xhr.statusText);
                        reject(new Error('Network error occurred'));
                    };

                    xhr.ontimeout = function() {
                        console.error('Request timed out');
                        reject(new Error('Request timed out'));
                    };

                    xhr.timeout = 10000;
                    xhr.send(JSON.stringify(data));
                });
            };

            window.ScoreManager.sendScoreUpdate = function() {
                // Collect current sets won from display
                const teamA_sets_won = parseInt(document.getElementById('teamA-sets').textContent);
                const teamB_sets_won = parseInt(document.getElementById('teamB-sets').textContent);

                // Collect all required data
                const data = {
                    schedule_id: document.getElementById('schedule_id').value,
                    game_id: document.getElementById('game_id').value,
                    teamA_id: document.getElementById('teamA_id').value,
                    teamB_id: document.getElementById('teamB_id').value,
                    teamA_score: parseInt(document.getElementById('scoreA').textContent),
                    teamB_score: parseInt(document.getElementById('scoreB').textContent),
                    teamA_sets_won: teamA_sets_won,
                    teamB_sets_won: teamB_sets_won,
                    current_set: parseInt(document.getElementById('currentSet').textContent),
                    timeout_teamA: parseInt(document.getElementById('teamA-timeouts').textContent),
                    timeout_teamB: parseInt(document.getElementById('teamB-timeouts').textContent)
                };

                console.group('Score Update Attempt');
                console.log('Sending Data:', data);
                console.groupEnd();

                // Send AJAX request
                this.sendAjax('update_set_score.php', data)
                    .then(response => {
                        console.group('Score Update Response');
                        console.log('Full Response:', response);
                        console.log('Success:', response.success);
                        console.groupEnd();

                        if (!response.success) {
                            console.error('Failed to update score', response);
                            Swal.fire({
                                icon: 'error',
                                title: 'Update Failed',
                                text: 'Could not update match score: ' + (response.error || 'Unknown error')
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error updating score:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Network Error',
                            text: 'Could not connect to server: ' + error.message
                        });
                    });
            };

            // Existing initialization code...
            this.loadState();
            this.debugLog('ScoreManager Initialization Completed');
        }
    };

    // Initialize ScoreManager
    ScoreManager.init();
});