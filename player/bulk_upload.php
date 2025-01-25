<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Upload</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h1>Bulk Upload Players</h1>

    <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error_messages'])): ?>
        <div class="alert alert-warning">
            <ul>
                <?php foreach ($_SESSION['error_messages'] as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
        </div>
    <?php endif; ?>

    <form id="bulkUploadForm" method="POST" action="bulk_register_process.php" enctype="multipart/form-data">
        <input type="hidden" name="team_id" value="<?= htmlspecialchars($_GET['team_id'] ?? 0) ?>">
        <div class="mb-3">
            <label for="bulk_file" class="form-label">Bulk Upload (CSV or Excel):</label>
            <input type="file" class="form-control" id="bulk_file" name="bulk_file" accept=".csv, .xls, .xlsx" required>
        </div>
        <button type="submit" class="btn btn-primary">Upload Players</button>
    </form>
</div>
</body>
</html>
