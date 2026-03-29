<?php
// Debug AI Connection Script
require_once 'config.php';

echo "<h2>AI Connection Diagnostic</h2>";

// 1. Check PHP Version
echo "<li>PHP Version: " . PHP_VERSION . "</li>";

// 2. Check cURL
if (function_exists('curl_init')) {
    echo "<li style='color:green'>✅ cURL is ENABLED</li>";
} else {
    echo "<li style='color:red'>❌ cURL is DISABLED. Please enable it in your hosting PHP settings.</li>";
}

// 3. Check API Key
$key = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
if ($key) {
    echo "<li style='color:green'>✅ API Key found: " . substr($key, 0, 10) . "...</li>";
} else {
    echo "<li style='color:red'>❌ API Key NOT found in config.php</li>";
}

// 4. Test External Connection
if (function_exists('curl_init')) {
    echo "<h3>Testing Connection to Google...</h3>";
    $test_url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $key;
    
    $ch = curl_init($test_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($http_code == 200) {
        echo "<li style='color:green'>✅ Connection Successful (HTTP 200)</li>";
    } else {
        echo "<li style='color:red'>❌ Connection Failed (HTTP $http_code)</li>";
        echo "<p>Error: $error</p>";
        echo "<p>Response: " . htmlspecialchars($response) . "</p>";
    }
}

echo "<hr><p>Upload file ini ke hosting dan akses melalui browser untuk melihat hasilnya.</p>";
?>
