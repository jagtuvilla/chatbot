<?php
require_once '../dbconn.php';

try {
    // Start transaction
    $conn->beginTransaction();

    // Default Income Categories
    $income_categories = [
        ['Salary', 'income', '#4CAF50'],  // Green
        ['Freelance', 'income', '#2196F3'],  // Blue
        ['Investments', 'income', '#9C27B0'],  // Purple
        ['Gifts', 'income', '#FF9800']  // Orange
    ];

    // Default Expense Categories
    $expense_categories = [
        ['Food & Dining', 'expense', '#F44336'],  // Red
        ['Transportation', 'expense', '#3F51B5'],  // Indigo
        ['Housing', 'expense', '#795548'],  // Brown
        ['Utilities', 'expense', '#009688'],  // Teal
        ['Entertainment', 'expense', '#E91E63'],  // Pink
        ['Shopping', 'expense', '#FFC107'],  // Amber
        ['Healthcare', 'expense', '#00BCD4'],  // Cyan
        ['Education', 'expense', '#673AB7'],  // Deep Purple
        ['Personal Care', 'expense', '#FF5722'],  // Deep Orange
        ['Travel', 'expense', '#8BC34A'],  // Light Green
        ['Gifts & Donations', 'expense', '#CDDC39']  // Lime
    ];

    // Prepare the insert statement
    $stmt = $conn->prepare("
        INSERT INTO categories (name, type, color, user_id) 
        VALUES (?, ?, ?, NULL)
    ");

    // Insert income categories
    foreach ($income_categories as $category) {
        $stmt->execute($category);
    }

    // Insert expense categories
    foreach ($expense_categories as $category) {
        $stmt->execute($category);
    }

    // Commit transaction
    $conn->commit();
    
    echo "Default categories added successfully!";
    
} catch(PDOException $e) {
    // Rollback transaction on error
    $conn->rollBack();
    echo "Error adding default categories: " . $e->getMessage();
}
?> 