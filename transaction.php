<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    header('Location: index.php?error=not_logged_in');
    exit;
}

include_once 'dbconn.php';
include_once 'functions.php';

$user_id = $_SESSION['user_id'];

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

// Get error message from session if it exists
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['error_message']); // Clear the message after retrieving it

// Get transactions
try {
    $stmt = $conn->prepare("
        SELECT t.*, c.name as category_name, c.type as category_type, c.color as category_color,
        a.name as account_name
        FROM transactions t
        LEFT JOIN categories c ON t.category_id = c.id
        LEFT JOIN accounts a ON t.account_id = a.id
        WHERE t.user_id = ?
        ORDER BY t.date DESC
    ");
    $stmt->execute([$user_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching transactions: " . $e->getMessage());
    $transactions = [];
}

// Get categories for the logged in user
try {
    $stmt = $conn->prepare("
        SELECT * FROM categories 
        WHERE user_id = ? OR user_id IS NULL 
        ORDER BY type, name ASC
    ");
    $stmt->execute([$user_id]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    $categories = [];
}

// Get accounts for the form
try {
    $stmt = $conn->prepare("SELECT * FROM accounts WHERE user_id = ? ORDER BY name ASC");
    $stmt->execute([$user_id]);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching accounts: " . $e->getMessage());
    $accounts = [];
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Spendex</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                        <a href="dashboard.php" class="flex items-center p-2 pl-5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full">
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
                        <a href="transaction.php" class="flex items-center p-2 pl-5 bg-[#187C19] text-white rounded-full">
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

    <!-- Main Content -->
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

        <!-- Content -->
        <div class="container mx-auto px-4 py-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white">TRANSACTIONS</h1>
                <div class="mb-4">
                    <input type="text" id="search" placeholder="Search by date, category, description, or account..." 
                        class="w-full p-2 border border-gray-300 rounded shadow-sm focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <button onclick="openAddTransactionModal()" class="bg-[#187C19] hover:bg-green-600 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-plus mr-2"></i> Add Transaction
                </button>
            </div>

            <!-- Transactions Table -->
            <div class="bg-white dark:bg-dark-bg-secondary rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Account</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-dark-bg-secondary divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?php echo date('M d, Y', strtotime($transaction['date'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <?php echo htmlspecialchars($transaction['description']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold" style="background-color: <?php echo $transaction['category_color']; ?>20; color: <?php echo $transaction['category_color']; ?>">
                                    <?php echo htmlspecialchars($transaction['category_name']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?php echo htmlspecialchars($transaction['account_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?php echo $transaction['category_type'] === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                                <?php echo $transaction['category_type'] === 'income' ? '+' : '-'; ?><?php echo $currency_symbol . number_format($transaction['amount'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="openEditTransactionModal(<?php echo htmlspecialchars(json_encode($transaction)); ?>)" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-200 mr-3">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteTransaction(<?php echo $transaction['id']; ?>)" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-200">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Transaction Modal -->
    <div id="addTransactionModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white dark:bg-dark-bg-secondary rounded-lg p-8 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Add New Transaction</h2>
                <button onclick="closeAddTransactionModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <?php if ($error_message): ?>
            <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>
            <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-4">
                Transaction added successfully!
            </div>
            <?php endif; ?>
            <form id="addTransactionForm" action="actions/add_transaction.php" method="POST" class="transaction-form">
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

    <!-- Edit Transaction Modal -->
    <div id="editTransactionModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white dark:bg-dark-bg-secondary rounded-lg p-8 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Edit Transaction</h2>
                <button onclick="closeEditTransactionModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="editTransactionForm" action="actions/edit_transaction.php" method="POST">
                <input type="hidden" name="id" id="editId">
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
                        <label for="editAmount" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Amount (<?php echo $currency_symbol; ?>)</label>
                        <input type="number" step="0.01" min="0.01" id="editAmount" name="amount" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    
                    <div>
                        <label for="editCategory" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Category</label>
                        <select id="editCategory" name="category_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['id']); ?>" data-type="<?php echo htmlspecialchars($category['type']); ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="editDescription" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Description</label>
                    <input type="text" id="editDescription" name="description" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>

                <div class="mb-4">
                    <label for="editDate" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Date</label>
                    <input type="date" id="editDate" name="date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>

                <div class="mb-4">
                    <label for="editAccount" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Account</label>
                    <select id="editAccount" name="account_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                        <option value="">Select an account</option>
                        <?php foreach ($accounts as $account): ?>
                        <option value="<?php echo htmlspecialchars($account['id']); ?>">
                            <?php echo htmlspecialchars($account['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeEditTransactionModal()" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Cancel
                    </button>
                    <button type="submit" class="bg-[#187C19] hover:bg-green-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Update Transaction
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Warning Message Overlay -->
    <div id="warningOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-[100] flex items-center justify-center">
        <div class="bg-white dark:bg-dark-bg-secondary rounded-lg shadow-xl w-full max-w-md mx-4 p-6">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-red-500 text-3xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Insufficient Balance</h3>
                    <p class="mt-1 text-gray-700 dark:text-gray-300" id="warningMessage"></p>
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button onclick="closeWarningOverlay()" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-200">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        // Check if there's an error message and open modal
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($error_message): ?>
            openAddTransactionModal();
            <?php endif; ?>
        });

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
        });

        // Debug logging function
        function debugLog(message) {
            console.log('[Budget Debug]', message);
        }

        // Initialize the form when modal opens
        function openAddTransactionModal() {
            debugLog('Opening modal');
            const modal = document.getElementById('addTransactionModal');
            
            // Reset form
            document.getElementById('addTransactionForm').reset();
            
            // Show modal
            modal.classList.remove('hidden');
        }

        function closeAddTransactionModal() {
            document.getElementById('addTransactionModal').classList.add('hidden');
        }

        // Edit Transaction Modal Functions
        function openEditTransactionModal(transaction) {
            document.getElementById('editId').value = transaction.id;
            document.getElementById('editAmount').value = transaction.amount;
            document.getElementById('editDescription').value = transaction.description;
            document.getElementById('editDate').value = transaction.date;
            document.querySelector(`input[name="transaction_type"][value="${transaction.category_type}"]`).checked = true;
            document.getElementById('editCategory').value = transaction.category_id;
            document.getElementById('editTransactionModal').classList.remove('hidden');
        }

        function closeEditTransactionModal() {
            document.getElementById('editTransactionModal').classList.add('hidden');
        }

        // Category filtering based on transaction type
        function updateCategoryVisibility(form) {
            const selectedType = form.querySelector('input[name="transaction_type"]:checked')?.value;
            const categorySelect = form.querySelector('select[name="category_id"]');
            
            if (!selectedType || !categorySelect) return;
            
            // Reset category selection
            categorySelect.value = '';
            
            // Show/hide categories based on type
            Array.from(categorySelect.options).forEach(option => {
                if (option.value === '') {
                    // Always show the default "Select a category" option
                    option.style.display = '';
                    option.disabled = false;
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
        }

        // Add event listeners to all transaction type radio buttons
        document.querySelectorAll('input[name="transaction_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const form = this.closest('form');
                updateCategoryVisibility(form);
            });
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Apply to both add and edit forms
            document.querySelectorAll('form').forEach(form => {
                if (form.querySelector('input[name="transaction_type"]')) {
                    const checkedRadio = form.querySelector('input[name="transaction_type"]:checked');
                    if (checkedRadio) {
                        updateCategoryVisibility(form);
                    }
                }
            });
        });

        // Delete Transaction Function
        function deleteTransaction(id) {
            if (confirm('Are you sure you want to delete this transaction?')) {
                window.location.href = `actions/delete_transaction.php?id=${id}`;
            }
        }

        // Check URL parameters for modal
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('action') && urlParams.get('action') === 'add') {
                openAddTransactionModal();
            }
        }

        // Add Transaction Form Submission
        document.getElementById('addTransactionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('actions/add_transaction.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success && data.message && data.message.includes('Insufficient Balance')) {
                    showWarningMessage('You cannot make this expense. Your account balance is too low.');
                } else if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'An error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your request');
            });
        });

        // Edit Transaction Form Submission
        document.getElementById('editTransactionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('actions/edit_transaction.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success && data.message && data.message.includes('Insufficient Balance')) {
                    showWarningMessage('You cannot make this expense. Your account balance is too low.');
                } else if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'An error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your request');
            });
        });

        // Warning Message Functions
        function showWarningMessage(message) {
            const overlay = document.getElementById('warningOverlay');
            const messageElement = document.getElementById('warningMessage');
            
            if (overlay && messageElement) {
                messageElement.textContent = message;
                overlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeWarningOverlay() {
            const overlay = document.getElementById('warningOverlay');
            
            if (overlay) {
                overlay.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        // Search Function
        document.getElementById('search').addEventListener('keyup', function() {
            var keyword = this.value;
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    document.querySelector('table tbody').innerHTML = this.responseText;
                }
            }
            xhr.open('GET', 'actions/search_transactions.php?keyword=' + encodeURIComponent(keyword), true);
            xhr.send();
        });

        // Check for error message in URL and show warning if it's about insufficient balance
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const error = urlParams.get('error');
            
            if (error && error.includes('Insufficient Balance')) {
                showWarningMessage('You cannot make this expense. Your account balance is too low.');
                // Remove the error from URL without refreshing the page
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
    </script>
</body>
</html> 