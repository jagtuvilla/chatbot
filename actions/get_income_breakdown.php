<?php
session_start();
include_once '../dbconn.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode([]);
    exit;
}

$period = $_GET['period'] ?? 'monthly';

switch ($period) {
    case 'daily':
        $date_format = '%Y-%m-%d';
        $group_by = "DATE(t.date)";
        $date_filter = "AND DATE(t.date) >= CURDATE() - INTERVAL 6 DAY";
        break;
    case 'weekly':
        $date_format = '%x-W%v';
        $group_by = "YEARWEEK(t.date, 1)";
        $date_filter = "AND t.date >= CURDATE() - INTERVAL 6 WEEK";
        break;
    case 'monthly':
        $date_format = '%Y-%m';
        $group_by = "YEAR(t.date), MONTH(t.date)";
        $date_filter = "AND t.date >= CURDATE() - INTERVAL 11 MONTH";
        break;
    case 'yearly':
        $date_format = '%Y';
        $group_by = "YEAR(t.date)";
        $date_filter = "AND t.date >= CURDATE() - INTERVAL 4 YEAR";
        break;
    default:
        $date_format = '%Y-%m';
        $group_by = "YEAR(t.date), MONTH(t.date)";
        $date_filter = "AND t.date >= CURDATE() - INTERVAL 11 MONTH";
}

$sql = "
    SELECT 
        DATE_FORMAT(t.date, '$date_format') as period,
        c.name as category,
        SUM(t.amount) as total
    FROM transactions t
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE c.type = 'income' AND t.user_id = :user_id $date_filter
    GROUP BY period, c.id
    ORDER BY period ASC, c.name ASC
";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data); 