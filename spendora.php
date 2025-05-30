<?php
session_start();
require_once 'dbconn.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Financial advice dataset
$financial_tips = [
    'budgeting' => [
        'Start by tracking all your expenses for a month to understand your spending patterns.',
        'Use the 50/30/20 rule: 50% for needs, 30% for wants, and 20% for savings.',
        'Set up automatic transfers to your savings account on payday.',
        'Review your subscriptions regularly and cancel unused ones.',
        'Create an emergency fund with 3-6 months of living expenses.'
    ],
    'investment' => [
        'Start investing early to take advantage of compound interest.',
        'Diversify your investments across different asset classes.',
        'Consider low-cost index funds for long-term investing.',
        'Don\'t invest money you\'ll need in the next 3-5 years.',
        'Regularly rebalance your investment portfolio.'
    ],
    'debt' => [
        'Pay off high-interest debt first (debt avalanche method).',
        'Consider debt consolidation for multiple high-interest loans.',
        'Avoid taking on new debt while paying off existing debt.',
        'Create a debt payoff plan and stick to it.',
        'Use windfalls (bonuses, tax refunds) to pay down debt.'
    ],
    'savings' => [
        'Set specific savings goals with deadlines.',
        'Use separate savings accounts for different goals.',
        'Take advantage of employer matching for retirement accounts.',
        'Consider high-yield savings accounts for better interest rates.',
        'Automate your savings to make it consistent.'
    ]
];

// System guidance responses
$system_guidance = [
    'dashboard' => 'The dashboard shows your financial overview, including income, expenses, and savings. You can view your recent transactions and budget status here.',
    'budget' => 'In the budget section, you can set monthly budgets for different categories and track your spending against these limits.',
    'transactions' => 'The transactions page allows you to add, edit, and categorize your income and expenses.',
    'profile' => 'Update your personal information and account settings in the profile section.',
    'categories' => 'Customize your spending categories to better track your expenses.'
];

function getWelcomeMessage() {
    return "👋 Hello! I'm Spendora, your AI financial assistant. I'm here to help you with:\n\n" .
           "📊 Budgeting advice\n" .
           "💰 Investment strategies\n" .
           "💳 Debt management\n" .
           "💵 Saving tips\n" .
           "❓ System guidance\n\n" .
           "Feel free to ask me anything about your finances or how to use Spendex!";
}

function clearChatHistory($user_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("DELETE FROM chat_messages WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $user_id]);
        return true;
    } catch (PDOException $e) {
        error_log("Error clearing chat history: " . $e->getMessage());
        return false;
    }
}

function getChatbotResponse($message) {
    global $financial_tips, $system_guidance;
    
    $message = strtolower($message);
    
    // Check for welcome/greeting related queries
    if (strpos($message, 'hello') !== false || 
        strpos($message, 'hi') !== false || 
        strpos($message, 'hey') !== false || 
        strpos($message, 'greet') !== false || 
        strpos($message, 'introduce') !== false || 
        strpos($message, 'who are you') !== false) {
        return getWelcomeMessage();
    }
    
    // Check for exact category names from welcome message
    if (strpos($message, 'budgeting advice') !== false) {
        return $financial_tips['budgeting'][array_rand($financial_tips['budgeting'])];
    }
    
    if (strpos($message, 'investment strategies') !== false) {
        return $financial_tips['investment'][array_rand($financial_tips['investment'])];
    }
    
    if (strpos($message, 'debt management') !== false) {
        return $financial_tips['debt'][array_rand($financial_tips['debt'])];
    }
    
    if (strpos($message, 'saving tips') !== false) {
        return $financial_tips['savings'][array_rand($financial_tips['savings'])];
    }
    
    if (strpos($message, 'system guidance') !== false) {
        return "I can help you understand how to use different features of Spendex:\n\n" .
               "📊 Dashboard: View your financial overview\n" .
               "💰 Budget: Set and track monthly budgets\n" .
               "💳 Transactions: Manage your income and expenses\n" .
               "👤 Profile: Update your account settings\n" .
               "📁 Categories: Customize your spending categories";
    }
    
    // Check for system guidance queries
    foreach ($system_guidance as $topic => $response) {
        if (strpos($message, $topic) !== false) {
            return $response;
        }
    }
    
    // Check for financial advice queries with expanded keyword matching
    if (strpos($message, 'saving') !== false || strpos($message, 'save') !== false || strpos($message, 'savings') !== false) {
        return $financial_tips['savings'][array_rand($financial_tips['savings'])];
    }
    
    if (strpos($message, 'invest') !== false || strpos($message, 'investment') !== false || strpos($message, 'investing') !== false) {
        return $financial_tips['investment'][array_rand($financial_tips['investment'])];
    }
    
    if (strpos($message, 'budget') !== false || strpos($message, 'budgeting') !== false) {
        return $financial_tips['budgeting'][array_rand($financial_tips['budgeting'])];
    }
    
    if (strpos($message, 'debt') !== false || strpos($message, 'loan') !== false) {
        return $financial_tips['debt'][array_rand($financial_tips['debt'])];
    }
    
    // Default responses
    $default_responses = [
        'Hello! I\'m Spendora, your financial advisor. How can I help you today?',
        'I can help you with budgeting tips, investment advice, debt management, and general financial guidance.',
        'Would you like to know more about budgeting, investing, or managing debt?',
        'I can also explain how to use different features of the Spendex system.',
        'Feel free to ask me any questions about your finances or how to use this system.'
    ];
    
    return $default_responses[array_rand($default_responses)];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Handle logout request
        if (isset($data['logout']) && $data['logout'] === true) {
            $user_id = $_SESSION['user_id'];
            clearChatHistory($user_id);
            echo json_encode(['success' => true]);
            exit();
        }
        
        if (isset($data['message'])) {
            $message = $data['message'];
            $user_id = $_SESSION['user_id'];
            
            // Save user message
            $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, message, is_user) VALUES (:user_id, :message, 1)");
            $stmt->execute([
                ':user_id' => $user_id,
                ':message' => $message
            ]);
            
            // Get and save chatbot response
            $response = getChatbotResponse($message);
            $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, message, is_user) VALUES (:user_id, :message, 0)");
            $stmt->execute([
                ':user_id' => $user_id,
                ':message' => $response
            ]);
            
            echo json_encode(['response' => $response]);
            exit();
        }
        
        if (isset($data['get_history'])) {
            $user_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("SELECT message, is_user, timestamp FROM chat_messages WHERE user_id = :user_id ORDER BY timestamp ASC");
            $stmt->execute([':user_id' => $user_id]);
            $history = $stmt->fetchAll();
            
            // If no chat history exists, add welcome message
            if (empty($history)) {
                $welcome_message = getWelcomeMessage();
                $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, message, is_user) VALUES (:user_id, :message, 0)");
                $stmt->execute([
                    ':user_id' => $user_id,
                    ':message' => $welcome_message
                ]);
                
                // Fetch the updated history including the welcome message
                $stmt = $conn->prepare("SELECT message, is_user, timestamp FROM chat_messages WHERE user_id = :user_id ORDER BY timestamp ASC");
                $stmt->execute([':user_id' => $user_id]);
                $history = $stmt->fetchAll();
            }
            
            echo json_encode(['history' => $history]);
            exit();
        }
    } catch (PDOException $e) {
        error_log("Spendora Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred while processing your request. Please try again.']);
        exit();
    }
}
?> 