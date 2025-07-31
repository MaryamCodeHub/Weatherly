<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get preferences from POST data
$default_location = isset($_POST['default_location']) ? $_POST['default_location'] : null;
$temperature_unit = isset($_POST['temperature_unit']) ? $_POST['temperature_unit'] : null;
$theme = isset($_POST['theme']) ? $_POST['theme'] : null;

// Validate temperature unit
if ($temperature_unit !== null && !in_array($temperature_unit, ['C', 'F'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid temperature unit'
    ]);
    exit;
}

// Validate theme
if ($theme !== null && !in_array($theme, ['light', 'dark'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid theme'
    ]);
    exit;
}

// Update preferences
$result = updateUserPreferences($user_id, $default_location, $temperature_unit, $theme);

// Return result
echo json_encode($result);
exit;
?> 