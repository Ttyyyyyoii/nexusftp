<?php
require_once __DIR__ . '/../config.php';
$data = getInput();
$host = $data['host'] ?? '';
$port = intval($data['port'] ?? 21);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';
$type = $data['type'] ?? 'ftp';
$passive = $data['passive'] ?? true;

if (empty($host) || empty($username)) sendError('Hostname and username are required');

$sessionId = generateSessionId();
$sessionData = [
    'id' => $sessionId, 'host' => $host, 'port' => $port,
    'username' => $username, 'password' => encryptPassword($password),
    'type' => $type, 'passive' => $passive, 'connected' => false,
    'createdAt' => time(), 'lastAccess' => time()
];

try {
    if ($type === 'sftp') {
        if (!function_exists('ssh2_connect')) { sendError('SFTP not supported. Install PHP ssh2 extension.', 500); }
        $connection = @ssh2_connect($host, $port);
        if (!$connection) sendError("Could not connect to SSH server at $host:$port", 401);
        if (!@ssh2_auth_password($connection, $username, $password)) sendError('SSH authentication failed', 401);
        $sftp = @ssh2_sftp($connection);
        if (!$sftp) sendError('Could not initialize SFTP subsystem', 500);
    } else {
        if ($type === 'ftps' || $type === 'ftpse') {
            if (!function_exists('ftp_ssl_connect')) sendError('FTPS not supported on this server', 500);
            $conn = @ftp_ssl_connect($host, $port, 30);
        } else {
            $conn = @ftp_connect($host, $port, 30);
        }
        if (!$conn) sendError("Could not connect to $host:$port", 401);
        if (!@ftp_login($conn, $username, $password)) { ftp_close($conn); sendError('Authentication failed', 401); }
        ftp_pasv($conn, $passive);
        ftp_close($conn);
    }

    $sessionData['connected'] = true;
    $sessionData['currentPath'] = '/';
    saveSession($sessionId, $sessionData);
    sendSuccess(['sessionId' => $sessionId, 'message' => 'Connected successfully', 'server' => $host, 'type' => $type]);
} catch (Exception $e) {
    sendError('Connection error: ' . $e->getMessage(), 500);
}
?>
