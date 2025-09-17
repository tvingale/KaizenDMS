<?php
/**
 * Secure Application Configuration
 * Loads settings from environment variables
 */

// Load environment variables
function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        die('Environment file not found. Copy .env.example to .env and configure it.');
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue; // Skip comments
        
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
            }
        }
    }
}

// Load .env file
loadEnv(__DIR__ . '/.env');

// Set timezone to Indian Standard Time
date_default_timezone_set('Asia/Kolkata');

// App Information
define('APP_NAME', $_ENV['APP_NAME'] ?? 'Dms Management');
define('APP_URL', $_ENV['APP_URL'] ?? 'https://localhost');
define('APP_VERSION', '1.0.0');
define('MODULE_PREFIX', $_ENV['MODULE_PREFIX'] ?? 'dms_');

// KaizenAuth Settings (from environment - secure)
define('KAIZEN_AUTH_URL', $_ENV['KAIZEN_AUTH_URL'] ?? 'https://auth.kaizenapps.co.in');
define('KAIZEN_APP_ID', $_ENV['KAIZEN_APP_ID'] ?? 'dms_32e2c668');
define('KAIZEN_APP_SECRET', $_ENV['KAIZEN_APP_SECRET'] ?? '794b532d59d383dc04a6e19f8a54659a6ef599424bf99e6b830737868ab4eb49');

// Database Configuration (from environment - secure)
define('DB_HOST', $_ENV['DB_HOST'] ?? '162.214.80.31');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'kaizenap_flow_db');
define('DB_USER', $_ENV['DB_USER'] ?? 'kaizenap_flowdb_user');
define('DB_PASS', $_ENV['DB_PASS'] ?? 'Cric$2009');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');

// App Settings
define('DEFAULT_ROLE', 'user');
define('ENABLE_RBAC', filter_var($_ENV['ENABLE_RBAC'] ?? 'false', FILTER_VALIDATE_BOOLEAN));
define('DEBUG_MODE', filter_var($_ENV['DEBUG_MODE'] ?? 'false', FILTER_VALIDATE_BOOLEAN));

// Initial Setup Settings
define('ALLOW_INITIAL_SETUP', filter_var($_ENV['ALLOW_INITIAL_SETUP'] ?? 'false', FILTER_VALIDATE_BOOLEAN));

// Cron Job Security
define('CRON_SECRET_KEY', $_ENV['CRON_SECRET_KEY'] ?? hash('sha256', KAIZEN_APP_SECRET . 'cron'));

// Validate critical settings
if (empty(KAIZEN_APP_ID) || empty(KAIZEN_APP_SECRET)) {
    die('Missing KaizenAuth credentials. Please check your .env file.');
}

// Enable KaizenAuth API integration - enabled for testing
define('KAIZEN_AUTH_API_ENABLED', true);

if (empty(DB_NAME) || empty(DB_USER)) {
    die('Missing database configuration. Please check your .env file.');
}

// Security Settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');

// Start session
session_start();

// Error reporting (controlled by environment)
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}
?>