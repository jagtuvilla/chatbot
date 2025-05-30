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

// Get all categories (both default and user-specific)
try {
    $stmt = $conn->prepare("
        SELECT c.*, 
        (SELECT COUNT(*) FROM transactions t WHERE t.category_id = c.id AND t.user_id = ?) as transaction_count,
        CASE WHEN c.user_id IS NULL THEN 'Default' ELSE 'Custom' END as category_type
        FROM categories c 
        WHERE c.user_id = ? OR c.user_id IS NULL 
        ORDER BY c.type, c.name ASC
    ");
    $stmt->execute([$user_id, $user_id]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    $categories = [];
}

// Count transactions per category
$category_counts = [];
try {
    $stmt = $conn->prepare("
        SELECT category_id, COUNT(*) as count 
        FROM transactions 
        WHERE user_id = ?
        GROUP BY category_id
    ");
    $stmt->execute([$user_id]);
    $counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    foreach($categories as &$category) {
        $category['expense_count'] = isset($counts[$category['id']]) ? $counts[$category['id']] : 0;
    }
} catch(PDOException $e) {
    error_log("Error counting transactions: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spendex - Categories</title>
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
                        <a href="categories.php" class="flex items-center p-2 pl-5 bg-[#187C19] text-white rounded-full">
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
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white">CATEGORIES</h1>

                <div class="mb-4">
                    <input type="text" id="search" placeholder="Search by category name or type..." 
                        class="w-full p-2 border border-gray-300 rounded shadow-sm focus:outline-none focus:ring focus:border-blue-500">
                </div>
            </div>


            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Add Category Form -->
                <div class="md:col-span-1 bg-white dark:bg-dark-bg-secondary rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Add New Category</h2>
                    <?php if (isset($_GET['error'])): ?>
                    <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['add_success'])): ?>
                    <div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($_GET['add_success']); ?>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['delete_success'])): ?>
                    <div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($_GET['delete_success']); ?>
                    </div>
                    <?php endif; ?>
                    <form action="actions/add_category.php" method="POST">
                        <div class="mb-4">
                            <label for="category-name" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Category Name</label>
                            <input type="text" id="category-name" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="category-type" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Type</label>
                            <select id="category-type" name="type" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                <option value="expense">Expense</option>
                                <option value="income">Income</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label for="category-color" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Color</label>
                            <input type="color" id="category-color" name="color" value="#4F46E5" class="shadow appearance-none border rounded w-full h-10 py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                        
                        <button type="submit" class="bg-[#187C19] hover:bg-green-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full">
                            Add Category
                        </button>
                    </form>
                </div>

                <!-- Category List -->
                <div class="md:col-span-2 bg-white dark:bg-dark-bg-secondary rounded-lg shadow-md ">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Manage Categories</h2>
                    </div>
                    
                    <div class="h-[400px] overflow-auto rounded-lg shadow-md">
                        <table class="min-w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Color</th>
                                    <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                    <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                                    <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Transactions</th>
                                    <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700" id="category-list">
                                <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="5" class="py-4 px-6 text-center text-gray-500 dark:text-gray-400">No categories found. Add your first category!</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($categories as $category): ?>
                                    <tr class="bg-white dark:bg-dark-bg-secondary">
                                        <td class="py-4 px-6">
                                            <div class="w-6 h-6 rounded-full border border-gray-300 dark:border-gray-600" style="background-color: <?php echo htmlspecialchars($category['color'] ?? '#4F46E5'); ?>"></div>
                                        </td>
                                        <td class="py-4 px-6 text-sm text-gray-900 dark:text-gray-300"><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td class="py-4 px-6 text-sm">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $category['type'] === 'income' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'; ?>">
                                                <?php echo ucfirst($category['type']); ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-sm text-gray-500 dark:text-gray-400"><?php echo $category['expense_count']; ?></td>
                                        <td class="py-4 px-6 text-sm font-medium">
                                            <button type="button" onclick="openEditCategoryOverlay(<?php echo $category['id']; ?>)" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3">Edit</button>
                                            <button type="button" onclick="deleteCategory(<?php echo $category['id']; ?>)" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
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

    <!-- Category Edit Overlay -->
    <div id="editCategoryOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-[100]">
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-dark-bg-secondary rounded-lg shadow-xl w-full max-w-2xl">
                <div class="flex justify-between items-center p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Edit Category</h3>
                    <button type="button" onclick="closeEditCategoryOverlay()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div id="editCategoryError" class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded m-6 hidden"></div>

                <form id="editCategoryForm" onsubmit="return handleEditCategorySubmit(event)" class="p-6">
                    <input type="hidden" id="editCategoryId" name="id">
                    <div class="mb-4">
                        <label for="editCategoryName" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Category Name</label>
                        <input type="text" id="editCategoryName" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="editCategoryType" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Type</label>
                        <select id="editCategoryType" name="type" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                            <option value="expense">Expense</option>
                            <option value="income">Income</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="editCategoryColor" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Color</label>
                        <input type="color" id="editCategoryColor" name="color" class="shadow appearance-none border rounded w-full h-10 py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    
                    <div class="flex justify-end space-x-4 mt-6">
                        <button type="button" onclick="closeEditCategoryOverlay()" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Cancel
                        </button>
                        <button type="submit" class="bg-[#187C19] hover:bg-green-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Update Category
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

        // Category Edit Modal Functions
        function openEditCategoryOverlay(categoryId) {
            console.log('Opening edit overlay for category:', categoryId);
            const editOverlay = document.getElementById('editCategoryOverlay');
            if (!editOverlay) {
                console.error('Edit overlay element not found');
                return;
            }
            
            // Show overlay first
            editOverlay.classList.remove('hidden');
            
            // Then fetch category data
            fetch(`actions/edit_category.php?id=${encodeURIComponent(categoryId)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Received category data:', data);
                    if (data.error) {
                        showEditCategoryError(data.error);
                        return;
                    }
                    
                    // Populate form
                    document.getElementById('editCategoryId').value = data.id || '';
                    document.getElementById('editCategoryName').value = data.name || '';
                    document.getElementById('editCategoryType').value = data.type || '';
                    document.getElementById('editCategoryColor').value = data.color || '#4F46E5';
                })
                .catch(error => {
                    console.error('Error fetching category:', error);
                    showEditCategoryError('Failed to fetch category data. Please try again.');
                });
        }

        function closeEditCategoryOverlay() {
            const editOverlay = document.getElementById('editCategoryOverlay');
            if (editOverlay) {
                editOverlay.classList.add('hidden');
            }

            const errorDiv = document.getElementById('editCategoryError');
            if (errorDiv) {
                errorDiv.classList.add('hidden');
                errorDiv.textContent = '';
            }

            const form = document.getElementById('editCategoryForm');
            if (form) {
                form.reset();
            }
        }

        function showEditCategoryError(message) {
            const errorDiv = document.getElementById('editCategoryError');
            if (errorDiv) {
                errorDiv.textContent = message;
                errorDiv.classList.remove('hidden');
            }
        }

        function handleEditCategorySubmit(event) {
            event.preventDefault();
            console.log('Handling form submission');
            
            const form = event.target;
            const formData = new FormData(form);
            
            fetch('actions/edit_category.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Response from edit:', data);
                if (data.error) {
                    showEditCategoryError(data.error);
                } else {
                    closeEditCategoryOverlay();
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error updating category:', error);
                showEditCategoryError('Failed to update category. Please try again.');
            });
            
            return false;
        }
        
        // Search Function
        document.getElementById('search').addEventListener('keyup', function() {
            var keyword = this.value;
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    document.getElementById('category-list').innerHTML = this.responseText;
                }
            }
            xhr.open('GET', 'actions/search_categories.php?keyword=' + encodeURIComponent(keyword), true);
            xhr.send();
        });

        // Delete Category Function
        function deleteCategory(categoryId) {
            if (confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
                fetch(`actions/delete_category.php?id=${categoryId}`)
                    .then(response => {
                        if (response.redirected) {
                            window.location.href = response.url;
                        } else {
                            return response.text();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to delete category. Please try again.');
                    });
            }
        }
    </script>

<?php include 'includes/spendora_chat.php'; ?>
</body>
</html>