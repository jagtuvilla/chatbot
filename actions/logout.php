<?php

session_start();
require_once '../dbconn.php';

// Clear chat history if user is logged in
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM chat_messages WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
    } catch (PDOException $e) {
        error_log("Error clearing chat history during logout: " . $e->getMessage());
    }
}

$_SESSION = array();

session_destroy();

header('Location: ../index.php');
exit;
?> 