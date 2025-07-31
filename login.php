<?php
$page_title = 'Login';
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $result = loginUser($username, $password);
        
        if ($result['success']) {
            // Set remember me cookie if requested
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                
                $db = connectDB();
                $stmt = $db->prepare("INSERT INTO user_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)");
                $expires_at = date('Y-m-d H:i:s', $expiry);
                $stmt->bindValue(':user_id', $result['user_id'], SQLITE3_INTEGER);
                $stmt->bindValue(':token', $token, SQLITE3_TEXT);
                $stmt->bindValue(':expires_at', $expires_at, SQLITE3_TEXT);
                $stmt->execute();
                $db->close();
                
                setcookie('remember_token', $token, $expiry, '/', '', false, true);
            }
            
            // Redirect to intended page or dashboard
            $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            
            header("Location: $redirect");
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="auth-page">
    <div class="auth-container">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Login to Your Account</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="form-group">
                        <label for="username">Username or Email</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Login</button>
                </form>
                
                <div class="auth-links">
                    <a href="forgot_password.php">Forgot Password?</a>
                    <span>Don't have an account? <a href="register.php">Register</a></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 