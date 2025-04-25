
    // function confirmEdit(event) {
    //     event.preventDefault(); // Prevent form from submitting immediately

    //     Swal.fire({
    //         title: 'Are you sure?',
    //         text: "Do you want to save these changes?",
    //         icon: 'question',
    //         showCancelButton: true,
    //         confirmButtonColor: '#3085d6',
    //         cancelButtonColor: '#d33',
    //         confirmButtonText: 'Yes, save changes',
    //         cancelButtonText: 'Cancel'
    //     }).then((result) => {
    //         if (result.isConfirmed) {
    //             // If user confirms, submit the form
    //             document.getElementById('editAnnouncementForm').submit();
    //         }
    //     });

    //     return false; // Prevent form from submitting normally
    // }

    // Add this to your existing JavaScript
    // function editAnnouncement(id) {
    //     fetch('get_announcement.php?id=' + id)
    //         .then(response => response.json())
    //         .then(data => {
    //             document.getElementById('edit_announcement_id').value = data.id;
    //             document.getElementById('edit_title').value = data.title;
    //             document.getElementById('edit_message').value = data.message;

    //             // Set department if it exists and user is School Admin
    //             const departmentSelect = document.getElementById('edit_department');
    //             if (departmentSelect && data.department_id) {
    //                 departmentSelect.value = data.department_id;
    //             }

    //             // Show current image preview if exists
    //             const imagePreview = document.getElementById('imagePreview');
    //             if (data.image) {
    //                 imagePreview.src = `../uploads/${data.image}`;
    //                 imagePreview.style.display = 'block';
    //             } else {
    //                 imagePreview.style.display = 'none';
    //             }

    //             // Show the modal
    //             new bootstrap.Modal(document.getElementById('editAnnouncementModal')).show();
    //         })
    //         .catch(error => {
    //             console.error('Error:', error);
    //             alert('Error loading announcement details');
    //         });
    // }

    // Add preview for new image upload
    // document.getElementById('edit_image').addEventListener('change', function(e) {
    //     const imagePreview = document.getElementById('imagePreview');
    //     if (this.files && this.files[0]) {
    //         const reader = new FileReader();
    //         reader.onload = function(e) {
    //             imagePreview.src = e.target.result;
    //             imagePreview.style.display = 'block';
    //         }
    //         reader.readAsDataURL(this.files[0]);
    //     }
    // });



    document.addEventListener('DOMContentLoaded', () => {
        // Handle view button click
        document.querySelectorAll('.view-btn').forEach(button => {
            button.addEventListener('click', () => {
                const announcementId = button.getAttribute('data-id');
                fetch('../../announcements/fetch_announcement.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `id=${announcementId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Update modal content
                        document.getElementById('announcementModalLabel').textContent = data.title || 'Announcement';
                        document.getElementById('modalMessage').textContent = data.message || 'No message available.';
                        document.getElementById('modalImage').src = data.image_url || 'default-image.jpg';

                        // Show the modal
                        const viewModal = new bootstrap.Modal(document.getElementById('announcementModal'));
                        viewModal.show();
                    })
                    .catch(error => console.error('Error fetching announcement:', error));
            });
        });

        // Handle edit button click
        // document.querySelectorAll('.edit-btn').forEach(button => {
        //     button.addEventListener('click', () => {
        //         const id = button.getAttribute('data-id');
        //         editAnnouncement(id);
        //     });
        // });

        // Handle delete button click
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', () => {
                const announcementId = button.getAttribute('data-id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('../announcements/delete_announcement.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: `announcement_id=${announcementId}`
                            })
                            .then(response => response.text())
                            .then(() => {
                                Swal.fire('Deleted!', 'Your announcement has been deleted.', 'success')
                                    .then(() => location.reload());
                            })
                            .catch(() => {
                                Swal.fire('Error!', 'There was a problem deleting the announcement.', 'error');
                            });
                    }
                });
            });
        });
    });
