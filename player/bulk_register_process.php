<?php
session_start();
include_once '../connection/conn.php';
include '../user_logs/logger.php';
$conn = con();

// Load PHPSpreadsheet library
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['team_id']) || !isset($_FILES['bulk_file'])) {
        $_SESSION['error_message'] = "Invalid request.";
        header("Location: bulk_upload.php?team_id=" . htmlspecialchars($_POST['team_id'] ?? 0));
        exit();
    }

    $team_id = intval($_POST['team_id']);
    $file = $_FILES['bulk_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error_message'] = "File upload error.";
        header("Location: bulk_upload.php?team_id=" . htmlspecialchars($team_id));
        exit();
    }

    $filename = $file['tmp_name'];
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);

    if (!in_array($extension, ['csv', 'xls', 'xlsx'])) {
        $_SESSION['error_message'] = "Invalid file type. Please upload a CSV, XLS, or XLSX file.";
        header("Location: bulk_upload.php?team_id=" . htmlspecialchars($team_id));
        exit();
    }

    try {
        $rows = [];
        if ($extension === 'csv') {
            if (($handle = fopen($filename, "r")) !== FALSE) {
                $header = fgetcsv($handle);
                $requiredFields = ['player_lastname', 'player_firstname', 'email', 'phone_number', 'jersey_number'];

                if (array_diff($requiredFields, $header)) {
                    $_SESSION['error_message'] = "CSV file is missing required fields.";
                    fclose($handle);
                    header("Location: bulk_upload.php?team_id=" . htmlspecialchars($team_id));
                    exit();
                }

                while (($row = fgetcsv($handle)) !== FALSE) {
                    $rows[] = array_combine($header, $row);
                }
                fclose($handle);
            }
        } else {
            $spreadsheet = IOFactory::load($filename);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            $header = array_shift($rows);

            $requiredFields = ['player_lastname', 'player_firstname', 'email', 'phone_number', 'jersey_number'];
            if (array_diff($requiredFields, $header)) {
                $_SESSION['error_message'] = "Excel file is missing required fields.";
                header("Location: bulk_upload.php?team_id=" . htmlspecialchars($team_id));
                exit();
            }

            $rows = array_map(fn($row) => array_combine($header, $row), $rows);
        }

        $successCount = 0;
        $errorMessages = [];
        $conn->begin_transaction();

        foreach ($rows as $index => $data) {
            if (empty($data['player_lastname']) || empty($data['player_firstname']) || empty($data['email']) || empty($data['phone_number']) || empty($data['jersey_number'])) {
                $errorMessages[] = "Row " . ($index + 2) . ": Missing required fields.";
                continue;
            }

            $player_middlename = $data['player_middlename'] ?? NULL;
            $date_of_birth = $data['date_of_birth'] ?? NULL;
            $picture = $data['picture'] ?? NULL;
            $height = $data['height'] ?? NULL;
            $weight = $data['weight'] ?? NULL;
            $position = $data['position'] ?? NULL;

            $stmtPlayers = $conn->prepare("INSERT INTO players (player_lastname, player_firstname, player_middlename, team_id, created_at, jersey_number) VALUES (?, ?, ?, ?, NOW(), ?)");
            $stmtPlayers->bind_param("sssii", $data['player_lastname'], $data['player_firstname'], $player_middlename, $team_id, $data['jersey_number']);

            if (!$stmtPlayers->execute()) {
                $errorMessages[] = "Row " . ($index + 2) . ": Error inserting player. " . $stmtPlayers->error;
                continue;
            }

            $player_id = $conn->insert_id;

            $stmtPlayersInfo = $conn->prepare("INSERT INTO players_info (player_id, email, phone_number, date_of_birth, picture, height, weight, position) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtPlayersInfo->bind_param("isssssss", $player_id, $data['email'], $data['phone_number'], $date_of_birth, $picture, $height, $weight, $position);

            if (!$stmtPlayersInfo->execute()) {
                $errorMessages[] = "Row " . ($index + 2) . ": Error inserting player info. " . $stmtPlayersInfo->error;
                continue;
            }

            $successCount++;
        }

        $conn->commit();

        // Fetch team name for logging
        $stmtTeam = $conn->prepare("SELECT team_name FROM teams WHERE team_id = ?");
        $stmtTeam->bind_param("i", $team_id);
        $stmtTeam->execute();
        $stmtTeam->bind_result($team_name);
        $stmtTeam->fetch();
        $stmtTeam->close();

        // Log the successful bulk upload
        if (isset($team_name)) {
            logUserAction($conn, $_SESSION['user_id'], 'players', 'Players Bulk Upload', $team_id, "Registered players for team ($team_name)");
        }

        $_SESSION['success_message'] = "$successCount players registered successfully!";
        if (!empty($errorMessages)) {
            $_SESSION['error_messages'] = $errorMessages;
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }

    header("Location: bulk_upload.php?team_id=" . htmlspecialchars($team_id));
    exit();
}
