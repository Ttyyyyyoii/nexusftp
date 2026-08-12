<?php
require_once __DIR__ . '/../config.php';
$data = getInput();
$sessionId = $data['sessionId'] ?? '';
$path = $data['path'] ?? '/';
$items = $data['items'] ?? [];
$stream = $data['stream'] ?? false;

if (empty($items)) {
    $name = $data['name'] ?? '';
    $isDirectory = $data['isDirectory'] ?? false;
    if (!empty($name)) {
        $items[] = ['name' => $name, 'isDirectory' => $isDirectory];
    }
}

if ($stream) {
    header('Content-Type: text/event-stream', true);
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    while (ob_get_level() > 0) ob_end_flush();
}

function sendStreamProgress($msg) {
    global $stream;
    if ($stream) {
        echo "data: " . json_encode(['status' => 'progress', 'message' => $msg]) . "\n\n";
        echo str_pad('', 4096) . "\n";
        @flush();
    }
}

if (empty($sessionId) || empty($items)) sendError('Session ID and items are required');
$session = loadSession($sessionId);
if (!$session) sendError('Invalid or expired session', 401);

$password = decryptPassword($session['password']);

try {
    if ($session['type'] === 'sftp') {
        $connection = ssh2_connect($session['host'], $session['port']);
        ssh2_auth_password($connection, $session['username'], $password);
        $sftp = ssh2_sftp($connection);
        
        foreach ($items as $item) {
            $name = $item['name'];
            $isDirectory = $item['isDirectory'] ?? false;
            $fullPath = rtrim($path, '/') . '/' . ltrim($name, '/');
            sendStreamProgress("Suppression de $name...");
            if ($isDirectory) deleteSFTPDir($sftp, $fullPath);
            else ssh2_sftp_unlink($sftp, $fullPath);
        }
    } else {
        $timeout = 30;
        if ($session['type'] === 'ftps' || $session['type'] === 'ftpse') $conn = ftp_ssl_connect($session['host'], $session['port'], $timeout);
        else $conn = ftp_connect($session['host'], $session['port'], $timeout);
        ftp_login($conn, $session['username'], $password);
        ftp_pasv($conn, $session['passive'] ?? true);

        $deleteLog = [];
        $deleteLog['timestamp'] = date('Y-m-d H:i:s');

        foreach ($items as $item) {
            $name = $item['name'];
            $isDirectory = $item['isDirectory'] ?? false;
            $fullPath = rtrim($path, '/') . '/' . ltrim($name, '/');
            error_clear_last();

            $deleteLog['target'] = $fullPath;
            $deleteLog['is_directory_flag_from_client'] = $isDirectory;

            sendStreamProgress("Suppression de $name...");

            if ($isDirectory) {
                $result = deleteFTPDir($conn, $fullPath, $session, $password, $deleteLog);
                file_put_contents(__DIR__ . '/debug_delete.log', print_r($deleteLog, true));
                if (!$result) {
                    $lastReason = !empty($deleteLog['failures']) ? end($deleteLog['failures']) : 'raison inconnue';
                    throw new Exception("Failed to delete directory: " . $lastReason);
                }
            } else {
                $del = @ftp_delete($conn, $fullPath);
                $deleteLog['ftp_delete_absolute_result'] = $del;

                if (!$del) {
                    // Fallback using raw FTP command
                    $raw = @ftp_raw($conn, 'DELE ' . $fullPath);
                    $deleteLog['ftp_raw_delete'] = $raw;
                    if (is_array($raw) && count($raw) > 0) {
                        $lastLine = end($raw);
                        if (preg_match('/^2\d\d/', $lastLine)) {
                            $del = true;
                        } else {
                            $deleteLog['failures'][] = "Raw DELE failed: $lastLine";
                        }
                    }
                }

                if (!$del) {
                    throw new Exception("Failed to delete file (permissions or doesn't exist): $name");
                }
            }
        }
        @ftp_close($conn);
    }

    if ($stream) {
        echo "data: " . json_encode(array_merge(['success' => true, 'timestamp' => date('c')], ['message' => 'Items deleted'])) . "\n\n";
        @flush();
    } else {
        sendSuccess(['message' => 'Items deleted']);
    }
} catch (Exception $e) {
    if ($stream) {
        echo "data: " . json_encode(['success' => false, 'message' => 'Failed to delete: ' . $e->getMessage(), 'timestamp' => date('c')]) . "\n\n";
        @flush();
    } else {
        sendError('Failed to delete: ' . $e->getMessage(), 500);
    }
}

// Execute une commande FTP brute (DELE, RMD, etc) via cURL, en pointant l'URL
// sur la racine du serveur. cURL execute la commande CURLOPT_QUOTE AVANT toute
// autre operation, donc meme si l'URL elle-meme fait ensuite un petit listing de
// la racine, la commande demandee (suppression) a deja ete executee a ce moment-la.
// On reutilise ici les memes options qui ont deja fait leurs preuves pour le
// listing et l'upload dans cet environnement (SKIP_PASV_IP, IPv4, pas d'EPSV/EPRT).
function curlFtpCommand($session, $password, $command) {
    if (!function_exists('curl_init')) {
        return ['success' => false, 'error' => 'cURL non disponible sur ce serveur PHP'];
    }
    $ch = curl_init();
    $protocol = ($session['type'] === 'ftps' || $session['type'] === 'ftpse') ? 'ftps://' : 'ftp://';
    $url = $protocol . $session['host'] . ':' . $session['port'] . '/';

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERPWD, $session['username'] . ':' . $password);
    curl_setopt($ch, CURLOPT_QUOTE, [$command]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_FTP_USE_EPSV, false);
    curl_setopt($ch, CURLOPT_FTP_USE_EPRT, false);
    curl_setopt($ch, CURLOPT_FTP_SKIP_PASV_IP, true);

    if ($session['type'] === 'ftps' || $session['type'] === 'ftpse') {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }

    curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    curl_close($ch);

    return ['success' => ($errno === 0), 'errno' => $errno, 'error' => $error, 'command' => $command];
}

function robust_nlist(&$conn, $path, $session, $password) {
    // cURL en premier (pas ftp_nlist) - deja confirme fiable dans cet environnement.
    if (function_exists('curl_init')) {
        $ch = curl_init();
        $protocol = ($session['type'] === 'ftps' || $session['type'] === 'ftpse') ? 'ftps://' : 'ftp://';
        $url = $protocol . $session['host'] . ":" . $session['port'] . '/' . ltrim($path, '/');
        if (substr($url, -1) !== '/') $url .= '/';

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERPWD, $session['username'] . ":" . $password);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_FTP_USE_EPSV, false);
        curl_setopt($ch, CURLOPT_FTP_USE_EPRT, false);
        curl_setopt($ch, CURLOPT_FTP_SKIP_PASV_IP, true);

        if ($session['type'] === 'ftps' || $session['type'] === 'ftpse') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $curlOutput = curl_exec($ch);
        curl_close($ch);

        if ($curlOutput !== false) {
            $lines = explode("\n", trim(str_replace("\r", "", $curlOutput)));
            $files = [];
            foreach ($lines as $line) {
                if (empty($line) || preg_match('/^total/', $line)) continue;
                $unixPattern = '/^([\-dlcbsp])([rwxst\-]{9})\s+(\d+)\s+(\S+)\s+(\S+)\s+(\d+)\s+(\w{3}\s+\d+\s+[\d:]+)\s+(.+)$/';
                if (preg_match($unixPattern, $line, $matches)) {
                    $files[] = $matches[8];
                    continue;
                }
                $dosPattern = '/^(\d{2}-\d{2}-\d{2,4}\s+\d{2}:\d{2}\s*[AaPp][Mm])\s+(<DIR>|\d+)\s+(.+)$/';
                if (preg_match($dosPattern, $line, $matches)) {
                    $files[] = trim($matches[3]);
                    continue;
                }
            }
            return $files;
        }
    }

    // Secours si cURL indisponible
    $list = @ftp_nlist($conn, $path);
    if (@ftp_pwd($conn) === false) {
        @ftp_close($conn);
        $conn = ftp_connect($session['host'], $session['port'], 30);
        ftp_login($conn, $session['username'], $password);
        ftp_pasv($conn, $session['passive'] ?? true);
    }
    if ($list !== false && is_array($list)) return $list;

    return false;
}

// Determine si un chemin est un dossier ou un fichier reel.
function isRemoteDirectory($conn, $fullPath) {
    $size = @ftp_size($conn, $fullPath);
    if ($size >= 0) return false;
    $currentPwd = @ftp_pwd($conn);
    $isDir = @ftp_chdir($conn, $fullPath);
    if ($currentPwd) @ftp_chdir($conn, $currentPwd);
    return (bool) $isDir;
}

function deleteFTPDir(&$conn, $path, $session, $password, &$deleteLog) {
    $files = robust_nlist($conn, $path, $session, $password);
    $deleteLog['listings'][$path] = $files;

    if (is_array($files)) {
        foreach ($files as $file) {
            $bn = basename($file);
            if ($bn === '.' || $bn === '..') continue;

            $entryPath = rtrim($path, '/') . '/' . $bn;
            sendStreamProgress("Suppression de $bn...");

            error_clear_last();
            // Chemin absolu direct, plus de chdir prealable.
            $del = @ftp_delete($conn, $entryPath);

            if ($del === false) {
                if (isRemoteDirectory($conn, $entryPath)) {
                    deleteFTPDir($conn, $entryPath, $session, $password, $deleteLog);
                    continue;
                }

                // Fichier reel dont la suppression native a echoue: fallback ftp_raw.
                $raw = @ftp_raw($conn, 'DELE ' . $entryPath);
                $deleteLog['ftp_raw_delete_attempts'][] = $raw;

                $success = false;
                if (is_array($raw) && count($raw) > 0) {
                    $lastLine = end($raw);
                    if (preg_match('/^2\d\d/', $lastLine)) {
                        $success = true;
                    } else {
                        $deleteLog['failures'][] = "Raw DELE failed on '$entryPath': $lastLine";
                    }
                }

                if (!$success) {
                    $reason = "Impossible de supprimer le fichier '$entryPath'";
                    $deleteLog['failures'][] = $reason;
                }
            }
        }
    }

    error_clear_last();
    $rmdir = @ftp_rmdir($conn, $path);
    $deleteLog['ftp_rmdir_absolute_result'][$path] = $rmdir;

    if ($rmdir === false) {
        $raw = @ftp_raw($conn, 'RMD ' . $path);
        $deleteLog['ftp_raw_rmdir_attempts'][$path] = $raw;
        
        if (is_array($raw) && count($raw) > 0) {
            $lastLine = end($raw);
            if (preg_match('/^2\d\d/', $lastLine)) {
                $rmdir = true;
            } else {
                $reason = "Impossible de supprimer le dossier '$path' : $lastLine";
                $deleteLog['failures'][] = $reason;
            }
        }

        if (!$rmdir && empty($deleteLog['failures'])) {
            $reason = "Impossible de supprimer le dossier '$path' (raison inconnue, non vide ?)";
            $deleteLog['failures'][] = $reason;
        }
    }
    return $rmdir;
}

function deleteSFTPDir($sftp, $path) {
    $dir = @opendir("ssh2.sftp://" . intval($sftp) . $path);
    if ($dir) { 
        while (($file = readdir($dir)) !== false) { 
            if ($file === '.' || $file === '..') continue; 
            $full = $path.'/'.$file; 
            sendStreamProgress("Suppression de $file...");
            $stat = ssh2_sftp_stat($sftp, $full); 
            if ($stat && ($stat['mode'] & 0040000)) deleteSFTPDir($sftp, $full); 
            else ssh2_sftp_unlink($sftp, $full); 
        } 
        closedir($dir); 
    }
    ssh2_sftp_rmdir($sftp, $path);
}
?>