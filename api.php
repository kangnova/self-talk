<?php
require_once 'config.php';
$pdo->exec("USE `self_talk_db` ");

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'get_entries') {
    $entries = $pdo->query("SELECT * FROM talk_entries ORDER BY id ASC")->fetchAll();
    echo json_encode($entries);
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
