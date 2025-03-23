document.getElementById('exportSchedules').addEventListener('click', function () {
    const department = document.getElementById('filterDepartment').value;
    const gradeLevel = document.getElementById('filterGradeLevel').value;
    const game = document.getElementById('filterGame').value;

    // ✅ Check if all filters are empty
    if (!department && !gradeLevel && !game) {
        Swal.fire({
            icon: 'warning',
            title: 'No Filters Selected!',
            text: 'Please select at least one filter before exporting.',
        });
        return; // Stop execution if no filter is provided
    }

    const formData = new FormData();
    formData.append('department', department);
    formData.append('gradeLevel', gradeLevel);
    formData.append('game', game);

    fetch('download-schedules/download_schedules.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            const disposition = response.headers.get('Content-Disposition');
            let filename = 'Schedule.xlsx';

            if (disposition) {
                const match = disposition.match(/filename="([^"]+)"/);
                if (match) {
                    filename = decodeURIComponent(match[1]);
                }
            }

            return response.blob().then(blob => ({ blob, filename }));
        } else {
            throw new Error('Failed to generate file');
        }
    })
    .then(({ blob, filename }) => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        a.remove();

        Swal.fire({
            icon: 'success',
            title: 'Export Successful!',
            text: 'The schedule has been downloaded.',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000
        });
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Export Failed!',
            text: 'There was an issue generating the file.',
        });
    });
});
