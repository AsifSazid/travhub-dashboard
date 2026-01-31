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
            $_SESSION['user_id'] = $user['user_id'];
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
            --primary: #50bc81;
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
        
        /* Responsive adjustments */
        @media (max-width: 480px) {
            .auth-container {
                padding: 1.5rem !important;
                margin: 0.5rem !important;
            }
            .logo-circle {
                width: 5rem !important;
                height: 5rem !important;
            }
            .logo-circle div {
                width: 4rem !important;
                height: 4rem !important;
            }
            h1 {
                font-size: 1.75rem !important;
            }
            .btn-gradient, button[type="submit"] {
                padding: 0.875rem !important;
                font-size: 1rem !important;
            }
            input {
                padding: 0.75rem !important;
                padding-left: 3rem !important;
            }
        }
        
        /* Bubble animations */
        .bubble {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(76, 188, 128, 0.1), rgba(56, 181, 137, 0.05));
            pointer-events: none;
            z-index: 1;
        }
        
        .bubble-1 {
            width: 40px;
            height: 40px;
            top: 15%;
            left: 10%;
            animation: bubble-float 20s infinite ease-in-out;
        }
        
        .bubble-2 {
            width: 60px;
            height: 60px;
            top: 60%;
            right: 8%;
            animation: bubble-float 25s infinite ease-in-out reverse;
        }
        
        .bubble-3 {
            width: 30px;
            height: 30px;
            bottom: 20%;
            left: 15%;
            animation: bubble-float 18s infinite ease-in-out;
        }
        
        .bubble-4 {
            width: 50px;
            height: 50px;
            top: 25%;
            right: 15%;
            animation: bubble-float 22s infinite ease-in-out reverse;
        }
        
        .bubble-5 {
            width: 35px;
            height: 35px;
            bottom: 40%;
            left: 85%;
            animation: bubble-float 19s infinite ease-in-out;
        }
        
        @keyframes bubble-float {
            0%, 100% {
                transform: translateY(0) translateX(0) scale(1);
                opacity: 0.7;
            }
            25% {
                transform: translateY(-40px) translateX(20px) scale(1.1);
                opacity: 0.9;
            }
            50% {
                transform: translateY(-80px) translateX(-10px) scale(0.9);
                opacity: 0.6;
            }
            75% {
                transform: translateY(-40px) translateX(-20px) scale(1.05);
                opacity: 0.8;
            }
        }
        
        /* Mobile optimized */
        @media (max-width: 768px) {
            .bubble-1, .bubble-2, .bubble-3, .bubble-4, .bubble-5 {
                display: none;
            }
            
            .floating-element {
                animation-duration: 10s;
            }
            
            .glass-effect {
                margin: 1rem;
                padding: 1.5rem;
            }
        }
        
        @media (max-width: 640px) {
            body {
                padding: 0.5rem;
            }
            
            .glass-effect {
                padding: 1.25rem;
                border-radius: 1.5rem;
            }
            
            .flex-col {
                gap: 0.75rem !important;
            }
            
            .space-y-6 > * + * {
                margin-top: 1rem !important;
            }
            
            .space-y-3 > * + * {
                margin-top: 0.75rem !important;
            }
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-2 sm:p-4 bg-[#171f3a] overflow-x-hidden">
    <!-- Background decorative elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <!-- Large background elements -->
        <div class="absolute top-20 left-5 sm:left-10 w-40 h-40 sm:w-80 sm:h-80 rounded-full bg-[#4cbc80]/5 blur-3xl floating-element"></div>
        <div class="absolute bottom-20 right-5 sm:right-10 w-48 h-48 sm:w-96 sm:h-96 rounded-full bg-[#38b589]/5 blur-3xl floating-element" style="animation-delay: -4s;"></div>
        <div class="absolute top-1/2 left-1/4 sm:left-1/3 w-32 h-32 sm:w-64 sm:h-64 rounded-full bg-[#4cbc80]/3 blur-3xl floating-element" style="animation-delay: -2s;"></div>
        
        <!-- Geometric patterns -->
        <div class="hidden sm:block absolute top-10 right-20 w-32 h-32 border-2 border-[#4cbc80]/10 rounded-3xl rotate-45 floating-element" style="animation-delay: -1s;"></div>
        <div class="hidden sm:block absolute bottom-32 left-20 w-24 h-24 border-2 border-[#38b589]/10 rounded-xl rotate-12 floating-element" style="animation-delay: -3s;"></div>
        
        <!-- Floating Bubbles -->
        <div class="bubble bubble-1"></div>
        <div class="bubble bubble-2"></div>
        <div class="bubble bubble-3"></div>
        <div class="bubble bubble-4"></div>
        <div class="bubble bubble-5"></div>
    </div>

    <div class="relative w-full max-w-sm sm:max-w-md mx-auto px-2 sm:px-0 z-10">
        <!-- Login Container -->
        <div class="glass-effect rounded-2xl sm:rounded-3xl p-4 sm:p-6 md:p-8 gradient-border">
            <!-- Logo -->
            <div class="flex flex-col items-center mb-6 sm:mb-10">
                <div class="relative mb-4 sm:mb-6">
                    <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full logo-circle flex items-center justify-center pulse-animation mx-auto">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-gradient-to-br from-[#4cbc80] to-[#38b589] flex items-center justify-center shadow-lg shadow-[#4cbc80]/40">
                            <img src="../assets/images/logo/round-logo.png" 
                                 class="w-full h-full rounded-full object-cover border-2 border-emerald-400/30" 
                                 alt="TravHub Logo"
                                 onerror="this.onerror=null; this.style.display='none'; this.parentNode.innerHTML='<i class=\'ri-dumbbell-fill text-2xl text-white\'></i>';">
                        </div>
                    </div>
                    <div class="absolute -top-2 -right-2 w-8 h-8 sm:w-10 sm:h-10 rounded-full badge-success flex items-center justify-center">
                        <i class="ri-shield-keyhole-fill text-xs sm:text-sm text-[#4cbc80]"></i>
                    </div>
                    <div class="absolute -bottom-2 -left-2 w-6 h-6 sm:w-8 sm:h-8 rounded-full badge-primary flex items-center justify-center">
                        <i class="ri-check-fill text-xs text-[#4cbc80]"></i>
                    </div>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold gradient-text mb-1 text-center">TravHub</h1>
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-[#4cbc80]"></div>
                    <p class="text-slate-400 text-xs sm:text-sm">Secure Admin Portal</p>
                    <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-[#4cbc80]"></div>
                </div>
            </div>

            <?php if($error): ?>
                <div class="mb-4 sm:mb-6 p-3 sm:p-4 rounded-xl bg-red-500/10 border border-red-500/30">
                    <div class="flex items-center gap-2 sm:gap-3">
                        <div class="w-6 h-6 sm:w-8 sm:h-8 rounded-full bg-red-500/20 flex items-center justify-center flex-shrink-0">
                            <i class="ri-alert-fill text-red-400 text-sm sm:text-base"></i>
                        </div>
                        <p class="text-red-300 text-xs sm:text-sm flex-1"><?php echo $error; ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="mb-4 sm:mb-6 p-3 sm:p-4 rounded-xl bg-[#4cbc80]/10 border border-[#4cbc80]/30">
                    <div class="flex items-center gap-2 sm:gap-3">
                        <div class="w-6 h-6 sm:w-8 sm:h-8 rounded-full bg-[#4cbc80]/20 flex items-center justify-center flex-shrink-0">
                            <i class="ri-checkbox-circle-fill text-[#4cbc80] text-sm sm:text-base"></i>
                        </div>
                        <p class="text-[#a7f3d0] text-xs sm:text-sm flex-1"><?php echo $success; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$show_signup): ?>
                <!-- Login Form -->
                <form id="login-form" action="login.php" method="POST" class="space-y-4 sm:space-y-6">
                    <input type="hidden" name="login_action" value="1">
                    
                    <div class="space-y-2 sm:space-y-3">
                        <label class="text-xs sm:text-sm font-semibold text-slate-300 flex items-center gap-2">
                            <i class="ri-mail-line text-[#4cbc80] text-sm"></i>
                            Email Address
                        </label>
                        <div class="relative group">
                            <input type="email" name="email" required 
                                   class="w-full bg-[#1e293b]/70 border border-slate-700 rounded-lg sm:rounded-xl py-3 sm:py-4 px-3 sm:px-4 text-white placeholder-slate-500 focus:outline-none focus:border-[#4cbc80] input-glow transition-all duration-300 pl-10 sm:pl-12 text-sm sm:text-base group-hover:border-slate-600">
                            <div class="absolute left-3 sm:left-4 top-1/2 transform -translate-y-1/2 text-slate-400 group-hover:text-[#4cbc80] transition-colors">
                                <i class="ri-user-3-line text-sm"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-2 sm:space-y-3">
                        <label class="text-xs sm:text-sm font-semibold text-slate-300 flex items-center gap-2">
                            <i class="ri-lock-2-line text-[#4cbc80] text-sm"></i>
                            Password
                        </label>
                        <div class="relative group">
                            <input type="password" name="password" required 
                                   class="w-full bg-[#1e293b]/70 border border-slate-700 rounded-lg sm:rounded-xl py-3 sm:py-4 px-3 sm:px-4 text-white placeholder-slate-500 focus:outline-none focus:border-[#4cbc80] input-glow transition-all duration-300 pl-10 sm:pl-12 text-sm sm:text-base group-hover:border-slate-600">
                            <div class="absolute left-3 sm:left-4 top-1/2 transform -translate-y-1/2 text-slate-400 group-hover:text-[#4cbc80] transition-colors">
                                <i class="ri-key-2-line text-sm"></i>
                            </div>
                            <button type="button" class="absolute right-3 sm:right-4 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-[#4cbc80] transition-colors">
                                <i class="ri-eye-line text-sm"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between text-xs sm:text-sm">
                        <label class="flex items-center gap-2 text-slate-400 cursor-pointer hover:text-slate-300 transition-colors">
                            <div class="relative">
                                <input type="checkbox" class="sr-only">
                                <div class="w-4 h-4 sm:w-5 sm:h-5 rounded border border-slate-600 bg-[#1e293b] flex items-center justify-center">
                                    <i class="ri-check-line text-[#4cbc80] text-xs sm:text-sm hidden"></i>
                                </div>
                            </div>
                            <span class="whitespace-nowrap">Remember me</span>
                        </label>
                        <a href="#" class="text-[#4cbc80] hover:text-[#38b589] transition-colors font-medium whitespace-nowrap">
                            Forgot Password?
                        </a>
                    </div>
                    
                    <button type="submit" 
                            class="w-full btn-gradient text-white font-bold py-3 sm:py-4 rounded-lg sm:rounded-xl text-base sm:text-lg transition-all duration-300 mt-2">
                        <i class="ri-dashboard-3-line mr-2 text-sm sm:text-base"></i>
                        LOGIN TO DASHBOARD
                    </button>
                    
                    <div class="text-center pt-4 sm:pt-6 border-t border-slate-800">
                        <p class="text-slate-400 text-xs sm:text-sm">
                            Need admin access? 
                            <button type="button" onclick="promptKey()" 
                                    class="text-[#4cbc80] hover:text-[#38b589] font-semibold transition-colors ml-1 whitespace-nowrap">
                                Request Sign Up <i class="ri-arrow-right-up-line ml-1 text-xs"></i>
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
                <form id="signup-form" action="login.php" method="POST" class="space-y-3 sm:space-y-5">
                    <input type="hidden" name="signup_action" value="1">
                    
                    <div class="space-y-2 sm:space-y-3">
                        <label class="text-xs sm:text-sm font-semibold text-slate-300 flex items-center gap-2">
                            <i class="ri-user-line text-[#4cbc80] text-sm"></i>
                            Full Name
                        </label>
                        <div class="relative group">
                            <input type="text" name="name" required 
                                   class="w-full bg-[#1e293b]/70 border border-slate-700 rounded-lg sm:rounded-xl py-3 sm:py-3.5 px-3 sm:px-4 text-white placeholder-slate-500 focus:outline-none focus:border-[#4cbc80] input-glow transition-all duration-300 pl-10 sm:pl-12 text-sm sm:text-base group-hover:border-slate-600">
                            <div class="absolute left-3 sm:left-4 top-1/2 transform -translate-y-1/2 text-slate-400 group-hover:text-[#4cbc80] transition-colors">
                                <i class="ri-id-card-line text-sm"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-2 sm:space-y-3">
                        <label class="text-xs sm:text-sm font-semibold text-slate-300 flex items-center gap-2">
                            <i class="ri-mail-line text-[#4cbc80] text-sm"></i>
                            Email Address
                        </label>
                        <div class="relative group">
                            <input type="email" name="email" required 
                                   class="w-full bg-[#1e293b]/70 border border-slate-700 rounded-lg sm:rounded-xl py-3 sm:py-3.5 px-3 sm:px-4 text-white placeholder-slate-500 focus:outline-none focus:border-[#4cbc80] input-glow transition-all duration-300 pl-10 sm:pl-12 text-sm sm:text-base group-hover:border-slate-600">
                            <div class="absolute left-3 sm:left-4 top-1/2 transform -translate-y-1/2 text-slate-400 group-hover:text-[#4cbc80] transition-colors">
                                <i class="ri-at-line text-sm"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-2 sm:space-y-3">
                        <label class="text-xs sm:text-sm font-semibold text-slate-300 flex items-center gap-2">
                            <i class="ri-phone-line text-[#4cbc80] text-sm"></i>
                            Phone Number
                        </label>
                        <div class="relative group">
                            <input type="text" name="phone" required 
                                   class="w-full bg-[#1e293b]/70 border border-slate-700 rounded-lg sm:rounded-xl py-3 sm:py-3.5 px-3 sm:px-4 text-white placeholder-slate-500 focus:outline-none focus:border-[#4cbc80] input-glow transition-all duration-300 pl-10 sm:pl-12 text-sm sm:text-base group-hover:border-slate-600">
                            <div class="absolute left-3 sm:left-4 top-1/2 transform -translate-y-1/2 text-slate-400 group-hover:text-[#4cbc80] transition-colors">
                                <i class="ri-smartphone-line text-sm"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-2 sm:space-y-3">
                        <label class="text-xs sm:text-sm font-semibold text-slate-300 flex items-center gap-2">
                            <i class="ri-building-line text-[#4cbc80] text-sm"></i>
                            Branch ID
                        </label>
                        <div class="relative group">
                            <input type="number" name="brunch_id" value="1" required 
                                   class="w-full bg-[#1e293b]/70 border border-slate-700 rounded-lg sm:rounded-xl py-3 sm:py-3.5 px-3 sm:px-4 text-white placeholder-slate-500 focus:outline-none focus:border-[#4cbc80] input-glow transition-all duration-300 pl-10 sm:pl-12 text-sm sm:text-base group-hover:border-slate-600">
                            <div class="absolute left-3 sm:left-4 top-1/2 transform -translate-y-1/2 text-slate-400 group-hover:text-[#4cbc80] transition-colors">
                                <i class="ri-number-1 text-sm"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-2 sm:space-y-3">
                        <label class="text-xs sm:text-sm font-semibold text-slate-300 flex items-center gap-2">
                            <i class="ri-lock-password-line text-[#4cbc80] text-sm"></i>
                            Create Password
                        </label>
                        <div class="relative group">
                            <input type="password" name="password" required 
                                   class="w-full bg-[#1e293b]/70 border border-slate-700 rounded-lg sm:rounded-xl py-3 sm:py-3.5 px-3 sm:px-4 text-white placeholder-slate-500 focus:outline-none focus:border-[#4cbc80] input-glow transition-all duration-300 pl-10 sm:pl-12 text-sm sm:text-base group-hover:border-slate-600">
                            <div class="absolute left-3 sm:left-4 top-1/2 transform -translate-y-1/2 text-slate-400 group-hover:text-[#4cbc80] transition-colors">
                                <i class="ri-key-line text-sm"></i>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-[#4cbc80] to-[#38b589] hover:from-[#45b077] hover:to-[#32a47c] text-white font-bold py-3 sm:py-4 rounded-lg sm:rounded-xl text-base sm:text-lg transition-all duration-300 shadow-lg shadow-[#4cbc80]/25 hover:shadow-[#4cbc80]/40 mt-2">
                        <i class="ri-user-add-line mr-2 text-sm sm:text-base"></i>
                        CREATE ADMIN ACCOUNT
                    </button>
                    
                    <div class="text-center pt-4 sm:pt-6 border-t border-slate-800">
                        <a href="login.php" 
                           class="inline-flex items-center gap-1 sm:gap-2 text-[#4cbc80] hover:text-[#38b589] font-semibold transition-colors text-xs sm:text-sm">
                            <i class="ri-arrow-left-line text-xs sm:text-sm"></i>
                            Back to Login
                        </a>
                    </div>
                </form>
            <?php endif; ?>
            
            <!-- Footer note -->
            <div class="mt-6 sm:mt-8 text-center">
                <div class="inline-flex items-center gap-1 sm:gap-2 px-3 sm:px-4 py-1.5 sm:py-2 rounded-full bg-[#1e293b]/50 border border-slate-800">
                    <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-[#4cbc80] animate-pulse"></div>
                    <p class="text-xs text-slate-500">
                        <i class="ri-shield-check-line mr-1 text-xs"></i>
                        Secured by TravHub
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Version badge -->
        <div class="absolute -bottom-5 sm:-bottom-6 left-1/2 transform -translate-x-1/2">
            <div class="px-3 sm:px-4 py-1 sm:py-2 bg-[#1e293b] backdrop-blur-sm rounded-full border border-[#4cbc80]/20">
                <span class="text-xs text-slate-400">v2.1.4 • <span class="text-[#4cbc80]">TravHub Pro</span></span>
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
            eyeBtn.className = 'absolute right-3 sm:right-4 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-[#4cbc80] transition-colors';
            eyeBtn.innerHTML = '<i class="ri-eye-line text-sm"></i>';
            
            eyeBtn.addEventListener('click', function() {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="ri-eye-line text-sm"></i>' : '<i class="ri-eye-off-line text-sm"></i>';
            });
            
            // Only append if not already there
            if (!input.parentNode.querySelector('button[type="button"]')) {
                input.parentNode.appendChild(eyeBtn);
            }
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
            
            // Add click handler to the visual checkbox
            const visualCheckbox = checkbox.parentNode;
            visualCheckbox.addEventListener('click', function(e) {
                if (e.target !== checkbox) {
                    checkbox.checked = !checkbox.checked;
                    checkbox.dispatchEvent(new Event('change'));
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
        
        // Handle logo image error
        document.addEventListener('DOMContentLoaded', function() {
            const logoImg = document.querySelector('img[alt="TravHub Logo"]');
            if (logoImg) {
                logoImg.onerror = function() {
                    this.style.display = 'none';
                    const fallbackIcon = document.createElement('i');
                    fallbackIcon.className = 'ri-dumbbell-fill text-xl sm:text-2xl text-white';
                    this.parentNode.appendChild(fallbackIcon);
                };
            }
            
            // Mobile optimization
            const isMobile = window.innerWidth <= 768;
            if (isMobile) {
                // Adjust input font size for better mobile experience
                document.querySelectorAll('input').forEach(input => {
                    input.style.fontSize = '16px'; // Prevents zoom on iOS
                });
            }
        });
    </script>
</body>
</html>