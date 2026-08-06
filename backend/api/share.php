<?php
require_once __DIR__ . '/../config.php';
$data = getInput();
$sessionId = $data['sessionId'] ?? '';
$remotePath = $data['remotePath'] ?? '/';
$remoteName = $data['remoteName'] ?? '';

if (empty($sessionId) || empty($remoteName)) sendError('Session ID and remote name are required');
$session = loadSession($sessionId);
if (!$session) sendError('Invalid or expired session', 401);

// Create shares directory
$sharesDir = __DIR__ . '/../shares/';
if (!is_dir($sharesDir)) mkdir($sharesDir, 0755, true);

// Generate a unique token
$token = bin2hex(random_bytes(24));
$expiresAt = time() + 86400; // 24 hours

$shareData = [
    'token' => $token,
    'sessionId' => $sessionId,
    'remotePath' => $remotePath,
    'remoteName' => $remoteName,
    'host' => $session['host'],
    'port' => $session['port'],
    'username' => $session['username'],
    'password' => $session['password'], // already encrypted
    'type' => $session['type'],
    'passive' => $session['passive'] ?? true,
    'createdAt' => time(),
    'expiresAt' => $expiresAt
];

file_put_contents($sharesDir . $token . '.json', json_encode($shareData));

// Build public share URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$shareUrl = $protocol . '://' . $host . '/api/shared_download.php?token=' . $token;

sendSuccess([
    'token' => $token,
    'shareUrl' => $shareUrl,
    'expiresAt' => date('Y-m-d H:i:s', $expiresAt),
    'fileName' => $remoteName
]);
?>
