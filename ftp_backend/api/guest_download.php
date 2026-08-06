<?php
// guest_download.php
require_once __DIR__ . '/../config.php';

$token = $_GET['token'] ?? '';
$password = $_GET['password'] ?? '';
$path = $_GET['path'] ?? '';

if (empty($token) || empty($path)) {
    http_response_code(400);
    die('Missing token or path');
}

$file = __DIR__ . '/../guests/' . basename($token) . '.json';
if (!file_exists($file)) {
    http_response_code(404);
    die('Guest link not found or expired');
}

$guest = json_decode(file_get_contents($file), true);
if (!$guest) die('Invalid guest link');

// Check expiration
if ($guest['expiresAt'] > 0 && time() > $guest['expiresAt']) {
    unlink($file);
    http_response_code(410);
    die('Guest link expired');
}

// Check password
if (!empty($guest['passwordReq'])) {
    if (empty($password) || !password_verify($password, $guest['passwordReq'])) {
        http_response_code(401);
        die('Invalid or missing password');
    }
}

// Build full path safely
$cleanPath = ltrim($path, '/');
if (strpos($cleanPath, '..') !== false) {
    http_response_code(403);
    die('Invalid path');
}

$fullPath = rtrim($guest['remotePath'], '/') . '/' . $cleanPath;
$ftpPassword = decryptPassword($guest['password']);
$remoteName = basename($cleanPath);

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $remoteName . '"');

try {
    if ($guest['type'] === 'sftp') {
        $connection = ssh2_connect($guest['host'], $guest['port']);
        ssh2_auth_password($connection, $guest['username'], $ftpPassword);
        $sftp = ssh2_sftp($connection);
        
        $remoteFile = "ssh2.sftp://" . intval($sftp) . $fullPath;
        $stream = @fopen($remoteFile, 'r');
        
        if (!$stream) {
            http_response_code(404);
            die('Cannot open file for download');
        }
        
        while (!feof($stream)) echo fread($stream, CHUNK_SIZE);
        fclose($stream);
        
    } else {
        $timeout = 30;
        if ($guest['type'] === 'ftps' || $guest['type'] === 'ftpse') {
            $conn = @ftp_ssl_connect($guest['host'], $guest['port'], $timeout);
        } else {
            $conn = @ftp_connect($guest['host'], $guest['port'], $timeout);
        }
        
        if (!$conn || !@ftp_login($conn, $guest['username'], $ftpPassword)) {
            http_response_code(500);
            die('Failed to connect to FTP server');
        }
        
        @ftp_pasv($conn, $guest['passive'] ?? true);
        
        $stream = fopen('php://temp', 'r+');
        if (@ftp_fget($conn, $stream, $fullPath, FTP_BINARY)) {
            rewind($stream);
            fpassthru($stream);
        } else {
            http_response_code(404);
            echo 'Cannot open file for download';
        }
        fclose($stream);
        @ftp_close($conn);
    }
} catch (Exception $e) {
    http_response_code(500);
    die('Download error: ' . $e->getMessage());
}
?>
