<?php
session_start();
require_once 'config.php';

// Cek hak akses admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';
$error = '';

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_settings') {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        
        $stmt->execute([$_POST['google_client_id'], 'google_client_id']);
        $stmt->execute([$_POST['google_client_secret'], 'google_client_secret']);
        $stmt->execute([$_POST['google_redirect_url'], 'google_redirect_url']);
        
        $pdo->commit();
        $message = "Pengaturan berhasil diperbarui.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Ambil settings saat ini
$stmt = $pdo->query("SELECT * FROM settings");
$settings_raw = $stmt->fetchAll();
$settings = [];
foreach ($settings_raw as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Google OAuth - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --bg: #f8fafc;
            --card-bg: rgba(255, 255, 255, 0.8);
            --text: #1e293b;
            --border: #e2e8f0;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            width: 100%;
            max-width: 600px;
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border: 1px solid white;
        }
        h2 { margin-top: 0; color: var(--primary); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 0.9rem; }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 0.95rem;
        }
        .btn-save {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
            transition: 0.3s;
        }
        .btn-save:hover { background: var(--primary-hover); }
        .message { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }
        .success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .back-link { display: block; margin-top: 20px; text-align: center; color: #64748b; text-decoration: none; font-size: 0.85rem; }
        .back-link:hover { color: var(--primary); }
    </style>
</head>
<body>
    <div class="container">
        <h2>Pengaturan Google OAuth</h2>
        <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 25px;">Kelola kredensial Google OAuth 2.0 untuk aplikasi ini secara dinamis.</p>

        <?php if ($message): ?>
            <div class="message success"><?= $message ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="update_settings">
            
            <div class="form-group">
                <label>Google Client ID</label>
                <input type="text" name="google_client_id" value="<?= htmlspecialchars($settings['google_client_id'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label>Google Client Secret</label>
                <input type="text" name="google_client_secret" value="<?= htmlspecialchars($settings['google_client_secret'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label>Google Redirect URI</label>
                <input type="text" name="google_redirect_url" value="<?= htmlspecialchars($settings['google_redirect_url'] ?? '') ?>" required>
            </div>

            <button type="submit" class="btn-save">Simpan Perubahan</button>
        </form>

        <a href="admin_users.php" class="back-link">← Kembali ke Managemen User</a>
    </div>
</body>
</html>
