<?php
$page_title = 'Settings';
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/weather_api.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    $_SESSION['message'] = 'Please log in to access settings.';
    $_SESSION['message_type'] = 'warning';
    header('Location: login.php');
    exit;
}

// Get user details
$user_details = getUserDetails($_SESSION['user_id']);
$user = $user_details['user'];

// Process form submissions
$success_message = '';
$error_message = '';

// Process profile update
if (isset($_POST['update_profile'])) {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($username) || empty($email)) {
        $error_message = 'Username and email are required.';
    } else {
        $db = connectDB();
        
        // Check if username or email already exists for other users
        $stmt = $db->prepare("SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :user_id");
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        if ($result->fetchArray(SQLITE3_ASSOC)) {
            $error_message = 'Username or email already exists.';
        } else {
            // If password change is requested
            if (!empty($current_password)) {
                // Verify current password
                $stmt = $db->prepare("SELECT password FROM users WHERE id = :user_id");
                $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
                $result = $stmt->execute();
                $user_data = $result->fetchArray(SQLITE3_ASSOC);
                
                if (!password_verify($current_password, $user_data['password'])) {
                    $error_message = 'Current password is incorrect.';
                } elseif (empty($new_password) || empty($confirm_password)) {
                    $error_message = 'New password and confirmation are required.';
                } elseif ($new_password !== $confirm_password) {
                    $error_message = 'New passwords do not match.';
                } else {
                    // Update user with new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE users SET username = :username, email = :email, password = :password WHERE id = :user_id");
                    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
                    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
                    $stmt->bindValue(':password', $hashed_password, SQLITE3_TEXT);
                    $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
                    
                    if ($stmt->execute()) {
                        $success_message = 'Profile and password updated successfully.';
                        $_SESSION['username'] = $username;
                    } else {
                        $error_message = 'Failed to update profile.';
                    }
                }
            } else {
                // Update user without changing password
                $stmt = $db->prepare("UPDATE users SET username = :username, email = :email WHERE id = :user_id");
                $stmt->bindValue(':username', $username, SQLITE3_TEXT);
                $stmt->bindValue(':email', $email, SQLITE3_TEXT);
                $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
                
                if ($stmt->execute()) {
                    $success_message = 'Profile updated successfully.';
                    $_SESSION['username'] = $username;
                } else {
                    $error_message = 'Failed to update profile.';
                }
            }
        }
        
        $db->close();
    }
}

// Process preferences update
if (isset($_POST['update_preferences'])) {
    $default_location = $_POST['default_location'] ?? '';
    $temperature_unit = $_POST['temperature_unit'] ?? 'C';
    $theme = $_POST['theme'] ?? 'light';
    
    // Update preferences
    $result = updateUserPreferences($_SESSION['user_id'], $default_location, $temperature_unit, $theme);
    
    if ($result['success']) {
        $success_message = 'Preferences updated successfully.';
        // Refresh user details
        $user_details = getUserDetails($_SESSION['user_id']);
        $user = $user_details['user'];
    } else {
        $error_message = 'Failed to update preferences.';
    }
}

// Include header
include 'includes/header.php';
?>

<div class="settings-page">
    <div class="page-header">
        <h1>Settings</h1>
    </div>
    
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
    
    <div class="settings-tabs">
        <div class="tab-buttons">
            <button class="tab-btn active" data-tab="preferences">Preferences</button>
            <button class="tab-btn" data-tab="profile">Profile</button>
            <button class="tab-btn" data-tab="saved-locations">Saved Locations</button>
        </div>
        
        <div class="tab-content">
            <!-- Preferences Tab -->
            <div class="tab-pane active" id="preferences-tab">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Weather Preferences</h2>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="form-group">
                                <label for="default_location">Default Location</label>
                                <input type="text" id="default_location" name="default_location" value="<?php echo htmlspecialchars($user['default_location'] ?? ''); ?>" placeholder="City name, zip/postal code or lat,lon">
                            </div>
                            
                            <div class="form-group">
                                <label>Temperature Unit</label>
                                <div class="form-check">
                                    <input type="radio" id="unit_c" name="temperature_unit" value="C" <?php echo ($user['temperature_unit'] ?? 'C') === 'C' ? 'checked' : ''; ?>>
                                    <label for="unit_c">Celsius (°C)</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" id="unit_f" name="temperature_unit" value="F" <?php echo ($user['temperature_unit'] ?? 'C') === 'F' ? 'checked' : ''; ?>>
                                    <label for="unit_f">Fahrenheit (°F)</label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Theme</label>
                                <div class="form-check">
                                    <input type="radio" id="theme_light" name="theme" value="light" <?php echo ($user['theme'] ?? 'light') === 'light' ? 'checked' : ''; ?>>
                                    <label for="theme_light">Light</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" id="theme_dark" name="theme" value="dark" <?php echo ($user['theme'] ?? 'light') === 'dark' ? 'checked' : ''; ?>>
                                    <label for="theme_dark">Dark</label>
                                </div>
                            </div>
                            
                            <button type="submit" name="update_preferences" class="btn btn-primary">Save Preferences</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Profile Tab -->
            <div class="tab-pane" id="profile-tab">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Account Information</h2>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <h3>Change Password</h3>
                            <p class="text-muted">Leave blank if you don't want to change your password.</p>
                            
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password">
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password">
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password">
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Account Details</h2>
                    </div>
                    <div class="card-body">
                        <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Saved Locations Tab -->
            <div class="tab-pane" id="saved-locations-tab">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Saved Locations</h2>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get saved locations from database
                        $db = connectDB();
                        $stmt = $db->prepare("SELECT * FROM saved_locations WHERE user_id = :user_id");
                        $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
                        $result = $stmt->execute();
                        
                        $saved_locations = [];
                        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                            $saved_locations[] = $row;
                        }
                        
                        $db->close();
                        
                        if (empty($saved_locations)):
                        ?>
                            <div class="no-locations">
                                <p>You haven't saved any locations yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="saved-locations-list">
                                <?php foreach ($saved_locations as $location): ?>
                                    <div class="saved-location-item">
                                        <div class="location-name">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo htmlspecialchars($location['location_name']); ?></span>
                                        </div>
                                        <div class="location-actions">
                                            <a href="index.php?location=<?php echo urlencode($location['location_name']); ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="settings.php?action=delete_location&id=<?php echo $location['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this location?');">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <h3>Add New Location</h3>
                        <form method="post" action="add_location.php" class="add-location-form">
                            <div class="form-group">
                                <label for="location_name">Location</label>
                                <input type="text" id="location_name" name="location_name" placeholder="City name, zip/postal code or lat,lon" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Location</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab functionality
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabPanes = document.querySelectorAll('.tab-pane');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                
                // Remove active class from all buttons and panes
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabPanes.forEach(pane => pane.classList.remove('active'));
                
                // Add active class to current button and pane
                this.classList.add('active');
                document.getElementById(tabId + '-tab').classList.add('active');
            });
        });
    });
</script>

<?php include 'includes/footer.php'; ?> 