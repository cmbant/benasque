<?php

/**
 * Migration script to add email_public column to existing databases
 */

// Set the document root for CLI execution
if (!isset($_SERVER['DOCUMENT_ROOT']) || empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
}

require_once 'Database.php';

echo "=== Email Privacy Migration Script ===\n";
echo "This script will add the email_public column to the participants table.\n\n";

try {
    $db = new Database();
    $pdo = $db->getPDO();

    // Check if email_public column already exists
    $stmt = $pdo->query("PRAGMA table_info(participants)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $hasEmailPublic = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'email_public') {
            $hasEmailPublic = true;
            break;
        }
    }

    if (!$hasEmailPublic) {
        echo "Adding email_public column to participants table...\n";
        $pdo->exec("ALTER TABLE participants ADD COLUMN email_public INTEGER DEFAULT 0");
        echo "✓ Email privacy column added successfully!\n";
        echo "  Default value: 0 (private) - existing users' emails will remain private by default.\n";
    } else {
        echo "✓ Email privacy column already exists.\n";
    }

    echo "\nMigration completed successfully!\n";
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
