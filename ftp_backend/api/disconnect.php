<?php
require_once __DIR__ . '/../config.php';
$data = getInput();
$sessionId = $data['sessionId'] ?? '';
if (empty($sessionId)) sendError('Session ID is required');
$session = loadSession($sessionId);
if (!$session) sendError('Invalid or expired session', 401);
deleteSession($sessionId);
sendSuccess(['message' => 'Disconnected successfully']);
?>
