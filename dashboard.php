<?php
session_start();


if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    header('Location: index.php?error=not_logged_in');
    exit;
}

include_once 'dbconn.php';
include_once 'functions.php';


$user_id = $_SESSION['user_id'];

// Get transactions
try {
    $stmt = $conn->prepare("
        SELECT t.*, c.type as category_type, c.name as category_name, c.color as category_color 
        FROM transactions t 
        LEFT JOIN categories c ON t.category_id = c.id 
        WHERE t.user_id = ?
        ORDER BY t.date DESC
    ");
    $stmt->execute([$user_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching transactions: " . $e->getMessage());
    $transactions = [];
}

// Calculate totals
$total_balance = 0;
$total_income = 0;
$total_expenses = 0;

// First get all account balances
try {
    $stmt = $conn->prepare("SELECT SUM(balance) as total_account_balance FROM accounts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $account_balance = $stmt->fetchColumn();
    $total_balance = floatval($account_balance);
} catch(PDOException $e) {
    error_log("Error fetching account balances: " . $e->getMessage());
}

// Then add transaction totals
foreach ($transactions as $transaction) {
    $amount = floatval($transaction['amount']);
    if ($transaction['category_type'] === 'income') {
        $total_income += $amount;
    } else {
        $total_expenses += $amount;
    }
}

// Get categories
try {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE user_id = ? ORDER BY name ASC");
    $stmt->execute([$user_id]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    $categories = [];
}

// Get monthly summary
try {
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(date, '%Y-%m') as month,
            SUM(CASE WHEN c.type = 'income' THEN amount ELSE 0 END) as income,
            SUM(CASE WHEN c.type = 'expense' THEN amount ELSE 0 END) as expenses
        FROM transactions t
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ?
        GROUP BY DATE_FORMAT(date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12
    ");
    $stmt->execute([$user_id]);
    $monthly_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching monthly summary: " . $e->getMessage());
    $monthly_summary = [];
}

// Get accounts
try {
    $stmt = $conn->prepare("SELECT * FROM accounts WHERE user_id = ? ORDER BY name ASC");
    $stmt->execute([$user_id]);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching accounts: " . $e->getMessage());
    $accounts = [];
}

// Get active budgets
try {
    $stmt = $conn->prepare("
        SELECT b.*, c.name as category_name,
        (SELECT COALESCE(SUM(amount), 0) 
         FROM transactions t 
         WHERE t.category_id = b.category_id 
         AND t.user_id = :user_id
         AND t.date BETWEEN b.start_date AND b.end_date) as expenses
        FROM budgets b 
        LEFT JOIN categories c ON b.category_id = c.id 
        WHERE b.end_date >= CURDATE() 
        AND b.user_id = :user_id
        ORDER BY b.end_date ASC
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $active_budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching budgets: " . $e->getMessage());
    $active_budgets = [];
}

// Get user's preferred currency
try {
    $stmt = $conn->prepare("SELECT currency FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_currency = $stmt->fetchColumn() ?: 'PHP';
} catch(PDOException $e) {
    error_log("Error fetching user currency: " . $e->getMessage());
    $user_currency = 'PHP';
}

// Get currency symbol
$currency_symbols = [
    'PHP' => '₱',
    'USD' => '$',
    'EUR' => '€',
    'GBP' => '£',
    'JPY' => '¥',
    'KRW' => '₩',
    'CAD' => 'C$',
    'AUD' => 'A$'
];
$currency_symbol = $currency_symbols[$user_currency] ?? $user_currency;

?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spendex - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            'bg-primary': '#1a1a1a',
                            'bg-secondary': '#2d2d2d',
                            'text-primary': '#ffffff',
                            'text-secondary': '#a0aec0',
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-[#EAEAEA] dark:bg-dark-bg-primary min-h-screen transition-colors duration-200">
         
            
        
    <!-- Sidebar -->
    <div id="sidebar" class="fixed top-0 left-0 h-full w-64 bg-white dark:bg-dark-bg-secondary text-gray-800 dark:text-white transform lg:translate-x-0 -translate-x-full transition-transform duration-300 ease-in-out z-50">
        <div class="p-4">
            <div class="mb-10 mt-5 flex items-center space-x-2">
                <img src="lightmode.png" alt="Spendex Logo" class="h-14 w-14 block dark:hidden">
                <img src="darkmode.png" alt="Spendex Logo" class="h-14 w-14 hidden dark:block">
                <div class="flex flex-col">
                    <span class="text-3xl font-bold mb-1">Spendex</span>
                    <span class="text-[12px] font-medium text-gray-600 dark:text-gray-400">Expense Tracker Web App</span>
                </div>
            </div>
            <nav>
                <ul class="space-y-2">
                    <li>
                        <a href="dashboard.php" class="flex items-center p-2 pl-5 bg-[#187C19] text-white rounded-full">
                            <i class="fas fa-home mr-3"></i>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="categories.php" class="flex items-center p-2 pl-5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full">
                            <i class="fas fa-tags mr-3"></i>
                            Categories
                        </a>
                    </li>
                    <li>
                        <a href="budget.php" class="flex items-center p-2 pl-5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full">
                            <i class="fas fa-chart-pie mr-3"></i>
                            Budgets
                        </a>
                    </li>
                    <li>
                        <a href="transaction.php" class="flex items-center p-2 pl-5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full">
                            <i class="fas fa-exchange-alt mr-3"></i>
                            Transactions
                        </a>
                    </li>
                    <li>
                        <a href="account.php" class="flex items-center p-2 pl-5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full">
                            <i class="fas fa-wallet mr-3"></i>
                            Accounts
                        </a>
                    </li>
                    <li>
                        <a href="profile.php" class="flex items-center p-2 pl-5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full">
                            <i class="fas fa-user mr-3"></i>
                            Profile
                        </a>
                    </li>
                    <li class="pt-4 mt-4 border-t border-gray-700">
                        <a href="actions/logout.php" class="flex items-center p-2 pl-5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full text-red-400">
                            <i class="fas fa-sign-out-alt mr-3"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>


    <!-- Overlay -->
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-40"></div>


    <!-- Main -->
    <div class="min-h-screen lg:ml-64">


        <!-- header part -->
        <nav>
            <div class="container mx-auto px-6 py-3 flex justify-between items-center border-b border-gray-300 dark:border-gray-700">
                <div class="flex items-center">
                    <button id="sidebarToggle" class="text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-white focus:outline-none mr-4 ">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
                <div class="flex items-center space-x-4">
                    <button id="themeToggle" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none">
                        <i class="fas fa-sun text-yellow-500 dark:hidden"></i>
                        <i class="fas fa-moon text-blue-300 hidden dark:block"></i>
                    </button>
                    <span class="text-gray-700 font-bold dark:text-gray-300">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                </div>
            </div>
        </nav>

        <!-- Add Transaction Modal -->
        <div id="addTransactionModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
            <div class="bg-white dark:bg-dark-bg-secondary rounded-lg p-8 w-full max-w-md">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Add New Transaction</h2>
                    <button onclick="closeAddTransactionModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
                <?php endif; ?>
                <?php if (isset($_GET['success'])): ?>
                <div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-4">
                    Transaction added successfully!
                </div>
                <?php endif; ?>
                <form action="actions/add_transaction.php" method="POST" class="transaction-form">
                    <div class="mb-4">
                        <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Transaction Type</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="transaction_type" value="income" class="form-radio" required>
                                <span class="ml-2 text-gray-700 dark:text-gray-300">Income</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="transaction_type" value="expense" class="form-radio" required>
                                <span class="ml-2 text-gray-700 dark:text-gray-300">Expense</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="amount" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Amount (<?php echo $currency_symbol; ?>)</label>
                            <input type="number" step="0.01" min="0.01" id="amount" name="amount" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                        
                        <div>
                            <label for="category" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Category</label>
                            <select id="category" name="category_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>" data-type="<?php echo htmlspecialchars($category['type']); ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="budgetStatus" class="mt-2 text-sm hidden">
                                <!-- Budget status will be shown here -->
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="description" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Description</label>
                        <input type="text" id="description" name="description" maxlength="255" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="date" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Date</label>
                        <input type="date" id="date" name="date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-4">
                        <label for="account" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Account</label>
                        <select id="account" name="account_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                            <option value="">Select an account</option>
                            <?php foreach ($accounts as $account): ?>
                            <option value="<?php echo htmlspecialchars($account['id']); ?>">
                                <?php echo htmlspecialchars($account['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <button type="button" onclick="closeAddTransactionModal()" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Cancel
                        </button>
                        <button type="submit" class="bg-[#187C19] hover:bg-green-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Add Transaction
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Account Modal -->
        <div id="addAccountModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
            <div class="bg-white dark:bg-dark-bg-secondary rounded-lg p-8 w-full max-w-md">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Add New Account</h2>
                    <button onclick="closeAddAccountModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
                <?php endif; ?>
                <form action="actions/add_account.php" method="POST" class="account-form">
                    <div class="mb-4">
                        <label for="account_name" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Account Name</label>
                        <input type="text" id="account_name" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="account_type" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Account Type</label>
                        <select id="account_type" name="type" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                            <option value="">Select account type</option>
                            <option value="checking">Checking Account</option>
                            <option value="savings">Savings Account</option>
                            <option value="credit">Credit Card</option>
                            <option value="cash">Cash</option>
                            <option value="ewallet">E-Wallet</option>
                            <option value="investment">Investment</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="account_balance" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Initial Balance</label>
                        <input type="number" step="0.01" id="account_balance" name="balance" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>

                    <div class="mb-4">
                        <label for="account_currency" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Currency</label>
                        <select id="account_currency" name="currency" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                            <option value="PHP">Philippine Peso (₱)</option>
                            <option value="USD">US Dollar ($)</option>
                            <option value="EUR">Euro (€)</option>
                            <option value="GBP">British Pound (£)</option>
                            <option value="JPY">Japanese Yen (¥)</option>
                            <option value="KRW">Korean Won (₩)</option>
                            <option value="CAD">Canadian Dollar (C$)</option>
                            <option value="AUD">Australian Dollar (A$)</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="account_description" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Description (Optional)</label>
                        <textarea id="account_description" name="description" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" rows="2"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <button type="button" onclick="closeAddAccountModal()" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Cancel
                        </button>
                        <button type="submit" class="bg-[#187C19] hover:bg-green-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Add Account
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Account Overlay -->
        <div id="editAccountOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
            <!-- Centered Panel -->
            <div class="w-full max-w-md bg-white dark:bg-dark-bg-secondary rounded-lg shadow-md p-6">
                <!-- Header -->
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">Edit Account</h2>
                    <button onclick="closeEditAccountOverlay()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Form Content -->
                <form action="actions/edit_account.php" method="POST" class="edit-account-form">
                    <input type="hidden" id="edit_account_id" name="id">
                    
                    <div class="mb-4">
                        <label for="edit_account_name" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Account Name</label>
                        <input type="text" id="edit_account_name" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit_account_type" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Account Type</label>
                        <select id="edit_account_type" name="type" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                            <option value="">Select account type</option>
                            <option value="checking">Checking Account</option>
                            <option value="savings">Savings Account</option>
                            <option value="credit">Credit Card</option>
                            <option value="cash">Cash</option>
                            <option value="ewallet">E-Wallet</option>
                            <option value="investment">Investment</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit_account_balance" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Balance</label>
                        <input type="number" step="0.01" id="edit_account_balance" name="balance" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>

                    <div class="mb-4">
                        <label for="edit_account_currency" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Currency</label>
                        <select id="edit_account_currency" name="currency" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                            <option value="PHP">Philippine Peso (₱)</option>
                            <option value="USD">US Dollar ($)</option>
                            <option value="EUR">Euro (€)</option>
                            <option value="GBP">British Pound (£)</option>
                            <option value="JPY">Japanese Yen (¥)</option>
                            <option value="KRW">Korean Won (₩)</option>
                            <option value="CAD">Canadian Dollar (C$)</option>
                            <option value="AUD">Australian Dollar (A$)</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="edit_account_description" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Description (Optional)</label>
                        <textarea id="edit_account_description" name="description" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" rows="3"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <button type="button" onclick="closeEditAccountOverlay()" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Cancel
                        </button>
                        <button type="submit" class="bg-[#187C19] hover:bg-green-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Update Account
                        </button>
                    </div>
                </form>
            </div>
        </div>


        <!-- Add Budget Modal -->
        <div id="addBudgetModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
            <div class="bg-white dark:bg-dark-bg-secondary rounded-lg p-8 w-full max-w-md">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Add New Budget</h2>
                    <button onclick="closeAddBudgetModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
                <?php endif; ?>
                <form id="addBudgetForm" class="budget-form">
                    <div class="mb-4">
                        <label for="budget_name" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Budget Name</label>
                        <input type="text" id="budget_name" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="budget_category" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Category</label>
                        <select id="budget_category" name="category_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <?php if ($category['type'] === 'expense'): ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>" style="color: <?php echo htmlspecialchars($category['color']); ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="budget_amount" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Budget Amount (₱)</label>
                        <input type="number" step="0.01" min="0.01" id="budget_amount" name="amount" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="budget_start_date" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Start Date</label>
                            <input type="date" id="budget_start_date" name="start_date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div>
                            <label for="budget_end_date" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">End Date</label>
                            <input type="date" id="budget_end_date" name="end_date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo date('Y-m-d', strtotime('+1 month')); ?>" required>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <button type="button" onclick="closeAddBudgetModal()" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Cancel
                        </button>
                        <button type="submit" class="bg-[#187C19] hover:bg-green-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Add Budget
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Budget Overlay -->
        <div id="editBudgetOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
            <div class="bg-white dark:bg-dark-bg-secondary rounded-lg p-8 w-full max-w-md">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Edit Budget</h2>
                    <button onclick="closeEditBudgetOverlay()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="editBudgetForm" class="space-y-4">
                    <input type="hidden" id="edit_budget_id" name="id">
                    
                    <div class="mb-4">
                        <label for="edit_budget_name" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Budget Name</label>
                        <input type="text" id="edit_budget_name" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit_budget_category" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Category</label>
                        <select id="edit_budget_category" name="category_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <?php if ($category['type'] === 'expense'): ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>" style="color: <?php echo htmlspecialchars($category['color']); ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit_budget_amount" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Budget Amount (₱)</label>
                        <input type="number" step="0.01" min="0.01" id="edit_budget_amount" name="amount" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="edit_budget_start_date" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Start Date</label>
                            <input type="date" id="edit_budget_start_date" name="start_date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                        <div>
                            <label for="edit_budget_end_date" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">End Date</label>
                            <input type="date" id="edit_budget_end_date" name="end_date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <button type="button" onclick="closeEditBudgetOverlay()" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Cancel
                        </button>
                        <button type="submit" class="bg-[#187C19] hover:bg-green-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Update Budget
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Budget Confirmation Modal -->
        <div id="deleteBudgetModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
            <div class="bg-white dark:bg-dark-bg-secondary rounded-lg p-8 w-full max-w-md">
                <div class="mb-4">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Delete Budget</h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">Are you sure you want to delete this budget? This action cannot be undone.</p>
                </div>
                <input type="hidden" id="delete_budget_id">
                <div class="flex justify-end space-x-4">
                    <button onclick="closeDeleteBudgetModal()" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Cancel
                    </button>
                    <button onclick="confirmDeleteBudget()" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Delete
                    </button>
                </div>
            </div>
        </div>

        <!-- Total Balance, Income, Expenses Part-->

        <div class="container mx-auto px-4 py-8">
            
            <div class="flex justify-between items-center mb-4">
                <span class="text-3xl font-bold text-gray-800 mb-4 dark:text-gray-300">DASHBOARD</span>
                <button onclick="openAddTransactionModal()" class="bg-[#187C19] hover:bg-green-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    <i class="fas fa-plus mr-2"></i>Add Transaction
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white dark:bg-dark-bg-secondary rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Total Balance</h2>
                    <p class="text-3xl font-bold <?php echo $total_balance >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-red-600 dark:text-red-400'; ?>">
                        <?php echo $currency_symbol . number_format(abs($total_balance), 2); ?>
                    </p>
                </div>
                
                <div class="bg-white dark:bg-dark-bg-secondary rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Total Income</h2>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400"><?php echo $currency_symbol . number_format($total_income, 2); ?></p>
                </div>
                
                <div class="bg-white dark:bg-dark-bg-secondary rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Total Expenses</h2>
                    <p class="text-3xl font-bold text-red-600 dark:text-red-400"><?php echo $currency_symbol . number_format($total_expenses, 2); ?></p>
                </div>
            </div>


            <!-- Accounts Section -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-300">Your Accounts</h2>
            </div>
            <div class="relative">
                <!-- Left Arrow -->
                <button onclick="prevAccountSlide()" class="absolute left-0 top-1/2 -translate-y-1/2 z-10 p-2 rounded-full bg-white/80 dark:bg-dark-bg-secondary/80 shadow-md hover:bg-white dark:hover:bg-dark-bg-secondary backdrop-blur-sm transition-all duration-200 -ml-4">
                    <i class="fas fa-chevron-left text-gray-600 dark:text-gray-300"></i>
                </button>
                
                <!-- Right Arrow -->
                <button onclick="nextAccountSlide()" class="absolute right-0 top-1/2 -translate-y-1/2 z-10 p-2 rounded-full bg-white/80 dark:bg-dark-bg-secondary/80 shadow-md hover:bg-white dark:hover:bg-dark-bg-secondary backdrop-blur-sm transition-all duration-200 -mr-4">
                    <i class="fas fa-chevron-right text-gray-600 dark:text-gray-300"></i>
                </button>

                <div id="accountCarousel" class="overflow-hidden px-4">
                    <div id="accountSlides" class="flex transition-transform duration-300 ease-in-out">
                        <!-- Add Account Button as first slide -->
                        <div class="account-slide w-full md:w-1/2 lg:w-1/4 flex-shrink-0 px-2">
                            <button onclick="openAddAccountModal()" class="w-full h-full bg-white dark:bg-dark-bg-secondary rounded-lg shadow-md p-4 border-2 border-dashed border-gray-300 flex items-center justify-center text-gray-500 dark:text-gray-400 hover:text-blue-500 hover:border-blue-500">
                                <div class="text-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    <span class="block mt-2">Add Account</span>
                                </div>
                            </button>
                        </div>
                        <?php foreach ($accounts as $account): ?>
                        <div class="account-slide w-full md:w-1/2 lg:w-1/4 flex-shrink-0 px-2">
                            <div class="bg-white dark:bg-dark-bg-secondary rounded-lg shadow-md p-4 h-full">
                                <div class="flex justify-between items-center">
                                    <h3 class="font-bold text-gray-800 dark:text-gray-300"><?php echo htmlspecialchars($account['name']); ?></h3>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($account['type']); ?></span>
                                        <button onclick='openEditAccountOverlay(<?php echo json_encode($account); ?>)' class="text-gray-500 hover:text-blue-500 dark:text-gray-400 dark:hover:text-blue-400">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </div>
                                <p class="text-xl font-bold text-green-600 mt-2"><?php echo htmlspecialchars($account['currency']); ?> <?php echo number_format($account['balance'], 2); ?></p>
                                <?php if (!empty($account['description'])): ?>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2"><?php echo htmlspecialchars($account['description']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div id="accountDots" class="flex justify-center mt-4 space-x-2">
                    <!-- Dots will be added dynamically -->
                </div>
            </div>
        </div>

        <!-- Budgets Section -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-300">Active Budgets</h2>
            </div>
            <div class="relative">
                <!-- Left Arrow -->
                <button onclick="prevBudgetSlide()" class="absolute left-0 top-1/2 -translate-y-1/2 z-10 p-2 rounded-full bg-white/80 dark:bg-dark-bg-secondary/80 shadow-md hover:bg-white dark:hover:bg-dark-bg-secondary backdrop-blur-sm transition-all duration-200 -ml-4">
                    <i class="fas fa-chevron-left text-gray-600 dark:text-gray-300"></i>
                </button>
                
                <!-- Right Arrow -->
                <button onclick="nextBudgetSlide()" class="absolute right-0 top-1/2 -translate-y-1/2 z-10 p-2 rounded-full bg-white/80 dark:bg-dark-bg-secondary/80 shadow-md hover:bg-white dark:hover:bg-dark-bg-secondary backdrop-blur-sm transition-all duration-200 -mr-4">
                    <i class="fas fa-chevron-right text-gray-600 dark:text-gray-300"></i>
                </button>

                <div id="budgetCarousel" class="overflow-hidden px-4">
                    <div id="budgetSlides" class="flex transition-transform duration-300 ease-in-out">
                        <!-- Add Budget Button as first slide -->
                        <div class="budget-slide w-full md:w-1/2 lg:w-1/3 flex-shrink-0 px-2">
                            <button onclick="openAddBudgetModal()" class="w-full h-full bg-white dark:bg-dark-bg-secondary rounded-lg shadow-md p-4 border-2 border-dashed border-gray-300 flex items-center justify-center text-gray-500 dark:text-gray-400 hover:text-blue-500 hover:border-blue-500">
                                <div class="text-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    <span class="block mt-2">Add Budget</span>
                                </div>
                            </button>
                        </div>
                        <?php foreach ($active_budgets as $budget): ?>
                        <div class="budget-slide w-full md:w-1/2 lg:w-1/3 flex-shrink-0 px-2">
                            <div class="bg-white dark:bg-dark-bg-secondary rounded-lg shadow-md p-4 h-full">
                                <div class="flex justify-between items-center mb-2">
                                    <h3 class="font-bold text-gray-800 dark:text-gray-300"><?php echo htmlspecialchars($budget['name']); ?></h3>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($budget['category_name']); ?></span>
                                        <button onclick='openEditBudgetOverlay(<?php echo json_encode($budget); ?>)' class="text-gray-500 hover:text-blue-500 dark:text-gray-400 dark:hover:text-blue-400">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick='openDeleteBudgetModal(<?php echo $budget['id']; ?>)' class="text-gray-500 hover:text-red-500 dark:text-gray-400 dark:hover:text-red-400">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="flex justify-between text-sm dark:text-gray-400 mb-1">
                                        <span>Spent: <?php echo $currency_symbol . number_format($budget['expenses'], 2); ?></span>
                                        <span>Budget: <?php echo $currency_symbol . number_format($budget['amount'], 2); ?></span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                        <div class="h-2.5 rounded-full bg-green-600" style="width: <?php echo min(($budget['expenses'] / $budget['amount']) * 100, 100); ?>%"></div>
                                    </div>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <?php
                                    $remaining = $budget['amount'] - $budget['expenses'];
                                    $status_text = $remaining >= 0 ? 'Remaining: ' : 'Over by: ';
                                    $text_color = $remaining >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
                                    ?>
                                    <span class="<?php echo $text_color; ?>"><?php echo $status_text . $currency_symbol . number_format(abs($remaining), 2); ?></span>
                                    <span class="dark:text-gray-400"><?php echo htmlspecialchars($budget['start_date']); ?> - <?php echo htmlspecialchars($budget['end_date']); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div id="budgetDots" class="flex justify-center mt-4 space-x-2">
                    <!-- Dots will be added dynamically -->
                </div>
            </div>
        </div>



                <!-- Expense Breakdown Chart -->
                <div class="flex flex-col lg:flex-row gap-8 mb-8">
                    <!-- Expense Breakdown -->
                    <div class="flex-1">
                        <div class="bg-white dark:bg-dark-bg-secondary rounded-lg shadow-md p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Expense Breakdown</h2>
                                <select id="timeFilter" class="shadow border rounded py-1 px-2 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 text-sm focus:outline-none focus:shadow-outline">
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                            </div>

                            <div class="flex flex-col md:flex-row">
                                <!-- Chart Container -->
                                <div class="w-full md:w-1/2 relative">
                                    <canvas id="expenseChart"></canvas>
                                    <!-- Center Info Display -->
                                    <div id="centerInfo"
                                         class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none text-center">
                                        <p class="text-2xl font-bold dark:text-white"><?php echo $currency_symbol; ?><span id="totalAmount">0.00</span></p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Total</p>
                                    </div>
                                </div>
                                
                                <!-- Legend Container -->
                                <div class="w-full md:w-1/2 mt-4 md:mt-0 md:pl-6 space-y-2 overflow-y-auto max-h-[300px]" id="customLegend">
                                    <!-- Legend items will be dynamically inserted here -->
                                </div>
                            </div>
                            <!-- Add this container for the breakdown bars -->
                            <div id="expenseBreakdownBars" class="mt-8 space-y-4 max-h-[100px] overflow-y-auto pr-2"></div>
                        </div>
                    </div>

                    <!-- Income Breakdown -->
                    <div class="flex-1">
                        <div class="bg-white dark:bg-dark-bg-secondary h-[460px] rounded-lg shadow-md p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Income Breakdown</h2>
                                <select id="incomeTimeFilter" class="shadow border rounded py-1 px-2 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 text-sm focus:outline-none focus:shadow-outline">
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly" selected>Monthly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                            </div>
                            <div class="w-full">
                                <canvas id="incomeLineChart"></canvas>
                            </div>
                            <div id="incomeBreakdownLegend" class="mt-4 max-h-[100px] overflow-y-auto"></div>
                        </div>
                    </div>
                </div>


            <!-- Recent Transactions Part -->

            <div class="bg-white dark:bg-dark-bg-secondary rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b dark:border-gray-700 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Recent Transactions</h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white dark:bg-dark-bg-secondary dark:text-white">
                        <thead>
                            <tr>
                                <th class="py-3 px-6 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                <th class="py-3 px-6 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                                <th class="py-3 px-6 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Description</th>
                                <th class="py-3 px-6 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                                <th class="py-3 px-6 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="5" class="py-4 px-6 text-center text-gray-500 dark:text-gray-400">No transactions found. Add your first transaction!</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td class="py-4 px-6 text-sm text-gray-500 dark:text-gray-400"><?php echo date('M d, Y', strtotime($transaction['date'])); ?></td>
                                    <td class="py-4 px-6 text-sm">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $transaction['category_type'] === 'income' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'; ?>">
                                            <?php echo ucfirst($transaction['category_type']); ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($transaction['description']); ?></td>
                                    <td class="py-4 px-6 text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($transaction['category_name']); ?></td>
                                    <td class="py-4 px-6 text-sm font-medium <?php echo $transaction['category_type'] === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                                        <?php echo $currency_symbol . number_format($transaction['amount'], 2); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    
<!-- Javascript -->

    <script>
        let expenseChart;
        let chartData = [];
        let chartLabels = [];
        let chartColors = [];
        let chartTotals = [];

        function fetchExpenseBreakdown(period = 'monthly') {
            fetch(`actions/get_expense_breakdown.php?period=${period}`)
                .then(res => res.json())
                .then(data => {
                    chartLabels = data.map(d => d.name);
                    chartColors = data.map(d => d.color || '#4F46E5');
                    chartTotals = data.map(d => parseFloat(d.total));
                    chartData = data;
                    renderExpenseChart();
                    renderCustomLegend();
                    renderCenterInfo();
                    renderExpenseBreakdownBars();
                });
        }

        function renderExpenseChart() {
            const ctx = document.getElementById('expenseChart').getContext('2d');
            if (expenseChart) expenseChart.destroy();
            expenseChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        data: chartTotals,
                        backgroundColor: chartColors,
                        borderWidth: 0
                    }]
                },
                options: {
                    cutout: '70%',
                    plugins: {
                        legend: { display: false }
                    },
                    maintainAspectRatio: true,
                    responsive: true
                }
            });
        }

        function renderCustomLegend() {
            const total = chartTotals.reduce((a, b) => a + b, 0);
            let html = '';
            chartData.forEach((d, i) => {
                html += `
                    <div class="flex items-center mb-2">
                        <span class="inline-block w-4 h-4 rounded-full mr-2" style="background:${d.color};"></span>
                        <span class="font-bold text-sm dark:text-white">${d.name}</span>
                    </div>
                `;
            });
            document.getElementById('customLegend').innerHTML = html;
        }

        function renderCenterInfo() {
            const total = chartTotals.reduce((a, b) => a + b, 0);
            document.getElementById('totalAmount').textContent = total.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function renderExpenseBreakdownBars() {
            const total = chartTotals.reduce((a, b) => a + b, 0);
            let html = '';
            chartData.forEach((d, i) => {
                const percent = total ? ((d.total / total) * 100).toFixed(2) : 0;
                
                html += `
                    <div class="flex items-center">
                        <div class="flex-1">
                            <div class="flex justify-between items-center mb-1">
                                <span class="font-semibold text-gray-700 dark:text-gray-100">${d.name}</span>
                                <span class="text-gray-600 dark:text-gray-100">${percent}%</span>
                                <span class="text-gray-600 dark:text-gray-100"><?php echo $currency_symbol; ?>${parseFloat(d.total).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                <div class="h-2.5 rounded-full" style="width: ${percent}%; background: ${d.color};"></div>
                            </div>
                        </div>
                    </div>
                `;
            });
            document.getElementById('expenseBreakdownBars').innerHTML = html;
        }

        document.getElementById('timeFilter').addEventListener('change', function() {
            fetchExpenseBreakdown(this.value);
        });

        // Initial load
        fetchExpenseBreakdown();

        // Sidebar toggle functionality
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const sidebarToggle = document.getElementById('sidebarToggle');

        function toggleSidebar() {
            if (window.innerWidth < 1024) { // Only toggle on mobile
                sidebar.classList.toggle('-translate-x-full');
                overlay.classList.toggle('hidden');
            }
        }

        sidebarToggle.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);

        // Handle sidebar visibility on window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.add('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
            }
        });

        // Dark mode functionality
        const themeToggle = document.getElementById('themeToggle');
        
        // Check for saved theme preference or use system preference
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        // Toggle theme
        themeToggle.addEventListener('click', () => {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                document.documentElement.classList.add('dark');
                localStorage.theme = 'dark';
            }
            // Update chart colors
            expenseChart.data.datasets[0].backgroundColor = chartColors;
            expenseChart.update();
        });

        // Add Transaction Modal Functions
        function openAddTransactionModal() {
            const modal = document.getElementById('addTransactionModal');
            if (modal) {
                modal.classList.remove('hidden');
                // Reset form when opening modal
                const form = modal.querySelector('form');
                if (form) {
                    form.reset();
                }
            }
        }

        function closeAddTransactionModal() {
            const modal = document.getElementById('addTransactionModal');
            if (modal) {
                modal.classList.add('hidden');
                // Reset form when closing modal
                const form = modal.querySelector('form');
                if (form) {
                    form.reset();
                }
            }
        }

        // Category filtering based on transaction type
        document.addEventListener('DOMContentLoaded', function() {
            const transactionTypeRadios = document.querySelectorAll('input[name="transaction_type"]');
            const categorySelect = document.getElementById('category');

            if (categorySelect) {
                transactionTypeRadios.forEach(radio => {
                    radio.addEventListener('change', function() {
                        const selectedType = this.value;
                        
                        // Reset category selection
                        categorySelect.value = '';
                        
                        // Show/hide categories based on type
                        categorySelect.querySelectorAll('option').forEach(option => {
                            if (option.value === '') {
                                option.style.display = ''; // Always show the default option
                            } else {
                                const categoryType = option.getAttribute('data-type');
                                if (categoryType === selectedType) {
                                    option.style.display = '';
                                    option.disabled = false;
                                } else {
                                    option.style.display = 'none';
                                    option.disabled = true;
                                }
                            }
                        });
                    });
                });

                // Trigger initial filtering if a transaction type is already selected
                const checkedRadio = document.querySelector('input[name="transaction_type"]:checked');
                if (checkedRadio) {
                    checkedRadio.dispatchEvent(new Event('change'));
                }
            }
        });

        // Check URL parameters for modal
        window.addEventListener('load', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('action') && urlParams.get('action') === 'add') {
                openAddTransactionModal();
            }
        });

        // Form submission handling
        document.addEventListener('DOMContentLoaded', function() {
            const addTransactionForm = document.querySelector('.transaction-form');
            if (addTransactionForm) {
                addTransactionForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Get form data
                    const formData = new FormData(this);
                    
                    // Send POST request to add_transaction.php
                    fetch('actions/add_transaction.php', {
                method: 'POST',
                body: formData
            })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success message
                            const successDiv = document.createElement('div');
                            successDiv.className = 'bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-4';
                            successDiv.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + data.message;
                            const form = document.querySelector('.transaction-form');
                            form.insertBefore(successDiv, form.firstChild);
                            
                            // Clear the form
                            form.reset();
                            
                            // Redirect to transaction page after 1 second
                            setTimeout(() => {
                                window.location.href = 'transaction.php';
                            }, 1000);
                        } else {
                            // Show error message
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-4';
                            errorDiv.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + (data.message || 'Failed to add transaction');
                            const form = document.querySelector('.transaction-form');
                            form.insertBefore(errorDiv, form.firstChild);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-4';
                        errorDiv.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>An error occurred while adding the transaction';
                        const form = document.querySelector('.transaction-form');
                        form.insertBefore(errorDiv, form.firstChild);
                    });
                });
            }
        });

        // Add after all other chart JS
        let incomeLineChart;

        function fetchIncomeBreakdown(period = 'monthly') {
            fetch(`actions/get_income_breakdown.php?period=${period}`)
                .then(res => res.json())
                .then(data => {
                    renderIncomeLineChart(data, period);
                    renderIncomeLegend(data);
                });
        }

        function renderIncomeLineChart(data, period) {
            const periods = [...new Set(data.map(d => d.period))];
            const categories = [...new Set(data.map(d => d.category))];
            const datasets = categories.map((cat) => {
                const categoryData = data.find(d => d.category === cat);
                return {
                    label: cat,
                    data: periods.map(p => {
                        const found = data.find(d => d.period === p && d.category === cat);
                        return found ? parseFloat(found.total) : 0;
                    }),
                    borderColor: categoryData.color,
                    backgroundColor: categoryData.color,
                    tension: 0.3,
                    fill: false
                };
            });

            const ctx = document.getElementById('incomeLineChart').getContext('2d');
            if (incomeLineChart) incomeLineChart.destroy();
            incomeLineChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: periods,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: true }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        function renderIncomeLegend(data) {
            const totals = {};
            data.forEach(d => {
                if (!totals[d.category]) totals[d.category] = 0;
                totals[d.category] += parseFloat(d.total);
            });
            let html = '';
            Object.entries(totals).forEach(([cat, total]) => {
                html += `<div class="flex justify-between text-gray-700 dark:text-gray-300">
                    <span class="font-bold">${cat}</span>
                    <span><?php echo $currency_symbol; ?>${total.toFixed(2)}</span>
                </div>`;
            });
            document.getElementById('incomeBreakdownLegend').innerHTML = html;
        }

        document.getElementById('incomeTimeFilter').addEventListener('change', function() {
            fetchIncomeBreakdown(this.value);
        });

        // Initial load for income chart
        fetchIncomeBreakdown();

        // Add Account Modal Functions
        function openAddAccountModal() {
            const modal = document.getElementById('addAccountModal');
            if (modal) {
                modal.classList.remove('hidden');
                // Reset form when opening modal
                const form = modal.querySelector('form');
                if (form) {
                    form.reset();
                }
            }
        }

        function closeAddAccountModal() {
            const modal = document.getElementById('addAccountModal');
            if (modal) {
                modal.classList.add('hidden');
                // Reset form when closing modal
                const form = modal.querySelector('form');
                if (form) {
                    form.reset();
                }
            }
        }

        // Add Budget Modal Functions
        function openAddBudgetModal() {
            const modal = document.getElementById('addBudgetModal');
            if (modal) {
                modal.classList.remove('hidden');
                // Reset form when opening modal
                const form = modal.querySelector('form');
                if (form) {
                    form.reset();
                }
            }
        }

        function closeAddBudgetModal() {
            const modal = document.getElementById('addBudgetModal');
            if (modal) {
                modal.classList.add('hidden');
                // Reset form when closing modal
                const form = modal.querySelector('form');
                if (form) {
                    form.reset();
                }
            }
        }

        // Form submission handling for accounts and budgets
        document.addEventListener('DOMContentLoaded', function() {
            const accountForm = document.querySelector('.account-form');
            const budgetForm = document.querySelector('.budget-form');

            if (accountForm) {
                accountForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    
                    fetch('actions/add_account.php', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Close the modal
                            closeAddAccountModal();
                            // Reload the page to show the new account
                            window.location.reload();
                        } else {
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-4';
                            errorDiv.textContent = data.message || 'Failed to add account';
                            accountForm.insertBefore(errorDiv, accountForm.firstChild);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-4';
                        errorDiv.textContent = 'An error occurred while adding the account';
                        accountForm.insertBefore(errorDiv, accountForm.firstChild);
                    });
                });
            }

            if (budgetForm) {
                budgetForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    
                    fetch('actions/add_budget.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Close the modal
                            closeAddBudgetModal();
                            // Reload the page to show the new budget
                            window.location.reload();
                        } else {
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-4';
                            errorDiv.textContent = data.message || 'Failed to add budget';
                            // Remove any existing error message
                            const existingError = budgetForm.querySelector('.bg-red-100');
                            if (existingError) {
                                existingError.remove();
                            }
                            budgetForm.insertBefore(errorDiv, budgetForm.firstChild);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-4';
                        errorDiv.textContent = 'An error occurred while adding the budget';
                        // Remove any existing error message
                        const existingError = budgetForm.querySelector('.bg-red-100');
                        if (existingError) {
                            existingError.remove();
                        }
                        budgetForm.insertBefore(errorDiv, budgetForm.firstChild);
                    });
                });
            }
        });

        // Edit Account Overlay Functions
        function openEditAccountOverlay(account) {
            const overlay = document.getElementById('editAccountOverlay');
            
            if (overlay) {
                // Fill in the form with account data
                document.getElementById('edit_account_id').value = account.id;
                document.getElementById('edit_account_name').value = account.name;
                document.getElementById('edit_account_type').value = account.type;
                document.getElementById('edit_account_balance').value = account.balance;
                document.getElementById('edit_account_currency').value = account.currency;
                document.getElementById('edit_account_description').value = account.description || '';
                
                // Show overlay
                overlay.classList.remove('hidden');
                // Prevent main content scrolling
                document.body.style.overflow = 'hidden';
            }
        }

        function closeEditAccountOverlay() {
            const overlay = document.getElementById('editAccountOverlay');
            
            if (overlay) {
                overlay.classList.add('hidden');
                // Reset form
                const form = overlay.querySelector('form');
                if (form) {
                    form.reset();
                }
                // Restore main content scrolling
                document.body.style.overflow = '';
            }
        }

        // Add click outside to close functionality
        document.addEventListener('DOMContentLoaded', function() {
            const overlay = document.getElementById('editAccountOverlay');
            
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    closeEditAccountOverlay();
                }
            });
        });

        // Form submission handling for edit account
        document.addEventListener('DOMContentLoaded', function() {
            const editAccountForm = document.querySelector('.edit-account-form');
            if (editAccountForm) {
                editAccountForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    
                    fetch('actions/edit_account.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Close the overlay
                            closeEditAccountOverlay();
                            // Reload the page to show updated data
                            window.location.reload();
                        } else {
                            // Show error message
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-4';
                            errorDiv.textContent = data.message || 'Failed to update account';
                            const form = document.querySelector('.edit-account-form');
                            form.insertBefore(errorDiv, form.firstChild);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-4';
                        errorDiv.textContent = 'An error occurred while updating the account';
                        const form = document.querySelector('.edit-account-form');
                        form.insertBefore(errorDiv, form.firstChild);
                    });
                });
            }
        });

        // Budget Carousel functionality
        let currentBudgetSlide = 0;
        const budgetSlides = document.querySelectorAll('.budget-slide');
        const budgetSlidesContainer = document.getElementById('budgetSlides');
        const budgetDotsContainer = document.getElementById('budgetDots');
        let slidesPerView = window.innerWidth >= 1024 ? 3 : window.innerWidth >= 768 ? 2 : 1;

        function updateBudgetSlidesPerView() {
            slidesPerView = window.innerWidth >= 1024 ? 3 : window.innerWidth >= 768 ? 2 : 1;
            updateBudgetCarousel();
        }

        function updateBudgetCarousel() {
            const slideWidth = 100 / slidesPerView;
            budgetSlides.forEach(slide => {
                slide.style.width = `${slideWidth}%`;
            });
            updateBudgetSlidePosition();
            updateBudgetDots();
            
            // Show/hide arrows based on position
            const leftArrow = document.querySelector('[onclick="prevBudgetSlide()"]');
            const rightArrow = document.querySelector('[onclick="nextBudgetSlide()"]');
            const maxSlide = Math.max(0, budgetSlides.length - slidesPerView);
            
            // Always show left arrow if we're not at the start
            if (leftArrow) {
                leftArrow.style.opacity = currentBudgetSlide > 0 ? '1' : '0';
                leftArrow.style.pointerEvents = currentBudgetSlide > 0 ? 'auto' : 'none';
            }
            
            // Show right arrow if there are more slides to show
            if (rightArrow) {
                rightArrow.style.opacity = currentBudgetSlide < maxSlide ? '1' : '0';
                rightArrow.style.pointerEvents = currentBudgetSlide < maxSlide ? 'auto' : 'none';
            }
        }

        function updateBudgetSlidePosition() {
            const slideWidth = 100 / slidesPerView;
            const maxSlide = Math.max(0, budgetSlides.length - slidesPerView);
            currentBudgetSlide = Math.min(Math.max(0, currentBudgetSlide), maxSlide);
            budgetSlidesContainer.style.transform = `translateX(-${currentBudgetSlide * slideWidth}%)`;
        }

        function updateBudgetDots() {
            const maxSlide = Math.max(0, budgetSlides.length - slidesPerView);
            budgetDotsContainer.innerHTML = '';
            for (let i = 0; i <= maxSlide; i++) {
                const dot = document.createElement('button');
                dot.className = `w-2 h-2 rounded-full transition-colors duration-200 ${
                    i === currentBudgetSlide ? 'bg-blue-500' : 'bg-gray-300 dark:bg-gray-600'
                }`;
                dot.onclick = () => {
                    currentBudgetSlide = i;
                    updateBudgetSlidePosition();
                    updateBudgetDots();
                };
                budgetDotsContainer.appendChild(dot);
            }
        }

        function nextBudgetSlide() {
            const maxSlide = Math.max(0, budgetSlides.length - slidesPerView);
            if (currentBudgetSlide < maxSlide) {
                currentBudgetSlide++;
                // Show left arrow when moving right
                const leftArrow = document.querySelector('[onclick="prevBudgetSlide()"]');
                if (leftArrow) {
                    leftArrow.style.opacity = '1';
                    leftArrow.style.pointerEvents = 'auto';
                }
                updateBudgetSlidePosition();
                updateBudgetDots();
            }
        }

        function prevBudgetSlide() {
            if (currentBudgetSlide > 0) {
                currentBudgetSlide--;
                updateBudgetSlidePosition();
                updateBudgetDots();
            }
        }

        // Initialize carousel with left arrow hidden
        document.addEventListener('DOMContentLoaded', function() {
            updateBudgetCarousel();
            window.addEventListener('resize', updateBudgetSlidesPerView);
            
            // Initially hide left arrow
            const leftArrow = document.querySelector('[onclick="prevBudgetSlide()"]');
            if (leftArrow) {
                leftArrow.style.opacity = '0';
                leftArrow.style.pointerEvents = 'none';
            }
        });

        // Add touch support for mobile
        let touchStartX = 0;
        let touchEndX = 0;

        document.getElementById('budgetCarousel').addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        });

        document.getElementById('budgetCarousel').addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            handleBudgetSwipe();
        });

        function handleBudgetSwipe() {
            const swipeThreshold = 50;
            if (touchEndX < touchStartX - swipeThreshold) {
                nextBudgetSlide();
            }
            if (touchEndX > touchStartX + swipeThreshold) {
                prevBudgetSlide();
            }
        }

        // Account Carousel functionality
        let currentAccountSlide = 0;
        const accountSlides = document.querySelectorAll('.account-slide');
        const accountSlidesContainer = document.getElementById('accountSlides');
        const accountDotsContainer = document.getElementById('accountDots');
        let accountSlidesPerView = window.innerWidth >= 1024 ? 4 : window.innerWidth >= 768 ? 2 : 1;

        function updateAccountSlidesPerView() {
            accountSlidesPerView = window.innerWidth >= 1024 ? 4 : window.innerWidth >= 768 ? 2 : 1;
            updateAccountCarousel();
        }

        function updateAccountCarousel() {
            const slideWidth = 100 / accountSlidesPerView;
            accountSlides.forEach(slide => {
                slide.style.width = `${slideWidth}%`;
            });
            updateAccountSlidePosition();
            updateAccountDots();
        }

        function updateAccountSlidePosition() {
            const slideWidth = 100 / accountSlidesPerView;
            const maxSlide = Math.max(0, accountSlides.length - accountSlidesPerView);
            currentAccountSlide = Math.min(Math.max(0, currentAccountSlide), maxSlide);
            accountSlidesContainer.style.transform = `translateX(-${currentAccountSlide * slideWidth}%)`;
        }

        function updateAccountDots() {
            const maxSlide = Math.max(0, accountSlides.length - accountSlidesPerView);
            accountDotsContainer.innerHTML = '';
            for (let i = 0; i <= maxSlide; i++) {
                const dot = document.createElement('button');
                dot.className = `w-2 h-2 rounded-full transition-colors duration-200 ${
                    i === currentAccountSlide ? 'bg-blue-500' : 'bg-gray-300 dark:bg-gray-600'
                }`;
                dot.onclick = () => {
                    currentAccountSlide = i;
                    updateAccountSlidePosition();
                    updateAccountDots();
                };
                accountDotsContainer.appendChild(dot);
            }
            
            // Show/hide arrows based on position
            const leftArrow = document.querySelector('[onclick="prevAccountSlide()"]');
            const rightArrow = document.querySelector('[onclick="nextAccountSlide()"]');
            
            // Always show left arrow if we're not at the start
            if (leftArrow) {
                leftArrow.style.opacity = currentAccountSlide > 0 ? '1' : '0';
                leftArrow.style.pointerEvents = currentAccountSlide > 0 ? 'auto' : 'none';
            }
            
            // Show right arrow if there are more slides to show
            if (rightArrow) {
                rightArrow.style.opacity = currentAccountSlide < maxSlide ? '1' : '0';
                rightArrow.style.pointerEvents = currentAccountSlide < maxSlide ? 'auto' : 'none';
            }
        }

        function nextAccountSlide() {
            const maxSlide = Math.max(0, accountSlides.length - accountSlidesPerView);
            if (currentAccountSlide < maxSlide) {
                currentAccountSlide++;
                // Show left arrow when moving right
                const leftArrow = document.querySelector('[onclick="prevAccountSlide()"]');
                if (leftArrow) {
                    leftArrow.style.opacity = '1';
                    leftArrow.style.pointerEvents = 'auto';
                }
                updateAccountSlidePosition();
                updateAccountDots();
            }
        }

        function prevAccountSlide() {
            if (currentAccountSlide > 0) {
                currentAccountSlide--;
                updateAccountSlidePosition();
                updateAccountDots();
            }
        }

        // Initialize account carousel with left arrow hidden
        document.addEventListener('DOMContentLoaded', function() {
            updateAccountCarousel();
            window.addEventListener('resize', updateAccountSlidesPerView);
            
            // Initially hide left arrow
            const leftArrow = document.querySelector('[onclick="prevAccountSlide()"]');
            if (leftArrow) {
                leftArrow.style.opacity = '0';
                leftArrow.style.pointerEvents = 'none';
            }
        });

        // Add touch support for account carousel
        let accountTouchStartX = 0;
        let accountTouchEndX = 0;

        document.getElementById('accountCarousel').addEventListener('touchstart', e => {
            accountTouchStartX = e.changedTouches[0].screenX;
        });

        document.getElementById('accountCarousel').addEventListener('touchend', e => {
            accountTouchEndX = e.changedTouches[0].screenX;
            handleAccountSwipe();
        });

        function handleAccountSwipe() {
            const swipeThreshold = 50;
            if (accountTouchEndX < accountTouchStartX - swipeThreshold) {
                nextAccountSlide();
            }
            if (accountTouchEndX > accountTouchStartX + swipeThreshold) {
                prevAccountSlide();
            }
        }

        // Budget Edit/Delete Functions
        function openEditBudgetOverlay(budget) {
            const overlay = document.getElementById('editBudgetOverlay');
            
            if (overlay) {
                // Fill in the form with budget data
                document.getElementById('edit_budget_id').value = budget.id;
                document.getElementById('edit_budget_name').value = budget.name;
                document.getElementById('edit_budget_category').value = budget.category_id;
                document.getElementById('edit_budget_amount').value = budget.amount;
                document.getElementById('edit_budget_start_date').value = budget.start_date;
                document.getElementById('edit_budget_end_date').value = budget.end_date;
                
                // Show overlay
                overlay.classList.remove('hidden');
                // Prevent main content scrolling
                document.body.style.overflow = 'hidden';
            }
        }

        function closeEditBudgetOverlay() {
            const overlay = document.getElementById('editBudgetOverlay');
            
            if (overlay) {
                overlay.classList.add('hidden');
                // Reset form
                const form = overlay.querySelector('form');
                if (form) {
                    form.reset();
                }
                // Restore main content scrolling
                document.body.style.overflow = '';
            }
        }

        function openDeleteBudgetModal(budgetId) {
            const modal = document.getElementById('deleteBudgetModal');
            if (modal) {
                document.getElementById('delete_budget_id').value = budgetId;
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeDeleteBudgetModal() {
            const modal = document.getElementById('deleteBudgetModal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        function confirmDeleteBudget() {
            const budgetId = document.getElementById('delete_budget_id').value;
            
            fetch('actions/delete_budget.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `id=${budgetId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close the modal and reload the page
                    closeDeleteBudgetModal();
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to delete budget');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the budget');
            });
        }

        // Add form submission handling for edit budget
        document.addEventListener('DOMContentLoaded', function() {
            const editBudgetForm = document.getElementById('editBudgetForm');
            if (editBudgetForm) {
                editBudgetForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    
                    fetch('actions/edit_budget.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Close the overlay and reload the page
                            closeEditBudgetOverlay();
                            window.location.reload();
                        } else {
                            alert(data.message || 'Failed to update budget');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating the budget');
                    });
                });
            }
        });

        // Add this to your existing JavaScript
        document.getElementById('category').addEventListener('change', function() {
            const categoryId = this.value;
            const budgetStatus = document.getElementById('budgetStatus');
            
            if (!categoryId) {
                budgetStatus.classList.add('hidden');
                return;
            }
            
            // Fetch budget status for this category
            fetch(`actions/get_budget_status.php?category_id=${categoryId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.has_budget) {
                        let statusClass = 'text-gray-600 dark:text-gray-400';
                        let statusIcon = 'info-circle';
                        
                        if (data.status === 'exceeded') {
                            statusClass = 'text-red-600 dark:text-red-400';
                            statusIcon = 'exclamation-circle';
                        } else if (data.status === 'warning') {
                            statusClass = 'text-yellow-600 dark:text-yellow-400';
                            statusIcon = 'exclamation-triangle';
                        }
                        
                        budgetStatus.innerHTML = `
                            <div class="${statusClass}">
                                <i class="fas fa-${statusIcon} mr-1"></i>
                                Budget: ${data.currency_symbol}${data.spent.toFixed(2)} / ${data.currency_symbol}${data.total.toFixed(2)}
                                (${data.percentage}% used)
                            </div>
                        `;
                        budgetStatus.classList.remove('hidden');
                    } else {
                        budgetStatus.classList.add('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error fetching budget status:', error);
                    budgetStatus.classList.add('hidden');
                });
        });

        // Form submission handling for budgets
        document.addEventListener('DOMContentLoaded', function() {
            const addBudgetForm = document.getElementById('addBudgetForm');
            if (addBudgetForm) {
                addBudgetForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    
                    // Validate dates
                    const startDate = new Date(formData.get('start_date'));
                    const endDate = new Date(formData.get('end_date'));
                    
                    if (endDate < startDate) {
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-4';
                        errorDiv.textContent = 'End date cannot be earlier than start date';
                        // Remove any existing error message
                        const existingError = addBudgetForm.querySelector('.bg-red-100');
                        if (existingError) {
                            existingError.remove();
                        }
                        addBudgetForm.insertBefore(errorDiv, addBudgetForm.firstChild);
                        return;
                    }
                    
                    fetch('actions/add_budget.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Close the modal
                            closeAddBudgetModal();
                            // Reload the page to show the new budget
                            window.location.reload();
                        } else {
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-4';
                            errorDiv.textContent = data.message || 'Failed to add budget';
                            // Remove any existing error message
                            const existingError = addBudgetForm.querySelector('.bg-red-100');
                            if (existingError) {
                                existingError.remove();
                            }
                            addBudgetForm.insertBefore(errorDiv, addBudgetForm.firstChild);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-4';
                        errorDiv.textContent = 'An error occurred while adding the budget';
                        // Remove any existing error message
                        const existingError = addBudgetForm.querySelector('.bg-red-100');
                        if (existingError) {
                            existingError.remove();
                        }
                        addBudgetForm.insertBefore(errorDiv, addBudgetForm.firstChild);
                    });
                });
            }
        });

        function loadChatHistory() {
            fetch('spendora.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ get_history: true })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                const chatMessages = document.getElementById('chat-messages');
                chatMessages.innerHTML = '';
                
                data.history.forEach(msg => {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = `flex ${msg.is_user ? 'justify-end' : 'justify-start'}`;
                    messageDiv.innerHTML = `
                        <div class="${msg.is_user ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800'} rounded-lg px-4 py-2 max-w-[80%]">
                            ${msg.message}
                        </div>
                    `;
                    chatMessages.appendChild(messageDiv);
                });
                
                chatMessages.scrollTop = chatMessages.scrollHeight;
            })
            .catch(error => {
                console.error('Error loading chat history:', error);
                const chatMessages = document.getElementById('chat-messages');
                chatMessages.innerHTML = `
                    <div class="flex justify-center">
                        <div class="bg-red-100 text-red-800 rounded-lg px-4 py-2">
                            Failed to load chat history. Please try again.
                        </div>
                    </div>
                `;
            });
        }

        document.getElementById('chat-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const input = document.getElementById('chat-input');
            const message = input.value.trim();
            
            if (message) {
                // Add user message to chat
                const chatMessages = document.getElementById('chat-messages');
                const userMessageDiv = document.createElement('div');
                userMessageDiv.className = 'flex justify-end';
                userMessageDiv.innerHTML = `
                    <div class="bg-blue-600 text-white rounded-lg px-4 py-2 max-w-[80%]">
                        ${message}
                    </div>
                `;
                chatMessages.appendChild(userMessageDiv);
                
                // Send message to server
                fetch('spendora.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ message: message })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    // Add bot response to chat
                    const botMessageDiv = document.createElement('div');
                    botMessageDiv.className = 'flex justify-start';
                    botMessageDiv.innerHTML = `
                        <div class="bg-gray-100 text-gray-800 rounded-lg px-4 py-2 max-w-[80%]">
                            ${data.response}
                        </div>
                    `;
                    chatMessages.appendChild(botMessageDiv);
                    
                    // Scroll to bottom
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                })
                .catch(error => {
                    console.error('Error sending message:', error);
                    const botMessageDiv = document.createElement('div');
                    botMessageDiv.className = 'flex justify-start';
                    botMessageDiv.innerHTML = `
                        <div class="bg-red-100 text-red-800 rounded-lg px-4 py-2 max-w-[80%]">
                            Failed to send message. Please try again.
                        </div>
                    `;
                    chatMessages.appendChild(botMessageDiv);
                });
                
                input.value = '';
            }
        });

        // Add this function to handle logout
        function handleLogout() {
            // Clear chat history before logging out
            fetch('spendora.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    logout: true
                })
            })
            .then(response => response.json())
            .then(data => {
                // Redirect to logout page regardless of response
                window.location.href = 'logout.php';
            })
            .catch(error => {
                console.error('Error:', error);
                // Still redirect to logout page even if there's an error
                window.location.href = 'logout.php';
            });
        }

        // Update the logout link to use the new function
        document.addEventListener('DOMContentLoaded', function() {
            const logoutLink = document.querySelector('a[href="logout.php"]');
            if (logoutLink) {
                logoutLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    handleLogout();
                });
            }
        });
    </script>

    <?php include 'includes/spendora_chat.php'; ?>
</body>
</html>