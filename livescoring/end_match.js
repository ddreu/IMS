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
        endMatchButton.addEventListener('click', () => {
            utils.showAlert({
                title: 'End Match?',
                text: 'Are you sure you want to end this match?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No'
            }).then((result) => {
                if (result.isConfirmed) {
                    const scheduleId = utils.getElement('schedule_id')?.value;

                    fetch('process_end_mtch_df.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ schedule_id: scheduleId })
                        })
                        .then(response => response.json())
                        .then(response => {
                            console.log("Server Response:", response); // ✅ Debugging

                            if (response.success) {
                                console.log("Match ID received:", response.match_id); // ✅ Debugging

                                utils.showAlert({
                                    title: 'Match Ended!',
                                    text: 'The match has concluded successfully.',
                                    icon: 'success',
                                    showCancelButton: true,
                                    confirmButtonText: 'View Summary',
                                    cancelButtonText: 'Back to Matches'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // ✅ Correct way to use match ID from response
                                        window.location.href = `match_summary.php?match_id=${response.match_id}&status=${response.status}`;
                                    } else {
                                        window.location.href = 'match_list.php';
                                    }
                                });
                            } else {
                                if (response.overtime_required) {
                                    utils.showAlert({
                                        title: 'Overtime Required',
                                        text: response.error,
                                        icon: 'info',
                                        showCancelButton: true,
                                        confirmButtonText: 'Start Overtime',
                                        cancelButtonText: 'Cancel'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            utils.getElement('periods').value = 'OT';
                                            timerManager.setTime(5 * 60); // 5 minutes
                                            timerManager.pause();
                                            scoreManager.sendUpdate();
                                        }
                                    });
                                } else {
                                    throw new Error(response.error || 'Failed to end match');
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error ending match:', error);
                            utils.showAlert({
                                title: 'Error',
                                text: error.message || 'Failed to end match. Please try again.',
                                icon: 'error'
                            });
                        });
                }
            });
        });
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', initializeEndMatchButton);
