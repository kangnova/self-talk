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
        :root { --primary: #2563eb; --bg: #f8fafc; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); padding: 40px; margin: 0; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        h1 { color: var(--primary); margin-bottom: 30px; font-size: 1.5rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
        th { background: #f8fafc; font-weight: 600; color: #64748b; }
        .btn { padding: 8px 12px; border-radius: 8px; text-decoration: none; font-size: 0.8rem; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s; }
        .btn-manage { background: #e0e7ff; color: #4338ca; }
        .btn-manage:hover { background: #c7d2fe; }
        .btn-delete { background: #fee2e2; color: #dc2626; }
        .btn-delete:hover { background: #fecaca; }
        .msg { background: #dcfce7; color: #166534; padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h2 style="margin: 0; color: #2563eb;">Managemen User</h2>
            <div style="display: flex; gap: 10px;">
                <a href="admin_settings.php" class="btn" style="background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;">⚙️ Pengaturan Google</a>
                <a href="index.php" class="btn btn-manage">Kembali ke Dashboard</a>
            </div>
        </div>

        <?php if ($message): ?> <div class="msg"><?= $message ?></div> <?php endif; ?>

        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Terdaftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['fullname']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="action" value="set_role">
                                    <select name="role" onchange="this.form.submit()" style="padding: 5px; border-radius: 5px; border: 1px solid #e2e8f0; font-family: inherit; font-size: 0.85rem;">
                                        <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                        <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                </form>
                            </td>
                            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <a href="index.php?user_id=<?= $u['id'] ?>" class="btn" style="background: #d1fae5; color: #065f46;">Lihat Dashboard</a>
                                    <a href="manage.php?user_id=<?= $u['id'] ?>" class="btn btn-manage">Manage Isi</a>
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
