<?php
// guest_share.php
ob_start();
require_once __DIR__ . '/../config.php';

$input = getInput();
$sessionId  = $input['sessionId'] ?? '';
$remotePath = $input['remotePath'] ?? '/';
$permission = $input['permission'] ?? 'read'; // 'read' or 'upload'
$expiration = (int)($input['expiration'] ?? 7); // days, 0 = never
$password   = $input['password'] ?? '';

if (empty($sessionId)) sendError('Session ID is required');

$session = loadSession($sessionId);
if (!$session) sendError('Invalid or expired session', 401);

$guestsDir = __DIR__ . '/../guests/';
if (!is_dir($guestsDir)) mkdir($guestsDir, 0755, true);

$token = bin2hex(random_bytes(16));
$expiresAt = $expiration > 0 ? time() + ($expiration * 86400) : 0;

$guestData = [
    'token'      => $token,
    'host'       => $session['host'],
    'port'       => $session['port'],
    'username'   => $session['username'],
    'password'   => $session['password'], // already encrypted
    'type'       => $session['type'],
    'passive'    => $session['passive'] ?? true,
    'remotePath' => rtrim($remotePath, '/'),
    'permission' => $permission,
    'passwordReq'=> !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null,
    'createdAt'  => time(),
    'expiresAt'  => $expiresAt
];

file_put_contents($guestsDir . $token . '.json', json_encode($guestData));

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

ob_end_clean();
sendSuccess([
    'token'    => $token,
    'guestUrl' => $protocol . '://' . $host . '/guest/' . $token,
    'expiresAt'=> $expiresAt ? date('c', $expiresAt) : null
]);
?>
