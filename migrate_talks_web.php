<?php

/**
 * Web-accessible migration script to add talks-related columns
 * Access this file through your web browser to run the migration
 */

// Simple security check - you can remove this after running the migration
$allowed = true; // Set to false after running migration for security

if (!$allowed) {
    die('Migration has been disabled for security. Edit this file to enable it.');
}

require_once 'database/Database.php';

echo "<h1>Database Migration: Add Talks Columns</h1>";
echo "<pre>";

try {
    echo "Starting migration to add talks columns...\n";

    $db = new Database();
    $pdo = $db->getPDO();

    // Check if columns already exist
    $stmt = $pdo->query("PRAGMA table_info(participants)");
    $columns = $stmt->fetchAll();
    $columnNames = array_column($columns, 'name');

    $columnsToAdd = [
        'talk_flash' => 'INTEGER DEFAULT 0',
        'talk_contributed' => 'INTEGER DEFAULT 0',
        'talk_title' => 'TEXT',
        'talk_abstract' => 'TEXT',
        'talk_flash_accepted' => 'INTEGER DEFAULT 1',
        'talk_contributed_accepted' => 'INTEGER DEFAULT NULL'
    ];

    $addedColumns = [];

    foreach ($columnsToAdd as $columnName => $columnDef) {
        if (!in_array($columnName, $columnNames)) {
            $sql = "ALTER TABLE participants ADD COLUMN $columnName $columnDef";
            $pdo->exec($sql);
            $addedColumns[] = $columnName;
            echo "✓ Added column: $columnName\n";
        } else {
            echo "• Column $columnName already exists, skipping\n";
        }
    }

    if (empty($addedColumns)) {
        echo "\n✓ No columns needed to be added. Database is already up to date.\n";
    } else {
        echo "\n✓ Migration completed successfully. Added columns: " . implode(', ', $addedColumns) . "\n";
    }

    echo "\n--- Current table structure ---\n";
    $stmt = $pdo->query("PRAGMA table_info(participants)");
    $columns = $stmt->fetchAll();
    foreach ($columns as $column) {
        echo "• {$column['name']} ({$column['type']})\n";
    }
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><strong>Important:</strong> After running this migration successfully, you should disable this script by setting \$allowed = false; in the file for security.</p>";
