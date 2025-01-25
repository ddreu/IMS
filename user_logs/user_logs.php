<?php

include "fetch_logs.php";
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
    </style>
</head>

<body>


    <?php
    $current_page = 'leaderboards';

    include '../navbar/navbar.php';
    if ($role == 'Committee') {
        include '../committee/csidebar.php';
    } else {
        //include '../department_admin/sidebar.php';
    }
    ?>

    <div class="container mt-5 margin">
        <h2 class="mb-4">User Logs</h2>

        <!-- Search bar -->
        <div class="mb-3">
            <input type="text" id="searchInput" class="form-control" placeholder="Search logs...">
        </div>

        <!-- Logs Table -->
        <table class="table table-striped table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th><i class="fas fa-user"></i> User</th>
                    <!-- <th><i class="fas fa-id-badge"></i> Log ID</th> -->
                    <th><i class="fas fa-table"></i> Action</th>
                    <th><i class="fas fa-tasks"></i> Operation</th>
                    <th><i class="fas fa-hashtag"></i> Record ID</th>
                    <th><i class="fas fa-info-circle"></i> Description</th>
                    <!--<th><i class="fas fa-history"></i> Previous Data</th>
                    <th><i class="fas fa-database"></i> New Data</th>-->

                    <th><i class="fas fa-clock"></i> Timestamp</th>
                    <th><i class="fas fa-cogs"></i> Details</th>
                </tr>
            </thead>
            <tbody id="logsTable">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <!--<td><?php echo htmlspecialchars($row['log_id']); ?></td> -->
                            <td><?php echo htmlspecialchars($row['table_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['log_action']); ?></td>
                            <td><?php echo htmlspecialchars($row['log_record_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['log_description']); ?></td>
                            <!-- <td><?php echo htmlspecialchars($row['previous_data']); ?></td>
                            <td><?php echo htmlspecialchars($row['new_data']); ?></td> -->

                            <td><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($row['log_time']))); ?></td>
                            <td>
                                <button class="btn btn-info btn-sm"
                                    data-toggle="modal"
                                    data-target="#detailsModal"
                                    data-fullname="<?php echo htmlspecialchars($row['full_name']); ?>"
                                    data-logid="<?php echo htmlspecialchars($row['log_id']); ?>"
                                    data-tablename="<?php echo htmlspecialchars($row['table_name']); ?>"
                                    data-operation="<?php echo htmlspecialchars($row['log_action']); ?>"
                                    data-recordid="<?php echo htmlspecialchars($row['log_record_id']); ?>"
                                    data-description="<?php echo htmlspecialchars($row['log_description']); ?>"
                                    data-previousdata="<?php echo htmlspecialchars($row['previous_data']); ?>"
                                    data-newdata="<?php echo htmlspecialchars($row['new_data']); ?>"

                                    data-logtime="<?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($row['log_time']))); ?>">
                                    View Details
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center">No logs found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal for displaying log details -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel">Log Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
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
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#logsTable tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });

        // Populate modal with log details
        $('#detailsModal').on('show.bs.modal', function(event) {
            const button = $(event.relatedTarget); // Button that triggered the modal
            const modal = $(this);

            // Populate modal fields with button data
            modal.find('#modalLogId').text(button.data('logid'));
            modal.find('#modalTableName').text(button.data('tablename'));
            modal.find('#modalOperation').text(button.data('operation'));
            modal.find('#modalRecordId').text(button.data('recordid'));
            modal.find('#modalDescription').text(button.data('description'));
            modal.find('#modalPreviousData').text(button.data('previousdata'));
            modal.find('#modalNewData').text(button.data('newdata'));
            modal.find('#modalFullName').text(button.data('fullname'));
            modal.find('#modalLogTime').text(button.data('logtime'));
        });
    </script>
</body>

</html>