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
            list($auth_where, $auth_params) = get_auth_query($is_admin_shared_mode, $manage_user_id);
            
            if ($_POST['action'] === 'save') {
                $id = $_POST['id'] ?? null;
                $text_id = $_POST['text_id'];
                $text_en = $_POST['text_en'];
                $pronunciation = $_POST['pronunciation'];
                $breakdown = $_POST['breakdown'];
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

// Handle Create / Update / Delete for Vocabulary
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type']) && $_POST['type'] === 'vocab') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'save') {
                $id = $_POST['id'] ?? null;
                $word_id = $_POST['word_id'];
                $word_en = $_POST['word_en'];
                $pronunciation = $_POST['pronunciation'];

                if ($id) {
                    $stmt = $pdo->prepare("UPDATE vocabulary SET word_id=?, word_en=?, pronunciation=? WHERE id=?");
                    $stmt->execute([$word_id, $word_en, $pronunciation, $id]);
                    $message = "Vocabulary updated successfully!";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO vocabulary (word_id, word_en, pronunciation) VALUES (?, ?, ?)");
                    $stmt->execute([$word_id, $word_en, $pronunciation]);
                    $message = "New vocabulary added successfully!";
                }
            } elseif ($_POST['action'] === 'delete') {
                $id = $_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM vocabulary WHERE id=?");
                $stmt->execute([$id]);
                $message = "Vocabulary deleted successfully!";
            }
        } catch (PDOException $e) { $message = "Error: " . $e->getMessage(); }
    }
}

// Fetch filtered entries
list($auth_where, $auth_params) = get_auth_query($is_admin_shared_mode, $manage_user_id);
$stmt = $pdo->prepare("SELECT talk_entries.*, users.fullname as creator_name FROM talk_entries LEFT JOIN users ON talk_entries.user_id = users.id WHERE $auth_where ORDER BY id ASC");
$stmt->execute($auth_params);
$entries = $stmt->fetchAll();
$vocabs = $pdo->query("SELECT * FROM vocabulary ORDER BY id ASC")->fetchAll();

// Edit mode for sentence
$edit_entry = null;
if (isset($_GET['edit_sentence'])) {
    $id = $_GET['edit_sentence'];
    foreach ($entries as $e) { if ($e['id'] == $id) { $edit_entry = $e; break; } }
}

// Edit mode for vocab
$edit_vocab = null;
if (isset($_GET['edit_vocab'])) {
    $id = $_GET['edit_vocab'];
    foreach ($vocabs as $v) { if ($v['id'] == $id) { $edit_vocab = $v; break; } }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Self-Talk - Rin</title>
    <style>
        :root { --primary: #2563eb; --secondary: #10b981; --accent: #f59e0b; --bg: #f8fafc; --text: #1e293b; --card-bg: #ffffff; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--bg); color: var(--text); padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: var(--card-bg); padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        h1, h2 { color: var(--primary); }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input[type="text"], textarea { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-family: inherit; }
        button { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn-save { background: var(--primary); color: white; }
        .btn-cancel { background: #64748b; color: white; text-decoration: none; padding: 10px 20px; display: inline-block; border-radius: 6px; }
        .btn-edit { background: var(--accent); color: white; text-decoration: none; padding: 5px 10px; border-radius: 4px; font-size: 0.8rem; }
        .btn-delete { background: #ef4444; color: white; padding: 5px 10px; font-size: 0.8rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #e2e8f0; }
        th { background: #f1f5f9; }
        .message { padding: 10px; background: #dcfce7; color: #166534; border-radius: 6px; margin-bottom: 20px; }
        .nav { margin-bottom: 20px; }
        .nav a { text-decoration: none; color: var(--primary); font-weight: bold; }
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

        <!-- SECTION 1: SENTENCES -->
        <section style="margin-bottom: 50px; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px;">
            <h2><?= $edit_entry ? "Edit Sentence" : "Add New Sentence" ?></h2>
            <form method="POST" action="manage.php">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="type" value="sentence">
                <?php if ($edit_entry): ?>
                    <input type="hidden" name="id" value="<?= $edit_entry['id'] ?>">
                <?php endif; ?>

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
                    <label>Pecahan/Breakdown (HTML allowed)</label>
                    <textarea name="breakdown" rows="2"><?= $edit_entry['breakdown'] ?? '' ?></textarea>
                    <small>Contoh: &lt;strong&gt;Word&lt;/strong&gt; (Arti)</small>
                </div>

                <div style="background: #f1f5f9; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <h4 style="margin-top: 0;">Vocabulary (Tampil di Header Card)</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                        <div class="form-group">
                            <label>ID (e.g. Bangun Tidur)</label>
                            <input type="text" name="vocab_id" value="<?= $edit_entry['vocab_id'] ?? '' ?>">
                        </div>
                        <div class="form-group">
                            <label>EN (e.g. Wake up)</label>
                            <input type="text" name="vocab_en" value="<?= $edit_entry['vocab_en'] ?? '' ?>">
                        </div>
                        <div class="form-group">
                            <label>Cara Baca</label>
                            <input type="text" name="vocab_pron" value="<?= $edit_entry['vocab_pron'] ?? '' ?>">
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-save"><?= $edit_entry ? "Update Sentence" : "Save Sentence" ?></button>
                <?php if ($edit_entry): ?>
                    <a href="manage.php" class="btn-cancel">Cancel</a>
                <?php endif; ?>
            </form>

            <h3 style="margin-top: 30px;">Existing Sentences</h3>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>ID / EN</th>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <th>Creator</th>
                        <?php endif; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $index => $row): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td>
                            <strong><?= htmlspecialchars($row['text_id']) ?></strong><br>
                            <span style="color:#64748b"><?= htmlspecialchars($row['text_en']) ?></span>
                        </td>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <td><span style="font-size: 0.8rem; color: #64748b; background: #f8fafc; padding: 2px 6px; border-radius: 4px; border: 1px solid #e2e8f0;"><?= htmlspecialchars($row['creator_name'] ?? 'System') ?></span></td>
                        <?php endif; ?>
                        <td>
                            <a href="manage.php?edit_sentence=<?= $row['id'] ?>" class="btn-edit">Edit</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="type" value="sentence">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <button type="submit" class="btn-delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>


    </div>
</body>
</html>
