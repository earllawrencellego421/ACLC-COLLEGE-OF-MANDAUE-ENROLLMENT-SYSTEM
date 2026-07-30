<?php
session_start();
require_once 'config.php'; // Ensure this points to your DB connection

// Load PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// Initialize variables
$step = isset($_SESSION['login_step']) ? $_SESSION['login_step'] : 1;
$login_error = '';
$email_error_msg = '';
$pass_error_msg = '';
$entered_email = '';

// --- STEP 1: VALIDATE EMAIL & PASSWORD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $entered_email = trim($_POST['email']);
    $password = MD5($_POST['password']); // Note: Consider password_hash() for better security
    
    // Check if EMAIL exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$entered_email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Email exists, now check PASSWORD
        if ($user['password'] === $password) {
            // SUCCESS: Generate OTP
            $otp_code = rand(100000, 999999);
            
            // Store temp data for Step 2
            $_SESSION['temp_user'] = $user;
            $_SESSION['otp_code'] = $otp_code;
            $_SESSION['otp_time'] = time();
            
            $mail = new PHPMailer(true);

            try {
                // Server settings
                $mail->isSMTP();                                            
                $mail->Host       = 'smtp.gmail.com';                     
                $mail->SMTPAuth   = true;                                   
                $mail->Username   = 'earllawrencellego@gmail.com'; 
                $mail->Password   = 'qxlahdmtbqtiiott'; // Your App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;            
                $mail->Port       = 587;                                    

                // Recipients
                $mail->setFrom('earllawrencellego@gmail.com', 'ACLC Admin System');
                $mail->addAddress($entered_email); // ⚠️ ensure this is a REAL email in your DB

                // Content
                $mail->isHTML(true);                                  
                $mail->Subject = 'ACLC Admin Login OTP Code';
                $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; max-width: 400px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 10px;'>
                        <h2 style='color: #667eea; text-align: center;'>Your OTP Code</h2>
                        <p style='text-align: center; color: #555;'>Your One-Time Password is:</p>
                        <h1 style='color: #667eea; letter-spacing: 5px; font-size: 32px; text-align: center; margin: 20px 0;'>$otp_code</h1>
                        <p style='color: #666; font-size: 12px; text-align: center;'>This code will expire in 5 minutes.</p>
                        <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                        <p style='color: #999; font-size: 11px; text-align: center;'>If you didn't request this, please ignore this email.</p>
                    </div>
                ";

                $mail->send();
                
                $_SESSION['login_step'] = 2;
                // Redirect to self to prevent form resubmission issues
                header("Location: admin_login.php");
                exit;

            } catch (Exception $e) {
                $login_error = "Mailer Error: Could not send OTP. Check your internet connection.";
            }
        } else {
            $pass_error_msg = "Incorrect password";
        }
    } else {
        $email_error_msg = "Account not found";
    }
}

// --- STEP 2: VERIFY OTP ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    // Check if OTP expired (5 minutes)
    if (time() - $_SESSION['otp_time'] > 300) {
        $login_error = "OTP expired. Please try logging in again.";
        $_SESSION['login_step'] = 1;
        unset($_SESSION['temp_user']);
        unset($_SESSION['otp_code']);
    } elseif ($_POST['otp_input'] == $_SESSION['otp_code']) {
        // SUCCESS: Log the user in
        $_SESSION['user'] = $_SESSION['temp_user'];
        $_SESSION['role'] = 'admin'; // Set session role
        
        // Clear temp session data
        unset($_SESSION['temp_user']);
        unset($_SESSION['otp_code']);
        unset($_SESSION['otp_time']);
        unset($_SESSION['login_step']);
        
        header("Location: admindashboard.php"); 
        exit;
    } else {
        $login_error = "Incorrect OTP Code!";
    }
}

// --- CANCEL / LOGOUT ---
if (isset($_POST['cancel'])) {
    session_destroy();
    header("Location: admin_login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - ACLC</title>
    <link rel="icon" type="image/png" href="logo.png">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }

        :root {
            --primary: #667eea;
            --primary-dark: #764ba2;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --bg-light: #f9fafb;
            --bg-white: #ffffff;
            --text-dark: #1f2937;
            --text-gray: #6b7280;
            --border: #e5e7eb;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: var(--text-dark);
        }

        .login-container {
            width: 100%;
            max-width: 420px;
        }

        .login-box {
            background: var(--bg-white);
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 50px 40px;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo-section {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo-section img {
            height: 70px;
            margin-bottom: 15px;
            filter: drop-shadow(var(--shadow-sm));
        }

        .logo-section h1 {
            font-size: 24px;
            color: var(--text-dark);
            font-weight: 700;
            margin-bottom: 5px;
        }

        .logo-section p {
            color: var(--text-gray);
            font-size: 14px;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 30px;
        }

        .step {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--border);
            transition: all 0.3s ease;
        }

        .step.active {
            width: 28px;
            border-radius: 4px;
            background: var(--primary);
        }

        h2 {
            color: var(--text-dark);
            margin-bottom: 10px;
            font-size: 22px;
            font-weight: 700;
        }

        .subtitle {
            color: var(--text-gray);
            font-size: 14px;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            gap: 10px;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-error { background: #fee; color: var(--danger); border: 1px solid #fcc; }
        .alert-success { background: #efe; color: var(--success); border: 1px solid #cfc; }

        .form-group { margin-bottom: 20px; }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 14px;
            transition: color 0.3s;
        }

        .form-group label.error { color: var(--danger); }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            background: var(--bg-white);
            color: var(--text-dark);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group input.error {
            border-color: var(--danger);
            background: #fef;
        }

        .password-toggle { position: relative; }

        .password-toggle button {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-gray);
            font-size: 18px;
            transition: color 0.3s;
            padding: 5px;
        }

        .password-toggle button:hover { color: var(--primary); }

        .form-group input[type="password"],
        .form-group input[type="text"].otp-input {
            padding-right: 40px;
            letter-spacing: 2px;
            text-align: center;
            font-size: 18px;
            font-weight: 600;
            word-spacing: 10px;
        }

        .button-group {
            display: flex;
            gap: 12px;
        }

        .btn {
            flex: 1;
            padding: 12px 16px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-secondary {
            background: var(--bg-light);
            color: var(--text-dark);
            border: 2px solid var(--border);
        }

        .btn-secondary:hover { background: var(--border); }

        .btn-full { width: 100%; }

        .link-section {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }

        .link-section a {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s;
        }

        .link-section a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .info-box {
            background: var(--bg-light);
            border-left: 4px solid var(--primary);
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 13px;
            color: var(--text-gray);
            line-height: 1.5;
        }

        .otp-email {
            font-weight: 600;
            color: var(--text-dark);
        }

        .otp-timer {
            text-align: center;
            margin-top: 15px;
            font-size: 13px;
            color: var(--text-gray);
        }

        .otp-timer span {
            font-weight: 600;
            color: var(--danger);
        }

        @media (max-width: 480px) {
            .login-box { padding: 40px 25px; }
            .button-group { flex-direction: column; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="logo-section">
                <img src="logo.png" alt="ACLC Logo">
                <h1>ACLC Admin</h1>
                <p>Secure Access Portal</p>
            </div>

            <div class="step-indicator">
                <div class="step <?php echo $step == 1 ? 'active' : ''; ?>"></div>
                <div class="step <?php echo $step == 2 ? 'active' : ''; ?>"></div>
            </div>

            <?php if ($step == 1): ?>
                <h2>Welcome Back</h2>
                <p class="subtitle">Enter your credentials to access the admin dashboard</p>

                <?php if ($login_error): ?>
                    <div class="alert alert-error">
                        <span>⚠️</span>
                        <span><?php echo htmlspecialchars($login_error); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" id="loginForm" novalidate>
                    <div class="form-group">
                        <label for="email" <?php echo $email_error_msg ? 'class="error"' : ''; ?>>
                            <?php echo $email_error_msg ? '❌ ' . htmlspecialchars($email_error_msg) : '📧 Email Address'; ?>
                        </label>
                        <input 
                            type="email" 
                            id="email"
                            name="email" 
                            value="<?php echo htmlspecialchars($entered_email); ?>" 
                            placeholder="Enter your email"
                            <?php echo $email_error_msg ? 'class="error"' : ''; ?>
                            required
                            autocomplete="email">
                    </div>

                    <div class="form-group">
                        <label for="password" <?php echo $pass_error_msg ? 'class="error"' : ''; ?>>
                            <?php echo $pass_error_msg ? '❌ ' . htmlspecialchars($pass_error_msg) : '🔐 Password'; ?>
                        </label>
                        <div class="password-toggle">
                            <input 
                                type="password" 
                                id="password"
                                name="password" 
                                placeholder="••••••••"
                                <?php echo $pass_error_msg ? 'class="error"' : ''; ?>
                                required
                                autocomplete="current-password">
                            <button type="button" id="togglePassword" title="Show/Hide password">👁️</button>
                        </div>
                    </div>

                    <button type="submit" name="login" class="btn btn-primary btn-full">
                        <span>Continue</span>
                        <span>→</span>
                    </button>
                </form>

            <?php elseif ($step == 2): ?>
                <h2>🔐 Verify Identity</h2>
                <p class="subtitle">We sent a code to your email. Enter it below.</p>

                <?php if ($login_error): ?>
                    <div class="alert alert-error">
                        <span>⚠️</span>
                        <span><?php echo htmlspecialchars($login_error); ?></span>
                    </div>
                <?php endif; ?>

                <div class="info-box">
                    Verification code sent to: <span class="otp-email"><?php echo htmlspecialchars($_SESSION['temp_user']['email'] ?? 'your email'); ?></span>
                </div>

                <form method="POST" id="otpForm" novalidate>
                    <div class="form-group">
                        <label for="otp_input">Enter 6-digit Code</label>
                        <input 
                            type="text" 
                            id="otp_input"
                            name="otp_input" 
                            placeholder="000000"
                            class="otp-input"
                            maxlength="6"
                            pattern="[0-9]{6}"
                            inputmode="numeric"
                            required 
                            autofocus>
                    </div>

                    <div class="button-group">
                        <button type="submit" name="verify_otp" class="btn btn-primary" id="verifyBtn">
                            Verify & Login
                        </button>
                        <button type="submit" name="cancel" class="btn btn-secondary">
                            Go Back
                        </button>
                    </div>
                </form>

                <div class="otp-timer">
                    ⏱️ Code expires in: <span id="timer">5:00</span>
                </div>

            <?php endif; ?>
        </div>
    </div>

    <script>
        // Password Visibility Toggle
        const togglePassword = document.getElementById('togglePassword');
        if (togglePassword) {
            togglePassword.addEventListener('click', function(e) {
                e.preventDefault();
                const passwordInput = document.getElementById('password');
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                this.textContent = isPassword ? '🙈' : '👁️';
            });
        }

        // OTP Input Auto-formatting
        const otpInput = document.getElementById('otp_input');
        if (otpInput) {
            otpInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
            });
        }

        // OTP Timer
        function startTimer() {
            let timeLeft = 300; // 5 minutes in seconds
            const timerDisplay = document.getElementById('timer');
            
            const interval = setInterval(() => {
                timeLeft--;
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                if(timerDisplay) {
                    timerDisplay.textContent = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
                }

                if (timeLeft <= 0) {
                    clearInterval(interval);
                    // Optional: Auto submit or disable form
                }
            }, 1000);
        }

        if (document.getElementById('otpForm')) {
            startTimer();
        }
    </script>
</body>
</html>