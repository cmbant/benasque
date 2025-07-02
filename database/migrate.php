<?php
// Migration script to add description column to existing database

require_once 'Database.php';

try {
    $pdo = new PDO("sqlite:database/benasque25.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if description column exists
    $stmt = $pdo->query("PRAGMA table_info(participants)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasDescription = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'description') {
            $hasDescription = true;
            break;
        }
    }
    
    if (!$hasDescription) {
        echo "Adding description column to participants table...\n";
        $pdo->exec("ALTER TABLE participants ADD COLUMN description TEXT");
        echo "✓ Description column added successfully!\n";
    } else {
        echo "✓ Description column already exists.\n";
    }
    
    echo "Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
