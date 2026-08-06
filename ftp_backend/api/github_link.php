<?php
require_once __DIR__ . '/../config.php';

$input = getInput();
$sessionId = $input['sessionId'] ?? '';
$remotePath = $input['remotePath'] ?? '';
$githubRepo = $input['githubRepo'] ?? ''; // ex: Ttyyyyyoii/nexusftp
$githubBranch = $input['githubBranch'] ?? 'main';
$githubToken = $input['githubToken'] ?? ''; // Optional PAT for private repos

if (!$sessionId || !$remotePath || !$githubRepo) {
    sendError('Champs manquants. Vous devez spécifier le dépôt GitHub.');
}

$session = loadSession($sessionId);
if (!$session) {
    sendError('Session invalide ou expirée', 401);
}

$deploymentsDir = __DIR__ . '/../deployments/';
if (!is_dir($deploymentsDir)) {
    mkdir($deploymentsDir, 0755, true);
}

// Générer un token unique pour le webhook
$webhookToken = bin2hex(random_bytes(16));

$deployConfig = [
    'host' => $session['host'],
    'port' => $session['port'],
    'username' => $session['username'],
    'password' => $session['password'], // This is already encrypted in the session
    'passive' => $session['passive'],
    'remotePath' => $remotePath,
    'githubRepo' => $githubRepo,
    'githubBranch' => $githubBranch,
    'githubToken' => $githubToken ? encryptPassword($githubToken) : null
];

file_put_contents($deploymentsDir . $webhookToken . '.json', json_encode($deployConfig));

// Renvoyer l'URL du webhook complet
$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = dirname($_SERVER['SCRIPT_NAME']);
$webhookUrl = $scheme . '://' . $host . $basePath . '/github_webhook.php?token=' . $webhookToken;

sendSuccess([
    'webhookUrl' => $webhookUrl,
    'message' => 'Lien de déploiement généré avec succès !'
]);
?>
