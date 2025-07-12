<?php
// logout.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Initialize session with secure settings
$sessionParams = [
    'lifetime' => 86400,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
];

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params($sessionParams);
    session_start();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle logout request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'Invalid security token';
        header('Location: http://localhost/Mid-Exam/ecommerce-system/auth/login.php');
        exit;
    }
}

// Clear session data
$_SESSION = [];

// Delete session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy session
session_destroy();

// Clear output buffers
while (ob_get_level() > 0) {
    ob_end_clean();
}

// Redirect to login page
$_SESSION['success_message'] = 'You have been logged out successfully';
header('Location: http://localhost/Mid-Exam/ecommerce-system/auth/login.php');
exit;