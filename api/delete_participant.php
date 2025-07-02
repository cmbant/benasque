<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../database/Database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }

    // Validate required fields
    if (!isset($_POST['email']) || empty(trim($_POST['email']))) {
        throw new Exception('Email is required');
    }

    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception('Invalid email format');
    }

    $db = new Database();
    
    // Check if participant exists
    $participant = $db->getParticipantByEmail($email);
    if (!$participant) {
        throw new Exception('Participant not found');
    }

    // Delete associated photo file if it exists
    if (!empty($participant['photo_path'])) {
        $photoPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $participant['photo_path'];
        if (file_exists($photoPath)) {
            unlink($photoPath);
            error_log("Deleted photo file: " . $photoPath);
        }
    }

    // Delete participant from database
    $success = $db->deleteParticipant($email);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Profile deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete profile');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
