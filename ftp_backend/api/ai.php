<?php
require_once __DIR__ . '/../config.php';

$input = getInput();
$prompt = $input['prompt'] ?? '';
$fileContent = $input['fileContent'] ?? '';

if (!$prompt) {
    sendError('Prompt is required');
}

// Clés API Gemini (avec fallback automatique si quota épuisé)
$apiKeys = [
    'AQ.Ab8RN6K7AchGxt5fOv3hEhR5LukoAjfOK7MH_oU188SMKCNk7g',
    'AQ.Ab8RN6JAmBWhmplVzUVtYj6GhcssecJZIDYbcDQRiPgeyz09sA',
    'AQ.Ab8RN6KV03CBLyMDKbUEP9d34LRuVdUrdV6bIiAvQHU-Z87RGQ'
];

$modelName = 'gemini-2.5-flash'; // Modèle actuel stable

// Construction du prompt complet
$fullPrompt = $prompt;
if ($fileContent) {
    $fullPrompt .= "\n\n--- FILE CONTEXT ---\n" . substr($fileContent, 0, 50000) . "\n--- END OF FILE CONTEXT ---";
}

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
            ["text" => "Tu es NexusBot, un assistant IA expert en développement web intégré dans NexusFTP, un client FTP premium. Tu aides l'utilisateur à comprendre ses fichiers, trouver des bugs, optimiser son code ou expliquer des concepts. Réponds toujours de manière concise, claire et structurée. Si un contexte de fichier est fourni, base ta réponse dessus. Utilise du markdown pour les blocs de code."]
        ]
    ]
];

// Fonction d'appel à Gemini (inspirée du projet de référence)
function callGemini($apiKey, $modelName, $payload) {
    $cleanKey = trim($apiKey);
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . urlencode($modelName) . ':generateContent?key=' . urlencode($cleanKey);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Expect:']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

    $body    = curl_exec($ch);
    $curlErr = curl_error($ch);
    $status  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false) {
        return ['ok' => false, 'status' => 0, 'error' => $curlErr ?: 'Erreur cURL', 'raw' => null];
    }

    $decoded = json_decode($body, true);

    if ($status < 200 || $status >= 300 || (is_array($decoded) && isset($decoded['error']))) {
        return [
            'ok'     => false,
            'status' => $status,
            'error'  => is_array($decoded) && isset($decoded['error']['message'])
                ? $decoded['error']['message']
                : ('Gemini HTTP ' . $status),
            'raw'    => $decoded ?: $body
        ];
    }

    return ['ok' => true, 'status' => $status, 'data' => $decoded];
}

// Rotation des clés avec fallback
$lastError = null;

foreach ($apiKeys as $apiKey) {
    $result = callGemini($apiKey, $modelName, $payload);

    if ($result['ok']) {
        $text = $result['data']['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if ($text) {
            sendSuccess(['reply' => $text]);
            exit;
        }
    }

    $lastError = $result;

    // Si l'erreur est liée à la clé (quota, auth, bad request), on essaie la suivante
    // Sinon (panne réseau) on sort de la boucle
    if (!in_array($result['status'], [400, 401, 403, 429, 500, 503])) {
        break;
    }
}

// Toutes les clés ont échoué
$debugMsg = isset($lastError['error']) ? $lastError['error'] : 'Erreur inconnue';
sendError('NexusBot indisponible. ' . $debugMsg);
?>
