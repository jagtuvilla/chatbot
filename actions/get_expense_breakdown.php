<?php
session_start();
include_once '../dbconn.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode([]);
    exit;
}

$period = $_GET['period'] ?? 'monthly';

$date_filter = '';
switch ($period) {
    case 'daily':
        $date_filter = "AND DATE(t.date) = CURDATE()";
        break;
    case 'weekly':
        $date_filter = "AND YEARWEEK(t.date, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'monthly':
        $date_filter = "AND YEAR(t.date) = YEAR(CURDATE()) AND MONTH(t.date) = MONTH(CURDATE())";
        break;
    case 'yearly':
        $date_filter = "AND YEAR(t.date) = YEAR(CURDATE())";
        break;
}

$sql = "
    SELECT c.name, c.color, SUM(t.amount) as total
    FROM transactions t
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE c.type = 'expense' AND t.user_id = :user_id $date_filter
    GROUP BY c.id
    ORDER BY total DESC
";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data); 