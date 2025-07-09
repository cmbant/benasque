<?php

/**
 * API endpoint to update registration data from parsed HTML
 *
 * Accepts JSON data with registration information and updates the database.
 * Used by the Python parser script to sync registration data.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../database/Database.php';

class RegistrationManager
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function updateRegistrations($registrations)
    {
        $updated = 0;
        $errors = [];

        $this->pdo->beginTransaction();

        try {
            // Prepare statements for efficiency
            $selectStmt = $this->pdo->prepare("SELECT * FROM registrations WHERE email = ?");
            $insertStmt = $this->pdo->prepare("
                INSERT INTO registrations (email, first_name, last_name, status, affiliation, start_date, end_date)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $updateStmt = $this->pdo->prepare("
                UPDATE registrations
                SET first_name = ?, last_name = ?, status = ?, affiliation = ?, start_date = ?, end_date = ?, updated_at = CURRENT_TIMESTAMP
                WHERE email = ?
            ");

            foreach ($registrations as $reg) {
                try {
                    // Validate required fields
                    if (empty($reg['email']) || empty($reg['first_name']) || empty($reg['last_name']) || empty($reg['status'])) {
                        $errors[] = "Missing required fields for registration: " . json_encode($reg);
                        continue;
                    }

                    // Validate email format
                    if (!filter_var($reg['email'], FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "Invalid email format: " . $reg['email'];
                        continue;
                    }

                    // Validate status
                    $validStatuses = ['ACCEPTED', 'INVITED', 'CANCELLED'];
                    if (!in_array($reg['status'], $validStatuses)) {
                        $errors[] = "Invalid status '{$reg['status']}' for {$reg['email']}";
                        continue;
                    }

                    // Check if registration already exists
                    $selectStmt->execute([$reg['email']]);
                    $existing = $selectStmt->fetch();

                    if ($existing) {
                        // Update existing registration
                        $success = $updateStmt->execute([
                            $reg['first_name'] ?? '',
                            $reg['last_name'] ?? '',
                            $reg['status'],
                            $reg['affiliation'] ?? '',
                            $reg['start_date'] ?? '',
                            $reg['end_date'] ?? '',
                            $reg['email']
                        ]);
                    } else {
                        // Insert new registration
                        $success = $insertStmt->execute([
                            $reg['email'],
                            $reg['first_name'] ?? '',
                            $reg['last_name'] ?? '',
                            $reg['status'],
                            $reg['affiliation'] ?? '',
                            $reg['start_date'] ?? '',
                            $reg['end_date'] ?? ''
                        ]);
                    }

                    if ($success) {
                        $updated++;
                    } else {
                        $errors[] = "Database error for {$reg['email']}: " . implode(', ', $this->pdo->errorInfo());
                    }
                } catch (Exception $e) {
                    $errors[] = "Error processing {$reg['email']}: " . $e->getMessage();
                }
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'updated' => $updated,
                'errors' => $errors,
                'total_processed' => count($registrations)
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getRegistrationStats()
    {
        $stmt = $this->pdo->query("
            SELECT status, COUNT(*) as count
            FROM registrations
            GROUP BY status
            ORDER BY status
        ");

        $stats = [];
        while ($row = $stmt->fetch()) {
            $stats[$row['status']] = $row['count'];
        }

        $totalStmt = $this->pdo->query("SELECT COUNT(*) as total FROM registrations");
        $stats['TOTAL'] = $totalStmt->fetch()['total'];

        return $stats;
    }

    public function getAllRegistrations()
    {
        $stmt = $this->pdo->query("
            SELECT * FROM registrations
            ORDER BY status, last_name, first_name
        ");
        return $stmt->fetchAll();
    }
}

try {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests are allowed');
    }

    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input: ' . json_last_error_msg());
    }

    if (!isset($data['registrations']) || !is_array($data['registrations'])) {
        throw new Exception('Missing or invalid registrations array');
    }

    // Initialize database
    $db = new Database();
    $pdo = $db->getPDO();

    // Check if registrations table exists
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='registrations'");
    if (!$stmt->fetch()) {
        throw new Exception('Registrations table does not exist. Please run migrate_registrations.php first.');
    }

    // Process registrations
    $manager = new RegistrationManager($pdo);
    $result = $manager->updateRegistrations($data['registrations']);

    // Add current statistics to response
    $result['stats'] = $manager->getRegistrationStats();

    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
