<?php
session_start();
require_once 'dbconn.php';


if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate input
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        try {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Email already exists";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users  (name, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$name, $email, $hashed_password]);
                
                // Get the new user's ID
                $user_id = $conn->lastInsertId();
                
                // Insert default categories for the new user
                $default_income_categories = [
                    ['Salary', 'income', '#10B981'],
                    ['Freelance', 'income', '#F59E0B'],
                    ['Investments', 'income', '#8B5CF6'],
                    ['Gifts', 'income', '#EC4899']
                ];
                
                $default_expense_categories = [
                    ['Food', 'expense', '#EF4444'],
                    ['Transportation', 'expense', '#84CC16'],
                    ['Housing', 'expense', '#F97316'],
                    ['Utilities', 'expense', '#6366F1'],
                    ['Entertainment', 'expense', '#4F46E5'],
                    ['Shopping', 'expense', '#10B981'],
                    ['Healthcare', 'expense', '#F59E0B'],
                    ['Education', 'expense', '#8B5CF6']
                ];
                
                // Prepare and execute category insertions
                $stmt = $conn->prepare("INSERT INTO categories (name, type, color, user_id) VALUES (?, ?, ?, ?)");
                
                // Insert income categories
                foreach ($default_income_categories as $category) {
                    $stmt->execute([$category[0], $category[1], $category[2], $user_id]);
                }
                
                // Insert expense categories
                foreach ($default_expense_categories as $category) {
                    $stmt->execute([$category[0], $category[1], $category[2], $user_id]);
                }
                
                // Redirect to login page with success message
                header('Location: index.php?registered=1');
                exit;
            }
        } catch(PDOException $e) {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spendex - Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        html, body {
            height: 100%;
            min-height: 100vh;
            overflow: hidden;
        }
        body {
            background: #fff;
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }
        .bg-art {
            position: absolute;
            top: 0; right: 0; left: 0; bottom: 0;
            width: 100vw;
            height: 100vh;
            z-index: 0;
            pointer-events: none;
            background: url('bgspendex.png') no-repeat right top;
            background-size: cover;
        }
    </style>
</head>
<body class="relative h-screen flex items-center justify-center bg-white overflow-hidden">
    <div class="bg-art"></div>
    <div class="w-full h-screen flex flex-col lg:flex-row items-center justify-center relative z-10">
        <!-- Left: Logo and App Name -->
        <div class="flex-1 flex flex-col items-center justify-center px-8 py-12">
            <img src="transparent logo.png" alt="Spendex Logo" class="w-100 h-100 mb-6 select-none" draggable="false">
        </div>
        <!-- Right: Signup Form -->
        <div class="flex-1 flex items-center justify-center w-full h-full">
            <div class="w-full max-w-sm bg-[#22A06B] rounded-[2rem] shadow-2xl px-8 py-5 flex flex-col items-center max-h-[90vh]">
                <h2 class="text-xl font-bold text-white mb-5 w-full text-center">Create New Account</h2>
                <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 w-full">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                <form action="signup.php" method="POST" class="w-full">
                    <div class="mb-4">
                        <label for="name" class="block text-white text-sm font-semibold mb-1">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" class="shadow appearance-none border border-white/40 rounded-full w-full py-2 px-4 text-white text-base focus:outline-none focus:ring-2 focus:ring-white bg-transparent placeholder:text-white/60" placeholder="Full Name" required>
                    </div>
                    <div class="mb-4">
                        <label for="email" class="block text-white text-sm font-semibold mb-1">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" class="shadow appearance-none border border-white/40 rounded-full w-full py-2 px-4 text-white text-base focus:outline-none focus:ring-2 focus:ring-white bg-transparent placeholder:text-white/60" placeholder="Email" required>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="block text-white text-sm font-semibold mb-1">Password</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" value="<?php echo isset($_POST['password']) ? htmlspecialchars($_POST['password']) : ''; ?>" class="shadow appearance-none border border-white/40 rounded-full w-full py-2 px-4 text-white text-base focus:outline-none focus:ring-2 focus:ring-white bg-transparent placeholder:text-white/60" placeholder="Password" required>
                            <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-4 flex items-center text-white/60 hover:text-white focus:outline-none">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-5">
                        <label for="confirm_password" class="block text-white text-sm font-semibold mb-1">Confirm Password</label>
                        <div class="relative">
                            <input type="password" id="confirm_password" name="confirm_password" class="shadow appearance-none border border-white/40 rounded-full w-full py-2 px-4 text-white text-base focus:outline-none focus:ring-2 focus:ring-white bg-transparent placeholder:text-white/60" placeholder="Confirm Password" required>
                            <button type="button" id="toggleConfirmPassword" class="absolute inset-y-0 right-0 pr-4 flex items-center text-white/60 hover:text-white focus:outline-none">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-gradient-to-r from-[#2ED47A] to-[#1FA463] hover:from-[#1FA463] hover:to-[#2ED47A] text-white font-bold py-2 rounded-full text-base shadow-lg transition-all duration-200 mb-3">Sign Up</button>
                </form>
                <div class="w-full text-center mt-1">
                    <span class="text-white/80 text-sm">Already have an account? </span>
                    <a href="index.php" class="font-bold text-white underline hover:text-[#2ED47A] transition text-sm">Log in</a>
                </div>
            </div>
        </div>
    </div>
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye-slash');
            this.querySelector('i').classList.toggle('fa-eye');
        });
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const confirmPassword = document.getElementById('confirm_password');
        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPassword.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye-slash');
            this.querySelector('i').classList.toggle('fa-eye');
        });
    </script>
</body>
</html>