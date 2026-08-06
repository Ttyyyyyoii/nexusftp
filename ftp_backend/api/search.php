<?php
require_once __DIR__ . '/../config.php';
$data = getInput();
$sessionId = $data['sessionId'] ?? '';
$startPath = $data['startPath'] ?? '/';
$query = $data['query'] ?? '';
$maxDepth = intval($data['maxDepth'] ?? 3);
$caseSensitive = $data['caseSensitive'] ?? false;
$includeDirectories = $data['includeDirectories'] ?? true;

if (empty($sessionId) || empty($query)) sendError('Session ID and query are required');
$session = loadSession($sessionId);
if (!$session) sendError('Invalid or expired session', 401);

// Cap depth for security
$maxDepth = min($maxDepth, 6);

$results = [];
$password = decryptPassword($session['password']);

function searchFTP($conn, $path, $query, $depth, $maxDepth, $caseSensitive, $includeDirectories, &$results) {
    if ($depth > $maxDepth || count($results) >= 200) return;

    $list = @ftp_rawlist($conn, '-al ' . $path);
    if (!$list) return;

    foreach ($list as $line) {
        if (empty($line) || preg_match('/^total/', $line)) continue;

        $unixPattern = '/^([\-dlcbsp])([rwxst\-]{9})\s+(\d+)\s+(\S+)\s+(\S+)\s+(\d+)\s+(\w{3}\s+\d+\s+[\d:]+)\s+(.+)$/';
        if (!preg_match($unixPattern, $line, $matches)) continue;

        $name = trim($matches[8]);
        if ($name === '.' || $name === '..') continue;

        $isDir = $matches[1] === 'd';
        $fullPath = rtrim($path, '/') . '/' . $name;

        $nameToCheck = $caseSensitive ? $name : strtolower($name);
        $queryToCheck = $caseSensitive ? $query : strtolower($query);

        if (strpos($nameToCheck, $queryToCheck) !== false) {
            if (!$isDir || $includeDirectories) {
                $results[] = [
                    'name' => $name,
                    'path' => $fullPath,
                    'isDirectory' => $isDir,
                    'size' => intval($matches[6]),
                    'sizeFormatted' => formatFileSize(intval($matches[6])),
                    'modified' => $matches[7]
                ];
            }
        }

        if ($isDir && $depth < $maxDepth) {
            searchFTP($conn, $fullPath, $query, $depth + 1, $maxDepth, $caseSensitive, $includeDirectories, $results);
        }
    }
}

function searchSFTP($sftp, $path, $query, $depth, $maxDepth, $caseSensitive, $includeDirectories, &$results) {
    if ($depth > $maxDepth || count($results) >= 200) return;

    $sftpPath = "ssh2.sftp://" . intval($sftp) . $path;
    $dir = @opendir($sftpPath);
    if (!$dir) return;

    while (($file = readdir($dir)) !== false) {
        if ($file === '.' || $file === '..') continue;
        $fullPath = rtrim($path, '/') . '/' . $file;
        $stat = @ssh2_sftp_stat($sftp, $fullPath);
        if (!$stat) continue;

        $isDir = ($stat['mode'] & 0040000) === 0040000;
        $nameToCheck = $caseSensitive ? $file : strtolower($file);
        $queryToCheck = $caseSensitive ? $query : strtolower($query);

        if (strpos($nameToCheck, $queryToCheck) !== false) {
            if (!$isDir || $includeDirectories) {
                $results[] = [
                    'name' => $file,
                    'path' => $fullPath,
                    'isDirectory' => $isDir,
                    'size' => $stat['size'],
                    'sizeFormatted' => formatFileSize($stat['size']),
                    'modified' => date('Y-m-d H:i', $stat['mtime'])
                ];
            }
        }

        if ($isDir && $depth < $maxDepth) {
            searchSFTP($sftp, $fullPath, $query, $depth + 1, $maxDepth, $caseSensitive, $includeDirectories, $results);
        }
    }
    closedir($dir);
}

if ($session['type'] === 'sftp') {
    $connection = ssh2_connect($session['host'], $session['port']);
    ssh2_auth_password($connection, $session['username'], $password);
    $sftp = ssh2_sftp($connection);
    searchSFTP($sftp, $startPath, $query, 0, $maxDepth, $caseSensitive, $includeDirectories, $results);
} else {
    $timeout = 30;
    if ($session['type'] === 'ftps' || $session['type'] === 'ftpse') {
        $conn = ftp_ssl_connect($session['host'], $session['port'], $timeout);
    } else {
        $conn = ftp_connect($session['host'], $session['port'], $timeout);
    }
    if ($conn && ftp_login($conn, $session['username'], $password)) {
        ftp_pasv($conn, $session['passive'] ?? true);
        searchFTP($conn, $startPath, $query, 0, $maxDepth, $caseSensitive, $includeDirectories, $results);
        ftp_close($conn);
    }
}

sendSuccess(['results' => $results, 'count' => count($results), 'query' => $query]);
?>
