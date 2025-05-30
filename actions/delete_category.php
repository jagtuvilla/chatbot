<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php?error=not_logged_in');
    exit;
}

require_once '../dbconn.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    // Improved validation: allow 0, but disallow false/null
    if ($id === false || $id === null) {
        error_log("Invalid category ID received: " . var_export($_GET['id'], true));
        header('Location: ../categories.php?error=Invalid category ID');
        exit;
    }
    
    try {
        // Check if category exists and is not a default category
        $stmt = $conn->prepare("SELECT user_id FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$category) {
            error_log("Category ID $id does not exist");
            header('Location: ../categories.php?error=Category does not exist');
            exit;
        }

        // Prevent deletion of default categories
        if ($category['user_id'] === null) {
            header('Location: ../categories.php?error=Cannot delete default categories');
            exit;
        }
        
        // Check if category is in use
        $stmt = $conn->prepare("SELECT COUNT(*) FROM transactions WHERE category_id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $count = $stmt->fetchColumn();
        
        error_log("Transactions count for category $id: $count");
        
        if ($count > 0) {
            header('Location: ../categories.php?error=Cannot delete category that is in use');
            exit;
        }
        
        // Delete the category only if it belongs to the current user
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        
        if ($stmt->rowCount() === 0) {
            header('Location: ../categories.php?error=You can only delete your own custom categories');
            exit;
        }
        
        header('Location: ../categories.php?delete_success=Category deleted successfully');
        exit;
    } catch (PDOException $e) {
        error_log("Error deleting category: " . $e->getMessage());
        header('Location: ../categories.php?error=Failed to delete category');
        exit;
    }
} else {
    header('Location: ../categories.php');
    exit;
}
