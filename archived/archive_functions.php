<?php
require_once '../connection/connection.php';

class Archive {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Archive an event
    public function archiveEvent($eventId) {
        try {
            // First, get the event details
            $query = "SELECT * FROM events WHERE event_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $eventId);
            $stmt->execute();
            $result = $stmt->get_result();
            $eventData = $result->fetch_assoc();
            
            if ($eventData) {
                // Insert into archived_events table
                $archiveQuery = "INSERT INTO archived_events 
                               SELECT *, NOW() as archived_date 
                               FROM events 
                               WHERE event_id = ?";
                $archiveStmt = $this->conn->prepare($archiveQuery);
                $archiveStmt->bind_param("i", $eventId);
                
                if ($archiveStmt->execute()) {
                    // Delete from active events
                    $deleteQuery = "DELETE FROM events WHERE event_id = ?";
                    $deleteStmt = $this->conn->prepare($deleteQuery);
                    $deleteStmt->bind_param("i", $eventId);
                    return $deleteStmt->execute();
                }
            }
            return false;
        } catch (Exception $e) {
            error_log("Archive Error: " . $e->getMessage());
            return false;
        }
    }
    
    // Archive a game
    public function archiveGame($gameId) {
        try {
            $query = "INSERT INTO archived_games 
                     SELECT *, NOW() as archived_date 
                     FROM games 
                     WHERE game_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $gameId);
            
            if ($stmt->execute()) {
                $deleteQuery = "DELETE FROM games WHERE game_id = ?";
                $deleteStmt = $this->conn->prepare($deleteQuery);
                $deleteStmt->bind_param("i", $gameId);
                return $deleteStmt->execute();
            }
            return false;
        } catch (Exception $e) {
            error_log("Archive Error: " . $e->getMessage());
            return false;
        }
    }
    
    // Get archived events
    public function getArchivedEvents() {
        $query = "SELECT * FROM archived_events ORDER BY archived_date DESC";
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get archived games
    public function getArchivedGames() {
        $query = "SELECT * FROM archived_games ORDER BY archived_date DESC";
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Restore archived event
    public function restoreEvent($eventId) {
        try {
            $query = "INSERT INTO events 
                     SELECT event_id, event_name, event_description, event_date, event_time, event_venue, event_status 
                     FROM archived_events 
                     WHERE event_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $eventId);
            
            if ($stmt->execute()) {
                $deleteQuery = "DELETE FROM archived_events WHERE event_id = ?";
                $deleteStmt = $this->conn->prepare($deleteQuery);
                $deleteStmt->bind_param("i", $eventId);
                return $deleteStmt->execute();
            }
            return false;
        } catch (Exception $e) {
            error_log("Restore Error: " . $e->getMessage());
            return false;
        }
    }
}
?>
