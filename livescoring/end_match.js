// Ensure utils is defined
if (typeof utils === "undefined") {
    var utils = {
        getElement: (id) => document.getElementById(id),
        showAlert: (options) => Swal.fire(options)
    };
}

function initializeEndMatchButton() {
    const endMatchButton = utils.getElement('end-match-button');

    if (endMatchButton) {
        endMatchButton.addEventListener('click', async () => {
            try {
                const scheduleId = utils.getElement('schedule_id')?.value;
                
                // First fetch the bracket type
                const bracketResponse = await fetch('helper/get_bracket_type.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ schedule_id: scheduleId })
                });

                const bracketData = await bracketResponse.json();
                if (!bracketData.success) {
                    throw new Error(bracketData.error || 'Failed to get bracket type');
                }

                // Determine which endpoint to use based on bracket type
                const endpoint = bracketData.bracket_type === 'round_robin' 
                    ? 'process_round_robin/end_match_default.php'
                    : 'process_end_mtch_df.php';

                console.log('Using endpoint:', endpoint); // Debug log

                // Show confirmation dialog
                const result = await utils.showAlert({
                    title: 'End Match?',
                    text: 'Are you sure you want to end this match?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No'
                });

                if (result.isConfirmed) {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ schedule_id: scheduleId })
                    });

                    const data = await response.json();
                    console.log("Server Response:", data);

                    if (data.success) {
                        console.log("Match ID received:", data.match_id);

                        // Clear state using stateManager
                        if (typeof stateManager !== 'undefined' && stateManager.clear) {
                            stateManager.clear();
                        }

                        // Clear any other states
                        localStorage.removeItem('defaultScoreboardState');
                      //  localStorage.removeItem('basketballScoreboardState');

                        const result = await utils.showAlert({
                            title: 'Match Ended!',
                            text: 'The match has concluded successfully.',
                            icon: 'success',
                            showCancelButton: true,
                            confirmButtonText: 'View Summary',
                            cancelButtonText: 'Back to Matches'
                        });

                        if (result.isConfirmed) {
                            window.location.href = `match_summary.php?match_id=${data.match_id}&status=${data.status}`;
                        } else {
                            window.location.href = 'match_list.php';
                        }
                    } else {
                        if (data.overtime_required) {
                            const otResult = await utils.showAlert({
                                title: 'Overtime Required',
                                text: data.error,
                                icon: 'info',
                                showCancelButton: true,
                                confirmButtonText: 'Start Overtime',
                                cancelButtonText: 'Cancel'
                            });

                            if (otResult.isConfirmed) {
                                utils.getElement('periods').value = 'OT';
                                timerManager.setTime(5 * 60); // 5 minutes
                                timerManager.pause();
                                scoreManager.sendUpdate();
                            }
                        } else {
                            throw new Error(data.error || 'Failed to end match');
                        }
                    }
                }
            } catch (error) {
                console.error('Error ending match:', error);
                utils.showAlert({
                    title: 'Error',
                    text: error.message || 'Failed to end match. Please try again.',
                    icon: 'error'
                });
            }
        });
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', initializeEndMatchButton);
