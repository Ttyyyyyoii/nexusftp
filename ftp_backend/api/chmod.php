<?php
require_once __DIR__ . '/../config.php';

$data = getInput();
$sessionId = $data['sessionId'] ?? '';
$path      = $data['path'] ?? '';
$mode      = $data['mode'] ?? '';

if (empty($sessionId)) sendError('Session ID is required');
if (empty($path))      sendError('Path is required');
if (empty($mode) || !preg_match('/^[0-7]{3,4}$/', $mode)) sendError('Invalid mode (expected octal like 755)');

$session = loadSession($sessionId);
if (!$session) sendError('Invalid or expired session', 401);

$type     = $session['type'] ?? 'ftp';
$password = decryptPassword($session['password']);
$octal    = intval($mode, 8);

if ($type === 'sftp') {
    if (!function_exists('ssh2_connect')) sendError('ssh2 extension not available');
    $conn = @ssh2_connect($session['host'], $session['port']);
    if (!$conn) sendError('SSH connection failed');
    if (!@ssh2_auth_password($conn, $session['username'], $password)) sendError('SSH authentication failed');

    $sftp = ssh2_sftp($conn);
    if (!$sftp) sendError('Could not init SFTP subsystem');

    $result = @ssh2_sftp_chmod($sftp, $path, $octal);
    if (!$result) sendError('chmod failed — permission denied or path not found');

    sendSuccess(['path' => $path, 'mode' => $mode]);

} else {
    // FTP/FTPS: use SITE CHMOD command
    $timeout = 30;
    if ($type === 'ftps' || $type === 'ftpse') {
        $conn = @ftp_ssl_connect($session['host'], $session['port'], $timeout);
    } else {
        $conn = @ftp_connect($session['host'], $session['port'], $timeout);
    }
    if (!$conn) sendError('FTP connection failed');
    if (!@ftp_login($conn, $session['username'], $password)) sendError('FTP authentication failed');

    ftp_pasv($conn, $session['passive'] ?? true);

    $response = @ftp_raw($conn, "SITE CHMOD $mode $path");
    ftp_close($conn);

    if (!$response) sendError('SITE CHMOD command failed');
    $responseStr = is_array($response) ? implode(' ', $response) : $response;

    // FTP returns 200 on success
    if (!preg_match('/^200/', $responseStr)) {
        sendError('chmod failed: ' . $responseStr);
    }

    sendSuccess(['path' => $path, 'mode' => $mode]);
}
?>
