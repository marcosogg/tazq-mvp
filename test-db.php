<?php
$dbPath = __DIR__ . '/database/tazq.sqlite';
echo "Testing database at: $dbPath\n";

try {
    // Create/Open database
    $db = new SQLite3($dbPath);
    
    // Create a simple test table
    $db->exec('
        CREATE TABLE IF NOT EXISTS test_table (
            id INTEGER PRIMARY KEY,
            name TEXT
        )
    ');
    
    // Insert a test record
    $db->exec('INSERT INTO test_table (name) VALUES ("Test Record")');
    
    // Query the table
    $result = $db->query('SELECT * FROM test_table');
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        print_r($row);
    }
    
    echo "\nTest completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}