<?php
header('Content-Type: application/json');
require_once 'archive_functions.php';
require_once '../connection/connection.php';

$response = array();
$db = new Database();
$conn = $db->connect();
$archive = new Archive($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['action'])) {
        switch ($data['action']) {
            case 'archiveEvent':
                if (isset($data['eventId'])) {
                    $success = $archive->archiveEvent($data['eventId']);
                    $response['success'] = $success;
                    $response['message'] = $success ? 'Event archived successfully' : 'Failed to archive event';
                }
                break;
                
            case 'archiveGame':
                if (isset($data['gameId'])) {
                    $success = $archive->archiveGame($data['gameId']);
                    $response['success'] = $success;
                    $response['message'] = $success ? 'Game archived successfully' : 'Failed to archive game';
                }
                break;
                
            case 'restoreEvent':
                if (isset($data['eventId'])) {
                    $success = $archive->restoreEvent($data['eventId']);
                    $response['success'] = $success;
                    $response['message'] = $success ? 'Event restored successfully' : 'Failed to restore event';
                }
                break;
                
            default:
                $response['success'] = false;
                $response['message'] = 'Invalid action';
        }
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['type'])) {
        switch ($_GET['type']) {
            case 'events':
                $response['data'] = $archive->getArchivedEvents();
                $response['success'] = true;
                break;
                
            case 'games':
                $response['data'] = $archive->getArchivedGames();
                $response['success'] = true;
                break;
                
            default:
                $response['success'] = false;
                $response['message'] = 'Invalid type';
        }
    }
}

echo json_encode($response);
?>
