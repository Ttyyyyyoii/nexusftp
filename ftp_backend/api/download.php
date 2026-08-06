<?php
require_once __DIR__ . '/../config.php';
$data = getInput();
if (empty($data['sessionId']) && !empty($_GET['sessionId'])) {
    $data = $_GET;
}

$sessionId = $data['sessionId'] ?? '';
$remotePath = $data['remotePath'] ?? '/';
$remoteName = $data['remoteName'] ?? '';

// Support for full 'path' parameter (used by Gallery)
if (!empty($data['path'])) {
    $parts = explode('/', ltrim($data['path'], '/'));
    $remoteName = array_pop($parts);
    $remotePath = '/' . implode('/', $parts);
}

if (empty($sessionId) || empty($remoteName)) sendError('Session ID and remote name are required');
$session = loadSession($sessionId);
if (!$session) sendError('Invalid or expired session', 401);

$password = decryptPassword($session['password']);
$tempFile = tempnam(sys_get_temp_dir(), 'nexusdl_');

$inline = !empty($data['inline']) && $data['inline'] == '1';

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
        if ($session['type'] === 'ftps' || $session['type'] === 'ftpse') $conn = @ftp_ssl_connect($session['host'], $session['port'], $timeout);
        else $conn = @ftp_connect($session['host'], $session['port'], $timeout);
        
        if (!$conn || !@ftp_login($conn, $session['username'], $password)) sendError('Failed to connect for download');
        
        // Tentative standard
        @ftp_pasv($conn, $session['passive'] ?? true);
        @ftp_chdir($conn, $remotePath);
        $success = @ftp_get($conn, $tempFile, $remoteName, FTP_BINARY);
        @ftp_close($conn);
        
        // Fallback cURL
        if (!$success) {
            $maxRetries = 3;
            $success = false;
            $curlError = '';
            $temp = fopen($tempFile, 'w+');

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                $ch = curl_init();
                
                // INDISPENSABLE : vider et rembobiner le fichier temporaire
                ftruncate($temp, 0);
                rewind($temp);
                
                $protocol = ($session['type'] === 'ftps' || $session['type'] === 'ftpse') ? 'ftps://' : 'ftp://';
                $url = $protocol . $session['host'] . ":" . $session['port'] . '/' . ltrim($remotePath, '/') . '/' . rawurlencode($remoteName);
                
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_USERPWD, $session['username'] . ":" . $password);
                curl_setopt($ch, CURLOPT_FILE, $temp);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 sec timeout for downloading
                
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                curl_setopt($ch, CURLOPT_FTP_USE_EPSV, false);
                curl_setopt($ch, CURLOPT_FTP_USE_EPRT, false);
                curl_setopt($ch, CURLOPT_FTP_SKIP_PASV_IP, true);
                
                if ($session['type'] === 'ftps' || $session['type'] === 'ftpse') {
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                }
                
                $success = curl_exec($ch);
                $curlError = curl_error($ch);
                $errno = curl_errno($ch);
                curl_close($ch);
                
                if ($errno === 0) {
                    break; // Success!
                }
                usleep(500000);
            }
            fclose($temp);
        }
        
        if (!$success) sendError('Failed to download file');
    }
    
    $mime = 'application/octet-stream';
    if ($inline) {
        $ext = strtolower(pathinfo($remoteName, PATHINFO_EXTENSION));
        $mimes = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','svg'=>'image/svg+xml','webp'=>'image/webp'];
        if (isset($mimes[$ext])) $mime = $mimes[$ext];
    }
    
    header('Content-Type: ' . $mime);
    if (!$inline) {
        header('Content-Disposition: attachment; filename="' . $remoteName . '"');
    } else {
        header('Content-Disposition: inline; filename="' . $remoteName . '"');
    }
    header('Content-Length: ' . filesize($tempFile));
    header('Cache-Control: no-cache');
    readfile($tempFile);
    unlink($tempFile);
    exit;
} catch (Exception $e) {
    if (file_exists($tempFile)) unlink($tempFile);
    sendError('Download failed: ' . $e->getMessage(), 500);
}
?>
