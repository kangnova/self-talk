<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$message = "";

// Handle User CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $target_id = $_POST['user_id'];
    if ($_POST['action'] === 'set_role') {
        $new_role = $_POST['role'];
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $target_id]);
        $message = "Role user berhasil diperbarui!";
    } elseif ($_POST['action'] === 'delete') {
        // Delete entries first
        $stmt = $pdo->prepare("DELETE FROM talk_entries WHERE user_id = ?");
        $stmt->execute([$target_id]);
        // Delete user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$target_id]);
        $message = "User dan datanya berhasil dihapus!";
    }
}

$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Managemen User - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2563eb; --secondary: #10b981; --bg: #f1f5f9; --text: #1e293b; }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); padding: 20px; margin: 0; min-height: 100vh; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; gap: 20px; }
        h2 { margin: 0; color: var(--primary); font-weight: 800; font-size: 1.5rem; }
        
        .btn { padding: 10px 16px; border-radius: 10px; text-decoration: none; font-size: 0.85rem; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .btn-manage { background: #eff6ff; color: var(--primary); border: 1px solid #dbeafe; }
        .btn-delete { background: #fef2f2; color: #ef4444; border: 1px solid #fee2e2; }
        .btn-view { background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; }
        
        .table-responsive { width: 100%; overflow-x: auto; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 20px; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
        th { background: #f8fafc; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.75rem; }
        tr:hover { background: #fbfcfe; }
        
        .role-select { padding: 6px 10px; border-radius: 8px; border: 1px solid #e2e8f0; font-family: inherit; font-size: 0.85rem; outline: none; background: white; cursor: pointer; }
        .role-select:focus { border-color: var(--primary); }
        
        .msg { background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 25px; font-size: 0.9rem; border-left: 4px solid var(--secondary); font-weight: 500; }
        
        @media (max-width: 768px) {
            body { padding: 10px; }
            .container { padding: 20px; border-radius: 12px; }
            .header-top { flex-direction: column; align-items: stretch; text-align: center; }
            .header-top .btn-group { display: grid; grid-template-columns: 1fr; gap: 10px; }
            .btn { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-top">
            <h2>Managemen User</h2>
            <div class="btn-group">
                <a href="admin_settings.php" class="btn" style="background: #f8fafc; color: #475569; border: 1px solid #e2e8f0;">⚙️ Pengaturan Google</a>
                <a href="index.php" class="btn btn-manage">Kembali ke Dashboard</a>
            </div>
        </div>

        <?php if ($message): ?> <div class="msg"><?= $message ?></div> <?php endif; ?>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Terdaftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600; color: var(--text);"><?= htmlspecialchars($u['fullname']) ?></div>
                                <div style="font-size: 0.75rem; color: #94a3b8;">ID: #<?= $u['id'] ?></div>
                            </td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="action" value="set_role">
                                    <select name="role" onchange="this.form.submit()" class="role-select">
                                        <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                        <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                </form>
                            </td>
                            <td style="color: #64748b; font-size: 0.85rem;"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <a href="index.php?user_id=<?= $u['id'] ?>" class="btn btn-view">Lihat</a>
                                    <a href="manage.php?user_id=<?= $u['id'] ?>" class="btn btn-manage">Manage</a>
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Hapus user ini?')">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-delete">Hapus</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
