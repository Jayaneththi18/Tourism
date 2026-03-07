<?php
session_start();
require_once '../config/db_connect.php';

$message = '';
$message_type = '';
$reset_link = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $message = "Please enter your email address";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
          
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $conn->query("CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_token (token),
                INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            $delete_stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $delete_stmt->bind_param("i", $user['id']);
            $delete_stmt->execute();
            $delete_stmt->close();
         
            $insert_stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("iss", $user['id'], $token, $expires);
            
            if ($insert_stmt->execute()) {
              
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
                
                $message = "Password reset instructions have been sent. (Development mode: link displayed below)";
                $message_type = "success";
                
            } else {
                $message = "Failed to generate reset link. Please try again.";
                $message_type = "error";
            }
            $insert_stmt->close();
        } else {
    
            $message = "If your email is registered, you will receive password reset instructions.";
            $message_type = "success";
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
    <title>Forgot Password - DreamTourSri Lanka</title>
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
            line-height: 1.5;
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

        .reset-link-box {
            margin-top: 1rem;
            padding: 1rem;
            background: #f0fdf4;
            border: 2px solid #86efac;
            border-radius: 10px;
        }

        .reset-link-box h4 {
            color: #065f46;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .reset-link-box a {
            color: #059669;
            word-break: break-all;
            font-size: 0.85rem;
        }

        .reset-link-box .copy-btn {
            margin-top: 0.5rem;
            padding: 0.5rem 1rem;
            background: #059669;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85rem;
        }

        .reset-link-box .copy-btn:hover {
            background: #047857;
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
                    <div class="auth-logo">🔐</div>
                    <h2>Forgot Password?</h2>
                    <p class="auth-subtitle">Enter your email address and we'll send you instructions to reset your password.</p>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <strong><?php echo $message_type === 'success' ? '✓' : '⚠️'; ?></strong> <?php echo htmlspecialchars($message); ?>
                    </div>

                    <?php if (!empty($reset_link) && $message_type === 'success'): ?>
                        <div class="reset-link-box">
                            <h4>📧 Development Mode - Reset Link:</h4>
                            <a href="<?php echo $reset_link; ?>" target="_blank"><?php echo $reset_link; ?></a>
                            <button class="copy-btn" onclick="copyResetLink()">Copy Link</button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your registered email" required
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <button type="submit" name="submit" class="btn-submit">Send Reset Link</button>
                </form>

                <div class="auth-footer">
                    Remember your password? <a href="login.php">Login</a>
                </div>
            </div>

            <div class="back-home">
                <a href="../index.html">← Back to Home</a>
            </div>
        </div>
    </section>

    <script>
        function copyResetLink() {
            const link = "<?php echo $reset_link; ?>";
            navigator.clipboard.writeText(link).then(function() {
                alert('Reset link copied to clipboard!');
            }, function() {
                alert('Failed to copy link. Please copy manually.');
            });
        }
    </script>
</body>

</html>
