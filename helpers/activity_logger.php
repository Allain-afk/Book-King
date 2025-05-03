<?php

function logActivity($pdo, $action_type, $description, $performed_by, $related_id = null, $status = 'completed')
{
    try {
        $stmt = $pdo->prepare('
            INSERT INTO activity_logs 
            (action_type, description, performed_by, related_id, status) 
            VALUES 
            (:action_type, :description, :performed_by, :related_id, :status)
        ');

        return $stmt->execute([
            'action_type' => $action_type,
            'description' => $description,
            'performed_by' => $performed_by,
            'related_id' => $related_id,
            'status' => $status
        ]);
    } catch (PDOException $e) {
        error_log("Error logging activity: " . $e->getMessage());
        return false;
    }
}

function getActivityIcon($action_type)
{
    switch (strtoupper($action_type)) {
        case 'BORROW':
            return '📚';
        case 'RETURN_REQUEST':
            return '🔄';
        case 'BOOK_RETURN':
            return '✅';
        case 'ADD':
            return '➕';
        case 'DELETE':
            return '🗑️';
        case 'UPDATE':
            return '✏️';
        case 'SIGNUP':
            return '👤';
        default:
            return '📝';
    }
}


// hi