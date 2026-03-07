<?php
session_start();
require_once '../config/db_connect.php';

$message = '';
$message_type = '';
$token_valid = false;
$token = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    $stmt = $conn->prepare("SELECT pr.id, pr.user_id, pr.expires_at, u.email, u.username 
                            FROM password_resets pr 
                            JOIN users u ON pr.user_id = u.id 
                            WHERE pr.token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $reset_data = $result->fetch_assoc();
        
        if (strtotime($reset_data['expires_at']) > time()) {
            $token_valid = true;
        } else {
            $message = "This reset link has expired. Please request a new one.";
            $message_type = "error";
        }
    } else {
        $message = "Invalid reset link.";
        $message_type = "error";
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $token = $_POST['token'];
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($new_password) || empty($confirm_password)) {
        $message = "Please fill in all fields";
        $message_type = "error";
    } elseif (strlen($new_password) < 6) {
        $message = "Password must be at least 6 characters long";
        $message_type = "error";
    } elseif ($new_password !== $confirm_password) {
        $message = "Passwords do not match";
        $message_type = "error";
    } else {
    
        $stmt = $conn->prepare("SELECT pr.id, pr.user_id, pr.expires_at 
                                FROM password_resets pr 
                                WHERE pr.token = ? AND pr.expires_at > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $reset_data = $result->fetch_assoc();
           
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
      
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $reset_data['user_id']);
            
            if ($update_stmt->execute()) {
        
                $delete_stmt = $conn->prepare("DELETE FROM password_resets WHERE id = ?");
                $delete_stmt->bind_param("i", $reset_data['id']);
                $delete_stmt->execute();
                $delete_stmt->close();
                
                $message = "Password reset successfully! You can now login with your new password.";
                $message_type = "success";
                $token_valid = false;
                
                header("refresh:3;url=login.php");
            } else {
                $message = "Failed to reset password. Please try again.";
                $message_type = "error";
            }
            $update_stmt->close();
        } else {
            $message = "Invalid or expired reset link.";
            $message_type = "error";
            $token_valid = false;
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - DreamTourSri Lanka</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .auth-section {
            min-height: 100vh;
            background: linear-gradient(135deg, #059669 0%, #0d9488 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .auth-container {
            max-width: 450px;
            width: 100%;
        }

        .auth-card {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-logo {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }

        .auth-card h2 {
            text-align: center;
            color: #1f2937;
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }

        .auth-subtitle {
            text-align: center;
            color: #6b7280;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #374151;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
            background: #f9fafb;
        }

        .form-group input:focus {
            outline: none;
            border-color: #059669;
            background: white;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }

        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.85rem;
        }

        .strength-bar {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-top: 0.3rem;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            transition: all 0.3s;
            width: 0;
        }

        .strength-weak {
            width: 33%;
            background: #ef4444;
        }

        .strength-medium {
            width: 66%;
            background: #f59e0b;
        }

        .strength-strong {
            width: 100%;
            background: #10b981;
        }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(5, 150, 105, 0.4);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: #6b7280;
            font-size: 0.95rem;
        }

        .auth-footer a {
            color: #059669;
            text-decoration: none;
            font-weight: bold;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        .back-home {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-home a {
            color: white;
            text-decoration: none;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: opacity 0.3s;
        }

        .back-home a:hover {
            opacity: 0.8;
        }

        @media screen and (max-width: 480px) {
            .auth-card {
                padding: 2rem 1.5rem;
            }

            .auth-card h2 {
                font-size: 1.6rem;
            }
        }
    </style>
</head>

<body>
    <section class="auth-section">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <div class="auth-logo">🔑</div>
                    <h2>Reset Password</h2>
                    <p class="auth-subtitle">Enter your new password below</p>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <strong><?php echo $message_type === 'success' ? '✓' : '⚠️'; ?></strong> <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($token_valid): ?>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" onsubmit="return validateForm()">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <div class="form-group">
                            <label for="password">New Password</label>
                            <input type="password" id="password" name="password" placeholder="Enter new password" required minlength="6" oninput="checkPasswordStrength()">
                            <div class="password-strength">
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strengthFill"></div>
                                </div>
                                <span id="strengthText"></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required minlength="6">
                        </div>

                        <button type="submit" name="submit" class="btn-submit">Reset Password</button>
                    </form>
                <?php elseif (empty($message)): ?>
                    <div class="alert alert-error">
                        <strong>⚠️</strong> Invalid or missing reset token.
                    </div>
                <?php endif; ?>

                <div class="auth-footer">
                    <a href="login.php">← Back to Login</a>
                </div>
            </div>

            <div class="back-home">
                <a href="../index.html">← Back to Home</a>
            </div>
        </div>
    </section>

    <script>
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            strengthFill.className = 'strength-fill';
            
            if (strength <= 2) {
                strengthFill.classList.add('strength-weak');
                strengthText.textContent = 'Weak';
                strengthText.style.color = '#ef4444';
            } else if (strength <= 3) {
                strengthFill.classList.add('strength-medium');
                strengthText.textContent = 'Medium';
                strengthText.style.color = '#f59e0b';
            } else {
                strengthFill.classList.add('strength-strong');
                strengthText.textContent = 'Strong';
                strengthText.style.color = '#10b981';
            }
        }

        function validateForm() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                alert('Passwords do not match!');
                return false;
            }
            if (password.length < 6) {
                alert('Password must be at least 6 characters long!');
                return false;
            }
            return true;
        }
    </script>
</body>

</html>
