<?php
require_once __DIR__ . '/../config.php';
$data = getInput();
$sessionId = $data['sessionId'] ?? '';
$remotePath = $data['remotePath'] ?? '/';
$remoteName = $data['remoteName'] ?? '';
if (empty($sessionId) || empty($remoteName)) sendError('Session ID and remote name are required');
$session = loadSession($sessionId);
if (!$session) sendError('Invalid or expired session', 401);

$password = decryptPassword($session['password']);
$tempFile = tempnam(sys_get_temp_dir(), 'nexusdl_');

try {
    if ($session['type'] === 'sftp') {
        $connection = ssh2_connect($session['host'], $session['port']);
        ssh2_auth_password($connection, $session['username'], $password);
        $sftp = ssh2_sftp($connection);
        $remoteFile = $remotePath . '/' . $remoteName;
        $stream = @fopen("ssh2.sftp://" . intval($sftp) . $remoteFile, 'r');
        if (!$stream) sendError('Cannot open remote file');
        $local = fopen($tempFile, 'w');
        while (!feof($stream)) fwrite($local, fread($stream, CHUNK_SIZE));
        fclose($stream); fclose($local);
    } else {
        $timeout = 30;
        if ($session['type'] === 'ftps' || $session['type'] === 'ftpse') $conn = ftp_ssl_connect($session['host'], $session['port'], $timeout);
        else $conn = ftp_connect($session['host'], $session['port'], $timeout);
        if (!$conn || !ftp_login($conn, $session['username'], $password)) sendError('Failed to connect for download');
        ftp_pasv($conn, $session['passive'] ?? true);
        $remoteFile = $remotePath . '/' . $remoteName;
        $success = ftp_get($conn, $tempFile, $remoteName, FTP_BINARY);
        ftp_close($conn);
        if (!$success) sendError('Failed to download file');
    }

    $fileSize = filesize($tempFile);
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $remoteName . '"');
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: no-cache');
    readfile($tempFile);
    unlink($tempFile);
    exit;
} catch (Exception $e) {
    if (file_exists($tempFile)) unlink($tempFile);
    sendError('Download failed: ' . $e->getMessage(), 500);
}
?>
