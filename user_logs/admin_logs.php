<?php
include "fetch_logs.php"

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Logs</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <div class="container mt-5">
        <h2 class="mb-4">User Logs</h2>

        <!-- Search bar -->
        <div class="mb-3">
            <input type="text" id="searchInput" class="form-control" placeholder="Search by name, email, or role...">
        </div>

        <!-- Logs Table -->
        <table class="table table-striped table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th><i class="fas fa-user"></i> Full Name</th>
                    <th><i class="fas fa-hourglass"></i> Age</th>
                    <th><i class="fas fa-venus-mars"></i> Gender</th>
                    <th><i class="fas fa-envelope"></i> Email</th>
                    <th><i class="fas fa-user-tag"></i> Role</th>
                    <th><i class="fas fa-building"></i> Department</th>
                    <th><i class="fas fa-futbol"></i> Game</th>
                    <th><i class="fas fa-school"></i> School</th>

                    <th><i class="fas fa-info-circle"></i> Action</th>
                    <th><i class="fas fa-sticky-note"></i> Description</th>
                    <th><i class="fas fa-clock"></i> Log Time</th>
                </tr>
            </thead>
            <tbody id="logsTable">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['age']); ?></td>
                            <td><?php echo htmlspecialchars($row['gender']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['role']); ?></td>
                            <td><?php echo htmlspecialchars($row['department_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['game_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['school_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['log_action']); ?></td>
                            <td><?php echo htmlspecialchars($row['log_description']); ?></td>
                            <td><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($row['log_time']))); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" class="text-center">No logs found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

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
    </script>
</body>

</html>