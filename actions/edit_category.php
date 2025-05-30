<?php
session_start();
include_once '../dbconn.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle GET request to fetch category data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Category ID is required']);
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT * FROM categories WHERE id = :id AND (user_id = :user_id OR user_id IS NULL)");
        $stmt->execute([':id' => $id, ':user_id' => $user_id]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$category) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Category not found']);
            exit;
        }

        // Add a flag to indicate if this is a default category
        $category['is_default'] = ($category['user_id'] === null);

        header('Content-Type: application/json');
        echo json_encode($category);
    } catch (PDOException $e) {
        error_log("Error fetching category: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error']);
    }
    exit;
}

// Handle POST request to update category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $type = $_POST['type'] ?? '';
    $color = $_POST['color'] ?? '';

    if (!$id || !$name || !$type || !$color) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'All fields are required']);
        exit;
    }

    try {
        // First verify the category belongs to the user and is not a default category
        $verifyStmt = $conn->prepare("SELECT user_id FROM categories WHERE id = :id");
        $verifyStmt->execute([':id' => $id]);
        $category = $verifyStmt->fetch();

        if (!$category) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Category not found']);
            exit;
        }

        // Prevent editing default categories
        if ($category['user_id'] === null) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Default categories cannot be edited']);
            exit;
        }

        // Check if another category with the same name exists for this user
        $stmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE name = :name AND id != :id AND user_id = :user_id");
        $stmt->execute([':name' => $name, ':id' => $id, ':user_id' => $user_id]);
        if ($stmt->fetchColumn() > 0) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Category with this name already exists']);
            exit;
        }

        // Update the category
        $stmt = $conn->prepare("UPDATE categories SET name = :name, type = :type, color = :color WHERE id = :id AND user_id = :user_id");
        $stmt->execute([
            ':name' => $name,
            ':type' => $type,
            ':color' => $color,
            ':id' => $id,
            ':user_id' => $user_id
        ]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log("Edit category error: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error']);
    }
    exit;
}
?>
