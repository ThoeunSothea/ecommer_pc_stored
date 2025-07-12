<?php
// config.php

// ✅ DATABASE CONFIGURATION
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ecommerce_168_db');

// ✅ ENVIRONMENT: development | production
define('ENVIRONMENT', 'development');

// ✅ ERROR REPORTING (according to environment)
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ✅ TIMEZONE SETTING
date_default_timezone_set('Asia/Phnom_Penh');

// ✅ SESSION START
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ✅ BASE URL CONFIGURATION
define('BASE_URL', 'http://localhost/MID-EXAM/ecommerce-system');
define('ASSETS_URL', BASE_URL . '/assets');

// ✅ ACCESS CONTROL FLAG
if (!defined('ALLOW_ACCESS')) {
    define('ALLOW_ACCESS', true);
}
?>
