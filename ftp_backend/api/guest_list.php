<?php
// guest_list.php
ob_start();
require_once __DIR__ . '/../config.php';

$input = getInput();
$token = $input['token'] ?? '';
$password = $input['password'] ?? '';
$path = $input['path'] ?? '/'; // Relative to the shared remotePath

if (empty($token)) sendError('Token is required');

$file = __DIR__ . '/../guests/' . basename($token) . '.json';
if (!file_exists($file)) sendError('Guest link not found or expired', 404);

$guest = json_decode(file_get_contents($file), true);
if (!$guest) sendError('Invalid guest link');

// Check expiration
if ($guest['expiresAt'] > 0 && time() > $guest['expiresAt']) {
    unlink($file);
    sendError('Guest link expired', 410);
}

// Check password
if (!empty($guest['passwordReq'])) {
    if (empty($password) || !password_verify($password, $guest['passwordReq'])) {
        sendError('Invalid or missing password', 401);
    }
}

// Prepare connection
$ftpPassword = decryptPassword($guest['password']);
$fullPath = rtrim($guest['remotePath'], '/') . '/' . ltrim($path, '/');

try {
    if ($guest['type'] === 'sftp') {
        $connection = ssh2_connect($guest['host'], $guest['port']);
        ssh2_auth_password($connection, $guest['username'], $ftpPassword);
        $sftp = ssh2_sftp($connection);
        
        $dir = "ssh2.sftp://" . intval($sftp) . $fullPath;
        $handle = @opendir($dir);
        if (!$handle) sendError('Cannot access directory');
        
        $files = [];
        while (false !== ($entry = readdir($handle))) {
            if ($entry == '.' || $entry == '..') continue;
            
            $stat = @ssh2_sftp_stat($sftp, $fullPath . '/' . $entry);
            $isDirectory = ($stat['mode'] & 040000) === 040000;
            
            $files[] = [
                'name' => $entry,
                'isDirectory' => $isDirectory,
                'size' => $isDirectory ? 0 : $stat['size'],
                'modified' => date('Y-m-d H:i:s', $stat['mtime'])
            ];
        }
        closedir($handle);
        
        usort($files, function($a, $b) {
            if ($a['isDirectory'] && !$b['isDirectory']) return -1;
            if (!$a['isDirectory'] && $b['isDirectory']) return 1;
            return strcasecmp($a['name'], $b['name']);
        });
        
    } else {
        $timeout = 30;
        if ($guest['type'] === 'ftps' || $guest['type'] === 'ftpse') {
            $conn = @ftp_ssl_connect($guest['host'], $guest['port'], $timeout);
        } else {
            $conn = @ftp_connect($guest['host'], $guest['port'], $timeout);
        }
        
        if (!$conn || !@ftp_login($conn, $guest['username'], $ftpPassword)) {
            sendError('Failed to connect to FTP server');
        }
        
        @ftp_pasv($conn, $guest['passive'] ?? true);
        
        $rawlist = @ftp_rawlist($conn, $fullPath);
        if ($rawlist === false) sendError('Cannot access directory');
        
        $files = [];
        foreach ($rawlist as $line) {
            $info = preg_split("/[\s]+/", $line, 9);
            if (count($info) !== 9) continue;
            $name = $info[8];
            if ($name === '.' || $name === '..') continue;
            
            $isDirectory = strpos($info[0], 'd') === 0;
            $size = (int)$info[4];
            
            $files[] = [
                'name' => $name,
                'isDirectory' => $isDirectory,
                'size' => $isDirectory ? 0 : $size,
                'modified' => trim("$info[5] $info[6] $info[7]")
            ];
        }
        
        @ftp_close($conn);
        
        usort($files, function($a, $b) {
            if ($a['isDirectory'] && !$b['isDirectory']) return -1;
            if (!$a['isDirectory'] && $b['isDirectory']) return 1;
            return strcasecmp($a['name'], $b['name']);
        });
    }
    
    ob_end_clean();
    sendSuccess([
        'files' => $files, 
        'path' => $path, 
        'permission' => $guest['permission']
    ]);

} catch (Exception $e) {
    ob_end_clean();
    sendError('Error: ' . $e->getMessage(), 500);
}
?>
