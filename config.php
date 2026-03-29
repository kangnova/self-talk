<?php
$host = 'localhost';
$db   = 'self_talk_db';
$user = 'root';
$pass = ''; // Default XAMPP password is empty
$charset = 'utf8mb4';


// Databse connection will be initialized below

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Load dynamic settings from DB
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $db_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    define('GOOGLE_CLIENT_ID', $db_settings['google_client_id'] ?? '');
    define('GOOGLE_CLIENT_SECRET', $db_settings['google_client_secret'] ?? '');
    define('GOOGLE_REDIRECT_URL', $db_settings['google_redirect_url'] ?? 'http://localhost/self-talk/google-callback.php');

} catch (\PDOException $e) {
    // Fallback if table not found or connection error
    if (!defined('GOOGLE_CLIENT_ID')) {
        define('GOOGLE_CLIENT_ID', '');
        define('GOOGLE_CLIENT_SECRET', '');
        define('GOOGLE_REDIRECT_URL', 'http://localhost/self-talk/google-callback.php');
    }
}

// Gemini AI API Key
define('GEMINI_API_KEY', 'AIzaSyAvncbyoOG8qiS3iFjLx4oNi0X_F2IUox0');
?>
