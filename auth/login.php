<?php
session_start();
include './db.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_email'])) {
    header("Location: ../pages/index.php");
    exit();
}

$error = "";
$success = "";
$show_signup = false;

// 1. HANDLE SECURITY KEY VERIFICATION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_key_action'])) {
    $input_key = $_POST['security_key'];
    
    // Read key from security.txt
    if (file_exists('security.txt')) {
        $stored_key = trim(file_get_contents('security.txt'));
        if ($input_key === $stored_key) {
            $show_signup = true;
        } else {
            $error = "Invalid Admin Security Key!";
        }
    } else {
        $error = "Security configuration file missing.";
    }
}

// 2. HANDLE SIGNUP LOGIC
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup_action'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $branch_id = $conn->real_escape_string($_POST['brunch_id']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $checkEmail = $conn->query("SELECT email FROM login WHERE email='$email'");
    if ($checkEmail->num_rows > 0) {
        $error = "Email already registered!";
        $show_signup = true; // Keep signup form open on error
    } else {
        $sql = "INSERT INTO login (brunch_id, name, email, password, phone) 
                VALUES ('$branch_id', '$name', '$email', '$password', '$phone')";
        
        if ($conn->query($sql)) {
            $success = "Account created! Please login.";
            $show_signup = false;
        } else {
            $error = "Registration failed: " . $conn->error;
            $show_signup = true;
        }
    }
}

// 3. HANDLE LOGIN LOGIC
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_action'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM login WHERE email='$email'");
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['branch_id'] = $user['brunch_id'];
            header("Location: ../pages/index.php");
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "No account found with that email!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TravHub Admin Panel</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        :root {
            --primary: #4cbc80;
            --bg: #171f3a;
            --sidebar: #1e293b;
            --accent: #4cbc80;
            --card: #1e293b;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --success: #4cbc80;
            --danger: #ef4444;
            --gradient-start: #4cbc80;
            --gradient-end: #38b589;
        }
        
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            min-height: 100vh;
        }
        
        .glass-effect {
            background: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(76, 188, 128, 0.15);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 
                        0 1px 3px rgba(76, 188, 128, 0.1);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #4cbc80 0%, #38b589 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .gradient-border {
            position: relative;
        }
        
        .gradient-border::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(135deg, #4cbc80, #38b589);
            border-radius: inherit;
            z-index: -1;
            opacity: 0.3;
            transition: opacity 0.3s ease;
        }
        
        .gradient-border:hover::before {
            opacity: 0.5;
        }
        
        .input-glow:focus {
            box-shadow: 0 0 0 3px rgba(76, 188, 128, 0.25);
            border-color: #4cbc80;
        }
        
        .btn-gradient {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(76, 188, 128, 0.35);
        }
        
        .btn-gradient::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s;
        }
        
        .btn-gradient:hover::after {
            left: 100%;
        }
        
        .pulse-animation {
            animation: pulse 3s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { 
                box-shadow: 0 0 0 0 rgba(76, 188, 128, 0.4); 
            }
            70% { 
                box-shadow: 0 0 0 15px rgba(76, 188, 128, 0); 
            }
        }
        
        .floating-element {
            animation: float 8s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(2deg); }
        }
        
        .logo-circle {
            background: linear-gradient(135deg, rgba(76, 188, 128, 0.15), rgba(56, 181, 137, 0.1));
            box-shadow: inset 0 0 30px rgba(76, 188, 128, 0.1);
        }
        
        .badge-success {
            background: linear-gradient(135deg, rgba(76, 188, 128, 0.15), rgba(56, 181, 137, 0.1));
            border: 1px solid rgba(76, 188, 128, 0.3);
        }
        
        .badge-primary {
            background: rgba(76, 188, 128, 0.1);
            border: 1px solid rgba(76, 188, 128, 0.3);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4 bg-[#171f3a]">
    <!-- Background decorative elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-20 left-10 w-80 h-80 rounded-full bg-[#4cbc80]/5 blur-3xl floating-element"></div>
        <div class="absolute bottom-20 right-10 w-96 h-96 rounded-full bg-[#38b589]/5 blur-3xl floating-element" style="animation-delay: -4s;"></div>
        <div class="absolute top-1/2 left-1/3 w-64 h-64 rounded-full bg-[#4cbc80]/3 blur-3xl floating-element" style="animation-delay: -2s;"></div>
        
        <!-- Geometric patterns -->
        <div class="absolute top-10 right-20 w-32 h-32 border-2 border-[#4cbc80]/10 rounded-3xl rotate-45 floating-element" style="animation-delay: -1s;"></div>
        <div class="absolute bottom-32 left-20 w-24 h-24 border-2 border-[#38b589]/10 rounded-xl rotate-12 floating-element" style="animation-delay: -3s;"></div>
    </div>

    <div class="relative w-full max-w-md z-10">
        <!-- Login Container -->
        <div class="glass-effect rounded-3xl p-8 gradient-border">
            <!-- Logo -->
            <div class="flex flex-col items-center mb-10">
                <div class="relative mb-6">
                    <div class="w-24 h-24 rounded-full logo-circle flex items-center justify-center pulse-animation">
                        <div class="w-20 h-20 rounded-full bg-gradient-to-br from-[#4cbc80] to-[#38b589] flex items-center justify-center shadow-lg shadow-[#4cbc80]/40">
                            <img src="../assets/images/logo/round-logo.png" class="ri-dumbbell-fill w-20 h-20 rounded-full shadow-lg shadow-emerald-500/30 border border-emerald-400/30 bg-slate-900" alt="TravHub Logo">
                        </div>
                    </div>
                    <div class="absolute -top-2 -right-2 w-10 h-10 rounded-full badge-success flex items-center justify-center">
                        <i class="ri-shield-keyhole-fill text-sm text-[#4cbc80]"></i>
                    </div>
                    <div class="absolute -bottom-2 -left-2 w-8 h-8 rounded-full badge-primary flex items-center justify-center">
                        <i class="ri-check-fill text-xs text-[#4cbc80]"></i>
                    </div>
                </div>
                <h1 class="text-3xl font-bold gradient-text mb-1">TravHub</h1>
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-[#4cbc80]"></div>
                    <p class="text-slate-400 text-sm">Secure Admin Portal</p>
                    <div class="w-2 h-2 rounded-full bg-[#4cbc80]"></div>
                </div>
            </div>

            <?php if($error): ?>
                <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/30">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-red-500/20 flex items-center justify-center">
                            <i class="ri-alert-fill text-red-400"></i>
                        </div>
                        <p class="text-red-300 text-sm flex-1"><?php echo $error; ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="mb-6 p-4 rounded-xl bg-[#4cbc80]/10 border border-[#4cbc80]/30">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-[#4cbc80]/20 flex items-center justify-center">
                            <i class="ri-checkbox-circle-fill text-[#4cbc80]"></i>
                        </div>
                        <p class="text-[#a7f3d0] text-sm flex-1"><?php echo $success; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$show_signup): ?>
                <!-- Login Form -->
                <form id="login-form" action="login.php" method="POST" class="space-y-6">
                    <input type="hidden" name="login_action" value="1">
                    
                    <div class="space-y-3">
                        <label class="text-sm font-semibold text-slate-300 flex items-center gap-2">
                            <i class="ri-mail-line text-[#4cbc80]"></i>
                            Email Address
                        </label>
                        <div class="relative group">
                            <input type="email" name="email" required 
                                   class="w-full bg-[#1e293b]/70 border border-slate-700 rounded-xl py-4 px-4 text-white placeholder-slate-500 focus:outline-none focus:border-[#4cbc80] input-glow transition-all duration-300 pl-12 group-hover:border-slate-600">
                            <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-400 group-hover:text-[#4cbc80] transition-colors">
                                <i class="ri-user-3-line"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <label class="text-sm font-semibold text-slate-300 flex items-center gap-2">
                            <i class="ri-lock-2-line text-[#4cbc80]"></i>
                            Password
                        </label>
                        <div class="relative group">
                            <input type="password" name="password" required 
                                   class="w-full bg-[#1e293b]/70 border border-slate-700 rounded-xl py-4 px-4 text-white placeholder-slate-500 focus:outline-none focus:border-[#4cbc80] input-glow transition-all duration-300 pl-12 group-hover:border-slate-600">
                            <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-400 group-hover:text-[#4cbc80] transition-colors">
                                <i class="ri-key-2-line"></i>
                            </div>
                            <button type="button" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-[#4cbc80]">
                                <i class="ri-eye-line"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center gap-2 text-slate-400 cursor-pointer hover:text-slate-300 transition-colors">
                            <div class="relative">
                                <input type="checkbox" class="sr-only">
                                <div class="w-5 h-5 rounded border border-slate-600 bg-[#1e293b] flex items-center justify-center">
                                    <i class="ri-check-line text-[#4cbc80] hidden"></i>
                                </div>
                            </div>
                            <span>Remember me</span>
                        </label>
                        <a href="#" class="text-[#4cbc80] hover:text-[#38b589] transition-colors font-medium">
                            Forgot Password?
                        </a>
                    </div>
                    
                    <button type="submit" 
                            class="w-full btn-gradient text-white font-bold py-4 rounded-xl text-lg transition-all duration-300 mt-2">
                        <i class="ri-dashboard-3-line mr-2"></i>
                        LOGIN TO DASHBOARD
                    </button>
                    
                    <div class="text-center pt-6 border-t border-slate-800">
                        <p class="text-slate-400 text-sm">
                            Need admin access? 
                            <button type="button" onclick="promptKey()" 
                                    class="text-[#4cbc80] hover:text-[#38b589] font-semibold transition-colors ml-1">
                                Request Sign Up <i class="ri-arrow-right-up-line ml-1"></i>
                            </button>
                        </p>
                    </div>
                </form>

                <form id="key-verify-form" action="login.php" method="POST" class="hidden">
                    <input type="hidden" name="verify_key_action" value="1">
                    <input type="hidden" name="security_key" id="hidden_security_key">
                </form>

            <?php else: ?>
                <!-- Signup Form -->
                <form id="signup-form" action="login.php" method="POST" class="space-y-5">
                    <input type="hidden" name="signup_action" value="1">
                    
                    <div class="space-y-3">
                        <label class="text-sm font-semibold text-slate-300 flex items-center gap-2">
                            <i class="ri-user-line text-[#4cbc80]"></i>
                            Full Name
                        </label>
                        <div class="relative group">
                            <input type="text" name="name" required 
                                   class="w-full bg-[#1e293b]/70 border border-slate-700 rounded-xl py-3.5 px-4 text-white placeholder-slate-500 focus:outline-none focus:border-[#4cbc80] input-glow transition-all duration-300 pl-12 group-hover:border-slate-600">
                            <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-400 group-hover:text-[#4cbc80] transition-colors">
                                <i class="ri-id-card-line"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <label class="text-sm font-semibold text-slate-300 flex items-center gap-2">
                            <i class="ri-mail-line text-[#4cbc80]"></i>
                            Email Address
                        </label>
                        <div class="relative group">
                            <input type="email" name="email" required 
                                   class="w-full bg-[#1e293b]/70 border border-slate-700 rounded-xl py-3.5 px-4 text-white placeholder-slate-500 focus:outline-none focus:border-[#4cbc80] input-glow transition-all duration-300 pl-12 group-hover:border-slate-600">
                            <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-400 group-hover:text-[#4cbc80] transition-colors">
                                <i class="ri-at-line"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <label class="text-sm font-semibold text-slate-300 flex items-center gap-2">
                            <i class="ri-phone-line text-[#4cbc80]"></i>
                            Phone Number
                        </label>
                        <div class="relative group">
                            <input type="text" name="phone" required 
                                   class="w-full bg-[#1e293b]/70 border border-slate-700 rounded-xl py-3.5 px-4 text-white placeholder-slate-500 focus:outline-none focus:border-[#4cbc80] input-glow transition-all duration-300 pl-12 group-hover:border-slate-600">
                            <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-400 group-hover:text-[#4cbc80] transition-colors">
                                <i class="ri-smartphone-line"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <label class="text-sm font-semibold text-slate-300 flex items-center gap-2">
                            <i class="ri-building-line text-[#4cbc80]"></i>
                            Branch ID
                        </label>
                        <div class="relative group">
                            <input type="number" name="brunch_id" value="1" required 
                                   class="w-full bg-[#1e293b]/70 border border-slate-700 rounded-xl py-3.5 px-4 text-white placeholder-slate-500 focus:outline-none focus:border-[#4cbc80] input-glow transition-all duration-300 pl-12 group-hover:border-slate-600">
                            <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-400 group-hover:text-[#4cbc80] transition-colors">
                                <i class="ri-number-1"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <label class="text-sm font-semibold text-slate-300 flex items-center gap-2">
                            <i class="ri-lock-password-line text-[#4cbc80]"></i>
                            Create Password
                        </label>
                        <div class="relative group">
                            <input type="password" name="password" required 
                                   class="w-full bg-[#1e293b]/70 border border-slate-700 rounded-xl py-3.5 px-4 text-white placeholder-slate-500 focus:outline-none focus:border-[#4cbc80] input-glow transition-all duration-300 pl-12 group-hover:border-slate-600">
                            <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-400 group-hover:text-[#4cbc80] transition-colors">
                                <i class="ri-key-line"></i>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-[#4cbc80] to-[#38b589] hover:from-[#45b077] hover:to-[#32a47c] text-white font-bold py-4 rounded-xl text-lg transition-all duration-300 shadow-lg shadow-[#4cbc80]/25 hover:shadow-[#4cbc80]/40 mt-2">
                        <i class="ri-user-add-line mr-2"></i>
                        CREATE ADMIN ACCOUNT
                    </button>
                    
                    <div class="text-center pt-6 border-t border-slate-800">
                        <a href="login.php" 
                           class="inline-flex items-center gap-2 text-[#4cbc80] hover:text-[#38b589] font-semibold transition-colors text-sm">
                            <i class="ri-arrow-left-line"></i>
                            Back to Login
                        </a>
                    </div>
                </form>
            <?php endif; ?>
            
            <!-- Footer note -->
            <div class="mt-8 text-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-[#1e293b]/50 border border-slate-800">
                    <div class="w-2 h-2 rounded-full bg-[#4cbc80] animate-pulse"></div>
                    <p class="text-xs text-slate-500">
                        <i class="ri-shield-check-line mr-1"></i>
                        Secured by TravHub Encryption
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Version badge -->
        <div class="absolute -bottom-6 left-1/2 transform -translate-x-1/2">
            <div class="px-4 py-2 bg-[#1e293b] backdrop-blur-sm rounded-full border border-[#4cbc80]/20">
                <span class="text-xs text-slate-400">Version 2.1.4 • <span class="text-[#4cbc80]">TravHub Pro</span></span>
            </div>
        </div>
    </div>

    <script>
        function promptKey() {
            const key = prompt("🔐 Enter Admin Security Key:\n\nPlease contact your system administrator for the security key.");
            if (key && key.trim() !== '') {
                document.getElementById('hidden_security_key').value = key.trim();
                document.getElementById('key-verify-form').submit();
            }
        }
        
        // Toggle password visibility
        document.querySelectorAll('input[type="password"]').forEach((input, index) => {
            const eyeBtn = document.createElement('button');
            eyeBtn.type = 'button';
            eyeBtn.className = 'absolute right-4 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-[#4cbc80] transition-colors';
            eyeBtn.innerHTML = '<i class="ri-eye-line"></i>';
            
            eyeBtn.addEventListener('click', function() {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="ri-eye-line"></i>' : '<i class="ri-eye-off-line"></i>';
            });
            
            input.parentNode.appendChild(eyeBtn);
        });
        
        // Enhanced checkbox functionality
        document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const icon = this.parentNode.querySelector('i');
                if (this.checked) {
                    icon.classList.remove('hidden');
                } else {
                    icon.classList.add('hidden');
                }
            });
        });
        
        // Add input focus effects
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentNode.classList.add('scale-[1.02]');
            });
            
            input.addEventListener('blur', function() {
                this.parentNode.classList.remove('scale-[1.02]');
            });
        });
    </script>
</body>
</html>