<?php
require_once 'config.php';
$pdo->exec("USE `self_talk_db` ");

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'get_entries') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
    
    if ($limit === 'all') {
        $stmt = $pdo->query("SELECT * FROM talk_entries ORDER BY id ASC");
        $entries = $stmt->fetchAll();
        echo json_encode(['data' => $entries, 'total' => count($entries)]);
    } else {
        $limit = (int)$limit;
        $offset = ($page - 1) * $limit;
        
        $total = $pdo->query("SELECT COUNT(*) FROM talk_entries")->fetchColumn();
        $stmt = $pdo->prepare("SELECT * FROM talk_entries ORDER BY id ASC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $entries = $stmt->fetchAll();
        
        echo json_encode(['data' => $entries, 'total' => (int)$total, 'page' => $page, 'limit' => $limit]);
    }
    exit;
}

if ($action === 'get_archive') {
    $stmt = $pdo->query("SELECT id, vocab_id, text_id, created_at FROM talk_entries ORDER BY created_at DESC");
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $archive = [];
    foreach ($entries as $entry) {
        $date = strtotime($entry['created_at']);
        $year = date('Y', $date);
        $month = date('F', $date);
        
        if (!isset($archive[$year])) $archive[$year] = [];
        if (!isset($archive[$year][$month])) $archive[$year][$month] = [];
        
        $archive[$year][$month][] = [
            'id' => $entry['id'],
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
