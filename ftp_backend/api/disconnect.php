<?php
require_once __DIR__ . '/../config.php';
$data = getInput();
$sessionId = $data['sessionId'] ?? '';
if (empty($sessionId)) sendSuccess(['message' => 'Already disconnected']);
$session = loadSession($sessionId);
// Si la session n'existe plus, l'utilisateur est déjà déconnecté -> succès
if (!$session) sendSuccess(['message' => 'Already disconnected']);
deleteSession($sessionId);
sendSuccess(['message' => 'Disconnected successfully']);
?>

