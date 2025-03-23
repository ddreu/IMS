document.getElementById('generateReportBtn').addEventListener('click', function () {
    Swal.fire({
        title: 'Choose Report Type',
        input: 'select',
        inputOptions: {
            'match_results': 'Match Results Report',
            'team_performance': 'Team Performance Report',
            'leaderboard': 'Leaderboard Report',
            'match_schedule': 'Match Schedule Report',
            'event_summary': 'Event Summary Report',
            'player_performance': 'Player Performance Report'
        },
        inputPlaceholder: 'Select a report',
        showCancelButton: true,
        confirmButtonText: 'Generate',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const reportType = result.value;
            const schoolId = `<?= htmlspecialchars($_SESSION['school_id']); ?>`;

            // Show loading screen
            Swal.fire({
                title: 'Generating Report...',
                text: 'Please wait while we generate your report.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Use fetch instead of $.ajax
            fetch('../reports/generate_report.php', {
                method: 'POST',
                body: new URLSearchParams({
                    report_type: reportType,
                    school_id: schoolId
                })
            })
            .then(response => {
                if (!response.ok) throw new Error('Failed to generate the report');
                return response.blob(); // For downloading files
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `${reportType}_report.xlsx`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);

                // Close loading and show success toast
                Swal.fire({
                    icon: 'success',
                    toast: true,
                    position: 'top',
                    title: 'Report Generated',
                    text: 'Your report has been downloaded successfully.',
                    showConfirmButton: false,
                    timer: 3000
                });
            })
            .catch(() => {
                // Close loading and show error
                Swal.fire({
                    icon: 'error',
                    toast: true,
                    position: 'top',
                    title: 'Error',
                    text: 'Failed to generate the report. Please try again.',
                    showConfirmButton: false,
                    timer: 3000
                });
            });
        }
    });
});
