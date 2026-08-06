<?php
// guest_manage.php
ob_start();
require_once __DIR__ . '/../config.php';

$input = getInput();
$action = $input['action'] ?? 'list';
$sessionId = $input['sessionId'] ?? '';

if (empty($sessionId)) sendError('Session ID is required');

$session = loadSession($sessionId);
if (!$session) sendError('Invalid or expired session', 401);

$guestsDir = __DIR__ . '/../guests/';
if (!is_dir($guestsDir)) {
    ob_end_clean();
    sendSuccess(['guests' => []]);
}

if ($action === 'list') {
    $files = glob($guestsDir . '*.json');
    $result = [];
    foreach ($files as $file) {
        $data = json_decode(file_get_contents($file), true);
        if (!$data) continue;
        
        if (($data['host'] ?? '') !== $session['host'] || ($data['username'] ?? '') !== $session['username']) {
            continue;
        }

        $result[] = [
            'token' => $data['token'],
            'remotePath' => $data['remotePath'],
            'permission' => $data['permission'] ?? 'read',
            'hasPassword' => !empty($data['passwordReq']),
            'createdAt' => $data['createdAt'],
            'expiresAt' => $data['expiresAt']
        ];
    }
    
    usort($result, fn($a, $b) => $b['createdAt'] <=> $a['createdAt']);
    
    ob_end_clean();
    sendSuccess(['guests' => $result]);
} 
elseif ($action === 'delete') {
    $token = $input['token'] ?? '';
    if (empty($token)) sendError('Token is required');
    
    $file = $guestsDir . basename($token) . '.json';
    if (!file_exists($file)) sendError('Guest link not found');
    
    $data = json_decode(file_get_contents($file), true);
    if (($data['host'] ?? '') !== $session['host'] || ($data['username'] ?? '') !== $session['username']) {
        sendError('Access denied', 403);
    }
    
    unlink($file);
    ob_end_clean();
    sendSuccess(['message' => 'Guest link deleted']);
}

ob_end_clean();
sendError('Invalid action');
?>
