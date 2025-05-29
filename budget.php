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

// Get user's currency
$stmt = $conn->prepare("SELECT currency FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_currency = $stmt->fetchColumn() ?: 'PHP';

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

// Get all budgets for the logged in user
try {
    $stmt = $conn->prepare("
        SELECT b.*, c.name as category_name, c.type as category_type,
        (SELECT COALESCE(SUM(amount), 0) 
         FROM transactions t 
         WHERE t.category_id = b.category_id 
         AND t.user_id = :user_id
         AND t.date BETWEEN b.start_date AND b.end_date) as spent
        FROM budgets b
        LEFT JOIN categories c ON b.category_id = c.id 
        WHERE b.user_id = :user_id
        ORDER BY b.end_date DESC
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching budgets: " . $e->getMessage());
    $budgets = [];
}

// Get categories for the logged in user
try {
    $stmt = $conn->prepare("
        SELECT DISTINCT c.* 
        FROM categories c 
        WHERE (c.user_id = ? OR c.user_id IS NULL)
        AND c.type = 'expense'
        ORDER BY c.name ASC
    ");
    $stmt->execute([$user_id]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Categories found: " . print_r($categories, true));
} catch(PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    $categories = [];
}

// Debug: Display categories count
error_log("Final categories array: " . print_r($categories, true));

// Get success/error messages from session if they exist
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Clear messages from session
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spendex - Budgets</title>
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

<body class="bg-gray-100 dark:bg-dark-bg-primary min-h-screen transition-colors duration-200">
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
                        <a href="budget.php" class="flex items-center p-2 pl-5 bg-[#187C19] text-white rounded-full">
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

        <div class="container mx-auto px-4 py-8">
            <!-- Success/Error Messages -->
            <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <!-- Active Budgets -->
            <div class="mb-8">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">Active Budgets</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($budgets as $budget): 
                        if (strtotime($budget['end_date']) >= time()):
                            $spent = floatval($budget['spent']);
                            $budget_amount = floatval($budget['amount']);
                            $percentage = $budget_amount > 0 ? ($spent / $budget_amount) * 100 : 0;
                            $remaining = $budget_amount - $spent;
                            
                            // Determine progress bar color
                            $progress_color = 'bg-green-600';
                            if ($percentage >= 90) {
                                $progress_color = 'bg-red-600';
                            } else if ($percentage >= 75) {
                                $progress_color = 'bg-yellow-500';
                            }
                    ?>
                    <div class="bg-white dark:bg-dark-bg-secondary rounded-lg shadow-md p-4">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($budget['name']); ?></h3>
                            <span class="text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($budget['category_name'] ?? 'Select a category'); ?></span>
                        </div>
                        <div class="mb-2">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="dark:text-gray-300">Spent: <?php echo $currency_symbol . number_format($spent, 2); ?></span>
                                <span class="dark:text-gray-300">Budget: <?php echo $currency_symbol . number_format($budget_amount, 2); ?></span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                <div class="h-2.5 rounded-full <?php echo $progress_color; ?>" style="width: <?php echo min(100, $percentage); ?>%"></div>
                            </div>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="<?php echo $remaining >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                                <?php echo $remaining >= 0 ? 'Remaining: ' . $currency_symbol . number_format(abs($remaining), 2) : 'Over by: ' . $currency_symbol . number_format(abs($remaining), 2); ?>
                            </span>
                            <span class="dark:text-gray-400">
                                <?php 
                                echo date('M d', strtotime($budget['start_date'])) . ' - ' . 
                                     date('M d, Y', strtotime($budget['end_date'])); 
                                ?>
                            </span>
                        </div>
                    </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Add New Budget Form -->
                <div class="md:col-span-1 bg-white dark:bg-dark-bg-secondary rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Add New Budget</h2>
                    <?php if (isset($_GET['error'])): ?>
                    <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                    <?php endif; ?>
                    <form action="actions/add_budget.php" method="POST" class="space-y-4" id="addBudgetForm">
                        <div>
                            <label for="name" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Budget Name</label>
                            <input type="text" id="name" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                        
                        <div>
                            <label for="category_id" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Category</label>
                            <select id="category_id" name="category_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">Select a category</option>
                                <?php 
                                if (empty($categories)) {
                                    error_log("No categories found in the array");
                                } else {
                                    foreach ($categories as $category) {
                                        echo '<option value="' . htmlspecialchars($category['id']) . '">' . 
                                             htmlspecialchars($category['name']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="amount" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Budget Amount (<?php echo $currency_symbol; ?>)</label>
                            <input type="number" step="0.01" min="0.01" id="amount" name="amount" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                        
                        <div>
                            <label for="start_date" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Start Date</label>
                            <input type="date" id="start_date" name="start_date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                        
                        <div>
                            <label for="end_date" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">End Date</label>
                            <input type="date" id="end_date" name="end_date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                        
                        <div>
                            <div class="flex items-center">
                                <input type="checkbox" id="recurring" name="recurring" class="mr-2">
                                <label for="recurring" class="text-gray-700 dark:text-gray-300 text-sm font-bold">Recurring Budget</label>
                            </div>
                        </div>
                        
                        <div id="recurring_options" class="hidden">
                            <label for="recurring_type" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Recurring Type</label>
                            <select id="recurring_type" name="recurring_type" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="bg-[#187C19] hover:bg-green-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full transition duration-200">
                            Add Budget
                        </button>
                    </form>
                </div>
                <script>
                    // Recurring budget toggle
                    document.getElementById('recurring').addEventListener('change', function() {
                        const recurringOptions = document.getElementById('recurring_options');
                        recurringOptions.classList.toggle('hidden', !this.checked);
                        
                        // Make recurring_type required if recurring is checked
                        const recurringType = document.getElementById('recurring_type');
                        recurringType.required = this.checked;
                    });

                    // Form validation and submission
                    document.getElementById('addBudgetForm').addEventListener('submit', function(e) {
                        e.preventDefault(); // Prevent default form submission
                        
                        const startDate = new Date(document.getElementById('start_date').value);
                        const endDate = new Date(document.getElementById('end_date').value);
                        
                        if (endDate < startDate) {
                            alert('End date cannot be earlier than start date');
                            return;
                        }

                        // Validate recurring budget
                        const isRecurring = document.getElementById('recurring').checked;
                        if (isRecurring && !document.getElementById('recurring_type').value) {
                            alert('Please select a recurring type');
                            return;
                        }

                        // Create FormData object
                        const formData = new FormData(this);
                        
                        // Submit form via AJAX
                        fetch('actions/add_budget.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Redirect to budget.php on success
                                window.location.href = 'budget.php';
                            } else {
                                // Show error message
                                const errorDiv = document.createElement('div');
                                errorDiv.className = 'bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-4';
                                errorDiv.textContent = data.message || 'Failed to add budget';
                                
                                // Remove any existing error message
                                const existingError = this.querySelector('.bg-red-100');
                                if (existingError) {
                                    existingError.remove();
                                }
                                
                                // Insert error message at the top of the form
                                this.insertBefore(errorDiv, this.firstChild);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            // Show error message
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-4';
                            errorDiv.textContent = 'An error occurred while adding the budget';
                            
                            // Remove any existing error message
                            const existingError = this.querySelector('.bg-red-100');
                            if (existingError) {
                                existingError.remove();
                            }
                            
                            // Insert error message at the top of the form
                            this.insertBefore(errorDiv, this.firstChild);
                        });
                    });

                    // Set min date as today for both date inputs
                    const today = new Date().toISOString().split('T')[0];
                    document.getElementById('start_date').min = today;
                    document.getElementById('end_date').min = today;
                </script>
                
                <!-- Budget List -->
                <div class="md:col-span-2 bg-white dark:bg-dark-bg-secondary rounded-lg shadow-md overflow-hidden">
                    <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Your Budgets</h2>
                        <div class="mb-4">
                            <input type="text" id="search" placeholder="Search by budget name, category, or period..." 
                                class="w-full p-2 border border-gray-300 rounded shadow-sm focus:outline-none focus:ring focus:border-blue-500">
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Period</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700" id="budgetTableBody">
                                <?php foreach ($budgets as $budget): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-600 transition duration-150">
                                    <td class="py-4 px-6 text-sm text-gray-900 dark:text-gray-300 font-medium">
                                        <?php echo htmlspecialchars($budget['name']); ?>
                                    </td>
                                    <td class="py-4 px-6 text-sm font-medium text-gray-900 dark:text-gray-300">
                                        <?php echo $currency_symbol . number_format($budget['amount'], 2); ?>
                                    </td>
                                    <td class="py-4 px-6 text-sm text-gray-500 dark:text-gray-400">
                                        <?php echo htmlspecialchars($budget['category_name'] ?? 'Select a category'); ?>
                                    </td>
                                    <td class="py-4 px-6 text-sm text-gray-500 dark:text-gray-400">
                                        <?php 
                                        echo date('M d', strtotime($budget['start_date'])) . ' - ' . 
                                             date('M d, Y', strtotime($budget['end_date'])); 
                                        ?>
                                    </td>
                                    <td class="py-4 px-6 text-sm font-medium">
                                        <button onclick="handleEditBudget(<?php echo htmlspecialchars(json_encode($budget)); ?>)" 
                                                class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3 transition duration-150">
                                            Edit
                                        </button>
                                        <button onclick="handleDeleteBudget(<?php echo $budget['id']; ?>)" 
                                                class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition duration-150">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Budget Edit Modal -->
    <div id="editBudgetOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-[100] flex items-center justify-center">
        <div class="bg-white dark:bg-dark-bg-secondary rounded-lg shadow-xl w-full max-w-md mx-4">
            <div class="flex justify-between items-center p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Edit Budget</h3>
                <button type="button" onclick="closeEditBudgetOverlay()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div id="editBudgetError" class="hidden mx-6 mt-4 p-4 bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 rounded"></div>

            <form id="editBudgetForm" class="p-6 space-y-4">
                <input type="hidden" id="editBudgetId" name="id">
                
                <div>
                    <label for="editBudgetName" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Budget Name</label>
                    <input type="text" id="editBudgetName" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                
                <div>
                    <label for="editBudgetCategory" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Category</label>
                    <select id="editBudgetCategory" name="category_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="editBudgetAmount" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Budget Amount (<?php echo $currency_symbol; ?>)</label>
                    <input type="number" step="0.01" min="0.01" id="editBudgetAmount" name="amount" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="editBudgetStartDate" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Start Date</label>
                        <input type="date" id="editBudgetStartDate" name="start_date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    <div>
                        <label for="editBudgetEndDate" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">End Date</label>
                        <input type="date" id="editBudgetEndDate" name="end_date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                </div>
                
                <div>
                    <div class="flex items-center">
                        <input type="checkbox" id="editBudgetRecurring" name="recurring" class="mr-2">
                        <label for="editBudgetRecurring" class="text-gray-700 dark:text-gray-300 text-sm font-bold">Recurring Budget</label>
                    </div>
                </div>
                
                <div id="editRecurringOptions" class="hidden">
                    <label for="editBudgetRecurringType" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Recurring Type</label>
                    <select id="editBudgetRecurringType" name="recurring_type" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="yearly">Yearly</option>
                    </select>
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

    <script>
    // Add this at the beginning of your script
    const userCurrencySymbol = '<?php echo $currency_symbol; ?>';

    document.getElementById('search').addEventListener('input', function(e) {
        const keyword = e.target.value;
        fetch(`actions/search_budgets.php?keyword=${encodeURIComponent(keyword)}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('budgetTableBody').innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('budgetTableBody').innerHTML = '<tr><td colspan="5" class="py-4 px-6 text-center text-red-500">Error searching budgets</td></tr>';
            });
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

    // Budget Edit Functions
    function handleEditBudget(budget) {
        console.log('Edit budget:', budget); // Debug log
        const overlay = document.getElementById('editBudgetOverlay');
        
        if (overlay) {
            // Fill in the form with budget data
            document.getElementById('editBudgetId').value = budget.id;
            document.getElementById('editBudgetName').value = budget.name;
            document.getElementById('editBudgetCategory').value = budget.category_id || '';
            document.getElementById('editBudgetAmount').value = budget.amount;
            document.getElementById('editBudgetStartDate').value = budget.start_date;
            document.getElementById('editBudgetEndDate').value = budget.end_date;
            
            // Handle recurring options
            const recurringCheckbox = document.getElementById('editBudgetRecurring');
            const recurringOptions = document.getElementById('editRecurringOptions');
            
            if (budget.recurring) {
                recurringCheckbox.checked = true;
                recurringOptions.classList.remove('hidden');
                document.getElementById('editBudgetRecurringType').value = budget.recurring_type || 'monthly';
            } else {
                recurringCheckbox.checked = false;
                recurringOptions.classList.add('hidden');
            }
            
            // Show overlay
            overlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        } else {
            console.error('Edit budget overlay not found!'); // Debug log
        }
    }

    // Add event listener for recurring checkbox in edit form
    document.getElementById('editBudgetRecurring').addEventListener('change', function() {
        const recurringOptions = document.getElementById('editRecurringOptions');
        recurringOptions.classList.toggle('hidden', !this.checked);
        
        // Make recurring_type required if recurring is checked
        const recurringType = document.getElementById('editBudgetRecurringType');
        recurringType.required = this.checked;
    });

    function closeEditBudgetOverlay() {
        const overlay = document.getElementById('editBudgetOverlay');
        
        if (overlay) {
            overlay.classList.add('hidden');
            document.body.style.overflow = '';
            // Reset form
            document.getElementById('editBudgetForm').reset();
            document.getElementById('editBudgetError').classList.add('hidden');
        }
    }

    function handleEditBudgetSubmit(event) {
        event.preventDefault();
        console.log('Submitting edit form...'); // Debug log
        
        const form = event.target;
        const formData = new FormData(form);
        
        // Validate dates
        const startDate = new Date(formData.get('start_date'));
        const endDate = new Date(formData.get('end_date'));
        
        if (endDate < startDate) {
            const errorDiv = document.getElementById('editBudgetError');
            errorDiv.textContent = 'End date cannot be earlier than start date';
            errorDiv.classList.remove('hidden');
            return false;
        }

        // Add recurring data to formData if checked
        const isRecurring = document.getElementById('editBudgetRecurring').checked;
        formData.set('recurring', isRecurring ? '1' : '0');
        if (isRecurring) {
            formData.set('recurring_type', document.getElementById('editBudgetRecurringType').value);
        }
        
        fetch('actions/edit_budget.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Edit response:', data); // Debug log
            if (data.success) {
                window.location.reload();
            } else {
                const errorDiv = document.getElementById('editBudgetError');
                errorDiv.textContent = data.message || 'Failed to update budget';
                errorDiv.classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const errorDiv = document.getElementById('editBudgetError');
            errorDiv.textContent = 'An error occurred while updating the budget';
            errorDiv.classList.remove('hidden');
        });
        
        return false;
    }

    // Budget Delete Function
    function handleDeleteBudget(budgetId) {
        console.log('Delete budget:', budgetId); // Debug log
        
        if (confirm('Are you sure you want to delete this budget? This action cannot be undone.')) {
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
                console.log('Delete response:', data); // Debug log
                if (data.success) {
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
    }

    // Initialize the edit form when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        const editForm = document.getElementById('editBudgetForm');
        if (editForm) {
            editForm.addEventListener('submit', handleEditBudgetSubmit);
        } else {
            console.error('Edit budget form not found!'); // Debug log
        }
    });
    </script>
</body>
</html>
