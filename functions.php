<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

//Function for getting the transaction

function getTransactions($conn, $user_id) {
    try {
        $stmt = $conn->prepare("
            SELECT t.*, c.name as category_name 
            FROM transactions t 
            JOIN categories c ON t.category_id = c.id 
            WHERE t.user_id = ? 
            ORDER BY t.date DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error fetching transactions: " . $e->getMessage());
        return [];
    }
}

//Function for calculating the Total

function calculateTotal($transactions, $type = null) {
    return array_reduce($transactions, function($carry, $transaction) use ($type) {
        if ($type === null || $transaction['transaction_type'] === $type) {
            return $carry + ($transaction['transaction_type'] === 'income' ? $transaction['amount'] : -$transaction['amount']);
        }
        return $carry;
    }, 0);
}

// Function for the categories

function getCategories($conn, $user_id, $type = null) {
    try {
        $sql = "SELECT c.*, 
                (SELECT COUNT(*) FROM transactions t WHERE t.category_id = c.id AND t.user_id = ?) as transaction_count 
                FROM categories c 
                WHERE (c.user_id = ? OR c.user_id IS NULL)";
        $params = [$user_id, $user_id];
        
        if ($type) {
            $sql .= " AND c.type = ?";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY c.type, c.name";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error fetching categories: " . $e->getMessage());
        return [];
    }
}

// Function for getting the monthly transaction

function getMonthlyTransactions($conn, $user_id) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                DATE_FORMAT(date, '%Y-%m') as month,
                transaction_type,
                SUM(amount) as total
            FROM transactions 
            WHERE user_id = ? 
            GROUP BY DATE_FORMAT(date, '%Y-%m'), transaction_type
            ORDER BY month DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error fetching monthly transactions: " . $e->getMessage());
        return [];
    }
}


// Function for getting the total for the current month

function getCurrentMonthTotal($monthly_summary, $type = null) {
    $current_month = date('Y-m');
    $total = 0;
    
    foreach ($monthly_summary as $month) {
        if ($month['month'] === $current_month) {
            if ($type === null) {
                $total += ($month['transaction_type'] === 'income' ? $month['total'] : -$month['total']);
            } elseif ($month['transaction_type'] === $type) {
                $total += $month['total'];
            }
        }
    }
    return $total;
}

// Function for getting the Total last month

function getLastMonthTotal($monthly_summary, $type = null) {
    $last_month = date('Y-m', strtotime('-1 month'));
    $total = 0;
    
    foreach ($monthly_summary as $month) {
        if ($month['month'] === $last_month) {
            if ($type === null) {
                $total += ($month['transaction_type'] === 'income' ? $month['total'] : -$month['total']);
            } elseif ($month['transaction_type'] === $type) {
                $total += $month['total'];
            }
        }
    }
    return $total;
}

// Fuction for getting the total categories

function getCategoryTotals($conn, $user_id, $type = null) {
    try {
        $sql = "
            SELECT c.name, c.type, SUM(t.amount) as total
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? AND (c.user_id = ? OR c.user_id IS NULL)
        ";
        $params = [$user_id, $user_id];
        
        if ($type) {
            $sql .= " AND t.transaction_type = ?";
            $params[] = $type;
        }
        
        $sql .= " GROUP BY c.id, c.name, c.type ORDER BY total DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error fetching category totals: " . $e->getMessage());
        return [];
    }
}

// Function for getting accounts
function getAccounts($conn, $user_id) {
    try {
        $stmt = $conn->prepare("
            SELECT * FROM accounts 
            WHERE user_id = ?
            ORDER BY name ASC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error fetching accounts: " . $e->getMessage());
        return [];
    }
}

// Function for getting budgets
function getBudgets($conn, $user_id) {
    try {
        $stmt = $conn->prepare("
            SELECT b.*, c.name as category_name 
            FROM budgets b 
            LEFT JOIN categories c ON b.category_id = c.id 
            WHERE b.user_id = ?
            ORDER BY b.start_date DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error fetching budgets: " . $e->getMessage());
        return [];
    }
}

?> 