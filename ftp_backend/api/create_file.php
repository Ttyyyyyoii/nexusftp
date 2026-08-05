<?php
require_once __DIR__ . '/../config.php';

$data = getInput();
$sessionId = $data['sessionId'] ?? '';
$path = $data['path'] ?? '/';
$name = $data['name'] ?? '';

if (empty($sessionId)) sendError('Session ID is required');
if (empty($name)) sendError('File name is required');

$session = loadSession($sessionId);
if (!$session) sendError('Invalid or expired session', 401);

$password = decryptPassword($session['password']);
$fullPath = rtrim($path, '/') . '/' . $name;

// Creer un fichier quasi-vide (1 octet) pour eviter les bugs de cURL avec les fichiers 0 octet
$tempFile = tempnam(sys_get_temp_dir(), 'ftp_');
file_put_contents($tempFile, ' ');
$localfile = fopen($tempFile, 'r');

$success = false;

if ($session['type'] === 'sftp') {
    $connection = @ssh2_connect($session['host'], $session['port']);
    if ($connection && @ssh2_auth_password($connection, $session['username'], $password)) {
        $sftp = @ssh2_sftp($connection);
        if ($sftp) {
            $sftpPath = "ssh2.sftp://" . intval($sftp) . $fullPath;
            $success = @file_put_contents($sftpPath, '') !== false;
        }
    }
} else {
    $timeout = 30;
    if ($session['type'] === 'ftps' || $session['type'] === 'ftpse') {
        $conn = @ftp_ssl_connect($session['host'], $session['port'], $timeout);
    } else {
        $conn = @ftp_connect($session['host'], $session['port'], $timeout);
    }

    if ($conn && @ftp_login($conn, $session['username'], $password)) {
        $pasvWanted = $session['passive'] ?? true;
        @ftp_pasv($conn, $pasvWanted);
        
        @ftp_chdir($conn, $path);
        
        $success = @ftp_fput($conn, $name, $localfile, FTP_BINARY);
        
        if (!$success) {
            $altMode = !$pasvWanted;
            @ftp_pasv($conn, $altMode);
            rewind($localfile);
            $success = @ftp_fput($conn, $name, $localfile, FTP_BINARY);
        }
        
        // Fallback cURL
        $curlErrorMsg = '';
        if (!$success && function_exists('curl_init')) {
            $maxRetries = 3;
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                $ch = curl_init();
                rewind($localfile);
                
                $protocol = ($session['type'] === 'ftps' || $session['type'] === 'ftpse') ? 'ftps://' : 'ftp://';
                $url = $protocol . $session['host'] . ":" . $session['port'] . '/' . ltrim($path, '/') . '/' . rawurlencode($name);
                
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_USERPWD, $session['username'] . ":" . $password);
                curl_setopt($ch, CURLOPT_UPLOAD, 1);
                curl_setopt($ch, CURLOPT_INFILE, $localfile);
                curl_setopt($ch, CURLOPT_INFILESIZE, 1); // 1 byte
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                curl_setopt($ch, CURLOPT_FTP_USE_EPSV, false);
                curl_setopt($ch, CURLOPT_FTP_USE_EPRT, false);
                curl_setopt($ch, CURLOPT_FTP_SKIP_PASV_IP, true);
                
                if ($session['type'] === 'ftps' || $session['type'] === 'ftpse') {
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                }
                
                $success = curl_exec($ch);
                if (curl_errno($ch) === 0) {
                    curl_close($ch);
                    break;
                }
                $curlErrorMsg = curl_error($ch);
                curl_close($ch);
                usleep(500000);
            }
        }
        @ftp_close($conn);
    }
}

fclose($localfile);
unlink($tempFile);

if ($success) {
    sendSuccess(['message' => 'File created successfully']);
} else {
    file_put_contents(__DIR__ . '/create_file_error.log', date('Y-m-d H:i:s') . " - Error: $curlErrorMsg\n", FILE_APPEND);
    sendError('Failed to create file: ' . $curlErrorMsg);
}
