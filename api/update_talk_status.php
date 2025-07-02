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
    if (!isset($_POST['email']) || empty($_POST['email'])) {
        throw new Exception('Email is required');
    }

    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception('Invalid email format');
    }

    $flashAccepted = $_POST['talk_flash_accepted'] ?? null;
    $contributedAccepted = $_POST['talk_contributed_accepted'] ?? null;

    // Convert empty strings to NULL for pending status
    if ($flashAccepted === '') $flashAccepted = null;
    if ($contributedAccepted === '') $contributedAccepted = null;

    // Convert to integers if not null
    if ($flashAccepted !== null) $flashAccepted = (int)$flashAccepted;
    if ($contributedAccepted !== null) $contributedAccepted = (int)$contributedAccepted;

    // Validate acceptance values (0, 1, or null)
    if ($flashAccepted !== null && !in_array($flashAccepted, [0, 1])) {
        throw new Exception('Invalid flash acceptance status value');
    }
    if ($contributedAccepted !== null && !in_array($contributedAccepted, [0, 1])) {
        throw new Exception('Invalid contributed acceptance status value');
    }

    $db = new Database();
    $pdo = $db->getPDO();

    // Check if participant exists
    $participant = $db->getParticipantByEmail($email);
    if (!$participant) {
        throw new Exception('Participant not found');
    }

    // Update talk acceptance status
    $sql = "UPDATE participants SET talk_flash_accepted = ?, talk_contributed_accepted = ? WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$flashAccepted, $contributedAccepted, $email]);

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Talk status updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update talk status');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
