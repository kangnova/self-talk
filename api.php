<?php
require_once 'config.php';
$pdo->exec("USE `$db` ");

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$where_clause = "user_id = ?";
$params = [$user_id];

// Admin viewing logic
if ($_SESSION['user_role'] === 'admin') {
    if (isset($_GET['user_id'])) {
        // Viewing specific user
        $user_id = $_GET['user_id'];
        $params = [$user_id];
    } else {
        // Viewing shared admin pool
        $where_clause = "user_id IN (SELECT id FROM users WHERE role = 'admin')";
        $params = [];
    }
}

$action = $_GET['action'] ?? '';

if ($action === 'get_entries') {
    $limit = $_GET['limit'] ?? 10;
    $page = $_GET['page'] ?? 1;
    
    if ($limit === 'all') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM talk_entries WHERE $where_clause");
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT * FROM talk_entries WHERE $where_clause ORDER BY id ASC");
        $stmt->execute($params);
        $entries = $stmt->fetchAll();
    } else {
        $limit = (int)$limit;
        $offset = ($page - 1) * $limit;
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM talk_entries WHERE $where_clause");
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT * FROM talk_entries WHERE $where_clause ORDER BY id ASC LIMIT $limit OFFSET $offset");
        $stmt->execute($params);
        $entries = $stmt->fetchAll();
    }
    
    echo json_encode(['data' => $entries, 'total' => $total]);
    exit;
}

if ($action === 'get_archive') {
    $stmt = $pdo->prepare("SELECT id, vocab_id, text_id, created_at FROM talk_entries WHERE $where_clause ORDER BY id ASC");
    $stmt->execute($params);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $archive = [];
    foreach ($entries as $index => $entry) {
        $date = strtotime($entry['created_at']);
        $year = date('Y', $date);
        $month = date('F', $date);
        
        if (!isset($archive[$year])) $archive[$year] = [];
        if (!isset($archive[$year][$month])) $archive[$year][$month] = [];
        
        $archive[$year][$month][] = [
            'id' => $entry['id'],
            'index' => $index,
            'title' => $entry['vocab_id'] ?: mb_strimwidth($entry['text_id'], 0, 30, "...")
        ];
    }
    echo json_encode($archive);
    exit;
}

if ($action === 'get_vocabs') {
    $vocabs = $pdo->query("SELECT * FROM vocabulary ORDER BY id ASC")->fetchAll();
    echo json_encode($vocabs);
    exit;
}

if ($action === 'toggle_complete') {
    $input = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare("UPDATE talk_entries SET is_completed = ? WHERE id = ? AND user_id = ?");
    $success = $stmt->execute([$input['status'], $input['id'], $user_id]);
    echo json_encode(['success' => $success]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($action === 'toggle_complete' && isset($input['id'])) {
        $id = $input['id'];
        $status = $input['status'];
        
        $stmt = $pdo->prepare("UPDATE talk_entries SET is_completed = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['error' => 'Invalid request']);
?>
