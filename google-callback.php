<?php
require_once 'config.php';
session_start();

if (isset($_GET['code'])) {
    $code = $_GET['code'];
    
    // Exchange code for token
    $token_url = "https://oauth2.googleapis.com/token";
    $post_data = [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URL,
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if (isset($data['access_token'])) {
        $access_token = $data['access_token'];
        
        // Get user info
        $user_info_url = "https://www.googleapis.com/oauth2/v2/userinfo?access_token=" . $access_token;
        $user_info_json = file_get_contents($user_info_url);
        $user_info = json_decode($user_info_json, true);
        
        if (isset($user_info['email'])) {
            $email = $user_info['email'];
            $google_id = $user_info['id'];
            $fullname = $user_info['name'];
            
            // Check if user exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                // Register new user
                $stmt = $pdo->prepare("INSERT INTO users (email, google_id, fullname, role) VALUES (?, ?, ?, 'user')");
                $stmt->execute([$email, $google_id, $fullname]);
                $user_id = $pdo->lastInsertId();
                $user_role = 'user';
            } else {
                // Update google_id if not set
                if (!$user['google_id']) {
                    $stmt = $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                    $stmt->execute([$google_id, $user['id']]);
                }
                $user_id = $user['id'];
                $user_role = $user['role'];
                $fullname = $user['fullname'] ?: $fullname;
            }
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = $user_role;
            $_SESSION['user_name'] = $fullname;
            
            header('Location: index.php');
            exit;
        }
    }
}
echo "Gagal login dengan Google. <a href='login.php'>Kembali</a>";
?>
