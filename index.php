<?php
include_once 'dbconn.php';

session_start();


if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'missing_fields';
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Update last_login timestamp
                $updateStmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'invalid_credentials';
            }
        } catch(PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'db_error';
            // Add detailed error for debugging
            $error_details = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spendex - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #fff;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
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
<body class="relative min-h-screen flex items-center justify-center bg-white">
    <div class="bg-art"></div>
    <div class="w-full min-h-screen flex flex-col lg:flex-row items-center justify-center relative z-10">
        <!-- Left: Logo and App Name -->
        <div class="flex-1 flex flex-col items-center justify-center px-8 py-12">
            <img src="transparent logo.png" alt="Spendex Logo" class="w-100 h-100 select-none" draggable="false">
        </div>
        <!-- Right: Login Form -->
        <div class="flex-1 flex items-center justify-center w-full">
            <div class="w-full max-w-md bg-[#22A06B] rounded-[2.5rem] shadow-2xl px-10 py-12 flex flex-col items-center">
                <h2 class="text-2xl font-bold text-white mb-8 w-full text-center">Login to your Account</h2>
                <?php if ($error || isset($_GET['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 w-full">
                    <?php
                    $error_message = $error ?: $_GET['error'];
                    switch($error_message) {
                        case 'missing_fields':
                            echo "Please provide both email and password.";
                            break;
                        case 'empty_email':
                            echo "Email cannot be empty.";
                            break;
                        case 'invalid_credentials':
                            echo "Invalid email or password. Please try again.";
                            break;
                        case 'db_error':
                            echo "A system error occurred. Please try again later.";
                            if (isset($error_details)) {
                                echo "<br>Debug info: " . htmlspecialchars($error_details);
                            }
                            break;
                        case 'not_logged_in':
                            echo "Please log in to access the dashboard.";
                            break;
                        default:
                            echo "An error occurred. Please try again.";
                    }
                    ?>
                </div>
                <?php endif; ?>
                <?php if (isset($_GET['registered'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 w-full">
                    Registration successful! Please login with your credentials.
                </div>
                <?php endif; ?>
                <form method="POST" class="w-full">
                    <div class="mb-6">
                        <label for="email" class="block text-white text-base font-semibold mb-2">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" class="shadow appearance-none border border-white rounded-full w-full py-3 px-5 text-white text-lg focus:outline-none focus:ring-2 focus:ring-white bg-transparent placeholder:text-white/60" placeholder="Email" required>
                    </div>
                    <div class="mb-8">
                        <label for="password" class="block text-white text-base font-semibold mb-2">Password</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" class="shadow appearance-none border border-white rounded-full w-full py-3 px-5 text-white text-lg focus:outline-none focus:ring-2 focus:ring-white bg-transparent placeholder:text-white/60" placeholder="Password" required>
                            <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-5 flex items-center text-white/60 hover:text-white focus:outline-none">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-gradient-to-r from-[#2ED47A] to-[#1FA463] hover:from-[#1FA463] hover:to-[#2ED47A] text-white font-bold py-3 rounded-full text-lg shadow-lg transition-all duration-200 mb-4">Login</button>
                </form>
                <div class="w-full text-center mt-2">
                    <span class="text-white/80 text-base">Don't have an account yet? </span>
                    <a href="signup.php" class="font-bold text-white underline hover:text-[#2ED47A] transition">Sign Up</a>
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
            this.querySelector('i').classList.toggle('fa-eye-');
        });
    </script>
</body>
</html>