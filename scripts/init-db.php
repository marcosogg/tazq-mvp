<?php
$dbPath = __DIR__ . '/../database/tazq.sqlite';
echo "Initializing database at: $dbPath\n";

try {
    // Create/Open database
    $db = new SQLite3($dbPath);
    echo "Database connection established.\n";
    
    // Enable foreign keys
    $db->exec('PRAGMA foreign_keys = ON');
    echo "Foreign key constraints enabled.\n";
    
    // Create tables using transaction
    $db->exec('BEGIN TRANSACTION');
    
    // Users table
    $db->exec('
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            name TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');
    echo "Created users table.\n";
    
    // Family groups table
    $db->exec('
        CREATE TABLE IF NOT EXISTS family_groups (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            admin_id INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES users(id)
        )
    ');
    echo "Created family_groups table.\n";
    
    // Family group members table
    $db->exec('
        CREATE TABLE IF NOT EXISTS family_group_members (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            family_group_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (family_group_id) REFERENCES family_groups(id),
            FOREIGN KEY (user_id) REFERENCES users(id),
            UNIQUE(family_group_id, user_id)
        )
    ');
    echo "Created family_group_members table.\n";
    
    // Tasks table
    $db->exec('
        CREATE TABLE IF NOT EXISTS tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT,
            due_date DATE,
            family_group_id INTEGER NOT NULL,
            assigned_to INTEGER,
            completed BOOLEAN DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (family_group_id) REFERENCES family_groups(id),
            FOREIGN KEY (assigned_to) REFERENCES users(id)
        )
    ');
    echo "Created tasks table.\n";
    
    // Task comments table
    $db->exec('
        CREATE TABLE IF NOT EXISTS task_comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            task_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            comment TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (task_id) REFERENCES tasks(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ');
    echo "Created task_comments table.\n";
    
    $db->exec('COMMIT');
    echo "Transaction committed.\n";
    
    // Verify tables
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    echo "\nVerifying created tables:\n";
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        echo "- " . $row['name'] . "\n";
    }
    
    $db->close();
    echo "\nDatabase initialization completed successfully!\n";
    
} catch (Exception $e) {
    // Rollback transaction if something went wrong
    if (isset($db)) {
        $db->exec('ROLLBACK');
        $db->close();
    }
    die("Database initialization failed: " . $e->getMessage() . "\n");
}