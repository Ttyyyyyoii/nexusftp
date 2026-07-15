<?php
require_once __DIR__ . '/../config.php';
$sessionId = $_POST['sessionId'] ?? '';
$remotePath = $_POST['remotePath'] ?? '/';
$remoteName = $_POST['remoteName'] ?? '';
if (empty($sessionId)) sendError('Session ID is required');
if (!isset($_FILES['file'])) sendError('No file uploaded');
$session = loadSession($sessionId);
if (!$session) sendError('Invalid or expired session', 401);

$file = $_FILES['file'];
$remoteName = $remoteName ?: $file['name'];
$password = decryptPassword($session['password']);

try {
    if ($session['type'] === 'sftp') {
        $connection = ssh2_connect($session['host'], $session['port']);
        ssh2_auth_password($connection, $session['username'], $password);
        $sftp = ssh2_sftp($connection);
        $remoteFile = $remotePath . '/' . $remoteName;
        $stream = @fopen("ssh2.sftp://" . intval($sftp) . $remoteFile, 'w');
        if (!$stream) sendError('Cannot create remote file');
        $local = fopen($file['tmp_name'], 'r');
        while (!feof($local)) fwrite($stream, fread($local, CHUNK_SIZE));
        fclose($local); fclose($stream);
    } else {
        $timeout = 30;
        if ($session['type'] === 'ftps' || $session['type'] === 'ftpse') $conn = ftp_ssl_connect($session['host'], $session['port'], $timeout);
        else $conn = ftp_connect($session['host'], $session['port'], $timeout);
        if (!$conn || !ftp_login($conn, $session['username'], $password)) sendError('Failed to connect for upload');
        ftp_pasv($conn, $session['passive'] ?? true);
        ftp_chdir($conn, $remotePath);
        $success = ftp_put($conn, $remoteName, $file['tmp_name'], FTP_BINARY);
        ftp_close($conn);
        if (!$success) sendError('Failed to upload file');
    }
    sendSuccess(['message' => 'File uploaded successfully', 'file' => $remoteName, 'size' => $file['size']]);
} catch (Exception $e) {
    sendError('Upload failed: ' . $e->getMessage(), 500);
}
?>
