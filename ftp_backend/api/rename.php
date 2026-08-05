<?php
require_once __DIR__ . '/../config.php';
$data = getInput();
$sessionId = $data['sessionId'] ?? '';
$path = $data['path'] ?? '/';
$oldName = $data['oldName'] ?? '';
$newName = $data['newName'] ?? '';
if (empty($sessionId) || empty($oldName) || empty($newName)) sendError('Session ID, old name and new name are required');
$session = loadSession($sessionId);
if (!$session) sendError('Invalid or expired session', 401);

$password = decryptPassword($session['password']);
$oldPath = $path . '/' . $oldName;
$newPath = $path . '/' . $newName;

try {
    if ($session['type'] === 'sftp') {
        $connection = ssh2_connect($session['host'], $session['port']);
        ssh2_auth_password($connection, $session['username'], $password);
        $sftp = ssh2_sftp($connection);
        $success = ssh2_sftp_rename($sftp, $oldPath, $newPath);
    } else {
        $timeout = 30;
        if ($session['type'] === 'ftps' || $session['type'] === 'ftpse') $conn = ftp_ssl_connect($session['host'], $session['port'], $timeout);
        else $conn = ftp_connect($session['host'], $session['port'], $timeout);
        ftp_login($conn, $session['username'], $password);
        ftp_pasv($conn, $session['passive'] ?? true);
        $success = ftp_rename($conn, $oldPath, $newPath);
        ftp_close($conn);
    }
    if ($success) sendSuccess(['message' => 'Item renamed', 'from' => $oldName, 'to' => $newName]);
    else sendError('Failed to rename item');
} catch (Exception $e) {
    sendError('Failed to rename: ' . $e->getMessage(), 500);
}
?>
