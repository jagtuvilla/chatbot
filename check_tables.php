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
    
} catch(PDOException $e) {
    echo "Error checking database structure: " . $e->getMessage();
}
?> 