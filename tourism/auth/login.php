<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: ../Backend/client_dashboard.php");
    exit();
}

require_once '../config/db_connect.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
 
    if (empty($email) || empty($password)) {
        $error_message = "Please fill in all fields";
    } else {
      
        $stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
             
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                if ($user['role'] == 'admin') {
                    header("Location: ../Backend/admin_dashboard.php");
                } else {
                    header("Location: ../Backend/client_dashboard.php");
                }
                exit();
            } else {
                $error_message = "Invalid email or password";
            }
        } else {
            $error_message = "Invalid email or password";
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
    <title>Login - DreamTourSri Lanka</title>
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

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .forgot-password {
            color: #059669;
            text-decoration: none;
            font-weight: 500;
        }

        .forgot-password:hover {
            text-decoration: underline;
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

        .divider {
            text-align: center;
            margin: 1.5rem 0;
            color: #9ca3af;
            font-size: 0.9rem;
            position: relative;
        }

        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: #e5e7eb;
        }

        .divider::before {
            left: 0;
        }

        .divider::after {
            right: 0;
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

        .demo-credentials {
            margin-top: 1.5rem;
            padding: 1rem;
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 10px;
            text-align: center;
            font-size: 0.85rem;
        }

        .demo-credentials h4 {
            color: #065f46;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .demo-credentials code {
            background: #dcfce7;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #166534;
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

            .form-options {
                flex-direction: column;
                gap: 0.8rem;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>
    <section class="auth-section">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <div class="auth-logo">🗺️</div>
                    <h2>Welcome Back</h2>
                    <p class="auth-subtitle">Login to access your account</p>
                </div>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-error">
                        <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <strong>✓ Success:</strong> <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>

                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember" id="remember">
                            <span>Remember me</span>
                        </label>
                        <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
                    </div>

                    <button type="submit" name="login" class="btn-submit">Login</button>
                </form>
                <div class="auth-footer">
                    Don't have an account? <a href="register.php">Sign Up</a>
                </div>
            </div>

            <div class="back-home">
                <a href="../index.html">← Back to Home</a>
            </div>
        </div>
    </section>
</body>

</html>