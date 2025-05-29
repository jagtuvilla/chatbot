<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    header('Location: index.php?error=not_logged_in');
    exit;
}

include_once 'dbconn.php';
include_once 'functions.php';

$user_id = $_SESSION['user_id'];
$accounts = getAccounts($conn, $user_id);

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

// Calculate total balance
$total_balance = 0;
foreach ($accounts as $account) {
    $total_balance += $account['balance'];
}
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spendex - Accounts</title>
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
                        <a href="transaction.php" class="flex items-center p-2 pl-5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full">
                            <i class="fas fa-exchange-alt mr-3"></i>
                            Transactions
                        </a>
                    </li>
                    <li>
                        <a href="account.php" class="flex items-center p-2 pl-5 bg-[#187C19] text-white rounded-full">
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
        <!-- Header -->
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
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white">ACCOUNTS</h1>
        </div>
            <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
            <?php endif; ?>
            <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-4">
                <?php 
                if ($_GET['success'] === 'deleted') {
                    echo 'Account deleted successfully!';
                } else {
                    echo 'Account added successfully!';
                }
                ?>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white dark:bg-dark-bg-secondary rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Total Balance</h2>
                    <p class="text-3xl font-bold <?php echo $total_balance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                        <?php echo $currency_symbol . number_format(abs($total_balance), 2); ?>
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Add Account Form -->
                <div class="md:col-span-1 bg-white dark:bg-dark-bg-secondary rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Add New Account</h2>
                    <form action="./actions/add_account.php" method="POST">
                        <div class="mb-4">
                            <label for="name" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Account Name</label>
                            <input type="text" id="name" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="type" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Account Type</label>
                            <select id="type" name="type" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
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
                            <label for="balance" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Current Balance</label>
                            <input type="number" step="0.01" id="balance" name="balance" value="0.00" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="currency" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Currency</label>
                            <select id="currency" name="currency" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                <option value="PHP" <?php echo $user_currency === 'PHP' ? 'selected' : ''; ?>>PHP (₱)</option>
                                <option value="USD" <?php echo $user_currency === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                <option value="EUR" <?php echo $user_currency === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                <option value="GBP" <?php echo $user_currency === 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                                <option value="JPY" <?php echo $user_currency === 'JPY' ? 'selected' : ''; ?>>JPY (¥)</option>
                                <option value="KRW" <?php echo $user_currency === 'KRW' ? 'selected' : ''; ?>>KRW (₩)</option>
                                <option value="CAD" <?php echo $user_currency === 'CAD' ? 'selected' : ''; ?>>CAD (C$)</option>
                                <option value="AUD" <?php echo $user_currency === 'AUD' ? 'selected' : ''; ?>>AUD (A$)</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label for="description" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Description (Optional)</label>
                            <textarea id="description" name="description" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                        </div>
                        
                        <button type="submit" class="bg-[#187C19] hover:bg-green-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full">
                            Add Account
                        </button>
                    </form>
                </div>
                
                <!-- Account List -->
                <div class="md:col-span-2 bg-white dark:bg-dark-bg-secondary rounded-lg shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Your Accounts</h2>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                    <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                                    <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Balance</th>
                                    <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <?php if (empty($accounts)): ?>
                                <tr>
                                    <td colspan="4" class="py-4 px-6 text-center text-gray-500 dark:text-gray-400">No accounts found. Add your first account!</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($accounts as $account): ?>
                                    <tr class="bg-white dark:bg-dark-bg-secondary">
                                        <td class="py-4 px-6 text-sm text-gray-900 dark:text-gray-300 font-medium">
                                            <?php echo htmlspecialchars($account['name']); ?>
                                        </td>
                                        <td class="py-4 px-6 text-sm text-gray-500 dark:text-gray-400">
                                            <?php echo ucfirst($account['type']); ?>
                                        </td>
                                        <td class="py-4 px-6 text-sm font-medium <?php echo $account['balance'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                                            <?php echo $account['currency'] . ' ' . number_format(abs($account['balance']), 2); ?>
                                        </td>
                                        <td class="py-4 px-6 text-sm font-medium">
                                            <button type="button" onclick="openEditAccountOverlay(<?php echo $account['id']; ?>)" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3">Edit</button>
                                            <a href="actions/delete_account.php?id=<?php echo $account['id']; ?>" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300" onclick="return confirm('Are you sure you want to delete this account? This action cannot be undone.')">Delete</a>
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
    </div>

    <!-- Account Edit Overlay -->
    <div id="editAccountOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-[100]">
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-dark-bg-secondary rounded-lg shadow-xl w-full max-w-2xl">
                <div class="flex justify-between items-center p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Edit Account</h3>
                    <button type="button" onclick="closeEditAccountOverlay()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div id="editAccountError" class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded m-6 hidden"></div>

                <form id="editAccountForm" onsubmit="return handleEditAccountSubmit(event)" class="p-6">
                    <input type="hidden" id="editAccountId" name="id">
                    <div class="mb-4">
                        <label for="editAccountName" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Account Name</label>
                        <input type="text" id="editAccountName" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="editAccountType" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Account Type</label>
                        <select id="editAccountType" name="type" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
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
                        <label for="editAccountBalance" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Current Balance</label>
                        <input type="number" step="0.01" id="editAccountBalance" name="balance" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="editAccountCurrency" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Currency</label>
                        <select id="editAccountCurrency" name="currency" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                            <option value="PHP">PHP (₱)</option>
                            <option value="USD">USD ($)</option>
                            <option value="EUR">EUR (€)</option>
                            <option value="GBP">GBP (£)</option>
                            <option value="JPY">JPY (¥)</option>
                            <option value="CAD">CAD ($)</option>
                            
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="editAccountDescription" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Description (Optional)</label>
                        <textarea id="editAccountDescription" name="description" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-4 mt-6">
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
    </div>

    <script>
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

        // Account Edit Overlay Functions
        function openEditAccountOverlay(accountId) {
            console.log('Opening edit overlay for account:', accountId);
            // Fetch account data
            fetch(`actions/get_account.php?id=${accountId}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Received account data:', data);
                    if (data.error) {
                        showEditAccountError(data.error);
                        return;
                    }
                    
                    // Populate form
                    document.getElementById('editAccountId').value = data.id;
                    document.getElementById('editAccountName').value = data.name;
                    document.getElementById('editAccountType').value = data.type;
                    document.getElementById('editAccountBalance').value = data.balance;
                    document.getElementById('editAccountCurrency').value = data.currency;
                    document.getElementById('editAccountDescription').value = data.description || '';
                    
                    // Show overlay
                    const editOverlay = document.getElementById('editAccountOverlay');
                    editOverlay.classList.remove('hidden');
                    console.log('Overlay should be visible now');
                })
                .catch(error => {
                    console.error('Error fetching account:', error);
                    showEditAccountError('Failed to fetch account data');
                });
        }

        function closeEditAccountOverlay() {
            console.log('Closing edit overlay');
            const editOverlay = document.getElementById('editAccountOverlay');
            editOverlay.classList.add('hidden');
            document.getElementById('editAccountError').classList.add('hidden');
            document.getElementById('editAccountForm').reset();
        }

        function showEditAccountError(message) {
            const errorDiv = document.getElementById('editAccountError');
            errorDiv.textContent = message;
            errorDiv.classList.remove('hidden');
        }

        function handleEditAccountSubmit(event) {
            event.preventDefault();
            console.log('Handling form submission');
            
            const formData = new FormData(event.target);
            
            fetch('actions/edit_account.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response from edit:', data);
                if (data.error) {
                    showEditAccountError(data.error);
                } else {
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error updating account:', error);
                showEditAccountError('Failed to update account');
            });
            
            return false;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('themeToggle');
            if (!themeToggle) return;

            // Set initial theme
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }

            themeToggle.addEventListener('click', function() {
                document.documentElement.classList.toggle('dark');
                localStorage.theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
            });
        });
    </script>
</body>
</html>
