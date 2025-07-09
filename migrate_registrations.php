<?php

/**
 * Migration script to add registrations table for conference
 *
 * This script adds a new table to store registration status data
 * parsed from the orgaccept.pl HTML files.
 */

require_once 'database/Database.php';

function addRegistrationsTable($pdo)
{
    $sql = "
    CREATE TABLE IF NOT EXISTS registrations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        first_name TEXT NOT NULL,
        last_name TEXT NOT NULL,
        status TEXT NOT NULL CHECK (status IN ('ACCEPTED', 'INVITED', 'CANCELLED')),
        affiliation TEXT,
        start_date TEXT,
        end_date TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($sql);

    // Create indexes for better performance
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_registrations_email ON registrations(email)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_registrations_status ON registrations(status)");

    // Create trigger to update timestamp
    $pdo->exec("
    CREATE TRIGGER IF NOT EXISTS update_registrations_timestamp
        AFTER UPDATE ON registrations
    BEGIN
        UPDATE registrations SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
    END");
}

try {
    echo "Starting migration to add registrations table...\n";

    $db = new Database();
    $pdo = $db->getPDO();

    // Check if table already exists
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='registrations'");
    $tableExists = $stmt->fetch();

    if ($tableExists) {
        echo "• Table 'registrations' already exists\n";

        // Check if we need to add any missing columns
        $stmt = $pdo->query("PRAGMA table_info(registrations)");
        $columns = $stmt->fetchAll();
        $columnNames = array_column($columns, 'name');

        $requiredColumns = ['id', 'email', 'first_name', 'last_name', 'status', 'affiliation', 'start_date', 'end_date', 'created_at', 'updated_at'];
        $missingColumns = array_diff($requiredColumns, $columnNames);

        if (!empty($missingColumns)) {
            echo "Adding missing columns: " . implode(', ', $missingColumns) . "\n";

            foreach ($missingColumns as $column) {
                switch ($column) {
                    case 'first_name':
                    case 'last_name':
                    case 'affiliation':
                    case 'start_date':
                    case 'end_date':
                        $pdo->exec("ALTER TABLE registrations ADD COLUMN $column TEXT");
                        break;
                    case 'created_at':
                    case 'updated_at':
                        $pdo->exec("ALTER TABLE registrations ADD COLUMN $column DATETIME DEFAULT CURRENT_TIMESTAMP");
                        break;
                }
                echo "✓ Added column: $column\n";
            }
        } else {
            echo "✓ All required columns are present\n";
        }
    } else {
        addRegistrationsTable($pdo);
        echo "✓ Created 'registrations' table with all required columns\n";
    }

    echo "\n✓ Migration completed successfully.\n";
    echo "\nTable structure:\n";

    // Show table structure
    $stmt = $pdo->query("PRAGMA table_info(registrations)");
    $columns = $stmt->fetchAll();

    foreach ($columns as $column) {
        echo sprintf(
            "  %-15s %-15s %s\n",
            $column['name'],
            $column['type'],
            $column['notnull'] ? 'NOT NULL' : 'NULL'
        );
    }

    // Show current count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM registrations");
    $count = $stmt->fetch()['count'];
    echo "\nCurrent registrations count: $count\n";
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
