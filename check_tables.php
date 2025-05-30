<?php
require_once 'dbconn.php';

try {
    // Get all tables
    $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in database:\n";
    print_r($tables);
    
    // Check user table structure
    echo "\nUser table structure:\n";
    $userColumns = $conn->query("SHOW COLUMNS FROM user")->fetchAll(PDO::FETCH_ASSOC);
    print_r($userColumns);
    
    // Check categories table structure
    echo "\nCategories table structure:\n";
    $categoriesColumns = $conn->query("SHOW COLUMNS FROM categories")->fetchAll(PDO::FETCH_ASSOC);
    print_r($categoriesColumns);
    
    // Check foreign keys
    echo "\nForeign key constraints:\n";
    $foreignKeys = $conn->query("
        SELECT 
            TABLE_NAME,
            COLUMN_NAME,
            CONSTRAINT_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE
            REFERENCED_TABLE_SCHEMA = 'spendex'
            AND REFERENCED_TABLE_NAME IS NOT NULL
    ")->fetchAll(PDO::FETCH_ASSOC);
    print_r($foreignKeys);
    
    // Check if chat_messages table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'chat_messages'");
    if ($stmt->rowCount() === 0) {
        // Create chat_messages table
        $conn->exec("
            CREATE TABLE IF NOT EXISTS chat_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                message TEXT NOT NULL,
                is_user BOOLEAN NOT NULL,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");
        echo "chat_messages table created successfully\n";
    } else {
        echo "chat_messages table already exists\n";
    }
    
    // Check table structure
    $stmt = $conn->query("DESCRIBE chat_messages");
    echo "\nTable structure:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 