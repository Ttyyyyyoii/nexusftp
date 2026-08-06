<?php
// guest_upload.php
error_reporting(0);
ob_start();
require_once __DIR__ . '/../config.php';

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$path = $_POST['path'] ?? '/';
$remoteName = $_POST['remoteName'] ?? '';

if (empty($token)) sendError('Token is required');
if (!isset($_FILES['file'])) sendError('No file uploaded');

$file_json = __DIR__ . '/../guests/' . basename($token) . '.json';
if (!file_exists($file_json)) sendError('Guest link not found or expired', 404);

$guest = json_decode(file_get_contents($file_json), true);
if (!$guest) sendError('Invalid guest link');

// Check expiration
if ($guest['expiresAt'] > 0 && time() > $guest['expiresAt']) {
    unlink($file_json);
    sendError('Guest link expired', 410);
}

// Check password
if (!empty($guest['passwordReq'])) {
    if (empty($password) || !password_verify($password, $guest['passwordReq'])) {
        sendError('Invalid or missing password', 401);
    }
}

// Check permission
if ($guest['permission'] !== 'upload') {
    sendError('You do not have permission to upload files', 403);
}

$file = $_FILES['file'];
$remoteName = $remoteName ?: $file['name'];
$ftpPassword = decryptPassword($guest['password']);

$cleanPath = ltrim($path, '/');
if (strpos($cleanPath, '..') !== false) sendError('Invalid path');

$fullPath = rtrim($guest['remotePath'], '/') . '/' . $cleanPath;

try {
    if ($guest['type'] === 'sftp') {
        $connection = ssh2_connect($guest['host'], $guest['port']);
        ssh2_auth_password($connection, $guest['username'], $ftpPassword);
        $sftp = ssh2_sftp($connection);
        
        $remoteFile = rtrim($fullPath, '/') . '/' . $remoteName;
        $stream = @fopen("ssh2.sftp://" . intval($sftp) . $remoteFile, 'w');
        if (!$stream) sendError('Cannot create remote file');
        $local = fopen($file['tmp_name'], 'r');
        while (!feof($local)) fwrite($stream, fread($local, CHUNK_SIZE));
        fclose($local); fclose($stream);
    } else {
        $timeout = 30;
        if ($guest['type'] === 'ftps' || $guest['type'] === 'ftpse') {
            $conn = @ftp_ssl_connect($guest['host'], $guest['port'], $timeout);
        } else {
            $conn = @ftp_connect($guest['host'], $guest['port'], $timeout);
        }
        
        if (!$conn || !@ftp_login($conn, $guest['username'], $ftpPassword)) sendError('Failed to connect for upload');
        
        @ftp_pasv($conn, $guest['passive'] ?? true);
        
        @ftp_chdir($conn, $fullPath);
        
        $success = @ftp_put($conn, $remoteName, $file['tmp_name'], FTP_BINARY);
        @ftp_close($conn);
        
        if (!$success && function_exists('curl_init')) {
            $localfile = fopen($file['tmp_name'], 'r');
            $maxRetries = 3;
            $curlError = '';
            
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                $ch = curl_init();
                rewind($localfile);
                
                $protocol = ($guest['type'] === 'ftps' || $guest['type'] === 'ftpse') ? 'ftps://' : 'ftp://';
                $url = $protocol . $guest['host'] . ":" . $guest['port'] . '/' . ltrim($fullPath, '/') . '/' . rawurlencode($remoteName);
                
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_USERPWD, $guest['username'] . ":" . $ftpPassword);
                curl_setopt($ch, CURLOPT_UPLOAD, 1);
                curl_setopt($ch, CURLOPT_INFILE, $localfile);
                curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file['tmp_name']));
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                curl_setopt($ch, CURLOPT_FTP_USE_EPSV, false);
                curl_setopt($ch, CURLOPT_FTP_USE_EPRT, false);
                curl_setopt($ch, CURLOPT_FTP_SKIP_PASV_IP, true);
                curl_setopt($ch, CURLOPT_FTP_CREATE_MISSING_DIRS, true);
                
                if ($guest['type'] === 'ftps' || $guest['type'] === 'ftpse') {
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                }
                
                $success = curl_exec($ch);
                $curlError = curl_error($ch);
                $errno = curl_errno($ch);
                curl_close($ch);
                
                if ($errno === 0) {
                    $success = true;
                    break;
                }
                usleep(500000);
            }
            fclose($localfile);
            
            if (!$success) sendError('Failed to upload file via guest link (cURL fallback failed: ' . $curlError . ')');
        } else if (!$success) {
            sendError('Failed to upload file via guest link (Standard FTP blocked)');
        }
    }
    
    ob_end_clean();
    sendSuccess(['message' => 'File uploaded successfully', 'file' => $remoteName]);
} catch (Exception $e) {
    ob_end_clean();
    sendError('Upload failed: ' . $e->getMessage(), 500);
}
?>
