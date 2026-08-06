<?php
ob_start(); // Capture les warnings PHP pour ne pas corrompre le JSON
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

// Helper : créer récursivement un chemin FTP sans planter si le dossier existe déjà
function ftpMkdirRecursive($conn, $path) {
    $parts = explode('/', trim($path, '/'));
    $current = '';
    foreach ($parts as $part) {
        if (empty($part)) continue;
        $current .= '/' . $part;
        $changed = @ftp_chdir($conn, $current);
        if (!$changed) {
            @ftp_mkdir($conn, $current);
        }
    }
    @ftp_chdir($conn, '/');
}

try {
    if ($session['type'] === 'sftp') {
        $connection = ssh2_connect($session['host'], $session['port']);
        ssh2_auth_password($connection, $session['username'], $password);
        $sftp = ssh2_sftp($connection);

        // Créer les dossiers parents si nécessaire (SFTP)
        $parts = explode('/', trim($remotePath, '/'));
        $currentDir = '';
        foreach ($parts as $part) {
            if (empty($part)) continue;
            $currentDir .= '/' . $part;
            @ssh2_sftp_mkdir($sftp, $currentDir, 0755, false);
        }

        $remoteFile = rtrim($remotePath, '/') . '/' . $remoteName;
        $stream = @fopen("ssh2.sftp://" . intval($sftp) . $remoteFile, 'w');
        if (!$stream) sendError('Cannot create remote file');
        $local = fopen($file['tmp_name'], 'r');
        while (!feof($local)) fwrite($stream, fread($local, CHUNK_SIZE));
        fclose($local); fclose($stream);

    } else {
        $timeout = 30;
        if ($session['type'] === 'ftps' || $session['type'] === 'ftpse') {
            $conn = @ftp_ssl_connect($session['host'], $session['port'], $timeout);
        } else {
            $conn = @ftp_connect($session['host'], $session['port'], $timeout);
        }

        if (!$conn || !@ftp_login($conn, $session['username'], $password)) {
            sendError('Failed to connect for upload');
        }

        @ftp_pasv($conn, $session['passive'] ?? true);

        // Création récursive robuste (ne plante pas si le dossier existe déjà)
        ftpMkdirRecursive($conn, $remotePath);

        // Se positionner dans le bon répertoire
        @ftp_chdir($conn, $remotePath);

        $success = @ftp_put($conn, $remoteName, $file['tmp_name'], FTP_BINARY);
        @ftp_close($conn);

        // Fallback cURL si la méthode standard échoue (pare-feu / IPv6 EPSV block)
        if (!$success && function_exists('curl_init')) {
            $localfile = fopen($file['tmp_name'], 'r');
            $maxRetries = 3;
            $success = false;
            $curlError = '';

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                $ch = curl_init();
                rewind($localfile);

                $protocol = ($session['type'] === 'ftps' || $session['type'] === 'ftpse') ? 'ftps://' : 'ftp://';
                $url = $protocol . $session['host'] . ":" . $session['port'] . '/' . ltrim($remotePath, '/') . '/' . rawurlencode($remoteName);

                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_USERPWD, $session['username'] . ":" . $password);
                curl_setopt($ch, CURLOPT_UPLOAD, 1);
                curl_setopt($ch, CURLOPT_INFILE, $localfile);
                curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file['tmp_name']));
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

                $curlResult = curl_exec($ch);
                $curlError  = curl_error($ch);
                $errno      = curl_errno($ch);
                curl_close($ch);

                if ($errno === 0) {
                    $success = true;
                    break;
                }
                usleep(500000); // pause 0.5s avant retry
            }

            fclose($localfile);

            if (!$success) sendError('Failed to upload file (cURL fallback failed: ' . $curlError . ')');

        } elseif (!$success) {
            sendError('Failed to upload file (Standard FTP blocked and cURL unavailable)');
        }
    }

    ob_end_clean();
    sendSuccess(['message' => 'File uploaded successfully', 'file' => $remoteName, 'size' => $file['size']]);

} catch (Exception $e) {
    ob_end_clean();
    sendError('Upload failed: ' . $e->getMessage(), 500);
}
?>
