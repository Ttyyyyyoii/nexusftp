<?php
require_once __DIR__ . '/../config.php';

$input = getInput();
$prompt = $input['prompt'] ?? '';
$fileContent = $input['fileContent'] ?? '';

if (!$prompt) {
    sendError('Prompt is required');
}

// Les clés API Gemini fournies par l'utilisateur
$apiKeys = [
    'AQ.Ab8RN6K7AchGxt5fOv3hEhR5LukoAjfOK7MH_oU188SMKCNk7g',
    'AQ.Ab8RN6JAmBWhmplVzUVtYj6GhcssecJZIDYbcDQRiPgeyz09sA',
    'AQ.Ab8RN6KV03CBLyMDKbUEP9d34LRuVdUrdV6bIiAvQHU-Z87RGQ'
];

$fullPrompt = $prompt;
if ($fileContent) {
    // Si un contexte de fichier est fourni, on demande à l'IA de l'analyser
    $fullPrompt .= "\n\n--- FILE CONTEXT ---\n" . substr($fileContent, 0, 50000) . "\n--- END OF FILE CONTEXT ---\n";
}

$success = false;
$responseContent = '';
$lastError = '';

// Rotation et Fallback sur les clés API
foreach ($apiKeys as $key) {
    // Note: The provided keys look like Google Cloud API keys, they might need to be used with the correct Gemini endpoint.
    // We will use the standard Gemini endpoint. If they are OAuth tokens, they might not work here.
    // Assuming they are standard Gemini API keys:
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=" . $key;
    
    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $fullPrompt]
                ]
            ]
        ],
        "systemInstruction" => [
            "parts" => [
                ["text" => "Tu es NexusBot, un assistant IA expert en programmation intégré dans le client FTP 'NexusFTP'. Réponds toujours de manière concise et utile. Tu aides l'utilisateur à comprendre ses fichiers, trouver des bugs ou optimiser son code. Si on te donne un contexte de fichier, base ta réponse dessus."]
            ]
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $result) {
        $json = json_decode($result, true);
        if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
            $responseContent = $json['candidates'][0]['content']['parts'][0]['text'];
            $success = true;
            break; // Succès ! On arrête d'essayer les autres clés.
        }
    } else {
        $lastError = $result;
    }
}

if ($success) {
    sendSuccess(['reply' => $responseContent]);
} else {
    sendError('Toutes les clés API ont échoué ou les quotas sont épuisés. Dernière erreur : ' . $lastError);
}
?>
