<?php
// github_link.php
// Crée automatiquement le webhook dans le dépôt GitHub via l'API,
// puis sauvegarde la config de déploiement localement.

require_once __DIR__ . '/../config.php';

$input       = getInput();
$sessionId   = $input['sessionId']   ?? '';
$remotePath  = $input['remotePath']  ?? '';
$githubRepo  = $input['githubRepo']  ?? ''; // ex: Ttyyyyyoii/nexusftp
$githubBranch= $input['githubBranch']?? 'main';
$githubToken = $input['githubToken'] ?? ''; // OAuth access_token

if (!$sessionId || !$remotePath || !$githubRepo || !$githubToken) {
    sendError('Champs manquants (sessionId, remotePath, githubRepo, githubToken)');
}

$session = loadSession($sessionId);
if (!$session) {
    sendError('Session invalide ou expirée', 401);
}

// ─── Créer le dossier deployments ────────────────────────────────────────────
$deploymentsDir = __DIR__ . '/../deployments/';
if (!is_dir($deploymentsDir)) {
    mkdir($deploymentsDir, 0755, true);
}

// ─── Générer un token unique pour ce webhook ─────────────────────────────────
$webhookToken = bin2hex(random_bytes(16));
$webhookSecret = bin2hex(random_bytes(16)); // Pour valider les payloads GitHub

// L'URL publique du webhook (sur Render)
$isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$scheme = $isHttps ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'nexusftp.onrender.com';
$webhookUrl = $scheme . '://' . $host . '/api/github_webhook.php?token=' . $webhookToken;

// ─── Appel API GitHub : créer le webhook dans le dépôt ───────────────────────
$webhookPayload = [
    'name'   => 'web',
    'active' => true,
    'events' => ['push'],
    'config' => [
        'url'          => $webhookUrl,
        'content_type' => 'json',
        'secret'       => $webhookSecret,
        'insecure_ssl' => '0',
    ]
];

$ch = curl_init("https://api.github.com/repos/$githubRepo/hooks");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhookPayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $githubToken",
    'Accept: application/vnd.github+json',
    'Content-Type: application/json',
    'User-Agent: NexusFTP',
    'X-GitHub-Api-Version: 2022-11-28'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

$result = curl_exec($ch);
$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$hookData = json_decode($result, true);

// Webhook créé avec succès = code 201
if ($status !== 201) {
    $errMsg = $hookData['message'] ?? ('Erreur GitHub API (HTTP ' . $status . ')');
    // Cas courant : webhook déjà existant avec la même URL → on ignore et on continue
    if ($status !== 422) {
        sendError("Impossible de créer le webhook GitHub : $errMsg");
    }
}

$hookId = $hookData['id'] ?? null;

// ─── Sauvegarder la config de déploiement ────────────────────────────────────
$deployConfig = [
    'host'          => $session['host'],
    'port'          => $session['port'],
    'username'      => $session['username'],
    'password'      => $session['password'], // déjà chiffré
    'passive'       => $session['passive'],
    'remotePath'    => $remotePath,
    'githubRepo'    => $githubRepo,
    'githubBranch'  => $githubBranch,
    'githubToken'   => encryptPassword($githubToken), // requis pour les dépôts privés
    'webhookSecret' => $webhookSecret,
    'hookId'        => $hookId,
    'createdAt'     => date('c')
];

file_put_contents($deploymentsDir . $webhookToken . '.json', json_encode($deployConfig));

sendSuccess([
    'message'    => 'Webhook créé et enregistré avec succès !',
    'webhookUrl' => $webhookUrl,
    'hookId'     => $hookId,
    'repo'       => $githubRepo,
    'branch'     => $githubBranch,
]);
?>
