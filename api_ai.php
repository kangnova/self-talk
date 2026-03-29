<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_GET['q'])) {
    echo json_encode(['error' => 'No query provided']);
    exit;
}

$query = $_GET['q'];
$apiKey = GEMINI_API_KEY;
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

$prompt = "You are a helpful language assistant for an English learning app. 
Translate the Indonesian word '$query' to English.
Provide its pronunciation in a simple readable English phonetic format (e.g. /brek-fest/).
Generate a short, natural Indonesian sentence for daily self-talk using this word.
Translate that sentence to English.
Provide the English sentence pronunciation in a simple readable phonetic format.
Break down the English sentence into key words in this format: 'Word (Translation), Word (Translation)'. 
Return ONLY a JSON object with keys: vocab_en, vocab_pron, text_id, text_en, pronunciation, breakdown.";

$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt]
            ]
        ]
    ],
    "generationConfig" => [
        "response_mime_type" => "application/json"
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo json_encode(['error' => 'API Error', 'status' => $httpCode, 'details' => $response]);
    exit;
}

$respData = json_decode($response);
$cleanText = $respData->candidates[0]->content->parts[0]->text;

// Ensure we only return the JSON part if AI included markdown blocks
if (preg_match('/\{.*\}/s', $cleanText, $matches)) {
    echo $matches[0];
} else {
    echo $cleanText;
}
?>
