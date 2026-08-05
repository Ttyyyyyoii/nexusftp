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
        if ($session['type'] === 'ftps' || $session['type'] === 'ftpse') $conn = @ftp_ssl_connect($session['host'], $session['port'], $timeout);
        else $conn = @ftp_connect($session['host'], $session['port'], $timeout);
        
        if (!$conn || !@ftp_login($conn, $session['username'], $password)) sendError('Failed to connect for upload');
        
        // Tentative standard
        @ftp_pasv($conn, $session['passive'] ?? true);
        
        // Creation recursive des dossiers si necessaire
        $parts = explode('/', trim($remotePath, '/'));
        $current = '';
        foreach ($parts as $part) {
            if (empty($part)) continue;
            $current .= '/' . $part;
            if (!@ftp_chdir($conn, $current)) {
                @ftp_mkdir($conn, $current);
                @ftp_chdir($conn, $current);
            }
        }
        
        // Assurez-vous qu'on est bien dans le bon repertoire
        @ftp_chdir($conn, $remotePath);
        
        $success = @ftp_put($conn, $remoteName, $file['tmp_name'], FTP_BINARY);
        @ftp_close($conn);
        
        // Fallback cURL si la methode standard echoue (Pare-feu / IPv6 EPSV block)
        if (!$success && function_exists('curl_init')) {
            $localfile = fopen($file['tmp_name'], 'r');
            $maxRetries = 3;
            $curlOutput = false;
            $curlError = '';
            
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                $ch = curl_init();
                
                // INDISPENSABLE : rembobiner le fichier local a chaque tentative !
                rewind($localfile);
                
                $protocol = ($session['type'] === 'ftps' || $session['type'] === 'ftpse') ? 'ftps://' : 'ftp://';
                $url = $protocol . $session['host'] . ":" . $session['port'] . '/' . ltrim($remotePath, '/') . '/' . rawurlencode($remoteName);
                
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_USERPWD, $session['username'] . ":" . $password);
                curl_setopt($ch, CURLOPT_UPLOAD, 1);
                curl_setopt($ch, CURLOPT_INFILE, $localfile);
                curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file['tmp_name']));
                // Le timeout doit etre assez grand pour uploader un fichier complet (30 sec au lieu de 6)
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                curl_setopt($ch, CURLOPT_FTP_USE_EPSV, false);
                curl_setopt($ch, CURLOPT_FTP_USE_EPRT, false);
                curl_setopt($ch, CURLOPT_FTP_SKIP_PASV_IP, true);
                curl_setopt($ch, CURLOPT_FTP_CREATE_MISSING_DIRS, true);
                
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
                usleep(500000); // 0.5s pause before retry
            }
            
            fclose($localfile);
            
            if (!$success) sendError('Failed to upload file (cURL fallback failed: ' . $curlError . ')');
        } else if (!$success) {
            sendError('Failed to upload file (Standard FTP blocked and cURL unavailable)');
        }
    }
    sendSuccess(['message' => 'File uploaded successfully', 'file' => $remoteName, 'size' => $file['size']]);
} catch (Exception $e) {
    sendError('Upload failed: ' . $e->getMessage(), 500);
}
?>
