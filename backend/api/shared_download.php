<?php
// This endpoint is PUBLIC - it does not require a sessionId.
// It uses a share token to download a file from FTP.
require_once __DIR__ . '/../config.php';

$token = $_GET['token'] ?? '';
if (empty($token) || !preg_match('/^[a-f0-9]{48}$/', $token)) {
    http_response_code(400);
    echo '<h1>Lien invalide</h1><p>Ce lien de partage est invalide.</p>'; exit;
}

$sharesDir = __DIR__ . '/../shares/';
$shareFile = $sharesDir . $token . '.json';

if (!file_exists($shareFile)) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Lien expiré - NexusFTP</title>
    <style>body{font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#0f172a;margin:0;}
    .box{background:#1e293b;border:1px solid #334155;border-radius:16px;padding:40px;text-align:center;max-width:400px;}
    h1{color:#f87171;margin:0 0 12px}p{color:#94a3b8;margin:0}a{color:#818cf8;}</style></head>
    <body><div class="box"><h1>🔗 Lien expiré</h1><p>Ce lien de partage n\'existe plus ou a expiré (24h).<br/><br/><a href="/">Retourner sur NexusFTP</a></p></div></body></html>';
    exit;
}

$share = json_decode(file_get_contents($shareFile), true);

if (!$share || time() > $share['expiresAt']) {
    @unlink($shareFile);
    http_response_code(410);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Lien expiré - NexusFTP</title>
    <style>body{font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#0f172a;margin:0;}
    .box{background:#1e293b;border:1px solid #334155;border-radius:16px;padding:40px;text-align:center;max-width:400px;}
    h1{color:#f87171;margin:0 0 12px}p{color:#94a3b8;margin:0}a{color:#818cf8;}</style></head>
    <body><div class="box"><h1>⏰ Lien expiré</h1><p>Ce lien de téléchargement a expiré (validité 24h).<br/><br/><a href="/">Retourner sur NexusFTP</a></p></div></body></html>';
    exit;
}

// Override Content-Type to binary stream (not JSON)
header_remove('Content-Type');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($share['remoteName']) . '"');
header('Cache-Control: no-cache');

$password = decryptPassword($share['password']);
$tempFile = tempnam(sys_get_temp_dir(), 'nexussh_');

try {
    if ($share['type'] === 'sftp') {
        $connection = ssh2_connect($share['host'], $share['port']);
        ssh2_auth_password($connection, $share['username'], $password);
        $sftp = ssh2_sftp($connection);
        $remoteFile = rtrim($share['remotePath'], '/') . '/' . $share['remoteName'];
        $stream = @fopen("ssh2.sftp://" . intval($sftp) . $remoteFile, 'r');
        if (!$stream) { http_response_code(500); echo 'Cannot open remote file'; exit; }
        $local = fopen($tempFile, 'w');
        while (!feof($stream)) fwrite($local, fread($stream, CHUNK_SIZE));
        fclose($stream); fclose($local);
    } else {
        $timeout = 30;
        if ($share['type'] === 'ftps' || $share['type'] === 'ftpse') {
            $conn = ftp_ssl_connect($share['host'], $share['port'], $timeout);
        } else {
            $conn = ftp_connect($share['host'], $share['port'], $timeout);
        }
        if (!$conn || !ftp_login($conn, $share['username'], $password)) {
            http_response_code(500); echo 'Failed to connect to server'; exit;
        }
        ftp_pasv($conn, $share['passive'] ?? true);
        $success = ftp_get($conn, $tempFile, $share['remoteName'], FTP_BINARY);
        ftp_close($conn);
        if (!$success) { http_response_code(500); echo 'Failed to download file'; exit; }
    }

    header('Content-Length: ' . filesize($tempFile));
    readfile($tempFile);
    unlink($tempFile);
} catch (Exception $e) {
    if (file_exists($tempFile)) unlink($tempFile);
    http_response_code(500);
    echo 'Error: ' . $e->getMessage();
}
?>
