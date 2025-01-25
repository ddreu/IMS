<?php
function logUserAction($conn, $userId, $tableName, $operation, $recordId = null, $description = null)
{
    // Prepare the SQL statement without previous_data and new_data
    $stmt = $conn->prepare("
        INSERT INTO logs (user_id, table_name, operation, record_id, description) 
        VALUES (?, ?, ?, ?, ?)
    ");

    // Bind parameters to the statement
    $stmt->bind_param(
        "issis",
        $userId,      // User ID
        $tableName,   // Table name
        $operation,   // Operation type (CREATE, READ, UPDATE, DELETE)
        $recordId,    // Record ID
        $description  // Description of the operation
    );

    // Execute the statement
    if ($stmt->execute()) {
        // Log entry created successfully
    } else {
        // Handle error (e.g., log it, display a message)
        error_log("Error logging user action: " . $stmt->error);
    }

    // Close the statement
    $stmt->close();
}
