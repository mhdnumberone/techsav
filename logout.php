<?php
/**
 * Logout Page - TechSavvyGenLtd
 */

require_once 'config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php');
}

// Initialize User class
$userClass = new User();

// Perform logout
$result = $userClass->logout();

// Redirect to homepage with message
if ($result['success']) {
    $_SESSION['logout_message'] = __('logout_success', 'You have been successfully logged out.');
    redirect(SITE_URL);
} else {
    // If logout failed for some reason, still clear session and redirect
    session_destroy();
    session_start();
    redirect(SITE_URL);
}
?>