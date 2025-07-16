<?php
// logout.php
require_once 'config.php';
require_once 'functions.php';

session_destroy(); // Destroy all session data
set_flash_message('info', 'You have been logged out.');
redirect(BASE_URL . 'login.php');
?>