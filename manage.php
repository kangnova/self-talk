<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$pdo->exec("USE `$db` "); // Ensure using the right database

$manage_user_id = $user_id;
$is_admin_shared_mode = false;
if ($_SESSION['user_role'] === 'admin') {
    if (isset($_GET['user_id'])) {
        $manage_user_id = $_GET['user_id'];
    } else {
        $is_admin_shared_mode = true;
    }
}

function get_auth_query($is_admin_shared_mode, $manage_user_id) {
    if ($is_admin_shared_mode) {
        return ["user_id IN (SELECT id FROM users WHERE role = 'admin')", []];
    }
    return ["user_id = ?", [$manage_user_id]];
}

$message = "";

// Handle Create / Update / Delete for Sentences
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type']) && $_POST['type'] === 'sentence') {
    if (isset($_POST['action'])) {
        try {
            list($auth_where, $auth_params) = get_auth_query($is_admin_shared_mode, $manage_user_id);
            
            if ($_POST['action'] === 'save') {
                $id = $_POST['id'] ?? null;
                $text_id = $_POST['text_id'];
                $text_en = $_POST['text_en'];
                $pronunciation = $_POST['pronunciation'];
                $breakdown = strip_tags($_POST['breakdown']);
                // Auto-bold: find words before parentheses and wrap them in <strong>
                // Example: "Wash (Mencuci)" -> "<strong>Wash</strong> (Mencuci)"
                $breakdown = preg_replace('/([^,()]+)\s*(\([^)]+\))/', '<strong>$1</strong> $2', $breakdown);
                $vocab_id = $_POST['vocab_id'];
                $vocab_en = $_POST['vocab_en'];
                $vocab_pron = $_POST['vocab_pron'];

                if ($id) {
                    $sql = "UPDATE talk_entries SET text_id=?, text_en=?, pronunciation=?, breakdown=?, vocab_id=?, vocab_en=?, vocab_pron=? WHERE id=? AND $auth_where";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(array_merge([$text_id, $text_en, $pronunciation, $breakdown, $vocab_id, $vocab_en, $vocab_pron, $id], $auth_params));
                    $message = "Sentence updated successfully!";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO talk_entries (user_id, text_id, text_en, pronunciation, breakdown, vocab_id, vocab_en, vocab_pron) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$manage_user_id, $text_id, $text_en, $pronunciation, $breakdown, $vocab_id, $vocab_en, $vocab_pron]);
                    $message = "New sentence added successfully!";
                }
            } elseif ($_POST['action'] === 'delete') {
                $id = $_POST['id'];
                $sql = "DELETE FROM talk_entries WHERE id=? AND $auth_where";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array_merge([$id], $auth_params));
                $message = "Sentence deleted successfully!";
            }
        } catch (PDOException $e) { $message = "Error: " . $e->getMessage(); }
    }
}



// Fetch filtered entries
list($auth_where, $auth_params) = get_auth_query($is_admin_shared_mode, $manage_user_id);
$stmt = $pdo->prepare("SELECT talk_entries.*, users.fullname as creator_name FROM talk_entries LEFT JOIN users ON talk_entries.user_id = users.id WHERE $auth_where ORDER BY id ASC");
$stmt->execute($auth_params);
$entries = $stmt->fetchAll();
$vocabs = []; // Not used anymore

// Edit mode for sentence
$edit_entry = null;
if (isset($_GET['edit_sentence'])) {
    $id = $_GET['edit_sentence'];
    foreach ($entries as $e) { if ($e['id'] == $id) { $edit_entry = $e; break; } }
}


?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Self-Talk - Rin</title>
    <style>
        :root { --primary: #2563eb; --secondary: #10b981; --accent: #f59e0b; --bg: #f1f5f9; --text: #1e293b; --card-bg: #ffffff; }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, sans-serif; background-color: var(--bg); color: var(--text); padding: 20px; margin: 0; min-height: 100vh; }
        .container { max-width: 900px; margin: 0 auto; background: var(--card-bg); padding: 30px; border-radius: 16px; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); border: 1px solid #e2e8f0; }
        h1, h2 { color: var(--primary); font-weight: 800; margin-top: 0; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.9rem; color: #475569; }
        input[type="text"], textarea { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 10px; box-sizing: border-box; font-family: inherit; font-size: 1rem; transition: border-color 0.2s; }
        input[type="text"]:focus, textarea:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        button { padding: 12px 24px; border: none; border-radius: 10px; cursor: pointer; font-weight: bold; transition: all 0.2s; font-size: 0.9rem; }
        button:hover { transform: translateY(-1px); box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .btn-save { background: var(--primary); color: white; }
        .btn-cancel { background: #f1f5f9; color: #475569; text-decoration: none; padding: 12px 24px; display: inline-block; border-radius: 10px; font-weight: bold; }
        .btn-edit { background: #eff6ff; color: var(--primary); text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 0.8rem; border: 1px solid #dbeafe; }
        .btn-delete { background: #fef2f2; color: #ef4444; padding: 6px 12px; font-size: 0.8rem; border: 1px solid #fee2e2; }
        
        .table-responsive { width: 100%; overflow-x: auto; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 20px; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th, td { text-align: left; padding: 16px; border-bottom: 1px solid #f1f5f9; }
        th { background: #f8fafc; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; font-weight: 700; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #fbfcfe; }
        
        .message { padding: 15px; background: #dcfce7; color: #166534; border-radius: 10px; margin-bottom: 25px; border-left: 4px solid var(--secondary); font-weight: 500; }
        .nav { margin-bottom: 30px; }
        .nav a { text-decoration: none; color: #64748b; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: color 0.2s; }
        .nav a:hover { color: var(--primary); }
        
        @media (max-width: 600px) {
            .container { padding: 20px; border-radius: 0; }
            .btn-group-mobile { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
            button, .btn-cancel { width: 100%; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav">
            <a href="index.php">← Back to App</a>
        </div>
        <h1>Manage Self-Talk Data</h1>
        
        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>



        <!-- SECTION: SENTENCES -->
        <section style="margin-bottom: 50px; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px;">
            <h2><?= $edit_entry ? "Edit Sentence" : "Add New Sentence" ?></h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="type" value="sentence">
                <?php if ($edit_entry): ?>
                    <input type="hidden" name="id" value="<?= $edit_entry['id'] ?>">
                <?php endif; ?>

                <div style="background: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #e2e8f0;">
                    <h4 style="margin-top: 0; color: #475569; font-size: 1rem; margin-bottom: 15px;">Vocabulary (Tampil di Header Card)</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>ID (Indo)</label>
                            <input type="text" name="vocab_id" value="<?= $edit_entry['vocab_id'] ?? '' ?>" placeholder="e.g. Bangun Tidur">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>EN (English)</label>
                            <input type="text" name="vocab_en" value="<?= $edit_entry['vocab_en'] ?? '' ?>" placeholder="e.g. Wake up">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Cara Baca</label>
                            <input type="text" name="vocab_pron" value="<?= $edit_entry['vocab_pron'] ?? '' ?>" placeholder="Pronunciation">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Bahasa Indonesia</label>
                    <input type="text" name="text_id" value="<?= $edit_entry['text_id'] ?? '' ?>" required>
                </div>
                <div class="form-group">
                    <label>English Sentence</label>
                    <input type="text" name="text_en" value="<?= $edit_entry['text_en'] ?? '' ?>" required>
                </div>
                <div class="form-group">
                    <label>Cara Baca (Pronunciation)</label>
                    <input type="text" name="pronunciation" value="<?= $edit_entry['pronunciation'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>Pecahan/Breakdown (Otomatis Tebalkan Kata)</label>
                    <textarea name="breakdown" rows="2"><?= isset($edit_entry['breakdown']) ? strip_tags($edit_entry['breakdown']) : '' ?></textarea>
                    <small>Contoh: Wash (Mencuci), Face (Muka)</small>
                </div>
                
                <button type="submit" class="btn-save"><?= $edit_entry ? "Update Sentence" : "Save Sentence" ?></button>
                <?php if ($edit_entry): ?>
                    <a href="manage.php?user_id=<?= $manage_user_id ?>" class="btn-cancel">Cancel</a>
                <?php endif; ?>
            </form>

            <h3 style="margin-top: 30px;">Existing Sentences</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Sentence (ID/EN)</th>
                            <th>Word (ID)</th>
                            <th>Word (EN)</th>
                            <th>Pronunciation</th>
                            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                <th>Creator</th>
                            <?php endif; ?>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $index => $row): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <strong style="color: var(--primary); display: block; margin-bottom: 4px;"><?= htmlspecialchars($row['text_id']) ?></strong>
                                <span style="color:#64748b; font-size: 0.9rem;"><?= htmlspecialchars($row['text_en']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($row['vocab_id']) ?></td>
                            <td><?= htmlspecialchars($row['vocab_en']) ?></td>
                            <td style="color: #64748b; font-size: 0.8rem; font-style: italic;"><?= htmlspecialchars($row['vocab_pron']) ?></td>
                            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                <td><span style="font-size: 0.75rem; color: #64748b; background: #f8fafc; padding: 4px 8px; border-radius: 6px; border: 1px solid #e2e8f0; white-space: nowrap; font-weight: 500;"><?= htmlspecialchars($row['creator_name'] ?? 'System') ?></span></td>
                            <?php endif; ?>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <a href="manage.php?edit_sentence=<?= $row['id'] ?>&user_id=<?= $manage_user_id ?>" class="btn-edit">Edit</a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="type" value="sentence">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn-delete">Del</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>


    </div>
</body>
</html>
