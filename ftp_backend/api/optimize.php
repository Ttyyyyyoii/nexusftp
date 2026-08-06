<?php
require_once __DIR__ . '/../config.php';

$input = getInput();
$sessionId  = $input['sessionId']  ?? '';
$remotePath = $input['remotePath'] ?? '';
$remoteName = $input['remoteName'] ?? '';

if (!$sessionId || !$remotePath || !$remoteName) {
    sendError('Missing required fields: sessionId, remotePath, remoteName');
}

$session = loadSession($sessionId);
if (!$session) sendError('Invalid or expired session', 401);

$ext = strtolower(pathinfo($remoteName, PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
    sendError('Only JPG and PNG images can be optimized.');
}

$password     = decryptPassword($session['password']);
$fullRemote   = rtrim($remotePath, '/') . '/' . $remoteName;
$tempFile     = tempnam(sys_get_temp_dir(), 'nexus_opt_');
$type         = $session['type'] ?? 'ftp';

// ── 1. Download the file ─────────────────────────────────────────────────
if ($type === 'sftp') {
    if (!function_exists('ssh2_connect')) sendError('ssh2 extension not available');
    $conn = @ssh2_connect($session['host'], $session['port']);
    if (!$conn) sendError('SSH connection failed');
    if (!@ssh2_auth_password($conn, $session['username'], $password)) sendError('SSH authentication failed');
    $sftp   = ssh2_sftp($conn);
    $stream = @fopen("ssh2.sftp://" . intval($sftp) . $fullRemote, 'r');
    if (!$stream) sendError('Cannot open remote file for reading');
    $local = fopen($tempFile, 'w');
    while (!feof($stream)) fwrite($local, fread($stream, 65536));
    fclose($stream); fclose($local);
} else {
    $timeout = 30;
    if ($type === 'ftps' || $type === 'ftpse') {
        $ftp = @ftp_ssl_connect($session['host'], $session['port'], $timeout);
    } else {
        $ftp = @ftp_connect($session['host'], $session['port'], $timeout);
    }
    if (!$ftp) sendError('Could not connect to FTP server');
    if (!@ftp_login($ftp, $session['username'], $password)) sendError('FTP authentication failed');
    if ($session['passive'] ?? true) ftp_pasv($ftp, true);
    if (!@ftp_get($ftp, $tempFile, $fullRemote, FTP_BINARY)) {
        ftp_close($ftp);
        sendError('Could not download file for optimization');
    }
    ftp_close($ftp);
}

$originalSize = filesize($tempFile);

// ── 2. Compress with GD ─────────────────────────────────────────────────
$image = null;
if ($ext === 'jpg' || $ext === 'jpeg') {
    $image = @imagecreatefromjpeg($tempFile);
} elseif ($ext === 'png') {
    $image = @imagecreatefrompng($tempFile);
    if ($image) { imagealphablending($image, false); imagesavealpha($image, true); }
}
if (!$image) { unlink($tempFile); sendError('Could not read image with GD — file may be corrupted'); }

$ok = false;
if ($ext === 'jpg' || $ext === 'jpeg') {
    $ok = imagejpeg($image, $tempFile, 70);
} elseif ($ext === 'png') {
    $ok = imagepng($image, $tempFile, 9);
}
imagedestroy($image);
if (!$ok) { unlink($tempFile); sendError('Compression error'); }

clearstatcache();
$newSize = filesize($tempFile);

// ── 3. Upload back ─────────────────────────────────────────────────────
if ($type === 'sftp') {
    $conn2  = @ssh2_connect($session['host'], $session['port']);
    if (!$conn2) { unlink($tempFile); sendError('SSH reconnection failed'); }
    ssh2_auth_password($conn2, $session['username'], $password);
    $sftp2  = ssh2_sftp($conn2);
    $stream2 = @fopen("ssh2.sftp://" . intval($sftp2) . $fullRemote, 'w');
    if (!$stream2) { unlink($tempFile); sendError('Cannot write optimized file back to server'); }
    $local2 = fopen($tempFile, 'r');
    while (!feof($local2)) fwrite($stream2, fread($local2, 65536));
    fclose($local2); fclose($stream2);
} else {
    $timeout = 30;
    if ($type === 'ftps' || $type === 'ftpse') {
        $ftp2 = @ftp_ssl_connect($session['host'], $session['port'], $timeout);
    } else {
        $ftp2 = @ftp_connect($session['host'], $session['port'], $timeout);
    }
    if (!$ftp2 || !@ftp_login($ftp2, $session['username'], $password)) {
        unlink($tempFile);
        sendError('FTP reconnection for upload failed');
    }
    if ($session['passive'] ?? true) ftp_pasv($ftp2, true);
    if (!@ftp_put($ftp2, $fullRemote, $tempFile, FTP_BINARY)) {
        ftp_close($ftp2); unlink($tempFile);
        sendError('Image compressed but upload back to server failed');
    }
    ftp_close($ftp2);
}

unlink($tempFile);

sendSuccess([
    'message'         => 'Image optimized successfully',
    'originalSize'    => $originalSize,
    'newSize'         => $newSize,
    'savedBytes'      => $originalSize - $newSize,
    'savedPercentage' => $originalSize > 0 ? round((($originalSize - $newSize) / $originalSize) * 100) : 0
]);
?>
