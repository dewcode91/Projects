<?php
// index.php - Landing page
require_once 'config.php';
require_once 'functions.php';

// Redirect to dashboard if logged in, otherwise to login
if (is_logged_in()) {
    redirect(BASE_URL . 'dashboard.php');
} else {
    redirect(BASE_URL . 'login.php');
}
?>