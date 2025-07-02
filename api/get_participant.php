<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../database/Database.php';

try {
    if (!isset($_GET['email']) || empty($_GET['email'])) {
        throw new Exception('Email parameter is required');
    }
    
    $email = filter_var($_GET['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception('Invalid email format');
    }
    
    $db = new Database();
    $participant = $db->getParticipantByEmail($email);
    
    if ($participant) {
        echo json_encode([
            'success' => true,
            'participant' => $participant
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Participant not found'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
