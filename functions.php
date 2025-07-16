<?php
// functions.php - Helper functions

/**
 * Redirects to a specified URL.
 * @param string $url The URL to redirect to.
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Checks if a user is logged in.
 * @return bool True if logged in, false otherwise.
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Sets a session flash message.
 * @param string $name The name of the flash message.
 * @param string $message The message content.
 * @param string $type The type of message (e.g., 'success', 'error', 'info').
 */
function set_flash_message($name, $message, $type = 'info') {
    $_SESSION['flash_' . $name] = ['message' => $message, 'type' => $type];
}

/**
 * Displays and clears a session flash message.
 * @param string $name The name of the flash message.
 * @return string HTML for the flash message or empty string if not set.
 */
function display_flash_message($name) {
    if (isset($_SESSION['flash_' . $name])) {
        $message = $_SESSION['flash_' . $name]['message'];
        $type = $_SESSION['flash_' . $name]['type'];
        unset($_SESSION['flash_' . $name]);
        return "<div class='alert alert-{$type}'>{$message}</div>";
    }
    return '';
}
?>