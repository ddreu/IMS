<?php
session_start();
require_once '../connection/conn.php';
$conn = con();

$table = isset($_GET['page']) ? $_GET['page'] : 'announcement';

// Validate table name to prevent SQL injection
$allowedTables = ['announcement', 'events', 'news'];
if (!in_array($table, $allowedTables)) {
    die("Invalid table name.");
}

// Fetch archived records from the selected table
$query = "SELECT * FROM `$table` WHERE is_archived = 1";
$result = $conn->query($query);

include '../navbar/navbar.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Archived Items</title>

    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <!-- Font Awesome CDN for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../styles/dashboard.css">
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #f8f9fa;
            color: #343a40;
            font-family: Arial, sans-serif;
        }

        h2 {
            font-size: 2rem;
            font-weight: 600;
            color: #212529;
        }

        .table {
            background-color: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        th {
            background-color: #e9ecef;
            color: #495057;
            font-weight: 600;
            text-align: center;
            padding: 12px;
        }

        td {
            padding: 12px;
            text-align: center;
            vertical-align: middle;
        }

        .btn {
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }

        .btn-success:hover {
            background-color: #218838;
            border-color: #218838;
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
    </style>
</head>

<body>


    <div class="container mt-5">
        <h2 class="mb-4">Archived Items (<?= ucfirst($table) ?>)</h2>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <?php
                        // Get the column names dynamically
                        $fields = $result->fetch_fields();
                        foreach ($fields as $field) {
                            echo "<th>" . htmlspecialchars(ucwords(str_replace('_', ' ', $field->name))) . "</th>";
                        }
                        ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) : ?>
                        <tr>
                            <?php foreach ($fields as $field) : ?>
                                <td><?= htmlspecialchars($row[$field->name]) ?></td>
                            <?php endforeach; ?>
                            <td>
                                <button class="btn btn-success btn-sm unarchive-btn"
                                    data-id="<?= $row['id'] ?>"
                                    data-table="<?= $table ?>">
                                    <i class="fas fa-box-open"></i> Unarchive
                                </button>
                                <button class="btn btn-danger btn-sm delete-btn"
                                    data-id="<?= $row['id'] ?>"
                                    data-table="<?= $table ?>">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>