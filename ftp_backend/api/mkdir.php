<?php
require_once __DIR__ . '/../config.php';
$data = getInput();
$sessionId = $data['sessionId'] ?? '';
$path = $data['path'] ?? '/';
$name = $data['name'] ?? '';
if (empty($sessionId) || empty($name)) sendError('Session ID and folder name are required');
$session = loadSession($sessionId);
if (!$session) sendError('Invalid or expired session', 401);

$password = decryptPassword($session['password']);
$fullPath = $path . '/' . $name;

try {
    if ($session['type'] === 'sftp') {
        $connection = ssh2_connect($session['host'], $session['port']);
        ssh2_auth_password($connection, $session['username'], $password);
        $sftp = ssh2_sftp($connection);
        $success = ssh2_sftp_mkdir($sftp, $fullPath, 0755, true);
    } else {
        $timeout = 30;
        if ($session['type'] === 'ftps' || $session['type'] === 'ftpse') $conn = ftp_ssl_connect($session['host'], $session['port'], $timeout);
        else $conn = ftp_connect($session['host'], $session['port'], $timeout);
        ftp_login($conn, $session['username'], $password);
        $fullPath = $path . '/' . $name;
        
        error_clear_last();
        $success = @ftp_mkdir($conn, $fullPath);
        $err = error_get_last();
        ftp_close($conn);
    }
    if ($success) sendSuccess(['message' => 'Folder created', 'path' => $fullPath]);
    else throw new Exception("Failed to create folder (permissions or already exists) " . ($err ? $err['message'] : ''));
} catch (Exception $e) {
    sendError('Failed to create folder: ' . $e->getMessage(), 500);
}
?>
