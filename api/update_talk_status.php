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

    // Only handle contributed talk status changes (flash talks are always auto-accepted)
    $contributedAccepted = $_POST['talk_contributed_accepted'] ?? null;
    $expectedContributedAccepted = $_POST['expected_contributed_accepted'] ?? null;
    $pageLoadTime = $_POST['page_load_time'] ?? null;

    // Convert empty strings and 'null' strings to NULL for pending status
    if ($contributedAccepted === '' || $contributedAccepted === 'null') $contributedAccepted = null;
    if ($expectedContributedAccepted === '' || $expectedContributedAccepted === 'null') $expectedContributedAccepted = null;

    // Convert to integers if not null
    if ($contributedAccepted !== null) $contributedAccepted = (int)$contributedAccepted;
    if ($expectedContributedAccepted !== null) $expectedContributedAccepted = (int)$expectedContributedAccepted;

    // Validate acceptance values (0, 1, or null)
    if ($contributedAccepted !== null && !in_array($contributedAccepted, [0, 1])) {
        throw new Exception('Invalid contributed acceptance status value');
    }

    $db = new Database();
    $pdo = $db->getPDO();

    // Check if participant exists and get current values
    $participant = $db->getParticipantByEmail($email);
    if (!$participant) {
        throw new Exception('Participant not found');
    }

    // Check for conflicts on contributed talk status only
    $currentContributedAccepted = $participant['talk_contributed_accepted'];

    // Convert current value for comparison
    if ($currentContributedAccepted !== null) $currentContributedAccepted = (int)$currentContributedAccepted;

    // Check if current contributed status matches expected value
    if ($currentContributedAccepted !== $expectedContributedAccepted) {
        throw new Exception('CONFLICT: This talk status was changed by another admin. Please refresh the page and try again.');
    }

    // Update only contributed talk acceptance status (flash status is managed automatically)
    $sql = "UPDATE participants SET talk_contributed_accepted = ? WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$contributedAccepted, $email]);

    if ($success) {
        // Check if ANY OTHER talk status has been modified since page load (after our save)
        $needsReload = false;
        if ($pageLoadTime) {
            $sql = "SELECT COUNT(*) as changed_count FROM participants
                    WHERE (talk_contributed_accepted IS NOT NULL)
                    AND updated_at > ? AND email != ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$pageLoadTime, $email]);
            $changedCount = $stmt->fetch()['changed_count'];

            if ($changedCount > 0) {
                $needsReload = true;
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Talk status updated successfully',
            'reload_needed' => $needsReload
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
