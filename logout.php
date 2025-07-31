<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Log the user out
$result = logoutUser();

// Clear remember me cookie if it exists
if (isset($_COOKIE['remember_token'])) {
    // Delete token from database
    $token = $_COOKIE['remember_token'];
    $db = connectDB();
    $stmt = $db->prepare("DELETE FROM user_tokens WHERE token = :token");
    $stmt->bindValue(':token', $token, SQLITE3_TEXT);
    $stmt->execute();
    $db->close();
    
    // Remove cookie
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

// Set success message
$_SESSION['message'] = 'You have been successfully logged out.';
$_SESSION['message_type'] = 'success';

// Redirect to login page
header('Location: login.php');
exit;
?> 