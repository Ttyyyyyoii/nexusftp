<?php
require_once __DIR__ . '/../config.php';
$data = getInput();
$sessionId = $data['sessionId'] ?? '';
$path = $data['path'] ?? '/';
if (empty($sessionId)) sendError('Session ID is required');
$session = loadSession($sessionId);
if (!$session) sendError('Invalid or expired session', 401);

$files = [];
$password = decryptPassword($session['password']);

if ($session['type'] === 'sftp') {
    $connection = ssh2_connect($session['host'], $session['port']);
    ssh2_auth_password($connection, $session['username'], $password);
    $sftp = ssh2_sftp($connection);
    $sftpPath = "ssh2.sftp://" . intval($sftp) . $path;
    $dir = @opendir($sftpPath);
    if ($dir) {
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') continue;
            $fullPath = $path . '/' . $file;
            $stat = @ssh2_sftp_stat($sftp, $fullPath);
            if ($stat) {
                $isDir = ($stat['mode'] & 0040000) === 0040000;
                $files[] = ['name' => $file, 'size' => $stat['size'], 'sizeFormatted' => formatFileSize($stat['size']),
                    'isDirectory' => $isDir, 'permissions' => formatPerms($stat['mode']), 'modified' => date('Y-m-d H:i:s', $stat['mtime']),
                    'type' => $isDir ? 'directory' : getFileType($file)];
            }
        }
        closedir($dir);
    }
} else {
    $timeout = 30;
    if ($session['type'] === 'ftps' || $session['type'] === 'ftpse') {
        $conn = ftp_ssl_connect($session['host'], $session['port'], $timeout);
    } else {
        $conn = ftp_connect($session['host'], $session['port'], $timeout);
    }
    if ($conn && ftp_login($conn, $session['username'], $password)) {
        ftp_set_option($conn, FTP_USEPASVADDRESS, false);
        ftp_pasv($conn, $session['passive'] ?? true);
        
        $current = ftp_pwd($conn);
        if ($path !== $current && $path !== '/' && $path !== '') {
            @ftp_chdir($conn, $path);
        }
        
        $list = @ftp_rawlist($conn, '-al');
        
        if (empty($list)) {
            $list = @ftp_rawlist($conn, '.'); 
        }
        
        if (empty($list)) {
            // Force active mode if passive completely failed and returned nothing
            ftp_pasv($conn, false);
            $list = @ftp_rawlist($conn, '.');
        }
        
        // Ultimate fallback: cURL
        if (empty($list) && function_exists('curl_init')) {
            $ch = curl_init();
            $protocol = ($session['type'] === 'ftps' || $session['type'] === 'ftpse') ? 'ftps://' : 'ftp://';
            $url = $protocol . $session['host'] . ":" . $session['port'] . '/' . ltrim($path, '/');
            if (substr($url, -1) !== '/') $url .= '/';
            
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_USERPWD, $session['username'] . ":" . $password);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_FTP_USE_EPSV, false); // Crucial fix for 'Failed EPSV attempt'
            curl_setopt($ch, CURLOPT_FTP_USE_EPRT, false);
            
            // Skip the IP provided by the server in PASV response (NAT bypass for cURL)
            curl_setopt($ch, CURLOPT_FTP_SKIP_PASV_IP, true);
            if ($session['type'] === 'ftps' || $session['type'] === 'ftpse') {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }
            
            $curlOutput = curl_exec($ch);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlOutput) {
                $list = explode("\n", trim(str_replace("\r", "", $curlOutput)));
            } else if ($curlError) {
                file_put_contents(__DIR__ . '/curl_error.log', $curlError);
                // Return the cURL error as a fake file so it is visible in the UI
                $files[] = [
                    'name' => 'CURL_ERROR_SEE_HERE: ' . $curlError,
                    'size' => 0, 'sizeFormatted' => '0 B',
                    'isDirectory' => false, 'permissions' => '---',
                    'modified' => date('Y-m-d H:i:s'), 'type' => 'file'
                ];
            }
        }
        
        if ($list) {
            foreach ($list as $line) {
                $parsed = parseFtpLine($line);
                if ($parsed && $parsed['name'] !== '.' && $parsed['name'] !== '..') $files[] = $parsed;
            }
        }
        ftp_close($conn);
    }
}

sendSuccess(['files' => $files, 'path' => $path, 'count' => count($files), 'raw' => $list ?? []]);

function parseFtpLine($line) {
    if (empty($line) || preg_match('/^total/', $line)) return null;
    
    // UNIX style: drwxr-xr-x   2 user  group    4096 Jan  1 12:00 folder
    $unixPattern = '/^([\-dlcbsp])([rwxst\-]{9})\s+(\d+)\s+(\S+)\s+(\S+)\s+(\d+)\s+(\w{3}\s+\d+\s+[\d:]+)\s+(.+)$/';
    if (preg_match($unixPattern, $line, $matches)) {
        $isDir = $matches[1] === 'd';
        return ['name' => $matches[8], 'size' => intval($matches[6]), 'sizeFormatted' => formatFileSize(intval($matches[6])),
            'isDirectory' => $isDir, 'permissions' => $matches[2], 'modified' => $matches[7], 'type' => $isDir ? 'directory' : getFileType($matches[8])];
    }

    // DOS style: 11-05-21  12:20PM       <DIR>          Folder Name
    // DOS style: 11-05-21  12:20PM               1024 file name.txt
    $dosPattern = '/^(\d{2}-\d{2}-\d{2,4}\s+\d{2}:\d{2}[AM|PM|am|pm]{2})\s+(<DIR>|\d+)\s+(.+)$/';
    if (preg_match($dosPattern, $line, $matches)) {
        $isDir = strtoupper($matches[2]) === '<DIR>';
        $size = $isDir ? 0 : intval($matches[2]);
        return ['name' => trim($matches[3]), 'size' => $size, 'sizeFormatted' => formatFileSize($size),
            'isDirectory' => $isDir, 'permissions' => $isDir ? 'drwxr-xr-x' : '-rw-r--r--', 'modified' => $matches[1], 'type' => $isDir ? 'directory' : getFileType($matches[3])];
    }

    return null;
}
function getFileType($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $types = ['jpg'=>'image','jpeg'=>'image','png'=>'image','gif'=>'image','pdf'=>'document','txt'=>'text','js'=>'code','html'=>'code','css'=>'code','zip'=>'archive','mp3'=>'audio','mp4'=>'video','csv'=>'spreadsheet','doc'=>'document'];
    return $types[$ext] ?? 'file';
}
function formatPerms($mode) {
    $perms = '';
    for ($i = 8; $i >= 0; $i--) $perms .= ($mode & (1 << $i)) ? (($i % 3 == 2) ? 'r' : (($i % 3 == 1) ? 'w' : 'x')) : '-';
    return $perms;
}
?>
