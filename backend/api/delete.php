<?php
require_once __DIR__ . '/../config.php';
$data = getInput();
$sessionId = $data['sessionId'] ?? '';
$path = $data['path'] ?? '/';
$name = $data['name'] ?? '';
$isDirectory = $data['isDirectory'] ?? false;
if (empty($sessionId) || empty($name)) sendError('Session ID and item name are required');
$session = loadSession($sessionId);
if (!$session) sendError('Invalid or expired session', 401);

$password = decryptPassword($session['password']);
$fullPath = $path . '/' . $name;

try {
    if ($session['type'] === 'sftp') {
        $connection = ssh2_connect($session['host'], $session['port']);
        ssh2_auth_password($connection, $session['username'], $password);
        $sftp = ssh2_sftp($connection);
        if ($isDirectory) deleteSFTPDir($sftp, $fullPath);
        else ssh2_sftp_unlink($sftp, $fullPath);
    } else {
        $timeout = 30;
        if ($session['type'] === 'ftps' || $session['type'] === 'ftpse') $conn = ftp_ssl_connect($session['host'], $session['port'], $timeout);
        else $conn = ftp_connect($session['host'], $session['port'], $timeout);
        ftp_login($conn, $session['username'], $password);
        ftp_pasv($conn, $session['passive'] ?? true);
        if ($isDirectory) deleteFTPDir($conn, $fullPath);
        else ftp_delete($conn, $fullPath);
        ftp_close($conn);
    }
    sendSuccess(['message' => 'Item deleted', 'path' => $fullPath]);
} catch (Exception $e) {
    sendError('Failed to delete: ' . $e->getMessage(), 500);
}

function deleteFTPDir($conn, $path) {
    $files = ftp_nlist($conn, $path);
    if ($files) { foreach ($files as $file) { $bn = basename($file); if ($bn === '.' || $bn === '..') continue; if (@ftp_delete($conn, $path.'/'.$bn) === false) deleteFTPDir($conn, $path.'/'.$bn); } }
    ftp_rmdir($conn, $path);
}
function deleteSFTPDir($sftp, $path) {
    $dir = @opendir("ssh2.sftp://" . intval($sftp) . $path);
    if ($dir) { while (($file = readdir($dir)) !== false) { if ($file === '.' || $file === '..') continue; $full = $path.'/'.$file; $stat = ssh2_sftp_stat($sftp, $full); if ($stat && ($stat['mode'] & 0040000)) deleteSFTPDir($sftp, $full); else ssh2_sftp_unlink($sftp, $full); } closedir($dir); }
    ssh2_sftp_rmdir($sftp, $path);
}
?>
