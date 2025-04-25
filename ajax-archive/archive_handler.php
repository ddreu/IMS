<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $action = $_POST['action'] ?? null;
    $operation = $_POST['operation'] ?? null; // New parameter for archive/unarchive

    if ($id && $action && $operation) {
        require_once '../connection/conn.php';
        $conn = con();

        $table = '';
        switch ($action) {
            case 'announcements':
                $table = 'announcement';
                break;
            case 'games':
                $table = 'games';
                break;
            case 'users':
                $table = 'users';
                break;
            case 'brackets':
                $table = 'brackets';
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                exit;
        }

        // Get the primary key column dynamically
        $result = $conn->query("SELECT COLUMN_NAME 
                                FROM INFORMATION_SCHEMA.COLUMNS 
                                WHERE TABLE_SCHEMA = DATABASE() 
                                AND TABLE_NAME = '$table' 
                                AND COLUMN_KEY = 'PRI'");

        if ($result && $row = $result->fetch_assoc()) {
            $primaryKey = $row['COLUMN_NAME'];

            if ($operation === 'unarchive') {
                // Unarchive logic
                $stmt = $conn->prepare("UPDATE `$table` SET `is_archived` = '0', `archived_at` = NULL WHERE `$primaryKey` = ?");
            } elseif ($operation === 'archive') {
                // Archive logic
                $stmt = $conn->prepare("UPDATE `$table` SET `is_archived` = '1', `archived_at` = NOW() WHERE `$primaryKey` = ?");
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid operation']);
                exit;
            }

            $stmt->bind_param("i", $id);
            $result = $stmt->execute();

            if ($result) {
                echo json_encode(['success' => true, 'message' => ucfirst($operation) . ' successful']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update record']);
            }

            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Primary key not found']);
        }

        $conn->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
