<?php
// index.php — נקודת כניסה ראשית
require_once __DIR__ . '/config/auth.php';
if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
} else {
    header('Location: ' . APP_URL . '/auth/login.php');
}
exit;
