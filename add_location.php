<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/weather_api.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    $_SESSION['message'] = 'Please log in to add locations.';
    $_SESSION['message_type'] = 'warning';
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $location_name = $_POST['location_name'] ?? '';
    
    if (empty($location_name)) {
        $_SESSION['message'] = 'Location name is required.';
        $_SESSION['message_type'] = 'danger';
    } else {
        // Validate location with API
        $location_data = getCurrentWeather($location_name);
        
        if (isset($location_data['error']) && $location_data['error'] === true) {
            $_SESSION['message'] = 'Invalid location: ' . ($location_data['message'] ?? 'Location not found');
            $_SESSION['message_type'] = 'danger';
        } else {
            // Save location to database
            $db = connectDB();
            
            // Check if location already exists for this user
            $stmt = $db->prepare("SELECT id FROM saved_locations WHERE user_id = :user_id AND location_name = :location_name");
            $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
            $stmt->bindValue(':location_name', $location_data['location']['name'], SQLITE3_TEXT);
            $result = $stmt->execute();
            
            if ($result->fetchArray(SQLITE3_ASSOC)) {
                $_SESSION['message'] = 'This location is already saved.';
                $_SESSION['message_type'] = 'warning';
            } else {
                // Insert new location
                $stmt = $db->prepare("INSERT INTO saved_locations (user_id, location_name, latitude, longitude) VALUES (:user_id, :location_name, :latitude, :longitude)");
                $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
                $stmt->bindValue(':location_name', $location_data['location']['name'], SQLITE3_TEXT);
                $stmt->bindValue(':latitude', $location_data['location']['lat'], SQLITE3_FLOAT);
                $stmt->bindValue(':longitude', $location_data['location']['lon'], SQLITE3_FLOAT);
                
                if ($stmt->execute()) {
                    $_SESSION['message'] = 'Location added successfully.';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'Failed to add location.';
                    $_SESSION['message_type'] = 'danger';
                }
            }
            
            $db->close();
        }
    }
}

// Redirect back to settings page
header('Location: settings.php?tab=saved-locations');
exit;
?> 