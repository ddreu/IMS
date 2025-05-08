<?php
session_start();
include_once '../connection/conn.php';
$conn = con();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link rel="stylesheet" href="../styles/committee.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
    <style>
        .margin {
            margin-left: 130px;
        }

        @media screen and (max-width: 768px) {
            .margin {
                margin-left: 0;
                padding: 10px;
            }

            /* Stack table cells on mobile */
            .table-responsive-mobile tbody tr {
                display: block;
                margin-bottom: 1rem;
                background: #fff;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                border-radius: 4px;
            }

            .table-responsive-mobile thead {
                display: none;
            }

            .table-responsive-mobile tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.75rem;
                border: none;
                border-bottom: 1px solid #eee;
            }

            .table-responsive-mobile tbody td:last-child {
                border-bottom: none;
            }

            .table-responsive-mobile tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                margin-right: 1rem;
            }

            /* Adjust search and filters for mobile */
            .row.mb-3 {
                margin: 0;
            }

            .col-md-6 {
                padding: 0;
                margin-bottom: 1rem;
            }

            .d-flex.gap-2 {
                flex-direction: column;
                gap: 0.5rem;
            }

            /* Adjust pagination for mobile */
            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .btn-group {
                display: flex;
                width: 100%;
            }

            .btn-group .btn {
                flex: 1;
            }
        }
    </style>
</head>

<body>
    <?php
    include '../navbar/navbar.php';
    if ($_SESSION['role'] === 'Committee') {
        include '../committee/csidebar.php';
    } else {
        include '../department_admin/sidebar.php';
    }
    ?>

    <div class="container mt-5 margin mb-5">
        <h2 class="mb-4">User Logs</h2>

        <!-- Search and Sort Controls -->
        <div class="row mb-3">
            <div class="col-md-6">
                <input type="text" id="searchInput" class="form-control" placeholder="Search logs...">
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-2">
                    <select id="sortColumn" class="form-select">
                        <option value="timestamp">Timestamp</option>
                        <option value="full_name">User</option>
                        <option value="log_action">Action</option>
                        <option value="log_record_id">Record ID</option>
                        <option value="log_description">Description</option>
                    </select>
                    <select id="sortOrder" class="form-select">
                        <option value="DESC">Descending</option>
                        <option value="ASC">Ascending</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-responsive-mobile">
                <thead class="thead-dark">
                    <tr>
                        <th>User</th>
                        <!--<th>Action</th>-->
                        <!-- <th>Operation</th> -->
                        <!--<th>Record ID</th>-->
                        <th>Description</th>
                        <th>Timestamp</th>
                        <!-- <th>Details</th> -->
                    </tr>
                </thead>
                <tbody id="logsTable">
                    <tr>
                        <td colspan="7" class="text-center">Loading logs...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="text-muted">
                Showing <span id="currentPage">1</span> of <span id="totalPages">1</span> pages
            </div>
            <div class="btn-group">
                <button id="prevBtn" class="btn btn-outline-primary" disabled>&laquo; Previous</button>
                <button id="nextBtn" class="btn btn-outline-primary" disabled>Next &raquo;</button>
            </div>
        </div>
    </div>

    <!-- Modal for displaying log details -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel">Log Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Log ID:</strong> <span id="modalLogId"></span></p>
                    <p><strong>Table Name:</strong> <span id="modalTableName"></span></p>
                    <p><strong>Operation:</strong> <span id="modalOperation"></span></p>
                    <p><strong>Record ID:</strong> <span id="modalRecordId"></span></p>
                    <p><strong>Description:</strong> <span id="modalDescription"></span></p>
                    <p><strong>User:</strong> <span id="modalFullName"></span></p>
                    <p><strong>Timestamp:</strong> <span id="modalLogTime"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let usersList = [];

        async function fetchUsers() {
            const res = await fetch('fetch_users_by_school.php');
            const result = await res.json();

            if (result.status === 'success') {
                usersList = result.users;
            } else {
                console.error('Failed to load users');
            }
        }

        let currentPage = 1;
        let totalPages = 1;

        async function fetchLogs() {
            try {
                const sortColumn = document.getElementById('sortColumn').value;
                const sortOrder = document.getElementById('sortOrder').value;
                const response = await fetch(`fetch_logs.php?page=${currentPage}&sort=${sortColumn}&order=${sortOrder}`);
                const result = await response.json();

                if (result.status === 'success') {
                    const logsTable = document.getElementById('logsTable');
                    logsTable.innerHTML = '';

                    // Update pagination info
                    totalPages = result.pagination.total_pages;
                    document.getElementById('currentPage').textContent = result.pagination.current_page;
                    document.getElementById('totalPages').textContent = totalPages;

                    // Update pagination buttons
                    document.getElementById('prevBtn').disabled = currentPage <= 1;
                    document.getElementById('nextBtn').disabled = currentPage >= totalPages;

                    result.data.forEach(log => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                    <td data-label="User">${log.full_name}</td> 

                   

                  <!--  <td data-label="Operation">${log.table_name}</td> -->
<td data-label="Description">${log.log_description}
   
</td>


                

                    <td data-label="Timestamp">${log.log_time}</td>
                   <!-- <td data-label="Details">
                        <button class="btn btn-sm btn-info" onclick="showDetails(${JSON.stringify(log).replace(/"/g, '&quot;')})">
                            View Details
                        </button>
                    </td> -->

                 

                `;
                        logsTable.appendChild(row);
                    });
                }
            } catch (error) {
                console.error('Error fetching logs:', error);
            }
        }


        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchText = this.value.toLowerCase();
            const rows = document.querySelectorAll('#logsTable tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            });
        });

        function showDetails(log) {
            document.getElementById('modalLogId').textContent = log.log_id;
            document.getElementById('modalTableName').textContent = log.table_name;
            document.getElementById('modalOperation').textContent = log.log_action;
            document.getElementById('modalRecordId').textContent = log.log_record_id;
            document.getElementById('modalDescription').textContent = log.log_description;
            document.getElementById('modalFullName').textContent = log.full_name;
            document.getElementById('modalLogTime').textContent = new Date(log.log_time).toLocaleString();
            var myModal = new bootstrap.Modal(document.getElementById('detailsModal'));
            myModal.show();
        }

        // Pagination event listeners
        document.getElementById('prevBtn').addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                fetchLogs();
            }
        });

        document.getElementById('nextBtn').addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                fetchLogs();
            }
        });

        // Sort event listeners
        document.getElementById('sortColumn').addEventListener('change', () => {
            currentPage = 1;
            fetchLogs();
        });

        document.getElementById('sortOrder').addEventListener('change', () => {
            currentPage = 1;
            fetchLogs();
        });

        // Initial fetch
        // fetchLogs();


        //edit logs function

        async function updateLogTimestamp(logId, newTimestamp) {
            try {
                const response = await fetch('update_log_time.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        log_id: logId,
                        timestamp: newTimestamp
                    })
                });

                const result = await response.json();

                if (result.status === 'success') {
                    Swal.fire('Success', 'Timestamp updated.', 'success');
                } else {
                    Swal.fire('Error', result.message || 'Failed to update timestamp.', 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Network error occurred.', 'error');
            }
        }

        //update user

        async function updateLogUser(logId, userId) {
            try {
                const response = await fetch('update_log_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        log_id: logId,
                        user_id: userId
                    })
                });

                const result = await response.json();
                if (result.status === 'success') {
                    Swal.fire('Updated!', 'User has been updated.', 'success');
                } else {
                    Swal.fire('Error!', result.message || 'Update failed.', 'error');
                }
            } catch (err) {
                Swal.fire('Error!', 'Network error.', 'error');
            }
        }

        //delete log
        async function deleteLog(logId) {
            const confirmation = await Swal.fire({
                title: 'Are you sure?',
                text: "This will permanently delete the log.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            });

            if (confirmation.isConfirmed) {
                try {
                    const response = await fetch('delete_log.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            log_id: logId
                        })
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        Swal.fire('Deleted!', 'The log has been removed.', 'success');
                        fetchLogs(); // Refresh the table
                    } else {
                        Swal.fire('Error!', result.message || 'Delete failed.', 'error');
                    }
                } catch (err) {
                    Swal.fire('Error!', 'Network error occurred.', 'error');
                }
            }
        }
        async function updateLogDescription(logId, newDescription) {
            try {
                const response = await fetch('update_log_description.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        log_id: logId,
                        description: newDescription
                    })
                });

                const result = await response.json();

                if (result.status === 'success') {
                    Swal.fire('Success', 'Description updated.', 'success');
                } else {
                    Swal.fire('Error', result.message || 'Failed to update description.', 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Network error occurred.', 'error');
            }
        }


        // Load users first, then logs
        fetchUsers().then(fetchLogs);
    </script>
</body>

</html>