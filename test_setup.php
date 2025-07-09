<?php
// Test script to verify the setup is working correctly

echo "<h1>Conference Setup Test</h1>";

// Test 1: Check PHP version
echo "<h2>PHP Version</h2>";
echo "PHP Version: " . phpversion() . "<br>";

// Test 2: Check required extensions
echo "<h2>Required Extensions</h2>";
$required_extensions = ['pdo', 'pdo_sqlite', 'gd', 'json'];
foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? "✓ Loaded" : "✗ Missing";
    echo "$ext: $status<br>";
}

// Test 3: Check directory permissions
echo "<h2>Directory Permissions</h2>";
$directories = ['uploads', 'database'];
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        $writable = is_writable($dir) ? "✓ Writable" : "✗ Not writable";
        echo "$dir/: $writable<br>";
    } else {
        echo "$dir/: ✗ Directory not found<br>";
    }
}

// Test 4: Database connection
echo "<h2>Database Connection</h2>";
try {
    require_once 'database/Database.php';
    $db = new Database();
    echo "✓ Database connection successful<br>";

    // Test adding a sample participant
    $testData = [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'interests' => 'quantum physics, cosmology',
        'description' => 'Test description for the participant',
        'arxiv_links' => '["https://arxiv.org/abs/2301.00001"]',
        'photo_path' => null
    ];

    // Clean up any existing test data
    $db->deleteParticipant('test@example.com');

    if ($db->addParticipant($testData)) {
        echo "✓ Test participant added successfully<br>";

        // Test retrieval
        $participant = $db->getParticipantByEmail('test@example.com');
        if ($participant) {
            echo "✓ Test participant retrieved successfully<br>";
        } else {
            echo "✗ Failed to retrieve test participant<br>";
        }

        // Clean up
        $db->deleteParticipant('test@example.com');
        echo "✓ Test participant cleaned up<br>";
    } else {
        echo "✗ Failed to add test participant<br>";
    }
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "<br>";
}

// Test 5: File upload directory
echo "<h2>File Upload Test</h2>";
$upload_dir = 'uploads/';
if (is_dir($upload_dir) && is_writable($upload_dir)) {
    $test_file = $upload_dir . 'test.txt';
    if (file_put_contents($test_file, 'test') !== false) {
        echo "✓ File upload directory is writable<br>";
        unlink($test_file);
    } else {
        echo "✗ Cannot write to upload directory<br>";
    }
} else {
    echo "✗ Upload directory is not writable<br>";
}

echo "<h2>Setup Complete</h2>";
echo "<p>If all tests show ✓, your setup is ready!</p>";
echo "<p><a href='index.php'>Go to Main Application</a></p>";
