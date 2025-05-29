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
$success_message = '';
$error_message = '';
$user = null; // Initialize user variable

// At the top of the file, after session_start():
if (isset($_GET['password_changed'])) {
    $success_message = "Password changed successfully!";
} elseif (isset($_GET['password_error'])) {
    $error_message = htmlspecialchars($_GET['password_error']);
}

// Fetch user data
try {
    // Get user data without the date_created column
    $stmt = $conn->prepare("SELECT id, name, email, currency, created_at, last_login FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // If user not found, redirect to login
        header('Location: index.php?error=user_not_found');
        exit;
    }
    
    // Format the dates
    $user['formatted_date'] = $user['created_at'] ? date('F j, Y', strtotime($user['created_at'])) : 'Active User';
    $user['last_login_formatted'] = $user['last_login'] ? date('F j, Y g:i A', strtotime($user['last_login'])) : 'Never';
    
} catch(PDOException $e) {
    // Show the specific error message for debugging
    $error_message = "Database Error: " . $e->getMessage();
    error_log("Error fetching user data: " . $e->getMessage());
    
    // Initialize user with session data as fallback
    $user = [
        'name' => $_SESSION['user_name'] ?? 'User',
        'email' => '',
        'formatted_date' => 'Active User'
    ];
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $currency = $_POST['currency'];
    
    if (empty($name) || empty($email)) {
        $error_message = "Name and email are required";
    } else {
        try {
            // Check if email is already taken by another user
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                $error_message = "Email is already taken";
            } else {
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, currency = ? WHERE id = ?");
                $stmt->execute([$name, $email, $currency, $user_id]);
                
                $_SESSION['user_name'] = $name;
                $success_message = "Profile updated successfully";
                
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            }
        } catch(PDOException $e) {
            error_log("Error updating profile: " . $e->getMessage());
            $error_message = "Failed to update profile";
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All password fields are required";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match";
    } elseif (strlen($new_password) < 6) {
        $error_message = "New password must be at least 6 characters long";
    } else {
        try {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $current_hash = $stmt->fetchColumn();
            
            if (!password_verify($current_password, $current_hash)) {
                $error_message = "Current password is incorrect";
            } else {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users  SET password = ? WHERE id = ?");
                $stmt->execute([$new_hash, $user_id]);
                
                $success_message = "Password changed successfully";
            }
        } catch(PDOException $e) {
            error_log("Error changing password: " . $e->getMessage());
            $error_message = "Failed to change password";
        }
    }
}

// Get user statistics
try {
    // Get total transactions
    $stmt = $conn->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_transactions = $stmt->fetchColumn();
    
    // Get unique categories used
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT category_id) FROM transactions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $categories_used = $stmt->fetchColumn();
    
    // Get last activity
    $stmt = $conn->prepare("SELECT MAX(date) FROM transactions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $last_activity = $stmt->fetchColumn();
} catch(PDOException $e) {
    error_log("Error fetching user statistics: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - Spendex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        // Configure Tailwind dark mode
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
<body class="bg-[#f2f5fa] dark:bg-dark-bg-primary min-h-screen transition-colors duration-200">
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
                        <a href="account.php" class="flex items-center p-2 pl-5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full">
                            <i class="fas fa-wallet mr-3"></i>
                            Accounts
                        </a>
                    </li>
                    <li>
                        <a href="profile.php" class="flex items-center p-2 pl-5 bg-[#187C19] text-white rounded-full">
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
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-1">
                    <div class="bg-white dark:bg-dark-bg-secondary rounded-lg shadow-md p-6 mb-6">
                        <div class="flex flex-col items-center">
                            <div class="w-24 h-24 rounded-full bg-[#187C19] flex items-center justify-center text-white text-3xl font-bold mb-4">
                                <?php echo strtoupper(substr($user['name'] ?? '', 0, 2)); ?>
                            </div>
                            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-300"><?php echo htmlspecialchars($user['name'] ?? ''); ?></h2>
                            <p class="text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                            <p class="text-gray-500 dark:text-gray-500 text-sm mt-2">
                                Member since: <?php echo $user['formatted_date']; ?>
                            </p>                        
                        </div>
                    </div>
                    
                    <!-- Statistics Part -->

                    <div class="bg-white dark:bg-dark-bg-secondary rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Account Statistics</h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Total Transactions</p>
                                <p class="text-lg font-bold text-gray-800 dark:text-gray-300"><?php echo $total_transactions; ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Categories Used</p>
                                <p class="text-lg font-bold text-gray-800 dark:text-gray-300"><?php echo $categories_used; ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Last Activity</p>
                                <p class="text-lg font-bold text-gray-800 dark:text-gray-300">
                                    <?php 
                                    if ($user['last_login']) {
                                        echo date('M d, Y g:i A', strtotime($user['last_login']));
                                    } else {
                                        echo 'No activity';
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="md:col-span-2">
                    <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($_SESSION['success_message']); 
                        unset($_SESSION['success_message']); ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                    <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                    <?php endif; ?>
                    

                    <!-- Change Profile Part -->

                    <div class="bg-white dark:bg-dark-bg-secondary rounded-lg shadow-md p-6 mb-6">
                        <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Profile Settings</h2>
                        
                        <form method="POST" action="actions/update_profile.php">
                            <div class="mb-4">
                                <label for="name" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Full Name</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="email" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                            </div>

                            <div class="mb-4">
                                <label for="currency" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Default Currency</label>
                                <select id="currency" name="currency" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                                    <option value="PHP" <?php echo ($user['currency'] ?? 'PHP') === 'PHP' ? 'selected' : ''; ?>>Philippine Peso (₱)</option>
                                    <option value="USD" <?php echo ($user['currency'] ?? 'PHP') === 'USD' ? 'selected' : ''; ?>>US Dollar ($)</option>
                                    <option value="EUR" <?php echo ($user['currency'] ?? 'PHP') === 'EUR' ? 'selected' : ''; ?>>Euro (€)</option>
                                    <option value="GBP" <?php echo ($user['currency'] ?? 'PHP') === 'GBP' ? 'selected' : ''; ?>>British Pound (£)</option>
                                    <option value="JPY" <?php echo ($user['currency'] ?? 'PHP') === 'JPY' ? 'selected' : ''; ?>>Japanese Yen (¥)</option>
                                    <option value="KRW" <?php echo ($user['currency'] ?? 'PHP') === 'KRW' ? 'selected' : ''; ?>>Korean Won (₩)</option>
                                    <option value="CAD" <?php echo ($user['currency'] ?? 'PHP') === 'CAD' ? 'selected' : ''; ?>>Canadian Dollar (C$)</option>
                                    <option value="AUD" <?php echo ($user['currency'] ?? 'PHP') === 'AUD' ? 'selected' : ''; ?>>Australian Dollar (A$)</option>
                                </select>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">This will be your default currency for new accounts</p>
                            </div>
                            
                            <button type="submit" name="update_profile" class="bg-[#187C19] hover:bg-green-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Update Profile
                            </button>
                        </form>
                    </div>
                    

                    <!-- Change Password Part -->
                    
                    <div class="bg-white dark:bg-dark-bg-secondary rounded-lg shadow-md p-6">
                        <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Change Password</h2>
                        
                        <div id="passwordMessage" class="hidden mb-4"></div>
                        
                        <form method="POST" action="actions/change_password.php" id="passwordForm">
                            <div class="mb-4">
                                <label for="current-password" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Current Password</label>
                                <input type="password" id="current-password" name="current_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="new-password" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">New Password</label>
                                <input type="password" id="new-password" name="new_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                            </div>
                            
                            <div class="mb-6">
                                <label for="confirm-password" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Confirm New Password</label>
                                <input type="password" id="confirm-password" name="confirm_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline" required>
                            </div>
                            
                            <button type="submit" name="change_password" class="bg-[#187C19] hover:bg-green-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Javascript Part-->

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

        // Add this to your existing JavaScript
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const messageDiv = document.getElementById('passwordMessage');
            const formData = new FormData(this);
            
            // Show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Changing Password...';
            submitButton.disabled = true;
            
            fetch('actions/change_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Reset button state
                submitButton.innerHTML = originalButtonText;
                submitButton.disabled = false;
                
                if (data.success) {
                    // Show success message
                    messageDiv.className = 'bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded flex items-center';
                    messageDiv.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Password changed successfully!';
                    messageDiv.classList.remove('hidden');
                    
                    // Clear the form
                    this.reset();
                    
                    // Remove the message after 5 seconds
                    setTimeout(() => {
                        messageDiv.classList.add('hidden');
                    }, 5000);
                } else {
                    // Show error message
                    messageDiv.className = 'bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded flex items-center';
                    messageDiv.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + (data.message || 'Failed to change password');
                    messageDiv.classList.remove('hidden');
                    
                    // Remove the message after 5 seconds
                    setTimeout(() => {
                        messageDiv.classList.add('hidden');
                    }, 5000);
                }
            })
            .catch(error => {
                // Reset button state
                submitButton.innerHTML = originalButtonText;
                submitButton.disabled = false;
                
                console.error('Error:', error);
                messageDiv.className = 'bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded flex items-center';
                messageDiv.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>An error occurred while changing password';
                messageDiv.classList.remove('hidden');
                
                // Remove the message after 5 seconds
                setTimeout(() => {
                    messageDiv.classList.add('hidden');
                }, 5000);
            });
        });
    </script>
</body>
</html>
